# Ecommerce_Shoe_Store
# Accessories

`PaceUP Accessories` là một dự án web thương mại điện tử dùng `PHP` và `MySQL`, phục vụ bán giày và đồ thể thao.
Dự án đã có xem sản phẩm, giỏ hàng, thanh toán, tài khoản người dùng, trang quản trị, mã giảm giá, đơn hàng, kho hàng và wishlist.

Tuy nhiên, dự án vẫn chưa hoàn thiện. README này được viết để giúp các thành viên mới nhanh chóng hiểu:
- dự án đang làm gì
- hiện tại đã có những gì
- còn bug nào cần sửa
- business logic nào phải tuân thủ
- chức năng nào còn thiếu hoặc chưa hợp lý
- cần bắt đầu từ đâu khi tham gia dự án

## Bối Cảnh Dự Án

Dự án này mô phỏng một cửa hàng bán giày và đồ thể thao online.
Luồng mong đợi của hệ thống là:
1. Khách truy cập xem danh sách sản phẩm.
2. Người dùng đăng ký hoặc đăng nhập.
3. Người dùng thêm sản phẩm vào giỏ hàng và tiến hành thanh toán.
4. Hệ thống tạo đơn hàng với thông tin giao hàng và áp dụng mã giảm giá nếu có.
5. Quản trị viên duyệt đơn, cập nhật trạng thái, quản lý sản phẩm, kho hàng và mã giảm giá.

Kiến trúc dự án đi theo mô hình đơn giản kiểu MVC:
- `Controller` nhận request và xử lý logic
- `Model` làm việc với dữ liệu trong database
- `View` hiển thị giao diện
- `Router` điều hướng URL tới đúng controller

## Trạng Thái Hiện Tại

Dự án đã có nhiều phần lõi quan trọng, nhưng vẫn còn nhiều chỗ cần sửa và hoàn thiện.

### Các Phần Đã Có
- Danh sách sản phẩm và trang chi tiết sản phẩm
- Giỏ hàng
- Thanh toán
- Đăng ký, đăng nhập, đăng xuất
- Quên mật khẩu và đặt lại mật khẩu
- Trang tài khoản, ảnh đại diện, địa chỉ nhận hàng
- Wishlist
- Quản lý sản phẩm ở admin
- Quản lý mã giảm giá ở admin
- Model đơn hàng có trạng thái và xử lý kho
- Database có sẵn các bảng cho sản phẩm, đơn hàng, biến thể, coupon, user và log

### Các Phần Còn Thiếu Hoặc Chưa Ổn
- Một số luồng vẫn chưa đồng bộ giữa controller, model, view và database
- Một số route đã có nhưng chưa được nối sạch sẽ
- Có chỗ vẫn phụ thuộc quá nhiều vào dữ liệu do client gửi lên
- Một số bảng trong database đã có sẵn nhưng chưa có feature hoàn chỉnh
- Khu vực admin vẫn còn lẫn giữa code cũ và code mới

## Business Logic Quan Trọng

Đây là các quy tắc nghiệp vụ cần giữ đúng:

- Một sản phẩm có thể có nhiều biến thể như size và màu
- Mỗi item trong giỏ phải luôn gắn với sản phẩm hoặc biến thể hợp lệ
- Khi thanh toán, tổng tiền phải được tính ở server, không được tin hoàn toàn vào dữ liệu từ trình duyệt
- Mã giảm giá phải hợp lệ theo ngày áp dụng, số lượt dùng và giá trị đơn tối thiểu
- Đơn hàng bắt đầu ở trạng thái `pending`
- Chỉ được trừ kho khi đơn hàng được xác nhận
- Nếu đơn bị hủy đúng điều kiện thì phải hoàn lại kho chính xác
- Người dùng chỉ được sửa giỏ, địa chỉ, hồ sơ và đơn của chính mình
- Admin chỉ được truy cập các chức năng quản trị khi đã đăng nhập đúng quyền

## Bug Và Rủi Ro Đang Có

### Bug Ưu Tiên Cao
- Chức năng cập nhật và xóa giỏ hàng chưa kiểm tra thật chặt quyền sở hữu item
- Checkout vẫn nhận `discount` và `coupon_id` từ client, nên nếu server không tính lại hoàn toàn thì có thể bị sửa request để giảm tiền sai
- Một số luồng đơn hàng và kho còn phụ thuộc vào trigger hoặc logic local trong database
- Phần quản lý đơn ở admin bị tách giữa controller mới và file legacy `public/views/admin.php`
- Một số luồng tài khoản và địa chỉ đang dùng cách xử lý khác nhau, dễ gây hành vi không đồng nhất

### Rủi Ro Logic
- Trừ kho và hoàn kho có thể bị lệch nếu trigger không chạy hoặc schema thay đổi
- Mã giảm giá phải tăng lượt dùng đúng thời điểm sau khi đặt hàng thành công
- Cần đồng bộ giỏ hàng guest và giỏ hàng của user khi đăng nhập
- Xử lý biến thể sản phẩm phải thống nhất giữa giỏ hàng, checkout, đơn hàng và admin

### Rủi Ro Giao Diện
- Một số trang còn thiếu trạng thái rỗng rõ ràng
- Thông báo lỗi chưa thống nhất
- Giao diện mobile cần kiểm tra lại lần cuối
- Một số màn hình admin chạy được nhưng chưa đẹp hoặc chưa dễ dùng

## Chức Năng Còn Thiếu Hoặc Chưa Hoàn Chỉnh

### Phía Khách Hàng
- Tìm kiếm và lọc sản phẩm nâng cao
- Phân trang danh sách sản phẩm
- Gợi ý sản phẩm liên quan tốt hơn
- Sản phẩm đã xem gần đây
- Flow đánh giá / chấm sao sau khi mua
- Trang chi tiết đơn hàng cho khách
- Chức năng hủy đơn từ phía khách theo điều kiện hợp lệ

