<?php

use App\Livewire\Concierge\ConciergeInvitation;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(ConciergeInvitation::class)
        ->assertStatus(200);
});
