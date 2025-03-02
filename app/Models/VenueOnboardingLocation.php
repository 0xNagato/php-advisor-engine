<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin IdeHelperVenueOnboardingLocation
 */
class VenueOnboardingLocation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'venue_onboarding_id',
        'name',
        'region',
        'logo_path',
        'created_venue_id',
        'prime_hours',
        'booking_hours',
        'use_non_prime_incentive',
        'non_prime_per_diem',
    ];

    /**
     * @return BelongsTo<VenueOnboarding, $this>
     */
    public function onboarding(): BelongsTo
    {
        return $this->belongsTo(VenueOnboarding::class, 'venue_onboarding_id');
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'created_venue_id');
    }

    protected function casts(): array
    {
        return [
            'prime_hours' => 'array',
            'booking_hours' => 'array',
        ];
    }

    protected function logo(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (blank($this->logo_path)) {
                    return null;
                }

                return Storage::disk('do')->url($this->logo_path);
            }
        );
    }
}
