<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property int $id
 * @property int $vip_code_id
 * @property string $token
 * @property int|null $sanctum_token_id
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read VipCode $vipCode
 * @property-read PersonalAccessToken|null $sanctumToken
 */
class VipSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'vip_code_id',
        'token',
        'sanctum_token_id',
        'expires_at',
        'ip_address',
        'user_agent',
        'started_at',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
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
     * @return BelongsTo<PersonalAccessToken, $this>
     */
    public function sanctumToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'sanctum_token_id');
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
