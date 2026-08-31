<?php
namespace App\Controller;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\UserModel;
use App\Models\Cart;
use App\Services\LoggingService;
use App\Services\MailService;

class AuthController {
    public function login() {
        if (isset($_SESSION['user_id'])) {
            SessionHelper::redirect('/');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $_SESSION['login_old'] = ['email' => $email];

            if ($email === '' || $password === '') {
                SessionHelper::setFlash('error', 'Vui lòng nhập email và mật khẩu');
                SessionHelper::redirect('/login');
            }

            $userModel = new UserModel();
            $clientIp=substr((string)($_SERVER['REMOTE_ADDR']??'unknown'),0,45);
            $throttle=$userModel->loginThrottleStatus($email,$clientIp);
            if($throttle['blocked']){
                $minutes=max(1,(int)ceil($throttle['retry_after']/60));
                SessionHelper::setFlash('error','Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau '.$minutes.' phút.');
                SessionHelper::redirect('/login');
            }
            $user = $userModel->findByEmail($email);

            if (!$user) {
                $userModel->recordLoginAttempt($email,$clientIp,false);
                SessionHelper::setFlash('error', 'Email hoặc mật khẩu không đúng');
                SessionHelper::redirect('/login');
            }

            if (!$this->isActiveUser($user)) {
                $userModel->recordLoginAttempt($email,$clientIp,false);
                SessionHelper::setFlash('error', 'Tài khoản đã bị khóa');
                SessionHelper::redirect('/login');
            }

            // check local password
            if (!$user['password'] || !password_verify($password, $user['password'])) {
                $userModel->recordLoginAttempt($email,$clientIp,false);
                SessionHelper::setFlash('error', 'Email hoặc mật khẩu không đúng');
                SessionHelper::redirect('/login');
            }

            if (isset($user['email_verified']) && (int)$user['email_verified'] === 0) {
                if (!MailService::isConfigured()) {
                    $userModel->updateEmailVerified((int)$user['id'], 1);
                    $userModel->invalidateOtp((int)$user['id'], 'email_verification');
                    $user['email_verified'] = 1;
                } else {
                    $_SESSION['verify_email_user_id'] = $user['id'];
                    SessionHelper::setFlash('error', 'Tài khoản chưa được xác thực email. Vui lòng xác thực.');
                    SessionHelper::redirect('/verify-email');
                }
            }

            $guestSessionId = session_id();
            $userModel->recordLoginAttempt($email,$clientIp,true);
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = !empty($user['display_name']) ? $user['display_name'] : $user['full_name'];
            $_SESSION['user_avatar'] = $user['avatar'] ?? null;
            unset($_SESSION['login_old']);

            if ($guestSessionId !== '') {
                (new Cart())->mergeGuestCartIntoUser($guestSessionId, (int)$user['id']);
            }

            LoggingService::write($user['id'], 'login', 'Đăng nhập thành công');

            if ($user['role'] === 'admin') {
                SessionHelper::redirect('/admin');
            }

            SessionHelper::redirect('/');
        }

        require __DIR__ . '/../Views/login.php';
    }

    public function register() {
        if (isset($_SESSION['user_id'])) {
            SessionHelper::redirect('/');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $_SESSION['register_old'] = [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone
            ];

            if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
                SessionHelper::setFlash('error', 'Vui lòng nhập đầy đủ thông tin');
                SessionHelper::redirect('/register');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                SessionHelper::setFlash('error', 'Email không hợp lệ');
                SessionHelper::redirect('/register');
            }

            if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
                SessionHelper::setFlash('error', 'Số điện thoại không hợp lệ');
                SessionHelper::redirect('/register');
            }

            if (strlen($password) < 8) {
                SessionHelper::setFlash('error', 'Mật khẩu phải có ít nhất 8 ký tự');
                SessionHelper::redirect('/register');
            }

            if ($password !== $confirmPassword) {
                SessionHelper::setFlash('error', 'Mật khẩu xác nhận không khớp');
                SessionHelper::redirect('/register');
            }

            $userModel = new UserModel();
            $existingUser = $userModel->findByEmail($email);

