# Lovable + Stack Integration Plan

## Overview

This document outlines our strategy for using Lovable alongside our existing tech stack to rapidly develop a unified customer portal serving all user types (consumers, restaurant managers, concierges, hotel managers, and influencers) while maintaining our core Laravel/Filament admin backend.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        Admin Backend                            │
│                   (Laravel + Filament)                          │
│  - Internal operations                                          │
│  - Business logic                                               │
│  - Platform integrations (CoverManager, Restoo)                 │
│  - Booking management                                           │
│  - SMS notifications (Twilio, SimpleTexting)                    │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         │ API Layer (JWT Auth)
                         │
┌────────────────────────┴────────────────────────────────────────┐
│              Unified Customer Portal (Single React App)          │
│                  (React + shadcn + Next.js)                     │
├─────────────────────────────────────────────────────────────────┤
│  Role-Based Views:                                              │
│  • Consumers: Browse venues, make bookings                      │
│  • Restaurant Managers: Manage availability, view analytics     │
│  • Concierges/Hotels/Influencers: Track QR codes, earnings     │
└─────────────────────────────────────────────────────────────────┘
```

## Why This Separation?

### Filament Limitations
- **Customization constraints**: Filament is excellent for admin CRUD but difficult to customize for consumer UX
- **Caching challenges**: Tightly coupled to Laravel sessions, making proper HTTP caching difficult
- **Performance**: Every request hits PHP/Laravel, no static generation or edge caching

### Benefits of Separation
- **Performance**: Consumer apps can use aggressive caching, CDN distribution, static generation
- **Developer Experience**: React/shadcn/Storybook ecosystem enables rapid UI development
- **Scalability**: Deploy and scale consumer apps independently from admin backend
- **User Experience**: Modern, responsive interfaces optimized for each user type
- **Maintainability**: Single codebase for all customer-facing features
- **Component Reuse**: Shared design system across all user roles

## Why Not Wire Lovable Directly to the Backend

Connecting Lovable to a generic backend like Supabase would require duplicating our entire business logic layer, creating significant risks:

### Complex Business Rules
- **Dynamic Pricing**: Prime/non-prime calculations change based on time, day, venue capacity, and special events
- **Auto-approval Logic**: Small parties (≤7) auto-approve only if venue has platform integration and receives successful sync
- **Booking Caps**: Daily limits for prime and non-prime bookings per venue
- **Earnings Calculations**: Complex multi-party splits (venue 60%, platform 40%, then platform splits with concierge)

### Platform Integrations
- **CoverManager**: Real-time availability sync, bulk calendar API integration, respects human overrides
- **Restoo**: Reservation creation and management with specific field mappings
- **SMS Workflows**: Automated notifications via Twilio/SimpleTexting with template management
- **QR Attribution**: Tracking concierge referrals through short URLs and visit analytics

### Data Integrity Requirements
- **Availability Management**: Complex view (`schedule_with_bookings`) that calculates remaining tables based on templates, overrides, and existing bookings
- **Financial Accuracy**: Earnings must be calculated consistently across bookings, reports, and payouts
- **Audit Trail**: Activity logging for compliance and dispute resolution
- **PII Protection**: Structured approach to data masking and reveal actions

### Operational Complexity
- **Venue Onboarding**: Multi-step process with conditional logic
- **Modification Requests**: Complex workflow for booking changes
- **Platform-specific Logic**: Each restaurant platform has unique requirements
- **Regional Variations**: VAT, currency, and regulatory differences

Duplicating this in Lovable would mean:
1. Maintaining two sets of business rules that could drift apart
2. Risk of incorrect calculations affecting real money
3. Security vulnerabilities from simplified implementations
4. Inability to leverage existing platform relationships
5. Loss of historical data and analytics

## Implementation Strategy

### 1. Rapid Prototyping with Lovable
- Create high-fidelity mockups for all user types in the unified portal
- Design role-based views that share common components
- Iterate on UX/UI until stakeholders approve
- Focus on information architecture and user flows

### 2. Lovable to Claude Code Workflow

#### Design Handoff Process
1. **Lovable Design Phase**
   - Create high-fidelity mockups with real data examples
   - Include all states (loading, empty, error, success)
   - Document interactions and transitions
   - Generate final React code from Lovable

2. **Export Package**
   - Screenshots of all screens and states
   - Lovable's generated React code (for reference)
   - Interaction notes and user flow documentation
   - Component hierarchy and layout structure

3. **Claude Code Implementation**
   ```
   Input to Claude Code:
   - Screenshot: "Hotel dashboard showing occupancy metrics"
   - Lovable code: <MetricGrid>...</MetricGrid> structure
   - Note: "Cards should update every 5 minutes"
   
   Claude Code Process:
   1. Analyzes visual structure
   2. Maps Lovable components to our components via Storybook MCP
   3. Identifies required data via OpenAPI MCP
   4. Generates production code with proper typing and error handling
   ```

### 3. MCP (Model Context Protocol) Setup

#### Storybook MCP
Exposes our component library to Claude Code:
```typescript
// Example Storybook MCP response
{
  "components": {
    "MetricCard": {
      "props": ["title", "value", "trend", "icon"],
      "variants": ["default", "success", "warning"],
      "usage": "<MetricCard title='Revenue' value={125000} trend='up' />"
    }
  }
}
```

#### OpenAPI MCP
Provides API documentation and examples:
```yaml
# Example OpenAPI MCP response
/api/v1/venues/{id}/metrics:
  get:
    parameters:
      - name: period
        enum: ["today", "week", "month"]
    responses:
      200:
        example: {
          "revenue": 125000,
          "bookings": 45,
          "occupancy_rate": 0.75
        }
