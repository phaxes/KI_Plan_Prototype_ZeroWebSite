# PBI-03: User Authentifizierung — Complete Documentation

**Status**: ✅ Complete  
**Date**: 2026-05-22  
**Version**: 1.0  
**Last Updated**: 2026-05-22

---

## Overview

User Authentifizierung (PBI-03) implements a complete role-based access control system for the Zero-Cost Website. The system provides:

- **Firebase Authentication** (client-side) via Firebase Auth SDK
- **Admin Role-Based Access Control** (server-side) via Firestore `isAdmin` flag
- **Protected Admin Routes** that enforce admin-only access
- **Session Management** with PHP sessions and Firebase Auth verification
- **Cross-Environment Support** (localhost with file paths, Render.com with Base64-encoded credentials)

---

## Architecture

### Authentication Flow

```
┌─────────────────────────────────────────────────────────────┐
│ 1. USER LOGIN (Firebase Auth JS SDK)                        │
│    → Email/password verification via Firebase              │
│    → Firebase Auth token received                          │
│    → Sent to backend /auth/verify endpoint                │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. BACKEND VERIFICATION (PHP)                              │
│    → Verify Firebase token via JWT inspection               │
│    → Extract UID from token                                 │
│    → Create/Update user in Firestore                        │
│      (Preserves existing isAdmin flag!)                    │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. SESSION SETUP (Auth::setupSession)                      │
│    → Check if user is admin: Auth::isUserAdmin()           │
│    → Store in $_SESSION['isAdmin']                          │
│    → Regenerate session ID (security)                      │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. ROUTE PROTECTION (Middleware/Controllers)               │
│    → Check $_SESSION['isAdmin'] via Auth::isAdmin()        │
│    → Deny access if not admin (HTTP 403)                   │
│    → Allow access if admin                                 │
└─────────────────────────────────────────────────────────────┘
```

### Data Model

**Firestore Collection: `users`**

```
users/{uid}
  ├── email: string (required)
  ├── displayName: string
  ├── isAdmin: boolean (required, default: false)
  ├── createdAt: Timestamp
  └── updatedAt: Timestamp
```

**Key Fields:**
- `uid`: Firebase Auth UID (document ID)
- `email`: User email address
- `displayName`: User display name
- `isAdmin`: **Critical** — Determines if user has admin access
- `createdAt`: Account creation timestamp
- `updatedAt`: Last update timestamp

### Routes & Access Control

**Public Routes** (no authentication required):
- `GET /` — Home page
- `GET /news`, `GET /news/{id}` — News articles
- `GET /blog`, `GET /blog/{id}` — Blog posts
- `GET /products`, `GET /products/{id}` — Products
- `GET /login` — Login page
- `GET /register` — Registration page
- `GET /profile` — User profile (auth required, not admin-only)

**Protected Routes** (authentication required):
- `GET /profile` — User profile
- `GET /profile/newsletter` — Newsletter preferences

**Admin Routes** (authentication + `isAdmin: true` required):
- `GET /admin` — Admin dashboard
- `GET/POST /admin/news/*` — News management
- `GET/POST /admin/blog/*` — Blog management
- `GET/POST /admin/products/*` — Product management

### Protection Mechanisms

**1. Middleware-Based Protection** (DashboardController)
```php
\App\Middleware\AuthMiddleware::requireAdmin();
```

**2. Method-Based Protection** (NewsAdminController, BlogAdminController, etc.)
```php
protected function checkAdmin() {
    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        header('Location: /login');
        exit;
    }
}
```

**3. Session Verification** (Auth class)
```php
public static function isAdmin(): bool {
    return isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true;
}
```

---

## Critical Bugs & Fixes

### Bug #1: Non-Existent Firebase::firestore() Method

**Issue**: Code called `Firebase::firestore()` which doesn't exist in the Firebase class

**Impact**: 
- Session setup failed with exception
- Admin status never set to `true`
- All users redirected to profile instead of admin dashboard