            if ($existingUser) {
                if (isset($existingUser['email_verified']) && (int)$existingUser['email_verified'] === 0) {
                    if (!MailService::isConfigured()) {
                        $userModel->updateEmailVerified((int)$existingUser['id'], 1);
                        $userModel->invalidateOtp((int)$existingUser['id'], 'email_verification');
                        SessionHelper::setFlash('success', 'Tài khoản đã được kích hoạt. Bạn có thể đăng nhập ngay.');
                        SessionHelper::redirect('/login');
                    }
                    $_SESSION['verify_email_user_id'] = $existingUser['id'];
                    SessionHelper::setFlash('info', 'Tài khoản này đã được đăng ký nhưng chưa xác thực email. Vui lòng lấy mã OTP để xác thực.');
                    SessionHelper::redirect('/verify-email');
                } else {
                    SessionHelper::setFlash('error', 'Email đã được sử dụng');
                    SessionHelper::redirect('/register');
                }
            }

            $mailEnabled = MailService::isConfigured();
            $newId = $userModel->create([
                'full_name' => $fullName,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'phone' => $phone,
                'email_verified' => $mailEnabled ? 0 : 1
            ]);

            unset($_SESSION['register_old']);
            LoggingService::write($newId, 'register', 'Đăng ký tài khoản mới');

            if (!$mailEnabled) {
                SessionHelper::setFlash('success', 'Đăng ký thành công. Tài khoản đã được kích hoạt vì xác minh email đang tắt.');
                SessionHelper::redirect('/login');
            }

            $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpHash = password_hash($otp, PASSWORD_DEFAULT);
            $expiresAt = date('Y-m-d H:i:s', time() + 600);
            $userModel->createAuthOtp($newId, 'email_verification', $otpHash, $expiresAt);

            try {
                MailService::sendVerificationEmail($email, $otp);
                SessionHelper::setFlash('success', 'Đăng ký thành công. Vui lòng kiểm tra email để lấy mã xác nhận.');
            } catch (\Exception $e) {
                LoggingService::write($newId, 'register_error', 'Không thể gửi email OTP: ' . $e->getMessage());
                SessionHelper::setFlash('error', 'Tài khoản đã được tạo nhưng chưa gửi được email xác minh. Vui lòng thử gửi lại mã sau.');
            }

