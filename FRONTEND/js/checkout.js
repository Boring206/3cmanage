/**
 * 結帳頁面處理腳本
 */
document.addEventListener('DOMContentLoaded', function() {
    // 檢查是否已登入
    const userData = localStorage.getItem('userData');
    if (!userData) {
        alert('請先登入再進行結帳');
        window.location.href = 'login.html';
        return;
    }

    // 檢查購物車是否為空
    if (typeof shoppingCart === 'undefined' || shoppingCart.items.length === 0) {
        alert('購物車是空的，請先添加商品');
        window.location.href = 'index.html';
        return;
    }

    // !!! 非常重要：根據您的伺服器設定修改此 API 基本路徑 !!!
    const API_BASE_PATH = '/3Cmanage/BACKEND/public'; // 確保大小寫與資料夾名稱一致

    // 獲取用戶資訊
    let user = null;
    try {
        user = JSON.parse(userData);
    } catch (e) {
        console.error('解析用戶資料錯誤:', e);
        localStorage.removeItem('userData');
        window.location.href = 'login.html';
        return;
    }

    // 獲取頁面元素
    const selectAddress = document.getElementById('select-address');
    const newAddressBtn = document.getElementById('new-address-btn');
    const addressForm = document.getElementById('address-form');
    const shippingForm = document.getElementById('shipping-form');
    const paymentForm = document.getElementById('payment-form');
    const creditCardForm = document.getElementById('credit-card-form');
    const bankTransferInfo = document.getElementById('bank-transfer-info');
    const orderItems = document.getElementById('order-items');
    const orderSummary = document.getElementById('order-summary');
    const placeOrderBtn = document.getElementById('place-order-btn');
    const backToCartBtn = document.getElementById('back-to-cart');

    // 載入用戶地址
    loadUserAddresses();

    // 渲染訂單商品和摘要
    renderOrderItems();
    renderOrderSummary();

    // 綁定事件處理器
    if (newAddressBtn) {
        newAddressBtn.addEventListener('click', function() {
            toggleAddressForm();
        });
    }

    // 地址選擇器變更事件
    if (selectAddress) {
        selectAddress.addEventListener('change', function() {
            if (this.value === 'new') {
                showAddressForm();
            } else {
                hideAddressForm();
            }
        });
    }

    // 付款方式選擇事件
    const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            updatePaymentForm(this.value);
            
            // 視覺反饋選擇的付款方式
            document.querySelectorAll('.payment-option').forEach(opt => {
                if (opt.contains(this)) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
        });
    });

    // 返回購物車按鈕
    if (backToCartBtn) {
        backToCartBtn.addEventListener('click', function() {
            window.location.href = 'cart.html';
        });
    }

    // 提交訂單按鈕
    if (placeOrderBtn) {
        placeOrderBtn.addEventListener('click', function() {
            submitOrder();
        });
    }

    /**
     * 載入用戶保存的地址
     */
    function loadUserAddresses() {
        if (!selectAddress) return;

        fetch(`${API_BASE_PATH}/addresses`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('獲取地址失敗');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.data && data.data.length > 0) {
                // 清除現有選項
                while (selectAddress.options.length > 1) {
                    selectAddress.remove(1);
                }
                
                // 添加地址選項
                data.data.forEach(address => {
                    const option = document.createElement('option');
                    option.value = address.id;
                    option.textContent = `${address.recipient_name} - ${address.city} ${address.street}`;
                    option.dataset.address = JSON.stringify(address);
                    selectAddress.appendChild(option);
                });
                
                // 添加"新增地址"選項
                const newOption = document.createElement('option');
                newOption.value = 'new';
                newOption.textContent = '+ 新增地址';
                selectAddress.appendChild(newOption);
            } else {
                // 沒有保存的地址，直接顯示地址表單
                showAddressForm();
            }
        })
        .catch(error => {
            console.error('獲取地址時出錯:', error);
            // 出錯時也顯示地址表單
            showAddressForm();
        });
    }

    /**
     * 顯示地址表單
     */
    function showAddressForm() {
        if (addressForm) {
            addressForm.style.display = 'block';
        }
    }

    /**
     * 隱藏地址表單
     */
    function hideAddressForm() {
        if (addressForm) {
            addressForm.style.display = 'none';
        }
    }

    /**
     * 切換地址表單顯示狀態
     */
    function toggleAddressForm() {
        if (addressForm.style.display === 'none') {
            showAddressForm();
            // 選擇"新增地址"選項
            if (selectAddress) {
                selectAddress.value = 'new';
            }
        } else {
            hideAddressForm();
            // 選擇第一個已保存的地址
            if (selectAddress && selectAddress.options.length > 1) {
                selectAddress.selectedIndex = 1;
            }
        }
    }

    /**
     * 根據選擇的付款方式更新表單
     */
    function updatePaymentForm(paymentMethod) {
        switch(paymentMethod) {
            case 'credit':
                creditCardForm.style.display = 'block';
                bankTransferInfo.style.display = 'none';
                break;
            case 'transfer':
                creditCardForm.style.display = 'none';
                bankTransferInfo.style.display = 'block';
                break;
            default: // 貨到付款或其他
                creditCardForm.style.display = 'none';
                bankTransferInfo.style.display = 'none';
                break;
        }
    }

    /**
     * 渲染訂單商品列表
     */
    function renderOrderItems() {
        if (!orderItems) return;

        let html = '';
        for (const item of shoppingCart.items) {
            const itemTotal = item.price * item.quantity;
            html += `
                <div class="order-item">
                    <div class="order-item-image">
                        <img src="${item.image}" alt="${escapeHtml(item.name)}">
                    </div>
                    <div class="order-item-details">
                        <h4>${escapeHtml(item.name)}</h4>
                        <div class="quantity">數量: ${item.quantity}</div>
                    </div>
                    <div class="order-item-price">
                        $${itemTotal.toFixed(2)}
                    </div>
                </div>
            `;
        }
        orderItems.innerHTML = html;
    }

    /**
     * 渲染訂單摘要
     */
    function renderOrderSummary() {
        if (!orderSummary) return;

        const subtotal = shoppingCart.getTotalPrice();
        const shipping = subtotal > 0 ? 100 : 0; // 簡單的固定運費
        const total = subtotal + shipping;

        orderSummary.innerHTML = `
            <div class="summary-row">
                <span>商品小計:</span>
                <span>$${subtotal.toFixed(2)}</span>
            </div>
            <div class="summary-row">
                <span>運費:</span>
                <span>$${shipping.toFixed(2)}</span>
            </div>
            <div class="summary-row total">
                <span>總計:</span>
                <span>$${total.toFixed(2)}</span>
            </div>
        `;
    }

    /**
     * 提交訂單
     */
    function submitOrder() {
        // 檢查地址是否已填寫
        let addressData = null;
        
        if (selectAddress.value && selectAddress.value !== 'new') {
            // 使用已保存的地址
            try {
                const selectedOption = selectAddress.options[selectAddress.selectedIndex];
                addressData = JSON.parse(selectedOption.dataset.address);
            } catch (e) {
                alert('地址資料錯誤，請重新選擇或新增地址');
                console.error('解析地址資料錯誤:', e);
                return;
            }
        } else if (addressForm.style.display !== 'none') {
            // 使用新填寫的地址
            const recipientName = document.getElementById('recipient_name').value;
            const phone = document.getElementById('phone').value;
            const postalCode = document.getElementById('postal_code').value;
            const city = document.getElementById('city').value;
            const addressLine1 = document.getElementById('address_line1').value;
            const addressLine2 = document.getElementById('address_line2').value;
            
            if (!recipientName || !phone || !postalCode || !city || !addressLine1) {
                alert('請填寫完整的地址資訊');
                return;
            }
            
            addressData = {
                recipient_name: recipientName,
                phone_number: phone,
                postal_code: postalCode,
                city: city,
                street: addressLine1 + (addressLine2 ? ' ' + addressLine2 : ''),
                country: 'Taiwan'
            };
            
            // 如果用戶選擇保存地址
            const saveAddress = document.getElementById('save_address').checked;
            if (saveAddress) {
                // 保存地址到後端
                saveUserAddress(addressData);
            }
        } else {
            alert('請選擇送貨地址');
            return;
        }
        
        // 獲取付款方式
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        // 如果選擇信用卡，檢查信用卡資料
        if (paymentMethod === 'credit') {
            const cardNumber = document.getElementById('card_number').value;
            const cardName = document.getElementById('card_name').value;
            const expiryDate = document.getElementById('expiry_date').value;
            const cvv = document.getElementById('cvv').value;
            
            if (!cardNumber || !cardName || !expiryDate || !cvv) {
                alert('請填寫完整的信用卡資訊');
                return;
            }
        }
        
        // 獲取訂單備註
        const orderNotes = document.getElementById('order_notes').value;
        
        // 準備訂單數據
        const orderData = {
            items: shoppingCart.items.map(item => ({
                product_id: item.id,
                quantity: item.quantity,
                price: item.price
            })),
            address_id: addressData.id || null,
            payment_method: paymentMethod,
            notes: orderNotes,
            subtotal_amount: shoppingCart.getTotalPrice(),
            shipping_fee: 100,
            total_amount: shoppingCart.getTotalPrice() + 100
        };
        
        // 如果是新地址，需要先創建地址
        if (!addressData.id) {
            // 創建新地址然後提交訂單
            saveUserAddress(addressData).then(savedAddress => {
                if (savedAddress && savedAddress.id) {
                    orderData.address_id = savedAddress.id;
                    submitOrderWithData(orderData);
                } else {
                    alert('保存地址失敗，請重試');
                }
            }).catch(error => {
                console.error('保存地址錯誤:', error);
                alert('保存地址時出錯，請重試');
            });
        } else {
            submitOrderWithData(orderData);
        }
    }

    function submitOrderWithData(orderData) {
        // 顯示提交按鈕載入狀態
        placeOrderBtn.disabled = true;
        placeOrderBtn.textContent = '處理訂單中...';
        
        // 提交訂單到後端
        fetch(`${API_BASE_PATH}/orders`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(orderData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('提交訂單失敗');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.order && data.order.id) {
                // 訂單建立成功
                alert('訂單建立成功！');
                
                // 清空購物車
                shoppingCart.clearCart();
                
                // 跳轉到訂單確認頁面
                window.location.href = `order-confirmation.html?id=${data.order.id}`;
            } else {
                throw new Error('服務器回應格式錯誤');
            }
        })
        .catch(error => {
            console.error('處理訂單時出錯:', error);
            alert('處理訂單時出錯，請稍後再試');
            // 還原按鈕狀態
            placeOrderBtn.disabled = false;
            placeOrderBtn.textContent = '確認下單';
        });
    }

    /**
     * 保存用戶地址到後端
     */
    function saveUserAddress(addressData) {
        return fetch(`${API_BASE_PATH}/addresses`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(addressData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('保存地址失敗');
            }
            return response.json();
        })
        .then(data => {
            console.log('地址保存成功:', data);
            return data.address;
        });
    }

    /**
     * 簡單的 HTML 轉義函數，防止 XSS
     * @param {string} unsafe - 未轉義的字符串
     * @returns {string} - 轉義後的字符串
     */
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
