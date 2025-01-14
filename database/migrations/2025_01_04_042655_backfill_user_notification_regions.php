<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->whereNull('notification_regions')
            ->orWhere('notification_regions', '[]')
            ->update([
                'notification_regions' => ['miami'],
            ]);
    }

    public function down(): void
    {
        User::query()
            ->where('notification_regions', ['miami'])
            ->update([
                'notification_regions' => [],
            ]);
    }
};
