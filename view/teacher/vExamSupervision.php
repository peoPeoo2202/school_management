<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công coi thi - Giáo viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
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
            color: #667eea;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .nav-tab:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(102,126,234,0.3);
        }

        .nav-tab.active {
            background: #667eea;
            color: white;
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
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .filter-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 8px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 500;
            color: #666;
            margin-bottom: 5px;
        }

        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .exam-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .exam-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }

        .exam-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }

        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .exam-subject {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
        }

        .exam-type {
            display: inline-block;
            padding: 5px 12px;
            background: #764ba2;
            color: white;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .exam-details {
            margin-bottom: 12px;
        }

        .exam-detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 14px;
            color: #666;
        }

        .exam-detail-item i {
            width: 20px;
            color: #667eea;
        }

        .exam-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            margin-top: 15px;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge.scheduled { background: #d1ecf1; color: #0c5460; }
        .badge.in-progress { background: #fff3cd; color: #856404; }
        .badge.completed { background: #d4edda; color: #155724; }
        .badge.cancelled { background: #f8d7da; color: #721c24; }
        .badge.urgent { background: #f8d7da; color: #721c24; }
        .badge.soon { background: #fff3cd; color: #856404; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .stats-row {
            display: flex;
            justify-content: space-around;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .exam-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .filter-section {
                grid-template-columns: 1fr;
            }

            .stats-row {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-clipboard-check"></i> Phân công coi thi</h1>
            <a href="../public/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <a href="?action=dashboard" class="nav-tab">
                <i class="fas fa-home"></i> Tổng quan
            </a>
            <a href="?action=viewClasses" class="nav-tab">
                <i class="fas fa-users"></i> Danh sách lớp
            </a>
            <a href="?action=viewSchedule" class="nav-tab">
                <i class="fas fa-calendar-alt"></i> Lịch dạy
            </a>
            <a href="?action=viewExamSupervision" class="nav-tab active">
                <i class="fas fa-clipboard-check"></i> Coi thi
            </a>
            <a href="?action=viewGradingAssignment" class="nav-tab">
                <i class="fas fa-edit"></i> Chấm điểm
            </a>
        </div>

        <!-- Statistics -->
        <?php if ($data['examSupervision']['success']): ?>
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $data['examSupervision']['total']; ?></div>
                    <div class="stat-label">Tổng số ca thi</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">
                        <?php 
                        $upcoming = 0;
                        foreach ($data['examSupervision']['data'] as $exam) {
                            if ($exam['trangThai'] == 'scheduled' && $exam['soNgayConLai'] >= 0) {
                                $upcoming++;
                            }
                        }
                        echo $upcoming;
                        ?>
                    </div>
                    <div class="stat-label">Ca thi sắp tới</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">
                        <?php 
                        $completed = 0;
                        foreach ($data['examSupervision']['data'] as $exam) {
                            if ($exam['trangThai'] == 'completed') {
                                $completed++;
                            }
                        }
                        echo $completed;
                        ?>
                    </div>
                    <div class="stat-label">Đã hoàn thành</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-list"></i> Danh sách phân công coi thi</h2>
            </div>

            <!-- Filter Section -->
            <form method="GET" action="">
                <input type="hidden" name="action" value="viewExamSupervision">
                <div class="filter-section">
                    <div class="filter-group">
                        <label>Trạng thái</label>
                        <select name="trangThai">
                            <option value="">Tất cả</option>
                            <option value="scheduled" <?php echo ($data['filters']['trangThai'] == 'scheduled') ? 'selected' : ''; ?>>Đã lên lịch</option>
                            <option value="in_progress" <?php echo ($data['filters']['trangThai'] == 'in_progress') ? 'selected' : ''; ?>>Đang diễn ra</option>
                            <option value="completed" <?php echo ($data['filters']['trangThai'] == 'completed') ? 'selected' : ''; ?>>Đã hoàn thành</option>
                            <option value="cancelled" <?php echo ($data['filters']['trangThai'] == 'cancelled') ? 'selected' : ''; ?>>Đã hủy</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Loại kỳ thi</label>
                        <select name="loaiKyThi">
                            <option value="">Tất cả</option>
                            <option value="Giữa kỳ" <?php echo ($data['filters']['loaiKyThi'] == 'Giữa kỳ') ? 'selected' : ''; ?>>Giữa kỳ</option>
                            <option value="Cuối kỳ" <?php echo ($data['filters']['loaiKyThi'] == 'Cuối kỳ') ? 'selected' : ''; ?>>Cuối kỳ</option>
                            <option value="Kiểm tra 15 phút" <?php echo ($data['filters']['loaiKyThi'] == 'Kiểm tra 15 phút') ? 'selected' : ''; ?>>Kiểm tra 15 phút</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Học kỳ</label>
                        <select name="hocKy">
                            <option value="">Tất cả</option>
                            <option value="1" <?php echo ($data['filters']['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                            <option value="2" <?php echo ($data['filters']['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Lọc
                        </button>
                    </div>
                </div>
            </form>

            <!-- Exam Cards Grid -->
            <?php if ($data['examSupervision']['success'] && count($data['examSupervision']['data']) > 0): ?>
                <div class="exam-grid">
                    <?php foreach ($data['examSupervision']['data'] as $exam): ?>
                        <div class="exam-card">
                            <div class="exam-header">
                                <div class="exam-subject"><?php echo htmlspecialchars($exam['tenMonHoc']); ?></div>
                                <div class="exam-type"><?php echo htmlspecialchars($exam['loaiKyThi']); ?></div>
                            </div>

                            <div class="exam-details">
                                <div class="exam-detail-item">
                                    <i class="fas fa-door-open"></i>
                                    <span><strong>Lớp:</strong> <?php echo htmlspecialchars($exam['tenLop']); ?></span>
                                </div>
                                <div class="exam-detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><strong>Phòng:</strong> <?php echo htmlspecialchars($exam['tenPhong']); ?></span>
                                </div>
                                <div class="exam-detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><strong>Ngày:</strong> <?php echo date('d/m/Y', strtotime($exam['ngayThi'])); ?></span>
                                </div>
                                <div class="exam-detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span><strong>Giờ:</strong> 
                                        <?php echo date('H:i', strtotime($exam['gioBatDau'])); ?> - 
                                        <?php echo date('H:i', strtotime($exam['gioKetThuc'])); ?>
                                    </span>
                                </div>
                                <div class="exam-detail-item">
                                    <i class="fas fa-user-tie"></i>
                                    <span><strong>Vị trí:</strong> <?php echo htmlspecialchars($exam['viTriCoiThi']); ?></span>
                                </div>
                            </div>

                            <div class="exam-footer">
                                <span class="badge <?php echo htmlspecialchars($exam['trangThai']); ?>">
                                    <?php 
                                    $statusLabels = [
                                        'scheduled' => 'Đã lên lịch',
                                        'in_progress' => 'Đang diễn ra',
                                        'completed' => 'Hoàn thành',
                                        'cancelled' => 'Đã hủy'
                                    ];
                                    echo $statusLabels[$exam['trangThai']] ?? $exam['trangThai'];
                                    ?>
                                </span>
                                <?php if ($exam['soNgayConLai'] >= 0 && $exam['soNgayConLai'] <= 1 && $exam['trangThai'] == 'scheduled'): ?>
                                    <span class="badge urgent">
                                        <i class="fas fa-exclamation-triangle"></i> Sắp diễn ra
                                    </span>
                                <?php elseif ($exam['soNgayConLai'] > 1 && $exam['soNgayConLai'] <= 3 && $exam['trangThai'] == 'scheduled'): ?>
                                    <span class="badge soon">
                                        Còn <?php echo $exam['soNgayConLai']; ?> ngày
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard"></i>
                    <h3>Không tìm thấy phân công coi thi nào</h3>
                    <p>Vui lòng thử lại với các tiêu chí lọc khác</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
