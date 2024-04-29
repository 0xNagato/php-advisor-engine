<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin_user = User::role('super_admin')->get()->random();
        Announcement::factory()->create([
            'title' => 'Test Announcement!',
            'sender_id' => $admin_user->id,
        ]);
    }
}
