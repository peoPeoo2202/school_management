<?php
if (!isset($_SESSION)) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xử lý yêu cầu - Hệ thống quản lý trường học</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .top-header {
            background: white;
            padding: 15px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-header h1 {
            color: #1e3c72;
            font-size: 24px;
            margin: 0;
        }

        .top-header .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .request-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }



        /* Thống kê */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }

        .stat-card.warning { border-left-color: #f59e0b; }
        .stat-card.success { border-left-color: #10b981; }
        .stat-card.danger { border-left-color: #ef4444; }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #6b7280;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #1f2937;
        }

        /* Bộ lọc */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-section h3 {
            margin: 0 0 15px 0;
            color: #1f2937;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #374151;
        }

        .form-group select,
        .form-group input {
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn-filter {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-filter:hover {
            background: #5568d3;
        }

        .btn-reset {
            padding: 10px 20px;
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-reset:hover {
            background: #4b5563;
        }

        /* Bảng danh sách */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-nghiphep {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-suadiem {
            background: #fce7f3;
            color: #9f1239;
        }

        .btn-view {
            padding: 6px 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view:hover {
            background: #5568d3;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>
    <div class="top-header">
        <h1><i class="fas fa-clipboard-check"></i> Xử lý yêu cầu</h1>
        <div class="btn-group">
            <a href="../view/bgh/vBGHDashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <a href="../public/logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </div>

    <div class="request-container">

        <!-- Thông báo -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Thống kê -->
        <div class="stats-container">
            <?php
            $tongChoXuLy = 0;
            $tongDaChapNhan = 0;
            $tongDaTuChoi = 0;
            
            if (isset($thongKe) && is_array($thongKe)) {
                foreach ($thongKe as $tk) {
                    if ($tk['trangThai'] == 'Choxuly') {
                        $tongChoXuLy += $tk['soLuong'];
                    } elseif ($tk['trangThai'] == 'Dachapnhan') {
                        $tongDaChapNhan += $tk['soLuong'];
                    } elseif ($tk['trangThai'] == 'Tuchoi') {
                        $tongDaTuChoi += $tk['soLuong'];
                    }
                }
            }
            ?>
            <div class="stat-card warning">
                <h3>Chờ xử lý</h3>
                <div class="number"><?php echo $tongChoXuLy; ?></div>
            </div>
            <div class="stat-card success">
                <h3>Đã chấp nhận</h3>
                <div class="number"><?php echo $tongDaChapNhan; ?></div>
            </div>
            <div class="stat-card danger">
                <h3>Đã từ chối</h3>
                <div class="number"><?php echo $tongDaTuChoi; ?></div>
            </div>
            <div class="stat-card">
                <h3>Tổng yêu cầu</h3>
                <div class="number"><?php echo ($tongChoXuLy + $tongDaChapNhan + $tongDaTuChoi); ?></div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="filter-section">
            <h3>🔍 Bộ lọc</h3>
            <form method="GET" action="" class="filter-form">
                <input type="hidden" name="action" value="danhsach">
                
                <div class="form-group">
                    <label>Loại yêu cầu</label>
                    <select name="loaiYeuCau">
                        <option value="all" <?php echo (!isset($loaiYeuCau) || $loaiYeuCau == 'all') ? 'selected' : ''; ?>>Tất cả</option>
                        <option value="NghiPhep" <?php echo (isset($loaiYeuCau) && $loaiYeuCau == 'NghiPhep') ? 'selected' : ''; ?>>Nghỉ phép</option>
                        <option value="SuaDiem" <?php echo (isset($loaiYeuCau) && $loaiYeuCau == 'SuaDiem') ? 'selected' : ''; ?>>Sửa điểm</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="trangThai">
                        <option value="all" <?php echo (!isset($trangThai) || $trangThai == 'all') ? 'selected' : ''; ?>>Tất cả</option>
                        <option value="Choxuly" <?php echo (isset($trangThai) && $trangThai == 'Choxuly') ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="Dachapnhan" <?php echo (isset($trangThai) && $trangThai == 'Dachapnhan') ? 'selected' : ''; ?>>Đã chấp nhận</option>
                        <option value="Tuchoi" <?php echo (isset($trangThai) && $trangThai == 'Tuchoi') ? 'selected' : ''; ?>>Đã từ chối</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Từ ngày</label>
                    <input type="date" name="tuNgay" value="<?php echo isset($tuNgay) ? $tuNgay : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Đến ngày</label>
                    <input type="date" name="denNgay" value="<?php echo isset($denNgay) ? $denNgay : ''; ?>">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-filter">Lọc</button>
                </div>

                <div class="form-group">
                    <a href="?action=danhsach" class="btn-reset" style="text-align: center; text-decoration: none; display: block;">Đặt lại</a>
                </div>
            </form>
        </div>

        <!-- Bảng danh sách -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Mã YC</th>
                        <th>Loại YC</th>
                        <th>Giáo viên</th>
                        <th>Mô tả</th>
                        <th>Ngày gửi</th>
                        <th>Trạng thái</th>
                        <th>Người xử lý</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($danhSachYeuCau) && count($danhSachYeuCau) > 0): ?>
                        <?php foreach ($danhSachYeuCau as $yc): ?>
                            <tr>
                                <td>#<?php echo $yc['maYeuCau']; ?></td>
                                <td>
                                    <?php if ($yc['loaiYeuCau'] == 'NghiPhep'): ?>
                                        <span class="badge badge-nghiphep">Nghỉ phép</span>
                                    <?php else: ?>
                                        <span class="badge badge-suadiem">Sửa điểm</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($yc['tenGV']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($yc['emailGV']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(substr($yc['moTa'], 0, 50)) . '...'; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($yc['ngayGui'])); ?></td>
                                <td>
                                    <?php if ($yc['trangThai'] == 'Choxuly'): ?>
                                        <span class="badge badge-pending">Chờ xử lý</span>
                                    <?php elseif ($yc['trangThai'] == 'Dachapnhan'): ?>
                                        <span class="badge badge-approved">Đã chấp nhận</span>
                                    <?php else: ?>
                                        <span class="badge badge-rejected">Đã từ chối</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $yc['nguoiXuLy'] ? htmlspecialchars($yc['nguoiXuLy']) : '-'; ?></td>
                                <td>
                                    <a href="?action=chitiet&maYeuCau=<?php echo $yc['maYeuCau']; ?>" class="btn-view">
                                        Xem chi tiết
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-data">
                                Không có yêu cầu nào
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
