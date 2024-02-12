<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'restaurant_name',
        'contact_phone',
        'website_url',
        'description',
        'cuisines',
        'price_range',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip',
        'payout_restaurant',
        'payout_charity',
        'payout_concierge',
        'payout_platform',
        'secondary_contact_phone',
        'primary_contact_name',
        'secondary_contact_name',
    ];

    protected $casts = [
        'cuisines' => AsArrayObject::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }
}
