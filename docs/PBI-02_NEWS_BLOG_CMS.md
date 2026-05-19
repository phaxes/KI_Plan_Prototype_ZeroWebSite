# PBI-02: News/Blog CMS System — Complete Documentation

**Status**: ✅ Implemented  
**Date**: 2026-05-19  
**Version**: 1.0

---

## Overview

The News/Blog Content Management System is now fully functional. It provides:

- **Full CRUD operations** for news and blog articles
- **Admin dashboard** with forms for creating, editing, and deleting content
- **Firestore integration** for serverless data storage
- **Public-facing pages** that display published content
- **Landing page** dynamic sections showing latest news, blog, and products
- **Responsive admin UI** with the project's design system

---

## Architecture

### Data Model

**Firestore Collection: `posts`**

All news and blog articles are stored in a single `posts` collection, distinguished by a `type` field:

```
posts/{autoId}
  ├── title: string (required)
  ├── content: string (markdown/plain text)
  ├── type: 'news' | 'blog' (required)
  ├── published: boolean
  ├── tags: string[]
  ├── imageUrl: string
  ├── authorId: string (email or 'system')
  ├── createdAt: Timestamp
  └── updatedAt: Timestamp
```

### Routes

**Public-facing:**
- `GET /` — Home page (displays latest news, blog, products)
- `GET /news` — News listing page  
- `GET /news/{id}` — Individual news article
- `GET /blog` — Blog listing page
- `GET /blog/{id}` — Individual blog post

**Admin (requires login + admin role):**
- `GET /admin` — Dashboard
- `GET /admin/news` — News list
- `GET /admin/news/create` — Create form
- `POST /admin/news/store` — Save new
- `GET /admin/news/{id}/edit` — Edit form
- `POST /admin/news/{id}/update` — Save changes
- `POST /admin/news/{id}/delete` — Delete (with confirmation)
- `GET /admin/blog` — Blog list
- `GET /admin/blog/create` — Create form
- `POST /admin/blog/store` — Save new
- `GET /admin/blog/{id}/edit` — Edit form
- `POST /admin/blog/{id}/update` — Save changes
- `POST /admin/blog/{id}/delete` — Delete (with confirmation)

---

## Bug Fixes Applied

### 1. Router 404 Path
**File**: `src/Router.php:64`  
**Issue**: Router tried to load `errors/404.php` instead of `errors/404.phtml`  
**Fix**: Updated path to `.phtml` extension

### 2. Firestore Timestamp Normalization
**File**: `src/Firebase.php`  
**Issue**: Firestore returns `Google\Cloud\Core\Timestamp` objects, but templates expected PHP `DateTime`  
**Fix**: Added `normalizeTimestamp()` helper that converts Firestore timestamps to PHP `DateTime` objects  
**Applied to**: `getPosts()`, `getPostById()`, `getProducts()`, `getProductById()`

### 3. Firestore Update API
**File**: `src/Firebase.php::updatePost()` and `updateProduct()`  
**Issue**: kreait/firebase-php's `update()` method requires field-path array format  
**Fix**: Switched to `set($data, ['merge' => true])` for merge-update behavior

### 4. NewsAdminController Error Handling
**File**: `src/Controllers/Admin/NewsAdminController.php:70`  
**Issue**: Called `App::showNotification()` which doesn't exist in PHP (confused with JS)  
**Fix**: Replaced with safe `header('Location: /admin/news/create?error=save_failed')`

### 5. Missing JavaScript Delete Handlers
**File**: `public/js/` (NEW: `public/js/admin.js`)  
**Issue**: Admin templates call `deleteNews()` and `deleteBlog()` functions that were undefined  
**Fix**: Created `admin.js` with functions that create and submit hidden forms for delete operations

### 6. Firebase Client Configuration
**Files**: `templates/layouts/base.phtml`, `templates/layouts/admin.phtml`, `public/js/firebase-init.js`  
**Issue**: Firebase credentials were hardcoded as `"YOUR_API_KEY"` placeholders  
**Fix**: 
- PHP injects `window.firebaseConfig` from `.env` variables
- `firebase-init.js` uses the injected config
- Client-side Firebase now initializes with actual credentials

### 7. Blog Admin Template Styling
**Files**: `templates/admin/blog/index.phtml`, `create.phtml`, `edit.phtml`  
**Issue**: Used generic Tailwind instead of the project design system  
**Fix**: Updated to match news admin styling (border-2, font-serif/mono, color tokens)

---

## Setup Instructions

### Prerequisites

