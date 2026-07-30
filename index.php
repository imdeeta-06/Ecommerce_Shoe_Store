<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define Base URL to handle subdirectories in XAMPP
$baseDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
define('BASE_URL', rtrim($baseDir, '/') . '/');

// Simple Autoloader
spl_autoload_register(function ($class) {
    $class = str_replace('App\\', 'app/', $class);
    $class = str_replace('\\', '/', $class);
    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Router;

$router = new Router();

// Define Routes
$router->add('/', 'HomeController', 'index');
$router->add('/shop', 'ShopController', 'index');
$router->add('/product', 'ProductController', 'show');
$router->add('/cart', 'CartController', 'index');
$router->add('/cart/add', 'CartController', 'add');
$router->add('/cart/remove', 'CartController', 'remove');
$router->add('/cart/update', 'CartController', 'update');
$router->add('/cart/get', 'CartController', 'get');
$router->add('/wishlist', 'WishlistController', 'index');
$router->add('/wishlist/add', 'WishlistController', 'add');
$router->add('/wishlist/remove', 'WishlistController', 'remove');
$router->add('/checkout', 'CheckoutController', 'index');
$router->add('/checkout/place-order', 'CheckoutController', 'placeOrder');
$router->add('/checkout-success', 'CheckoutController', 'success');
$router->add('/login', 'AuthController', 'login');
$router->add('/register', 'AuthController', 'register');
$router->add('/logout', 'AuthController', 'logout');
$router->add('/change-password', 'AuthController', 'changePassword');
$router->add('/forgot-password', 'AuthController', 'forgotPassword');
$router->add('/verify-otp', 'AuthController', 'verifyOtp');
$router->add('/reset-password', 'AuthController', 'resetPassword');
$router->add('/account', 'User/ProfileController', 'index');
$router->add('/account/update', 'User/ProfileController', 'update');
$router->add('/account/avatar', 'User/ProfileController', 'uploadAvatar');
$router->add('/account/addresses/add', 'User/ProfileController', 'addAddress');
$router->add('/account/addresses/default', 'User/ProfileController', 'setDefaultAddress');
$router->add('/account/addresses/delete', 'User/ProfileController', 'deleteAddress');
$router->add('/admin', 'AdminController', 'index');
$router->add('/admin/users/create', 'Admin\UserController', 'create');
$router->add('/admin/products', 'Admin\ProductController', 'index');
$router->add('/admin/products/create', 'Admin\ProductController', 'create');
$router->add('/admin/products/edit', 'Admin\ProductController', 'edit');
$router->add('/admin/products/delete', 'Admin\ProductController', 'delete');
$router->add('/admin/products/destroy', 'Admin\ProductController', 'destroy');
$router->add('/admin/products/variants/add', 'Admin\ProductController', 'addVariant');
$router->add('/admin/products/variants/update', 'Admin\ProductController', 'updateVariant');
$router->add('/admin/products/variants/delete', 'Admin\ProductController', 'deleteVariant');
$router->add('/admin/products/images/primary', 'Admin\ProductController', 'setPrimaryImage');
$router->add('/admin/products/images/delete', 'Admin\ProductController', 'deleteImage');
$router->add('/admin/categories', 'Admin\CategoryController', 'index');
$router->add('/admin/categories/create', 'Admin\CategoryController', 'create');
$router->add('/admin/categories/delete', 'Admin\CategoryController', 'delete');
$router->add('/admin/inventory', 'Admin\InventoryController', 'index');
$router->add('/admin/inventory/update', 'Admin\InventoryController', 'update');
$router->add('/admin/inventory/variants/create', 'Admin\InventoryController', 'createVariant');
$router->add('/admin/coupons', 'Admin\CouponController', 'index');
$router->add('/admin/coupons/create', 'Admin\CouponController', 'create');
$router->add('/admin/coupons/store', 'Admin\CouponController', 'store');
$router->add('/admin/coupons/edit', 'Admin\CouponController', 'edit');
$router->add('/admin/coupons/update', 'Admin\CouponController', 'update');
$router->add('/admin/coupons/delete', 'Admin\CouponController', 'delete');
$router->add('/account', 'AccountController', 'index');
$router->add('/apply-coupon', 'CheckoutController', 'applyCoupon');

// Parse URL
$url = $_SERVER['REQUEST_URI'];
$basePath = parse_url(BASE_URL, PHP_URL_PATH);
if (strpos($url, $basePath) === 0) {
    $url = substr($url, strlen($basePath));
}
$url = '/' . ltrim($url, '/');

// Redirect logged in users away from auth pages
if (isset($_SESSION['user_id']) && ($url === '/login' || $url === '/register')) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ' . BASE_URL . 'admin');
    } else {
        header('Location: ' . BASE_URL);
    }
    exit;
}

$router->dispatch($url);
