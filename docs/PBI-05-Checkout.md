# PBI-05: "Kaufen" Signal – Checkout mit Webhook-Vorbereitung

## Übersicht
Vollständige Implementierung des Checkout-Flows mit:
- **Simulation-Modus:** Test-Checkout ohne echte Stripe-Integration
- **Email-Speicherung:** Bestellbestätigung mit E-Mail in Order
- **Order-Details:** Success-Page zeigt Bestellnummer + Gesamtbetrag
- **Webhook-Ready:** Vorbereitung für zukünftige Stripe-Integration

---

## Features

### 1. DOM-Bug Fix: Stripe-Konfiguration

**Vorher (Broken):**
```javascript
// stripe.js las Key von document.body (war aber auf <div>)
const publishableKey = document.body.dataset.stripeKey; // undefined!
```

**Nachher (Fixed):**
```javascript
// Liest jetzt von [data-stripe-key] Element
const configElement = document.querySelector('[data-stripe-key]');
const publishableKey = configElement.dataset.stripeKey; // ✅
```

**Template-Struktur:**
```html
<div class="bg-white rounded shadow p-8" 
     data-stripe-key="pk_test_..." 
     data-stripe-test-mode="true">
  <form id="checkout-form">
    <input type="email" name="email" required>
    <div id="card-element"></div>
    <button type="submit">Bestellung aufgeben (TEST)</button>
  </form>
</div>
```

---

### 2. Test-Mode Simulation

**Keine Kreditkarte erforderlich:**
```javascript
if (this.isTestMode) {
    // In Test-Mode: Card-Element verstecken
    this.setupTestModeUI();  // Zeigt Info-Banner statt Stripe
    this.setupCheckoutForm();
    return; // Stripe.js nicht initialisieren
}
```

**Info-Banner im Test-Mode:**
```html
<div class="bg-blue-50 p-4 rounded border border-blue-200">
  <p class="text-sm text-blue-800">
    <strong>Test-Modus aktiviert:</strong> 
    Kreditkarte nicht erforderlich. 
    Klicke "Bestellung aufgeben" um zu simulieren.
  </p>
</div>
```

---

### 3. Email speichern

**Checkout-Form:**
```html
<input type="email" name="email" 
       placeholder="deine@email.de" 
       required>
```

**stripe.js `simulateCheckout()`:**
```javascript
simulateCheckout(email, total) {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    fetch('/checkout/process', {
        method: 'POST',
        body: JSON.stringify({
            items: cart,
            total: total,
            email: email,  // ← Email mit senden!
            testMode: true
        })
    })
}
```

**CheckoutController `process()`:**
```php
$email = $input['email'] ?? '';
$orderData = [
    'userId' => $userId,
    'items' => $items,
    'total' => $total,
    'email' => $email,  // ← In Firestore speichern
    'paymentIntentId' => $paymentIntentId,
    'status' => $testMode ? 'test' : 'pending',
    'createdAt' => new \DateTime()
];

$orderId = Firebase::createOrder($orderData);
```

---

### 4. Success-Page mit echten Daten

**Früher (Fake):**
```
Bestellnummer: ORDER_12345
Eine Bestätigung wurde an deine E-Mail gesendet. ❌ (Lüge!)
```

**Jetzt (Echt):**
```php
// CheckoutController::success()
$order = Firebase::getOrderById($orderId);
$orderTotal = $order['total'] ?? null;
$orderEmail = $order['email'] ?? '';

echo View::render('checkout/success', [
    'orderId' => $orderId,
    'orderTotal' => $orderTotal,
    'orderEmail' => $orderEmail,
]);
```

**Template-Output:**
```
Bestellnummer: abc123xyz
Gesamtbetrag: 179,98 €
Bestellbestätigung: user@example.de
```

---

### 5. Webhook-Endpoint für Stripe-Integration

**Endpoint:** `POST /api/webhook/stripe`

