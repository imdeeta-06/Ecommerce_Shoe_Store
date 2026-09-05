<?php
namespace App\Controller;

use App\Models\Product;
use App\Models\Order;

class HomeController {
    public function index() {
        try { (new Order())->expirePendingOrders(50); } catch (\Throwable $ignored) {}
        $productModel = new Product();
        $banners = $this->getBanners();
        $featuredProducts = $productModel->getFeaturedProducts(3);
        $featuredIds = array_column($featuredProducts, 'id');
        $bestSellingProducts = [];
        foreach ($productModel->getBestSellingProducts(20) as $product) {
            if (!in_array($product['id'], $featuredIds)) {
                $bestSellingProducts[] = $product;
            }
            if (count($bestSellingProducts) >= 6) {
                break;
            }
        }
        $discountedProducts = $productModel->getDiscountedProducts(4);

        // Real stats for hero section
        $heroStats = $this->getHeroStats();

        $metaTitle = 'Liên Hoa - Pháp Phục & Đồ Lam Phật Giáo Cao Cấp';
        $metaDescription = 'Chuyên cung cấp áo lam đi chùa, pháp phục Tăng Ni, tràng hạt trầm hương và vật phẩm Phật giáo cao cấp tại Liên Hoa.';
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

    private function getHeroStats(): array {
        try {
            $db = \App\Models\Database::getInstance()->getConnection();

            // Total active products
            $productCount = (int)$db->query("SELECT COUNT(*) FROM product WHERE status = 1")->fetchColumn();

            // Total completed orders
            $orderCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status IN ('delivered','completed')")->fetchColumn();

            // Total categories
            $categoryCount = (int)$db->query("SELECT COUNT(*) FROM categories WHERE status = 1")->fetchColumn();

            return [
                'product_count' => $productCount,
                'order_count'   => $orderCount,
                'category_count' => $categoryCount,
            ];
        } catch (\Throwable $e) {
            return [
                'product_count' => 0,
                'order_count'   => 0,
                'category_count' => 0,
            ];
        }
    }

}
