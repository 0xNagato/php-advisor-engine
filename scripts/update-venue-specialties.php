<?php

/**
 * Tinkerwell Script: Update Venue Specialties (Additive)
 *
 * This script reads venue specialties from CSV and adds them to existing
 * venue specialty arrays without overwriting existing data.
 */

use App\Models\Specialty;
use App\Models\Venue;
use Illuminate\Support\Facades\Log;

// ==============================================
// CONFIGURATION
// ==============================================

// Set to true for dry run (preview changes only)
// Set to false to actually update the database
$isDryRun = true;

// ==============================================

// Path to the CSV file
$csvPath = storage_path('venue-specialties.csv');

if (! file_exists($csvPath)) {
    echo "❌ CSV file not found at: {$csvPath}\n";

    return;
}

if ($isDryRun) {
    echo "🧪 Starting venue specialties DRY RUN (no changes will be made)...\n";
    echo "💡 To actually update venues, set \$isDryRun = false in the script\n\n";
} else {
    echo "🚀 Starting venue specialties update...\n";
}

// Create mapping from CSV column names to specialty IDs
$specialtyMapping = [
    'Waterfront' => 'waterfront',
    'Sunset view' => 'sunset_view',
    'Scenic view' => 'scenic_view',
    'Traditional Ibiza' => 'traditional_ibiza',
    'On the Beach' => 'on_the_beach',
    'Family Friendly' => 'family_friendly',
    'Fine Dining' => 'fine_dining',
    'Romantic Atmosphere' => 'romantic_atmosphere',
    'Live Music/DJ' => 'live_music_dj',
    'Farm-to-Table' => 'farm_to_table',
    'Vegetarian/Vegan Options' => 'vegetarian_vegan_options',
    'Michelin/Repsol Recognition' => 'michelin_repsol_recognition',
    'Rooftop' => 'rooftop',
];

// Verify all specialties exist in the Specialty model
$validSpecialtyIds = Specialty::all()->pluck('id')->toArray();
foreach ($specialtyMapping as $csvName => $specialtyId) {
    if (! in_array($specialtyId, $validSpecialtyIds)) {
        echo "⚠️  Warning: Specialty ID '{$specialtyId}' not found in Specialty model\n";
    }
}

// Read CSV file
$handle = fopen($csvPath, 'r');
if (! $handle) {
    echo "❌ Could not open CSV file\n";

    return;
}

// Get header row to map column positions
$headers = fgetcsv($handle, 0, ',', '"', '\\');
if (! $headers) {
    echo "❌ Could not read CSV headers\n";
    fclose($handle);

    return;
}

// Map specialty columns (skip the first 3 columns: Venue Name, Venue Slug, Region)
$specialtyColumns = array_slice($headers, 3);
echo '📋 Found specialty columns: '.implode(', ', $specialtyColumns)."\n";
echo '📋 Mapped to specialty IDs: '.implode(', ', array_values(array_intersect_key($specialtyMapping, array_flip($specialtyColumns))))."\n";

$venuesUpdated = 0;
$venuesNotFound = 0;
$errors = [];

