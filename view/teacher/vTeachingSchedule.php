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
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch dạy - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
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
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h1 {
            color: #5081BE;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header-left p {
            color: #999;
            font-size: 14px;
        }

        .header-right {
            text-align: right;
        }

        .header-right .welcome-text {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .header-right .user-name {
            color: #5081BE;
            font-weight: 600;
            font-size: 16px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: #5081BE;
            font-size: 24px;
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
            background: #5081BE;
            color: white;
        }

        .btn-primary:hover {
            background: #2d5a8c;
        }

        .schedule-grid {
            overflow-x: auto;
            margin-top: 30px;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .schedule-table th {
            background: #5081BE;
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
            color: #5081BE;
            width: 100px;
        }

        .schedule-table .day-header {
            background: #4a6fa5;
        }

        .lesson-cell {
            background: linear-gradient(135deg, #5081BE15 0%, #4a6fa515 100%);
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .lesson-cell:hover {
            background: linear-gradient(135deg, #5081BE30 0%, #4a6fa530 100%);
            transform: scale(1.02);
        }

        .lesson-subject {
            font-weight: 700;
            color: #5081BE;
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

        .section-title {
            text-align: center;
            margin-top: 30px;
            margin-bottom: 20px;
            color: #5081BE;
            font-size: 18px;
            font-weight: 600;
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
            font-size: 13px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            background: linear-gradient(135deg, #5081BE15 0%, #4a6fa515 100%);
        }

        @media print {

            .header-section,
            .filter-section {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .header-section {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .filter-section {
                grid-template-columns: 1fr;
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
                    <h1><i class="fas fa-calendar-alt"></i> Lịch dạy</h1>
                    <p>Xem thời khóa biểu của bạn</p>
                </div>
                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <!-- Main Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-calendar-week"></i> Thời khóa biểu
                    </h2>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> In lịch
                    </button>
                </div>

                <!-- Filter Section -->
                <form method="GET" action="../../controller/cTeachingSchedule.php">
                    <input type="hidden" name="action" value="schedule">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Học kỳ</label>
                            <select name="hocKy">
                                <option value="1" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Năm học</label>
                            <select name="namHoc">
                                <option value="2024-2025" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option>
                                <option value="2023-2024" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2023-2024') ? 'selected' : ''; ?>>2023-2024</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Lọc theo lớp</label>
                            <select name="maLop">
                                <option value="">Tất cả các lớp</option>
                                <?php if (isset($data['classes']) && $data['classes']['success'] && count($data['classes']['data']) > 0): ?>
                                    <?php foreach ($data['classes']['data'] as $class): ?>
                                        <option value="<?php echo $class['maLop']; ?>"
                                            <?php echo (isset($data['filters']['maLop']) && $data['filters']['maLop'] == $class['maLop']) ? 'selected' : ''; ?>>
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
                    <h3 class="section-title"><i class="fas fa-sun"></i> BUỔI SÁNG (7:00 - 11:30)</h3>
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
                                            if (
                                                isset($data['scheduleGrid'][$thu][$tiet]) &&
                                                $data['scheduleGrid'][$thu][$tiet] !== null &&
                                                $data['scheduleGrid'][$thu][$tiet] !== 'merged'
                                            ):
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

                    <h3 class="section-title"><i class="fas fa-moon"></i> BUỔI CHIỀU (13:30 - 17:15)</h3>
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
                                            if (
                                                isset($data['scheduleGrid'][$thu][$tiet]) &&
                                                $data['scheduleGrid'][$thu][$tiet] !== null &&
                                                $data['scheduleGrid'][$thu][$tiet] !== 'merged'
                                            ):
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
                        <div class="legend-color"></div>
                        <span>Tiết học có lịch</span>
                    </div>
                    <div class="legend-item">
                        <i class="fas fa-info-circle" style="color: #5081BE;"></i>
                        <span>Nhấp vào ô tiết để xem chi tiết</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>