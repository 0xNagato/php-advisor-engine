<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sushi\Sushi;

/**
 * @property string $id
 * @property string $name
 * @property string $region Region ID
 * @property-read Region $regionModel
 *
 */
class Neighborhood extends Model
{
    use Sushi;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Get the region that this neighborhood belongs to
     *
     * @return BelongsTo<Region, $this>
     */
    public function regionModel(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region', 'id');
    }

    protected array $rows = [
        // Miami
        [
            'id' => 'brickell',
            'region' => 'miami',
            'name' => 'Brickell',
        ],
        [
            'id' => 'south_beach',
            'region' => 'miami',
            'name' => 'South Beach',
        ],
        [
            'id' => 'wynwood',
            'region' => 'miami',
            'name' => 'Wynwood',
        ],
        [
            'id' => 'coconut_grove',
            'region' => 'miami',
            'name' => 'Coconut Grove',
        ],
        [
            'id' => 'coral_gables',
            'region' => 'miami',
            'name' => 'Coral Gables',
        ],
        [
            'id' => 'edgewater',
            'region' => 'miami',
            'name' => 'Edgewater',
        ],
        [
            'id' => 'downtown_miami',
            'region' => 'miami',
            'name' => 'Downtown Miami',
        ],
        [
            'id' => 'little_havana',
            'region' => 'miami',
            'name' => 'Little Havana',
        ],
        [
            'id' => 'design_district',
            'region' => 'miami',
            'name' => 'Design District',
        ],
        [
            'id' => 'key_biscayne',
            'region' => 'miami',
            'name' => 'Key Biscayne',
        ],
        [
            'id' => 'miami_beach',
            'region' => 'miami',
            'name' => 'Miami Beach',
        ],
        [
            'id' => 'aventura',
            'region' => 'miami',
            'name' => 'Aventura',
        ],
        [
            'id' => 'doral',
            'region' => 'miami',
            'name' => 'Doral',
        ],
        [
            'id' => 'sunny_isles',
            'region' => 'miami',
            'name' => 'Sunny Isles',
        ],
        [
            'id' => 'bal_harbour',
            'region' => 'miami',
            'name' => 'Bal Harbour',
        ],
        [
            'id' => 'surfside',
            'region' => 'miami',
            'name' => 'Surfside',
        ],
        [
            'id' => 'hollywood_beach',
            'region' => 'miami',
            'name' => 'Hollywood Beach',
        ],
        [
            'id' => 'fort_lauderdale',
            'region' => 'miami',
            'name' => 'Fort Lauderdale',
        ],

        // Ibiza
        [
            'id' => 'ibiza_town',
            'region' => 'ibiza',
            'name' => 'Ibiza Town (Eivissa)',
        ],
        [
            'id' => 'sant_antoni',
            'region' => 'ibiza',
            'name' => 'Sant Antoni de Portmany',
        ],
        [
            'id' => 'santa_eularia',
            'region' => 'ibiza',
            'name' => 'Santa Eulària des Riu',
        ],
        [
            'id' => 'playa_en_bossa',
            'region' => 'ibiza',
            'name' => 'Playa d\'en Bossa',
        ],
        [
            'id' => 'talamanca',
            'region' => 'ibiza',
            'name' => 'Talamanca',
        ],
        [
            'id' => 'portinatx',
            'region' => 'ibiza',
            'name' => 'Portinatx',
        ],
        [
            'id' => 'san_jose',
            'region' => 'ibiza',
            'name' => 'San José',
        ],
        [
            'id' => 'figueretas',
            'region' => 'ibiza',
            'name' => 'Figueretas',
        ],
        [
            'id' => 'es_canar',
            'region' => 'ibiza',
            'name' => 'Es Canar',
        ],
        [
            'id' => 'cala_llonga',
            'region' => 'ibiza',
            'name' => 'Cala Llonga',
        ],
        [
            'id' => 'es_cavallet',
            'region' => 'ibiza',
            'name' => 'Es Cavallet',
        ],
        [
            'id' => 'cala_vadella',
            'region' => 'ibiza',
            'name' => 'Cala Vadella',
        ],
        [
            'id' => 'san_miguel',
            'region' => 'ibiza',
            'name' => 'San Miguel',
        ],
        [
            'id' => 'santa_gertrudis',
            'region' => 'ibiza',
            'name' => 'Santa Gertrudis',
        ],
        [
            'id' => 'formentera',
            'region' => 'ibiza',
            'name' => 'Formentera',
        ],
        [
            'id' => 'salinas',
            'region' => 'ibiza',
            'name' => 'Salinas',
        ],
        [
            'id' => 'marina_botafoch',
            'region' => 'ibiza',
            'name' => 'Marina Botafoch',
        ],
        [
            'id' => 'cala_jondal',
            'region' => 'ibiza',
            'name' => 'Cala Jondal',
        ],
        [
            'id' => 'st_jordi',
            'region' => 'ibiza',
            'name' => 'St Jordi',
        ],
        [
            'id' => 'es_pujols',
            'region' => 'ibiza',
            'name' => 'Es Pujols',
        ],

        // Mykonos
        [
            'id' => 'mykonos_town',
            'region' => 'mykonos',
            'name' => 'Mykonos Town (Chora)',
        ],
        [
            'id' => 'little_venice',
            'region' => 'mykonos',
            'name' => 'Little Venice',
        ],
        [
            'id' => 'matogianni_street',
            'region' => 'mykonos',
            'name' => 'Matogianni Street',
        ],
        [
            'id' => 'ornos',
            'region' => 'mykonos',
            'name' => 'Ornos',
        ],
        [
            'id' => 'psarou_beach',
            'region' => 'mykonos',
            'name' => 'Psarou Beach',
        ],
        [
            'id' => 'platis_gialos',
            'region' => 'mykonos',
            'name' => 'Platis Gialos',
        ],
        [
            'id' => 'ano_mera',
            'region' => 'mykonos',
            'name' => 'Ano Mera',
        ],
        [
            'id' => 'agios_ioannis',
            'region' => 'mykonos',
            'name' => 'Agios Ioannis',
        ],
        [
            'id' => 'tourlos',
            'region' => 'mykonos',
            'name' => 'Tourlos',
        ],
        [
            'id' => 'kalafati',
            'region' => 'mykonos',
            'name' => 'Kalafati',
        ],
        [
            'id' => 'paradise_beach',
            'region' => 'mykonos',
            'name' => 'Paradise Beach',
        ],
        [
            'id' => 'super_paradise',
            'region' => 'mykonos',
            'name' => 'Super Paradise Beach',
        ],
        [
            'id' => 'elia_beach',
            'region' => 'mykonos',
            'name' => 'Elia Beach',
        ],

        // Paris
        [
            'id' => 'le_marais',
            'region' => 'paris',
            'name' => 'Le Marais',
        ],
        [
            'id' => 'montmartre',
            'region' => 'paris',
            'name' => 'Montmartre',
        ],
        [
            'id' => 'latin_quarter',
            'region' => 'paris',
            'name' => 'Latin Quarter',
        ],
        [
            'id' => 'champs_elysees',
            'region' => 'paris',
            'name' => 'Champs-Élysées',
        ],
        [
            'id' => 'saint_germain',
            'region' => 'paris',
            'name' => 'Saint-Germain-des-Prés',
        ],
        [
            'id' => 'belleville',
            'region' => 'paris',
            'name' => 'Belleville',
        ],
        [
            'id' => 'la_defense',
            'region' => 'paris',
            'name' => 'La Défense',
        ],
        [
            'id' => 'bastille',
            'region' => 'paris',
            'name' => 'Bastille',
        ],
        [
            'id' => 'canal_saint_martin',
            'region' => 'paris',
            'name' => 'Canal Saint-Martin',
        ],
        [
            'id' => 'pigalle',
            'region' => 'paris',
            'name' => 'Pigalle',
        ],
        [
            'id' => 'opera',
            'region' => 'paris',
            'name' => 'Opéra',
        ],
        [
            'id' => 'louvre',
            'region' => 'paris',
            'name' => 'Louvre',
        ],
        [
            'id' => 'eiffel_tower',
            'region' => 'paris',
            'name' => 'Eiffel Tower (7th arrondissement)',
        ],

        // London
        [
            'id' => 'soho',
            'region' => 'london',
            'name' => 'Soho',
        ],
        [
            'id' => 'camden',
            'region' => 'london',
            'name' => 'Camden',
        ],
        [
            'id' => 'kensington',
            'region' => 'london',
            'name' => 'Kensington',
        ],
        [
            'id' => 'chelsea',
            'region' => 'london',
            'name' => 'Chelsea',
        ],
        [
            'id' => 'notting_hill',
            'region' => 'london',
            'name' => 'Notting Hill',
        ],
        [
            'id' => 'shoreditch',
            'region' => 'london',
            'name' => 'Shoreditch',
        ],
        [
            'id' => 'mayfair',
            'region' => 'london',
            'name' => 'Mayfair',
        ],
        [
            'id' => 'brixton',
            'region' => 'london',
            'name' => 'Brixton',
        ],
        [
            'id' => 'greenwich',
            'region' => 'london',
            'name' => 'Greenwich',
        ],
        [
            'id' => 'canary_wharf',
            'region' => 'london',
            'name' => 'Canary Wharf',
        ],
        [
            'id' => 'covent_garden',
            'region' => 'london',
            'name' => 'Covent Garden',
        ],
        [
            'id' => 'westminster',
            'region' => 'london',
            'name' => 'Westminster',
        ],
        [
            'id' => 'knightsbridge',
            'region' => 'london',
            'name' => 'Knightsbridge',
        ],

        // St. Tropez
        [
            'id' => 'vieux_port',
            'region' => 'st_tropez',
            'name' => 'Vieux Port',
        ],
        [
            'id' => 'la_ponche',
            'region' => 'st_tropez',
            'name' => 'La Ponche',
        ],
        [
            'id' => 'pampelonne_beach',
            'region' => 'st_tropez',
            'name' => 'Pampelonne Beach',
        ],
        [
            'id' => 'les_salins',
            'region' => 'st_tropez',
            'name' => 'Les Salins',
        ],
        [
            'id' => 'gassin',
            'region' => 'st_tropez',
            'name' => 'Gassin',
        ],
        [
            'id' => 'ramatuelle',
            'region' => 'st_tropez',
            'name' => 'Ramatuelle',
        ],
        [
            'id' => 'port_grimaud',
            'region' => 'st_tropez',
            'name' => 'Port Grimaud',
        ],

        // New York
        [
            'id' => 'soho_ny',
            'region' => 'new_york',
            'name' => 'SoHo',
        ],
        [
            'id' => 'harlem',
            'region' => 'new_york',
            'name' => 'Harlem',
        ],
        [
            'id' => 'upper_east_side',
            'region' => 'new_york',
            'name' => 'Upper East Side',
        ],
        [
            'id' => 'tribeca',
            'region' => 'new_york',
            'name' => 'Tribeca',
        ],
        [
            'id' => 'williamsburg',
            'region' => 'new_york',
            'name' => 'Williamsburg',
        ],
        [
            'id' => 'dumbo',
            'region' => 'new_york',
            'name' => 'DUMBO',
        ],
        [
            'id' => 'park_slope',
            'region' => 'new_york',
            'name' => 'Park Slope',
        ],
        [
            'id' => 'astoria',
            'region' => 'new_york',
            'name' => 'Astoria',
        ],
        [
            'id' => 'long_island_city',
            'region' => 'new_york',
            'name' => 'Long Island City',
        ],
        [
            'id' => 'riverdale',
            'region' => 'new_york',
            'name' => 'Riverdale',
        ],
        [
            'id' => 'st_george',
            'region' => 'new_york',
            'name' => 'St. George',
        ],
        [
            'id' => 'chelsea_ny',
            'region' => 'new_york',
            'name' => 'Chelsea',
        ],
        [
            'id' => 'greenwich_village',
            'region' => 'new_york',
            'name' => 'Greenwich Village',
        ],
        [
            'id' => 'financial_district',
            'region' => 'new_york',
            'name' => 'Financial District',
        ],
        [
            'id' => 'west_village',
            'region' => 'new_york',
            'name' => 'West Village',
        ],
        [
            'id' => 'midtown',
            'region' => 'new_york',
            'name' => 'Midtown',
        ],
        [
            'id' => 'upper_west_side',
            'region' => 'new_york',
            'name' => 'Upper West Side',
        ],
        [
            'id' => 'east_village',
            'region' => 'new_york',
            'name' => 'East Village',
        ],

        // Los Angeles
        [
            'id' => 'hollywood',
            'region' => 'los_angeles',
            'name' => 'Hollywood',
        ],
        [
            'id' => 'beverly_hills',
            'region' => 'los_angeles',
            'name' => 'Beverly Hills',
        ],
        [
            'id' => 'downtown_la',
            'region' => 'los_angeles',
            'name' => 'Downtown LA',
        ],
        [
            'id' => 'santa_monica',
            'region' => 'los_angeles',
            'name' => 'Santa Monica',
        ],
        [
            'id' => 'venice_beach',
            'region' => 'los_angeles',
            'name' => 'Venice Beach',
        ],
        [
            'id' => 'silver_lake',
            'region' => 'los_angeles',
            'name' => 'Silver Lake',
        ],
        [
            'id' => 'koreatown',
            'region' => 'los_angeles',
            'name' => 'Koreatown',
        ],
        [
            'id' => 'malibu',
            'region' => 'los_angeles',
            'name' => 'Malibu',
        ],
        [
            'id' => 'echo_park',
            'region' => 'los_angeles',
            'name' => 'Echo Park',
        ],
        [
            'id' => 'westwood',
            'region' => 'los_angeles',
            'name' => 'Westwood',
        ],
        [
            'id' => 'west_hollywood',
            'region' => 'los_angeles',
            'name' => 'West Hollywood',
        ],
        [
            'id' => 'bel_air',
            'region' => 'los_angeles',
            'name' => 'Bel Air',
        ],
        [
            'id' => 'los_feliz',
            'region' => 'los_angeles',
            'name' => 'Los Feliz',
        ],

        // Las Vegas
        [
            'id' => 'the_strip',
            'region' => 'las_vegas',
            'name' => 'The Strip',
        ],
        [
            'id' => 'downtown_las_vegas',
            'region' => 'las_vegas',
            'name' => 'Downtown Las Vegas',
        ],
        [
            'id' => 'summerlin',
            'region' => 'las_vegas',
            'name' => 'Summerlin',
        ],
        [
            'id' => 'henderson',
            'region' => 'las_vegas',
            'name' => 'Henderson',
        ],
        [
            'id' => 'spring_valley',
            'region' => 'las_vegas',
            'name' => 'Spring Valley',
        ],
        [
            'id' => 'green_valley',
            'region' => 'las_vegas',
            'name' => 'Green Valley',
        ],
        [
            'id' => 'anthem',
            'region' => 'las_vegas',
            'name' => 'Anthem',
        ],
        [
            'id' => 'southern_highlands',
            'region' => 'las_vegas',
            'name' => 'Southern Highlands',
        ],
        [
            'id' => 'arts_district',
            'region' => 'las_vegas',
            'name' => 'Arts District',
        ],
        [
            'id' => 'chinatown',
            'region' => 'las_vegas',
            'name' => 'Chinatown',
        ],
    ];
}
