<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Artisan::call('migrate:fresh');

        User::factory([
            'first_name' => 'Andrew',
            'last_name' => 'Weir',
            'email' => 'andru.weir@gmail.com',
            'phone' => '+16473823326',
            'password' => bcrypt('password'),
        ])->create();

        User::factory([
            'first_name' => 'Alex',
            'last_name' => 'Zhardanovsky',
            'email' => 'alex.zhard@gmail.com',
            'phone' => '+19176644415',
            'password' => bcrypt('password'),
        ])->create();

        User::factory([
            'first_name' => 'Demo',
            'last_name' => 'Admin',
            'email' => 'demo@primavip.co',
            'password' => bcrypt('demo2024'),
        ])->create();

        Artisan::call('shield:super-admin', [
            '--user' => 1,
        ]);

        Artisan::call('shield:super-admin', [
            '--user' => 2,
        ]);

        Artisan::call('shield:super-admin', [
            '--user' => 3,
        ]);

        Role::create(['name' => 'concierge']);
        Role::create(['name' => 'venue']);
        Role::create(['name' => 'partner']);

        // Create House Concierge
        $houseConciergeUser = User::factory([
            'first_name' => 'House',
            'last_name' => 'Concierge',
            'email' => 'house.concierge@primavip.co',
            'password' => bcrypt('secure_password_here'),
        ])->create();

        Concierge::query()->create([
            'user_id' => $houseConciergeUser->id,
            'hotel_name' => 'Prima VIP House',
        ]);

        $houseConciergeUser->assignRole('concierge');

        // Create House Partner
        $housePartnerUser = User::factory([
            'first_name' => 'House',
            'last_name' => 'Partner',
            'email' => 'house.partner@primavip.co',
            'password' => bcrypt('secure_password_here'),
        ])->create();

        Partner::query()->create([
            'user_id' => $housePartnerUser->id,
            'percentage' => 20,
        ]);

        $housePartnerUser->assignRole('partner');

        $this->call([
            PartnerSeeder::class,
        ]);

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

        $this->createOrUpdateConcierges();

        $this->call([
            ShieldSeeder::class,
            ConciergeSeeder::class,
            MiamiVenueSeeder::class,
            // VenueSeeder::class,
            BookingSeeder::class,
        ]);

        Artisan::call('shield:generate --all');
    }

    private function createOrUpdateConcierges(): void
    {
        $names = [
            'Andrew',
            'Alex',
            'Kevin',
            'Steve',
            'Brad',
            'Sara',
            'Tom',
            'Guy',
        ];

        foreach ($names as $name) {
            $name = trim((string) $name);
            $email = strtolower($name).'concierge@primavip.co';

            $user = User::query()->firstOrCreate(['email' => $email], [
                'first_name' => $name,
                'last_name' => 'Concierge',
                'password' => bcrypt('password'),
                'partner_referral_id' => Partner::query()->inRandomOrder()->first()->id,
                'email_verified_at' => now(),
                'secured_at' => now(),
            ]);

            Concierge::query()->firstOrCreate(['user_id' => $user->id], [
                'hotel_name' => "$name's Hotel",
            ]);

            $user->assignRole('concierge');
        }
    }
}
