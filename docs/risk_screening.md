# Risk Screening Feature Documentation

## Overview

The Risk Screening feature provides an AI-assisted suspicious booking detection system to protect venues from fraudulent or problematic reservations. It automatically scores bookings based on various risk indicators and places suspicious bookings on hold for manual review before notifying venues.

## Plain English Explanation

### What is Risk Screening?

Risk screening is a security feature that automatically checks every booking for signs of fraud or suspicious activity before notifying the venue. Think of it as a bouncer at the door who checks IDs before letting people into the club.

### How It Works for Different Risk Levels

#### ✅ **Normal Bookings (Low Risk - Score 0-29)**
When someone makes a legitimate-looking booking:
1. They pay (if Prime) and get their confirmation email immediately
2. The venue gets notified right away (via their booking system or SMS)
3. Small parties (7 or less) at tech-enabled venues are automatically approved
4. We still log it to Slack (green notification) just so we can see everything

**Example**: John books a table for 4 at 7pm using his real email john@gmail.com
- Risk score: 10 (low)
- Result: Everything proceeds normally, venue sees it immediately

#### ⚠️ **Suspicious Bookings (Medium Risk - Score 30-69)**
When something seems off but not definitely fraudulent:
1. The booking is paused - put "on hold"
2. Customer does NOT get confirmation yet
3. Venue does NOT see the booking yet
4. Yellow alert goes to our ops team in Slack
5. Someone reviews it manually
6. If approved → everything proceeds as normal (just delayed)
7. If rejected → customer gets refunded, venue never knows

**Example**: Jane books using jane@tempmail.com with party of 15
- Risk score: 45 (medium)
- Result: Held for review, no one notified until we check it

#### 🚨 **High Risk Bookings (Score 70+)**
When it's almost certainly fraud or problematic:
1. Hard stop - urgent review required
2. Everything blocked immediately
3. Red alert to ops team
4. Requires immediate attention
5. Usually rejected, but can be approved if legitimate

**Example**: "Test Test" books using fake@mailinator.com from a VPN
- Risk score: 85 (high)
- Result: Blocked completely until manual review

### What Triggers Risk Scores?

The system looks at:
- **Email**: Is it from a disposable email service? Gibberish username? **Profanity (weighted 40-100 points)**?
- **Phone**: All same digits (555-5555)? Invalid format?
- **Name**: "Test Test"? Single letters? **Profanity (weighted by severity and position)**?
- **IP Address**: Coming from a data center? VPN? Different country? **High velocity (20+ bookings = 100 score)**?
- **Behavior**: Multiple bookings in seconds? Copy-pasted notes? **Device abuse tracking**?

### What Happens During Manual Review?

When our team reviews a flagged booking, they can:
1. **Approve it** → The booking continues as if nothing happened (customer gets confirmation, venue gets notified)
2. **Reject it** → Booking cancelled, customer refunded, venue never sees it
3. **Whitelist/Blacklist** → Add trusted partners or block bad actors for future

### Why This Matters

- **Venues don't get spam bookings** - We catch fake reservations before venues waste time on them
- **Real customers aren't affected** - Normal bookings go through instantly
- **Fraudsters are blocked** - Can't test stolen credit cards or harass venues
- **We have visibility** - Every booking logged to Slack for transparency

---

## Key Features

### 1. Multi-Factor Risk Scoring (0-100 scale)

The system analyzes multiple data points to calculate a comprehensive risk score:

#### Email Analysis
- Disposable domain detection (40 points)
- Plus/minus addressing variants (10 points)
- No-reply patterns (25 points)
- Gibberish ratio in username (20 points)
- **Weighted profanity detection (40-100 points)**
  - Extreme profanity: 100 points
  - Context-aware word boundaries to prevent false positives
- MX record validation (30 points if invalid)

#### Phone Analysis
- E.164 format validation
- NANP pattern validation for US/CA numbers
- Repeating digit patterns (e.g., 9898989898)
- Sequential digits detection
- VoIP number flagging
- Test number detection

