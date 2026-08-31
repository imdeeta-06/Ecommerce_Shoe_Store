<?php

namespace App\Controller;

use App\Helpers\SessionHelper;
use App\Models\SupportTicket;

class SupportController {
    public function index() {
        $metaTitle = 'Hỗ trợ khách hàng - Liên Hoa';
        $metaDescription = 'Gửi yêu cầu hỗ trợ, đổi trả, giao hàng hoặc sản phẩm cho Liên Hoa.';
        $flash = SessionHelper::getAllFlash();
        require __DIR__ . '/../Views/pages/support.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/support');
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));
        $rating = (int)($_POST['rating'] ?? 0);
        if ($rating >= 1 && $rating <= 5) {
            $message = "Mức độ hài lòng: {$rating}/5\n" . $message;
        }
        $returnTo = (string)($_POST['return_to'] ?? '') === 'feedback' ? '/feedback' : '/support';

        if ($name === '' || $subject === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            SessionHelper::setFlash('error', 'Vui lòng nhập họ tên, email hợp lệ, chủ đề và nội dung cần hỗ trợ.');
            SessionHelper::redirect($returnTo);
        }
        if (mb_strlen($message) < 10) {
            SessionHelper::setFlash('error', 'Nội dung hỗ trợ cần ít nhất 10 ký tự để Liên Hoa có thể tiếp nhận.');
            SessionHelper::redirect($returnTo);
        }

        $id = (new SupportTicket())->createTicket([
            'user_id' => $_SESSION['user_id'] ?? null,
            'name' => mb_substr($name, 0, 150),
            'email' => mb_substr($email, 0, 255),
            'phone' => mb_substr($phone, 0, 30),
            'subject' => mb_substr($subject, 0, 255),
            'message' => mb_substr($message, 0, 5000)
        ]);
        $ticket = (new SupportTicket())->getTicketById($id);
        SessionHelper::setFlash('success', 'Đã tiếp nhận yêu cầu ' . ($ticket['ticket_code'] ?? '') . '. Liên Hoa sẽ phản hồi qua email.');
        SessionHelper::redirect($returnTo);
    }
}
