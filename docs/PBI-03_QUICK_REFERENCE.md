# PBI-03: User Authentication — Quick Reference Card

## 🎯 What is PBI-03?

Role-based user authentication system with:
- ✅ Firebase Authentication (client-side)
- ✅ Admin access control (server-side)
- ✅ Protected admin routes
- ✅ Cross-environment support (localhost + Render.com)

---

## 🚀 Quick Start

### Local Testing
```bash
# Test the entire authentication system
php debug-pbi-03-auth.php

# Expected: "ALL TESTS PASSED ✅"
```

### Make Someone Admin
```bash
# Interactive setup (works on localhost & Render.com)
php cli-admin-setup.php

# Or manually: Firestore Console → users collection → isAdmin: true
```

### Login & Test
```bash
1. Go to http://localhost:8000
2. Click "Login"
3. Enter email/password
4. If admin: redirects to /admin ✓
5. If user: redirects to /profile ✓
6. Try /admin → 403 Access Denied (if not admin) ✓
```

---

## 🔑 Key Files

| File | Purpose |
|------|---------|
| `src/Auth.php` | Main auth class (getUserFromFirestore, isUserAdmin, setupSession) |
| `src/FirestoreRest.php` | REST API client for Firestore |
| `src/Controllers/AuthController.php` | Login/logout endpoints |
| `public/js/auth.js` | Client-side login logic |
| `debug-pbi-03-auth.php` | Test script (8 tests) |
| `cli-admin-setup.php` | Admin setup CLI |

---

## 📋 Common Tasks

### Check if User is Admin
```php
// In controller or template
if (Auth::isAdmin()) {
    // Show admin panel
} else {
    // Show access denied
}
```

### Get Current User Info
```php
$userId = $_SESSION['userId'] ?? null;
$email = $_SESSION['email'] ?? null;
$isAdmin = $_SESSION['isAdmin'] ?? false;
```

### Protect a Route (Method 1: Middleware)
```php
// In controller
\App\Middleware\AuthMiddleware::requireAdmin();
```

### Protect a Route (Method 2: Manual Check)
```php
protected function checkAdmin() {
    if (!Auth::isAdmin()) {
        header('Location: /login');
        exit;
    }
}
```

### Create/Update User (Automatic)
```php
// Automatically preserves isAdmin flag
Auth::createOrUpdateUser($uid, $email, $displayName);
```

---

## 🧪 Testing Checklist

- [ ] Firebase configured in .env / Render env vars
- [ ] `php debug-pbi-03-auth.php` passes all 8 tests
- [ ] test@example.com has `isAdmin: true` in Firestore
- [ ] Can login as admin → redirects to /admin
- [ ] Can login as user → redirects to /profile
- [ ] Can't access /admin as non-admin (403)
- [ ] Admin panel loads correctly
- [ ] Can create/edit/delete news, blog, products
- [ ] Re-login preserves admin status
- [ ] Works on localhost
- [ ] Works on Render.com (Base64 credentials)

---

## 🐛 Quick Troubleshooting

### "Access Denied" on /admin
```bash
# Check if user is actually admin
php debug-pbi-03-auth.php  # See TEST 4

# Solution: Firestore → users → set isAdmin: true
```

### Login doesn't work
```bash
# Check Firebase config
php debug-pbi-03-auth.php  # See TEST 1-2

# Check browser console for JS errors
# Open DevTools → Console
```

### Admin status lost after login
```bash
# This is fixed! But verify:
php debug-pbi-03-auth.php  # See TEST 8

# Should say: "✅ PASS: isAdmin flag preserved during update"
```

### Works locally but not on Render.com
```bash
# Check Base64 decoding
php debug-pbi-03-auth.php  # See TEST 2

# Render.com needs: env var (not .env file)
# FIREBASE_SERVICE_ACCOUNT_JSON must be Base64-encoded
```

---

## 🔐 Security Checklist

- [ ] Session ID regenerated on login
- [ ] Admin status verified server-side (not client)
- [ ] All admin routes protected
- [ ] Firebase token verified on /auth/verify
- [ ] Service account credentials in env vars (not code)
- [ ] HTTPS in production (Render.com handles)
- [ ] Sensitive data not logged

---

## 📊 Architecture Summary

```
User Login (Firebase Auth)
         ↓
Backend Verification (/auth/verify)
         ↓
Create/Update User (Firestore)
   ├─ Preserve isAdmin flag ✓
   └─ Handle Base64 credentials ✓
         ↓
Session Setup
   ├─ Check isUserAdmin()
   ├─ Set $_SESSION['isAdmin']
   └─ Regenerate session ID
         ↓
Route Protection
   ├─ Middleware check
   └─ Controller check
         ↓
Access Allowed/Denied
```

---

## 🌐 Environments

### Localhost
```
FIREBASE_SERVICE_ACCOUNT_JSON=/path/to/serviceAccountKey.json
FIREBASE_PROJECT_ID=zerocostws
```

### Render.com
```
FIREBASE_SERVICE_ACCOUNT_JSON=eyJhbGciOiJSUzI1NiI... (Base64)
FIREBASE_PROJECT_ID=zerocostws
```

---

## 📞 Need Help?

1. **Read full docs**: `docs/PBI-03_USER_AUTHENTIFIZIERUNG.md`
2. **Run test script**: `php debug-pbi-03-auth.php`
3. **Check logs**: `tail /tmp/dev-server.log`
4. **Firebase Console**: https://console.firebase.google.com

---

## ✅ PBI-03 Status

| Component | Status |
|-----------|--------|
| Firebase Auth (client) | ✅ Complete |
| Admin role check (server) | ✅ Complete |
| Protected routes | ✅ Complete |
| isAdmin flag preserved | ✅ Complete |
| Base64 support | ✅ Complete |
| Testing scripts | ✅ Complete |
| Documentation | ✅ Complete |

**PBI-03 is production-ready! 🚀**

---

**Last Updated**: 2026-05-22  
**Version**: 1.0  
**For more details**: See `PBI-03_USER_AUTHENTIFIZIERUNG.md`
