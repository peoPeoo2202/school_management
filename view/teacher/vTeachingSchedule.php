<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch dạy - Giáo viên</title>
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
            margin-bottom: 20px;
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

        .schedule-grid {
            overflow-x: auto;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .schedule-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }

        .schedule-table td {
            border: 1px solid #e0e0e0;
            padding: 10px;
            text-align: center;
            vertical-align: middle;
            min-height: 80px;
            font-size: 13px;
        }

        .schedule-table .time-cell {
            background: #f8f9ff;
            font-weight: 600;
            color: #667eea;
            width: 100px;
        }

        .schedule-table .day-header {
            background: #764ba2;
        }

        .lesson-cell {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .lesson-cell:hover {
            background: linear-gradient(135deg, #667eea30 0%, #764ba230 100%);
            transform: scale(1.02);
        }

        .lesson-subject {
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .lesson-class {
            color: #333;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .lesson-room {
            color: #666;
            font-size: 12px;
        }

        .empty-cell {
            color: #ccc;
            font-style: italic;
        }

        .session-divider {
            background: #f0f0f0;
            text-align: center;
            font-weight: 600;
            color: #666;
            padding: 8px;
        }

        .legend {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            padding: 15px;
            background: #f8f9ff;
            border-radius: 8px;
            margin-top: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .morning { background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); }

        @media print {
            .nav-tabs, .filter-section, .logout-btn {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .filter-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-calendar-alt"></i> Lịch dạy - Thời khóa biểu</h1>
            <a href="../public/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
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
            <a href="?action=viewSchedule" class="nav-tab active">
                <i class="fas fa-calendar-alt"></i> Lịch dạy
            </a>
            <a href="?action=viewExamSupervision" class="nav-tab">
                <i class="fas fa-clipboard-check"></i> Coi thi
            </a>
            <a href="?action=viewGradingAssignment" class="nav-tab">
                <i class="fas fa-edit"></i> Chấm điểm
            </a>
        </div>

        <!-- Main Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-calendar-week"></i> Thời khóa biểu</h2>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> In lịch
                </button>
            </div>

            <!-- Filter Section -->
            <form method="GET" action="">
                <input type="hidden" name="action" value="viewSchedule">
                <div class="filter-section">
                    <div class="filter-group">
                        <label>Học kỳ</label>
                        <select name="hocKy">
                            <option value="1" <?php echo ($data['filters']['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                            <option value="2" <?php echo ($data['filters']['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Năm học</label>
                        <select name="namHoc">
                            <option value="2024-2025" <?php echo ($data['filters']['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option>
                            <option value="2023-2024" <?php echo ($data['filters']['namHoc'] == '2023-2024') ? 'selected' : ''; ?>>2023-2024</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Lọc theo lớp</label>
                        <select name="maLop">
                            <option value="">Tất cả các lớp</option>
                            <?php if ($data['classes']['success'] && count($data['classes']['data']) > 0): ?>
                                <?php foreach ($data['classes']['data'] as $class): ?>
                                    <option value="<?php echo $class['maLop']; ?>" 
                                        <?php echo ($data['filters']['maLop'] == $class['maLop']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['tenLop']) . ' - ' . htmlspecialchars($class['tenMonHoc']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Xem lịch
                        </button>
                    </div>
                </div>
            </form>

            <!-- Schedule Grid -->
            <div class="schedule-grid">
                <h3 style="text-align: center; margin-bottom: 20px; color: #667eea;">
                    <i class="fas fa-sun"></i> BUỔI SÁNG (7:00 - 11:30)
                </h3>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th class="time-cell">Tiết</th>
                            <th class="day-header">Thứ 2</th>
                            <th class="day-header">Thứ 3</th>
                            <th class="day-header">Thứ 4</th>
                            <th class="day-header">Thứ 5</th>
                            <th class="day-header">Thứ 6</th>
                            <th class="day-header">Thứ 7</th>
                            <th class="day-header">CN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $morningLessons = [
                            1 => '07:00',
                            2 => '07:50',
                            3 => '08:50',
                            4 => '09:40',
                            5 => '10:35'
                        ];
                        
                        foreach ($morningLessons as $tiet => $gio): 
                        ?>
                            <tr>
                                <td class="time-cell">
                                    <strong>Tiết <?php echo $tiet; ?></strong><br>
                                    <small><?php echo $gio; ?></small>
                                </td>
                                <?php for ($thu = 2; $thu <= 8; $thu++): ?>
                                    <td>
                                        <?php 
                                        if (isset($data['scheduleGrid'][$thu][$tiet]) && 
                                            $data['scheduleGrid'][$thu][$tiet] !== null && 
                                            $data['scheduleGrid'][$thu][$tiet] !== 'merged'):
                                            $lesson = $data['scheduleGrid'][$thu][$tiet];
                                        ?>
                                            <div class="lesson-cell">
                                                <div class="lesson-subject"><?php echo htmlspecialchars($lesson['tenMonHoc']); ?></div>
                                                <div class="lesson-class"><i class="fas fa-users"></i> <?php echo htmlspecialchars($lesson['tenLop']); ?></div>
                                                <div class="lesson-room"><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($lesson['tenPhong']); ?></div>
                                            </div>
                                        <?php elseif ($data['scheduleGrid'][$thu][$tiet] === 'merged'): ?>
                                            <!-- Cell merged with above -->
                                        <?php else: ?>
                                            <span class="empty-cell">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin: 30px 0;"></div>

                <h3 style="text-align: center; margin-bottom: 20px; color: #764ba2;">
                    <i class="fas fa-moon"></i> BUỔI CHIỀU (13:30 - 17:15)
                </h3>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th class="time-cell">Tiết</th>
                            <th class="day-header">Thứ 2</th>
                            <th class="day-header">Thứ 3</th>
                            <th class="day-header">Thứ 4</th>
                            <th class="day-header">Thứ 5</th>
                            <th class="day-header">Thứ 6</th>
                            <th class="day-header">Thứ 7</th>
                            <th class="day-header">CN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $afternoonLessons = [
                            6 => '13:30',
                            7 => '14:20',
                            8 => '15:20',
                            9 => '16:10',
                            10 => '17:00'
                        ];
                        
                        foreach ($afternoonLessons as $tiet => $gio): 
                        ?>
                            <tr>
                                <td class="time-cell">
                                    <strong>Tiết <?php echo $tiet; ?></strong><br>
                                    <small><?php echo $gio; ?></small>
                                </td>
                                <?php for ($thu = 2; $thu <= 8; $thu++): ?>
                                    <td>
                                        <?php 
                                        if (isset($data['scheduleGrid'][$thu][$tiet]) && 
                                            $data['scheduleGrid'][$thu][$tiet] !== null && 
                                            $data['scheduleGrid'][$thu][$tiet] !== 'merged'):
                                            $lesson = $data['scheduleGrid'][$thu][$tiet];
                                        ?>
                                            <div class="lesson-cell">
                                                <div class="lesson-subject"><?php echo htmlspecialchars($lesson['tenMonHoc']); ?></div>
                                                <div class="lesson-class"><i class="fas fa-users"></i> <?php echo htmlspecialchars($lesson['tenLop']); ?></div>
                                                <div class="lesson-room"><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($lesson['tenPhong']); ?></div>
                                            </div>
                                        <?php elseif ($data['scheduleGrid'][$thu][$tiet] === 'merged'): ?>
                                            <!-- Cell merged with above -->
                                        <?php else: ?>
                                            <span class="empty-cell">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Legend -->
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color morning"></div>
                    <span>Tiết học có lịch</span>
                </div>
                <div class="legend-item">
                    <i class="fas fa-info-circle" style="color: #667eea;"></i>
                    <span>Click vào ô tiết để xem chi tiết</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>