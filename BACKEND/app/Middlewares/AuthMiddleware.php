<?php
//             }
require_once __DIR__ . '/../vendor/Autoload.php';

use Vendor\DB;
use Middlewares\AuthMiddleware;

class AuthController {
    
    /**
     * 用戶登入
     */
    public static function login() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['username']) || !isset($input['password'])) {
                return [
                    'success' => false,
                    'message' => '請提供用戶名和密碼'
                ];
            }
            
            $username = $input['username'];
            $password = $input['password'];
            
            // 查詢用戶
            $sql = "SELECT * FROM users WHERE username = ? AND password = MD5(?)";
            $user = DB::query($sql, [$username, $password]);
            
            if (empty($user)) {
                return [
                    'success' => false,
                    'message' => '用戶名或密碼錯誤'
                ];
            }
            
            $userData = $user[0];
            
            // 生成 token
            $token = AuthMiddleware::generateToken([
                'user_id' => $userData['user_id'],
                'username' => $userData['username'],
                'role' => $userData['role'] ?? 'user'
            ]);
            
            return [
                'success' => true,
                'message' => '登入成功',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'user_id' => $userData['user_id'],
                        'username' => $userData['username'],
                        'role' => $userData['role']
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '登入失敗：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 用戶登出
     */
    public static function logout() {
        return [
            'success' => true,
            'message' => '登出成功'
        ];
    }
    
    /**
     * 驗證 token
     */
    public static function verify() {
        return AuthMiddleware::checkToken();
    }
}
?>