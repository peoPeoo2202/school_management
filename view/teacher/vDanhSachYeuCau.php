<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once(__DIR__ . '/../../config.php');

// Kiểm tra đăng nhập và quyền
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true || $_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: " . url('public/index.php'));
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
$loaiYeuCau = $_GET['loaiYeuCau'] ?? 'all';
$trangThai = $_GET['trangThai'] ?? 'all';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách yêu cầu - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>

        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }


        

        .btn-filter {
            padding: 9px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-filter:hover {
            background: #2980b9;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .content-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table thead {
            background: #f8f9fa;
        }

        table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }

        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-suadiem {
            background: #e3f2fd;
            color: #1565c0;
        }

        .badge-nghiphep {
            background: #f3e5f5;
            color: #6a1b9a;
        }

        .badge-choxuly {
            background: #fff3e0;
            color: #e65100;
        }

        .badge-dachapnhan {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-tuchoi {
            background: #ffebee;
            color: #c62828;
        }

        .btn-action {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view {
            background: #2196f3;
            color: white;
        }

        .btn-view:hover {
            background: #1976d2;
        }

        .btn-download {
            background: #4caf50;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-download:hover {
            background: #45a049;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #95a5a6;
        }

        .empty-state p {
            margin-bottom: 25px;
        }

        .request-details {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 8px;
            color: white;
        }

        .stat-card.pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card.approved {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.rejected {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .stat-card h4 {
            font-size: 14px;
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: 700;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #7f8c8d;
            font-size: 14px;
        }

        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include_once(__DIR__ . '/../layouts/teacher-layout-header.php'); ?>

    <div class="container">
        <div class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
            <span>/</span>
            <span>Danh sách yêu cầu</span>
        </div>

        <div class="page-header">
            <h1><i class="fas fa-list-alt"></i> Danh sách yêu cầu đã gửi</h1>
            <div class="action-buttons">
                <a href="index.php?action=yeucau&type=suadiem" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Yêu cầu sửa điểm
                </a>
                <a href="index.php?action=yeucau&type=nghiphep" class="btn btn-success">
                    <i class="fas fa-calendar-times"></i> Yêu cầu nghỉ phép
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Thống kê -->
        <div class="stats-row">
            <div class="stat-card">
                <h4>Tổng yêu cầu</h4>
                <div class="number"><?php echo count($danhSachYeuCau); ?></div>
            </div>
            <div class="stat-card pending">
                <h4>Chờ xử lý</h4>
                <div class="number">
                    <?php echo count(array_filter($danhSachYeuCau, fn($yc) => $yc['trangThai'] == 'Choxuly')); ?>
                </div>
            </div>
            <div class="stat-card approved">
                <h4>Đã chấp nhận</h4>
                <div class="number">
                    <?php echo count(array_filter($danhSachYeuCau, fn($yc) => $yc['trangThai'] == 'Dachapnhan')); ?>
                </div>
            </div>
            <div class="stat-card rejected">
                <h4>Từ chối</h4>
                <div class="number">
                    <?php echo count(array_filter($danhSachYeuCau, fn($yc) => $yc['trangThai'] == 'Tuchoi')); ?>
                </div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="filter-section">
            <form method="GET" action="index.php">
                <input type="hidden" name="action" value="danhsachyeucau">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Loại yêu cầu</label>
                        <select name="loaiYeuCau" class="filter-control">
                            <option value="all" <?php echo $loaiYeuCau == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                            <option value="SuaDiem" <?php echo $loaiYeuCau == 'SuaDiem' ? 'selected' : ''; ?>>Sửa điểm</option>
                            <option value="NghiPhep" <?php echo $loaiYeuCau == 'NghiPhep' ? 'selected' : ''; ?>>Nghỉ phép</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Trạng thái</label>
                        <select name="trangThai" class="filter-control">
                            <option value="all" <?php echo $trangThai == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                            <option value="Choxuly" <?php echo $trangThai == 'Choxuly' ? 'selected' : ''; ?>>Chờ xử lý</option>
                            <option value="Dachapnhan" <?php echo $trangThai == 'Dachapnhan' ? 'selected' : ''; ?>>Đã chấp nhận</option>
                            <option value="Tuchoi" <?php echo $trangThai == 'Tuchoi' ? 'selected' : ''; ?>>Từ chối</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Lọc
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Danh sách yêu cầu -->
        <div class="content-section">
            <?php if (empty($danhSachYeuCau)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Chưa có yêu cầu nào</h3>
                    <p>Bạn chưa gửi yêu cầu nào. Hãy tạo yêu cầu mới.</p>
                    <a href="index.php?action=yeucau&type=suadiem" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tạo yêu cầu mới
                    </a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã YC</th>
                                <th>Loại yêu cầu</th>
                                <th>Mô tả</th>
                                <th>Ngày gửi</th>
                                <th>Trạng thái</th>
                                <th>Minh chứng</th>
                                <th>Ngày xử lý</th>
                                <th>Người xử lý</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($danhSachYeuCau as $yeuCau): ?>
                                <tr>
                                    <td><strong>#<?php echo $yeuCau['maYeuCau']; ?></strong></td>
                                    <td>
                                        <?php if ($yeuCau['loaiYeuCau'] == 'SuaDiem'): ?>
                                            <span class="badge badge-suadiem">
                                                <i class="fas fa-edit"></i> Sửa điểm
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-nghiphep">
                                                <i class="fas fa-calendar-times"></i> Nghỉ phép
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($yeuCau['moTa'], 0, 60)) . (strlen($yeuCau['moTa']) > 60 ? '...' : ''); ?>
                                        <?php if ($yeuCau['loaiYeuCau'] == 'SuaDiem' && $yeuCau['tenHS']): ?>
                                            <div class="request-details">
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($yeuCau['tenHS']); ?>
                                                | <i class="fas fa-book"></i> <?php echo htmlspecialchars($yeuCau['tenMonHoc']); ?>
                                            </div>
                                        <?php elseif ($yeuCau['loaiYeuCau'] == 'NghiPhep'): ?>
                                            <div class="request-details">
                                                <i class="fas fa-calendar"></i> 
                                                <?php echo date('d/m/Y', strtotime($yeuCau['ngayBatDauNghi'])); ?> - 
                                                <?php echo date('d/m/Y', strtotime($yeuCau['ngayKetThucNghi'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($yeuCau['ngayGui'])); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'Choxuly' => 'badge-choxuly',
                                            'Dachapnhan' => 'badge-dachapnhan',
                                            'Tuchoi' => 'badge-tuchoi'
                                        ];
                                        $statusText = [
                                            'Choxuly' => 'Chờ xử lý',
                                            'Dachapnhan' => 'Đã chấp nhận',
                                            'Tuchoi' => 'Từ chối'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $statusClass[$yeuCau['trangThai']]; ?>">
                                            <?php echo $statusText[$yeuCau['trangThai']]; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php 
                                            // Lấy minh chứng từ bảng yeucau (đã được lưu khi gửi yêu cầu)
                                            $minhChungFile = $yeuCau['minhChung'] ?? '';
                                            if (!empty($minhChungFile)): 
                                        ?>
                                            <a href="<?php echo CONTROLLER_URL; ?>/download.php?maYeuCau=<?php echo $yeuCau['maYeuCau']; ?>" 
                                               class="btn-action btn-view" 
                                               style="display: inline-flex; align-items: center; gap: 5px;"
                                               title="Tải xuống minh chứng">
                                                <i class="fas fa-download"></i> Tải về
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #95a5a6;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $yeuCau['ngayXuLy'] ? date('d/m/Y H:i', strtotime($yeuCau['ngayXuLy'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php echo $yeuCau['nguoiXuLy'] ? htmlspecialchars($yeuCau['nguoiXuLy']) : '-'; ?>
                                    </td>
                                    <td>
                                        <a href="dashboard.php?action=chitietyeucau&maYeuCau=<?php echo $yeuCau['maYeuCau']; ?>" 
                                           class="btn-action btn-view">
                                            <i class="fas fa-eye"></i> Xem
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once(__DIR__ . '/../layouts/teacher-layout-footer.php'); ?>
</body>
</html>
