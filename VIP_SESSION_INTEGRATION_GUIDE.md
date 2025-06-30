# VIP Session Token System - React Integration

## What This Is

We've implemented a new VIP session token system that allows users to access the booking platform using VIP codes provided by concierges. This replaces the current demo concierge authentication approach with dynamic VIP session-based authentication.

## Current React Implementation vs New System

### What React Currently Does

- **Hardcoded demo concierge token**: Using a fixed bearer token for a demo concierge account
- **Static authentication**: Same token used for all users and sessions
- **No VIP code integration**: VIP codes aren't connected to the authentication system
- **Limited personalization**: All users see the same concierge experience

### What React Will Do Now

- **Dynamic VIP session tokens**: Get session tokens from VIP codes via the VIP session API
- **VIP code-specific authentication**: Each VIP code creates a session tied to its specific concierge
- **Personalized experience**: Users see the actual concierge's information and branding
- **Graceful fallbacks**: Invalid VIP codes fall back to demo mode instead of failing

## How This Changes Your Current Authentication

### Current Authentication (Demo Concierge)

```javascript
// Current approach - hardcoded demo concierge token
const BEARER_TOKEN = '970|YGALEbH9Gnm0x8V2SrUSJJrNtx5tItovFlXruhJo77303af4';

export const api = axios.create({
  headers: {
    'Authorization': `Bearer ${BEARER_TOKEN}`, // Always the same demo concierge
  },
});
```

### New Authentication (VIP Session)

```javascript
// New approach - dynamic VIP session token
const getVipSessionToken = () => localStorage.getItem('vip_session_token');

export const api = axios.create({
  headers: {
    'Authorization': `Bearer ${getVipSessionToken()}`, // Dynamic based on VIP session
  },
});
```

## How This Changes Your Current Flow

### Before (Current Demo Concierge)

```
React app loads → Uses hardcoded demo concierge token → All API calls use same demo identity
```

### After (VIP Session System)

```
User visits ibiza.primaapp.com/MIAMI2024 → React extracts VIP code → Calls VIP session API → Gets session token → API calls use VIP-specific identity
```

## VIP Code URL Structure

### URL Format

```
ibiza.primaapp.com/VIP_CODE_HERE
```

### Examples

- `ibiza.primaapp.com/MIAMI2024` → VIP code: "MIAMI2024"
- `ibiza.primaapp.com/IBIZA2024` → VIP code: "IBIZA2024"
- `ibiza.primaapp.com/` → No VIP code (use demo token)
- `ibiza.primaapp.com/INVALID` → Invalid VIP code (fall back to demo mode)

## What Changes in Your Current Code

### 1. Replace Hardcoded Bearer Token

Instead of:

```javascript
// Current approach
const BEARER_TOKEN = import.meta.env.VITE_BEARER_TOKEN || '970|YGALEbH9Gnm0x8V2SrUSJJrNtx5tItovFlXruhJo77303af4';

export const api = axios.create({
  headers: {
    'Authorization': `Bearer ${BEARER_TOKEN}`,
  },
});
```

You'll do:

```javascript
// New approach
const getAuthToken = () => {
  const vipSession = localStorage.getItem('vip_session_token');
  // Fallback to demo token if no VIP session
  return vipSession || '970|YGALEbH9Gnm0x8V2SrUSJJrNtx5tItovFlXruhJo77303af4';
};

export const api = axios.create({
  headers: {
    'Authorization': `Bearer ${getAuthToken()}`,
  },
});
```

### 2. Extract VIP Code from URL Slug

Add logic to extract the VIP code from the first URL segment:

```javascript
// Extract VIP code from URL slug
const getVipCodeFromUrl = () => {
  const path = window.location.pathname;
  const segments = path.split('/').filter(segment => segment.length > 0);
  
  // First segment is the VIP code
  const vipCode = segments[0];
  
  // Validate VIP code format (4-12 characters, alphanumeric)
  if (vipCode && /^[A-Za-z0-9]{4,12}$/.test(vipCode)) {
    return vipCode.toUpperCase();
  }
  
  return null;
};
```

### 3. Add VIP Session Creation on App Load

Create VIP session when the app loads if VIP code is in URL:

```javascript
// Step 1: Create VIP session from URL slug
const initializeVipSession = async () => {
  const vipCode = getVipCodeFromUrl();
  
  if (!vipCode) {
    // No VIP code in URL, use demo token
    console.log('No VIP code found, using demo mode');
    return;
  }
  
  // Check if we already have a valid session for this VIP code
  const existingSession = JSON.parse(localStorage.getItem('vip_session_data') || '{}');
  if (existingSession.vip_code?.code === vipCode && new Date(existingSession.expires_at) > new Date()) {
    console.log('Using existing VIP session for:', vipCode);
    return;
  }
  
  try {
    const response = await fetch('https://staging-julio.primavip.co/api/vip/sessions', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ vip_code: vipCode })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Store the session token for API authentication
      localStorage.setItem('vip_session_token', data.data.session_token);
      localStorage.setItem('vip_session_data', JSON.stringify(data.data));
      
      // Update axios instance to use new token
      api.defaults.headers['Authorization'] = `Bearer ${data.data.session_token}`;
      
      if (data.data.is_demo) {
        console.log('Invalid VIP code, using demo mode:', data.data.demo_message);
      } else {
        console.log('VIP session created for:', data.data.vip_code.concierge.name);
      }
    }
  } catch (error) {
    console.error('Failed to create VIP session:', error);
    // Fall back to demo token on error
  }
};

// Call this when your app initializes
initializeVipSession();
```

### 4. Update Your Axios Interceptor

Modify your request interceptor to handle dynamic tokens:

```javascript
api.interceptors.request.use(
  (config) => {
    // Get current VIP session token or fallback to demo
    const currentToken = getAuthToken();
    config.headers['Authorization'] = `Bearer ${currentToken}`;

    if (process.env.NODE_ENV === 'development') {
      const sessionData = JSON.parse(localStorage.getItem('vip_session_data') || '{}');
      const vipCode = getVipCodeFromUrl();
      console.log(`🚀 API Request: ${config.method?.toUpperCase()} ${config.url}`);
      console.log('🔗 URL VIP Code:', vipCode || 'None');
      console.log('🔑 Using token for:', sessionData.is_demo ? 'Demo Mode' : `VIP Code: ${sessionData.vip_code?.code}`);
    }
    return config;
  },
  (error) => Promise.reject(error)
);
```

## Router Integration

### React Router Setup

If using React Router, you can set up routes to handle VIP codes:

```javascript
// App.js or your router setup
import { BrowserRouter, Routes, Route, useParams } from 'react-router-dom';

const VipCodeHandler = () => {
  const { vipCode } = useParams();
  
  useEffect(() => {
    if (vipCode) {
      // VIP code found in URL, create session
      initializeVipSession();
    }
  }, [vipCode]);
  
  return <YourMainComponent />;
};

const App = () => {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/:vipCode?" element={<VipCodeHandler />} />
      </Routes>
    </BrowserRouter>
  );
};
```

### Without React Router

If not using React Router, handle URL changes manually:

```javascript
// Listen for URL changes (if using single-page app)
useEffect(() => {
  const handleUrlChange = () => {
    initializeVipSession();
  };
  
  // Initialize on mount
  handleUrlChange();
  
  // Listen for navigation events
  window.addEventListener('popstate', handleUrlChange);
  
  return () => {
    window.removeEventListener('popstate', handleUrlChange);
  };
}, []);
```

## VIP Session API Integration

### 1. Session Creation

**When to call**: When app loads and VIP code is detected in URL

```javascript
const response = await fetch('https://staging-julio.primavip.co/api/vip/sessions', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ vip_code: extractedVipCode })
});
```

### 2. Session Response Handling

The API returns either a real VIP session or a demo session:

```javascript
const data = await response.json();

if (data.data.is_demo) {
  // Invalid VIP code - using demo mode
  console.log('Demo mode:', data.data.demo_message);
  // Still store the demo token for consistency
  localStorage.setItem('vip_session_token', data.data.session_token);
} else {
  // Valid VIP code - real concierge session
  console.log('VIP session for:', data.data.vip_code.concierge.name);
  localStorage.setItem('vip_session_token', data.data.session_token);
}
```

## Key Response Examples

### Valid VIP Code Response

```json
{
  "success": true,
  "data": {
    "session_token": "1234|AbCdEfGhIjKlMnOpQrStUvWxYz", // Real concierge token
    "expires_at": "2024-06-20T17:05:10.000Z",
    "is_demo": false,
    "vip_code": {
      "code": "MIAMI2024",
      "concierge": {
        "name": "John Doe",
        "hotel_name": "Luxury Resort Miami"
      }
    }
  }
}
```

### Invalid VIP Code Response (Demo Mode)

```json
{
  "success": true,
  "data": {
    "session_token": "970|YGALEbH9Gnm0x8V2SrUSJJrNtx5tItovFlXruhJo77303af4", // Your current demo token
    "expires_at": "2024-06-20T17:05:10.000Z",
    "is_demo": true,
    "demo_message": "You are viewing in demo mode. Some features may be limited."
  }
}
```

## Implementation Steps

### 1. **Add URL VIP Code Extraction**

- Extract VIP code from first URL segment (`ibiza.primaapp.com/MIAMI2024`)
- Validate VIP code format (4-12 alphanumeric characters)
- Handle cases where no VIP code is present

### 2. **Update App Initialization**

- Call VIP session API on app load if VIP code is detected
- Store session token and replace demo token
- Handle session caching to avoid unnecessary API calls

### 3. **Update Axios Configuration**

- Make bearer token dynamic instead of hardcoded
- Update request interceptor to use current session token
- Handle token refresh when sessions expire

### 4. **Handle Demo vs VIP Mode**

- Show different UI when `is_demo: true` vs real VIP session
- Display actual concierge information for VIP sessions
- Keep demo experience for invalid codes

### 5. **Add Router Integration**

- Set up routes to handle VIP codes as URL parameters
- Listen for URL changes if using single-page navigation
- Clean URL handling for both VIP and non-VIP visits

## URL Examples

### Valid VIP Code URLs

- `ibiza.primaapp.com/MIAMI2024` → Creates session for MIAMI2024
- `ibiza.primaapp.com/IBIZA2024` → Creates session for IBIZA2024
- `ibiza.primaapp.com/LONDON24` → Creates session for LONDON24

### Invalid/Demo URLs

- `ibiza.primaapp.com/INVALID` → Falls back to demo mode
- `ibiza.primaapp.com/123` → Falls back to demo mode (too short)
- `ibiza.primaapp.com/` → Uses demo token (no VIP code)

## Migration Benefits

- **Better Authentication**: Move from static demo token to dynamic VIP-specific tokens
- **Personalized Experience**: Users see their actual concierge's information
- **Better UX**: Invalid codes don't break the app, they fall back to demo
- **Clean URLs**: VIP codes in URL path instead of query parameters
- **Analytics**: Track how VIP codes are being used
- **Security**: Session tokens expire after 24 hours

## Backward Compatibility

- **Demo token fallback**: If no VIP session exists, fall back to your current demo token
- **No breaking changes**: Current functionality continues to work
- **Incremental migration**: Can implement VIP sessions while keeping demo as fallback
- **Environment flexibility**: Can still override with `VITE_BEARER_TOKEN` for testing
