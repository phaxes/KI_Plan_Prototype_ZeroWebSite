# Milestone M3: System & Architektur Leitfaden

## M3 Authentication Architecture

M3 erweitert das System um eine **Two-Tier Authentication**:
1. **Client-Side**: Firebase Auth JS SDK (Email/Password)
2. **Server-Side**: Token Verification (PHP Session)

```
┌──────────────────────────────────┐
│      Browser / JavaScript         │
│                                  │
│  1. Firebase Auth JS SDK         │
│     → Login/Register Forms       │
│     → auth.signIn/createUser()   │
│  2. Gets ID Token                │
│  3. Sends to /auth/verify        │
└──────────────────────────────────┘
              ↕ HTTPS
┌──────────────────────────────────┐
│      PHP Backend                 │
│                                  │
│  1. AuthController::verify()     │
│  2. Auth::verifyToken()          │
│  3. Firebase Admin SDK Verify    │
│  4. Auth::setupSession()         │
│  5. $_SESSION variables set      │
│  6. Protected routes check       │
│     AuthMiddleware::require()    │
└──────────────────────────────────┘
              ↕ API
┌──────────────────────────────────┐
│      Firebase Services           │
│                                  │
│  1. Firebase Auth (JWT tokens)   │
│  2. Firestore (user profiles)    │
│  3. Security Rules (access ctrl) │
└──────────────────────────────────┘
```

## Authentication Flow (Step-by-Step)

### Registration Flow

```
1. User → GET /register
   ↓
2. AuthController::register()
   AuthMiddleware::requireGuest()  ← Must not be logged in
   Render form
   ↓
3. User fills form: name, email, password
   ↓
4. Form submit → JavaScript auth.js
   ↓
5. AuthModule.handleRegister()
   ↓
6. Validate: password == passwordConfirm, length >= 6
   ↓
7. auth.createUserWithEmailAndPassword(email, password)
   ↓
8. Firebase Auth creates user
   ↓
9. user.updateProfile({displayName: name})
   ↓
10. db.collection('users').doc(user.uid).set({
      displayName, email, isAdmin: false,
      createdAt: serverTimestamp
    })
   ↓
11. user.getIdToken()
   ↓
12. POST /auth/verify
    Body: {idToken, uid, email, displayName}
   ↓
13. AuthController::verify()
    ↓
14. Auth::verifyToken($idToken)
    → Firebase Admin SDK verifyIdToken()
    → Returns: {uid, email, displayName, emailVerified}
    ↓
15. Auth::setupSession($uid, $email, $displayName)
    → $_SESSION['userId'] = $uid
    → $_SESSION['email'] = $email
    → $_SESSION['displayName'] = $displayName
    → $_SESSION['isAdmin'] = Auth::isUserAdmin($uid)  ← From Firestore
    ↓
16. Return JSON: {success: true}
    ↓
17. JavaScript: window.location.href = '/profile'
    ↓
18. GET /profile
    ↓
19. ProfileController::index()
    AuthMiddleware::require()  ← Check $_SESSION['userId'] exists
    ← Authorized, show profile
```

### Login Flow

```
1. User → GET /login
   ↓
2. AuthController::login()
   AuthMiddleware::requireGuest()
   Render form
   ↓
3. User enters email + password
   ↓
4. Form submit → JavaScript auth.js
   ↓
5. auth.signInWithEmailAndPassword(email, password)
   ↓
6. Firebase Auth validates
   ↓
7. If valid: user object returned
   If invalid: throw error (caught, show message)
   ↓
8-16. [Same as registration: getIdToken → /auth/verify → setupSession]
   ↓
17. Redirect to /profile
```

### Logout Flow

```
1. User clicks "Logout" button
   ↓
2. GET /logout
   ↓
3. AuthController::logout()
   ↓
4. Auth::logout()
   → session_destroy()
   ↓
5. Header redirect /
```

## Class Architecture

### src/Auth.php

Static utility class for server-side auth operations:

```php
class Auth {
    // Get Firebase Admin Auth service
    public static function getAuthService()

    // Verify ID token from client
    // Returns: ['uid', 'email', 'emailVerified', 'displayName']
    public static function verifyToken($idToken)

    // Get user doc from Firestore
    public static function getUserFromFirestore($uid)

    // Create or update user in Firestore
    public static function createOrUpdateUser($uid, $email, $displayName)

    // Check if user has isAdmin flag
    public static function isUserAdmin($uid)

    // Setup PHP session variables
    // Sets: $_SESSION['userId', 'email', 'displayName', 'isAdmin', 'loginTime']
    public static function setupSession($uid, $email, $displayName)

    // Check if user is logged in
    public static function isLoggedIn()

    // Check if user is admin
    public static function isAdmin()

    // Get current user ID from session
    public static function getCurrentUserId()

    // Destroy session
    public static function logout()
}
```

**Usage**:
```php
// In controller
if (Auth::isLoggedIn()) {
    $userId = Auth::getCurrentUserId();
    $isAdmin = Auth::isAdmin();
}

// Logout
Auth::logout();
```

### src/Middleware/AuthMiddleware.php

```php
class AuthMiddleware {
    // Require authentication (redirect to /login if not)
    public static function require()

    // Require admin (403 Forbidden if not)
    public static function requireAdmin()

    // Require guest (redirect to /profile if already logged in)
    public static function requireGuest()
}
```

**Usage** in controller:
```php
public function profilePage() {
    AuthMiddleware::require();  // ← Must be logged in
    // rest of code
}

public function adminPanel() {
    AuthMiddleware::requireAdmin();  // ← Must be admin
    // rest of code
}

public function loginPage() {
    AuthMiddleware::requireGuest();  // ← Must NOT be logged in
    // rest of code
}
```

