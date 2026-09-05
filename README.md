# Liên Hoa — đồ án website thương mại điện tử

Liên Hoa là website PHP/MySQL mô phỏng cửa hàng đồ lam, pháp phục và vật dụng đi chùa. Toàn bộ tên pháp nhân, mã số thuế, nhà cung cấp, hóa đơn bán hàng và giao dịch trong dự án đều là dữ liệu phục vụ học tập, không phải thông tin kinh doanh hoặc hóa đơn hợp pháp.

## Chức năng chính

- Danh mục, tìm kiếm, chi tiết sản phẩm, biến thể, SKU/barcode và tồn kho.
- Giỏ hàng, mã giảm giá, checkout, địa chỉ đã lưu và phí giao hàng tính tại server theo tỉnh, khối lượng quy đổi và gói vận chuyển.
- COD, chuyển khoản VietQR và PayPal Orders v2; admin xác minh chuyển khoản, PayPal có capture, webhook xác minh chữ ký và nút đối soát dự phòng.
- Vòng đời đơn hàng, giữ/trả lượt coupon, giữ/trừ/hoàn kho, lịch sử trạng thái, thông báo và tra cứu đơn bằng mã đơn kèm thông tin liên hệ.
- Đổi trả/hoàn tiền một phần, phân bổ giảm giá, hoàn tiền cộng dồn và luồng giao sản phẩm thay thế.
- Đánh giá chỉ dành cho sản phẩm đã mua; wishlist, hồ sơ, sổ địa chỉ và ticket hỗ trợ của khách.
- Nhà cung cấp, đơn nhập nhiều dòng, nhận hàng từng phần, giá vốn bình quân, lịch sử trả công nợ và lợi nhuận gộp đã trừ hàng hoàn.
- Hóa đơn bán hàng cho hộ kinh doanh theo phương pháp trực tiếp trên doanh thu: giá gộp, dòng hàng/đơn vị tính/số lượng/giảm giá, phát hành, điều chỉnh, hủy và sổ doanh thu theo tháng.
- Newsletter có double opt-in, phân nhóm, chiến dịch, gửi mail, open rate và click rate.
- CSRF cho request thay đổi dữ liệu, khóa tài khoản, security headers và chặn truy cập trực tiếp file nội bộ.

## Yêu cầu môi trường

- PHP 8.2 trở lên, có PDO MySQL và `mbstring`.
- MySQL 8.0 hoặc MariaDB tương thích.
- MAMP mẫu dùng MySQL port `8889`, user/password `root/root` và socket `/Applications/MAMP/tmp/mysql/mysql.sock`.

Sao chép `.env.example` thành `.env` rồi sửa các biến `DB_*` theo máy đang chạy. `config/database.php` chỉ đọc biến môi trường; không ghi tài khoản database trực tiếp vào source.

## Cài đặt database

Chỉ dùng một file duy nhất: `Database/paceup_db.sql`.

1. Bật Apache và MySQL trong MAMP.
2. Mở `http://localhost:8888/phpMyAdmin5/`.
3. Nếu đã có database `paceup_db` cũ, chọn database đó, vào **Operations** và chọn **Drop the database**.
4. Vào tab **Import**, chọn `Database/paceup_db.sql`, giữ charset `utf-8` rồi bấm **Import**.

Các script cũ `seed.php`, `migrate_auth.php`, `check*.php`, `debug*.php`, `query_orders.php`, `patch.js` và `test_db.php` đã được loại khỏi bản nộp. Không chạy file PHP chẩn đoán trực tiếp qua trình duyệt.

Database có sẵn 156 sản phẩm, 585 biến thể, biểu phí giao hàng, nhà cung cấp và dữ liệu minh họa. Không seed đơn hàng/giao dịch để tránh làm sai báo cáo.

## Chạy local

Có thể trỏ Apache document root vào thư mục dự án và mở `http://127.0.0.1:8888/` theo cấu hình MAMP mẫu. Hoặc đổi `APP_PUBLIC_URL` trong `.env` sang `http://127.0.0.1:18765` rồi chạy PHP development server:

```bash
/Applications/MAMP/bin/php/php8.3.9/bin/php -S 127.0.0.1:18765 router.php
```

Sau đó mở `http://127.0.0.1:18765/`.

