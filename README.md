AI-First in professionellen Händen ermöglicht vieles, vor allem wo viel Boilerplate-Code einfließt. Um dies zu demonstrieren, wollen wir uns nun gewissermaßen der Königsdisziplin annehmen: Ein Szenario, wo AI-First das leistet, wofür früher eine ganze Webagentur benötigt wurde.

Dieses Szenario ist das wahrscheinlichste in der realen Welt, und wenn man dieses meistert, kann man sagen, man hat die ganze Thematik wirklich durchdrungen. Deshalb ist dies dein Abschlussprojekt.

Es geht um die Erstellung einer professionellen Webpräsenz für eine kleine Organisation oder einen Freund. Die Anforderungen sind spezifisch und herausfordernd. Normalerweise wäre dies ein Projekt für ein 3-köpfiges Agentur-Team über einen oder mehrere Monate. Deine Aufgabe ist es, dieses Projekt alleine in zwei Wochen umzusetzen.

Funktionale Anforderungen:

CMS: Sie wollen ihre News und Blogartikel selbst pflegen können.
CRM: Sie wollen Kunden per E-Mail-Newsletter binden.
Shop-Funktion: Es soll eine sehr kleine Shop-Funktionalität integriert sein (Produkte ansehen, Warenkorb füllen), jedoch ohne die komplexe Zahlungsabwicklung (nur bis zum "Kauf"-Button).
User-Login: Nutzer sollen sich auf der Seite registrieren und anmelden können.
Rahmenbedingungen:

Harte Restriktion: Sie weigern sich strikt, monatliche Serverkosten zu zahlen (abgesehen von der Domain). Budget: maximal 2 Euro pro Monat oder 24 Euro pro Jahr.
Deadline: Aufgrund einer anstehenden Messe muss das System in maximal zwei Wochen stehen.
Hauptanforderung ist, dass das System:

Fehlerfrei und stabil läuft
Erweiterbar ist
Verständlich dokumentiert ist
Denn du willst danach eigentlich nicht mehr daran weiterarbeiten.

Welche Technologien du verwendest (Flutter, PHP, HTML, Hugo...) ist ganz dir überlassen.

Es geht hierbei nicht nur allein um die Umsetzung, sondern auch darum, die Recherche durchzuführen – ein zentraler Punkt! Du musst selbst herausfinden, welche Tools in dieses "Zero-Cost"-Konzept passen.

Stichworte & Hinweise für die Recherche:

Firebase
MailChimp
GitHub Student Developer Pack
Stripe
Teilaufgaben
1. Subtask 1
Der öffentliche Bereich - Was der Kunde und die Besucher sehen:

Webpräsenz & Shop: Eine moderne Landing Page (Hero, Services, Über uns) mit einem dynamischen News-Feed direkt aus der Datenbank sowie ein funktionierender Shop-Bereich (Produktübersicht, Detailansicht, Warenkorb).
Interaktion & User: Login/Registrierung für Kunden und ein valides Formular zur Newsletter-Anmeldung.
2. Subtask 2
Das Admin-Dashboard - Was der Geschäftsführer/Freund sieht (unter /admin):

Zugriff & Sicherheit: Ein geschützter Bereich, der nur nach Login erreichbar ist (Protected Routes) und grundlegende Statistiken (z.B. Abonnenten-Zahl) anzeigt.
Verwaltung (CMS & CRM): Tools zum Erstellen und Löschen von Blogartikeln (inkl. Bild-Upload) sowie ein Bereich zum Verfassen und Versenden von Newslettern an die gespeicherten Abonnenten.
3. Subtask 3
Abschluss-Reflexion: Am Ende schreibe eine kurze Reflexion:

Welche Gedanken hast du zu diesem Projekt?
Was denkst du darüber, dass man damit eine ganze Agentur ersetzen kann?

<div align="center">
<img width="1200" height="475" alt="GHBanner" src="https://github.com/user-attachments/assets/0aa67016-6eaf-458a-adb2-6e31a0763ed6" />
</div>

# Run and deploy your AI Studio app

This contains everything you need to run your app locally.

View your app in AI Studio: https://ai.studio/apps/5f990a49-cfa6-49a7-a9dc-a4228b0474ac

## Run Locally

**Prerequisites:**  Node.js


1. Install dependencies:
   `npm install`
2. Set the `GEMINI_API_KEY` in [.env.local](.env.local) to your Gemini API key
3. Run the app:
   `npm run dev`
