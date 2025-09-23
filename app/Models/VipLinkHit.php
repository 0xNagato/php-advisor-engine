<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $vip_code_id
 * @property string|null $code
 * @property Carbon $visited_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $referer_url
 * @property string|null $full_url
 * @property string|null $raw_query
 * @property array|null $query_params
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class VipLinkHit extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'vip_code_id',
        'code',
        'visited_at',
        'ip_address',
        'user_agent',
        'referer_url',
        'full_url',
        'raw_query',
        'query_params',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'query_params' => 'array',
        ];
    }

    /**
     * @return BelongsTo<VipCode, $this>
     */
    public function vipCode(): BelongsTo
    {
        return $this->belongsTo(VipCode::class);
    }
}
