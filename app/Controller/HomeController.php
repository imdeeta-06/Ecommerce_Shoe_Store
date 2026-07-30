<?php
namespace App\Controller;

use App\Models\Product;

class HomeController {
    public function index() {
        $productModel = new Product();
        $featuredProducts = array_slice($productModel->getProductsByFilter([]), 0, 4);
        require __DIR__ . '/../Views/index.php';
    }

}