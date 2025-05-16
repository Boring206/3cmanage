<?php
// 3Cmanage/core/Router.php
namespace Core; // <--- 非常重要：宣告命名空間

class Router {
    protected $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => []
    ];

    public static function load($file) {
        $router = new static; // 建立 Router 實例
        // $file 通常是 routes/web.php，該檔案中會有 $router->get(...) 等定義
        require $file;
        return $router;
    }

    // Helper to add route for a specific method
    private function addRoute($method, $uri, $controllerAction) {
        $this->routes[strtoupper($method)][$this->normalizeUri($uri)] = $controllerAction;
    }

    public function get($uri, $controllerAction) {
        $this->addRoute('GET', $uri, $controllerAction);
    }

    public function post($uri, $controllerAction) {
        $this->addRoute('POST', $uri, $controllerAction);
    }

    public function put($uri, $controllerAction) {
        $this->addRoute('PUT', $uri, $controllerAction);
    }

    public function delete($uri, $controllerAction) {
        $this->addRoute('DELETE', $uri, $controllerAction);
    }
    
    public function patch($uri, $controllerAction) {
        $this->addRoute('PATCH', $uri, $controllerAction);
    }


    private function normalizeUri($uri) {
        return trim($uri, '/');
    }

    public function direct($uri, $requestMethod) {
        $uri = $this->normalizeUri($uri);
        $requestMethod = strtoupper($requestMethod);

        if (!isset($this->routes[$requestMethod])) {
            throw new \Exception("Request method {$requestMethod} not supported by router.", 405); // Method Not Allowed
        }

        // 1. 直接匹配路由 (例如 /products)
        if (array_key_exists($uri, $this->routes[$requestMethod])) {
            return $this->callAction(
                ...explode('@', $this->routes[$requestMethod][$uri])
            );
        }

        // 2. 嘗試匹配帶參數的路由 (例如 /products/{id} 或 /admin/orders/{id}/status)
        foreach ($this->routes[$requestMethod] as $route => $action) {
            // 將路由轉換為正則表達式，例如 products/{id} -> #^products/([^/]+)$#
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $uri, $matches)) {
                // 提取路由中定義的參數名，例如 {id} -> id
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $route, $paramNames);
                
                $params = [];
                // $matches[0] 是完整匹配的字串，參數從 $matches[1] 開始
                if (!empty($paramNames[1])) { // $paramNames[1] 包含了所有參數名
                    foreach ($paramNames[1] as $index => $name) {
                        if (isset($matches[$index + 1])) {
                            $params[$name] = $matches[$index + 1];
                        }
                    }
                }
                list($controllerName, $methodName) = explode('@', $action);
                return $this->callAction($controllerName, $methodName, $params);
                
            }
        }
        
        // 如果都沒有匹配到
        throw new \Exception("No route defined for URI '{$uri}' and method {$requestMethod}.", 404);
    }

    protected function callAction($controller, $action, $params = []) {
        $controllerClass = "App\\Controllers\\{$controller}";
        
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller class {$controllerClass} not found.", 500);
        }

        $controllerInstance = new $controllerClass();

        if (!method_exists($controllerInstance, $action)) {
            throw new \Exception("Action {$action} not found in controller {$controllerClass}.", 500);
        }
        
        // 將參數陣列傳遞給控制器方法
        // 如果控制器方法期望的是獨立參數而不是一個陣列，這裡需要更複雜的參數綁定 (例如使用 Reflection API)
        // 目前我們的控制器方法設計為接收一個 $params 陣列
        return $controllerInstance->$action($params);
    }
}
?>