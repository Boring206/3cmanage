/**
 * 購物車模組 - 處理商品的加入、移除、數量變更及相關操作
 */
class ShoppingCart {
    constructor() {
        this.items = [];
        this.cartKey = 'shopping_cart';
        this.init();
    }

    /**
     * 初始化購物車
     */
    init() {
        // 從 localStorage 讀取購物車數據
        const savedCart = localStorage.getItem(this.cartKey);
        if (savedCart) {
            try {
                this.items = JSON.parse(savedCart);
            } catch (e) {
                console.error('購物車數據解析錯誤:', e);
                this.items = [];
                localStorage.removeItem(this.cartKey);
            }
        }

        // 更新購物車計數器顯示
        this.updateCartCountDisplay();

        // 如果在購物車頁面，則顯示購物車內容
        if (window.location.pathname.includes('cart.html')) {
            this.renderCart();
        }

        // 設置購物車按鈕事件
        const viewCartBtn = document.getElementById('view-cart');
        if (viewCartBtn) {
            viewCartBtn.addEventListener('click', () => {
                window.location.href = 'cart.html';
            });
        }
    }

    /**
     * 將商品加入購物車
     * @param {Object} product - 要添加的商品對象
     * @param {number} quantity - 商品數量
     * @returns {boolean} - 操作是否成功
     */
    addItem(product, quantity = 1) {
        if (!product || !product.id || quantity <= 0) {
            console.error('無效的商品或數量');
            return false;
        }

        // 檢查商品是否已在購物車中
        const existingItemIndex = this.items.findIndex(item => item.id === product.id);

        if (existingItemIndex !== -1) {
            // 商品已存在，增加數量
            this.items[existingItemIndex].quantity += quantity;
        } else {
            // 添加新商品到購物車
            this.items.push({
                id: product.id,
                name: product.name,
                price: product.price,
                image: product.image_url || 'placeholder.jpg',
                quantity: quantity,
                stock: product.stock_quantity || 0
            });
        }

        // 保存到本地存儲
        this.saveCart();
        this.updateCartCountDisplay();

        return true;
    }

    /**
     * 從購物車中移除商品
     * @param {number} productId - 要移除的商品ID
     * @returns {boolean} - 操作是否成功
     */
    removeItem(productId) {
        const initialCount = this.items.length;
        this.items = this.items.filter(item => item.id !== productId);

        if (initialCount !== this.items.length) {
            this.saveCart();
            this.updateCartCountDisplay();
            return true;
        }
        return false;
    }

    /**
     * 更新購物車中商品的數量
     * @param {number} productId - 商品ID
     * @param {number} quantity - 新的數量
     * @returns {boolean} - 操作是否成功
     */
    updateQuantity(productId, quantity) {
        if (quantity <= 0) {
            return this.removeItem(productId);
        }

        const itemIndex = this.items.findIndex(item => item.id === productId);
        if (itemIndex !== -1) {
            // 檢查庫存限制
            if (this.items[itemIndex].stock && quantity > this.items[itemIndex].stock) {
                alert(`商品庫存不足，最多只能購買 ${this.items[itemIndex].stock} 件`);
                this.items[itemIndex].quantity = this.items[itemIndex].stock;
            } else {
                this.items[itemIndex].quantity = quantity;
            }
            
            this.saveCart();
            this.updateCartCountDisplay();
            return true;
        }
        return false;
    }

    /**
     * 清空購物車
     */
    clearCart() {
        this.items = [];
        this.saveCart();
        this.updateCartCountDisplay();
    }

    /**
     * 計算購物車中的商品總數
     * @returns {number} - 商品總數
     */
    getTotalItems() {
        return this.items.reduce((total, item) => total + item.quantity, 0);
    }

    /**
     * 計算購物車總金額
     * @returns {number} - 總金額
     */
    getTotalPrice() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    /**
     * 保存購物車到本地存儲
     */
    saveCart() {
        localStorage.setItem(this.cartKey, JSON.stringify(this.items));
    }

