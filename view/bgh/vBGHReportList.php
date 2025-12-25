<?php
// Lấy thông tin từ session (đã được kiểm tra ở controller)
$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách báo cáo - Ban giám hiệu</title>
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
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

        .btn-primary {
            background: #2a5298;
            color: white;
        }

        .btn-primary:hover {
            background: #1e3c72;
            transform: translateY(-2px);
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

        .page-title {
            color: #1e3c72;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2a5298;
            font-size: 24px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }

        .report-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            border-left: 5px solid #2a5298;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .report-card h3 {
            color: #1e3c72;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
        }

        .report-card h3 i {
            font-size: 28px;
            color: #2a5298;
        }

        .report-card p {
            color: #555;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .report-card .btn {
            width: 100%;
            justify-content: center;
            font-weight: 600;
        }

        .intro-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #2a5298;
        }

        .intro-section h2 {
            color: #1e3c72;
            margin-bottom: 10px;
        }

        .intro-section p {
            color: #666;
            line-height: 1.8;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .report-grid {
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
                <div class="btn-group">
                    <a href="../view/bgh/vBGHDashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                    <a href="../public/index.php?logout=1" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h2 class="page-title">
                <i class="fas fa-chart-line"></i> Danh sách Báo cáo & Thống kê
            </h2>

            <div class="intro-section">
                <h2>Chào mừng Ban giám hiệu!</h2>
                <p>Hệ thống cung cấp các báo cáo và thống kê toàn diện về tình hình học tập và giảng dạy của nhà trường. 
                Quý BGH có thể xem chi tiết, lọc dữ liệu theo nhiều tiêu chí khác nhau và xuất báo cáo ra file Excel để phục vụ công tác quản lý.</p>
            </div>

            <!-- Report Grid -->
            <div class="report-grid">
                <!-- Báo cáo kết quả học tập -->
                <div class="report-card">
                    <h3>
                        <i class="fas fa-graduation-cap"></i>
                        Báo cáo kết quả học tập
                    </h3>
                    <p>Xem chi tiết điểm số, kết quả học tập của học sinh theo lớp, môn học, học kỳ. Hỗ trợ xuất báo cáo Excel.</p>
                    <a href="cBGHReport.php?action=hoc-tap" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Xem báo cáo
                    </a>
                </div>

                <!-- Báo cáo chuyên cần -->
                <div class="report-card">
                    <h3>
                        <i class="fas fa-calendar-check"></i>
                        Báo cáo chuyên cần
                    </h3>
                    <p>Theo dõi tình hình điểm danh, số ngày nghỉ có phép/không phép của học sinh theo lớp và học kỳ.</p>
                    <a href="cBGHReport.php?action=chuyen-can" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Xem báo cáo
                    </a>
                </div>

                <!-- Báo cáo giảng dạy -->
                <div class="report-card">
                    <h3>
                        <i class="fas fa-chalkboard-teacher"></i>
                        Báo cáo giảng dạy
                    </h3>
                    <p>Xem hoạt động giảng dạy của giáo viên, phân công giảng dạy, số tiết dạy theo môn học và lớp.</p>
                    <a href="cBGHReport.php?action=giang-day" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Xem báo cáo
                    </a>
                </div>

                <!-- Báo cáo tổng hợp -->
                <div class="report-card">
                    <h3>
                        <i class="fas fa-file-contract"></i>
                        Báo cáo tổng hợp
                    </h3>
                    <p>Tổng hợp kết quả học tập toàn trường, phân loại học sinh theo lớp, khối, học kỳ.</p>
                    <a href="cBGHReport.php?action=tong-hop" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Xem báo cáo
                    </a>
                </div>

                <!-- Báo cáo danh hiệu học sinh -->
                <div class="report-card">
                    <h3>
                        <i class="fas fa-medal"></i>
                        Báo cáo kết quả đánh giá
                    </h3>
                    <p>Xét và cập nhật danh hiệu học sinh dựa trên học lực, hạnh kiểm, vi phạm và khen thưởng.</p>
                    <a href="cBGHReport.php?action=ket-qua-danh-gia" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Xem báo cáo
                    </a>
                </div>

                <!-- Thống kê điểm môn học -->
                <div class="report-card">
                    <h3>
                        <i class="fas fa-chart-bar"></i>
                        Thống kê điểm môn học
                    </h3>
                    <p>Thống kê phân bố điểm số theo môn học, tính điểm trung bình, cao nhất, thấp nhất và phân loại.</p>
                    <a href="cBGHReport.php?action=thong-ke-diem" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Xem thống kê
                    </a>
                </div>

                <!-- Báo cáo đã nộp -->
                <div class="report-card">
                    <h3>
                        <i class="fas fa-file-upload"></i>
                        Báo cáo đã nộp từ GV
                    </h3>
                    <p>Xem danh sách các báo cáo mà giáo viên đã nộp lên hệ thống, tải xuống và theo dõi.</p>
                    <a href="cBGHReport.php?action=bao-cao-da-nop" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Xem báo cáo
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
