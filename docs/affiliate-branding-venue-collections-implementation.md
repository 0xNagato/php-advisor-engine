# Affiliate Branding & Venue Collections Implementation Checklist

## Overview

Implement venue collections for affiliate branding, allowing concierges and VIP codes to curate specific venue lists with notes/reviews. This will be used by influencers and hotels to customize their booking experience for their guests/followers.

**Goal**: Enable concierges to create curated restaurant experiences with custom branding, allowing their guests to see personalized recommendations instead of all available venues.

**Key Relationship**: Each concierge has exactly ONE venue collection, and each VIP code has exactly ONE venue collection (1-to-1 relationships).

## Current Status

✅ **COMPLETED**: Basic affiliate branding implementation

- JSON-based branding configuration
- Brand name, description, colors, logo, redirect URL
- API integration with conditional responses
- Admin interface with organized form sections

## ✅ **PHASE 2 COMPLETED SUCCESSFULLY**

**All admin interface functionality is now working:**

- ✅ **EditConcierge**: Branding and venue collections load and save properly
- ✅ **VIP Code Branding**: Modal opens and manages branding data correctly
- ✅ **VIP Code Collections**: Modal opens and manages venue collections correctly
- ✅ **Shared Logic**: All functionality is reusable between concierge and VIP code levels
- ✅ **Permissions**: Proper toggles control access to branding and collections features

## Implementation Phases

### Phase 1: Database Schema & Models

**Status**: ✅ **COMPLETE**

#### 1.1 Create VenueCollection Model

- [x] Create `app/Models/VenueCollection.php`
- [x] Define fillable fields: `concierge_id`, `vip_code_id`, `name`, `description`, `is_active`
- [x] Add relationships: `concierge()`, `vipCode()`, `venues()`
- [x] Add factory: `database/factories/VenueCollectionFactory.php`

#### 1.2 Create VenueCollectionItem Model

- [x] Create `app/Models/VenueCollectionItem.php`
- [x] Define fillable fields: `venue_collection_id`, `venue_id`, `note`, `is_active`
- [x] Add relationships: `venueCollection()`, `venue()`
- [x] Add factory: `database/factories/VenueCollectionItemFactory.php`

#### 1.3 Update VipCode Model

- [x] Add `branding` to fillable array
- [x] Add `branding` cast to `AffiliateBrandingData::class`
- [x] Add relationship: `venueCollections()`

#### 1.4 Update Concierge Model

- [x] Add `can_manage_own_branding` to fillable array
- [x] Add `can_manage_own_collections` to fillable array
- [x] Add boolean casts for new fields
- [x] Add relationship: `venueCollections()`

#### 1.5 Create Migrations

- [x] Create `create_venue_collections_table` migration
- [x] Create `create_venue_collection_items_table` migration
- [x] Create `add_branding_to_vip_codes_table` migration
- [x] Create `add_self_management_to_concierges_table` migration
- [x] Add proper foreign key constraints and indexes

### Phase 2: Admin Interface Updates

**Status**: ✅ **COMPLETE**

#### 3.1 Enhanced EditConcierge Form

- [x] Add venue collection management section
- [x] Add self-management permission toggles
- [x] Integrate with existing branding configuration
- [x] Add venue selection with search/filter
- [x] Add note field for each venue
- [x] Implement conditional visibility based on toggles
- [x] Add full CRUD operations for collections and venues
- [x] Implement drag-and-drop reordering for collections and venues
- [x] Add proper form data handling and persistence
- [x] **NEW**: Extract shared venue collection logic into `ManagesVenueCollections` trait
- [x] **NEW**: Create shared `VenueCollectionManager` Livewire component for reuse
- [x] **NEW**: Fix data loading in EditConcierge using proper `mount()` method
- [x] **NEW**: Fix branding data loading in EditConcierge form

#### 3.2 VIP Code Resource Updates

- [x] Add branding override fields to VIP code forms
- [x] Add venue collection assignment
- [x] Maintain existing VIP code functionality
- [x] Add collection management interface
- [x] **NEW**: Update permissions to use VIP-specific toggles (`can_manage_vip_branding`, `can_manage_vip_collections`)
- [x] **NEW**: Refactor VIP collections to use shared `VenueCollectionManager` component
- [x] **NEW**: Fix Livewire components to properly implement `HasForms` interface
- [x] **NEW**: Fix `PropertyNotFoundException` errors in VIP branding and collections modals

