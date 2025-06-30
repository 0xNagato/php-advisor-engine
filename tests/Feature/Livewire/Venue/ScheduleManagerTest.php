<?php

use App\Livewire\Venue\ScheduleManager;
use App\Models\Venue;
use Carbon\Carbon;
use Livewire\Livewire;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->venue = Venue::factory()->create([
        'region' => 'miami',
        'timezone' => 'America/New_York',
        'open_days' => [
            'monday' => 'open',
            'tuesday' => 'closed',
            'wednesday' => 'open',
            'thursday' => 'open',
            'friday' => 'open',
            'saturday' => 'open',
            'sunday' => 'closed',
        ],
        'party_sizes' => json_encode([4 => '4 people', 6 => '6 people']),
    ]);

    $this->today = Carbon::now('UTC')->format('Y-m-d');
});

it('initializes with default state for venue', function () {
    Livewire::test(ScheduleManager::class, ['venue' => $this->venue])
        ->assertSet('activeView', 'template')
        ->assertSet('selectedDay', 'monday')
        ->assertSet('todayDate', $this->today)
        ->assertSet('selectedDate', $this->today);
});

it('initializes schedules correctly with open and closed days', function () {
    // Arrange: Create a venue with specific open and closed day configurations
    $this->venue->update([
        'open_days' => [
            'monday' => 'open',
            'tuesday' => 'closed',
            'wednesday' => 'open',
            'thursday' => 'open',
            'friday' => 'open',
            'saturday' => 'open',
            'sunday' => 'closed',
        ],
    ]);

    // Act: Mount the ScheduleManager Livewire component
    livewire(ScheduleManager::class, ['venue' => $this->venue])

        // Assert closed days are set as 'closed' in schedules
        ->assertSet('schedules.tuesday', 'closed')
        ->assertSet('schedules.sunday', 'closed')

        // Assert open days have non-empty schedules
        ->assertSet('schedules.monday', function ($mondaySchedule) {
            return is_array($mondaySchedule) && ! empty($mondaySchedule);
        })
        ->assertSet('schedules.wednesday', function ($wednesdaySchedule) {
            return is_array($wednesdaySchedule) && ! empty($wednesdaySchedule);
        })

        // Assert structure of a time slot for an open day
        ->assertSeeHtml('<table class="w-full">')
        ->assertSeeHtml('<button wire:click="openBulkTemplateSizeEditModal');
});
