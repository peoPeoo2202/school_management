-- Kiểm tra và sửa trạng thái tài khoản
USE school_management;

-- Kiểm tra kiểu dữ liệu của cột trangThaiTaiKhoan
SHOW COLUMNS FROM taikhoan LIKE 'trangThaiTaiKhoan';

-- Đảm bảo các tài khoản admin và username có trạng thái = 1 (active)
UPDATE taikhoan 
SET trangThaiTaiKhoan = 1 
WHERE tenDangNhap IN ('admin', 'username', 'qtv1');

-- Kiểm tra lại
SELECT tenDangNhap, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, 
       LEFT(matKhau, 32) as password_hash
FROM taikhoan 
WHERE tenDangNhap IN ('admin', 'username', 'qtv1');
