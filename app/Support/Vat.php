<?php

namespace App\Support;

/**
 * RD2-R2.4 — VAT-inclusive breakdown.
 *
 * Confirmed business treatment (project owner): Edmar Motorcycle Parts is VAT-registered and its
 * catalogue/selling prices are ALREADY VAT-inclusive at the Philippine standard rate of 12%. VAT is
 * therefore EXTRACTED from the gross (VAT-inclusive) amount, never added on top:
 *
 *     VAT          = round(gross * 12 / 112)            (to the centavo, half away from zero)
 *     VATable/net  = gross - VAT                        (so net + VAT == gross, always reconciles)
 *
 * All arithmetic is done in integer centavos so binary-float drift can never reach a displayed or
 * compared figure. The same derivation is used for historical and new transactions, so nothing is
 * stored, snapshotted, or backfilled, and no past total ever changes — the breakdown is purely a
 * deterministic view over the gross amount already recorded on each sale.
 *
 * The identical formula in integer centavos is mirrored in public/js/pos-totals.js so the POS screen
 * and the server always agree to the centavo.
 *
 * Scope note: every MotoTrack product is treated as standard-rated (12% VAT-inclusive). The schema has
 * no VAT-exempt / zero-rated classification, so those cases are out of scope until the owner/accountant
 * confirms they exist; this helper is structured so a per-line classification can be layered on later.
 */
final class Vat
{
    /** Standard Philippine VAT rate, as a whole-number percentage. */
    public const RATE_PERCENT = 12;

    /** Divisor used to extract VAT from a VAT-inclusive amount: gross * 12 / (100 + 12). */
    private const INCLUSIVE_DIVISOR = 100 + self::RATE_PERCENT;

    /**
     * Break a VAT-inclusive gross amount into its VATable (net) and VAT components.
     *
     * @return object{gross: float, net: float, vat: float, rate_percent: int}
     *               All monetary values are exact to the centavo; net + vat === gross.
     */
    public static function fromInclusive(float|int|string $grossAmount): object
    {
        $grossCents = (int) round(((float) $grossAmount) * 100);
        $vatCents = (int) round($grossCents * self::RATE_PERCENT / self::INCLUSIVE_DIVISOR);
        $netCents = $grossCents - $vatCents;

        return (object) [
            'gross' => (float) ($grossCents / 100),
            'net' => (float) ($netCents / 100),
            'vat' => (float) ($vatCents / 100),
            'rate_percent' => self::RATE_PERCENT,
        ];
    }
}
