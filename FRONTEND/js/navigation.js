// FRONTEND/js/navigation.js
document.addEventListener('DOMContentLoaded', function() {
    const API_BASE_PATH = '/3Cmanage/BACKEND/public'; // 確保大小寫與資料夾名稱一致

    function handleLogout() {
        // 清除前端的登入狀態
        localStorage.removeItem('userData');
        // localStorage.removeItem('userToken'); // 如果您使用 token

        // 呼叫後端登出 API (如果後端 Session 需要明確銷毀)
        fetch(`${API_BASE_PATH}/logout`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
                // 如果您的登出 API 需要認證 (例如 JWT)，在這裡加入 Authorization header
            },
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            console.log(data.message || '已成功登出');
            // 重新渲染導覽列並跳轉到首頁
            renderNavigation();
            window.location.href = 'index.html';
        })
        .catch(error => {
            console.error('登出時發生錯誤:', error);
            // 即使後端登出失敗，也先清除前端狀態並跳轉
            renderNavigation();
            window.location.href = 'index.html';
        });
    }

    function renderNavigation() {
        const mainNavigation = document.getElementById('main-navigation');
        if (!mainNavigation) return;

        const userData = localStorage.getItem('userData');
        let user = null;
        
        try {
            user = userData ? JSON.parse(userData) : null;
        } catch (e) {
            console.error('解析用戶資料錯誤:', e);
            localStorage.removeItem('userData');
            user = null;
        }

        let navHTML = '';
        
        if (user) {
            // 已登入用戶的導覽
            navHTML = `
                <a href="index.html">首頁</a>
                <a href="cart.html">購物車</a>
                <a href="my-orders.html">我的訂單</a>
                <a href="my-addresses.html">我的地址</a>
                <span>歡迎, ${escapeHtml(user.name || user.username)}</span>
                <button id="logout-btn">登出</button>
            `;
            
            // 如果是管理員，顯示管理功能
            if (user.role === 'store_admin') {
                navHTML = navHTML.replace('<span>', '<a href="admin.html">管理後台</a><span>');
            }
        } else {
            // 未登入用戶的導覽
            navHTML = `
                <a href="index.html">首頁</a>
                <a href="cart.html">購物車</a>
                <a href="login.html">登入</a>
                <a href="register.html">註冊</a>
            `;
        }
        
        mainNavigation.innerHTML = navHTML;
        
        // 綁定登出按鈕事件
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', handleLogout);
        }
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

    // 初始化導覽列
    renderNavigation();
});