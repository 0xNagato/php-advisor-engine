# Venue Booking Investment System Implementation Plan

## Executive Summary

The Venue Booking Investment System replaces the current post-billing invoice model with a simple "covers tracking" approach where venues pay for bookings as they use them, with deposits for future capacity. This system eliminates payment delays while providing venues with transparent, real-time control over their booking investment through a one-page "Booking Investment" portal.

### Business Value

- **Improved Cash Flow**: Venues pay for covers as used + make deposits, eliminating invoice collection delays
- **Real-time Control**: Venues see exactly how many covers they've used vs. paid for
- **Automatic Protection**: System prevents over-spending by suspending service when covers used > covers paid for
- **Instant Reactivation**: One-click payments restore venue availability immediately  
- **First 20 Covers Grace**: New venues get 20 covers free, then pay retroactively to continue
- **Velocity-Based Deposits**: High-usage venues deposit for more future covers

### Key Differentiators from Current System

- **Pay-as-you-go vs. Post-paid**: Pay for covers used + deposit for future vs. monthly invoicing
- **Real-time vs. Delayed**: Instant usage tracking vs. monthly reconciliation
- **Self-service vs. Manual**: Venues control their own payment/deposit management
- **Transparent vs. Opaque**: Clear per-cover costs vs. complex invoice line items
- **Automatic vs. Manual**: System prevents over-spending automatically

## 1. System Requirements: "Covers Tracking with Deposits"

### Core Concept

The new Venue Booking Investment System implements a **covers tracking approach** where venues:

1. **Grace Period**: Receive 20 complimentary covers to start (trial period)
2. **Retroactive Payment**: After using 20 covers, pay for those covers at their prescribed rates
3. **Velocity-Based Deposits**: Make deposits for future covers based on their booking frequency patterns
4. **Automatic Service Control**: System suspends bookings when covers used exceeds covers paid for
5. **Instant Reactivation**: Service resumes immediately upon payment

### System Flow

```
1. Venue onboarding → 20 grace period covers available
2. Covers 1-20 → Track usage, accumulate outstanding balance at venue's rate
3. Cover 21 → Service automatically suspended, payment required
4. Venue pays outstanding balance → Service reactivated  
5. Venue makes velocity-based deposit for future covers → Service continues
6. Process repeats when covers used > covers paid for
```

### Velocity-Based Deposit Logic

The system calculates recommended deposit amounts based on venue booking patterns:

- **High Velocity** (2+ bookings/day): 50 covers deposit
- **Medium Velocity** (1+ bookings/day): 30 covers deposit  
- **Low Velocity** (0.5+ bookings/day): 20 covers deposit
- **Very Low Velocity** (<0.5 bookings/day): 10 covers deposit

## 2. Complex Considerations: Prime Payouts & Cancellations

### A. Prime Booking Payouts (90-Day Challenge)

**Problem**: Prime bookings generate revenue for venues, but bookings can be 90 days out. How do we handle:

- Venue owes money for non-prime covers used
- Venue is owed money for prime covers booked
- Cash flow timing mismatches

**Solution Approach:**

```
Net Position Calculation:
- Covers Owed: (Non-prime covers used × cost_per_cover)
- Covers Credit: (Prime covers confirmed × payout_per_cover)  
- Net Position: Covers Owed - Covers Credit
- Service Status: Active if Net Position ≤ Covers Paid For
```

### B. Cancellation Handling

**Prime Cancellations:**

- Remove from venue's credit balance (reduce money owed to them)
- Don't count toward "covers used" since booking didn't happen
- Adjust net position calculation

**Non-Prime Cancellations:**

- Remove from "covers used" count (venue gets cover back)
- Reduce outstanding balance owed
- Automatically reactivate service if now under limit

## 3. Database Schema (Laravel 11 Migrations)

### A. Migration Commands

```bash
php artisan make:migration create_venue_booking_accounts_table
php artisan make:migration create_venue_cover_transactions_table
php artisan make:migration add_booking_investment_fields_to_venues_table
```

