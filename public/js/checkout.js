function renderCheckoutSummary() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const container = document.getElementById('checkout-summary');

    if (!container) return;

    if (cart.length === 0) {
        window.location.href = '/shop';
        return;
    }

    let html = '<div class="space-y-3">';
    let total = 0;

    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        html += `
            <div class="flex justify-between text-sm">
                <span>${item.name} × ${item.quantity}</span>
                <span>${itemTotal.toFixed(2).replace('.', ',')} €</span>
            </div>
        `;
    });

    html += `
        <div class="border-t pt-3 mt-3 font-bold flex justify-between">
            <span>Gesamtsumme:</span>
            <span>${total.toFixed(2).replace('.', ',')} €</span>
        </div>
    `;

    html += '</div>';
    container.innerHTML = html;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderCheckoutSummary);
} else {
    renderCheckoutSummary();
}
