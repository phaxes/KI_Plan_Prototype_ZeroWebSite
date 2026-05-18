// Stripe Integration Module
const StripeModule = {
    stripe: null,
    elements: null,
    cardElement: null,

    init: function() {
        const publishableKey = document.body.dataset.stripeKey;
        if (!publishableKey) {
            console.warn('Stripe publishable key not found');
            return;
        }

        this.stripe = Stripe(publishableKey);
        this.elements = this.stripe.elements();
        this.setupCardElement();
        this.setupCheckoutForm();
    },

    setupCardElement: function() {
        const cardElement = document.getElementById('card-element');
        if (!cardElement) return;

        this.cardElement = this.elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#424770'
                },
                invalid: {
                    color: '#9e2146'
                }
            }
        });

        this.cardElement.mount('#card-element');
        this.cardElement.addEventListener('change', (event) => {
            const errorDiv = document.getElementById('card-errors');
            if (event.error) {
                errorDiv.textContent = event.error.message;
            } else {
                errorDiv.textContent = '';
            }
        });
    },

    setupCheckoutForm: function() {
        const checkoutForm = document.getElementById('checkout-form');
        if (!checkoutForm) return;

        checkoutForm.addEventListener('submit', (e) => this.handleCheckout(e));
    },

    handleCheckout: function(e) {
        e.preventDefault();

        const email = document.querySelector('input[name="email"]').value;
        const cartTotal = this.getCartTotal();

        if (!email || cartTotal === 0) {
            App.showNotification('Bitte alle Felder ausfüllen', 'error');
            return;
        }

        // In test mode, just simulate success
        if (this.isTestMode()) {
            this.simulateCheckout(cartTotal);
            return;
        }

        // In production, create payment intent
        this.createPaymentIntent(cartTotal, email);
    },

    createPaymentIntent: function(amount, email) {
        fetch('/api/checkout/intent', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ amount: amount, email: email })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.confirmPayment(data.clientSecret, email);
            } else {
                App.showNotification('Fehler: ' + data.error, 'error');
            }
        })
        .catch(err => {
            console.error('Intent creation error:', err);
            App.showNotification('Fehler bei der Zahlungsverarbeitung', 'error');
        });
    },

    confirmPayment: function(clientSecret, email) {
        this.stripe.confirmCardPayment(clientSecret, {
            payment_method: {
                card: this.cardElement,
                billing_details: { email: email }
            }
        })
        .then((result) => {
            if (result.error) {
                App.showNotification(result.error.message, 'error');
            } else {
                // Payment successful
                this.processOrder(result.paymentIntent.id);
            }
        });
    },

    processOrder: function(paymentIntentId) {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const total = this.getCartTotal();

        fetch('/checkout/process', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                items: cart,
                total: total,
                paymentIntentId: paymentIntentId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                localStorage.removeItem('cart');
                App.updateCartCount();
                window.location.href = '/checkout/success?orderId=' + data.orderId;
            } else {
                App.showNotification('Fehler beim Speichern der Bestellung', 'error');
            }
        });
    },

    simulateCheckout: function(total) {
        // Test mode: simulate checkout
        const cart = JSON.parse(localStorage.getItem('cart')) || [];

        fetch('/checkout/process', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                items: cart,
                total: total,
                testMode: true
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                localStorage.removeItem('cart');
                App.updateCartCount();
                window.location.href = '/checkout/success?orderId=' + data.orderId;
            }
        })
        .catch(err => {
            console.error('Checkout error:', err);
            App.showNotification('Fehler bei der Bestellung', 'error');
        });
    },

    getCartTotal: function() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    },

    isTestMode: function() {
        // Check if Stripe key indicates test mode
        const body = document.body;
        return body.dataset.stripeTestMode === 'true' ||
               !document.body.dataset.stripeKey?.startsWith('pk_live');
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    StripeModule.init();
});
