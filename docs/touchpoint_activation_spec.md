# PRIMA – Touchpoint First-Scan Activation
## Technical Specification v2.0

## 1. Purpose

Provide a scalable mechanism to pre-manufacture "virgin" Touchpoints, allow field partners to instantly assign them to new concierges during on-site visits, and attribute all subsequent scans, bookings, and revenue to that concierge for tracking and payout.

## 2. Scope & Use Cases

**Pre-generation**: Fixed batches of touchpoints printed on physical plaques/stickers for field deployment
**On-site activation**: Partner scans virgin touchpoint, concierge completes registration, touchpoint becomes permanently linked to that concierge account
**Consumer interaction**: Customers scan active touchpoint to open the concierge's PRIMA VIP booking flow
**Admin oversight**: Back-office team views status of all touchpoints (virgin vs assigned), detailed performance metrics (scans → bookings → revenue), bulk operations

## 3. Actors & Roles

| Actor | Description | Responsibilities |
|-------|-------------|------------------|
| **Partner** | PRIMA partner who installs touchpoints in field | Deploys touchpoints, assists concierges with activation |
| **Concierge** | Business owner/manager who registers and manages VIP bookings | Completes registration, manages customer bookings |
| **End-Customer** | Person scanning QR to make a booking | Scans touchpoint, makes reservation through PRIMA |
| **PRIMA Admin** | HQ staff monitoring touchpoint performance | Generates batches, assigns to partners, monitors status, analyzes performance |

## 4. Current State

PRIMA already has:
- ✅ QR code bulk generation (`GenerateQrCodes` action)
- ✅ QR code assignment to concierges (`AssignQrCodeToConcierge` action)  
- ✅ Short URL routing (`/t/{shortURLKey}` → `ShortURLController`)
- ✅ Concierge invitation page (`/invitation/{referral}` → `ConciergeInvitation`)
- ✅ QR code management in Filament admin (`QrCodeResource`)
- ✅ Visit tracking via Short URL package
- ✅ VIP booking flow integration

## 5. High-Level Workflow

### Factory Pre-Generation
1. **PRIMA Admin** generates batch of touchpoints in admin interface
2. **System** creates unassigned touchpoints (`concierge_id` = null)
3. **Admin** assigns touchpoints to partners for field deployment
4. **Admin** exports batch to CSV for print vendor
5. **Physical touchpoints** printed and distributed to assigned partners

### Activation Flow
1. **Partner** scans virgin touchpoint (existing `/t/{token}` URL - no changes)
2. **Backend** resolves token → status virgin → redirect to concierge invitation
3. **Concierge** completes registration, accepts terms
4. **System** executes assignment: verify token still virgin, assign to concierge and partner, set timestamps
5. **Success page** displays confirmation and onboarding information

### Customer Scan Flow
1. **Customer** scans assigned touchpoint (same `/t/{token}` URL)
2. **Backend** resolves token → status assigned → redirect to VIP booking flow
3. **Booking attribution** links reservation to touchpoint, concierge, and originating partner

## 6. Implementation

### 6.1 Database Schema Changes
**Existing `qr_codes` table supports most requirements:**
- `concierge_id` NULL = Virgin, NOT NULL = Assigned
- `assigned_at` timestamp for activation tracking
- `scan_count` for performance metrics
- `notes` field for batch identification
- Short URL integration for visit tracking

**Required Enhancement** (partner tracking):
```php
Schema::table('qr_codes', function (Blueprint $table) {
    $table->unsignedBigInteger('partner_id')->nullable()->after('concierge_id');
    $table->foreign('partner_id')->references('id')->on('partners');
    $table->index(['concierge_id', 'assigned_at']); // For performance queries
});
```

### 6.2 Touchpoint Admin Interface
**File**: `app/Filament/Resources/TouchpointResource.php`

**Features:**
- Filter touchpoints by status (virgin/assigned) and partner assignment
- Generate Touchpoint Batch action with quantity and notes
- Partner assignment capability for touchpoint distribution
- Table columns: Token, Status Badge, Assigned Concierge, Partner, Scans, Bookings, Revenue, Dates
- Bulk actions: Export CSV, Assign to Partner, Deactivate (set inactive), Re-assign (admin only)
- Performance metrics: Last 30 days + lifetime stats

### 6.3 Touchpoint Generation Action
**File**: `app/Actions/GenerateTouchpointBatch.php`

**Features:**
- Extends existing `GenerateQrCodes` action
- Creates unassigned touchpoints (`concierge_id` = null, `partner_id` = null)
- Sets batch notes for tracking
- Returns collection for CSV export

### 6.4 Custom QR Resolution Controller
**File**: `app/Http/Controllers/TouchpointController.php`

