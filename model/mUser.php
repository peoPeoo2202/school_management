<?php
    include_once("mConnect.php");
    class mUser{
        public function mLogin($name, $pass){
            $p = new mConnect();
            $conn = $p->mConnect();
            if($conn==true){
                $query = "select*from taikhoan where tenDangNhap = '$name' and matKhau = '$pass'";
                $result = $conn->query($query);
                $p->mDisconnect($conn);
                return $result;
            }else{
                return false;
            }
        }
    }
?>