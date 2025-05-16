<?php
// 3Cmanage/app/Controllers/UserController.php
namespace App\Controllers;

use Core\Controller;
use App\Models\Warranty; // 我們會用到 Warranty 模型
// use App\Models\User; // 如果需要用戶基本資料模型

class UserController extends Controller {

    private $warrantyModel;
    // private $userModel;

    public function __construct() {
        $this->warrantyModel = new Warranty();
        // $this->userModel = new User();

        // 確保 session 已啟動，用於身份驗證
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function ensureLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            $this->errorResponse('Unauthorized. Please login to proceed.', 401);
            exit; // 確保控制器停止執行
        }
        return $_SESSION['user_id'];
    }

    /**
     * 列出當前登入使用者的所有設備 (基於保固記錄)
     */
    public function listMyDevices() {
        $currentUserId = $this->ensureLoggedIn();

        $devices = $this->warrantyModel->findByUserId($currentUserId);

        if ($devices === false) {
            return $this->errorResponse('Could not fetch your devices.', 500);
        }
        
        // 如果希望加入分頁，可以像 ProductController 或 OrderController 那樣處理
        // $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        // $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
        // $offset = ($page - 1) * $limit;
        // $devices = $this->warrantyModel->findByUserId($currentUserId, $limit, $offset);


        return $this->jsonResponse($devices);
    }

    /**
     * 獲取特定設備的詳細保固資訊
     * $params 包含從路由傳來的 'warrantyId'
     */
    public function getDeviceWarrantyDetails($params) {
        $currentUserId = $this->ensureLoggedIn();

        if (!isset($params['warrantyId']) || !is_numeric($params['warrantyId'])) {
            return $this->errorResponse('Warranty ID is required and must be numeric.', 400);
        }
        $warrantyId = (int)$params['warrantyId'];

        // 我們需要在 Warranty 模型中新增一個方法來獲取特定保固的詳細資料並驗證用戶
        $warrantyDetails = $this->warrantyModel->findDetailsByIdForUser($warrantyId, $currentUserId);

        if ($warrantyDetails) {
            return $this->jsonResponse($warrantyDetails);
        } elseif ($warrantyDetails === false) {
             return $this->errorResponse('Could not fetch warranty details.', 500);
        } else {
            // null case from model if not found or not authorized
            return $this->errorResponse('Warranty not found or access denied.', 404);
        }
    }
    
    // 之後可以加入用戶個人資料管理等方法
    // public function getMyProfile() { ... }
    // public function updateMyProfile() { ... }
}
?>