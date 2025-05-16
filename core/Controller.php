<?php
// 3Cmanage/core/Controller.php
namespace Core; // <--- 非常重要：宣告命名空間

class Controller {
    /**
     * Helper method to return JSON response.
     * @param mixed $data The data to encode as JSON.
     * @param int $statusCode The HTTP status code.
     */
    protected function jsonResponse($data, $statusCode = 200) {
        // 確保之前的輸出被清除 (如果有的話)
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8'); // 指定 UTF-8
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); // JSON_UNESCAPED_UNICODE 讓中文正確顯示, JSON_PRETTY_PRINT 方便閱讀
        exit; // 確保腳本停止執行
    }

    /**
     * Helper method to return JSON error response.
     * @param string $message The error message.
     * @param int $statusCode The HTTP status code.
     * @param array|null $errors Additional error details.
     */
    protected function errorResponse($message, $statusCode = 400, $errors = null) {
        $responseData = ['error' => ['message' => $message]];
        if ($errors !== null) {
            $responseData['error']['details'] = $errors;
        }
        $this->jsonResponse($responseData, $statusCode);
    }
}
?>