### B. Core Table: `venue_booking_accounts`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_booking_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_group_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Core tracking
            $table->integer('covers_used_total')->default(0);
            $table->integer('covers_paid_for')->default(0);
            $table->integer('outstanding_balance')->default(0); // cents owed
            
            // Prime booking tracking
            $table->integer('prime_covers_pending')->default(0); // future prime bookings
            $table->integer('prime_revenue_pending')->default(0); // cents venue will earn
            
            // Grace period
            $table->boolean('grace_period_active')->default(true);
            $table->integer('grace_covers_used')->default(0);
            $table->integer('grace_amount_owed')->default(0); // cents
            
            // Service status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamp('last_deposit_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['venue_id', 'is_active']);
            $table->index(['grace_period_active', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_booking_accounts');
    }
};
```

### C. Transaction Tracking: `venue_cover_transactions`

```php
<?php

use App\Enums\VenueCoverTransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_cover_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_booking_account_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // VenueCoverTransactionType enum
            $table->integer('covers_amount')->default(0); // number of covers
            $table->integer('financial_amount')->default(0); // cents
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['venue_booking_account_id', 'type']);
            $table->index(['booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_cover_transactions');
    }
};
```

## 4. Enums and Models

### A. Transaction Types Enum

```bash
php artisan make:enum VenueCoverTransactionType
```

```php
<?php

namespace App\Enums;

enum VenueCoverTransactionType: string
{
    case GRACE_COVER_USED = 'grace_cover_used';
    case NON_PRIME_COVER_USED = 'non_prime_cover_used';
    case PRIME_COVER_BOOKED = 'prime_cover_booked';
    case PRIME_COVER_CONFIRMED = 'prime_cover_confirmed';
    case COVER_CANCELLED = 'cover_cancelled';
    case OUTSTANDING_PAYMENT = 'outstanding_payment';
    case DEPOSIT_PAYMENT = 'deposit_payment';
    case REFUND = 'refund';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::GRACE_COVER_USED => 'Grace Period Cover Used',
            self::NON_PRIME_COVER_USED => 'Non-Prime Cover Used',
            self::PRIME_COVER_BOOKED => 'Prime Cover Booked',
            self::PRIME_COVER_CONFIRMED => 'Prime Cover Confirmed',
            self::COVER_CANCELLED => 'Cover Cancelled',
            self::OUTSTANDING_PAYMENT => 'Outstanding Balance Payment',
            self::DEPOSIT_PAYMENT => 'Deposit Payment',
            self::REFUND => 'Refund',
            self::ADJUSTMENT => 'Manual Adjustment',
        };
    }

    public function isDebit(): bool
    {
        return in_array($this, [
            self::GRACE_COVER_USED,
            self::NON_PRIME_COVER_USED,
            self::REFUND,
        ]);
    }
}
```

### B. VenueBookingAccount Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VenueBookingAccount extends Model
{
    protected $fillable = [
        'venue_id',
        'venue_group_id',
        'covers_used_total',
        'covers_paid_for',
        'outstanding_balance',
        'prime_covers_pending',
        'prime_revenue_pending',
        'grace_period_active',
        'grace_covers_used',
        'grace_amount_owed',
        'is_active',
        'last_payment_at',
        'last_deposit_at',
    ];

    protected function casts(): array
    {
        return [
            'grace_period_active' => 'boolean',
            'is_active' => 'boolean',
            'last_payment_at' => 'datetime',
            'last_deposit_at' => 'datetime',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(VenueCoverTransaction::class);
    }

    public function canAcceptBookings(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Grace period: can accept up to 20 covers
        if ($this->grace_period_active && $this->grace_covers_used < 20) {
            return true;
        }

        // Post-grace: net position must be covered by payments
        $netPosition = $this->calculateNetPosition();
        return $netPosition <= $this->covers_paid_for;
    }

    public function calculateNetPosition(): int
    {
        $coversOwed = $this->covers_used_total - $this->grace_covers_used;
        $primeCredit = $this->prime_covers_pending; // Prime covers offset non-prime debt
        
        return max(0, $coversOwed - $primeCredit);
    }

    public function getAvailableCovers(): int
    {
        if ($this->grace_period_active) {
            return 20 - $this->grace_covers_used;
        }

        $netPosition = $this->calculateNetPosition();
        return max(0, $this->covers_paid_for - $netPosition);
    }
}
```

