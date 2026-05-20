# Render.com Deployment & Firebase Configuration Guide

## The Issue: 401 Authentication Error on Render

Your Render.com deployment is getting a 401 "Invalid token" error because the Firebase service account credentials are not properly configured.

**Root Cause:** Render.com has an ephemeral filesystem, so file paths in `.env` like `/home/racex/.config/firebase/serviceAccountKey.json` don't work on production.

---

## Solution: Configure Firebase Service Account on Render Dashboard

### Step 1: Get Your Service Account JSON

1. Go to [Firebase Console](https://console.firebase.google.com)
2. Select your project: **zerocostws**
3. Go to **Settings** → **Service Accounts** → **Firebase Admin SDK**
4. Click **Generate New Private Key**
5. Save the downloaded JSON file (keep it safe!)

### Step 2: Encode to Base64

You need to convert the JSON to base64 format for Render.

**Option A: Using Linux/Mac Terminal**
```bash
cat serviceAccountKey.json | base64 -w 0
# Copy the output
```

**Option B: Using Online Tool**
1. Go to [base64encode.org](https://www.base64encode.org/)
2. Upload or paste your `serviceAccountKey.json`
3. Copy the encoded result

### Step 3: Set Environment Variable on Render

1. Go to your Render service dashboard
2. Click **Settings** → **Environment**
3. Add a new environment variable:
   - **Name:** `FIREBASE_SERVICE_ACCOUNT_JSON`
   - **Value:** Paste the base64-encoded JSON from Step 2
4. Click **Save Changes**

⚠️ **Important:** 
- Do NOT paste the JSON directly - it must be base64-encoded
- Do NOT commit the service account key to git
- Keep the key secret!

### Step 4: Verify Configuration

After setting the environment variable, redeploy:

1. Click **Manual Deploy** or push a commit
2. Check deployment logs for:
   ```
   Firebase Admin SDK initialized successfully
   ```

If you see:
```
FIREBASE_SERVICE_ACCOUNT_JSON not configured
```
Then the environment variable didn't get set. Go back to Step 3.

---

## Troubleshooting

### Error: "Firebase service account not configured"

**Solution:**
1. Check Render dashboard → Settings → Environment
2. Verify `FIREBASE_SERVICE_ACCOUNT_JSON` is set
3. Make sure it's base64-encoded (starts with `ey...` if it's JSON)
4. Redeploy

### Error: "FIREBASE_SERVICE_ACCOUNT_JSON is not valid JSON"

**Solution:**
1. The base64 encoding might be incorrect
2. Re-encode: `cat serviceAccountKey.json | base64 -w 0`
3. Make sure to copy the ENTIRE output
4. Update environment variable and redeploy

### Error: "Firebase Auth service not initialized"

**Causes:**
- Service account is invalid
- Missing required fields in service account JSON
- Network issue contacting Firebase

**Solution:**
1. Test locally first:
   ```bash
   php -S localhost:8000
   # Try login
   tail -f /tmp/php.log
   ```
2. Check logs match expected format
3. Verify service account hasn't been deleted from Firebase Console

### Still getting 401 after setup?

1. **Check server logs** on Render:
   - View **Logs** in Render dashboard
   - Look for: `Token analysis: type=...`
   - Check for specific error messages

2. **Verify Firebase Project IDs match:**
   - Render logs should show: `project_id: zerocostws`
   - Client config should have: `projectId: "zerocostws"`
   - Service account should have: `"project_id": "zerocostws"`

3. **Check token format:**
   - Browser console should show: `Token parts: 3`
   - Token should be 800+ characters

---

## Environment Variable Configuration Summary

Your Render service should have these environment variables:

| Variable | Value | Source |
|----------|-------|--------|
| `FIREBASE_SERVICE_ACCOUNT_JSON` | Base64-encoded service account | Firebase Console |
| `FIREBASE_PROJECT_ID` | `zerocostws` | Firebase Console |
| `FIREBASE_API_KEY` | Your API key | Firebase Console |
| `FIREBASE_AUTH_DOMAIN` | Your auth domain | Firebase Console |
| `FIREBASE_STORAGE_BUCKET` | Your storage bucket | Firebase Console |
| `STRIPE_SECRET_KEY` | Your Stripe secret key | Stripe Dashboard |
| `STRIPE_PUBLISHABLE_KEY` | Your Stripe public key | Stripe Dashboard |

---

## How the Code Now Works

### Local Development (localhost)
```
.env file contains: /path/to/serviceAccountKey.json
↓
Auth.php checks if file exists → YES
↓
Uses file directly
```

### Render.com Production
```
.env file contains: /path/to/serviceAccountKey.json (ignored)
↓
Environment variable set: FIREBASE_SERVICE_ACCOUNT_JSON (base64)
↓
Auth.php checks if file exists → NO
↓
Decodes base64 environment variable
↓
Creates temporary file for kreait library
↓
Uses temporary file for authentication
```

---

## Security Notes

### Never commit service account keys!

1. Add to `.gitignore`:
   ```bash
   echo "serviceAccountKey.json" >> .gitignore
   ```

2. Add environment config to `.gitignore`:
   ```bash
   echo ".env.production" >> .gitignore
   ```

3. Use Render's environment variables instead

### Rotate your service account key if:
- You accidentally committed it
- You suspect it's been compromised
- Change was made by someone who shouldn't have access

To rotate:
1. Delete old key in Firebase Console
2. Generate new key
3. Update Render environment variable

---

## Testing on Render

### Quick Test
```bash
# Open Render logs and try login on your site
# Look for: "Token verified successfully"
# If you see "Token analysis: type=custom", it's the wrong token type
```

### Detailed Diagnostics
1. Click **Logs** in Render dashboard
2. Search for `Token` to see all token-related messages
3. Check for:
   - `Firebase Admin SDK initialized successfully` ✓
   - `Token analysis: type=id_token` ✓
   - `Token verified successfully` ✓

### If Something Goes Wrong
1. Check logs for error messages
2. Note the exact error
3. Compare with "Troubleshooting" section above
4. Search for the error on [Stack Overflow](https://stackoverflow.com)

---

## After Fixing Authentication

Once login works, verify these endpoints:

```bash
# Homepage loads
curl -s https://your-render-url.onrender.com/ | grep -o "<title>.*</title>"

# Login page loads
curl -s https://your-render-url.onrender.com/login | grep -c "login-form"

# Can view posts (if any exist)
curl -s https://your-render-url.onrender.com/news
```

---

## Next Steps

1. ✅ Get Firebase service account JSON
2. ✅ Encode to base64
3. ✅ Set `FIREBASE_SERVICE_ACCOUNT_JSON` on Render
4. ✅ Redeploy
5. ✅ Test login
6. ✅ Check Render logs for success messages

If you're still stuck, share the output of:
- Render deployment logs
- Browser console errors
- Steps you've already tried
