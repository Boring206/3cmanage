<?php
// 3Cmanage/app/Models/User.php
namespace App\Models; // <--- 確保命名空間正確

use Core\DB;
use PDO;

class User {
    private $db;
    private $tableName = 'users'; // 定義表名

    public function __construct() {
        $this->db = DB::getInstance()->getConnection();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findById($id) {
        // 避免回傳密碼
        $stmt = $this->db->prepare("SELECT id, username, email, name, role, created_at FROM {$this->tableName} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        // 在模型中進行密碼雜湊
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO {$this->tableName} (username, email, password, name, role) 
                VALUES (:username, :email, :password, :name, :role)";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashedPassword); // 儲存雜湊後的密碼
        $stmt->bindParam(':name', $data['name']);
        
        // 確保角色有預設值或從 $data 傳入
        $role = $data['role'] ?? 'customer'; 
        $stmt->bindParam(':role', $role);

        try {
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (\PDOException $e) {
            // 處理潛在的 email/username 重複錯誤 (MySQL error code 1062)
            if ($e->errorInfo[1] == 1062) { 
                error_log("User creation error: Duplicate entry for email or username. " . $e->getMessage());
                return 'DUPLICATE_ENTRY'; // 返回特定錯誤標識
            }
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }
    
    // 您可以根據需要加入 update, delete 等方法
}
?>