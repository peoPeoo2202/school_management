<?php
include_once("../model/mUser.php");
class cUser
{
    public function cLogin($name, $pass)
    {
        $p = new mUser();
        // mLogin() tự xử lý cả MD5 và bcrypt, không cần hash trước
        $user = $p->mLogin($name, $pass);
        
        // mLogin() trả về array (user info) hoặc false
        if ($user !== false && is_array($user)) {
            // Set session variables
            $_SESSION["login"] = true;
            $_SESSION["loaiTaiKhoan"] = $user["loaiTaiKhoan"];
            $_SESSION["maTaiKhoan"] = $user["maTaiKhoan"];
            $_SESSION["hoTen"] = $user["hoTen"];
            $_SESSION["tenDangNhap"] = $user["tenDangNhap"];
            
            // Nếu là giáo viên, lấy thêm maGV để sử dụng cho chức năng tra cứu giảng dạy
            if ($user["loaiTaiKhoan"] === 'giaovien') {
                $maGV = $p->getTeacherIdByAccountId($user["maTaiKhoan"]);
                if ($maGV !== null) {
                    $_SESSION["maGV"] = $maGV;
                }
            }
            
            return true;
        } else {
            return false;
        }
    }
}
