# Admin Login & Access Guide

**For Site Administrators and Content Managers**

---

## Prerequisites

Before you can log in, you will need:
- A **registered account** on the website (email + password)
- **Admin access** granted by the site owner (set in Firebase `users` collection)
- Access to the website URL (e.g., `http://localhost:8000` or your production domain)

---

## Step-by-Step Login Procedure

### Step 1: Navigate to the Login Page

Go to the website homepage and look for the **Login** button in the top-right corner of the navigation bar.

**Direct URL**: `http://localhost:8000/login` (or your production URL)

### Step 2: Enter Your Email & Password

You'll see the login page with an email/password form.

Fill in:
- **E-Mail**: Your registered email address
- **Passwort** (Password): Your account password

### Step 3: Submit the Form

Click the **Anmelden** (Sign in) button.

### Step 4: Wait for Server Verification

The system will:
1. Send your credentials to Firebase for authentication
2. Verify the Firebase token on the server
3. Create a PHP session for you
4. Redirect you to your profile page

If login fails, check:
- Email address is correct
- Password is correct (case-sensitive)
- Your account is registered (if not, click "Hier registrieren" to create one)

---

## Getting Admin Access

Once you've logged in, you need to be granted **admin status** by the site owner.

### For Users: Requesting Admin Access

1. **Create your account** by registering at `/register`
2. **Log in** with your credentials
3. **Contact the site owner** and provide your **email address**
4. The site owner will grant you admin status in Firebase

### For Site Owners: Setting Up Admins

Follow these steps to make a user an admin:

