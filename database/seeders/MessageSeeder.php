<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Concierge;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin_user = User::role('super_admin')->first();
        $announcement = Announcement::factory()->create([
            'sender_id' => $admin_user->id
        ]);

        foreach (Concierge::all() as $concierge) {
            Message::factory()->create([
                'user_id' => $concierge->user->id,
                'announcement_id' => $announcement->id,
            ]);
        }
    }
}
