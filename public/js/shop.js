function addToCart(productId, productName, price) {
    const quantity = parseInt(document.getElementById('quantity').value);
    const cart = JSON.parse(localStorage.getItem('cart')) || [];

    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({ id: productId, name: productName, price: price, quantity: quantity });
    }

    localStorage.setItem('cart', JSON.stringify(cart));
    App.updateCartCount();
    App.showNotification(`${productName} wurde zum Warenkorb hinzugefügt!`, 'success');
    document.getElementById('quantity').value = 1;
}
