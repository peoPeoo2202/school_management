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

// Nếu gọi trực tiếp từ view (không có $data), gọi controller để lấy dữ liệu
if (!isset($data)) {
    $maGV = $_SESSION['maGV'];

    // Import model để lấy dữ liệu
    require_once(__DIR__ . '/../../model/mTeachingSchedule.php');
    $model = new mTeachingSchedule();

    // Lấy tham số lọc từ GET
    $hocKy = isset($_GET['hocKy']) ? intval($_GET['hocKy']) : (isset($_SESSION['hocKy']) ? $_SESSION['hocKy'] : 1);
    $namHoc = isset($_GET['namHoc']) ? trim($_GET['namHoc']) : (isset($_SESSION['namHoc']) ? $_SESSION['namHoc'] : '2024-2025');
    $maLop = isset($_GET['maLop']) ? intval($_GET['maLop']) : null;
    $thu = isset($_GET['thu']) ? intval($_GET['thu']) : null;

    // Lấy lịch dạy
    $schedule = $model->getTeachingSchedule($maGV, $hocKy, $namHoc, $maLop, $thu);

    // Lấy danh sách lớp để hiển thị filter
    $classes = $model->getTeacherClasses($maGV, $hocKy, $namHoc);

    // Tổ chức dữ liệu thành grid (theo thứ và tiết)
    $scheduleGrid = [];

    // Khởi tạo grid trống (Thứ 2-8, Tiết 1-10)
    for ($i = 2; $i <= 8; $i++) {
        for ($j = 1; $j <= 10; $j++) {
            $scheduleGrid[$i][$j] = null;
        }
    }

    // Điền dữ liệu vào grid
    if ($schedule['success'] && count($schedule['data']) > 0) {
        foreach ($schedule['data'] as $item) {
            $thu_item = $item['thu'];
            $tietBatDau = $item['tietBatDau'];
            $tietKetThuc = $item['tietKetThuc'];

            // Đánh dấu các tiết từ tietBatDau đến tietKetThuc
            for ($tiet = $tietBatDau; $tiet <= $tietKetThuc; $tiet++) {
                if ($tiet == $tietBatDau) {
                    // Tiết đầu tiên lưu toàn bộ thông tin
                    $scheduleGrid[$thu_item][$tiet] = $item;
                } else {
                    // Các tiết sau đánh dấu là "merged"
                    $scheduleGrid[$thu_item][$tiet] = 'merged';
                }
            }
        }
    }

    // Truyền dữ liệu sang view
    $data = [
        'schedule' => $schedule,
        'scheduleGrid' => $scheduleGrid,
        'classes' => $classes,
        'filters' => [
            'hocKy' => $hocKy,
            'namHoc' => $namHoc,
            'maLop' => $maLop,
            'thu' => $thu
        ]
    ];
}
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
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .btn-primary {
            background: #5081BE;
            color: white;
        }

        .btn-primary:hover {
            background: #2d5a8c;
        }

        .print-btn-container {
            max-height: 60px;
            align-items: center;
            display: flex;
        }

        .btn-primary i {
            font-size: 14px;
        }

        .schedule-grid {
            overflow-x: auto;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }

        .schedule-table th {
            background: #5081BE;
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }

        .schedule-table td {
            padding: 8px;
            text-align: center;
            vertical-align: middle;
            min-height: 50px;
            font-size: 12px;
            min-width: 48px;
            max-width: 80px;
        }

        .schedule-table .day-header {
            background: #4a6fa5;
        }

        .lesson-cell {
            background: linear-gradient(135deg, #5081BE15 0%, #4a6fa515 100%);
            padding: 8px;
            border-radius: 8px;
        }

        .lesson-subject {
            font-weight: 700;
            color: #5081BE;
            margin-bottom: 4px;
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
            margin-bottom: 24px;
            color: #5081BE;
            font-size: 18px;
            font-weight: 600;
            margin-top: 24px;
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
                <div class="header-left ">
                    <div class="header-left-icon">
                        <h2><i class="fas fa-calendar-alt"></i></h2>
                        <h2> Lịch dạy</h2>
                    </div>
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
                    <div class="print-btn-container">
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print"></i> In lịch
                        </button>
                    </div>

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
                    <h3 class="section-title"><i class="fas fa-sun"></i> BUỔI SÁNG (7:00 - 10:50)</h3>
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
                                1 => '07:00 - 07:45',
                                2 => '07:50 - 08:35',
                                3 => '08:40 - 09:25',
                                4 => '09:55 - 10:40',
                                5 => '10:45 - 11:30'
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