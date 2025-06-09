<?php declare(strict_types=1);
// 3Cmanage/app/Controllers/OrderController.php
namespace App\Controllers;

use Core\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Address;
use App\Models\Warranty;

class OrderController extends Controller {
    private $orderModel;
    private $addressModel;
    private $productModel;
    
    public function __construct() {
        $this->orderModel = new Order();
        $this->addressModel = new Address();
        $this->productModel = new Product();
        
        // 確保 session 已啟動，用於身份驗證
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    private function ensureLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            $this->errorResponse('Unauthorized. Please login to proceed.', 401);
            exit;
        }
        return $_SESSION['user_id'];
    }
    
    /**
     * 查看使用者的所有地址
     * GET /my/addresses
     */
    public function listAddresses($params = []) {
        $userId = $this->ensureLoggedIn();
        
        // 獲取用戶的所有地址
        $addresses = $this->addressModel->getByUserId($userId);
        
        return $this->jsonResponse(['data' => $addresses]);
    }
    
    /**
     * 新增地址
     * POST /my/addresses
     */
    public function addAddress($params = []) {
        $userId = $this->ensureLoggedIn();
        
        $input = (array) json_decode(file_get_contents('php://input'), true);
        
        // 驗證必要欄位
        $requiredFields = ['recipient_name', 'phone_number', 'postal_code', 'city', 'street', 'country'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                return $this->errorResponse("Field '{$field}' is required.", 400);
            }
        }
        
        // 添加使用者ID到資料
        $input['user_id'] = $userId;
        
        // 創建地址
        $addressId = $this->addressModel->create($input);
        
        if ($addressId) {
            $address = $this->addressModel->findById($addressId);
            return $this->jsonResponse(['message' => 'Address added successfully.', 'address' => $address], 201);
        } else {
            return $this->errorResponse('Failed to add address.', 500);
        }
    }
    
    /**
     * 更新地址
     * PUT /my/addresses/{id}
     */
    public function updateAddress($params = []) {
        $userId = $this->ensureLoggedIn();
        
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Invalid address ID.', 400);
        }
        
        $addressId = (int)$params['id'];
        $address = $this->addressModel->findById($addressId);
        
        if (!$address) {
            return $this->errorResponse('Address not found.', 404);
        }
        
        // 確保使用者只能修改自己的地址
        if ($address['user_id'] != $userId) {
            return $this->errorResponse('You do not have permission to modify this address.', 403);
        }
        
        $input = (array) json_decode(file_get_contents('php://input'), true);
        
        // 驗證必要欄位
        $requiredFields = ['recipient_name', 'phone_number', 'postal_code', 'city', 'street', 'country'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                return $this->errorResponse("Field '{$field}' is required.", 400);
            }
        }
        
        // 更新地址
        $result = $this->addressModel->update($addressId, $input);
        
        if ($result) {
            $updatedAddress = $this->addressModel->findById($addressId);
            return $this->jsonResponse(['message' => 'Address updated successfully.', 'address' => $updatedAddress]);
        } else {
            return $this->errorResponse('Failed to update address.', 500);
        }
    }
    
    /**
     * 刪除地址
     * DELETE /my/addresses/{id}
     */
    public function deleteAddress($params = []) {
        $userId = $this->ensureLoggedIn();
        
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Invalid address ID.', 400);
        }
        
        $addressId = (int)$params['id'];
        $address = $this->addressModel->findById($addressId);
        
        if (!$address) {
            return $this->errorResponse('Address not found.', 404);
        }
        
        // 確保使用者只能刪除自己的地址
        if ($address['user_id'] != $userId) {
            return $this->errorResponse('You do not have permission to delete this address.', 403);
        }
        
        // 刪除地址
        $result = $this->addressModel->delete($addressId);
        
        if ($result) {
            return $this->jsonResponse(['message' => 'Address deleted successfully.']);
        } else {
            return $this->errorResponse('Failed to delete address.', 500);
        }
    }
    
    /**
     * 設定預設地址
     * POST /my/addresses/{id}/set-default
     */
    public function setDefaultAddress($params = []) {
        $userId = $this->ensureLoggedIn();
        
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Invalid address ID.', 400);
        }
        
        $addressId = (int)$params['id'];
        $address = $this->addressModel->findById($addressId);
        
        if (!$address) {
            return $this->errorResponse('Address not found.', 404);
        }
        
        // 確保使用者只能設定自己的地址為預設
        if ($address['user_id'] != $userId) {
            return $this->errorResponse('You do not have permission to modify this address.', 403);
        }
        
        // 設定為預設地址
        $result = $this->addressModel->setDefault($addressId, $userId);
        
        if ($result) {
            $updatedAddress = $this->addressModel->findById($addressId);
            return $this->jsonResponse(['message' => 'Default address set successfully.', 'address' => $updatedAddress]);
        } else {
            return $this->errorResponse('Failed to set default address.', 500);
        }
    }
    
    /**
     * 查看使用者的所有訂單
     * GET /orders
     */
    public function listOrders($params = []) {
        $userId = $this->ensureLoggedIn();
        
        // 支援分頁
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        try {
            // 獲取用戶的訂單 - 使用正確的方法名稱
            $orders = $this->orderModel->getByUserId($userId, $limit, $offset);
            $totalCount = $this->orderModel->countByUserId($userId);
            
            if ($orders === false) {
                return $this->errorResponse('Could not fetch your orders.', 500);
            }
            
            $totalPages = ceil($totalCount / $limit);
            
            return $this->jsonResponse([
                'data' => $orders,
                'meta' => [
                    'current_page' => $page,
                    'last_page' => $totalPages,
                    'per_page' => $limit,
                    'total' => $totalCount
                ]
            ]);
        } catch (\Exception $e) {
            error_log('Error fetching orders for user ' . $userId . ': ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch orders.', 500);
        }
    }
    
    /**
     * 查看特定訂單
     * GET /orders/{id}
     */
    public function showOrder($params = []) {
        $userId = $this->ensureLoggedIn();
        
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Invalid order ID.', 400);
        }
        
        $orderId = (int)$params['id'];
        
        try {
            // 獲取訂單基本資訊（確保用戶只能查看自己的訂單） - 使用正確的方法名稱
            $order = $this->orderModel->findById($orderId, $userId);
            
            if (!$order) {
                return $this->errorResponse('Order not found or you do not have permission to view it.', 404);
            }
            
            // 獲取訂單項目
            $items = $this->orderModel->getOrderItems($orderId);
            
            $orderData = [
                'order' => $order,
                'items' => $items
            ];
            
            return $this->jsonResponse(['data' => $orderData]);
        } catch (\Exception $e) {
            error_log('Error fetching order ' . $orderId . ' for user ' . $userId . ': ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch order details.', 500);
        }
    }
    
    /**
     * 建立訂單
     * POST /orders
     */
    public function createOrder($params = []) {
        $userId = $this->ensureLoggedIn();
        
        $input = (array) json_decode(file_get_contents('php://input'), true);
        
        // 驗證必要欄位
        if (empty($input['address_id']) || !is_numeric($input['address_id'])) {
            return $this->errorResponse('Valid address ID is required.', 400);
        }
        
        if (empty($input['items']) || !is_array($input['items']) || count($input['items']) === 0) {
            return $this->errorResponse('Order must contain at least one item.', 400);
        }
        
        $addressId = (int)$input['address_id'];
        $address = $this->addressModel->findById($addressId);
        
        // 確保地址存在且屬於該用戶
        if (!$address || $address['user_id'] != $userId) {
            return $this->errorResponse('Invalid or unauthorized address.', 400);
        }
        
        // 驗證每個項目
        $items = $input['items'];
        $subtotalAmount = 0;
        
        foreach ($items as &$item) {
            if (empty($item['product_id']) || !is_numeric($item['product_id'])) {
                return $this->errorResponse('Each item must have a valid product ID.', 400);
            }
            
            if (empty($item['quantity']) || !is_numeric($item['quantity']) || $item['quantity'] <= 0) {
                return $this->errorResponse('Each item must have a valid quantity.', 400);
            }
            
            // 檢查產品是否存在
            $product = $this->productModel->findById($item['product_id']);
            if (!$product) {
                return $this->errorResponse("Product not found: {$item['product_id']}", 404);
            }
            
            // 檢查庫存是否足夠
            if ($product['stock_quantity'] < $item['quantity']) {
                return $this->errorResponse("Insufficient stock for product: {$product['name']}", 400);
            }
            
            // 計算項目總價
            $item['price'] = $product['price'];
            $subtotalAmount += $product['price'] * $item['quantity'];
        }
        
        // 計算運費（假設超過一定金額免運費）
        $shippingFee = ($subtotalAmount >= 1000) ? 0 : 100;
        
        // 準備訂單資料
        $orderData = [
            'user_id' => $userId,
            'address_id' => $addressId,
            'subtotal_amount' => $subtotalAmount,
            'shipping_fee' => $shippingFee,
            'discount_amount' => 0,
            'payment_method' => $input['payment_method'] ?? 'credit_card',
            'transaction_id' => $input['transaction_id'] ?? null,
            'notes' => $input['notes'] ?? null
        ];
        
        try {
            // 創建訂單（這會在事務中處理所有項目和庫存調整） - 使用正確的方法名稱
            $orderId = $this->orderModel->create($orderData, $items);
            
            if ($orderId) {
                // 獲取完整訂單資料
                $order = $this->orderModel->findById($orderId);
                $orderItems = $this->orderModel->getOrderItems($orderId);
                
                return $this->jsonResponse([
                    'message' => 'Order placed successfully.',
                    'order' => $order,
                    'items' => $orderItems
                ], 201);
            } else {
                return $this->errorResponse('Failed to create order.', 500);
            }
        } catch (\Exception $e) {
            error_log('Error creating order for user ' . $userId . ': ' . $e->getMessage());
            return $this->errorResponse('Failed to create order.', 500);
        }
    }
}
?>