#### 3.3 Concierge Self-Management Pages

- [x] Create `app/Livewire/Concierge/VipCodeBrandingManager.php`
- [x] Create `app/Livewire/Concierge/VipCodeCollectionsManager.php` (now uses shared component)
- [x] Create `app/Livewire/Concierge/CreateVenueCollectionForm.php`
- [x] Create `app/Livewire/Concierge/EditVenueCollectionForm.php`
- [x] Create `app/Livewire/Concierge/ManageVenueCollectionItems.php`
- [x] Add proper access controls and permissions
- [x] Implement venue management within collections
- [x] Add drag-and-drop reordering for venues
- [x] **NEW**: Create `app/Livewire/Shared/VenueCollectionManager.php` for reuse
- [x] **NEW**: Create `app/Traits/ManagesVenueCollections.php` for shared logic
- [x] **NEW**: Fix Filament form integration in Livewire components

### Phase 3: API Integration

**Status**: 🔄 **READY TO START**

#### 4.1 Update AvailabilityCalendarController

- [ ] Add venue collection filtering logic
- [ ] Return curated venue lists with collection notes
- [ ] Handle VIP code vs concierge collection inheritance
- [ ] Include collection metadata in venue responses
- [ ] Update API documentation

#### 4.2 Update ReservationService

- [ ] Add venue collection filtering logic
- [ ] Implement VIP code context detection
- [ ] Add fallback to default venue filtering when no collections exist
- [ ] Ensure backward compatibility
- [ ] Update venue filtering in all relevant services

#### 4.3 Conditional Collection Logic

- [ ] Only show venue collections when they exist for VIP code/concierge
- [ ] Fall back to default venue filtering when no collections exist
- [ ] Ensure backward compatibility with existing venue filtering logic

#### 4.4 VIP Code Context Detection

- [ ] Determine how to pass VIP code context to AvailabilityCalendar
- [ ] Consider session-based VIP code tracking
- [ ] Implement VIP code detection in API requests

### Phase 4: Concierge Self-Management

**Status**: 🔄 **PENDING**

#### 5.1 Permission System

- [ ] Add `can_manage_own_branding` permission logic
- [ ] Add `can_manage_own_collections` permission logic
- [ ] Update policies and access controls
- [ ] Add audit trail for self-managed changes

#### 5.2 Self-Management Interface

- [ ] Create concierge branding management form
- [ ] Create venue collection management interface
- [ ] Add VIP code branding override interface
- [ ] Implement proper validation and error handling

#### 5.3 Integration with Existing Systems

- [ ] Integrate with existing VIP code management
- [ ] Update concierge dashboard navigation
- [ ] Add proper notifications and feedback

### Phase 5: Testing & Quality Assurance

**Status**: 🔄 **PENDING**

#### 6.1 Unit Tests

- [ ] Test VenueCollection model relationships
- [ ] Test VenueCollectionItem model relationships
- [ ] Test data classes and serialization
- [ ] Test permission logic

#### 6.2 Feature Tests

- [ ] Test venue collection creation and management
- [ ] Test VIP code branding overrides
- [ ] Test API responses with collections
- [ ] Test venue filtering with collections
- [ ] Test concierge self-management

#### 6.3 Integration Tests

- [ ] Test full API workflow with collections
- [ ] Test admin interface functionality
- [ ] Test self-management workflows
- [ ] Test backward compatibility

### Phase 6: Documentation & Deployment

**Status**: 🔄 **PENDING**

#### 7.1 API Documentation

- [ ] Update OpenAPI specifications
- [ ] Document new API endpoints and responses
- [ ] Add examples for venue collections
- [ ] Update existing API documentation

#### 7.2 User Documentation

- [ ] Create admin user guide for venue collections
- [ ] Create concierge self-management guide
- [ ] Document permission system
- [ ] Add troubleshooting guide

#### 7.3 Deployment Preparation

- [ ] Create database migration rollback strategies
- [ ] Prepare feature flags if needed
- [ ] Plan gradual rollout strategy
- [ ] Prepare monitoring and alerting

## Technical Decisions & Questions

### Resolved Decisions

- ✅ Use JSON column for branding data
- ✅ Use `branding` field name (not `branding_override`)
- ✅ Remove `display_order` from venue collections
- ✅ Venue collections belong in AvailabilityCalendar, not VipSession
- ✅ VipSession only returns branding info, not venue data
- ✅ Only show collections when they exist, fallback to default venue filtering
- ✅ No changes needed to VenueController

