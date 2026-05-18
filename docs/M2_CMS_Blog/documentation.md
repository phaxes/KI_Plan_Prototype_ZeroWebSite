# Milestone M2: CMS & Blog - Dokumentation

**Zeitraum**: Tag 3-4  
**Status**: Abgeschlossen  
**Änderungsdatum**: 2026-05-18

## Übersicht

M2 integriert **Firestore als zentrale Datenbank** und implementiert ein **vollständiges CMS für News und Blog**
mit Admin-Interface, CRUD-Operationen und Markdown-Editor (SimpleMDE).

## Implementierte Komponenten

### 1. Firebase Firestore Integration (src/Firebase.php)

Zentrale Klasse für alle Firestore-Operationen mit statischen Methoden:

**Posts (News/Blog)**:
- `getPosts($type, $published, $limit, $offset)` — Liste mit Filterung
- `getPostById($postId)` — Einzeldokument
- `createPost($data)` — Neuer Post
- `updatePost($postId, $data)` — Update
- `deletePost($postId)` — Löschen

**Products** (vorbereitet für M4):
- `getProducts($published, $limit, $offset)`
- `getProductById($productId)`
- `createProduct($data)`, `updateProduct()`, `deleteProduct()`

**Error Handling**: Alle Methoden catchen Exceptions und loggen zu `error_log()`

### 2. Frontend-Controller (News/Blog)

**src/Controllers/NewsController.php**:
- `index()` — News-Listing mit Pagination (9 pro Seite)
- `show()` — News-Detail-View mit vollständigem Inhalt

**src/Controllers/BlogController.php**:
- Identische Struktur wie News, aber mit `type: 'blog'`
- Separate Templates (andere Farben: Gradient secondary→accent)

**Features**:
- Pagination (prev/next)
- Tag-Anzeige
- Autor + Datum
- Rich-Content (htmlspecialchars + nl2br)
- "Zurück"-Link

### 3. Shop-Controller (vorbereitet für M4)

**src/Controllers/ShopController.php**:
- `index()` — Produkt-Listing mit Pagination
- `show()` — Produkt-Detail mit "In den Warenkorb"-Button
- localStorage-Integration (Warenkorb wird lokal gespeichert)

### 4. Admin-Interface

**src/Controllers/Admin/DashboardController.php**:
- Zeigt Statistik (News-, Blog-, Produkt-Counts)
- Quick Links zu Admin-Seiten
- Neueste Items als Vorschau

**src/Controllers/Admin/NewsAdminController.php**:
- `index()` — Tabelle aller News mit Status/Datum/Aktionen
- `create()` — Formular mit SimpleMDE Markdown-Editor
- `store()` — POST-Handler (POST zur DB)
- `edit()` — Bestehenden Post bearbeiten
- `update()` — PUT-Handler
- `delete()` — Löschen

**src/Controllers/Admin/BlogAdminController.php**:
- Identische Struktur zu News

**src/Controllers/Admin/ProductAdminController.php**:
- Produkt-CRUD (Name, Kategorie, Preis, Bestand, Beschreibung, Image)
- Status-Toggle (aktiv/inaktiv)

**src/Controllers/Admin/SubscriberController.php**:
- Placeholder für Newsletter-Abonnenten (vollständig in M5)

### 5. SimpleMDE Integration

Im `templates/layouts/base.php` und `admin.php`:
```html
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
```

Im Admin-Create/Edit:
```javascript
new SimpleMDE({
    element: document.getElementById('editor'),
    spellChecker: false,
    autoDownloadFontAwesome: false
});
```

## Firestore Datenmodell

### Collection: `posts`

```
documents:
  {
    id: "auto-generated-by-firestore",
    type: "news" | "blog",
    title: "Titel",
    content: "Markdown/HTML-Inhalt",
    published: true | false,
    tags: ["Tag1", "Tag2"],
    imageUrl: "https://...",
    authorId: "admin@email.de",
    createdAt: Timestamp,
    updatedAt: Timestamp
  }
```

