<?php

use App\Livewire\ConciergeInvitation;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(ConciergeInvitation::class)
        ->assertStatus(200);
});
