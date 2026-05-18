# Credentials Setup Guide

Complete guide for setting up Firebase, Stripe, and Mailchimp credentials for local testing and production deployment.

**Status**: ✅ All services support free tiers
**Time to Complete**: ~30 minutes for all credentials
**Required for**: M2 (CMS), M3 (Auth), M5 (Newsletter)

---

## Overview

The Zero-Cost Website integrates three external services:

| Service | Purpose | Free Tier | Setup Time |
|---------|---------|-----------|-----------|
| **Firebase** | User auth, database | ✅ Yes | 5 min |
| **Stripe** | Payments | ✅ Yes (test) | 2 min |
| **Mailchimp** | Email marketing | ✅ Yes | 10 min |

---

## 🔥 Firebase Setup

Firebase provides authentication, real-time database (Firestore), and cloud storage.

### Step 1: Create Firebase Project

1. Go to https://console.firebase.google.com
2. Click **"Create a project"** or select existing
3. Enter project name: `zero-cost-website`
4. Select **"Continue"**
5. Disable Google Analytics (optional)
6. Click **"Create project"** (takes ~1 min)

### Step 2: Generate Service Account Key

This allows your backend (PHP) to access Firebase securely.

1. In Firebase console, click **gear icon** → **"Project Settings"**
2. Click **"Service Accounts"** tab
3. Click **"Generate New Private Key"**
4. Download JSON file (save safely)
5. File contains: `private_key`, `project_id`, `client_email`

### Step 3: Get API Keys

For frontend Firebase JavaScript SDK:

1. In Firebase console, click **gear icon** → **"Project Settings"**
2. Click **"General"** tab
3. Scroll to **"Your apps"** section
4. Click web icon **</>**
5. Copy configuration object containing:
   - `apiKey`
   - `projectId`
   - `authDomain`
   - `storageBucket`

### Step 4: Enable Authentication

1. In Firebase console, click **"Authentication"**
2. Click **"Get started"**
3. Enable **"Email/Password"** provider
4. Click **"Save"**

### Step 5: Create Firestore Database

1. In Firebase console, click **"Firestore Database"**
2. Click **"Create database"**
3. Select **"Start in test mode"** (for development)
4. Select region closest to you
5. Click **"Create"**

**Note**: Test mode allows reads/writes for testing. Deploy security rules before production.

### Step 6: Update .env (Local)

```bash
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_SERVICE_ACCOUNT_JSON=/path/to/downloaded/serviceAccountKey.json
FIREBASE_API_KEY=AIzaSy...from_project_settings
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
```

### Step 7: Deploy Security Rules

Upload `firestore.rules` to Firebase:

1. Firebase console → **"Firestore Database"** → **"Rules"** tab
2. Replace content with `firestore.rules` from project root
3. Click **"Publish"**

---

## 💳 Stripe Setup

Stripe handles payment processing with comprehensive test mode.

### Step 1: Create Stripe Account

1. Go to https://dashboard.stripe.com
2. Click **"Sign up"** or **"Sign in"**
3. Complete account setup
4. Verify email address

### Step 2: Get API Keys

1. In Stripe dashboard, go to **"Developers"** (top navigation)
2. Click **"API keys"**
3. Ensure **"Test mode"** is enabled (toggle top-right)
4. Copy both keys:
   - **Publishable key** (starts with `pk_test_`)
   - **Secret key** (starts with `sk_test_`)

### Step 3: Update .env (Local)

```bash
STRIPE_PUBLISHABLE_KEY=pk_test_YOUR_KEY_HERE
STRIPE_SECRET_KEY=sk_test_YOUR_KEY_HERE
```

### Step 4: Test Cards

Use these test card numbers for development:

**Success**
```
Card: 4242 4242 4242 4242
Expiry: Any future date (e.g., 12/25)
CVC: Any 3 digits (e.g., 123)
Name: Any text
Result: Payment succeeds
```

**Declined**
```
Card: 4000 0000 0000 0002
Expiry: Any future date
CVC: Any 3 digits
Result: Card declined error
```

**Authentication Required**
```
Card: 4000 0025 0000 3155
Expiry: Any future date
CVC: Any 3 digits
Result: 3D Secure authentication
```

### Step 5: Configure Webhook (Optional, For Production)

1. Stripe dashboard → **"Webhooks"**
2. Click **"Add an endpoint"**
3. Enter your Render.com URL: `https://zero-cost-website.onrender.com/webhook/stripe`
4. Select events: `payment_intent.succeeded`, `charge.failed`
5. Get signing secret and add to .env

---

## 📧 Mailchimp Setup

Mailchimp handles email marketing and newsletter subscriptions.

### Step 1: Create Mailchimp Account

1. Go to https://mailchimp.com
2. Click **"Sign up"** or **"Sign in"**
3. Complete account setup
4. Verify email address

### Step 2: Create Audience (Email List)

