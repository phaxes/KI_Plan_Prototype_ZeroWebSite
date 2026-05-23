# Newsletter System — Complete Flow Documentation

Three working endpoints with clear UX flow for different user scenarios.

---

## Endpoint Overview

| Endpoint | Type | Access | Purpose |
|----------|------|--------|---------|
| `GET /newsletter` | Page | Public | Dedicated landing page with signup + content preview |
| `POST /api/newsletter/subscribe` | API | Public | Subscribe new user (email + optional name) |
| `GET /profile/newsletter` | Page | Auth | Manage preferences (categories) for logged-in users |
| `POST /api/profile/newsletter-preference` | API | Auth | Save preference choices to Firestore + SESSION |

---

## User Flows

### 1. Anonymous User — Quick Subscribe (Home Page)

```
Home Page → Newsletter Section
  ↓
Email input + "Abonnieren" button
  ↓
[Success] Notification appears + form reset
  ↓
Subscriber saved to Firestore (generic subscription)
Welcome email sent via Resend
```

**Endpoint used:** `POST /api/newsletter/subscribe`

**Session impact:** None (anonymous user)

---

### 2. Anonymous User — Explore & Subscribe (Landing Page)

```
Home Page Newsletter Section
  ↓
Click "Mehr Infos & Einstellungen" link
  ↓
Navigate to GET /newsletter
  ↓
See hero form + news/blog content preview
  ↓
Email + optional name input
  ↓
Click "Jetzt abonnieren"
  ↓
POST /api/newsletter/subscribe
  ↓
Success: show thank you + auto-close form
```

**Endpoints used:** `GET /newsletter`, `POST /api/newsletter/subscribe`

**Session impact:** None (anonymous user)

---

### 3. Logged-in User — Set Preferences

```
User logged in → Profile page
  ↓
Click "Newsletter-Einstellungen" link
  ↓
GET /profile/newsletter
  ↓
See form with:
  - Subscribed checkbox
  - Category checkboxes (News, Blog, Produkte)
  ↓
Select preferences
  ↓
Click "Einstellungen speichern"
  ↓
POST /api/profile/newsletter-preference
  ↓
Saved to:
  1. Firestore (userNewsletterPreferences collection)
  2. SESSION (for immediate use)
  ↓
Success notification + instant display
```

**Endpoints used:** `GET /profile/newsletter`, `POST /api/profile/newsletter-preference`

**Session impact:**
- `$_SESSION['newsletterSubscribed']` = true/false
- `$_SESSION['newsletterCategories']` = ['news', 'blog', 'products', ...]

---

## Firestore Collections

### 1. `subscribers` — All Subscribers (PBI-06)

```json
Document ID: md5(email)
Fields:
  - email: "user@example.com"
  - name: "John Doe" (optional)
  - source: "website"
  - active: true/false
  - subscribedAt: <DateTime>
```

Used by: Home page form, `/newsletter` page, public subscribe API

---

### 2. `userNewsletterPreferences` — User Preferences (Auth)

```json
Document ID: userId (from auth)
Fields:
  - subscribed: true/false
  - categories: ["news", "blog", "products"]
  - updatedAt: <DateTime>
```

Used by: `/profile/newsletter`, preference management for logged-in users

---

## Data Flow

### Subscribe Endpoint (`POST /api/newsletter/subscribe`)

```
Request: { email, name? }
  ↓
1. Validate email (filter_var FILTER_VALIDATE_EMAIL)
2. Create Firestore subscriber doc (PATCH /subscribers/{md5(email)})
3. Send welcome email via Resend (fire-and-forget)
  ↓
Response: { success: true, message: "Danke für dein Abonnement!" }
```

**Error cases:**
- Invalid email → 400 + "Invalid email address"
- Firebase unavailable → 500 + error message

**Note:** Email is idempotent (second signup = update, no duplicate)

---

### Profile Preferences Endpoint (`POST /api/profile/newsletter-preference`)

```
Request: { subscribed: bool, categories: string[] }
  ↓
1. Validate user auth (401 if not logged in)
2. Save to Firestore userNewsletterPreferences/{userId}
3. **NEW:** Save to SESSION immediately
  ↓
Session updated:
  - $_SESSION['newsletterSubscribed'] = true/false
  - $_SESSION['newsletterCategories'] = ['news', ...]
  ↓
Response: { success: true, message: "Newsletter preferences updated" }
```

