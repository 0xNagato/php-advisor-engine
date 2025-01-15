<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('user can view login page', function () {
get('/platform/login')
->assertStatus(200)
->assertSee('Login')
->assertSee('Sign in');
    });

test('user can access platform messages page', function () {
    $user = User::role('concierge')->first();

    actingAs($user)->get('/platform/messages')
        ->assertStatus(200);
});

test('user can access platform vip code page', function () {
    $user = User::role('concierge')->first();

    actingAs($user)->get('/platform/vip-code-manager')
        ->assertStatus(200)
        ->assertSee('VIP Codes')
        ->assertSee('VIP Code')
        ->assertSeeHtml('<button')
        ->assertSee('Enter VIP Code');
});

test('user cannot access admin panel when not authenticated', function () {
get('/platform/messages')
->assertRedirect('/platform/login');
    });