Tài khoản demo:

- Admin: `admin@lienhoa.local` / `Admin@12345`
- Khách hàng: `customer@lienhoa.local` / `Customer@12345`

Đổi mật khẩu demo trước khi đưa dự án lên máy chủ có thể truy cập từ Internet.

## Quy tắc nghiệp vụ đang áp dụng

- Giá, giảm giá, phí ship và tổng thanh toán luôn được tính lại ở server.
- Đơn mới giữ tồn kho ngay trong transaction tạo đơn; PayPal giữ 30 phút, chuyển khoản/COD giữ 24 giờ. Đơn chưa thanh toán quá hạn tự hủy và trả kho/coupon; admin xác nhận mới chuyển lượng giữ thành hàng đã bán.
- Coupon được giữ theo đơn và được trả lượt khi đơn bị hủy; lượt sử dụng không được tin từ client.
- Đơn chuyển khoản phải được admin ghi nhận mã giao dịch/đã nhận tiền trước khi chuyển sang giao hàng.
- Mỗi dòng đơn lưu giá bán, phần giảm giá và giá vốn tại thời điểm mua để hoàn tiền và lợi nhuận không thay đổi khi giá hiện tại đổi.
- Đơn nhập có nhiều dòng; mỗi lần nhận chỉ ghi số thực nhận, tăng kho, tính lại giá vốn và tăng công nợ tương ứng. Mỗi lần trả công nợ có một dòng lịch sử riêng.
- Chỉ đơn đã giao/hoàn thành và đã thanh toán mới được phát hành hóa đơn bán hàng mô phỏng.
- Hủy chứng từ gốc sẽ hủy các chứng từ điều chỉnh liên quan; mọi phát hành, điều chỉnh và hủy đều lưu sự kiện.
- Email newsletter chỉ được đưa vào chiến dịch sau khi chủ email bấm liên kết xác nhận.

## Hóa đơn bán hàng mô phỏng

Cấu hình tại `config/store.php` mô hình hóa người bán là hộ kinh doanh nộp thuế theo phương pháp trực tiếp trên doanh thu. Đơn giá và thành tiền trên hóa đơn là giá bán gộp; hệ thống không tách giá trước VAT, thuế suất hoặc tiền VAT.

Trang **Admin → Hóa đơn bán hàng** cho phép phát hành mẫu số/ký hiệu/số hóa đơn, lập điều chỉnh, hủy và xem sổ doanh thu hóa đơn. Đây là mô hình nghiệp vụ cho đồ án; không ký số, không cấp mã cơ quan thuế và không thay thế phần mềm hóa đơn điện tử được pháp luật công nhận.

## PayPal, email và newsletter

Sao chép `.env.example` thành `.env`, sau đó điền khóa riêng vào `.env`; không ghi Client Secret hoặc mật khẩu SMTP trực tiếp vào source. PayPal Sandbox chỉ dùng tiền thử nghiệm. Khi triển khai thật, đổi sang `PAYPAL_MODE=live`, dùng bộ Live Client ID/Secret và đặt `APP_PUBLIC_URL` là địa chỉ HTTPS công khai.

```text
APP_ENV=local
APP_PUBLIC_URL=http://127.0.0.1:18765
FORCE_HTTPS=false
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
PAYPAL_WEBHOOK_ID=...
PAYPAL_LIVE_CREDENTIALS_ROTATED=false
PAYPAL_VND_PER_USD=26085.01
APP_SECURITY_KEY=chuoi-ngau-nhien-dai

SMTP_ENABLED=true
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=...
SMTP_PASSWORD=...
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=...
SMTP_FROM_NAME="Lien Hoa Shop"
SMTP_CREDENTIALS_ROTATED=false
```

PayPal không hỗ trợ VND trực tiếp nên checkout quy đổi sang USD theo `PAYPAL_VND_PER_USD` và lưu cả số tiền USD lẫn tỷ giá cùng giao dịch để đối soát. Trong PayPal Developer Dashboard, tạo webhook trỏ đến `APP_PUBLIC_URL/paypal/webhook`, đăng ký ít nhất `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.DENIED` và `PAYMENT.CAPTURE.REFUNDED`, rồi điền Webhook ID vào `.env`. Chế độ Live chỉ được bật khi đồng thời có URL HTTPS công khai, `FORCE_HTTPS=true`, Webhook ID và `PAYPAL_LIVE_CREDENTIALS_ROTATED=true`.

