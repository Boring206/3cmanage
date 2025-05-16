<?php
// 3Cmanage/app/Models/Order.php
namespace App\Models;

use Core\DB;
use PDO;

class Order {
    private $db;
    private $tableName = 'orders';

    public function __construct() {
        $this->db = DB::getInstance()->getConnection();
    }

    // ... (createOrderWithItems, findByIdForUser, findByUserId 已存在) ...

    /**
     * [ADMIN] 獲取所有訂單 (支持篩選和分頁)
     */
    public function getAllForAdmin($filters = [], $limit = 15, $offset = 0, $sortBy = 'order_date', $sortOrder = 'DESC') {
        $sql = "SELECT o.*, u.username as customer_username, u.email as customer_email,
                       a.recipient_name, a.phone_number, a.street, a.city, a.postal_code, a.country
                FROM {$this->tableName} o
                JOIN users u ON o.user_id = u.id
                JOIN addresses a ON o.address_id = a.id
                WHERE 1=1"; // Base condition

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['user_id'])) {
            $sql .= " AND o.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(o.order_date) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(o.order_date) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        // Add more complex filters here if needed (e.g., by product_id in order_items)

        // Validate sortBy column to prevent SQL injection
        $allowedSortBy = ['order_date', 'total_amount', 'status', 'customer_username']; // Add more as needed
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'order_date'; // Default sort
        }
        $sql .= " ORDER BY o.`{$sortBy}` " . ($sortOrder === 'ASC' ? 'ASC' : 'DESC'); // Use backticks for column name

        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->prepare($sql);
        // Bind integer params explicitly
        if (isset($params[':limit'])) $stmt->bindParam(':limit', $params[':limit'], PDO::PARAM_INT);
        if (isset($params[':offset'])) $stmt->bindParam(':offset', $params[':offset'], PDO::PARAM_INT);
        if (isset($params[':user_id'])) $stmt->bindParam(':user_id', $params[':user_id'], PDO::PARAM_INT);
        
        // Bind other string params
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset' && $key !== ':user_id') { // Avoid re-binding
                $stmt->bindValue($key, $value);
            }
        }

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error fetching all orders for admin: " . $e->getMessage() . " SQL: " . $sql . " Params: " . json_encode($params));
            return false;
        }
    }
    
    /**
     * [ADMIN] 計算符合篩選條件的總訂單數
     */
    public function getTotalCountForAdmin($filters = []) {
        $sql = "SELECT COUNT(DISTINCT o.id) as total
                FROM {$this->tableName} o
                JOIN users u ON o.user_id = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['user_id'])) {
            $sql .= " AND o.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(o.order_date) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(o.order_date) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $stmt = $this->db->prepare($sql);
        if (isset($params[':user_id'])) $stmt->bindParam(':user_id', $params[':user_id'], PDO::PARAM_INT);
        foreach ($params as $key => $value) {
             if ($key !== ':user_id') {
                $stmt->bindValue($key, $value);
            }
        }
        
        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['total'] : 0;
        } catch (\PDOException $e) {
            error_log("Error counting orders for admin: " . $e->getMessage());
            return 0;
        }
    }


    /**
     * [ADMIN] 根據訂單ID獲取詳細訂單資訊 (包含顧客和訂單項目)
     */
    public function findDetailsByIdForAdmin($orderId) {
        // 這個方法可以和 findByIdForUser 非常相似，只是不受限於特定 user_id
        // 我們可以重用 findByIdForUser 的邏輯，或者建立一個更通用的版本
        $sql = "SELECT o.*, 
                       u.id as customer_id, u.username as customer_username, u.email as customer_email, u.name as customer_name,
                       a.recipient_name, a.phone_number, a.postal_code, a.city, a.street, a.country 
                FROM {$this->tableName} o
                JOIN users u ON o.user_id = u.id
                JOIN addresses a ON o.address_id = a.id
                WHERE o.id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // 獲取訂單項目
            $itemsSql = "SELECT oi.*, p.name as product_name, p.model_number as product_model, p.image_url as product_image_url 
                         FROM order_items oi
                         JOIN products p ON oi.product_id = p.id
                         WHERE oi.order_id = :order_id";
            $itemsStmt = $this->db->prepare($itemsSql);
            $itemsStmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $itemsStmt->execute();
            $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $order; // 返回 null 如果找不到訂單
    }

    // updateStatus 方法先前已有，我們可以調整它以供 Admin 使用
    // 先前的 updateStatus($orderId, $status, $userId = null, $userRole = null)
    // Admin 更新狀態時，不需要 $userId 和 $userRole 檢查 (或檢查 $userRole === 'store_admin')
    // 為了清楚，我們可以建立一個 updateStatusForAdmin，或者讓現有的 updateStatus 處理 Admin 情況

    /**
     * 更新訂單的特定欄位 (例如狀態、追蹤編號等)
     * @param int $orderId
     * @param array $data 包含要更新欄位和值的關聯陣列
     * @return bool True on success, false on failure
     */
    public function updateFields($orderId, $data) {
        if (empty($data)) {
            return false;
        }
        $setClauses = [];
        $params = [':order_id' => $orderId];

        // 白名單允許更新的欄位
        $allowedKeys = ['status', 'payment_method', 'transaction_id', 'notes' /* , 'tracking_number' ... */]; 
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $setClauses[] = "`{$key}` = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($setClauses)) {
            return false; // 沒有有效的欄位可更新
        }

        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setClauses) . " WHERE id = :order_id";
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute($params);
            return $stmt->rowCount() > 0; // 如果有行受到影響
        } catch (\PDOException $e) {
            error_log("Order field update error for ID {$orderId}: " . $e->getMessage());
            return false;
        }
    }
}
?>