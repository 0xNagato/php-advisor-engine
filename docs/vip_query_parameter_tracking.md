# VIP Query Parameter Tracking

## Business Impact & Value

**What This Does:** When customers click on PRIMA VIP links (like those shared by concierges or in marketing campaigns), this feature automatically captures and stores all the extra information included in the link. This "extra information" comes in the form of query parameters - those are the parts of a web address that come after the `?` symbol.

**Why It Matters:** This gives us powerful insights into:
- **Marketing Performance:** Which campaigns, ads, or social media posts drive the most qualified traffic
- **Partner Success:** Which concierges and venues send us customers who actually book
- **Customer Preferences:** What amenities, cuisines, or features customers are looking for
- **Business Optimization:** Data-driven decisions about where to invest marketing dollars
- **Complete Conversion Funnel:** Track the entire customer journey from click to booking to revenue

**Real-World Example:**
```
Original link: https://prima.test/v/MIAMI2024

Enhanced link: https://prima.test/v/MIAMI2024?utm_source=facebook&utm_campaign=summer2024&cuisine[]=italian&cuisine[]=seafood&guest_count=4
```

This enhanced link tells us:
- The customer came from Facebook
- It was part of our Summer 2024 campaign
- They're interested in Italian and Seafood restaurants
- They want to book for 4 people

**How It Helps Your Business:**
- **Track ROI:** See which marketing channels actually convert to bookings, not just clicks
- **Reward Top Performers:** Identify which concierges drive the most valuable business
- **Optimize Campaigns:** Focus budget on what works based on real conversion data
- **Understand Customers:** Learn what features and options customers value most
- **Improve Partnerships:** Strengthen relationships with venues that attract high-quality leads
- **Revenue Attribution:** Calculate actual revenue impact of different traffic sources
- **Business Intelligence:** Make data-driven decisions with complete customer journey analytics

**Privacy & Security:** All data stays within PRIMA's systems. No personal customer information is shared externally. The system is designed to be GDPR-compliant and respects user privacy.

## Overview

This feature enables comprehensive tracking of query parameters on VIP links and sessions, providing valuable insights into marketing attribution, user behavior, and conversion funnels.

## Features

### 1. VIP Link Hit Tracking
- **Automatic capture** of all query parameters when users visit VIP links (`/v/{code}`)
- **Array preservation** for parameters like `cuisine[]=value1&cuisine[]=value2`
- **Non-blocking operation** - tracking failures never interrupt user flows
- **Rich context** including IP, user agent, referer, and full URL

### 2. VIP Session Enhancement
- **Session-level persistence** of initial query parameters
- **API integration** for frontend applications
- **Referrer tracking** to understand traffic sources
- **Landing URL capture** for conversion attribution

### 3. Database Schema
- **PostgreSQL-optimized** with JSONB storage and GIN indexes
- **Efficient querying** of complex parameter structures
- **Future-ready** foundation for analytics and reporting

## Booking Linkage

### How VIP Sessions Connect to Bookings

The VIP query parameter tracking system provides a complete conversion funnel from link click to booking:

1. **Link Click → VIP Link Hit** (`vip_link_hits` table)
   - Captures all initial query parameters when someone clicks a VIP link
   - Stores marketing attribution data (UTM parameters, campaigns, sources)
   - Records user context (IP, user agent, referer)

2. **Session Creation → VIP Session** (`vip_sessions` table)
   - When frontend creates a VIP session, query parameters are persisted
   - Session tokens enable authentication for subsequent API calls
   - Sessions expire after 24 hours for security

3. **Booking Creation → Linked Booking** (`bookings` table)
   - Bookings are automatically linked to VIP sessions via `vip_session_id`
   - Frontend can provide session token in booking request
   - OR system automatically detects VIP session from Bearer token authentication
   - Query parameters from original link click are available for analytics

### Frontend Integration for Booking Linkage

