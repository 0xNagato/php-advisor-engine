To implement this feature, you would need to make several changes to your existing models and possibly create new ones. Here's a step-by-step plan:

1. Add a `prime_time` boolean field to the `Schedule` model to distinguish between prime and non-prime schedules.

2. Add a `check_size` field to the `Booking` model to store the check size for non-prime reservations. This field would be nullable and default to the equivalent of $15.

3. Add a `payout` field to the `Booking` model to store the calculated payout to the concierge. This field would be calculated as 5% of the `check_size` for non-prime reservations, and a fixed amount for prime reservations.

4. Add methods to the `Restaurant` model to calculate the total revenue from prime time and the total cost of non-prime time.

5. Create a new `Invoice` model to store invoices for restaurants. This model would have fields for the restaurant ID, the invoice date, the total amount, and a status field to track whether the invoice has been paid.

6. Create a method to generate invoices for restaurants at the end of the month. This method would calculate the balance between the revenue from prime time and the cost of non-prime time, and create a new invoice if there's a balance.

Here's how you could implement these changes in code:

```php
// In the Schedule model
protected $fillable = [
    // ...
    'prime_time',
];

// In the Booking model
protected $fillable = [
    // ...
    'check_size',
    'payout',
];

// In the Restaurant model
public function getPrimeTimeRevenueAttribute(): float
{
    return $this->bookings()
        ->whereHas('schedule', function ($query) {
            $query->where('prime_time', true);
        })
        ->sum('total_fee');
}

public function getNonPrimeTimeCostAttribute(): float
{
    return $this->bookings()
        ->whereHas('schedule', function ($query) {
            $query->where('prime_time', false);
        })
        ->sum('payout');
}

// The new Invoice model
class Invoice extends Model
{
    protected $fillable = [
        'restaurant_id',
        'invoice_date',
        'total_amount',
        'status',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}

// The method to generate invoices
public function generateInvoices(): void
{
    $restaurants = Restaurant::all();

    foreach ($restaurants as $restaurant) {
        $balance = $restaurant->non_prime_time_cost - $restaurant->prime_time_revenue;

        if ($balance > 0) {
            Invoice::create([
                'restaurant_id' => $restaurant->id,
                'invoice_date' => now(),
                'total_amount' => $balance,
                'status' => 'unpaid',
            ]);
        }
    }
}
```

This code assumes that you have a `Booking` model that is related to the `Restaurant` model through a `Schedule` model, and that the total fee for a booking is stored in a `total_fee` field on the `Booking` model. Adjust as necessary to fit your actual data structure.
