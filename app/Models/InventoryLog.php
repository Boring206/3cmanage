<?php declare(strict_types=1);
// 3Cmanage/app/Models/InventoryLog.php
namespace App\Models;

use Core\DB;
use PDO;

class InventoryLog {
    private $db;
    private $tableName = 'inventory_logs';

    public function __construct() {
        $this->db = DB::getInstance()->getConnection();
    }
    
    public function logChange($productId, $userId, $changeInQuantity, $reason) {
        $sql = "INSERT INTO {$this->tableName} (product_id, user_id, change_in_quantity, reason, log_date) 
                VALUES (:product_id, :user_id, :change_in_quantity, :reason, NOW())";
                
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':change_in_quantity', $changeInQuantity, PDO::PARAM_INT);
        $stmt->bindParam(':reason', $reason);
        
        try {
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (\PDOException $e) {
            error_log("InventoryLog creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByProductId($productId, $limit = 50, $offset = 0) {
        $sql = "SELECT il.*, u.username as admin_username 
                FROM {$this->tableName} il 
                LEFT JOIN users u ON il.user_id = u.id
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
    
    public function countByProductId($productId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->tableName} WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    public function getRecentActivity($limit = 20) {
        $sql = "SELECT il.*, p.name as product_name, u.username as admin_username 
                FROM {$this->tableName} il 
                JOIN products p ON il.product_id = p.id
                LEFT JOIN users u ON il.user_id = u.id
                ORDER BY il.log_date DESC 
                LIMIT :limit";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error fetching recent inventory activity: " . $e->getMessage());
            return false;
        }
    }
}
