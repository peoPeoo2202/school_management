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
                }else{
                    include_once('timeTable.php');
                }
            ?>
        </div>
    </div>

    <script>
        // Menu toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const navbarToggle = document.getElementById('navbarToggle');
            const studentNavbar = document.querySelector('.student-navbar');
            const contentArea = document.querySelector('.content-area');

            if (navbarToggle && studentNavbar) {
                navbarToggle.addEventListener('click', function() {
                    studentNavbar.style.transform = studentNavbar.style.transform === 'translateX(-250px)' 
                        ? 'translateX(0)' 
                        : 'translateX(-250px)';
                });
            }
        });
    </script>
</body>
</html>
