<?php

namespace App\Controller\User;

use App\Core\App;

class HomeController {
    public function index() {
        // Return view for frontend
        // Assuming there is a core View class, but for simplicity we will include directly if not
        $viewPath = __DIR__ . '/../../../public/views/home.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "Home view not found.";
        }
    }
}