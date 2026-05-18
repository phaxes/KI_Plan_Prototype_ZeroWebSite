# Milestone M1: System & Architektur Leitfaden

## Architektur-Übersicht

Das Projekt nutzt eine **PHP Backend + Firebase NoSQL + Client-Side JS** Architektur:

```
┌─────────────────────────────────────────────────────────────┐
│                     User Browser                             │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  HTML Templates + Tailwind CSS                        │  │
│  │  JavaScript (Firebase Auth, Cart, Notifications)      │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                           ↕ HTTP
┌─────────────────────────────────────────────────────────────┐
│              PHP Backend (Render.com)                        │
│                                                               │
│  Front Controller (index.php)                                │
│         ↓                                                     │
│  Router → Controller (HomeController, AuthController, ...)   │
│         ↓                                                     │
│  Template Rendering                                          │
│                                                               │
│  (Later) Firestore Client (kreait/firebase-php)             │
│  (Later) Stripe PHP SDK                                     │
│  (Later) Mailchimp PHP SDK                                  │
└─────────────────────────────────────────────────────────────┘
                           ↕ API/SDK
┌─────────────────────────────────────────────────────────────┐
│              External Services                               │
│  • Firebase Firestore (Database)                            │
│  • Firebase Auth (Authentication)                           │
│  • Stripe (Payments)                                        │
│  • Mailchimp (Newsletter)                                   │
└─────────────────────────────────────────────────────────────┘
```

## Request-Response Cycle

### Beispiel: User besucht Homepage `/`

1. **Browser macht HTTP GET /request**
   - Browser ↔ Apache (Render.com)
   - `.htaccess` rewrites zu `index.php?_route=/`

2. **Front Controller (index.php)**
   ```php
   require_once 'vendor/autoload.php';
   Config::load();           // ← Lade .env
   $router = new Router();   // ← Erstelle Router
   $router->dispatch();      // ← Route zu Controller
   ```

3. **Router matched Route**
   - Pattern: `GET /` → Handler: `HomeController@index`

4. **Controller wird aufgerufen**
   ```php
   // src/Controllers/HomeController.php
   public function index($params, $post, $get) {
       $latestNews = [];  // ← Später: aus Firestore
       require 'templates/home/index.php';
   }
   ```

5. **Template wird gerendert**
   - `templates/home/index.php` rendert HTML
   - Layout (`templates/layouts/base.php`) umhüllt den Content

6. **HTML wird an Browser gesendet**
   - Browser lädt CSS + JavaScript
   - Firebase JS SDK initialisiert
   - JavaScript event listeners werden gesetzt

## Komponenten im Detail

### 1. Router (src/Router.php)

Konvertiert URLs zu Controller-Aufrufen:

```php
$router = new Router();
$router->get('/', 'HomeController@index');
$router->post('/api/cart/add', 'CartController@add');
$router->dispatch();  // ← Findet passende Route
```

**URL-Muster mit Parametern**:
```php
$router->get('/blog/{id}', 'BlogController@show');
// GET /blog/123 → params = ['id' => '123']
```

### 2. Controller-Pattern

Jeder Controller ist eine PHP-Klasse in `src/Controllers/`:

```php
namespace App\Controllers;

class HomeController {
    public function index($params = [], $post = [], $get = []) {
        $data = [];
        require __DIR__ . '/../../templates/home/index.php';
    }
}
```

**Namespacing**: `App\Controllers\` (auto-loaded via Composer PSR-4)

### 3. Template-Rendering

Templates sind PHP-Dateien in `templates/`:

```php
<?php
$title = 'Home';
ob_start();  // ← Puffer Content
?>

<!-- HTML Content -->
<h1><?= htmlspecialchars($title) ?></h1>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';  // ← Umhüllen mit Layout
?>
```

**Layout-System**: 
- `base.php` — Standard-Layout (Header, Footer, CSS/JS)
- `admin.php` — Admin-Layout (Sidebar, Top-Bar)

### 4. Tailwind CSS Build-Pipeline

**Development**:
```bash
npm run dev
# Beobachtet *.php in templates/ und src/
# Bei Änderungen: Tailwind neucompiliert zu public/css/app.css
```

**Production** (Docker):
```dockerfile
RUN npm install && npm run build
# Minifiziert CSS für optimale Größe
```

**Workflow**:
```
templates/**/*.php
    ↓
tailwind.config.js (content-paths)
    ↓
Tailwind CSS Parser
    ↓
public/css/app.css (nur genutzte Klassen)
```

### 5. Firebase JS SDK Integration

In `public/js/firebase-init.js`:

```javascript
firebase.initializeApp(firebaseConfig);
const auth = firebase.auth();
const db = firebase.firestore();

