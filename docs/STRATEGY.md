# Strategie & Technologie-Stack

Dieses Projekt folgt dem "Zero-Cost"-Paradigma für kleine Organisationen. Ziel ist es, eine professionelle Webpräsenz ohne laufende Serverkosten (außer der Domain) zu betreiben.

## Hosting-Optionen (Zero Cost)

1. **Firebase Hosting (Favorit)**: 
   - **Vorteil**: Extrem schnell (CDN), integrierte SSL Zertifikate, nahtlose Integration mit Firebase Auth und Firestore.
   - **Kosten**: Im "Spark Plan" (Free Tier) großzügige Limits (10GB Speicher, 360MB/Tag Datentransfer).
   
2. **GitHub Pages + GitHub Actions**:
   - **Vorteil**: Gut für statische Seiten.
   - **Kosten**: Komplett kostenlos für öffentliche Repositories.
   
3. **Netlify / Vercel**:
   - **Vorteil**: Überragende DX (Developer Experience), automatisierte Builds.
   - **Kosten**: Kostenlose "Hobby" Pläne mit 100GB Bandbreite.

## Technologie-Stack

| Bereich | Technologie | Begründung |
| :--- | :--- | :--- |
| **Frontend** | React + Vite | Modern, schnell und hochgradig erweiterbar. |
| **Styling** | Tailwind CSS + shadcn/ui | Ermöglicht agentur-reifes Design in Rekordzeit. |
| **Backend / DB** | Firebase Firestore | NoSQL Datenbank mit Realtime-Fähigkeiten und exzellentem Free-Tier. |
| **Authentifizierung** | Firebase Auth | Sicherer User-Login (E-Mail/Google) ohne eigenen Server-Code. |
| **CMS** | Custom Firestore CMS | News und Blogbeiträge werden direkt in Firestore verwaltet. |
| **CRM / Newsletter** | Resend / Mailchimp | Resend bietet eine moderne API für Transaktions-E-Mails (3000/Monat kostenlos). |
| **Shop** | Stripe (Client-side) | Nutzung von Stripe Checkout-Links oder reinem Client-Warenkorb für Kosteneffizienz. |

## Budget-Check
- **Hosting**: 0€ (Firebase Spark)
- **Datenbank**: 0€ (Firestore Spark)
- **Email**: 0€ (Resend Free)
- **Zahlung**: Transaktionsbasiert (Stripe), keine monatlichen Fixkosten.
- **Gesamt**: **0€ / Monat** (zzgl. ca. 1€/Monat für die Domain bei Anbietern wie Namecheap oder Cloudflare).
