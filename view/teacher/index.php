<?php
session_start();
include_once("../../model/mTeacher.php");

// Kiểm tra quyền đăng nhập
if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "giaovien") {
  header("Location: ../../login.php");
  exit;
}

// Lấy thông tin giáo viên
$model = new mTeacher();
$info = $model->getTeacherInfoByAccount($_SESSION["tenDangNhap"]);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Giáo viên - Hệ thống Quản lý Giáo dục</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      font-family: "Inter", sans-serif;
      background: #f9fafc;
      margin: 0;
      padding: 0;
    }
    header {
      background: #2f6bff;
      color: white;
      padding: 10px 20px;
    }
    .container {
      display: flex;
      min-height: calc(100vh - 100px);
    }
    .sidebar-container {
      width: 220px;
      background: #fff;
      border-right: 1px solid #e5e7eb;
      padding: 16px;
    }
    .main {
      flex: 1;
      padding: 20px;
      background: #f9fafc;
    }
    footer {
      background: #2f6bff;
      color: white;
      text-align: center;
      padding: 10px;
    }
    a.active {
      color: #2f6bff;
      font-weight: bold;
    }
  </style>
</head>

<body>
  <header>
    <h3>Xin chào giáo viên, 
      <strong><?= htmlspecialchars($info['hoTen'] ?? 'Chưa xác định') ?></strong>
    </h3>
  </header>

  <div class="container">
    <!-- Sidebar -->
    <div class="sidebar-container">
<?php include('../layouts/navigate/navigateTeacher.php'); ?>
    </div>

    <!-- Main content -->
    <div class="main" id="content-right">
      <h2>Chào mừng bạn đến trang quản lý giáo viên</h2>
      <p>Vui lòng chọn chức năng bên trái để bắt đầu.</p>
    </div>
  </div>

  <footer>
    © 2025 Hệ thống Quản lý Giáo Dục - Trang Giáo Viên
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

        fetch(href, { credentials: 'include' })
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