### Pending Decisions

- [ ] Collection scope: Global vs Regional venues
- [ ] VIP code context detection method
- [ ] Note field naming convention
- [ ] Collection inheritance strategy

## API Response Examples

### Expected VipSession Response (Branding Only)

```json
{
  "concierge": {
    "id": 1,
    "name": "Hotel Concierge",
    "branding": {
      "brand_name": "Luxury Hotel",
      "description": "Exclusive booking experience",
      "logo_url": "https://...",
      "main_color": "#3B82F6",
      "secondary_color": "#1E40AF",
      "gradient_start": "#3B82F6",
      "gradient_end": "#1E40AF",
      "text_color": "#1F2937",
      "redirect_url": "https://example.com/thank-you"
    }
  }
}
```

### Expected AvailabilityCalendar Response (With Collections)

```json
{
  "data": {
    "venues": [
      {
        "id": 123,
        "name": "Restaurant A",
        "status": "active",
        "logo": "https://...",
        "collection_note": "Influencer favorite - great for Instagram",
        "collection_name": "Premium Restaurants",
        "schedules": [
          {
            "id": 102882,
            "schedule_template_id": 102882,
            "is_bookable": true,
            "prime_time": true,
            "time": {
              "value": "4:00 PM",
              "raw": "16:00:00"
            },
            "date": "2025-06-17",
            "fee": "$100",
            "has_low_inventory": false,
            "is_available": true,
            "remaining_tables": 0
          }
        ]
      }
    ],
    "timeslots": ["4:00 PM", "4:30 PM", "5:00 PM", "5:30 PM", "6:00 PM"]
  }
}
```

## File Structure

### New Files to Create

```
app/Models/VenueCollection.php
app/Models/VenueCollectionItem.php
app/Filament/Pages/Concierge/BrandingManager.php
app/Filament/Pages/Concierge/VenueCollectionManager.php
app/Filament/Pages/Concierge/VipCodeBrandingManager.php
database/factories/VenueCollectionFactory.php
database/factories/VenueCollectionItemFactory.php
database/migrations/xxxx_create_venue_collections_table.php
database/migrations/xxxx_create_venue_collection_items_table.php
database/migrations/xxxx_add_branding_to_vip_codes_table.php
database/migrations/xxxx_add_self_management_to_concierges_table.php
tests/Feature/VenueCollectionTest.php
tests/Feature/VipCodeBrandingTest.php
tests/Feature/ConciergeSelfManagementTest.php
```

### Files to Modify

```
app/Models/Concierge.php
app/Models/VipCode.php
app/Http/Controllers/Api/AvailabilityCalendarController.php
app/Services/ReservationService.php
app/Filament/Resources/ConciergeResource/Pages/EditConcierge.php
app/Filament/Resources/VipCodeResource.php
api-docs/Endpoints/AvailabilityCalendar.md
```

## Success Criteria

### Functional Requirements

- [ ] Concierges can create and manage venue collections with notes/reviews
- [ ] VIP codes can have custom branding and collections
- [ ] API returns curated venue lists with collection metadata
- [ ] Venue filtering respects collections when they exist
- [ ] Self-management works with proper permissions
- [ ] Backward compatibility maintained (default venue filtering when no collections)

### Technical Requirements

- [ ] All tests pass
- [ ] Code follows Laravel conventions
- [ ] Proper error handling and validation
- [ ] Database migrations are reversible
- [ ] API documentation is updated
- [ ] Performance is acceptable

### User Experience Requirements

- [ ] Admin interface is intuitive for managing collections
- [ ] Self-management is easy to use for concierges
- [ ] API responses are consistent with collection metadata
- [ ] Error messages are helpful
- [ ] Loading states are handled properly
- [ ] Guests see curated, branded booking experience

## Notes & Considerations

### Performance Considerations

- Eager load venue relationships to avoid N+1 queries
- Consider caching for frequently accessed collections
- Monitor API response times with full venue data

### Security Considerations

- Validate venue access permissions
- Ensure proper authorization for self-management
- Sanitize user input for notes and descriptions

### Migration Considerations

- Preserve existing venue filtering functionality
- Provide migration path for existing data
- Consider feature flags for gradual rollout

---

**Last Updated**: August 14, 2025
**Status**: Planning Phase
**Next Action**: Begin Phase 1 - Database Schema & Models