1. **Access Firebase Console**
   - Go to [console.firebase.google.com](https://console.firebase.google.com)
   - Select your project

2. **Navigate to Firestore Database**
   - In the left sidebar, click **Firestore Database**
   - Look for the **`users`** collection

3. **Find the User Document**
   - In the `users` collection, find the document with the user's **UID**
   - The UID is the document ID and can be found in your Firebase Auth console
   - Or search by the user's email in the `users` collection documents

4. **Add Admin Status**
   - Click on the user's document to open it
   - Click **Add field** button
   - **Field name**: `isAdmin`
   - **Type**: Boolean
   - **Value**: `true`
   - Click **Save**

5. **Verify Admin Access**
   - Have the user log out and log back in
   - They should now be able to access `/admin`

**Note**: If the user's document doesn't exist, you can create it manually with:
```
UID: (their Firebase UID)
isAdmin: true
email: (their email)
displayName: (their name)
createdAt: (current date)
```

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

### "Access Denied" when accessing `/admin`

**Cause**: You are logged in but not set as an admin in Firestore.

**Solution**:
1. Confirm with your site owner that your account has been marked as admin
2. Make sure your UID in Firebase matches the document in Firestore `users` collection
3. Have the site owner check that `isAdmin: true` is set in your user document
4. Log out and log back in to refresh your session
5. If still denied, contact your site administrator

---

### "Email/Password combination is incorrect"

**Cause**: Wrong email or password entered.

**Solution**:
1. Check that you're using the correct email (case-insensitive, but whitespace matters)
2. Verify your password is correct (passwords are case-sensitive)
3. Use browser's password manager if available
4. If you forgot your password:
   - Contact your site administrator for password reset
   - Or manually delete your user from Firebase and re-register

---

### "Account doesn't exist"

**Cause**: You haven't registered yet, or registered with a different email.

**Solution**:
1. Click **"Hier registrieren"** (Register here) on the login page
2. Fill in your name, email, and password
3. Confirm your password
4. Click **Register**
5. You can now log in with your email and password

---

### "Dashboard is blank or shows errors"

**Cause**: Firebase credentials not properly configured on the server, or permission issues in Firestore.

**Solution**:
1. Check browser Developer Tools Console (F12 or Cmd+Opt+J)
2. Look for Firebase error messages (red errors)
3. If you see permission denied errors:
   - Check Firestore security rules allow your user to read data
   - Verify your `users` document has `isAdmin: true`
4. Contact your site administrator if errors persist

---

### "I can log in but can't create/edit content"

**Cause**: Firestore security rules don't allow your user role to write data.

**Solution**:
1. Verify your account has `isAdmin: true` in Firestore `users` collection
2. Check browser console for permission errors
3. If rules are too restrictive, contact your site administrator to adjust them
4. Log out and log back in to refresh permissions

---

## Logging Out

To log out:
1. Navigate to the **Logout** page at `/logout`
2. Or click the logout button in your profile menu (if available)
3. You'll be redirected to the home page
4. Your session will be cleared on both the client and server

---

## Security Tips

✅ **Do:**
- Use a **strong, unique password** (mix of upper/lowercase, numbers, symbols)
- Make your password at least 8 characters long
- Log out when finished, especially on shared computers
- Clear your browser cache after logging out on public machines
- Keep your email address secure
- Contact admin immediately if you suspect unauthorized access

❌ **Don't:**
- Use the same password across multiple websites
- Write down your password or store it in plain text
- Share your credentials with anyone
- Leave your browser logged in on public computers
- Click login links from suspicious emails
- Disable browser security/HTTPS warnings

---

## Need Help?

If you encounter issues logging in:
1. Check the troubleshooting section above
2. Review the browser console for error messages (F12)
3. Clear your browser cache and try again
4. Contact your site administrator

---

## Technical Details (For Administrators)

### Authentication Flow

1. **User Registration**
   - User fills email/password form on `/register`
   - `AuthModule.handleRegister()` calls `auth.createUserWithEmailAndPassword()`
   - Firebase creates user account
   - User document created in Firestore with `isAdmin: false`

2. **User Login**
   - User fills email/password form on `/login`
   - `AuthModule.handleLogin()` calls `auth.signInWithEmailAndPassword()`
   - Firebase authenticates and returns JWT token

3. **Session Verification**
   - Client-side JavaScript calls `user.getIdToken(true)` to get JWT
   - JWT is sent to `/auth/verify` POST endpoint
   - Server verifies JWT with Firebase SDK
   - PHP session created with user info (`uid`, `email`, `displayName`)

4. **Admin Check**
   - When accessing `/admin`, `AuthMiddleware::requireAdmin()` is called
   - Checks if user is logged in (session exists)
   - Checks if user is admin by querying Firestore `users/{uid}` document
   - Grants access only if `isAdmin === true`

### Key Files

**Backend**:
- `src/Controllers/AuthController.php` — Login/Register form handlers, Token verification
- `src/Middleware/AuthMiddleware.php` — Permission checks for routes
- `src/Auth.php` — Session management, Firebase token verification
- `src/Controllers/Admin/DashboardController.php` — Admin dashboard (requires admin)

**Frontend**:
- `public/js/auth.js` — Firebase authentication, form handling
- `public/js/firebase-init.js` — Firebase SDK initialization
- `templates/auth/login.phtml` — Login form
- `templates/auth/register.phtml` — Registration form
- `templates/admin/` — Admin dashboard templates

**Firebase**:
- `firestore.rules` — Security rules for read/write permissions
- Firebase Authentication enabled (Email/Password provider)
- Firestore database with `users` and `posts` collections

### Environment Requirements

```env
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_API_KEY=your-web-api-key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=your-sender-id
FIREBASE_APP_ID=your-app-id
FIREBASE_SERVICE_ACCOUNT_JSON=/path/to/serviceAccount.json
```

### Firestore Security Rules

The `firestore.rules` file controls who can read/write data:

```
collection users/{uid} {
  allow read: if request.auth.uid == uid || userIsAdmin();
  allow write: if request.auth.uid == uid && !isModifyingIsAdminField();
  allow create: if request.auth.uid == uid;
}

function userIsAdmin() {
  return request.auth != null && 
         get(/databases/$(database)/documents/users/$(request.auth.uid)).data.isAdmin == true;
}
```

Only the site owner can set `isAdmin: true` via Firebase Console.
