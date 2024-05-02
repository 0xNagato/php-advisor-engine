<?php

namespace Database\Seeders;

ini_set('max_execution_time', 0); // 0 = Unlimited
ini_set('memory_limit', '5G');

use App\Models\Partner;
use App\Models\Referral;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantSeeder extends Seeder
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
                'Restaurant Guy Savoy',
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

        foreach ($regions as $region => $restaurants) {
            foreach ($restaurants as $restaurantName) {
                $partner = Partner::inRandomOrder()->first();
                $email = 'restaurant@'.Str::slug($restaurantName).'-'.$region.'.com';

                $user = User::factory([
                    'first_name' => 'Restaurant',
                    'partner_referral_id' => $partner?->id,
                    'email' => $email,
                ])
                    ->has(Restaurant::factory([
                        'restaurant_name' => $restaurantName,
                        'region' => $region,
                    ]))
                    ->create();

                $user->assignRole('restaurant');

                Referral::create([
                    'referrer_id' => $partner?->user->id,
                    'user_id' => $user->id,
                    'email' => $email,
                    'secured_at' => now(),
                    'type' => 'restaurant',
                    'referrer_type' => 'partner',
                ]);
            }
        }
    }
}
