// FRONTEND/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('前端 JavaScript 已載入！');

    const productListDiv = document.getElementById('product-list');
    const loadingMessage = '<p>載入中...</p>';
    const errorMessage = '<p>無法載入產品列表。請檢查 API 路徑與後端狀態。</p>';

    // !!! 非常重要：根據您的伺服器設定修改此 API 基本路徑 !!!
    // 這裡假設您的 Apache DocumentRoot 是 htdocs，專案在 htdocs/3cmanage/
    // 且 BACKEND/public/.htaccess 中的 RewriteBase 設定為 /3cmanage/BACKEND/public/
    const API_BASE_PATH = '/3Cmanage/BACKEND/public'; // 注意大小寫要和資料夾名稱一致

    if (productListDiv) {
        productListDiv.innerHTML = loadingMessage;

        fetch(`${API_BASE_PATH}/products`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP 錯誤！狀態: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('API 回應:', data);
                if (data && data.data && Array.isArray(data.data) && data.data.length > 0) {
                    let productsHTML = '<div class="products-grid">';
                    data.data.forEach(product => {
                        const imageUrl = product.image_url || 'placeholder.jpg';
                        const productName = escapeHtml(product.name || '未知產品');
                        const productPrice = parseFloat(product.price || 0).toFixed(2);
                        const productStock = parseInt(product.stock_quantity || 0);
                        const stockStatus = productStock > 0 ? `庫存: ${productStock}` : '缺貨';
                        const stockClass = productStock > 0 ? 'in-stock' : 'out-of-stock';

                        productsHTML += `
                            <div class="product-card">
                                <img src="${imageUrl}" alt="${productName}" onerror="this.src='placeholder.jpg';">
                                <h3>${productName}</h3>
                                <p class="price">$${productPrice}</p>
                                <p class="stock ${stockClass}">${stockStatus}</p>
                                <p class="description">${escapeHtml(product.description || '')}</p>
                                ${productStock > 0 ? 
                                    `<button class="add-to-cart-btn" data-product-id="${product.id}" data-product-name="${productName}" data-product-price="${productPrice}" data-product-image="${imageUrl}" data-stock="${productStock}">加入購物車</button>` 
                                    : '<button class="add-to-cart-btn" disabled>缺貨</button>'
                                }
                            </div>
                        `;
                    });
                    productsHTML += '</div>';
                    productListDiv.innerHTML = productsHTML;

                    // 綁定加入購物車按鈕事件
                    attachAddToCartListeners();
                } else {
                    productListDiv.innerHTML = '<p>目前沒有產品可顯示。</p>';
                }
            })
            .catch(error => {
                console.error('載入產品時發生錯誤:', error);
                productListDiv.innerHTML = errorMessage;
            });
    }

    function attachAddToCartListeners() {
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (this.disabled) return;

                const productData = {
                    id: parseInt(this.getAttribute('data-product-id')),
                    name: this.getAttribute('data-product-name'),
                    price: parseFloat(this.getAttribute('data-product-price')),
                    image: this.getAttribute('data-product-image'),
                    stock_quantity: parseInt(this.getAttribute('data-stock'))
                };

                if (typeof addToCart === 'function') {
                    addToCart(productData);
                    this.textContent = '已加入！';
                    this.style.backgroundColor = '#28a745';
                    setTimeout(() => {
                        this.textContent = '加入購物車';
                        this.style.backgroundColor = '';
                    }, 2000);
                } else {
                    console.error('購物車功能尚未載入');
                    alert('購物車功能尚未載入，請稍後再試。');
                }
            });
        });
    }

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