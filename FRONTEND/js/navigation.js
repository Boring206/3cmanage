// FRONTEND/js/navigation.js
document.addEventListener('DOMContentLoaded', function() {
    const navigationElement = document.getElementById('main-navigation');
    // !!! 非常重要：根據您的伺服器設定修改此 API 基本路徑 !!!
    const API_BASE_PATH = '/BACKEND/public'; // <--- 請修改這裡

    function renderNavigation() {
        if (!navigationElement) {
            console.error('Navigation element with ID "main-navigation" not found.');
            return;
        }

        let userData = null;
        const storedUserData = localStorage.getItem('userData');
        if (storedUserData) {
            try {
                userData = JSON.parse(storedUserData);
            } catch (e) {
                console.error('Error parsing user data from localStorage:', e);
                localStorage.removeItem('userData'); // 清除損壞的資料
            }
        }

        let navHTML = `
            <a href="index.html">首頁</a>
            `;

        if (userData && userData.id) { // 假設 userData 包含 id 代表已登入
            navHTML += `
                <a href="my-orders.html">我的訂單</a>
                <a href="my-addresses.html">我的地址</a>
                <a href="my-devices.html">我的設備</a>
            `;
            if (userData.role === 'store_admin') {
                navHTML += `<a href="admin-dashboard.html">管理後台</a> `; // 管理員專用連結
            }
            navHTML += `<span style="color: #fff; margin-left: 15px;">歡迎，${escapeHtml(userData.name || userData.username)}</span>`;
            navHTML += `<a href="#" id="logout-link" style="margin-left: 15px;">登出</a>`;
        } else {
            navHTML += `
                <a href="login.html">登入</a>
                <a href="register.html">註冊</a>
            `;
        }
        navigationElement.innerHTML = navHTML;

        // 為登出連結添加事件監聽器
        const logoutLink = document.getElementById('logout-link');
        if (logoutLink) {
            logoutLink.addEventListener('click', function(event) {
                event.preventDefault();
                handleLogout();
            });
        }
    }

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
            }
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

    renderNavigation(); // 頁面載入時立即渲染導覽列
});