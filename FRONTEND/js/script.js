// FRONTEND/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('前端 JavaScript 已載入！');

    const productListDiv = document.getElementById('product-list');
    const loadingMessage = '<p>載入中...</p>';
    const errorMessage = '<p>無法載入產品列表。請檢查 API 路徑與後端狀態。</p>';    // !!! 非常重要：根據您的伺服器設定修改此 API 基本路徑 !!!
    // 這裡假設您的 Apache DocumentRoot 是 htdocs，專案在 htdocs/3cmanage/
    // 且 BACKEND/public/.htaccess 中的 RewriteBase 設定為 /3cmanage/BACKEND/public/
    const API_BASE_PATH = '/3Cmanage/BACKEND/public'; // 注意大小寫要和資料夾名稱一致

    if (productListDiv) {
        productListDiv.innerHTML = loadingMessage;
        fetchProducts();
    }

    function fetchProducts(queryParams = {}) {
        // 构建帶有查詢參數的 URL
        // window.location.origin 通常是 http://localhost
        // 所以 API_BASE_PATH 必須是從網域根開始的絕對路徑
        const url = new URL(`${API_BASE_PATH}/products`, window.location.origin);
        Object.keys(queryParams).forEach(key => {
            if (queryParams[key] !== null && queryParams[key] !== undefined) {
                url.searchParams.append(key, queryParams[key]);
            }
        });

        console.log(`正在從 ${url.href} 獲取產品...`); // 使用 url.href 確保是完整路徑

        fetch(url.href) // 使用 url.href
            .then(response => {
                if (!response.ok) {
                    console.error('網路回應錯誤:', response.status, response.statusText);
                    // 嘗試讀取回應內容，即使是錯誤回應，也可能有 HTML 或 JSON 格式的錯誤訊息
                    return response.text().then(text => {
                        // 試圖解析 HTML 中的錯誤訊息
                        let detailMessage = text;
                        const titleMatch = text.match(/<title>(.*?)<\/title>/i);
                        const h1Match = text.match(/<h1>(.*?)<\/h1>/i);
                        if (titleMatch && titleMatch[1] && h1Match && h1Match[1]) {
                            detailMessage = `${titleMatch[1]}: ${h1Match[1]}`;
                        }
                        throw new Error(`網路回應錯誤: ${response.status} ${response.statusText}. 內容: ${detailMessage}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (productListDiv) {
                    if (data && data.data && Array.isArray(data.data) && data.data.length > 0) { // 檢查 data.data 是否為陣列
                        let html = '<ul class="products-grid">'; // 使用 class 以便 CSS 控制
                        data.data.forEach(product => {
                            html += `
                                <li class="product-card">
                                    <img src="${product.image_url || 'placeholder.jpg'}" alt="${escapeHtml(product.name)}">
                                    <div class="product-card-content">
                                        <h3><a href="product-detail.html?id=${product.id}">${escapeHtml(product.name)}</a></h3>
                                        <p>品牌: ${escapeHtml(product.brand)} - 型號: ${escapeHtml(product.model_number)}</p>
                                        <p class="price">價格: $${product.price}</p>
                                        <p class="stock">庫存: ${product.stock_quantity > 0 ? product.stock_quantity : '無庫存'}</p>
                                        <button class="add-to-cart-btn btn" 
                                                data-product-id="${product.id}"
                                                data-product-name="${escapeHtml(product.name)}"
                                                data-product-price="${product.price}"
                                                data-product-image="${product.image_url || 'placeholder.jpg'}"
                                                data-product-stock="${product.stock_quantity}"
                                                ${product.stock_quantity <= 0 ? 'disabled class="out-of-stock"' : ''}>
                                            ${product.stock_quantity > 0 ? '加入購物車' : '已售完'}
                                        </button>
                                    </div>
                                </li>`;
                        });
                        html += '</ul>';
                        // 處理分頁 (簡化版)
                        if (data.meta && data.meta.last_page > 1) {
                            html += '<div class="pagination">';
                            for (let i = 1; i <= data.meta.last_page; i++) {
                                html += `<button class="page-btn" data-page="${i}" ${i === data.meta.current_page ? 'disabled' : ''}>${i}</button> `;
                            }
                            html += '</div>';
                        }
                        productListDiv.innerHTML = html;

                        // 為分頁按鈕添加事件監聽器
                        document.querySelectorAll('.page-btn').forEach(button => {
                            button.addEventListener('click', function() {
                                const page = this.getAttribute('data-page');
                                fetchProducts({ page: page /*, 其他篩選條件 */ });
                            });
                        });
                        
                        // 為加入購物車按鈕添加事件監聽器 (因為它們是動態生成的)
                        attachAddToCartListeners();

                    } else {
                        productListDiv.innerHTML = '<p>目前沒有產品。</p>';
                    }
                }
            })
           // script.js (fetch 的 .catch 部分)
        .catch(error => {
            console.error('獲取產品列表時發生錯誤:', error);
            if (productListDiv) {
                // 顯示更詳細的錯誤訊息
                // error.message 現在會包含 "網路回應錯誤: 500 Internal Server Error. 內容: ..."
                productListDiv.innerHTML = `${errorMessage} <br><pre>錯誤詳情: ${escapeHtml(error.message)}</pre>`;
            }
        })
    }

    // 初始載入第一頁
    // fetchProducts({ page: 1, limit: 10 }); // 您可以在這裡傳入預設的 limit

    // HTML 轉義函數 (確保它已定義或從其他地方引入)
    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    // 添加加入購物車按鈕的事件監聽器 (因為按鈕是動態生成的)
    function attachAddToCartListeners() {
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (this.classList.contains('out-of-stock') || this.disabled) {
                    return; // 如果已售完或禁用，則不執行
                }
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
                
                // 假設 shoppingCart 是全域可用的 (來自 shopping-cart.js)
                if (window.shoppingCart && window.shoppingCart.addItem(product, 1)) {
                    alert(`${productName} 已成功加入購物車！`);
                } else {
                    // 可能庫存為0 (addItem 內部應該也會有檢查) 或 shoppingCart 未定義
                    alert(`無法將 ${productName} 加入購物車。`);
                }
            });
        });
    }
});