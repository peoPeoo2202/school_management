<?php
include_once("../model/mUser.php");
class cUser
{
    public function cLogin($name, $pass)
    {
        $p = new mUser();
        $pass = MD5($pass);
        $result = $p->mLogin($name, $pass);
        if ($result->num_rows > 0) {
            while ($r = $result->fetch_assoc()) {
                $_SESSION["login"] = true;
                $_SESSION["loaiTaiKhoan"] = $r["loaiTaiKhoan"];
                $_SESSION["maTaiKhoan"] = $r["maTaiKhoan"];
                $_SESSION["hoTen"] = $r["hoTen"];
                $_SESSION["tenDangNhap"] = $r["tenDangNhap"];
                return true;
            }
        } else {
            return false;
        }
    }
}
