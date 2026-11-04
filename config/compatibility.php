<?php

return [
    /*
     | Inventory-sourced compatibility (Make/Model catalogue + inventory model map).
     | OFF by default: production behaviour is unchanged until explicitly enabled.
     */
    'inventory_sourced' => (bool) env('COMPAT_INVENTORY_SOURCED', false),

    // Customer Make dropdown (exactly these, in this order). Universal is a special selection.
    'dropdown_makes' => ['Yamaha', 'Honda', 'Suzuki', 'Kawasaki', 'Universal'],

    'result_limit' => 100,

    /*
     | Owner-approved spelling equivalents for the ORIGINAL (flag-off) fitment matcher. Keys and values are in
     | normalised form (lower case, single spaces). Only the listed spellings are treated as the same model;
     | every other model still needs an exact match ("Click 125 FI", "Click 150", "Click155", "Click 1250" are
     | different models). This does not touch owner-entered fitment records or the inventory catalogue.
     */
    'model_aliases' => [
        'click125' => 'click 125',
    ],
];
