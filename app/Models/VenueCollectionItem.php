<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperVenueCollectionItem
 */
class VenueCollectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_collection_id',
        'venue_id',
        'position',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<VenueCollection, $this>
     */
    public function venueCollection(): BelongsTo
    {
        return $this->belongsTo(VenueCollection::class);
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Scope to only active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order items by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
