<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: /view/public/index.php");
    exit();
}

// Kiểm tra quyền Ban giám hiệu
if (!isset($_SESSION['loaiTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    header("Location: /view/public/index.php?error=access_denied");
    exit();
}

// Lấy thông tin từ session
$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';
$tenDangNhap = $_SESSION['tenDangNhap'] ?? '';
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../teacher/style.css">
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateBGH.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <!-- Header -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-home"></i> Dashboard</h2>
                    <p>Chào mừng bạn trở lại</p>
                </div>
                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <!-- Thông tin hệ thống -->
            <div class="card info-session">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-info-circle"></i> Thông tin tài khoản
                    </h2>
                </div>
                <div class="card-info">
                    <div class="info-box">
                        <h3><i class="fas fa-user"></i> Thông tin cá nhân</h3>
                        <div class="info-detail">
                            <span>
                                <strong>Họ tên:</strong>
                                <p><?php echo htmlspecialchars($hoTen); ?></p>
                            </span>
                            <span>
                                <strong>Tên đăng nhập:</strong>
                                <p><?php echo htmlspecialchars($tenDangNhap); ?></p>
                            </span>
                            <span>
                                <strong>Chức vụ:</strong>
                                <p>Ban giám hiệu</p>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Báo cáo học tập -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-chart-bar"></i> Báo cáo học tập
                        </h2>
                    </div>
                    <div class="quick-links">
                        <a href="vBGHAcademicReport.php" class="quick-link">
                            <i class="fas fa-book"></i>
                            <span>Kết quả học tập</span>
                        </a>
                        <a href="vBGHAttendanceReport.php" class="quick-link">
                            <i class="fas fa-clipboard-check"></i>
                            <span>Báo cáo chuyên cần</span>
                        </a>
                        <a href="vBGHGradeStatistics.php" class="quick-link">
                            <i class="fas fa-chart-line"></i>
                            <span>Thống kê điểm</span>
                        </a>
                    </div>
                </div>

                <!-- Báo cáo khác -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-file-alt"></i> Báo cáo khác
                        </h2>
                    </div>
                    <div class="quick-links">
                        <a href="vBGHTeachingReport.php" class="quick-link">
                            <i class="fas fa-chalkboard"></i>
                            <span>Báo cáo giảng dạy</span>
                        </a>
                        <a href="vBGHSummaryReport.php" class="quick-link">
                            <i class="fas fa-layer-group"></i>
                            <span>Báo cáo tổng hợp</span>
                        </a>
                        <a href="vBGHHonorTitleReport.php" class="quick-link">
                            <i class="fas fa-star"></i>
                            <span>Báo cáo danh hiệu</span>
                        </a>
                    </div>
                </div>

                <!-- Thống kê -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-database"></i> Thống kê
                        </h2>
                    </div>
                    <div class="quick-links">
                        <a href="vBGHStudentStatistics.php" class="quick-link">
                            <i class="fas fa-users"></i>
                            <span>Thống kê học sinh</span>
                        </a>
                        <a href="vBGHSubmittedReports.php" class="quick-link">
                            <i class="fas fa-inbox"></i>
                            <span>Báo cáo đã gửi</span>
                        </a>
                    </div>
                </div>

                <!-- Quản lý kỳ thi -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-tasks"></i> Quản lý kỳ thi
                        </h2>
                    </div>
                    <div class="quick-links">
                        <a href="vChonDe.php" class="quick-link">
                            <i class="fas fa-file-upload"></i>
                            <span>Chọn đề thi</span>
                        </a>
                        <a href="vDanhSachYeuCau.php" class="quick-link">
                            <i class="fas fa-list"></i>
                            <span>Danh sách yêu cầu</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Toggle Script -->
    <script>
        const menuToggle = document.querySelector('.navbar-toggle');
        const navbar = document.querySelector('.bgh-navbar');
        const body = document.body;

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                navbar.classList.toggle('active');
            });

            document.addEventListener('click', function(event) {
                if (!navbar.contains(event.target) && !menuToggle.contains(event.target)) {
                    navbar.classList.remove('active');
                }
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    navbar.classList.remove('active');
                }
            });
        }

        // Menu toggle functionality
        if (typeof window.__navigateBGHInitialized === 'undefined') {
            const menuToggles = document.querySelectorAll('.menu-toggle');
            menuToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const submenuId = this.getAttribute('data-submenu');
                    const submenu = document.getElementById(submenuId + '-submenu');

                    if (submenu) {
                        submenu.classList.toggle('open');
                    }
                });
            });
            window.__navigateBGHInitialized = true;
        }
    </script>
</body>

</html>