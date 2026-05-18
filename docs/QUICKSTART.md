# Quick Start Guide

Fast track to get the Zero-Cost Website live in 5 minutes.

---

## 📋 Pre-Flight Checklist (30 seconds)

- [ ] Code committed to GitHub
- [ ] Render.com account created (free)
- [ ] Terminal ready

---

## 🚀 5-Minute Deployment

### Step 1: Push Code to GitHub (1 min)

```bash
cd /mnt/c/KI/KI_Plan_Prototype_ZeroWebSite/plan-prototyp-produktion-v1-0-phaxes

git add -A
git commit -m "M1-M5 complete - ready for production"
git push origin main
```

### Step 2: Create Render Service (2 min)

1. Go to https://render.com/dashboard
2. Click **"New +"** → **"Web Service"**
3. Select **"Deploy an existing repository"**
4. Connect GitHub (authorize if first time)
5. Select repository: `KI_Plan_Prototype_ZeroWebSite`
6. Click **"Connect"**

### Step 3: Configure Service (1 min)

**Basic Settings:**
- Name: `zero-cost-website`
- Region: `Frankfurt (EU)` or closest
- Plan: `Free`
- Runtime: `Docker` (auto-selected)

### Step 4: Add Environment Variables (1 min)

In Render dashboard → Environment tab, add these 10 variables:

```
APP_ENV=production
FIREBASE_PROJECT_ID=test-project
FIREBASE_SERVICE_ACCOUNT_JSON=/dev/null
FIREBASE_API_KEY=AIzaSyDummyKeyTest123456789
FIREBASE_AUTH_DOMAIN=test-project.firebaseapp.com
FIREBASE_STORAGE_BUCKET=test-project.appspot.com
STRIPE_SECRET_KEY=sk_test_dummy123456789
STRIPE_PUBLISHABLE_KEY=pk_test_dummy123456789
MAILCHIMP_API_KEY=dummy-key-us1
MAILCHIMP_SERVER_PREFIX=us1
MAILCHIMP_LIST_ID=dummy123
```

### Step 5: Deploy (1 min)

1. Click **"Create Web Service"**
2. Wait for build to complete (3-5 minutes)
3. You'll get a URL like: `https://zero-cost-website.onrender.com`
4. **🎉 You're Live!**

---

## ✅ Verify Deployment

Test your site:
```
https://zero-cost-website.onrender.com           # Should load
https://zero-cost-website.onrender.com/news      # Should work
https://zero-cost-website.onrender.com/checkout  # Should show checkout
```

---

## 📚 What's Included

✅ **M1: Landing Page** - Fully functional, styled with Tailwind
✅ **M2: CMS Admin** - Routes ready, forms visible (needs Firebase)
✅ **M3: User Auth** - Forms ready, logic in place (needs Firebase)
✅ **M4: Shop & Checkout** - Stripe integration ready (test mode)
✅ **M5: Newsletter** - Forms ready, API ready (needs Mailchimp)

---

## 🔧 Next Steps (Optional)

### To Enable Full Features (Firebase/Stripe/Mailchimp)

**Get credentials** (20 min):
- Firebase: https://console.firebase.google.com (5 min)
- Stripe: https://dashboard.stripe.com (2 min)
- Mailchimp: https://mailchimp.com (10 min)

**Update Render environment** (2 min):
1. Go to Render dashboard
2. Select service → Environment
3. Update credentials
4. Auto-redeploys with new features ✅

### To Update Code

Any push to GitHub auto-redeploys:
```bash
git push origin main
# Wait 1-2 minutes, site updates automatically
```

---

## 📖 Full Documentation

| Document | Purpose | Time |
|----------|---------|------|
| `DEPLOYMENT_GUIDE.md` | Complete deployment + architecture | 15 min |
| `CREDENTIALS_SETUP.md` | Firebase/Stripe/Mailchimp setup | 20 min |
| `TESTING_SUMMARY.md` | Test results for all 5 milestones | 10 min |
| `LOCAL_TESTING.md` | 35+ test cases with instructions | 30 min |

---

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| Build fails | Check Render logs (Service → Logs tab) |
| 502 Bad Gateway | Wait 30s, site may be restarting |
| Can't find site | Verify URL matches Render dashboard |
| Need real features | Add Firebase/Stripe/Mailchimp credentials |

---

## 🎯 Success Indicators

After deployment, you should see:
- ✅ Service showing "Live"
- ✅ Green checkmark next to service name
- ✅ URL accessible (no 503 errors)
- ✅ Landing page loads in <3 seconds
- ✅ All routes respond (200/302 codes)

---

## 📞 Help

**Deployment Issues?** → See `DEPLOYMENT_GUIDE.md`
**Credentials Issues?** → See `CREDENTIALS_SETUP.md`
**Testing Issues?** → See `TESTING_SUMMARY.md` or `LOCAL_TESTING.md`
**Render Help?** → https://render.com/docs

---

## 🎉 You're Done!

Your Zero-Cost Website is now **live on the internet** at no cost.

**What's Next?**
1. Share the URL
2. (Optional) Add real credentials for full features
3. Monitor and enjoy!

---

**Time to deployment**: ~10 minutes
**Cost**: $0 (free tier)
**Features available**: M1 fully, M2-M5 partial (upgradeable)

**Status**: ✅ READY TO LAUNCH 🚀
