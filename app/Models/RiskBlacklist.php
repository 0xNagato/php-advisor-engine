<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskBlacklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'value',
        'reason',
        'is_active',
        'created_by',
    ];

    const TYPE_EMAIL = 'email';

    const TYPE_DOMAIN = 'domain';

    const TYPE_PHONE = 'phone';

    const TYPE_IP = 'ip';

    const TYPE_NAME = 'name';

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if a value is blacklisted
     */
    public static function isBlacklisted(string $type, string $value): bool
    {
        return self::query()->where('type', $type)
            ->where('value', strtolower($value))
            ->exists();
    }

    /**
     * Boot method to lowercase values before saving
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (RiskBlacklist $blacklist) {
            $blacklist->value = strtolower($blacklist->value);
        });
    }
}
