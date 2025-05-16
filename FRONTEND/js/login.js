// FRONTEND/js/login.js
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const messageElement = document.getElementById('form-message');

    // !!! 非常重要：根據您的伺服器設定修改此 API 基本路徑 !!!
    const API_BASE_PATH = '/BACKEND/public'; // <--- 請修改這裡

    // 檢查是否已經登入，如果是，可以考慮導向到其他頁面
    if (localStorage.getItem('userToken')) { // 假設您用 token，或檢查 session 狀態
        // window.location.href = 'index.html'; // 或會員中心
    }


    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();
            clearMessage();

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            const loginData = {
                email: email,
                password: password
            };

            fetch(`${API_BASE_PATH}/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(loginData)
            })
            .then(response => {
                return response.json().then(data => ({ status: response.status, body: data }));
            })
            .then(({ status, body }) => {
                if (status === 200 && body.message) {
                    showMessage(body.message, 'success');
                    // 登入成功後的操作：
                    // 1. 如果後端使用 JWT，將 token 儲存到 localStorage
                    // if (body.token) {
                    //     localStorage.setItem('userToken', body.token);
                    // }
                    // 2. 儲存使用者資訊 (可選)
                    if (body.user) {
                        localStorage.setItem('userData', JSON.stringify(body.user));
                    }
                    // 3. 重新導向到首頁或會員中心
                    setTimeout(() => {
                        window.location.href = 'index.html'; // 或其他登入後應前往的頁面
                    }, 1500);
                } else if (body.error && body.error.message) {
                    showMessage(body.error.message, 'error');
                } else {
                    showMessage('登入失敗，請檢查您的帳號或密碼。', 'error');
                }
            })
            .catch(error => {
                console.error('登入請求錯誤:', error);
                showMessage('登入過程中發生網路錯誤。', 'error');
            });
        });
    }

    function showMessage(message, type) {
        if (messageElement) {
            messageElement.textContent = message;
            messageElement.className = `form-message ${type}`;
        }
    }

    function clearMessage() {
        if (messageElement) {
            messageElement.textContent = '';
            messageElement.className = 'form-message';
        }
    }
});