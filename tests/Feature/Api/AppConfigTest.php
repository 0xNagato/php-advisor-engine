<?php

use function Pest\Laravel\getJson;

test('app config contains required fields', function () {
    getJson('/api/app-config')
        ->assertSuccessful()
        ->assertJsonStructure([
            'bookings_enabled',
            'bookings_disabled_message',
            'login' => [
                'background_image',
                'text_color',
            ],
        ]);
});
