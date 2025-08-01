# PRIMA – Concierge Duplicate Booking Override
## Simple Specification v1.0

## 1. Purpose

Allow specific high-end concierges to bypass duplicate booking restrictions when they book multiple reservations using their own contact information on behalf of discrete clientele.

## 2. Business Context

- **Primary Use Case**: Concierge "Showblank" in Ibiza books for high-end clients who want privacy
- **Current Problem**: She uses her own phone/info, triggering duplicate booking prevention
- **Solution**: Whitelist specific concierges to bypass duplicate checks

## 3. Current Duplicate Checking System

Prima currently prevents duplicate bookings using two checks:

### Daily Check
**File**: `app/Actions/Booking/CheckCustomerHasNonPrimeBooking.php`
- Prevents multiple non-prime bookings on same day
- Uses phone number matching
- Currently disabled via config `CHECK_CUSTOMER_HAS_NON_PRIME_BOOKING=false`

### Time Window Check  
**File**: `app/Actions/Booking/CheckCustomerHasConflictingNonPrimeBooking.php`
- Prevents bookings within 2-hour window
- Cannot be disabled via config
- **This is the main blocker for Showblank**

## 4. Implementation

### 4.1 Database Changes

Add override field to concierges table:

**Migration**: `add_can_override_duplicate_checks_to_concierges_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concierges', function (Blueprint $table) {
            $table->boolean('can_override_duplicate_checks')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('concierges', function (Blueprint $table) {
            $table->dropColumn('can_override_duplicate_checks');
        });
    }
};
```

### 4.2 Model Update

**File**: `app/Models/Concierge.php`
```php
// Add to fillable array
protected $fillable = [
    // ... existing fields
    'can_override_duplicate_checks',
];

// Add to casts
protected function casts(): array
{
    return [
        // ... existing casts
        'can_override_duplicate_checks' => 'boolean',
    ];
}
```

### 4.3 Override Logic

**Update**: `app/Actions/Booking/CheckCustomerHasConflictingNonPrimeBooking.php`

Add concierge override check:
```php
public function handle(array $bookingData): ?Carbon
{
    // NEW: Check if concierge can override duplicate checks
    if (isset($bookingData['concierge_id'])) {
        $concierge = Concierge::find($bookingData['concierge_id']);
        if ($concierge && $concierge->can_override_duplicate_checks) {
            return null; // Skip duplicate check
        }
    }
    
    // ... existing duplicate check logic
}
```

**Update**: `app/Actions/Booking/CheckCustomerHasNonPrimeBooking.php`

Add same override check:
```php
public function handle(array $bookingData): bool
{
    // NEW: Check if concierge can override duplicate checks  
    if (isset($bookingData['concierge_id'])) {
        $concierge = Concierge::find($bookingData['concierge_id']);
        if ($concierge && $concierge->can_override_duplicate_checks) {
            return false; // Skip duplicate check
        }
    }
    
    // ... existing duplicate check logic
}
```

## 5. Admin Interface

**Update**: `app/Filament/Resources/ConciergeResource.php`

Add override toggle to edit form:
```php
Forms\Components\Toggle::make('can_override_duplicate_checks')
    ->label('Can Override Duplicate Bookings')
    ->helperText('Allow this concierge to bypass duplicate booking restrictions')
    ->columnSpanFull()
```

Add to table columns:
```php
Tables\Columns\IconColumn::make('can_override_duplicate_checks')
    ->label('Override')
    ->boolean()
    ->tooltip('Can bypass duplicate checks')
```

## 6. Initial Data

Create Showblank concierge record with override permission:

**Seeder**: `database/seeders/ConciergeOverrideSeeder.php`
```php
<?php

namespace Database\Seeders;

use App\Models\Concierge;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConciergeOverrideSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create Showblank user
        $user = User::firstOrCreate([
            'email' => 'showblank@prima.com'
        ], [
            'name' => 'Showblank',
            'password' => bcrypt('secure-password')
        ]);

        // Create concierge with override permission
        Concierge::updateOrCreate([
            'user_id' => $user->id
        ], [
            'hotel_name' => 'Ibiza Premium Hotels',
            'can_override_duplicate_checks' => true
        ]);
    }
}
```

## 7. Testing

**Test**: `tests/Feature/ConciergeOverrideTest.php`
```php
<?php

namespace Tests\Feature;

use App\Models\Concierge;
use Tests\TestCase;

class ConciergeOverrideTest extends TestCase
{
    public function test_concierge_with_override_bypasses_duplicate_check(): void
    {
        $concierge = Concierge::factory()->create([
            'can_override_duplicate_checks' => true
        ]);

        // Create first booking
        $booking1 = $this->createBooking([
            'concierge_id' => $concierge->id,
            'guest_phone' => '+1234567890'
        ]);

        // Create second booking with same phone (should succeed)
        $booking2 = $this->createBooking([
            'concierge_id' => $concierge->id, 
            'guest_phone' => '+1234567890'
        ]);

        $this->assertDatabaseHas('bookings', ['id' => $booking2->id]);
    }

    public function test_regular_concierge_still_blocked_by_duplicate_check(): void
    {
        $concierge = Concierge::factory()->create([
            'can_override_duplicate_checks' => false
        ]);

        // Create first booking
        $this->createBooking([
            'concierge_id' => $concierge->id,
            'guest_phone' => '+1234567890'
        ]);

        // Second booking should fail
        $this->expectException(\Exception::class);
        $this->createBooking([
            'concierge_id' => $concierge->id,
            'guest_phone' => '+1234567890'
        ]);
    }
}
```

## 8. Deployment Steps

1. **Run Migration**:
   ```bash
   php artisan make:migration add_can_override_duplicate_checks_to_concierges_table
   php artisan migrate
   ```

2. **Update Models and Actions** with override logic

3. **Run Seeder** to create Showblank with override permission:
   ```bash
   php artisan db:seed --class=ConciergeOverrideSeeder
   ```

4. **Test** with Showblank's bookings to ensure override works

## 9. Admin Usage

1. Navigate to Admin → Concierges → Edit Showblank
2. Toggle "Can Override Duplicate Bookings" to ON
3. Save changes
4. Showblank can now make multiple bookings with same phone number

## 10. Security Notes

- Only super admins can toggle override permission
- Override applies to ALL duplicate checks (daily + time window)
- Maintains audit trail - all bookings still logged normally
- No impact on other booking validation rules

---

**Total Implementation**: ~4 files to modify, 1 migration, 1 seeder, minimal code changes