auth.onAuthStateChanged((user) => {
    if (user) {
        user.getIdToken().then(token => {
            // ← Sende Token zu PHP für Session-Verifikation (M3)
            fetch('/auth/verify', {
                method: 'POST',
                body: JSON.stringify({ idToken: token })
            });
        });
    }
});
```

**In M2/M3 wird hinzugefügt**:
- Firestore Document-Reading
- Real-time Listener
- Auth State Management
- Token-zu-Cookie-Conversion

## Dependency Injection (Composer)

`composer.json` definiert externe Pakete:

```json
{
  "require": {
    "kreait/firebase-php": "^7.4",
    "stripe/stripe-php": "^13.0",
    "drewm/mailchimp-api": "^3.0"
  }
}
```

`composer install` erzeugt `vendor/autoload.php`:
```php
require 'vendor/autoload.php';
// ← Alle Classes sind auto-loadable
$firebase = new Kreait\Firebase\Factory();
```

## Configuration Management (src/Config.php)

Lädt Umgebungsvariablen aus `.env`:

```php
Config::load();  // ← Liest .env in /vendor/config
$projectId = Config::get('FIREBASE_PROJECT_ID');  // ← Nutze später in Firestore-SDK
```

**Sicherheit**: 
- `.env` ist in `.gitignore` (nicht commits)
- Sensitive Keys (Stripe, Firebase) bleiben lokal/in Umgebung
- In Production: Keys via Render.com Environment Variables

## Error Handling

### 404-Fehler
Wenn Router keine passende Route findet:
```php
// In Router::dispatch()
http_response_code(404);
require __DIR__ . '/../templates/errors/404.php';
```

### Exceptions (später in M2+)
Middleware können Pre-/Post-Processing machen:
```php
// src/Middleware/AuthMiddleware.php
class AuthMiddleware {
    public static function check() {
        if (!isset($_SESSION['userId'])) {
            header('Location: /login');
            exit;
        }
    }
}
```

## Session Management

PHP-Sessions werden in `index.php` aktiviert:
```php
session_start();
```

**Session-Daten** (werden in M3 mit Firebase Auth gefüllt):
```php
$_SESSION['userId']  // ← Firebase UID
$_SESSION['isAdmin'] // ← Flag aus Firestore
$_SESSION['email']   // ← User-Email
```

## Client-Side State: localStorage

Für Warenkorb (M4):
```javascript
// Cart als JSON in localStorage
localStorage.setItem('cart', JSON.stringify([
    { id: 'prod1', quantity: 2 },
    { id: 'prod2', quantity: 1 }
]));

// Abrufen
const cart = JSON.parse(localStorage.getItem('cart'));
```

## Security Concerns (M1 → später)

### Aktuell sicher:
- ✅ HTTPS via Render.com (Auto-SSL)
- ✅ CSRF: Wird in M3 mit Token-Pattern implementiert
- ✅ XSS: Templates nutzen `htmlspecialchars()` zur Escaping

### Wird implementiert:
- 🔄 Auth: Firebase Auth + server-side Token-Verify (M3)
- 🔄 Admin-Schutz: isAdmin-Flag + AuthMiddleware (M2)
- 🔄 Firestore Rules: Dirty-Dozen-Rules (M3)
- 🔄 Input-Validation: In Controllers + Firestore (M2+)

## Performance Considerations

### M1 (jetzt):
- Landing Page ist statisch → sehr schnell
- CSS wird nur einmal gebuild → Cache-friendly
- Keine DB-Queries (noch)

### M2+ (Zukunft):
- Firestore Indizes für häufige Queries
- Template-Caching für komplexe Views
- Image Optimization (CloudStorage)

## Deployment zu Render.com

```bash
# 1. Dockerfile wird gelesen
# 2. Docker image gebaut (PHP 8.2 + Apache + Node.js)
# 3. npm install && npm run build (CSS kompiliert)
# 4. composer install (PHP packages)
# 5. Apache gestartet
# 6. App live unter https://project-name.onrender.com
```

**Environment Variables** in Render.com UI eingeben:
- `FIREBASE_PROJECT_ID`
- `STRIPE_SECRET_KEY`
- etc.

## Directory Permissions

In Production (Docker):
```dockerfile
RUN chown -R www-data:www-data /var/www/html
```
→ Apache-User kann alle Dateien lesen/schreiben

Lokal: Meist nicht nötig (dein User hat Permissions)

---

## Zusammenfassung der Datenflüsse

| Flow | Beschreibung |
|------|-------------|
| **Page Request** | Browser → Apache (mod_rewrite) → PHP Router → Controller → Template |
| **API Call** | JavaScript → `/api/endpoint` POST → Controller → JSON Response |
| **Firebase Init** | page load → firebase-init.js → SDK initialisiert → auth.onAuthStateChanged |
| **Firestore Query** | (M2+) Controller → Firebase Admin SDK → Firestore → Data an Template |
| **User Auth** | (M3+) Firebase Auth JS → idToken → POST /auth/verify → PHP Session |

---

**Nächster Step**: M2 - Firestore-Integration für Daten-Persistierung