#### Name Analysis
- Repeated token detection ("Test Test")
- Single letter names
- **Weighted profanity filtering (40-100 points based on severity)**
  - Extreme profanity (fuck, shit, cunt): 100 points
  - High profanity (dick, piss, bastard): 80 points
  - Position-sensitive words (suck, blow): 60 points if at start
  - Context-aware detection to avoid false positives (e.g., "Dick" as first name, "Blow" as surname)
- Emoji/special character detection
- Test name patterns

#### IP Analysis
- Datacenter/VPN range detection (40-60 points)
- Geographic location vs venue region mismatch (30-50 points)
- **Enhanced velocity tracking:**
  - 20+ bookings in 2 hours: 100 points (extreme abuse)
  - 10+ bookings: 80 points (very high velocity)
  - 5+ bookings: 50 points (high velocity)
  - 3+ bookings: 20 points (moderate)
- Private IP detection (10 points)
- Tor exit node detection (60 points)

#### Behavioral Analysis
- Burst submission velocity (3+ in 10 minutes: 10-40 points)
- Identical notes across bookings (25 points)
- **Device velocity tracking:**
  - 20+ bookings from same device: 80 points (extreme)
  - 10+ bookings: 60 points (very high)
  - 5+ bookings: 40 points (high)
  - 3+ bookings: 20 points (moderate)
- Form submission timing (< 5 seconds: 30 points)
- Venue hopping patterns (3+ venues in 30 min: 25 points)

#### Scoring Algorithm

The system uses an intelligent scoring approach:

1. **Multiple Extreme Red Flags**: When 2+ analyzers score ≥80, the system takes the maximum score instead of averaging
2. **Normal Weighted Scoring**: For less extreme cases, uses weighted average:
   - Email: 25% weight
   - Phone: 25% weight
   - Name: 15% weight
   - IP: 20% weight
   - Behavioral: 15% weight
3. **Minimum Score for Extreme Profanity**: Bookings with extreme profanity get a minimum score of 70, regardless of other factors
4. **Risk Metadata Storage**: Complete breakdown of individual scores stored in database for admin review

### 2. Automatic Risk Actions

Based on the calculated risk score:

- **Score < 30 (Low Risk)**: Continue normal booking flow
  - Customer receives confirmation immediately
  - Platform sync attempts (if venue has Restoo/CoverManager)
  - Auto-approval for parties ≤7 with successful platform sync
  - SMS to venue if no platform or sync fails
  - Green Slack notification for visibility

- **30 ≤ Score < 70 (Medium Risk)**: Soft hold for review
  - All customer notifications suppressed
  - All venue notifications suppressed
  - No platform sync attempted
  - Yellow Slack alert with review link
  - Manual approval required in Filament

- **Score ≥ 70 (High Risk)**: Hard hold requiring immediate attention
  - All notifications blocked
  - No platform sync attempted
  - Red Slack alert marked urgent
  - Priority review required in Filament

### 3. Whitelist/Blacklist System

- Domain-level whitelisting for trusted partners (e.g., marriott.com)
- Phone, IP, and name pattern blacklisting
- Automatic override of risk scoring for listed entities
- Management through Filament admin interface

### 4. Review Interface (Filament)

The Risk Review page provides:

- Filterable table with risk scores and reasons
- Quick approve/reject actions
- Bulk approval capabilities
- Whitelist/blacklist management
- Audit trail viewing
- Prior booking history

### 5. Notification System

#### Slack Integration
**ALL bookings** now send Slack notifications to #ops-bookings:
- 🟢 **Green (Low Risk)**: Normal bookings, auto-approved message
- 🟡 **Yellow (Medium Risk)**: Soft hold bookings with review link
- 🔴 **Red (High Risk)**: Hard hold bookings marked urgent

Each notification includes:
- Risk score and level
- Guest details (name, email, phone)
- Venue and booking information
- **Booking Type**: 💎 Prime (Paid) or 🎟️ Non-Prime
- **Total amount** for Prime bookings (properly formatted with currency)
- Appropriate action buttons (View Details or Review Booking)
- Risk indicators when present

#### Customer Notifications
- **Low Risk**: Sent immediately after booking
- **Medium/High Risk**: Suppressed until manual approval

#### Venue Notifications
- **Low Risk**: Follows normal flow (platform sync or SMS)
- **Medium/High Risk**: Never sent until manually approved

