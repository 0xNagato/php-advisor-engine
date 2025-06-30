<?php

namespace App\Models;

use AshAllenDesign\ShortURL\Models\ShortURL;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperQrCode
 */
class QrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'url_key',
        'short_url_id',
        'qr_code_path',
        'name',
        'notes',
        'concierge_id',
        'scan_count',
        'last_scanned_at',
        'assigned_at',
        'is_active',
        'meta',
    ];

    /**
     * Get the short URL for this QR code
     */
    public function shortUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->short_url_id) {
                    $shortUrl = ShortURL::query()->find($this->short_url_id);
                    if ($shortUrl) {
                        return $shortUrl->default_short_url;
                    }
                }

                $domain = config('short-url.default_url');
                $prefix = trim((string) config('short-url.prefix'), '/');

                return "{$domain}/{$prefix}/{$this->url_key}";
            }
        );
    }

    /**
     * Get the ShortURL model for this QR code
     *
     * @return BelongsTo<ShortURL, $this>
     */
    public function shortUrlModel(): BelongsTo
    {
        return $this->belongsTo(ShortURL::class, 'short_url_id');
    }

    /**
     * Get the concierge assigned to this QR code (if any)
     *
     * @return BelongsTo<Concierge, $this>
     */
    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    /**
     * Get only active QR codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get only unassigned QR codes
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('concierge_id');
    }

    /**
     * Get only assigned QR codes
     */
    public function scopeAssigned($query)
    {
        return $query->whereNotNull('concierge_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'meta' => 'array',
            'last_scanned_at' => 'datetime',
            'assigned_at' => 'datetime',
        ];
    }
}
