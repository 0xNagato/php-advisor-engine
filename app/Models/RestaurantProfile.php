<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantProfile extends Model
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
        'sunday_hours_of_operation',
        'monday_hours_of_operation',
        'tuesday_hours_of_operation',
        'wednesday_hours_of_operation',
        'thursday_hours_of_operation',
        'friday_hours_of_operation',
        'saturday_hours_of_operation',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip',
    ];

    protected $casts = [
        'cuisines' => AsArrayObject::class,
        'price_range' => AsArrayObject::class,
        'sunday_hours_of_operation' => AsArrayObject::class,
        'monday_hours_of_operation' => AsArrayObject::class,
        'tuesday_hours_of_operation' => AsArrayObject::class,
        'wednesday_hours_of_operation' => AsArrayObject::class,
        'thursday_hours_of_operation' => AsArrayObject::class,
        'friday_hours_of_operation' => AsArrayObject::class,
        'saturday_hours_of_operation' => AsArrayObject::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
