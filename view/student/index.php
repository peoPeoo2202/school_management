<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Học sinh - Hệ thống Quản lý Giáo dục</title>
  <link rel="stylesheet" href="/view//student/style.css">

</head>

<body>

  <header style="background:#2f6bff;color:white;padding:10px 20px;">
    <h3>Xin chào học sinh, <strong>Võ Thị Thương Hoài</strong></h3>
  </header>

  <div class="container">
    <!-- Content left (Menu) -->
    <div class="sidebar-container">
      <?php include('../layouts/navigate.php'); ?>
    </div>

    <!-- Content right (Thời khóa biểu/ điểm) -->
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