<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property string code
 */
class VipCode extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['code', 'concierge_id'];

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getAuthPassword(): string
    {
        return $this->code;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function link(): Attribute
    {
        return Attribute::make(get: fn () => route('vip.login').'/'.$this->code);
    }

    /**
     * @return BelongsTo<Concierge, VipCode>
     */
    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    /**
     * @return HasMany<Booking>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasManyThrough<Earning>
     */
    public function earnings(): HasManyThrough
    {
        return $this->hasManyThrough(Earning::class, Booking::class);
    }
}
