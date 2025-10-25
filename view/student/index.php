<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Học sinh - Hệ thống Quản lý Giáo dục</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .container {
      display: flex;
      height: 100vh;
    }
    .sidebar {
      width: 220px;
      background: #f2f3f5;
      padding: 15px;
      border-right: 1px solid #ddd;
    }
    .main {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }
    .sidebar ul {
      list-style: none;
      padding: 0;
    }
    .sidebar a {
      display: block;
      padding: 10px;
      text-decoration: none;
      color: #333;
      border-radius: 6px;
    }
    .sidebar a.active,
    .sidebar a:hover {
      background: #2f6bff;
      color: #fff;
    }
    iframe {
      width: 100%;
      height: calc(100vh - 80px);
      border: none;
    }
  </style>
</head>
<body>

<header style="background:#2f6bff;color:white;padding:10px 20px;">
  <h3>Xin chào học sinh, <strong>Võ Thị Thương Hoài</strong></h3>
</header>

<div class="container">
  <!-- Content left -->
  <div id="sidebar">
    <?php include('../layouts/navigate.php'); ?>
  </div>

  <!-- Content right -->
  <div class="main" id="content-right">
    <!-- Mặc định load thời khóa biểu -->
    <?php include('timeTable.php'); ?>
  </div>
</div>

<footer style="background:#2f6bff;color:white;text-align:center;padding:10px;">
  © 2025 Hệ thống Quản lý Học sinh - Demo giao diện
</footer>

<script>
  // Lấy phần tử menu
  const menuLinks = document.querySelectorAll('.sidebar a');
  const contentRight = document.getElementById('content-right');

  menuLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();

      // Xóa class active cũ
      menuLinks.forEach(l => l.classList.remove('active'));
      this.classList.add('active');

      // Lấy URL của file tương ứng
      let href = this.getAttribute('href');
      let fileToLoad = '';

      if (href.includes('grades')) fileToLoad = 'grades.php';
      else fileToLoad = 'timeTable.php';

      // Dùng AJAX load nội dung mới
      fetch(fileToLoad)
        .then(res => res.text())
        .then(html => {
          contentRight.innerHTML = html;
        })
        .catch(err => {
          contentRight.innerHTML = '<p style="color:red;">Không thể tải nội dung.</p>';
        });
    });
  });
</script>

</body>
</html>
