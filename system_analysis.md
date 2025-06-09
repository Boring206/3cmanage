# 3C Management System - 系統分析報告

## 1. 系統概述

3C Management System 是一個專門用於管理電腦、通訊設備和消費性電子產品（Computer, Communication, Consumer Electronics）的完整電商系統。該系統結合了前後端技術，實現了從商品瀏覽、購物車操作、訂單處理到售後服務（保固管理）的完整電商流程。

## 2. 系統架構

### 2.1 整體架構

此系統採用了典型的前後端分離架構：

- **前端 (FRONTEND)**：基於原生 HTML, CSS 和 JavaScript (ES6+) 實現的客戶端界面
- **後端 (BACKEND)**：基於 PHP 實現的 RESTful API 服務，採用自定義 MVC 框架

### 2.2 後端架構

後端採用了自定義的 MVC (Model-View-Controller) 架構：

- **Model**：處理數據和業務邏輯
- **Controller**：處理 HTTP 請求，並協調 Model 與 View
- **Router**：基於 URL 路徑將請求分派到對應的控制器和方法
- **Database**：使用 MySQL 數據庫存儲數據

### 2.3 資料夾結構

```
- BACKEND/
  - app/
    - Controllers/       # 控制器類別
    - Models/            # 模型類別
  - core/                # 核心框架類別
  - database/            # 數據庫初始化腳本
  - public/              # 入口點和公開文件
  - routes/              # 路由定義
  - vendor/              # Composer 依賴

- FRONTEND/
  - css/                 # 樣式文件
  - js/                  # JavaScript 腳本
  - *.html               # HTML 頁面
```

## 3. 數據庫設計

系統使用關聯式數據庫（MySQL）來存儲所有數據。主要表結構如下：

### 3.1 主要表格

1. **users**：存儲用戶信息
   - 主要字段：id, username, email, password, name, role

2. **products**：存儲產品信息
   - 主要字段：id, name, description, category, brand, model_number, specifications, price, stock_quantity, image_url, default_warranty_months

3. **addresses**：存儲用戶地址
   - 主要字段：id, user_id, recipient_name, phone_number, postal_code, city, street, country, is_default

4. **orders**：存儲訂單信息
   - 主要字段：id, user_id, address_id, order_date, status, total_amount, payment_method

5. **order_items**：存儲訂單項目（一個訂單可包含多個商品）
   - 主要字段：id, order_id, product_id, quantity, price_at_purchase

6. **warranties**：存儲產品保固信息
   - 主要字段：id, order_item_id, user_id, product_id, serial_number, purchase_date, warranty_period_months, expiry_date, status

7. **inventory_logs**：存儲庫存變動日誌
   - 主要字段：id, product_id, change_type, quantity_change, quantity_before, quantity_after, reason, performed_by, created_at

### 3.2 表關係

- 用戶(users) 1:N 地址(addresses)
- 用戶(users) 1:N 訂單(orders)
- 訂單(orders) 1:N 訂單項目(order_items)
- 訂單項目(order_items) 1:1 保固(warranties)
- 產品(products) 1:N 訂單項目(order_items)
- 產品(products) 1:N 庫存日誌(inventory_logs)

## 4. 業務邏輯

### 4.1 用戶模組

- **用戶註冊**：新用戶可以創建帳號，需提供用戶名、電子郵件和密碼
- **用戶登入**：已註冊用戶可以登入系統，登入後獲取 Session 憑證
- **用戶類型**：分為普通用戶（customer）和店家管理員（store_admin）

### 4.2 產品模組

- **產品瀏覽**：用戶可以瀏覽、搜索和過濾所有產品
- **產品詳情**：用戶可查看特定產品的詳細資訊，包括規格、價格和庫存
- **產品管理**：管理員可以添加、編輯、刪除產品及調整庫存

### 4.3 購物流程

1. **加入購物車**：
   - 用戶可以將產品添加到購物車
   - 購物車數據存儲在瀏覽器的 localStorage 中
   - 可調整商品數量或從購物車中移除商品

2. **結帳流程**：
   - 選擇或添加收貨地址
   - 選擇付款方式（信用卡、銀行轉帳等）
   - 確認訂單並提交

3. **訂單處理**：
   - 系統創建訂單記錄
   - 減少相應產品的庫存
   - 生成訂單確認

### 4.4 地址管理

- 用戶可以添加、編輯、刪除多個收貨地址
- 可將特定地址設為默認地址
- 結帳時可選擇已有地址或添加新地址

### 4.5 保固管理

- 產品售出後自動生成保固記錄
- 用戶可查看所擁有設備的保固狀態
- 保固有三種狀態：有效（active）、過期（expired）、作廢（voided）
- 保固期基於產品設定的默認保固月數計算

