# Milestone M3: Nutzeranweisung - Auth Setup & Nutzung

Diese Anleitung zeigt, wie du Firebase Auth einrichtest und mit Login/Register arbeitest.

## Schritt 1: Firebase Auth aktivieren

### 1a. Firebase Console
- Gehe zu https://console.firebase.google.com
- Wähle dein Projekt

### 1b. Authentication aktivieren
- Gehe zu **Build → Authentication**
- Klick **Get Started**
- Wähle **Email/Password** als Auth-Methode
- Toggle **Enable**
- Speichern

### 1c. Test-Benutzer erstellen (optional)
1. Auth Console → **Users**
2. **Add user**
3. Email: `test@example.com`
4. Password: `password123`
5. Create

## Schritt 2: Firebase Config in App

### 2a. Firebase-Keys kopieren
- Firebase Console → **Project Settings** (Zahnrad)
- Tab **Your Apps**
- Web-App wählen
- Config kopieren:
```javascript
{
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_AUTH_DOMAIN",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_STORAGE_BUCKET",
  messagingSenderId: "YOUR_SENDER_ID",
  appId: "YOUR_APP_ID"
}
```

### 2b. In App eintragen
Öffne `public/js/firebase-init.js`:
```javascript
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",  // ← hier
    authDomain: "YOUR_AUTH_DOMAIN",  // ← hier
    projectId: "YOUR_PROJECT_ID",  // ← hier
    // ... rest
};
```

## Schritt 3: Firestore Security Rules deployen

### 3a. Firebase CLI installieren
```bash
npm install -g firebase-tools
firebase login
```

### 3b. Rules deployen
```bash
firebase deploy --only firestore:rules
```

Prüfe: Firebase Console → **Firestore Database → Rules** zeigt die neuen Rules.

## Schritt 4: Test-Konto erstellen

### 4a. Registrierungsseite
```
http://localhost:8000/register
```

### 4b. Form ausfüllen
- Name: "Test Benutzer"
- Email: "testuser@example.com"
- Passwort: "TestPassword123"
- Passwort wiederholen: "TestPassword123"

### 4c. Registrieren
- Klick **Registrieren**
- Sollte zu `/profile` umleiten

### 4d. Prüfen
- Firebase Console → **Authentication → Users** → Neuer Benutzer sichtbar
- Firestore → **users** collection → Neues Dokument mit deiner UID

## Schritt 5: Login testen

### 5a. Login-Seite
```
http://localhost:8000/login
```

### 5b. Mit Test-Account anmelden
- Email: testuser@example.com (oder test@example.com)
- Passwort: TestPassword123 (oder password123)

### 5c. Nach erfolg
- Umleitung zu `/profile`
- Zeigt: Name, Email
- "Logout" Button sichtbar

## Schritt 6: Admin-Flag setzen

Damit ein Nutzer Admin wird, musst du manuell das Flag in Firestore setzen:

### 6a. Firestore Console
- Gehe zu **Firestore Database**
- **users** collection
- Öffne dein Benutzer-Dokument
- Klick **Edit** (Bleistift-Icon)
- Neues Feld hinzufügen:
  - Field name: `isAdmin`
  - Type: `Boolean`
  - Value: `true`
- Speichern

### 6b. Neu anmelden
- Logout
- Erneut anmelden
- Jetzt sollte `/admin` erreichbar sein

## Schritt 7: Admin-Panel testen

### 7a. Ohne Admin-Status
```
http://localhost:8000/admin
→ 403 Access Denied
```

### 7b. Mit Admin-Status
```
http://localhost:8000/admin
→ Dashboard mit News/Blog/Produkt-Stats
```

## Firestore Security Rules prüfen

### Teste die "Dirty Dozen" Sicherheitsfälle:

**Test 1: Unauthenticated Create**
```javascript
// Browser Console
db.collection('posts').add({
  title: 'Hack!',
  content: 'Should fail',
  type: 'news',
  published: true
})
// → Permission denied error ✅
```

**Test 2: Wrong AuthorId**
```javascript
// Logged in as user A
db.collection('posts').add({
  title: 'Hack!',
  authorId: 'user_B_id',  // ← Wrong
  createdAt: firebase.firestore.FieldValue.serverTimestamp(),
  type: 'news'
})
// → Permission denied error ✅
```

**Test 3: Client-provided createdAt**
```javascript
// Logged in
db.collection('posts').add({
  title: 'Hack!',
  authorId: 'my_uid',
  createdAt: new Date('2020-01-01'),  // ← Client value (wrong)
  type: 'news'
})
// → Permission denied error ✅
```

**Test 4: Self-elevate to admin**
```javascript
// Logged in (non-admin)
db.collection('users').doc(firebase.auth().currentUser.uid).set({
  email: 'test@...',
  isAdmin: true  // ← Try to make myself admin
}, {merge: true})
// → Permission denied error ✅
```

**Test 5: Read other user's profile**
```javascript
// Logged in as user A
db.collection('users').doc('user_B_uid').get()
// → Permission denied error ✅
```

## Troubleshooting

### "Email already in use"
**Problem**: Versuchst, dich mit bereits existierender Email zu registrieren

**Lösung**:
- Nutze andere Email-Adresse
- Oder lösche Account in Firebase Console (Authentication → Users → delete)

### "Invalid email/password"
**Problem**: Falsches Passwort oder nicht existierende Email

**Lösung**:
- Prüfe Email-Schreibweise
- Prüfe Passwort (case-sensitive)
- Stelle sicher, dass Benutzer in Authentication existiert

### "Admin check failed"
**Problem**: Versuchst, `/admin` zu besuchen, aber `isAdmin: false`

**Lösung**:
- Gehe zu Firestore Console
- Öffne dein Benutzer-Dokument
- Setze `isAdmin: true`
- Melde dich aus und neu an

### Firestore Rules Error
**Problem**: POST /auth/verify gibt 401 Unauthorized

**Lösung**:
1. Prüfe Firebase Config in `firebase-init.js` ist korrekt
2. Prüfe ServiceAccount JSON ist gültig
3. Prüfe Firestore Rules sind deployed: `firebase deploy --only firestore:rules`
4. Browser Console → Network Tab → /auth/verify response

### Login Button funktioniert nicht
**Problem**: Klick auf "Anmelden" macht nichts

**Lösung**:
1. Prüfe Browser Console auf Errors
2. Prüfe `auth.js` wird geladen (Network Tab)
3. Prüfe Firebase-Config in `firebase-init.js`
4. Prüfe Form IDs matchen: `<form id="login-form">` in Template

## Firestore Sicherheitsregeln deployments

### Mit Firebase CLI
```bash
firebase deploy --only firestore:rules
# Zeigt alte und neue Rules, wartet auf Bestätigung
```

### In Firebase Console
1. Firestore → **Rules**
2. Paste Content von `firestore.rules`
3. Klick **Publish**

### Rules werden sofort aktiv
Alle neuen Firestore Operationen respektieren die Rules.

---

**Nächste Schritte**:
- M4: Shop mit Stripe Checkout
- M5: Mailchimp Newsletter Integration
