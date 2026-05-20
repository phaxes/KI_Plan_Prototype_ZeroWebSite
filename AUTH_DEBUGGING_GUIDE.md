# Firebase Authentication Debugging Guide

## The 401 "Invalid Token" Error

### What This Means
When you login and get a 401 error with "Invalid token", it means:
- ✓ Your login request reached the server
- ✓ Firebase recognized your credentials
- ✗ The token sent to the server cannot be verified

### Root Cause
The server's `Auth::verifyToken()` method is rejecting the token from the client. This typically means:

1. **Wrong Token Type** - Client sent a custom token instead of an ID token
2. **Token Expired** - Token expired before reaching the server
3. **Token Format Issue** - Token is corrupted or malformed
4. **Firebase Mismatch** - Client and server Firebase projects don't match

---

## How to Debug

### Step 1: Check Browser Console

1. Open browser Dev Tools (F12)
2. Go to **Console** tab
3. Try to login
4. Look for these messages:

```javascript
// Good - you should see this:
Got ID token, length: 1234
Token parts: 3
Token preview: eyJhbGciOi...

// Bad - if you see this:
Failed to get ID token: error message
```

**If you see "Failed to get ID token":**
- Your Firebase SDK on client isn't working properly
- Check if Firebase is initialized
- Check if your email/password are correct

### Step 2: Check Server Logs

1. Open terminal where server is running:
```bash
tail -f /tmp/php.log
```

2. Try to login and watch for messages like:
```
Token analysis: type=id_token, reason=Valid Firebase ID token
Token verified successfully
```

Or if there's an error:
```
Token analysis: type=custom, reason=Admin SDK custom token
ERROR: Received Admin SDK custom token instead of Firebase ID token
```

### Step 3: Manually Test Token Verification

Run this test script:
```bash
php /tmp/test-login.php
```

This will show:
- ✓ Firebase Admin SDK is working
- ✓ Token creation works
- Details about token structure
- Specific error messages

---

## Common Issues and Fixes

### Issue 1: "Wrong token type: Admin SDK token received"
**Cause:** Client is somehow sending custom tokens instead of ID tokens

**Fix:** 
- Verify client code is using `user.getIdToken()` (line 137 in auth.js)
- Check that user is properly authenticated with Firebase before calling `getIdToken()`
- Restart server and try login again

### Issue 2: "Token expired"
**Cause:** Token expired between client generation and server verification

**Fix:**
1. Check server system time is correct:
   ```bash
   date
   ```
2. Verify `FIREBASE_PROJECT_ID` matches in:
   - `/templates/layouts/base.phtml` (client config)
   - `/.env` (server config)

### Issue 3: Still getting 401 after fixes
**Cause:** Firebase SDK initialization issue

**Fix:**
1. Clear browser cache: Ctrl+Shift+Delete
2. Open login page in new incognito window
3. Try login again
4. Check console for any JavaScript errors

### Issue 4: Server shows "Firebase Auth service not initialized"
**Cause:** Service account credentials not loaded

**Fix:**
1. Verify `FIREBASE_SERVICE_ACCOUNT_JSON` in `.env`:
   ```bash
   cat .env | grep FIREBASE_SERVICE_ACCOUNT_JSON
   ```
2. Make sure the file path exists:
   ```bash
   ls -la /home/racex/.config/firebase/serviceAccountKey.json
   ```
3. If on Render.com, ensure the environment variable is set in the dashboard

---

## Testing the Full Flow

### Test 1: Check Server is Running
```bash
curl -s http://localhost:8000 | grep -o "<title>.*</title>"
# Should show: <title>Home</title>
```

### Test 2: Check Firebase Config is Loaded
```bash
curl -s http://localhost:8000 | grep "projectId"
# Should show: projectId: "zerocostws"
```

### Test 3: Check Login Page Works
```bash
curl -s http://localhost:8000/login | grep -o "login-form"
# Should show: login-form
```

### Test 4: Test Debug Endpoint
```bash
# Create a test token
php -r "
require 'vendor/autoload.php';
\App\Config::load();
\$auth = \App\Auth::getAuthService();
\$token = (string)\$auth->createCustomToken('test-user');
echo json_encode([
  'idToken' => \$token,
  'email' => 'test@example.com',
  'uid' => 'test-user'
]);
" | curl -s -X POST http://localhost:8000/auth/debug-token \
  -H "Content-Type: application/json" \
  -d @- | jq .
```

---

## What the Logs Tell You

### Good Scenario
```
Token header: {"typ":"JWT","alg":"RS256"}
Token payload claims - iss: https://securetoken.google.com/zerocostws, aud: zerocostws
Token verified successfully
Session verified on server
```

### Bad Scenario - Wrong Token Type
```
Token analysis: type=custom, reason=Admin SDK custom token
ERROR: Received Admin SDK custom token instead of Firebase ID token
Token verification failed with exception: Kreait\Firebase\Exception\Auth\FailedToVerifyToken
```

### Bad Scenario - Token Expired
```
Token payload claims - exp: 1234567890, current_time: 1234567900
Token has expired: exp=1234567890, current=1234567900
Token verification failed with exception: Kreait\Firebase\Exception\Auth\FailedToVerifyToken
```

---

## Permanent Fix Strategy

If you're still getting 401 errors after trying the above:

1. **Verify Firebase Configuration**
   ```bash
   # Check both client and server have same project ID
   grep projectId /templates/layouts/base.phtml
   grep FIREBASE_PROJECT_ID .env
   # Both should show: zerocostws
   ```

2. **Check System Time**
   ```bash
   date
   # Should show current time (Firebase uses this for token validation)
   ```

3. **Review Client-Side Authentication**
   - Open browser console (F12)
   - Try to login
   - Check for any JavaScript errors
   - Look for token generation logs

4. **Enable Detailed Logging**
   - Edit `auth.js` to add more console.log() calls
   - Edit `Auth.php` to add more error_log() calls
   - Capture full conversation and share error logs

---

## For Render.com Deployment

If running on Render.com:

1. **Environment Variables**
   - `FIREBASE_SERVICE_ACCOUNT_JSON` should be base64-encoded or a valid file path
   - The code automatically handles base64-encoded values

2. **Testing on Render**
   ```bash
   # SSH into Render instance
   # Check if service account file exists
   ls -la /app/firebase_sa_*.json
   
   # Check environment variable
   echo $FIREBASE_SERVICE_ACCOUNT_JSON | head -c 100
   ```

3. **Common Render Issues**
   - Authorization headers are stripped (already fixed - using body-based auth)
   - File system is ephemeral (service account loaded from env variable)
   - Time might be out of sync (check Render logs)

---

## Need More Help?

If after following this guide you still have issues:

1. **Collect diagnostic information:**
   ```bash
   # Run this and save output
   php /tmp/test-login.php > /tmp/diagnostic.txt 2>&1
   cat /tmp/diagnostic.txt
   ```

2. **Check recent server logs:**
   ```bash
   tail -100 /tmp/php.log
   ```

3. **Browser console errors:**
   - F12 → Console tab → Screenshot

4. **Share the above with details about:**
   - Error message you see
   - Browser version
   - What Firebase SDK version you're using
   - If on localhost or Render.com

---

## Key Files Modified for Debugging

- `src/Auth.php` - Token verification and analysis
- `src/Controllers/AuthController.php` - Endpoint error handling
- `public/js/auth.js` - Client-side logging
- `index.php` - Added `/auth/debug-token` route
