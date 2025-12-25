<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập và quyền TTBM
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'ttbm') {
    header("Location: ../../public/index.php");
    exit;
}

// Lấy thông tin TTBM
require_once(__DIR__ . '/../../model/mExamGradingAssignment.php');
$model = new mExamGradingAssignment();
$ttbmInfo = $model->getTTBMInfo($_SESSION['maTaiKhoan']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công kỳ thi - TTBM</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }

        .header-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content h1 {
            font-size: 28px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-content h1 .icon {
            font-size: 36px;
        }

        .user-info {
            text-align: right;
        }

        .user-info h3 {
            font-size: 16px;
            color: #667eea;
            margin-bottom: 5px;
        }

        .user-info p {
            color: #666;
            font-size: 13px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
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
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .selection-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .selection-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }

        .selection-card.grading::before {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .selection-card.proctoring::before {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        }

        .selection-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
        }

        .selection-card .icon {
            font-size: 72px;
            margin-bottom: 20px;
        }

        .selection-card.grading .icon {
            color: #667eea;
        }

        .selection-card.proctoring .icon {
            color: #27ae60;
        }

        .selection-card h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }

        .selection-card p {
            color: #666;
            line-height: 1.6;
            font-size: 15px;
        }

        .selection-card .arrow {
            margin-top: 25px;
            font-size: 24px;
            color: #999;
            transition: all 0.3s;
        }

        .selection-card:hover .arrow {
            color: #667eea;
            transform: translateX(10px);
        }

        .selection-card.grading:hover .arrow {
            color: #667eea;
        }

        .selection-card.proctoring:hover .arrow {
            color: #27ae60;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: white;
            font-size: 14px;
        }

        .breadcrumb a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumb a:hover {
            color: white;
        }

        .breadcrumb .separator {
            color: rgba(255,255,255,0.5);
        }

        .breadcrumb .current {
            color: white;
            font-weight: 600;
        }

        .features {
            margin-top: 20px;
            text-align: left;
        }

        .features li {
            padding: 8px 0;
            color: #555;
            font-size: 14px;
            list-style: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .features li::before {
            content: '✓';
            color: #27ae60;
            font-weight: bold;
        }

        .disabled-card {
            opacity: 0.6;
            pointer-events: none;
        }

        .coming-soon {
            position: absolute;
            top: 15px;
            right: -30px;
            background: #f39c12;
            color: white;
            padding: 5px 40px;
            font-size: 12px;
            font-weight: bold;
            transform: rotate(45deg);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">🏠 Dashboard</a>
            <span class="separator">›</span>
            <span class="current">Phân công kỳ thi</span>
        </div>

        <div class="header-card">
            <div class="header-content">
                <h1>
                    <span class="icon">📋</span>
                    Phân công kỳ thi
                </h1>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($ttbmInfo['hoTen'] ?? 'TTBM'); ?></h3>
                    <p>Tổ bộ môn: <strong><?php echo htmlspecialchars($ttbmInfo['toBoMon'] ?? 'N/A'); ?></strong></p>
                    <div style="margin-top: 10px; display: flex; gap: 10px; justify-content: flex-end;">
                        <a href="index.php" class="btn btn-secondary">← Quay lại</a>
                        <a href="../../public/logout.php" class="btn btn-danger">🚪 Đăng xuất</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="selection-grid">
            <!-- Phân công chấm điểm -->
            <a href="vExamGradingAssignment.php" class="selection-card grading">
                <div class="icon">📝</div>
                <h2>Phân công chấm điểm</h2>
                <p>Phân công giáo viên trong tổ bộ môn chấm điểm bài thi theo kỳ thi, lớp và môn học.</p>
                <ul class="features">
                    <li>Tìm kiếm theo Lớp, Môn thi</li>
                    <li>Xem danh sách bài thi cần chấm</li>
                    <li>Phân công 1 hoặc 2 giáo viên</li>
                    <li>Lọc giáo viên chưa phân công</li>
                    <li>Gửi thông báo tự động</li>
                </ul>
                <div class="arrow">→</div>
            </a>

            <!-- Phân công coi thi -->
            <a href="vExamProctorAssignment.php" class="selection-card proctoring">
                <div class="icon">👁️</div>
                <h2>Phân công coi thi</h2>
                <p>Phân công giáo viên coi thi cho các phòng thi trong kỳ thi.</p>
                <ul class="features">
                    <li>Xem danh sách phòng thi</li>
                    <li>Phân công giám thị</li>
                    <li>Quản lý ca thi</li>
                    <li>In danh sách phân công</li>
                </ul>
                <div class="arrow">→</div>
            </a>
        </div>
    </div>
</body>
</html>
