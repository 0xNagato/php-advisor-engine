<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 */
class VenueGroup extends Model
{
    protected $fillable = [
        'name',
        'primary_manager_id',
        'slug',
        'logo_path',
    ];

    protected static function booted()
    {
        static::creating(function ($venueGroup) {
            $venueGroup->slug = str($venueGroup->name)->slug();
        });
    }

    /**
     * @return HasMany<Venue, $this>
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'venue_group_managers')
            ->withPivot('current_venue_id', 'allowed_venue_ids', 'is_current')
            ->withTimestamps();
    }

    public function getAllowedVenueIds(User $user): array
    {
        return json_decode($this->managers->firstWhere('id', $user->id)?->pivot->allowed_venue_ids ?? '[]', true);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function primaryManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_manager_id');
    }

    public function currentVenue(User $user): ?Venue
    {
        $venueId = $this->managers->firstWhere('id', $user->id)?->pivot->current_venue_id;

        return $venueId ? Venue::query()->find($venueId) : null;
    }

    public function switchVenue(User $user, Venue $venue): void
    {
        throw_if($venue->venue_group_id !== $this->id, new InvalidArgumentException('Venue does not belong to this venue group'));

        $allowedVenueIds = $this->getAllowedVenueIds($user);
        throw_if(filled($allowedVenueIds) && ! in_array($venue->id, $allowedVenueIds), new InvalidArgumentException('User is not allowed to access this venue'));

        $this->managers()->updateExistingPivot(
            $user->id,
            ['current_venue_id' => $venue->id]
        );
    }

    /**
     * Get the logo URL for the venue group.
     */
    protected function logo(): Attribute
    {
        return Attribute::make(get: fn () => $this->logo_path
            ? Storage::disk('do')->url($this->logo_path)
            : 'https://ui-avatars.com/api/?background=312596&color=fff&name='.urlencode($this->name));
    }
}
