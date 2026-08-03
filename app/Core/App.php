<?php

namespace {
    if (!function_exists('str_starts_with')) {
        function str_starts_with($haystack, $needle) {
            $haystack = (string) $haystack;
            $needle = (string) $needle;

            return $needle === '' || strpos($haystack, $needle) === 0;
        }
    }
}

namespace App\Core {
    class App {
        private static $autoloadRegistered = false;

        public static function run() {
            try {
                self::bootstrap();

                $path = self::currentPath();
                if (isset($_SESSION['user_id']) && in_array($path, ['/login', '/register'], true)) {
                    self::redirect(($_SESSION['user_role'] ?? null) === 'admin' ? '/admin' : '/');
                }

                self::router()->dispatch($path);
            } catch (\Throwable $exception) {
                self::renderException($exception);
            }
        }

        public static function bootstrap() {
            self::defineBaseUrl();
            self::startSession();
            self::registerAutoloader();
        }

        public static function router() {
            self::registerAutoloader();

            $router = new Router();
            self::registerRoutes($router);

            return $router;
        }

        public static function rootPath() {
            return dirname(__DIR__, 2);
        }

        public static function url($path = '') {
            $path = (string) $path;

            if (preg_match('#^https?://#i', $path)) {
                return $path;
            }

            $baseUrl = defined('BASE_URL') ? BASE_URL : '/';

            if ($path === '' || $path === '/') {
                return $baseUrl;
            }

            return $baseUrl . ltrim($path, '/');
        }

        public static function currentPath() {
            $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $requestPath = self::normalizeSlashes($requestPath ?: '/');

            $basePath = defined('BASE_URL') ? parse_url(BASE_URL, PHP_URL_PATH) : '';
            $basePath = self::cleanBasePath($basePath ?: '');

            if ($basePath !== '' && self::pathStartsWith($requestPath, $basePath)) {
                $requestPath = substr($requestPath, strlen($basePath));
            }

            if (strpos($requestPath, '/index.php') === 0) {
                $requestPath = substr($requestPath, strlen('/index.php'));
            }

            return self::normalizeRoutePath($requestPath);
        }

        private static function defineBaseUrl() {
            if (defined('BASE_URL')) {
                return;
            }

            $configured = getenv('APP_BASE_URL');
            if ($configured !== false && trim($configured) !== '') {
                define('BASE_URL', self::normalizeBaseUrl($configured));
                return;
            }

            $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $requestPath = self::normalizeSlashes($requestPath ?: '/');
            $candidates = [
                self::scriptDirectory($_SERVER['SCRIPT_NAME'] ?? ''),
                self::scriptDirectory($_SERVER['PHP_SELF'] ?? ''),
                '/' . basename(self::rootPath())
            ];

            $basePath = '';
            foreach ($candidates as $candidate) {
                $candidate = self::cleanBasePath($candidate);
                if ($candidate !== '' && self::pathStartsWith($requestPath, $candidate) && strlen($candidate) > strlen($basePath)) {
                    $basePath = $candidate;
                }
            }

            define('BASE_URL', $basePath === '' ? '/' : $basePath . '/');
        }

