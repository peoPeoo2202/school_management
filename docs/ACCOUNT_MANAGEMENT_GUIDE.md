# Hướng dẫn Triển khai Chức năng Quản lý Tài khoản

## 📋 Tổng quan

Module Quản lý Tài khoản cung cấp đầy đủ các chức năng CRUD, bảo mật, và kiểm soát truy cập cho hệ thống quản lý trường học.

## 🎯 Tính năng chính

### 1. Quản lý Tài khoản
- ✅ Danh sách tài khoản với phân trang (20 bản ghi/trang)
- ✅ Tìm kiếm và lọc theo: username, họ tên, loại tài khoản, trạng thái, nhóm
- ✅ Tạo tài khoản mới với tự động sinh mật khẩu
- ✅ Sửa thông tin tài khoản
- ✅ Khóa/Mở khóa tài khoản
- ✅ Reset mật khẩu
- ✅ Xóa tài khoản (soft delete - chuyển sang disabled)

### 2. Bảo mật
- ✅ Mã hóa mật khẩu bằng bcrypt (hỗ trợ migration từ MD5)
- ✅ Session timeout sau 15 phút không hoạt động
- ✅ Giới hạn số lần đăng nhập sai (5 lần)
- ✅ Tự động khóa tài khoản sau 5 lần đăng nhập sai
- ✅ CSRF token protection cho mọi form
- ✅ Rate limiting cho login attempts
- ✅ Audit log cho mọi thao tác quan trọng

### 3. Phân quyền
- ✅ Chỉ Quản trị viên được truy cập module
- ✅ Không cho phép admin tự xóa/khóa tài khoản của mình
- ✅ Không cho phép admin tự giảm quyền
- ✅ Kiểm tra quyền ở cả controller và view

## 📁 Cấu trúc File

```
school_management/
├── database/
│   └── migrations/
│       └── upgrade_taikhoan_schema.sql    # Migration cập nhật DB
├── model/
│   └── mUser.php                          # Model đã cập nhật với CRUD đầy đủ
├── controller/
│   └── cAccountManagement.php             # Controller mới cho quản lý tài khoản
├── view/
│   └── admin/
│       ├── vAccountList.php               # Giao diện danh sách tài khoản
│       └── css/
│           └── account-management.css     # CSS cho giao diện
└── public/
    └── session_security.php               # Middleware bảo mật session
```

## 🚀 Hướng dẫn Triển khai

### Bước 1: Cập nhật Database

```bash
# Kết nối MySQL
mysql -u root -p

# Chọn database
USE school_management;

# Chạy migration
source d:/xampp/htdocs/PTUD/Code/school_management/database/migrations/upgrade_taikhoan_schema.sql;
```

**Lưu ý quan trọng:**
- Migration sẽ thêm các cột mới vào bảng `taikhoan`
- Chuyển đổi `trangThaiTaiKhoan` từ tinyint sang ENUM
- Tạo indexes cho tối ưu performance
- Tạo trigger tự động log thay đổi
- Backup dữ liệu trước khi chạy migration!

### Bước 2: Kiểm tra Migration

```sql
-- Kiểm tra cấu trúc bảng
SHOW COLUMNS FROM taikhoan;

-- Kiểm tra indexes
SHOW INDEXES FROM taikhoan;

-- Kiểm tra dữ liệu
SELECT * FROM v_account_management LIMIT 10;

-- Kiểm tra trigger
SHOW TRIGGERS LIKE 'taikhoan';
```

### Bước 3: Cấu hình Session Security

Trong các file PHP cần bảo vệ, thêm:

```php
<?php
require_once(__DIR__ . '/../public/session_security.php');

// Yêu cầu đăng nhập
requireLogin();

// Hoặc yêu cầu quyền admin
requireAdmin();

// Hoặc yêu cầu nhiều role
requireRole(['quantrivien', 'bangiamhieu']);
?>
```

### Bước 4: Truy cập Giao diện

```
URL: http://localhost/school_management/view/admin/vAccountList.php
```

**Yêu cầu:**
- Phải đăng nhập với tài khoản `quantrivien`
- Session hợp lệ và chưa timeout

## 🔧 API Endpoints

