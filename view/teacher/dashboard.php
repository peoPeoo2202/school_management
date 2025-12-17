<?php
session_start();

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

// Xử lý routing cho các chức năng yêu cầu
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (in_array($action, ['yeucau', 'danhsachyeucau', 'chitietyeucau', 'xulysuadiem', 'xulynghiphep', 'download'])) {
    require_once(__DIR__ . '/../../controller/cTeacherRequest.php');
    $controller = new ControllerTeacherRequest();
    
    switch ($action) {
        case 'yeucau':
            $type = isset($_GET['type']) ? $_GET['type'] : 'suadiem';
            if ($type == 'suadiem') {
                $controller->formSuaDiem();
            } else if ($type == 'nghiphep') {
                $controller->formNghiPhep();
            }
            exit();
            
        case 'danhsachyeucau':
            $controller->danhSachYeuCau();
            exit();
            
        case 'chitietyeucau':
            $controller->chiTietYeuCau();
            exit();
            
        case 'xulysuadiem':
            $controller->xuLyGuiYeuCauSuaDiem();
            exit();
            
        case 'xulynghiphep':
            $controller->xuLyGuiYeuCauNghiPhep();
            exit();
            
        case 'download':
            $controller->downloadMinhChung();
            exit();
    }
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
            margin: 0;
            font-size: 24px;
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
            font-size: 16px;
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
            gap: 32px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 8px 24px 24px 24px;
        }

        .card-info {
            margin-bottom: 32px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 8px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: #5081BE;
            font-size: 16px;
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
            font-size: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-weight: 400;
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


            <!-- Thông tin hệ thống -->
            <div class="card card-info">
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
                        <a href="../../controller/cTeachingSchedule.php?action=dashboard" class="quick-link">
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
                        <a href="<?php echo url('view/teacher/vInsertGrade.php'); ?>" class="quick-link">
                            <i class="fas fa-edit"></i>
                            <span>Nhập điểm</span>
                        </a>
                        <a href="<?php echo url('view/teacher/vSubmitExam.php'); ?>" class="quick-link">
                            <i class="fas fa-file-upload"></i>
                            <span>Gửi đề thi</span>
                        </a>
                        <a href="<?php echo url('view/teacher/vAssignHomework.php'); ?>" class="quick-link">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Giao bài tập</span>
                        </a>
                    </div>
                </div>

                <!-- Gửi yêu cầu -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-paper-plane"></i> Gửi yêu cầu
                        </h2>
                        <a href="dashboard.php?action=danhsachyeucau" class="view-link">
                            Xem danh sách <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="quick-links">
                        <a href="dashboard.php?action=yeucau&type=suadiem" class="quick-link">
                            <i class="fas fa-edit"></i>
                            <span>Yêu cầu sửa điểm</span>
                        </a>
                        <a href="dashboard.php?action=yeucau&type=nghiphep" class="quick-link">
                            <i class="fas fa-calendar-times"></i>
                            <span>Yêu cầu nghỉ phép</span>
                        </a>
                        <a href="dashboard.php?action=danhsachyeucau" class="quick-link">
                            <i class="fas fa-list-alt"></i>
                            <span>Danh sách yêu cầu đã gửi</span>
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

                <!-- Xem thông tin lớp chủ nhiệm -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-chalkboard-teacher"></i> Lớp chủ nhiệm
                        </h2>
                    </div>
                    <div class="quick-links">
                        <a href="../../view/teacher/vListofStudents.php" class="quick-link">
                            <i class="fas fa-users"></i>
                            <span>Danh sách học sinh</span>
                        </a>
                        <a href="../../view/teacher/vClassPerformance.php" class="quick-link">
                            <i class="fas fa-chart-line"></i>
                            <span>Kết quả học tập và rèn luyện lớp</span>
                        </a>
                        <a href="../../view/teacher/vStudentClassification.php" class="quick-link">
                            <i class="fas fa-star"></i>
                            <span>Xếp loại học sinh</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>