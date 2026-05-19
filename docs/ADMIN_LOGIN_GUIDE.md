# Admin Login & Access Guide

**For Site Administrators and Content Managers**

---

## Prerequisites

Before you can log in, you will need:
- A **Google account** (Gmail or any Google Workspace account)
- **Admin access** granted by the site owner (set in Firebase)
- Access to the website URL (e.g., `http://localhost:8000` or your production domain)

---

## Step-by-Step Login Procedure

### Step 1: Navigate to the Login Page

Go to the website homepage and look for the **Login** button in the top-right corner of the navigation bar.

**Direct URL**: `http://localhost:8000/login` (or your production URL)

### Step 2: Click "Login" Button

You'll see the login page with the Google sign-in option.

Click the **"Sign in with Google"** button.

### Step 3: Enter Your Google Credentials

A Google login popup will appear:
1. Enter your **Google email address**
2. Click **Next**
3. Enter your **Google password**
4. Click **Next**

If you have 2-factor authentication enabled, you may be asked to verify your identity.

### Step 4: Authorize the Application

Google will ask for permission to access your account information. Click **Allow** to proceed.

After successful authentication, you'll be redirected back to the website.

---

## Getting Admin Access

Once you've logged in with Google, you need to be granted **admin status** by the site owner.

### For Site Owners: Setting Up Admins

1. **Access Firebase Console**
   - Go to [console.firebase.google.com](https://console.firebase.google.com)
   - Select your project

2. **Navigate to Firestore Database**
   - In the left sidebar, click **Firestore Database**

3. **Open the Users Collection**
   - Find the **`users`** collection
   - Click on the document matching the admin's **Google UID**
   - The UID can be found in your Firebase Auth console under the user's profile

4. **Add Admin Status**
   - Click **Edit** on the document
   - Click **Add field** at the bottom
   - Field name: **`isAdmin`**
   - Type: **Boolean**
   - Value: **`true`**
   - Click **Save**

The user is now an admin and can access the admin dashboard.

---

## Accessing the Admin Dashboard

Once you have admin status, you can access the admin dashboard:

1. **Navigate to Admin Panel**
   - Click **Admin** in the top navigation
   - Or go directly to: `http://localhost:8000/admin` (or your production URL)

2. **What You'll See**
   - Dashboard with statistics
   - Recent news articles
   - Recent blog posts
   - Quick links to create new content

3. **From Here You Can**
   - Click **News** to manage news articles
   - Click **Blog** to manage blog posts
   - Click **Products** to manage products (if available)
   - Click **+ New** to create new content

---

## Common Issues & Solutions

### "You don't have permission to access this page"

**Cause**: You are logged in but not set as an admin in Firestore.

**Solution**:
1. Confirm with your site owner that your Google account has been added as admin
2. Check your Google UID in Firebase Auth console
3. Have the site owner manually set `isAdmin: true` in your Firestore user document
4. Refresh the page or log out and log back in

---

### "Google login popup didn't open"

**Cause**: Browser popup blocker is active, or you're using an unsupported browser.

**Solution**:
1. Check your browser popup settings and allow popups for this website
2. Try a different browser (Chrome, Firefox, Safari, or Edge)
3. Ensure you have JavaScript enabled
4. Clear your browser cache and try again

---

### "I'm logged in but see a blank dashboard"

**Cause**: Firebase credentials not properly configured on the server.

**Solution**:
1. Check browser Developer Tools Console (F12 or Cmd+Opt+J)
2. Look for Firebase error messages
3. Verify `.env` file has correct Firebase credentials
4. Contact your site administrator if errors persist

---

### "Can't see the Login button"

**Cause**: You may already be logged in, or the page hasn't fully loaded.

**Solution**:
1. Look in the top-right corner — if you see your Google profile picture, you're already logged in
2. If no picture appears, refresh the page
3. Try clearing your browser cache
4. Ensure JavaScript is enabled

---

## Logging Out

To log out:
1. Click your profile picture in the top-right corner (if visible)
2. Select **Logout** or **Sign out**
3. You'll be redirected to the home page

---

## Security Tips

✅ **Do:**
- Use a strong, unique password for your Google account
- Enable 2-factor authentication on your Google account
- Log out when finished, especially on shared computers
- Never share your login credentials

❌ **Don't:**
- Leave your browser logged in on public computers
- Share your Google account with others
- Write down your password
- Use the same password across multiple sites

---

## Need Help?

If you encounter issues logging in:
1. Check the troubleshooting section above
2. Review the browser console for error messages (F12)
3. Clear your browser cache and try again
4. Contact your site administrator

---

## Technical Details (For Administrators)

**Authentication Flow**:
- User logs in via Google OAuth 2.0
- Firebase verifies credentials
- Server-side session is created
- Admin status checked in Firestore `users` collection
- Access to `/admin` granted if `isAdmin === true`

**Related Files**:
- Authentication: `src/Controllers/AuthController.php`
- Admin panel: `templates/admin/` directory
- Firestore rules: `firestore.rules`

**Environment Requirements**:
- Firebase project with Authentication enabled
- Google OAuth configured in Firebase Console
- Firestore database with `users` collection
- Valid FIREBASE_* credentials in `.env`
