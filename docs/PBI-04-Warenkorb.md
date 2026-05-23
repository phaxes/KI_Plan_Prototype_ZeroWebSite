# PBI-04: Einfacher Warenkorb mit Mengensteuerung

## Übersicht
Komplette Implementierung eines funktionsfähigen Warenkorbs mit:
- **Mengen-Steuerung:** +/− Buttons für jedes Item
- **Lagerbestandsprüfung:** Echtzeit-Validierung gegen Firestore
- **Lokale Persistenz:** localStorage für Browser-Session
- **Robuste Verwaltung:** ID-basiert statt Index-basiert

---

## Features

### 1. Stock-Check API
**Endpoint:** `GET /api/product/{id}/stock`

```bash
curl http://localhost:8000/api/product/product-1/stock
# Response: {"stock": 3}
```

**In:** `src/Controllers/CartController.php`
```php
public function stock($params = [], $post = [], $get = []) {
    $product = Firebase::getProductById($productId);
    echo json_encode(['stock' => (int)($product['stock'] ?? 0)]);
}
```

---

### 2. Mengensteuerung im Warenkorb

**Buttons im Warenkorb-HTML:**
```html
<button onclick="changeQuantity('product-1', -1)">−</button>
<span>2</span>
<button onclick="changeQuantity('product-1', 1)">+</button>
```

**JavaScript-Funktion:**
```javascript
async function changeQuantity(productId, delta) {
    // 1. Aktuelle Menge laden
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const item = cart.find(i => i.id === productId);
    
    // 2. Neue Menge berechnen
    const newQty = item.quantity + delta;
    
    // 3. Lagerbestand prüfen
    const response = await fetch(`/api/product/${productId}/stock`);
    const data = await response.json();
    
    // 4. Validieren & kappen
    if (newQty > data.stock) {
        item.quantity = data.stock;
        App.showNotification(`Nur ${data.stock} verfügbar`, 'warning');
    } else {
        item.quantity = newQty;
    }
    
    // 5. Speichern & neu rendern
    localStorage.setItem('cart', JSON.stringify(cart));
    renderCart();
}
```

---

### 3. Stock-Validierung beim Hinzufügen

**Shop-Detail-Seite:**
```html
<input type="number" id="quantity" min="1" max="3" value="1">
<button onclick="addToCart('product-1', 'Name', 145, 'imageUrl', 3)">
    In den Warenkorb
</button>
```

**shop.js `addToCart()`:**
- ✅ Prüft: `existingQty + newQty <= maxStock`
- ✅ Kürzt: Wenn über Lagerbestand → auf max gekürzt
- ✅ Warnt: Notification wenn gekürzt
- ✅ Speichert: `imageUrl` im localStorage

---

### 4. localStorage Schema

```json
[
  {
    "id": "product-1",
    "name": "Ignition Coil",
    "price": 145,
    "quantity": 2,
    "imageUrl": "https://example.com/img.jpg"
  },
  {
    "id": "product-2",
    "name": "Water Pump",
    "price": 34.95,
    "quantity": 1,
    "imageUrl": "https://example.com/img2.jpg"
  }
]
```

---

## Dateien

| Datei | Änderung |
|-------|----------|
| `index.php` | Route: `GET /api/product/{id}/stock` |
| `src/Controllers/CartController.php` | Neue `stock()` Methode |
| `templates/shop/show.phtml` | `max` Attribut + 5 Parameter an `addToCart()` |
| `public/js/shop.js` | `addToCart()` mit Stock-Validierung |
| `public/js/cart-ui.js` | Komplett neu: `renderCart()`, `changeQuantity()`, `removeFromCart()` |

---

## Edge Cases

### Menge über Lagerbestand
```javascript
// User klickt + wenn qty=2, stock=3
changeQuantity('product-1', 1)
// → qty wird 3 (OK)

// User klickt + wenn qty=3, stock=3
changeQuantity('product-1', 1)
// → qty bleibt 3, Warning: "Nur 3 verfügbar"
```

### Menge auf 0
```javascript
changeQuantity('product-1', -1)  // qty war 1
// → Item wird entfernt: cart.filter(item => item.id !== productId)
```

### Item mehrmals hinzufügen
```javascript
addToCart('product-1', ..., ..., maxStock=3)
// Wenn bereits 2× im Warenkorb → nur 1 mehr hinzugefügt
```

---

## API & Fehlerbehandlung

**Stock-API Responses:**
```bash
# Existierendes Produkt
GET /api/product/product-1/stock
→ 200 OK: {"stock": 3}

# Nicht vorhanden
GET /api/product/nonexistent/stock
→ 404 Not Found: {"error": "Not found"}
```

**JavaScript Error-Handling:**
```javascript
try {
    const response = await fetch(`/api/product/${productId}/stock`);
    if (!response.ok) {
        App.showNotification('Fehler beim Überprüfen des Lagerbestands', 'error');
        return;
    }
    const data = await response.json();
} catch (error) {
    console.error('Stock check failed:', error);
    App.showNotification('Fehler bei der Bestellung', 'error');
}
```

---

## Testing

### 1. Stock-API
```bash
curl http://localhost:8000/api/product/product-1/stock
# Sollte: {"stock": N}
```

### 2. Warenkorb-Mengensteuerung
1. Produkt hinzufügen
2. `/cart` aufrufen
3. +/− Buttons klicken
4. Über Lagerbestand → Warning
5. Auf 0 → Item entfernt

### 3. Persistenz
- Warenkorb bleibt nach F5 erhalten ✅
- Nur localStorage (keine DB-Writes) ✅

---

## Bekannte Limitationen

⚠️ **imageUrl ist leer** – Firebase `getProductById()` gibt `image`/`imageUrl` nicht zurück. Fallback: Platzhalter-Box wenn leer.

✅ **Lagerprüfung ist async** – Bei Netzwerk-Latenz kurzes Verzögerung beim +-Klick (normal, akzeptabel).

---

## Nächste Schritte (PBI-05+)

- [ ] **PBI-05:** Checkout-Simulation + Webhook
- [ ] **Später:** Email-Benachrichtigungen
- [ ] **Später:** Lagerwarnungen für Admin
- [ ] **Später:** Wishlist / Mergeliste
