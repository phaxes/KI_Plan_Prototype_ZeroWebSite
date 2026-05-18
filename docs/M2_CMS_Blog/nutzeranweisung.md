# Milestone M2: Nutzeranweisung - Firestore Setup & CMS Bedienung

Diese Anleitung zeigt dir, wie du:
1. Firestore-Datenbank einrichtest
2. Das CMS für News/Blog nutzt
3. Produkte verwaltest (Vorbereitung für M4)

## Schritt 1: Firestore-Projekt Setup

### 1a. Firebase Console öffnen
- Gehe zu https://console.firebase.google.com
- Wähle dein Projekt

### 1b. Firestore aktivieren
- Gehe zu **Build → Firestore Database**
- Klick **Create Database**
- Wähle: **Start in test mode** (für Entwicklung)
- Region: `europe-west1` (Standard)
- Klick **Create**

### 1c. ServiceAccount erstellen
- Gehe zu **Project Settings** (Zahnrad oben rechts)
- Tab **Service Accounts**
- Klick **Generate New Private Key**
- Speichere JSON auf deinen Computer: `serviceAccountKey.json`

### 1d. ServiceAccount in Projekt kopieren
```bash
# Kopiere serviceAccountKey.json in dein Projekt
cp ~/Downloads/serviceAccountKey.json ./config/serviceAccountKey.json

# Update .env mit dem Pfad
echo "FIREBASE_SERVICE_ACCOUNT_JSON=/path/to/config/serviceAccountKey.json" >> .env
```

> ⚠️ **SICHERHEIT**: `serviceAccountKey.json` enthält Geheimnisse!
> - Niemals in Git committen
> - `.gitignore` hat `*.json` → Automatisch ignoriert

### 1e. Firestore Collections erstellen (optional, auto-created)

Wenn du manuell starten möchtest:
1. Firestore Console
2. **+ Create Collection**
3. Collection ID: `posts`
4. **Add Document**
5. Document ID: auto (oder custom)
6. Fields:
   ```
   type: "news" (string)
   title: "Meine erste News" (string)
   content: "..." (string)
   published: true (boolean)
   createdAt: 2026-05-18 (timestamp)
   tags: ["news"] (array)
   authorId: "admin" (string)
   ```

> Alternativ: Controller erstellen automatisch Collections beim ersten Post.

## Schritt 2: App lokal testen

### 2a. Dependencies installieren
```bash
composer install
npm install
```

### 2b. Tailwind CSS kompilieren
```bash
npm run build
# oder watch-mode:
npm run dev
```

### 2c. Dev-Server starten
```bash
php -S localhost:8000
```

### 2d. Testen
```
http://localhost:8000                # Homepage (leer bei noch keine Daten)
http://localhost:8000/news           # News-Liste (leer)
http://localhost:8000/blog           # Blog-Liste (leer)
http://localhost:8000/admin          # Admin-Dashboard
```

> Falls Admin-Seite "Ort nicht gefunden" zeigt: Deine `.env` ist leer. Check `FIREBASE_SERVICE_ACCOUNT_JSON`.

## Schritt 3: Erste News erstellen

### 3a. Zum Admin gehen
```
http://localhost:8000/admin
```

### 3b. News erstellen
1. Klick **+ Neue News** (rechts oben)
2. Formular ausfüllen:
   - **Titel**: "Willkommen!"
   - **Inhalt**: `# Heading` (Markdown)
   - **Tags**: `welcome, news`
   - **Bild-URL**: https://via.placeholder.com/800x400
   - **Veröffentlichen**: ✓ Checkbox
3. Klick **Speichern**

### 3c. Prüfen
- Admin-Dashboard zeigt die News
- `/news` zeigt sie in Liste
- `/news/{id}` zeigt Detail-Seite

## Schritt 4: Blog-Artikel erstellen

Identisch zu News:
```
/admin/blog/create → Artikel erstellen
/blog → Liste sehen
/blog/{id} → Detail
```

**Unterschied zu News**: Andere Farben (grün/gelb statt blau)

## Schritt 5: Produkte erstellen (M4 Vorbereitung)

### 5a. Produkt hinzufügen
```
/admin/products/create
```

### 5b. Formular
- **Name**: "Premium Widget"
- **Kategorie**: "Widgets"
- **Preis**: 29.99
- **Bestand**: 10
- **Beschreibung**: "Das beste Widget!"
- **Bild-URL**: https://...
- **Aktiv**: ✓

### 5c. Testen
- `/shop` zeigt Produkt
- `/shop/{id}` zeigt Detail mit "In den Warenkorb"-Button

## Troubleshooting

### "Firestore connection failed"
**Symptom**: Admin-Seite zeigt Fehler oder ist leer

**Lösung**:
1. Prüfe `.env`: Ist `FIREBASE_SERVICE_ACCOUNT_JSON` gesetzt?
2. Prüfe Datei existiert: `ls /path/to/serviceAccountKey.json`
3. Prüfe Firestore ist aktiv: https://console.firebase.google.com → Firestore
4. Prüfe Logs: `tail -f php_errors.log`

### "Admin check failed" (Login required)
**Symptom**: Admin-Seite leitet zu `/login` um

**Grund**: M3 (Auth) noch nicht implementiert. Workaround:

In `src/Controllers/Admin/DashboardController.php` ändern:
```php
// Comment out für Testing (M3 wird das fixen)
// if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
//     header('Location: /login');
//     exit;
// }
```

> Nach M3 wird das automatisch durch Firebase-Auth gelöst.

### SimpleMDE Editor funktioniert nicht
**Symptom**: Textarea ist keine Rich-Text

**Lösung**:
1. Browser-Console: `console.log(SimpleMDE)`
2. Ist es `undefined`? → CDN-Link kaputt
3. Prüfe in `templates/layouts/base.php`:
   ```html
   <script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
   ```

### Pagination bricht
**Symptom**: "Seite 2" zeigt keine Items

**Grund**: Nur 9 Items pro Seite (in Code), aber weniger Daten

**Test**: Erstelle 10+ News, dann sollte Pagination funktionieren

### Firestore Emulator benutzen
Für lokale Entwicklung ohne echtes Firebase:

```bash
npm install -g firebase-tools
firebase emulators:start --only firestore
```

In `.env`:
```
FIREBASE_EMULATOR_HOST=localhost:8080
```

In `src/Firebase.php` am Top:
```php
putenv('FIREBASE_EMULATOR_HOST=localhost:8080');
```

## Admin-Features

### News bearbeiten
1. `/admin/news` → Tabelle
2. Klick **Bearbeiten** in Zeile
3. Form ändern
4. **Speichern**

### News löschen
1. `/admin/news` → Tabelle
2. Klick **Löschen**
3. Bestätigen
4. Weg ist das Ding!

### Entwurf vs. Veröffentlicht
- **Entwurf** (Checkbox unchecked): Zeigt auf Admin-Seite, aber nicht auf `/news`, `/blog`
- **Veröffentlicht** (Checkbox checked): Zeigt überall

### Markdown Editor
- Schreib oben, sieh Preview rechts
- Tastenkombinationen:
  - `Ctrl+B` = **Bold**
  - `Ctrl+I` = *Italic*
  - `Ctrl+Alt+1` = # Heading
  - `Ctrl+Alt+L` = Link

## Newsletter (M2 → M5)
- Placeholder im `/admin/subscribers`
- Wird in M5 mit Mailchimp verbunden
- Subscriber können sich via Homepage anmelden

---

**Fragen?** Siehe `docs/M2_CMS_Blog/system_architektur.md` für technische Details.
