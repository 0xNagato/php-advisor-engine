# Partner Revenue Zero Operation

## Overview

The Partner Revenue Zero Operation is a one-time business decision tool that sets all partner revenue percentages to 0% and recalculates all affected bookings to ensure accurate earnings distribution. This operation transfers all partner earnings to platform revenue while maintaining proper audit trails.

## Business Context

Partners in the PRIMA platform earn a percentage of the "remainder" (the amount left after venue and concierge payouts) from both prime and non-prime bookings. This operation was created to support business decisions around partner compensation changes.

### Current Partner Revenue Structure

- **Default Percentage**: 10% (configurable per partner)
- **Maximum Cap**: 20% of remainder (per `BookingPercentages::MAX_PARTNER_EARNINGS_PERCENTAGE`)
- **Earning Types**: `partner_concierge` and `partner_venue` (partners can refer both venues and concierges)
- **Booking Types**: Both prime bookings (customer pays) and non-prime bookings (venue pays) can generate partner earnings

## What This Operation Does

1. **Partner Updates**: Sets the `percentage` field to 0 for all partners in the database
2. **Booking Recalculation**: Finds all confirmed bookings with partner earnings and:
   - Deletes existing earnings records
   - Recalculates all earnings using the zeroed partner percentages
   - Updates platform revenue (which increases as partner earnings decrease)
3. **Audit Trail**: Logs all changes using Laravel's activity log system
4. **Error Handling**: Continues processing even if individual bookings fail, capturing errors for review

## Safety Features

- **Dry-Run Mode**: Preview all changes without making them (`--dry-run` flag)
- **Confirmation Required**: Requires explicit user confirmation in live mode (bypass with `--force`)
- **Transaction Safety**: Each booking recalculation is wrapped in a database transaction
- **Error Isolation**: Failures in individual bookings don't stop the overall process
- **Comprehensive Logging**: All changes are logged for audit purposes

## Usage

### Console Command

```bash
# Preview changes (recommended first step)
php artisan prima:zero-partner-revenue --dry-run

# Show detailed summary of what would be changed
php artisan prima:zero-partner-revenue --summary --dry-run

# Execute the operation with confirmation prompt
php artisan prima:zero-partner-revenue

# Execute without confirmation (for automation)
php artisan prima:zero-partner-revenue --force
```

### Action (Programmatic)

```php
use App\Actions\Partner\SetPartnerRevenueToZeroAndRecalculate;

// Dry run to preview changes
$result = SetPartnerRevenueToZeroAndRecalculate::run(dryRun: true);

// Execute the operation
$result = SetPartnerRevenueToZeroAndRecalculate::run(dryRun: false);

// Get detailed preview
$summary = app(
  SetPartnerRevenueToZeroAndRecalculate::class,
)->getDryRunSummary();
```

## Expected Impact

### Before Operation

- Partners earn percentages from booking remainder
- Platform revenue = Total Fee - Venue - Concierge - Partners - Referrals

### After Operation

- All partner percentages = 0%
- Partner earnings = $0 for all bookings
- Platform revenue = Total Fee - Venue - Concierge - Referrals (higher than before)

### Financial Impact Example

For a $200 prime booking:

- **Before**: Venue $120, Concierge $20, Partners $12, Platform $48
- **After**: Venue $120, Concierge $20, Partners $0, Platform $60

## Technical Details

### Database Changes

#### Partners Table

```sql
UPDATE partners SET percentage = 0 WHERE percentage != 0;
```

#### Bookings Affected

- All bookings with `partner_concierge_id` or `partner_venue_id` not null
- All bookings with existing partner earnings records
- Only confirmed bookings (`status` in: confirmed, venue_confirmed, partially_refunded)

#### Earnings Table

- Deletes all existing earnings for affected bookings
- Creates new earnings with partner amounts = 0
- Preserves all other earning types (venue, concierge, referrals)

### Performance Considerations

- **Chunking**: Processes bookings in chunks of 100 to avoid memory issues
- **Transactions**: Each booking recalculation is in its own transaction for safety
- **Memory Usage**: Uses Laravel collections with chunking for large datasets
- **Execution Time**: Depends on number of bookings (approximately 1-2 seconds per 100 bookings)

### Files Modified

- `app/Actions/Partner/SetPartnerRevenueToZeroAndRecalculate.php`
- `app/Console/Commands/SetPartnerRevenueToZeroCommand.php`
- `tests/Feature/Actions/Partner/SetPartnerRevenueToZeroAndRecalculateTest.php`
- `tests/Feature/Console/SetPartnerRevenueToZeroCommandTest.php`
- `docs/partner_revenue_zero_operation.md`

## Return Data Structure

```php
[
  'partners_found' => 5, // Total partners in system
  'partners_with_non_zero_percentage' => 3, // Partners that needed updating
  'partners_updated' => 3, // Partners actually updated
  'bookings_found' => 150, // Bookings with partner earnings
  'bookings_recalculated' => 148, // Successfully recalculated
  'errors' => [
    // Any errors that occurred
    ['booking_id' => 123, 'error' => 'Error message'],
  ],
  'dry_run' => false, // Whether this was a dry run
];
```

## Error Handling

### Common Errors

- **Booking Calculation Failures**: Individual booking calculations may fail due to data integrity issues
- **Database Constraints**: Foreign key or constraint violations during recalculation
- **Memory Limits**: Large datasets may hit PHP memory limits (mitigated by chunking)

### Error Recovery

- Failed bookings are logged but don't stop the overall process
- Partner percentage updates happen first and are not rolled back on booking failures
- Failed bookings can be manually reviewed and fixed using the error logs

### Monitoring

- All operations are logged to Laravel's standard log files
- Activity log entries are created for audit purposes
- Error details include full stack traces for debugging

## Testing

### Automated Tests

- **Unit Tests**: Test the Action with various scenarios (dry run, live mode, error handling)
- **Console Tests**: Verify command options, output formatting, and user interaction
- **Integration Tests**: End-to-end testing with real booking calculations

### Manual Testing Checklist

1. Run with `--dry-run` to verify impact
2. Check partner percentages before and after
3. Verify booking earnings before and after
4. Confirm platform revenue increases correctly
5. Check activity logs for proper audit trail
6. Test error scenarios with invalid data

## Rollback Procedure

**⚠️ Important**: This operation is not automatically reversible. To rollback:

1. **Partner Percentages**: Must be manually restored from backups or logs
2. **Booking Earnings**: Must be recalculated with restored partner percentages
3. **Activity Logs**: Review activity logs to see original partner percentages

### Recommended Rollback Steps

```bash
# 1. Restore partner percentages from activity logs
# (Manual SQL based on logged 'previous_percentages')

# 2. Recalculate affected bookings
# (Use existing recalculation commands or create custom script)

# 3. Verify earnings are restored correctly
# (Compare totals with pre-operation backups)
```

## Maintenance

### Future Partner Defaults

After running this operation, ensure new partners default to 0%:

- Update the database migration default value
- Update the Partner factory default
- Update any partner creation forms/APIs

### Monitoring After Operation

- Monitor platform revenue metrics for the expected increase
- Verify partner complaint systems work with 0% earnings
- Check that partner dashboards handle 0% gracefully

## Related Documentation

- [Booking Calculations](booking_calculations.md) - Core earnings calculation system
- [Partner System](partner_system.md) - Partner referral and earnings structure
- [Actions Pattern](../CLAUDE.md#action-pattern) - Laravel Actions implementation guide
