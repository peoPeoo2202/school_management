<?php
/**
 * SCHEDULE MANAGEMENT - Main Page
 * Trang chính cho chức năng Lên lịch
 */

session_start();

// Check authentication
if (!isset($_SESSION['loaiTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    header('Location: ../../public/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lên lịch - Admin</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="css/table-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .schedule-options {
            display: flex;
            gap: 40px;
            justify-content: center;
            align-items: stretch;
            padding: 60px 20px;
            flex-wrap: wrap;
        }
        
        .schedule-card {
            background: white;
            border-radius: 16px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            width: 350px;
            border: 3px solid transparent;
        }
        
        .schedule-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        
        .schedule-card.timetable:hover {
            border-color: #3498db;
        }
        
        .schedule-card.exam:hover {
            border-color: #e74c3c;
        }
        
        .schedule-card .icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
        }
        
        .schedule-card.timetable .icon {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .schedule-card.exam .icon {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .schedule-card h2 {
            font-size: 26px;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .schedule-card p {
            color: #7f8c8d;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .schedule-card .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .schedule-card.timetable .btn-action {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .schedule-card.exam .btn-action {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .schedule-card .btn-action:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .page-intro {
            text-align: center;
            padding: 40px 20px 20px;
        }
        
        .page-intro h1 {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .page-intro p {
            color: #7f8c8d;
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .features-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            padding: 0 20px;
        }
        
        .feature-tag {
            background: #ecf0f1;
            color: #2c3e50;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .feature-tag i {
            color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i> QLTH
                </a>
                <div class="sidebar-subtitle">Hệ thống Quản lý Trường học</div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">MENU CHÍNH</div>
                    
                    <div class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="students.php" class="nav-link">
                            <i class="fas fa-users-cog"></i>
                            <span>Quản lý thông tin</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="schedule.php" class="nav-link active">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Lên lịch</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="permissions.php" class="nav-link">
                            <i class="fas fa-user-shield"></i>
                            <span>Phân quyền</span>
                        </a>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">CÔNG CỤ</div>
                    
                    <div class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-chart-bar"></i>
                            <span>Báo cáo thống kê</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Cấu hình hệ thống</span>
                        </a>
                    </div>
                </div>
            </nav>

            <div class="sidebar-footer">
                <a href="../../public/index.php?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="breadcrumb">
                    <div class="breadcrumb-item">
                        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    </div>
                    <div class="breadcrumb-item">
                        <span>Lên lịch</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="notification-wrapper">
                        <button class="notification-btn">
                            <i class="fas fa-bell"></i>
                            <span class="badge">3</span>
                        </button>
                    </div>
                    <div class="user-dropdown">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['hoTen'] ?? 'A', 0, 1)); ?></div>
                        <span class="user-name"><?php echo $_SESSION['hoTen'] ?? 'Admin Root'; ?></span>
                    </div>
                </div>
            </header>

            <!-- Page Intro -->
            <div class="page-intro">
                <h1><i class="fas fa-calendar-alt"></i> Quản lý Lịch học & Lịch thi</h1>
                <p>Lập lịch giảng dạy và lịch thi cho các lớp học trong hệ thống. Đảm bảo không trùng phòng, không trùng tiết.</p>
                
                <div class="features-list">
                    <span class="feature-tag"><i class="fas fa-check"></i> Kiểm tra trùng lịch tự động</span>
                    <span class="feature-tag"><i class="fas fa-check"></i> Hiển thị phòng trống</span>
                    <span class="feature-tag"><i class="fas fa-check"></i> Phân công giáo viên</span>
                    <span class="feature-tag"><i class="fas fa-check"></i> Quản lý kỳ thi</span>
                </div>
            </div>

            <!-- Schedule Options -->
            <div class="schedule-options">
                <!-- Lên TKB -->
                <div class="schedule-card timetable" onclick="window.location.href='timetable.php'">
                    <div class="icon">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <h2>Lên Thời khóa biểu</h2>
                    <p>Tạo và quản lý thời khóa biểu cho các lớp học. Phân công giáo viên, môn học và phòng học theo từng tiết.</p>
                    <a href="timetable.php" class="btn-action">
                        <i class="fas fa-arrow-right"></i> Bắt đầu lập TKB
                    </a>
                </div>

                <!-- Lên lịch thi -->
                <div class="schedule-card exam" onclick="window.location.href='exam-schedule.php'">
                    <div class="icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h2>Lên Lịch thi</h2>
                    <p>Lập lịch thi cho các kỳ thi. Phân công phòng thi, giáo viên coi thi và thời gian thi cho từng môn.</p>
                    <a href="exam-schedule.php" class="btn-action">
                        <i class="fas fa-arrow-right"></i> Bắt đầu lập lịch thi
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