        private static function startSession() {
            if (session_status() !== PHP_SESSION_NONE) {
                return;
            }

            if (!headers_sent()) {
                $params = session_get_cookie_params();
                $path = parse_url(defined('BASE_URL') ? BASE_URL : '/', PHP_URL_PATH) ?: '/';

                session_set_cookie_params([
                    'lifetime' => 0,
                    'path' => $path,
                    'domain' => $params['domain'] ?? '',
                    'secure' => self::isHttps(),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }

            session_start();
        }

        private static function registerAutoloader() {
            if (self::$autoloadRegistered) {
                return;
            }

            spl_autoload_register(function ($class) {
                $prefix = 'App\\';
                if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                    return;
                }

                $relativeClass = substr($class, strlen($prefix));
                $file = self::rootPath() . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

                if (is_file($file)) {
                    require_once $file;
                }
            });

            self::$autoloadRegistered = true;
        }

        private static function registerRoutes(Router $router) {
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
            $router->add('/apply-coupon', 'CheckoutController', 'applyCoupon');
            $router->add('/support', 'SupportController', 'index');
            $router->add('/support/store', 'SupportController', 'store');
            $router->add('/review/store', 'ReviewController', 'store');
            $router->add('/after-sale/request', 'AfterSaleController', 'store');
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
            $router->add('/account/orders/cancel', 'User/ProfileController', 'cancelOrder');

            // Static pages
            $router->add('/about', 'PageController', 'about');
            $router->add('/careers', 'PageController', 'careers');
            $router->add('/franchise', 'PageController', 'franchise');
            $router->add('/faqs', 'PageController', 'faqs');
            $router->add('/privacy', 'PageController', 'privacy');
            $router->add('/terms', 'PageController', 'terms');
            $router->add('/tracking', 'PageController', 'tracking');
            $router->add('/cart-reminder/unsubscribe', 'PageController', 'unsubscribeCartReminder');

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
            $router->add('/admin/orders', 'Admin\OrderController', 'index');
            $router->add('/admin/orders/view', 'Admin\OrderController', 'view');
            $router->add('/admin/orders/status', 'Admin\OrderController', 'updateStatus');
            $router->add('/admin/orders/shipping', 'Admin\OrderController', 'updateShipping');
            $router->add('/admin/after-sales', 'Admin\AfterSaleController', 'index');
            $router->add('/admin/after-sales/update', 'Admin\AfterSaleController', 'update');
            $router->add('/admin/marketing', 'Admin\MarketingController', 'index');
            $router->add('/admin/marketing/banner/store', 'Admin\MarketingController', 'storeBanner');
            $router->add('/admin/marketing/banner/status', 'Admin\MarketingController', 'updateBannerStatus');
            $router->add('/admin/marketing/banner/delete', 'Admin\MarketingController', 'deleteBanner');
            $router->add('/admin/marketing/cart-reminders/send', 'Admin\MarketingController', 'sendAbandonedReminders');
            $router->add('/admin/marketing/order-notifications/send', 'Admin\MarketingController', 'sendOrderNotifications');
            $router->add('/admin/support', 'Admin\SupportController', 'index');
            $router->add('/admin/support/status', 'Admin\SupportController', 'updateStatus');
            $router->add('/admin/support/send-auto-replies', 'Admin\SupportController', 'sendAutoReplies');
        }

        private static function redirect($path) {
            header('Location: ' . self::url($path));
            exit;
        }

        private static function normalizeBaseUrl($baseUrl) {
            $baseUrl = trim(str_replace('\\', '/', (string) $baseUrl));
            if ($baseUrl === '') {
                return '/';
            }
            if (preg_match('#^https?://#i', $baseUrl)) {
                return rtrim($baseUrl, '/') . '/';
            }
            return '/' . trim($baseUrl, '/') . '/';
        }

        private static function scriptDirectory($path) {
            $path = self::normalizeSlashes((string) $path);
            $indexPosition = strpos($path, '/index.php');

            return $indexPosition === false ? dirname($path) : substr($path, 0, $indexPosition);
        }

        private static function normalizeSlashes($path) {
            return preg_replace('#/+#', '/', str_replace('\\', '/', (string) $path));
        }

        private static function cleanBasePath($path) {
            $path = rtrim(self::normalizeSlashes((string) $path), '/');
            if ($path === '' || $path === '.' || $path === '/') {
                return '';
            }

            return $path[0] === '/' ? $path : '/' . $path;
        }

        private static function normalizeRoutePath($path) {
            $path = parse_url((string) $path, PHP_URL_PATH);
            $path = '/' . trim(self::normalizeSlashes($path ?: '/'), '/');

            return $path === '/' ? '/' : rtrim($path, '/');
        }

        private static function pathStartsWith($path, $basePath) {
            $path = self::normalizeRoutePath($path);
            $basePath = self::cleanBasePath($basePath);

            return $basePath === '' || $path === $basePath || strpos($path, $basePath . '/') === 0;
        }

        private static function isHttps() {
            return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
                || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
        }

        private static function renderException(\Throwable $exception) {
            http_response_code(500);
            $debug = strtolower((string) getenv('APP_DEBUG'));

            if (in_array($debug, ['1', 'true', 'yes', 'on'], true)) {
                echo '<pre>' . htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8') . '</pre>';
                return;
            }

            echo '500 Server Error';
        }
    }
}
