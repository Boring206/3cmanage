<?php declare(strict_types=1);
// 3Cmanage/app/Models/OrderItem.php
namespace App\Models;

use Core\DB;
use PDO;

class OrderItem {
    private $db;
    private $tableName = 'order_items';

    public function __construct() {
        $this->db = DB::getInstance()->getConnection();
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getByOrderId($orderId) {
        $sql = "SELECT oi.*, p.name as product_name, p.brand, p.model_number, p.image_url 
                FROM {$this->tableName} oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = :order_id";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $sql = "INSERT INTO {$this->tableName} (order_id, product_id, quantity, price_at_purchase) 
                VALUES (:order_id, :product_id, :quantity, :price_at_purchase)";
                
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':order_id', $data['order_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $data['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $data['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':price_at_purchase', $data['price_at_purchase']);
        
        try {
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (\PDOException $e) {
            error_log("OrderItem creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getItemsAndWarrantiesByOrderId($orderId) {
        $sql = "SELECT oi.*, p.name as product_name, p.brand, p.model_number, p.image_url,
                w.id as warranty_id, w.serial_number, w.purchase_date, w.expiry_date, w.status as warranty_status
                FROM {$this->tableName} oi 
                JOIN products p ON oi.product_id = p.id 
                LEFT JOIN warranties w ON oi.id = w.order_item_id
                WHERE oi.order_id = :order_id";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $items = [];
        
        // 重組數據，將保固信息整合到對應的訂單項目
        foreach ($results as $row) {
            $itemId = $row['id'];
            
            if (!isset($items[$itemId])) {
                $items[$itemId] = [
                    'id' => $row['id'],
                    'order_id' => $row['order_id'],
                    'product_id' => $row['product_id'],
                    'quantity' => $row['quantity'],
                    'price_at_purchase' => $row['price_at_purchase'],
                    'product_name' => $row['product_name'],
                    'brand' => $row['brand'],
                    'model_number' => $row['model_number'],
                    'image_url' => $row['image_url'],
                    'warranties' => []
                ];
            }
            
            // 如果有保固記錄，加入到該項目的保固陣列
            if ($row['warranty_id']) {
                $items[$itemId]['warranties'][] = [
                    'id' => $row['warranty_id'],
                    'serial_number' => $row['serial_number'],
                    'purchase_date' => $row['purchase_date'],
                    'expiry_date' => $row['expiry_date'],
                    'status' => $row['warranty_status']
                ];
            }
        }
        
        // 將關聯陣列轉為索引陣列
        return array_values($items);
    }
}
