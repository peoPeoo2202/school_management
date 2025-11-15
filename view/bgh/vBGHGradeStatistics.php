<?php
// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true || $_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    header("Location: ../../public/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thống kê điểm môn học</title>
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
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table thead { background: #1e3c72; color: white; }
        table th, table td { padding: 12px; text-align: left; border: 1px solid #dee2e6; font-size: 14px; }
        table tbody tr:nth-child(even) { background-color: #f8f9fa; }
        .no-data { text-align: center; padding: 40px; color: #666; font-style: italic; }
        .export-section { display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> Thống kê điểm môn học</h1>
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
            <h2 class="page-title">Thống kê phân bố điểm số theo môn học</h2>

            <div class="filter-section">
                <form method="GET" action="cBGHReport.php">
                    <input type="hidden" name="action" value="thong-ke-diem">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="maMonHoc">Môn học:</label>
                            <select name="maMonHoc" id="maMonHoc">
                                <option value="">-- Tất cả môn học --</option>
                                <?php foreach ($danhSachMonHoc as $mon): ?>
                                    <option value="<?php echo $mon['maMonHoc']; ?>" 
                                        <?php echo (isset($_GET['maMonHoc']) && $_GET['maMonHoc'] == $mon['maMonHoc']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mon['tenMonHoc']); ?>
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
                <a href="cBGHReport.php?action=xuat-excel&loai=thong-ke-diem&maMonHoc=<?php echo $_GET['maMonHoc'] ?? ''; ?>&hocKy=<?php echo $_GET['hocKy'] ?? ''; ?>&namHoc=<?php echo $_GET['namHoc'] ?? '2024-2025'; ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </a>
            </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Môn học</th>
                        <th>Học kỳ</th>
                        <th>Năm học</th>
                        <th>Tổng số HS</th>
                        <th>Điểm TB</th>
                        <th>Điểm cao nhất</th>
                        <th>Điểm thấp nhất</th>
                        <th>HS Giỏi</th>
                        <th>HS Khá</th>
                        <th>HS TB</th>
                        <th>HS Yếu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($duLieuBaoCao)): ?>
                        <tr>
                            <td colspan="12" class="no-data">
                                <i class="fas fa-info-circle"></i> Không có dữ liệu để hiển thị.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $stt = 1; foreach ($duLieuBaoCao as $row): ?>
                        <tr>
                            <td><?php echo $stt++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['tenMonHoc']); ?></strong></td>
                            <td><?php echo $row['hocKy']; ?></td>
                            <td><?php echo $row['namHoc']; ?></td>
                            <td><?php echo $row['tongSoHocSinh']; ?></td>
                            <td><strong><?php echo number_format($row['diemTrungBinh'], 2); ?></strong></td>
                            <td style="color: green;"><strong><?php echo number_format($row['diemCaoNhat'], 2); ?></strong></td>
                            <td style="color: red;"><?php echo number_format($row['diemThapNhat'], 2); ?></td>
                            <td><?php echo $row['soHSGioi']; ?></td>
                            <td><?php echo $row['soHSKha']; ?></td>
                            <td><?php echo $row['soHSTrungBinh']; ?></td>
                            <td><?php echo $row['soHSYeu']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