1. In Mailchimp, go to **"Audience"** (top navigation)
2. Click **"Create Audience"**
3. Fill in details:
   - **Audience name**: `Zero-Cost Website Subscribers`
   - **Default from email**: Your email address
   - **Default from name**: Your organization
   - **Notify email**: Your email (for new subscribers)
4. Click **"Save"**

### Step 3: Get API Key

1. In Mailchimp, go to **"Account"** → **"Extras"** → **"API Keys"**
2. Click **"Create Key"**
3. Copy API key (format: `abc123def456ghi789-us1`)
   - First part: `abc123def456ghi789`
   - Server prefix (after dash): `us1`

### Step 4: Get List/Audience ID

1. In Mailchimp, go to **"Audience"** → Select your audience
2. Click **"Settings"** → **"Audience name and defaults"**
3. Under **"Audience ID"**, copy the ID (format: `a1b2c3d4e5`)

### Step 5: Create Email Templates (Optional)

For automated emails, create templates in Mailchimp:

1. Go to **"Content"** → **"Templates"**
2. Click **"Create a template"**
3. Create templates for:
   - Welcome email (new subscribers)
   - New blog post notification
   - New product notification
   - Order confirmation

### Step 6: Update .env (Local)

```bash
MAILCHIMP_API_KEY=abc123def456ghi789-us1
MAILCHIMP_SERVER_PREFIX=us1
MAILCHIMP_LIST_ID=a1b2c3d4e5
```

### Step 7: Set Up Tags/Segments (Optional)

Create segments for targeted emails:

1. Mailchimp → **"Audience"** → **"Segments"**
2. Create dynamic segments by tag:
   - `news_subscribers` → Interested in news
   - `blog_subscribers` → Interested in blog
   - `product_subscribers` → Interested in products

---

## 📋 Complete .env Example

```bash
# Application
APP_ENV=development
APP_SECRET=local-dev-secret-key

# Firebase
FIREBASE_PROJECT_ID=my-firebase-project
FIREBASE_SERVICE_ACCOUNT_JSON=/path/to/firebase-key.json
FIREBASE_API_KEY=AIzaSyDummyKey123456789ABC
FIREBASE_AUTH_DOMAIN=my-project.firebaseapp.com
FIREBASE_STORAGE_BUCKET=my-project.appspot.com

# Stripe (Test Mode)
STRIPE_SECRET_KEY=sk_test_51234567890abcdefghijklmnopqrst
STRIPE_PUBLISHABLE_KEY=pk_test_51234567890abcdefghijklmnopqrst

# Mailchimp
MAILCHIMP_API_KEY=abc123def456ghi789-us1
MAILCHIMP_SERVER_PREFIX=us1
MAILCHIMP_LIST_ID=a1b2c3d4e5
```

---

## 🚀 Apply Credentials Locally

### Step 1: Update .env File

```bash
cd /mnt/c/KI/KI_Plan_Prototype_ZeroWebSite/plan-prototyp-produktion-v1-0-phaxes

# Edit .env with your credentials
nano .env

# OR use echo to append values
echo "FIREBASE_SERVICE_ACCOUNT_JSON=/path/to/firebase-key.json" >> .env
```

### Step 2: Restart PHP Server

```bash
pkill -9 -f "php -S"
sleep 1
php -S localhost:8000 > /tmp/php-server.log 2>&1 &
```

### Step 3: Verify Setup

```bash
# Check homepage loads
curl -s http://localhost:8000/ | grep -o "<title>.*</title>"
# Expected: <title>Home - ZeroWeb</title>

# Check no errors in server log
tail -20 /tmp/php-server.log | grep -i error
# Expected: (no output = no errors)
```

### Step 4: Test Each Module

**Firebase Auth** (M3):
```bash
curl -s http://localhost:8000/register | grep -o "Register"
# Should see register form
```

**Firebase CMS** (M2):
```bash
curl -s http://localhost:8000/admin/news 2>&1
# Should redirect to login (302) or show news list
```

**Stripe Checkout** (M4):
```bash
curl -s http://localhost:8000/checkout | grep -o "Card Element\|card-element"
# Should show card element
```

**Mailchimp Newsletter** (M5):
```bash
curl -X POST http://localhost:8000/api/newsletter/subscribe \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","name":"Test User"}'
# Should return JSON response
```

---

## 🌐 Apply Credentials to Render.com Production

### Step 1: Prepare Credentials

For Firebase service account JSON, convert to single line:

```bash
# Original (multiline JSON file)
{
  "type": "service_account",
  "project_id": "my-project",
  ...
}

# Convert to single line (remove newlines, escape quotes)
{"type":"service_account","project_id":"my-project",...}
```

### Step 2: Update Render Environment

1. Go to Render dashboard
2. Select service: `zero-cost-website`
3. Go to **"Environment"** tab
4. For each variable, click **"Edit"** and paste value

