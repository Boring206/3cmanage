<?php
// 3Cmanage/public/index.php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require BASE_PATH . '/vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    error_log("Could not find .env file (this is okay if using default fallbacks or server env vars): " . $e->getMessage());
} catch (\Throwable $e) {
    error_log("Error loading .env file: " . $e->getMessage());
}

if (session_status() == PHP_SESSION_NONE) {
    // 可選: session_set_cookie_params(['lifetime' => 3600, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

// 處理 PUT/PATCH/DELETE 等方法的 _method 覆寫 (通常用於 HTML 表單)
if ($requestMethod === 'POST' && isset($_POST['_method'])) {
    $methodOverride = strtoupper(trim($_POST['_method']));
    if (in_array($methodOverride, ['PUT', 'PATCH', 'DELETE'])) {
        $requestMethod = $methodOverride;
    }
}

// URI 解析 (配合 .htaccess 使用 RewriteRule ^(.*)$ index.php?url=$1 [L,QSA])
$requestUri = '';
if (isset($_GET['url'])) { // 'url' 來自 .htaccess 的 RewriteRule
    $requestUri = trim($_GET['url'], '/');
} else {
    // 如果沒有 'url' 參數，嘗試從 REQUEST_URI 解析 (適用於未使用特定 RewriteRule 或 PHP 內建伺服器)
    // 假設 .htaccess 或伺服器設定已處理好基礎路徑 (例如 Apache 的 DocumentRoot 或 RewriteBase)
    $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // 如果您的應用程式在子目錄下，例如 /3cmanage/public/，並且 DocumentRoot 是 htdocs
    // 您需要移除基礎路徑部分。
    // 例如，如果 RewriteBase 是 /3Cmanage/public/
    // 這裡的 $uriPath 可能是 /3Cmanage/public/products
    // 我們需要得到 'products'
    // 一個更通用的方法是讓 Web 伺服器配置 (VirtualHost DocumentRoot) 正確指向 public 目錄
    // 或者，如果您堅持使用子目錄，則 RewriteBase 和這裡的解析邏輯需要非常小心地匹配。
    // 為了簡化，我們先假設您的 Web 伺服器或 .htaccess 設定能讓這裡的 $uriPath 是應用程式的相對路徑。
    // 如果您使用了我之前提供的 .htaccess (有 RewriteBase /3Cmanage/public/ 和 RewriteRule ^(.*)$ index.php?url=$1)
    // 那麼 $_GET['url'] 的方式是最直接的。

    // 如果您沒有 .htaccess 或 .htaccess 的 RewriteRule 不傳遞 url GET 參數，
    // 則需要根據您的伺服器設定和 RewriteBase 來調整此處的 $requestUri 提取邏輯。
    // 例如，如果沒有 RewriteBase 但 DocumentRoot 正確指向 public:
    // $requestUri = trim($uriPath, '/');
}


// 載入路由定義並分派請求
try {
    $router = Core\Router::load(BASE_PATH . '/routes/web.php');
    $router->direct($requestUri, $requestMethod);
} catch (\Exception $e) {
    $statusCode = method_exists($e, 'getCode') && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => [
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            // 'trace' => explode("\n", $e->getTraceAsString()) // 開發時可以開啟更詳細的追蹤
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    error_log("Routing Exception: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
}
?>