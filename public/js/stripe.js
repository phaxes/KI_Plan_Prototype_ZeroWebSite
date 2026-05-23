// Stripe Integration Module
const StripeModule = {
    stripe: null,
    elements: null,
    cardElement: null,
    isTestMode: false,

    init: function() {
        // Fix: read from [data-stripe-key] element, not body
        const configElement = document.querySelector('[data-stripe-key]');
        if (!configElement) {
            console.warn('Stripe configuration element not found');
            return;
        }

        const publishableKey = configElement.dataset.stripeKey;
        this.isTestMode = configElement.dataset.stripeTestMode === 'true' || !publishableKey?.startsWith('pk_live');

        // In test mode, we don't need Stripe.js at all
        if (this.isTestMode) {
            this.setupCheckoutForm();
            this.setupTestModeUI();
            return;
        }

        // In live mode, initialize Stripe
        if (!publishableKey) {
            console.warn('Stripe publishable key not found');
            return;
        }

        this.stripe = Stripe(publishableKey);
        this.elements = this.stripe.elements();
        this.setupCardElement();
        this.setupCheckoutForm();
    },

    setupTestModeUI: function() {
        const cardElement = document.getElementById('card-element');
        if (cardElement) {
            cardElement.innerHTML = '<div class="bg-blue-50 p-4 rounded border border-blue-200"><p class="text-sm text-blue-800"><strong>Test-Modus aktiviert:</strong> Kreditkarte nicht erforderlich. Klicke "Bestellung aufgeben" um die Bestellung zu simulieren.</p></div>';
            cardElement.style.minHeight = 'auto';
        }

        // Hide the Kartendaten label
        const labels = document.querySelectorAll('label');
        for (const label of labels) {
            if (label.textContent.includes('Kartendaten')) {
                label.style.display = 'none';
                break;
            }
        }
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
            App.showNotification('Bitte E-Mail ausfüllen', 'error');
            return;
        }

        // In test mode, simulate checkout
        if (this.isTestMode) {
            this.simulateCheckout(email, cartTotal);
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
                this.processOrder(email, result.paymentIntent.id);
            }
        });
    },

    processOrder: function(email, paymentIntentId) {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const total = this.getCartTotal();

        fetch('/checkout/process', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                items: cart,
                total: total,
                email: email,
                paymentIntentId: paymentIntentId
            })
        })
        .then(res => {
            if (res.status === 401) {
                App.showNotification('Bitte melde dich an, um fortzufahren', 'warning');
                window.location.href = '/login?redirect=/checkout';
                return null;
            }
            return res.json();
        })
        .then(data => {
            if (!data) return;
            if (data.success) {
                localStorage.removeItem('cart');
                App.updateCartCount();
                window.location.href = '/checkout/success?orderId=' + data.orderId;
            } else {
                App.showNotification('Fehler beim Speichern der Bestellung', 'error');
            }
        });
    },

    simulateCheckout: function(email, total) {
        // Test mode: simulate checkout
        const cart = JSON.parse(localStorage.getItem('cart')) || [];

        fetch('/checkout/process', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                items: cart,
                total: total,
                email: email,
                testMode: true
            })
        })
        .then(res => {
            if (res.status === 401) {
                App.showNotification('Bitte melde dich an, um fortzufahren', 'warning');
                window.location.href = '/login?redirect=/checkout';
                return null;
            }
            return res.json();
        })
        .then(data => {
            if (!data) return;
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
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    StripeModule.init();
});
