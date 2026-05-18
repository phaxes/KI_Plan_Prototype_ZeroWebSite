# Milestone M3: Auth & User - Dokumentation

**Zeitraum**: Tag 5-6  
**Status**: Abgeschlossen  
**Änderungsdatum**: 2026-05-18

## Übersicht

M3 implementiert **Firebase Authentication** mit kompletter Auth-Flow (Login/Register/Logout), **Server-Side Token-Verifikation** und **Firestore Security Rules** (Dirty Dozen Test Cases).

## Implementierte Komponenten

### 1. Firebase Auth JS SDK (public/js/auth.js)

Vollständiges Auth-Modul mit:

**Login Flow**:
```javascript
auth.signInWithEmailAndPassword(email, password)
  → Firebase Auth validates credentials
  → User object returned
  → Sync with server via /auth/verify
```

**Register Flow**:
```javascript
auth.createUserWithEmailAndPassword(email, password)
  → Create Firebase Auth user
  → Create user profile (Firestore)
  → Sync session with server
```

**Session Sync**:
```javascript
user.getIdToken() → POST /auth/verify
Backend verifies token + sets PHP session
```

**Event Listeners**:
- `#login-form` submit
- `#register-form` submit
- `auth.onAuthStateChanged()` (firebase-init.js)

### 2. Server-Side Auth (src/Auth.php)

Zentrale Auth-Klasse mit statischen Methoden:

**Token Verification**:
```php
Auth::verifyToken($idToken)
  → Firebase Admin SDK verifyIdToken()
  → Returns: uid, email, emailVerified, displayName
```

**User Management**:
```php
Auth::createOrUpdateUser($uid, $email, $displayName)
Auth::getUserFromFirestore($uid)
Auth::isUserAdmin($uid)
Auth::setupSession($uid, $email, $displayName)
```

**Session Helpers**:
```php
Auth::isLoggedIn()         // Check if $_SESSION['userId'] exists
Auth::isAdmin()            // Check if $_SESSION['isAdmin'] == true
Auth::getCurrentUserId()   // Get user ID from session
Auth::logout()             // Destroy session
```

### 3. Middleware (src/Middleware/AuthMiddleware.php)

Three middleware functions for route protection:

```php
AuthMiddleware::require()         // Redirect to /login if not authenticated
AuthMiddleware::requireAdmin()    // Redirect to /login if not admin
AuthMiddleware::requireGuest()    // Redirect to /profile if logged in
```

**Usage**:
```php
public function profile() {
    AuthMiddleware::require();  // ← Must be logged in
    // ... rest of code
}

public function admin() {
    AuthMiddleware::requireAdmin();  // ← Must be admin
}

public function login() {
    AuthMiddleware::requireGuest();  // ← Must NOT be logged in
}
```

### 4. Updated AuthController

**Login Page** (`/login`):
- Email + Password form
- Error messages (invalid credentials, etc.)
- Link to registration
- Test account info

**Register Page** (`/register`):
- Name + Email + Password fields
- Password confirmation
- Client-side validation (matching, length)
- Creates Firestore user doc

**Verify Endpoint** (`POST /auth/verify`):
- Receives idToken from client
- Verifies with Firebase Admin SDK
- Creates/updates user in Firestore
- Sets PHP session variables
- Returns JSON success/error

**Logout** (`GET /logout`):
- Destroys PHP session
- Redirects to home

### 5. Protected Profile Page

**ProfileController** now requires authentication:
```php
public function index() {
    AuthMiddleware::require();  // ← Enforced
    // Shows user's profile data
}
```

### 6. Admin Protection

All Admin Controllers updated:
```php
protected function checkAdmin() {
    AuthMiddleware::requireAdmin();
}
```

Now called at start of each admin method.

### 7. Firestore Security Rules (firestore.rules)

Complete rule set covering all collections:

**Posts** (News/Blog):
- ✅ Read published posts (anyone)
- ✅ Create posts (auth users, own author)
- ✅ Update/Delete own posts (or admin)
- ❌ Server timestamp required
- ❌ Title max 500 chars
- ❌ Shadow-field injection blocked

**Products**:
- ✅ Read active products (anyone)
- ❌ Create/Update/Delete (admin only)

**Users**:
- ✅ Read own profile (owner)
- ✅ Read all (admin)
- ❌ Self-elevate to admin blocked
- ❌ Email field immutable
- ❌ Create without isAdmin blocked

