# Milestone M2: System & Architektur Leitfaden

## M2 Architektur-Übersicht

M2 erweitert M1 um die **Firestore-Datenschicht**:

```
┌─────────────────────────────────────────────────────┐
│              Browser (Frontend)                     │
│  /news, /blog, /shop (public)                      │
│  /admin/news, /admin/blog, /admin/products (CMS)   │
└─────────────────────────────────────────────────────┘
                        ↕ HTTP
┌─────────────────────────────────────────────────────┐
│          PHP Backend (Request Handler)              │
│                                                     │
│  Router (index.php)                                │
│    ↓                                                │
│  Controllers:                                       │
│    - NewsController → getPosts('news')              │
│    - BlogController → getPosts('blog')              │
│    - ShopController → getProducts()                 │
│    - Admin/NewsAdminController → CRUD              │
│    - Admin/BlogAdminController → CRUD              │
│    - Admin/ProductAdminController → CRUD           │
│    ↓                                                │
│  Firebase class (centralized DB access)            │
└─────────────────────────────────────────────────────┘
                        ↕ API (JSON)
┌─────────────────────────────────────────────────────┐
│        Firestore (NoSQL Database)                   │
│                                                     │
│  Collections:                                       │
│    - posts (news & blog)                           │
│    - products                                       │
│    - users (vorbereitet M3)                        │
│    - subscribers (M5)                              │
└─────────────────────────────────────────────────────┘
```

## Data Flow: News erstellen

```
1. Admin Browser
   ↓
2. POST /admin/news/store
   Form: title, content, tags, published, authorId
   ↓
3. PHP Router → NewsAdminController::store()
   ↓
4. Parse POST data:
   $data = [
     'title' => $post['title'],
     'content' => markdown_from_editor,
     'type' => 'news',
     'published' => isset($post['published']),
     'tags' => explode(',', $post['tags']),
     'authorId' => $_SESSION['email'],
     'createdAt' => now,
     'updatedAt' => now
   ]
   ↓
5. Firebase::createPost($data)
   ↓
6. kreait Admin SDK
   → POST to Firestore REST API
   ← Get back document ID
   ↓
7. Header redirect /admin/news
   ↓
8. Browser: GET /admin/news
   ↓
9. NewsAdminController::index()
   → Firebase::getPosts('news', null, 20, 0)
   ← Array of documents
   ↓
10. Render admin table with latest news
```

## Data Flow: News anschauen (public)

```
1. User Browser
   ↓
2. GET /news
   ↓
3. NewsController::index()
   ↓
4. Firebase::getPosts('news', true, 9, 0)
   - type: 'news'
   - published: true
   - limit: 9 per page
   - order by createdAt DESC
   ↓
5. Firestore REST API
   ← Documents returned
   ↓
6. Render template with cards
   └─ Each card: title, excerpt, date, tags, "Weiterlesen" link
   ↓
7. User klickt auf Artikel
   ↓
8. GET /news/{id}
   ↓
9. NewsController::show($params={'id' => '123'})
   ↓
10. Firebase::getPostById('123')
    ↓
11. Document returned
    ↓
12. Render full article with title, content, author, date, tags
```

## Firebase Class (src/Firebase.php)

Zentrale Klasse mit statischen Methoden. Beispiel:

```php
class Firebase {
    private static $firestore = null;

    public static function firestore() {
        if (!self::$firestore) {
            // Lazy-load beim ersten Aufruf
            $serviceAccount = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');
            $factory = new Factory();
            $firebase = $factory->withServiceAccount($serviceAccount);
            self::$firestore = $firebase->createFirestore();
        }
        return self::$firestore;
    }

    public static function getPosts($type = null, $published = null, $limit = 10) {
        try {
            $query = self::firestore()->collection('posts');

            if ($type) $query = $query->where('type', '==', $type);
            if ($published !== null) $query = $query->where('published', '==', $published);

            $query = $query->orderBy('createdAt', 'DESCENDING');
            $documents = $query->limit($limit)->documents();

            $posts = [];
            foreach ($documents as $doc) {
                $posts[] = ['id' => $doc->id(), ...$doc->data()];
            }
            return $posts;
        } catch (Exception $e) {
            error_log('Firestore error: ' . $e->getMessage());
            return [];
        }
    }
}
```

**Vorteile**:
- ✅ Zentralisierte Fehlerbehandlung
- ✅ Einfach zu testen
- ✅ Consistent API across Controllers

## Controller Pattern in M2

Alle Controllers folgen diesem Pattern:

```php
namespace App\Controllers;

use App\Firebase;

class NewsController {
    public function index($params = [], $post = [], $get = []) {
        // 1. Fetch data
        $news = Firebase::getPosts('news', true, 9, 0);

        // 2. Setup template variables
        $title = 'News';
        $pageTitle = 'Neueste News';

        // 3. Buffer output
        ob_start();
        ?>
        <!-- HTML Template -->
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <?php foreach ($news as $article): ?>
            <article>
                <h2><?= htmlspecialchars($article['title']) ?></h2>
                <p><?= nl2br(htmlspecialchars($article['content'])) ?></p>
            </article>
        <?php endforeach; ?>
        <?php
        // 4. Get buffer and render in layout
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/base.php';
    }
}
```

