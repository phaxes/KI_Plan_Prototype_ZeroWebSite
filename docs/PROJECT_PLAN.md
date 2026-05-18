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

Google AI Studio: 

"Generiere mir hierzu einen Projektplan mit Product Backlog Items, Milestones und einer groben Zeitleiste. Schlage Non Cost Hosting Möglichkeiten vor, die die Kriterien erfüllen. Erstelle hierzu einen Projektordner mit Dokumenten. Erstelle ein Kanban Board mit den erstellten Product Backlog Items und Priorisierung derer dass die genannten Tools optimal integriert werden können. Schlage passende Technologien für die einzelnen Bereiche vor, wie Datenbanken oder APIs, vor."

# Projektplan & Milestones

Der Zeitrahmen ist auf 2 Wochen (10 Arbeitstage) festgesetzt.

## Meilensteine

| Meilenstein | Ziel | Deadline |
| :--- | :--- | :--- |
| **M1: Setup & Design** | Grundgerüst, CI/CD, Design-System (shadcn) | Tag 2 |
| **M2: CMS & Blog** | Firestore-Anbindung, News-Feed, Artikel-Detailansicht | Tag 4 |
| **M3: Auth & User** | Login/Registrierung, Profil-Seite | Tag 6 |
| **M4: Shop-Kern** | Produktübersicht, Warenkorb-Logik, "Kaufen"-Mockup | Tag 8 |
| **M5: CRM & Finishing** | Newsletter-Anbindung, Polishing, Dokumentation | Tag 10 |

## Zeitplan (Grob)

- **Woche 1**: Fokus auf Content und Struktur.
  - Tag 1-2: Setup, Routing, Header/Footer, Landingpage Design.
  - Tag 3-5: CMS Implementierung (Admin-Bereich für Blog/News).
  
- **Woche 2**: Fokus auf Interaktion und CRM.
  - Tag 6-7: Authentifizierung und geschützte User-Bereiche.
  - Tag 8-9: Shop-Funktionalität und CRM-Integration (Newsletter).
  - Tag 10: Bugfixing, Performance-Check und Übergabe.

  Claude Code CLI: 

  "generiere mir eine website mit: HTML PHP Javascript CSS Tailwind und einer firebase no sql datenbank anbindung, integriere mailchimp und stripe. Die Website muss ein admin backend haben. Das 
  CMS soll eine vom Nutzer im Backend zu pflegende Newsseite haben und Blogartikel pflegen können. Eine kleine Shopfunktion muss auch integriert werden (Produkte ansehen / in Warenkorb legen 
  die Bezahlfunktion soll mit Stripe gelöst sein jedoch ohne echte Zahlungsabwicklung (soll später erweiterbar sein). Nutzer sollen sich registrieren und anmelden können. Kunden sollen per 
  E-Mail Newsletter informiert werden und Marketing innerhalb soll diese binden ( neue Produkte / News / Blogeinträge / Cross Selling). Die Website soll gehostet werden können auf No Cost 
  Servern. Lege zu jedem Milestone eine Dokumentation an, ein interne Nutzeranweisung und eine System und Architektur Leitfaden als Dokument im Quellverzeichniß unter Docs."
