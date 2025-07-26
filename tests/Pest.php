<?php

use App\Models\Concierge;
use App\Models\Partner;
use App\Models\User;
use App\Models\Venue;
use Database\Seeders\PartnerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function () {
        // Ensure queue uses sync driver for immediate execution
        config(['queue.default' => 'sync']);

        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'concierge']);
        Role::create(['name' => 'venue']);
        Role::create(['name' => 'partner']);
        $this->seed(PartnerSeeder::class);

        User::factory([
            'first_name' => 'Andrew',
            'last_name' => 'Weir',
            'email' => 'andru.weir@gmail.com',
            'phone' => '+16473823326',
            'password' => bcrypt('password'),
        ])->create()
            ->assignRole('super_admin');
        User::factory([
            'first_name' => 'Demo',
            'last_name' => 'Venue',
            'email' => 'venue@primavip.co',
            'password' => bcrypt('demo2024'),
            'partner_referral_id' => Partner::query()->inRandomOrder()->first()->id,
        ])
            ->has(Venue::factory([
                'name' => 'Demo Venue',
            ]))
            ->create()
            ->assignRole('venue');
        User::factory([
            'first_name' => 'Demo',
            'last_name' => 'Concierge',
            'email' => 'concierge@primavip.co',
            'password' => bcrypt('demo2024'),
            'partner_referral_id' => Partner::query()->inRandomOrder()->first()->id,
        ])
            ->has(Concierge::factory([
                'hotel_name' => 'Demo Hotel',
            ]))
            ->create()
            ->assignRole('concierge');
    })
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/
