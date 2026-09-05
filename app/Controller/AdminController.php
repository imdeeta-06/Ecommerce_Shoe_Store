<?php
namespace App\Controller;

class AdminController {
    public function index() {
        (new Admin\DashboardController())->index();
    }
}