**Root Cause**: The Firebase class only implements REST API via `apiCall()`, not gRPC via Kreait

**Fix**: Removed all `Firebase::firestore()` calls and use only `FirestoreRest` (REST API)

**Files Changed**:
- `src/Auth.php` — `getUserFromFirestore()`, `createOrUpdateUser()`

**Code**:
```php
// BEFORE (broken):
$firestore = Firebase::firestore();  // ❌ Method doesn't exist!
if ($firestore !== null) { ... }

// AFTER (fixed):
// Use FirestoreRest directly - it works everywhere
$restClient = FirestoreRest::getInstance($projectId, $serviceAccountJson);
$restClient->getDocument('users', $uid);
```

---

### Bug #2: isAdmin Flag Lost on User Update

**Issue**: When user logged in again, the `isAdmin` flag was overwritten and lost

**Impact**:
- Admin users lost their admin status on re-login
- Had to manually reset isAdmin in Firestore after each login
- Admin panel became inaccessible after logout/login

**Root Cause**: `createOrUpdateUser()` only added `isAdmin: false` for NEW users, but didn't preserve existing `isAdmin` value for updates

**Fix**: Always preserve existing `isAdmin` value when updating user document

**Files Changed**:
- `src/Auth.php` — `createOrUpdateUser()`

**Code**:
```php
// BEFORE (broken):
if (!$existingUser) {
    $userData['isAdmin'] = false;  // New user
} 
// Old user? Don't touch isAdmin!

// AFTER (fixed):
if (!$existingUser) {
    $userData['isAdmin'] = false;
} else {
    $userData['isAdmin'] = $existingUser['isAdmin'] ?? false;  // Preserve!
}
```

---

### Bug #3: Base64-Encoded Credentials Not Decoded on Render.com

**Issue**: On Render.com, `FIREBASE_SERVICE_ACCOUNT_JSON` is stored as Base64-encoded string (not a file path), but the code didn't decode it

**Impact**:
- FirestoreRest couldn't parse the JSON (invalid format)
- Admin check failed on production
- Users couldn't login on Render.com

**Root Cause**: Code only checked `file_exists()` but didn't handle Base64 encoding

**Fix**: Added Base64 decoding when file path doesn't exist

**Files Changed**:
- `src/Auth.php` — `getUserFromFirestore()`, `createOrUpdateUser()`
- `cli-admin-setup.php` — CLI script

**Code**:
```php
// BEFORE (broken):
if (file_exists($serviceAccountJson)) {
    $serviceAccountJson = file_get_contents($serviceAccountJson);
}
// On Render.com: file doesn't exist, so we pass Base64 to FirestoreRest
// ❌ FirestoreRest fails: "Invalid service account configuration"

// AFTER (fixed):
if (file_exists($serviceAccountJson)) {
    $serviceAccountJson = file_get_contents($serviceAccountJson);
} else {
    $decoded = base64_decode($serviceAccountJson, true);
    if ($decoded !== false) {
        $serviceAccountJson = $decoded;  // Now it's valid JSON!
    }
}
```

---

## Implementation Details

### Firebase REST API Client

**File**: `src/FirestoreRest.php`

```php
class FirestoreRest {
    // Singleton pattern
    public static function getInstance($projectId, $serviceAccountJson): self
    
    // User operations
    public function getDocument($collection, $documentId): ?array
    public function setDocument($collection, $documentId, $data): bool
    public function getCollection($collection): array
    
    // Internal token management
    private function refreshAccessToken($serviceAccountJson)
}
```

**Key Methods**:
- `getDocument()` — Fetch single user from Firestore
- `setDocument()` — Create/update user in Firestore
- `getCollection()` — List all users (used for admin setup)

### Auth Class

**File**: `src/Auth.php`

