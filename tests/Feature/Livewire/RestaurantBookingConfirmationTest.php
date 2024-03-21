<?php

use App\Livewire\RestaurantBookingConfirmation;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(RestaurantBookingConfirmation::class)
        ->assertStatus(200);
});
