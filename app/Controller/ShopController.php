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

        $categories = $productModel->getCategoriesWithCounts();
        $totalActiveProducts = $productModel->getActiveProductsCount();
        $metaTitle = 'Cửa hàng Pháp phục & Đồ lam Phật giáo - Liên Hoa';
        $metaDescription = 'Mua sắm áo lam, tràng hạt, tượng thờ và các vật phẩm Phật giáo chọn lọc tại Liên Hoa.';

        require __DIR__ . '/../Views/shop.php';
    }

}
