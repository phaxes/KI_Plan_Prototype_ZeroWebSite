# Milestone M1: Setup & Design - Dokumentation

**Zeitraum**: Tag 1-2  
**Status**: Abgeschlossen  
**Änderungsdatum**: 2026-05-18

## Übersicht

M1 etabliert das Projekt-Fundament: Scaffolding, Routing, Base-Templates und die Landing Page.
Das Projekt nutzt PHP 8.2 als Backend-Runtime mit einem einfachen Front-Controller-Pattern,
Tailwind CSS für das Styling und Firebase JS SDK für Client-seitige Features.

## Implementierte Komponenten

### 1. Projekt-Struktur
- **composer.json**: PHP-Abhängigkeiten (kreait/firebase-php, stripe/stripe-php, drewm/mailchimp-api)
- **package.json**: Node.js-Abhängigkeiten (Tailwind CSS, PostCSS, Autoprefixer)
- **tailwind.config.js**: Tailwind-Konfiguration mit Custom-Farben (primary, secondary, accent)
- **postcss.config.js**: PostCSS-Konfiguration für Tailwind-Build

### 2. PHP Framework
- **index.php**: Front-Controller (entrypoint)
- **src/Config.php**: Umgebungsvariablen-Verwaltung (lädt .env)
- **src/Router.php**: URL-Routing (GET/POST auf Controller-Klassen)
- **src/Controllers/HomeController.php**: Home-Page-Handler

### 3. Templates
- **templates/layouts/base.php**: Basis-Layout mit Meta-Tags, Firebase/Stripe JS, SimpleMDE CDN
- **templates/layouts/admin.php**: Admin-Seiten-Layout mit Sidebar und Top-Bar
- **templates/partials/header.php**: Navigation mit Mobile-Menü und Warenkrob-Badge
- **templates/partials/footer.php**: Footer mit Newsletter-Signup und Links
- **templates/home/index.php**: Landing Page mit Hero, Features, News/Blog/Produkt-Previews
- **templates/errors/404.php**: 404-Fehlerseite

### 4. Frontend Assets
- **public/css/input.css**: Tailwind-Input (wird via npm zu app.css kompiliert)
- **public/css/app.css**: Placeholder-CSS (wird durch Tailwind-Build ersetzt)
- **public/js/app.js**: Globales App-Modul (Cart-Management, Notifications, Price-Formatting)
- **public/js/firebase-init.js**: Firebase SDK-Initialisierung und Auth-State-Monitoring

### 5. Deployment
- **Dockerfile**: Multi-stage Docker für PHP 8.2 + Apache + Node.js (für Tailwind-Build)
- **render.yaml**: Render.com-Konfiguration (Free Tier, Environment Variables)
- **.htaccess**: Apache mod_rewrite (alles → index.php)
- **.env.example**: Vorlage für Umgebungsvariablen

## Technische Entscheidungen

### Front-Controller Pattern
Alle HTTP-Requests laufen durch `index.php`, das die URL parst und zum passenden
Controller routet. Vorteile:
- Single entry point, einfach zu debuggen
- Zentralisierte Session-/Auth-Verarbeitung
- Kein Asset-Caching-Problem

### Tailwind CSS
- Über npm installiert für Development
- Build-Befehl: `npm run dev` (watch) oder `npm run build` (production)
- Im Docker wird `npm run build` automatisch ausgeführt
- PostCSS autoprefixer für Browser-Kompatibilität

### Firebase JS SDK (nicht Admin SDK in M1)
- Firebase Auth JS SDK lädt im Browser (public-facing)
- Admin SDK kommt später in M3 (server-side Token-Verify)
- Konfiguration muss in `public/js/firebase-init.js` eingetragen werden

### Render.com Hosting
- Free Tier: App läuft auf Render-Servern
- Auto-Deploy via Git Push
- Environment-Variablen in UI konfigurierbar
- Nach 15 min Inaktivität in Standby (kalt-Start möglich)

## Firestore Schema (Vorschau, wird in M2 implementiert)

```
collections:
  - posts (type: news|blog, title, content, authorId, createdAt, published, tags)
  - products (name, description, price, imageUrl, category, stock)
  - users (displayName, email, isAdmin, createdAt, address)
  - subscribers (email, name, source, subscribedAt)
```

## Routes (implementiert)

### Frontend
- `GET /` → HomeController (Landing Page)
- `GET /news` → NewsController (später in M2)
- `GET /blog` → BlogController (später in M2)
- `GET /shop` → ShopController (später in M4)
- `GET /cart` → CartController (später in M4)
- `GET /checkout` → CheckoutController (später in M4)
- `GET /login`, `GET /register` → AuthController (später in M3)
- `GET /profile` → ProfileController (später in M3)

### Admin
- `GET /admin` → Admin\DashboardController (später in M2)
- `GET /admin/news`, `/admin/blog`, `/admin/products` → Admin-Controller (später)

## .env Variablen (müssen vor Deploy gefüllt werden)

```
FIREBASE_PROJECT_ID=
FIREBASE_SERVICE_ACCOUNT_JSON=
FIREBASE_API_KEY=
FIREBASE_AUTH_DOMAIN=
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
MAILCHIMP_API_KEY=
MAILCHIMP_SERVER_PREFIX=
MAILCHIMP_LIST_ID=
```

## Landing Page Sections

1. **Hero** — Headline + CTAs (Shop, Blog)
2. **Features** — 3 Feature-Cards (News/Blog, Shop, Newsletter)
3. **Neueste News** — 3 News-Cards (placeholder, real data später in M2)
4. **Neueste Blog-Artikel** — 3 Blog-Cards (placeholder, real data später in M2)
5. **Featured Products** — 3 Produkt-Cards (placeholder, real data später in M4)
6. **Footer** — Links, Newsletter-Signup-Form

## Known Issues / TODOs für nächste Milestones

- [ ] Tailwind CSS buildchain muss lokal laufen (`npm install && npm run dev`)
- [ ] Firebase-Konfiguration in `public/js/firebase-init.js` muss mit echten Keys gefüllt werden
- [ ] Responsive Design müssen noch mobile getestet werden
- [ ] Controllers für News, Blog, Shop, Auth sind noch Stubs
- [ ] Firestore-Datenbank muss erstellt werden (M2)

## Testing

### Lokal entwickeln
```bash
npm install && npm run dev          # Tailwind watch
php -S localhost:8000                # PHP built-in server
```
Browser: http://localhost:8000

### Mit Composer
```bash
composer install
php -S localhost:8000
```

### Docker-Test
```bash
docker build -t zero-website .
docker run -p 8000:80 \
  -e FIREBASE_PROJECT_ID=your-id \
  -e STRIPE_SECRET_KEY=sk_test_... \
  zero-website
```

## Performance Notes

- Landing Page ist statisch (keine DB-Queries in M1)
- CSS wird einmal beim Build kompiliert, dann cached
- JavaScript ist minimal (App.js ~4KB gzipped)
- Google Fonts/CDN-Ressourcen können lazy-loaded werden in nächster Phase

---

**Nächster Step (M2)**: Firestore-Integration, News/Blog CRUD, Admin-Interface
