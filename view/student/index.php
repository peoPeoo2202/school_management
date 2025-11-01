<?php
session_start();
include_once("../../model/mStudent.php");

if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "hocsinh") {
  header("Location: ../../login.php");
  exit;
}

$model = new mStudent();
$info = $model->getStudentInfoByAccount($_SESSION["tenDangNhap"]);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Học sinh - Hệ thống Quản lý Giáo dục</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <header style="background:#2f6bff;color:white;padding:10px 20px;">
    <h3>Xin chào học sinh, <strong><?= $info['hoTen'] ?></strong></h3>
  </header>

  <!-- <div class="container">
    Sidebar -->
    <div class="container">
    <!-- Content left (Menu) -->
    <div class="sidebar-container">
      <?php include('../layouts/navigate.php'); ?>
    </div>

    <!-- Main content -->
    <div class="main" id="content-right">
      <?php include('timeTable.php'); ?>
    </div>
  </div>

  <footer style="background:#2f6bff;color:white;text-align:center;padding:10px;">
    © 2025 Hệ thống Quản lý Học sinh - Demo giao diện
  </footer>

  <script>
    const menuLinks = document.querySelectorAll('.sidebar-container a');
    const contentRight = document.getElementById('content-right');

    menuLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        menuLinks.forEach(l => l.classList.remove('active'));
        this.classList.add('active');

        const href = this.getAttribute('href');

        fetch(href)
          .then(res => res.text())
          .then(html => {
            contentRight.innerHTML = html;
          })
          .catch(() => {
            contentRight.innerHTML = '<p style="color:red;">Không thể tải nội dung.</p>';
          });
      });
    });
  </script>

</body>
</html>
