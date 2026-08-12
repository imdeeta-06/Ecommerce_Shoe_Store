<?php
namespace App\Controller;

use App\Models\Product;

class HomeController {
    public function index() {
        $productModel = new Product();
        $banners = $this->getBanners();
        $featuredProducts = $productModel->getFeaturedProducts(6);
        
        $featuredIds = array_column($featuredProducts, 'id');
        $allBestSelling = $productModel->getBestSellingProducts(20);
        $bestSellingIsFallback = empty($allBestSelling);
        if ($bestSellingIsFallback) {
            // Database mẫu chưa có đơn giao thành công, nên không giả tạo sold_count.
            // Hiển thị sản phẩm còn hàng để trang chủ không bị trống trong lúc demo.
            $allBestSelling = $productModel->getProductsByFilter(['sort' => 'default']);
        }
        $bestSellingProducts = [];
        foreach ($allBestSelling as $product) {
            if (!in_array($product['id'], $featuredIds)) {
                $bestSellingProducts[] = $product;
            }
            if (count($bestSellingProducts) >= 6) {
                break;
            }
        }
        $bestSellingTitle = $bestSellingIsFallback ? 'Gợi ý sản phẩm' : 'Sản phẩm bán chạy';
        $metaTitle = 'PaceUp - Đồ lam, pháp phục và vật dụng đi chùa';
        $metaDescription = 'Mua đồ lam, pháp phục, túi đi chùa, chuỗi hạt và phụ kiện Phật giáo tại PaceUp.';
        $canonicalUrl = \App\Core\App::url('/');
        require __DIR__ . '/../Views/index.php';
    }

    private function getBanners(): array {
        try {
            $db = \App\Models\Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT * FROM banner WHERE status = 1 ORDER BY id DESC");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

}
