<?php

namespace Database\Seeders;

ini_set('max_execution_time', 0); // 0 = Unlimited
ini_set('memory_limit', '5G');

use App\Enums\VenueStatus;
use App\Models\Partner;
use App\Models\Referral;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = collect([
            'miami' => [
                'Gekkō',
                'Casadonna',
                'Papi Steak',
                'Swan',
                'KOMODO',
                'Call Me Gaby',
                'Sexy Fish',
                'Standard Hotel',
                'RAOs',
                'Kissaki',
            ],
            'ibiza' => [
                'Jondal',
                'Amante',
                'Cotton Beach Club',
                'Sublimotion',
                'Bambuddha',
                'Blue Marlin',
                'La Escollera',
                'Casa Maca',
                'Nobu Hotel Ibiza Bay',
                'Heart Ibiza',
                'Sa Capella',
            ],
            'mykonos' => [
                'Scorpios',
                'Nammos',
                'Ling Ling by Hakkasan',
                'Interni',
                'Alemàgou',
                'Kiki’s Tavern',
                'Sea Satin',
                'Nobu',
                'Bill & Coo',
                'Beefbar',
            ],
            'paris' => [
                'L\'Avenue',
                'Le Meurice',
                'Septime',
                'Le Cinq',
                'Pierre Gagnaire',
                'Arpège',
                'Guy Savoy',
                'L’Ambroisie',
                'Miznon',
                'Frenchie',
            ],
            'london' => [
                'Chiltern Firehouse',
                'The Clove Club',
                'Sketch',
                'Nobu',
                'Dabbous',
                'The Ledbury',
                'Dishoom',
                'The Wolseley',
                'Duck & Waffle',
                'Gymkhana',
            ],
            'los_angeles' => [
                'Catch',
                'Nobu',
                'Bestia',
                'République',
                'Perch',
                'Guelaguetza',
                'Pizzeria Mozza',
                'Trois Mec',
                'Spago',
                'The Bazaar by José Andrés',
            ],
            'new_york' => [
                'Upland',
                'Via Carota',
                'Le Bernardin',
                'Momofuku Ko',
                'Eleven Madison Park',
                'Per Se',
                'Racines',
                'Contra',
                'Lilia',
                'Carbone',
            ],
            'las_vegas' => [
                'Carson Kitchen',
                'Sparrow + Wolf',
                'Joël Robuchon',
                'Picasso',
                'Vetri Cucina',
                'Twist',
                'e by José Andrés',
                'Venue Guy Savoy',
                'Bazaar Meat',
                'Bardot Brasserie',
            ],
            'st_tropez' => [
                'Bagatelle',
                'Nikki Beach',
                'Cheval Blanc',
                'Le Club 55',
                'La Vague d’Or',
                'Le BanH Hoi',
                'Le Petit Lardon',
                'La Ponche',
                'L’Opéra',
                'Le Sénéquier',
            ],
        ]);

        foreach ($regions as $region => $venues) {
            foreach ($venues as $venueName) {
                $partner = Partner::query()->inRandomOrder()->first();
                $email = 'venue@'.Str::slug($venueName).'-'.$region.'.com';

                $user = User::factory([
                    'first_name' => 'Venue',
                    'last_name' => $venueName,
                    'partner_referral_id' => $partner?->id,
                    'email' => $email,
                ])
                    ->has(Venue::factory([
                        'name' => $venueName,
                        'status' => VenueStatus::ACTIVE,
                        'region' => $region,
                    ]))
                    ->create();

                $user->assignRole('venue');

                Referral::query()->create([
                    'referrer_id' => $partner?->user->id,
                    'user_id' => $user->id,
                    'email' => $email,
                    'secured_at' => now(),
                    'type' => 'venue',
                    'referrer_type' => 'partner',
                ]);
            }
        }
    }
}