```php
class Auth {
    // User retrieval
    public static function getUserFromFirestore($uid): ?array
    public static function createOrUpdateUser($uid, $email, $displayName): bool
    
    // Admin checks
    public static function isUserAdmin($uid, $email = null): bool
    public static function isAdmin(): bool
    
    // Session management
    public static function setupSession($uid, $email, $displayName): bool
    
    // Authentication checks
    public static function isLoggedIn(): bool
    public static function verifyFirebaseToken($token): ?array
}
```

**Key Features**:
- `getUserFromFirestore()` — Fetch user and check `isAdmin` field
- `isUserAdmin()` — Check if user has admin rights (with fallback for hardcoded admins)
- `setupSession()` — Initialize PHP session with admin status
- `isAdmin()` — Runtime check for current user's admin status

### AuthController

**File**: `src/Controllers/AuthController.php`

```php
class AuthController {
    public function verify()        // POST /auth/verify
    public function logout()        // POST /auth/logout
    public function adminStatus()   // GET /auth/admin-status
}
```

**Endpoints**:
- `POST /auth/verify` — Verify Firebase token and setup session
- `POST /auth/logout` — Destroy session
- `GET /auth/admin-status` — Check current user's admin status

---

## Testing & Verification

### Local Testing

**Run Comprehensive Tests**:
```bash
php debug-pbi-03-auth.php
```

This script tests:
1. ✅ Firebase configuration
2. ✅ Firestore REST API connection
3. ✅ Users collection integrity
4. ✅ test@example.com admin status
5. ✅ `Auth::isUserAdmin()` function
6. ✅ Session-based admin check
7. ✅ `Auth::isAdmin()` runtime check
8. ✅ isAdmin flag preservation during update

**Expected Output**:
```
🎉 ALL TESTS PASSED!
✨ PBI-03 USER AUTHENTIFIZIERUNG is COMPLETE and READY FOR PRODUCTION

You can now:
  ✓ Login as admin users via Firebase Auth
  ✓ Access protected admin routes (/admin)
  ✓ Manage users with role-based access control
  ✓ Deploy to Render.com with confidence
```

### Admin Setup (if needed)

**Manually Set isAdmin for a User**:
```bash
# Update all users and set test@example.com as admin
php cli-admin-setup.php
```

**Firestore Console Method**:
1. Open Firebase Console → Firestore
2. Navigate to `users` collection
3. Find user document by UID
4. Edit the `isAdmin` field to `true`
5. User must re-login for changes to take effect

### Manual Testing in Browser

**Local**:
1. Open http://localhost:8000
2. Click "Login"
3. Enter test@example.com / password
4. Should redirect to `/admin` (if admin)
5. Should redirect to `/profile` (if regular user)
6. Try accessing `/admin` directly:
   - Admin user: ✅ Sees dashboard
   - Regular user: ❌ Gets "Access Denied"

**Production (Render.com)**:
1. Open https://zero-cost-website.onrender.com
2. Repeat steps above
3. Base64 decoding should work automatically

---

## Deployment

### Local Development

```bash
# Install dependencies
composer install
npm install

# Run Tailwind watcher
npm run dev

# Start PHP dev server
php -S localhost:8000

# Test authentication
php debug-pbi-03-auth.php
```

### Production (Render.com)

**Automatic Deployment**:
```bash
# Push to main branch
git push origin main

# Render.com automatically:
# 1. Pulls latest code
# 2. Runs build (composer install, npm run build)
# 3. Starts PHP server
# 4. Base64 credentials automatically decoded
```

**Environment Variables on Render.com**:
- `FIREBASE_PROJECT_ID` — Firebase project ID
- `FIREBASE_SERVICE_ACCOUNT_JSON` — Base64-encoded service account JSON
- `FIREBASE_API_KEY` — Firebase API key
- `FIREBASE_AUTH_DOMAIN` — Firebase auth domain
- etc.

**Critical**: Ensure `FIREBASE_SERVICE_ACCOUNT_JSON` is set as environment variable (not in .env file)

---

## Security Considerations

### ✅ What We Protect