#### Rejection Notifications
- Optional SMS to customer for legitimate-seeming rejections
- Refund processed automatically

## Configuration

### Environment Variables

```env
# Core Settings
RISK_SCREENING_ENABLED=true
AI_SCREENING_ENABLED=false

# Thresholds (0-100)
AI_SCREENING_THRESHOLD_SOFT=30
AI_SCREENING_THRESHOLD_HARD=70

# Slack Webhook for Alerts
LOG_SLACK_RISK_WEBHOOK_URL=https://hooks.slack.com/services/...

# OpenAI API Configuration (Optional)
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-5-mini  # Updated to latest model
OPENAI_API_URL=https://api.openai.com/v1/chat/completions
```

**Note:** After adding these environment variables, you must refresh the configuration cache:
```bash
php artisan config:cache
```

### Feature Flags

The feature can be toggled globally via the `risk_screening` feature flag in the application configuration.

## Technical Implementation

### Architecture Overview

```
┌─────────────────┐
│ Booking Created │
└────────┬────────┘
         ↓
┌───────────────────────────────┐
│ Prime Bookings:               │
│ CompleteBooking::handle       │
│                              │
│ Non-Prime Bookings:          │
│ BookingService::processBooking│
└────────┬──────────────────────┘
         ↓
┌──────────────────────────┐
│ ProcessBookingRisk::run  │
│ - Calculate score 0-100  │
│ - Set risk_state         │
│ - Send Slack (ALL)       │
└────────┬─────────────────┘
         ↓
    ┌────┴────┐
    │ if (!   │
    │ isOnRisk│
    │ Hold()) │
    └────┬────┘
         ↓
    ┌────┴────┐
    │         │
    ▼         ▼
┌───────┐ ┌────────┐
│ TRUE  │ │ FALSE  │
│Low    │ │Med/High│
│Risk   │ │Risk    │
└───┬───┘ └────────┘
    ↓          │
    ↓          ▼
    ↓     ┌─────────┐
    ↓     │ BLOCKED │
    ↓     │ Wait for│
    ↓     │ Review  │
    ↓     └────┬────┘
    ▼              ▼
┌──────────────────────────┐
│ BookingConfirmed::dispatch│
│ → Platform Sync           │
│ → Auto-approval           │
└───────────────────────────┘
```

### Code Hook Points

#### 1. Main Entry Points

**Prime Bookings** - `app/Actions/Booking/CompleteBooking.php`:
```php
// Line 64: Risk screening injection point
ProcessBookingRisk::run($booking);

// Line 67: Risk hold check gates everything
if (!$booking->isOnRiskHold()) {
    // Send notifications
    // Fire events
    BookingConfirmed::dispatch($booking->load('schedule', 'venue'));
}
```

**Non-Prime Bookings** - `app/Services/BookingService.php`:
```php
// Line 37: Risk screening for non-prime
ProcessBookingRisk::run($booking);

// Line 40: Same risk hold check
if (!$booking->isOnRiskHold()) {
    // Send notifications...
    BookingConfirmed::dispatch($booking->load('schedule', 'venue'));
}
```

#### 2. Risk State Determination
**File**: `app/Actions/Risk/ProcessBookingRisk.php`
```php
// Threshold application
$softThreshold = config('app.ai_screening_threshold_soft', 30);
$hardThreshold = config('app.ai_screening_threshold_hard', 70);

if ($result['score'] >= $hardThreshold) {
    $riskState = 'hard';
} elseif ($result['score'] >= $softThreshold) {
    $riskState = 'soft';
}

// Persist to database
$booking->update([
    'risk_score' => $result['score'],
    'risk_state' => $riskState,
    'risk_reasons' => $result['reasons'],
]);

// Always send Slack (new behavior)
SendRiskAlertToSlack::run($booking, $result);
```

#### 3. Risk Hold Check
**File**: `app/Models/Booking.php`
```php
public function isOnRiskHold(): bool
{
    return in_array($this->risk_state, ['soft', 'hard'])
           && !$this->reviewed_at;
}
```

