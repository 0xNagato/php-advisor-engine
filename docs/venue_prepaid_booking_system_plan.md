# Venue Prepaid Booking System Implementation Plan

## Executive Summary

The Venue Prepaid Booking System introduces a revolutionary shift from the current post-billing invoice model to a prepaid credit system. This new system eliminates the challenges of venue payment delays and bad debt while providing venues with transparent, real-time control over their booking investment. The system is designed as a one-page "Booking Investment" portal where venues can monitor their spending, make deposits, and track performance in real-time.

### Business Value

- **Improved Cash Flow**: Venues prepay for bookings, eliminating invoice collection delays and bad debt
- **Real-time Control**: Venues see exactly how much they've spent and how many bookings they can afford
- **Transparent Pricing**: Clear cost-per-booking visibility with velocity-based optimization
- **Automatic Protection**: System automatically prevents over-spending by turning off bookings when credits are exhausted
- **Instant Reactivation**: One-click payments restore venue availability immediately
- **First 20 Covers Grace Period**: New venues get their first 20 bookings on credit, then must pay for them to continue

### Key Differentiators from Legacy Invoice System

- **Prepaid vs. Post-paid**: Credits purchased upfront instead of monthly invoicing
- **Real-time vs. Delayed**: Instant booking deduction vs. monthly reconciliation
- **Self-service vs. Manual**: Venues control their own credit management
- **Transparent vs. Opaque**: Clear per-booking costs vs. complex invoice line items
- **Automatic vs. Manual**: System prevents over-spending automatically

## 1. Current System Understanding

### A. Current Invoice-Based System

The existing system operates on a post-billing model:

- **Prime Bookings**: Venues receive 60% of booking fees (positive earnings)
- **Non-Prime Bookings**: Venues pay incentive fees plus 10% processing (negative earnings)
- **Monthly Invoices**: Generated via `GenerateVenueInvoice` and `GenerateVenueGroupInvoice`
- **Stripe Integration**: Invoice URLs created via `CreateStripeVenueInvoice`
- **Payment Delays**: 15-30 day payment terms creating cash flow issues

### B. Current Earnings Calculation

- **Prime**: Calculated in `PrimeEarningsCalculationService`
- **Non-Prime**: Calculated in `NonPrimeEarningsCalculationService`
- **Tracking**: All earnings stored in `earnings` table with `EarningType` enum
- **Venue Payments**: Tracked via `VENUE_PAID` earning type

## 2. New Prepaid System Architecture

### A. Core Concept: Booking Credits

Instead of monthly invoices, venues purchase "booking credits" that are consumed with each confirmed booking:

- **Credit Value**: Each credit = 1 confirmed booking
- **Dynamic Pricing**: Credit cost varies by venue's non-prime incentive settings
- **Velocity-Based Deposits**: Deposit amounts adjust based on booking frequency
- **Real-time Deduction**: Credits consumed immediately upon booking confirmation

### B. First 20 Covers Grace Period Model

- **Grace Period**: New venues get 20 booking credits on a grace period (not free)
- **Payment Required**: After 20 bookings, venues must pay for those initial bookings at their rate
- **Deposit Required**: Venues must also make a deposit for future bookings to continue operations
- **Service Suspension**: If payment isn't made, venue bookings are disabled until payment is received

## 3. Required Database Changes

### A. Migration Commands

```bash
php artisan make:migration create_venue_credit_accounts_table
php artisan make:migration create_venue_credit_transactions_table  
php artisan make:migration create_venue_credit_deposit_links_table
php artisan make:migration add_prepaid_system_fields_to_venues_table
```

### B. Migration Files

#### `create_venue_credit_accounts_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_credit_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('current_balance')->default(0);
            $table->integer('total_purchased')->default(0);
            $table->integer('total_consumed')->default(0);
            $table->integer('grace_credits_used')->default(0);
            $table->integer('grace_credits_owed')->default(0); // in cents
            $table->boolean('grace_period_active')->default(true);
            $table->timestamp('grace_period_expired_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_deposit_at')->nullable();
            $table->timestamps();
            
            $table->index(['venue_id', 'is_active']);
            $table->index(['grace_period_active', 'grace_credits_used']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_credit_accounts');
    }
};
```

#### `create_venue_credit_transactions_table.php`

```php
<?php

