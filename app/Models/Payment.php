<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'amount',
        'currency',
        'status',
    ];

    /**
     * @return HasMany<Earning, $this>
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    /**
     * @return HasMany<PaymentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PaymentItem::class);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => PaymentStatus::PAID,
        ]);
    }

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
        ];
    }
}