**Subscribers**:
- ✅ Subscribe (anyone)
- ❌ Read (admin only)
- ❌ Update/Delete (admin only)

**Orders**:
- ✅ Read own orders (owner)
- ✅ Create orders (auth users)
- ❌ Delete orders (audit trail)
- ✅ Update status (admin only)

## Auth Flow Diagram

```
1. User clicks "Anmelden"
   ↓
2. Browser → /login (LoginController)
   ↓
3. User fills form: email + password
   ↓
4. Form submit → JavaScript (auth.js)
   ↓
5. auth.signInWithEmailAndPassword()
   ↓
6. Firebase Auth validates
   ↓
7. If valid: user.getIdToken()
   ↓
8. POST /auth/verify (JSON)
   Body: {idToken, uid, email, displayName}
   ↓
9. PHP AuthController::verify()
   ↓
10. Auth::verifyToken($idToken)
    → Firebase Admin SDK
    ↓
11. If token valid: Auth::setupSession()
    → $_SESSION['userId'] = uid
    → $_SESSION['email'] = email
    → $_SESSION['isAdmin'] = false (from Firestore check)
    ↓
12. Return JSON: {success: true}
    ↓
13. JS: window.location.href = '/profile'
    ↓
14. Browser → GET /profile
    ↓
15. ProfileController::index()
    → AuthMiddleware::require() ✓ (session exists)
    → Show profile page
```

## Session Variables

After login, PHP session contains:

```php
$_SESSION = [
    'userId' => 'firebase-uid-xyz',
    'email' => 'user@example.com',
    'displayName' => 'Max Mustermann',
    'isAdmin' => false,  // From Firestore users.isAdmin
    'loginTime' => 1234567890
]
```

## Firestore Collections After Auth

**users** collection created automatically:

```json
{
  "users": {
    "uid_123": {
      "email": "user@example.com",
      "displayName": "Max Mustermann",
      "isAdmin": false,
      "createdAt": Timestamp,
      "updatedAt": Timestamp,
      "address": {}
    }
  }
}
```

**Admin Flag**: Must be set manually in Firebase Console or via Admin SDK (never via UI).

## Security Rules Testing (Dirty Dozen)

Alle 12 Test-Cases sind in firestore.rules abgedeckt:

| # | Test | Rule | Status |
|---|------|------|--------|
| 1 | Unauthenticated create post | auth required | ✅ BLOCKED |
| 2 | Create with wrong authorId | must match uid | ✅ BLOCKED |
| 3 | Client-provided createdAt | must be request.time | ✅ BLOCKED |
| 4 | Non-owner post update | owner or admin | ✅ BLOCKED |
| 5 | Non-admin product create | admin only | ✅ BLOCKED |
| 6 | Self-elevate to admin | no isAdmin field | ✅ BLOCKED |
| 7 | Read other user profile | owner only | ✅ BLOCKED |
| 8 | Read all subscribers | admin only | ✅ BLOCKED |
| 9 | 1MB title injection | title.size() <= 500 | ✅ BLOCKED |
| 10 | Shadow field injection | affectedKeys().hasOnly() | ✅ BLOCKED |
| 11 | Self-add to admins | write: false | ✅ BLOCKED |
| 12 | Modify email field | immutable | ✅ BLOCKED |

## Known Issues / TODOs

- [ ] Password reset flow (not yet implemented)
- [ ] Email verification (Firebase native, not UI)
- [ ] Session timeout (could add TTL check)
- [ ] Refresh token handling (Firebase handles automatically)
- [ ] Profile editing (forms exist but not fully wired)
- [ ] Rate limiting on auth endpoints

## Testing Checklist

- [ ] Register new account → Check Firestore users collection
- [ ] Login with email/password → Check session
- [ ] Visit /profile (logged in) → Shows user data
- [ ] Visit /profile (logged out) → Redirects to /login
- [ ] Visit /admin (not admin) → 403 Forbidden
- [ ] Visit /admin (admin) → Shows dashboard
- [ ] Firestore Rules: Try to create post with wrong authorId → BLOCKED
- [ ] Firestore Rules: Try to read private user profile → BLOCKED

---

**Nächster Step (M4)**: Shop finalization, Stripe integration, Order management