use App\Enums\VenueCreditTransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_credit_account_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // Will cast to VenueCreditTransactionType enum
            $table->integer('amount'); // credits
            $table->integer('cost_per_credit')->nullable(); // cents per credit
            $table->integer('total_cost')->nullable(); // total cents
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['venue_credit_account_id', 'type']);
            $table->index(['booking_id']);
            $table->index(['stripe_payment_intent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_credit_transactions');
    }
};
```

#### `add_prepaid_system_fields_to_venues_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->boolean('uses_prepaid_system')->default(false)->after('uses_covermanager');
            $table->integer('cost_per_credit')->nullable()->after('uses_prepaid_system'); // in cents
            $table->integer('minimum_credit_balance')->default(10)->after('cost_per_credit');
            $table->boolean('auto_recharge_enabled')->default(false)->after('minimum_credit_balance');
            $table->integer('auto_recharge_threshold')->default(5)->after('auto_recharge_enabled');
            $table->integer('auto_recharge_amount')->default(20)->after('auto_recharge_threshold');
            
            $table->index(['uses_prepaid_system', 'is_suspended']);
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn([
                'uses_prepaid_system',
                'cost_per_credit',
                'minimum_credit_balance',
                'auto_recharge_enabled',
                'auto_recharge_threshold',
                'auto_recharge_amount',
            ]);
        });
    }
};
```

## 4. Enums and Data Classes

### A. Create Enums

```bash
php artisan make:enum VenueCreditTransactionType
php artisan make:enum VenueCreditDepositStatus
```

#### `app/Enums/VenueCreditTransactionType.php`

```php
<?php

namespace App\Enums;

enum VenueCreditTransactionType: string
{
    case DEPOSIT = 'deposit';
    case CONSUMPTION = 'consumption';
    case GRACE_CONSUMPTION = 'grace_consumption';
    case REFUND = 'refund';
    case ADJUSTMENT = 'adjustment';
    case AUTO_RECHARGE = 'auto_recharge';
    case GRACE_PAYMENT = 'grace_payment';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'Credit Purchase',
            self::CONSUMPTION => 'Booking Credit Used',
            self::GRACE_CONSUMPTION => 'Grace Period Booking',
            self::REFUND => 'Credit Refund',
            self::ADJUSTMENT => 'Manual Adjustment',
            self::AUTO_RECHARGE => 'Auto Recharge',
            self::GRACE_PAYMENT => 'Grace Period Payment',
        };
    }

    public function isDebit(): bool
    {
        return in_array($this, [
            self::CONSUMPTION,
            self::GRACE_CONSUMPTION,
            self::REFUND,
        ]);
    }
}
```

## 5. Models

### A. Create Models

```bash
php artisan make:model VenueCreditAccount
php artisan make:model VenueCreditTransaction
php artisan make:model VenueCreditDepositLink
```

#### `app/Models/VenueCreditAccount.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin IdeHelperVenueCreditAccount
 */
