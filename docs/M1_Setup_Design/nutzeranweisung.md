# Milestone M1: Nutzeranweisung - Installation & Setup

Diese Anweisung beschreibt, wie du das Projekt lokal einrichtest und aufsetzt.

## Voraussetzungen

- **PHP 8.2+** (mit `mod_rewrite` für Apache)
- **Composer** (PHP-Dependency-Manager)
- **Node.js 20+** (für npm und Tailwind CSS Build)
- **npm** (meist mit Node.js enthalten)
- **Git** (um das Projekt zu klonen)

## Schritt-für-Schritt Installation

### 1. Projekt klonen
```bash
git clone <REPOSITORY_URL>
cd plan-prototyp-produktion-v1-0-phaxes
```

### 2. PHP-Abhängigkeiten installieren
```bash
composer install
```
→ Erzeugt `vendor/` Verzeichnis mit Paketen

### 3. Node.js-Abhängigkeiten installieren
```bash
npm install
```
→ Erzeugt `node_modules/` Verzeichnis mit Tailwind CSS & PostCSS

### 4. Umgebungsvariablen konfigurieren
```bash
cp .env.example .env
```
→ Bearbeite `.env` mit deinen Werten (Firebase, Stripe Keys, etc.)

**Wichtig**: `.env` enthält sensitive Daten und sollte NICHT ins Repo committed werden.
`.gitignore` ignoriert `.env` automatisch.

### 5. Tailwind CSS kompilieren
```bash
npm run build
```
→ Erzeugt `public/css/app.css` aus `public/css/input.css`

Für Development (watch-mode):
```bash
npm run dev
```
→ Tailwind beobachtet Änderungen und re-kompiliert automatisch

### 6. Lokal testen
```bash
php -S localhost:8000
```
→ Starte PHP's Built-in Server auf http://localhost:8000

**Alternative mit npm**:
```bash
npm run serve
```
→ Startet auch PHP-Server

### 7. Browser aufrufen
- Homepage: http://localhost:8000
- Admin: http://localhost:8000/admin (noch nicht auth-protected in M1)
- 404-Test: http://localhost:8000/nicht-existente-seite

## Verzeichnis-Struktur

```
project-root/
├── index.php              ← Front Controller (entry point)
├── src/
│   ├── Config.php         ← .env loader
│   ├── Router.php         ← URL router
│   └── Controllers/       ← Page handlers
├── templates/
│   ├── layouts/           ← base.php, admin.php
│   ├── partials/          ← header, footer, nav
│   ├── home/              ← Landing page
│   └── errors/            ← 404
├── public/
│   ├── css/
│   │   ├── input.css      ← Tailwind source
│   │   └── app.css        ← Compiled CSS (generiert)
│   ├── js/                ← app.js, firebase-init.js
│   └── images/
├── vendor/                ← PHP packages (Composer)
├── node_modules/          ← JS packages (npm)
├── composer.json
├── package.json
├── tailwind.config.js
└── .env                   ← Geheime Keys (nicht ins Repo!)
```

## Firebase Setup

Damit die App funktioniert, brauchst du ein Firebase-Projekt:

### 1. Firebase-Projekt erstellen
- Gehe zu https://console.firebase.google.com
- Klick "Add project" → Projekt-Name eingeben → "Create"

### 2. Firebase-Keys kopieren
- Gehe zu Project Settings (Zahnrad-Icon oben)
- Scrolle zu "Your apps"
- Füge eine Web-App hinzu
- Kopiere die Config:
```javascript
{
  apiKey: "YOUR_API_KEY",
  authDomain: "your-project.firebaseapp.com",
  projectId: "your-project-id",
  storageBucket: "your-project.appspot.com",
  messagingSenderId: "YOUR_SENDER_ID",
  appId: "YOUR_APP_ID"
}
```

### 3. In public/js/firebase-init.js eintragen
Ersetze `firebaseConfig` mit deinen echten Werten:
```javascript
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",  // ← hier
    authDomain: "YOUR_AUTH_DOMAIN",  // ← hier
    ...
};
```

## Stripe Setup

Für Test-Zahlungen (später in M4):

1. Gehe zu https://dashboard.stripe.com
2. Logge dich ein (oder registriere dich)
3. Kopiere **Test Keys** von der Seite
   - Publishable Key: `pk_test_...`
   - Secret Key: `sk_test_...`
4. Eintragen in `.env`:
```
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
```

## Mailchimp Setup

Für Newsletter-Integration (später in M5):

1. Gehe zu https://mailchimp.com
2. Logge dich ein (oder registriere dich)
3. Erstelle eine neue "Audience" (Liste)
4. Gehe zu Account Settings → API Keys → Generiere einen Key
5. Kopiere API Key und Server Prefix (z.B. `us1`)
6. Gehe zu Audiences → Wähle deine Liste → Settings → Kopiere List ID
7. Eintragen in `.env`:
```
MAILCHIMP_API_KEY=your-api-key
MAILCHIMP_SERVER_PREFIX=us1
MAILCHIMP_LIST_ID=your-list-id
```

## Troubleshooting

### "composer: command not found"
- Installiere Composer: https://getcomposer.org/download
- Oder nutze `php composer.phar install`

### "npm: command not found"
- Installiere Node.js: https://nodejs.org
- npm ist darin enthalten

### CSS ist nicht kompiliert
- Starte `npm run build` manuell
- Oder `npm run dev` für watch-mode
- Prüfe ob `public/css/app.css` existiert

### 404 Fehler auf alle Routes außer `/`
- Prüfe ob `.htaccess` existiert in der Root
- Prüfe ob Apache `mod_rewrite` aktiviert ist
- Nutze stattdessen `php -S localhost:8000`

### "FIREBASE_PROJECT_ID not found"
- Prüfe ob `.env` existiert (von `.env.example` kopieren)
- Prüfe ob Werte gesetzt sind (nicht leer)
- Prüfe ob die Keys korrekt sind (von Firebase Console)

### CSS/JS wird nicht geladen
- Prüfe Browser-DevTools → Network Tab
- Prüfe ob Pfade richtig sind (`/public/css/app.css`)
- Lösche Browser-Cache (Ctrl+Shift+Delete)
- Prüfe file permissions: `chmod 644 public/css/app.css`

## Nächste Schritte

Nach erfolgreichem Setup:
1. **M2**: Firestore-Integration für News/Blog
2. **M3**: Firebase Auth für User-Login
3. **M4**: Shop und Stripe-Integration
4. **M5**: Mailchimp Newsletter und Cross-Selling

---

**Fragen?** Siehe `docs/M1_Setup_Design/system_architektur.md` für technische Details.
