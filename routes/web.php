<?php
// 3Cmanage/routes/web.php
declare(strict_types=1);

// --- 訪客路由 (Guest Routes - 無需登入) ---
$router->get('products', 'ProductController@index');      // 查看所有產品與規格
$router->get('products/{id}', 'ProductController@show');  // 查看單一產品規格
$router->post('register', 'AuthController@register');     // 註冊帳號
$router->post('login', 'AuthController@login');          // 登入帳號

// --- 一般用戶路由 (Customer Routes - 需要登入) ---
$router->post('logout', 'AuthController@logout');   // 登出路由

// 地址管理 (Address Management)
$router->get('my/addresses', 'OrderController@listAddresses');              // 列出我的所有地址
$router->post('my/addresses', 'OrderController@addAddress');                // 新增地址
$router->put('my/addresses/{id}', 'OrderController@updateAddress');         // 修改地址
$router->delete('my/addresses/{id}', 'OrderController@deleteAddress');      // 刪除地址
$router->post('my/addresses/{id}/set-default', 'OrderController@setDefaultAddress'); // 設定為預設地址

// 訂單處理 (Order Management)
$router->post('orders', 'OrderController@createOrder');                 // 選擇3C並下訂單
$router->get('orders', 'OrderController@listOrders');                   // 查看歷史訂單列表
$router->get('orders/{id}', 'OrderController@showOrder');               // 查看特定訂單詳情

// 我的設備與保固 (My Devices and Warranty)
$router->get('my-devices', 'UserController@listMyDevices');            // 我的設備列表
$router->get('my-devices/warranty/{warrantyId}', 'UserController@getDeviceWarrantyDetails'); // 查看特定設備的保固詳情

// --- 店家路由 (Store Admin Routes - 需要登入且角色為 store_admin) ---

// 產品管理 (Product Management)
$router->get('admin/products', 'AdminProductController@index');        // 列出所有產品
$router->post('admin/products', 'AdminProductController@store');       // 新增產品
$router->get('admin/products/{id}', 'AdminProductController@show');    // 獲取特定產品詳情
$router->put('admin/products/{id}', 'AdminProductController@update');  // 更新產品
$router->delete('admin/products/{id}', 'AdminProductController@destroy'); // 刪除產品

// 盤點管理 (Inventory Management)
$router->post('admin/products/{id}/adjust-stock', 'AdminProductController@adjustStock');     // 手動調整產品庫存
$router->get('admin/products/{id}/inventory-logs', 'AdminProductController@getInventoryLogs'); // 查看產品庫存調整日誌

// 訂單管理 (Order Management)
$router->get('admin/orders', 'AdminOrderController@index');              // 列出所有顧客訂單
$router->get('admin/orders/{id}', 'AdminOrderController@show');          // 獲取特定訂單詳情
$router->put('admin/orders/{id}/status', 'AdminOrderController@updateStatus'); // 更新訂單狀態
?>