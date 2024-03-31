<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // need to drop tables customers, payments, subscriptions, subscription_items,laragenie_responses, teams, team_invitations, team_user
        Schema::dropIfExists('customers');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('laragenie_responses');
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('team_user');

    }
};
