<?php
// 3Cmanage/app/Controllers/AuthController.php
namespace App\Controllers;

use Core\Controller;
use App\Models\User; // 假設您在 app/Models/User.php 中定義了 User 模型

class AuthController extends Controller {

    private $userModel;

    public function __construct() {
        // parent::__construct(); // 如果 Core\Controller 有建構子
        $this->userModel = new User(); // 實例化 User 模型

        // 確保 session 已啟動
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * 處理使用者註冊
     * POST /register
     * Input JSON: {"username": "...", "email": "...", "password": "...", "name": "..."}
     */
    public function register($params = []) { // Router 會傳遞 $params，但這裡我們從 input 讀取
        $input = (array) json_decode(file_get_contents('php://input'), true);

        // --- 基本驗證 ---
        if (empty($input['username']) || empty($input['email']) || empty($input['password'])) {
            return $this->errorResponse('Username, email, and password are required.', 400);
        }
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->errorResponse('Invalid email format.', 400);
        }
        if (strlen($input['password']) < 6) { // 密碼長度範例
            return $this->errorResponse('Password must be at least 6 characters long.', 400);
        }

        // 檢查 email 或 username 是否已存在
        if ($this->userModel->findByEmail($input['email'])) {
            return $this->errorResponse('Email already exists.', 409); // 409 Conflict
        }
        if ($this->userModel->findByUsername($input['username'])) {
            return $this->errorResponse('Username already exists.', 409);
        }

        $userData = [
            'username' => trim($input['username']),
            'email' => trim($input['email']),
            'password' => $input['password'], // 密碼雜湊會在 User 模型中處理
            'name' => $input['name'] ?? null,
            'role' => 'customer' // 新註冊用戶預設為 customer
        ];

        $userId = $this->userModel->create($userData);

        if ($userId === 'DUPLICATE_ENTRY') {
             return $this->errorResponse('Email or username already registered.', 409);
        } elseif ($userId) {
            // 註冊成功，可以選擇直接登入或僅返回成功訊息
            // 為了簡單，我們先返回成功訊息
            $newUser = $this->userModel->findById($userId); // 獲取剛建立的使用者資料 (不含密碼)
            return $this->jsonResponse(['message' => 'User registered successfully.', 'user' => $newUser], 201);
        } else {
            return $this->errorResponse('Registration failed. Please try again.', 500);
        }
    }

    /**
     * 處理使用者登入
     * POST /login
     * Input JSON: {"email": "...", "password": "..."}
     */
    public function login($params = []) { // Router 會傳遞 $params，但這裡我們從 input 讀取
        $input = (array) json_decode(file_get_contents('php://input'), true);

        if (empty($input['email']) || empty($input['password'])) {
            return $this->errorResponse('Email and password are required.', 400);
        }

        $user = $this->userModel->findByEmail($input['email']);

        if ($user && password_verify($input['password'], $user['password'])) {
            // 密碼正確，設定 session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username']; // 可以儲存更多需要的資訊

            // 從回傳資料中移除密碼
            unset($user['password']); 

            return $this->jsonResponse([
                'message' => 'Login successful.', 
                'user' => $user,
                // 'token' => 'YOUR_JWT_TOKEN_IF_USING_JWT' // 如果未來使用 JWT
            ]);
        } else {
            return $this->errorResponse('Invalid email or password.', 401); // 401 Unauthorized
        }
    }

    /**
     * 處理使用者登出
     * POST /logout
     */
    public function logout($params = []) {
        // 清除所有 session 變數
        $_SESSION = array();

        // 如果使用 cookie 來傳遞 session ID，則刪除 session cookie
        if (ini_get("session.use_cookies")) {
            $cookieParams = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $cookieParams["path"], $cookieParams["domain"],
                $cookieParams["secure"], $cookieParams["httponly"]
            );
        }

        // 最後銷毀 session
        session_destroy();

        return $this->jsonResponse(['message' => 'Logged out successfully.']);
    }
}
?>