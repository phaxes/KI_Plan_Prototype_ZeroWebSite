# PBI-06: Newsletter Signup

Newsletter signup system with Firestore subscriber storage and Resend transactional emails.

## Quick Start

### 1. Configure Resend API

Add to `.env`:
```
RESEND_API_KEY=re_your_actual_key_here
RESEND_FROM_EMAIL=onboarding@resend.dev
RESEND_FROM_NAME=Zero Cost Website
```

Get API key at [resend.com](https://resend.com) → API Keys → Create.

> **For production:** Verify your own domain in Resend dashboard, then set `RESEND_FROM_EMAIL=newsletter@yourdomain.com`

### 2. Add Newsletter Form (Home Page)

Already included in `/` homepage — form at bottom of page sends to `/api/newsletter/subscribe`.

### 3. Create Landing Page

Visit `/newsletter` to see:
- Hero signup form (email + optional name)
- Latest news posts preview
- Latest blog posts preview

## API Endpoints

### POST /api/newsletter/subscribe
Subscribe a new user to newsletter.

**Request:**
```json
{
  "email": "user@example.com",
  "name": "John Doe"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Danke für dein Abonnement!"
}
```

**Response (Error):**
```json
{
  "error": "Invalid email address"
}
```

**Behavior:**
- Email validation required
- Duplicate emails: no error, record updated (idempotent)
- Resend failures: email not sent but signup succeeds (logged only)

## Subscriber Data

Stored in Firestore collection `subscribers`:

```
Document ID: md5(email)  // Stable, unique per email

Fields:
- email: "user@example.com"
- name: "John Doe"
- source: "website"
- active: true
- subscribedAt: <DateTime>
```

## Key Files

| File | Purpose |
|------|---------|
| `src/ResendMailer.php` | Resend API client (sendWelcome, send) |
| `src/Firebase.php::createSubscriber()` | Store subscriber in Firestore |
| `src/Controllers/NewsletterController.php` | Handle signup + landing page |
| `templates/newsletter/index.phtml` | Landing page template |
| `templates/home/index.phtml` | Home page form + JavaScript |

## Signing Up

### Via Home Page
1. Scroll to "Newsletter" section
2. Enter email (+ optional name)
3. Click "Abonnieren"
4. See success notification

### Via /newsletter Page
1. Visit `/newsletter`
2. Enter email + optional name
3. Click "Jetzt abonnieren"
4. See success message + content preview

## Testing

**Test signup:**
```bash
curl -X POST http://localhost:8000/api/newsletter/subscribe \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","name":"Test"}'
```

**Expected:** `{"success":true,"message":"Danke für dein Abonnement!"}`

**Test duplicate:**
Same email again → returns success (no error, record updated)

## Sending Newsletters

Currently manual workflow:
1. Log into [Resend Dashboard](https://resend.com/emails)
2. Create new email campaign
3. Send to all subscribers (export list from Firestore if needed)

> **Future:** Admin panel could automate this

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "No API key configured" | Check `.env` RESEND_API_KEY (should not start with `re_xxxx`) |
| Email not received | Check spam folder; verify domain DNS in Resend (if using custom domain) |
| Signup shows error 400 | Email validation failed; check email format |
| Resend API errors in logs | Check API key is valid; verify request format |

## Configuration Reference

### Environment Variables

| Variable | Default | Notes |
|----------|---------|-------|
| `RESEND_API_KEY` | `re_xxxx` | Get from resend.com; placeholder won't send emails |
| `RESEND_FROM_EMAIL` | `onboarding@resend.dev` | Works without domain verification; production needs own domain |
| `RESEND_FROM_NAME` | `Zero Cost Website` | Display name in emails |

### Welcome Email

Template: `src/ResendMailer.php::welcomeHtml()`

- Greeting with subscriber name
- List of newsletter benefits
- CTA button to website
- Unsubscribe instructions

## Subscriber Management

### View Subscribers
Firebase Console → `zerocostws` project → Firestore → `subscribers` collection

### Deactivate Subscriber
1. Find document by `email` field
2. Set `active: false`
3. Resend will skip deactivated subscribers (when campaign feature is added)

### Bulk Export
Firestore → Export `subscribers` collection as JSON, then process as needed

## Notes

- ✅ Subscriber list managed in Firebase (not external service)
- ✅ Email send failures don't block signup
- ✅ Idempotent: duplicate emails safe
- ✅ Works offline: queued in Firestore even if Resend down
- ⚠️ Currently no UI for unsubscribe/preferences
- ⚠️ No automated campaigns (manual Resend dashboard only)

## Related

- [[PBI-04]](PBI-04-CART.md) — Shopping cart
- [[PBI-05]](PBI-05-CHECKOUT.md) — Checkout & payments
- [Resend API Docs](https://resend.com/docs)
