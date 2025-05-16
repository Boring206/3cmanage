<?php
// 3Cmanage/app/Controllers/AdminProductController.php
namespace App\Controllers;

use Core\Controller;
use App\Models\Product;

class AdminProductController extends Controller {

    // ... (existing __construct, ensureLoggedIn, ensureStoreAdmin, index, store, show, update, destroy methods) ...

    /**
     * [ADMIN] 手動調整特定產品的庫存
     * POST /admin/products/{id}/adjust-stock
     * Input JSON: {"change_in_quantity": -5, "reason": "盤點調整 - 發現損壞"}
     * Input JSON: {"change_in_quantity": 100, "reason": "新到貨登錄"}
     */
    public function adjustStock($params) {
        $adminUserId = $this->ensureStoreAdmin(); // 獲取操作的 admin user ID

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
            $updatedProduct = $this->productModel->findById($productId); // 獲取更新後的產品資訊
            return $this->jsonResponse([
                'message' => 'Stock adjusted successfully.',
                'product' => $updatedProduct
            ]);
        } elseif ($result === 'INSUFFICIENT_STOCK') {
            return $this->errorResponse('Failed to adjust stock: insufficient stock for negative adjustment.', 409); // 409 Conflict
        } else {
            return $this->errorResponse('Failed to adjust stock.', 500);
        }
    }

    /**
     * [ADMIN] 查看特定產品的庫存調整日誌 (可選功能)
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

        // 需要 Product 模型 (或一個新的 InventoryLog 模型) 提供獲取日誌的方法
        $logs = $this->productModel->getInventoryLogsForProduct($productId); // 假設 Product 模型有此方法

        if ($logs === false) {
            return $this->errorResponse('Could not fetch inventory logs.', 500);
        }
        $this->jsonResponse($logs);
    }
}
?>