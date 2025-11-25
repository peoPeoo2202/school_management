<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Quản Trị - Hệ thống Quản lý Trường học</title>
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info .welcome {
            color: #666;
            font-size: 14px;
        }

        .user-info .username {
            color: #667eea;
            font-weight: bold;
            font-size: 16px;
        }

        .btn-logout {
            padding: 10px 20px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }

        .dashboard-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .dashboard-card h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .dashboard-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .card-accounts { border-left: 4px solid #667eea; }
        .card-students { border-left: 4px solid #28a745; }
        .card-teachers { border-left: 4px solid #17a2b8; }
        .card-reports { border-left: 4px solid #ffc107; }
        .card-settings { border-left: 4px solid #6c757d; }
        .card-schedule { border-left: 4px solid #e83e8c; }

        .stats-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stats-section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-box {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-align: center;
        }

        .stat-box .number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-box .label {
            font-size: 14px;
            opacity: 0.9;
        }

        .quick-actions {
            margin-top: 30px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .quick-actions h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="header">
            <h1>🎓 Trang Quản Trị Hệ Thống</h1>
            <div class="user-info">
                <div>
                    <div class="welcome">Xin chào,</div>
                    <div class="username"><?php echo htmlspecialchars($_SESSION['hoTen']); ?></div>
                </div>
                <a href="../../public/index.php?logout=1" class="btn-logout">Đăng xuất</a>
            </div>
        </div>

        <div class="dashboard-grid">
            <a href="vAccountList.php" class="dashboard-card card-accounts">
                <div class="icon">👥</div>
                <h3>Quản lý Tài khoản</h3>
                <p>Tạo, sửa, xóa tài khoản người dùng. Phân quyền và quản lý truy cập hệ thống.</p>
            </a>

            <a href="vStudentList.php" class="dashboard-card card-students">
                <div class="icon">🎒</div>
                <h3>Quản lý Học sinh</h3>
                <p>Quản lý thông tin học sinh, lớp học, điểm số và kết quả học tập.</p>
            </a>

            <a href="vTeacherList.php" class="dashboard-card card-teachers">
                <div class="icon">👨‍🏫</div>
                <h3>Quản lý Giáo viên</h3>
                <p>Quản lý thông tin giáo viên, phân công giảng dạy và lịch làm việc.</p>
            </a>

            <a href="vTimetableList.php" class="dashboard-card card-schedule">
                <div class="icon">📅</div>
                <h3>Thời khóa biểu</h3>
                <p>Xếp lịch học, phân công giảng dạy và quản lý thời gian biểu.</p>
            </a>

            
        </div>

        <div class="stats-section">
            <h2>📈 Thống kê Tổng quan</h2>
            <div class="stats-grid">
                <?php
                // Lấy thống kê từ database
                include_once("../../model/mConnect.php");
                $db = new mConnect();
                $conn = $db->mConnect();

                // Đếm số tài khoản
                $result = $conn->query("SELECT COUNT(*) as total FROM taikhoan WHERE trangThaiTaiKhoan = 1");
                $accounts = $result->fetch_assoc()['total'];

                // Đếm số học sinh
                $result = $conn->query("SELECT COUNT(*) as total FROM hocsinh");
                $students = $result->fetch_assoc()['total'];

                // Đếm số giáo viên
                $result = $conn->query("SELECT COUNT(*) as total FROM giaovien");
                $teachers = $result->fetch_assoc()['total'];

                // Đếm số lớp học
                $result = $conn->query("SELECT COUNT(*) as total FROM lophoc");
                $classes = $result->fetch_assoc()['total'];

                $db->mDisconnect($conn);
                ?>
                <div class="stat-box">
                    <div class="number"><?php echo $accounts; ?></div>
                    <div class="label">Tài khoản</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo $students; ?></div>
                    <div class="label">Học sinh</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo $teachers; ?></div>
                    <div class="label">Giáo viên</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo $classes; ?></div>
                    <div class="label">Lớp học</div>
                </div>
            </div>
        </div>

        <div class="quick-actions">
            <h2>⚡ Thao tác Nhanh</h2>
            <div class="action-buttons">
                <a href="vAccountList.php" class="action-btn">➕ Tạo tài khoản mới</a>
                <a href="#" class="action-btn">📝 Nhập điểm</a>
                <a href="#" class="action-btn">📋 Xem báo cáo</a>
                <a href="#" class="action-btn">🔔 Gửi thông báo</a>
                <a href="#" class="action-btn">📊 Xuất dữ liệu</a>
            </div>
        </div>
    </div>
</body>
</html>