## 5. Core Actions

### A. Track Cover Usage

```bash
php artisan make:action TrackCoverUsage
```

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\VenueCoverTransactionType;
use App\Models\Booking;
use App\Models\VenueCoverTransaction;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class TrackCoverUsage
{
    use AsAction;

    public function handle(Booking $booking): void
    {
        $venue = $booking->venue;
        $account = $venue->bookingAccount;

        if (!$account) {
            throw new \Exception('No booking account found for venue');
        }

        DB::transaction(function () use ($booking, $account) {
            if ($booking->is_prime) {
                $this->trackPrimeCover($booking, $account);
            } else {
                $this->trackNonPrimeCover($booking, $account);
            }

            $this->checkServiceStatus($account);
        });
    }

    private function trackPrimeCover(Booking $booking, $account): void
    {
        $venueEarnings = $this->calculatePrimeEarnings($booking);

        // Prime bookings credit the venue
        $account->increment('prime_covers_pending');
        $account->increment('prime_revenue_pending', $venueEarnings);

        VenueCoverTransaction::create([
            'venue_booking_account_id' => $account->id,
            'type' => VenueCoverTransactionType::PRIME_COVER_BOOKED,
            'covers_amount' => 1,
            'financial_amount' => $venueEarnings,
            'booking_id' => $booking->id,
            'description' => "Prime cover booked #{$booking->id}",
        ]);
    }

    private function trackNonPrimeCover(Booking $booking, $account): void
    {
        $coverCost = $this->calculateNonPrimeCost($booking);

        if ($account->grace_period_active && $account->grace_covers_used < 20) {
            // Grace period usage
            $account->increment('grace_covers_used');
            $account->increment('grace_amount_owed', $coverCost);
            $type = VenueCoverTransactionType::GRACE_COVER_USED;
        } else {
            // Regular usage
            $account->increment('covers_used_total');
            $account->increment('outstanding_balance', $coverCost);
            $type = VenueCoverTransactionType::NON_PRIME_COVER_USED;
        }

        VenueCoverTransaction::create([
            'venue_booking_account_id' => $account->id,
            'type' => $type,
            'covers_amount' => 1,
            'financial_amount' => $coverCost,
            'booking_id' => $booking->id,
            'description' => "Non-prime cover used #{$booking->id}",
        ]);
    }

    private function checkServiceStatus($account): void
    {
        if (!$account->canAcceptBookings()) {
            $account->update(['is_active' => false]);
            
            // Send notification to venue about service suspension
            // VenueServiceSuspended::dispatch($account);
        }
    }

    private function calculatePrimeEarnings(Booking $booking): int
    {
        // Logic to calculate venue earnings from prime booking
        return $booking->total_amount * 60; // 60% to venue, in cents
    }

    private function calculateNonPrimeCost(Booking $booking): int
    {
        $venue = $booking->venue;
        $incentiveFee = $venue->non_prime_fee_per_head * $booking->party_size;
        $processingFee = $incentiveFee * 0.10;
        
        return (int)(($incentiveFee + $processingFee) * 100); // Convert to cents
    }
}
```

### B. Handle Cancellations

```bash
php artisan make:action HandleBookingCancellation
```

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\VenueCoverTransactionType;
use App\Models\Booking;
use App\Models\VenueCoverTransaction;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class HandleBookingCancellation
{
    use AsAction;

    public function handle(Booking $booking): void
    {
        $account = $booking->venue->bookingAccount;

        if (!$account) {
            return;
        }

        DB::transaction(function () use ($booking, $account) {
            if ($booking->is_prime) {
                $this->handlePrimeCancellation($booking, $account);
            } else {
                $this->handleNonPrimeCancellation($booking, $account);
            }

            // Check if cancellation reactivates service
            if (!$account->is_active && $account->canAcceptBookings()) {
                $account->update(['is_active' => true]);
            }
        });
    }

    private function handlePrimeCancellation(Booking $booking, $account): void
    {
        $originalTransaction = VenueCoverTransaction::where('booking_id', $booking->id)
            ->whereIn('type', [
                VenueCoverTransactionType::PRIME_COVER_BOOKED,
                VenueCoverTransactionType::PRIME_COVER_CONFIRMED
            ])
            ->first();

        if (!$originalTransaction) {
            return;
        }

        // Reverse the prime cover credit
        $account->decrement('prime_covers_pending');
        $account->decrement('prime_revenue_pending', $originalTransaction->financial_amount);

        VenueCoverTransaction::create([
            'venue_booking_account_id' => $account->id,
            'type' => VenueCoverTransactionType::COVER_CANCELLED,
            'covers_amount' => -1,
            'financial_amount' => -$originalTransaction->financial_amount,
            'booking_id' => $booking->id,
            'description' => "Prime cover cancelled #{$booking->id}",
        ]);
    }

    private function handleNonPrimeCancellation(Booking $booking, $account): void
    {
        $originalTransaction = VenueCoverTransaction::where('booking_id', $booking->id)
            ->whereIn('type', [
                VenueCoverTransactionType::GRACE_COVER_USED,
                VenueCoverTransactionType::NON_PRIME_COVER_USED
            ])
            ->first();

        if (!$originalTransaction) {
            return;
        }

        // Reverse the cover usage
        if ($originalTransaction->type === VenueCoverTransactionType::GRACE_COVER_USED) {
            $account->decrement('grace_covers_used');
            $account->decrement('grace_amount_owed', $originalTransaction->financial_amount);
        } else {
            $account->decrement('covers_used_total');
            $account->decrement('outstanding_balance', $originalTransaction->financial_amount);
        }

        VenueCoverTransaction::create([
            'venue_booking_account_id' => $account->id,
            'type' => VenueCoverTransactionType::COVER_CANCELLED,
            'covers_amount' => -1,
            'financial_amount' => -$originalTransaction->financial_amount,
            'booking_id' => $booking->id,
            'description' => "Non-prime cover cancelled #{$booking->id}",
        ]);
    }
}
```

