<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Venue Tier Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the ordering and priority of venues by tier
    | for each region. Venues are ordered by their position in the array,
    | with earlier positions having higher priority in search results.
    |
    */

    'tiers' => [
        // Region ID => tier configuration
        'miami' => [ // Miami region
            'tier_1' => [
            ],
            'tier_2' => [
            ],
        ],

        // Add other regions as needed
        'ibiza' => [ // Ibiza region
            'tier_1' => [
                326, // LíO
                189, // El Chiringuito
                330, // el Chiringuito beach sunbeds
                243, // Hannah
                259, // Juan y Andrea
                312, // 1742
                228, // Juls
            ],
            'tier_2' => [
                310, // Laylah
                187, // It Ibiza (higher ID)
                193, // Roto (higher ID)
                316, // Pomelo Playa
                190, // ChezzGerdi
                285, // La Escollera
                284, // Cala Duo (higher ID)
                192, // Playa Soleil
                265, // Lado
                215, // Tigre Morado
                221, // Es Boldado
                214, // Almar
                267, // Es Nautic
            ],
        ],
        // 'new_york' => [ // New York region
        //     'tier_1' => [],
        //     'tier_2' => [],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Tier
    |--------------------------------------------------------------------------
    |
    | The default tier for venues that are not explicitly configured.
    | null = Standard tier
    |
    */
    'default_tier' => null,

    /*
    |--------------------------------------------------------------------------
    | Tier Labels
    |--------------------------------------------------------------------------
    |
    | Human-readable labels for each tier number.
    |
    */
    'tier_labels' => [
        1 => 'Gold',
        2 => 'Silver',
        null => 'Standard',
    ],
];