Nếu Client Secret PayPal hoặc Gmail App Password từng xuất hiện trong ảnh, chat, commit hay log: thu hồi khóa cũ trong trang quản trị nhà cung cấp, tạo khóa mới, thay giá trị trong `.env`, sau đó mới đặt cờ `*_CREDENTIALS_ROTATED=true`. Cờ xác nhận không tự thu hồi khóa; nó chỉ ngăn bản production vô tình dùng lại khóa đã lộ. Trước khi tự hủy đơn PayPal hết hạn, tác vụ nền sẽ đối soát lại PayPal để không hủy nhầm giao dịch đã thanh toán nhưng khách đóng tab/mất callback. SMTP mặc định tắt nếu thiếu biến môi trường; khi đó đăng ký tài khoản demo vẫn dùng được theo chính sách local và newsletter chỉ ở trạng thái chờ.

Chạy `scripts/send_order_notifications.php` bằng cron mỗi 5 phút. Tác vụ tổng hợp này:

- hủy đơn chưa thanh toán quá hạn và trả kho/coupon;
- đối soát PayPal trước khi hủy;
- gửi thông báo đơn, email nhắc giỏ hàng và xác nhận tiếp nhận CSKH;
- xử lý các chiến dịch newsletter đã được admin xếp hàng.

Ví dụ cron trên macOS/MAMP (đổi đường dẫn nếu dự án nằm nơi khác):

```cron
*/5 * * * * cd /Users/tanhiep/Desktop/Lam_do_store && /Applications/MAMP/bin/php/php8.3.9/bin/php scripts/send_order_notifications.php >> /tmp/lienhoa-cron.log 2>&1
```

Không chạy đồng thời ba script hàng đợi riêng nếu đã dùng tác vụ tổng hợp trên, tránh gửi trùng. Các script riêng chỉ phục vụ chạy thủ công khi cần chẩn đoán một hàng đợi cụ thể.

## Cấu trúc chính

- `index.php`, `router.php`: entrypoint và router cho môi trường local.
- `app/Core/App.php`: route, session, CSRF và middleware.
- `app/Controller`: xử lý request phía khách và admin.
- `app/Models`: dữ liệu và quy tắc nghiệp vụ.
- `app/Services`: email, vận chuyển, upload và thông báo.
- `app/Views`: giao diện.
- `Database/paceup_db.sql`: schema/dữ liệu import duy nhất.
- `config/store.php`: thông tin cửa hàng, thuế và tài khoản ngân hàng mô phỏng.
- `config/paypal.php`, `config/mail.php`: đọc cấu hình bí mật từ `.env` (không chứa khóa trực tiếp).

## Giới hạn khi triển khai thật

Dự án hoàn chỉnh ở mức mô phỏng nghiệp vụ môn học. PayPal có luồng kỹ thuật Sandbox/Live nhưng muốn nhận tiền thật vẫn phải dùng tài khoản doanh nghiệp đã được PayPal chấp thuận, bộ khóa Live mới, HTTPS công khai và quy trình đối soát/hoàn tiền. Catalog cũ được gắn nhãn nguồn nội bộ chưa xác minh thay vì bịa URL/quyền sử dụng; trước khi kinh doanh phải thay bằng ảnh tự sở hữu hoặc có văn bản cho phép. Ngoài ra phải thay dữ liệu pháp nhân và thông tin ngân hàng; tích hợp nhà vận chuyển/hóa đơn điện tử thật; cấu hình SMTP, backup, giám sát, chống spam và quy trình kế toán–thuế theo quy định hiện hành.
# Chạy migration (chỉ từ Terminal, không chạy trong request web)

Sau khi import database hoặc cập nhật source, chạy một lần lệnh sau tại thư mục dự án. Lệnh dừng ngay nếu migration lỗi:

```bash
RUN_SCHEMA_MIGRATIONS=1 /Applications/MAMP/bin/php/php8.3.9/bin/php -r "require 'app/Core/App.php'; App\\Core\\App::bootstrap(); App\\Models\\Database::getInstance();"
```
