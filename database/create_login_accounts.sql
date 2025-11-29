-- Tạo tài khoản username/password và admin/password
-- Chạy script này trong phpMyAdmin hoặc MySQL

USE school_management;

-- Xóa tài khoản username nếu đã tồn tại
DELETE FROM taikhoan WHERE tenDangNhap = 'username';

-- Tạo tài khoản username/password
-- MD5 hash của 'password' = 5f4dcc3b5aa765d61d8327deb882cf99
INSERT INTO taikhoan (tenDangNhap, matKhau, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom) 
VALUES ('username', '5f4dcc3b5aa765d61d8327deb882cf99', 'Username Account', 'quantrivien', 1, 3001);

-- Cập nhật lại tài khoản admin để chắc chắn
UPDATE taikhoan 
SET matKhau = '5f4dcc3b5aa765d61d8327deb882cf99',
    trangThaiTaiKhoan = 1
WHERE tenDangNhap = 'admin';

-- Kiểm tra kết quả
SELECT tenDangNhap, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, 
       CASE 
           WHEN matKhau = '5f4dcc3b5aa765d61d8327deb882cf99' THEN 'OK - password đúng'
           ELSE 'SAI'
       END as check_password
FROM taikhoan 
WHERE tenDangNhap IN ('username', 'admin');
