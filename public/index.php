<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng nhập - Hệ thống Quản lý Giáo dục</title>
  <link rel="stylesheet" href="./css/style.css">
</head>
<body class="login-body">

<div class="login-container">
  <h2>HỆ THỐNG QUẢN LÝ GIÁO DỤC</h2>
  <form action="#" method="post" class="login-form">
    <label for="username">Tên đăng nhập</label>
    <input type="text" id="username" name="txtName" placeholder="Nhập tên đăng nhập">

    <label for="password">Mật khẩu</label>
    <input type="password" id="password" name="txtPass" placeholder="Nhập mật khẩu">

    <button type="submit">Đăng nhập</button>
  </form>
</div>

<?php
        if(isset($_POST["btnSub"])){
            include_once("controller/cUser.php");
            $p = new cUser();

            if($p->clogin($_REQUEST["txtName"],$_REQUEST["txtPass"])){
                echo "<script>alert('Bạn đã đăng nhập tài khoản thành công!')</script>";
                switch ($_SESSION["loaiTaiKhoan"]) {
                    case 'admin':
                        echo "Đây là trang quản trị viên."; // khi nào có giao diện thì điều hướng
                        break;
                    case 'hocsinh':
                        echo "Đây là trang học sinh.";
                        break;
                    case 'phuhuynh':
                        echo "Đây là trang phụ huynh.";
                        break;
                    case 'giaovien':
                        echo "Đây là trang giáo viên.";
                        break;
                    case 'bangiamhieu':
                        echo "Đây là trang ban giám hiệu.";
                        break;
                }
            }else{
                echo "<script>alert('Bạn đã đăng nhập tài khoản không thành công!')</script>";
                header("refresh:0.5; url=index.php?page=login");
                exit();
            }
        }
    ?>

</body>
</html>
