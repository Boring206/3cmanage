// FRONTEND/js/my-orders.js
document.addEventListener('DOMContentLoaded', function() {
    const ordersContainer = document.getElementById('orders-container');
    const paginationContainer = document.getElementById('orders-pagination');
    const API_BASE_PATH = '/BACKEND/public'; // <--- 請再次確認並修改這裡

    // 頁面保護：檢查是否登入
    const storedUserData = localStorage.getItem('userData');
    if (!storedUserData) {
        window.location.href = 'login.html?redirect=my-orders.html';
        return;
    }

    function fetchOrders(page = 1, limit = 10) {
        ordersContainer.innerHTML = '<p>載入訂單中...</p>';
        fetch(`${API_BASE_PATH}/orders?page=${page}&limit=${limit}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                // 如果後端需要 Session Cookie，瀏覽器會自動帶上。
                // 如果使用 JWT，則需要從 localStorage 讀取並加入 Authorization header
                // 'Authorization': `Bearer ${localStorage.getItem('userToken')}`
            }
        })
        .then(response => {
            if (response.status === 401) { // 未授權
                localStorage.removeItem('userData');
                window.location.href = 'login.html?redirect=my-orders.html&reason=session_expired';
                throw new Error('Unauthorized');
            }
            if (!response.ok) {
                throw new Error('獲取訂單失敗: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.data && data.data.length > 0) {
                let ordersHTML = '<ul class="order-list">';
                data.data.forEach(order => {
                    ordersHTML += `
                        <li class="order-item">
                            <h3>訂單編號: #${escapeHtml(String(order.id))}</h3>
                            <div class="order-meta">
                                <span>日期: ${new Date(order.order_date).toLocaleDateString()}</span>
                                <span>狀態: ${escapeHtml(translateOrderStatus(order.status))}</span>
                                <span>總金額: $${parseFloat(order.total_amount).toFixed(2)}</span>
                            </div>
                            <p><strong>收件人:</strong> ${escapeHtml(order.recipient_name)}</p>
                            <p><strong>地址:</strong> ${escapeHtml(order.postal_code)} ${escapeHtml(order.city)} ${escapeHtml(order.street)}</p>
                            <h4>訂單商品:</h4>
                            <ul class="order-products-list">`;
                    if(order.items && order.items.length > 0){
                        order.items.forEach(item => {
                            ordersHTML += `<li>${escapeHtml(item.product_name)} x ${item.quantity} (單價: $${parseFloat(item.price_at_purchase).toFixed(2)})</li>`;
                        });
                    } else {
                        ordersHTML += `<li>無商品資訊 (或需點擊查看詳情)</li>`;
                    }
                    ordersHTML += `
                            </ul>
                            <a href="order-detail.html?id=${order.id}">查看訂單詳情</a>
                        </li>`;
                });
                ordersHTML += '</ul>';
                ordersContainer.innerHTML = ordersHTML;

                renderPagination(data.meta.current_page, data.meta.last_page, data.meta.limit);
            } else {
                ordersContainer.innerHTML = '<p>您目前沒有任何訂單。</p>';
                paginationContainer.innerHTML = '';
            }
        })
        .catch(error => {
            if (error.message !== 'Unauthorized') {
                console.error('載入訂單錯誤:', error);
                ordersContainer.innerHTML = '<p>無法載入您的訂單，請稍後再試。</p>';
            }
        });
    }

    function renderPagination(currentPage, totalPages, limit) {
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        let paginationHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            paginationHTML += `<button data-page="${i}" ${i === currentPage ? 'disabled' : ''}>${i}</button> `;
        }
        paginationContainer.innerHTML = paginationHTML;

        document.querySelectorAll('#orders-pagination button').forEach(button => {
            button.addEventListener('click', function() {
                const page = parseInt(this.getAttribute('data-page'));
                fetchOrders(page, limit);
            });
        });
    }

    function translateOrderStatus(status) {
        const map = {
            'pending': '待處理',
            'processing': '處理中',
            'shipped': '已出貨',
            'delivered': '已送達',
            'cancelled': '已取消',
            'refunded': '已退款'
        };
        return map[status] || status;
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

    fetchOrders(); // 初始載入
});