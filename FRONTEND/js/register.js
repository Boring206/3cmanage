// FRONTEND/js/register.js
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('register-form');
    const messageElement = document.getElementById('form-message');

    // !!! 非常重要：根據您的伺服器設定修改此 API 基本路徑 !!!
    const API_BASE_PATH = '/BACKEND/public'; // <--- 請修改這裡

    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            event.preventDefault(); // 防止表單的傳統提交方式
            clearMessage();

            const name = document.getElementById('name').value;
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                showMessage('密碼與確認密碼不相符。', 'error');
                return;
            }

            const userData = {
                name: name,
                username: username,
                email: email,
                password: password
            };

            fetch(`${API_BASE_PATH}/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userData)
            })
            .then(response => {
                return response.json().then(data => ({ status: response.status, body: data }));
            })
            .then(({ status, body }) => {
                if (status === 201 && body.message) {
                    showMessage(body.message + ' 您現在可以登入了。', 'success');
                    registerForm.reset(); // 清空表單
                    // 可以選擇幾秒後跳轉到登入頁面
                    // setTimeout(() => { window.location.href = 'login.html'; }, 3000);
                } else if (body.error && body.error.message) {
                    showMessage(body.error.message, 'error');
                } else {
                    showMessage('註冊失敗，請稍後再試。', 'error');
                }
            })
            .catch(error => {
                console.error('註冊請求錯誤:', error);
                showMessage('註冊過程中發生網路錯誤。', 'error');
            });
        });
    }

    function showMessage(message, type) {
        if (messageElement) {
            messageElement.textContent = message;
            messageElement.className = `form-message ${type}`; // 'success' or 'error'
        }
    }

    function clearMessage() {
        if (messageElement) {
            messageElement.textContent = '';
            messageElement.className = 'form-message';
        }
    }
});