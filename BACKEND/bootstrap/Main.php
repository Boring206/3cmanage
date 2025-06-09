<?php
namespace Middlewares;

class AuthMiddleware {
    
    /**
     * 檢查用戶認證狀態
     */
    public static function checkToken() {
        // 檢查是否有 Authorization header
        $headers = getallheaders();
        $token = null;
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
            }
        }
        
        // 如果沒有 token，返回未認證錯誤
        if (!$token) {
            return [
                'success' => false,
                'message' => '未提供認證令牌',
                'code' => 401
            ];
        }
        
        // 驗證 token
        $user = self::validateToken($token);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => '無效的認證令牌',
                'code' => 401
            ];
        }
        
        return [
            'success' => true,
            'message' => '認證成功',
            'data' => $user
        ];
    }
    
    /**
     * 驗證 JWT token
     */
    private static function validateToken($token) {
        try {
            // 簡單的 token 驗證邏輯
            // 這裡你可以使用 JWT 庫來解析和驗證 token
            
            // 暫時使用簡單的驗證方式
            $tokenParts = explode('.', $token);
            
            if (count($tokenParts) !== 3) {
                return false;
            }
            
            // 解碼 payload
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            
            if (!$payload) {
                return false;
            }
            
            // 檢查 token 是否過期
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            // 返回用戶信息
            return [
                'user_id' => $payload['user_id'] ?? null,
                'username' => $payload['username'] ?? null,
                'role' => $payload['role'] ?? 'user'
            ];
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 生成 JWT token
     */
    public static function generateToken($userData) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload = json_encode([
            'user_id' => $userData['user_id'],
            'username' => $userData['username'],
            'role' => $userData['role'] ?? 'user',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24小時後過期
        ]);
        
        $base64Header = base64_encode($header);
        $base64Payload = base64_encode($payload);
        
        // 簡單的簽名（生產環境中應該使用更安全的密鑰）
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, 'your-secret-key', true);
        $base64Signature = base64_encode($signature);
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * 檢查用戶是否為管理員
     */
    public static function isAdmin($token) {
        $user = self::validateToken($token);
        return $user && isset($user['role']) && $user['role'] === 'admin';
    }
    
    /**
     * 中間件處理函數
     */
    public static function handle($requireAuth = true, $requireAdmin = false) {
        $headers = getallheaders();
        $token = null;
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
            }
        }
        
        if ($requireAuth) {
            if (!$token) {
                return [
                    'success' => false,
                    'message' => '需要認證',
                    'code' => 401
                ];
            }
            
            $user = self::validateToken($token);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => '無效的認證令牌',
                    'code' => 401
                ];
            }
            
            if ($requireAdmin && $user['role'] !== 'admin') {
                return [
                    'success' => false,
                    'message' => '需要管理員權限',
                    'code' => 403
                ];
            }
            
            // 將用戶信息存儲到全局變量中，供其他地方使用
            $GLOBALS['current_user'] = $user;
        }
        
        return [
            'success' => true,
            'user' => $user ?? null
        ];
    }
}
?>