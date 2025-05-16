<?php
// 3Cmanage/public/index.php
declare(strict_types=1); // 建議開啟嚴格模式

// 顯示所有錯誤 (開發階段)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// 定義應用程式根目錄路徑 (可選，但方便)
define('BASE_PATH', dirname(__DIR__)); // 3Cmanage/

// 載入 Composer 的 autoload.php
require BASE_PATH . '/vendor/autoload.php';

// 載入 .env 環境變數
try {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // 如果 .env 檔案不存在，可以選擇不執行任何操作或記錄錯誤
    // die("Could not find .env file: " . $e->getMessage()); // 開發時可以這樣提示
    error_log("Could not find .env file: " . $e->getMessage());
}


// --- 路由處理 (簡易版，之後會用 Core\Router) ---
// 為了先解決 Core\DB 的問題，我們暫時不完整實作路由
// 但為了讓 UserController 的方法能被呼叫到，我們做個簡單的測試

$requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$requestMethod = $_SERVER['REQUEST_METHOD'];

// 測試路由 (假設您想測試 listMyDevices)
if ($requestUri === 'my-devices' && $requestMethod === 'GET') {
    // 模擬登入 (之後會用真正的 session)
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    // 假設 user_id 1 已登入 (僅供測試)
    // 在實際應用中，這應該在登入流程中設定
    if (!isset($_SESSION['user_id'])) {
         $_SESSION['user_id'] = 1; 
         $_SESSION['user_role'] = 'customer';
         error_log("Simulating login for user_id: 1 for testing /my-devices");
    }


    $controller = new App\Controllers\UserController();
    $controller->listMyDevices();
    exit;
}

echo "3Cmanage Backend. No specific route matched for: /{$requestUri}";

// 之後我們會用類似這樣的程式碼來載入路由並分派：
/*
try {
    Core\Router::load(BASE_PATH . '/routes/web.php')
        ->direct($requestUri, $requestMethod);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage()]);
}
*/
?>