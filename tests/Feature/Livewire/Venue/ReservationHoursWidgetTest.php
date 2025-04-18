<?php

use App\Data\Venue\SaveReservationHoursBlockData;
use App\Livewire\Venue\ReservationHoursWidget;
use App\Models\Venue;
use App\Services\ReservationHoursService;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->venue = Venue::factory()->create();
});

it('can load component', function () {
    livewire(ReservationHoursWidget::class, ['venue' => $this->venue])
        // Assert days of the week are visible
        ->assertSee('Mon')
        ->assertSee('Tue')
        ->assertSee('Wed')
        ->assertSee('Thu')
        ->assertSee('Fri')
        ->assertSee('Sat')
        ->assertSee('Sun')
        // Assert checkboxes exist for each day
        ->assertSeeHtml('<input id="monday" type="checkbox" wire:model.live="selectedDays.monday"')
        ->assertSeeHtml('<input id="tuesday" type="checkbox" wire:model.live="selectedDays.tuesday"')
        ->assertSeeHtml('<input id="wednesday" type="checkbox" wire:model.live="selectedDays.wednesday"')
        ->assertSeeHtml('<input id="thursday" type="checkbox" wire:model.live="selectedDays.thursday"')
        ->assertSeeHtml('<input id="friday" type="checkbox" wire:model.live="selectedDays.friday"')
        ->assertSeeHtml('<input id="saturday" type="checkbox" wire:model.live="selectedDays.saturday"')
        ->assertSeeHtml('<input id="sunday" type="checkbox" wire:model.live="selectedDays.sunday"')
        // Assert time inputs for start and end times exist for the first block of Monday
        ->assertSeeHtml('id="start-time-monday-0"')
        ->assertSeeHtml('id="end-time-monday-0"')

        // Assert 'Add Time Block' buttons exist for one of the days
        ->assertSeeHtml('<button type="button" wire:click="addTimeBlock(')

        // Assert form submission button exists
        ->assertSeeHtml('wire:submit.prevent="saveHours"');
});

it('loads data correctly during mount using ReservationHoursService', function () {
    $service = new ReservationHoursService;
    $loadReservationHoursData = $service->loadHours($this->venue);

    // Act: Mount the ReservationHoursWidget Livewire component
    livewire(ReservationHoursWidget::class, ['venue' => $this->venue])
        // Assert the component has correctly loaded openingHours and selectedDays
        ->assertSet('openingHours', $loadReservationHoursData->openingHours)
        ->assertSet('selectedDays', $loadReservationHoursData->selectedDays);
});

it('handles empty input data gracefully', function () {
    // Arrange: Prepare ReservationHoursService instance
    $service = new ReservationHoursService;

    // Arrange: Provide empty openingHours and selectedDays
    $emptyData = new SaveReservationHoursBlockData(
        venue: $this->venue,
        selectedDays: [
            'monday' => false,
            'tuesday' => false,
            'wednesday' => false,
            'thursday' => false,
            'friday' => false,
            'saturday' => false,
            'sunday' => false,
        ],
        openingHours: []
    );

    // Act: Call saveHours with empty data
    $service->saveHours($emptyData);

    // Assert: No schedules are updated
    $this->assertDatabaseMissing('schedule_templates', [
        'venue_id' => $this->venue->id,
        'is_available' => true,
    ]);

    // Assert: Venue's open days are updated to closed
    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
        $this->assertDatabaseHas('venues', [
            'id' => $this->venue->id,
            "open_days->$day" => 'closed',
        ]);
    }
});

