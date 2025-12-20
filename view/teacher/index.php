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
                <div class="header-left dashboard-header-left">
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
                                <strong>Mã giáo viên:</strong>
                                <p><?php echo $maGV ? $maGV : '<em style="color: #dc3545;">Chưa liên kết</em>'; ?></p>
                            </span>
                            <span>
                                <strong>Loại tài khoản:</strong>
                                <p>Giáo viên</p>
                            </span>
                        </div>
                    </div>
                    <div class="info-box">
                        <h3><i class="fas fa-user"></i> Lớp chủ nhiệm</h3>
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
                                <strong>Mã giáo viên:</strong>
                                <p><?php echo $maGV ? $maGV : '<em style="color: #dc3545;">Chưa liên kết</em>'; ?></p>
                            </span>
                            <span>
                                <strong>Loại tài khoản</strong>
                                <p>Giáo viên</p>
                            </span>
                        </div>
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
                    </div>
                    <div class="quick-links">
                        <a href="../../view/teacher/vTeachingSchedule.php" class="quick-link <?php echo (basename($_SERVER['PHP_SELF']) == 'vTeachingSchedule.php') ? 'active' : ''; ?>">
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
                    </div>
                    <div class="quick-links">
                        <a href="index.php?action=yeucau&type=suadiem" class="quick-link">
                            <i class="fas fa-edit"></i>
                            <span>Yêu cầu sửa điểm</span>
                        </a>
                        <a href="index.php?action=yeucau&type=nghiphep" class="quick-link">
                            <i class="fas fa-calendar-times"></i>
                            <span>Yêu cầu nghỉ phép</span>
                        </a>
                        <a href="index.php?action=danhsachyeucau" class="quick-link">
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


            </div>
        </div>
    </div>
</body>

</html>