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
  <form action="" method="post" class="login-form">
    <label for="username">Tên đăng nhập</label>
    <input type="text" id="username" name="txtName" placeholder="Nhập tên đăng nhập" required>

    <label for="password">Mật khẩu</label>
    <input type="password" id="password" name="txtPass" placeholder="Nhập mật khẩu" required>

    <button type="submit" name="btnSub">Đăng nhập</button>
  </form>
</div>

<?php
session_start(); // cần có để lưu thông tin đăng nhập

if (isset($_POST["btnSub"])) {
    include_once("controller/cUser.php");
    $p = new cUser();

    $username = $_POST["txtName"];
    $password = $_POST["txtPass"];

    if ($p->clogin($username, $password)) {
        echo "<script>alert('Đăng nhập thành công!');</script>";

        // điều hướng đến trang tương ứng
        switch ($_SESSION["loaiTaiKhoan"]) {
            case 'admin':
                header("Location: view/admin/index.php");
                break;
            case 'hocsinh':
                header("Location: view/student/index.php");
                break;
            case 'phuhuynh':
                header("Location: view/parent/index.php");
                break;
            case 'giaovien':
                header("Location: view/teacher/index.php");
                break;
            case 'bangiamhieu':
                header("Location: view/bgh/index.php");
                break;
            default:
                echo "<script>alert('Không xác định loại tài khoản!');</script>";
        }
        exit();
    } else {
        echo "<script>alert('Tên đăng nhập hoặc mật khẩu sai!');</script>";
    }
}
?>

</body>
</html>
