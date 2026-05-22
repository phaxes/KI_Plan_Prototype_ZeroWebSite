# Mailchimp Konfigurationsleitfaden für Zero-Cost Website

Dieser Leitfaden zeigt dir Schritt-für-Schritt, wie du Mailchimp mit der Zero-Cost Website konfigurierst.

---

## Schritt 1: Mailchimp-Konto erstellen

1. Gehe zu [mailchimp.com](https://mailchimp.com)
2. Klicke auf **"Kostenlos registrieren"** (oben rechts)
3. Gib folgende Informationen ein:
   - **E-Mail-Adresse**: Deine E-Mail
   - **Benutzername**: Wähle einen Benutzernamen
   - **Passwort**: Erstelle ein sicheres Passwort
4. Klicke **"Registrieren"**
5. Bestätige deine E-Mail-Adresse (Bestätigungslink in deinem Postfach)

---

## Schritt 2: Zielgruppe (Newsletter-Liste) erstellen

### 2.1 Zur Zielgruppen-Verwaltung gehen

1. Nach dem Login klickst du oben auf **"Zielgruppen"** (in der Navigation)
2. Klicke auf **"Zielgruppe verwalten"** → **"Alle Zielgruppen"**
3. Klicke auf die grüne Schaltfläche **"Zielgruppe erstellen"**

### 2.2 Zielgruppe konfigurieren

**Schritt 1: Name und Standardwerte**
- **Name der Zielgruppe**: z.B. "Newsletter Abonnenten"
- **Standard-E-Mail**: Deine E-Mail-Adresse
- **Standard-Absendername**: Dein Geschäftsname oder Name
- **Standard-Adressen**: Füll diese mit deinen Angaben aus

**Schritt 2: Benachrichtigungseinstellungen**
- **An diese E-Mail senden**: Deine E-Mail-Adresse
- Aktiviere: **"Wenn ein abonnent hinzugefügt wird"** und **"Wenn ein abonnent sich abmeldet"**

**Schritt 3: Zielgruppe erstellen**
- Klicke **"Zielgruppe erstellen"**

---

## Schritt 3: Newsletter-Felder konfigurieren

### 3.1 Zu den Zielgruppenseite gehen

1. Klicke auf deine neu erstellte Zielgruppe
2. Klicke rechts oben auf das **Zahnrad-Symbol** → **"Zielgruppe und Standard"**
3. Scrolle zu **"Zielgruppen-Felder und -Zusammenführung"**

### 3.2 Kategorien-Feld hinzufügen

Da die Website Kategorie-Abos unterstützt (News, Blog, Produkte), empfehlen wir, ein Feld hinzuzufügen:

1. Klicke auf **"Feld hinzufügen"**
2. Wähle **"Eigenes Feld"**
3. Konfiguriere:
   - **Feldname**: "Kategorien"
   - **Feldtyp**: "Text"
   - **Tag**: "KATEGORIEN"
4. Klicke **"Speichern"**

---

## Schritt 4: API-Schlüssel abrufen

### 4.1 API-Schlüssel generieren

1. Klicke oben auf dein **Profilsymbol** (rechts oben)
2. Wähle **"Konto"**
3. Klicke auf **"Extras"** → **"API-Schlüssel"**
4. Klicke auf **"API-Schlüssel erstellen"**
5. Ein neuer Schlüssel wird generiert (z.B. `abc123def456789xyz`)

### 4.2 API-Schlüssel kopieren

1. Klicke auf **Kopieren** neben deinem API-Schlüssel
2. Speichere diesen sicher ab (du brauchst ihn später)

**WICHTIG**: Behandle den API-Schlüssel wie ein Passwort - nie public machen!

---

## Schritt 5: Server-ID ermitteln

Dein API-Schlüssel enthält eine Server-ID, z.B.:
```
abc123def456789xyz-us5
```

Die Server-ID ist der Teil **nach dem Bindestrich** (`us5` in diesem Beispiel).

**Speichere deine Server-ID auf** - du brauchst sie später!

---

## Schritt 6: .env Datei konfigurieren

Auf deinem Server (Render.com oder lokal):

### 6.1 .env Datei öffnen

```bash
nano .env
```

### 6.2 Folgende Variablen setzen/aktualisieren

```
MAILCHIMP_API_KEY=abc123def456789xyz-us5
MAILCHIMP_SERVER_PREFIX=us5
MAILCHIMP_LIST_ID=xxxxxxxxxxxxx
```

**Wobei:**
- `MAILCHIMP_API_KEY`: Dein kompletter API-Schlüssel
- `MAILCHIMP_SERVER_PREFIX`: Die Server-ID (z.B. us5, us1, eu1, etc.)
- `MAILCHIMP_LIST_ID`: Die Zielgruppen-ID (siehe nächster Schritt)

---

## Schritt 7: Zielgruppen-ID (LIST_ID) ermitteln

### 7.1 LIST_ID finden

1. Gehe zu **"Zielgruppen"** → **"Alle Zielgruppen"**
2. Klicke auf deine Zielgruppe
3. Klicke auf **"Zielgruppe verwalten"** → **"Einstellungen"**
4. Scrolle zu **"Zielgruppen-ID"**
5. Kopiere diese ID (z.B. `a1b2c3d4e5`)

### 7.2 LIST_ID in .env eintragen

```
MAILCHIMP_LIST_ID=a1b2c3d4e5
```

---

## Schritt 8: Auf Render.com deployen

### 8.1 Umgebungsvariablen auf Render setzen

1. Gehe zu deinem Render-Dashboard
2. Wähle dein Projekt
3. Gehe zu **"Environment"**
4. Füge folgende Variablen hinzu:
   - `MAILCHIMP_API_KEY`: Dein API-Schlüssel
   - `MAILCHIMP_SERVER_PREFIX`: Deine Server-ID
   - `MAILCHIMP_LIST_ID`: Deine Zielgruppen-ID

5. **"Deploy"** klicken für Neustart

---

## Schritt 9: Newsletter-Formular testen

### 9.1 Lokales Testen

1. Starte die Website lokal
2. Scrolle zum Newsletter-Formular (Footer)
3. Gib folgende Test-Daten ein:
   - **E-Mail**: test@example.com
   - Klicke **"Abonnieren"**

4. Prüfe Mailchimp:
   - Gehe zu **"Zielgruppen"** → deine Zielgruppe
   - Klicke auf **"Abonnenten verwalten"**
   - Deine Test-E-Mail sollte dort auftauchen

### 9.2 Auf Render testen

1. Gehe zu https://zero-cost-website.onrender.com
2. Scrolle zum Newsletter-Formular
3. Gib eine echte E-Mail ein
4. Klicke **"Abonnieren"**
5. Bestätige in deinem Postfach
6. Prüfe Mailchimp auf neue Abonnenten

---

## Schritt 10: Welcome-E-Mail erstellen (Optional)

### 10.1 Automatisierung einrichten

1. Gehe zu **"Marketing"** → **"Automation"**
2. Klicke **"Automation erstellen"**
3. Wähle **"Bei Zielgruppenereignis"** → **"Wenn jemand der Zielgruppe beitritt"**
4. Klicke **"Nächste"**

### 10.2 E-Mail verfassen

1. Klicke **"Neue E-Mail"**
2. Verfasse eine Welcome-E-Mail, z.B.:
   - **Betreff**: "Willkommen bei unserem Newsletter!"
   - **Inhalt**: Begrüße den Abonnenten, erkläre, was er erwarten kann

3. Speichere und aktiviere die Automation

---

## Schritt 11: Newsletter-Kampagne versenden

### 11.1 Kampagne erstellen

1. Gehe zu **"Marketing"** → **"Kampagnen"**
2. Klicke **"Kampagne erstellen"**
3. Wähle **"E-Mail"**

### 11.2 Empfänger wählen

1. Wähle deine **Zielgruppe** aus
2. Optional: Filtere nach Kategorien (z.B. nur "News"-Abonnenten)
3. Klicke **"Nächste"**

### 11.3 E-Mail-Inhalt

1. Verfasse deine Newsletter-E-Mail
2. Füge Links zu deinen News/Blog-Posts ein
3. Klicke **"Versand planen"** oder **"Jetzt versenden"**

---

## Troubleshooting

### Problem: "Invalid API Key"

**Lösung:**
- Prüfe, dass der API-Schlüssel korrekt kopiert wurde
- Stelle sicher, dass kein Leerzeichen vor oder nach dem Schlüssel ist
- Regeneriere den API-Schlüssel falls nötig

### Problem: "List not found"

**Lösung:**
- Prüfe, dass die LIST_ID korrekt ist
- Stelle sicher, dass die Zielgruppe noch existiert
- Regeneriere die LIST_ID

### Problem: "Zu viele Anfragen" (Rate Limit)

**Lösung:**
- Mailchimp hat Rate Limits (~10 Anfragen/Sekunde)
- Die Website implementiert Caching und Delays
- Normalerweise kein Problem bei normaler Nutzung

### Problem: E-Mails landen im Spam

**Lösung:**
1. Gehe zu **"Zielgruppen"** → **"Einstellungen"**
2. Verifikation: Stelle sicher, deine **Domain ist verifiziert**
3. SPF/DKIM Records hinzufügen (im Hosting-Panel)
4. Teste mit [Mail-tester.com](https://mail-tester.com)

---

## Sicherheit & Best Practices

### ✅ Zu tun:

1. **API-Schlüssel schützen**
   - Nur auf Render.com und lokalen Variablen speichern
   - Nie im Code oder GitHub commiten

2. **Double-Opt-In aktivieren**
   - Gehe zu **"Zielgruppen"** → **"Einstellungen"**
   - Aktiviere **"Opt-In-Bestätigung erforderlich"**
   - Abonnenten müssen ihre E-Mail bestätigen

3. **Impressum & Datenschutz**
   - Mailchimp erfordert ein **Impressum** mit Adresse
   - Gehe zu **"Zielgruppen"** → **"Einstellungen"** → **"Kontaktdaten verwalten"**
   - Trage deine vollständigen Daten ein

4. **Datenschutzerklärung**
   - Gehe zu **"Zielgruppen"** → **"Einstellungen"**
   - Erstelle/Update deine **Datenschutzerklärung**

### ❌ Nicht zu tun:

1. **API-Schlüssel in GitHub pushen**
2. **Spam versenden** (führt zu Bannungen)
3. **Automatische Bestätigung** ohne Double-Opt-In
4. **Persönliche Daten sammeln** ohne Zustimmung

---

## Kontakt & Support

- **Mailchimp Support**: https://mailchimp.com/contact/
- **Mailchimp Knowledge Base**: https://mailchimp.com/help/
- **Deutsche Hilfe**: https://de.mailchimp.com/

---

## Checkliste

- [ ] Mailchimp-Konto erstellt
- [ ] Zielgruppe erstellt
- [ ] API-Schlüssel generiert
- [ ] Server-ID notiert
- [ ] .env Datei aktualisiert
- [ ] Auf Render deployed
- [ ] Newsletter-Formular getestet
- [ ] Datenschutzerklärung hinzugefügt
- [ ] Double-Opt-In aktiviert
- [ ] Erste Kampagne versendet

---

**Fragen?** Überprüfe die Konsole auf Fehler oder kontaktiere Mailchimp Support.

**Viel Erfolg mit deinem Newsletter! 🚀**