1. **Session Fixation** — Session ID regenerated on login
2. **Unauthorized Access** — Admin routes checked on every request
3. **Token Verification** — Firebase tokens verified on backend
4. **Credential Exposure** — Service account credentials never in code
5. **Role Escalation** — Client can't modify `isAdmin` flag directly

### ⚠️ Limitations

1. **Client-Side Storage** — Admin status stored in `sessionStorage` (only for UI)
2. **Session Cookies** — Default PHP session security settings
3. **HTTPS** — Must use HTTPS in production (Render.com handles this)

### 🔒 Best Practices

1. Never expose `FIREBASE_SERVICE_ACCOUNT_JSON` in code
2. Always verify admin status server-side
3. Regenerate session ID after login
4. Check admin status before sensitive operations
5. Log admin actions for audit trail (future)

---

## Troubleshooting

### Problem: "Access Denied" on /admin

**Solutions**:
1. Check if user is actually admin in Firestore: `isAdmin: true`
2. Re-login (session cache issue)
3. Run verification script: `php debug-pbi-03-auth.php`
4. Check logs: `tail /tmp/dev-server.log`

### Problem: Login works but session lost

**Solutions**:
1. Check if cookies are enabled
2. Check browser console for errors
3. Verify Firebase config in `public/js/firebase-init.js`
4. Ensure `/auth/verify` endpoint is called

### Problem: Base64 decoding errors on Render.com

**Solutions**:
1. Verify `FIREBASE_SERVICE_ACCOUNT_JSON` is Base64-encoded
2. Test locally: `echo $FIREBASE_SERVICE_ACCOUNT_JSON | base64 -d | jq`
3. If it's a file path locally, use env var on Render.com instead
4. Check Render.com logs: Look for "Invalid service account configuration"

### Debug Script Output

**If test fails**, check specific error:

```bash
# Check which test failed
php debug-pbi-03-auth.php 2>&1 | grep "❌"

# View error logs
tail -f /tmp/dev-server.log | grep "Admin check\|Create/update user\|Get user"
```

---

## File Structure

```
project/
├── src/
│   ├── Auth.php                    # Main authentication class
│   ├── Firebase.php                # Firebase REST API wrapper
│   ├── FirestoreRest.php           # Firestore REST client (singleton)
│   ├── Controllers/
│   │   ├── AuthController.php      # Login/logout endpoints
│   │   ├── ProfileController.php   # User profile
│   │   └── Admin/
│   │       ├── DashboardController.php
│   │       ├── NewsAdminController.php
│   │       ├── BlogAdminController.php
│   │       └── ProductAdminController.php
│   └── Middleware/
│       └── AuthMiddleware.php      # Admin route protection
│
├── public/
│   └── js/
│       ├── firebase-init.js        # Firebase SDK config
│       ├── auth.js                 # Client-side auth logic
│       └── admin.js                # Admin panel logic
│
├── templates/
│   ├── admin/
│   │   ├── dashboard.phtml
│   │   ├── news/
│   │   ├── blog/
│   │   └── products/
│   ├── auth/
│   │   ├── login.phtml
│   │   └── register.phtml
│   └── profile/
│       └── index.phtml
│
└── docs/
    ├── PBI-03_USER_AUTHENTIFIZIERUNG.md (this file)
    ├── debug-pbi-03-auth.php            # Verification script
    ├── cli-admin-setup.php              # Admin setup CLI
    └── CREDENTIALS_SETUP.md             # Firebase setup guide
```

---

## Summary

PBI-03 **User Authentifizierung** is now fully implemented and production-ready:

✅ Firebase Authentication working  
✅ Admin role-based access control  
✅ Cross-environment support (localhost + Render.com)  
✅ All critical bugs fixed  
✅ Comprehensive testing scripts included  
✅ Secure session management  
✅ Protected admin routes  

**Next Steps**:
- Continue with PBI-04 (Stripe Checkout)
- Monitor admin logins in production
- Implement audit logging for admin actions (future)
