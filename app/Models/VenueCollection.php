<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperVenueCollection
 */
class VenueCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'concierge_id',
        'vip_code_id',
        'name',
        'description',
        'is_active',
        'region',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Concierge, $this>
     */
    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    /**
     * @return BelongsTo<VipCode, $this>
     */
    public function vipCode(): BelongsTo
    {
        return $this->belongsTo(VipCode::class);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region', 'id');
    }

    /**
     * @return HasMany<VenueCollectionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(VenueCollectionItem::class);
    }

    /**
     * Get active venue items for this collection, ordered by position
     */
    public function activeItems(): HasMany
    {
        return $this->items()->active()->ordered();
    }

    /**
     * Get venues through collection items
     */
    public function venues()
    {
        return $this->belongsToMany(Venue::class, 'venue_collection_items')
            ->withPivot('note', 'is_active')
            ->withTimestamps();
    }

    /**
     * Scope to only active collections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
