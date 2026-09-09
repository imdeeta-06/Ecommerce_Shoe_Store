# Bản sửa luồng mua hàng và doanh thu — 06/09/2026

Bản sửa áp dụng cho source hiện tại trên nhánh `codex/hiep-local-backup-20260905`, nền commit `7c02a53`. Không áp dụng riêng gói file này lên nhánh `merge-dev` cũ vì hai bản khác nhau về schema và chức năng.

## Quy tắc doanh thu

- Đơn mới, đã xác nhận, đang chuẩn bị, đang giao: chưa tính doanh thu, kể cả đã chuyển khoản/PayPal.
- COD đang giao: quản trị viên chọn **Giao thành công**, tích **Người nhận đã nhận hàng và đã trả đủ tiền COD**, rồi cập nhật. Server kiểm tra lại xác nhận; bỏ tích sẽ không đổi trạng thái.
- Đơn trả trước: phải có thanh toán đã xác minh trước các bước xử lý/giao hàng. Không tự đánh dấu chuyển khoản/PayPal đã thanh toán bằng thao tác giao thành công.
- Đơn đã giao và đã thanh toán mới tính doanh thu. Cập nhật lại Đã giao hoặc chuyển Hoàn thành không cộng lần hai.
- Đơn hủy/giao thất bại không tính doanh thu. Tiền hoàn thực tế làm giảm giá trị đơn gốc; yêu cầu hoàn đang chờ chưa giảm doanh thu.
- Tổng doanh thu, biểu đồ và báo cáo thuế dùng cùng điều kiện, cùng số tiền hoàn tích lũy và cùng ngày ghi nhận: thời điểm muộn hơn giữa giao hàng và thu tiền. Hoàn thành sau đó không chuyển doanh thu sang tháng khác.
- Doanh thu vận hành gồm tổng tiền đơn sau giảm giá và phí giao hàng, trừ hoàn tiền. Báo cáo lợi nhuận hàng hóa không gồm phí giao hàng.
- Nếu một đơn có nhiều lần thử thanh toán, báo cáo chỉ tính đơn một lần. `refunded_amount` là snapshot hoàn tiền tích lũy của đơn, không cộng tất cả snapshot với nhau.

## Những lỗi đã sửa

1. Dashboard tính đơn đã giao nhưng không kiểm tra thanh toán; tổng, biểu đồ và báo cáo thuế lệch nhau về thời điểm/hoàn tiền.
2. Báo cáo thuế làm mất doanh thu của đơn đang chờ hoàn dù chưa hoàn tiền.
3. Thiếu xác nhận đã thu tiền COD; thiếu chặn thanh toán không hợp lệ ở các bước giao hàng.
4. Route gửi đánh giá ngay tại trang sản phẩm thiếu `storeDirect`, gây lỗi 500.
5. Thêm giỏ hàng dùng tồn kho vật lý, bỏ qua hàng đã giữ cho đơn khác.
6. Truy cập sản phẩm không tồn tại cố tạo biến thể, gây lỗi khóa ngoại thay vì 404.
7. Xóa địa chỉ mặc định truy vấn cột `created_at` không tồn tại.
8. Một số controller admin chỉ tin quyền lưu trong session, không kiểm tra tài khoản bị khóa/thu hồi quyền.
9. Menu Khách hàng/Cài đặt trỏ về dashboard cũ: bổ sung danh sách/tìm kiếm/phân trang/khóa mở khách hàng và trang xem thông tin cấu hình. Giữ chuyển hướng URL cũ.
10. Tạo biến thể ở trang kho thiếu kiểm tra sản phẩm, trùng size/màu và xử lý lỗi giao dịch.
11. Nhập mã hoàn tiền đã dùng cho yêu cầu khác báo thành công dù chưa hoàn; nay báo lỗi và giữ nguyên dữ liệu.
12. Kiểm tra số lượng yêu cầu đổi trả trước transaction có thể bị vượt khi gửi đồng thời; nay khóa dòng hàng trước kiểm tra và tạo yêu cầu trong cùng transaction.
13. Giao hàng thay thế cho phép dùng biến thể đã ẩn; nay chặn.
14. Thông tin đổi trả 30 ngày/miễn phí giao hàng cố định tại trang sản phẩm không khớp chính sách/backend.
15. `trim(null)` tạo cảnh báo PHP; `.env` ghi đè biến môi trường triển khai/kiểm thử; PHP và MySQL không thống nhất múi giờ Việt Nam.

## Kiểm tra đã thực hiện

