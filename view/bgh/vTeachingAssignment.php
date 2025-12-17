<?php
/**
 * View: vTeachingAssignment.php
 * Trang chính phân công giảng dạy - Menu lựa chọn
 */
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

// Kiểm tra quyền truy cập
if ($_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công Giảng dạy - BGH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px 35px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #1e3c72;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 24px;
        }

        .header h1 i {
            color: #2a5298;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .main-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title h2 {
            color: #1e3c72;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-title p {
            color: #666;
            font-size: 16px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .menu-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .menu-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        .menu-card.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .menu-card.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .menu-card.blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .menu-card-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .menu-card h3 {
            font-size: 22px;
            margin-bottom: 15px;
        }

        .menu-card p {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .menu-card .arrow {
            position: absolute;
            right: 25px;
            bottom: 25px;
            font-size: 24px;
            opacity: 0;
            transition: all 0.3s;
        }

        .menu-card:hover .arrow {
            opacity: 1;
            right: 20px;
        }

        .sub-menu {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }

        .sub-menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .sub-menu-item i {
            width: 20px;
        }

        .breadcrumb {
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .breadcrumb a {
            color: #1e3c72;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb span {
            color: #666;
            margin: 0 10px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-tasks"></i>
                Phân công Giảng dạy
            </h1>
            <div class="user-info">
                <span><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($hoTen); ?></span>
                <a href="vBGHDashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <a href="../../public/logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="breadcrumb">
                <a href="vBGHDashboard.php"><i class="fas fa-home"></i> Trang chủ</a>
                <span>›</span>
                <strong>Phân công Giảng dạy</strong>
            </div>

            <div class="page-title">
                <h2><i class="fas fa-chalkboard-teacher"></i> Quản lý Phân công Giảng dạy</h2>
                <p>Chọn loại phân công bạn muốn thực hiện</p>
            </div>

            <div class="menu-grid">
                <!-- Phân công Lớp - Phòng học -->
                <a href="../../controller/cTeachingAssignment.php?action=studentAssignment" class="menu-card">
                    <div class="menu-card-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>Phân công Lớp cho Học sinh</h3>
                    <p>Phân công lớp cho học sinh đầu cấp. Hỗ trợ phân lớp thủ công hoặc tự động theo các tiêu chí.</p>
                    <div class="sub-menu">
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Phân lớp học sinh đầu cấp
                        </div>
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Phân lớp tự động
                        </div>
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Chia đều nam/nữ
                        </div>
                    </div>
                    <i class="fas fa-arrow-right arrow"></i>
                </a>

                <!-- Phân công Phòng học -->
                <a href="../../controller/cTeachingAssignment.php?action=roomAssignment" class="menu-card green">
                    <div class="menu-card-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h3>Phân công Phòng học</h3>
                    <p>Phân công phòng học cho các lớp. Kiểm tra tránh trùng phòng và phòng bảo trì.</p>
                    <div class="sub-menu">
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Xem lớp chưa có phòng
                        </div>
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Xem phòng trống
                        </div>
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Kiểm tra trùng phòng
                        </div>
                    </div>
                    <i class="fas fa-arrow-right arrow"></i>
                </a>

                <!-- Phân công GVBM -->
                <a href="../../controller/cTeachingAssignment.php?action=subjectTeacherAssignment" class="menu-card orange">
                    <div class="menu-card-icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <h3>Phân công Giáo viên Bộ môn</h3>
                    <p>Phân công giáo viên dạy các môn học cho từng lớp theo học kỳ.</p>
                    <div class="sub-menu">
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Tìm kiếm theo khối/lớp/môn
                        </div>
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Áp dụng cho cả 2 học kỳ
                        </div>
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Sửa/Xóa phân công
                        </div>
                    </div>
                    <i class="fas fa-arrow-right arrow"></i>
                </a>

                <!-- Phân công GVCN -->
                <a href="../../controller/cTeachingAssignment.php?action=homeroomTeacherAssignment" class="menu-card blue">
                    <div class="menu-card-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Phân công Giáo viên Chủ nhiệm</h3>
                    <p>Phân công giáo viên chủ nhiệm cho các lớp theo năm học. Kiểm tra trùng chủ nhiệm.</p>
                    <div class="sub-menu">
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Xem theo khối/năm học
                        </div>
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Xem GV chưa phân công
                        </div>
                        <div class="sub-menu-item">
                            <i class="fas fa-check"></i> Kiểm tra trùng CN
                        </div>
                    </div>
                    <i class="fas fa-arrow-right arrow"></i>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
