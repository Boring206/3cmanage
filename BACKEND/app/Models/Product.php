<?php
// 3Cmanage/app/Models/Product.php
namespace App\Models;

use Core\DB;
use PDO;

class Product {
    private $db;
    private $tableName = 'products';

    public function __construct() {
        $this->db = DB::getInstance()->getConnection();
    }
    
    /**
     * 根據ID查找產品
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 獲取所有產品，支援分頁和篩選
     */
    public function getAll($limit = null, $offset = 0, $filters = []) {
        $sql = "SELECT * FROM {$this->tableName} WHERE 1=1";
        $params = [];
        
        // 添加過濾條件
        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $sql .= " AND brand = :brand";
            $params[':brand'] = $filters['brand'];
        }
        
        // 價格範圍過濾
        if (isset($filters['min_price'])) {
            $sql .= " AND price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (isset($filters['max_price'])) {
            $sql .= " AND price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        // 關鍵字搜尋
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (name LIKE :search OR description LIKE :search OR brand LIKE :search OR model_number LIKE :search)";
            $params[':search'] = $search;
        }
        
        // 排序
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'DESC';
        
        // 防SQL注入：確保order_by是合法的列名
        $allowedColumns = ['id', 'name', 'price', 'stock_quantity', 'category', 'brand', 'created_at'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'created_at';
        }
        
        $sql .= " ORDER BY {$orderBy} {$orderDirection}";
        
