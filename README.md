# PaceUP Accessories

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
Mục tiêu hiện tại không còn là chạy local trên XAMPP nữa, mà phải hướng tới deploy lên môi trường thật để mọi người truy cập qua internet.

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
- Giao diện responsive đa thiết bị, đặc biệt mobile và tablet, cần kiểm tra lại lần cuối
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
- Chuẩn bị cấu hình để deploy thật thay vì chỉ test local
- Kiểm tra base URL, session, upload path và quyền file khi đưa lên internet

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

## Cập Nhật P0 Nền Tảng

Ngày cập nhật: 01/08/2026

### Bug Nền Tảng Đã Xử Lý
- `index.php` trước đây vừa start session, vừa autoload, vừa khai báo toàn bộ route và parse URL. Đã chuyển logic khởi tạo sang `app/Core/App.php`, còn `index.php` chỉ giữ vai trò entrypoint.
- `app/Core/App.php` đang rỗng. Đã bổ sung bootstrap chung cho `BASE_URL`, session, autoload, route registry và xử lý lỗi khởi động.
- `BASE_URL` trước đây dễ sai khi chạy ở root domain, subfolder, MAMP symlink hoặc URL có `index.php`. Đã chuẩn hóa cơ chế tự nhận diện base path và hỗ trợ override bằng biến môi trường `APP_BASE_URL`.
- Route `/account` bị khai báo hai lần, làm `User/ProfileController@index` bị ghi đè bởi controller legacy. Đã giữ route mới `User/ProfileController` để đồng bộ với các route `/account/update`, `/account/avatar`, `/account/addresses/*`.
- `Router` trước đây chỉ match exact string và silent overwrite khi route trùng. Đã normalize path, bỏ query/trailing slash, phát hiện route trùng, kiểm tra controller/action tồn tại và trả lỗi rõ hơn.
- Session trước đây được start rải rác ở nhiều nơi. Bootstrap mới start session tập trung, cấu hình cookie `HttpOnly`, `SameSite=Lax` và path theo `BASE_URL`.
- Login view trước đây tự require database và xử lý POST, tạo logic đăng nhập song song với `AuthController`. Đã đưa view về đúng vai trò hiển thị form/flash message.
- Sau login đã regenerate session id và lưu avatar vào session; logout xóa dữ liệu session/cookie rõ ràng hơn.
- Admin dashboard legacy trước đây redirect sai sang `login.php` và require DB bằng relative path. Đã đổi về route `/login` và require DB theo absolute path.
- Một số view dùng `str_starts_with`, dễ lỗi nếu môi trường PHP cũ hơn 8. Đã thêm polyfill ở bootstrap.
- `test_db.php` trước đây hardcode DB local và tự chạy `ALTER TABLE` khi mở file. Đã đổi sang đọc `config/database.php` và chỉ chạy migration khi truyền flag CLI rõ ràng.

### File Đã Sửa
- `index.php`
- `app/Core/App.php`
- `app/Core/Router.php`
- `app/Helpers/SessionHelper.php`
- `app/Controller/AuthController.php`
- `app/Controller/AdminController.php`
- `app/Views/login.php`
- `app/Views/admin.php`
- `test_db.php`
- `README.md`

### Ghi Chú Kiến Trúc
- Route mới nên được thêm trong `App::registerRoutes()` để tránh `index.php` phình to trở lại.
- View không nên tự xử lý POST, gọi database hoặc quyết định redirect nghiệp vụ. Việc đó thuộc controller/model.
- `public/views/*` và `app/Views/admin.php` vẫn là vùng legacy. Không nên mở rộng thêm logic mới ở đây nếu có controller mới tương ứng.
- Khi deploy vào subfolder, có thể set `APP_BASE_URL=/ten-thu-muc/` hoặc full URL như `https://domain.com/ten-thu-muc/`.
- Các bug business như cart, checkout, order, inventory, coupon và admin product vẫn cần được xử lý trong task feature tương ứng sau khi nền tảng đã ổn.

## Cập Nhật Admin Product Management

Ngày cập nhật: 01/08/2026

