function addToCart(productId, productName, price, imageUrl, maxStock) {
    try {
        const quantityInput = document.getElementById('quantity');
        let quantity = quantityInput ? parseInt(quantityInput.value) : 1;

        if (isNaN(quantity) || quantity <= 0) {
            quantity = 1;
        }

        const cart = JSON.parse(localStorage.getItem('cart')) || [];

        const existingItem = cart.find(item => item.id === productId);
        const existingQuantity = existingItem ? existingItem.quantity : 0;
        const newTotalQuantity = existingQuantity + quantity;

        if (newTotalQuantity > maxStock) {
            quantity = maxStock - existingQuantity;
            if (quantity <= 0) {
                App.showNotification(`Nur ${maxStock} Stück verfügbar. Menge im Warenkorb: ${existingQuantity}`, 'warning');
                if (quantityInput) quantityInput.value = 1;
                return;
            }
            App.showNotification(`Nur ${maxStock} verfügbar. Menge gekürzt auf ${quantity}.`, 'warning');
        }

        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            cart.push({
                id: productId,
                name: productName,
                price: parseFloat(price),
                imageUrl: imageUrl || '',
                quantity: quantity
            });
        }

        localStorage.setItem('cart', JSON.stringify(cart));
        console.log('Cart updated:', cart);

        // Update cart count badge
        if (App && typeof App.updateCartCount === 'function') {
            App.updateCartCount();
        }

        // Trigger cart UI update if on cart page
        if (typeof renderCart === 'function') {
            try {
                renderCart();
                console.log('renderCart called successfully');
            } catch (e) {
                console.error('renderCart error:', e);
            }
        } else {
            console.log('renderCart not available');
        }

        // Dispatch custom event for any listeners
        window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { cart } }));

        App.showNotification(`${productName} wurde zum Warenkorb hinzugefügt!`, 'success');

        if (quantityInput) {
            quantityInput.value = 1;
        }
    } catch (error) {
        console.error('addToCart error:', error);
        App.showNotification('Fehler beim Hinzufügen zum Warenkorb', 'error');
    }
}
