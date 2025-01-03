<?php

use Stripe\StripeClient;

uses()->group('stripe', 'payments');

beforeEach(function () {
    $this->stripeMock = Mockery::mock(StripeClient::class);
});

function setupPaymentIntentMock($test, $params = [], $response = null): void
{
    $test->stripeMock->paymentIntents = Mockery::mock();

    $defaultResponse = new class
    {
        public string $id = 'pi_123';

        public int $amount = 1000;

        public string $status = 'succeeded';

        public string $client_secret = 'pi_123_secret_456';

        public string $currency = 'usd';

        public string $payment_method = 'pm_123';

        public string $customer = 'cus_123';

        public string $payment_status = 'paid';

        public string $latest_charge = 'ch_123';
    };

    $mockBuilder = $test->stripeMock->paymentIntents->shouldReceive('create')->once();

    if ($params) {
        $mockBuilder->with($params);
    }

    $mockBuilder->andReturn($response ?? $defaultResponse);
}

function setupPaymentMethodMock($test, $params = [], $response = null): void
{
    $test->stripeMock->paymentMethods = Mockery::mock();

    $defaultResponse = new class
    {
        public string $id = 'pm_123';

        public string $type = 'card';

        public $card;

        public function __construct()
        {
            $this->card = new class
            {
                public string $brand = 'visa';

                public string $last4 = '4242';

                public int $exp_month = 12;

                public int $exp_year = 2024;
            };
        }
    };

    $mockBuilder = $test->stripeMock->paymentMethods->shouldReceive('create')->once();

    if ($params) {
        $mockBuilder->with($params);
    }

    $mockBuilder->andReturn($response ?? $defaultResponse);
}

test('it can create a payment intent', function () {
    setupPaymentIntentMock($this);

    $paymentIntent = $this->stripeMock->paymentIntents->create([
        'amount' => 1000,
        'currency' => 'usd',
        'payment_method_types' => ['card'],
    ]);

    expect($paymentIntent)
        ->id->toBe('pi_123')
        ->and($paymentIntent->amount)->toBe(1000)
        ->and($paymentIntent->status)->toBe('succeeded')
        ->and($paymentIntent->client_secret)->toBe('pi_123_secret_456');
});

test('it can create a payment intent with customer and payment method', function () {
    setupPaymentIntentMock(
        $this,
        [
            'amount' => 2000,
            'currency' => 'usd',
            'customer' => 'cus_123',
            'payment_method' => 'pm_123',
            'confirmation_method' => 'automatic',
            'confirm' => true,
        ],
        new class
        {
            public $id = 'pi_456';

            public $amount = 2000;

            public $status = 'succeeded';

            public $client_secret = 'pi_456_secret_789';

            public $customer = 'cus_123';

            public $payment_method = 'pm_123';

            public $payment_status = 'paid';

            public $latest_charge = 'ch_456';
        }
    );

    $paymentIntent = $this->stripeMock->paymentIntents->create([
        'amount' => 2000,
        'currency' => 'usd',
        'customer' => 'cus_123',
        'payment_method' => 'pm_123',
        'confirmation_method' => 'automatic',
        'confirm' => true,
    ]);

    expect($paymentIntent)
        ->id->toBe('pi_456')
        ->and($paymentIntent->amount)->toBe(2000)
        ->and($paymentIntent->status)->toBe('succeeded')
        ->and($paymentIntent->customer)->toBe('cus_123')
        ->and($paymentIntent->payment_method)->toBe('pm_123')
        ->and($paymentIntent->latest_charge)->toBe('ch_456');
});

test('it can create a payment method and payment intent together', function () {
    setupPaymentMethodMock($this);
    setupPaymentIntentMock($this);

    // First create payment method
    $paymentMethod = $this->stripeMock->paymentMethods->create([
        'type' => 'card',
        'card' => [
            'number' => '4242424242424242',
            'exp_month' => 12,
            'exp_year' => 2024,
            'cvc' => '123',
        ],
    ]);

    expect($paymentMethod)
        ->id->toBe('pm_123')
        ->and($paymentMethod->type)->toBe('card')
        ->and($paymentMethod->card->brand)->toBe('visa')
        ->and($paymentMethod->card->last4)->toBe('4242');

    // Then create payment intent with payment method
    $paymentIntent = $this->stripeMock->paymentIntents->create([
        'amount' => 1000,
        'currency' => 'usd',
        'payment_method' => $paymentMethod->id,
        'confirmation_method' => 'automatic',
        'confirm' => true,
    ]);

    expect($paymentIntent)
        ->id->toBe('pi_123')
        ->and($paymentIntent->status)->toBe('succeeded')
        ->and($paymentIntent->payment_method)->toBe('pm_123');
});

afterEach(function () {
    Mockery::close();
});
