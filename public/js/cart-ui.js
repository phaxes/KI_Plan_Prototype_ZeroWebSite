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

    cart.forEach((item) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        const imageHtml = item.imageUrl
            ? `<img src="${item.imageUrl}" alt="${item.name}" class="w-24 h-24 object-cover rounded mr-4">`
            : `<div class="w-24 h-24 bg-gray-200 rounded flex items-center justify-center mr-4 text-xs text-gray-400">Kein Bild</div>`;

        html += `
            <div class="flex justify-between items-center p-4 border-b gap-4">
                <div class="flex flex-1">
                    ${imageHtml}
                    <div>
                        <h3 class="font-semibold">${item.name}</h3>
                        <p class="text-gray-600 text-sm">${item.price.toFixed(2).replace('.', ',')} € pro Stück</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="flex items-center gap-2 mb-2 justify-end">
                        <button onclick="changeQuantity('${item.id}', -1)" class="bg-gray-200 hover:bg-gray-300 w-8 h-8 rounded transition">−</button>
                        <span class="w-8 text-center font-semibold">${item.quantity}</span>
                        <button onclick="changeQuantity('${item.id}', 1)" class="bg-gray-200 hover:bg-gray-300 w-8 h-8 rounded transition">+</button>
                    </div>
                    <div class="font-bold mb-2">${itemTotal.toFixed(2).replace('.', ',')} €</div>
                    <button onclick="removeFromCart('${item.id}')" class="text-red-500 hover:text-red-700 transition text-sm">Entfernen</button>
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
    document.getElementById('cart-total').textContent = total.toFixed(2).replace('.', ',') + ' €';
}

async function changeQuantity(productId, delta) {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const itemIndex = cart.findIndex(item => item.id === productId);

    if (itemIndex === -1) return;

    const currentQty = cart[itemIndex].quantity;
    const newQty = currentQty + delta;

    if (newQty <= 0) {
        removeFromCart(productId);
        return;
    }

    try {
        const response = await fetch(`/api/product/${productId}/stock`);
        if (!response.ok) {
            App.showNotification('Fehler beim Abrufen des Lagerbestands', 'error');
            return;
        }

        const data = await response.json();
        const maxStock = data.stock;

        if (newQty > maxStock) {
            App.showNotification(`Nur ${maxStock} verfügbar`, 'warning');
            cart[itemIndex].quantity = maxStock;
        } else {
            cart[itemIndex].quantity = newQty;
        }
    } catch (error) {
        console.error('Stock check failed:', error);
        App.showNotification('Fehler beim Überprüfen des Lagerbestands', 'error');
        return;
    }

    localStorage.setItem('cart', JSON.stringify(cart));
    App.updateCartCount();
    renderCart();
}

function removeFromCart(productId) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart = cart.filter(item => item.id !== productId);
    localStorage.setItem('cart', JSON.stringify(cart));
    App.updateCartCount();
    renderCart();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderCart);
} else {
    renderCart();
}

window.addEventListener('storage', (e) => {
    if (e.key === 'cart' || e.key === null) {
        renderCart();
    }
});