#### 4. Manual Approval Flow
**File**: `app/Actions/Risk/ApproveRiskReview.php`
```php
public function handle(Booking $booking, ?int $userId = null): bool
{
    // Clear the hold
    $booking->update([
        'risk_state' => null,
        'reviewed_at' => now(),
        'reviewed_by' => $userId ?? auth()->id(),
        'status' => BookingStatus::CONFIRMED,
        'confirmed_at' => now(),
    ]);

    // Send customer confirmation (was suppressed)
    $booking->notify(new CustomerBookingConfirmed);

    // Send venue SMS
    SendConfirmationToVenueContacts::run($booking);

    // NOW fire the event that was blocked
    BookingConfirmed::dispatch($booking->load('schedule', 'venue'));
}
```

### Database Schema

#### Bookings Table Additions

- `risk_score` (smallint): Risk score 0-100
- `risk_state` (varchar): null, 'soft', or 'hard'
- `risk_reasons` (jsonb): Array of risk indicators
- `risk_metadata` (jsonb): **Complete breakdown of risk scoring**
  - `totalScore`: Final calculated score
  - `breakdown`: Individual analyzer scores (email, phone, name, IP, behavioral)
  - `reasons`: All risk reasons from analyzers
  - `features`: Detailed features detected
  - `analyzedAt`: Timestamp of analysis
  - `llmUsed`: Whether AI was used
  - `llmResponse`: AI response if applicable
- `reviewed_at` (timestamp): When manually reviewed
- `reviewed_by` (bigint): User ID who reviewed
- `ip_address` (varchar): Customer IP
- `user_agent` (varchar): Browser user agent

#### Supporting Tables

- `risk_whitelists`: Trusted entities
- `risk_blacklists`: Blocked entities
- `risk_audit_logs`: Complete audit trail with PII masking

### Action Classes

- `ProcessBookingRisk`: Main orchestrator
- `ScoreBookingSuspicion`: Core scoring logic with LLM integration
- `ApproveRiskReview`: Approval workflow
- `RejectRiskReview`: Rejection workflow
- `SendRiskAlertToSlack`: Slack notifications (with Prime/Non-Prime distinction)
- `EvaluateWithLLM`: OpenAI integration for enhanced risk detection

### Analyzer Classes

- `AnalyzeEmailRisk`: Email-specific checks
- `AnalyzePhoneRisk`: Phone validation
- `AnalyzeNameRisk`: Name pattern detection
- `AnalyzeIPRisk`: IP reputation checking
- `AnalyzeBehavioralSignals`: Velocity and pattern analysis

### LLM Integration (Optional)

When `AI_SCREENING_ENABLED=true`:
- Sends complete booking details to OpenAI API for enhanced analysis
- Includes all customer information (email, phone, name, IP, notes) for better context
- Weighted combination: 70% rules, 30% LLM for scores between 20-80
- Fails gracefully to comprehensive rule-based scoring if API unavailable
- Configurable model selection (defaults to GPT-5-mini for cost efficiency)

## Workflow Integration

### Complete Booking Flow with Risk Screening

#### Step 1: Initial Processing

**For Prime Bookings (with payment):**
1. Customer submits booking form with payment
2. `CompleteBooking` action processes Stripe payment
3. `ProcessBookingRisk` immediately calculates risk score
4. Slack notification sent with 💎 Prime indicator and total amount

**For Non-Prime Bookings (no payment):**
1. Customer submits booking form without payment
2. `BookingService::processBooking` handles the submission
3. `ProcessBookingRisk` immediately calculates risk score
4. Slack notification sent with 🎟️ Non-Prime indicator

#### Step 2A: Low Risk Path (Score < 30)
1. No risk hold applied
2. Customer gets confirmation email/SMS immediately
3. `BookingConfirmed` event fires
4. Platform sync attempts (if venue has Restoo/CoverManager):
   - Success + party ≤7 → Auto-approved via `AutoApproveSmallPartyBooking`
   - Success + party >7 → SMS to venue for manual approval
   - Failed sync → SMS to venue for manual approval
5. If no platform → SMS to venue immediately

#### Step 2B: Medium Risk Path (Score 30-69)
1. Soft hold applied (`risk_state = 'soft'`)
2. Booking status → `review_pending`
3. Customer notifications suppressed
4. Venue notifications suppressed
5. `BookingConfirmed` event NOT fired (no platform sync)
6. Yellow Slack alert with review link
7. Waits for manual review

