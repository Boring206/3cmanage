<?php
// require_once __DIR__ . '/../vendor/Router.php';
// require_once __DIR__ . '/../vendor/DB.php';

require_once __DIR__ . '/../vendor/Autoload.php';


use Vendor\DB;
use Vendor\Router;
use Middlewares\AuthMiddleware;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");


class Main{
    static function run(){

            $conf =  parse_ini_file(__DIR__ . '/../vendor/.env');
            DB::$dbHost = $conf['dbHost'];
            DB::$dbName = $conf['dbName'];
            DB::$dbUser = $conf['dbUser'];
            DB::$dbPassword = $conf['dbPassword'];
            //
            $router = new Router();
            require_once __DIR__ . "/../routes/web.php";

            if (isset($_GET['action'])) {
                $action = $_GET['action'];
                $response = $router->run($action);
            } else {
                // 處理根路徑請求，檢查認證狀態
                $response = AuthMiddleware::checkToken();
            }
            
            echo json_encode($response);
        }
    }
?>