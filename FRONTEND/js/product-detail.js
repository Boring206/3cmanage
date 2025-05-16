// FRONTEND/js/product-detail.js
document.addEventListener('DOMContentLoaded', function() {
    const productDetailDiv = document.getElementById('product-detail-content');
    const loadingMessage = '<p>載入產品詳情中...</p>';
    const errorMessage = '<p>無法載入產品詳情。</p>';    // !!! 非常重要：根據您的伺服器設定修改此 API 基本路徑 !!!
    const API_BASE_PATH = '/3Cmanage/BACKEND/public'; // 注意大小寫要和資料夾名稱一致

    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id');

    if (!productId) {
        productDetailDiv.innerHTML = '<p>未提供產品 ID。</p>';
        return;
    }

    if (productDetailDiv) {
        productDetailDiv.innerHTML = loadingMessage;
    }

    fetch(`${API_BASE_PATH}/products/${productId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('網路回應錯誤: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (productDetailDiv && data && data.data) {
                const product = data.data;
                let specificationsHtml = '<h4>規格：</h4><ul>';
                if (product.specifications && typeof product.specifications === 'object') {
                    for (const key in product.specifications) {
                        specificationsHtml += `<li><strong>${escapeHtml(key)}:</strong> ${escapeHtml(String(product.specifications[key]))}</li>`;
                    }
                } else if (typeof product.specifications === 'string') {
                     try {
                        const specsObj = JSON.parse(product.specifications);
                        for (const key in specsObj) {
                            specificationsHtml += `<li><strong>${escapeHtml(key)}:</strong> ${escapeHtml(String(specsObj[key]))}</li>`;
                        }
                     } catch (e) {
                        specificationsHtml += `<li>${escapeHtml(product.specifications)}</li>`;
                     }
                } else {
                    specificationsHtml += '<li>無詳細規格</li>';
                }
                specificationsHtml += '</ul>';

                productDetailDiv.innerHTML = `
                    <h2>${escapeHtml(product.name)}</h2>
                    <img src="${product.image_url || 'placeholder.jpg'}" alt="${escapeHtml(product.name)}" style="max-width:300px; height:auto;">
                    <p><strong>品牌:</strong> ${escapeHtml(product.brand)}</p>
                    <p><strong>型號:</strong> ${escapeHtml(product.model_number)}</p>
                    <p><strong>分類:</strong> ${escapeHtml(product.category)}</p>
                    <p><strong>價格:</strong> $${product.price}</p>
                    <p><strong>庫存:</strong> ${product.stock_quantity > 0 ? product.stock_quantity : '無庫存'}</p>
                    <p><strong>描述:</strong> ${escapeHtml(product.description || '')}</p>
                    ${specificationsHtml}
                    <p><strong>預設保固:</strong> ${product.default_warranty_months} 個月</p>
                    <button id="add-to-cart-btn" data-product-id="${product.id}" ${product.stock_quantity <= 0 ? 'disabled' : ''}>
                        ${product.stock_quantity > 0 ? '加入購物車' : '已售完'}
                    </button>
                `;
                // 可以為加入購物車按鈕添加事件監聽器
                const addToCartButton = document.getElementById('add-to-cart-btn');
                if (addToCartButton) {
                    addToCartButton.addEventListener('click', function() {
                        const productId = this.getAttribute('data-product-id');
                        alert(`產品 ID: ${productId} 已加入購物車 (此為範例功能)`);
                        // 在這裡實現真正的加入購物車邏輯
                        // 例如，將產品 ID 和數量存儲到 localStorage
                        // addToCart(productId, 1);
                    });
                }
            } else {
                productDetailDiv.innerHTML = errorMessage;
            }
        })
        .catch(error => {
            console.error('獲取產品詳情時發生錯誤:', error);
            if (productDetailDiv) {
                productDetailDiv.innerHTML = errorMessage;
            }
        });

    // 簡單的 HTML 轉義函數，防止 XSS
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