## 6. Booking Investment Portal (One-Page Interface)

### A. Dashboard Layout

```
┌─────────────────────────────────────────────────┐
│ BOOKING INVESTMENT PORTAL                       │
├─────────────────────────────────────────────────┤
│ 📊 Current Status: ACTIVE ✅                   │
│                                                 │
│ Covers Used: 23/50                             │
│ ├─ Non-Prime Used: 18 covers                   │
│ ├─ Prime Pending: 5 covers (+$234 credit)      │
│ └─ Net Position: 13 covers owed                │
│                                                 │
│ Outstanding Balance: $117.00                    │
│ Grace Period: Completed ✓                      │
│                                                 │
│ [ Pay Outstanding ] [ Make Deposit ] [ Refund ] │
└─────────────────────────────────────────────────┘
```

### B. Velocity-Based Deposit Calculator

```php
// app/Actions/CalculateVelocityDeposit.php
public function handle(VenueBookingAccount $account): array
{
    $dailyAverage = $this->calculateDailyAverage($account);
    
    $depositCovers = match (true) {
        $dailyAverage >= 2.0 => 50,  // High velocity
        $dailyAverage >= 1.0 => 30,  // Medium velocity  
        $dailyAverage >= 0.5 => 20,  // Low velocity
        default => 10,               // Very low velocity
    };

    $costPerCover = $account->venue->cost_per_credit ?? 900;
    $depositAmount = $depositCovers * $costPerCover;

    return [
        'recommended_covers' => $depositCovers,
        'deposit_amount' => $depositAmount,
        'daily_average' => $dailyAverage,
    ];
}
```

## 7. Stripe Integration

### A. Payment Link Generation

