<?php

use App\Actions\Booking\CreateBooking;
use App\Actions\Booking\RefundBooking;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Notifications\Booking\CustomerBookingRefunded;
use Stripe\StripeClient;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->venue = Venue::factory()->create(['payout_venue' => 60]);
    $this->concierge = Concierge::factory()->create();
    $this->scheduleTemplate = ScheduleTemplate::factory()->create(['venue_id' => $this->venue->id]);
    actingAs($this->concierge->user);

    Notification::fake();
});

afterEach(function () {
    Mockery::close();
});

it('processes a full refund for a booked and paid booking', function () {
    //
    $intentId = 'pi_test123';
    // Create a booking
    $bookingResult = (new CreateBooking)->handle(
        $this->scheduleTemplate->id,
        ['date' => now()->addDays(2)->format('Y-m-d'), 'guest_count' => 2]
    );

    $booking = $bookingResult->booking;

    $booking->update([
        'guest_phone' => '2014589764',
        'stripe_payment_intent_id' => $intentId,
        'stripe_charge_id' => 'ch_test123',
    ]);

    // Use the mockStripeClient function to set up the mock
    $mockStripeClient = mockStripeClient($booking->total_fee, $intentId);
    app()->instance(StripeClient::class, $mockStripeClient);

    // Act: Execute the RefundBooking action
    $refundBookingAction = app(RefundBooking::class);
    $response = $refundBookingAction->handle($booking);

    // Assert: Verify response and database changes
    expect($response['success'])->toBeTrue()
        ->and($response['message'])->toBe('Refund processed successfully')
        ->and(Booking::query()->find($booking->id)->status)->toBe(BookingStatus::REFUNDED);

    // Assert that the notification was sent
    Notification::assertSentTo(
        [$booking], // The notifiable entity
        CustomerBookingRefunded::class
    );
});

it('handles a refund failure for a booked and paid booking', function () {
    $intentId = 'pi_test123';

    // Create a booking as in the original test
    $bookingResult = (new CreateBooking)->handle(
        $this->scheduleTemplate->id,
        ['date' => now()->addDays(2)->format('Y-m-d'), 'guest_count' => 2],
    );

    $booking = $bookingResult->booking;

    $booking->update([
        'guest_phone' => '2014589764',
        'stripe_payment_intent_id' => $intentId,
        'stripe_charge_id' => 'ch_test123',
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Use the modified mockStripeClient function to simulate a refund failure
    $mockStripeClient = mockStripeClientWithFailingRefund($booking->total_fee, $intentId);
    app()->instance(StripeClient::class, $mockStripeClient);

    // Act: Execute the RefundBooking action
    $refundBookingAction = app(RefundBooking::class);
    $response = $refundBookingAction->handle($booking);

    // Assert: Verify that the refund process fails
    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('Refund could not be processed')
        ->and(Booking::query()->find($booking->id)->status)->toBe(BookingStatus::CONFIRMED);

    // Optionally assert that no notification was sent
    Notification::assertNothingSent();
});

/**
 * Function to create a mock StripeClient.
 *
 * @param  int  $amount  The amount to be refunded
 * @param  string  $intentId  The payment intent ID
 */
function mockStripeClient(int $amount, string $intentId): StripeClient
{
    $mockStripeClient = Mockery::mock(StripeClient::class);
    $mockStripeClient->refunds = Mockery::mock();

    $expectedParams = [
        'amount' => $amount,
        'payment_intent' => $intentId,
        'reason' => null,
    ];

    $defaultResponse = new class
    {
        public string $id = 're_123';

        public int $amount = 1000;

        public string $status = 'succeeded';

        public string $payment_intent = 'pi_test123';

        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'amount' => $this->amount,
                'status' => $this->status,
                'payment_intent' => $this->payment_intent,
            ];
        }
    };

    $mockStripeClient->refunds->shouldReceive('create')->once()
        ->with(Mockery::subset($expectedParams))
        ->andReturn($defaultResponse);

    return $mockStripeClient;
}

function mockStripeClientWithFailingRefund(int $amount, string $intentId): StripeClient
{
    $mockStripeClient = Mockery::mock(StripeClient::class);
    $mockStripeClient->refunds = Mockery::mock();

    $expectedParams = [
        'amount' => $amount,
        'payment_intent' => $intentId,
        'reason' => null,
    ];

    $mockStripeClient->refunds->shouldReceive('create')->once()
        ->with(Mockery::subset($expectedParams))
        ->andThrow(new Exception('Refund could not be processed'));

    return $mockStripeClient;
}