class VenueCreditAccount extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'venue_id',
        'venue_group_id',
        'current_balance',
        'total_purchased',
        'total_consumed',
        'grace_credits_used',
        'grace_credits_owed',
        'grace_period_active',
        'grace_period_expired_at',
        'is_active',
        'last_deposit_at',
    ];

    protected function casts(): array
    {
        return [
            'grace_period_active' => 'boolean',
            'is_active' => 'boolean',
            'grace_period_expired_at' => 'datetime',
            'last_deposit_at' => 'datetime',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function venueGroup(): BelongsTo
    {
        return $this->belongsTo(VenueGroup::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(VenueCreditTransaction::class);
    }

    public function depositLinks(): HasMany
    {
        return $this->hasMany(VenueCreditDepositLink::class);
    }

    public function canAcceptBookings(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Can accept if in grace period and under 20 bookings
        if ($this->grace_period_active && $this->grace_credits_used < 20) {
            return true;
        }

        // Can accept if has purchased credits
        if ($this->current_balance > 0) {
            return true;
        }

        return false;
    }

    public function getTotalCreditsAvailable(): int
    {
        $available = $this->current_balance;
        
        if ($this->grace_period_active) {
            $available += (20 - $this->grace_credits_used);
        }
        
        return max(0, $available);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['current_balance', 'is_active', 'grace_credits_used'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

## 6. Actions

### A. Create Action Classes

```bash
php artisan make:action CalculateVenueCreditCost
php artisan make:action ConsumeVenueCredit
php artisan make:action CreateCreditPurchaseSession
php artisan make:action ProcessCreditPurchase
php artisan make:action MigrateVenueToPrepaipSystem
```

#### `app/Actions/CalculateVenueCreditCost.php`

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Venue;
use Lorisleiva\Actions\Concerns\AsAction;

class CalculateVenueCreditCost
{
    use AsAction;

    public function handle(Venue $venue): int
    {
        if (!$venue->use_non_prime_incentive) {
            return 0; // Prime-only venues don't pay for bookings
        }
        
        $incentiveFee = $venue->non_prime_fee_per_head * 2; // Average party size
        $processingFee = $incentiveFee * 0.10; // 10% processing fee
        $totalCostPerBooking = $incentiveFee + $processingFee;
        
        return (int) ($totalCostPerBooking * 100); // Convert to cents
    }

    public function getVolumeDiscount(Venue $venue): float
    {
        $monthlyBookings = $venue->bookings()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return match (true) {
            $monthlyBookings >= 31 => 0.10, // 10% discount for high volume
            $monthlyBookings >= 11 => 0.05, // 5% discount for medium volume
            default => 0.00, // No discount for low volume
        };
    }
}
```

#### `app/Actions/ConsumeVenueCredit.php`

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\VenueCreditTransactionType;
use App\Events\VenueGracePeriodCompleted;
use App\Events\VenueLowCreditBalance;
use App\Exceptions\InsufficientCreditsException;
use App\Models\Booking;
use App\Models\VenueCreditTransaction;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ConsumeVenueCredit
{
    use AsAction;

    public function handle(Booking $booking): void
    {
        $venue = $booking->venue;
        $account = $venue->creditAccount;

        if (!$account) {
            throw new InsufficientCreditsException('No credit account found for venue');
        }

        DB::transaction(function () use ($booking, $account) {
            if ($account->grace_period_active && $account->grace_credits_used < 20) {
                $this->consumeGraceCredit($booking, $account);
            } elseif ($account->current_balance > 0) {
                $this->consumePurchasedCredit($booking, $account);
            } else {
                throw new InsufficientCreditsException('No credits available');
            }

            $this->checkLowBalanceAlert($account);
        });
    }

    private function consumeGraceCredit(Booking $booking, $account): void
    {
        $costPerCredit = $booking->venue->cost_per_credit;
        
        $account->increment('grace_credits_used');
        $account->increment('grace_credits_owed', $costPerCredit);

        VenueCreditTransaction::create([
            'venue_credit_account_id' => $account->id,
            'type' => VenueCreditTransactionType::GRACE_CONSUMPTION,
            'amount' => 1,
            'cost_per_credit' => $costPerCredit,
            'total_cost' => $costPerCredit,
            'booking_id' => $booking->id,
            'description' => "Grace period booking #{$booking->id}",
        ]);

        if ($account->grace_credits_used >= 20) {
            $account->update([
                'grace_period_active' => false,
                'grace_period_expired_at' => now(),
            ]);
            
            VenueGracePeriodCompleted::dispatch($account);
        }
    }

    private function consumePurchasedCredit(Booking $booking, $account): void
    {
        $costPerCredit = $booking->venue->cost_per_credit;
        
        $account->decrement('current_balance');
        $account->increment('total_consumed');

        VenueCreditTransaction::create([
            'venue_credit_account_id' => $account->id,
            'type' => VenueCreditTransactionType::CONSUMPTION,
            'amount' => 1,
            'cost_per_credit' => $costPerCredit,
            'total_cost' => $costPerCredit,
            'booking_id' => $booking->id,
            'description' => "Booking #{$booking->id}",
        ]);
    }

    private function checkLowBalanceAlert($account): void
    {
        if ($account->current_balance <= $account->venue->minimum_credit_balance) {
            VenueLowCreditBalance::dispatch($account);
        }
    }
}
```

## 7. Events and Listeners

### A. Create Events

```bash
php artisan make:event VenueGracePeriodCompleted
php artisan make:event VenueLowCreditBalance
php artisan make:event VenueCreditPurchased
```

#### `app/Events/VenueGracePeriodCompleted.php`

```php
<?php

namespace App\Events;

use App\Models\VenueCreditAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VenueGracePeriodCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public VenueCreditAccount $account
    ) {}
}
```

### B. Create Listeners

```bash
php artisan make:listener SendGracePeriodPaymentNotice
php artisan make:listener SendLowCreditAlert
```

## 8. FilamentPHP 3 Resources

### A. Create Filament Resources

```bash
php artisan make:filament-resource VenueCreditAccount --generate
php artisan make:filament-resource VenueCreditTransaction --generate
```

#### `app/Filament/Resources/VenueCreditAccountResource.php`

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VenueCreditAccountResource\Pages;
use App\Models\VenueCreditAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class VenueCreditAccountResource extends Resource
{
    protected static ?string $model = VenueCreditAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Venue Management';

    protected static ?string $navigationLabel = 'Credit Accounts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('venue_id')
                    ->relationship('venue', 'name')
                    ->required()
                    ->searchable(),
                    
                Forms\Components\TextInput::make('current_balance')
                    ->numeric()
                    ->required(),
                    
                Forms\Components\TextInput::make('grace_credits_owed')
                    ->numeric()
                    ->suffix('cents'),
                    
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                    
                Forms\Components\Toggle::make('grace_period_active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venue.name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Credits')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state > 20 => 'success',
                        $state > 5 => 'warning',
                        default => 'danger',
                    }),
                    
                Tables\Columns\TextColumn::make('grace_credits_used')
                    ->label('Grace Used')
                    ->visible(fn ($record) => $record->grace_period_active),
                    
                Tables\Columns\TextColumn::make('grace_credits_owed')
                    ->label('Grace Owed')
                    ->money('USD', divideBy: 100)
                    ->visible(fn ($record) => $record->grace_credits_owed > 0),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                    
                Tables\Columns\IconColumn::make('grace_period_active')
                    ->boolean()
                    ->label('Grace Period'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('grace_period_active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Account Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('venue.name'),
                        Infolists\Components\TextEntry::make('current_balance')
                            ->label('Available Credits'),
                        Infolists\Components\TextEntry::make('total_purchased')
                            ->label('Total Purchased'),
                        Infolists\Components\TextEntry::make('total_consumed')
                            ->label('Total Used'),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Grace Period')
                    ->schema([
                        Infolists\Components\TextEntry::make('grace_credits_used')
                            ->label('Grace Credits Used'),
                        Infolists\Components\TextEntry::make('grace_credits_owed')
                            ->label('Amount Owed')
                            ->money('USD', divideBy: 100),
                        Infolists\Components\IconEntry::make('grace_period_active')
                            ->boolean(),
                    ])->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVenueCreditAccounts::route('/'),
            'create' => Pages\CreateVenueCreditAccount::route('/create'),
            'view' => Pages\ViewVenueCreditAccount::route('/{record}'),
            'edit' => Pages\EditVenueCreditAccount::route('/{record}/edit'),
        ];
    }
}
```

## 9. Livewire Components

### A. Create Livewire Components

```bash
php artisan make:livewire VenueManager/BookingInvestmentPortal
```

#### `app/Livewire/VenueManager/BookingInvestmentPortal.php`

```php
<?php

namespace App\Livewire\VenueManager;

use App\Actions\CreateCreditPurchaseSession;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BookingInvestmentPortal extends Component
{
    public ?int $customAmount = null;
    
    #[Computed]
    public function creditAccount()
    {
        return auth()->user()->currentVenue()?->creditAccount;
    }
    
    #[Computed]
    public function venue()
    {
        return auth()->user()->currentVenue();
    }

    public function purchaseCredits(int $credits): void
    {
        $session = CreateCreditPurchaseSession::run(
            $this->creditAccount,
            $credits,
            route('venue.booking-investment')
        );

        $this->redirect($session['url']);
    }

    public function render()
    {
        return view('livewire.venue-manager.booking-investment-portal');
    }
}
```

## 10. Jobs and Queues

### A. Create Jobs

```bash
php artisan make:job ProcessAutoRecharge
php artisan make:job SendGracePeriodReminder
```

#### `app/Jobs/ProcessAutoRecharge.php`

```php
<?php

namespace App\Jobs;

use App\Actions\CreateCreditPurchaseSession;
use App\Models\VenueCreditAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAutoRecharge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public VenueCreditAccount $account
    ) {}

    public function handle(): void
    {
        if (!$this->account->venue->auto_recharge_enabled) {
            return;
        }

        if ($this->account->current_balance > $this->account->venue->auto_recharge_threshold) {
            return;
        }

        // Process auto-recharge using stored payment method
        // Implementation depends on Stripe setup
    }
}
```

## 11. Validation and Form Requests

### A. Create Form Requests

```bash
php artisan make:request VenueCreditPurchaseRequest
```

#### `app/Http/Requests/VenueCreditPurchaseRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VenueCreditPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasActiveRole(['venue_manager', 'venue']);
    }

    public function rules(): array
    {
        return [
            'credits' => ['required', 'integer', 'min:1', 'max:1000'],
            'success_url' => ['required', 'url'],
            'cancel_url' => ['required', 'url'],
        ];
    }
}
```

## 12. Booking Investment Portal (One-Page Interface)

The Booking Investment Portal is the centerpiece of the venue experience - a single page where venues can monitor, manage, and invest in their booking credits. This replaces the complex invoice system with a transparent, real-time dashboard.

### A. Dashboard Components

#### Real-time Credit Balance

```
┌─────────────────────────────────────┐
│ Current Credit Balance: 45 credits  │
│ Est. bookings remaining: ~45        │
│ Last used: 2 hours ago             │
└─────────────────────────────────────┘
```

#### Velocity Tracker

```
┌─────────────────────────────────────┐
│ This Month: 23 bookings used       │
│ Average per day: 1.2 bookings      │
│ Projected monthly usage: 36         │
│ Days until depletion: 38 days      │
└─────────────────────────────────────┘
```

#### Quick Purchase Actions

```
┌─────────────────────────────────────┐
│ Quick Purchase:                     │
│ [ 20 credits - $180 ] [ 50 credits - $425 ] │
│ [ 100 credits - $800 ] [ Custom Amount ]    │
└─────────────────────────────────────┘
```

#### Performance Metrics

```
┌─────────────────────────────────────┐
│ Cost per booking: $9.00             │
│ Total invested: $1,440              │
│ Bookings generated: 160             │
│ Average booking value: $127         │
│ ROI: 2,244%                        │
└─────────────────────────────────────┘
```

#### Grace Period Status

```
┌─────────────────────────────────────┐
│ Grace Period: 7 bookings remaining  │
│ Grace credits used: 13/20           │
│ Amount owed: $117.00                │
│ Payment required after 20 bookings  │
└─────────────────────────────────────┘
```

### B. Status Indicators

- **Green (Active)**: Sufficient credits, accepting bookings
- **Yellow (Low)**: Credits below threshold, warning displayed
- **Red (Inactive)**: No credits, bookings disabled
- **Blue (Auto-recharge)**: Automatic recharge enabled

### C. Livewire Implementation

## 13. Dashboard UI Components (Livewire/Vue)

### A. Grace Period Status Component

```php
// resources/views/livewire/venue-manager/grace-period-status.blade.php
<div class="p-4 border border-blue-200 rounded-lg bg-blue-50">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-blue-900">Grace Period Active</h3>
            <p class="text-blue-700">{{ 20 - $account->grace_credits_used }} bookings remaining</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-blue-600">Amount owed:</p>
            <p class="text-xl font-bold text-blue-900">{{ money($account->grace_credits_owed, 'USD') }}</p>
        </div>
    </div>