```php
// app/Actions/CreateBookingInvestmentPayment.php
public function handle(VenueBookingAccount $account, string $paymentType, array $options = []): array
{
    $venue = $account->venue;
    
    $lineItems = match ($paymentType) {
        'outstanding' => $this->createOutstandingBalanceItems($account),
        'deposit' => $this->createDepositItems($account, $options['covers'] ?? 20),
        'combined' => array_merge(
            $this->createOutstandingBalanceItems($account),
            $this->createDepositItems($account, $options['covers'] ?? 20)
        ),
    };

    $session = $this->stripeClient->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => route('venue.booking-investment.success') . '?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => route('venue.booking-investment'),
        'metadata' => [
            'venue_id' => $venue->id,
            'payment_type' => $paymentType,
            'booking_account_id' => $account->id,
        ],
    ]);

    return ['session_id' => $session->id, 'url' => $session->url];
}
```

## 8. Admin Interface (FilamentPHP 3)

### A. Venue Booking Account Resource

```php
// app/Filament/Resources/VenueBookingAccountResource.php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('venue.name')
                ->searchable()
                ->sortable(),
                
            Tables\Columns\TextColumn::make('covers_used_total')
                ->label('Covers Used')
                ->badge()
                ->color(fn ($record) => $record->canAcceptBookings() ? 'success' : 'danger'),
                
            Tables\Columns\TextColumn::make('covers_paid_for')
                ->label('Covers Paid'),
                
            Tables\Columns\TextColumn::make('outstanding_balance')
                ->label('Outstanding')
                ->money('USD', divideBy: 100),
                
            Tables\Columns\TextColumn::make('prime_revenue_pending')
                ->label('Prime Credit')
                ->money('USD', divideBy: 100),
                
            Tables\Columns\IconColumn::make('is_active')
                ->boolean()
                ->label('Active'),
                
            Tables\Columns\IconColumn::make('grace_period_active')
                ->boolean()
                ->label('Grace Period'),
        ])
        ->actions([
            Tables\Actions\Action::make('adjust_balance')
                ->label('Adjust')
                ->icon('heroicon-o-calculator')
                ->form([
                    Forms\Components\TextInput::make('covers_adjustment')
                        ->label('Covers Adjustment')
                        ->numeric(),
                    Forms\Components\TextInput::make('amount_adjustment')
                        ->label('Amount Adjustment (cents)')
                        ->numeric(),
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason')
                        ->required(),
                ])
                ->action(function ($record, $data) {
                    // Handle manual adjustments
                }),
        ]);
}
```

## 9. Implementation Timeline

### Phase 1: Core System (Days 1-3)

- [ ] Create database migrations and models
- [ ] Build covers tracking logic with prime/non-prime handling
- [ ] Implement booking validation (canAcceptBookings)
- [ ] Integrate cancellation handling

### Phase 2: Payment Integration (Days 4-6)  

- [ ] Build Stripe payment integration for outstanding + deposits
- [ ] Create velocity-based deposit calculator
- [ ] Implement payment confirmation and account updates
- [ ] Add refund functionality

### Phase 3: Dashboard & Testing (Days 7-8)

- [ ] Build one-page Booking Investment portal
- [ ] Create admin interface for monitoring/adjustments
- [ ] Test with sample data including prime/non-prime scenarios
- [ ] Handle edge cases (cancellations, refunds, adjustments)

### Phase 4: Deployment (Days 9-10)

- [ ] Deploy to production
- [ ] Migrate Ibiza venues to new system
- [ ] Monitor performance and handle issues
- [ ] Document processes for ongoing support

## 10. Testing Scenarios

### A. Core Flow Testing

- Grace period: 20 covers, then payment required
- Mixed bookings: prime + non-prime covers
- Cancellations: before and after payment
- Service suspension and reactivation

### B. Edge Cases

- Venue with only prime bookings (always in credit)
- High cancellation rate scenarios  
- Prime bookings 90 days out
- Venue requesting refunds

This implementation plan provides the technical foundation for the boss's vision while handling the complex realities of prime payouts and cancellations in a booking system.