**Logic:**
- Check QR assignment status (`concierge_id`)
- Virgin QRs → redirect to concierge invitation with QR token
- Assigned QRs → normal Short URL behavior (VIP booking)
- Track scan events for analytics

### 6.5 Concierge Invitation Enhancement
**File**: `app/Livewire/Concierge/ConciergeInvitation.php`

**Changes:**
- Accept `qr` parameter from URL
- After successful registration, assign QR to new concierge
- Handle assignment conflicts (QR already assigned)

## 7. Admin Dashboard Requirements

### 7.1 Touchpoint Table View
**Columns:**
- **Token**: QR code identifier (searchable, copyable)
- **Status**: Badge (Virgin/Assigned/Inactive)
- **Assigned Concierge**: Link to concierge profile
- **Partner**: PRIMA partner who deployed the touchpoint
- **Scans**: Total scan count from Short URL tracking
- **Bookings**: Count of attributed bookings (last 30 days | lifetime)
- **Revenue**: Attributed revenue from bookings (last 30 days | lifetime)
- **Assigned Date**: When QR was activated
- **Generated Date**: When QR was created

### 7.2 Bulk Actions
- **Export CSV**: Print-ready format for vendors
- **Deactivate**: Set status to inactive (scans show retirement message)
- **Re-assign**: Admin-only capability to reassign QR to different concierge

### 7.3 Performance Analytics
- **Status Overview**: Virgin vs Assigned counts
- **Activation Rate**: Percentage of generated QRs that get assigned
- **Time to Activation**: Average time from generation to assignment
- **Performance Metrics**: Scans → Bookings → Revenue conversion rates

## 8. Business Rules & Edge Cases

### 8.1 Assignment Rules
- **First-scan wins**: Whichever concierge registration completes first locks the QR
- **Idempotent assignment**: Prevent race conditions during concurrent activations
- **One-time activation**: Each QR can only be assigned once (except admin re-assignment)

### 8.2 Timeout Handling
- **Registration TTL**: When virgin QR redirects to invitation, implement 30-minute timeout
- **Pending state**: Track in-progress registrations to prevent conflicts
- **Auto-revert**: Incomplete registrations revert QR to virgin status

### 8.3 Inactive QR Handling
- **Lost/damaged touchpoints**: Admin can mark as inactive
- **Consumer experience**: Inactive QR scans show retirement message with support contact
- **Historical integrity**: Maintain scan/booking history for reporting

### 8.4 Re-assignment Capability
- **Admin-only**: Restricted to authorized admin users
- **Historical preservation**: Previous scan/booking data remains linked to original concierge
- **Audit trail**: Log all re-assignment actions with timestamps and reasons

## 9. API Endpoints (Internal)

### 9.1 QR Resolution (Public)
```
GET /t/{token} (existing URL - no changes)
Response: 302 redirect based on assignment status
- Virgin: → /invitation?qr={token}
- Assigned: → VIP booking flow via existing Short URL system
- Inactive: → Retirement message page
```

### 9.2 Assignment API (Internal)
```
POST /api/v1/touchpoints/{token}/assign
Body: {
  "concierge_id": "uuid",
  "partner_id": "uuid" // PRIMA partner ID who facilitated activation
}
Response: 201 Created | 409 Conflict (already assigned)
```

### 9.3 Admin Management API
```
GET /api/admin/touchpoints
Query: ?status=virgin|assigned&limit=100&cursor=...
Response: Paginated list with performance metrics

PUT /api/admin/touchpoints/{token}/deactivate
Response: 200 OK with updated status

PUT /api/admin/touchpoints/{token}/reassign
Body: { "concierge_id": "uuid", "reason": "string" }
Response: 200 OK with audit log
```

## 10. Performance & Analytics

### 10.1 Metrics Collection
- **Scan tracking**: Leverages existing Short URL visit analytics
- **Attribution chain**: QR → Concierge → Bookings → Revenue
- **Time-based reporting**: Last 30 days vs lifetime performance
- **Conversion funnel**: Scans → Activations → Bookings → Revenue

### 10.2 Reporting Queries
**Booking Attribution:**
```sql
-- Bookings attributed to touchpoints
SELECT qr.url_key, c.user_id, p.company_name as partner, COUNT(b.id) as bookings, SUM(b.total_amount) as revenue
FROM qr_codes qr
JOIN concierges c ON qr.concierge_id = c.id  
LEFT JOIN partners p ON qr.partner_id = p.id
JOIN bookings b ON c.id = b.concierge_id
WHERE qr.assigned_at IS NOT NULL
GROUP BY qr.id, c.id, p.id
```

