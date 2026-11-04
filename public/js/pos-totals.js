/*
 * MotoTrack POS totals — the single calculation used by the POS screen for every amount it shows
 * (line totals, subtotal, total, tendered, change / shortfall) and for what it submits.
 *
 * Everything is computed in whole centavos so the screen matches the receipt exactly: the server stores
 * each line as price × quantity (2 decimals), the receipt total is the sum of those lines, and the change
 * is tendered − total rounded to 2 decimals (App\Actions\CommitOrder + SaleController::storeSale).
 * Floating-point peso arithmetic (e.g. 0.1 + 0.2) can never leak into the display this way.
 *
 * No discounts exist in the MotoTrack POS, so the total always equals the subtotal.
 * Loaded as a plain browser script (window.MotoTrackPos); tests evaluate the same file in a sandbox.
 */
(function (root, factory) {
    root.MotoTrackPos = factory();
})(typeof globalThis !== 'undefined' ? globalThis : self, function () {
    'use strict';

    // Same ceiling as StoreSaleRequest (amount_tendered max:999999).
    var MAX_TENDER_CENTS = 99999900;
    // Same ceiling as CommitOrder (at most 9,999 units of one item per transaction).
    var MAX_LINE_QUANTITY = 9999;
    // RD2-R2.4: standard Philippine VAT rate; prices are VAT-inclusive, so VAT is extracted.
    var VAT_RATE_PERCENT = 12;

    /** Highest quantity one cart line may hold: the stock on hand, never above the server limit. */
    function maxQuantity(stock) {
        var available = Math.floor(Number(stock));
        if (!isFinite(available) || available < 0) available = 0;
        return Math.min(available, MAX_LINE_QUANTITY);
    }

    /** Peso amount (number or numeric string such as "1,250.50") -> integer centavos, or null. */
    function toCents(value) {
        if (value === null || value === undefined) return null;
        var text = String(value).replace(/,/g, '').trim();
        if (text === '' || !/^-?\d*(\.\d*)?$/.test(text) || text === '.' || text === '-') return null;
        var amount = Number(text);
        if (!isFinite(amount)) return null;
        return Math.round(amount * 100);
    }

    /** True when a typed amount has at most 2 decimals (the server rejects more). */
    function hasValidDecimals(value) {
        if (value === null || value === undefined || value === '') return true;
        var text = String(value).replace(/,/g, '').trim();
        var parts = text.split('.');
        return parts.length < 2 || parts[1].length <= 2;
    }

    /** Positive whole quantity, otherwise 0 (blank or invalid input contributes nothing). */
    function toQuantity(value) {
        var q = Number(value);
        return Number.isInteger(q) && q > 0 ? q : 0;
    }

    function formatCents(cents) {
        var negative = cents < 0;
        var abs = Math.abs(Math.round(cents));
        var pesos = Math.floor(abs / 100).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        var centavos = String(abs % 100).padStart(2, '0');
        return (negative ? '-' : '') + pesos + '.' + centavos;
    }

    /** "1234.50" style string for form submission (matches the server's 2-decimal rule). */
    function centsToDecimalString(cents) {
        var abs = Math.abs(cents);
        return (cents < 0 ? '-' : '') + Math.floor(abs / 100) + '.' + String(abs % 100).padStart(2, '0');
    }

    /**
     * @param {Array<{price: (number|string), quantity: (number|string)}>} lines
     * @param {(number|string|null)} tendered  Cash received as typed; blank means "not entered yet".
     */
    function compute(lines, tendered) {
        var lineTotalsCents = [];
        var subtotalCents = 0;
        var unitCount = 0;
        var invalidLine = false;

        (lines || []).forEach(function (line) {
            var priceCents = toCents(line.price);
            var quantity = toQuantity(line.quantity);
            if (priceCents === null || priceCents < 0) {
                priceCents = 0;
                invalidLine = true;
            }
            // A line must hold 1..limit units, where the limit is the stock shown (when known) capped at 9,999.
            var limit = line.maxStock === undefined || line.maxStock === null ? MAX_LINE_QUANTITY : maxQuantity(line.maxStock);
            if (quantity === 0 || quantity > limit) invalidLine = true;
            var lineCents = priceCents * quantity;
            lineTotalsCents.push(lineCents);
            subtotalCents += lineCents;
            unitCount += quantity;
        });

        var discountCents = 0;
        var totalCents = subtotalCents - discountCents;

        // RD2-R2.4: prices are VAT-inclusive (12%). VAT is EXTRACTED from the total, never added,
        // using the same integer-centavo formula as App\Support\Vat so POS and server agree exactly.
        var vatCents = Math.round(totalCents * VAT_RATE_PERCENT / (100 + VAT_RATE_PERCENT));
        var netCents = totalCents - vatCents;

        var tenderEntered = !(tendered === null || tendered === undefined || String(tendered).trim() === '');
        var tenderedCents = tenderEntered ? toCents(tendered) : null;
        var tenderValid = tenderEntered && tenderedCents !== null && tenderedCents >= 0
            && hasValidDecimals(tendered) && tenderedCents <= MAX_TENDER_CENTS;

        var changeCents = tenderValid ? tenderedCents - totalCents : null;
        var shortCents = changeCents !== null && changeCents < 0 ? -changeCents : 0;

        var problem = null;
        if (!lines || lines.length === 0) problem = 'empty';
        else if (invalidLine) problem = 'quantity';
        else if (!tenderEntered) problem = 'tender_missing';
        else if (!tenderValid) problem = tenderedCents !== null && tenderedCents > MAX_TENDER_CENTS ? 'tender_too_large' : 'tender_invalid';
        else if (shortCents > 0) problem = 'insufficient';

        return {
            lineTotalsCents: lineTotalsCents,
            unitCount: unitCount,
            subtotalCents: subtotalCents,
            discountCents: discountCents,
            totalCents: totalCents,
            vatCents: vatCents,
            netCents: netCents,
            vatRatePercent: VAT_RATE_PERCENT,
            tenderEntered: tenderEntered,
            tenderValid: tenderValid,
            tenderedCents: tenderValid ? tenderedCents : null,
            changeCents: changeCents,
            shortCents: shortCents,
            problem: problem,
            canCheckout: problem === null && totalCents > 0
        };
    }

    return {
        MAX_TENDER_CENTS: MAX_TENDER_CENTS,
        MAX_LINE_QUANTITY: MAX_LINE_QUANTITY,
        maxQuantity: maxQuantity,
        toCents: toCents,
        toQuantity: toQuantity,
        hasValidDecimals: hasValidDecimals,
        formatCents: formatCents,
        centsToDecimalString: centsToDecimalString,
        compute: compute
    };
});