#### Step 2C: High Risk Path (Score 70+)
1. Hard hold applied (`risk_state = 'hard'`)
2. Booking status → `review_pending`
3. All notifications suppressed
4. `BookingConfirmed` event NOT fired
5. Red Slack alert marked urgent
6. Requires immediate manual review

### Manual Review Process

1. Admin accesses Risk Review in Filament (via Slack link or navigation)
2. Reviews complete booking details and risk indicators
3. Takes action:
   - **Approve**:
     - Clears risk hold
     - Fires `BookingConfirmed` event
     - Triggers platform sync if applicable
     - Sends all customer/venue notifications
     - Applies auto-approval if eligible
   - **Reject**:
     - Cancels booking
     - Processes refund
     - Optional rejection SMS to customer
     - Logs rejection reason
   - **Whitelist/Blacklist**:
     - Add email domain, phone, or IP to lists
     - Affects future bookings immediately

## Operational Considerations

### Data Handling

#### Audit Logs
- Automatically mask sensitive data for privacy
- Email: `ki***@y****.com`
- Phone: `+1******9898`
- Name: `J*** S*****`

#### LLM Processing
- Complete booking details sent to OpenAI for better context
- Includes email, phone, name, IP address, and notes
- Enables detection of sophisticated patterns and correlations
- Admin reviewers see the same complete information

### Metrics & Monitoring

Track these key metrics:
- `risk.scores.hist`: Distribution of risk scores
- `risk.flag.rate`: Percentage flagged for review
- `risk.fp.rate`: False positive rate (manual approvals of holds)
- `time-to-decision`: Review response time

### Key Decision Points

1. **Should notifications be sent?**
   - Check: `!$booking->isOnRiskHold()`
   - If TRUE → Send all notifications
   - If FALSE → Block everything

2. **Should platform sync happen?**
   - Triggered by: `BookingConfirmed` event
   - Event only fires if not on risk hold OR after manual approval

3. **Should auto-approval run?**
   - Requires: Party ≤7 + venue has platform + sync succeeds
   - Only applies to bookings not on hold

4. **What risk level?**
   - Score < 30 → Low risk (proceed normally)
   - Score 30-69 → Medium risk (soft hold)
   - Score 70+ → High risk (hard hold)

### Performance Considerations

1. **Risk scoring is synchronous** - Happens during booking creation
   - Average time: ~200ms
   - Includes optional OpenAI call (500ms timeout)

2. **Slack notifications are queued** - Don't block booking
   - Processed by Laravel queue worker
   - Retry on failure

3. **Platform sync is queued** - Via BookingConfirmed event
   - Only for non-held bookings
   - Retries 3 times with exponential backoff

### Edge Cases Handled

1. **Risk scoring fails** → Fails open (allows booking)
2. **OpenAI timeout** → Falls back to rule-based scoring
3. **Slack webhook fails** → Logged but doesn't block booking
4. **Platform sync fails** → Falls back to SMS notification
5. **Manual approval after venue already confirmed** → Skips re-notification

### Security Considerations

1. **PII in audit logs** → Automatically masked
2. **Admin access** → Only authenticated users can review
3. **Webhook URL** → Stored in environment variable
4. **OpenAI data** → Complete booking details sent for context
5. **Refunds** → Automatically processed on rejection

## Testing

### Unit Tests
Run risk-specific tests:
```bash
./vendor/bin/pest tests/Feature/Risk --parallel
./vendor/bin/pest tests/Unit/Actions/Risk --parallel
```

### Test Coverage
- Email/Phone/Name/IP analyzers
- Whitelist/blacklist precedence
- Notification suppression
- Approval/rejection workflows
- LLM evaluation with fallback scoring
- OpenAI API integration
- Configuration respect
- Prime vs Non-Prime booking flows

### Manual Testing
```bash
# Check risk score for a booking
php artisan tinker
>>> $booking = Booking::find(123);
>>> dump($booking->only(['risk_score', 'risk_state', 'risk_reasons']));

# Manually trigger scoring
>>> App\Actions\Risk\ProcessBookingRisk::run($booking);

# View audit trail
>>> $booking->riskAuditLogs->pluck('event', 'created_at');
```

