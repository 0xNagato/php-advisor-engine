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

        $this->call([
            ShieldSeeder::class,
            // ConciergeSeeder::class,
            VenueSeeder::class,
            // BookingSeeder::class,
        ]);

        Artisan::call('shield:generate --all');
    }
}
