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

// Require model để lấy dữ liệu
require_once(__DIR__ . '/../../model/mTeacher.php');

$maGV = $_SESSION['maGV'] ?? null;

// Nếu $data không được định nghĩa, khởi tạo nó
if (!isset($data)) {
    $model = new mTeacher();
    
    // Lấy danh sách lớp chi tiết của giáo viên
    $danhSachLop = $model->getDetailedClassListByTeacher($maGV);
    
    $data = [
        'classes' => [
            'success' => !empty($danhSachLop),
            'data' => $danhSachLop ?? []
        ],
        'filters' => [
            'hocKy' => $_GET['hocKy'] ?? '',
            'namHoc' => $_GET['namHoc'] ?? '',
            'maKhoi' => $_GET['maKhoi'] ?? ''
        ]
    ];
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách lớp - Giáo viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .main-wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .content-area {
            flex: 1;
            padding: 32px;
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

        .header h1 {
            font-size: 26px;
            font-weight: 600;
            color: #5081BE;
            margin: 0;
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .nav-tab:hover {
            background: #5081BE;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(80, 129, 190, 0.3);
        }

        .nav-tab.active {
            background: #5081BE;
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

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
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

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge.primary {
            background: #e3e8ff;
            color: #667eea;
        }

        .badge.success {
            background: #d4edda;
            color: #155724;
        }

        .badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge.info {
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
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9ff;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .filter-section {
                grid-template-columns: 1fr;
            }

            .stats-row {
                flex-direction: column;
                gap: 15px;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
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
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <div class="header">
                <h1><i class="fas fa-list"></i> Danh sách lớp</h1>
            </div>

            <!-- Main Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-list"></i> Danh sách lớp học đang giảng dạy</h2>
                </div>

                <!-- Filter Section -->
                <form method="GET" action="">
                    <input type="hidden" name="action" value="viewClasses">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Học kỳ</label>
                            <select name="hocKy">
                                <option value="">Tất cả</option>
                                <option value="1" <?php echo ($data['filters']['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo ($data['filters']['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Năm học</label>
                            <select name="namHoc">
                                <option value="">Tất cả</option>
                                <option value="2024-2025" <?php echo ($data['filters']['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option>
                                <option value="2023-2024" <?php echo ($data['filters']['namHoc'] == '2023-2024') ? 'selected' : ''; ?>>2023-2024</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Khối</label>
                            <select name="maKhoi">
                                <option value="">Tất cả</option>
                                <option value="1" <?php echo ($data['filters']['maKhoi'] == 1) ? 'selected' : ''; ?>>Khối 10</option>
                                <option value="2" <?php echo ($data['filters']['maKhoi'] == 2) ? 'selected' : ''; ?>>Khối 11</option>
                                <option value="3" <?php echo ($data['filters']['maKhoi'] == 3) ? 'selected' : ''; ?>>Khối 12</option>
                            </select>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                            <a href="?action=viewClasses" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Đặt lại
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <?php if ($data['classes']['success'] && count($data['classes']['data']) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên lớp</th>
                                    <th>Khối</th>
                                    <th>Môn học</th>
                                    <th>Sĩ số</th>
                                    <th>Phòng học</th>
                                    <th>GVCN</th>
                                    <th>Số tiết/tuần</th>
                                    <th>Học kỳ</th>
                                    <th>Năm học</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['classes']['data'] as $index => $class): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($class['tenLop']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge primary">Khối <?php echo htmlspecialchars($class['khoiLop']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($class['tenMonHoc']); ?></td>
                                        <td>
                                            <i class="fas fa-users"></i> <?php echo htmlspecialchars($class['siSo']); ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($class['tenPhong'] ?? 'Chưa xếp'); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($class['giaoVienChuNhiem'] ?? 'Chưa có'); ?></td>
                                        <td>
                                            <span class="badge info"><?php echo htmlspecialchars($class['soTietTrongTuan']); ?> tiết</span>
                                        </td>
                                        <td>HK <?php echo htmlspecialchars($class['hocKy']); ?></td>
                                        <td><?php echo htmlspecialchars($class['namHoc']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>Không tìm thấy lớp học nào</h3>
                            <p>Vui lòng thử lại với các tiêu chí lọc khác</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
</body>

</html>
