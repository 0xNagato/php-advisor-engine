# PRIMA – Venue Management Dashboard
## Technical Specification v1.0

## 1. Purpose & Scope

Replace Prima's existing venue dashboard with a comprehensive, data-driven interface that lets venue managers monitor concierge performance, partner contributions, and operational metrics. This spec covers Filament widgets, database queries, and implementation requirements.

**Target Users:**
- **Venue Managers**: Multi-venue oversight and management  
- **Individual Venue Owners**: Single venue performance monitoring

## 2. Current Prima Foundation

### ✅ Existing Infrastructure
- **VenueDashboard**: `/app/Filament/Pages/Venue/VenueDashboard.php` (basic earnings/bookings)
- **VenueManagerDashboard**: `/app/Filament/Pages/VenueManager/VenueManagerDashboard.php` (multi-venue)
- **Role System**: `venue` and `venue_manager` roles with proper authentication
- **Analytics Widgets**: VenueOverview, VenueRecentBookings with earnings integration
- **Models**: Venue, VenueGroup, Booking with comprehensive relationships
- **Earnings System**: Multi-currency support via `HasEarnings` trait

### 🔄 Components to Replace/Enhance
- Current basic KPI widgets with advanced metrics
- Simple booking tables with interactive leaderboards
- Basic earnings display with comprehensive partner analytics

## 3. Dashboard Layout & Components

### 3.1 Top KPI Cards

**Layout**: 3-card responsive grid (348px width on ≥sm screens, full-width mobile)

| Metric | Data Source | Display | Sparkline |
|--------|-------------|---------|-----------|
| **Concierges with Bookings** | `kpi.conciergeCount` | Count + trend % | `kpi.activeConciergesTrend` |
| **PRIME Bookings** | `kpi.prime.bookings` | Count + `$kpi.prime.revenue` | Booking trend (7 days) |
| **Non-Prime Bookings** | `kpi.nonPrime.bookings` | Count + active concierges | Booking trend (7 days) |


### 3.2 Hotel Partners Table

**Table**: QR Concierges (`is_qr_concierge = true`)

| Column | Data Key | Sort | Display |
|--------|----------|------|---------|
| **Hotel** | `concierge.hotel_name` | Alpha | Hotel company name |
| **Unique Concierges** | `qrCodeCount` | Numeric | Number of QR codes owned by this concierge |
| **Bookings** | `bookingCount` | Numeric (default DESC) | Total bookings from QR scans |
| **Revenue** | `revenue` | Numeric | Formatted currency earned |

**Data Source**: `concierges` table where `is_qr_concierge = true`
**Default Sort**: Bookings DESC

### 3.3 Concierge Leaderboard

**Table**: Regular concierges (`is_qr_concierge = false`)

| Column | Data Key | Sort | Display |
|--------|----------|------|---------|
| **Ranking** | Auto-generated | - | #1, #2, #3... |
| **Concierge** | `user.name` | Alpha | Individual concierge name |
| **Bookings** | `bookings` | Numeric | Total booking count |
| **Covers** | `covers` | Numeric | Total guest count |
| **Revenue** | `revenue` | Numeric | Formatted currency earned |

**Data Source**: `concierges` table where `is_qr_concierge = false`
**Default Sort**: Revenue DESC

### 3.4 Business Intelligence

**Single Table**: Combined performance and trouble metrics with date range picker

| Column | Data Key | Sort | Display |
|--------|----------|------|---------|
| **Concierge** | `concierge.name` | Alpha | Name |
| **Bookings** | `bookings` | Numeric | Total count |
| **No Shows** | `noShows` | Numeric | Count |
| **Revenue** | `revenue` | Numeric | Formatted currency |
| **Cancellations** | `cancellations` | Numeric | Count |
| **Trouble Rate** | `troublePct` | Numeric DESC | Percentage |

**Data Source**: All concierges (both `is_qr_concierge = true` and `false`)
**Styling**:
- Row background: `bg-red-50` if `troublePct > 15%`
- Trouble rate: Red text if >15%
- Sort default: Trouble Rate DESC

## 4. Technical Implementation

### 4.1 Enhanced VenueDashboard Page

**File**: `/app/Filament/Pages/Venue/VenueDashboard.php`

**New Features**:
```php
class VenueDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.venue.dashboard';
    
    // Date range filtering
    public $dateRange = '30d';
    
    protected function getHeaderWidgets(): array
    {
        return [
            VenueKpiCards::class,
            ConciergeGroupsTable::class,
            ConciergeLeaderboard::class,
            BusinessIntelligence::class,
        ];
    }
}
```

### 4.2 KPI Cards Widget

**File**: `/app/Livewire/Venue/VenueKpiCards.php`