```javascript
// Frontend creates VIP session with captured query parameters
const queryParams = new URLSearchParams(window.location.search);
const vipCode = 'MIAMI2024'; // From URL path

const sessionResponse = await fetch('/api/vip/sessions', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        vip_code: vipCode,
        query_params: Object.fromEntries(queryParams.entries())
    })
});

// Store session token for booking requests
const { data: { session_token } } = await sessionResponse.json();

// When user makes a booking, include session token
const bookingResponse = await fetch('/api/bookings', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${session_token}`
    },
    body: JSON.stringify({
        schedule_template_id: selectedTemplateId,
        date: selectedDate,
        guest_count: guestCount,
        session_token: session_token // Optional but recommended
    })
});
```

### Backend Linkage Logic

The system automatically links bookings to VIP sessions through two methods:

**Method 1: Session Token in Request**
- Frontend provides `session_token` parameter in booking request
- System validates token and extracts VIP session ID
- Booking is created with `vip_session_id` populated

**Method 2: Bearer Token Authentication**
- If no `session_token` provided, system checks VIP session context from Bearer token
- VIP session ID is extracted from authentication context
- Booking is linked to the authenticated VIP session

### Benefits of Complete Linkage

- **Full Conversion Tracking:** Link clicks → sessions → bookings → revenue
- **Marketing Attribution:** See which campaigns drive actual bookings, not just clicks
- **Partner ROI:** Measure concierge effectiveness through booking completion
- **Customer Journey:** Understand the complete path from marketing to conversion
- **Revenue Analytics:** Calculate actual revenue impact of different traffic sources

## Implementation Details

### Database Tables

#### `vip_link_hits`
```sql
CREATE TABLE vip_link_hits (
    id BIGSERIAL PRIMARY KEY,
    vip_code_id BIGINT REFERENCES vip_codes(id) ON DELETE SET NULL,
    code VARCHAR(32),
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referer_url TEXT,
    full_url TEXT,
    raw_query TEXT,
    query_params JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX vip_link_hits_vip_code_id_idx ON vip_link_hits(vip_code_id);
CREATE INDEX vip_link_hits_visited_at_idx ON vip_link_hits(visited_at);
CREATE INDEX vip_link_hits_query_params_gin ON vip_link_hits USING GIN(query_params);
```

#### Enhanced `vip_sessions`
```sql
ALTER TABLE vip_sessions ADD COLUMN query_params JSONB;
ALTER TABLE vip_sessions ADD COLUMN landing_url TEXT;
ALTER TABLE vip_sessions ADD COLUMN referer_url TEXT;

CREATE INDEX vip_sessions_query_params_gin ON vip_sessions USING GIN(query_params);
```

#### Enhanced `bookings`
```sql
ALTER TABLE bookings ADD COLUMN vip_session_id BIGINT REFERENCES vip_sessions(id) ON DELETE SET NULL;
CREATE INDEX bookings_vip_session_id_idx ON bookings(vip_session_id);
```

### API Endpoints

#### Create VIP Session with Query Parameters
```http
POST /api/vip/sessions
Content-Type: application/json

{
    "vip_code": "MIAMI2024",
    "query_params": {
        "utm_source": "facebook",
        "utm_campaign": "summer2024",
        "cuisine": ["italian", "seafood"],
        "guest_count": 4
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "session_token": "eyJ0eXAiOiJKV1Q...",
        "expires_at": "2025-01-01T12:00:00Z",
        "vip_code": {
            "id": 123,
            "code": "MIAMI2024",
            "concierge": {
                "id": 456,
                "name": "John Doe",
                "hotel_name": "Luxury Resort Miami"
            }
        }
    }
}
```

### Route Integration

#### VIP Link Tracking (`routes/web.php`)
```php
Route::get('v/{code}', function ($code) {
    // Fire-and-forget tracking of VIP landing with query params
    try {
        app(TrackVipLinkHit::class)->handle($code, request());
    } catch (Throwable $e) {
        // Never block redirect on tracking failure
    }

    // Original redirect logic preserved
    $queryParams = request()->query();
    $redirectUrl = config('app.booking_url') . "/vip/{$code}";

    if (!empty($queryParams)) {
        $redirectUrl .= '?' . http_build_query($queryParams);
    }

    return redirect($redirectUrl);
})->name('v.booking');
```

## Usage Examples

### Marketing Attribution
Track which campaigns drive VIP link visits:
```
https://prima.test/v/MIAMI2024?utm_source=facebook&utm_campaign=summer2024&utm_medium=cpc
```

### Partner Referrals
Track referral sources:
```
https://prima.test/v/MIAMI2024?ref=partner123&source=hotel_website
```

### User Preferences
Capture user preferences:
```
https://prima.test/v/MIAMI2024?cuisine[]=italian&cuisine[]=seafood&guest_count=4&special_requests=romantic
```

### Array Parameters
Preserve array structures:
```
https://prima.test/v/MIAMI2024?tags[]=food&tags[]=nightlife&tags[]=luxury
```

## Benefits

### 1. Marketing Insights
- **Campaign attribution**: Track which marketing channels drive VIP link visits
- **Conversion analysis**: Link query parameters to actual bookings
- **ROI measurement**: Understand which campaigns and partners perform best

### 2. User Behavior Analysis
- **Preference tracking**: Understand what parameters users typically include
- **Journey mapping**: See how users navigate from VIP links to bookings
- **Segmentation**: Group users by their initial query parameters

### 3. Partner Management
- **Referral tracking**: See which partners send the most qualified traffic
- **Performance metrics**: Measure partner success by conversion rates
- **Optimization**: Identify which parameters lead to successful bookings

### 4. Technical Benefits
- **Non-blocking**: Tracking failures never interrupt user experience
- **Scalable**: JSONB storage with GIN indexes for efficient querying
- **Backward compatible**: All existing functionality preserved
- **Future-ready**: Foundation for advanced analytics and reporting

## Data Retention and Privacy

### Storage Strategy
- **JSONB format**: Efficient storage of complex parameter structures
- **GIN indexes**: Fast querying of JSONB fields in PostgreSQL
- **Truncation**: Long parameter values are truncated to prevent storage bloat
- **Array preservation**: Maintains array structures like `cuisine[]=value1&cuisine[]=value2`

### Privacy Considerations
- **Optional PII**: Query parameters may contain personal information
- **Server-side only**: No client-side tracking or cookies
- **Internal use**: Data is stored internally for analysis only
- **No third-party sharing**: Data remains within the PRIMA platform

### Data Retention
- **Production policy**: Consider implementing retention policies for old tracking data
- **GDPR compliance**: Ensure compliance with data protection regulations
- **Access controls**: Limit access to tracking data to authorized personnel only

## Testing

Comprehensive test suite covers:
- ✅ **VIP landing capture** with various parameter types
- ✅ **Array parameter preservation**
- ✅ **Invalid VIP code handling**
- ✅ **Session creation with query parameters**
- ✅ **Validation of parameter formats**
- ✅ **Error handling and resilience**
- ✅ **Referer and user agent tracking**
- ✅ **URL encoding/decoding**
- ✅ **Truncation of long values**

## Future Enhancements

### Phase 2: Analytics Dashboard
- **Filament interface** for viewing captured parameters
- **Conversion funnel analysis**
- **Campaign performance metrics**
- **Partner attribution reports**

### Phase 3: Advanced Features
- **Real-time parameter analysis**
- **A/B testing integration**
- **Automated insights**
- **Export capabilities**

### Phase 4: Machine Learning
- **Predictive analytics** for conversion likelihood
- **Parameter optimization suggestions**
- **Automated campaign insights**

## Implementation Status

✅ **Phase 1 Complete**: Core tracking functionality implemented
- Database schema with migrations
- Models and actions for data capture
- Route integration with error resilience
- API enhancements for session tracking
- Comprehensive test suite (11 tests, 60+ assertions)
- Full documentation

🚀 **Ready for Production**: Feature is fully functional and tested
