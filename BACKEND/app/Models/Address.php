<?php declare(strict_types=1);
// 3Cmanage/app/Models/Address.php
namespace App\Models;

use Core\DB;
use PDO;

class Address {
    private $db;
    private $tableName = 'addresses';

    public function __construct() {
        $this->db = DB::getInstance()->getConnection();
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        // 如果這是用戶的第一個地址，設為預設
        $isFirstAddress = $this->isFirstAddress($data['user_id']);
        $isDefault = $isFirstAddress ? 1 : ($data['is_default'] ?? 0);
        
        // 如果設為預設，需要先將該用戶其他地址設為非預設
        if ($isDefault) {
            $this->resetDefaultAddresses($data['user_id']);
        }
        
        $sql = "INSERT INTO {$this->tableName} 
                (user_id, recipient_name, phone_number, postal_code, city, street, country, is_default) 
                VALUES 
                (:user_id, :recipient_name, :phone_number, :postal_code, :city, :street, :country, :is_default)";
                
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':recipient_name', $data['recipient_name']);
        $stmt->bindParam(':phone_number', $data['phone_number']);
        $stmt->bindParam(':postal_code', $data['postal_code']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':street', $data['street']);
        $stmt->bindParam(':country', $data['country']);
        $stmt->bindParam(':is_default', $isDefault, PDO::PARAM_INT);
        
        try {
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Address creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data) {
        // 如果將地址設為預設，需要先將該用戶其他地址設為非預設
        if (isset($data['is_default']) && $data['is_default'] == 1) {
            $address = $this->findById($id);
            if ($address) {
                $this->resetDefaultAddresses($address['user_id']);
            }
        }
        
        $sql = "UPDATE {$this->tableName} SET 
                recipient_name = :recipient_name, 
                phone_number = :phone_number, 
                postal_code = :postal_code, 
                city = :city, 
                street = :street, 
                country = :country";
        
        if (isset($data['is_default'])) {
            $sql .= ", is_default = :is_default";
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':recipient_name', $data['recipient_name']);
        $stmt->bindParam(':phone_number', $data['phone_number']);
        $stmt->bindParam(':postal_code', $data['postal_code']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':street', $data['street']);
        $stmt->bindParam(':country', $data['country']);
        
        if (isset($data['is_default'])) {
            $isDefault = (int)$data['is_default'];
            $stmt->bindParam(':is_default', $isDefault, PDO::PARAM_INT);
        }
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Address update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        // 先檢查這是否是預設地址，如果是，刪除後需要將其他地址設為預設
        $address = $this->findById($id);
        if (!$address) {
            return false;
        }
        
        $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        try {
            $result = $stmt->execute();
            // 如果刪除的是預設地址，嘗試設置其他地址為預設
            if ($result && $address['is_default'] == 1) {
                $this->setNewDefaultIfNeeded($address['user_id']);
            }
            return $result;
        } catch (\PDOException $e) {
            error_log("Address deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    public function setDefault($id, $userId) {
        // 先重置該用戶的所有地址為非預設
        $this->resetDefaultAddresses($userId);
        
        // 再設置指定地址為預設
        $stmt = $this->db->prepare("UPDATE {$this->tableName} SET is_default = 1 WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Setting default address error: " . $e->getMessage());
            return false;
        }
    }
    
    private function resetDefaultAddresses($userId) {
        $stmt = $this->db->prepare("UPDATE {$this->tableName} SET is_default = 0 WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Resetting default addresses error: " . $e->getMessage());
            return false;
        }
    }
    
    private function isFirstAddress($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->tableName} WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] == 0;
    }
    
    private function setNewDefaultIfNeeded($userId) {
        // 找出用戶的其他地址中最新的一筆，將其設置為預設
        $stmt = $this->db->prepare("UPDATE {$this->tableName} SET is_default = 1 
                                    WHERE user_id = :user_id 
                                    ORDER BY created_at DESC 
                                    LIMIT 1");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Setting new default address error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDefaultByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE user_id = :user_id AND is_default = 1");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
