<?php
session_start();

// Load config
require_once(__DIR__ . '/../../config.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: " . url('public/index.php'));
    exit();
}

// Kiểm tra quyền giáo viên
if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: " . url('public/index.php?error=access_denied'));
    exit();
}

// Lấy thông tin từ session
$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
$tenDangNhap = $_SESSION['tenDangNhap'] ?? '';
$maGV = $_SESSION['maGV'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .main-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .content-area {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
            overflow-x: hidden;
            height: 100vh;
            box-sizing: border-box;
        }

        .header-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 32px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;

        }
       
        .header-left h2 {
            color: #5081BE;
            margin:0;
        }

        .header-left p {
            color: #999;
            font-size: 14px;
        }

        .header-right {
            text-align: right;
        }

        .header-right .welcome-text {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
            margin-top: 8px;
        }

        .header-right .user-name {
            color: #5081BE;
            font-weight: 600;
            font-size: 17px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #5081BE 0%, #2d5a8c 100%);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-icon.purple {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-info h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 16px;
        }

        .stat-info p {
            margin: 0;
            color: #999;
            font-size: 12px;
        }

        .stat-value {
            display: block;
            color: #5081BE;
            font-size: 24px;
            font-weight: 700;
            margin-top: 5px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: #5081BE;
            font-size: 20px;
        }

        .view-link {
            color: #5081BE;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-link:hover {
            color: #2d5a8c;
            gap: 10px;
        }

        .quick-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f9f9f9;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .quick-link:hover {
            background: #E0F2FC;
            color: #5081BE;
            padding-left: 17px;
        }

        .quick-link i {
            width: 20px;
            text-align: center;
            color: #5081BE;
        }

        .info-box {
            background: linear-gradient(135deg, #5081BE15 0%, #2d5a8c15 100%);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #5081BE;
        }

        .info-box h4 {
            color: #5081BE;
            margin: 0 0 10px 0;
            font-size: 14px;
        }

        .info-box p {
            margin: 0;
            color: #666;
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .main-wrapper {
                flex-direction: column;
            }

            .content-area {
                margin-left: 0;
                padding: 15px;
            }

            /* .header-section {
                flex-direction: column;
                text-align: center;
            } */

            .header-right {
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

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

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Lịch dạy</h3>
                        <p>Xem lịch dạy của bạn</p>
                        <span class="stat-value">Hôm nay</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Danh sách lớp</h3>
                        <p>Quản lý các lớp học</p>
                        <span class="stat-value">Nhiều lớp</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Báo cáo</h3>
                        <p>Xem báo cáo thống kê</p>
                        <span class="stat-value">6 loại</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Phân công</h3>
                        <p>Xem nhiệm vụ được giao</p>
                        <span class="stat-value">Cập nhật</span>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Chức năng chính -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-tasks"></i> Chức năng chính
                        </h2>
                        <a href="#" class="view-link">
                            Xem tất cả <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="quick-links">
                        <a href="<?php echo url('controller/cTeachingSchedule.php?action=dashboard'); ?>" class="quick-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Tra cứu Lịch dạy</span>
                        </a>
                        <a href="<?php echo url('view/teacher/vClassList.php'); ?>" class="quick-link">
                            <i class="fas fa-list"></i>
                            <span>Danh sách lớp</span>
                        </a>
                        <a href="<?php echo url('controller/cReport.php'); ?>" class="quick-link">
                            <i class="fas fa-chart-bar"></i>
                            <span>Báo cáo & Thống kê</span>
                        </a>
                        <a href="<?php echo url('view/teacher/vExamSupervision.php'); ?>" class="quick-link">
                            <i class="fas fa-eye"></i>
                            <span>Phân công coi thi</span>
                        </a>
                    </div>
                </div>

                <!-- Báo cáo & Thống kê -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-chart-bar"></i> Báo cáo & Thống kê
                        </h2>
                        <a href="<?php echo url('controller/cReport.php'); ?>" class="view-link">
                            Xem tất cả <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="quick-links">
                        <a href="<?php echo url('controller/cReport.php?action=academic'); ?>" class="quick-link">
                            <i class="fas fa-book"></i>
                            <span>Báo cáo kết quả học tập</span>
                        </a>
                        <a href="<?php echo url('controller/cReport.php?action=attendance'); ?>" class="quick-link">
                            <i class="fas fa-clipboard-check"></i>
                            <span>Báo cáo chuyên cần</span>
                        </a>
                        <a href="<?php echo url('controller/cReport.php?action=teaching'); ?>" class="quick-link">
                            <i class="fas fa-chalkboard"></i>
                            <span>Báo cáo giảng dạy</span>
                        </a>
                        <a href="<?php echo url('controller/cReport.php?action=grade_stats'); ?>" class="quick-link">
                            <i class="fas fa-chart-line"></i>
                            <span>Thống kê điểm môn học</span>
                        </a>
                    </div>
                </div>

                <!-- Thông tin hệ thống -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-info-circle"></i> Thông tin tài khoản
                        </h2>
                    </div>
                    <div class="info-box">
                        <h4><i class="fas fa-user"></i> Thông tin cá nhân</h4>
                        <p>
                            <strong>Họ tên:</strong> <?php echo htmlspecialchars($hoTen); ?><br>
                            <strong>Tên đăng nhập:</strong> <?php echo htmlspecialchars($tenDangNhap); ?><br>
                            <strong>Mã giáo viên:</strong> <?php echo $maGV ? $maGV : '<em style="color: #dc3545;">Chưa liên kết</em>'; ?><br>
                            <strong>Loại tài khoản:</strong> Giáo viên
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>