**Data Structure**:
```php
protected function getKpiData(): array
{
    return [
        'conciergeCount' => $this->getActiveConciergeCount(),
        'activeConciergesTrend' => $this->getConciergesTrend(),
        'prime' => [
            'bookings' => $this->getPrimeBookingCount(),
            'revenue' => $this->getPrimeRevenue(),
        ],
        'nonPrime' => [
            'bookings' => $this->getNonPrimeBookingCount(),
            'activeConcierges' => $this->getNonPrimeActiveConcierges(),
        ]
    ];
}
```

### 4.3 Hotel Partners Table Widget

**File**: `/app/Livewire/Venue/HotelPartnersTable.php`

**Database Query** (QR Concierges):
```php
protected function getHotelPartnersQuery(): Builder
{
    return Concierge::query()
        ->select([
            'concierges.id',
            'concierges.hotel_name',
            DB::raw('COUNT(DISTINCT qr_codes.id) as qr_code_count'),
            DB::raw('COUNT(bookings.id) as booking_count'),
            DB::raw('SUM(earnings.amount) as revenue')
        ])
        ->leftJoin('qr_codes', 'concierges.id', '=', 'qr_codes.concierge_id')
        ->join('bookings', 'concierges.id', '=', 'bookings.concierge_id')
        ->join('earnings', 'bookings.id', '=', 'earnings.booking_id')
        ->where('concierges.is_qr_concierge', true)
        ->whereBetween('bookings.booking_date', $this->getDateRange())
        ->groupBy('concierges.id', 'concierges.hotel_name')
        ->orderBy('booking_count', 'desc');
}
```

### 4.4 Concierge Leaderboard Widget

**File**: `/app/Livewire/Venue/ConciergeLeaderboard.php`

**Database Query** (Regular Concierges):
```php
protected function getConciergeLeaderboardQuery(): Builder
{
    return Concierge::query()
        ->select([
            'concierges.id',
            'users.name as concierge_name',
            DB::raw('COUNT(bookings.id) as booking_count'),
            DB::raw('SUM(bookings.guest_count) as covers'),
            DB::raw('SUM(earnings.amount) as revenue')
        ])
        ->join('users', 'concierges.user_id', '=', 'users.id')
        ->join('bookings', 'concierges.id', '=', 'bookings.concierge_id')
        ->join('earnings', 'bookings.id', '=', 'earnings.booking_id')
        ->where('concierges.is_qr_concierge', false)
        ->whereBetween('bookings.booking_date', $this->getDateRange())
        ->groupBy('concierges.id', 'users.name')
        ->orderBy('revenue', 'desc')
        ->limit(10);
}
```

### 4.5 Business Intelligence Widget

**File**: `/app/Livewire/Venue/BusinessIntelligence.php`

**Business Intelligence Query**:
```php
protected function getBusinessIntelligenceData(): Collection
{
    return Concierge::query()
        ->select([
            'concierges.id',
            'concierges.is_qr_concierge',
            DB::raw('
                CASE 
                    WHEN concierges.is_qr_concierge = 1 THEN concierges.hotel_name
                    ELSE users.name 
                END as concierge_name
            '),
            DB::raw('COUNT(bookings.id) as total_bookings'),
            DB::raw('SUM(CASE WHEN bookings.status = "no_show" THEN 1 ELSE 0 END) as no_shows'),
            DB::raw('SUM(CASE WHEN bookings.status = "cancelled" THEN 1 ELSE 0 END) as cancellations'),
            DB::raw('SUM(earnings.amount) as revenue'),
            DB::raw('ROUND(
                (SUM(CASE WHEN bookings.status IN ("no_show", "cancelled") THEN 1 ELSE 0 END) * 100.0) / 
                COUNT(bookings.id), 1
            ) as trouble_pct')
        ])
        ->join('users', 'concierges.user_id', '=', 'users.id')
        ->join('bookings', 'concierges.id', '=', 'bookings.concierge_id')
        ->join('earnings', 'bookings.id', '=', 'earnings.booking_id')
        ->whereBetween('bookings.booking_date', $this->getDateRange())
        ->groupBy('concierges.id', 'users.name', 'concierges.hotel_name', 'concierges.is_qr_concierge')
        ->having('total_bookings', '>=', 5) // Min 5 bookings for relevance
        ->orderBy('trouble_pct', 'desc')
        ->get();
}
```

## 5. Data Sources & Relationships

### 5.1 Database Integration

**Core Models Used**:
- **Venue**: Venue information and ownership
- **VenueGroup**: Multi-venue management
- **Concierge**: Individual concierge data
- **Partner**: Hotel/partner company information
- **Booking**: Reservation data with status tracking
- **Earnings**: Revenue attribution

