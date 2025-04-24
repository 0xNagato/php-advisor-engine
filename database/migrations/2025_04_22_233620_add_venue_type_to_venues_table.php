<?php

use App\Enums\VenueType; // Import the enum
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            // Add the venue_type column after 'status'
            $table->string('venue_type')
                ->default(VenueType::STANDARD->value) // Use enum default
                ->after('status')
                ->index(); // Add an index for faster lookups
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            // Drop the index first if it exists (good practice)
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails('venues');
            if ($doctrineTable->hasIndex('venues_venue_type_index')) {
                $table->dropIndex('venues_venue_type_index');
            }
            // Drop the column
            $table->dropColumn('venue_type');
        });
    }
};
