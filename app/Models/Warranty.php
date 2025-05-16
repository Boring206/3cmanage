<?php
// 3Cmanage/app/Models/Warranty.php
namespace App\Models;

use Core\DB;
use PDO;

class Warranty {
    private $db;

    public function __construct() {
        $this->db = DB::getInstance()->getConnection();
    }

    // ... (create, findByOrderItemId, findByUserDevice 方法已存在) ...

    /**
     * 根據使用者ID查找其所有保固記錄 (用於 "我的設備")
     * @param int $userId
     * @param int $limit (可選, 用於分頁)
     * @param int $offset (可選, 用於分頁)
     * @return array|false
     */
    public function findByUserId($userId, $limit = null, $offset = 0) {
        $sql = "SELECT 
                    w.id as warranty_id, 
                    w.purchase_date, 
                    w.warranty_period_months, 
                    w.expiry_date, 
                    w.status as warranty_status, 
                    w.serial_number,
                    p.id as product_id, 
                    p.name as product_name, 
                    p.model_number, 
                    p.image_url as product_image_url,
                    p.specifications as product_specifications,
                    oi.quantity as purchased_quantity, -- 該訂單項目購買的數量
                    o.id as order_id,
                    o.order_date
                FROM warranties w
                JOIN order_items oi ON w.order_item_id = oi.id
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON w.product_id = p.id
                WHERE w.user_id = :user_id
                ORDER BY w.purchase_date DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        if ($limit !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
        
        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error fetching warranties by user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 根據保固ID和使用者ID查找特定保固的詳細資訊
     * @param int $warrantyId
     * @param int $userId
     * @return array|null|false Null if not found/unauthorized, false on DB error
     */
    public function findDetailsByIdForUser($warrantyId, $userId) {
        $sql = "SELECT 
                    w.id as warranty_id, 
                    w.order_item_id,
                    w.purchase_date, 
                    w.warranty_period_months, 
                    w.expiry_date, 
                    w.status as warranty_status, 
                    w.serial_number,
                    p.id as product_id, 
                    p.name as product_name, 
                    p.brand as product_brand,
                    p.category as product_category,
                    p.model_number, 
                    p.image_url as product_image_url,
                    p.description as product_description,
                    p.specifications as product_specifications,
                    p.price as product_current_price, -- 目前產品價格，非購買時價格
                    oi.quantity as purchased_quantity,
                    oi.price_at_purchase,
                    o.id as order_id,
                    o.order_date,
                    o.status as order_status,
                    addr.recipient_name, addr.phone_number, addr.postal_code, addr.city, addr.street -- 訂單運送地址
                FROM warranties w
                JOIN order_items oi ON w.order_item_id = oi.id
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON w.product_id = p.id
                JOIN addresses addr ON o.address_id = addr.id
                WHERE w.id = :warranty_id AND w.user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':warranty_id', $warrantyId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result : null; // Return null if no record found (or not authorized)
        } catch (\PDOException $e) {
            error_log("Error fetching warranty details for warranty ID {$warrantyId}, user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }
}
?>