</div>
```

### B. Credit Balance Widget

```php
// app/Filament/Widgets/VenueCreditBalanceWidget.php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VenueCreditBalanceWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $account = auth()->user()->currentVenue()?->creditAccount;
        
        return [
            Stat::make('Available Credits', $account->current_balance)
                ->description('Credits ready to use')
                ->color($account->current_balance > 10 ? 'success' : 'danger'),
                
            Stat::make('Grace Period', $account->grace_period_active ? 'Active' : 'Completed')
                ->description($account->grace_credits_used . '/20 used')
                ->color($account->grace_period_active ? 'warning' : 'success'),
        ];
    }
}
```

## 14. Stripe Integration Architecture

### A. Checkout Session Flow

The Stripe integration is designed for simplicity and immediate activation:

1. **Venue Clicks Purchase**: Selects credit amount from dashboard
2. **Stripe Checkout**: Creates checkout session with metadata
3. **Payment Processing**: Stripe handles payment collection
4. **Webhook Confirmation**: Credits added upon successful payment
5. **Instant Activation**: Venue immediately able to receive bookings

### B. Stripe Metadata Structure

Every checkout session includes comprehensive metadata for tracking:

```json
{
  "venue_id": "123",
  "venue_group_id": "456",
  "credits_purchasing": "50",
  "cost_per_credit": "900",
  "deposit_link_id": "789",
  "booking_investment_session": "true"
}
```

### C. Webhook Handling Architecture

The webhook system ensures reliable credit delivery:

- **Idempotency**: Prevents duplicate credit additions
- **Retry Logic**: Handles failed webhook deliveries
- **Audit Trail**: Complete transaction logging
- **Real-time Updates**: Immediate dashboard refreshes

### D. Implementation

```php
// app/Actions/CreateCreditPurchaseSession.php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VenueCreditAccount;
use App\Models\VenueCreditDepositLink;
use Lorisleiva\Actions\Concerns\AsAction;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class CreateCreditPurchaseSession
{
    use AsAction;

    public function __construct(
        private StripeClient $stripeClient
    ) {}

    public function handle(VenueCreditAccount $account, int $credits, string $successUrl, string $cancelUrl = null): array
    {
        $venue = $account->venue;
        $costPerCredit = $venue->cost_per_credit;
        $totalCost = $credits * $costPerCredit;
        
        // Create deposit link record for tracking
        $depositLink = VenueCreditDepositLink::create([
            'venue_credit_account_id' => $account->id,
            'credits_to_purchase' => $credits,
            'cost_per_credit' => $costPerCredit,
            'total_cost' => $totalCost,
            'status' => VenueCreditDepositStatus::PENDING,
            'expires_at' => now()->addHours(2),
        ]);

        $session = $this->stripeClient->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => "PRIMA Booking Credits ({$credits} credits)",
                        'description' => "Booking investment credits for {$venue->name}",
                    ],
                    'unit_amount' => $costPerCredit,
                ],
                'quantity' => $credits,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl ?: $successUrl,
            'metadata' => [
                'venue_id' => $venue->id,
                'venue_group_id' => $venue->venue_group_id,
                'credits_purchasing' => $credits,
                'cost_per_credit' => $costPerCredit,
                'deposit_link_id' => $depositLink->id,
                'booking_investment_session' => 'true',
            ],
            'customer_email' => $venue->user->email,
            'expires_at' => now()->addHours(2)->timestamp,
        ]);

        $depositLink->update([
            'stripe_checkout_session_id' => $session->id,
        ]);

        return [
            'session_id' => $session->id,
            'url' => $session->url,
            'deposit_link_id' => $depositLink->id,
        ];
    }
}
```

## 15. Booking Flow Changes

### A. Pre-Booking Validation

The booking creation process now includes credit validation as a crucial step:

**Current Flow Enhancement:**

1. Standard venue/time/party size validation
2. **NEW**: Credit availability check
3. Concierge assignment
4. Booking creation
5. **NEW**: Credit consumption upon confirmation

**Grace Period Logic:**

- Venues in grace period can accept bookings up to 20
- Each booking accumulates debt but doesn't require immediate payment
- After 20 bookings, payment is required to continue

**Credit System Logic:**

- Venues with purchased credits consume one credit per booking
- Low balance alerts trigger at configurable thresholds
- Auto-recharge can prevent service interruption

### B. Credit Consumption Timing

Credits are consumed at the moment of booking confirmation, not creation. This ensures:

- No credits lost on cancelled bookings
- Accurate accounting of actual venue utilization
- Proper handling of no-shows and cancellations

### C. Error Handling

The system provides clear, actionable error messages:

- "No credits available - Please add credits to continue accepting bookings"
- "Grace period expired - Payment required for previous bookings"
- "Credit purchase in progress - Bookings will resume shortly"

## 16. Auto-recharge System

### A. Configuration Options

Venues can customize auto-recharge behavior:

- **Threshold**: When credits drop below X (default: 5)
- **Amount**: Purchase Y credits (default: 20)
- **Payment Method**: Stored Stripe payment method
- **Notifications**: Email alerts before auto-recharge

### B. Implementation Logic

The auto-recharge system operates on multiple triggers:

1. **Post-booking**: Check after each credit consumption
2. **Daily**: Scheduled check for all venues
3. **Manual**: Venue can trigger immediate check

### C. Safety Mechanisms

- **Spending Limits**: Maximum auto-recharge amount per month
- **Failure Handling**: Graceful degradation if payment fails
- **Override Controls**: Admin can disable for specific venues
- **Notification Chain**: Multiple alerts before account suspension

### D. Code Implementation

```php
// app/Jobs/ProcessAutoRecharge.php
<?php

