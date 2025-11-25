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
require_once(__DIR__ . '/../../model/mGradingAssignment.php');
$mGrading = new mGradingAssignment();
$ttbmInfo = $mGrading->getTTBMInfo($_SESSION['maTaiKhoan']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tổ trưởng bộ môn</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-card {
            background: white;
            border-radius: 10px;
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
            font-size: 32px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            text-align: right;
        }

        .user-info h3 {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 5px;
        }

        .user-info p {
            color: #666;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .dashboard-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .dashboard-card h3 {
            font-size: 22px;
            color: #333;
            margin-bottom: 10px;
        }

        .dashboard-card p {
            color: #666;
            line-height: 1.6;
        }

        .card-grading {
            border-top: 4px solid #667eea;
        }

        .card-grading:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .card-exam {
            border-top: 4px solid #27ae60;
        }

        .card-exam:hover {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.05) 0%, rgba(46, 204, 113, 0.05) 100%);
        }

        .stats-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .stats-section h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-box .number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-box .label {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-card">
            <div class="header-content">
                <h1>🎓 Dashboard Tổ trưởng bộ môn</h1>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($ttbmInfo['hoTen'] ?? 'TTBM'); ?></h3>
                    <p>Tổ bộ môn: <strong><?php echo htmlspecialchars($ttbmInfo['toBoMon'] ?? 'N/A'); ?></strong></p>
                    <a href="../../public/logout.php" class="btn btn-danger" style="margin-top: 10px;">🚪 Đăng xuất</a>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <a href="vGradingAssignment.php" class="dashboard-card card-grading">
                <div class="icon">📝</div>
                <h3>Phân công chấm thi</h3>
                <p>Quản lý phân công chấm thi, chấm điểm cho giáo viên trong tổ bộ môn.</p>
            </a>

            <a href="vExamApproval.php" class="dashboard-card card-exam">
                <div class="icon">📋</div>
                <h3>Duyệt đề thi</h3>
                <p>Quản lý và duyệt đề thi của tổ bộ môn. Phê duyệt hoặc từ chối đề thi.</p>
            </a>
        </div>

        <div class="stats-section">
            <h2>📊 Thống kê Tổng quan</h2>
            <div class="stats-grid">
                <?php
                // Đếm số phân công theo tổ bộ môn
                include_once("../../model/mConnect.php");
                $db = new mConnect();
                $conn = $db->mConnect();

                $toBoMon = $ttbmInfo['toBoMon'];
                
                // Tổng phân công
                $sql = "SELECT COUNT(*) as total 
                        FROM phancongchamdiem pc
                        LEFT JOIN giaovien gv ON pc.maGV = gv.maGV
                        WHERE gv.toBoMon = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $toBoMon);
                $stmt->execute();
                $total = $stmt->get_result()->fetch_assoc()['total'];

                // Phân công pending
                $sql = "SELECT COUNT(*) as pending 
                        FROM phancongchamdiem pc
                        LEFT JOIN giaovien gv ON pc.maGV = gv.maGV
                        WHERE gv.toBoMon = ? AND pc.trangThai = 'pending'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $toBoMon);
                $stmt->execute();
                $pending = $stmt->get_result()->fetch_assoc()['pending'];

                // Phân công in_progress
                $sql = "SELECT COUNT(*) as progress 
                        FROM phancongchamdiem pc
                        LEFT JOIN giaovien gv ON pc.maGV = gv.maGV
                        WHERE gv.toBoMon = ? AND pc.trangThai = 'in_progress'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $toBoMon);
                $stmt->execute();
                $progress = $stmt->get_result()->fetch_assoc()['progress'];

                // Phân công completed
                $sql = "SELECT COUNT(*) as completed 
                        FROM phancongchamdiem pc
                        LEFT JOIN giaovien gv ON pc.maGV = gv.maGV
                        WHERE gv.toBoMon = ? AND pc.trangThai = 'completed'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $toBoMon);
                $stmt->execute();
                $completed = $stmt->get_result()->fetch_assoc()['completed'];

                // Số giáo viên trong tổ
                $sql = "SELECT COUNT(*) as teachers FROM giaovien WHERE toBoMon = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $toBoMon);
                $stmt->execute();
                $teachers = $stmt->get_result()->fetch_assoc()['teachers'];
                ?>

                <div class="stat-box">
                    <div class="number"><?php echo $total; ?></div>
                    <div class="label">Tổng phân công</div>
                </div>

                <div class="stat-box">
                    <div class="number"><?php echo $pending; ?></div>
                    <div class="label">Chưa bắt đầu</div>
                </div>

                <div class="stat-box">
                    <div class="number"><?php echo $progress; ?></div>
                    <div class="label">Đang chấm</div>
                </div>

                <div class="stat-box">
                    <div class="number"><?php echo $completed; ?></div>
                    <div class="label">Hoàn thành</div>
                </div>

                <div class="stat-box">
                    <div class="number"><?php echo $teachers; ?></div>
                    <div class="label">Giáo viên trong tổ</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
