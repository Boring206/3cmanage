<?php
// 3Cmanage/core/DB.php
namespace Core; // <--- 非常重要：宣告命名空間

use PDO;
use PDOException;

class DB {
    private static $instance = null;
    private $conn;

    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;

    private function __construct() {
        // 從 .env 檔案讀取設定，如果 .env 沒設定，則使用預設值
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_DATABASE'] ?? '3cmanage';
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? ''; // 預設空密碼
        $this->port = $_ENV['DB_PORT'] ?? '3306';

        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // 預設取出為關聯陣列
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // 使用真正的預備語句
        } catch (PDOException $e) {
            // 在實際應用中，應該記錄錯誤並顯示一個通用的錯誤訊息
            error_log("Database Connection Failed: " . $e->getMessage()); // 記錄錯誤到伺服器日誌
            // 為了開發方便，我們先直接顯示錯誤，但生產環境不應如此
            die("資料庫連接失敗，請檢查 .env 設定或聯繫管理員。錯誤訊息: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new DB();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    // 查詢輔助方法 (可以擴展)
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
}
?>