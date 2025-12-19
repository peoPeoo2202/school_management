<?php
session_start();
include_once("../../model/mStudent.php");

if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "hocsinh") {
  header("Location: ../../public/index.php");
  exit;
}

$model = new mStudent();
$info = $model->getStudentInfoByAccount($_SESSION["tenDangNhap"]);

if (!$info) {
  echo "<p class='error-message'>Không tìm thấy thông tin học sinh. Vui lòng liên hệ quản trị viên.</p>";
  exit;
}

// Lưu maHS vào session - LUÔN cập nhật để đảm bảo đúng với tài khoản hiện tại
$_SESSION['maHS'] = $info['maHS'];
$_SESSION['hoTen'] = $info['hoTen'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ học sinh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateStudent.php'); ?>

        <!-- Main Content -->
            <div class="content-area">
                <?php
                    $page = $_GET['page'] ?? 'timeTable';
                    if($page == 'grades'){
                        include_once('grades.php');
                    }elseif($page == 'classification'){
                        include_once('classification.php');
                    }elseif($page == 'submitHomework'){
                        include_once('../student/homework/index.php');
                    }else{
                        include_once('timeTable.php');
                    }
                ?>
            </div>
    </div>

    <script>
        // Menu toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.querySelector('.student-navbar');
            
            // Create toggle button for mobile
            if (window.innerWidth <= 768) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'navbar-toggle';
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                toggleBtn.id = 'navbarToggle';
                document.body.appendChild(toggleBtn);
                
                toggleBtn.addEventListener('click', function() {
                    navbar.classList.toggle('active');
                });
                
                // Close navbar when clicking outside
                document.addEventListener('click', function(e) {
                    if (!navbar.contains(e.target) && !toggleBtn.contains(e.target)) {
                        navbar.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>