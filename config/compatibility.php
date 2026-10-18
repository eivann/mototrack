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
];
