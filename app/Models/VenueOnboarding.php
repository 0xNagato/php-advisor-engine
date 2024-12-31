<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VenueOnboarding extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'venue_count',
        'has_logos',
        'agreement_accepted',
        'agreement_accepted_at',
        'prime_hours',
        'use_non_prime_incentive',
        'non_prime_per_diem',
        'status',
        'processed_by_id',
        'processed_at',
        'notes',
        'partner_id',
    ];

    /**
     * @return HasMany<VenueOnboardingLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(VenueOnboardingLocation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function partnerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function markAsProcessed(User $user): void
    {
        $this->update([
            'processed_at' => now(),
            'processed_by_id' => $user->id,
            'status' => 'completed',
        ]);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->whereNull('processed_at')
            ->where('status', 'submitted');
    }

    protected function casts(): array
    {
        return [
            'has_logos' => 'boolean',
            'agreement_accepted' => 'boolean',
            'agreement_accepted_at' => 'datetime',
            'prime_hours' => 'array',
            'use_non_prime_incentive' => 'boolean',
            'non_prime_per_diem' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }
}
