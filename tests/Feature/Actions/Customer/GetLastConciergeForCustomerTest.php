<?php

namespace Tests\Feature\Actions\Customer;

use App\Actions\Customer\GetLastConciergeForCustomer;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\ScheduleTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetLastConciergeForCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required partners for BookingFactory
        Partner::factory()->count(2)->create();
    }

    /** @test */
    public function it_returns_null_when_no_booking_history_exists()
    {
        $conciergeId = GetLastConciergeForCustomer::run('+1234567890');

        $this->assertNull($conciergeId);
    }

    /** @test */
    public function it_returns_concierge_id_from_most_recent_booking()
    {
        $customerPhone = '+1234567890';

        // Create concierges
        $oldConciergeUser = User::factory()->create();
        $oldConcierge = Concierge::factory()->create(['user_id' => $oldConciergeUser->id]);

        $recentConciergeUser = User::factory()->create();
        $recentConcierge = Concierge::factory()->create(['user_id' => $recentConciergeUser->id]);

        // Create bookings with different timestamps
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $oldConcierge->id,
            'booking_at' => now()->subDays(10),
            'created_at' => now()->subDays(10),
        ]);

        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $recentConcierge->id,
            'booking_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
        ]);

        $conciergeId = GetLastConciergeForCustomer::run($customerPhone);

        $this->assertEquals($recentConcierge->id, $conciergeId);
    }

    /** @test */
    public function it_matches_exact_phone_number()
    {
        $conciergeUser = User::factory()->create();
        $concierge = Concierge::factory()->create(['user_id' => $conciergeUser->id]);

        // Create booking with specific phone number
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => '+1234567890',
            'concierge_id' => $concierge->id,
            'booking_at' => now(),
        ]);

        // Create booking with different phone number
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => '+0987654321',
            'concierge_id' => $concierge->id,
            'booking_at' => now(),
        ]);

        // Should only match the exact phone number
        $conciergeId = GetLastConciergeForCustomer::run('+1234567890');
        $this->assertEquals($concierge->id, $conciergeId);

        // Different phone should return nothing
        $conciergeId = GetLastConciergeForCustomer::run('+5555555555');
        $this->assertNull($conciergeId);
    }

    /** @test */
    public function it_returns_most_recent_among_multiple_bookings()
    {
        $customerPhone = '+1234567890';

        // Create multiple concierges
        $concierge1User = User::factory()->create();
        $concierge1 = Concierge::factory()->create(['user_id' => $concierge1User->id]);

        $concierge2User = User::factory()->create();
        $concierge2 = Concierge::factory()->create(['user_id' => $concierge2User->id]);

        $concierge3User = User::factory()->create();
        $concierge3 = Concierge::factory()->create(['user_id' => $concierge3User->id]);

        // Create multiple bookings in non-chronological order
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $concierge2->id,
            'booking_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $concierge1->id,
            'booking_at' => now()->subDays(10),
            'created_at' => now()->subDays(10),
        ]);

        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $concierge3->id,
            'booking_at' => now()->subDays(1),
            'created_at' => now()->subDays(1), // Most recent
        ]);

        $conciergeId = GetLastConciergeForCustomer::run($customerPhone);

        $this->assertEquals($concierge3->id, $conciergeId);
    }

    /** @test */
    public function it_works_with_international_phone_format()
    {
        $conciergeUser = User::factory()->create();
        $concierge = Concierge::factory()->create(['user_id' => $conciergeUser->id]);

        // Test various international formats
        $phoneFormats = [
            '+1234567890',
            '+44123456789',
            '+33123456789',
        ];

        $scheduleTemplate = ScheduleTemplate::factory()->create();
        foreach ($phoneFormats as $phone) {
            Booking::factory()->create([
                'schedule_template_id' => $scheduleTemplate->id,
                'guest_phone' => $phone,
                'concierge_id' => $concierge->id,
                'booking_at' => now(),
            ]);

            $conciergeId = GetLastConciergeForCustomer::run($phone);
            $this->assertEquals($concierge->id, $conciergeId, "Failed for phone format: {$phone}");
        }
    }
}
