<?php

namespace Tests\Feature\Actions\Booking;

use App\Actions\Booking\UpdateBookingConciergeAttribution;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\VipCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateBookingConciergeAttributionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required partners for BookingFactory
        Partner::factory()->count(2)->create();
    }

    /** @test */
    public function it_updates_concierge_for_api_booking_with_customer_history()
    {
        $customerPhone = '+1234567890';

        // Create two different concierges
        $originalConciergeUser = User::factory()->create();
        $originalConcierge = Concierge::factory()->create(['user_id' => $originalConciergeUser->id]);

        $historicalConciergeUser = User::factory()->create();
        $historicalConcierge = Concierge::factory()->create(['user_id' => $historicalConciergeUser->id]);

        // Create historical booking with the customer
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $historicalConcierge->id,
            'booking_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        // Create new booking with different concierge
        $newBooking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $originalConcierge->id,
            'source' => 'api',
            'vip_code_id' => null,
            'booking_at' => now(),
        ]);

        // Apply attribution logic
        $updated = UpdateBookingConciergeAttribution::run($newBooking, $customerPhone);

        $this->assertTrue($updated);
        $this->assertEquals($historicalConcierge->id, $newBooking->fresh()->concierge_id);
    }

    /** @test */
    public function it_does_not_update_booking_with_vip_code()
    {
        $customerPhone = '+1234567890';

        // Create concierges
        $vipConciergeUser = User::factory()->create();
        $vipConcierge = Concierge::factory()->create(['user_id' => $vipConciergeUser->id]);

        $historicalConciergeUser = User::factory()->create();
        $historicalConcierge = Concierge::factory()->create(['user_id' => $historicalConciergeUser->id]);

        // Create VIP code
        $vipCode = VipCode::factory()->create(['concierge_id' => $vipConcierge->id]);

        // Create historical booking
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $historicalConcierge->id,
            'booking_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        // Create booking with VIP code
        $vipBooking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $vipConcierge->id,
            'source' => 'api',
            'vip_code_id' => $vipCode->id,
            'booking_at' => now(),
        ]);

        // Try to apply attribution logic
        $updated = UpdateBookingConciergeAttribution::run($vipBooking, $customerPhone);

        $this->assertFalse($updated);
        $this->assertEquals($vipConcierge->id, $vipBooking->fresh()->concierge_id);
    }

    /** @test */
    public function it_does_not_update_platform_bookings()
    {
        $customerPhone = '+1234567890';

        // Create concierges
        $platformConciergeUser = User::factory()->create();
        $platformConcierge = Concierge::factory()->create(['user_id' => $platformConciergeUser->id]);

        $historicalConciergeUser = User::factory()->create();
        $historicalConcierge = Concierge::factory()->create(['user_id' => $historicalConciergeUser->id]);

        // Create historical booking
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $historicalConcierge->id,
            'booking_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        // Create platform booking
        $platformBooking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $platformConcierge->id,
            'source' => 'reservation_hub',
            'vip_code_id' => null,
            'booking_at' => now(),
        ]);

        // Try to apply attribution logic
        $updated = UpdateBookingConciergeAttribution::run($platformBooking, $customerPhone);

        $this->assertFalse($updated);
        $this->assertEquals($platformConcierge->id, $platformBooking->fresh()->concierge_id);
    }

    /** @test */
    public function it_does_not_update_when_no_customer_history_exists()
    {
        $customerPhone = '+1234567890';

        // Create concierge
        $conciergeUser = User::factory()->create();
        $concierge = Concierge::factory()->create(['user_id' => $conciergeUser->id]);

        // Create booking without any customer history
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        $booking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $concierge->id,
            'source' => 'api',
            'vip_code_id' => null,
            'booking_at' => now(),
        ]);

        // Try to apply attribution logic
        $updated = UpdateBookingConciergeAttribution::run($booking, $customerPhone);

        $this->assertFalse($updated);
        $this->assertEquals($concierge->id, $booking->fresh()->concierge_id);
    }

    /** @test */
    public function it_does_not_update_when_same_concierge_in_history()
    {
        $customerPhone = '+1234567890';

        // Create concierge
        $conciergeUser = User::factory()->create();
        $concierge = Concierge::factory()->create(['user_id' => $conciergeUser->id]);

        // Create historical booking with same concierge
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $concierge->id,
            'booking_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        // Create new booking with same concierge
        $newBooking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $concierge->id,
            'source' => 'api',
            'vip_code_id' => null,
            'booking_at' => now(),
        ]);

        // Try to apply attribution logic
        $updated = UpdateBookingConciergeAttribution::run($newBooking, $customerPhone);

        $this->assertFalse($updated);
        $this->assertEquals($concierge->id, $newBooking->fresh()->concierge_id);
    }

    /** @test */
    public function it_uses_most_recent_concierge_from_history()
    {
        $customerPhone = '+1234567890';

        // Create three different concierges
        $currentConciergeUser = User::factory()->create();
        $currentConcierge = Concierge::factory()->create(['user_id' => $currentConciergeUser->id]);

        $oldConciergeUser = User::factory()->create();
        $oldConcierge = Concierge::factory()->create(['user_id' => $oldConciergeUser->id]);

        $recentConciergeUser = User::factory()->create();
        $recentConcierge = Concierge::factory()->create(['user_id' => $recentConciergeUser->id]);

        // Create historical bookings
        $scheduleTemplate = ScheduleTemplate::factory()->create();

        // Older booking
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $oldConcierge->id,
            'booking_at' => now()->subDays(10),
            'created_at' => now()->subDays(10),
        ]);

        // More recent booking (should be used)
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $recentConcierge->id,
            'booking_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
        ]);

        // Create new booking
        $newBooking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $currentConcierge->id,
            'source' => 'api',
            'vip_code_id' => null,
            'booking_at' => now(),
        ]);

        // Apply attribution logic
        $updated = UpdateBookingConciergeAttribution::run($newBooking, $customerPhone);

        $this->assertTrue($updated);
        $this->assertEquals($recentConcierge->id, $newBooking->fresh()->concierge_id);
    }

    /** @test */
    public function it_applies_attribution_regardless_of_referral_type()
    {
        $customerPhone = '+1234567890';

        // Create concierges
        $currentConciergeUser = User::factory()->create();
        $currentConcierge = Concierge::factory()->create(['user_id' => $currentConciergeUser->id]);

        $historicalConciergeUser = User::factory()->create();
        $historicalConcierge = Concierge::factory()->create(['user_id' => $historicalConciergeUser->id]);

        // Create historical booking
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $historicalConcierge->id,
            'booking_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        // Create booking to test different referral types
        $booking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $currentConcierge->id,
            'source' => 'api',
            'vip_code_id' => null,
            'booking_at' => now(),
        ]);

        // Should work with 'sms' referral type
        $updated = UpdateBookingConciergeAttribution::run($booking, $customerPhone);

        $this->assertTrue($updated);
        $this->assertEquals($historicalConcierge->id, $booking->fresh()->concierge_id);

        // Reset booking for next test
        $booking->update(['concierge_id' => $currentConcierge->id]);

        // Should work with 'qr' referral type
        $updated = UpdateBookingConciergeAttribution::run($booking, $customerPhone);

        $this->assertTrue($updated);
        $this->assertEquals($historicalConcierge->id, $booking->fresh()->concierge_id);
    }

    /** @test */
    public function it_works_with_null_referral_type()
    {
        $customerPhone = '+1234567890';

        // Create concierges
        $currentConciergeUser = User::factory()->create();
        $currentConcierge = Concierge::factory()->create(['user_id' => $currentConciergeUser->id]);

        $historicalConciergeUser = User::factory()->create();
        $historicalConcierge = Concierge::factory()->create(['user_id' => $historicalConciergeUser->id]);

        // Create historical booking
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $historicalConcierge->id,
            'booking_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        // Create booking
        $booking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $currentConcierge->id,
            'source' => 'api',
            'vip_code_id' => null,
            'booking_at' => now(),
        ]);

        // Apply attribution logic with null referral type
        $updated = UpdateBookingConciergeAttribution::run($booking, $customerPhone);

        $this->assertTrue($updated);
        $this->assertEquals($historicalConcierge->id, $booking->fresh()->concierge_id);
    }

    /** @test */
    public function it_applies_attribution_to_house_vip_codes()
    {
        $customerPhone = '+1234567890';

        // Create concierges
        $houseConciergeUser = User::factory()->create();
        $houseConcierge = Concierge::factory()->create(['user_id' => $houseConciergeUser->id]);

        $historicalConciergeUser = User::factory()->create();
        $historicalConcierge = Concierge::factory()->create(['user_id' => $historicalConciergeUser->id]);

        // Create house VIP code (HOME)
        $houseVipCode = VipCode::factory()->create([
            'code' => 'HOME',
            'concierge_id' => $houseConcierge->id,
        ]);

        // Create historical booking
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $historicalConcierge->id,
            'booking_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        // Create booking with house VIP code
        $booking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $houseConcierge->id,
            'source' => 'api',
            'vip_code_id' => $houseVipCode->id,
            'booking_at' => now(),
        ]);

        // Apply attribution logic
        $updated = UpdateBookingConciergeAttribution::run($booking, $customerPhone);

        $this->assertTrue($updated);
        $this->assertEquals($historicalConcierge->id, $booking->fresh()->concierge_id);
        // VIP code should still be preserved for tracking
        $this->assertEquals($houseVipCode->id, $booking->fresh()->vip_code_id);
    }

    /** @test */
    public function it_does_not_apply_attribution_to_regular_vip_codes()
    {
        $customerPhone = '+1234567890';

        // Create concierges
        $vipConciergeUser = User::factory()->create();
        $vipConcierge = Concierge::factory()->create(['user_id' => $vipConciergeUser->id]);

        $historicalConciergeUser = User::factory()->create();
        $historicalConcierge = Concierge::factory()->create(['user_id' => $historicalConciergeUser->id]);

        // Create regular VIP code (not in house codes config)
        $regularVipCode = VipCode::factory()->create([
            'code' => 'REGULAR123',
            'concierge_id' => $vipConcierge->id,
        ]);

        // Create historical booking
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $historicalConcierge->id,
            'booking_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        // Create booking with regular VIP code
        $booking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $vipConcierge->id,
            'source' => 'api',
            'vip_code_id' => $regularVipCode->id,
            'booking_at' => now(),
        ]);

        // Apply attribution logic
        $updated = UpdateBookingConciergeAttribution::run($booking, $customerPhone);

        $this->assertFalse($updated);
        $this->assertEquals($vipConcierge->id, $booking->fresh()->concierge_id);
    }

    /** @test */
    public function it_works_with_direct_house_vip_code()
    {
        $customerPhone = '+1234567890';

        // Create concierges
        $houseConciergeUser = User::factory()->create();
        $houseConcierge = Concierge::factory()->create(['user_id' => $houseConciergeUser->id]);

        $historicalConciergeUser = User::factory()->create();
        $historicalConcierge = Concierge::factory()->create(['user_id' => $historicalConciergeUser->id]);

        // Create house VIP code (DIRECT)
        $directVipCode = VipCode::factory()->create([
            'code' => 'DIRECT',
            'concierge_id' => $houseConcierge->id,
        ]);

        // Create historical booking
        $scheduleTemplate = ScheduleTemplate::factory()->create();
        Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_phone' => $customerPhone,
            'concierge_id' => $historicalConcierge->id,
            'booking_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        // Create booking with DIRECT VIP code
        $booking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $houseConcierge->id,
            'source' => 'api',
            'vip_code_id' => $directVipCode->id,
            'booking_at' => now(),
        ]);

        // Apply attribution logic
        $updated = UpdateBookingConciergeAttribution::run($booking, $customerPhone);

        $this->assertTrue($updated);
        $this->assertEquals($historicalConcierge->id, $booking->fresh()->concierge_id);
        // VIP code should still be preserved for tracking
        $this->assertEquals($directVipCode->id, $booking->fresh()->vip_code_id);
    }
}
