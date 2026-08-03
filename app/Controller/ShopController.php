<?php
namespace App\Controller;

use App\Models\Product;

class ShopController {
    public function index() {
        $productModel = new Product();
        $gender = isset($_GET['gender']) ? $_GET['gender'] : 'all';
        $category = $_GET['category'] ?? 'all';
        $sort = $_GET['sort'] ?? 'default';
        $priceRange = $_GET['price'] ?? 'all';
        $keyword = trim($_GET['q'] ?? '');

        $products = $productModel->getProductsByFilter([
            'gender' => $gender,
            'category' => $category,
            'price' => $priceRange,
            'sort' => $sort,
            'keyword' => $keyword
        ]);

        $categories = $productModel->getActiveCategories();
        $metaTitle = 'Cửa hàng giày Nike chính hãng - PaceUp';
        $metaDescription = 'Tìm kiếm và lọc giày Nike theo giới tính, danh mục, giá và sản phẩm phù hợp.';

        require __DIR__ . '/../Views/shop.php';
    }

}
