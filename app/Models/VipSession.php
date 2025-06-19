<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vip_code_id
 * @property string $token
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read VipCode $vipCode
 */
class VipSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'vip_code_id',
        'token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<VipCode, $this>
     */
    public function vipCode(): BelongsTo
    {
        return $this->belongsTo(VipCode::class);
    }

    /**
     * Check if this session is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if this session is valid (not expired)
     */
    public function isValid(): bool
    {
        return ! $this->isExpired();
    }
}
