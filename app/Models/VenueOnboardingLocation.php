<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VenueOnboardingLocation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'venue_onboarding_id',
        'name',
        'logo_path',
        'created_venue_id',
        'prime_hours',
    ];

    protected $casts = [
        'prime_hours' => 'array',
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
}
