/**
 * 購物車頁面處理腳本
 */
document.addEventListener('DOMContentLoaded', function() {
    // 檢查購物車實例是否已加載
    if (typeof shoppingCart === 'undefined') {
        console.error('購物車模組未載入');
        return;
    }

    // 渲染購物車內容
    shoppingCart.renderCart();

    // 繼續購物按鈕事件處理
    const continueShoppingBtn = document.getElementById('continue-shopping');
    if (continueShoppingBtn) {
        continueShoppingBtn.addEventListener('click', function() {
            window.location.href = 'index.html';
        });
    }

    // 結帳按鈕事件處理
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
            // 檢查是否已登入
            const userData = localStorage.getItem('userData');
            if (!userData) {
                alert('請先登入再進行結帳');
                // 保存當前 URL，以便登入後返回
                sessionStorage.setItem('redirectAfterLogin', 'checkout.html');
                window.location.href = 'login.html';
                return;
            }
            
            // 已登入，跳轉到結帳頁面
            window.location.href = 'checkout.html';
        });
    }
});