it('saves opening hours and validates input data', function () {
    // Arrange: Prepare ReservationHoursService instance
    $service = new ReservationHoursService;

    // Arrange: Mock valid data for SaveReservationHoursBlockData
    $validData = new SaveReservationHoursBlockData(
        venue: $this->venue,
        selectedDays: [
            'monday' => true,
            'tuesday' => false,
            'wednesday' => false,
            'thursday' => false,
            'friday' => false,
            'saturday' => false,
            'sunday' => false,
        ],
        openingHours: [
            'monday' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00'],
                ['start_time' => '13:00:00', 'end_time' => '16:00:00'],
            ],
        ]
    );

    // Act: Save hours using the service
    $service->saveHours($validData);

    // Assert: Check database contains updated schedule templates
    $blocks = createThirtyMinuteIntervals('09:00:00', '12:00:00');
    foreach ($blocks as $block) {
        [$blockStart, $blockEnd] = $block;
        $this->assertDatabaseHas('schedule_templates', [
            'venue_id' => $this->venue->id,
            'day_of_week' => 'monday',
            'start_time' => $blockStart,
            'end_time' => $blockEnd,
            'is_available' => 1,
        ]);
    }

    $blocks = createThirtyMinuteIntervals('13:00:00', '16:00:00');
    foreach ($blocks as $block) {
        [$blockStart, $blockEnd] = $block;
        $this->assertDatabaseHas('schedule_templates', [
            'venue_id' => $this->venue->id,
            'day_of_week' => 'monday',
            'start_time' => $blockStart,
            'end_time' => $blockEnd,
            'is_available' => 1,
        ]);
    }

    $this->assertDatabaseMissing('schedule_templates', [
        'venue_id' => $this->venue->id,
        'day_of_week' => 'tuesday',
        'is_available' => 1,
    ]);

    // Assert: Updated `open_days` in venue
    $this->assertDatabaseHas('venues', [
        'id' => $this->venue->id,
        'open_days->monday' => 'open',
        'open_days->tuesday' => 'closed',
        'open_days->wednesday' => 'closed',
        'open_days->thursday' => 'closed',
        'open_days->friday' => 'closed',
        'open_days->saturday' => 'closed',
        'open_days->sunday' => 'closed',
    ]);
});

it('throws validation error when start time is greater than end time via the widget', function () {
    // Instantiate the ReservationHoursWidget
    $widget = app(ReservationHoursWidget::class);

    // Set widget properties (selectedDays and openingHours)
    $widget->venue = $this->venue;
    $widget->selectedDays = [
        'monday' => true,
        'tuesday' => false,
        'wednesday' => false,
        'thursday' => false,
        'friday' => false,
        'saturday' => false,
        'sunday' => false,
    ];
    $widget->openingHours = [
        'monday' => [
            ['start_time' => '12:00:00', 'end_time' => '09:00:00'], // Invalid: start > end
        ],
    ];

    // Act & Assert: Expect a validation exception to be thrown
    $this->expectException(ValidationException::class);

    // Call the saveHours method, which should trigger the validation
    $widget->saveHours();
});

it('does not throw error when start time is less than or equal to end time via the widget', function () {

    // Instantiate the ReservationHoursWidget
    $widget = app(ReservationHoursWidget::class);

    // Set widget properties (selectedDays and openingHours) with valid time ranges
    $widget->venue = $this->venue;
    $widget->selectedDays = [
        'monday' => true,
        'tuesday' => false,
        'wednesday' => false,
        'thursday' => false,
        'friday' => false,
        'saturday' => false,
        'sunday' => false,
    ];
    $widget->openingHours = [
        'monday' => [
            ['start_time' => '09:00:00', 'end_time' => '12:00:00'], // Valid: start < end
        ],
    ];

    // Act & Assert: Ensure no validation exception is thrown
    expect(fn () => $widget->saveHours())->not->toThrow(ValidationException::class);
});

/**
 * Generate 30-minute intervals between a start and an end time.
 *
 * @param  string  $startTime  (in `H:i:s` format)
 * @param  string  $endTime  (in `H:i:s` format)
 * @return array Array of time intervals as [start_time, end_time]
 */
function createThirtyMinuteIntervals(string $startTime, string $endTime): array
{
    $blocks = [];
    $current = strtotime($startTime);
    $end = strtotime($endTime);

    // Generate time blocks
    while ($current < $end) {
        $next = strtotime('+30 minutes', $current);
        $blocks[] = [
            date('H:i:s', $current), // start_time
            date('H:i:s', $next),    // end_time
        ];
        $current = $next;
    }

    return $blocks;
}
