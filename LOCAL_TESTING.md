# Local Testing Guide

## Schritt 1: Environment Setup

### 1a Dependencies installieren
```bash
cd /mnt/c/KI/KI_Plan_Prototype_ZeroWebSite/plan-prototyp-produktion-v1-0-phaxes

# PHP dependencies
composer install

# Node dependencies
npm install
```

### 1b Tailwind CSS kompilieren
```bash
npm run build
# oder für watch-mode:
npm run dev
```

### 1c .env konfigurieren
```bash
cp .env.example .env

# Edit .env mit TEST KEYS:
FIREBASE_PROJECT_ID=test-project
FIREBASE_SERVICE_ACCOUNT_JSON=/path/to/serviceAccountKey.json
FIREBASE_API_KEY=AIzaSyDummyKeyTest123456789
STRIPE_SECRET_KEY=sk_test_dummy123456789
STRIPE_PUBLISHABLE_KEY=pk_test_dummy123456789
MAILCHIMP_API_KEY=dummykey-us1
MAILCHIMP_LIST_ID=dummy123
```

### 1d Dev Server starten
```bash
# Terminal 1: PHP Server
php -S localhost:8000

# Terminal 2: Tailwind watch (optional)
npm run dev
```

---

## Testing Checklist

### Phase 1: Routing & Pages

- [ ] `http://localhost:8000/` → Landing Page lädt
  - [ ] Hero Section sichtbar
  - [ ] Features Cards vorhanden (3x)
  - [ ] News/Blog/Product Preview leer (erwartet)
  - [ ] Footer mit Newsletter Form

- [ ] `http://localhost:8000/news` → 404 or empty list
- [ ] `http://localhost:8000/blog` → 404 or empty list
- [ ] `http://localhost:8000/shop` → 404 or empty list
- [ ] `http://localhost:8000/login` → Login Form
- [ ] `http://localhost:8000/register` → Register Form
- [ ] `http://localhost:8000/cart` → Empty Cart
- [ ] `http://localhost:8000/checkout` → Checkout Form (needs cart items)
- [ ] `http://localhost:8000/admin` → 403 or redirect /login

### Phase 2: Authentication (M3)

**Note:** Benötigt Firebase project + Service Account JSON

- [ ] Register: `http://localhost:8000/register`
  - [ ] Form fields: Name, Email, Password, Confirm Password
  - [ ] Submit → Error "Firebase config incomplete" (expected)
  - [ ] **With real Firebase:**
    - [ ] Submit → User created in Firebase Auth
    - [ ] User doc created in Firestore
    - [ ] Redirect to /profile
    - [ ] Session variables set

- [ ] Login: `http://localhost:8000/login`
  - [ ] Form fields: Email, Password
  - [ ] Submit → Error (without Firebase)
  - [ ] **With real Firebase:**
    - [ ] Submit → Session established
    - [ ] Redirect to /profile
    - [ ] Profile shows email + logout button

- [ ] Protected Pages
  - [ ] Visit `/profile` without auth → Redirect /login
  - [ ] Login → Visit `/profile` → User data shown
  - [ ] Logout → Session cleared
  - [ ] Visit `/admin` without auth → 403
  - [ ] Login (non-admin) → Visit `/admin` → 403
  - [ ] Set isAdmin=true in Firestore
  - [ ] Relogin → `/admin` → Dashboard loads

### Phase 3: CMS (M2)

**Note:** Benötigt Firestore connection

- [ ] `/admin/news` (as admin)
  - [ ] Table loads (even if empty)
  - [ ] Button "+ Neue News" visible

- [ ] `/admin/news/create`
  - [ ] Form: Title, Content, Image URL, Tags, Publish checkbox
  - [ ] Submit → Error (without Firestore)
  - [ ] **With Firestore:**
    - [ ] Submit → Document in posts collection
    - [ ] Redirect /admin/news
    - [ ] News appears in table

- [ ] `/news` (public)
  - [ ] Submit → Article appears in list
  - [ ] Click article → `/news/{id}` → Detail page
  - [ ] Shows: Title, Author, Date, Tags, Content

- [ ] Blog (same tests as News)
  - [ ] `/admin/blog/create`
  - [ ] `/admin/blog`
  - [ ] `/blog`
  - [ ] `/blog/{id}`

### Phase 4: Shop (M4)

**Note:** Benötigt Products in Firestore

- [ ] `/admin/products/create` (as admin)
  - [ ] Form: Name, Price, Category, Description, Stock, Image, Active
  - [ ] Submit → Product in Firestore

- [ ] `/shop` (public)
  - [ ] Products listing
  - [ ] Click product → `/shop/{id}`
  - [ ] Shows: Name, Description, Price, Stock
  - [ ] Button "In den Warenkorb"

- [ ] Cart (localStorage)
  - [ ] Click "In den Warenkorb" → Cart updated
  - [ ] Cart badge shows count (top right)
  - [ ] `/cart` → Items listed
  - [ ] Remove item → Cart updated
  - [ ] localStorage has cart data (DevTools → Application → Local Storage)

- [ ] Checkout (M4)
  - [ ] Add items to cart
  - [ ] `/checkout`
  - [ ] Form: Email, Stripe Card Element
  - [ ] Card Element mounts (blue box visible)
  - [ ] Enter test card: 4242 4242 4242 4242
  - [ ] Submit → Error (Stripe test keys needed)
  - [ ] **With Stripe keys:**
    - [ ] Submit → Payment processing
    - [ ] Firestore order created
    - [ ] Redirect `/checkout/success?orderId=...`
    - [ ] Order confirmation page