### Collection: `products` (M4)

```
documents:
  {
    id: "auto-generated",
    name: "Produkt-Name",
    description: "Beschreibung",
    price: 29.99,
    category: "Kategorie",
    imageUrl: "https://...",
    stock: 10,
    active: true,
    createdAt: Timestamp,
    updatedAt: Timestamp
  }
```

## Routes (implementiert)

**Frontend**:
```
GET /news                  → News-Liste
GET /news/{id}            → News-Detail
GET /blog                 → Blog-Liste
GET /blog/{id}            → Blog-Detail
GET /shop                 → Produkt-Liste
GET /shop/{id}            → Produkt-Detail
```

**Admin**:
```
GET /admin                                → Dashboard
GET /admin/news                           → News-Liste
GET /admin/news/create                    → News-Formular
POST /admin/news/store                    → News speichern
GET /admin/news/{id}/edit                 → Edit-Formular
POST /admin/news/{id}/update              → Update
POST /admin/news/{id}/delete              → Löschen

GET /admin/blog/...                       → (identisch zu news)
GET /admin/products/...                   → (identisch zu news)
GET /admin/subscribers                    → Liste
```

## Admin-Auth (vorbereitet für M3)

Alle Admin-Controller prüfen:
```php
if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header('Location: /login');
    exit;
}
```

Diese Flag wird in M3 von Firebase gesetzt.

## Wichtige Entscheidungen

### 1. Firestore Client-Library
- Nutzt `kreait/firebase-php` (weit verbreitet, aktiv gepflegt)
- Admin SDK direkt vom Server (keine Public API nötig)
- ServiceAccount JSON muss in `FIREBASE_SERVICE_ACCOUNT_JSON` .env sein

### 2. Statische Firebase-Klasse
Alle Operationen über `Firebase::getPosts()` etc.
- Zentralisiert Fehlerbehandlung
- Error Logging für alle Operationen
- Einfache Dependency Injection

### 3. SimpleMDE statt TinyMCE/Quill
- Lightweight (~60KB)
- Markdown-Editor (nicht WYSIWYG)
- CDN verfügbar
- Built-in Preview Mode

### 4. Pagination mit Offset/Limit
- Einfach zu verstehen
- Firestore-native
- Für kleine/mittlere Datenmengen ausreichend

### 5. Markdown + nl2br statt HTML
- Sicherer (XSS-Schutz durch htmlspecialchars)
- Noch nicht HTML im Editor, aber vorbereitet

## Security Notes

- ✅ htmlspecialchars() auf allen Outputs
- ✅ Admin-Check in allen Admin-Controllern
- ⏳ Firestore Security Rules (M3)
- ⏳ CSRF-Token (später)
- ⏳ Rate-Limiting (später)

## Testing

### Lokale Firestore-Emulation
```bash
firebase emulators:start --only firestore
# In .env: FIREBASE_EMULATOR_HOST=localhost:8080
```

### Mit echtem Firebase
1. Firebase-Projekt erstellen
2. ServiceAccount JSON downloadee
3. Pfad in FIREBASE_SERVICE_ACCOUNT_JSON setzen
4. `.env` laden
5. Testen

## Known Issues / TODOs

- [ ] Firestore Connection Error Handling (zeige Fehler dem Admin)
- [ ] Bulk Operations (mehrere Items löschen)
- [ ] Image Upload (jetzt nur URL)
- [ ] Markdown Preview in Editor
- [ ] Duplicate Post Detection
- [ ] Trash/Restore Funktionalität

## Performance

- News/Blog Queries sind indexed auf `createdAt`
- Pagination begrenzt auf 20 Items pro Admin-Seite
- Frontend zeigt max 3 Items pro Kategorie (Landing Page)

---

**Nächster Step (M3)**: Firebase Auth, User-Accounts, Security Rules