### Checkout
- Tích hợp cổng thanh toán thật
- Theo dõi trạng thái thanh toán rõ ràng
- Tính phí ship
- Chọn địa chỉ đã lưu ngay trong checkout
- Kiểm tra và phản hồi coupon tốt hơn ở phía server

### Admin
- Bộ quản lý đơn hàng bằng controller riêng, rõ ràng hơn
- Quản lý user, khóa/mở tài khoản, đổi role
- Duyệt / ẩn review
- Trang thống kê doanh thu, báo cáo
- Tách sạch logic backend khỏi view

### Nền Tảng
- Chống CSRF cho các form quan trọng
- Validate input chặt hơn ở mọi form
- Bảo mật upload file tốt hơn
- Có test cho các luồng quan trọng
- Chuẩn hóa error handling và logging

## Sơ Đồ File Chính

### Điểm Vào Và Router
- `index.php` khai báo route và điều hướng chính
- `app/Core/Router.php` xử lý match URL
- `app/Core/App.php` khởi tạo ứng dụng

### Controllers
- `app/Controller/AuthController.php` xử lý đăng nhập, đăng ký, đăng xuất, quên mật khẩu
- `app/Controller/CartController.php` xử lý giỏ hàng
- `app/Controller/CheckoutController.php` xử lý checkout và coupon
- `app/Controller/ShopController.php` xử lý danh sách sản phẩm
- `app/Controller/ProductController.php` xử lý chi tiết sản phẩm
- `app/Controller/WishlistController.php` xử lý wishlist
- `app/Controller/User/ProfileController.php` xử lý hồ sơ và địa chỉ
- `app/Controller/Admin/*` xử lý các chức năng admin

### Models
- `app/Models/Product.php` xử lý sản phẩm, danh mục, biến thể, ảnh và tồn kho
- `app/Models/Cart.php` xử lý dữ liệu giỏ hàng và đồng bộ guest/user
- `app/Models/Order.php` xử lý đơn hàng, item đơn, thanh toán và log trạng thái
- `app/Models/Coupons.php` xử lý validate mã giảm giá
- `app/Models/UserModel.php` xử lý user, địa chỉ và OTP reset
- `app/Models/Wishlist.php` xử lý wishlist

### Views
- `app/Views/*` chứa giao diện public và admin
- `public/views/*` chứa một số view legacy hoặc view phụ
- `public/assets/css/style.css` chứa CSS chính

### Database
- `Database/paceup_db.sql` chứa schema và dữ liệu mẫu

## Checklist Cho Thành Viên Mới

### Trước Khi Bắt Đầu
- [ ] Đọc hết README này
- [ ] Mở `index.php` để hiểu cấu trúc route
- [ ] Mở controller và model liên quan đến phần mình phụ trách
- [ ] Xem `Database/paceup_db.sql` để hiểu bảng dữ liệu
- [ ] Xác định đang làm theo flow controller mới hay code legacy trong `public/views/admin.php`

### Checklist Chất Lượng Code
- [ ] Business logic phải được xử lý ở server
- [ ] Không tin hoàn toàn vào tổng tiền hoặc discount do browser gửi lên
- [ ] Phải kiểm tra quyền sở hữu trước khi sửa hoặc xóa dữ liệu của user
- [ ] Luôn giữ đồng bộ giữa product, variant, cart, order và inventory
- [ ] Dùng flash message hoặc JSON response rõ ràng
- [ ] Tránh trộn code cũ và code mới nếu không cần thiết

### Checklist Kiểm Thử
- [ ] Đăng nhập và đăng ký vẫn hoạt động
- [ ] Thêm, cập nhật, xóa giỏ hàng vẫn hoạt động
- [ ] Checkout tạo đơn đúng
- [ ] Coupon hoạt động đúng cho cả trường hợp hợp lệ và không hợp lệ
- [ ] Admin cập nhật trạng thái đơn được
- [ ] Kho thay đổi đúng khi đơn đổi trạng thái
- [ ] Cập nhật profile và địa chỉ vẫn chạy
- [ ] Giao diện mobile vẫn dùng được

## Thứ Tự Nên Làm

Nếu nhóm đang sửa project theo hướng hoàn thiện dần, thứ tự an toàn nên là:
1. Sửa nền tảng và route bị lỗi
2. Ổn định auth và account
3. Hoàn thiện catalog và tìm kiếm sản phẩm
4. Sửa cart và checkout
5. Sửa business logic của order, stock và coupon
6. Hoàn thiện admin product và order
7. Polish giao diện và kiểm thử cuối

## Cấu Trúc Thư Mục

```text
/app
  /Controller   Xử lý request cho public và admin
  /Core         Khởi tạo app và router
  /Helpers      Các hàm hỗ trợ session và tiện ích
  /Middleware   Kiểm tra đăng nhập và phân quyền
  /Models       Làm việc với database và business logic
  /Services     Các dịch vụ dùng chung như upload và logging
  /Views        Giao diện public và admin
/config         Cấu hình database
/Database       Schema SQL và dữ liệu mẫu
/public         Tài nguyên public và file upload
```

## Ghi Chú Cho Team
- Khi sửa bug, cần kiểm tra cả PHP lẫn SQL vì nhiều vấn đề đến từ việc code và schema không khớp nhau.
- Nếu thêm tính năng mới, hãy đảm bảo route, controller, model, view và database đều hỗ trợ đầy đủ.
- Nếu refactor một feature, luôn để ý luồng cũ để không làm hỏng cart, checkout hoặc admin.

