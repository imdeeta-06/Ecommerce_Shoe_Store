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
        $perPage = 10;
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;

        $filters = [
            'category' => $category,
            'price' => $priceRange,
            'sort' => $sort,
            'keyword' => $keyword
        ];
        $totalFilteredProducts = $productModel->getProductsCountByFilter($filters);
        $totalPages = max(1, (int)ceil($totalFilteredProducts / $perPage));
        $page = min($page, $totalPages);

        $products = $productModel->getProductsByFilter($filters, $perPage, ($page - 1) * $perPage);

        $categories = $productModel->getActiveCategories();
        $metaTitle = 'Cửa hàng đồ lam và pháp phục - PaceUp';
        $metaDescription = 'Tìm kiếm và lọc đồ lam, pháp phục, túi đi chùa, chuỗi hạt theo danh mục và mức giá.';

        require __DIR__ . '/../Views/shop.php';
    }

}