**Performance Dashboard:**
```sql
-- Touchpoint performance summary
SELECT 
  qr.url_key,
  qr.assigned_at,
  qr.scan_count,
  COUNT(b.id) as total_bookings,
  COUNT(CASE WHEN b.created_at >= NOW() - INTERVAL '30 days' THEN 1 END) as recent_bookings,
  SUM(b.total_amount) as lifetime_revenue,
  SUM(CASE WHEN b.created_at >= NOW() - INTERVAL '30 days' THEN b.total_amount ELSE 0 END) as recent_revenue
FROM qr_codes qr
LEFT JOIN concierges c ON qr.concierge_id = c.id
LEFT JOIN bookings b ON c.id = b.concierge_id
GROUP BY qr.id
```

## 11. Testing Strategy

### 11.1 Core Workflow Tests
1. **Virgin touchpoint scan** → redirects to concierge invitation
2. **Concierge registration** → touchpoint gets assigned successfully
3. **Assigned touchpoint scan** → redirects to VIP booking flow
4. **Concurrent activation** → first-wins assignment logic
5. **Assignment timeout** → incomplete registrations revert to virgin

### 11.2 Admin Interface Tests
1. **Batch generation** → creates unassigned touchpoints
2. **Partner assignment** → touchpoints assigned to partners successfully
3. **Performance metrics** → accurate scan/booking/revenue attribution
4. **Bulk operations** → CSV export, partner assignment, deactivation, re-assignment
5. **Status filtering** → virgin/assigned/inactive filtering works

### 11.3 Analytics Tests
1. **Attribution accuracy** → bookings correctly linked to touchpoints and partners
2. **Time-based reporting** → 30-day vs lifetime metrics accurate
3. **Performance calculations** → conversion rates computed correctly

## 12. Deployment Plan

### 12.1 Database Changes
```php
// Add partner tracking to touchpoints
Schema::table('qr_codes', function (Blueprint $table) {
    $table->unsignedBigInteger('partner_id')->nullable()->after('concierge_id');
    $table->foreign('partner_id')->references('id')->on('partners');
    $table->index(['concierge_id', 'assigned_at']); // For performance queries
});
```

### 12.2 Code Changes
1. **TouchpointController**: Custom resolution logic (extends existing ShortURLController)
2. **TouchpointResource**: Dedicated admin interface with partner assignment
3. **GenerateTouchpointBatch**: Batch generation action
4. **ConciergeInvitation**: Touchpoint assignment integration with partner tracking
5. **Routes**: Update existing `/t/{shortURLKey}` route to use custom controller (no URL changes)

### 12.3 Migration Strategy
1. **Backward compatibility**: Existing QR codes continue working normally
2. **Gradual rollout**: Deploy admin interface first, then activation logic
3. **Performance monitoring**: Track QR resolution latency and success rates
4. **Analytics validation**: Verify attribution accuracy with sample data

## 13. Success Metrics

### 13.1 Operational Metrics
- **Activation Rate**: >80% of generated touchpoints activated within 30 days
- **Assignment Success**: >95% successful assignments without conflicts
- **Resolution Performance**: <200ms p95 latency for QR resolution

### 13.2 Business Metrics
- **Customer Acquisition**: Track concierges acquired through touchpoints
- **Revenue Attribution**: Measure incremental bookings from touchpoint channel
- **Partner Performance**: Monitor touchpoints deployed per partner and their success rates

### 13.3 Technical Metrics
- **System Availability**: >99.9% uptime for QR resolution service
- **Error Rates**: <1% failed QR scans or assignment errors
- **Performance**: Maintain existing Short URL performance benchmarks

## 14. Summary

This implementation leverages PRIMA's existing QR code infrastructure with minimal changes:

**Core Features:**
- **Touchpoint Management**: Dedicated admin interface for deployment workflow
- **Partner Assignment**: Assign touchpoints to specific partners for field deployment
- **First-Scan Activation**: Virgin touchpoints automatically redirect to concierge invitation
- **Performance Analytics**: Complete attribution chain from partner to scans to revenue
- **Field Operations**: Batch generation, partner assignment, CSV export, status tracking

**Key Benefits:**
- **Simple architecture**: Uses existing `qr_codes` table and Short URL system
- **Complete attribution**: Full tracking from partner → touchpoint → concierge → booking revenue
- **Partner management**: Track partner performance and touchpoint deployment success
- **Scalable deployment**: Batch generation and partner-friendly distribution
- **Admin oversight**: Comprehensive dashboard for monitoring and management

**Total Implementation:**
- ~6 files: Controller, Resource, Action, Component updates, Route change
- ~400 lines of code
- No breaking changes to existing QR code functionality
- Complete analytics and reporting capabilities