- 151 assertions tích hợp trên MySQL 8 riêng, import schema hiện tại; database kiểm thử tự xóa sau mỗi lượt.
- 150 assertions HTTP trên server local dùng database riêng, gồm đăng ký/đăng nhập, CSRF, quyền truy cập, giỏ hàng, wishlist, checkout, admin giao COD, đánh giá mua hàng và khóa/mở khách.
- 119 routes đều có controller/action công khai tương ứng.
- Kiểm tra riêng 4 phiên admin đang mở bị từ chối ngay sau khi khóa tài khoản.
- Duyệt giao diện bằng Chrome: cửa hàng, chi tiết/chọn size/thêm giỏ, dashboard và danh sách khách hàng.
- Tình huống nghiệp vụ: COD, chuyển khoản, PayPal capture mô phỏng, hủy, hết hạn, coupon hoàn lượt, giao thất bại/trả kho, trả hàng/hoàn một phần, đổi hàng/giao thay thế, hóa đơn/phát hành lặp/điều chỉnh/hủy, nhập từng phần/công nợ/trả vượt mức, địa chỉ mặc định, newsletter xác nhận/hủy và ticket hỗ trợ.

Lệnh chạy lại:

```bash
/Applications/MAMP/bin/php/php8.3.9/bin/php tests/routes.php
TEST_MYSQL_DSN='mysql:unix_socket=/duong-dan/mysql.sock' TEST_MYSQL_USER='test_user' TEST_MYSQL_PASSWORD='test_password' /Applications/MAMP/bin/php/php8.3.9/bin/php tests/commerce.php
TEST_BASE_URL='http://127.0.0.1:18766' python3 tests/http_smoke.py
```

Chỉ chạy HTTP suite trên server/database thử nghiệm có SMTP tắt: suite tạo khách hàng và đơn COD mô phỏng. Test MySQL yêu cầu tài khoản được tạo database; tự tạo tên ngẫu nhiên và chỉ xóa database do chính suite tạo. Không chạy test lên dữ liệu thật.

## Cập nhật hosting

1. Sao lưu source đang chạy và export database hiện tại.
2. Xác nhận hosting đang dùng cùng nền source/schema của bản hiện tại. Nếu còn là `merge-dev` cũ, cần cập nhật toàn bộ bản hiện tại và các migration đã hướng dẫn trong README trước; không chỉ chép gói vá.
3. Gói `lienhoa_flow_fixes_20260906.zip` chứa file thay đổi và file mới, giữ nguyên đường dẫn. Chép vào thư mục gốc website, bật hiển thị file ẩn để cập nhật cả `.htaccess`. Gói không chứa `.env`, mật khẩu, ảnh upload hoặc SQL reset.
4. Bản vá này không thêm cột/bảng và không yêu cầu import SQL trên database đã đúng schema hiện tại. Giữ nguyên `.env` và dữ liệu của hosting.
5. Tải lại trang admin. Kiểm tra một đơn thử: trước giao doanh thu không tăng; thiếu xác nhận COD phải bị chặn; giao và thu tiền tăng đúng một lần; chuyển Hoàn thành không tăng tiếp.
6. Kiểm tra tổng/biểu đồ/báo cáo thuế cùng kỳ; một khoản hoàn đang chờ không làm mất doanh thu, khoản hoàn hoàn tất mới được trừ.

## Phần chưa xác minh trên dịch vụ thật

Chưa upload/deploy hoặc truy cập database hosting; chỉ mở trang chủ công khai để kiểm tra khả năng truy cập. Chưa thực hiện giao dịch PayPal thật, webhook từ PayPal, SMTP gửi email, cron hosting, upload ảnh thực tế và kiểm thử tải lớn/concurrency bằng nhiều tiến trình. Capture PayPal trong suite là dữ liệu mô phỏng gọi model, không chứng minh kết nối provider hoạt động.

Timestamp lịch sử không có timezone trong schema được giữ nguyên, không tự chuyển đổi. Nếu hosting từng lưu theo UTC, cần xác minh múi giờ dữ liệu lịch sử trước khi điều chỉnh; bản vá chỉ thống nhất thời gian cho các request/kết nối mới. Những đơn cũ đã bị đánh dấu giao/thu tiền sai phải được đối soát với chứng từ; không tự đoán hoặc sửa lịch sử thanh toán.

Các kết quả trên là phạm vi kiểm thử cụ thể, không phải cam kết mọi lỗi trên toàn website đã được loại bỏ.