```

### 4. Component Development with Storybook
- Build reusable React components using shadcn/ui
- Document all props, variants, and states
- Include usage examples and best practices
- Maintain design tokens (colors, spacing, typography)
- Sync with Figma for design consistency

### 5. Production Code Generation

#### What Claude Code Generates
```typescript
// Example: Hotel Dashboard Component
import { MetricCard } from '@/components/ui/metric-card'
import { useVenueMetrics } from '@/hooks/api/venues'

export function HotelDashboard({ venueId }: Props) {
  const { data, isLoading } = useVenueMetrics(venueId, {
    period: 'month',
    refetchInterval: 5 * 60 * 1000 // 5 minutes
  })

  if (isLoading) return <DashboardSkeleton />
  
  return (
    <DashboardLayout>
      <MetricGrid>
        <MetricCard
          title="Monthly Revenue"
          value={data.revenue}
          format="currency"
          trend={data.revenueTrend}
        />
        {/* More metrics... */}
      </MetricGrid>
    </DashboardLayout>
  )
}
```

### 6. API Design
- Create purpose-built, cacheable endpoints for the unified portal
- Keep business logic in Laravel
- Design with caching in mind
- Role-based access control at the API level
- Version APIs to support gradual migrations

## API Design Principles

### Caching Strategy
```
Consumer Endpoints:
- /api/v1/venues/search              → Cache: 5 minutes
- /api/v1/venues/{id}/availability   → Cache: 2 minutes
- /api/v1/bookings                   → No cache (user-specific)

Restaurant Manager Endpoints:
- /api/v1/venues/{id}/metrics        → Cache: 15 minutes
- /api/v1/venues/{id}/bookings/today → Cache: 5 minutes  
- /api/v1/venues/{id}/revenue/month  → Cache: 1 hour

Concierge/Hotel/Influencer Endpoints:
- /api/v1/qr/{code}/performance      → Cache: 5 minutes
- /api/v1/concierge/earnings         → Cache: 15 minutes
- /api/v1/concierge/leaderboard      → Cache: 1 hour
```

### Authentication
- JWT tokens for the unified portal
- Role claims embedded in JWT (consumer, venue_manager, concierge, etc.)
- Separate from Laravel session auth
- Short-lived access tokens (1 hour)
- Refresh tokens for extended sessions

### Data Structure
```json
// Example: Hotel Dashboard Response
{
  "venue": {
    "id": "uuid",
    "name": "Restaurant Name",
    "metrics": {
      "period": "2024-01-01/2024-01-31",
      "occupancy_rate": 0.75,
      "revenue": 125000,
      "bookings_count": 450,
      "trend": "up"
    }
  },
  "cache_control": "max-age=900",
  "generated_at": "2024-01-15T10:00:00Z"
}
```

## Security Considerations

### PII Handling
- Consumer apps receive anonymized/aggregated data by default
- PII requires separate authenticated API calls
- Audit trail for all PII access

### API Rate Limiting
- Consumers: 200 requests/hour
- Restaurant Managers: 1000 requests/hour
- Concierges/Hotels/Influencers: 500 requests/hour  
- Implement exponential backoff

### CORS Configuration
- Whitelist specific domains for the unified portal
- Strict origin validation
- No wildcard origins in production

## Development Workflow

### Phase 1: Core Infrastructure & Consumer Features
1. **Week 1-2**: Lovable mockups for all user types
2. **Week 2-3**: Core API endpoints and authentication
3. **Week 3-4**: Shared component library in Storybook
4. **Week 4-5**: Consumer booking flow implementation
5. **Week 5-6**: Testing and initial deployment

### Phase 2: Restaurant Manager Features
1. **Week 7-8**: Dashboard and analytics views
2. **Week 8-9**: Availability management interface
3. **Week 9-10**: Integration with existing platform APIs

### Phase 3: Concierge/Hotel/Influencer Features
1. **Week 11-12**: QR tracking and earnings dashboards
2. **Week 12-13**: Commission tracking and leaderboards
3. **Week 13-14**: Final testing and full rollout

## Technical Stack

### Unified Customer Portal
- **Framework**: Next.js 14 (App Router)
- **UI Components**: shadcn/ui
- **Styling**: Tailwind CSS
- **State Management**: Zustand or TanStack Query
- **Charts**: Recharts or Tremor
- **Authentication**: NextAuth.js with JWT

### API Layer
- **Framework**: Laravel 11
- **API Documentation**: OpenAPI 3.0
- **Caching**: Redis + CloudFlare
- **Rate Limiting**: Laravel Throttle
- **Monitoring**: Laravel Telescope

### Development Tools
- **Mockups**: Lovable
- **Component Development**: Storybook
- **Code Generation**: Claude Code + MCPs
- **API Testing**: Postman/Insomnia
- **E2E Testing**: Playwright

## MCP Server Setup

### OpenAPI MCP
```yaml
servers:
  - url: https://api.prima.com/v1
    description: Production API