### public/js/auth.js

JavaScript Auth Module with event handlers:

```javascript
AuthModule = {
    init()                      // Setup event listeners
    setupEventListeners()       // Bind form submits
    checkAuthState()            // Listen for auth changes
    handleLogin(e)              // Email/password login
    handleRegister(e)           // Create account
    handleLogout(e)             // Sign out
    syncSessionWithServer()     // POST idToken to backend
}
```

**Event Listeners**:
```javascript
document.getElementById('login-form')?.addEventListener('submit', 
  (e) => AuthModule.handleLogin(e)
)

document.getElementById('register-form')?.addEventListener('submit',
  (e) => AuthModule.handleRegister(e)
)

auth.onAuthStateChanged((user) => {
    if (user) AuthModule.syncSessionWithServer(user)
})
```

## Session Management

### PHP Session Structure

```php
$_SESSION = [
    'userId'     => 'firebase_uid_xyz',      // Firebase UID
    'email'      => 'user@example.com',      // From Firebase
    'displayName' => 'Max Mustermann',       // From Firestore
    'isAdmin'    => false,                   // From Firestore users.isAdmin
    'loginTime'  => 1234567890               // Unix timestamp
]
```

### Lifetime

- Sessions persist in PHP (server-side)
- No automatic timeout (can add TTL check in M4)
- Destroyed on logout: `session_destroy()`
- Persists across page refreshes
- Not tied to Firebase token expiry (tokens auto-refresh)

### CSRF Protection (Future)

For M4+, add token to forms:
```php
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
```

Then validate on POST:
```php
if (!hash_equals($_POST['csrf'], $_SESSION['csrf_token'])) {
    die('CSRF validation failed');
}
```

## Firestore Security Rules Architecture

### Rule Structure

```
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // Helper functions
    function isAuthenticated() { ... }
    function isAdmin() { ... }
    function isOwner(uid) { ... }

    // Collection rules
    match /posts/{postId} { ... }
    match /products/{productId} { ... }
    match /users/{userId} { ... }
    match /subscribers/{email} { ... }
    match /orders/{orderId} { ... }

    // Default deny
    match /{document=**} { allow read, write: if false; }
  }
}
```

### Key Decisions

1. **Helper Functions**: Reduce duplication, centralize logic
2. **Collection-Scoped**: Each collection has clear rules
3. **Field-Level**: Use `affectedKeys().hasOnly()` for update validation
4. **Deny by Default**: Only allow explicit cases
5. **Server Timestamp**: Enforce `createdAt == request.time` (no client hack)

### Dirty Dozen Coverage

| Requirement | Rule | Implementation |
|-------------|------|-----------------|
| Auth required | isAuthenticated() | Used in all create/update/delete |
| Own content | authorId == request.auth.uid | Posts rule |
| Server timestamp | createdAt == request.time | Posts create |
| Field size | title.size() <= 500 | Posts create |
| No shadow fields | affectedKeys().hasOnly() | Posts update |
| No self-admin | !('isAdmin' in ...) | Users create |
| Private profiles | read: if isOwner(userId) | Users read |
| Admin-only reads | read: if isAdmin() | Subscribers read |
| Admin-only writes | write: if isAdmin() | Products write |
| Email immutable | !('email' in affectedKeys()) | Users update |
| No admin deletion | allow delete: if false | Admins/Users delete |
| Audit trail | allow delete: if false | Orders delete |

## Token Flow & JWT

### Firebase ID Token

```
Header: {alg: "RS256", typ: "JWT"}
Payload: {
  iss: "https://securetoken.google.com/project-id",
  aud: "project-id",
  auth_time: 1234567890,
  user_id: "uid_xyz",
  sub: "uid_xyz",
  iat: 1234567890,
  exp: 1234571490,
  email: "user@example.com",
  email_verified: false,
  firebase: {...}
}
Signature: [HMAC(base64(header).base64(payload))]
```

### Verification Process

1. **Client** calls `user.getIdToken()`
2. **Client** sends to server in JSON body
3. **Server** calls `Auth::verifyToken($idToken)`
4. **Admin SDK** validates signature against Firebase public keys
5. **Admin SDK** checks expiry (`exp` claim)
6. **Admin SDK** returns verified claims
7. **Server** extracts `uid`, `email`
8. **Server** sets PHP session

### Auto-Refresh

Firebase JS SDK automatically:
- Refreshes token before expiry
- Calls `onAuthStateChanged()` when refreshed
- Syncs new token to server (calls `/auth/verify` again)

Server-side: No action needed, Firebase handles it.

## Protected Routes

### Without Middleware

```php
public function publicPage() {
    // No auth check
    echo "Anyone can see this";
}
```

### Require Authentication

```php
public function privatePage() {
    AuthMiddleware::require();  // ← Added
    // Now only logged-in users can access
    echo "Welcome " . $_SESSION['displayName'];
}
```

### Require Admin

```php
public function adminPanel() {
    AuthMiddleware::requireAdmin();  // ← Added
    // Now only admins can access
    // Non-admins get 403 Forbidden
}
```

### Require Guest (Not Logged In)

```php
public function loginPage() {
    AuthMiddleware::requireGuest();  // ← Added
    // Already logged in? Redirected to /profile
}
```

---

**Zusammenfassung**:
- Client-side: Firebase Auth JS SDK handles login/register/logout
- Server-side: Auth class verifies tokens and manages sessions
- Middleware: Protects routes with simple function calls
- Firestore Rules: Enforce access control at database level
- Security: Token validation + session checks + Firestore rules
