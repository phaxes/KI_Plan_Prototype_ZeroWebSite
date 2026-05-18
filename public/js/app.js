// Global App Module
const App = {
    init: function() {
        console.log('App initialized');
        this.updateCartCount();
        this.setupEventListeners();
    },

    updateCartCount: function() {
        try {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            const elements = document.querySelectorAll('#cart-count, #cart-count-mobile');
            elements.forEach(el => el.textContent = count);
        } catch (err) {
            console.error('Error updating cart count:', err);
        }
    },

    setupEventListeners: function() {
        // Listen for cart changes
        document.addEventListener('cart-updated', () => {
            this.updateCartCount();
        });
    },

    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded shadow-lg text-white notification-${type}`;
        notification.textContent = message;

        if (type === 'success') {
            notification.style.backgroundColor = '#10B981';
        } else if (type === 'error') {
            notification.style.backgroundColor = '#EF4444';
        } else {
            notification.style.backgroundColor = '#3B82F6';
        }

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 4000);
    },

    formatPrice: function(price) {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR'
        }).format(price);
    }
};

// Initialize app on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
