<?php
// 3Cmanage/app/Controllers/AdminProductController.php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Product;
use App\Models\InventoryLog;

class AdminProductController extends Controller {
    private $productModel;
    private $inventoryLogModel;

    public function __construct() {
        $this->productModel = new Product();
        $this->inventoryLogModel = new InventoryLog();
        
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

    private function ensureStoreAdmin() {
        $userId = $this->ensureLoggedIn();
        
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'store_admin') {
            $this->errorResponse('Forbidden. Admin access required.', 403);
            exit;
        }
        
        return $userId;
    }

    /**
     * [ADMIN] 列出所有產品
     * GET /admin/products
     */
    public function index($params = []) {
        $this->ensureStoreAdmin();
        
        // 支援分頁與篩選
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        // 準備過濾條件
        $filters = [
            'category' => $_GET['category'] ?? null,
            'brand' => $_GET['brand'] ?? null,
            'search' => $_GET['search'] ?? null,
            'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : null,
            'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : null,
            'order_by' => $_GET['order_by'] ?? 'created_at',
            'order_direction' => $_GET['order_direction'] ?? 'DESC'
        ];
        
        // 管理員可能需要更多產品信息
        $products = $this->productModel->getAll($limit, $offset, $filters);
        $totalCount = $this->productModel->count($filters);
        
        $totalPages = ceil($totalCount / $limit);
        $categories = $this->productModel->getCategories();
        $brands = $this->productModel->getBrands();
        
        return $this->jsonResponse([
            'data' => $products,
            'meta' => [
                'current_page' => $page,
                'last_page' => $totalPages,
                'per_page' => $limit,
                'total' => $totalCount,
                'filters' => [
                    'categories' => $categories,
                    'brands' => $brands
                ]
            ]
        ]);
    }

    /**
     * [ADMIN] 新增產品
     * POST /admin/products
     */
    public function store($params = []) {
        $userId = $this->ensureStoreAdmin();
        
        $input = (array) json_decode(file_get_contents('php://input'), true);
        
        // 驗證必要欄位
        $requiredFields = ['name', 'description', 'category', 'brand', 'model_number', 'price', 'stock_quantity', 'default_warranty_months'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                return $this->errorResponse("Field '{$field}' is required.", 400);
            }
        }
        
        // 驗證數字欄位
        if (!is_numeric($input['price']) || $input['price'] <= 0) {
            return $this->errorResponse('Price must be a positive number.', 400);
        }
        
        if (!is_numeric($input['stock_quantity']) || $input['stock_quantity'] < 0) {
            return $this->errorResponse('Stock quantity must be a non-negative integer.', 400);
        }
        
        // 確保規格是陣列或對象
        if (!isset($input['specifications']) || !is_array($input['specifications'])) {
            $input['specifications'] = [];
        }
        
        // 添加創建此產品的管理員ID
        $input['user_id'] = $userId;
        
        // 嘗試創建產品
        $productId = $this->productModel->create($input);
        