**Error cases:**
- Not authenticated → 401
- Firestore unavailable → 500

---

## UI/UX Integration

### Home Page (`/`)

1. **Newsletter Section (before footer)**
   - Quick subscribe form: email input + "Abonnieren" button
   - CTA link: "Mehr Infos & Einstellungen" → `/newsletter`
   - JavaScript: POST form data to `/api/newsletter/subscribe`

### Newsletter Landing Page (`/newsletter`)

1. **Hero Section**
   - Heading: "Newsletter abonnieren"
   - Subtext: benefit statement
   - Form: email + optional name
   - Success state: thank you message + "Zur Website" CTA

2. **Content Preview Section**
   - Latest 3 news posts
   - Latest 3 blog posts
   - Cards show: thumbnail, title, excerpt, link

3. **JavaScript**
   - Form submit → debounced validation
   - POST `/api/newsletter/subscribe`
   - Show success/error notifications

### Profile Newsletter Page (`/profile/newsletter`)

1. **Auth Required**
   - Redirects to `/login` if not logged in

2. **Subscription Toggle**
   - Checkbox: "Newsletter abonnieren"
   - Toggles category section visibility

3. **Category Filters**
   - Checkboxes: News, Blog, Produkte
   - Only shown if subscribed = true
   - Minimum 1 required if subscribed

4. **Save Button**
   - POST to `/api/profile/newsletter-preference`
   - Shows success notification
   - **NEW:** Session updated immediately (no Firestore latency)

---

## Key Features

### 1. Idempotent Signup
- Multiple signups with same email = update, not duplicate
- No validation errors, just overwrites (fire-and-forget pattern)

### 2. Session Persistence
- User preferences saved to SESSION after Firestore save
- Instant display without Firestore latency
- Session expires with browser session (configurable)

### 3. Welcome Email (Fire-and-Forget)
- Email failure doesn't block signup
- Resend errors only logged, not returned to API
- Graceful degradation if Resend unavailable

### 4. Role-Based Preferences
- **Anonymous:** Subscribe to generic list (no categories)
- **Logged-in:** Can select categories (News, Blog, Products)

---

## Testing

### Test 1: Anonymous Subscribe
```bash
curl -X POST http://localhost:8000/api/newsletter/subscribe \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","name":"Test"}'

Expected: 200 { success: true, ... }
```

### Test 2: Check Firestore
- Firestore Console → `subscribers` collection
- Document ID = md5(email)
- Fields populated: email, name, source, subscribedAt, active

### Test 3: User Preferences
1. Log in as user
2. Go to `/profile/newsletter`
3. Select categories + click save
4. Check SESSION in browser dev tools
5. Session contains: `newsletterSubscribed`, `newsletterCategories`

---

## Deployment Notes

### Render.com (Production)

1. **Environment Variables Required:**
   - `FIREBASE_PROJECT_ID`
   - `FIREBASE_SERVICE_ACCOUNT_JSON`
   - `RESEND_API_KEY`
   - `RESEND_FROM_EMAIL`
   - `RESEND_FROM_NAME`

2. **Session Handling:**
   - Ephemeral filesystem; session files don't persist across dyno restarts
   - Use database-backed sessions for production (if needed)
   - Current: in-memory sessions, acceptable for MVP

3. **Firestore:**
   - Two collections used: `subscribers`, `userNewsletterPreferences`
   - Ensure both collections exist in Firestore

---

## Future Enhancements

- [ ] Unsubscribe link in welcome email
- [ ] Double opt-in (confirmation email)
- [ ] Scheduled newsletter campaigns (via Resend API)
- [ ] Activity tracking (open rates, click tracking)
- [ ] Preference management UI for newsletter content
- [ ] Segment targeting (by category, geography, etc.)

---

## Related

- [[PBI-06]](PBI-06-NEWSLETTER.md) — Newsletter signup system implementation
- Auth flow: See `src/Auth.php`, `src/Middleware/AuthMiddleware.php`
- Session management: `index.php` (lines 14-37)
