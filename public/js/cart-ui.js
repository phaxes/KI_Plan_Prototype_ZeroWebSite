function renderCart() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const container = document.getElementById('cart-items-container');
    const summary = document.getElementById('cart-summary');
    const empty = document.getElementById('cart-empty');

    if (!container) return;

    if (cart.length === 0) {
        empty.classList.remove('hidden');
        summary.classList.add('hidden');
        container.innerHTML = '';
        return;
    }

    empty.classList.add('hidden');
    summary.classList.remove('hidden');

    let html = '<div class="space-y-4">';
    let total = 0;

    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        html += `
            <div class="flex justify-between items-center p-4 border-b">
                <div>
                    <h3 class="font-semibold">${item.name}</h3>
                    <p class="text-gray-600 text-sm">${item.price.toFixed(2).replace('.', ',')} € × ${item.quantity}</p>
                </div>
                <div class="text-right">
                    <div class="font-bold mb-2">${itemTotal.toFixed(2).replace('.', ',')} €</div>
                    <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-red-700 transition text-sm">Entfernen</button>
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
    document.getElementById('cart-total').textContent = total.toFixed(2).replace('.', ',') + ' €';
}

function removeFromCart(index) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart.splice(index, 1);
    localStorage.setItem('cart', JSON.stringify(cart));
    App.updateCartCount();
    renderCart();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderCart);
} else {
    renderCart();
}
