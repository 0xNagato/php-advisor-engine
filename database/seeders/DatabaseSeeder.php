<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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

        Artisan::call('make:filament-user', [
            '--name' => 'Andrew Weir',
            '--email' => 'andru.weir@gmail.com',
            '--password' => 'password',
        ]);

        Artisan::call('make:filament-user', [
            '--name' => 'Alex Zhardanovsky',
            '--email' => 'alex.zhard@gmail.com',
            '--password' => 'password',
        ]);

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
            // ShieldSeeder::class,
        ]);

        Artisan::call('shield:generate --all');
    }
}