### Bug Và Logic Đã Sửa
- `Product::destroyProduct()` không còn xóa cứng trực tiếp bản ghi `product`. Hàm mới kiểm tra sản phẩm đã phát sinh trong `order_items` hay chưa; nếu đã có đơn hàng thì chặn xóa vĩnh viễn và yêu cầu admin dùng chức năng ẩn sản phẩm.
- Khi sản phẩm chưa có đơn hàng, xóa vĩnh viễn sẽ chạy theo transaction và xóa dữ liệu liên quan theo thứ tự an toàn: `inventory_logs`, `product_sales_reports`, `cart`, `wishlist`, `reviews`, `product_images`, `product_variants`, rồi mới xóa `product`.
- Bổ sung kiểm tra schema linh hoạt cho `order_items.product_id` hoặc `order_items.variant_id`, vì một số máy có thể đang lệch schema giữa product và variant.
- Chặn slug sản phẩm/danh mục bị trùng trước khi insert/update.
- Chặn phân loại trùng cùng `size + màu` trong một sản phẩm.
- Chặn sửa/xóa ảnh hoặc variant không thuộc sản phẩm đang thao tác.
- Chuẩn hóa tồn kho và phần cộng giá không nhận số âm trong admin product.

### UI/UX Đã Nâng Cấp
- Việt hóa màn danh sách sản phẩm, form thêm/sửa sản phẩm và màn danh mục.
- Nâng cấp giao diện admin theo tông trắng, đen, xám; panel rõ ràng, bảng dễ đọc, badge trạng thái đồng bộ.
- Form sản phẩm được chia thành thông tin chung, nội dung/ảnh, thư viện ảnh và phân loại size/màu.
- Variant có hiển thị chấm màu trực quan cho Đen, Đỏ, Trắng.

### File Đã Sửa
- `app/Models/Product.php`
- `app/Controller/Admin/ProductController.php`
- `app/Controller/Admin/CategoryController.php`
- `app/Services/UploadService.php`
- `app/Views/admin/_helpers.php`
- `app/Views/admin/products/index.php`
- `app/Views/admin/products/form.php`
- `app/Views/admin/categories/index.php`
- `README.md`

### Checklist Kiểm Thử
- Tạo sản phẩm mới, nhập tên tiếng Việt, kiểm tra slug tự sinh.
- Upload ảnh sản phẩm, đặt ảnh đại diện, xóa ảnh.
- Thêm, sửa, xóa variant size/màu; thử thêm trùng size/màu để kiểm tra báo lỗi.
- Ẩn/hiện sản phẩm và danh mục.
- Xóa vĩnh viễn sản phẩm chưa có đơn hàng.
- Thử xóa sản phẩm đã có đơn hàng, hệ thống phải chặn và hiển thị thông báo yêu cầu ẩn sản phẩm.

## Checklist Cho Thành Viên Mới

### Trước Khi Bắt Đầu
- [ ] Đọc hết README này
- [ ] Mở `index.php` để hiểu cấu trúc route
- [ ] Mở controller và model liên quan đến phần mình phụ trách
- [ ] Xem `Database/paceup_db.sql` để hiểu bảng dữ liệu
- [ ] Xác định đang làm theo flow controller mới hay code legacy trong `public/views/admin.php`
- [ ] Kiểm tra giao diện trên desktop, tablet và mobile
- [ ] Xác định sẵn môi trường deploy, không chỉ chạy local bằng XAMPP

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
- [ ] Giao diện responsive chạy ổn trên desktop, tablet và mobile
- [ ] Dự án chạy đúng sau khi deploy lên internet

## Phân Công 8 Thành Viên

### 1. Nền Tảng Hệ Thống
- Mục tiêu: làm cho project ổn định, ít lỗi nền tảng.
- Việc cần làm: rà route, session, base URL, autoload, lỗi chạy chung, chuẩn hóa cách đặt tên và luồng xử lý cơ bản.
- Deliverable: danh sách bug nền tảng + file đã sửa + ghi chú ngắn về kiến trúc.
- Cần đọc trước: `index.php`, `app/Core/*`, `README.md`.

### 2. Auth Và Tài Khoản
- Mục tiêu: hoàn thiện đăng ký, đăng nhập, quên mật khẩu, hồ sơ cá nhân.
- Việc cần làm: sửa login/register/logout, đổi mật khẩu, OTP reset, cập nhật profile, avatar, địa chỉ.
- Deliverable: luồng tài khoản chạy ổn + checklist test.
- Cần đọc trước: `app/Controller/AuthController.php`, `app/Controller/User/ProfileController.php`, `app/Models/UserModel.php`.

