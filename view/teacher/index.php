<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

// Kiểm tra quyền giáo viên
if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: ../../public/index.php?error=access_denied");
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
    <title>Trang chủ Giáo viên - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #667eea;
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info span {
            color: #666;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .menu-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .menu-card .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 36px;
            color: white;
        }

        .menu-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .menu-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .menu-card.primary {
            border: 2px solid #667eea;
        }

        .badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }

        .info-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .info-box h3 {
            color: #667eea;
            margin-bottom: 15px;
        }

        .info-box ul {
            list-style: none;
            padding-left: 0;
        }

        .info-box ul li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }

        .info-box ul li:last-child {
            border-bottom: none;
        }

        .info-box ul li strong {
            color: #333;
        }

        .info-box ul li span {
            color: #666;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
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
            <div>
                <h1><i class="fas fa-chalkboard-teacher"></i> Trang Giáo viên</h1>
                <p style="color: #666; margin-top: 5px;">Hệ thống Quản lý Giáo dục</p>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <strong><?php echo htmlspecialchars($hoTen); ?></strong></span>
                <a href="../../public/index.php?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <!-- Thông tin tài khoản -->
        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> Thông tin tài khoản</h3>
            <ul>
                <li>
                    <strong>Họ tên:</strong>
                    <span><?php echo htmlspecialchars($hoTen); ?></span>
                </li>
                <li>
                    <strong>Tên đăng nhập:</strong>
                    <span><?php echo htmlspecialchars($tenDangNhap); ?></span>
                </li>
                <li>
                    <strong>Loại tài khoản:</strong>
                    <span>Giáo viên</span>
                </li>
                <li>
                    <strong>Mã giáo viên:</strong>
                    <span><?php echo $maGV ? $maGV : '<em style="color: #dc3545;">Chưa liên kết</em>'; ?></span>
                </li>
            </ul>
        </div>

        <!-- Menu chức năng -->
        <div class="menu-grid">
            <!-- Chức năng Tra cứu lịch dạy -->
            <a href="../../controller/cTeachingSchedule.php?action=dashboard" class="menu-card primary">
                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Tra cứu Lịch dạy</h3>
                <p>Xem lịch giảng dạy, danh sách lớp, phân công coi thi và chấm điểm</p>
                <span class="badge">Mới</span>
            </a>

            <!-- Quản lý điểm -->
            <a href="#" class="menu-card" onclick="alert('Chức năng đang phát triển!'); return false;">
                <div class="icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>Quản lý Điểm</h3>
                <p>Nhập và quản lý điểm số học sinh</p>
            </a>

            <!-- Quản lý học sinh -->
            <a href="#" class="menu-card" onclick="alert('Chức năng đang phát triển!'); return false;">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Danh sách Học sinh</h3>
                <p>Xem thông tin học sinh trong các lớp phụ trách</p>
            </a>

            <!-- Báo cáo -->
            <a href="#" class="menu-card" onclick="alert('Chức năng đang phát triển!'); return false;">
                <div class="icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3>Báo cáo</h3>
                <p>Xem các báo cáo thống kê và phân tích</p>
            </a>

            <!-- Tài liệu -->
            <a href="#" class="menu-card" onclick="alert('Chức năng đang phát triển!'); return false;">
                <div class="icon">
                    <i class="fas fa-book"></i>
                </div>
                <h3>Tài liệu</h3>
                <p>Quản lý tài liệu giảng dạy và bài tập</p>
            </a>

            <!-- Thông báo -->
            <a href="#" class="menu-card" onclick="alert('Chức năng đang phát triển!'); return false;">
                <div class="icon">
                    <i class="fas fa-bell"></i>
                </div>
                <h3>Thông báo</h3>
                <p>Xem và gửi thông báo đến học sinh</p>
            </a>
        </div>
    </div>
</body>
</html>