### 1. Lấy danh sách tài khoản
```
GET /controller/cAccountManagement.php?action=list
Parameters:
  - page: số trang (default: 1)
  - limit: số bản ghi/trang (default: 20)
  - tenDangNhap: lọc theo username
  - hoTen: lọc theo họ tên
  - loaiTaiKhoan: lọc theo loại
  - trangThaiTaiKhoan: lọc theo trạng thái
  - maNhom: lọc theo nhóm
```

### 2. Lấy chi tiết tài khoản
```
GET /controller/cAccountManagement.php?action=get&id={maTaiKhoan}
```

### 3. Tạo tài khoản mới
```
POST /controller/cAccountManagement.php?action=create
Body (form-data):
  - csrf_token: token bảo mật
  - tenDangNhap: username (required)
  - hoTen: họ tên (required)
  - loaiTaiKhoan: loại tài khoản (required)
  - email: email (optional)
  - soDienThoai: số điện thoại (optional)
  - maNhom: mã nhóm (optional)
  - trangThaiTaiKhoan: trạng thái (default: active)
```

### 4. Cập nhật tài khoản
```
POST /controller/cAccountManagement.php?action=update&id={maTaiKhoan}
Body: tương tự create (không cần tenDangNhap)
```

### 5. Khóa tài khoản
```
POST /controller/cAccountManagement.php?action=lock&id={maTaiKhoan}
Body:
  - csrf_token
  - lyDo: lý do khóa
```

### 6. Mở khóa tài khoản
```
POST /controller/cAccountManagement.php?action=unlock&id={maTaiKhoan}
Body:
  - csrf_token
```

### 7. Reset mật khẩu
```
POST /controller/cAccountManagement.php?action=reset-password&id={maTaiKhoan}
Body:
  - csrf_token
Response:
  - newPassword: mật khẩu mới được tạo
```

### 8. Xóa tài khoản
```
POST /controller/cAccountManagement.php?action=delete&id={maTaiKhoan}
Body:
  - csrf_token
```

### 9. Kiểm tra username
```
GET /controller/cAccountManagement.php?action=check-username&username={value}&exclude={id}
```

### 10. Lấy danh sách nhóm
```
GET /controller/cAccountManagement.php?action=groups
```

## 📊 Database Schema

### Bảng `taikhoan` (Sau Migration)

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| maTaiKhoan | INT (PK, AI) | ID tài khoản |
| tenDangNhap | VARCHAR(100) UNIQUE | Tên đăng nhập |
| matKhau | VARCHAR(255) | Mật khẩu (bcrypt) |
| hoTen | VARCHAR(150) | Họ tên |
| email | VARCHAR(255) UNIQUE | Email |
| soDienThoai | VARCHAR(20) | Số điện thoại |
| loaiTaiKhoan | ENUM | Loại tài khoản |
| trangThaiTaiKhoan | ENUM | active/locked/disabled |
| maNhom | INT (FK) | Mã nhóm người dùng |
| ngayTao | DATETIME | Ngày tạo |
| nguoiTao | INT (FK) | ID người tạo |
| ngayCapNhat | DATETIME | Ngày cập nhật |
| nguoiCapNhat | INT (FK) | ID người cập nhật |
| batBuocDoiMatKhau | TINYINT | Cờ bắt buộc đổi mật khẩu |
| soLanDangNhapSai | INT | Số lần đăng nhập sai |
| lanDangNhapCuoi | DATETIME | Lần đăng nhập cuối |

### View `v_account_management`

View kết hợp thông tin từ `taikhoan`, `nhomnguoidung` để hiển thị đầy đủ.

## 🔐 Quy tắc Validation

### Username
- 4-32 ký tự
- Chỉ chữ cái, số, dấu chấm, gạch dưới
- Không trùng lặp

### Password
- Tối thiểu 8 ký tự
- Ít nhất 1 chữ hoa
- Ít nhất 1 chữ thường
- Ít nhất 1 chữ số

### Email
- Format chuẩn email
- Không trùng lặp (nếu có)

### Số điện thoại
- 9-11 chữ số
- Chỉ số

## 🛡️ Bảo mật

### Session Management
```php
// Kiểm tra timeout
checkSessionTimeout(); // Timeout sau 15 phút

// Rate limiting
checkLoginRateLimit($username); // Tối đa 5 lần/5 phút

// CSRF Protection
validateCSRFToken(); // Mọi POST request
```

