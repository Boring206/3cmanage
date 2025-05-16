// FRONTEND/js/my-addresses.js
document.addEventListener('DOMContentLoaded', function() {
    const addressesContainer = document.getElementById('addresses-container');
    const addAddressFormContainer = document.getElementById('add-address-form-container');
    const showAddAddressFormBtn = document.getElementById('show-add-address-form-btn');
    const addressForm = document.getElementById('address-form');
    const addressFormTitle = document.getElementById('address-form-title');
    const submitAddressBtn = document.getElementById('submit-address-btn');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');
    const addressFormMessage = document.getElementById('address-form-message');
    let editingAddressId = null;

    const API_BASE_PATH = '/BACKEND/public'; // <--- 請再次確認並修改這裡

    // 頁面保護
    const storedUserData = localStorage.getItem('userData');
    if (!storedUserData) {
        window.location.href = 'login.html?redirect=my-addresses.html';
        return;
    }

    function fetchAddresses() {
        addressesContainer.innerHTML = '<p>載入地址中...</p>';
        fetch(`${API_BASE_PATH}/my/addresses`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => handleAuthError(response))
        .then(data => {
            if (data.data && Array.isArray(data.data)) {
                renderAddresses(data.data);
            } else {
                addressesContainer.innerHTML = '<p>您尚未新增任何地址。</p>';
            }
        })
        .catch(error => {
            if (error.message !== 'Unauthorized') {
                console.error('載入地址錯誤:', error);
                addressesContainer.innerHTML = '<p>無法載入地址，請稍後再試。</p>';
            }
        });
    }

    function renderAddresses(addresses) {
        if (addresses.length === 0) {
            addressesContainer.innerHTML = '<p>您尚未新增任何地址。</p>';
            return;
        }
        let addressesHTML = '<ul class="address-list">';
        addresses.forEach(addr => {
            addressesHTML += `
                <li class="address-item" data-id="${addr.id}">
                    ${addr.is_default == 1 ? '<span class="default-badge">預設</span>' : ''}
                    <h3>${escapeHtml(addr.recipient_name)}</h3>
                    <p>${escapeHtml(addr.phone_number)}</p>
                    <p>${escapeHtml(addr.postal_code)} ${escapeHtml(addr.city)}</p>
                    <p>${escapeHtml(addr.street)}</p>
                    <p>${escapeHtml(addr.country)}</p>
                    <div class="address-actions">
                        <button class="edit-address-btn" data-id="${addr.id}">編輯</button>
                        <button class="delete-address-btn" data-id="${addr.id}">刪除</button>
                        ${addr.is_default == 0 ? `<button class="set-default-btn" data-id="${addr.id}">設為預設</button>` : ''}
                    </div>
                </li>`;
        });
        addressesHTML += '</ul>';
        addressesContainer.innerHTML = addressesHTML;
        attachAddressActionListeners();
    }

    function attachAddressActionListeners() {
        document.querySelectorAll('.edit-address-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                editingAddressId = this.dataset.id;
                const addressItem = this.closest('.address-item');
                // 從地址項目中獲取資料填充表單 - 這裡簡化處理，實際應用中應重新fetch或從已有的addresses陣列獲取
                const addresses = JSON.parse(localStorage.getItem('myAddressesCache') || '[]'); // 假設您會快取
                const addrToEdit = addresses.find(a => a.id == editingAddressId);

                if (addrToEdit) {
                    addressForm.recipient_name.value = addrToEdit.recipient_name;
                    addressForm.phone_number.value = addrToEdit.phone_number;
                    addressForm.postal_code.value = addrToEdit.postal_code;
                    addressForm.city.value = addrToEdit.city;
                    addressForm.street.value = addrToEdit.street;
                    addressForm.country.value = addrToEdit.country;
                    addressForm.is_default.checked = addrToEdit.is_default == 1;
                    addressForm.address_id.value = editingAddressId;

                    addressFormTitle.textContent = '編輯地址';
                    submitAddressBtn.textContent = '更新地址';
                    addAddressFormContainer.style.display = 'block';
                    cancelEditBtn.style.display = 'inline-block';
                    showAddAddressFormBtn.style.display = 'none';
                    window.scrollTo(0, addAddressFormContainer.offsetTop - 20);
                } else {
                    // 如果快取中沒有，可以考慮重新fetch一次該地址的詳細資料
                    fetchAndPopulateEditForm(editingAddressId);
                }
            });
        });

        document.querySelectorAll('.delete-address-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const addressId = this.dataset.id;
                if (confirm('您確定要刪除此地址嗎？')) {
                    deleteAddress(addressId);
                }
            });
        });

        document.querySelectorAll('.set-default-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const addressId = this.dataset.id;
                setDefaultAddress(addressId);
            });
        });
    }
    
    async function fetchAndPopulateEditForm(addressId) {
        try {
            // 實際上後端應該提供一個 GET /my/addresses/{id} 的接口
            // 這裡我們先假設一個場景：從列表數據中找到對應的
            // 為簡化，這裡重新 fetch 列表並找到對應的項目
            const response = await fetch(`${API_BASE_PATH}/my/addresses`);
            const data = await handleAuthError(response);
            if (data.data) {
                localStorage.setItem('myAddressesCache', JSON.stringify(data.data)); // 更新快取
                const addrToEdit = data.data.find(a => a.id == addressId);
                if (addrToEdit) {
                    addressForm.recipient_name.value = addrToEdit.recipient_name;
                    addressForm.phone_number.value = addrToEdit.phone_number;
                    addressForm.postal_code.value = addrToEdit.postal_code;
                    addressForm.city.value = addrToEdit.city;
                    addressForm.street.value = addrToEdit.street;
                    addressForm.country.value = addrToEdit.country;
                    addressForm.is_default.checked = addrToEdit.is_default == 1;
                    addressForm.address_id.value = editingAddressId;

                    addressFormTitle.textContent = '編輯地址';
                    submitAddressBtn.textContent = '更新地址';
                    addAddressFormContainer.style.display = 'block';
                    cancelEditBtn.style.display = 'inline-block';
                    showAddAddressFormBtn.style.display = 'none';
                    window.scrollTo(0, addAddressFormContainer.offsetTop - 20);
                } else {
                     showMessage(addressFormMessage, '無法找到要編輯的地址資料。', 'error');
                }
            }
        } catch (error) {
            showMessage(addressFormMessage, '獲取地址資料失敗。', 'error');
        }
    }


    function handleAddressFormSubmit(event) {
        event.preventDefault();
        clearMessage(addressFormMessage);

        const formData = new FormData(addressForm);
        const addressData = Object.fromEntries(formData.entries());
        // is_default 如果沒勾選，FormData 不會包含它，所以要手動處理
        addressData.is_default = addressForm.is_default.checked ? 1 : 0;


        const url = editingAddressId ? `${API_BASE_PATH}/my/addresses/${editingAddressId}` : `${API_BASE_PATH}/my/addresses`;
        const method = editingAddressId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(addressData)
        })
        .then(response => handleAuthError(response))
        .then(data => {
            if (data.message) {
                showMessage(addressFormMessage, data.message, 'success');
                fetchAddresses(); // 重新載入列表
                resetAddressForm();
            } else if (data.error && data.error.message) {
                showMessage(addressFormMessage, data.error.message, 'error');
            } else {
                showMessage(addressFormMessage, editingAddressId ? '更新地址失敗。' : '新增地址失敗。', 'error');
            }
        })
        .catch(error => {
            if (error.message !== 'Unauthorized') {
                console.error('地址表單提交錯誤:', error);
                showMessage(addressFormMessage, '操作失敗，請檢查網路連線。', 'error');
            }
        });
    }

    function deleteAddress(addressId) {
        fetch(`${API_BASE_PATH}/my/addresses/${addressId}`, { method: 'DELETE' })
        .then(response => handleAuthError(response))
        .then(data => {
            if (data.message) {
                alert(data.message); // 用 alert 提示
                fetchAddresses();
            } else if (data.error && data.error.message) {
                alert('刪除失敗: ' + data.error.message);
            } else {
                alert('刪除地址失敗。');
            }
        })
        .catch(error => {
            if (error.message !== 'Unauthorized') {
                console.error('刪除地址錯誤:', error);
                alert('刪除過程中發生網路錯誤。');
            }
        });
    }

    function setDefaultAddress(addressId) {
        fetch(`${API_BASE_PATH}/my/addresses/${addressId}/set-default`, { method: 'POST' })
        .then(response => handleAuthError(response))
        .then(data => {
            if (data.message) {
                alert(data.message);
                fetchAddresses();
            } else if (data.error && data.error.message) {
                alert('設定預設地址失敗: ' + data.error.message);
            } else {
                alert('設定預設地址失敗。');
            }
        })
        .catch(error => {
            if (error.message !== 'Unauthorized') {
                console.error('設定預設地址錯誤:', error);
                alert('設定過程中發生網路錯誤。');
            }
        });
    }
    
    function handleAuthError(response) {
        if (response.status === 401) {
            localStorage.removeItem('userData');
            window.location.href = 'login.html?redirect=my-addresses.html&reason=session_expired';
            throw new Error('Unauthorized'); // 拋出錯誤以停止後續 .then()
        }
        return response.json(); // 否則正常處理 JSON
    }

    function resetAddressForm() {
        addressForm.reset();
        editingAddressId = null;
        addressFormTitle.textContent = '新增地址';
        submitAddressBtn.textContent = '儲存地址';
        addAddressFormContainer.style.display = 'none';
        cancelEditBtn.style.display = 'none';
        showAddAddressFormBtn.style.display = 'inline-block';
        clearMessage(addressFormMessage);
    }

    function showMessage(element, message, type) {
        if (element) {
            element.textContent = message;
            element.className = `form-message ${type}`;
        }
    }
    function clearMessage(element) {
        if (element) {
            element.textContent = '';
            element.className = 'form-message';
        }
    }

    showAddAddressFormBtn.addEventListener('click', () => {
        resetAddressForm(); // 確保是乾淨的表單
        addAddressFormContainer.style.display = 'block';
        showAddAddressFormBtn.style.display = 'none';
        addressForm.recipient_name.focus();
    });

    cancelEditBtn.addEventListener('click', () => {
        resetAddressForm();
    });

    addressForm.addEventListener('submit', handleAddressFormSubmit);
    
    fetchAddresses(); // 初始載入地址列表
    
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