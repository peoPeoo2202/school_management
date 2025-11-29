<?php
/**
 * Trang index cho Ban giám hiệu
 * Điều hướng đến controller báo cáo
 */
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

// Kiểm tra quyền truy cập - Chỉ Ban giám hiệu
if ($_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

// Điều hướng đến trang dashboard
header("Location: vBGHDashboard.php");
exit();
?>
