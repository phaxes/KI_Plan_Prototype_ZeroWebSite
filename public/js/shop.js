function addToCart(productId, productName, price, imageUrl, maxStock) {
    let quantity = parseInt(document.getElementById('quantity').value);
    const cart = JSON.parse(localStorage.getItem('cart')) || [];

    const existingItem = cart.find(item => item.id === productId);
    const existingQuantity = existingItem ? existingItem.quantity : 0;
    const newTotalQuantity = existingQuantity + quantity;

    if (newTotalQuantity > maxStock) {
        quantity = maxStock - existingQuantity;
        if (quantity <= 0) {
            App.showNotification(`Nur ${maxStock} Stück verfügbar. Menge im Warenkorb: ${existingQuantity}`, 'warning');
            document.getElementById('quantity').value = 1;
            return;
        }
        App.showNotification(`Nur ${maxStock} verfügbar. Menge gekürzt auf ${quantity}.`, 'warning');
    }

    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({ id: productId, name: productName, price: price, imageUrl: imageUrl, quantity: quantity });
    }

    localStorage.setItem('cart', JSON.stringify(cart));
    App.updateCartCount();
    if (typeof renderCart === 'function') {
        renderCart();
    }
    App.showNotification(`${productName} wurde zum Warenkorb hinzugefügt!`, 'success');
    document.getElementById('quantity').value = 1;
}
