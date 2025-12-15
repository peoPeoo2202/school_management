<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$maGV = $_SESSION['maGV'] ?? null;

// Load data from model
require_once(__DIR__ . '/../../model/mConnect.php');
require_once(__DIR__ . '/../../model/mClassPerformance.php');

$mConnect = new mConnect();
$conn = $mConnect->mConnect();

if (!$conn) {
    die("Kết nối thất bại!");
}

$model = new ModelClassPerformance($conn);

// Lấy danh sách lớp chủ nhiệm của giáo viên
$classes = $model->getClassesByTeacher($maGV);

// Lấy maLop từ URL hoặc lớp đầu tiên
$maLop = $_GET['maLop'] ?? null;
if (!$maLop && !empty($classes)) {
    $maLop = $classes[0]['maLop'];
}

// Khởi tạo biến $data
$data = [];

if ($maLop && $maGV) {
    // Lấy thông tin lớp
    $classInfo = $model->getClassInfo($maLop);
    
    // Kiểm tra giáo viên có phải là GVCN của lớp này không
    if (!$classInfo || $classInfo['maGV'] != $maGV) {
        $data['error'] = 'Bạn không có quyền xem thông tin của lớp này';
    } else {
        // Lấy thông tin học kỳ và năm học hiện tại
        $currentYear = date('Y');
        $namHoc = ($currentYear - 1) . '-' . $currentYear;
        $hocKy = (date('m') <= 6) ? 2 : 1;
        
        // Lấy các thống kê
        $averageScore = $model->getClassAverageScore($maLop, $hocKy, $namHoc);
        $yearlyAverage = $model->getClassYearlyAverage($maLop, $namHoc);
        $awardsCount = $model->getAwardsCount($maLop, $hocKy, $namHoc);
        $awardsByLevel = $model->getAwardsByLevel($maLop, $hocKy, $namHoc);
        
        // Lấy danh sách học sinh được khen thưởng theo cấp (chi tiết)
        $awardsByLevelDetail = [];
        foreach ($awardsByLevel as $award) {
            $capKhenThuong = $award['capKhenThuong'];
            $awardsByLevelDetail[$capKhenThuong] = $model->getAwardsByLevelDetail($maLop, $hocKy, $namHoc, $capKhenThuong);
        }
        
        $violationsCount = $model->getViolationsCount($maLop, $hocKy, $namHoc);
        
        // Lấy danh sách học sinh vi phạm theo mức độ
        $violationsNhe = $model->getViolationsByLevel($maLop, $hocKy, $namHoc, 'Nhe');
        $violationsTB = $model->getViolationsByLevel($maLop, $hocKy, $namHoc, 'Trung binh');
        $violationsNang = $model->getViolationsByLevel($maLop, $hocKy, $namHoc, 'Nang');
        
        $conductStats = $model->getConductStatistics($maLop, $hocKy, $namHoc);
        $academicStats = $model->getAcademicStatistics($maLop, $hocKy, $namHoc);
        
        // Lấy thống kê nghỉ học
        $absenceStats = $model->getAbsenceStatistics($maLop, $hocKy, $namHoc);
        $topAbsentStudents = $model->getTopAbsentStudents($maLop, $hocKy, $namHoc, 5);
        
        $data = [
            'classes' => $classes,
            'classInfo' => $classInfo,
            'currentClassId' => $maLop,
            'hocKy' => $hocKy,
            'namHoc' => $namHoc,
            'averageScore' => $averageScore,
            'yearlyAverage' => $yearlyAverage,
            'awardsCount' => $awardsCount,
            'awardsByLevel' => $awardsByLevel,
            'awardsByLevelDetail' => $awardsByLevelDetail,
            'violationsCount' => $violationsCount,
            'violationsNhe' => $violationsNhe,
            'violationsTB' => $violationsTB,
            'violationsNang' => $violationsNang,
            'conductStats' => $conductStats,
            'academicStats' => $academicStats,
            'absenceStats' => $absenceStats,
            'topAbsentStudents' => $topAbsentStudents
        ];
    }
} else if (empty($classes)) {
    $data['error'] = 'Bạn chưa được phân công làm giáo viên chủ nhiệm lớp nào';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả học tập và rèn luyện - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
        }

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
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
        }

        .header-left {
            display: flex;
            justify-content: space-between;
            flex-direction: column;
        }

        .header-left-icon {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #5081BE;
            font-weight: 600;
        }

        .header-left h2 {
            margin: 0;
            font-size: 24px;
        }

        .header-left p {
            color: #999;
            font-size: 14px;
            margin: 0;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            padding-bottom: 16px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: #5081BE;
            font-size: 20px;
        }

        .class-info {
            background: linear-gradient(135deg, #5081BE15 0%, #4a6fa515 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #5081BE;
        }

        .class-info h3 {
            color: #5081BE;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
        }

        .class-info p {
            margin: 8px 0;
            color: #555;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 24px;
            border-radius: 12px;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .stat-card.success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        }

        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card h4 {
            margin: 0 0 15px 0;
            font-size: 14px;
            font-weight: 500;
            opacity: 0.9;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 13px;
            opacity: 0.9;
        }

        .chart-container {
            margin-top: 20px;
        }

        .conduct-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .conduct-label {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            color: #333;
        }

        .conduct-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .conduct-icon.tot {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .conduct-icon.kha {
            background: #bbdefb;
            color: #1976d2;
        }

        .conduct-icon.tb {
            background: #fff9c4;
            color: #f57f17;
        }

        .conduct-icon.yeu {
            background: #ffcdd2;
            color: #c62828;
        }

        .conduct-stats {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .conduct-count {
            font-size: 24px;
            font-weight: 700;
            color: #5081BE;
        }

        .conduct-percent {
            font-size: 16px;
            color: #666;
            font-weight: 500;
        }

        .progress-bar {
            width: 100px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #5081BE;
            transition: width 0.3s;
        }

        .awards-list,
        .violations-list {
            margin-top: 15px;
        }

        .award-item,
        .violation-item {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            background: #f9f9f9;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 500;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #c62828;
        }

        /* Class Selector Styles */
        .class-selector {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .class-selector h3 {
            color: #5081BE;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .class-selector h3 i {
            font-size: 18px;
        }

        .class-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .class-btn {
            padding: 12px 20px;
            background: #f5f5f5;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 120px;
        }

        .class-btn:hover {
            background: #E0F2FC;
            border-color: #5081BE;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(80, 129, 190, 0.2);
        }

        .class-btn.active {
            background: linear-gradient(135deg, #5081BE 0%, #4a6fa5 100%);
            border-color: #5081BE;
            color: white;
        }

        .class-btn-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .class-btn-info {
            font-size: 12px;
            opacity: 0.8;
        }

        /* Tooltip Styles - Enhanced - Position to Right */
        .tooltip-container {
            position: relative;
            display: inline-block;
        }

        .tooltip-trigger {
            cursor: help;
            border-bottom: 1px dashed #666;
        }

        .tooltip-content {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            z-index: 10000;
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            min-width: 300px;
            max-width: 450px;
            left: 105%;
            top: 50%;
            transform: translateY(-50%);
            transition: opacity 0.3s, visibility 0.3s;
            font-size: 13px;
        }

        .tooltip-content::after {
            content: "";
            position: absolute;
            top: 50%;
            right: 100%;
            margin-top: -8px;
            border-width: 8px;
            border-style: solid;
            border-color: transparent #2c3e50 transparent transparent;
        }

        .tooltip-container:hover .tooltip-content {
            visibility: visible;
            opacity: 1;
        }

        .tooltip-title {
            font-weight: 600;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .tooltip-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 200px;
            overflow-y: auto;
        }

        .tooltip-list li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .student-name {
            flex: 1;
            color: #ecf0f1;
            font-weight: 600;
        }

        .violation-types {
            color: #95a5a6;
            font-size: 11px;
            font-style: italic;
            padding-left: 5px;
            line-height: 1.4;
        }

        .violation-detail {
            display: inline-block;
            margin-right: 8px;
            padding: 2px 6px;
            background: rgba(231, 76, 60, 0.2);
            border-radius: 3px;
            font-size: 10px;
        }

        /* Tooltip for Awards - Use same style as violations */
        .award-count {
            background: rgba(46, 125, 50, 0.3);
            color: #2e7d32;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
        }

        .award-detail {
            display: inline-block;
            margin-right: 8px;
            padding: 2px 6px;
            background: rgba(46, 125, 50, 0.2);
            border-radius: 3px;
            font-size: 10px;
        }

        @media (max-width: 768px) {
            .main-wrapper {
                flex-direction: column;
            }

            .content-area {
                margin-left: 0;
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .class-buttons {
                flex-direction: column;
            }

            .class-btn {
                width: 100%;
            }

            /* Tooltip on mobile - show on top */
            .tooltip-content {
                left: 50%;
                top: auto;
                bottom: 125%;
                transform: translateX(-50%);
            }

            .tooltip-content::after {
                top: 100%;
                right: auto;
                left: 50%;
                margin-top: 0;
                margin-left: -8px;
                border-color: #2c3e50 transparent transparent transparent;
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
                    <div class="header-left-icon">
                        <h2><i class="fas fa-chart-line"></i></h2>
                        <h2>Kết quả học tập và rèn luyện </h2>
                    </div>
                </div>
            </div>

            <?php if (isset($data['error'])): ?>
                <div class="card">
                    <div class="error-message">
                        <strong>⚠️ Lỗi:</strong> <?php echo htmlspecialchars($data['error']); ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Chọn lớp chủ nhiệm -->
                <?php if (isset($data['classes']) && count($data['classes']) > 1): ?>
                <div class="class-selector">
                    <h3><i class="fas fa-school"></i> Chọn lớp chủ nhiệm</h3>
                    <div class="class-buttons">
                        <?php foreach ($data['classes'] as $class): ?>
                            <a href="?maLop=<?php echo $class['maLop']; ?>" 
                               class="class-btn <?php echo ($class['maLop'] == $data['currentClassId']) ? 'active' : ''; ?>">
                                <span class="class-btn-name"><?php echo htmlspecialchars($class['tenLop']); ?></span>
                                <span class="class-btn-info">
                                    Khối <?php echo htmlspecialchars($class['khoiLop']); ?> | 
                                    <?php echo $class['siSo']; ?> HS
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Thông tin lớp -->
                <div class="class-info">
                    <h3><i class="fas fa-info-circle"></i> Thông tin lớp học</h3>
                    <p><strong>Lớp:</strong> <?php echo htmlspecialchars($data['classInfo']['tenLop']); ?> - Khối <?php echo htmlspecialchars($data['classInfo']['khoiLop']); ?></p>
                    <p><strong>Sĩ số:</strong> <?php echo $data['classInfo']['siSo']; ?> học sinh</p>
                    <p><strong>Năm học:</strong> <?php echo htmlspecialchars($data['classInfo']['namHoc']); ?> - Học kỳ <?php echo $data['hocKy']; ?></p>
                    <p><strong>Giáo viên chủ nhiệm:</strong> <?php echo htmlspecialchars($data['classInfo']['tenGVCN']); ?></p>
                </div>

                <!-- Thống kê tổng quan -->
                <div class="stats-grid">
                    <div class="stat-card info">
                        <h4><i class="fas fa-graduation-cap"></i> Điểm trung bình lớp</h4>
                        <div class="stat-value"><?php echo number_format($data['averageScore']['diemTBLop'] ?? 0, 2); ?></div>
                        <div class="stat-label">Học kỳ <?php echo $data['hocKy']; ?></div>
                    </div>

                    <div class="stat-card success" style="cursor: pointer;" 
                         onclick="window.location.href='../../view/teacher/vStudentAward.php?maLop=<?php echo $data['currentClassId']; ?>'">
                        <h4><i class="fas fa-award"></i> Khen thưởng</h4>
                        <div class="stat-value"><?php echo $data['awardsCount']['soHSKhenThuong'] ?? 0; ?></div>
                        <div class="stat-label"><?php echo $data['awardsCount']['tongKhenThuong'] ?? 0; ?> lượt khen thưởng</div>
                        <div style="margin-top: 10px; font-size: 12px; color: #27ae60;">
                            <i class="fas fa-hand-pointer"></i> Click để quản lý
                        </div>
                    </div>

                    <div class="stat-card warning" style="cursor: pointer;" 
                         onclick="window.location.href='../../view/teacher/vStudentViolation.php?maLop=<?php echo $data['currentClassId']; ?>'">
                        <h4><i class="fas fa-exclamation-triangle"></i> Vi phạm</h4>
                        <div class="stat-value"><?php echo $data['violationsCount']['soHSViPham'] ?? 0; ?></div>
                        <div class="stat-label"><?php echo $data['violationsCount']['tongViPham'] ?? 0; ?> lượt vi phạm</div>
                        <div style="margin-top: 10px; font-size: 12px; color: #e74c3c;">
                            <i class="fas fa-hand-pointer"></i> Click để quản lý
                        </div>
                    </div>

                    <div class="stat-card" style="background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%); cursor: pointer;" 
                         onclick="window.location.href='../../view/teacher/vStudentAbsence.php?maLop=<?php echo $data['currentClassId']; ?>'">
                        <h4><i class="fas fa-calendar-times"></i> Nghỉ học</h4>
                        <div class="stat-value"><?php echo $data['absenceStats']['soHSNghi'] ?? 0; ?></div>
                        <div class="stat-label"><?php echo $data['absenceStats']['tongSoNgayNghi'] ?? 0; ?> ngày nghỉ (<?php echo $data['absenceStats']['tongNghiKhongPhep'] ?? 0; ?> không phép)</div>
                        <div style="margin-top: 10px; font-size: 12px; color: #fff;">
                            <i class="fas fa-hand-pointer"></i> Click để quản lý
                        </div>
                    </div>

                    <?php if ($data['yearlyAverage']): ?>
                    <div class="stat-card">
                        <h4><i class="fas fa-calendar-alt"></i> Điểm TB cả năm</h4>
                        <div class="stat-value"><?php echo number_format($data['yearlyAverage'], 2); ?></div>
                        <div class="stat-label">Điểm tổng kết năm học</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Thống kê Hạnh kiểm -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-star"></i> Thống kê Hạnh kiểm
                        </h2>
                    </div>
                    <div class="chart-container">
                        <?php if (empty($data['conductStats'])): ?>
                            <p style="text-align: center; color: #999;">Chưa có dữ liệu hạnh kiểm</p>
                        <?php else: ?>
                            <?php foreach ($data['conductStats'] as $conduct): ?>
                                <div class="conduct-item">
                                    <div class="conduct-label">
                                        <div class="conduct-icon <?php echo strtolower($conduct['loaiHK']); ?>">
                                            <i class="fas fa-<?php 
                                                echo $conduct['loaiHK'] == 'Tot' ? 'smile' : 
                                                    ($conduct['loaiHK'] == 'Kha' ? 'meh' : 
                                                    ($conduct['loaiHK'] == 'TB' ? 'frown' : 'sad-tear')); 
                                            ?>"></i>
                                        </div>
                                        <span>Hạnh kiểm <?php echo htmlspecialchars($conduct['loaiHK']); ?></span>
                                    </div>
                                    <div class="conduct-stats">
                                        <span class="conduct-count"><?php echo $conduct['soLuong']; ?></span>
                                        <span class="conduct-percent">(<?php echo number_format($conduct['tyLe'], 1); ?>%)</span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $conduct['tyLe']; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Khen thưởng và Vi phạm -->
                <div class="stats-grid">
                    <!-- Khen thưởng -->
                    <div class="card">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h2 class="card-title">
                                <i class="fas fa-trophy"></i> Khen thưởng
                            </h2>
                            <a href="../../view/teacher/vStudentAward.php?maLop=<?php echo $data['currentClassId']; ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Quản lý khen thưởng
                            </a>
                        </div>
                        <div class="awards-list">
                            <?php if (empty($data['awardsByLevel'])): ?>
                                <p style="text-align: center; color: #999;">Chưa có khen thưởng</p>
                            <?php else: ?>
                                <?php foreach ($data['awardsByLevel'] as $award): ?>
                                    <div class="award-item">
                                        <div class="tooltip-container">
                                            <span class="tooltip-trigger">Cấp <?php echo htmlspecialchars($award['capKhenThuong']); ?></span>
                                            <div class="tooltip-content">
                                                <div class="tooltip-title">
                                                    <i class="fas fa-trophy"></i> Danh sách học sinh được khen thưởng cấp <?php echo htmlspecialchars($award['capKhenThuong']); ?>
                                                </div>
                                                <?php 
                                                $capKhenThuong = $award['capKhenThuong'];
                                                $studentsAwards = $data['awardsByLevelDetail'][$capKhenThuong] ?? [];
                                                ?>
                                                <?php if (!empty($studentsAwards)): ?>
                                                    <ul class="tooltip-list">
                                                        <?php foreach ($studentsAwards as $student): ?>
                                                            <li>
                                                                <div class="student-header">
                                                                    <span class="student-name"><?php echo htmlspecialchars($student['hoTen']); ?></span>
                                                                    <span class="award-count"><?php echo $student['soLanKhenThuong']; ?> lần</span>
                                                                </div>
                                                                <div class="violation-types">
                                                                    <i class="fas fa-angle-right"></i> 
                                                                    <?php 
                                                                    $details = explode(' | ', $student['chiTietKhenThuong']);
                                                                    foreach ($details as $detail) {
                                                                        echo '<span class="award-detail">' . htmlspecialchars($detail) . '</span>';
                                                                    }
                                                                    ?>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <div class="tooltip-empty">Không có học sinh được khen thưởng</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="badge badge-success"><?php echo $award['soLuong']; ?> lượt</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Vi phạm -->
                    <div class="card">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h2 class="card-title">
                                <i class="fas fa-ban"></i>Vi phạm
                            </h2>
                            <a href="../../view/teacher/vStudentViolation.php?maLop=<?php echo $data['currentClassId']; ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Quản lý vi phạm
                            </a>
                        </div>
                        <div class="violations-list">
                            <?php if ($data['violationsCount']['tongViPham'] > 0): ?>
                                <!-- Vi phạm nhẹ -->
                                <div class="award-item">
                                    <div class="tooltip-container">
                                        <span class="tooltip-trigger">Vi phạm nhẹ</span>
                                        <div class="tooltip-content">
                                            <div class="tooltip-title">
                                                <i class="fas fa-exclamation-circle"></i> Danh sách học sinh vi phạm nhẹ
                                            </div>
                                            <?php if (!empty($data['violationsNhe'])): ?>
                                                <ul class="tooltip-list">
                                                    <?php foreach ($data['violationsNhe'] as $student): ?>
                                                        <li>
                                                            <div class="student-header">
                                                                <span class="student-name"><?php echo htmlspecialchars($student['hoTen']); ?></span>
                                                                <span class="violation-count"><?php echo $student['soLanViPham']; ?> lần</span>
                                                            </div>
                                                            <div class="violation-types">
                                                                <i class="fas fa-angle-right"></i> 
                                                                <?php 
                                                                $details = explode(' | ', $student['chiTietViPham']);
                                                                foreach ($details as $detail) {
                                                                    echo '<span class="violation-detail">' . htmlspecialchars($detail) . '</span>';
                                                                }
                                                                ?>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="tooltip-empty">Không có học sinh vi phạm</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge badge-warning"><?php echo $data['violationsCount']['viPhamNhe'] ?? 0; ?> lượt</span>
                                </div>

                                <!-- Vi phạm trung bình -->
                                <div class="award-item">
                                    <div class="tooltip-container">
                                        <span class="tooltip-trigger">Vi phạm trung bình</span>
                                        <div class="tooltip-content">
                                            <div class="tooltip-title">
                                                <i class="fas fa-exclamation-triangle"></i> Danh sách học sinh vi phạm trung bình
                                            </div>
                                            <?php if (!empty($data['violationsTB'])): ?>
                                                <ul class="tooltip-list">
                                                    <?php foreach ($data['violationsTB'] as $student): ?>
                                                        <li>
                                                            <div class="student-header">
                                                                <span class="student-name"><?php echo htmlspecialchars($student['hoTen']); ?></span>
                                                                <span class="violation-count"><?php echo $student['soLanViPham']; ?> lần</span>
                                                            </div>
                                                            <div class="violation-types">
                                                                <i class="fas fa-angle-right"></i> 
                                                                <?php 
                                                                $details = explode(' | ', $student['chiTietViPham']);
                                                                foreach ($details as $detail) {
                                                                    echo '<span class="violation-detail">' . htmlspecialchars($detail) . '</span>';
                                                                }
                                                                ?>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="tooltip-empty">Không có học sinh vi phạm</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge badge-warning"><?php echo $data['violationsCount']['viPhamTB'] ?? 0; ?> lượt</span>
                                </div>

                                <!-- Vi phạm nặng -->
                                <div class="award-item">
                                    <div class="tooltip-container">
                                        <span class="tooltip-trigger">Vi phạm nặng</span>
                                        <div class="tooltip-content">
                                            <div class="tooltip-title">
                                                <i class="fas fa-ban"></i> Danh sách học sinh vi phạm nặng
                                            </div>
                                            <?php if (!empty($data['violationsNang'])): ?>
                                                <ul class="tooltip-list">
                                                    <?php foreach ($data['violationsNang'] as $student): ?>
                                                        <li>
                                                            <div class="student-header">
                                                                <span class="student-name"><?php echo htmlspecialchars($student['hoTen']); ?></span>
                                                                <span class="violation-count"><?php echo $student['soLanViPham']; ?> lần</span>
                                                            </div>
                                                            <div class="violation-types">
                                                                <i class="fas fa-angle-right"></i> 
                                                                <?php 
                                                                $details = explode(' | ', $student['chiTietViPham']);
                                                                foreach ($details as $detail) {
                                                                    echo '<span class="violation-detail">' . htmlspecialchars($detail) . '</span>';
                                                                }
                                                                ?>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="tooltip-empty">Không có học sinh vi phạm</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge badge-danger"><?php echo $data['violationsCount']['viPhamNang'] ?? 0; ?> lượt</span>
                                </div>
                            <?php else: ?>
                                <p style="text-align: center; color: #999;">Không có vi phạm</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Thống kê nghỉ học -->
                <?php if (!empty($data['topAbsentStudents'])): ?>
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h2 class="card-title">
                            <i class="fas fa-calendar-times"></i> Học sinh nghỉ nhiều nhất
                        </h2>
                        <a href="../../view/teacher/vStudentAbsence.php?maLop=<?php echo $data['currentClassId']; ?>" 
                           class="btn btn-primary btn-sm" style="text-decoration: none;">
                            <i class="fas fa-cog"></i> Quản lý nghỉ học
                        </a>
                    </div>
                    <div class="chart-container">
                        <?php foreach ($data['topAbsentStudents'] as $student): ?>
                        <div class="conduct-item">
                            <div class="conduct-label">
                                <div class="conduct-icon" style="background: #ffe0b2; color: #e65100;">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <span><?php echo htmlspecialchars($student['hoTen']); ?></span>
                            </div>
                            <div class="conduct-stats">
                                <span style="font-size: 14px; color: #27ae60;">
                                    Có phép: <strong><?php echo $student['soNghiCoPhep']; ?></strong>
                                </span>
                                <span style="font-size: 14px; color: #e74c3c;">
                                    Không phép: <strong><?php echo $student['soNghiKhongPhep']; ?></strong>
                                </span>
                                <div class="conduct-count"><?php echo $student['tongNghi']; ?></div>
                                <span style="font-size: 12px; color: #999;">ngày</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Thống kê Học lực (nếu cần) -->
                <?php if (!empty($data['academicStats'])): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-book"></i> Thống kê Học lực
                        </h2>
                    </div>
                    <div class="chart-container">
                        <?php foreach ($data['academicStats'] as $academic): ?>
                            <div class="conduct-item">
                                <div class="conduct-label">
                                    <span>Học lực <?php echo htmlspecialchars($academic['xepLoai']); ?></span>
                                </div>
                                <div class="conduct-stats">
                                    <span class="conduct-count"><?php echo $academic['soLuong']; ?></span>
                                    <span class="conduct-percent">học sinh</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
