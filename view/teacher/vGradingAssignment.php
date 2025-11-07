<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

$maGV = $_SESSION['maGV'] ?? null;

// Nếu $data không được định nghĩa, khởi tạo nó
if (!isset($data)) {
    require_once(__DIR__ . '/../../model/mTeachingSchedule.php');
    
    $model = new mTeachingSchedule();
    
    // Lấy danh sách phân công chấm điểm của giáo viên
    $phanCongResult = $model->getGradingAssignment($maGV);
    
    $data = [
        'gradingAssignment' => [
            'success' => $phanCongResult['success'],
            'data' => $phanCongResult['data'] ?? [],
            'total' => $phanCongResult['total'] ?? 0
        ],
        'filters' => []
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công chấm điểm - Giáo viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .content-area {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
            transition: margin-left 0.3s;
        }

        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 26px;
            font-weight: 600;
            color: #667eea;
        }

        .container {
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .nav-tab:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
        }

        .nav-tab.active {
            background: #667eea;
            color: white;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9ff;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tbody tr {
            transition: all 0.2s;
        }

        tbody tr:hover {
            background: #f8f9ff;
        }

        .progress-container {
            width: 100%;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .progress-fill.low {
            background: linear-gradient(90deg, #f5576c 0%, #f093fb 100%);
        }

        .progress-fill.medium {
            background: linear-gradient(90deg, #f093fb 0%, #feca57 100%);
        }

        .progress-fill.high {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .progress-fill.complete {
            background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
        }

        .progress-text {
            font-size: 12px;
            color: #666;
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge.in-progress {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge.completed {
            background: #d4edda;
            color: #155724;
        }

        .badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .badge.mieng {
            background: #e3e8ff;
            color: #667eea;
        }

        .badge.phut15 {
            background: #d4edda;
            color: #155724;
        }

        .badge.tiet1 {
            background: #fff3cd;
            color: #856404;
        }

        .badge.giuaky {
            background: #f8d7da;
            color: #721c24;
        }

        .badge.cuoiky {
            background: #d1ecf1;
            color: #0c5460;
        }

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

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .action-btn.view {
            background: #667eea;
            color: white;
        }

        .action-btn.view:hover {
            background: #5568d3;
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

            .stats-row {
                flex-direction: column;
                gap: 15px;
            }

            table {
                font-size: 12px;
            }

            th,
            td {
                padding: 10px 8px;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
            <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <div class="header">
                <h1><i class="fas fa-pen-square"></i> Phân công chấm điểm</h1>
            </div>

            <!-- Statistics -->
            <?php if ($data['gradingAssignment']['success']): ?>
                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $data['gradingAssignment']['total']; ?></div>
                        <div class="stat-label">Tổng phân công</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php
                            $pending = 0;
                            foreach ($data['gradingAssignment']['data'] as $item) {
                                if ($item['trangThai'] == 'pending') {
                                    $pending++;
                                }
                            }
                            echo $pending;
                            ?>
                        </div>
                        <div class="stat-label">Chưa bắt đầu</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php
                            $inProgress = 0;
                            foreach ($data['gradingAssignment']['data'] as $item) {
                                if ($item['trangThai'] == 'in_progress') {
                                    $inProgress++;
                                }
                            }
                            echo $inProgress;
                            ?>
                        </div>
                        <div class="stat-label">Đang làm</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php
                            $completed = 0;
                            foreach ($data['gradingAssignment']['data'] as $item) {
                                if ($item['trangThai'] == 'completed') {
                                    $completed++;
                                }
                            }
                            echo $completed;
                            ?>
                        </div>
                        <div class="stat-label">Hoàn thành</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Main Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-list"></i> Danh sách phân công chấm điểm</h2>
                </div>

                <!-- Filter Section -->
                <form method="GET" action="">
                    <input type="hidden" name="action" value="viewGradingAssignment">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Trạng thái</label>
                            <select name="trangThai">
                                <option value="">Tất cả</option>
                                <option value="pending" <?php echo ($data['filters']['trangThai'] == 'pending') ? 'selected' : ''; ?>>Chưa bắt đầu</option>
                                <option value="in_progress" <?php echo ($data['filters']['trangThai'] == 'in_progress') ? 'selected' : ''; ?>>Đang làm</option>
                                <option value="completed" <?php echo ($data['filters']['trangThai'] == 'completed') ? 'selected' : ''; ?>>Hoàn thành</option>
                                <option value="cancelled" <?php echo ($data['filters']['trangThai'] == 'cancelled') ? 'selected' : ''; ?>>Đã hủy</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Loại kiểm tra</label>
                            <select name="loaiKiemTra">
                                <option value="">Tất cả</option>
                                <option value="Miệng" <?php echo ($data['filters']['loaiKiemTra'] == 'Miệng') ? 'selected' : ''; ?>>Miệng</option>
                                <option value="15 phút" <?php echo ($data['filters']['loaiKiemTra'] == '15 phút') ? 'selected' : ''; ?>>15 phút</option>
                                <option value="1 tiết" <?php echo ($data['filters']['loaiKiemTra'] == '1 tiết') ? 'selected' : ''; ?>>1 tiết</option>
                                <option value="Giữa kỳ" <?php echo ($data['filters']['loaiKiemTra'] == 'Giữa kỳ') ? 'selected' : ''; ?>>Giữa kỳ</option>
                                <option value="Cuối kỳ" <?php echo ($data['filters']['loaiKiemTra'] == 'Cuối kỳ') ? 'selected' : ''; ?>>Cuối kỳ</option>
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

                <!-- Table -->
                <div class="table-responsive">
                    <?php if ($data['gradingAssignment']['success'] && count($data['gradingAssignment']['data']) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Loại kiểm tra</th>
                                    <th>Môn học</th>
                                    <th>Lớp</th>
                                    <th>Số lượng</th>
                                    <th>Hình thức</th>
                                    <th>Ngày chấm</th>
                                    <th>Tiến độ</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['gradingAssignment']['data'] as $index => $item): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php
                                            $loaiKTClass = 'mieng';
                                            if ($item['loaiKiemTra'] == '15 phút') $loaiKTClass = 'phut15';
                                            elseif ($item['loaiKiemTra'] == '1 tiết') $loaiKTClass = 'tiet1';
                                            elseif ($item['loaiKiemTra'] == 'Giữa kỳ') $loaiKTClass = 'giuaky';
                                            elseif ($item['loaiKiemTra'] == 'Cuối kỳ') $loaiKTClass = 'cuoiky';
                                            ?>
                                            <span class="badge <?php echo $loaiKTClass; ?>">
                                                <?php echo htmlspecialchars($item['loaiKiemTra']); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($item['tenMonHoc']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['tenLop']); ?></td>
                                        <td>
                                            <i class="fas fa-users"></i>
                                            <?php echo $item['soDaCham']; ?>/<?php echo $item['soLuongHocSinh']; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['hinhThucCham']); ?></td>
                                        <td>
                                            <?php
                                            if ($item['ngayCham']) {
                                                echo date('d/m/Y', strtotime($item['ngayCham']));
                                            } else {
                                                echo '<span style="color: #999;">Chưa xác định</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="progress-container">
                                                <?php
                                                $percent = round($item['phanTramHoanThanh'] ?? 0);
                                                $progressClass = 'low';
                                                if ($percent >= 100) $progressClass = 'complete';
                                                elseif ($percent >= 70) $progressClass = 'high';
                                                elseif ($percent >= 40) $progressClass = 'medium';
                                                ?>
                                                <div class="progress-bar">
                                                    <div class="progress-fill <?php echo $progressClass; ?>"
                                                        style="width: <?php echo $percent; ?>%"></div>
                                                </div>
                                                <div class="progress-text"><?php echo $percent; ?>%</div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo htmlspecialchars($item['trangThai']); ?>">
                                                <?php
                                                $statusLabels = [
                                                    'pending' => 'Chưa bắt đầu',
                                                    'in_progress' => 'Đang làm',
                                                    'completed' => 'Hoàn thành',
                                                    'cancelled' => 'Đã hủy'
                                                ];
                                                echo $statusLabels[$item['trangThai']] ?? $item['trangThai'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="#" class="action-btn view" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i> Chi tiết
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>Không tìm thấy phân công chấm điểm nào</h3>
                            <p>Vui lòng thử lại với các tiêu chí lọc khác</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
</body>

</html>
