<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Concierge;
use App\Models\Restaurant;
use App\Models\User;
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
            'password' => bcrypt('password'),
        ])->create();

        User::factory([
            'first_name' => 'Alex',
            'last_name' => 'Zhardanovsky',
            'email' => 'alex.zhard@gmail.com',
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
        Role::create(['name' => 'restaurant']);
        Role::create(['name' => 'partner']);

        $restaurant = User::factory([
            'first_name' => 'Demo',
            'last_name' => 'Restaurant',
            'email' => 'restaurant@primavip.co',
            'password' => bcrypt('demo2024'),
        ])
            ->has(Restaurant::factory([
                'restaurant_name' => 'Demo Restaurant',
            ]))
            ->create()
            ->assignRole('restaurant');

        $concierge = User::factory([
            'first_name' => 'Demo',
            'last_name' => 'Concierge',
            'email' => 'concierge@primavip.co',
            'password' => bcrypt('demo2024'),
        ])
            ->has(Concierge::factory([
                'hotel_name' => 'Demo Hotel',
            ]))
            ->create()
            ->assignRole('concierge');

        $this->call([
            PartnerSeeder::class,
            ConciergeSeeder::class,
            RestaurantSeeder::class,
            BookingSeeder::class,
            ShieldSeeder::class,
        ]);

        Artisan::call('shield:generate --all');
    }
}
