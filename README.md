AI MILK TEA - Thư viện công thức trà sữa chuẩn vị

Đồ án môn học Web Development / Ứng dụng Web.  
Chủ đề: Xây dựng hệ thống tra cứu công thức pha chế trà sữa thông minh, hỗ trợ phân quyền người dùng và tích hợp AI.

---

1. Giới thiệu dự án
- AI Milk Tea là nền tảng trực tuyến giúp người dùng dễ dàng tra cứu nguyên liệu, công thức pha chế trà sữa độc quyền.
- Hệ thống hỗ trợ phân quyền rõ ràng giữa Quản trị viên (Admin) và Người dùng (User), kết hợp giao diện hiện đại, tìm kiếm tức thì bằng AJAX và hiệu ứng thông báo trực quan.

2. Công nghệ sử dụng
- Front-end: HTML5, CSS3, JavaScript (AJAX, DOM manipulation).
- Back-end: PHP (PDO kết nối cơ sở dữ liệu an toàn).
- Database: MySQL / phpMyAdmin.
- Môi trường chạy: WampServer64 / XAMPP (PHP 8.x).

3. Hướng dẫn cài đặt và chạy ứng dụng
1. Tải source code về máy, sau đó đặt thư mục dự án vào thư mục `www` của WampServer (Ví dụ: `C:/wamp64/www/DoAn12/`).
2. Khởi động dịch vụ WampServer (đảm bảo biểu tượng dưới khay hệ thống chuyển sang màu Xanh lá).
3. Import Cơ sở dữ liệu:
   - Truy cập `http://localhost/phpmyadmin/`.
   - Tạo một cơ sở dữ liệu mới (ví dụ: `ai_milk_tea`).
   - Import file SQL mới nhất của dự án vào database vừa tạo.
4. Cấu hình kết nối:
   - Mở file `config.php` trong source code và kiểm tra lại thông tin kết nối database cho khớp với máy của bạn:
     ```php
     $host = 'localhost';$db   = 'ai_milk_tea';
     $user = 'root';$pass = '';
     ```
5. Trải nghiệm: Mở trình duyệt và truy cập `http://localhost/DoAn12/index.php`

4. Phân quyền tài khoản Demo
- Tài khoản Quản trị viên (Admin): Có quyền thêm, sửa, xóa công thức ngay tại trang chủ hoặc trang quản trị.
- Tài khoản Người dùng (User): Có quyền tra cứu, tìm kiếm tức thì và xem chi tiết công thức.