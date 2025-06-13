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
    // 為了清楚，我們可以建立一個 updateStatusForAdmin，或者讓現有的 updateStatus 處理 Admin 情況    /**
    //  * 更新訂單的特定欄位 (例如狀態、追蹤編號等)
    //  * @param int $orderId
    //  * @param array $data 包含要更新欄位和值的關聯陣列
    //  * @return bool True on success, false on failure
    //  */
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
    
    /**
     * [ADMIN] 更新訂單狀態
     * @param int $orderId
     * @param string $status
     * @return bool True on success, false on failure
     */
    public function updateStatus($orderId, $status) {
        // 驗證狀態是否為有效值
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        return $this->updateFields($orderId, ['status' => $status]);
    }

    /**
     * 根據用戶ID獲取訂單列表
     * @param int $userId 用戶ID
     * @param int $limit 每頁數量
     * @param int $offset 偏移量
     * @return array|false 訂單列表或false
     */
    public function getByUserId($userId, $limit = 10, $offset = 0) {
        $sql = "SELECT o.*, 
                       a.recipient_name, a.phone_number, a.postal_code, a.city, a.street, a.country 
                FROM {$this->tableName} o
                JOIN addresses a ON o.address_id = a.id
                WHERE o.user_id = :user_id
                ORDER BY o.order_date DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        try {
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 為每個訂單獲取訂單項目
            foreach ($orders as &$order) {
                $order['items'] = $this->getOrderItems($order['id']);
            }
            
            return $orders;
        } catch (\PDOException $e) {
            error_log("Error fetching orders for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 計算用戶的訂單總數
     * @param int $userId 用戶ID
     * @return int 訂單總數
     */
    public function countByUserId($userId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['total'] : 0;
        } catch (\PDOException $e) {
            error_log("Error counting orders for user {$userId}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 根據訂單ID獲取訂單詳情（包含用戶權限檢查）
     * @param int $orderId 訂單ID
     * @param int $userId 用戶ID（可選，用於權限檢查）
     * @return array|null 訂單詳情或null
     */
    public function findById($orderId, $userId = null) {
        $sql = "SELECT o.*, 
                       a.recipient_name, a.phone_number, a.postal_code, a.city, a.street, a.country 
                FROM {$this->tableName} o
                JOIN addresses a ON o.address_id = a.id
                WHERE o.id = :order_id";
        
        $params = [':order_id' => $orderId];
        
        if ($userId !== null) {
            $sql .= " AND o.user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute($params);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                $order['items'] = $this->getOrderItems($orderId);
            }
            
            return $order;
        } catch (\PDOException $e) {
            error_log("Error fetching order {$orderId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 獲取訂單項目
     * @param int $orderId 訂單ID
     * @return array 訂單項目列表
     */
    public function getOrderItems($orderId) {
        $sql = "SELECT oi.*, p.name as product_name, p.model_number as product_model, p.image_url as product_image_url 
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        
        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error fetching order items for order {$orderId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 創建訂單（包含訂單項目）
     * @param array $orderData 訂單數據
     * @param array $items 訂單項目
     * @return int|false 新建訂單ID或false
     */
    public function create($orderData, $items) {
        try {
            $this->db->beginTransaction();
            
            // 計算總金額
            $totalAmount = $orderData['subtotal_amount'] + $orderData['shipping_fee'] - $orderData['discount_amount'];
            
            // 插入訂單
            $sql = "INSERT INTO {$this->tableName} 
                    (user_id, address_id, subtotal_amount, shipping_fee, discount_amount, total_amount, payment_method, transaction_id, notes, status, order_date) 
                    VALUES (:user_id, :address_id, :subtotal_amount, :shipping_fee, :discount_amount, :total_amount, :payment_method, :transaction_id, :notes, 'pending', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $orderData['user_id'],
                ':address_id' => $orderData['address_id'],
                ':subtotal_amount' => $orderData['subtotal_amount'],
                ':shipping_fee' => $orderData['shipping_fee'],
                ':discount_amount' => $orderData['discount_amount'],
                ':total_amount' => $totalAmount,
                ':payment_method' => $orderData['payment_method'],
                ':transaction_id' => $orderData['transaction_id'],
                ':notes' => $orderData['notes']
            ]);
            
            $orderId = $this->db->lastInsertId();
            
            // 插入訂單項目
            $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
            $itemStmt = $this->db->prepare($itemSql);
            
            foreach ($items as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
                
                // 更新產品庫存
                $updateStockSql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                $updateStockStmt = $this->db->prepare($updateStockSql);
                $updateStockStmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            $this->db->commit();
            return $orderId;
            
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Error creating order: " . $e->getMessage());
            return false;
        }
    }
}
?>