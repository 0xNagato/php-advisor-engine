<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'event',
        'payload',
        'user_id',
        'ip_hash',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    const EVENT_SCORED = 'scored';
    const EVENT_APPROVED = 'approved';
    const EVENT_REJECTED = 'rejected';
    const EVENT_WHITELISTED = 'whitelisted';
    const EVENT_BLACKLISTED = 'blacklisted';
    const EVENT_AUTO_APPROVED = 'auto_approved';
    const EVENT_AUTO_HELD = 'auto_held';

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create an audit log entry with PII masking
     */
    public static function createEntry(
        ?int $bookingId,
        string $event,
        array $payload,
        ?int $userId = null,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'booking_id' => $bookingId,
            'event' => $event,
            'payload' => self::maskPII($payload),
            'user_id' => $userId ?? auth()->id(),
            'ip_hash' => $ipAddress ? hash('sha256', $ipAddress) : null,
        ]);
    }

    /**
     * Mask PII in payload data
     */
    protected static function maskPII(array $payload): array
    {
        $masked = $payload;

        // Mask email (only if it's a string)
        if (isset($masked['email']) && is_string($masked['email'])) {
            $masked['email'] = self::maskEmail($masked['email']);
        }

        // Mask phone (only if it's a string)
        if (isset($masked['phone']) && is_string($masked['phone'])) {
            $masked['phone'] = self::maskPhone($masked['phone']);
        }

        // Mask name (only if it's a string)
        if (isset($masked['name']) && is_string($masked['name'])) {
            $masked['name'] = self::maskName($masked['name']);
        }

        // Recursively mask nested arrays
        foreach ($masked as $key => $value) {
            if (is_array($value)) {
                $masked[$key] = self::maskPII($value);
            }
        }

        return $masked;
    }

    /**
     * Mask email address
     */
    protected static function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***.***';
        }

        $localPart = $parts[0];
        $domain = $parts[1];
        $domainParts = explode('.', $domain);

        // Mask local part (keep first 2 chars)
        $maskedLocal = strlen($localPart) > 2
            ? substr($localPart, 0, 2) . str_repeat('*', min(strlen($localPart) - 2, 6))
            : str_repeat('*', strlen($localPart));

        // Mask domain (keep first char and TLD)
        $maskedDomain = strlen($domainParts[0]) > 1
            ? substr($domainParts[0], 0, 1) . str_repeat('*', min(strlen($domainParts[0]) - 1, 4))
            : '*';

        if (count($domainParts) > 1) {
            $maskedDomain .= '.' . end($domainParts);
        }

        return $maskedLocal . '@' . $maskedDomain;
    }

    /**
     * Mask phone number
     */
    protected static function maskPhone(string $phone): string
    {
        if (strlen($phone) < 10) {
            return str_repeat('*', strlen($phone));
        }

        // Keep country code and last 4 digits
        return substr($phone, 0, 2) . str_repeat('*', strlen($phone) - 6) . substr($phone, -4);
    }

    /**
     * Mask name
     */
    protected static function maskName(string $name): string
    {
        $parts = explode(' ', $name);
        $masked = [];

        foreach ($parts as $part) {
            if (strlen($part) > 1) {
                $masked[] = substr($part, 0, 1) . str_repeat('*', min(strlen($part) - 1, 5));
            } else {
                $masked[] = '*';
            }
        }

        return implode(' ', $masked);
    }
}