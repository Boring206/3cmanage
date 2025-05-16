<?php
// 3Cmanage/app/Controllers/AdminOrderController.php
namespace App\Controllers;

use Core\Controller;
use App\Models\Order; // 我們會用到 Order 模型

class AdminOrderController extends Controller {

    private $orderModel;

    public function __construct() {
        // parent::__construct(); // 如果 Core\Controller 有建構子
        $this->orderModel = new Order();

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * 輔助方法：確保使用者已登入
     */
    private function ensureLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            $this->errorResponse('Unauthorized. Please login.', 401);
            exit;
        }
        return $_SESSION['user_id'];
    }

    /**
     * 輔助方法：確保使用者是店家管理員 (store_admin)
     */
    private function ensureStoreAdmin() {
        $this->ensureLoggedIn(); // 先確保登入
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'store_admin') {
            $this->errorResponse('Forbidden. You do not have administrative privileges.', 403);
            exit;
        }
        // 可以返回 admin 的 user_id，雖然在訂單管理中可能較少直接用到 admin 的 id 來篩選訂單
        return $_SESSION['user_id'];
    }

    /**
     * [ADMIN] 列出所有訂單供店家管理
     * GET /admin/orders
     * 可選查詢參數: status, page, limit, sort_by, sort_order, user_id, date_from, date_to
     */
    public function index() {
        $this->ensureStoreAdmin();

        // --- 篩選與分頁參數 ---
        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }
        if (!empty($_GET['date_from'])) { // YYYY-MM-DD
            $filters['date_from'] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) { // YYYY-MM-DD
            $filters['date_to'] = $_GET['date_to'];
        }
        // TODO: Add more filters like product_id if needed (would require more complex query)

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 15;
        $offset = ($page - 1) * $limit;

        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'order_date'; // e.g., order_date, total_amount
        $sortOrder = isset($_GET['sort_order']) && in_array(strtoupper($_GET['sort_order']), ['ASC', 'DESC']) ? strtoupper($_GET['sort_order']) : 'DESC';


        // 需要 Order 模型中有一個 getAllForAdmin 方法來處理這些篩選和排序
        $orders = $this->orderModel->getAllForAdmin($filters, $limit, $offset, $sortBy, $sortOrder);
        $totalOrders = $this->orderModel->getTotalCountForAdmin($filters); // 計算符合篩選條件的總訂單數

        if ($orders === false) {
            return $this->errorResponse('Could not fetch orders for admin.', 500);
        }
        
        $this->jsonResponse([
            'orders' => $orders,
            'total' => $totalOrders,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($totalOrders / $limit)
        ]);
    }

    /**
     * [ADMIN] 獲取特定訂單的詳細資訊
     * GET /admin/orders/{id}
     */
    public function show($params) {
        $this->ensureStoreAdmin();
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Order ID is required and must be numeric.', 400);
        }
        $orderId = (int)$params['id'];

        // Order 模型中的 findByIdForUser 可能可以沿用，或建立一個 findByIdForAdmin
        // findByIdForAdmin 可能會獲取更多店家相關的資訊或不受限於特定 user_id
        $order = $this->orderModel->findDetailsByIdForAdmin($orderId); // 假設有此方法

        if ($order) {
            return $this->jsonResponse($order);
        } else {
            return $this->errorResponse('Order not found.', 404);
        }
    }

    /**
     * [ADMIN] 更新訂單狀態
     * PUT /admin/orders/{id}/status (或 PATCH)
     * Input JSON: {"status": "shipped", "tracking_number": "..." (可選)}
     */
    public function updateStatus($params) {
        $this->ensureStoreAdmin();
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Order ID is required.', 400);
        }
        $orderId = (int)$params['id'];

        $input = (array) json_decode(file_get_contents('php://input'), true);
        if (empty($input['status'])) {
            return $this->errorResponse('New status is required.', 400);
        }
        $newStatus = trim($input['status']);
        
        // 驗證訂單是否存在
        $order = $this->orderModel->findDetailsByIdForAdmin($orderId);
        if (!$order) {
             return $this->errorResponse('Order not found.', 404);
        }

        // (可選) 驗證狀態是否為合法的訂單狀態 (ENUM 中定義的)
        $allowedStatuses = ['pending_confirmation', 'pending_payment', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        if (!in_array($newStatus, $allowedStatuses)) {
            return $this->errorResponse("Invalid order status: {$newStatus}.", 400);
        }

        // (可選) 根據當前狀態和新狀態進行邏輯檢查 (例如，已出貨的訂單不能直接變回待處理)
        // if ($order['status'] === 'shipped' && $newStatus === 'processing') {
        // return $this->errorResponse('Cannot change status from shipped back to processing.', 400);
        // }

        $updateData = ['status' => $newStatus];
        
        // 如果是 'shipped' 狀態，可能需要記錄運送資訊 (例如，追蹤編號)
        // 這需要 orders 資料表有相應的欄位，例如 tracking_number VARCHAR(255) NULL
        if ($newStatus === 'shipped' && !empty($input['tracking_number'])) {
            // $updateData['tracking_number'] = trim($input['tracking_number']); // 假設 orders 表有 tracking_number 欄位
        }
        
        // 我們需要 Order 模型中有一個通用的 update 方法或專用的 updateStatusForAdmin
        if ($this->orderModel->updateFields($orderId, $updateData)) { // 假設有 updateFields 方法
            // (可選) 觸發通知給顧客 (例如Email)
            $updatedOrder = $this->orderModel->findDetailsByIdForAdmin($orderId);
            return $this->jsonResponse(['message' => 'Order status updated successfully.', 'order' => $updatedOrder]);
        } else {
            return $this->errorResponse('Failed to update order status or no changes made.', 500);
        }
    }
    
    // 其他可能的店家訂單管理功能：
    // - 為訂單新增備註 (店家內部備註)
    // - 列印訂單/出貨單
    // - 處理退款申請 (如果訂單狀態為 'refunded')
}
?>