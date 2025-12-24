<?php
// Lấy thông tin từ session (đã được kiểm tra ở controller)
$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo đã nộp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../student/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .content-area { flex: 1; padding: 20px; }
        .header { background: white; padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #1e3c72; font-size: 24px; }
        .btn { padding: 10px 18px; border: none; border-radius: 8px; text-decoration: none; font-size: 14px; transition: all 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #2a5298; color: white; }
        .btn-primary:hover { background: #1e3c72; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .logout-btn { background: #dc3545; color: white; padding: 10px 18px; border: none; border-radius: 8px; text-decoration: none; font-size: 14px; transition: all 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .logout-btn:hover { background: #c82333; }
        .main-content { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .page-title { color: #1e3c72; margin-bottom: 25px; padding-bottom: 12px; border-bottom: 3px solid #2a5298; font-size: 22px; }
        .filter-section { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { color: #333; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        .form-group select, .form-group input { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        table thead { background: #1e3c72; color: white; }
        table th, table td { padding: 12px; text-align: left; border: 1px solid #dee2e6; }
        table tbody tr:nth-child(even) { background-color: #f8f9fa; }
        table tbody tr:hover { background-color: #e9ecef; }
        .no-data { text-align: center; padding: 40px; color: #666; font-style: italic; }
        .text-center { text-align: center; }
        .badge { padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-primary { background: #2a5298; color: white; }
        .badge-success { background: #28a745; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        .badge-warning { background: #ffc107; color: #000; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateBGH.php'); ?>
        <div class="content-area">
            <div class="container">
        <div class="header">
            <h1><i class="fas fa-file-upload"></i> Báo cáo đã nộp</h1>
            <div class="btn-group">
                <a href="../../controller/cBGHReport.php?action=index" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <a href="../public/index.php?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <div class="main-content">
            <h2 class="page-title">Danh sách báo cáo giáo viên đã nộp</h2>

            <div class="filter-section">
                <form method="GET" action="../../controller/cBGHReport.php">
                    <input type="hidden" name="action" value="bao-cao-da-nop">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="maGV">Giáo viên:</label>
                            <select name="maGV" id="maGV">
                                <option value="">-- Tất cả giáo viên --</option>
                                <?php foreach ($danhSachGiaoVien as $gv): ?>
                                    <option value="<?php echo $gv['maGV']; ?>" 
                                        <?php echo (isset($_GET['maGV']) && $_GET['maGV'] == $gv['maGV']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($gv['hoTen']); ?> - <?php echo htmlspecialchars($gv['toBoMon']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="loaiBaoCao">Loại báo cáo:</label>
                            <select name="loaiBaoCao" id="loaiBaoCao">
                                <option value="">-- Tất cả --</option>
                                <option value="hoc-tap" <?php echo (isset($_GET['loaiBaoCao']) && $_GET['loaiBaoCao'] == 'hoc-tap') ? 'selected' : ''; ?>>Học tập</option>
                                <option value="chuyen-can" <?php echo (isset($_GET['loaiBaoCao']) && $_GET['loaiBaoCao'] == 'chuyen-can') ? 'selected' : ''; ?>>Chuyên cần</option>
                                <option value="giang-day" <?php echo (isset($_GET['loaiBaoCao']) && $_GET['loaiBaoCao'] == 'giang-day') ? 'selected' : ''; ?>>Giảng dạy</option>
                                <option value="danh-gia" <?php echo (isset($_GET['loaiBaoCao']) && $_GET['loaiBaoCao'] == 'danh-gia') ? 'selected' : ''; ?>>Đánh giá</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tuNgay">Từ ngày:</label>
                            <input type="date" name="tuNgay" id="tuNgay" value="<?php echo $_GET['tuNgay'] ?? ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="denNgay">Đến ngày:</label>
                            <input type="date" name="denNgay" id="denNgay" value="<?php echo $_GET['denNgay'] ?? ''; ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Lọc dữ liệu
                    </button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="text-center">STT</th>
                        <th>Tên báo cáo</th>
                        <th class="text-center">Loại báo cáo</th>
                        <th>Giáo viên</th>
                        <th>Tổ bộ môn</th>
                        <th class="text-center">Ngày nộp</th>
                        <th>Mô tả</th>
                        <th class="text-center">Tên file</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($duLieuBaoCao)): ?>
                        <tr>
                            <td colspan="9" class="no-data">
                                <i class="fas fa-info-circle"></i> Không có dữ liệu để hiển thị.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $stt = 1; 
                        foreach ($duLieuBaoCao as $row): 
                            $badgeClass = '';
                            switch($row['loaiBaoCao']) {
                                case 'hoc-tap':
                                    $badgeClass = 'badge-primary';
                                    break;
                                case 'chuyen-can':
                                    $badgeClass = 'badge-success';
                                    break;
                                case 'giang-day':
                                    $badgeClass = 'badge-info';
                                    break;
                                case 'danh-gia':
                                    $badgeClass = 'badge-warning';
                                    break;
                            }
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $stt++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['tenBaoCao']); ?></strong></td>
                            <td class="text-center">
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($row['tenLoaiBaoCao']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['tenGiaoVien']); ?></td>
                            <td><?php echo htmlspecialchars($row['toBoMon']); ?></td>
                            <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($row['ngayNop'])); ?></td>
                            <td><?php echo htmlspecialchars($row['moTa'] ?? 'Không có mô tả'); ?></td>
                            <td class="text-center">
                                <small><?php echo htmlspecialchars($row['tenFile']); ?></small>
                            </td>
                            <td class="text-center">
                                <?php 
                                $fileExists = file_exists($row['duongDan']);
                                if ($fileExists): 
                                ?>
                                    <a href="../controller/download.php?file=<?php echo urlencode($row['duongDan']); ?>" 
                                       class="btn btn-info btn-sm" 
                                       title="Tải xuống file">
                                        <i class="fas fa-download"></i> Tải
                                    </a>
                                <?php else: ?>
                                    <span style="color: #dc3545; font-size: 11px;">
                                        <i class="fas fa-exclamation-triangle"></i> File không tồn tại
                                    </span>
                                    <br>
                                    <small style="color: #999; font-size: 10px;">
                                        <?php echo htmlspecialchars($row['duongDan']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
            </div>
        </div>
    </div>
</body>
</html>
