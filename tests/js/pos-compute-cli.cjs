// Test helper: evaluates public/js/pos-totals.js exactly as the POS page loads it and prints the result
// of MotoTrackPos.compute() for the JSON read from stdin: {"lines": [...], "tendered": "..."}.
// Used by tests/Feature/Sales/PosTotalsTest.php (input via stdin, so no shell quoting on any OS).
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const box = {};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/pos-totals.js'), 'utf8'), box);
const pos = box.MotoTrackPos;

const input = JSON.parse(fs.readFileSync(0, 'utf8'));
const t = pos.compute(input.lines, input.tendered);
process.stdout.write(JSON.stringify({
    total: t.totalCents,
    change: t.changeCents,
    canCheckout: t.canCheckout,
    problem: t.problem,
    expected: pos.centsToDecimalString(t.totalCents),
    tendered: t.tenderValid ? pos.centsToDecimalString(t.tenderedCents) : null,
}));
