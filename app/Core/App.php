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
        private static $cspNonce = null;
        private static $environment = [];

        public static function run() {
            try {
                self::bootstrap();

                $path = self::currentPath();
                if (self::requiresPost($path) && strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
                    self::renderMethodNotAllowed();
                    return;
                }
                if (self::isUnsafeRequest() && !self::isCsrfExemptPath($path) && !\App\Helpers\SessionHelper::validateCsrfRequest()) {
                    self::renderCsrfFailure();
                    return;
                }
                if (isset($_SESSION['user_id']) && in_array($path, ['/login', '/register'], true)) {
                    self::redirect(($_SESSION['user_role'] ?? null) === 'admin' ? '/admin' : '/');
                }

                self::router()->dispatch($path);
            } catch (\Throwable $exception) {
                self::renderException($exception);
            }
        }

        public static function bootstrap() {
            self::loadEnv();
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            self::defineBaseUrl();
            self::enforceProductionHttps();
            self::sendSecurityHeaders();
            self::startSession();
            self::registerAutoloader();
        }

        public static function loadEnv() {
            $rootPath = self::rootPath();
            $envFile = null;

            // Trên shared hosting, ưu tiên đặt .env ở ngoài document root
            // (ví dụ /home/.../.env trong khi mã nguồn nằm ở /home/.../htdocs).
            // Máy local vẫn dùng .env trong thư mục dự án như trước.
            $candidates = [
                dirname($rootPath) . '/.env',
                $rootPath . '/.env',
            ];
            foreach ($candidates as $candidate) {
                if (is_file($candidate) && is_readable($candidate)) {
                    $envFile = $candidate;
                    break;
                }
            }

            if ($envFile === null) {
                return;
            }

            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (str_starts_with(trim($line), '#')) continue;
                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2) {
                        $name = trim($parts[0]);
                        $value = trim($parts[1]);
                        // Remove quotes if present
                        $value = trim($value, '"\'');
                        // Explicit process configuration must win over .env (CLI,
                        // tests and hosting settings must not connect to another DB).
                        $processValue = function_exists('getenv') ? getenv($name) : false;
                        if ($processValue !== false) {
                            $value = $processValue;
                        }

                        if (!array_key_exists($name, self::$environment)) {
                            self::$environment[$name] = $value;
                        }
                        if (!array_key_exists($name, $_SERVER)) {
                            $_SERVER[$name] = $value;
                        }
                        if (!array_key_exists($name, $_ENV)) {
                            $_ENV[$name] = $value;
                        }

                        // InfinityFree vô hiệu hóa putenv(). Ứng dụng không phụ thuộc
                        // vào hàm này: App::env() luôn đọc được giá trị đã nạp ở trên.
                        if (function_exists('putenv')) {
                            putenv(sprintf('%s=%s', $name, $value));
                        }
                    }
                }
            }
        }

        /**
         * Đọc cấu hình môi trường tương thích cả shared hosting chặn putenv().
         */
        public static function env(string $name, $default = null) {
            $native = function_exists('getenv') ? getenv($name) : false;
            if ($native !== false && $native !== '') {
                return $native;
            }
            if (array_key_exists($name, self::$environment)) {
                return self::$environment[$name];
            }
            if (array_key_exists($name, $_ENV)) {
                return $_ENV[$name];
            }
            if (array_key_exists($name, $_SERVER)) {
                return $_SERVER[$name];
            }

            return $default;
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

        /**
         * Tạo URL tuyệt đối cho nội dung rời khỏi website như email và cổng
         * thanh toán. BASE_URL có thể chỉ là "/", nên không được dùng trực
         * tiếp làm href trong email.
         */
        public static function publicUrl($path = '') {
            $path = (string)$path;
            if (preg_match('#^https?://#i', $path)) {
                return $path;
            }

            $configured = trim((string)(self::env('APP_PUBLIC_URL') ?: ''));
            if (preg_match('#^https?://[^/]+#i', $configured)) {
                $origin = rtrim($configured, '/');
            } else {
                $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
                if ($host === '' || !preg_match('/^[a-z0-9.\-\[\]:]+$/i', $host)) {
                    throw new \RuntimeException('Chưa cấu hình APP_PUBLIC_URL hợp lệ để tạo liên kết email.');
                }
                $origin = (self::isHttps() ? 'https://' : 'http://') . $host;
                $basePath = rtrim((string)(defined('BASE_URL') ? BASE_URL : '/'), '/');
                if ($basePath !== '') {
                    $origin .= '/' . ltrim($basePath, '/');
                }
            }

            return $path === '' || $path === '/'
                ? $origin . '/'
                : $origin . '/' . ltrim($path, '/');
        }

        /**
         * Nonce dùng cho các khối script/style nội tuyến do chính ứng dụng
         * sinh ra. Giá trị chỉ sống trong một request và không được tái sử dụng.
         */
        public static function cspNonce(): string {
            if (self::$cspNonce === null) {
                self::$cspNonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
            }
            return self::$cspNonce;
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

            $configured = (string)self::env('APP_BASE_URL', '');
            if (trim($configured) !== '') {
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
            $router->add('/shipping/quote', 'CheckoutController', 'shippingQuote');
            $router->add('/paypal/return', 'CheckoutController', 'paypalReturn');
            $router->add('/paypal/cancel', 'CheckoutController', 'paypalCancel');
            $router->add('/paypal/webhook', 'CheckoutController', 'paypalWebhook');
            $router->add('/checkout-success', 'CheckoutController', 'success');
            $router->add('/order/receipt', 'CheckoutController', 'receipt');
            $router->add('/invoice/view', 'InvoiceController', 'view');
            $router->add('/apply-coupon', 'CheckoutController', 'applyCoupon');
            $router->add('/support', 'SupportController', 'index');
            // Giữ alias phổ biến để liên kết cũ hoặc người dùng nhập trực tiếp
            // /contact không rơi vào trang 404.
            $router->add('/contact', 'SupportController', 'index');
            $router->add('/support/store', 'SupportController', 'store');
            $router->add('/review/store', 'ReviewController', 'store');
            $router->add('/product/review', 'ReviewController', 'storeDirect');
            $router->add('/after-sale/request', 'AfterSaleController', 'store');
            $router->add('/login', 'AuthController', 'login');
            $router->add('/register', 'AuthController', 'register');
            $router->add('/logout', 'AuthController', 'logout');
            $router->add('/change-password', 'AuthController', 'changePassword');
            
            // Verification Routes
            $router->add('/verify-email', 'AuthController', 'verifyEmail');
            $router->add('/resend-verification-otp', 'AuthController', 'resendVerificationOtp');
            
            // Forgot Password Routes
            $router->add('/forgot-password', 'AuthController', 'forgotPassword');
            $router->add('/verify-reset-otp', 'AuthController', 'verifyResetOtp');
            $router->add('/reset-password', 'AuthController', 'resetPassword');

            // Google Login
            $router->add('/auth/google', 'AuthController', 'googleLoginCallback');
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
            $router->add('/feedback', 'PageController', 'feedback');
            $router->add('/newsletter/subscribe', 'PageController', 'subscribeNewsletter');
            $router->add('/newsletter/unsubscribe', 'PageController', 'unsubscribeNewsletter');
            $router->add('/newsletter/confirm', 'PageController', 'confirmNewsletter');
            $router->add('/newsletter/open', 'PageController', 'openNewsletter');
            $router->add('/newsletter/click', 'PageController', 'clickNewsletter');
            $router->add('/analytics/event', 'PageController', 'recordAnalytics');

            $router->add('/admin', 'Admin\\DashboardController', 'index');
            $router->add('/admin/users', 'Admin\\UserController', 'index');
            $router->add('/admin/users/status', 'Admin\\UserController', 'updateStatus');
            $router->add('/admin/settings', 'Admin\\SettingsController', 'index');
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
            $router->add('/admin/inventory/variants/update', 'Admin\InventoryController', 'updateVariant');
            $router->add('/admin/inventory/variants/delete', 'Admin\InventoryController', 'deleteVariant');
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
            $router->add('/admin/orders/payment/confirm', 'Admin\OrderController', 'confirmPayment');
            $router->add('/admin/orders/payment/refund-canceled', 'Admin\OrderController', 'refundCanceledPayment');
            $router->add('/admin/orders/paypal/reconcile', 'Admin\OrderController', 'reconcilePayPal');
            $router->add('/admin/after-sales', 'Admin\AfterSaleController', 'index');
            $router->add('/admin/after-sales/update', 'Admin\AfterSaleController', 'update');
            $router->add('/admin/marketing', 'Admin\MarketingController', 'index');
            $router->add('/admin/marketing/banner/store', 'Admin\MarketingController', 'storeBanner');
            $router->add('/admin/marketing/banner/status', 'Admin\MarketingController', 'updateBannerStatus');
            $router->add('/admin/marketing/banner/delete', 'Admin\MarketingController', 'deleteBanner');
            $router->add('/admin/marketing/cart-reminders/send', 'Admin\MarketingController', 'sendAbandonedReminders');
            $router->add('/admin/marketing/order-notifications/send', 'Admin\MarketingController', 'sendOrderNotifications');
            $router->add('/admin/marketing/campaign/store', 'Admin\MarketingController', 'storeCampaign');
            $router->add('/admin/marketing/campaign/queue', 'Admin\MarketingController', 'queueCampaign');
            $router->add('/admin/marketing/campaign/send', 'Admin\MarketingController', 'sendCampaign');
            $router->add('/admin/invoices', 'Admin\InvoiceController', 'index');
            $router->add('/admin/invoices/issue', 'Admin\InvoiceController', 'issue');
            $router->add('/admin/invoices/adjust', 'Admin\InvoiceController', 'adjust');
            $router->add('/admin/invoices/cancel', 'Admin\InvoiceController', 'cancel');
            $router->add('/admin/tax-report', 'Admin\TaxReportController', 'index');
            $router->add('/admin/procurement', 'Admin\ProcurementController', 'index');
            $router->add('/admin/procurement/supplier', 'Admin\ProcurementController', 'supplier');
            $router->add('/admin/procurement/order', 'Admin\ProcurementController', 'order');
            $router->add('/admin/procurement/receive', 'Admin\ProcurementController', 'receive');
            $router->add('/admin/procurement/pay', 'Admin\ProcurementController', 'pay');
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
                || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
                || strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0])) === 'https';
        }

        private static function enforceProductionHttps(): void {
            // Cron/CLI không có HTTP host hoặc scheme; không được kết thúc các
            // tác vụ nền chỉ vì production đang cưỡng chế HTTPS cho web.
            if (PHP_SAPI === 'cli') {
                return;
            }
            $enabled = in_array(strtolower((string)self::env('FORCE_HTTPS')), ['1', 'true', 'yes', 'on'], true);
            $host = (string)($_SERVER['HTTP_HOST'] ?? '');
            $isLocal = preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/i', $host);
            if (!$enabled || $host === '' || self::isHttps() || $isLocal || headers_sent()) {
                return;
            }
            header('Location: https://' . $host . (string)($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
            exit;
        }

        private static function sendSecurityHeaders(): void {
            if (headers_sent()) {
                return;
            }
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self "https://www.paypal.com")');
            $nonce = self::cspNonce();
            header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self' https://www.paypal.com https://www.sandbox.paypal.com https://accounts.google.com; script-src 'self' 'nonce-{$nonce}' https://www.paypal.com https://www.paypalobjects.com https://accounts.google.com https://cdn.jsdelivr.net; script-src-elem 'self' 'nonce-{$nonce}' https://www.paypal.com https://www.paypalobjects.com https://accounts.google.com https://cdn.jsdelivr.net; script-src-attr 'unsafe-inline'; style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com https://cdnjs.cloudflare.com; style-src-elem 'self' 'nonce-{$nonce}' https://fonts.googleapis.com https://cdnjs.cloudflare.com; style-src-attr 'unsafe-inline'; font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: blob: https:; connect-src 'self' https://api-m.paypal.com https://api-m.sandbox.paypal.com https://accounts.google.com; frame-src https://www.paypal.com https://www.sandbox.paypal.com https://www.google.com https://accounts.google.com");
            if (self::isHttps()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }

        private static function isUnsafeRequest(): bool {
            return in_array(strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
        }

        private static function requiresPost(string $path): bool {
            return in_array($path, [
                '/cart/add',
                '/cart/remove',
                '/cart/update',
                '/wishlist/add',
                '/wishlist/remove',
                '/checkout/place-order',
                '/paypal/webhook',
                '/shipping/quote',
                '/apply-coupon',
                '/support/store',
                '/newsletter/subscribe',
                '/analytics/event',
                '/review/store',
                '/product/review',
                '/after-sale/request',
                '/change-password',
                '/resend-verification-otp',
                '/auth/google',
                '/account/update',
                '/account/avatar',
                '/account/addresses/add',
                '/account/addresses/default',
                '/account/addresses/delete',
                '/account/orders/cancel',
                '/admin/products/delete',
                '/admin/products/destroy',
                '/admin/products/variants/add',
                '/admin/products/variants/update',
                '/admin/products/variants/delete',
                '/admin/products/images/primary',
                '/admin/users/status',
                '/admin/products/images/delete',
                '/admin/categories/create',
                '/admin/categories/delete',
                '/admin/inventory/update',
                '/admin/inventory/variants/create',
                '/admin/inventory/variants/update',
                '/admin/inventory/variants/delete',
                '/admin/coupons/store',
                '/admin/coupons/update',
                '/admin/coupons/delete',
                '/admin/orders/status',
                '/admin/orders/shipping',
                '/admin/orders/payment/confirm',
                '/admin/orders/payment/refund-canceled',
                '/admin/orders/paypal/reconcile',
                '/admin/after-sales/update',
                '/admin/marketing/banner/store',
                '/admin/marketing/banner/status',
                '/admin/marketing/banner/delete',
                '/admin/marketing/cart-reminders/send',
                '/admin/marketing/order-notifications/send',
                '/admin/marketing/campaign/store',
                '/admin/marketing/campaign/queue',
                '/admin/marketing/campaign/send',
                '/admin/invoices/issue',
                '/admin/invoices/adjust',
                '/admin/invoices/cancel',
                '/admin/procurement/supplier',
                '/admin/procurement/order',
                '/admin/procurement/receive',
                '/admin/procurement/pay',
                '/admin/support/status',
                '/admin/support/send-auto-replies'
            ], true);
        }

        private static function renderMethodNotAllowed(): void {
            http_response_code(405);
            header('Allow: POST');
            header('Content-Type: text/plain; charset=UTF-8');
            echo '405 Method Not Allowed';
        }

        private static function isCsrfExemptPath(string $path): bool {
            // Google Identity Services posts directly from Google's origin.
            // Its callback validates Google's own double-submit CSRF token.
            return in_array($path, ['/auth/google', '/paypal/webhook'], true);
        }

        private static function renderCsrfFailure(): void {
            http_response_code(419);
            $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
            $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
            $isJson = strpos($contentType, 'application/json') !== false
                || strpos($accept, 'application/json') !== false
                || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

            if ($isJson) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Phiên bảo mật đã hết hạn. Vui lòng tải lại trang và thử lại.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            header('Content-Type: text/html; charset=UTF-8');
            echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>Phiên đã hết hạn</title>'
                . '<body style="font-family:Arial,sans-serif;max-width:680px;margin:80px auto;padding:24px">'
                . '<h1>Phiên bảo mật đã hết hạn</h1><p>Vui lòng quay lại, tải lại trang rồi thực hiện thao tác một lần nữa.</p>'
                . '<p><a href="javascript:history.back()">Quay lại</a></p></body></html>';
        }

        private static function renderException(\Throwable $exception) {
            http_response_code(500);
            $debug = strtolower((string) self::env('APP_DEBUG'));

            if (in_array($debug, ['1', 'true', 'yes', 'on'], true)) {
                echo '<pre>' . htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8') . '</pre>';
                return;
            }

            echo '500 Server Error';
        }
    }
}
