// FRONTEND/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('前端 JavaScript 已載入！');

    const productListDiv = document.getElementById('product-list');
    const loadingMessage = '<p>載入中...</p>';
    const errorMessage = '<p>無法載入產品列表。請檢查 API 路徑與後端狀態。</p>';

    // !!! 非常重要：根據您的伺服器設定修改此 API 基本路徑 !!!
    // 假設您的後端 API 可以透過 /api/ 前綴訪問，且該前綴指向 BACKEND/public/
    // 例如： http://localhost/api/products
    // 如果您直接將 BACKEND/public/ 設為網站根目錄 (例如 http://localhost/)，則 basePath 可以是 ''
    // 如果您的前端和後端在同一個網域和根目錄下，且後端在 BACKEND 資料夾，
    // 則可能是 '/BACKEND/public' (如果前端 HTML 在根目錄) 或 '../BACKEND/public' (如果前端 HTML 在 FRONTEND 子目錄)
    const API_BASE_PATH = '/BACKEND/public'; // <--- 請修改這裡

    if (productListDiv) {
        productListDiv.innerHTML = loadingMessage;
        fetchProducts();
    }

    function fetchProducts(queryParams = {}) {
        // 构建帶有查詢參數的 URL
        const url = new URL(`${API_BASE_PATH}/products`, window.location.origin);
        Object.keys(queryParams).forEach(key => {
            if (queryParams[key] !== null && queryParams[key] !== undefined) {
                url.searchParams.append(key, queryParams[key]);
            }
        });

        console.log(`正在從 ${url} 獲取產品...`);

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    console.error('網路回應錯誤:', response.status, response.statusText);
                    return response.text().then(text => { // 嘗試讀取錯誤訊息文本
                        throw new Error(`網路回應錯誤: ${response.status} ${response.statusText}. 內容: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (productListDiv) {
                    if (data && data.data && data.data.length > 0) {
                        let html = '<ul>';
                        data.data.forEach(product => {
                            // 假設您有一個 product-detail.html 頁面用於顯示詳情
                            html += `
                                <li>
                                    <img src="${product.image_url || 'placeholder.jpg'}" alt="${product.name}" style="width:100px; height:auto;">
                                    <h3><a href="product-detail.html?id=${product.id}">${product.name}</a></h3>
                                    <p>品牌: ${product.brand} - 型號: ${product.model_number}</p>
                                    <p>價格: $${product.price}</p>
                                    <p>庫存: ${product.stock_quantity > 0 ? product.stock_quantity : '無庫存'}</p>
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

                    } else {
                        productListDiv.innerHTML = '<p>目前沒有產品。</p>';
                    }
                }
            })
            .catch(error => {
                console.error('獲取產品列表時發生錯誤:', error);
                if (productListDiv) {
                    productListDiv.innerHTML = errorMessage + `<pre>${error.message}</pre>`;
                }
            });
    }

    // 初始載入第一頁
    // fetchProducts({ page: 1, limit: 10 }); // 您可以在這裡傳入預設的 limit
});