namespace App\Jobs;

use App\Actions\CreateCreditPurchaseSession;
use App\Models\VenueCreditAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAutoRecharge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public VenueCreditAccount $account
    ) {}

    public function handle(): void
    {
        if (!$this->account->venue->auto_recharge_enabled) {
            return;
        }

        if ($this->account->current_balance > $this->account->venue->auto_recharge_threshold) {
            return;
        }

        // Process auto-recharge using stored payment method
        // Implementation depends on Stripe customer setup
        $this->processAutomaticRecharge();
    }

    private function processAutomaticRecharge(): void
    {
        // Create automatic checkout session or use stored payment method
        // Send confirmation email to venue
        // Update account balance
        // Log transaction for audit trail
    }
}
```

## 17. Migration Strategy

### A. Phased Rollout Approach

The migration follows a careful, risk-managed approach:

**Phase 1: Ibiza Venues (Immediate)**

- All Ibiza venues migrate immediately
- Intensive monitoring and support
- Quick iteration based on feedback

**Phase 2: High-Volume Venues**

- Venues with 30+ monthly bookings
- Proven system stability
- Volume discount incentives

**Phase 3: Medium-Volume Venues**

- Venues with 10-30 monthly bookings
- Standard rollout process
- Group training sessions

**Phase 4: All Remaining Venues**

- Complete system migration
- Legacy invoice system deprecation
- Full feature utilization

### B. Migration Process Per Venue

Each venue migration follows a standardized process:

1. **Credit Cost Calculation**: Based on current non-prime settings
2. **Account Creation**: With 20 grace period credits
3. **Dashboard Access**: Immediate portal availability
4. **Training**: Video tutorials and documentation
5. **Monitoring**: 30-day intensive support period

### C. Rollback Strategy

If issues arise, the system supports graceful rollback:

- **Data Preservation**: All credit data maintained
- **Invoice Restoration**: Can revert to legacy invoicing
- **Service Continuity**: No booking interruption
- **Credit Refunds**: Unused credits fully refundable

### D. Command-Line Migration Tool

```bash
php artisan venue:migrate-to-prepaid --region=ibiza --dry-run
php artisan venue:migrate-to-prepaid --venue=123
php artisan venue:migrate-to-prepaid --region=ibiza
```

## 18. Admin Interface Enhancements

### A. Venue Credit Management Dashboard

**Credit Account Overview**

- Real-time balance monitoring across all venues
- Grace period status tracking
- Payment collection queue
- Low balance alerts and resolution

**Manual Adjustment Tools**

- Add/remove credits with audit trail
- Grace period extensions
- Emergency account reactivation
- Bulk credit operations

**Refund Processing**

- Streamlined refund workflow
- Automatic credit removal
- Audit trail maintenance
- Customer communication

### B. Analytics and Reporting

**System Performance Metrics**

- Total credits sold vs. consumed
- Grace period conversion rates
- Average credit purchase size
- Payment collection efficiency

**Venue Performance Analysis**

- Booking velocity by venue
- Credit utilization patterns
- ROI calculations
- Predictive usage modeling

**Financial Reporting**

- Revenue recognition for prepaid credits
- Outstanding grace period receivables
- Refund liability tracking
- Cash flow improvement metrics

### C. FilamentPHP Implementation

The admin interface leverages FilamentPHP 3's advanced features:

- **Real-time Updates**: LiveWire integration for instant data refresh
- **Advanced Filtering**: Multi-criteria venue search and filtering
- **Bulk Operations**: Mass credit adjustments and migrations
- **Export Capabilities**: CSV/Excel export for financial reporting

## 19. Success Metrics and KPIs

### A. Financial Metrics

**Cash Flow Improvement**

- Days Sales Outstanding (DSO) reduction: Target 80% improvement
- Bad debt elimination: Target 100% reduction in uncollectable invoices
- Collection efficiency: Target 99% first-payment success rate

**Revenue Optimization**

- Prepaid revenue recognition timing
- Credit utilization rates (target: 85%+)
- Auto-recharge adoption (target: 60%+)

### B. Operational Metrics

**Venue Satisfaction**

- Portal usage rates: Target 90% daily active usage
- Support ticket reduction: Target 50% decrease in payment-related issues
- Grace period conversion: Target 90% payment rate after 20 bookings

**System Performance**

- Payment processing time: Target <30 seconds end-to-end
- Credit availability: Target 99.9% uptime
- Booking flow latency: Target <2 second credit validation

### C. Technical Metrics

**Reliability Measures**

- Transaction success rate: Target 99.5%
- Webhook processing reliability: Target 99.9%
- Database performance: Target <100ms query response

**Scalability Indicators**

- Concurrent venue capacity
- Peak transaction throughput
- Storage growth patterns

## 20. Risk Mitigation

### A. Technical Risks

**Stripe Service Interruption**

- Fallback to manual credit addition
- Offline payment processing capability
- Real-time status monitoring and alerts

**Database Performance**

- Optimized indexing for credit queries
- Read replicas for reporting
- Automated query performance monitoring

**Data Consistency**

- Transaction-wrapped credit operations
- Audit logging for all credit changes
- Regular data integrity checks

### B. Business Risks

**Grace Period Non-Payment**

- Automated reminder sequence
- Personal outreach for high-value venues
- Grace period extension policies

**Venue Resistance to Change**

- Comprehensive training programs
- Dedicated onboarding support
- Gradual feature introduction

**Cash Flow Timing**

- Staged venue migration
- Financial impact modeling
- Revenue recognition planning

### C. Operational Risks

**Support Overwhelm**

- Detailed self-service documentation
- Chatbot for common questions
- Escalation procedures for complex issues

**System Complexity**

- Comprehensive testing coverage
- Gradual feature rollout
- Rollback procedures

## 21. Future Enhancements

### A. Advanced Features

**Predictive Analytics**

- AI-powered usage forecasting
- Optimal credit purchase recommendations
- Seasonal adjustment suggestions

**Dynamic Pricing**

- Volume-based discount tiers
- Peak/off-peak credit pricing
- Market-based rate adjustments

**Enhanced Automation**

- Smart auto-recharge algorithms
- Predictive low-balance alerts
- Automated budget management

### B. Integration Opportunities

**Marketing Tools**

- Credit-based promotional campaigns
- Bonus credit reward programs
- Referral credit incentives

**Partner Ecosystem**

- Shared credit pools for venue groups
- White-label credit systems
- API access for third-party tools

**Mobile Experience**

- Native mobile app for credit management
- Push notifications for balance alerts
- Mobile-optimized purchase flow

### C. Scalability Enhancements

**Multi-Currency Support**

- Regional pricing strategies
- Currency conversion optimization
- Local payment method integration

**Advanced Reporting**

- Custom dashboard creation
- Automated report scheduling
- Advanced data visualization

## 22. Support and Documentation

### A. Venue Training Materials

**Video Tutorial Series**

- "Getting Started with Booking Investment"
- "Understanding Grace Period and Payments"
- "Optimizing Credit Management"
- "Advanced Features and Auto-recharge"

**Interactive Guides**

- Step-by-step portal walkthrough
- Credit purchase simulation
- Troubleshooting common issues

**Best Practices Documentation**

- Optimal credit balance management
- Seasonal planning strategies
- Cost optimization techniques

### B. Technical Documentation

**API Documentation**

- Webhook integration guides
- Third-party integration examples
- Rate limiting and error handling

**Admin Manual**

- Credit management procedures
- Migration process documentation
- Troubleshooting guide

**Developer Resources**

- Database schema documentation
- Code architecture overview
- Deployment procedures

### C. Support Infrastructure

**Help Desk Integration**

- Credit-specific ticket categories
- Escalation procedures
- Knowledge base integration

**Community Resources**

- Venue manager forums
- Best practices sharing
- Feature request voting

## 23. Implementation Timeline

### Phase 1: Ibiza Venues (Days 1-3)

- [ ] Create migrations and models
- [ ] Build basic Stripe integration
- [ ] Create venue migration tool
- [ ] Deploy to Ibiza venues

### Phase 2: Core Features (Days 4-7)  

- [ ] Complete FilamentPHP admin interface
- [ ] Build venue dashboard
- [ ] Implement booking validation
- [ ] Add auto-recharge system

### Phase 3: Testing & Rollout (Days 8-10)

- [ ] Comprehensive testing
- [ ] Monitor Ibiza performance
- [ ] Gradual rollout to other regions
- [ ] Documentation and training

This comprehensive Laravel 11/FilamentPHP 3 implementation provides all the tools needed to build the venue prepaid booking system within your 10-day timeline, combining detailed technical architecture with concrete code implementations following your existing codebase patterns.