1. **Firebase Project**
   - Create a project at [firebase.google.com](https://firebase.google.com)
   - Enable Firestore (start in test mode for development)
   - Create a service account and download JSON key

2. **PHP Environment**
   - PHP 8.2+
   - Composer

### Installation

**Step 1: Install Composer Dependencies**
```bash
php composer.phar install
```
This installs:
- `kreait/firebase-php` (Firebase Admin SDK)
- `stripe/stripe-php` (Stripe payments)
- `drewm/mailchimp-api` (Email marketing)

**Step 2: Configure Firebase Credentials**

Copy `.env.example` to `.env` and fill in your Firebase credentials:

```env
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_SERVICE_ACCOUNT_JSON=/path/to/serviceAccount.json
FIREBASE_API_KEY=your-api-key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=your-sender-id
FIREBASE_APP_ID=your-app-id
```

**Step 3: Configure Firestore Security Rules**

Deploy the rules in `firestore.rules` to your Firebase project:

```bash
firebase deploy --only firestore:rules
```

**Step 4: Start Development Server**

```bash
npm run dev  # Watch Tailwind CSS in one terminal
php -S localhost:8000  # Dev server in another terminal
```

### Create Admin User

1. Login via Google OAuth at `http://localhost:8000/login`
2. Verify you're logged in
3. Manually set your user as admin in Firestore:
   - Collection: `users`
   - Document: `{your-uid}`
   - Field: `isAdmin: true`

---

## Using the CMS

### Creating a News Article

1. Login and navigate to `/admin`
2. Click **News** → **+ Neue News**
3. Fill in the form:
   - **Title**: Article headline
   - **Image URL**: Optional image link
   - **Content**: Article body (supports markdown syntax, renders as plain text)
   - **Tags**: Comma-separated tags
   - **Publish**: Check to publish immediately (uncheck to save as draft)
4. Click **Speichern** (Save)

### Viewing Published Content

**Home Page** (`/`)
- Shows 3 latest published news articles
- Shows 3 latest published blog posts
- Shows 3 featured products

**News Listing** (`/news`)
- Paginated grid of all published news
- Click to read full article

**Blog Listing** (`/blog`)
- Paginated grid of all published blog posts
- Click to read full article

---

## Key Components

### HomeController
**File**: `src/Controllers/HomeController.php`

Fetches latest content from Firestore and passes to landing page template:
- `$latestNews` — 3 newest published news items
- `$latestBlog` — 3 newest published blog items
- `$featuredProducts` — 3 featured products

### Firebase PHP Wrapper
**File**: `src/Firebase.php`

Provides CRUD methods for Firestore:

```php
// Read
Firebase::getPosts('news', $published = true, $limit = 10, $offset = 0)
Firebase::getPostById($postId)

// Create
Firebase::createPost($data)

// Update
Firebase::updatePost($postId, $data)

// Delete
Firebase::deletePost($postId)
```

### Admin JavaScript Handlers
**File**: `public/js/admin.js`

```js
// Delete with confirmation
deleteNews(id)   // News articles
deleteBlog(id)   // Blog posts
deleteProduct(id)  // Products
```

---

## Firestore Security Rules

The rules in `firestore.rules` enforce:

**Posts Collection**
- **Read**: Public if `published === true`, auth users can read own unpublished
- **Create**: Auth required; must set `authorId` and `createdAt`
- **Update**: Author or admin only; can't modify `type` or `authorId`
- **Delete**: Author or admin only

**Users Collection**
- **Read/Write**: Owners can read/write own profile; admins read all
- **Create**: Cannot set `isAdmin` (must be set by admin manually)

---

## Timestamp Handling

Firestore returns timestamps as `Google\Cloud\Core\Timestamp` objects. The Firebase wrapper normalizes these to PHP `DateTime` for template compatibility:

```php
// In Firebase.php::normalizeTimestamp()
if ($value instanceof \Google\Cloud\Core\Timestamp) {
    return \DateTime::createFromInterface($value->get());
}
return $value;
```

All date fields (`createdAt`, `updatedAt`) are automatically normalized when fetched.

---

## Template Variables

### Public Templates

**`templates/news/index.phtml`**
- `$news`: array of article objects
- `$page`: current page number
- `$limit`: articles per page

**`templates/news/show.phtml`**
- `$article`: single article object with keys: `title`, `content`, `imageUrl`, `createdAt`, `authorId`, `tags`

**`templates/blog/...`** — Same as news, using `$articles` variable name

### Admin Templates

**`templates/admin/news/index.phtml`**
- `$news`: array of article objects (includes unpublished)

**`templates/admin/news/create.phtml` / `edit.phtml`**
- `$article`: (edit only) article object with `createdAt` and `updatedAt`
- `$newsId`: (edit only) document ID

---

## Troubleshooting

### Firebase Not Initializing
- Check `.env` has valid credentials
- Check `public/js/firebase-init.js` receives `window.firebaseConfig`
- Open DevTools Console → check for Firebase errors

### Timestamps Show as Objects
- Ensure `composer install` was run (normalizer needs Google Cloud SDK)
- Check that `normalizeTimestamp()` is being called in Firebase.php

### Delete Buttons Don't Work
- Check that `public/js/admin.js` is loaded (admin layout loads it)
- Check browser console for `ReferenceError: deleteNews is not defined`

### Can't Save Articles
- Ensure you're logged in as an admin
- Check Firestore rules allow `create` for your user's role
- Check browser console for Firebase permission errors

---

## File Changes Summary

| File | Change | Type |
|------|--------|------|
| `src/Router.php` | 404 path fix | Bug fix |
| `src/Firebase.php` | Timestamp normalizer + update() fix | Bug fix |
| `src/Controllers/Admin/NewsAdminController.php` | Safe error handling | Bug fix |
| `public/js/firebase-init.js` | Use window.firebaseConfig | Bug fix |
| `public/js/admin.js` | Delete handler functions | New |
| `templates/layouts/base.phtml` | Inject firebaseConfig block | Bug fix |
| `templates/layouts/admin.phtml` | Inject firebaseConfig + load admin.js | Bug fix |
| `templates/admin/blog/index.phtml` | Design system restyle | Bug fix |
| `templates/admin/blog/create.phtml` | Design system restyle | Bug fix |
| `templates/admin/blog/edit.phtml` | Design system restyle | Bug fix |

---

## Next Steps (For Future Milestones)

- [ ] **PBI-06**: Newsletter Signup — Complete Mailchimp integration
- [ ] **PBI-07**: Admin Dashboard — Enhanced analytics and quick actions
- [ ] **PBI-08**: Global Search — Full-text search across news, blog, products
- [ ] Add markdown rendering for article content
- [ ] Implement image upload to Firebase Storage
- [ ] Add featured article highlighting
- [ ] Category/tag filtering on listing pages
- [ ] Comment system (if needed)
- [ ] Content scheduling (publish at future date)
