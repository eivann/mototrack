<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Product-picture storage for NEW uploads only.
 *
 * Pictures stay ordinary files on the Laravel "public" disk; only the path is stored in the database
 * (no BLOBs, no Base64). A new upload is decoded, auto-rotated from EXIF, shrunk so the longest side
 * is at most MAX_SIDE (never enlarged), re-encoded as lossy WebP with its alpha channel kept, and
 * written as a small set of width-suffixed files:
 *
 *     products/p_<32 hex>-<width>.webp     the largest file (this is what image_path stores)
 *     products/p_<32 hex>-640.webp         only when the largest is wider than 640
 *     products/p_<32 hex>-320.webp         only when the largest is wider than 320
 *
 * The one exception: a picture that is already small (longest side <= MAX_SIDE) and already smaller
 * than its WebP re-encoding is kept byte-for-byte (jpg/png/webp, same suffix scheme) rather than made
 * bigger, unless it needs EXIF rotation or carries GPS data (then it is always re-encoded).
 *
 * Unusable NEW uploads are refused with a validation error on the `image` field (nothing is stored): not a
 * readable JPEG/PNG/WebP, more than MAX_PIXELS, a JPEG cut off before its end marker, or data that cannot be
 * decoded. Legacy files are never inspected or changed.
 *
 * File names are random and never reused, so they can be cached "forever". Legacy rows, whose paths do
 * not match that pattern, are never touched or rewritten. Anything this service cannot safely decode
 * (no GD/WebP support, corrupt or huge image, animated file) is stored exactly as before.
 */
class ProductImageService
{
    public const MAX_SIDE = 960;
    public const VARIANT_WIDTHS = [320, 640];
    public const QUALITY = 82;
    /** Decoded size cap. GD allocates outside PHP's memory_limit: measured peak RSS was ~146 MB for 16 MP, so 10 MP keeps one upload around 100 MB. Larger new uploads are refused with a validation error (legacy files are never touched). */
    public const MAX_PIXELS = 10_000_000;

    public const PATH_PATTERN = '#^products/(p_[a-f0-9]{32})-(\d{2,4})\.(webp|jpg|png)$#';

    public function __construct(private string $disk = 'public')
    {
    }

    public static function canOptimize(): bool
    {
        return function_exists('imagecreatefromstring') && function_exists('imagewebp') && function_exists('imagecopyresampled');
    }

    /** Store an uploaded product picture and return the path to keep in image_path. */
    public function store(UploadedFile $file): string
    {
        // New uploads only: a clearly unusable picture is refused with a validation error instead of being kept as-is.
        $this->assertUsable($file);

        try {
            $path = $this->optimize($file);
            if ($path !== null) {
                return $path;
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('Product image optimisation skipped: '.$e->getMessage());
        }

        // Exceptional file: keep the previous behaviour untouched.
        return $file->store('products', $this->disk);
    }

    /** Remove a stored picture. Optimised pictures take their sibling sizes with them; legacy paths behave as before. */
    public function delete(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        $disk = Storage::disk($this->disk);

        if (preg_match(self::PATH_PATTERN, $path, $m)) {
            foreach ($disk->files('products') as $f) {
                if (str_starts_with($f, 'products/'.$m[1].'-')) {
                    $disk->delete($f);
                }
            }
            return;
        }

        $disk->delete($path);
    }

    /** Available widths => path for an optimised picture (ascending), or [] for anything else. */
    public function variants(?string $path): array
    {
        if (!$path || !preg_match(self::PATH_PATTERN, $path, $m)) {
            return [];
        }

        $disk = Storage::disk($this->disk);
        if (!$disk->exists($path)) {
            return []; // the largest file is gone: advertise nothing rather than a dead link
        }
        $out = [];
        foreach (self::VARIANT_WIDTHS as $w) {
            if ($w < (int) $m[2] && $disk->exists("products/{$m[1]}-{$w}.webp")) {
                $out[$w] = "products/{$m[1]}-{$w}.webp";
            }
        }
        $out[(int) $m[2]] = $path;
        ksort($out);

        return $out;
    }

    /**
     * READ-ONLY estimate for the backfill audit: what the largest stored file would weigh for this image
     * file if it were uploaded today. Nothing is written. Returns null when the file is not optimisable.
     *
     * @return array{width:int,height:int,bytes:int,kept_original:bool}|null
     */
    public function estimate(string $realPath): ?array
    {
        if (!self::canOptimize() || !is_file($realPath)) {
            return null;
        }
        $info = @getimagesize($realPath);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }
        $raw = file_get_contents($realPath);
        $src = @imagecreatefromstring($raw);
        if (!$src) {
            return null;
        }
        try {
            if (!imageistruecolor($src)) {
                imagepalettetotruecolor($src);
            }
            $w = imagesx($src);
            $h = imagesy($src);
            $scale = min(1, self::MAX_SIDE / max($w, $h));
            $tw = max(1, (int) round($w * $scale));
            $bytes = $this->encode($src, $w, $h, $tw);
            if ($bytes === null) {
                return null;
            }
            $keep = $scale === 1 && strlen($bytes) >= strlen($raw);

            return ['width' => $tw, 'height' => max(1, (int) round($h * $tw / $w)), 'bytes' => $keep ? strlen($raw) : strlen($bytes), 'kept_original' => $keep];
        } finally {
            imagedestroy($src);
        }
    }

