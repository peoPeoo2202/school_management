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

// Lưu maHS vào session nếu chưa có
if (!isset($_SESSION['maHS'])) {
    $_SESSION['maHS'] = $info['maHS'];
    $_SESSION['hoTen'] = $info['hoTen'];
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
    <style>
        /* Fix layout - prevent content from being hidden behind navbar */
        .main-wrapper {
            display: flex;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .student-navbar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            background: #2c3e50;
        }
        
        .content-area {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 20px;
            background: #f5f5f5;
        }
        
        /* Responsive design for mobile */
        @media (max-width: 768px) {
            .student-navbar {
                transform: translateX(-250px);
                transition: transform 0.3s ease;
            }
            
            .student-navbar.active {
                transform: translateX(0);
            }
            
            .content-area {
                margin-left: 0;
                width: 100%;
            }
            
            .navbar-toggle {
                position: fixed;
                top: 10px;
                left: 10px;
                z-index: 1001;
                background: #2c3e50;
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 5px;
                cursor: pointer;
            }
        }
    </style>
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
                    include_once('submitHomework.php');
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