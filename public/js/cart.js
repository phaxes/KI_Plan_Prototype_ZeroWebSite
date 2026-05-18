const CartModule = {
    cartKey: 'cart',

    init: function() {
        this.updateCartCount();
        this.setupCartButtons();
    },

    addToCart: function(product) {
        let cart = this.getCart();

        const existingItem = cart.find(item => item.id === product.id);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1,
                imageUrl: product.imageUrl || ''
            });
        }

        this.saveCart(cart);
        this.updateCartCount();
        App.showNotification(product.name + ' zum Warenkorb hinzugefügt', 'success');
    },

    removeFromCart: function(productId) {
        let cart = this.getCart();
        cart = cart.filter(item => item.id !== productId);
        this.saveCart(cart);
        this.updateCartCount();
    },

    updateQuantity: function(productId, quantity) {
        let cart = this.getCart();
        const item = cart.find(item => item.id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeFromCart(productId);
            } else {
                item.quantity = quantity;
                this.saveCart(cart);
                this.updateCartCount();
            }
        }
    },

    getCart: function() {
        const stored = localStorage.getItem(this.cartKey);
        return stored ? JSON.parse(stored) : [];
    },

    saveCart: function(cart) {
        localStorage.setItem(this.cartKey, JSON.stringify(cart));
    },

    getCartTotal: function() {
        const cart = this.getCart();
        return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    },

    getCartCount: function() {
        const cart = this.getCart();
        return cart.reduce((count, item) => count + item.quantity, 0);
    },

    updateCartCount: function() {
        const count = this.getCartCount();
        const badge = document.getElementById('cart-count');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    },

    setupCartButtons: function() {
        document.querySelectorAll('[data-product-id]').forEach(button => {
            if (!button.dataset.setupDone) {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productId = button.dataset.productId;
                    const productName = button.dataset.productName || 'Product';
                    const productPrice = parseFloat(button.dataset.productPrice || 0);
                    const productImage = button.dataset.productImage || '';

                    this.addToCart({
                        id: productId,
                        name: productName,
                        price: productPrice,
                        imageUrl: productImage
                    });
                });
                button.dataset.setupDone = 'true';
            }
        });
    },

    clearCart: function() {
        localStorage.removeItem(this.cartKey);
        this.updateCartCount();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    CartModule.init();
});
