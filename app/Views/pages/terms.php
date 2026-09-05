<?php
$store = require __DIR__ . '/../../../config/store.php';
$e = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$metaTitle = 'Điều khoản mua bán - Liên Hoa';
include __DIR__ . '/../partials/header.php';
?>
<main class="client-page">
    <h1 class="client-title" style="text-align:center;margin-bottom:.5rem;">Điều khoản mua bán và sử dụng website</h1>
    <p style="text-align:center;color:#777;">Phiên bản <?= $e($store['terms_version']) ?> · Dữ liệu pháp lý mô phỏng phục vụ đồ án</p>
    <article class="client-main-content" style="max-width:900px;margin:2rem auto;line-height:1.8;color:#333;">
        <div style="padding:1rem 1.25rem;background:#fff8e6;border:1px solid #e8c36a;margin-bottom:2rem;"><strong>Lưu ý đồ án:</strong> Liên Hoa trong website này là cửa hàng mô phỏng. Tên pháp lý, mã số thuế, giấy đăng ký và địa chỉ dưới đây không đại diện một chủ thể kinh doanh thật và không được dùng để giao dịch hoặc xuất hóa đơn thật.</div>
        <h2 class="client-section-title">1. Thông tin người bán mô phỏng</h2>
        <p><strong><?= $e($store['legal_name']) ?></strong><br>Mã số thuế: <?= $e($store['tax_code']) ?> · Giấy ĐKKD: <?= $e($store['business_registration']) ?><br>Địa chỉ: <?= $e($store['address']) ?><br>Điện thoại: <?= $e($store['phone']) ?> · Email: <?= $e($store['email']) ?> · Giờ hỗ trợ: <?= $e($store['support_hours']) ?>.</p>
        <h2 class="client-section-title">2. Thông tin hàng hóa và giá bán</h2>
        <p>Tên, hình ảnh, size, màu, tồn kho và giá được công bố trên từng trang sản phẩm. Hình ảnh trong bản đồ án có tính minh họa; màu thực tế có thể chênh lệch theo màn hình. Liên Hoa được mô hình hóa là hộ kinh doanh áp dụng phương pháp trực tiếp trên doanh thu, vì vậy giá niêm yết, đơn giá và tổng thanh toán là giá gộp khách phải trả; hóa đơn bán hàng không tách giá trước thuế, thuế suất hoặc tiền thuế GTGT.</p>
        <h2 class="client-section-title">3. Đặt hàng và hình thành giao dịch</h2>
        <p>Khách phải cung cấp tên, số điện thoại và địa chỉ chính xác, chọn sản phẩm, phương thức thanh toán và tích đồng ý điều khoản. Đơn ở trạng thái “Chờ xác nhận” mới là đề nghị mua hàng. Giao dịch được Liên Hoa chấp nhận khi đơn chuyển sang “Đã xác nhận”. Hệ thống lưu phiên bản điều khoản, thời điểm và dữ liệu kỹ thuật của lần chấp thuận để đối chiếu.</p>
        <h2 class="client-section-title">4. Thanh toán</h2>
        <p>Website hỗ trợ: (a) COD – thanh toán khi nhận hàng; (b) chuyển khoản ngân hàng qua thông tin/VietQR hiển thị sau khi đặt đơn; và (c) PayPal. Với chuyển khoản, khách ghi đúng mã đơn và admin chỉ xác nhận sau khi đối soát tiền vào. Với PayPal, hệ thống chuyển số tiền từ VND sang USD theo tỷ giá được công bố tại checkout; đơn chỉ được ghi nhận đã thanh toán sau khi máy chủ xác nhận capture thành công với PayPal. Website chưa hỗ trợ trực tiếp VISA, MasterCard hoặc MoMo.</p>
        <h2 class="client-section-title" id="delivery">5. Giao hàng</h2>
        <p>Thời gian dự kiến 1–6 ngày làm việc kể từ khi xác nhận, tùy địa chỉ và gói vận chuyển. Phí được tính tại server theo tỉnh/thành, khối lượng thực, khối lượng quy đổi và biểu phí đang áp dụng; ngưỡng miễn phí hiện từ 500.000 đến 1.500.000 đồng tùy khu vực và gói giao. Mức phí, thời gian dự kiến và điều kiện miễn phí cụ thể được hiển thị trước khi đặt hàng. Chậm trễ do thiên tai, dịch bệnh, gián đoạn vận chuyển hoặc thông tin người nhận sai sẽ được thông báo và xử lý theo tình hình thực tế.</p>
        <h2 class="client-section-title">6. Sửa và hủy đơn</h2>
        <p>Khách có thể tự hủy khi đơn còn “Chờ xác nhận”. Sau khi xác nhận, khách liên hệ hỗ trợ; Liên Hoa chỉ sửa/hủy nếu đơn chưa bàn giao vận chuyển. Nếu chuyển khoản đã được xác minh, khoản hoàn được xử lý theo trạng thái đối soát và chính sách hoàn tiền, không tự động hoàn ngay khi gửi yêu cầu.</p>
        <h2 class="client-section-title" id="returns">7. Kiểm hàng, đổi trả, hoàn tiền và bảo hành</h2>
        <p>Khách nên kiểm tra đúng sản phẩm, số lượng và tình trạng bên ngoài khi nhận. Yêu cầu đổi/trả phải gắn với đơn đã giao, đúng sản phẩm đã mua và số lượng không vượt quá số đã mua. Thời hạn gửi yêu cầu là 7 ngày từ lúc giao thành công; bảo hành mô phỏng là 180 ngày với lỗi thuộc phạm vi hỗ trợ. Sản phẩm phải còn nguyên vẹn, chưa sử dụng/giặt, có phụ kiện và bằng chứng khi cần. Không áp dụng cho hao mòn tự nhiên, sử dụng hoặc bảo quản sai hướng dẫn. Tiền hoàn dựa trên giá thực trả sau phân bổ giảm giá; chỉ hoàn sau khi duyệt, nhận lại và kiểm tra hàng. Đổi hàng chỉ hoàn tất khi hàng thay thế được trừ kho và có thông tin giao lại.</p>
        <h2 class="client-section-title">8. Khiếu nại và giải quyết tranh chấp</h2>
        <p>Gửi yêu cầu tại trang Hỗ trợ hoặc email <?= $e($store['email']) ?>, kèm mã đơn và bằng chứng. Hệ thống cấp mã yêu cầu; Liên Hoa phản hồi tiếp nhận trong 2 ngày làm việc và dự kiến giải quyết trong 7–15 ngày làm việc tùy hồ sơ. Hai bên ưu tiên thương lượng; nếu không đạt thỏa thuận, tranh chấp được giải quyết theo pháp luật Việt Nam tại cơ quan có thẩm quyền.</p>
        <h2 class="client-section-title">9. Trách nhiệm và bồi thường</h2>
        <p>Liên Hoa chịu trách nhiệm về hàng giao sai, thiếu hoặc lỗi thuộc phạm vi chính sách, tối đa theo thiệt hại trực tiếp có chứng cứ và nghĩa vụ pháp luật bắt buộc. Không loại trừ quyền lợi người tiêu dùng mà pháp luật quy định. Khách chịu trách nhiệm về tính chính xác của thông tin và không được lạm dụng website, gian lận khuyến mại hoặc xâm phạm hệ thống.</p>
        <h2 class="client-section-title">10. Chứng từ và thay đổi điều khoản</h2>
        <p>Phiếu xác nhận đơn hàng và hóa đơn bán hàng trong đồ án <strong>không thay thế hóa đơn điện tử hợp pháp</strong>. Điều khoản mới chỉ áp dụng cho đơn được tạo sau ngày hiệu lực; đơn cũ tiếp tục đối chiếu theo phiên bản đã lưu, trừ khi pháp luật bắt buộc áp dụng khác.</p>
    </article>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