    /**
     * Header-level checks that need almost no memory (nothing is decoded here): readable image of a supported type,
     * not beyond MAX_PIXELS, and, for JPEG, a complete file. Throws a validation error on the `image` field.
     */
    private function assertUsable(UploadedFile $file): void
    {
        $real = $file->getRealPath();
        $info = $real ? @getimagesize($real) : false;
        if (!$info || $info[0] < 1 || $info[1] < 1) {
            $this->reject('This file could not be read as a picture. It may be damaged. Please upload a JPEG, PNG or WebP image.');
        }
        if (!in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            $this->reject('Only JPEG, PNG or WebP pictures can be uploaded.');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            $this->reject(sprintf(
                'This picture is %s x %s pixels (%.1f megapixels), which is too large to process safely. Please resize it to at most %d megapixels (about 3,100 x 3,100 pixels); the 2 MB file-size limit is unchanged.',
                number_format($info[0]), number_format($info[1]), $info[0] * $info[1] / 1e6, self::MAX_PIXELS / 1e6
            ));
        }
        if ($info[2] === IMAGETYPE_JPEG && !self::jpegIsComplete($real)) {
            $this->reject('This JPEG is incomplete (the file looks cut off, for example by an interrupted copy or download). Please export or copy it again and upload the complete picture.');
        }
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['image' => $message]);
    }

    /**
     * True when the JPEG reaches its End-Of-Image marker. libjpeg/GD silently decode a cut-off JPEG (the missing part
     * turns grey) without any warning, so the marker structure is walked instead. Data after EOI is ignored.
     */
    public static function jpegIsComplete(string $path): bool
    {
        $fh = @fopen($path, 'rb');
        if (!$fh) {
            return false;
        }
        try {
            if (fread($fh, 2) !== "\xFF\xD8") {
                return false;
            }
            while (!feof($fh)) {
                // find next marker (skipping entropy-coded data, byte stuffing FF00, restart markers D0-D7, fill FF)
                $b = fread($fh, 1);
                if ($b === '' || $b === false) {
                    return false;
                }
                if ($b !== "\xFF") {
                    continue;
                }
                do {
                    $m = fread($fh, 1);
                } while ($m === "\xFF");
                if ($m === '' || $m === false) {
                    return false;
                }
                $c = ord($m);
                if ($c === 0x00 || ($c >= 0xD0 && $c <= 0xD7) || $c === 0x01) {
                    continue;
                }
                if ($c === 0xD9) {
                    return true;
                }
                if ($c === 0xD8) {
                    continue;
                }
                $len = fread($fh, 2);
                if (strlen($len) < 2) {
                    return false;
                }
                $n = unpack('n', $len)[1];
                if ($n < 2 || fseek($fh, $n - 2, SEEK_CUR) !== 0) {
                    return false;
                }
            }

            return false;
        } finally {
            fclose($fh);
        }
    }

    private function optimize(UploadedFile $file): ?string
    {
        if (!self::canOptimize()) {
            return null;
        }

        $real = $file->getRealPath();
        $info = $real ? @getimagesize($real) : false;
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }
        if ($info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }

        $raw = file_get_contents($real);
        // Animated WebP cannot be flattened safely by GD: keep the original.
        if ($info[2] === IMAGETYPE_WEBP && str_contains(substr($raw, 0, 64), 'ANIM')) {
            return null;
        }

        $src = @imagecreatefromstring($raw);
        if (!$src) {
            // The header was fine and GD is available, yet the data cannot be decoded: confirmed corruption.
            $this->reject('This picture is damaged or incomplete and could not be decoded. Please export or copy it again and upload the complete picture.');
        }
        $original = $raw;
        unset($raw);

        $exif = ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) ? (@exif_read_data($real) ?: []) : [];
        $hasGps = (bool) array_filter(array_keys($exif), fn ($k) => str_starts_with((string) $k, 'GPS'));
        $needsRotation = in_array((int) ($exif['Orientation'] ?? 1), [3, 6, 8], true);

        try {
            if (!imageistruecolor($src)) {
                imagepalettetotruecolor($src);
            }
            $src = $this->autoRotate($src, (int) ($exif['Orientation'] ?? 1));

            $w = imagesx($src);
            $h = imagesy($src);
            $scale = min(1, self::MAX_SIDE / max($w, $h));
            $largestW = max(1, (int) round($w * $scale));

            $widths = [$largestW];
            foreach (self::VARIANT_WIDTHS as $vw) {
                if ($vw < $largestW) {
                    $widths[] = $vw;
                }
            }
            sort($widths);

            $stem = 'p_'.bin2hex(random_bytes(16));
            $disk = Storage::disk($this->disk);
            $written = [];

            try {
                foreach ($widths as $tw) {
                    $bytes = $this->encode($src, $w, $h, $tw);
                    $ext = 'webp';
                    if ($bytes !== null && $tw === $largestW && $scale === 1 && !$hasGps && !$needsRotation
                        && strlen($bytes) >= strlen($original)) {
                        // Already small and already lean: keep the original file rather than inflate it.
                        $bytes = $original;
                        $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2]];
                    }
                    if ($bytes === null) {
                        foreach ($written as $p) {
                            $disk->delete($p);
                        }
                        return null;
                    }
                    $p = "products/{$stem}-{$tw}.{$ext}";
                    if ($disk->put($p, $bytes) === false) {
                        throw new \RuntimeException("could not write {$p}");
                    }
                    $written[] = $p;
                }
            } catch (\Throwable $e) {
                // Never leave a half-written set behind (disk full, permissions); store() then falls back to the plain upload.
                foreach ($written as $p) {
                    $disk->delete($p);
                }
                throw $e;
            }

            return end($written);
        } finally {
            imagedestroy($src);
        }
    }

    private function encode($src, int $w, int $h, int $targetW): ?string
    {
        $targetH = max(1, (int) round($h * $targetW / $w));
        $dst = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));

        if ($targetW === $w) {
            imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);
        } else {
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $w, $h);
        }

        ob_start();
        $ok = imagewebp($dst, null, self::QUALITY);
        $bytes = ob_get_clean();
        imagedestroy($dst);

        return ($ok && is_string($bytes) && $bytes !== '') ? $bytes : null;
    }

    private function autoRotate($img, int $orientation)
    {
        if (!function_exists('imagerotate')) {
            return $img;
        }
        $deg = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($deg === 0) {
            return $img;
        }
        $rot = imagerotate($img, $deg, 0);
        if (!$rot) {
            return $img;
        }
        imagedestroy($img);

        return $rot;
    }
}
