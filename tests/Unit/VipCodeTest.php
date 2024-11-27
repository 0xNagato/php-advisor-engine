<?php

use App\Models\Concierge;
use App\Models\VipCode;
use Illuminate\Database\QueryException;

test('vip code has fillable attributes', function () {
    $vipCode = new VipCode;
    expect($vipCode->getFillable())->toBe(['code', 'concierge_id']);
});

test('vip code uses id as auth identifier name', function () {
    $vipCode = new VipCode;
    expect($vipCode->getAuthIdentifierName())->toBe('id');
});

test('vip code returns correct auth identifier', function () {
    $vipCode = VipCode::factory()->create();
    expect($vipCode->getAuthIdentifier())->toBe($vipCode->id);
});

test('vip code returns code as auth password', function () {
    $vipCode = VipCode::factory()->create(['code' => 'test123']);
    expect($vipCode->getAuthPassword())->toBe('test123');
});

test('vip code has correct link attribute', function () {
    $newCode = 'abc123';
    $vipCode = VipCode::factory()->create(['code' => $newCode]);
    $expectedLink = route('v.booking', $newCode);
    expect($vipCode->link)->toBe($expectedLink);
});

test('vip code belongs to a concierge', function () {
    $vipCode = VipCode::factory()->create();
    $concierge = Concierge::factory()->create();
    $vipCode->concierge()->associate($concierge);
    $vipCode->save();

    expect($vipCode->concierge)
        ->toBeInstanceOf(Concierge::class)
        ->and($vipCode->concierge->id)->toBe($concierge->id);
});

test('vip code must be unique', function () {
    $code = 'UNIQUE123';
    VipCode::factory()->create(['code' => $code]);
    VipCode::factory()->create(['code' => $code]);
})->throws(QueryException::class);