### Audit Log
Mọi thao tác được ghi vào bảng `lichsunhapxuat`:
- Đăng nhập/đăng xuất
- Tạo/sửa/xóa tài khoản
- Khóa/mở khóa
- Reset mật khẩu
- Thay đổi quyền

### File Logs
Security events được ghi vào `logs/security.log` với format:
```
[2025-11-17 10:30:45] Event: LOGIN_SUCCESS | User: admin | IP: 127.0.0.1 | Details: Username: admin | UserAgent: Mozilla...
```

## 🧪 Testing

### Test Cases Cơ bản

1. **Tạo tài khoản**
   - Tạo với đầy đủ thông tin
   - Tạo với thông tin tối thiểu
   - Tạo với username trùng (expect fail)
   - Tạo với email trùng (expect fail)

2. **Đăng nhập**
   - Đăng nhập thành công
   - Đăng nhập sai mật khẩu 5 lần (expect lock)
   - Đăng nhập tài khoản bị khóa (expect fail)

3. **Phân quyền**
   - Admin truy cập → OK
   - Giáo viên truy cập → Forbidden
   - Không đăng nhập → Redirect login

4. **Session**
   - Không hoạt động 15 phút → Auto logout
   - Hoạt động liên tục → Session còn hợp lệ

## 📝 Ghi chú Migration

### Nếu gặp lỗi với ENUM
```sql
-- Nếu bảng có dữ liệu cũ không hợp lệ
UPDATE taikhoan SET trangThaiTaiKhoan = 1 WHERE trangThaiTaiKhoan IS NULL OR trangThaiTaiKhoan NOT IN (0, 1);

-- Sau đó chạy lại ALTER
```

### Rollback Migration
Nếu cần rollback, uncomment phần ROLLBACK trong file migration và chạy.

## 🔄 Migration từ MD5 sang bcrypt

Mật khẩu cũ (MD5) vẫn hoạt động. Khi user đăng nhập lần đầu với MD5, hệ thống sẽ:
1. Verify MD5 hash
2. Cho phép đăng nhập
3. Khuyến nghị migrate sang bcrypt

Để force migrate tất cả:
```php
// Trong script riêng hoặc admin panel
$users = $mUser->getAllAccounts([], 1, 1000);
foreach ($users['data'] as $user) {
    // Reset password để tạo bcrypt hash mới
    $tempPassword = $mUser->generatePassword();
    $mUser->resetPassword($user['maTaiKhoan'], $tempPassword, $adminId);
    // Gửi email thông báo password mới
}
```

## 🎨 Customization

### Thay đổi Session Timeout
Trong `session_security.php`:
```php
$timeout = 15 * 60; // Đổi thành số phút * 60
```

### Thay đổi Login Rate Limit
Trong `session_security.php`:
```php
checkLoginRateLimit($identifier, 5, 300); // (maxAttempts, timeWindowSeconds)
```

### Thay đổi Pagination
Trong `vAccountList.php`:
```javascript
const limit = 20; // Đổi số bản ghi/trang
```

## ⚠️ Lưu ý Quan trọng

1. **Backup Database** trước khi chạy migration
2. **Test trên môi trường dev** trước khi deploy production
3. **Đảm bảo HTTPS** khi deploy lên production
4. **Không commit** file chứa mật khẩu thật vào Git
5. **Cấu hình email** để gửi thông báo mật khẩu mới
6. **Monitor logs** thường xuyên để phát hiện bất thường

## 📞 Hỗ trợ

Nếu gặp vấn đề:
1. Kiểm tra logs tại `logs/security.log`
2. Kiểm tra lỗi PHP trong Apache error log
3. Kiểm tra console browser (F12) cho lỗi JavaScript
4. Xem lại các bước triển khai

## 🔮 Tính năng Tương lai

- [ ] Two-Factor Authentication (2FA)
- [ ] Single Sign-On (SSO)
- [ ] Bulk import/export accounts (CSV)
- [ ] Email notifications
- [ ] Advanced audit reports
- [ ] Password strength meter
- [ ] Account activity timeline
- [ ] Delegated admin roles

---

**Phiên bản:** 1.0  
**Ngày cập nhật:** 17/11/2025  
**Tác giả:** Development Team