### Phase 5: Newsletter (M5)

- [ ] Footer Newsletter Form
  - [ ] Enter: Email + Name
  - [ ] Submit → API call `/api/newsletter/subscribe`
  - [ ] Error (Mailchimp keys needed)
  - [ ] **With Mailchimp keys:**
    - [ ] Submit → Added to Mailchimp List
    - [ ] Success message

- [ ] `/admin/subscribers` (as admin)
  - [ ] List of subscribers
  - [ ] **With Mailchimp:**
    - [ ] Shows real subscribers

- [ ] Auto-emails on publish
  - [ ] Publish new News → Email trigger
  - [ ] **With Mailchimp:**
    - [ ] Email sent to subscribers with tag "news"

### Phase 6: Admin Backend (M2, M3, M4)

- [ ] `/admin` (Dashboard)
  - [ ] Shows counts: News, Blog, Products
  - [ ] Quick links to manage

- [ ] `/admin/news`, `/admin/blog`, `/admin/products`
  - [ ] Tables load
  - [ ] Create, Edit, Delete buttons work

### Phase 7: UI/UX

- [ ] Responsive Design (mobile)
  - [ ] Open DevTools → Toggle mobile view
  - [ ] Menu collapses on mobile
  - [ ] Cards stack vertically
  - [ ] Buttons clickable

- [ ] Navigation
  - [ ] Header links work
  - [ ] Mobile menu toggle works
  - [ ] Footer links work

- [ ] Styling
  - [ ] Tailwind CSS loaded (no unstyled page)
  - [ ] Colors: Primary (blue), Secondary (green), Accent (amber)
  - [ ] Spacing, shadows, rounded corners visible

- [ ] Error Messages
  - [ ] Form errors show in red
  - [ ] Toast notifications appear (top right)
  - [ ] Dismiss after 4s

### Phase 8: Browser Console

- [ ] No JavaScript errors
  - [ ] Open DevTools → Console tab
  - [ ] No red errors (warnings OK)
  - [ ] Firebase JS SDK loads (if keys configured)
  - [ ] Stripe JS loads (if keys configured)

- [ ] Network requests
  - [ ] DevTools → Network tab
  - [ ] CSS files load (200 OK)
  - [ ] JS files load (200 OK)
  - [ ] API calls return 200/201/200

---

## Testing Matrix

| Feature | Without Firebase | With Firebase | With Stripe | With Mailchimp |
|---------|-----------------|--------------|------------|----------------|
| **M1** Landing | ✅ | ✅ | ✅ | ✅ |
| **M2** CMS | ❌ Error | ✅ Full | ✅ | ✅ |
| **M3** Auth | ❌ Error | ✅ Full | ✅ | ✅ |
| **M4** Shop | ❌ Error | ✅ Full | ❌ Error | ✅ |
| **M4** Checkout | ❌ Error | ✅ Partial | ✅ Full | ✅ |
| **M5** Newsletter | ❌ Error | ✅ Partial | ✅ | ❌ Error |
| **M5** Newsletter | ❌ Error | ✅ | ✅ | ✅ Full |

---

## Common Issues & Fixes

### Issue: "Tailwind CSS not loaded"
**Solution:**
```bash
npm run build
# Check: public/css/app.css should have content
```

### Issue: "404 on all routes"
**Solution:**
- Prüfe: `.htaccess` existiert in root
- Prüfe: Apache mod_rewrite enabled
- Use: `php -S localhost:8000` (built-in server, no mod_rewrite needed)

### Issue: "Firebase services unavailable"
**Solution:**
```bash
# Edit .env
FIREBASE_SERVICE_ACCOUNT_JSON=WRONG_PATH

# Fix: Use absolute path
FIREBASE_SERVICE_ACCOUNT_JSON=/Users/name/path/to/serviceAccountKey.json
```

### Issue: "Stripe card element not mounting"
**Solution:**
```bash
# Check: STRIPE_PUBLISHABLE_KEY in .env
# Check: public/js/stripe.js loaded (DevTools → Network)
# Check: <div id="card-element"> in template
```

### Issue: "Firestore permission denied"
**Solution:**
- Prüfe: firestore.rules deployed
- Prüfe: Authenticated user exists
- Prüfe: Data structure matches rules

---

## Quick Test Commands

```bash
# Full local setup
composer install && npm install && npm run build

# Start dev server
php -S localhost:8000

# In new terminal, watch Tailwind
npm run dev

# Test specific endpoints
curl http://localhost:8000/
curl http://localhost:8000/api/cart
curl -X POST http://localhost:8000/api/newsletter/subscribe -d '{...}'
```

---

## Performance Checklist

- [ ] Page load time < 3s
- [ ] CSS file size < 100 KB
- [ ] JS file size < 500 KB
- [ ] No console errors
- [ ] No memory leaks (DevTools → Memory)
- [ ] Smooth animations (no jank)

---

## Sign-off Checklist

When all tests pass:

- [ ] M1: Routing, Landing page ✅
- [ ] M2: CMS (create, read, list, delete) ✅
- [ ] M3: Auth (register, login, logout) ✅
- [ ] M4: Shop (products, cart, checkout) ✅
- [ ] M5: Newsletter (signup, auto-emails) ✅
- [ ] No console errors ✅
- [ ] Responsive design ✅
- [ ] All features working ✅

→ **Ready to deploy to Render.com!** 🚀
