// FRONTEND/js/display-cart.js
document.addEventListener('DOMContentLoaded', function() {
    const cartItemsContainer = document.getElementById('cart-items-container');
    const cartSummaryContainer = document.getElementById('cart-summary-container');
    const emptyCartMessageContainer = document.getElementById('empty-cart-message-container');
    const cartTotalSpan = document.getElementById('cart-total');
    const clearCartButton = document.getElementById('clear-cart-btn');
    const checkoutButton = document.getElementById('checkout-btn');

    function renderCartItems() {
        const cart = getCart(); // 來自 cart.js

        if (!cartItemsContainer || !cartTotalSpan || !cartSummaryContainer || !emptyCartMessageContainer) {
            console.error('One or more cart elements not found on the page.');
            return;
        }

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '';
            cartSummaryContainer.style.display = 'none';
            emptyCartMessageContainer.style.display = 'block';
            return;
        }

        cartSummaryContainer.style.display = 'block';
        emptyCartMessageContainer.style.display = 'none';
        let itemsHTML = '';

        cart.forEach(item => {
            const subtotal = item.price * item.quantity;
            itemsHTML += `
                <div class="cart-item" data-product-id="${item.id}">
                    <img src="${item.image_url || 'placeholder.jpg'}" alt="${escapeHtml(item.name)}">
                    <div class="cart-item-details">
                        <h3>${escapeHtml(item.name)}</h3>
                        <p>型號: ${escapeHtml(item.model_number || '')}</p>
                        <p>單價: $${item.price.toFixed(2)}</p>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-decrease" data-id="${item.id}">-</button>
                        <input type="number" class="item-quantity-input" value="${item.quantity}" min="1" max="${item.stock_quantity}" data-id="${item.id}" data-stock="${item.stock_quantity}">
                        <button class="quantity-increase" data-id="${item.id}">+</button>
                    </div>
                    <div class="cart-item-subtotal">
                        小計: $${subtotal.toFixed(2)}
                    </div>
                    <button class="remove-item-btn" data-id="${item.id}" style="margin-left: 15px; background-color: #ffc107; color: black; border:none; padding: 5px 10px; cursor:pointer; border-radius:3px;">移除</button>
                </div>
            `;
        });

        cartItemsContainer.innerHTML = itemsHTML;
        cartTotalSpan.textContent = getCartTotal().toFixed(2); // 來自 cart.js
        attachCartEventListeners();
    }

    function attachCartEventListeners() {
        // 移除商品按鈕
        document.querySelectorAll('.remove-item-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = parseInt(this.getAttribute('data-id'));
                removeFromCart(productId); // from cart.js
                renderCartItems(); // Re-render
            });
        });

        // 數量減少按鈕
        document.querySelectorAll('.quantity-decrease').forEach(button => {
            button.addEventListener('click', function() {
                const productId = parseInt(this.getAttribute('data-id'));
                const currentQuantity = parseInt(document.querySelector(`.item-quantity-input[data-id="${productId}"]`).value);
                if (currentQuantity > 1) {
                    updateCartItemQuantity(productId, currentQuantity - 1); // from cart.js
                } else {
                    // 如果數量減到0或1，可以選擇移除或保持1
                    if (confirm(`您確定要從購物車中移除 "${getCart().find(item => item.id === productId).name}" 嗎？`)) {
                        removeFromCart(productId);
                    }
                }
                renderCartItems(); // Re-render
            });
        });

        // 數量增加按鈕
        document.querySelectorAll('.quantity-increase').forEach(button => {
            button.addEventListener('click', function() {
                const productId = parseInt(this.getAttribute('data-id'));
                const inputElement = document.querySelector(`.item-quantity-input[data-id="${productId}"]`);
                const currentQuantity = parseInt(inputElement.value);
                const stock = parseInt(inputElement.getAttribute('data-stock'));
                if (currentQuantity < stock) {
                    updateCartItemQuantity(productId, currentQuantity + 1); // from cart.js
                } else {
                    alert('已達庫存上限！');
                }
                renderCartItems(); // Re-render
            });
        });

        // 數量輸入框變更
        document.querySelectorAll('.item-quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const productId = parseInt(this.getAttribute('data-id'));
                let newQuantity = parseInt(this.value);
                const stock = parseInt(this.getAttribute('data-stock'));

                if (isNaN(newQuantity) || newQuantity < 1) {
                    newQuantity = 1;
                }
                if (newQuantity > stock) {
                    alert(`產品庫存僅剩 ${stock} 件，已為您調整購買數量。`);
                    newQuantity = stock;
                    this.value = newQuantity; // 更新輸入框的值
                }
                updateCartItemQuantity(productId, newQuantity); // from cart.js
                renderCartItems(); // Re-render
            });
        });
    }

    if (clearCartButton) {
        clearCartButton.addEventListener('click', function() {
            if (confirm('您確定要清空整個購物車嗎？')) {
                clearCart(); // from cart.js
                renderCartItems(); // Re-render
            }
        });
    }

    if (checkoutButton) {
        checkoutButton.addEventListener('click', function() {
            const isLoggedIn = localStorage.getItem('userData');
            if (!isLoggedIn) {
                alert('請先登入才能結帳！');
                window.location.href = 'login.html?redirect=cart.html'; // 登入後跳回購物車
                return;
            }
            // 跳轉到結帳頁面 (尚未建立)
            alert('準備跳轉到結帳頁面 (此頁面尚未實作)');
            // window.location.href = 'checkout.html';
        });
    }

    // 初始渲染
    renderCartItems();

    // 簡單的 HTML 轉義函數
    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }
});