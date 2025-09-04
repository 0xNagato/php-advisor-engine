# Plan for using Lovable + our stack

## 1) How we will use Lovable
Lovable is for fast mockups of our **unified customer portal** that will serve consumers, restaurant managers, concierges, hotel managers, and influencers. We will iterate on layout, copy, and interactions there until the screen feels right. Once approved, we take only the visual ideas and rebuild with our real components and APIs.

## 2) How we turn mockups into production

### The Workflow: From Lovable to Live Code

1. **Design in Lovable**: Create and iterate on screens until they look perfect
2. **Export from Lovable**: Once approved, export:
   - Screenshots of the final designs
   - The React code that Lovable generated (as reference)
   - Any specific interactions or animations noted
3. **Import to Claude Code**: Share the screenshots and reference code with Claude Code
4. **Claude Code generates production code**: Using our real components and APIs (see below)
5. **Deploy**: The generated code goes straight into our React app

### The Technical Magic (Made Simple)

**What is MCP?**
MCP (Model Context Protocol) is like giving Claude Code a direct phone line to our tools. Instead of copy-pasting code or explaining our setup, Claude Code can directly ask "what components do we have?" or "what API endpoints are available?"

**What is Storybook?**
Think of Storybook as our component catalog - like IKEA but for UI pieces. It shows all our buttons, cards, forms, etc. with examples of how to use them. When Claude Code needs a "primary button", it knows exactly which one to use and how to style it.

**What is OpenAPI?**
OpenAPI is like a menu for our backend. It lists all the API endpoints (like "get restaurant availability" or "create booking"), what data they expect, and what they return. Claude Code reads this menu to know exactly how to fetch and send data.

### How Claude Code Uses These Tools

When you share a Lovable design with Claude Code, here's what happens:

1. **Claude Code looks at your screenshot**: "I see a dashboard with booking metrics"
2. **Asks Storybook MCP**: "What components do we have for dashboards and metrics?"
3. **Gets back**: "Use MetricCard, LineChart, and DashboardLayout"
4. **Asks OpenAPI MCP**: "How do I get booking metrics data?"
5. **Gets back**: "Call GET /api/v1/venues/{id}/metrics with these parameters"
6. **Generates code**: Combines the right components with the right API calls
7. **Result**: Production-ready code that matches your design and works with our backend

### Why This Approach Works

- **Consistency**: Every screen uses the same components
- **Speed**: Claude Code doesn't guess - it knows exactly what to use
- **Accuracy**: The generated code already follows our patterns
- **Maintainable**: When we update a component in Storybook, all screens using it get updated

Our design team maintains the component library in Figma and keeps Storybook in sync, ensuring everything stays consistent across the entire app.

## 3) Why we are building a single React app for all user types
Our admin system (Filament) is great for internal operations but not ideal for customer-facing experiences. By building a single React app with role-based views, we can:
- Share components and design system across all user types
- Maintain one codebase instead of five
- Create faster, more responsive experiences  
- Cache data aggressively for better performance
- Deploy and update without affecting our core booking system

## 4) Why we are not wiring Lovable directly to our backend
Our platform handles complex operations that cannot be replicated by connecting Lovable to a generic backend like Supabase:

**Booking Logic & Rules:**
- Prime vs non-prime time calculations
- Dynamic pricing based on time slots
- Venue capacity management
- Auto-approval for small parties (≤7 guests)
- Daily booking caps by venue

**Platform Integrations:**
- CoverManager sync for real-time availability
- Restoo reservation management
- Automated SMS notifications via Twilio/SimpleTexting
- Platform-specific booking confirmations

**Financial Complexity:**
- Multi-party earnings splits (venue, concierge, platform)
- Commission calculations based on booking type
- QR code attribution for referral tracking
- VAT and tax calculations by region

**Data Privacy & Security:**
- PII masking and audited reveal actions
- Role-based access control
- Venue contact management
- GDPR compliance features

Connecting Lovable directly would mean duplicating all this logic, risking data inconsistencies, wrong calculations, and security vulnerabilities. It would also mean maintaining two backends with the same business rules.

## 5) Guardrails
- The React app talks to our backend via small, purpose-built API endpoints only
- PII is masked by default; "reveal" is a separate audited action
- One source of truth for all calculations and metrics
- Short-lived tokens for any dev/staging calls
- All business logic stays in our Laravel backend

## 6) What we need to set up

**API Endpoints by User Type:**

For Consumers:
- Search and browse venues
- Make bookings
- View booking history

For Restaurant Managers:
- Dashboard: occupancy trends, revenue metrics, booking pipeline
- Real-time availability management
- Customer insights and analytics

For Concierges/Hotel Managers/Influencers:
- QR performance: scans, conversions, earnings
- Attribution: bookings driven, commissions earned
- Leaderboards and achievements

**Technical setup:**
- Publish our OpenAPI spec for these new endpoints
- Stand up the OpenAPI MCP to serve those endpoints with examples
- Stand up the Storybook MCP so tools can discover our components
- Confirm the design tokens and component variants match Figma

## 7) Workflow going forward
Mock in Lovable → approve → generate real code via Claude Code using Storybook MCP + OpenAPI MCP → plug into our API → ship. This keeps the iteration speed high while protecting our business logic, data, and brand consistency.

## Key Benefits
- **Faster development**: Design to production in days, not weeks
- **Better performance**: Modern React apps with proper caching
- **Improved UX**: Purpose-built interfaces for each user type
- **Protected data**: All sensitive logic stays in our secure backend
- **Easy updates**: Deploy frontend changes without touching booking logic