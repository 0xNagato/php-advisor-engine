# Database Sync Venue Platform Skip Flag

## Overview

Added a `--skip-venue-platforms` flag to the `sync-db.sh` script and `venue-platforms:update-config` artisan command to skip updating venue platform configurations entirely during testing.

## Usage

### Shell Script
```bash
# Skip venue platform config updates during sync
./sync-db.sh --skip-venue-platforms

# Full sync skipping venue platform updates
./sync-db.sh --skip-venue-platforms

# Import only without venue platform config updates
./sync-db.sh --import-only --skip-venue-platforms

# Can be combined with other flags
./sync-db.sh --tunnel --skip-venue-platforms
```

### Artisan Command
```bash
# Skip venue platform config updates entirely
php artisan venue-platforms:update-config --skip-venue-platforms

# Normal behavior (updates all venue platform configs)
php artisan venue-platforms:update-config
```

## How It Works

### Simple Skip Behavior
When the `--skip-venue-platforms` flag is used:
- ✅ **Database sync proceeds normally**
- 🔒 **Venue platform configurations are completely skipped**
- 📊 **No venue platform configs are modified at all**

### Output Example
```
⚠️  SKIP-VENUE-PLATFORMS flag enabled - preserving all venue platform configurations
⚠️  SKIP-VENUE-PLATFORMS FLAG ENABLED
   Venue platform configurations will NOT be updated
   All existing platform configs will be preserved
```

## Use Cases

1. **Testing CoverManager Sync**: Keep all existing venue platform configs while testing the new sync functionality
2. **Development**: Preserve venue platform credentials during database syncs  
3. **Staging**: Test without modifying any venue platform configurations

## Safety Features

- Simple all-or-nothing approach - either all configs are preserved or all are updated
- Clear warning messages when venue platform updates are skipped
- Flag must be explicitly set - default behavior unchanged
- No complex detection logic - just skips the entire update process

This ensures that testing the new CoverManager availability sync won't accidentally modify any venue platform configurations.