        if ($productId) {
            $product = $this->productModel->findById($productId);
            return $this->jsonResponse(['message' => 'Product created successfully.', 'product' => $product], 201);
        } else {
            return $this->errorResponse('Failed to create product.', 500);
        }
    }

    /**
     * [ADMIN] 查看特定產品
     * GET /admin/products/{id}
     */
    public function show($params = []) {
        $this->ensureStoreAdmin();
        
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Invalid product ID.', 400);
        }
        
        $productId = (int)$params['id'];
        $product = $this->productModel->findById($productId);
        
        if (!$product) {
            return $this->errorResponse('Product not found.', 404);
        }
        
        return $this->jsonResponse(['data' => $product]);
    }

    /**
     * [ADMIN] 更新產品
     * PUT /admin/products/{id}
     */
    public function update($params = []) {
        $this->ensureStoreAdmin();
        
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Invalid product ID.', 400);
        }
        
        $productId = (int)$params['id'];
        $product = $this->productModel->findById($productId);
        
        if (!$product) {
            return $this->errorResponse('Product not found.', 404);
        }
        
        $input = (array) json_decode(file_get_contents('php://input'), true);
        
        // 驗證必要欄位和數字欄位...
        // (與 store 方法類似，但這裡可以只更新提供的欄位)
        
        if (isset($input['price']) && (!is_numeric($input['price']) || $input['price'] <= 0)) {
            return $this->errorResponse('Price must be a positive number.', 400);
        }
        
        // 確保不通過此方法更改庫存數量 (應使用 adjustStock 方法)
        if (isset($input['stock_quantity'])) {
            unset($input['stock_quantity']);
        }
        
        // 確保規格是陣列或對象
        if (isset($input['specifications']) && !is_array($input['specifications'])) {
            $input['specifications'] = [];
        }
        
        // 嘗試更新產品
        $result = $this->productModel->update($productId, $input);
        
        if ($result) {
            $updatedProduct = $this->productModel->findById($productId);
            return $this->jsonResponse(['message' => 'Product updated successfully.', 'product' => $updatedProduct]);
        } else {
            return $this->errorResponse('Failed to update product.', 500);
        }
    }

    /**
     * [ADMIN] 刪除產品
     * DELETE /admin/products/{id}
     */
    public function destroy($params = []) {
        $this->ensureStoreAdmin();
        
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Invalid product ID.', 400);
        }
        
        $productId = (int)$params['id'];
        $product = $this->productModel->findById($productId);
        
        if (!$product) {
            return $this->errorResponse('Product not found.', 404);
        }
        
        // 嘗試刪除產品
        $result = $this->productModel->delete($productId);
        
        if ($result === true) {
            return $this->jsonResponse(['message' => 'Product deleted successfully.']);
        } else if ($result === 'PRODUCT_IN_USE') {
            return $this->errorResponse('Cannot delete product as it is referenced in orders.', 409);
        } else {
            return $this->errorResponse('Failed to delete product.', 500);
        }
    }

    /**
     * [ADMIN] 手動調整特定產品的庫存
     * POST /admin/products/{id}/adjust-stock
     * Input JSON: {"change_in_quantity": -5, "reason": "盤點調整 - 發現損壞"}
     * Input JSON: {"change_in_quantity": 100, "reason": "新到貨登錄"}
     */
    public function adjustStock($params) {
        $adminUserId = $this->ensureStoreAdmin();

        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Product ID is required for stock adjustment.', 400);
        }
        $productId = (int)$params['id'];

        $input = (array) json_decode(file_get_contents('php://input'), true);

        if (!isset($input['change_in_quantity']) || !is_numeric($input['change_in_quantity'])) {
            return $this->errorResponse('Change in quantity is required and must be numeric.', 400);
        }
        $changeInQuantity = (int)$input['change_in_quantity'];

        if ($changeInQuantity === 0) {
            return $this->errorResponse('Change in quantity cannot be zero.', 400);
        }
        
        $reason = isset($input['reason']) ? trim($input['reason']) : null;
        if (empty($reason)) {
            return $this->errorResponse('A reason for stock adjustment is required.', 400);
        }

        // 檢查產品是否存在
        $product = $this->productModel->findById($productId);
        if (!$product) {
            return $this->errorResponse('Product not found for stock adjustment.', 404);
        }

        // 執行庫存調整與日誌記錄 (在模型中處理)
        $result = $this->productModel->adjustStockAndLog($productId, $changeInQuantity, $adminUserId, $reason);

        if ($result === true) {
            $updatedProduct = $this->productModel->findById($productId);
            return $this->jsonResponse([
                'message' => 'Stock adjusted successfully.',
                'product' => $updatedProduct
            ]);
        } elseif ($result === 'INSUFFICIENT_STOCK') {
            return $this->errorResponse('Failed to adjust stock: insufficient stock for negative adjustment.', 409);
        } else {
            return $this->errorResponse('Failed to adjust stock.', 500);
        }
    }

    /**
     * [ADMIN] 查看特定產品的庫存調整日誌
     * GET /admin/products/{id}/inventory-logs
     */
    public function getInventoryLogs($params) {
        $this->ensureStoreAdmin();
        
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Product ID is required to fetch inventory logs.', 400);
        }
        $productId = (int)$params['id'];

        // 檢查產品是否存在
        $product = $this->productModel->findById($productId);
        if (!$product) {
            return $this->errorResponse('Product not found.', 404);
        }

        // 支援分頁
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        
        // 獲取日誌
        $logs = $this->inventoryLogModel->getByProductId($productId, $limit, $offset);
        $totalCount = $this->inventoryLogModel->countByProductId($productId);
        
        $totalPages = ceil($totalCount / $limit);
        
        if ($logs === false) {
            return $this->errorResponse('Could not fetch inventory logs.', 500);
        }
        
        return $this->jsonResponse([
            'data' => $logs,
            'product' => [
                'id' => $product['id'],
                'name' => $product['name'],
                'current_stock' => $product['stock_quantity']
            ],
            'meta' => [
                'current_page' => $page,
                'last_page' => $totalPages,
                'per_page' => $limit,
                'total' => $totalCount
            ]
        ]);
    }
}
?>