### 3. Catalog Và Sản Phẩm
- Mục tiêu: hiển thị sản phẩm rõ ràng, dễ tìm, dễ xem.
- Việc cần làm: sửa shop list, filter, sort, search, trang chi tiết sản phẩm, sản phẩm liên quan, ảnh, size, màu, tồn kho.
- Deliverable: trang shop và product hoàn chỉnh.
- Cần đọc trước: `app/Controller/ShopController.php`, `app/Controller/ProductController.php`, `app/Models/Product.php`, `app/Views/shop.php`, `app/Views/product.php`.

### 4. Cart Và Checkout
- Mục tiêu: hoàn thiện luồng mua hàng từ giỏ đến đặt đơn.
- Việc cần làm: sửa thêm/xóa/cập nhật giỏ, xử lý guest/user cart, checkout, áp coupon, kiểm tra tổng tiền, xác nhận đơn.
- Deliverable: giỏ hàng và checkout end-to-end chạy đúng.
- Cần đọc trước: `app/Controller/CartController.php`, `app/Controller/CheckoutController.php`, `app/Models/Cart.php`, `app/Models/Coupons.php`, `app/Views/cart.php`, `app/Views/checkout.php`.

### 5. Order Và Inventory
- Mục tiêu: đơn hàng và kho phải đồng bộ.
- Việc cần làm: kiểm tra tạo đơn, trạng thái đơn, hủy đơn, trừ kho, hoàn kho, log trạng thái.
- Deliverable: luồng order chuẩn + stock không lệch.
- Cần đọc trước: `app/Models/Order.php`, `Database/paceup_db.sql`.

### 6. Admin Sản Phẩm
- Mục tiêu: quản lý sản phẩm đầy đủ trong admin.
- Việc cần làm: CRUD sản phẩm, ảnh, biến thể size/màu, cập nhật giá, ẩn/hiện sản phẩm, danh mục liên quan.
- Deliverable: trang admin sản phẩm hoàn chỉnh.
- Cần đọc trước: `app/Controller/Admin/ProductController.php`, `app/Controller/Admin/CategoryController.php`, `app/Views/admin/products/*`, `app/Models/Product.php`.

### 7. Admin Đơn Hàng Và User
- Mục tiêu: quản lý vận hành shop.
- Việc cần làm: xem danh sách đơn, đổi trạng thái đơn, xem chi tiết đơn, quản lý user, khóa/mở tài khoản, theo dõi hoạt động.
- Deliverable: bộ quản trị đơn hàng và người dùng.
- Cần đọc trước: `app/Controller/Admin/UserController.php`, `app/Controller/Admin/OrderController.php`, `app/Models/UserModel.php`, `app/Models/Order.php`.

### 8. UI/UX Và QA
- Mục tiêu: giao diện dễ dùng, ổn định trên nhiều thiết bị.
- Việc cần làm: sửa layout, responsive, thông báo lỗi, empty state, test flow chính, viết tài liệu hướng dẫn chạy project.
- Deliverable: bộ test tay + tài liệu chạy project + chỉnh giao diện.
- Cần đọc trước: `app/Views/*`, `public/assets/css/style.css`, `README.md`.

## Thứ Tự Nên Làm

Nếu nhóm đang sửa project theo hướng hoàn thiện dần, thứ tự an toàn nên là:
1. Sửa nền tảng và route bị lỗi
2. Ổn định auth và account
3. Hoàn thiện catalog và tìm kiếm sản phẩm
4. Sửa cart và checkout
5. Sửa business logic của order, stock và coupon
6. Hoàn thiện admin product và order
7. Polish giao diện và kiểm thử cuối

## Gợi Ý Chia Việc Theo Phụ Thuộc

- Nên cho `Nền Tảng Hệ Thống` đi trước vì các bạn khác sẽ dựa vào route, session và cấu trúc chung.
- `Auth Và Tài Khoản`, `Catalog Và Sản Phẩm`, và `UI/UX Và QA` có thể làm song song sau khi nền tảng ổn.
- `Cart Và Checkout` nên bắt đầu sau khi catalog và product đã ổn định để dữ liệu hiển thị đúng.
- `Order Và Inventory` phụ thuộc mạnh vào checkout vì đây là nơi tạo đơn và cập nhật tồn kho.
- `Admin Sản Phẩm` và `Admin Đơn Hàng Và User` có thể làm song song, nhưng phải thống nhất schema và rule của `Order` trước.

## Kết Luận Nhanh

- Checklist hiện tại phù hợp cho một project chưa hoàn thiện.
- Phân công nên tách theo feature/role để mỗi người có đầu việc rõ ràng và không chờ nhau quá nhiều.
- Với project này, ưu tiên lớn nhất vẫn là: `responsive đa thiết bị`, `deploy internet`, `checkout đúng`, `order đúng`, và `inventory đúng`.

