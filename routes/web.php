<?php
// 3Cmanage/routes/web.php

// ... (existing routes) ...

// --- Customer Routes (Require Login) ---

// ... (Order Management and Address Management routes already defined) ...
// Admin Inventory Management (linked to products)
$router->post('admin/products/{id}/adjust-stock', 'AdminProductController@adjustStock'); // 手動調整庫存
$router->get('admin/products/{id}/inventory-logs', 'AdminProductController@getInventoryLogs'); // 查看產品庫存日誌
// --- Store Admin Routes (Require Login and 'store_admin' role) ---
$router->get('admin/products', 'AdminProductController@index');         // 列出所有產品 (管理)
$router->post('admin/products', 'AdminProductController@store');        // 新增產品
$router->get('admin/products/{id}', 'AdminProductController@show');     // 獲取特定產品資訊 (供編輯)
$router->put('admin/products/{id}', 'AdminProductController@update');   // 更新特定產品
$router->delete('admin/products/{id}', 'AdminProductController@destroy'); // 刪除特定產品

// My Devices and Warranty Lookup
$router->get('my-devices', 'UserController@listMyDevices');                      // 我的設備列表
$router->get('my-devices/warranty/{warrantyId}', 'UserController@getDeviceWarrantyDetails'); // 查看特定設備的保固詳情
// Admin Order Management
$router->get('admin/orders', 'AdminOrderController@index');              // 列出所有訂單 (管理)
$router->get('admin/orders/{id}', 'AdminOrderController@show');          // 獲取特定訂單詳情 (管理)
$router->put('admin/orders/{id}/status', 'AdminOrderController@updateStatus'); // 更新訂單狀態 (管理)
// ... (other future routes) ...
?>