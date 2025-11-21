<?php
// Lấy thông tin từ session (đã được kiểm tra ở controller)
$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ban giám hiệu</title>
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
            font-size: 28px;
        }

        .header h1 i {
            color: #2a5298;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-name {
            color: #666;
            font-weight: 600;
        }

        .btn-group {
            display: flex;
            gap: 10px;
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
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

        .logout-btn:hover {
            background: #c82333;
        }

        .main-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .welcome-section {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .welcome-section h2 {
            color: #1e3c72;
            font-size: 32px;
            margin-bottom: 15px;
        }

        .welcome-section p {
            color: #555;
            font-size: 18px;
            line-height: 1.6;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .quick-link-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            color: white;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .quick-link-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .quick-link-card i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }

        .quick-link-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .quick-link-card p {
            font-size: 14px;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .quick-links {
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
                <i class="fas fa-school"></i>
                Hệ thống Quản lý Giáo dục - Ban giám hiệu
            </h1>
            <div class="user-info">
                <span class="user-name">
                    <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($hoTen); ?>
                </span>
                <a href="../public/index.php?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="welcome-section">
                <h2>Chào mừng Ban giám hiệu!</h2>
                <p>Hệ thống cung cấp các công cụ quản lý và báo cáo toàn diện về hoạt động giáo dục của nhà trường.</p>
            </div>

            <!-- Quick Links -->
            <div class="quick-links">
                <a href="../../controller/cBGHReport.php?action=index" class="quick-link-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Xem báo cáo</h3>
                    <p>Xem các báo cáo và thống kê chi tiết về học tập, giảng dạy của nhà trường</p>
                </a>

                <a href="../../controller/cYeuCau.php?action=danhsach" class="quick-link-card">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>Xử lý yêu cầu</h3>
                    <p>Xử lý các yêu cầu nghỉ phép và sửa điểm từ giáo viên</p>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
