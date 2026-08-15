<?php
namespace App\Controller;

use App\Models\Product;

class ShopController {
    public function index() {
        $productModel = new Product();
        $category = $_GET['category'] ?? 'all';
        $sort = $_GET['sort'] ?? 'default';
        $priceRange = $_GET['price'] ?? 'all';
        $keyword = trim($_GET['q'] ?? '');

        $products = $productModel->getProductsByFilter([
            'category' => $category,
            'price' => $priceRange,
            'sort' => $sort,
            'keyword' => $keyword
        ]);

        $categories = $productModel->getActiveCategories();
        $metaTitle = 'Cửa hàng đồ lam và pháp phục - PaceUp';
        $metaDescription = 'Tìm kiếm và lọc đồ lam, pháp phục, túi đi chùa, chuỗi hạt theo danh mục và mức giá.';

        require __DIR__ . '/../Views/shop.php';
    }

}