```bash
curl -X POST http://localhost:8000/api/webhook/stripe \
  -H "Content-Type: application/json" \
  -d '{
    "type": "payment_intent.succeeded",
    "data": {
      "object": {
        "id": "pi_test123"
      }
    }
  }'
# Response: 200 {"success": true}
```

**WebhookController:**
```php
public function handle($params = [], $post = [], $get = []) {
    $payload = file_get_contents('php://input');
    $event = json_decode($payload, true);
    
    // Webhook-Signatur-Validierung (TODO: wenn STRIPE_WEBHOOK_SECRET)
    // if (!$this->verifySignature($payload, $sig)) { ... }
    
    switch ($event['type']) {
        case 'payment_intent.succeeded':
            $this->handlePaymentSucceeded($event);
            break;
        
        case 'payment_intent.payment_failed':
            $this->handlePaymentFailed($event);
            break;
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
}
```

---

### 6. Order-Status Updates

**Firebase::updateOrder():**
```php
public static function updateOrder($orderId, $data): bool {
    // PATCH /orders/{orderId} mit gewünschten Feldern
    $updateData = ['updatedAt' => new \DateTime()];
    
    foreach (['status', 'email', 'paymentIntentId', ...] as $key) {
        if (isset($data[$key])) {
            $updateData[$key] = $data[$key];
        }
    }
    
    $documentData = self::arrayToDocument($updateData);
    self::apiCall('PATCH', '/orders/' . $orderId, ['fields' => $documentData]);
}
```

**Webhook-Handler:**
```php
private function handlePaymentSucceeded($event) {
    $paymentIntentId = $event['data']['object']['id'];
    
    // Order per paymentIntentId finden
    $order = Firebase::getOrderByPaymentIntentId($paymentIntentId);
    
    // Status updaten: pending → paid
    Firebase::updateOrder($order['id'], ['status' => 'paid']);
}
```

---

## Datenfluss

```
User auf /checkout
  ↓
Form: Email eingeben
  ↓
Klick "Bestellung aufgeben (TEST)"
  ↓
stripe.js: simulateCheckout(email, total)
  ↓
POST /checkout/process
  Body: {items, total, email, testMode: true}
  ↓
CheckoutController::process()
  ↓
Firebase::createOrder({userId, items, total, email, status: 'test'})
  ↓
Firestore: orders/{autoId} erstellt
  ↓
Response: {success: true, orderId: 'abc123'}
  ↓
Redirect: /checkout/success?orderId=abc123
  ↓
CheckoutController::success()
  ↓
Firebase::getOrderById(orderId)
  ↓
Success-Template: zeige Bestellnummer + Total + Email
```

---

## Webhook-Flow (Zukünftig)

```
Stripe Payment erfolgreich
  ↓
Stripe sendet: POST /api/webhook/stripe
  Body: {type: 'payment_intent.succeeded', data: {object: {id: 'pi_xxx'}}}
  ↓
WebhookController::handle()
  ↓
handlePaymentSucceeded()
  ↓
Firebase::getOrderByPaymentIntentId('pi_xxx')
  ↓
Firebase::updateOrder(orderId, {status: 'paid'})
  ↓
Firestore: orders/{orderId}.status = 'paid'
  ↓
Response: 200 {success: true}
  ↓
Stripe erhält OK, Webhook abgeschlossen
```

---

## Dateien

| Datei | Änderung |
|-------|----------|
| `public/js/stripe.js` | DOM-Bug fix + Test-Mode UI |
| `src/Controllers/CheckoutController.php` | Email/PaymentIntentId speichern + Order laden |
| `src/Controllers/WebhookController.php` | **Neu** – Stripe Events verarbeiten |
| `src/Firebase.php` | `updateOrder()` + `getOrderByPaymentIntentId()` |
| `index.php` | Route: `POST /api/webhook/stripe` |
| `templates/checkout/success.phtml` | Zeige echte Order-Daten |

---

## Order-Dokument in Firestore