**Pattern**:
1. **Query DB** → `Firebase::method()`
2. **Set variables** → `$title`, `$content`, etc.
3. **Buffer HTML** → `ob_start()`
4. **Render layout** → `require base.php`

Alle Admin-Controller haben zusätzlich:
```php
protected function checkAdmin() {
    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        header('Location: /login');
        exit;
    }
}
```

## Admin CRUD Interface

### Read (list)
```
GET /admin/news
→ NewsAdminController::index()
→ Firebase::getPosts('news', null, 20, 0)  // alle, auch Entwürfe
→ Render table with status badge
```

### Create (form)
```
GET /admin/news/create
→ NewsAdminController::create()
→ Render form with SimpleMDE
```

### Store (save)
```
POST /admin/news/store
→ NewsAdminController::store()
→ Parse form data
→ Firebase::createPost($data)
→ Redirect to index
```

### Update (edit)
```
GET /admin/news/{id}/edit
→ NewsAdminController::edit()
→ Firebase::getPostById($id)
→ Render form with data

POST /admin/news/{id}/update
→ NewsAdminController::update()
→ Firebase::updatePost($id, $data)
→ Redirect to index
```

### Delete
```
POST /admin/news/{id}/delete
→ NewsAdminController::delete()
→ Firebase::deletePost($id)
→ Redirect to index
```

## Firestore Collection-Struktur

### Collection: `posts`

```json
{
  "posts": {
    "doc_id_1": {
      "type": "news",           // Index: orderBy type
      "title": "...",           // Index: where title
      "content": "...",         // Full markdown/text
      "published": true,        // Index: where published
      "tags": ["news", "web"],  // Array
      "imageUrl": "https://...",
      "authorId": "user@...",
      "createdAt": 1234567890,  // Index: orderBy createdAt DESC
      "updatedAt": 1234567890
    },
    "doc_id_2": { ... }
  }
}
```

**Wichtige Indizes** (auto-created by Firestore):
- `type + published + createdAt DESC` (News-Listing)
- `type: 'blog' + published + createdAt DESC` (Blog-Listing)

### Collection: `products`

```json
{
  "products": {
    "prod_id_1": {
      "name": "...",
      "category": "...",
      "price": 29.99,
      "description": "...",
      "imageUrl": "...",
      "stock": 10,
      "active": true,           // Where active == true
      "createdAt": 1234567890,
      "updatedAt": 1234567890
    }
  }
}
```

## Performance & Optimierungen

### Lazy-Loading
`Firebase` class lazy-loads den Firestore-Client:
```php
public static function firestore() {
    if (!self::$firestore) {
        // nur beim ersten Call instantiiert
        self::$firestore = new FirestoreClient(...);
    }
    return self::$firestore;
}
```
→ Reduziert overhead bei requests ohne DB-Zugriff

### Pagination
Limit auf 9/20 Items statt alle zu laden:
```php
Firebase::getPosts('news', true, 9, 0)  // 1-9
Firebase::getPosts('news', true, 9, 9)  // 10-18
```

### Indexing
Firestore erzeugt automatisch Indizes für:
- `where` + `orderBy` Kombinationen
- Häufig genutzte Filters

## Fehlerbehandlung

Alle Firebase-Methoden:
```php
try {
    // Firestore operation
} catch (Exception $e) {
    error_log('Operation failed: ' . $e->getMessage());
    return [];  // Safe default
}
```

**Error Cases**:
- ServiceAccount JSON nicht lesbar → "File not found"
- Firestore offline → "Could not reach server"
- Invalid document ID → "Not found"

**User sieht**: Leere Listen statt Crash

**Dev sieht**: Errors in `php_errors.log` oder Browser-Console

## Security (M2 → M3)

Aktuell (M2):
- ✅ htmlspecialchars() auf alle Outputs
- ✅ Admin-Check redirect
- ❌ Keine Firestore-Rules noch (alles public lesbar)
- ❌ Keine CSRF-Token
- ❌ Keine Input-Validation

M3 wird hinzufügen:
- 🔄 Firebase-Auth
- 🔄 Server-side token verify
- 🔄 Firestore Security Rules
- 🔄 Admin-flag aus Firestore

## Testing-Ansätze

### Unit Test (Firebase Class)
```php
// tests/FirebaseTest.php
class FirebaseTest {
    public function testGetPostsReturnsArray() {
        $posts = Firebase::getPosts('news', true, 10, 0);
        $this->assertIsArray($posts);
    }
}
```

### Integration Test
```bash
# Setup Firestore Emulator
firebase emulators:start --only firestore

# Run tests against emulator
phpunit
```

### Manual Test
```bash
php -S localhost:8000
# Browser: /news, /admin/news/create, etc.
```

---

**Zusammenfassung**:
- Firebase class ↔ Firestore API (zentral)
- Controller nutzen Firebase (nicht direkt Firestore)
- Admin CRUD via Controller+Template
- Public Reads via NewsController/BlogController
- Security Rules folgen in M3
