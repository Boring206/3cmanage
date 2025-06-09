document.addEventListener('DOMContentLoaded', function() {
    const devicesContainer = document.getElementById('devices-container');
    const API_BASE_PATH = '/3Cmanage/BACKEND/public';

    // 頁面保護：檢查是否登入
    const storedUserData = localStorage.getItem('userData');
    if (!storedUserData) {
        window.location.href = 'login.html?redirect=my-devices.html';
        return;
    }

    function fetchMyDevices() {
        devicesContainer.innerHTML = '<p>載入設備資訊中...</p>';
        
        fetch(`${API_BASE_PATH}/my-devices`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.status === 401) {
                localStorage.removeItem('userData');
                window.location.href = 'login.html?redirect=my-devices.html&reason=session_expired';
                throw new Error('Unauthorized');
            }
            if (!response.ok) {
                throw new Error('獲取設備資訊失敗: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.data && Array.isArray(data.data) && data.data.length > 0) {
                renderDevices(data.data);
            } else {
                devicesContainer.innerHTML = '<p>您目前沒有任何設備記錄。</p>';
            }
        })
        .catch(error => {
            if (error.message !== 'Unauthorized') {
                console.error('載入設備資訊錯誤:', error);
                devicesContainer.innerHTML = '<p>無法載入設備資訊，請稍後再試。</p>';
            }
        });
    }

    function renderDevices(devices) {
        let devicesHTML = '<ul class="device-list">';
        
        devices.forEach(device => {
            const warrantyStatus = getWarrantyStatus(device.warranty_end_date, device.warranty_status);
            const statusClass = getWarrantyStatusClass(warrantyStatus);
            
            devicesHTML += `
                <li class="device-item">
                    <img src="${device.product_image_url || 'images/placeholder.jpg'}" alt="${escapeHtml(device.product_name)}">
                    <div class="device-info">
                        <h3>${escapeHtml(device.product_name)}</h3>
                        <p><strong>品牌:</strong> ${escapeHtml(device.product_brand)}</p>
                        <p><strong>型號:</strong> ${escapeHtml(device.product_model)}</p>
                        <p><strong>序號:</strong> ${escapeHtml(device.serial_number || '未提供')}</p>
                        <p><strong>購買日期:</strong> ${formatDate(device.purchase_date)}</p>
                        <p><strong>保固狀態:</strong> 
                            <span class="${statusClass}">${warrantyStatus}</span>
                        </p>
                        <p><strong>保固到期:</strong> ${formatDate(device.warranty_end_date)}</p>
                        ${device.notes ? `<p><strong>備註:</strong> ${escapeHtml(device.notes)}</p>` : ''}
                        <div class="device-actions">
                            <a href="warranty-detail.html?id=${device.id}" class="btn">查看保固詳情</a>
                        </div>
                    </div>
                </li>
            `;
        });
        
        devicesHTML += '</ul>';
        devicesContainer.innerHTML = devicesHTML;
    }

    function getWarrantyStatus(warrantyEndDate, warrantyStatus) {
        if (!warrantyEndDate) {
            return '無保固資訊';
        }

        const endDate = new Date(warrantyEndDate);
        const today = new Date();
        
        // 檢查是否已被手動設定為失效
        if (warrantyStatus === 'voided') {
            return '保固已失效';
        }
        
        if (endDate > today) {
            const daysLeft = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
            if (daysLeft <= 30) {
                return `保固即將到期 (${daysLeft}天)`;
            }
            return '保固有效';
        } else {
            return '保固已過期';
        }
    }

    function getWarrantyStatusClass(status) {
        if (status === '保固有效') {
            return 'warranty-status-active';
        } else if (status.includes('即將到期')) {
            return 'warranty-status-expired';
        } else if (status === '保固已失效') {
            return 'warranty-status-voided';
        } else {
            return 'warranty-status-expired';
        }
    }

    function formatDate(dateString) {
        if (!dateString) return '未提供';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('zh-TW', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        } catch (e) {
            return dateString;
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

    // 初始載入設備列表
    fetchMyDevices();
});
