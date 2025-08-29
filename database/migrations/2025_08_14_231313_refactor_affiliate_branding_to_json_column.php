<?php

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
        Schema::table('concierges', function (Blueprint $table) {
            // Add the new JSON column
            $table->json('branding')->nullable()->after('can_override_duplicate_checks');
        });

        // Migrate existing data to the JSON column
        $this->migrateExistingBrandingData();

        // Drop the old columns
        Schema::table('concierges', function (Blueprint $table) {
            $table->dropColumn([
                'logo_url',
                'main_color',
                'secondary_color',
                'gradient_start',
                'gradient_end',
                'text_color',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('concierges', function (Blueprint $table) {
            // Recreate the old columns
            $table->string('logo_url')->nullable()->after('can_override_duplicate_checks');
            $table->string('main_color')->nullable()->after('logo_url');
            $table->string('secondary_color')->nullable()->after('main_color');
            $table->string('gradient_start')->nullable()->after('secondary_color');
            $table->string('gradient_end')->nullable()->after('gradient_start');
            $table->string('text_color')->nullable()->after('gradient_end');
        });

        // Migrate data back from JSON to individual columns
        $this->migrateJsonDataBack();

        // Drop the JSON column
        Schema::table('concierges', function (Blueprint $table) {
            $table->dropColumn('branding');
        });
    }

    /**
     * Migrate existing branding data to JSON format
     */
    private function migrateExistingBrandingData(): void
    {
        $concierges = DB::table('concierges')->get();

        foreach ($concierges as $concierge) {
            $brandingData = [];

            // Only include non-null values
            if ($concierge->logo_url) {
                $brandingData['logo_url'] = $concierge->logo_url;
            }
            if ($concierge->main_color) {
                $brandingData['main_color'] = $concierge->main_color;
            }
            if ($concierge->secondary_color) {
                $brandingData['secondary_color'] = $concierge->secondary_color;
            }
            if ($concierge->gradient_start) {
                $brandingData['gradient_start'] = $concierge->gradient_start;
            }
            if ($concierge->gradient_end) {
                $brandingData['gradient_end'] = $concierge->gradient_end;
            }
            if ($concierge->text_color) {
                $brandingData['text_color'] = $concierge->text_color;
            }
            // Note: brand_name and description are new fields, so they won't exist in old data

            // Only update if there's branding data
            if (! empty($brandingData)) {
                DB::table('concierges')
                    ->where('id', $concierge->id)
                    ->update(['branding' => json_encode($brandingData)]);
            }
        }
    }

    /**
     * Migrate JSON data back to individual columns
     */
    private function migrateJsonDataBack(): void
    {
        $concierges = DB::table('concierges')->whereNotNull('branding')->get();

        foreach ($concierges as $concierge) {
            $brandingData = json_decode((string) $concierge->branding, true);

            $updateData = [];
            if (isset($brandingData['logo_url'])) {
                $updateData['logo_url'] = $brandingData['logo_url'];
            }
            if (isset($brandingData['main_color'])) {
                $updateData['main_color'] = $brandingData['main_color'];
            }
            if (isset($brandingData['secondary_color'])) {
                $updateData['secondary_color'] = $brandingData['secondary_color'];
            }
            if (isset($brandingData['gradient_start'])) {
                $updateData['gradient_start'] = $brandingData['gradient_start'];
            }
            if (isset($brandingData['gradient_end'])) {
                $updateData['gradient_end'] = $brandingData['gradient_end'];
            }
            if (isset($brandingData['text_color'])) {
                $updateData['text_color'] = $brandingData['text_color'];
            }

            if (! empty($updateData)) {
                DB::table('concierges')
                    ->where('id', $concierge->id)
                    ->update($updateData);
            }
        }
    }
};
