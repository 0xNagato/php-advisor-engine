<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Artisan;
use Illuminate\Database\Seeder;
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

        Artisan::call('shield:super-admin', [
            '--user' => 1,
        ]);

        Artisan::call('shield:super-admin', [
            '--user' => 2,
        ]);

        Role::create(['name' => 'concierge']);
        Role::create(['name' => 'restaurant']);

        $this->call([
            ConciergeSeeder::class,
            RestaurantSeeder::class,
            ScheduleSeeder::class,
            BookingSeeder::class,
            ShieldSeeder::class,
        ]);

        Artisan::call('shield:generate --all');
    }
}
