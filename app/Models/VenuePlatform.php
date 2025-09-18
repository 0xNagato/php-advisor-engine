<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperVenuePlatform
 */
class VenuePlatform extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'venue_id',
        'platform_type',
        'is_enabled',
        'configuration',
        'last_synced_at',
    ];

    /**
     * Get the venue that owns the platform.
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Helper method to access configuration values.
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * Helper method to set configuration values.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setConfig(string $key, $value): self
    {
        $config = $this->configuration ?? [];
        data_set($config, $key, $value);
        $this->configuration = $config;

        return $this;
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'is_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }
}
