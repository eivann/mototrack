<?php
/**
 * Compatibility reference catalogue + inventory model map (version-controlled).
 *
 * COMPLETENESS: this catalogue is NOT claimed to be the complete list of models Edmar supports.
 * It currently holds only models evidenced by existing inventory records. Supported models with zero
 * inventory must be supplied by the owner in 'owner_models' below (never invented here).
 *
 * Component A (reference catalogue): Make -> Model list shown in the customer's Model dropdown,
 *   independent of current inventory.
 * Component B (inventory map): each entry's 'aliases' are the EXACT strings found in
 *   inventory_items.model (case-insensitive, whitespace-collapsed) that mean that model.
 *   Original inventory values are never altered.
 *
 * type 'specific' : a named model/variant.
 * type 'family'   : a general line recorded without a variant (e.g. 'Mio'). Matches ONLY products
 *                   recorded with exactly that string; never expanded to variants.
 *
 * Manufacturer assignments were audited against manufacturer/market sources (e.g. STX and YZF-R15 are
 * Yamaha, GS125 is Suzuki, TMX is Honda). Entries whose make/model could not be verified are listed in
 * 'pending_verification' and are NOT selectable or matched.
 */
return [
    'makes' => ['Yamaha', 'Honda', 'Suzuki', 'Kawasaki'],

    'catalogue_status' => [
        'complete' => false,
        'basis' => 'inventory-evidenced models only; owner-supplied zero-inventory models pending',
    ],

    'catalogue' => [
        'Yamaha' => [
            ['name' => 'Aerox', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Aerox']],
            ['name' => 'Aerox 155', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Aerox 155']],
            ['name' => 'Crypton', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Crypton']],
            ['name' => 'Crypton R', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Crypton R']],
            ['name' => 'Mio', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Mio']],
            ['name' => 'Mio 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Mio 125']],
            ['name' => 'Mio 125 MX', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Mio 125 MX']],
            ['name' => 'Mio Gravis', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Mio Gravis']],
            ['name' => 'Mio I 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Mio I 125']],
            ['name' => 'Mio I125 M3', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Mio I125 M3']],
            ['name' => 'Mio M3', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Mio M3']],
            ['name' => 'Mio Racing', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Mio Racing']],
            ['name' => 'Mio Soul', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Mio Soul']],
            ['name' => 'Mio Soul I 115', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Mio Soul I 115']],
            ['name' => 'Mio Soul I 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Mio Soul I 125']],
            ['name' => 'Mio Sporty', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Mio Sporty']],
            ['name' => 'NMax', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['NMax', 'NMAX']],
            ['name' => 'NMax 155', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['NMax 155']],
            ['name' => 'NMax V1', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['NMax V1']],
            ['name' => 'NMax V2', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['NMax V2']],
            ['name' => 'Nouvo', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Nouvo']],
            ['name' => 'RS100', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['RS100', 'RS 100']],
            ['name' => 'Sniper', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Sniper']],
            ['name' => 'Sniper 135', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Sniper 135']],
            ['name' => 'Sniper 150', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Sniper 150']],
            ['name' => 'Sniper 150F', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Sniper 150F']],
            ['name' => 'STX', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['STX']],
            ['name' => 'STX 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['STX 125', 'STX125']],
            ['name' => 'Vega Force', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Vega Force']],
            ['name' => 'Vega-ZR09', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Vega-ZR09']],
            ['name' => 'YTX', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['YTX']],
            ['name' => 'YTX 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['YTX 125', 'YTX125']],
            ['name' => 'YZF-R15', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['R15']],
        ],
        'Honda' => [
            ['name' => 'ADV150', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['ADV150']],
            ['name' => 'Beat', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Beat']],
            ['name' => 'Beat 110 FI', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Beat 110 FI']],
            ['name' => 'Beat FI', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Beat FI']],
            ['name' => 'Beat FI V2', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Beat FI V2']],
            ['name' => 'C100', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['C100']],
            ['name' => 'CB110', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['CB110']],
            ['name' => 'CB125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['CB125']],
            ['name' => 'CG125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['CG125']],
            ['name' => 'CG150', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['CG150']],
            ['name' => 'Click', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Click']],
            ['name' => 'Click 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Click 125']],
            ['name' => 'Click 125 I', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Click 125 I']],
            ['name' => 'Click 150', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Click 150']],
            ['name' => 'Click 150 FI', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Click 150 FI']],
            ['name' => 'Click 150i', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Click 150i']],
            ['name' => 'Click155', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Click155']],
            ['name' => 'DIO', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Dio', 'DIO']],
            ['name' => 'Dream', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Dream']],
            ['name' => 'PCX', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['PCX']],
            ['name' => 'PCX 150 R', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['PCX 150 R']],
            ['name' => 'PCX 160', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['PCX160', 'PCX-160']],
            ['name' => 'TMX', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['TMX']],
            ['name' => 'TMX 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['TMX 125', 'TMX125']],
            ['name' => 'TMX 125 Alpha', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['TMX 125 Alpha']],
            ['name' => 'TMX 155', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['TMX 155', 'TMX155']],
            ['name' => 'TMX Supremo', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['TMX Supremo', 'Supremo']],
            ['name' => 'Wave 100', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Wave 100']],
            ['name' => 'Wave 110', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Wave 110']],
            ['name' => 'Wave 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Wave 125']],
            ['name' => 'Wave 125 FI', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Wave 125 FI']],
            ['name' => 'Wave Alpha', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Wave Alpha']],
            ['name' => 'Wave Dash', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Wave Dash']],
            ['name' => 'XRM', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['XRM']],
            ['name' => 'XRM 110', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['XRM 110', 'XRM110']],
            ['name' => 'XRM 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['XRM 125', 'XRM125']],
            ['name' => 'XRM 125 FI', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['XRM 125 FI']],
        ],
        'Suzuki' => [
            ['name' => 'Burgman', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Burgman']],
            ['name' => 'GS125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['GS125']],
            ['name' => 'R150', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['R150']],
            ['name' => 'R150 FI', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['R150 FI']],
            ['name' => 'Raider 150', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Raider 150', 'Raider150']],
            ['name' => 'Raider 150 FI', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Raider 150 FI']],
            ['name' => 'Raider 150 R', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Raider 150 R']],
            ['name' => 'Raider FI', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Raider FI']],
            ['name' => 'Shogun', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Shogun']],
            ['name' => 'Shogun 110', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Shogun 110']],
            ['name' => 'Shogun 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Shogun 125']],
            ['name' => 'Skydrive', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Skydrive']],
            ['name' => 'Skydrive 115i', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Skydrive 115i']],
            ['name' => 'Skydrive 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Skydrive 125']],
            ['name' => 'Skydrive 125 FI', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Skydrive 125 FI']],
            ['name' => 'Skydrive E-125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Skydrive E-125']],
            ['name' => 'Skydrive Sport', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Skydrive Sport']],
            ['name' => 'Smash', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Smash']],
            ['name' => 'Smash 110', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Smash 110', 'Smash110']],
            ['name' => 'Smash 115', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Smash 115', 'Smash115']],
            ['name' => 'Smash 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Smash 125']],
        ],
        'Kawasaki' => [
            ['name' => 'Barako', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Barako']],
            ['name' => 'Barako 175', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Barako 175']],
            ['name' => 'Barako Bolt B175', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Barako Bolt B175']],
            ['name' => 'Barako Electric', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Barako Electric', 'Electric Barako']],
            ['name' => 'Barako II', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Barako II', 'Barako 2']],
            ['name' => 'Barako Kick', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Barako  Kick', 'Barako Kick']],
            ['name' => 'BC175', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['BC175']],
            ['name' => 'Fury', 'type' => 'family', 'source' => 'inventory', 'aliases' => ['Fury']],
            ['name' => 'Fury 125', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['Fury 125']],
            ['name' => 'HD3', 'type' => 'specific', 'source' => 'inventory', 'aliases' => ['HD3']],
        ],
    ],

    // Owner-supplied models that have NO inventory yet (same shape as 'catalogue', source 'owner').
    // Empty until the owner returns the zero-inventory template. Merged into the catalogue at runtime.
    'owner_models' => [
        'Yamaha' => [],
        'Honda' => [],
        'Suzuki' => [],
        'Kawasaki' => [],
    ],

    // Inventory strings whose make/model could not be verified. Not matched, not in dropdowns.
    'pending_verification' => [
        ['KMX F', 'Yamaha (inventory guess)', 'KMX is a Kawasaki model line; the "F" variant and make are unverified'],
        ['LC150', 'Honda (inventory guess)', 'No reliable evidence found for this model/make'],
        ['C150', 'Honda (inventory guess)', 'No reliable evidence found for this model/make'],
        ['Wave 126', 'Honda (inventory guess)', 'Not a recognised Honda model name; possible typo'],
        ['Dash', 'Honda (inventory guess)', 'Bare name; no reliable evidence of the model/make'],
        ['TNX Supremo', 'Honda (inventory guess)', 'Possible typo of TMX Supremo; meaning not confirmed by the owner'],
    ],

    // Universal is NOT a manufacturer. Inventory strings are classified, never inferred.
    'universal' => [
        'labelled' => ['Universal', 'Universal Handle Switch Set With Colored Button', 'Universal 7ml'],
        'conditional' => ['Universal (according To The Bearing Size)', 'Universal Ordinary 28MM'],
        'confirmed' => [],
    ],
];
