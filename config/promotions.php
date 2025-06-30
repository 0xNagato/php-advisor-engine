<?php

return [
    /**
     * Concierge earnings promotion configuration
     */
    'concierge' => [
        /**
         * Whether concierge earnings promotions are enabled
         */
        'enabled' => env('CONCIERGE_PROMOTIONS_ENABLED', true),

        /**
         * Periods when concierge earnings are doubled for prime bookings
         * Format: [['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD', 'multiplier' => 2.0]]
         */
        'periods' => [
            [
                'start' => env('CONCIERGE_PROMOTION_START', '2025-05-02'),
                'end' => env('CONCIERGE_PROMOTION_END', '2025-05-04'),
                'multiplier' => env('CONCIERGE_PROMOTION_MULTIPLIER', 2.0),
            ],
            // Add more periods as needed
        ],
    ],

/**
 * You can add other promotion types here in the future
 */
];
