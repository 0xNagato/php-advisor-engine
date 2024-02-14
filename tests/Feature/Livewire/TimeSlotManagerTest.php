<?php

use App\Livewire\ScheduleManager;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(ScheduleManager::class)
        ->assertStatus(200);
});
