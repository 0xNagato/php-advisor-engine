<?php

/** @noinspection NullPointerExceptionInspection */
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpPossiblePolymorphicInvocationInspection */

namespace Tests\Feature\Services\Booking;

use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\Restaurant;
use App\Models\ScheduleTemplate;
use App\Services\Booking\BookingCalculationService;
use App\Services\Booking\EarningCreationService;
use App\Services\Booking\NonPrimeEarningsCalculationService;
use App\Services\Booking\PrimeEarningsCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingCalculationService $service;

    protected Restaurant $restaurant;

    protected Concierge $concierge;

    protected Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $earningCreationService = new EarningCreationService;
        $primeEarningsCalculationService = new PrimeEarningsCalculationService($earningCreationService);
        $nonPrimeEarningsCalculationService = new NonPrimeEarningsCalculationService($earningCreationService);

        $this->service = new BookingCalculationService(
            $primeEarningsCalculationService,
            $nonPrimeEarningsCalculationService
        );

        $this->restaurant = Restaurant::factory()->create([
            'payout_restaurant' => 60,
            'non_prime_fee_per_head' => 10,
        ]);
        $this->concierge = Concierge::factory()->create();
        $this->partner = Partner::factory()->create(['percentage' => 6]);

        $this->partialMock(Concierge::class, function ($mock) {
            $mock->shouldReceive('getAttribute')->with('payout_percentage')->andReturn(10);
        });
    }

    public function test_scenario_1_partner_referred_both_concierge_and_restaurant(): void
    {
        Booking::withoutEvents(function () {
            $this->concierge->user->update(['partner_referral_id' => $this->partner->id]);
            $this->restaurant->user->update(['partner_referral_id' => $this->partner->id]);

            $booking = $this->createBooking();

            $this->service->calculateEarnings($booking);

            $this->assertDatabaseCount('earnings', 4);
            $this->assertEarningExists($booking, 'restaurant', 12000);
            $this->assertEarningExists($booking, 'concierge', 2000);
            $this->assertEarningExists($booking, 'partner_restaurant', 360);
            $this->assertEarningExists($booking, 'partner_concierge', 360);
            $this->assertEquals(5280, $booking->fresh()->platform_earnings);
        });
    }

    public function test_scenario_2_different_partners_referred_concierge_and_restaurant(): void
    {
        Booking::withoutEvents(function () {
            $partnerConcierge = Partner::factory()->create(['percentage' => 6]);
            $partnerRestaurant = Partner::factory()->create(['percentage' => 6]);

            $this->concierge->user->update(['partner_referral_id' => $partnerConcierge->id]);
            $this->restaurant->user->update(['partner_referral_id' => $partnerRestaurant->id]);

            $booking = $this->createBooking();

            $this->service->calculateEarnings($booking);

            $this->assertDatabaseCount('earnings', 4);
            $this->assertEarningExists($booking, 'restaurant', 12000);
            $this->assertEarningExists($booking, 'concierge', 2000);
            $this->assertEarningExists($booking, 'partner_restaurant', 360);
            $this->assertEarningExists($booking, 'partner_concierge', 360);
            $this->assertEquals(5280, $booking->fresh()->platform_earnings);
        });
    }

    public function test_scenario_3_concierge_with_level_1_referral(): void
    {
        Booking::withoutEvents(function () {
            $referringConcierge = Concierge::factory()->create();
            $this->concierge->user->update(['concierge_referral_id' => $referringConcierge->id]);

            $booking = $this->createBooking();

            $this->service->calculateEarnings($booking);

            $this->assertDatabaseCount('earnings', 3);
            $this->assertEarningExists($booking, 'restaurant', 12000);
            $this->assertEarningExists($booking, 'concierge', 2000);
            $this->assertEarningExists($booking, 'concierge_referral_1', 600);
            $this->assertEquals(5400, $booking->fresh()->platform_earnings);
        });
    }

    public function test_scenario_4_concierge_with_level_1_and_level_2_referrals(): void
    {
        Booking::withoutEvents(function () {
            $referringConcierge1 = Concierge::factory()->create();
            $referringConcierge2 = Concierge::factory()->create();
            $this->concierge->user->update(['concierge_referral_id' => $referringConcierge1->id]);
            $referringConcierge1->user->update(['concierge_referral_id' => $referringConcierge2->id]);

            $booking = $this->createBooking();

            $this->service->calculateEarnings($booking);

            $this->assertDatabaseCount('earnings', 4);
            $this->assertEarningExists($booking, 'restaurant', 12000);
            $this->assertEarningExists($booking, 'concierge', 2000);
            $this->assertEarningExists($booking, 'concierge_referral_1', 600);
            $this->assertEarningExists($booking, 'concierge_referral_2', 300);
            $this->assertEquals(5100, $booking->fresh()->platform_earnings);
        });
    }

    public function test_non_prime_booking_calculation(): void
    {
        Booking::withoutEvents(function () {
            $booking = $this->createNonPrimeBooking();

            $this->service->calculateNonPrimeEarnings($booking);

            $this->assertDatabaseCount('earnings', 2);
            $this->assertEarningExists($booking, 'restaurant_paid', -2200);
            $this->assertEarningExists($booking, 'concierge_bounty', 1800);

            $freshBooking = $booking->fresh();
            $this->assertEquals(1800, $freshBooking->concierge_earnings);
            $this->assertEquals(-2200, $freshBooking->restaurant_earnings);
            $this->assertEquals(400, $freshBooking->platform_earnings);
        });
    }

    public function test_non_prime_booking_with_different_guest_count(): void
    {
        Booking::withoutEvents(function () {
            $booking = $this->createNonPrimeBooking(guestCount: 5);

            $this->service->calculateNonPrimeEarnings($booking);

            $this->assertDatabaseCount('earnings', 2);
            $this->assertEarningExists($booking, 'restaurant_paid', -5500);
            $this->assertEarningExists($booking, 'concierge_bounty', 4500);

            $freshBooking = $booking->fresh();
            $this->assertEquals(4500, $freshBooking->concierge_earnings);
            $this->assertEquals(-5500, $freshBooking->restaurant_earnings);
            $this->assertEquals(1000, $freshBooking->platform_earnings);
        });
    }

    public function test_non_prime_booking_with_custom_fee(): void
    {
        Booking::withoutEvents(function () {
            $this->restaurant->update(['non_prime_fee_per_head' => 15]);
            $booking = $this->createNonPrimeBooking();

            $this->service->calculateNonPrimeEarnings($booking);

            $this->assertDatabaseCount('earnings', 2);
            $this->assertEarningExists($booking, 'restaurant_paid', -3300);
            $this->assertEarningExists($booking, 'concierge_bounty', 2700);

            $freshBooking = $booking->fresh();
            $this->assertEquals(2700, $freshBooking->concierge_earnings);
            $this->assertEquals(-3300, $freshBooking->restaurant_earnings);
            $this->assertEquals(600, $freshBooking->platform_earnings);
        });
    }

    private function createNonPrimeBooking(int $guestCount = 2): Booking
    {
        $restaurant = $this->restaurant;

        return Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => false,
            'guest_count' => $guestCount,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['restaurant_id' => $restaurant->id])->id,
            'total_fee' => $restaurant->non_prime_fee_per_head * $guestCount * 100,
        ]);
    }

    private function createBooking(): Booking
    {
        return Booking::factory()->create([
            'uuid' => Str::uuid(),
            'is_prime' => true,
            'total_fee' => 20000,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => ScheduleTemplate::factory()->create(['restaurant_id' => $this->restaurant->id])->id,
        ]);
    }

    private function assertEarningExists(Booking $booking, string $type, int $amount): void
    {
        $this->assertDatabaseHas('earnings', [
            'booking_id' => $booking->id,
            'type' => $type,
            'amount' => $amount,
        ]);
    }
}
