# VIP Session Token System Integration Guide

## Overview

The VIP session token system allows users to access the booking platform using VIP codes provided by concierges. This system provides 24-hour session tokens with automatic fallback to demo mode for invalid codes.

## Key Features

- **24-Hour Sessions**: Tokens expire after 24 hours for security
- **Demo Mode Fallback**: Invalid VIP codes automatically fall back to demo mode
- **Analytics Tracking**: All session events are tracked separately from bookings
- **Secure Token Management**: SHA-256 hashed tokens with automatic cleanup

## API Endpoints

### 1. Create VIP Session

**Endpoint**: `POST /api/vip/sessions`
**Authentication**: None required (public endpoint)

**Request Body**:

```json
{
  "vip_code": "MIAMI2024"
}
```

**Response (Valid Code)**:

```json
{
  "success": true,
  "data": {
    "session_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0",
    "expires_at": "2024-06-20T17:05:10.000Z",
    "is_demo": false,
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

**Response (Invalid Code - Demo Mode)**:

```json
{
  "success": true,
  "data": {
    "session_token": "demo_1718898310",
    "expires_at": "2024-06-20T17:05:10.000Z",
    "is_demo": true,
    "demo_message": "You are viewing in demo mode. Some features may be limited."
  }
}
```

### 2. Validate VIP Session

**Endpoint**: `POST /api/vip/sessions/validate`
**Authentication**: None required (public endpoint)

**Request Body**:

```json
{
  "session_token": "your_session_token_here"
}
```

**Response (Valid Token)**:

```json
{
  "success": true,
  "data": {
    "valid": true,
    "is_demo": false,
    "session": {
      "id": 789,
      "expires_at": "2024-06-20T17:05:10.000Z"
    },
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

**Response (Invalid/Expired Token)**:

```json
{
  "success": false,
  "data": {
    "valid": false,
    "message": "Invalid or expired session token"
  }
}
```

## React Implementation

### 1. VIP Session Hook

Create a custom hook to manage VIP sessions:

```typescript
// hooks/useVipSession.ts
import { useState, useEffect, useCallback } from 'react';

interface VipSessionData {
  sessionToken: string;
  expiresAt: string;
  isDemo: boolean;
  vipCode?: {
    id: number;
    code: string;
    concierge: {
      id: number;
      name: string;
      hotel_name: string;
    };
  };
  demoMessage?: string;
}

interface UseVipSessionReturn {
  session: VipSessionData | null;
  isLoading: boolean;
  error: string | null;
  createSession: (vipCode: string) => Promise<void>;
  validateSession: (token: string) => Promise<boolean>;
  clearSession: () => void;
  isSessionValid: boolean;
}

export const useVipSession = (): UseVipSessionReturn => {
  const [session, setSession] = useState<VipSessionData | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Load session from localStorage on mount
  useEffect(() => {
    const savedSession = localStorage.getItem('vip_session');
    if (savedSession) {
      try {
        const parsedSession = JSON.parse(savedSession);
        // Check if session is expired
        if (new Date(parsedSession.expiresAt) > new Date()) {
          setSession(parsedSession);
        } else {
          localStorage.removeItem('vip_session');
        }
      } catch (error) {
        localStorage.removeItem('vip_session');
      }
    }
  }, []);

  const createSession = useCallback(async (vipCode: string) => {
    setIsLoading(true);
    setError(null);

    try {
      const response = await fetch('/api/vip/sessions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ vip_code: vipCode }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        const sessionData: VipSessionData = {
          sessionToken: data.data.session_token,
          expiresAt: data.data.expires_at,
          isDemo: data.data.is_demo,
          vipCode: data.data.vip_code,
          demoMessage: data.data.demo_message,
        };

        setSession(sessionData);
        localStorage.setItem('vip_session', JSON.stringify(sessionData));
      } else {
        throw new Error(data.message || 'Failed to create session');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unknown error occurred');
    } finally {
      setIsLoading(false);
    }
  }, []);

  const validateSession = useCallback(async (token: string): Promise<boolean> => {
    try {
      const response = await fetch('/api/vip/sessions/validate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ session_token: token }),
      });

      const data = await response.json();
      return response.ok && data.success && data.data.valid;
    } catch {
      return false;
    }
  }, []);

  const clearSession = useCallback(() => {
    setSession(null);
    localStorage.removeItem('vip_session');
  }, []);

  const isSessionValid = session ? new Date(session.expiresAt) > new Date() : false;

  return {
    session,
    isLoading,
    error,
    createSession,
    validateSession,
    clearSession,
    isSessionValid,
  };
};
```

### 2. VIP Code Input Component

```typescript
// components/VipCodeInput.tsx
import React, { useState } from 'react';
import { useVipSession } from '../hooks/useVipSession';

interface VipCodeInputProps {
  onSessionCreated?: (session: any) => void;
  placeholder?: string;
}

export const VipCodeInput: React.FC<VipCodeInputProps> = ({
  onSessionCreated,
  placeholder = "Enter VIP Code (e.g., MIAMI2024)"
}) => {
  const [vipCode, setVipCode] = useState('');
  const { createSession, isLoading, error } = useVipSession();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!vipCode.trim()) return;

    await createSession(vipCode.trim().toUpperCase());
    if (onSessionCreated) {
      onSessionCreated(session);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <input
          type="text"
          value={vipCode}
          onChange={(e) => setVipCode(e.target.value)}
          placeholder={placeholder}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          disabled={isLoading}
          minLength={4}
          maxLength={12}
        />
      </div>

      {error && (
        <div className="text-red-600 text-sm">
          {error}
        </div>
      )}

      <button
        type="submit"
        disabled={isLoading || !vipCode.trim()}
        className="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {isLoading ? 'Creating Session...' : 'Access VIP Booking'}
      </button>
    </form>
  );
};
```

### 3. Session Status Component

```typescript
// components/SessionStatus.tsx
import React from 'react';
import { useVipSession } from '../hooks/useVipSession';

export const SessionStatus: React.FC = () => {
  const { session, clearSession, isSessionValid } = useVipSession();

  if (!session) return null;

  const timeRemaining = session ? new Date(session.expiresAt).getTime() - new Date().getTime() : 0;
  const hoursRemaining = Math.floor(timeRemaining / (1000 * 60 * 60));
  const minutesRemaining = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));

  return (
    <div className={`p-4 rounded-lg ${session.isDemo ? 'bg-yellow-50 border-yellow-200' : 'bg-green-50 border-green-200'} border`}>
      {session.isDemo ? (
        <div>
          <h3 className="font-semibold text-yellow-800">Demo Mode Active</h3>
          <p className="text-yellow-700 text-sm">{session.demoMessage}</p>
        </div>
      ) : (
        <div>
          <h3 className="font-semibold text-green-800">VIP Session Active</h3>
          <p className="text-green-700 text-sm">
            Code: {session.vipCode?.code} | Concierge: {session.vipCode?.concierge.name}
          </p>
          {session.vipCode?.concierge.hotel_name && (
            <p className="text-green-600 text-xs">{session.vipCode.concierge.hotel_name}</p>
          )}
        </div>
      )}

      <div className="mt-2 flex justify-between items-center">
        <span className="text-sm text-gray-600">
          {isSessionValid ? `${hoursRemaining}h ${minutesRemaining}m remaining` : 'Session expired'}
        </span>
        <button
          onClick={clearSession}
          className="text-sm text-red-600 hover:text-red-800"
        >
          Clear Session
        </button>
      </div>
    </div>
  );
};
```

### 4. URL Parameter Handling

Handle VIP codes from URL parameters (e.g., `yourapp.com?vip=MIAMI2024`):

```typescript
// hooks/useUrlVipCode.ts
import { useEffect } from 'react';
import { useVipSession } from './useVipSession';

export const useUrlVipCode = () => {
  const { createSession, session } = useVipSession();

  useEffect(() => {
    // Only process URL parameter if no active session
    if (session) return;

    const urlParams = new URLSearchParams(window.location.search);
    const vipCode = urlParams.get('vip');

    if (vipCode) {
      createSession(vipCode);
      // Clean up URL parameter
      const newUrl = new URL(window.location.href);
      newUrl.searchParams.delete('vip');
      window.history.replaceState({}, '', newUrl.toString());
    }
  }, [createSession, session]);
};
```

### 5. Integration in Main App

```typescript
// App.tsx
import React from 'react';
import { VipCodeInput } from './components/VipCodeInput';
import { SessionStatus } from './components/SessionStatus';
import { useVipSession } from './hooks/useVipSession';
import { useUrlVipCode } from './hooks/useUrlVipCode';

export const App: React.FC = () => {
  const { session, isSessionValid } = useVipSession();
  
  // Handle VIP codes from URL parameters
  useUrlVipCode();

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-3xl font-bold text-center mb-8">
          PRIMA VIP Booking
        </h1>

        {session && isSessionValid ? (
          <div className="space-y-6">
            <SessionStatus />
            {/* Your booking interface components here */}
            <BookingInterface isDemoMode={session.isDemo} />
          </div>
        ) : (
          <div className="max-w-md mx-auto">
            <div className="bg-white p-6 rounded-lg shadow-md">
              <h2 className="text-xl font-semibold mb-4">Enter VIP Code</h2>
              <VipCodeInput />
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
```

## Implementation Checklist

- [ ] Install and configure the VIP session hook
- [ ] Create VIP code input component
- [ ] Add session status display
- [ ] Handle URL parameter VIP codes
- [ ] Implement localStorage persistence
- [ ] Add session validation on app load
- [ ] Handle demo mode UI differences
- [ ] Add session expiration warnings
- [ ] Implement automatic session refresh
- [ ] Add error handling for network issues

## Best Practices

1. **Session Persistence**: Store sessions in localStorage for return visits
2. **Expiration Handling**: Check session validity on app load and periodically
3. **Demo Mode UX**: Clearly indicate demo mode with appropriate messaging
4. **Error Handling**: Gracefully handle network errors and invalid codes
5. **Security**: Never store raw session tokens in logs or analytics
6. **Performance**: Cache session validation results appropriately

## URL Structure Examples

- `yourapp.com?vip=MIAMI2024` - Direct VIP code access
- `yourapp.com` - Manual VIP code entry
- `yourapp.com?vip=INVALID` - Falls back to demo mode automatically

## Testing

Test the following scenarios:

- Valid VIP code entry
- Invalid VIP code (should fall back to demo)
- Session expiration handling
- URL parameter processing
- localStorage persistence
- Network error handling
- Session validation on app reload