### Seed Data
Apply test blacklist/whitelist entries:
```bash
php artisan db:seed --class=RiskScreeningSeeder
```

## Monitoring & Debugging

### SQL Queries
```sql
-- Check risk distribution
SELECT risk_state, COUNT(*)
FROM bookings
WHERE created_at > NOW() - INTERVAL '7 days'
GROUP BY risk_state;

-- Find pending reviews
SELECT id, guest_name, risk_score, created_at
FROM bookings
WHERE risk_state IN ('soft', 'hard')
AND reviewed_at IS NULL
ORDER BY risk_score DESC;

-- Audit trail for a booking
SELECT * FROM risk_audit_logs
WHERE booking_id = 123
ORDER BY created_at;
```

### Debug Commands
```bash
# Toggle risk screening
RISK_SCREENING_ENABLED=false  # Disable completely

# Adjust thresholds
AI_SCREENING_THRESHOLD_SOFT=40  # More strict
AI_SCREENING_THRESHOLD_HARD=60  # More strict

# Manual review
php artisan tinker
>>> App\Actions\Risk\ApproveRiskReview::run(Booking::find(123));
>>> App\Actions\Risk\RejectRiskReview::run(Booking::find(456), 'Fraud');
```

## Support & Troubleshooting

### Common Issues

**High False Positive Rate**
- Increase `AI_SCREENING_THRESHOLD_SOFT` value
- Review and whitelist legitimate domains
- Fine-tune analyzer weights

**Notifications Still Sending**
- Verify `RISK_SCREENING_ENABLED=true`
- Check booking status is `review_pending`
- Confirm risk_state is not null

**Slack Alerts Not Working**
- Verify webhook URL is correct
- Check Laravel queue is running
- Review logs for HTTP errors
- Run `php artisan config:cache` after adding webhook

**LLM Not Being Used**
- Verify `AI_SCREENING_ENABLED=true`
- Check `OPENAI_API_KEY` is set
- Review logs for API errors
- Confirm score is in 20-80 range (LLM only applied in uncertain cases)

## Quick Reference

### URLs
- Risk Review Queue: `/platform/risk-reviews`
- Whitelist Management: `/platform/risk-whitelists`
- Blacklist Management: `/platform/risk-blacklists`
- Individual Booking: `/platform/bookings/{id}`

### Slack Commands
- View all bookings: Monitor `#ops-bookings` channel
- Review urgent: Click red notification links
- Review medium: Click yellow notification links
- View normal: Green notifications for transparency

## Deployment Checklist

1. Run migrations to add risk fields
2. Set environment variables
3. Configure Slack webhook URL
4. Run `php artisan config:cache`
5. (Optional) Add OpenAI API key for enhanced detection
6. Run seeder for initial blacklist data
7. Grant admin users access to Risk Review
8. Test with known suspicious patterns
9. Monitor initial false positive rate
10. Adjust thresholds based on data

## Changelog

### 2025-09-19: Enhanced Profanity and Velocity Scoring
- **Weighted profanity detection**: Extreme words (fuck, shit) score 100 points, mild words score 40-60
- **Position-sensitive detection**: "suck" at start of name is offensive, but "Suckerman" as surname is acceptable
- **Word boundary detection**: Prevents false positives for "Michelle", "Dick Johnson", "Ashley Cockburn"
- **Aggressive velocity scoring**: 20+ bookings from same IP/device scores 100 points
- **Smart scoring algorithm**: Multiple extreme red flags take maximum score instead of averaging
- **Minimum score enforcement**: Extreme profanity always scores at least 70 points
- **Risk metadata storage**: Complete breakdown stored in database for admin review
- **Comprehensive test coverage**: All changes covered by automated tests

## Future Enhancements

- Fine-tune custom LLM model on historical booking data
- Real-time carrier lookup for phone validation
- GeoIP database integration
- Browser fingerprinting
- Email reputation API integration
- Customizable risk rules per venue
- Automated threshold adjustment based on false positive rates
- Integration with additional LLM providers (Anthropic, Google)
- Real-time learning from admin approval/rejection decisions