    /**
     * 更新購物車數量顯示
     */
    updateCartCountDisplay() {
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            cartCount.textContent = this.getTotalItems().toString();
        }
    }

    /**
     * 渲染購物車頁面內容
     */
    renderCart() {
        const cartContainer = document.getElementById('cart-items');
        if (!cartContainer) return;

        if (this.items.length === 0) {
            cartContainer.innerHTML = '<p class="empty-cart">您的購物車是空的。</p>';
            this.updateOrderSummary(0, 0);
            return;
        }

        let html = '<div class="cart-items-list">';
        this.items.forEach(item => {
            const itemTotal = item.price * item.quantity;
            html += `
                <div class="cart-item" data-id="${item.id}">
                    <div class="cart-item-image">
                        <img src="${item.image}" alt="${this.escapeHtml(item.name)}">
                    </div>
                    <div class="cart-item-details">
                        <h3>${this.escapeHtml(item.name)}</h3>
                        <p class="price">$${item.price.toFixed(2)}</p>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn decrease">-</button>
                        <input type="number" min="1" max="${item.stock || 99}" value="${item.quantity}" class="quantity-input">
                        <button class="quantity-btn increase">+</button>
                    </div>
                    <div class="cart-item-total">
                        $${itemTotal.toFixed(2)}
                    </div>
                    <button class="remove-item">×</button>
                </div>`;
        });
        html += '</div>';

        cartContainer.innerHTML = html;

        // 添加事件監聽器
        this.addCartItemEventListeners();
        
        // 更新訂單摘要
        this.updateOrderSummary();
    }

    /**
     * 更新訂單摘要
     */
    updateOrderSummary() {
        const subtotal = this.getTotalPrice();
        const shipping = subtotal > 0 ? 100 : 0; // 簡單的固定運費
        const total = subtotal + shipping;

        const summaryContainer = document.getElementById('order-summary');
        if (summaryContainer) {
            summaryContainer.innerHTML = `
                <div class="summary-row">
                    <span>商品小計:</span>
                    <span>$${subtotal.toFixed(2)}</span>
                </div>
                <div class="summary-row">
                    <span>運費:</span>
                    <span>$${shipping.toFixed(2)}</span>
                </div>
                <div class="summary-row total">
                    <span>總計:</span>
                    <span>$${total.toFixed(2)}</span>
                </div>
            `;
        }

        // 啟用/禁用結帳按鈕
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) {
            if (this.items.length === 0) {
                checkoutBtn.disabled = true;
                checkoutBtn.classList.add('disabled');
            } else {
                checkoutBtn.disabled = false;
                checkoutBtn.classList.remove('disabled');
            }
        }
    }

    /**
     * 為購物車項目添加事件監聽器
     */
    addCartItemEventListeners() {
        // 刪除按鈕事件
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', () => {
                const cartItem = button.closest('.cart-item');
                const itemId = parseInt(cartItem.dataset.id);
                this.removeItem(itemId);
                this.renderCart();
            });
        });

        // 數量變更事件
        document.querySelectorAll('.quantity-input').forEach(input => {
            const cartItem = input.closest('.cart-item');
            const itemId = parseInt(cartItem.dataset.id);
            
            // 輸入變更事件
            input.addEventListener('change', () => {
                let value = parseInt(input.value);
                if (isNaN(value) || value < 1) {
                    value = 1;
                    input.value = '1';
                }
                const maxStock = parseInt(input.getAttribute('max'));
                if (maxStock && value > maxStock) {
                    value = maxStock;
                    input.value = maxStock.toString();
                    alert(`最多只能購買 ${maxStock} 件此商品`);
                }
                this.updateQuantity(itemId, value);
                this.renderCart();
            });

            // 增加按鈕
            cartItem.querySelector('.increase').addEventListener('click', () => {
                let value = parseInt(input.value) + 1;
                const maxStock = parseInt(input.getAttribute('max'));
                if (maxStock && value > maxStock) {
                    value = maxStock;
                    alert(`最多只能購買 ${maxStock} 件此商品`);
                }
                input.value = value.toString();
                this.updateQuantity(itemId, value);
                this.renderCart();
            });

            // 減少按鈕
            cartItem.querySelector('.decrease').addEventListener('click', () => {
                let value = parseInt(input.value) - 1;
                if (value < 1) value = 1;
                input.value = value.toString();
                this.updateQuantity(itemId, value);
                this.renderCart();
            });
        });
    }

    /**
     * 簡單的 HTML 轉義函數，防止 XSS
     * @param {string} unsafe - 未轉義的字符串
     * @returns {string} - 轉義後的字符串
     */
    escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }
}

// 創建購物車實例並掛載到全局對象
const shoppingCart = new ShoppingCart();
window.shoppingCart = shoppingCart;

// 監聽 DOM 完成加載
document.addEventListener('DOMContentLoaded', function() {
    // 為商品頁面上的加入購物車按鈕添加事件
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = parseInt(this.dataset.productId);
            const productName = this.dataset.productName;
            const productPrice = parseFloat(this.dataset.productPrice);
            const productImage = this.dataset.productImage;
            const productStock = parseInt(this.dataset.productStock);
            
            const product = {
                id: productId,
                name: productName,
                price: productPrice,
                image_url: productImage,
                stock_quantity: productStock
            };
            
            if (shoppingCart.addItem(product, 1)) {
                alert(`${productName} 已成功加入購物車！`);
            }
        });
    });
    
    // 商品詳情頁面的加入購物車按鈕
    const detailAddToCartBtn = document.getElementById('add-to-cart-btn');
    if (detailAddToCartBtn) {
        detailAddToCartBtn.addEventListener('click', function() {
            const productId = parseInt(this.dataset.productId);
            // 從當前頁面的顯示內容中獲取產品信息
            const productName = document.querySelector('#product-detail-content h2').textContent;
            const priceText = document.querySelector('#product-detail-content .price') || 
                             document.querySelector('#product-detail-content strong:contains("價格")');
            const productPrice = priceText ? parseFloat(priceText.textContent.replace(/[^0-9.]/g, '')) : 0;
            const productImage = document.querySelector('#product-detail-content img').src;
            const stockText = document.querySelector('#product-detail-content .stock') || 
                             document.querySelector('#product-detail-content strong:contains("庫存")');
            const productStock = stockText ? parseInt(stockText.textContent.replace(/[^0-9]/g, '')) : 0;
            
            const product = {
                id: productId,
                name: productName,
                price: productPrice,
                image_url: productImage,
                stock_quantity: productStock
            };
            
            const quantity = document.getElementById('quantity') ? 
                           parseInt(document.getElementById('quantity').value) : 1;
            
            if (shoppingCart.addItem(product, quantity)) {
                alert(`${productName} 已成功加入購物車！`);
            }
        });
    }
});
