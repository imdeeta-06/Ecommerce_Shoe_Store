# Hướng dẫn cập nhật VAT lên InfinityFree

Gói cập nhật không chứa `.env`, khóa PayPal, mật khẩu SMTP hoặc mật khẩu database.

## 1. Sao lưu trước khi thay đổi

1. Trong File Manager, mở `htdocs` và tải một bản sao lưu mã nguồn hiện tại về máy.
2. Trong InfinityFree Control Panel, mở `phpMyAdmin` của đúng database đang dùng.
3. Chọn database ở cột trái, vào **Export**, chọn **Quick** và định dạng **SQL**, sau đó bấm **Export**.
4. Giữ cả hai bản sao lưu cho đến khi website đã kiểm tra xong.

Không xóa `htdocs`, không Drop database và không import lại `Database/paceup_db.sql` trên website đang chạy.

## 2. Cập nhật database VAT

1. Tronimporrg phpMyAdmin, chọn đúng database có các bảng `product`, `orders`, `order_items` và `user`.
2. Mở tab **Import**.
3. Chọn file `Database/migrations/20260903_vat_sales.sql` từ máy Mac.
4. Giữ charset `utf-8`, bấm **Import/Go** và chờ thông báo thành công.
5. Kiểm tra bảng `product` có cột `tax_category`, `tax_rate`; bảng `orders` có `taxable_amount`, `tax_amount` và `shipping_tax_category`.

Migration chỉ thêm trường VAT và tách ngược thuế từ giá cũ đã gồm thuế. Nó không xóa tài khoản, sản phẩm, đơn hàng hoặc giao dịch và không đổi tổng tiền khách đã trả.

## 3. Tải gói mã nguồn lên

1. Trở lại File Manager và bấm đúp mở thư mục `htdocs`.
2. Chọn **Upload & Unzip** ở thanh bên trái.
3. Chọn file `lienhoa_vat_update_20260903.zip`.
4. Chọn giải nén ngay trong `htdocs`, không tạo thêm thư mục bao ngoài.
5. Khi được hỏi, chọn ghi đè/replace các file trùng tên và giữ nguyên những file khác.
6. Sau khi giải nén, phải thấy `htdocs/app/Services/TaxService.php` và `htdocs/config/tax.php`. Nếu thấy đường dẫn `htdocs/lienhoa_vat_update_20260903/app/...` thì đã giải nén sai cấp; cần di chuyển nội dung bên trong lên thẳng `htdocs`.
7. Xóa riêng file ZIP trên hosting sau khi giải nén thành công. Không xóa `.env`.

## 4. Kiểm tra sau cập nhật

1. Mở website bằng cửa sổ ẩn danh và tải lại trang.
2. Vào **Admin → Sản phẩm → Sửa**, kiểm tra mục **Phân loại thuế GTGT**.
3. Cho một sản phẩm vào giỏ, mở checkout và kiểm tra có các dòng **Tiền trước thuế** và **Thuế GTGT (đã gồm trong giá)**.
4. Tổng thanh toán phải bằng đúng tổng cũ; VAT chỉ được tách ra, không cộng thêm lần nữa.
5. Tạo một đơn Sandbox PayPal nhỏ và kiểm tra số tiền quy đổi USD không thay đổi ngoài tỷ giá đang cấu hình.
6. Chỉ phát hành hóa đơn GTGT mô phỏng sau khi đơn ở trạng thái đã giao/hoàn thành và thanh toán đã được xác nhận.

Nếu xuất hiện lỗi 500 ngay sau cập nhật, không xóa dữ liệu. Kiểm tra trước xem migration VAT đã import thành công và đúng database chưa; sau đó khôi phục mã nguồn từ bản sao lưu nếu cần.
