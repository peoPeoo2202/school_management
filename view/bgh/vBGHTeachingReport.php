<?php
// Lấy thông tin từ session (đã được kiểm tra ở controller)
$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo giảng dạy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; }
        .header { background: white; padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #1e3c72; font-size: 24px; }
        .btn { padding: 10px 18px; border: none; border-radius: 8px; text-decoration: none; font-size: 14px; transition: all 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #2a5298; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
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
        .form-group select { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        table thead { background: #1e3c72; color: white; }
        table th, table td { padding: 10px; text-align: left; border: 1px solid #dee2e6; }
        table th { white-space: nowrap; }
        table tbody tr:nth-child(even) { background-color: #f8f9fa; }
        table tbody tr:hover { background-color: #e9ecef; }
        .no-data { text-align: center; padding: 40px; color: #666; font-style: italic; }
        .export-section { display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 15px; }
        .progress-good { color: #28a745; font-weight: bold; }
        .progress-warning { color: #ffc107; font-weight: bold; }
        .progress-danger { color: #dc3545; font-weight: bold; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chalkboard-teacher"></i> Báo cáo giảng dạy</h1>
            <div class="btn-group">
                <a href="cBGHReport.php?action=index" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <a href="../public/index.php?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <div class="main-content">
            <h2 class="page-title">Báo cáo tiến độ giảng dạy của giáo viên</h2>

            <div class="filter-section">
                <form method="GET" action="cBGHReport.php">
                    <input type="hidden" name="action" value="giang-day">
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
                            <label for="hocKy">Học kỳ:</label>
                            <select name="hocKy" id="hocKy">
                                <option value="">-- Tất cả --</option>
                                <option value="1" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '1') ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '2') ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="namHoc">Năm học:</label>
                            <select name="namHoc" id="namHoc">
                                <option value="2024-2025" <?php echo (!isset($_GET['namHoc']) || $_GET['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option>
                                <option value="2023-2024" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2023-2024') ? 'selected' : ''; ?>>2023-2024</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Lọc dữ liệu
                    </button>
                </form>
            </div>

            <?php if (!empty($duLieuBaoCao)): ?>
            <div class="export-section">
                <a href="cBGHReport.php?action=xuat-excel&loai=giang-day&maGV=<?php echo $_GET['maGV'] ?? ''; ?>&hocKy=<?php echo $_GET['hocKy'] ?? ''; ?>&namHoc=<?php echo $_GET['namHoc'] ?? '2024-2025'; ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </a>
            </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã GV</th>
                        <th>Họ tên GV</th>
                        <th>Tổ bộ môn</th>
                        <th>Lớp</th>
                        <th>Môn học</th>
                        <th>HK</th>
                        <th>Năm học</th>
                        <th class="text-center">Tiết KH</th>
                        <th class="text-center">Đã dạy</th>
                        <th class="text-center">Còn lại</th>
                        <th class="text-center">Hoàn thành</th>
                        <th class="text-center">Sĩ số</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($duLieuBaoCao)): ?>
                        <tr>
                            <td colspan="13" class="no-data">
                                <i class="fas fa-info-circle"></i> Không có dữ liệu để hiển thị.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $stt = 1; foreach ($duLieuBaoCao as $row): 
                            $tyLe = $row['tyLeHoanThanh'];
                            $classProgress = '';
                            if ($tyLe >= 80) {
                                $classProgress = 'progress-good';
                            } elseif ($tyLe >= 50) {
                                $classProgress = 'progress-warning';
                            } else {
                                $classProgress = 'progress-danger';
                            }
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $stt++; ?></td>
                            <td><?php echo htmlspecialchars($row['maGV']); ?></td>
                            <td><?php echo htmlspecialchars($row['tenGiaoVien']); ?></td>
                            <td><?php echo htmlspecialchars($row['toBoMon']); ?></td>
                            <td><?php echo htmlspecialchars($row['tenLop']); ?></td>
                            <td><?php echo htmlspecialchars($row['tenMonHoc']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['hocKy']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['namHoc']); ?></td>
                            <td class="text-center"><strong><?php echo $row['tongSoTietKeHoach']; ?></strong></td>
                            <td class="text-center"><strong><?php echo $row['soTietDaDay']; ?></strong></td>
                            <td class="text-center"><?php echo $row['soTietConLai']; ?></td>
                            <td class="text-center <?php echo $classProgress; ?>"><?php echo $tyLe; ?>%</td>
                            <td class="text-center"><?php echo $row['siSo']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
