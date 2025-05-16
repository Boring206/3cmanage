<?php
// 3Cmanage/app/Models/Product.php
namespace App\Models;

use Core\DB;
use PDO;

class Product {
    // ... (existing __construct, tableName, create, findById, update, delete, etc. methods) ...

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
    
    // getTotalInventoryLogsForProductCount - 可選，用於分頁
    public function getTotalInventoryLogsForProductCount($productId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM inventory_logs WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['total'] : 0;
    }

}
?>