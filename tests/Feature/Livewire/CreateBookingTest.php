<?php

use App\Livewire\CreateBooking;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(CreateBooking::class)
        ->assertStatus(200);
});
