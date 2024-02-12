<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('concierge_profiles', 'concierges');
        Schema::rename('restaurant_profiles', 'restaurants');
        Schema::rename('customer_profiles', 'customers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('concierges', 'concierge_profiles');
        Schema::rename('restaurants', 'restaurant_profiles');
        Schema::rename('customers', 'customer_profiles');
    }
};
