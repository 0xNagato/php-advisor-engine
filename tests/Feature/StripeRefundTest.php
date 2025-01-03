<?php

use Stripe\StripeClient;

uses()->group('stripe', 'refunds');

beforeEach(function () {
    $this->stripeMock = Mockery::mock(StripeClient::class);
});

function setupStripeMock($test, $params = [], $response = null): void
{
    $test->stripeMock->refunds = Mockery::mock();

    $defaultResponse = new class
    {
        public string $id = 're_123';

        public int $amount = 1000;

        public string $status = 'succeeded';

        public string $payment_intent = 'pi_123';
    };

    $mockBuilder = $test->stripeMock->refunds->shouldReceive('create')->once();

    if ($params) {
        $mockBuilder->with($params);
    }

    $mockBuilder->andReturn($response ?? $defaultResponse);
}

test('it can process a basic stripe refund', function () {
    setupStripeMock($this);

    $refund = $this->stripeMock->refunds->create([
        'payment_intent' => 'pi_123',
        'amount' => 1000,
    ]);

    expect($refund)
        ->id->toBe('re_123')
        ->and($refund->status)->toBe('succeeded')
        ->and($refund->amount)->toBe(1000);
});

test('it can process a refund with custom parameters', function () {
    setupStripeMock(
        $this,
        [
            'charge' => 'ch_123',
            'amount' => 2000,
            'reason' => 'requested_by_customer',
        ],
        new class
        {
            public string $id = 're_456';

            public int $amount = 2000;

            public string $status = 'succeeded';

            public string $charge = 'ch_123';
        }
    );

    $refund = $this->stripeMock->refunds->create([
        'charge' => 'ch_123',
        'amount' => 2000,
        'reason' => 'requested_by_customer',
    ]);

    expect($refund)
        ->id->toBe('re_456')
        ->and($refund->amount)->toBe(2000)
        ->and($refund->charge)->toBe('ch_123')
        ->and($refund->status)->toBe('succeeded');
});

test('it can process a partial refund', function () {
    setupStripeMock(
        $this,
        [
            'payment_intent' => 'pi_123',
            'amount' => 500,
            'reason' => 'requested_by_customer',
        ],
        new class
        {
            public string $id = 're_789';

            public int $amount = 500;

            public string $status = 'succeeded';

            public string $payment_intent = 'pi_123';
        }
    );

    $refund = $this->stripeMock->refunds->create([
        'payment_intent' => 'pi_123',
        'amount' => 500,
        'reason' => 'requested_by_customer',
    ]);

    expect($refund)
        ->id->toBe('re_789')
        ->and($refund->amount)->toBe(500)
        ->and($refund->status)->toBe('succeeded');
});

afterEach(function () {
    Mockery::close();
});