**Firebase**:
```
FIREBASE_PROJECT_ID=your-real-project-id
FIREBASE_SERVICE_ACCOUNT_JSON={"type":"service_account",...}
FIREBASE_API_KEY=AIzaSy...
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
```

**Stripe** (replace dummy values):
```
STRIPE_SECRET_KEY=sk_test_YOUR_REAL_KEY
STRIPE_PUBLISHABLE_KEY=pk_test_YOUR_REAL_KEY
```

**Mailchimp** (replace dummy values):
```
MAILCHIMP_API_KEY=your-real-key-us1
MAILCHIMP_SERVER_PREFIX=us1
MAILCHIMP_LIST_ID=your-real-list-id
```

### Step 3: Deploy

1. Click **"Save"** for each change
2. Render auto-redeploys service (1-2 minutes)
3. Check logs for errors: Render → Service logs

---

## ✅ Verification Checklist

After setting up credentials:

### Firebase
- [ ] Service account JSON file exists
- [ ] File path is correct
- [ ] Project ID matches console
- [ ] Firestore database created
- [ ] Authentication enabled
- [ ] Security rules deployed
- [ ] Test: Can create user in console

### Stripe
- [ ] Keys start with `sk_test_` and `pk_test_`
- [ ] Keys copied exactly (no extra spaces)
- [ ] Test mode enabled in dashboard
- [ ] Webhook configured (for production)
- [ ] Test: Card 4242 4242 4242 4242 works

### Mailchimp
- [ ] API key contains `-us1` or similar
- [ ] Audience/List created
- [ ] List ID copied correctly
- [ ] From email configured
- [ ] Test: Can add subscriber via API

### Overall
- [ ] .env file saved
- [ ] No syntax errors in .env
- [ ] Server restarted
- [ ] Homepage loads: http://localhost:8000
- [ ] No PHP errors in logs
- [ ] All modules responding

---

## 🆘 Troubleshooting

### Firebase

| Problem | Solution |
|---------|----------|
| `Firebase class not found` | SDK not installed (dev only, production needs Composer) |
| `Service account not found` | Check file path in FIREBASE_SERVICE_ACCOUNT_JSON |
| `403 Firestore error` | Security rules too restrictive, deploy firestore.rules |
| `Auth fails silently` | Check FIREBASE_API_KEY is correct |
| `Can't create user` | Firestore database not created, go to Firebase console |

### Stripe

| Problem | Solution |
|---------|----------|
| `Card element won't mount` | STRIPE_PUBLISHABLE_KEY not in .env or wrong key |
| `Payment fails silently` | Check STRIPE_SECRET_KEY is correct test key |
| `Invalid request` | Card data format wrong, check browser console |
| `Webhook not working` | Signing secret not configured, add to .env |

### Mailchimp

| Problem | Solution |
|---------|----------|
| `API error 404` | Wrong List ID or API key for different list |
| `SSL certificate error` | Check PHP curl extension enabled (in Dockerfile) |
| `Rate limited` | Waiting 60s, Mailchimp rate-limits to 10 req/sec |
| `User not subscribed` | Check double opt-in settings in Mailchimp |

---

## 🔐 Security Best Practices

### Local Development
- ✅ Use `.env` file (in `.gitignore`, never committed)
- ✅ Use test/development credentials
- ✅ Store Firebase key file outside repo
- ✅ Share credentials only with team (secure channel)

### Production (Render.com)
- ✅ Use Render environment variables (not in code)
- ✅ Use test keys for Stripe (no real charges)
- ✅ Rotate API keys every 90 days
- ✅ Use separate Mailchimp list for production

### Never
- ❌ Commit .env to git
- ❌ Share API keys publicly
- ❌ Use production credentials in development
- ❌ Commit Firebase keys to repository

---

## Cost Summary

| Service | Plan | Cost |
|---------|------|------|
| **Firebase** | Spark (free) | $0/month |
| **Stripe** | Pay-as-you-go | 2.9% + $0.30 per transaction |
| **Mailchimp** | Free tier | $0/month (up to 500 contacts) |
| **Render.com** | Free tier | $0/month (auto-sleep included) |
| **Domain** (optional) | Custom | $10-15/year |
| **Total** | | **~$0-50/year** |

---

## Upgrade Path

When ready to scale:

| Service | Upgrade | Cost |
|---------|---------|------|
| Firebase | Blaze (pay-as-you-go) | $0.06/100k reads |
| Stripe | Same (scales with revenue) | 2.9% + $0.30 |
| Mailchimp | Standard | $20/month |
| Render.com | Pro | $7/month |
| **Total** | | **~$27-100/month** |

---

## Support Resources

- **Firebase Documentation**: https://firebase.google.com/docs
- **Stripe Documentation**: https://stripe.com/docs
- **Mailchimp Documentation**: https://mailchimp.com/help
- **Render.com Documentation**: https://render.com/docs

---

**Last Updated**: May 18, 2026
**Status**: Tested and Verified ✅