paths:
  /venues/{venueId}/metrics:
    get:
      summary: Get venue performance metrics
      parameters:
        - name: venueId
          in: path
          required: true
        - name: period
          in: query
          schema:
            type: string
            enum: [today, week, month, quarter]
```

### Storybook MCP
```typescript
// Exposes components like:
export { Button } from '@/components/ui/button'
export { Card } from '@/components/ui/card'
export { LineChart } from '@/components/charts/line-chart'
export { MetricCard } from '@/components/dashboard/metric-card'
```

## Success Metrics

### Performance
- Initial page load: < 1 second
- Time to interactive: < 2 seconds
- API response time: < 200ms (cached)
- Lighthouse score: > 90

### Business Impact
- Hotel manager engagement: Daily active usage
- Influencer portal adoption: 80% of active concierges
- Support ticket reduction: 30% fewer dashboard questions
- Feature development speed: 2x faster than Filament

## Future Enhancements

### Phase 3: Mobile Apps
- React Native apps using same API layer
- Shared component library via React Native Web
- Push notifications for important events

### Phase 4: Real-time Features
- WebSocket connections for live updates
- Server-sent events for booking notifications
- Real-time availability updates

### Phase 5: Advanced Analytics
- Custom report builder for hotel managers
- Predictive analytics for booking trends
- A/B testing framework for UI optimization

## Complete Example: Building a Concierge Earnings Dashboard

### Step 1: Design in Lovable
The product team creates a dashboard showing:
- Monthly earnings summary
- QR code performance metrics
- Recent bookings with commissions
- Leaderboard position

### Step 2: Export from Lovable
```
Package contents:
📁 concierge-dashboard/
  ├── 📸 dashboard-main.png
  ├── 📸 dashboard-loading.png
  ├── 📸 dashboard-empty.png
  ├── 📝 lovable-code.jsx (reference)
  └── 📝 interactions.md
```

### Step 3: Share with Claude Code
```markdown
"Please create a concierge earnings dashboard based on these designs.
The dashboard should update earnings every 15 minutes and show
real-time QR scan counts. Here are the screenshots and reference code..."
```

### Step 4: Claude Code Implementation Process

1. **Analyzes the design**
   - Identifies metric cards, charts, and tables
   - Notes the layout structure and responsive behavior

2. **Queries Storybook MCP**
   ```
   Q: "What components are available for metrics and charts?"
   A: MetricCard, LineChart, DataTable, DashboardLayout
   ```

3. **Queries OpenAPI MCP**
   ```
   Q: "How do I fetch concierge earnings and QR performance?"
   A: GET /api/v1/concierge/earnings
      GET /api/v1/qr/{code}/performance
   ```

4. **Generates production code**
   ```typescript
   // Generated by Claude Code
   export function ConciergeDashboard() {
     const { data: earnings } = useEarnings({ 
       refetchInterval: 15 * 60 * 1000 
     })
     const { data: qrPerformance } = useQRPerformance()
     
     return (
       <DashboardLayout>
         <MetricGrid>
           <MetricCard 
             title="Monthly Earnings"
             value={earnings?.total}
             format="currency"
           />
           {/* Rest of implementation */}
         </MetricGrid>
       </DashboardLayout>
     )
   }
   ```

### Step 5: Deploy
The generated code is reviewed, tested, and deployed as part of the unified portal.

## Conclusion

This architecture separation allows us to:
1. Keep complex business logic secure in Laravel
2. Build modern, fast consumer experiences
3. Iterate quickly on UI/UX with Lovable
4. Scale different parts of the system independently
5. Provide better experiences for each user type

By using the right tool for each job, we can deliver better products faster while maintaining system integrity and security.