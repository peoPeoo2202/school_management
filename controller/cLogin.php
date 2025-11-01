<?php
    include_once("../model/mUser.php");
    class cUser{
        public function cLogin($name, $pass){
            $p = new mUser();
            $pass = MD5($pass);
            $result = $p->mLogin($name,$pass);
            if($result->num_rows>0){
                while($r=$result->fetch_assoc()){
                        $_SESSION["login"] = true;
                        $_SESSION["loaiTaiKhoan"] = $r["loaiTaiKhoan"];
                        return true; // đăng nhập thành công
                    }
            }else{
                return false;
            }
        }
    }
?>