**Key Relationships**:
```php
// VenueGroup -> Venues -> Bookings -> Concierges -> Partners
$venueGroup->venues()
    ->with(['bookings.concierge.partner', 'bookings.earnings'])
    ->whereBetween('bookings.booking_date', $dateRange)
```

### 5.2 Performance Optimization

**Query Optimization**:
- Use aggregate queries with DB::raw() for KPIs
- Eager load relationships to prevent N+1 queries
- Index on booking_date, concierge_id, venue_id
- Cache expensive calculations (5-minute TTL)

**Caching Strategy**:
```php
Cache::remember(
    "venue_kpis_{$venue->id}_{$dateRange}", 
    300, // 5 minutes
    fn() => $this->calculateKpis()
);
```

## 6. Interactive Elements

**Table Features**:
- Sortable column headers 
- Row highlighting for trouble rates >15%
- Loading states during data refresh

## 7. Performance Requirements

### 7.1 Loading Targets

- **Initial Page Load**: < 2 seconds
- **Widget Load Time**: < 500ms
- **Table Data Refresh**: < 1 second

### 7.2 Scalability Considerations

**Database Performance**:
- Composite indexes on (venue_id, booking_date, status)
- Materialized views for complex aggregations
- Query result caching with Redis

**Frontend Performance**:
- Lazy loading for large tables
- Efficient Livewire component updates

## 8. Testing Requirements

### 8.1 Unit Tests

**Widget Tests**:
```php
// VenueKpiCardsTest.php
public function test_calculates_concierge_count_correctly(): void
public function test_formats_revenue_with_currency(): void
public function test_handles_empty_date_ranges(): void

// ConciergeGroupsTableTest.php  
public function test_sorts_by_bookings_descending(): void
public function test_groups_concierges_by_partner(): void

// BusinessIntelligenceTest.php
public function test_calculates_trouble_rate_accurately(): void
public function test_highlights_high_trouble_rates(): void
```

### 8.2 Feature Tests

**Dashboard Integration**:
```php
public function test_venue_manager_can_access_dashboard(): void
public function test_venue_owner_sees_only_own_venue_data(): void
public function test_widgets_load_correctly(): void
public function test_data_filtering_works(): void
```

## 9. Migration & Deployment

### 9.1 Database Changes

**New Indexes** (for performance):
```sql
CREATE INDEX idx_bookings_venue_date_status ON bookings(venue_id, booking_date, status);
CREATE INDEX idx_earnings_booking_type ON earnings(booking_id, type);
CREATE INDEX idx_concierges_partner ON concierges(partner_id);
```

### 9.2 Implementation Tasks

**Data Layer**:
- [ ] KPI calculation methods
- [ ] Livewire widget data methods
- [ ] Database query optimization
- [ ] Caching implementation

**Core Widgets**:
- [ ] VenueKpiCards widget
- [ ] ConciergeGroupsTable widget
- [ ] Basic responsive layout

**Advanced Features**:
- [ ] ConciergeLeaderboard widget
- [ ] BusinessIntelligence widget with trouble rate
- [ ] Real-time updates and auto-refresh

**Polish & Testing**:
- [ ] Responsive design refinements
- [ ] Performance optimization
- [ ] Comprehensive testing
- [ ] Documentation and deployment

### 9.3 Rollout Strategy

1. **Feature Flag**: Enable for select venue managers initially
2. **A/B Testing**: Compare with existing dashboard metrics
3. **Gradual Rollout**: Venue-by-venue deployment based on feedback
4. **Full Replacement**: Switch all venues after validation

## 10. Success Metrics

### 10.1 Performance Metrics

- **Page Load Time**: Target < 2s (measure 95th percentile)
- **Widget Load Time**: Target < 500ms for KPIs
- **Cache Hit Rate**: Target > 80% for dashboard queries
- **Error Rate**: Target < 0.1% for dashboard requests

### 10.2 User Engagement Metrics

- **Daily Active Users**: Track venue manager daily usage
- **Session Duration**: Measure time spent on dashboard
- **Feature Adoption**: Track usage of each widget
- **User Feedback**: Survey satisfaction vs. old dashboard

### 10.3 Business Impact Metrics

- **Operational Efficiency**: Reduction in support tickets
- **Decision Speed**: Time from data to action
- **Revenue Visibility**: Improved revenue tracking accuracy
- **Partner Satisfaction**: Feedback from venue partners

---

*This specification replaces Prima's existing venue dashboard with a comprehensive, real-time management interface built on Laravel/Filament foundations, providing venue managers with the analytics they need to optimize operations and partner relationships.*