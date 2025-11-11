<?php
// Session đã được start từ controller, không cần start lại

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

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Giáo viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            box-sizing: border-box;
        }
        .main-wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .content-area {
            flex: 1;
            padding: 16px 8px;
            overflow-y: auto;
        }

        .header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 32px;
            font-size: 14px;
        }

        .header-content {
            max-width: 1400px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .header-info {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .user-info {
            text-align: right;
        }

        .user-info .name {
            font-weight: 600;
            font-size: 16px;
        }

        .user-info .role {
            font-size: 13px;
            opacity: 0.9;
        }

        .back-btn, .logout-btn {
            background: #6c757d;
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-btn:hover, .logout-btn:hover {
            background: #545b62;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .nav-tab {
            padding: 12px 25px;
            background: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            color: #5081BE;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .nav-tab:hover {
            background: #5081BE;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(102,126,234,0.3);
        }

        .nav-tab.active {
            background: #5081BE;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #5081BE 0%, #2d5a8c 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

        .stat-info {
            flex: 1;
        }

        .stat-label {
            font-size: 13px;
            color: #888;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .view-all-link {
            color: #5081BE;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .view-all-link:hover {
            text-decoration: underline;
        }

        .schedule-item, .exam-item, .grading-item {
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #5081BE;
            background: #f8f9ff;
            border-radius: 5px;
        }

        .schedule-item:last-child, .exam-item:last-child, .grading-item:last-child {
            margin-bottom: 0;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .item-title {
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }

        .item-time {
            color: #5081BE;
            font-weight: 500;
            font-size: 14px;
        }

        .item-details {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 8px;
        }

        .badge.success { background: #d4edda; color: #155724; }
        .badge.warning { background: #fff3cd; color: #856404; }
        .badge.danger { background: #f8d7da; color: #721c24; }
        .badge.info { background: #d1ecf1; color: #0c5460; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #5081BE 0%, #2d5a8c 100%);
            transition: width 0.3s ease;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .header-info {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>
        
        <div class="content-area">
            <div class="container">
                <div class="header">
                    <div class="header-content">
                        <div>
                            <h1><i class="fas fa-chalkboard-teacher"></i> TRA CỨU LỊCH DẠY</h1>
                            <p style="margin-top: 5px; opacity: 0.9;">Học kỳ <?php echo htmlspecialchars($data['hocKy']); ?> - Năm học <?php echo htmlspecialchars($data['namHoc']); ?></p>
                        </div>
                        <div class="header-info">
                            <div class="user-info">
                                <div class="name">
                                    <?php echo htmlspecialchars($hoTen); ?>
                                </div>
                                <div class="role">Giáo viên</div>
                            </div>
                            <div class="header-buttons">
                                <a href="../../controller/cTeachingSchedule.php?action=viewSchedule" class="back-btn">
                                    <i class="fas fa-calendar-alt"></i> Lịch dạy chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Số lớp đang dạy</div>
                    <div class="stat-value">
                        <?php echo $data['summary']['data']['soLopDangDay'] ?? 0; ?>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Số tiết trong tuần</div>
                    <div class="stat-value">
                        <?php echo $data['summary']['data']['soTietTrongTuan'] ?? 0; ?>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Ca thi sắp tới</div>
                    <div class="stat-value">
                        <?php echo $data['summary']['data']['soCaThiSapToi'] ?? 0; ?>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Chấm đang làm</div>
                    <div class="stat-value">
                        <?php echo $data['summary']['data']['soChamDangLam'] ?? 0; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Lịch dạy hôm nay -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-calendar-day"></i> Lịch dạy hôm nay</h2>
                    <a href="../../controller/cTeachingSchedule.php?action=viewSchedule" class="view-all-link">Xem tất cả <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <?php if ($data['scheduleToday']['success'] && count($data['scheduleToday']['data']) > 0): ?>
                    <?php foreach ($data['scheduleToday']['data'] as $item): ?>
                        <div class="schedule-item">
                            <div class="item-header">
                                <span class="item-title"><?php echo htmlspecialchars($item['tenMonHoc']); ?></span>
                                <span class="item-time"><?php echo htmlspecialchars($item['tietHoc']); ?></span>
                            </div>
                            <div class="item-details">
                                <i class="fas fa-door-open"></i> Lớp: <?php echo htmlspecialchars($item['tenLop']); ?><br>
                                <i class="fas fa-map-marker-alt"></i> Phòng: <?php echo htmlspecialchars($item['tenPhong']); ?><br>
                                <i class="fas fa-clock"></i> Giờ: <?php echo htmlspecialchars($item['gioBatDau']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>Không có lịch dạy hôm nay</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ca thi sắp tới -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-clipboard-check"></i> Ca thi sắp tới</h2>
                    <a href="../../view/teacher/vExamSupervision.php" class="view-all-link">Xem tất cả <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <?php if ($data['upcomingExams']['success'] && count($data['upcomingExams']['data']) > 0): ?>
                    <?php foreach ($data['upcomingExams']['data'] as $item): ?>
                        <div class="exam-item">
                            <div class="item-header">
                                <span class="item-title"><?php echo htmlspecialchars($item['tenMonHoc']); ?></span>
                                <span class="item-time"><?php echo date('d/m/Y', strtotime($item['ngayThi'])); ?></span>
                            </div>
                            <div class="item-details">
                                <i class="fas fa-door-open"></i> Lớp: <?php echo htmlspecialchars($item['tenLop']); ?><br>
                                <i class="fas fa-map-marker-alt"></i> Phòng: <?php echo htmlspecialchars($item['tenPhong']); ?><br>
                                <i class="fas fa-clock"></i> Giờ: <?php echo date('H:i', strtotime($item['gioBatDau'])); ?> - <?php echo date('H:i', strtotime($item['gioKetThuc'])); ?><br>
                                <i class="fas fa-user-tie"></i> Vị trí: <?php echo htmlspecialchars($item['viTriCoiThi']); ?>
                                <?php if ($item['soNgayConLai'] <= 1): ?>
                                    <span class="badge danger">Sắp diễn ra</span>
                                <?php elseif ($item['soNgayConLai'] <= 3): ?>
                                    <span class="badge warning">Còn <?php echo $item['soNgayConLai']; ?> ngày</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard"></i>
                        <p>Không có ca thi sắp tới</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Chấm điểm đang làm -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-edit"></i> Chấm điểm đang làm</h2>
                    <a href="../../view/teacher/vGradingAssignment.php" class="view-all-link">Xem tất cả <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <?php if ($data['ongoingGrading']['success'] && count($data['ongoingGrading']['data']) > 0): ?>
                    <?php foreach ($data['ongoingGrading']['data'] as $item): ?>
                        <div class="grading-item">
                            <div class="item-header">
                                <span class="item-title"><?php echo htmlspecialchars($item['tenMonHoc']); ?> - <?php echo htmlspecialchars($item['loaiKiemTra']); ?></span>
                                <span class="item-time"><?php echo round($item['phanTramHoanThanh'] ?? 0); ?>%</span>
                            </div>
                            <div class="item-details">
                                <i class="fas fa-door-open"></i> Lớp: <?php echo htmlspecialchars($item['tenLop']); ?><br>
                                <i class="fas fa-users"></i> Đã chấm: <?php echo $item['soDaCham']; ?>/<?php echo $item['soLuongHocSinh']; ?> học sinh
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo round($item['phanTramHoanThanh'] ?? 0); ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>Không có công việc chấm điểm đang làm</p>
                    </div>
                <?php endif; ?>
            </div>
            </div>
        </div>
    </div>
</body>
</html>
