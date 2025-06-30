<?php

namespace App\Console\Commands;

use App\Models\Venue;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateVenueSpecialties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:update-specialties {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update venue specialties based on predefined data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('Running in dry-run mode - no changes will be made.');
        }

        // Define specialty mapping (CSV header to database ID)
        $specialtyMapping = [
            'Waterfront View' => 'waterfront',
            'On the Beach' => 'on_the_beach',
            'Family Friendly' => 'family_friendly',
            'Sunset View' => 'sunset_view',
            'Fine Dining' => 'fine_dining',
            'Romantic Atmosphere' => 'romantic_atmosphere',
            'Live Music/DJ' => 'live_music_dj',
            'Farm-to-Table' => 'farm_to_table',
            'Vegetarian/Vegan Options' => 'vegetarian_vegan_options',
            'Michelin/Repsol Recognition' => 'michelin_repsol_recognition',
        ];

        // Embedded venue data from All_Venue_Specialties_Final.csv
        // Format: [region, slug, Waterfront, Beach, Family, Sunset, FineDining, Romantic, LiveMusic, FarmToTable, Veg, Michelin, Description]
        $venueData = [
            ['miami', 'miami-mila', '', '', '', '', 'X', 'X', '', '', '', '', 'Mediterranean & Asian fusion rooftop restaurant'],
            ['miami', 'miami-casa-neo', '', '', '', '', 'X', 'X', '', '', '', '', 'Modern Mediterranean fine dining'],
            ['miami', 'miami-macchialina', '', '', '', '', 'X', '', '', '', '', 'X', 'Rustic Italian trattoria with house‑made pasta'],
            ['miami', 'miami-cecconis-at-soho-house', '', '', '', '', 'X', 'X', '', '', '', 'X', 'Upscale Venetian‑style Italian courtyard dining'],
            ['miami', 'miami-giselle', '', '', '', '', '', '', 'X', '', '', '', 'Asian, Mediterranean & French fusion in a glamorous rooftop setting'],
            ['miami', 'miami-lpm', '', '', '', '', '', '', '', '', '', '', 'French Mediterranean (Niçoise) cuisine'],
            ['miami', 'miami-mandolin', '', '', '', '', '', '', '', '', '', '', 'Authentic Greek & Turkish Aegean cuisine'],
            ['miami', 'miami-sexy-fish', '', '', 'X', '', '', '', '', '', '', '', 'Japanese‑inspired seafood & sushi restaurant'],
            ['miami', 'miami-sereia', '', '', 'X', '', '', '', '', '', '', '', 'Modern Iberian‑coast seafood‑forward cuisine'],
            ['miami', 'miami-prime-112', '', '', '', '', 'X', '', '', '', '', '', 'Chef‑driven boutique steakhouse'],
            ['miami', 'miami-prime-italian', '', '', '', '', 'X', '', '', '', '', '', 'Upscale Italian‑American steakhouse'],
            ['miami', 'miami-milos', '', '', 'X', '', 'X', '', '', '', '', '', 'High‑end Greek seafood restaurant'],
            ['miami', 'miami-kyu', '', '', '', '', '', '', '', '', '', '', 'Modern Asian wood‑fired BBQ and grill'],
            ['miami', 'miami-gekko', '', '', '', '', '', '', 'X', '', '', '', 'Japanese‑inspired steakhouse & lounge'],
            ['miami', 'miami-komodo', '', '', '', '', '', '', '', '', '', '', 'Pan‑Asian cuisine in a swanky multi‑level space'],
            ['miami', 'miami-casadonna', '', '', '', '', '', '', '', '', '', '', 'Coastal Italian cuisine with Riviera flair'],
            ['miami', 'miami-papi-steak', '', '', '', '', 'X', '', '', '', '', '', 'High‑energy boutique steakhouse'],
            ['miami', 'miami-shadow-wagyu', '', '', '', '', '', '', '', '', '', '', 'Premium Wagyu butcher shop & restaurant'],
            ['miami', 'miami-call-me-gaby', '', '', '', '', 'X', '', '', '', '', '', 'Charming Italian trattoria & pizzeria'],
            ['miami', 'miami-niu-kitchen', '', '', '', '', 'X', '', '', '', '', '', 'Catalan‑Spanish tapas bistro'],
            ['miami', 'miami-catch', '', '', 'X', '', '', '', '', '', '', '', 'Trendy seafood, sushi & steak hotspot'],
            ['miami', 'miami-semilla', '', '', '', '', 'X', '', '', '', '', '', 'French‑American gastro‑bistro with tapas'],
            ['miami', 'miami-sparrow', '', '', '', '', '', '', 'X', '', '', '', 'Retro rooftop cocktail lounge with Japanese bites'],
            ['miami', 'miami-mother-wolf', '', '', '', '', '', '', '', '', '', '', 'Roman Italian pastas & pizzas'],
            ['miami', 'miami-paya', '', '', '', '', '', '', '', '', '', '', 'Island‑inspired cuisine & cocktails'],
            ['miami', 'miami-kissaki', '', '', '', '', '', '', '', '', '', '', 'Contemporary Japanese omakase sushi'],
            ['miami', 'miami-erba', '', '', '', '', '', '', '', 'X', '', '', 'Farm‑to‑table Italian pasta kitchen'],
            ['miami', 'miami-ghee', '', '', '', '', '', '', '', 'X', '', '', 'Modern Indian farm‑to‑table cuisine'],
            ['miami', 'miami-le-jardinier', '', '', '', '', '', '', '', 'X', 'X', '', 'Vegetable‑forward modern French cuisine'],
            ['miami', 'miami-latelier-de-joel-robuchon', '', '', '', '', 'X', '', '', '', '', '', 'Haute French contemporary fine dining'],
            ['los_angeles', 'los-angeles-mother-wolf', '', '', '', '', '', '', '', '', '', '', 'Roman Italian restaurant by Evan Funke'],
            ['los_angeles', 'los-angeles-layla', '', '', '', '', '', '', 'X', '', '', '', 'Mediterranean rooftop restaurant'],
            ['los_angeles', 'los-angeles-mxo', '', '', '', '', '', '', '', '', '', '', 'Modern Mexican restaurant'],
            ['miami', 'miami-palma', '', '', '', '', '', '', '', '', '', '', 'Mediterranean tapas & wine bar'],
            ['miami', 'miami-matys', '', '', '', '', '', '', '', '', '', '', 'Contemporary Peruvian homestyle cuisine'],
            ['miami', 'miami-itamae', '', '', '', '', '', '', '', '', '', '', 'Nikkei (Peruvian‑Japanese) sushi bar'],
            ['miami', 'miami-salvaje', '', '', '', '', '', '', '', '', '', '', 'Trendy Japanese‑fusion steakhouse'],
            ['miami', 'miami-calle-dragones', '', '', '', '', '', '', '', '', '', '', 'Cuban‑Chinese fusion speakeasy'],
            ['miami', 'miami-grand-central', '', '', '', '', '', '', '', '', '', '', 'Food hall & entertainment venue'],
            ['miami', 'miami-villa-azur', '', '', '', '', '', '', 'X', '', '', '', 'Chic French‑Mediterranean supper club'],
            ['miami', 'miami-los-felix', '', '', '', '', '', '', '', '', '', '', 'Artisanal Mexican cocina & natural wine'],
            ['miami', 'miami-krus-kitchen', '', '', 'X', '', 'X', '', '', 'X', '', '', 'Seasonal wine bar & chef‑driven plates'],
            ['miami', 'miami-klaw', '', '', '', '', '', '', '', '', '', '', 'Surf‑and‑turf steakhouse famous for King Crab'],
            ['miami', 'miami-gao', '', '', '', '', '', '', '', '', '', '', 'Modern Vietnamese & Pan‑Asian eatery'],
            ['miami', 'miami-sotto-sopra', '', '', '', '', '', '', '', '', '', '', 'Coastal Italian trattoria & wood‑fired pizza'],
            ['miami', 'miami-ensenada', '', '', '', '', '', '', '', '', '', '', 'Marisquería wine bar with Argentine flair'],
            ['miami', 'miami-forte-dei-marmi', '', '', '', '', 'X', 'X', '', '', '', '', 'Elegant Italian fine‑dining villa'],
            ['miami', 'miami-delilah', '', '', '', '', '', '', 'X', '', '', '', '1920s‑inspired American supper club'],
            ['miami', 'miami-casa-madera', '', '', '', '', '', '', '', '', '', '', 'Immersive Mexican‑Mediterranean dining'],
            ['miami', 'miami-via-emilia-9', '', '', '', '', '', '', '', '', '', '', 'Authentic Emilia‑Romagna pastas & charcuterie'],
            ['miami', 'miami-anima', '', '', '', '', '', '', '', '', '', '', 'Contemporary European fusion cuisine'],
            ['miami', 'miami-doya', '', '', '', '', '', '', '', '', '', '', 'Modern Aegean meze & bar'],
            ['los_angeles', 'los-angeles-cipriani', '', '', '', '', 'X', '', '', '', '', '', 'Upscale Italian Venetian classics'],
            ['los_angeles', 'los-angeles-the-benjamin', '', '', '', '', 'X', '', 'X', '', '', '', 'Boutique lounge / bar'],
            ['los_angeles', 'los-angeles-craigs', '', '', '', '', '', '', '', '', '', '', 'Classic American comfort‑food hotspot'],
            ['los_angeles', 'los-angeles-delilah', '', '', '', '', '', '', 'X', '', '', '', 'Art‑Deco American supper club'],
            ['los_angeles', 'los-angeles-the-nice-guy', '', '', '', '', '', '', 'X', '', '', '', 'Italian‑American lounge & piano bar'],
            ['los_angeles', 'los-angeles-didi', '', '', '', '', '', '', '', '', '', '', 'Contemporary Asian fusion concept'],
            ['los_angeles', 'los-angeles-arden', '', '', '', '', 'X', '', '', 'X', 'X', '', 'Modern American vegetable‑forward fine dining'],
            ['los_angeles', 'los-angeles-casa-madera', '', '', '', '', '', '', '', '', '', '', 'Coastal Mexican with Mediterranean flair'],
            ['los_angeles', 'los-angeles-hideaway', '', '', '', '', '', '', '', '', '', '', 'Mexican steakhouse & bar'],
            ['los_angeles', 'los-angeles-puzzle', '', '', '', '', '', '', '', '', '', '', 'Speakeasy craft‑cocktail bar'],
            ['los_angeles', 'los-angeles-kasbah', '', '', '', '', '', '', 'X', '', '', '', 'Moroccan/Mediterranean lounge'],
            ['los_angeles', 'los-angeles-amour-weho', '', '', '', '', '', '', '', '', '', '', 'French café & wine bar'],
            ['los_angeles', 'los-angeles-mr-wanderlust', '', '', '', '', '', '', 'X', '', '', '', 'Travel‑themed cocktail lounge'],
            ['los_angeles', 'los-angeles-maison-kasai', '', '', '', '', '', '', '', '', '', '', 'Japanese teppanyaki / sushi concept'],
            ['los_angeles', 'los-angeles-lucky-mizu', '', '', '', '', '', '', '', '', '', '', 'Asian fusion / sushi bar'],
            ['los_angeles', 'los-angeles-que-barbaro', '', '', '', '', '', '', '', '', '', '', 'Argentine steakhouse & bar'],
            ['los_angeles', 'los-angeles-the-brown-sheep', '', '', '', '', 'X', '', '', '', '', '', 'Boutique bar / café'],
            ['los_angeles', 'los-angeles-golden-hour', '', '', '', 'X', '', '', 'X', '', '', '', 'Rooftop bar renowned for sunsets'],
            ['los_angeles', 'los-angeles-mother-of-pearl', '', '', '', '', '', '', '', '', '', '', 'Tiki bar with tropical cocktails'],
            ['los_angeles', 'los-angeles-sinners-y-santos', '', '', '', '', '', '', 'X', '', '', '', 'Latin‑inspired nightclub bar'],
            ['ibiza', 'ibiza-playa-soleil', '', 'X', '', '', '', 'X', '', '', '', '', 'Beach club & Mediterranean dining'],
            ['ibiza', 'ibiza-duo', 'X', '', '', '', '', '', '', '', '', '', 'Seaside Mediterranean restaurant'],
            ['ibiza', 'ibiza-casa-maca', '', '', '', '', '', '', '', 'X', '', '', 'Farm‑to‑table Balearic cuisine'],
            ['ibiza', 'ibiza-cala-gracioneta', '', 'X', '', '', '', '', '', '', '', '', 'Traditional Ibizan beach chiringuito'],
            ['ibiza', 'ibiza-sa-capella', '', '', '', '', 'X', '', '', '', '', '', 'Fine dining in a historic chapel'],
            ['ibiza', 'formentera-juan-y-andrea', 'X', 'X', '', '', '', '', '', '', '', '', 'Iconic beachfront seafood restaurant'],
            ['ibiza', 'ibiza-smart-charter', '', '', '', '', '', '', '', '', '', '', 'Private yacht charter experiences'],
            ['ibiza', 'formentera-cala-duo', '', 'X', '', '', '', '', '', '', '', '', 'Beach restaurant with Mediterranean menu'],
            ['ibiza', 'ibiza-ibiza-hike-station', '', '', '', '', '', '', '', '', '', '', 'Guided hiking & nature tours'],
            ['ibiza', 'ibiza-almar', 'X', '', '', '', '', '', '', '', '', '', 'Italian & Mediterranean seaside dining'],
            ['ibiza', 'ibiza-tigre-morado', '', '', '', '', '', '', '', '', '', '', 'Nikkei‑Peruvian cuisine & cocktails'],
            ['miami', 'miami-zuma', '', '', '', '', '', '', '', '', '', '', 'Contemporary Japanese izakaya'],
            ['ibiza', 'ibiza-a-mi-manera-2', '', '', '', '', 'X', '', 'X', '', '', '', 'Countryside farm‑to‑table Mediterranean & Italian dining in a romantic garden'],
            ['ibiza', 'ibiza-amalur', '', '', '', '', 'X', '', '', '', '', '', 'Fine‑dining Italian haute cuisine in a historic finca'],
            ['ibiza', 'formentera-beso-beach', 'X', 'X', '', '', '', '', '', '', '', '', 'Trendy beachfront Mediterranean seafood & grill with paellas'],
            ['ibiza', 'ibiza-cbbc', 'X', 'X', '', '', '', '', '', '', '', '', 'Beach club restaurant serving Mediterranean cuisine & sushi at Cala Bassa'],
            ['ibiza', 'ibiza-chezzgerdi', 'X', 'X', '', '', '', '', '', '', '', '', 'Beachfront Italian & Mediterranean fusion with wood‑fired pizza and seafood'],
            ['ibiza', 'ibiza-cotton-beach-club', 'X', 'X', '', '', '', '', '', '', '', '', 'Panoramic sea‑view Mediterranean cuisine & sushi beach club'],
            ['ibiza', 'ibiza-el-chiringuito-ibiza', '', 'X', '', '', 'X', '', '', '', '', '', 'Upscale beach restaurant offering contemporary Mediterranean cuisine and fresh seafood'],
            ['ibiza', 'ibiza-el-silencio', '', 'X', '', '', '', '', '', '', '', '', 'Rustic chic beach restaurant with modern Mediterranean dishes and creative cocktails'],
            ['ibiza', 'ibiza-es-fumeral', '', '', '', '', '', '', '', '', 'X', '', 'Modern Mediterranean seafood grill awarded Repsol Sun'],
            ['ibiza', 'ibiza-hostel-la-torre', '', '', '', 'X', '', '', '', '', '', '', 'Sunset‑view terrace serving Mediterranean tapas & cocktails'],
            ['ibiza', 'ibiza-it-ibiza', '', '', '', '', '', '', '', '', '', '', 'Contemporary Italian dining & sushi with Mediterranean flair'],
            ['ibiza', 'formentera-a-mi-manera', '', '', '', '', '', '', 'X', '', '', '', 'Countryside farm‑to‑table Mediterranean & Italian dining with organic produce'],
            ['ibiza', 'ibiza-beso-beach', 'X', 'X', '', '', '', '', '', '', '', '', 'Trendy beach club for Basque‑Mediterranean grill and seafood paellas'],
            ['ibiza', 'miami-nobu', '', '', '', '', 'X', '', '', '', '', '', 'Japanese‑Peruvian fine dining sushi & robata by Chef Nobu Matsuhisa'],
            ['ibiza', 'ibiza-a-mi-manera', '', '', '', '', 'X', '', 'X', '', '', '', 'Countryside farm‑to‑table Mediterranean & Italian dining in lush gardens'],
            ['ibiza', 'ibiza-roto', 'X', '', '', '', '', '', '', '', '', '', 'Waterfront restaurant serving modern Mediterranean tapas & cocktails with marina views'],
            ['los_angeles', 'los-angeles-boa-steakhouse', '', '', '', '', 'X', '', '', '', '', '', 'Upscale modern steakhouse with prime aged beef & seafood'],
            ['los_angeles', 'los-angeles-carmel-melrose', '', '', '', '', '', '', 'X', '', '', '', 'Californian farm‑to‑table bistro showcasing local produce'],
            ['los_angeles', 'los-angeles-chez-mia', '', '', '', '', 'X', 'X', '', '', '', '', 'Intimate French‑Italian fusion fine dining'],
            ['los_angeles', 'los-angeles-harriets', '', '', '', '', '', '', 'X', '', '', '', 'Rooftop cocktail lounge with light Californian bites'],
            ['los_angeles', 'los-angeles-katsuya', '', '', '', '', '', '', '', '', '', '', 'Contemporary Japanese sushi & robata by Chef Katsuya Uechi'],
            ['los_angeles', 'los-angeles-nobu-malibu', 'X', '', '', '', 'X', '', '', '', '', '', 'High‑end Japanese‑Peruvian fusion sushi overlooking the Pacific'],
            ['miami', 'miami-a-fish-called-avalon', 'X', '', '', '', '', '', 'X', '', '', '', 'Iconic Ocean Drive seafood & steak restaurant with live music'],
            ['miami', 'miami-amor-amor-cantina-del-corazon', '', '', '', '', '', '', '', '', '', '', 'Lively modern Mexican cantina with creative tacos & mezcal'],
            ['miami', 'miami-andres-carne-de-res-miami', '', '', '', '', '', '', 'X', '', '', '', 'Colombian steakhouse & party restaurant known for flamboyant atmosphere'],
            ['miami', 'miami-avalon-by-day', 'X', '', '', '', '', '', '', '', '', '', 'Oceanfront American bistro & cocktail bar at Avalon Hotel'],
            ['miami', 'miami-azabu', '', '', '', '', 'X', '', '', '', '', 'X', 'Michelin‑starred Japanese sushi & robata with omakase counter'],
            ['miami', 'miami-baoli-miami', '', '', '', '', '', '', 'X', '', '', '', 'Chic Asian‑Mediterranean supper club with sushi & nightlife vibes'],
            ['miami', 'miami-byblos-miami', '', '', '', '', '', '', '', '', '', '', 'Eastern Mediterranean cuisine with Levantine flavors'],
            ['miami', 'miami-catch-miami-beach', '', '', '', '', '', '', '', '', '', '', 'Trendy seafood, sushi & steak hotspot'],
            ['miami', 'miami-claudie', '', '', '', '', 'X', '', '', '', '', '', 'Upscale French patisserie café and bistro'],
            ['miami', 'miami-tala-at-1-hotel', 'X', 'X', '', '', '', '', '', '', '', '', 'Spanish‑inspired beachfront restaurant & bar at 1 Hotel South Beach'],
            ['miami', 'doma', '', '', '', '', '', '', '', '', '', '', 'Southern Italian cuisine with seafood & pasta in Wynwood'],
            ['miami', 'miami-el-patio-bar-habana', '', '', '', '', '', '', '', '', '', '', 'Cuban street‑style bar with live Latin music & tapas'],
            ['miami', 'miami-gold-standard-sushi-prepaid-omakase-experience', '', '', '', '', 'X', '', '', '', '', '', 'Hidden 15‑course omakase speakeasy'],
            ['miami', 'miami-habibi', '', '', '', '', '', '', 'X', '', '', '', 'Modern Lebanese & Middle Eastern restaurant and lounge'],
            ['miami', 'miami-kevins-hangout', '', '', '', '', '', '', '', '', '', '', 'Casual American bar & grill sports hangout'],
            ['miami', 'miami-kiki-on-the-river', 'X', '', '', '', '', '', 'X', '', '', '', 'Greek‑inspired waterfront restaurant & lounge on the Miami River'],
            ['miami', 'miami-klaw-rooftop-lounge', '', '', '', '', '', '', '', '', '', '', 'Surf‑and‑turf steakhouse & rooftop bar famous for King Crab'],
            ['miami', 'miami-kyu-2', '', '', '', '', '', '', '', '', '', '', 'Modern Asian wood‑fired BBQ and grill'],
            ['miami', 'miami-lafayette-steakhouse', '', '', '', '', '', '', '', '', '', '', 'French‑style steakhouse blending Parisian flair with prime cuts'],
            ['miami', 'lilikoi', '', '', '', '', '', '', 'X', '', '', '', 'Hawaiian‑inspired organic café with poké bowls & smoothies'],
            ['miami', 'miami-lpm-2', '', '', '', '', '', '', '', '', '', '', 'French Mediterranean (Niçoise) cuisine'],
            ['miami', 'miami-margot-sobe', '', '', '', '', '', '', '', '', '', '', 'Seasonal American plates & natural wine bar'],
            ['miami', 'miami-marion', '', '', '', '', '', '', 'X', '', '', '', 'French‑Asian supper club with lively dinner parties'],
            ['miami', 'miami-mayami', '', '', '', '', '', '', 'X', '', '', '', 'Tulum‑inspired Mexican restaurant & nightclub'],
            ['miami', 'miami-mimi-chinese', '', '', '', '', 'X', '', '', '', '', '', 'Upscale Cantonese‑inspired Chinese restaurant'],
            ['miami', 'miami-momento-by-ikaro', '', '', '', '', 'X', '', '', '', '', '', 'Chef‑driven experiential tasting menu concept'],
            ['miami', 'miami-motek-brickell', '', '', '', '', '', '', '', '', '', '', 'Modern Israeli‑Mediterranean café & bakery'],
            ['miami', 'miami-orilla-bar-grill', '', '', '', '', '', '', '', '', '', '', 'Argentine grill focusing on wood‑fired steaks'],
            ['miami', 'miami-oro', '', '', '', '', '', '', 'X', '', '', '', 'High‑energy Latin nightclub with bottle service (not a restaurant)'],
            ['miami', 'miami-osaka', '', '', '', '', '', '', '', '', '', '', 'Peruvian Nikkei Japanese‑Peruvian cuisine'],
            ['miami', 'miami-ossobuco-coconut-grove', '', '', '', '', '', '', '', '', '', '', 'Italian restaurant specializing in Milanese ossobuco & classics'],
            ['miami', 'miami-ossobuco-wynwood', '', '', '', '', '', '', '', '', '', '', 'Italian trattoria specializing in ossobuco & fresh pasta'],
            ['miami', 'miami-otto-pepe', '', '', '', '', '', '', '', '', '', '', 'Neapolitan pizzeria & Italian street food'],
            ['miami', 'miami-pasta-e-basta', '', '', '', '', '', '', '', '', '', '', 'Authentic Italian pasta bar & osteria'],
            ['miami', 'miami-piegari-ristorante', '', '', '', '', 'X', '', '', '', '', '', 'Upscale Argentine‑Italian steakhouse & pasta restaurant'],
            ['miami', 'miami-queen-miami-beach', '', '', '', '', '', '', 'X', '', '', '', 'Glamorous Asian steakhouse & supper club in former Paris Theater'],
            ['miami', 'miami-santorini-by-georgios', '', '', '', '', '', '', '', '', '', '', 'Greek taverna & seafood with Mykonos vibes'],
            ['miami', 'miami-sexy-fish-2', '', '', '', '', '', '', '', '', '', '', 'Japanese‑inspired seafood & sushi restaurant'],
            ['miami', 'miami-shingo', '', '', '', '', 'X', '', '', '', '', '', '12‑seat high‑end omakase by Chef Shingo'],
            ['miami', 'miami-silverlake', '', '', '', '', '', '', '', '', '', '', 'California‑inspired American bistro'],
            ['miami', 'miami-sofia', '', '', '', '', '', '', 'X', '', '', '', 'Elegant modern Italian restaurant & lounge'],
            ['miami', 'miami-sushi-bar-miami-beach', '', '', '', '', 'X', '', '', '', '', '', 'Exclusive 8‑seat omakase sushi bar'],
            ['miami', 'miami-tanuki-river-landing', '', '', '', '', '', '', '', '', '', '', 'Contemporary Pan‑Asian izakaya featuring sushi & dim sum'],
            ['miami', 'miami-the-den', '', '', '', '', 'X', '', '', '', '', '', 'Speakeasy omakase by Azabu with Edomae sushi'],
            ['miami', 'miami-the-joyce', '', '', '', '', '', '', '', '', '', '', 'Classic Irish pub & bar with comfort food'],
            ['miami', 'miami-the-standard', 'X', '', '', '', '', '', '', '', '', '', 'Healthy Mediterranean bayside grill at The Standard hotel'],
            ['miami', 'miami-torno-subito-miami', '', '', '', '', '', '', '', '', '', '', 'Italian Riviera‑style trattoria by Massimo Bottura'],
            ['miami', 'miami-zuri', '', '', '', '', 'X', '', 'X', '', '', '', 'High‑end Mediterranean lounge & restaurant'],
        ];

        // Additional mapping for venues that need specific specialty combinations
        $additionalSpecialties = [
            'ibiza-el-chiringuito-ibiza' => ['farm_to_table', 'vegetarian_vegan_options', 'michelin_repsol_recognition'],
            'ibiza-casa-maca' => ['sunset_view', 'romantic_atmosphere', 'vegetarian_vegan_options', 'michelin_repsol_recognition'],
            'ibiza-hostel-la-torre' => ['family_friendly', 'fine_dining', 'vegetarian_vegan_options', 'michelin_repsol_recognition'],
            'ibiza-it-ibiza' => ['waterfront', 'on_the_beach', 'family_friendly', 'sunset_view'],
        ];

        // Initialize counters
        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        // Begin transaction
        if (! $dryRun) {
            DB::beginTransaction();
        }

        try {
            $this->info('Starting venue specialty update process...');

            // Create a progress bar
            $progressBar = $this->output->createProgressBar(count($venueData));
            $progressBar->start();

            // Process each venue
            foreach ($venueData as $row) {
                // Extract venue data
                $region = $row[0];
                $slug = $row[1];

                // Find the venue
                $venue = Venue::query()->where('slug', $slug)->first();

                if (! $venue) {
                    $this->warn("Venue with slug '{$slug}' not found, skipping.");
                    $skippedCount++;
                    $progressBar->advance();

                    continue;
                }

                // Map specialties from the data
                $specialties = [];
                $i = 2; // Start index for specialty columns

                foreach ($specialtyMapping as $csvHeader => $dbId) {
                    if (isset($row[$i]) && $row[$i] === 'X') {
                        $specialties[] = $dbId;
                    }
                    $i++;
                }

                // Add any additional specialties from our mapping
                if (isset($additionalSpecialties[$slug])) {
                    foreach ($additionalSpecialties[$slug] as $additionalSpecialty) {
                        if (! in_array($additionalSpecialty, $specialties)) {
                            $specialties[] = $additionalSpecialty;
                        }
                    }
                }

                // Update the venue
                $oldSpecialties = $venue->specialty ?? [];

                if (! $dryRun) {
                    $venue->specialty = $specialties;
                    $venue->save();
                }

                $oldSpecialtiesStr = filled($oldSpecialties) ? implode(', ', $oldSpecialties) : 'none';
                $newSpecialtiesStr = filled($specialties) ? implode(', ', $specialties) : 'none';

                $this->line("\nUpdated: {$venue->name} (ID: {$venue->id}, Slug: {$slug})");
                $this->line("  - Old specialties: {$oldSpecialtiesStr}");
                $this->line("  - New specialties: {$newSpecialtiesStr}");

                $updatedCount++;
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Commit transaction
            if (! $dryRun) {
                DB::commit();
                $this->info('Changes committed to database.');
            } else {
                $this->info('DRY RUN: No changes were made to the database.');
            }

            // Get all existing venues
            $allVenues = Venue::query()->orderBy('region')->orderBy('name')->get(['id', 'name', 'slug', 'region']);

            // Create a lookup of slugs that have specialties in our data
            $slugsWithSpecialties = [];
            foreach ($venueData as $row) {
                $slug = $row[1];
                $hasSpecialty = false;

                // Check columns 2-11 for any 'X' values (specialty columns)
                for ($i = 2; $i <= 11; $i++) {
                    if (isset($row[$i]) && $row[$i] === 'X') {
                        $hasSpecialty = true;
                        break;
                    }
                }

                if ($hasSpecialty) {
                    $slugsWithSpecialties[] = $slug;
                }
            }

            // Get venues without specialties by checking against our lookup
            $venuesWithoutSpecialties = $allVenues->filter(fn ($venue) => ! in_array($venue->slug, $slugsWithSpecialties));

            // Summary
            $this->info('Process completed!');
            $this->info("Venues updated: {$updatedCount}");
            $this->info("Venues skipped: {$skippedCount}");
            $this->info("Errors: {$errorCount}");

            if ($venuesWithoutSpecialties->count() > 0) {
                $this->newLine();
                $this->info("VENUES WITHOUT SPECIALTIES ({$venuesWithoutSpecialties->count()} venues):");
                $headers = ['ID', 'Name', 'Slug', 'Region'];
                $rows = [];

                foreach ($venuesWithoutSpecialties as $venue) {
                    $rows[] = [$venue->id, $venue->name, $venue->slug, $venue->region];
                }

                $this->table($headers, $rows);
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            // Rollback transaction on error
            if (! $dryRun) {
                DB::rollBack();
            }

            $this->error('ERROR: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            return self::FAILURE;
        }
    }
}
