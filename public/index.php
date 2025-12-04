<?php
session_start();

// Xử lý logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
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
            <?php
            // Hiển thị lỗi nếu có
            if (isset($_GET['error'])) {
                if ($_GET['error'] == 'access_denied') {
                    echo '<p style="color: red;">Bạn không có quyền truy cập!</p>';
                }
            }
            if (isset($login_error)) {
                echo '<p style="color: red;">' . $login_error . '</p>';
            }
            ?>
            <button type="submit" name="btnSub">Đăng nhập</button>
        </form>
    </div>

    <?php

if (isset($_POST["btnSub"])) {
    include_once("../controller/cUser.php");
    $p = new cUser();

    $username = $_POST["txtName"];
    $password = $_POST["txtPass"];

    if ($p->clogin($username, $password)) {
        
        // điều hướng đến trang tương ứng
        switch ($_SESSION["loaiTaiKhoan"]) {
            case 'quantrivien':
            case 'admin':
                header("Location: ../view/admin/index.php");
                exit();
                
            case 'hocsinh':
                header("Location: ../view/student/index.php");
                exit();
                
            case 'phuhuynh':
                header("Location: ../view/parent/index.php");
                exit();
                
            case 'giaovien':
                header("Location: ../view/teacher/index.php");
                exit();
                
            case 'bangiamhieu':
                header("Location: ../view/bgh/index.php"); 
                exit();
                
            case 'ttbm':
                header("Location: ../view/toTruongBoMon/index.php");
                exit();
                
            default:
                // Nếu loại tài khoản không xác định
                echo "<script>alert('Loại tài khoản không được hỗ trợ: " . $_SESSION["loaiTaiKhoan"] . "');</script>";
                break;
        }
    } else {
        echo "<script>alert('Tên đăng nhập hoặc mật khẩu sai!');</script>";
    }
}
?>

</body>

</html>