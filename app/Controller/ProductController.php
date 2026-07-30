<?php
namespace App\Controller;

use App\Models\Product;

class ProductController {
    public function show() {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        
        $productModel = new Product();
        $product = $productModel->getProductWithImages($id);
        
        if (!$product) {
            http_response_code(404);
            echo '<div style="max-width:600px;margin:5rem auto;text-align:center;font-family:sans-serif;"><h1>Không tìm thấy sản phẩm</h1><p><a href="' . BASE_URL . 'shop">Quay lại cửa hàng</a></p></div>';
            require __DIR__ . '/../Views/partials/footer.php';
            exit;
        }

        $related = $productModel->getRelatedProducts($product['id'], $product['category_id'], $product['gender'], 4);

        require __DIR__ . '/../Views/product.php';
    }

}