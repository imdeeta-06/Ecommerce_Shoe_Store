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
        $bestSellingProducts = [];
        foreach ($allBestSelling as $product) {
            if (!in_array($product['id'], $featuredIds)) {
                $bestSellingProducts[] = $product;
            }
            if (count($bestSellingProducts) >= 6) {
                break;
            }
        }
        $metaTitle = 'PaceUp - Giày Nike chính hãng';
        $metaDescription = 'Mua giày Nike chính hãng online tại PaceUp: Running, Lifestyle, Football, Training và nhiều dòng sản phẩm thể thao.';
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