## Hoàn Thiện Nghiệp Vụ Thương Mại Điện Tử

Đã triển khai trục nghiệp vụ B2C: `PRODUCT → VARIANT → CART → ORDER → INVENTORY → SHIPPING → AFTER-SALE`.

- Giỏ hàng và đơn hàng lưu `variant_id`; khách phải chọn đúng size/màu. Giá được đọc lại từ database khi đặt hàng.
- Checkout kiểm tra tồn kho, tính phí ship ở server (miễn phí từ 1.000.000đ, dưới mức này 30.000đ), tính lại coupon và không tin tổng tiền từ trình duyệt.
- Vòng đời đơn: `pending → confirmed → preparing → shipping → delivered → completed`; nhánh hủy có kiểm soát. Xác nhận đơn mới trừ kho, hủy đơn đã trừ kho mới hoàn kho, mọi thay đổi lưu `inventory_logs` và `order_status_logs`.
- Bổ sung vận chuyển: đơn vị vận chuyển, mã vận đơn, phí ship, trạng thái giao, thời điểm giao.
- Bổ sung đổi trả, đổi sản phẩm, hoàn tiền và bảo hành; chỉ áp dụng sau khi đơn giao thành công.
- Review chỉ được tạo khi người dùng sở hữu `order_item` trong đơn `delivered/completed`.
- Coupon có hạn dùng, đơn tối thiểu, lượt dùng toàn hệ thống, lượt dùng mỗi tài khoản, phạm vi theo sản phẩm/danh mục và bảng `coupon_usages`.
- Marketing: banner động, sản phẩm nổi bật do admin bật `is_featured`, sản phẩm bán chạy theo số lượng đã giao thành công, SEO title/description/canonical và hàng đợi giỏ bỏ quên có retry, trạng thái gửi và link hủy nhận email.
- Checkout lưu xác nhận thỏa thuận điện tử theo phiên bản điều khoản, thời điểm, IP và user-agent; đơn cũ không có dữ liệu sẽ được hiển thị là chưa ghi nhận.
- Thông báo giao dịch cho đơn mới và từng lần đổi trạng thái được đưa vào `order_notifications`, có retry tối đa 3 lần và chạy qua SMTP.
- CSKH có form tạo ticket, mã yêu cầu, trạng thái xử lý ở admin và email xác nhận tự động qua hàng đợi `support_tickets`.

Ứng dụng tự chạy migration idempotent trong `app/Models/Database.php` để nâng CSDL cũ: thêm `variant_id`, snapshot giá/size/màu, trường vận chuyển, trạng thái giữ kho/bán/trả, bảng usage/review/hậu mãi/bằng chứng và giỏ bỏ quên. Hiện checkout chỉ hỗ trợ COD; chuyển khoản và ví điện tử sẽ tích hợp sau khi có gateway/callback/đối soát.

Migration `ecommerce_business_v8` cũng đồng bộ các sản phẩm cũ chưa có `product_variants`, sửa bộ đếm bán/giữ/trả, trạng thái thanh toán, thỏa thuận điện tử, thông báo đơn hàng và hỗ trợ khách hàng; sản phẩm không có variant sẽ không được hiển thị ngoài shop cho đến khi admin thiết lập phân loại.

Để bật email, cấu hình `PACEUP_SMTP_ENABLED=1` cùng các biến môi trường `PACEUP_SMTP_HOST`, `PACEUP_SMTP_PORT`, `PACEUP_SMTP_USERNAME`, `PACEUP_SMTP_PASSWORD`, `PACEUP_SMTP_ENCRYPTION`, `PACEUP_MAIL_FROM`, `PACEUP_MAIL_FROM_NAME` và `APP_BASE_URL` là URL đầy đủ. Có thể chạy các hàng đợi bằng cron: `*/15 * * * * /usr/bin/php /path/to/Ecommerce_Shoe_Store/scripts/send_abandoned_cart_reminders.php`; `*/15 * * * * /usr/bin/php /path/to/Ecommerce_Shoe_Store/scripts/send_order_notifications.php`; `*/15 * * * * /usr/bin/php /path/to/Ecommerce_Shoe_Store/scripts/send_customer_care_replies.php`.

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
- Giao diện phải ưu tiên responsive đa thiết bị, không chỉ đẹp trên màn hình máy tính.
- Mục tiêu cuối là đưa project lên hosting hoặc server thật để truy cập được qua internet.
