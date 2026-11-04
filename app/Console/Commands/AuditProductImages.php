<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Services\ProductImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * READ-ONLY. Lists the product pictures already on record and estimates what re-encoding them with
 * ProductImageService would save. It never writes, renames, deletes or updates anything (no files,
 * no database rows). It is the "dry run" for a future backfill that needs the owner's approval.
 */
class AuditProductImages extends Command
{
    protected $signature = 'products:images-audit {--limit=0 : Only look at the first N pictures} {--json : Machine-readable output}';

    protected $description = 'Read-only audit of existing product pictures and the estimated saving from optimising them (changes nothing)';

    public function handle(ProductImageService $svc): int
    {
        $rows = [];
        $totalNow = $totalEst = $unresolved = $skipped = $already = $downscale = $keep = $smaller = 0;
        $limit = (int) $this->option('limit');

        $query = InventoryItem::query()->whereNotNull('image_path')->where('image_path', '!=', '')->orderBy('id');
        foreach ($query->cursor() as $item) {
            if ($limit > 0 && count($rows) >= $limit) {
                break;
            }
            $path = ltrim((string) $item->image_path, '/');
            if (preg_match('#^https?://#', $path)) {
                continue; // external URL: not ours to measure
            }
            if (preg_match(ProductImageService::PATH_PATTERN, $path)) {
                $already++;
                continue;
            }
            $abs = collect([public_path('images/'.$path), public_path($path), Storage::disk('public')->path($path)])->first(fn ($p) => is_file($p));
            if (!$abs) {
                $unresolved++;
                continue;
            }
            $now = filesize($abs);
            $est = $svc->estimate($abs);
            if (!$est) {
                $skipped++;
                $totalNow += $now;
                $totalEst += $now;
                continue;
            }
            $dim = @getimagesize($abs);
            $rows[] = ['id' => $item->id, 'path' => $path, 'bytes' => $now, 'w' => $dim[0] ?? 0, 'h' => $dim[1] ?? 0, 'est_bytes' => $est['bytes'], 'est_w' => $est['width']];
            $totalNow += $now;
            $totalEst += $est['bytes'];
            $downscale += max($dim[0] ?? 0, $dim[1] ?? 0) > ProductImageService::MAX_SIDE ? 1 : 0;
            $keep += $est['kept_original'] ? 1 : 0;
            $smaller += $est['bytes'] < $now ? 1 : 0;
        }

        $summary = ['pictures_measured' => count($rows), 'already_optimised' => $already, 'file_not_found' => $unresolved, 'not_optimisable' => $skipped,
            'would_be_smaller' => $smaller, 'would_keep_original_unchanged' => $keep, 'would_be_downscaled' => $downscale, 'bytes_now' => $totalNow, 'bytes_estimated' => $totalEst, 'estimated_saving_pct' => $totalNow ? round(100 * (1 - $totalEst / $totalNow), 1) : 0];

        if ($this->option('json')) {
            $this->line(json_encode(['summary' => $summary, 'rows' => $rows], JSON_PRETTY_PRINT));
        } else {
            $this->table(['Item', 'Path', 'Bytes now', 'Dimensions', 'Est. bytes', 'Est. width'], array_map(fn ($r) => [$r['id'], $r['path'], $r['bytes'], $r['w'].'x'.$r['h'], $r['est_bytes'], $r['est_w']], $rows));
            foreach ($summary as $k => $v) {
                $this->line(str_pad($k, 22).' '.$v);
            }
            $this->comment('Read-only: nothing was written, renamed, deleted or updated.');
        }

        return self::SUCCESS;
    }
}