        // 分頁
        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
        }
        
        $stmt = $this->db->prepare($sql);
        
        // 繫結參數
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 根據篩選條件計算產品數量
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->tableName} WHERE 1=1";
        $params = [];
        
        // 添加過濾條件
        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $sql .= " AND brand = :brand";
            $params[':brand'] = $filters['brand'];
        }
        
        // 價格範圍過濾
        if (isset($filters['min_price'])) {
            $sql .= " AND price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (isset($filters['max_price'])) {
            $sql .= " AND price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        // 關鍵字搜尋
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (name LIKE :search OR description LIKE :search OR brand LIKE :search OR model_number LIKE :search)";
            $params[':search'] = $search;
        }
        
        $stmt = $this->db->prepare($sql);
        
        // 繫結參數
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    /**
     * 獲取所有產品分類
     */
    public function getCategories() {
        $stmt = $this->db->query("SELECT DISTINCT category FROM {$this->tableName} ORDER BY category");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * 獲取所有產品品牌
     */
    public function getBrands() {
        $stmt = $this->db->query("SELECT DISTINCT brand FROM {$this->tableName} ORDER BY brand");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * 創建新產品
     */
    public function create($data) {
        // 確保 specifications 是 JSON 格式
        $specifications = is_array($data['specifications']) ? json_encode($data['specifications']) : '{}';
        
        $sql = "INSERT INTO {$this->tableName} 
                (name, description, category, brand, model_number, specifications, price, 
                stock_quantity, image_url, default_warranty_months) 
                VALUES 
                (:name, :description, :category, :brand, :model_number, :specifications, :price, 
                :stock_quantity, :image_url, :default_warranty_months)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':brand', $data['brand']);
        $stmt->bindParam(':model_number', $data['model_number']);        $stmt->bindParam(':specifications', $specifications);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':stock_quantity', $data['stock_quantity'], PDO::PARAM_INT);
        $imageUrl = $data['image_url'] ?? null;
        $stmt->bindParam(':image_url', $imageUrl);
        $stmt->bindParam(':default_warranty_months', $data['default_warranty_months'], PDO::PARAM_INT);
        
        try {
            if ($stmt->execute()) {
                $productId = $this->db->lastInsertId();
                
                // 記錄初始庫存
                $inventoryLog = new InventoryLog();
                $inventoryLog->logChange(
                    $productId, 
                    $data['user_id'] ?? null, 
                    $data['stock_quantity'], 
                    'Initial stock on product creation'
                );
                
                return $productId;
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Product creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 更新產品
     */
    public function update($id, $data) {
        $setClauses = [];
        $params = [':id' => $id];
        
        // 建立允許更新的欄位清單
        $allowedFields = [
            'name', 'description', 'category', 'brand', 'model_number', 
            'specifications', 'price', 'image_url', 'default_warranty_months'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                // 特殊處理 specifications 欄位 (需要 JSON 格式)
                if ($field === 'specifications' && is_array($data[$field])) {
                    $params[":$field"] = json_encode($data[$field]);
                } else {
                    $params[":$field"] = $data[$field];
                }
                $setClauses[] = "$field = :$field";
            }
        }
        
        if (empty($setClauses)) {
            return false; // 沒有欄位需要更新
        }
        
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setClauses) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        // 繫結參數
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Product update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 刪除產品
     */
    public function delete($id) {
        // 檢查產品是否存在訂單關聯
        $checkOrderItems = $this->db->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = :id");
        $checkOrderItems->bindParam(':id', $id, PDO::PARAM_INT);
        $checkOrderItems->execute();
        $result = $checkOrderItems->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return 'PRODUCT_IN_USE'; // 產品被訂單使用中，不能刪除
        }
        
        // 執行刪除
        $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Product deletion error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 手動調整產品庫存並記錄到 inventory_logs。
     * 此操作應在一個交易中完成。
     *
     * @param int $productId 產品ID
     * @param int $quantityChange 庫存變化量 (正數增加, 負數減少)
     * @param int $adminUserId 操作的管理者ID
     * @param string $reason 調整原因
     * @return bool|string True on success, 'INSUFFICIENT_STOCK' if trying to decrease more than available, false on general error.
     */
    public function adjustStockAndLog($productId, $quantityChange, $adminUserId, $reason) {
        $this->db->beginTransaction();

        try {
            // 1. 獲取當前庫存並鎖定該行 (防止併發問題)
            $stmt = $this->db->prepare("SELECT stock_quantity FROM {$this->tableName} WHERE id = :id FOR UPDATE");
            $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            $currentStock = $stmt->fetchColumn();

            if ($currentStock === false) { // 產品不存在
                $this->db->rollBack();
                return false;
            }

            $newStock = (int)$currentStock + $quantityChange;

            // 2. 如果是減少庫存，檢查是否足夠
            if ($newStock < 0) {
                $this->db->rollBack();
                return 'INSUFFICIENT_STOCK'; // 返回特定錯誤標識
            }

            // 3. 更新產品庫存
            $updateSql = "UPDATE {$this->tableName} SET stock_quantity = :new_stock WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bindParam(':new_stock', $newStock, PDO::PARAM_INT);
            $updateStmt->bindParam(':id', $productId, PDO::PARAM_INT);
            
            if (!$updateStmt->execute()) {
                $this->db->rollBack();
                error_log("Failed to update stock for product ID {$productId} during adjustment.");
                return false;
            }

            // 4. 記錄到 inventory_logs
            $logSql = "INSERT INTO inventory_logs (product_id, user_id, change_in_quantity, reason, log_date)
                       VALUES (:product_id, :user_id, :change_in_quantity, :reason, NOW())";
            $logStmt = $this->db->prepare($logSql);
            $logStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $logStmt->bindParam(':user_id', $adminUserId, PDO::PARAM_INT);
            $logStmt->bindParam(':change_in_quantity', $quantityChange, PDO::PARAM_INT);
            $logStmt->bindParam(':reason', $reason);

            if (!$logStmt->execute()) {
                $this->db->rollBack();
                error_log("Failed to log inventory adjustment for product ID {$productId}.");
                return false;
            }

            $this->db->commit();
            return true;

        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Stock adjustment and logging error for product ID {$productId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 獲取特定產品的庫存調整日誌
     * @param int $productId
     * @param int $limit
     * @param int $offset
     * @return array|false
     */
    public function getInventoryLogsForProduct($productId, $limit = 20, $offset = 0) {
        $sql = "SELECT il.*, u.username as admin_username 
                FROM inventory_logs il
                JOIN users u ON il.user_id = u.id
                WHERE il.product_id = :product_id
                ORDER BY il.log_date DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error fetching inventory logs for product ID {$productId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 獲取特定產品的庫存調整日誌總數
     * @param int $productId
     * @return int
     */
    public function getTotalInventoryLogsForProductCount($productId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM inventory_logs WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['total'] : 0;
    }
}
?>