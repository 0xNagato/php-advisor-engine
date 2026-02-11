# PRIMA Glossary (Expanded)

## Core Entities

 - [Booking](booking_creation_guide.md#L3): A reservation tying a guest, venue schedule, time, and fees.
 - [Concierge](booking_creation_guide.md#L83): User who creates/owns bookings and manages guest relationships.
 - [Venue](booking_platforms.md#L1): Establishment receiving the reservation; may integrate with platforms.
 - [Partner](booking_calculations.md#L10): Entity that referred a venue and/or concierge; can earn a share.
 - [VIP Code](booking_creation_guide.md#L153): Optional code that attributes bookings to a VIP program and concierge.

## Availability & Scheduling

 - [`ScheduleTemplate`](booking_creation_guide.md#L11): Defines a venue’s operating slot (day_of_week, start/end, `prime_time`, `prime_time_fee`).
 - [Timeslot](booking_creation_guide.md#L37): A bookable window derived from a `ScheduleTemplate` (e.g., 18:00–22:00).
 - [`ScheduleWithBookingMV`](booking_creation_guide.md#L16): Materialized view that surfaces real-time availability and booking counts.
 - [`ReservationService`](booking_creation_guide.md#L25): Fetches available venues and timeslot headers for a given date/time/guest count.

## Booking Lifecycle & API

 - [`CreateBooking`](booking_creation_guide.md#L43): Action that creates a booking in `pending` state after validations.
 - [`CompleteBooking`](booking_creation_guide.md#L62): Action that finalizes a booking, confirming it and recording payment/guest data.
 - [`booking_at`](booking_creation_guide.md#L86): Datetime when the reservation occurs (not when it was created).
 - [Source/Device](booking_creation_guide.md#L113): Tracking fields like `source` (web, api, mobile_app) and `device` (web, mobile_app).
 - [Calendar API](booking_creation_guide.md#L172): GET `/api/calendar` to fetch venues and available timeslots (VIP session token required).
 - [Booking API](booking_creation_guide.md#L176): POST `/api/bookings` (create), POST `/api/bookings/{id}/complete` (complete), GET `/api/bookings/{id}` (detail).

## Booking Status & Risk

 - [`BookingStatus`](booking_creation_guide.md#L114): Enum of lifecycle states:
  - `pending`, `guest_on_page`, `review_pending` (risk hold), `confirmed`, `venue_confirmed`, `completed`, `cancelled`, `abandoned`, `refunded`, `no_show`, `partially_refunded`.
 - [`REPORTING_STATUSES`](booking_creation_guide.md#L129): Statuses counted for reporting (e.g., VIP code earnings): `confirmed`, `venue_confirmed`, `completed`, `refunded`, `partially_refunded`.
 - [Risk scoring](booking_creation_guide.md#L148): All bookings are scored; high-risk bookings move to `review_pending` until cleared. See also: [Risk Screening](risk_screening.md#L1).
 - [`venue_confirmed_at`](auto_approval_small_parties.md#L51): Timestamp indicating venue confirmation (manual or auto-approved). See also: [Auto-Approval](auto_approval_small_parties.md#L33).

## Payments & Fees

 - [Prime booking / `is_prime`](booking_creation_guide.md#L95): Occurs in prime time; usually requires Stripe payment intent and has distinct fee splits.
 - Payment intent: [`stripe_payment_intent_id`](booking_creation_guide.md#L105) used to confirm prime bookings; `stripe_charge_id` may be stored.
 - Fee fields: [`total_fee`, `venue_fee`, `concierge_fee`, `platform_fee`](booking_creation_guide.md#L96) (monetary amounts in minor units).
 - [Currency](revenue_reporting_summary.md#L65): ISO currency code for booking fees and earnings (e.g., USD, EUR).

## Earnings & Calculations

 - [Remainder](booking_calculations.md#L60): Amount left after paying venue and concierge; base for partner/platform splits.
 - [`EarningType`](booking_calculations.md#L19): Enum categorizing earnings (e.g., `venue`, `partner_venue`, `concierge`, `partner_concierge`, `concierge_referral_1`, `concierge_referral_2`, `venue_paid`, `concierge_bounty`, `refund`).
 - [`BookingPercentages`](booking_calculations.md#L40): Constants controlling distribution (e.g., platform percentages, non-prime splits, referral rates, `MAX_PARTNER_EARNINGS_PERCENTAGE`).
 - [Partner earnings cap](booking_calculations.md#L187): Partner earnings are capped at 20% of the remainder across venue+concierge referrals.
 - [Concierge referrals](booking_calculations.md#L25): Level 1 and Level 2 referring concierges can earn from both prime and non-prime bookings.
 - [Non-prime structure](booking_calculations.md#L108): Customer pays $0; venue pays per-head fee, concierge earns bounty, platform takes processing and platform fees; partners/referrals earn from platform share.
 - Venue payment ([`venue_paid`](booking_calculations.md#L27)): Represents outgoing payment by venue in non-prime bookings (may be negative entry).

## Reporting & Metrics

 - [Gross Revenue](booking_revenue_calculations_update.md#L37): Total money received by PRIMA.
  - Prime: `total_fee - total_refunded` (customer payments net of refunds).
  - Non-prime: `ABS(venue_earnings)` (venue payments; absolute value of negative amounts).
 - [PRIMA Share](booking_revenue_calculations_update.md#L54): Revenue after venue costs, before concierge costs.
  - Prime: `(total_fee - total_refunded) - venue_earnings`.
  - Non-prime: `ABS(venue_earnings)`.
 - [Platform Revenue](booking_revenue_calculations_update.md#L48) (net platform): Historically shown as `platform_earnings - platform_earnings_refunded`; net after all distributions (venue, concierge, partners, referrals).
 - [`venue_earnings` sign](booking_revenue_calculations_update.md#L106): Positive for prime (payout to venue); negative for non-prime (payment from venue to PRIMA).
 - [Refund fields](booking_revenue_calculations_update.md#L30): `total_refunded`, `platform_earnings_refunded` used to net out refunds in reporting.
 - [Dashboard components](revenue_reporting_summary.md#L25): `BookingsOverview` (stats + chart) and `EarningsBreakdown` (per-booking view) use consistent Gross/PRIMA Share logic.

## Platform Integrations

 - [`BookingPlatformInterface`](booking_platforms.md#L25): Contract for platform services (`checkAuth`, `checkAvailability`, `createReservation`, `cancelReservation`, `getPlatformName`).
 - [`VenuePlatform`](booking_platforms.md#L40): Model representing a venue’s platform connection with fields `platform_type`, `is_enabled`, `configuration` (JSON), `last_synced_at`.
 - [`BookingPlatformFactory`](booking_platforms.md#L78): Resolves the correct platform service for a venue based on enabled `VenuePlatform`.
 - [Platform types](booking_platforms.md#L91): `covermanager`, `restoo` (extensible via factory and admin forms).
 - [Venue helpers](booking_platforms.md#L105): `platforms()`, `getPlatform()`, `hasPlatform()`, `getBookingPlatform()` to manage platform links.
 - [Admin (Filament)](booking_platforms.md#L130): `BookingPlatformsResource` with `ListPlatforms`, `CreatePlatform`, `EditPlatform` to manage connections and test credentials.
 - [Future enhancements](booking_platforms.md#L188): Webhooks for real-time updates; unified dashboards; failover between platforms.
 
## Sync Flags

- [`--skip-venue-platforms`](sync-db-production-config-flag.md#L5): Flag to skip updating all venue platform configs during DB syncs.
- [`sync-db.sh`](sync-db-production-config-flag.md#L12): Shell script supporting the skip flag for safe test syncs.
- [`venue-platforms:update-config`](sync-db-production-config-flag.md#L27): Artisan command with `--skip-venue-platforms` option.
- Skip output banner: Clear warnings shown when skip is active.

## Risk Screening

- [Risk Screening](risk_screening.md#L1): AI-assisted system to score bookings and hold/notify accordingly.
- [Low Risk (0–29)](risk_screening.md#L15): Immediate proceed; venue notified; logged to Slack (green).
- [Medium Risk (30–69)](risk_screening.md#L26): Soft hold; manual review; Slack yellow alert; no venue/customer notify.
- [High Risk (70+)](risk_screening.md#L40): Hard hold; block notifications; Slack red alert; urgent review.
- [Extreme red flags rule](risk_screening.md#L148): If ≥2 analyzers ≥80, take max score (not average).
- [Profanity thresholds](risk_screening.md#L158): Min 85 for extreme; up to 90+.
- [Test name detection](risk_screening.md#L160): Obvious fake names score 80+.
- [Review actions](risk_screening.md#L63): Approve, reject/refund, whitelist or blacklist.
- [Risk metadata storage](risk_screening.md#L161): Per-analyzer breakdown saved for admin review.
- Related status: `REVIEW_PENDING` in [`BookingStatus`](booking_creation_guide.md#L114).

## Unified Platform Reservations

- [Unified architecture](unified_platform_reservations.md#L1): Single `PlatformReservation` model replacing per-platform tables.
- [Model location](unified_platform_reservations.md#L18): `app/Models/PlatformReservation.php`.
- [Schema fields](unified_platform_reservations.md#L27): `platform_type`, `platform_reservation_id`, `platform_status`, `platform_data`.
- [Key fields table](unified_platform_reservations.md#L42): Meanings of type/id/status/data.
- [`createFromBooking`](unified_platform_reservations.md#L85): Factory for CoverManager/Restoo reservations.
- [`syncToPlatform`](unified_platform_reservations.md#L95): Retry syncing to external platform.
- [`cancelInPlatform`](unified_platform_reservations.md#L104): Cancel external reservation and sync status.
- [Duplicate detection](unified_platform_reservations.md#L111): Track duplicates via `_dup_` suffix and metadata.
- [Smart cancellation](unified_platform_reservations.md#L141): Local-only cancel for duplicates; cascade for originals.

## Booking Investment System

- [System overview](venue_booking_investment_system_plan.md#L5): Replace post-billing with covers tracking + deposits. [Ref: docs/venue_booking_investment_system_plan.md:5]
- [Covers tracking](venue_booking_investment_system_plan.md#L28): Pay-as-you-go for covers used. [Ref: docs/venue_booking_investment_system_plan.md:28]
- [Velocity-based deposits](venue_booking_investment_system_plan.md#L49): Recommended deposit sizes by booking velocity. [Ref: docs/venue_booking_investment_system_plan.md:49]
- [Outstanding balance](venue_booking_investment_system_plan.md#L121): Balance in cents owed by venue. [Ref: docs/venue_booking_investment_system_plan.md:121]
- [Portal UI](venue_booking_investment_system_plan.md#L549): One-page portal for Outstanding/Deposit/Refund actions. [Ref: docs/venue_booking_investment_system_plan.md:567]
- [`CreateBookingInvestmentPayment`](venue_booking_investment_system_plan.md#L602): Action to build payments for outstanding and deposits. [Ref: docs/venue_booking_investment_system_plan.md:602]

## Venue Dashboards

 - [Venue dashboards](venue_management_dashboard_spec.md#L15): VenueDashboard and VenueManagerDashboard pages.
 - [Top KPI Cards](venue_management_dashboard_spec.md#L29): Core metrics at top of dashboard.
 - [Hotel Partners Table](venue_management_dashboard_spec.md#L40): QR concierge table with bookings and revenue.
 - [Concierge Leaderboard](venue_management_dashboard_spec.md#L165): Leaderboard for regular concierges.
 - [Business Intelligence](venue_management_dashboard_spec.md#L192): No-shows, cancellations, revenue, `trouble_pct`.
 - [`trouble_pct` formula](venue_management_dashboard_spec.md#L215): Percent of no_show + cancelled out of total bookings.

## Refunds, Currencies & Validation

 - [Currency handling](revenue_reporting_summary.md#L65): Amounts stored in minor units; multi-currency conversion maintained consistently.
 - [Consistency](revenue_reporting_summary.md#L69): Dashboard and detail views share the same calculation methods to align metrics.
 - [Validation tips](booking_revenue_calculations_update.md#L113): Ensure Gross Revenue includes non-prime venue payments; PRIMA Share > old net platform revenue when appropriate.

## Caching & Ops Notes

 - [Widget caching](calculation_impact_analysis.md#L98): Filament may cache stats; refresh when validating changes.
 - [Application cache](calculation_impact_analysis.md#L100): Clear with `php artisan cache:clear` if metrics seem stale.
 - [Date ranges](calculation_impact_analysis.md#L102): Dashboard metrics depend on the active date filter (e.g., last 30 days).

## Short Examples

- Prime booking (metrics): Customer pays $100; venue earns $60; concierge earns $10. [Ref: docs/booking_revenue_calculations_update.md:129]
  - Gross Revenue: $100
  - PRIMA Share: $40 (`$100 - $60` venue payout)
  - Net platform (context): $30 after concierge/partners/referrals

- Non-prime booking (2 guests, $10/head): Venue pays $20 to PRIMA; concierge bounty is 80% ($16); platform keeps 30% ($6). [Ref: docs/booking_calculations.md:118]
  - Gross Revenue: $20 (`ABS(venue_earnings)`)
  - PRIMA Share: $20 (same as Gross for non-prime)
  - Net platform (context): $6 after bounty/referrals

- Partner cap (prime): On a $200 booking with venue $120 and concierge $20, remainder is $60. Max total partner earnings = 20% of $60 = $12 across partner_venue + partner_concierge. [Ref: docs/booking_calculations.md:187]

- Concierge referrals (prime): $200 booking remainder $60 → level 1 earns 10% ($6), level 2 earns 5% ($3). [Ref: docs/booking_calculations.md:98]

- Auto-approval eligibility: Party size = 6, venue has Restoo enabled, sync succeeds (returns `uuid`), and no `venue_confirmed_at` → booking is auto-approved and venue notified. [Ref: docs/auto_approval_small_parties.md:18]

- Platform sync signals: Restoo `createReservation` returns `uuid`; CoverManager returns `id_reserv`. Presence indicates successful sync. [Ref: docs/auto_approval_small_parties.md:63]

- Refunds (prime): Customer paid $100, $20 refunded → Gross Revenue = $80; PRIMA Share = `$80 - $60` venue payout (if venue gets $60) = $20. [Ref: docs/booking_revenue_calculations_update.md:38]

- Skip flag during sync: `./sync-db.sh --import-only --skip-venue-platforms` preserves all platform configs while importing data. [Ref: docs/sync-db-production-config-flag.md:18]

- Risk screening thresholds:
  - Low: Score 10 with normal details → proceed and notify (green). [Ref: docs/risk_screening.md:23]
  - Medium: Score 45 with temp email/large party → soft hold, review (yellow). [Ref: docs/risk_screening.md:37]
  - High: Score 85 with multiple red flags → hard hold, block (red). [Ref: docs/risk_screening.md:49]

- Unified reservations duplicate cancel: Duplicate `PlatformReservation` cancels locally; original remains active. [Ref: docs/unified_platform_reservations.md:542]

- Deposit recommendation: Medium-velocity venue → 30 covers deposit; deposit amount = covers × cost per cover. [Ref: docs/venue_booking_investment_system_plan.md:52]

- BI trouble_pct: If 3 of 12 bookings are no_show/cancelled → `trouble_pct = 25.0%` using the dashboard formula. [Ref: docs/venue_management_dashboard_spec.md:215]

 - Prepaid credits: Venue at 0 credits tries to confirm → bookings suspended until deposit; after buying 20 credits, confirmations resume immediately. [Ref: docs/venue_prepaid_booking_system_plan.md:59]

 - VIP query tracking: Enhanced link `…/v/MIAMI2024?utm_source=facebook&cuisine[]=italian&guest_count=4` is captured at link hit, persisted to session, and available when creating bookings with `session_token`. [Ref: docs/vip_query_parameter_tracking.md:18]

 - VIP session API flow: POST `/api/vip/sessions` returns `session_token` and `expires_at`; use token as `Authorization: Bearer …` in subsequent booking API calls. [Ref: docs/vip_query_parameter_tracking.md:206]

## Auto-Approval & Operation References

 - [`AutoApproveSmallPartyBooking`](auto_approval_small_parties.md#L33) action: Main auto-approval logic. [Ref: docs/auto_approval_small_parties.md:33]
 - [`BookingPlatformSyncListener`](auto_approval_small_parties.md#L36): Triggers auto-approval post-sync. [Ref: docs/auto_approval_small_parties.md:36]
 - [`MAX_AUTO_APPROVAL_PARTY_SIZE`](auto_approval_small_parties.md#L101): Current limit for auto-approval (7). [Ref: docs/auto_approval_small_parties.md:101]
 - Partner revenue operation action: [`SetPartnerRevenueToZeroAndRecalculate`](partner_revenue_zero_operation.md#L57). [Ref: docs/partner_revenue_zero_operation.md:57]
 - Console command: [`prima:zero-partner-revenue`](partner_revenue_zero_operation.md#L48). [Ref: docs/partner_revenue_zero_operation.md:48]
 - Return structure key: [`dry_run`](partner_revenue_zero_operation.md#L141). [Ref: docs/partner_revenue_zero_operation.md:141]

## Auto-Approval & Platform Sync

- Auto-approval: Automatic venue confirmation for small parties after successful platform sync.
- Party size limit: Max guests for auto-approval (currently 7 via `MAX_AUTO_APPROVAL_PARTY_SIZE`).
- Platform integration: Venue must have an enabled platform (Restoo or CoverManager) configuration.
- Successful sync signals: Restoo returns `uuid`; CoverManager returns `id_reserv`.
- `BookingPlatformSyncListener`: Triggers auto-approval after a successful platform sync.
- Notifications: `SendAutoApprovalNotificationToVenueContacts`, `VenueContactBookingAutoApproved` with SMS templates `venue_contact_booking_auto_approved` and `venue_contact_booking_auto_approved_notes`.
- Fallback: If eligibility fails or sync fails, proceed with the standard manual confirmation flow.

## Partner Revenue Zero Operation

- Operation goal: Set all partner percentages to 0% and recalculate earnings so platform revenue increases accordingly.
- Partner percentage: `partners.percentage` share of the remainder (set to 0% by the operation).
- Affected bookings: Confirmed variants (e.g., `confirmed`, `venue_confirmed`, `partially_refunded`) that had partner earnings or partner links.
- Action/command: `SetPartnerRevenueToZeroAndRecalculate` and console `prima:zero-partner-revenue`.
- Safety flags: `--dry-run` (preview), `--summary` (details), `--force` (skip confirmation).
- Processing: Chunking (e.g., 100/book) and per-booking DB transactions.
- Return structure (summary): Counts of partners/bookings updated and errors encountered, plus `dry_run` indicator.

## Logging, Safety & Ops

- Activity logs: Audit trail of changes (e.g., partner percentage updates, auto-approvals).
- Application logs: Detailed operational logs for sync attempts, auto-approval checks, and errors.
- Monitoring: Track platform revenue, booking flows, and complaint systems after operational changes.
- Rollback guidance: Restore partner percentages from logs/backups and recalculate bookings if needed (no automatic rollback).

## Prepaid Booking System

 - [Prepaid credit system](venue_prepaid_booking_system_plan.md#L5): Venues prepay for booking credits managed via a one-page portal.
 - [Booking credits](venue_prepaid_booking_system_plan.md#L45): Credits are consumed on each confirmed booking; [Credit value = 1 booking](venue_prepaid_booking_system_plan.md#L49).
 - [Grace period (first 20 covers)](venue_prepaid_booking_system_plan.md#L56): Initial 20 bookings accrue as owed; must be paid to continue.
 - [Deposit requirement](venue_prepaid_booking_system_plan.md#L58): Deposit required for future bookings post‑grace; [service suspension](venue_prepaid_booking_system_plan.md#L59) on non‑payment disables bookings.
 - [Migrations](venue_prepaid_booking_system_plan.md#L66): `venue_credit_accounts`, `venue_credit_transactions`, `venue_credit_deposit_links`, and venue prepaid fields.
 - [`venue_credit_accounts` schema](venue_prepaid_booking_system_plan.md#L87): Tracks `current_balance`, `grace_credits_used/owed`, `last_deposit_at`.
 - [`venue_credit_transactions` schema](venue_prepaid_booking_system_plan.md#L128): Records credit `amount`, `cost_per_credit`, and `type`.
 - [Venue prepaid fields](venue_prepaid_booking_system_plan.md#L170): `uses_prepaid_system`, `cost_per_credit`, `minimum_credit_balance`, `auto_recharge_enabled`.
 - [Transaction types](venue_prepaid_booking_system_plan.md#L215): e.g., `DEPOSIT`, [`GRACE_CONSUMPTION`](venue_prepaid_booking_system_plan.md#L228), [`GRACE_PAYMENT`](venue_prepaid_booking_system_plan.md#L232).
 - [Account relations](venue_prepaid_booking_system_plan.md#L320): `depositLinks()` relation and grace checks.

## VIP Query Parameter Tracking

 - [VIP Link Hit Tracking](vip_query_parameter_tracking.md#L44): Auto‑captures query params on `/v/{code}` with array preservation and rich context.
 - [VIP Session Enhancement](vip_query_parameter_tracking.md#L50): Persists initial query params at session level; adds referrer and landing URL.
 - [Conversion linkage](vip_query_parameter_tracking.md#L67): Links Link Hit → VIP Session → Booking via `vip_session_id`.
 - [Database tables](vip_query_parameter_tracking.md#L144): `vip_link_hits` (GIN on `query_params`), enhanced `vip_sessions`, and `bookings.vip_session_id`.
 - [Create VIP Session API](vip_query_parameter_tracking.md#L185): Accepts `vip_code` and `query_params`; returns `session_token`, `expires_at`.
 - [Route integration](vip_query_parameter_tracking.md#L221): `v/{code}` route calls `TrackVipLinkHit` and redirects preserving params.
 - [Example UTM link](vip_query_parameter_tracking.md#L18): Demonstrates multi‑value params and attribution fields.

## VIP Session Commands

 - [`vip:create-session-token`](vip-session-commands.md#L7): Creates VIP session tokens for a code or demo; supports `--expires`, search, and list.
 - [`vip:setup-demo-user`](vip-session-commands.md#L222): Ensures a demo user/concierge exists for demo sessions.
 - [`user:create-token`](vip-session-commands.md#L146): Creates a Sanctum user token with `--name`, `--expires`, and `--abilities`.
 - [Token usage](vip-session-commands.md#L138): Use as `Authorization: Bearer <token>`; [24‑hour expiry](vip-session-commands.md#L141).