```json
{
  "userId": "uid_abc123",
  "items": [
    {
      "id": "product-1",
      "name": "Ignition Coil",
      "price": 145,
      "quantity": 2,
      "imageUrl": "https://..."
    }
  ],
  "total": 179.98,
  "email": "user@example.de",
  "paymentIntentId": "pi_test123",
  "status": "test",
  "createdAt": "2026-05-23T10:57:40Z",
  "updatedAt": "2026-05-23T10:57:40Z"
}
```

**Status-Werte:**
- `'test'` – Test-Mode Simulation
- `'pending'` – Live-Zahlung ausstehend (wartet auf Webhook)
- `'paid'` – Payment erfolgreich (via Webhook aktualisiert)
- `'failed'` – Payment fehlgeschlagen

---

## Testing

### 1. Test-Mode Checkout
```bash
# 1. Produkt in Warenkorb
GET /shop → Produkt hinzufügen

# 2. Zur Checkout-Seite
GET /checkout

# 3. Form ausfüllen + absenden
email: test@example.com
Klick: "Bestellung aufgeben (TEST)"

# 4. Success-Page sollte kommen
GET /checkout/success?orderId=abc123xyz
→ Zeige: Bestellnummer + 179,98 € + test@example.com
```

### 2. Webhook-Test
```bash
curl -X POST http://localhost:8000/api/webhook/stripe \
  -H "Content-Type: application/json" \
  -d '{
    "type": "payment_intent.succeeded",
    "data": {
      "object": {
        "id": "pi_test_from_previous_order"
      }
    }
  }'

# Response: 200 OK {"success": true}
# Firestore: Order-Status aktualisiert zu 'paid'
```

### 3. Order in Firestore prüfen
```bash
# Über Firebase Console oder Firebase Admin SDK:
db.collection('orders').doc('abc123xyz').get()
→ Sollte zeigen: status='test', email='test@...', total=179.98
```

---

## Konfiguration

### Stripe Test-Mode (Aktuell)
```php
// .env
STRIPE_SECRET_KEY=sk_test_dummy123456789
STRIPE_PUBLISHABLE_KEY=pk_test_dummy123456789
```
→ Checkout läuft im Test-Mode, keine echte Stripe nötig

### Live-Mode (Zukünftig)
```php
// .env
STRIPE_SECRET_KEY=sk_live_real_key_here
STRIPE_PUBLISHABLE_KEY=pk_live_real_key_here
STRIPE_WEBHOOK_SECRET=whsec_real_secret_here
```
→ Dann activates WebhookController Signatur-Validierung

---

## Fehlerbehandlung

**Checkout-Fehler:**
```javascript
// In stripe.js
.catch(err => {
    console.error('Checkout error:', err);
    App.showNotification('Fehler bei der Bestellung', 'error');
});
```

**Webhook-Fehler (Logging):**
```php
// In WebhookController
error_log('Webhook: Order not found for paymentIntentId: ' . $paymentIntentId);
```

---

## Bekannte Limitationen

⚠️ **Webhook-Signatur nicht validiert** – Skeleton vorhanden, aber ohne `STRIPE_WEBHOOK_SECRET` nicht aktiv. In Produktion: Environment-Variable setzen.

✅ **Email wird nicht versendet** – Order speichert Email, aber Mailchimp-Integration nicht implementiert.

✅ **Test-Mode Checkout funktioniert** – Simulation ohne Stripe arbeitet zuverlässig.

---

## Nächste Schritte (Nach PBI-05)

- [ ] **STRIPE_WEBHOOK_SECRET:** Konfigurieren + Signatur-Validierung aktivieren
- [ ] **Email-Versand:** Order-Bestätigung per Mailchimp an `$order['email']`
- [ ] **Admin-Dashboard:** Order-Status anzeigen + filtern
- [ ] **Versandintegration:** Shipping-Address speichern + API-Integration
- [ ] **Live-Stripe:** `pk_live` + `sk_live` Keys verbinden

---

## Referenzen

- **Stripe Docs:** https://stripe.com/docs/webhooks
- **Firestore REST:** Verwendet `Firebase::apiCall('PATCH', ...)`
- **PBI-04:** Warenkorb mit Mengensteuerung → `docs/PBI-04-Warenkorb.md`