// Process each row
while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
    if (count($row) < count($headers)) {
        continue; // Skip incomplete rows
    }

    $venueName = trim($row[0]);
    $venueSlug = trim($row[1]);
    $region = trim($row[2]);

    if (empty($venueSlug)) {
        continue; // Skip rows without slug
    }

    try {
        // Find venue by slug
        $venue = Venue::where('slug', $venueSlug)->first();

        if (! $venue) {
            echo "⚠️  Venue not found: {$venueSlug} ({$venueName})\n";
            $venuesNotFound++;

            continue;
        }

        // Get current specialties (or empty array if null)
        $currentSpecialties = $venue->specialty ?? [];

        // Collect new specialties from CSV
        $newSpecialties = [];

        // Check each specialty column (starting from index 3)
        for ($i = 3; $i < count($headers) && $i < count($row); $i++) {
            $csvColumnName = trim($headers[$i]);
            $hasSpecialty = trim($row[$i]) === 'X';

            if ($hasSpecialty && ! empty($csvColumnName)) {
                // Map CSV column name to specialty ID
                if (isset($specialtyMapping[$csvColumnName])) {
                    $specialtyId = $specialtyMapping[$csvColumnName];
                    $newSpecialties[] = $specialtyId;
                } else {
                    echo "⚠️  Unknown specialty column: {$csvColumnName} for venue {$venueName}\n";
                }
            }
        }

        if (empty($newSpecialties)) {
            if ($isDryRun) {
                echo "ℹ️  [DRY RUN] No specialties to add for: {$venueName}\n";
            } else {
                echo "ℹ️  No specialties to add for: {$venueName}\n";
            }

            continue;
        }

        // Merge with existing specialties (remove duplicates)
        $mergedSpecialties = array_unique(array_merge($currentSpecialties, $newSpecialties));

        // Sort for consistency
        sort($mergedSpecialties);

        // Convert specialty IDs back to names for display
        $newSpecialtyNames = [];
        foreach ($newSpecialties as $specialtyId) {
            $specialty = Specialty::find($specialtyId);
            $newSpecialtyNames[] = $specialty ? $specialty->name : $specialtyId;
        }

        // Convert current specialties to names for display
        $currentSpecialtyNames = [];
        foreach ($currentSpecialties as $specialtyId) {
            $specialty = Specialty::find($specialtyId);
            $currentSpecialtyNames[] = $specialty ? $specialty->name : $specialtyId;
        }

        // Convert merged specialties to names for display
        $mergedSpecialtyNames = [];
        foreach ($mergedSpecialties as $specialtyId) {
            $specialty = Specialty::find($specialtyId);
            $mergedSpecialtyNames[] = $specialty ? $specialty->name : $specialtyId;
        }

        if ($isDryRun) {
            echo "🔍 [DRY RUN] Would update {$venueName}:\n";
            echo '   Current specialties ('.count($currentSpecialties).'): '.(empty($currentSpecialtyNames) ? 'None' : implode(', ', $currentSpecialtyNames))."\n";
            echo '   Would add ('.count($newSpecialties).'): '.implode(', ', $newSpecialtyNames)."\n";
            echo '   Total after update ('.count($mergedSpecialties).'): '.implode(', ', $mergedSpecialtyNames)."\n";
        } else {
            // Actually update the venue
            $venue->update(['specialty' => $mergedSpecialties]);
            echo "✅ Updated {$venueName}: Added ".count($newSpecialties).' specialties ('.implode(', ', $newSpecialtyNames).")\n";
            echo '   Total specialties now: '.count($mergedSpecialties)."\n";
        }

        $venuesUpdated++;

    } catch (Exception $e) {
        $error = "Error processing {$venueName} ({$venueSlug}): ".$e->getMessage();
        $errors[] = $error;
        echo "❌ {$error}\n";

        // Only log actual errors during real updates, not dry runs
        if (! $isDryRun) {
            Log::error('Venue specialty update error', [
                'venue_slug' => $venueSlug,
                'venue_name' => $venueName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

fclose($handle);

// Summary
echo "\n📊 Summary:\n";
if ($isDryRun) {
    echo "🔍 Venues that would be updated: {$venuesUpdated}\n";
} else {
    echo "✅ Venues updated: {$venuesUpdated}\n";
}
echo "⚠️  Venues not found: {$venuesNotFound}\n";
echo '❌ Errors: '.count($errors)."\n";

if (! empty($errors)) {
    echo "\n🔍 Error details:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

if ($isDryRun) {
    echo "\n🧪 Dry run completed! No changes were made to the database.\n";
    echo "💡 To actually update venues, set \$isDryRun = false and run again.\n";
} else {
    echo "\n🎉 Venue specialties update completed!\n";
}

// Optional: Display some example venues with their current specialties
if ($isDryRun) {
    echo "\n📋 Sample of venues with current specialties:\n";
} else {
    echo "\n📋 Sample of venues with updated specialties:\n";
}

$sampleVenues = Venue::whereNotNull('specialty')
    ->where(function ($query) {
        $query->whereJsonLength('specialty', '>', 0);
    })
    ->take(5)
    ->get(['name', 'slug', 'specialty']);

foreach ($sampleVenues as $venue) {
    $specialtyNames = [];
    foreach ($venue->specialty ?? [] as $specialtyId) {
        $specialty = Specialty::find($specialtyId);
        $specialtyNames[] = $specialty ? $specialty->name : $specialtyId;
    }
    echo "  • {$venue->name}: ".(empty($specialtyNames) ? 'None' : implode(', ', $specialtyNames))."\n";
}
