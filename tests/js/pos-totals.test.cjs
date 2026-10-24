// POS totals unit tests (no dependencies): run with `node --test tests/js/pos-totals.test.cjs`.
// The same file is exercised from PHP by tests/Feature/Sales/PosTotalsTest.php.
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

// Evaluate the exact browser script in an isolated context, as the POS page does.
const sandbox = {};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/pos-totals.js'), 'utf8'), sandbox);
const pos = sandbox.MotoTrackPos;

test('empty cart shows zero amounts and cannot be checked out', () => {
    const t = pos.compute([], '');
    assert.equal(t.subtotalCents, 0);
    assert.equal(t.totalCents, 0);
    assert.equal(t.changeCents, null);
    assert.equal(t.problem, 'empty');
    assert.equal(t.canCheckout, false);
});

test('running total updates with products and quantities, in exact centavos', () => {
    const lines = [{ price: '0.10', quantity: 1 }, { price: '0.20', quantity: 1 }];
    assert.equal(pos.compute(lines, '').totalCents, 30); // never 0.30000000000000004
    const many = [{ price: '1250.50', quantity: 3 }, { price: '99.99', quantity: 7 }];
    const t = pos.compute(many, '');
    assert.deepEqual(Array.from(t.lineTotalsCents), [375150, 69993]);
    assert.equal(t.subtotalCents, 445143);
    assert.equal(t.totalCents, 445143);
    assert.equal(t.unitCount, 10);
    assert.equal(t.discountCents, 0);
});

test('change, exact payment and shortfall', () => {
    const lines = [{ price: '250.00', quantity: 1 }, { price: '100.00', quantity: 1 }];
    assert.equal(pos.compute(lines, '400').changeCents, 5000);
    assert.equal(pos.compute(lines, '400').canCheckout, true);
    assert.equal(pos.compute(lines, '350.00').changeCents, 0);
    assert.equal(pos.compute(lines, '350.00').canCheckout, true);
    const short = pos.compute(lines, '349.99');
    assert.equal(short.shortCents, 1);
    assert.equal(short.problem, 'insufficient');
    assert.equal(short.canCheckout, false);
});

test('blank, invalid, too many decimals and too large tenders are refused', () => {
    const lines = [{ price: '10.00', quantity: 1 }];
    assert.equal(pos.compute(lines, '').problem, 'tender_missing');
    assert.equal(pos.compute(lines, 'abc').problem, 'tender_invalid');
    assert.equal(pos.compute(lines, '10.005').problem, 'tender_invalid');
    assert.equal(pos.compute(lines, '-5').problem, 'tender_invalid');
    assert.equal(pos.compute(lines, '1000000').problem, 'tender_too_large');
    assert.equal(pos.compute(lines, '999999').canCheckout, true);
});

test('blank or invalid quantities contribute nothing and block checkout', () => {
    const t = pos.compute([{ price: '10.00', quantity: '' }, { price: '5.00', quantity: 2 }], '100');
    assert.deepEqual(Array.from(t.lineTotalsCents), [0, 1000]);
    assert.equal(t.problem, 'quantity');
    assert.equal(t.canCheckout, false);
});

test('formatting and submission strings', () => {
    assert.equal(pos.formatCents(123456789), '1,234,567.89');
    assert.equal(pos.formatCents(5), '0.05');
    assert.equal(pos.formatCents(-250), '-2.50');
    assert.equal(pos.centsToDecimalString(445143), '4451.43');
    assert.equal(pos.centsToDecimalString(7), '0.07');
    assert.equal(pos.toCents('1,250.5'), 125050);
});

test('a line is capped at min(stock, 9,999), matching the server limit', () => {
    assert.equal(pos.MAX_LINE_QUANTITY, 9999);
    assert.equal(pos.maxQuantity(12000), 9999);
    assert.equal(pos.maxQuantity(25), 25);
    assert.equal(pos.maxQuantity(-3), 0);
    assert.equal(pos.compute([{ price: '1.00', quantity: 9999, maxStock: 12000 }], '20000').canCheckout, true);
    assert.equal(pos.compute([{ price: '1.00', quantity: 10000, maxStock: 12000 }], '20000').problem, 'quantity');
    assert.equal(pos.compute([{ price: '1.00', quantity: 26, maxStock: 25 }], '100').problem, 'quantity');
    assert.equal(pos.compute([{ price: '1.00', quantity: 10000 }], '20000').problem, 'quantity');
});
