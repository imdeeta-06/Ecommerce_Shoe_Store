<?php
$store = require __DIR__ . '/../../../config/store.php';
$e = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$metaTitle = 'Chính sách bảo vệ dữ liệu cá nhân - Liên Hoa';
include __DIR__ . '/../partials/header.php';
?>
<main class="client-page">
    <h1 class="client-title" style="text-align:center;margin-bottom:.5rem;">Chính sách bảo vệ dữ liệu cá nhân</h1>
    <p style="text-align:center;color:#777;">Phiên bản <?= $e($store['privacy_version']) ?> · Áp dụng cho website đồ án Liên Hoa</p>
    <article class="client-main-content" style="max-width:900px;margin:2rem auto;line-height:1.8;color:#333;">
        <h2 class="client-section-title">1. Đơn vị tiếp nhận yêu cầu</h2>
        <p><?= $e($store['legal_name']) ?> (mô phỏng), địa chỉ <?= $e($store['address']) ?>. Yêu cầu về dữ liệu gửi tới <?= $e($store['privacy_email']) ?> hoặc <?= $e($store['phone']) ?>.</p>
        <h2 class="client-section-title">2. Dữ liệu và nguồn thu thập</h2>
        <p>Website thu thập dữ liệu khách chủ động cung cấp: họ tên, email, điện thoại, địa chỉ, tài khoản, nội dung hỗ trợ, đánh giá và thông tin đơn hàng; dữ liệu vận hành như IP, user-agent, phiên đăng nhập, lịch sử trạng thái; và dữ liệu đo lường đã được đồng ý như trang xem, sản phẩm, nguồn/chiến dịch UTM. Website không lưu số thẻ ngân hàng.</p>
        <h2 class="client-section-title">3. Mục đích và căn cứ xử lý</h2>
        <p>Dữ liệu cần thiết được dùng để tạo tài khoản, thực hiện hợp đồng mua bán, giao hàng, thanh toán, chống gian lận, hỗ trợ, đổi trả và tuân thủ nghĩa vụ pháp lý. Email marketing và đo lường không thiết yếu chỉ được ghi nhận sau lựa chọn đồng ý riêng; khách có thể từ chối mà vẫn mua hàng.</p>
        <h2 class="client-section-title">4. Cookie và đo lường</h2>
        <p>Cookie phiên và CSRF là cần thiết cho đăng nhập, giỏ hàng và bảo mật. Khi khách chọn “Đồng ý đo lường”, website lưu lựa chọn trên trình duyệt và ghi các sự kiện tổng hợp nhằm tính traffic, phễu chuyển đổi và hiệu quả chiến dịch. Khi từ chối, endpoint đo lường không được gọi. Có thể xóa lựa chọn trong bộ nhớ trình duyệt để được hỏi lại.</p>
        <h2 class="client-section-title">5. Bên nhận dữ liệu</h2>
        <p>Dữ liệu có thể được chia sẻ trong phạm vi cần thiết với đơn vị vận chuyển, nhà cung cấp email, ngân hàng/đối soát, PayPal và cơ quan có thẩm quyền. Khi khách chọn chuyển khoản, trình duyệt tải ảnh QR từ VietQR với mã đơn, số tiền và thông tin tài khoản nhận; VietQR có thể nhận địa chỉ IP và dữ liệu kỹ thuật của lượt tải. Khi khách chọn PayPal, hệ thống gửi mã đơn, mô tả giao dịch, số tiền quy đổi USD và địa chỉ trả về cho PayPal; khách tiếp tục đăng nhập/thanh toán trên trang PayPal theo chính sách riêng của nhà cung cấp này. Giao diện hiện còn tải Google Fonts, Google Maps và dịch vụ tạo avatar; các nhà cung cấp này cũng có thể nhận IP, user-agent và referrer. Liên Hoa không bán dữ liệu cá nhân.</p>
        <h2 class="client-section-title">6. Thời hạn lưu</h2>
        <p>Tài khoản được lưu đến khi khách yêu cầu xóa hoặc sau 24 tháng không hoạt động; đơn hàng, thanh toán và bản ghi chấp thuận được lưu 10 năm cho mục đích kế toán/đối chiếu mô phỏng; ticket hỗ trợ 24 tháng sau khi đóng; OTP hết hạn được xóa tối đa sau 30 ngày; sự kiện đo lường 13 tháng; newsletter đến khi rút lại đồng ý. Khi hết hạn, dữ liệu được xóa hoặc ẩn danh, trừ trường hợp pháp luật yêu cầu lưu lâu hơn.</p>
        <h2 class="client-section-title">7. Quyền của người dùng</h2>
        <p>Người dùng có thể yêu cầu được biết, truy cập, sửa, hạn chế, phản đối, rút lại đồng ý, xóa dữ liệu hoặc khiếu nại. Liên Hoa có thể yêu cầu xác minh danh tính và sẽ phản hồi trong thời hạn phù hợp; một số dữ liệu giao dịch không thể xóa ngay nếu còn nghĩa vụ lưu giữ hoặc tranh chấp.</p>
        <h2 class="client-section-title">8. An toàn dữ liệu và sự cố</h2>
        <p>Website áp dụng kiểm soát truy cập, mật khẩu băm, CSRF, session an toàn, câu lệnh tham số hóa và các header bảo mật. Khi triển khai thật, HTTPS phải được bật và cấu hình HSTS. Không có phương thức truyền hoặc lưu trữ nào an toàn tuyệt đối; khi phát hiện sự cố, đơn vị vận hành sẽ cô lập, đánh giá, khắc phục và thông báo theo nghĩa vụ pháp luật.</p>
        <h2 class="client-section-title">9. Chuyển dữ liệu và thay đổi chính sách</h2>
        <p>Một số nhà cung cấp hạ tầng/email/QR có thể xử lý dữ liệu ngoài Việt Nam; khi triển khai thật phải đánh giá nhà cung cấp và thực hiện hồ sơ/nghĩa vụ chuyển dữ liệu tương ứng. Chính sách mới ghi rõ ngày hiệu lực; thay đổi mục đích marketing cần xin lại đồng ý khi cần thiết.</p>
    </article>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
