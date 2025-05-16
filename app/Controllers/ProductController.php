<?php
// 3Cmanage/app/Controllers/ProductController.php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Product;

class ProductController extends Controller {
    private $productModel;

    public function __construct() {
        // 實例化 Product 模型
        $this->productModel = new Product();
    }

    /**
     * 查看所有產品與規格
     * GET /products
     */
    public function index($params = []) {
        // 支援分頁與篩選
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10; // 預設每頁10筆
        $offset = ($page - 1) * $limit;
        
        // 準備過濾條件
        $filters = [
            'category' => $_GET['category'] ?? null,
            'brand' => $_GET['brand'] ?? null,
            'search' => $_GET['search'] ?? null,
            'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : null,
            'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : null,
            'order_by' => $_GET['order_by'] ?? 'created_at',
            'order_direction' => $_GET['order_direction'] ?? 'DESC'
        ];
        
        // 取得符合篩選條件的產品清單
        $products = $this->productModel->getAll($limit, $offset, $filters);
        $totalCount = $this->productModel->count($filters);
        
        // 計算分頁資訊
        $totalPages = ceil($totalCount / $limit);
        
        // 獲取可用的產品分類和品牌，以便前端過濾選項
        $categories = $this->productModel->getCategories();
        $brands = $this->productModel->getBrands();
        
        // 回傳JSON格式的產品資料與分頁資訊
        return $this->jsonResponse([
            'data' => $products,
            'meta' => [
                'current_page' => $page,
                'last_page' => $totalPages,
                'per_page' => $limit,
                'total' => $totalCount,
                'filters' => [
                    'categories' => $categories,
                    'brands' => $brands
                ]
            ]
        ]);
    }

    /**
     * 查看單一產品規格
     * GET /products/{id}
     */
    public function show($params = []) {
        // 從路由參數取得產品ID
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            return $this->errorResponse('Invalid product ID', 400);
        }
        
        $productId = (int)$params['id'];
        $product = $this->productModel->findById($productId);
        
        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }
        
        // 取得產品詳細資訊，包括規格等
        return $this->jsonResponse(['data' => $product]);
    }
}
?>
