# Hệ Thống Quản Lý Giáo Dục

Hệ thống quản lý giáo dục được xây dựng bằng PHP, MySQL với kiến trúc MVC (Model-View-Controller). Dự án hỗ trợ quản lý thông tin học sinh, giáo viên, lớp học, thời khóa biểu và điểm số.

## 🎯 Tính Năng

### Đã Triển Khai
- ✅ **Hệ thống đăng nhập**: Xác thực người dùng với mã hóa MD5
- ✅ **Quản lý phiên làm việc**: Session management cho các loại tài khoản khác nhau
- ✅ **Giao diện học sinh**:
  - Xem thông tin cá nhân
  - Xem thời khóa biểu
  - Xem điểm trung bình theo môn học
- ✅ **Kiến trúc MVC**: Tách biệt logic nghiệp vụ, dữ liệu và giao diện

### Các Vai Trò Người Dùng
- 👨‍🎓 **Học sinh** (hocsinh): Xem thông tin cá nhân, thời khóa biểu, điểm số
- 👨‍🏫 **Giáo viên** (giaovien): Quản lý lớp học và học sinh
- 👨‍👩‍👧 **Phụ huynh** (phuhuynh): Theo dõi thông tin con em
- 👔 **Ban giám hiệu** (bangiamhieu): Quản lý toàn trường
- 🔧 **Admin** (admin): Quản trị hệ thống

## 🛠️ Công Nghệ Sử Dụng

- **Backend**: PHP 7.x+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla JS)
- **Architecture**: MVC Pattern
- **Security**: MD5 password hashing, Session management

## 📁 Cấu Trúc Dự Án

```
school_management/
│
├── controller/              # Controllers - Xử lý logic điều khiển
│   ├── cLogin.php          # Controller đăng nhập
│   └── studentController.php
│
├── model/                   # Models - Xử lý dữ liệu và database
│   ├── mConnect.php        # Kết nối database
│   ├── mUser.php           # Model người dùng
│   └── mStudent.php        # Model học sinh
│
├── view/                    # Views - Giao diện người dùng
│   ├── layouts/            # Các thành phần layout chung
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── navigate.php
│   ├── student/            # Giao diện học sinh
│   │   ├── index.php       # Trang chủ học sinh
│   │   ├── grades.php      # Xem điểm
│   │   ├── timeTable.php   # Thời khóa biểu
│   │   └── style.css
│   └── teacher/            # Giao diện giáo viên
│       └── index.php
│
└── public/                  # Thư mục public
    ├── index.php           # Trang đăng nhập chính
    └── css/
        └── style.css       # CSS chung

```

## 🚀 Cài Đặt

### Yêu Cầu Hệ Thống
- PHP 7.0 hoặc cao hơn
- MySQL 5.7 hoặc MariaDB 10.0+
- Apache/Nginx web server
- XAMPP/WAMP/LAMP (khuyến nghị cho môi trường phát triển)

### Các Bước Cài Đặt

1. **Clone repository**
   ```bash
   git clone https://github.com/peoPeoo2202/school_management.git
   cd school_management
   ```

2. **Cấu hình Database**
   - Tạo database mới tên `school_management`
   - Import file SQL schema (nếu có) hoặc tạo các bảng sau:
     - `taikhoan` - Bảng tài khoản người dùng
     - `hocsinh` - Bảng thông tin học sinh
     - `lophoc` - Bảng lớp học
     - `khoi` - Bảng khối lớp
     - `monhoc` - Bảng môn học
     - `bangdiem` - Bảng điểm số
     - `lichday` - Bảng lịch dạy/thời khóa biểu

3. **Cấu hình kết nối Database**
   
   Mở file `model/mConnect.php` và chỉnh sửa thông tin kết nối:
   ```php
   $host = "localhost";
   $user = "school_management";  // Tên user MySQL của bạn
   $pass = "123";                // Mật khẩu MySQL của bạn
   $db   = "school_management";  // Tên database
   ```

4. **Cấu hình Web Server**
   
   - Với XAMPP: Copy thư mục project vào `htdocs/`
   - Với WAMP: Copy thư mục project vào `www/`
   - Hoặc cấu hình virtual host trỏ đến thư mục `public/`

5. **Truy cập ứng dụng**
   
   Mở trình duyệt và truy cập:
   ```
   http://localhost/school_management/public/index.php
   ```

## 📊 Cấu Trúc Database

### Bảng `taikhoan`
```sql
- maTaiKhoan (Primary Key)
- tenDangNhap
- matKhau (MD5 hashed)
- loaiTaiKhoan (admin/hocsinh/giaovien/phuhuynh/bangiamhieu)
- hoTen
```

### Bảng `hocsinh`
```sql
- maHS (Primary Key, Foreign Key -> taikhoan.maTaiKhoan)
- hoTen
- maLop (Foreign Key -> lophoc.maLop)
```

### Bảng `lophoc`
```sql
- maLop (Primary Key)
- tenLop
- maKhoi (Foreign Key -> khoi.maKhoi)
```

### Bảng `bangdiem`
```sql
- maHS (Foreign Key)
- maMonHoc (Foreign Key)
- diem
```

### Bảng `lichday`
```sql
- maLop (Foreign Key)
- maMonHoc (Foreign Key)
- thu (Thứ trong tuần)
- tietBatDau
- tietKetThuc
```

## 🔐 Bảo Mật

**Lưu ý quan trọng về bảo mật:**

1. **Mã hóa mật khẩu**: Hiện tại sử dụng MD5 - **không an toàn**
   - Khuyến nghị: Nâng cấp lên `password_hash()` và `password_verify()` của PHP

2. **SQL Injection**: Đã sử dụng Prepared Statements trong model
   - ✅ `mStudent.php` sử dụng prepared statements
   - ⚠️ `mUser.php` cần cập nhật để sử dụng prepared statements

3. **Session Security**: 
   - Đã implement session management cơ bản
   - Khuyến nghị: Thêm session timeout và regenerate session ID

## 📖 Hướng Dẫn Sử Dụng

### Đăng Nhập
1. Truy cập trang chủ: `http://localhost/school_management/public/index.php`
2. Nhập tên đăng nhập và mật khẩu
3. Hệ thống sẽ tự động chuyển hướng dựa trên loại tài khoản

### Giao Diện Học Sinh
Sau khi đăng nhập với tài khoản học sinh, bạn có thể:
- Xem thông tin cá nhân và lớp học
- Xem thời khóa biểu theo tuần
- Xem điểm trung bình các môn học
- Navigation menu được load động bằng AJAX

## 🤝 Đóng Góp

Mọi đóng góp đều được hoan nghênh! Vui lòng:
1. Fork repository
2. Tạo branch mới (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Mở Pull Request

## 📝 Roadmap

- [ ] Hoàn thiện giao diện giáo viên
- [ ] Thêm chức năng quản lý điểm cho giáo viên
- [ ] Giao diện phụ huynh
- [ ] Giao diện admin quản trị
- [ ] Báo cáo và thống kê
- [ ] Nâng cấp bảo mật (password_hash, CSRF protection)
- [ ] Thêm validation form
- [ ] Responsive design cho mobile
- [ ] API RESTful

## 📄 License

Dự án được phát triển cho mục đích học tập và nghiên cứu.

## 👥 Tác Giả

- GitHub: [@peoPeoo2202](https://github.com/peoPeoo2202)

## 📞 Liên Hệ

Nếu có bất kỳ câu hỏi hoặc góp ý nào, vui lòng tạo issue trên GitHub repository.

---

⭐ Nếu dự án hữu ích, hãy cho một star để ủng hộ!