### 4.6 訂單管理

- 用戶可查看自己的訂單歷史和狀態
- 管理員可查看所有訂單並更新訂單狀態
- 訂單狀態包括：待處理、處理中、已發貨、已送達、已取消、已退款

## 5. 前後端交互

### 5.1 API 設計

系統採用 RESTful API 設計原則，主要端點如下：

- **訪客可用 API**：
  - `GET /products`：獲取產品列表
  - `GET /products/{id}`：獲取單一產品詳情
  - `POST /register`：用戶註冊
  - `POST /login`：用戶登入

- **一般用戶 API**：
  - `POST /logout`：用戶登出
  - `GET/POST/PUT/DELETE /my/addresses`：地址管理
  - `POST /orders`：創建訂單
  - `GET /orders`：獲取訂單列表
  - `GET /my-devices`：獲取用戶設備與保固

- **管理員 API**：
  - `GET/POST/PUT/DELETE /admin/products`：產品管理
  - `POST /admin/products/{id}/adjust-stock`：庫存調整
  - `GET /admin/orders`：查看所有訂單
  - `PUT /admin/orders/{id}/status`：更新訂單狀態

### 5.2 前端實現

前端採用原生 JavaScript 實現，主要功能模塊：

1. **Navigation 模組**：處理導航菜單和用戶認證狀態
2. **Product 模組**：處理產品列表和詳情頁面
3. **ShoppingCart 模組**：管理購物車操作和本地存儲
4. **Checkout 模組**：處理結帳流程和訂單提交
5. **User Profile 模組**：管理用戶地址、訂單和設備

## 6. 安全考量

系統實施了多項安全措施：

1. **資料驗證**：
   - 伺服器端驗證所有用戶輸入
   - 防止 SQL 注入攻擊

2. **認證與授權**：
   - 密碼雜湊存儲
   - 基於角色的訪問控制
   - API 權限檢查

3. **前端安全**：
   - 防 XSS 攻擊（資料轉義）
   - CSRF 保護
   - 敏感數據處理

## 7. 商業邏輯特點

### 7.1 產品保固追踪

系統的一個關鍵特點是自動化的產品保固追踪系統：

- 當用戶購買產品時，系統自動創建保固記錄
- 保固期限基於產品的默認保固月數
- 用戶可以通過「我的設備」頁面查看所有已購產品的保固狀態
- 系統自動計算保固到期日，並更新保固狀態

### 7.2 庫存管理

庫存管理是系統的另一個重要功能：

- 庫存數量在創建訂單時自動減少
- 管理員可以手動調整庫存數量
- 所有庫存變動都會記錄在庫存日誌中，確保可追溯性
- 產品列表頁面實時顯示庫存狀態

### 7.3 訂單處理流程

訂單處理涉及多個步驟：

1. **訂單創建**：
   - 驗證購物車商品庫存
   - 計算訂單總額
   - 保存收貨地址和付款方式

2. **訂單確認**：
   - 發送訂單確認通知
   - 減少產品庫存
   - 創建初始保固記錄

3. **訂單狀態更新**：
   - 管理員可更新訂單狀態
   - 狀態變更會自動通知用戶

## 8. 系統優勢

1. **完整電商流程**：從產品瀏覽、購物車、結帳到售後服務的完整流程

2. **保固管理**：自動化的保固記錄和追踪系統，幫助用戶管理已購設備

3. **庫存透明度**：實時庫存顯示，防止超賣問題

4. **多地址管理**：用戶可以管理多個收貨地址，便於不同場景使用

5. **完整管理後台**：管理員擁有全面的產品和訂單管理功能

## 9. 未來可擴展方向

1. **多語言支持**：添加多語言支持以拓展國際市場

2. **進階搜索功能**：實現更複雜的產品搜索和過濾功能

3. **評價系統**：允許用戶對已購產品進行評分和評論

4. **會員等級**：實施會員等級制度，提供差異化服務

5. **售後服務申請**：線上申請維修或退換貨功能

6. **數據分析**：實施銷售和用戶行為分析功能，優化庫存和營銷策略

## 10. 總結

3C Management System 是一個功能完整的電子產品銷售管理系統，採用前後端分離架構，實現了從產品瀏覽、購物車管理、訂單處理到售後服務的完整電商流程。系統的特色在於其強大的保固管理功能和透明的庫存追踪系統，為用戶和管理員提供了良好的使用體驗。

通過模組化設計和清晰的業務邏輯分層，系統具有良好的可維護性和可擴展性，能夠滿足電子產品零售業務的各種需求。