            $_SESSION['verify_email_user_id'] = $newId;
            SessionHelper::redirect('/verify-email');
        }

        require __DIR__ . '/../Views/register.php';
    }

    public function verifyEmail() {
        if (!isset($_SESSION['verify_email_user_id'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Phiên xác thực đã hết hạn, vui lòng đăng nhập lại.']);
                exit;
            }
            SessionHelper::redirect('/login');
        }
        
        $userId = $_SESSION['verify_email_user_id'];
        $userModel = new UserModel();
        $user = $userModel->findById($userId);
        
        if (!$user) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại.']);
                exit;
            }
            SessionHelper::redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            // Đọc body dạng JSON nếu client gửi `application/json`
            $input = json_decode(file_get_contents('php://input'), true);
            $otp = trim($input['otp'] ?? $_POST['otp'] ?? '');
            
            if (!$otp) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã OTP.']);
                exit;
            }

            if ((int)$user['email_verified'] === 1) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Email đã được xác thực.']);
                exit;
            }
            
            $otpRecord = $userModel->findAuthOtp($userId, 'email_verification');
            
            if (!$otpRecord) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy mã xác nhận. Vui lòng gửi lại mã mới.']);
                exit;
            }
            
            if (strtotime($otpRecord['expires_at']) < time()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Mã xác nhận đã hết hạn.']);
                exit;
            }
            
            if ($otpRecord['attempts'] >= 5) {
                $userModel->invalidateOtp($userId, 'email_verification');
                http_response_code(429);
                echo json_encode(['success' => false, 'message' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng yêu cầu gửi lại mã mới.']);
                exit;
            }
            
            if (!password_verify($otp, $otpRecord['otp_hash'])) {
                $userModel->incrementOtpAttempts($otpRecord['id']);
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Mã xác nhận không đúng.']);
                exit;
            }
            
            // Success
            $userModel->updateEmailVerified($userId, 1);
            $userModel->deleteAuthOtp($otpRecord['id']);
            unset($_SESSION['verify_email_user_id']);
            
            SessionHelper::setFlash('success', 'Xác thực email thành công. Bạn có thể đăng nhập ngay.');
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Xác thực thành công.', 'redirect' => BASE_URL . 'login']);
            exit;
        }
        
        require __DIR__ . '/../Views/verify_email.php';
    }

    public function resendVerificationOtp() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            exit;
        }

        if (!isset($_SESSION['verify_email_user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Phiên xác thực đã hết hạn, vui lòng đăng nhập lại.']);
            exit;
        }

        if (!MailService::isConfigured()) {
            http_response_code(503);
            echo json_encode(['success' => false, 'message' => 'Xác minh email hiện đang tắt. Vui lòng quay lại trang đăng nhập.']);
            exit;
        }

        $userId = (int)$_SESSION['verify_email_user_id'];
        $userModel = new UserModel();
        $remaining = $userModel->otpRequestCooldownRemaining($userId, 'email_verification');
        if ($remaining > 0) {
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Vui lòng chờ ' . $remaining . ' giây trước khi yêu cầu gửi lại mã.']);
            exit;
        }

        $user = $userModel->findById($userId);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại.']);
            exit;
        }

        if ((int)$user['email_verified'] === 1) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email đã được xác thực.']);
            exit;
        }

        // Generate OTP
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + 600); 

        // Invalidate old OTP
        $userModel->invalidateOtp($userId, 'email_verification');
        // Save new OTP
        $userModel->createAuthOtp($userId, 'email_verification', $otpHash, $expiresAt);
        
        try {
            MailService::sendVerificationEmail($user['email'], $otp);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể gửi OTP lúc này. Vui lòng thử lại sau.'
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Mã xác nhận mới đã được gửi tới email của bạn.']);
        exit;
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            LoggingService::write($_SESSION['user_id'], 'logout', 'Đăng xuất');
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                $params['secure'] ?? false,
                $params['httponly'] ?? true
            );
        }

        session_destroy();
        SessionHelper::redirect('/login');
    }

    public function changePassword() {
        AuthMiddleware::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/account');
        }

        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmNewPassword = (string)($_POST['confirm_new_password'] ?? '');

        $userModel = new UserModel();
        $user = $userModel->findById($_SESSION['user_id']);

        if (!$user) {
            SessionHelper::redirect('/account');
        }

        $hasLocalPassword = isset($user['password']) && is_string($user['password']) && $user['password'] !== '';
        if ($hasLocalPassword && !password_verify($currentPassword, $user['password'])) {
            SessionHelper::setFlash('error', 'Mật khẩu hiện tại không đúng');
            SessionHelper::redirect('/account');
        }

        $passwordLength = strlen($newPassword);
        if ($passwordLength < 8 || $passwordLength > 72) {
            SessionHelper::setFlash('error', 'Mật khẩu mới phải có từ 8 đến 72 ký tự');
            SessionHelper::redirect('/account');
        }

        if ($newPassword !== $confirmNewPassword) {
            SessionHelper::setFlash('error', 'Mật khẩu mới xác nhận không khớp');
            SessionHelper::redirect('/account');
        }

        if ($hasLocalPassword && password_verify($newPassword, $user['password'])) {
            SessionHelper::setFlash('error', 'Mật khẩu mới không được trùng với mật khẩu hiện tại');
            SessionHelper::redirect('/account');
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if (!is_string($hash) || !$userModel->updatePassword($_SESSION['user_id'], $hash)) {
            SessionHelper::setFlash('error', 'Không thể cập nhật mật khẩu. Vui lòng thử lại.');
            SessionHelper::redirect('/account');
        }

        LoggingService::write($_SESSION['user_id'], 'change_password', 'Đổi mật khẩu thành công');
        session_regenerate_id(true);
        SessionHelper::setFlash('success', 'Đổi mật khẩu thành công');
        SessionHelper::redirect('/account');
    }

    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                SessionHelper::setFlash('error', 'Email không hợp lệ');
                SessionHelper::redirect('/forgot-password');
            }

            if (!MailService::isConfigured()) {
                SessionHelper::setFlash('error', 'Chức năng đặt lại mật khẩu qua email chưa khả dụng vì SMTP chưa được cấu hình.');
                SessionHelper::redirect('/forgot-password');
            }

            $userModel = new UserModel();
            $user = $userModel->findByEmail($email);

            if ($user) {
                if ($userModel->otpRequestCooldownRemaining((int)$user['id'], 'password_reset') > 0) {
                    // Luôn trả thông điệp chung để không lộ trạng thái tài khoản,
                    // đồng thời chặn spam email qua việc đổi trình duyệt/session.
                    SessionHelper::setFlash('info', 'Nếu email tồn tại trong hệ thống, một mã OTP sẽ được gửi đến email đó.');
                    SessionHelper::redirect('/forgot-password');
                }
                $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otpHash = password_hash($otp, PASSWORD_DEFAULT);
                $expiresAt = date('Y-m-d H:i:s', time() + 600);
                
                $userModel->createAuthOtp($user['id'], 'password_reset', $otpHash, $expiresAt);
                
                try {
                    MailService::sendForgotPasswordEmail($email, $otp);
                } catch (\Exception $e) {
                    LoggingService::write($user['id'], 'forgot_password_error', 'Không thể gửi email OTP: ' . $e->getMessage());
                }
                
                $_SESSION['reset_password_user_id'] = $user['id'];
                SessionHelper::setFlash('info', 'Nếu email tồn tại trong hệ thống, một mã OTP sẽ được gửi đến email đó.');
                SessionHelper::redirect('/verify-reset-otp');
            } else {
                SessionHelper::setFlash('info', 'Nếu email tồn tại trong hệ thống, một mã OTP sẽ được gửi đến email đó.');
                SessionHelper::redirect('/forgot-password');
            }
        }

        require __DIR__ . '/../Views/forgot_password.php';
    }

    public function verifyResetOtp() {
        if (empty($_SESSION['reset_password_user_id'])) {
            SessionHelper::redirect('/forgot-password');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $otp = trim($_POST['otp'] ?? '');
            $userId = $_SESSION['reset_password_user_id'];
            
            $userModel = new UserModel();
            $otpRecord = $userModel->findAuthOtp($userId, 'password_reset');

            if (!$otpRecord) {
                SessionHelper::setFlash('error', 'Không tìm thấy mã xác nhận. Vui lòng yêu cầu lại.');
                SessionHelper::redirect('/verify-reset-otp');
            }
            
            if (strtotime($otpRecord['expires_at']) < time()) {
                SessionHelper::setFlash('error', 'Mã xác nhận đã hết hạn.');
                SessionHelper::redirect('/verify-reset-otp');
            }
            
            if ($otpRecord['attempts'] >= 5) {
                $userModel->invalidateOtp($userId, 'password_reset');
                SessionHelper::setFlash('error', 'Bạn đã nhập sai quá nhiều lần. Vui lòng yêu cầu gửi lại mã mới.');
                SessionHelper::redirect('/forgot-password');
            }
            
            if (!password_verify($otp, $otpRecord['otp_hash'])) {
                $userModel->incrementOtpAttempts($otpRecord['id']);
                SessionHelper::setFlash('error', 'Mã xác nhận không đúng.');
                SessionHelper::redirect('/verify-reset-otp');
            }

            // Success
            $userModel->deleteAuthOtp($otpRecord['id']);
            unset($_SESSION['reset_password_user_id']);
            $_SESSION['password_reset_verified'] = [
                'user_id' => $userId,
                'expires_at' => time() + 600
            ];
            
            SessionHelper::redirect('/reset-password');
        }

        require __DIR__ . '/../Views/verify_reset_otp.php';
    }

    public function resetPassword() {
        if (empty($_SESSION['password_reset_verified'])) {
            SessionHelper::redirect('/forgot-password');
        }
        
        if (time() > $_SESSION['password_reset_verified']['expires_at']) {
            unset($_SESSION['password_reset_verified']);
            SessionHelper::setFlash('error', 'Thời gian đổi mật khẩu đã hết hạn.');
            SessionHelper::redirect('/forgot-password');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            if (strlen($newPassword) < 8) {
                SessionHelper::setFlash('error', 'Mật khẩu phải có ít nhất 8 ký tự');
                SessionHelper::redirect('/reset-password');
            }

            if ($newPassword !== $confirmPassword) {
                SessionHelper::setFlash('error', 'Mật khẩu xác nhận không khớp');
                SessionHelper::redirect('/reset-password');
            }

            $userId = $_SESSION['password_reset_verified']['user_id'];
            $userModel = new UserModel();
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $userModel->updatePassword($userId, $hash);
            unset($_SESSION['password_reset_verified']);

            LoggingService::write($userId, 'reset_password', 'Đặt lại mật khẩu thành công');
            SessionHelper::setFlash('success', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập.');
            SessionHelper::redirect('/login');
        }

        require __DIR__ . '/../Views/reset_password.php';
    }

    public function googleLoginCallback() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/login');
        }

        $googleCsrfCookie = (string)($_COOKIE['g_csrf_token'] ?? '');
        $googleCsrfSubmitted = (string)($_POST['g_csrf_token'] ?? '');
        if ($googleCsrfCookie === '' || $googleCsrfSubmitted === '' || !hash_equals($googleCsrfCookie, $googleCsrfSubmitted)) {
            SessionHelper::setFlash('error', 'Yêu cầu đăng nhập Google không hợp lệ. Vui lòng thử lại.');
            SessionHelper::redirect('/login');
        }

        $idToken = $_POST['credential'] ?? '';
        if (!$idToken) {
            SessionHelper::setFlash('error', 'Không nhận được thông tin từ Google.');
            SessionHelper::redirect('/login');
        }

        // Verify ID Token
        $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            SessionHelper::setFlash('error', 'Lỗi kết nối tới Google.');
            SessionHelper::redirect('/login');
        }

        $payload = json_decode($response, true);

        if (isset($payload['error'])) {
            SessionHelper::setFlash('error', 'Xác thực Google không hợp lệ.');
            SessionHelper::redirect('/login');
        }

        $googleClientId = trim((string)getenv('GOOGLE_CLIENT_ID'));
        if (!$googleClientId && defined('GOOGLE_CLIENT_ID')) {
             $googleClientId = GOOGLE_CLIENT_ID;
        }
        
        // Không được nhận token khi chưa biết token đó được cấp cho OAuth client nào.
        if ($googleClientId === '') {
            SessionHelper::setFlash('error', 'Đăng nhập Google chưa được cấu hình an toàn. Vui lòng dùng email và mật khẩu.');
            SessionHelper::redirect('/login');
        }
        if (!isset($payload['aud']) || !hash_equals($googleClientId, (string)$payload['aud'])) {
            SessionHelper::setFlash('error', 'Google Client ID không khớp.');
            SessionHelper::redirect('/login');
        }

        if (empty($payload['email'])) {
            SessionHelper::setFlash('error', 'Không thể lấy email từ Google.');
            SessionHelper::redirect('/login');
        }

        $isEmailVerified = isset($payload['email_verified']) && ($payload['email_verified'] === true || $payload['email_verified'] === 'true');
        if (!$isEmailVerified) {
            SessionHelper::setFlash('error', 'Email của tài khoản Google này chưa được xác minh.');
            SessionHelper::redirect('/login');
        }

        $email = $payload['email'];
        $googleId = $payload['sub'];
        $fullName = $payload['name'] ?? 'Google User';
        $avatar = $payload['picture'] ?? null;

        $userModel = new UserModel();
        $user = $userModel->findByGoogleId($googleId);

        if (!$user) {
            $userByEmail = $userModel->findByEmail($email);
            if ($userByEmail) {
                // Link account
                $userModel->updateGoogleId($userByEmail['id'], $googleId);
                $userModel->updateEmailVerified($userByEmail['id'], 1);
                $user = $userModel->findById($userByEmail['id']);
            } else {
                // Create new user
                $newId = $userModel->create([
                    'full_name' => $fullName,
                    'email' => $email,
                    'password' => null, 
                    'phone' => null,
                    'google_id' => $googleId,
                    'email_verified' => 1 
                ]);
                if ($avatar) {
                    $userModel->updateAvatar($newId, $avatar);
                }
                $user = $userModel->findById($newId);
                LoggingService::write($newId, 'register', 'Đăng ký qua Google');
            }
        }

        if (!$this->isActiveUser($user)) {
            SessionHelper::setFlash('error', 'Tài khoản đã bị khóa');
            SessionHelper::redirect('/login');
        }

        // Login
        $guestSessionId = session_id();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = !empty($user['display_name']) ? $user['display_name'] : $user['full_name'];
        $_SESSION['user_avatar'] = $user['avatar'] ?? null;

        if ($guestSessionId !== '') {
            (new Cart())->mergeGuestCartIntoUser($guestSessionId, (int)$user['id']);
        }

        LoggingService::write($user['id'], 'login', 'Đăng nhập Google thành công');

        if ($user['role'] === 'admin') {
            SessionHelper::redirect('/admin');
        }
        SessionHelper::redirect('/');
    }

    private function isActiveUser(array $user): bool {
        $status = strtolower(trim((string)($user['status'] ?? '')));
        return in_array($status, ['1', 'active'], true);
    }
}
