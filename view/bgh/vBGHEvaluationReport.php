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
    <title>Báo cáo kết quả đánh giá</title>
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
        .xep-loai { padding: 5px 10px; border-radius: 5px; font-weight: bold; }
        .xep-loai-gioi { background: #d4edda; color: #155724; }
        .xep-loai-kha { background: #d1ecf1; color: #0c5460; }
        .xep-loai-tb { background: #fff3cd; color: #856404; }
        .xep-loai-yeu { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-star"></i> Báo cáo kết quả đánh giá</h1>
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
            <h2 class="page-title">Báo cáo kết quả đánh giá và xếp loại học sinh</h2>

            <div class="filter-section">
                <form method="GET" action="cBGHReport.php">
                    <input type="hidden" name="action" value="danh-gia">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="maLop">Lớp:</label>
                            <select name="maLop" id="maLop">
                                <option value="">-- Tất cả các lớp --</option>
                                <?php foreach ($danhSachLop as $lop): ?>
                                    <option value="<?php echo $lop['maLop']; ?>" 
                                        <?php echo (isset($_GET['maLop']) && $_GET['maLop'] == $lop['maLop']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lop['tenLop']); ?>
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
                <a href="cBGHReport.php?action=xuat-excel&loai=danh-gia&maLop=<?php echo $_GET['maLop'] ?? ''; ?>&hocKy=<?php echo $_GET['hocKy'] ?? ''; ?>&namHoc=<?php echo $_GET['namHoc'] ?? '2024-2025'; ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </a>
            </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã HS</th>
                        <th>Họ tên</th>
                        <th>Lớp</th>
                        <th>Khối</th>
                        <th>Điểm TB chung</th>
                        <th>Số ngày nghỉ</th>
                        <th>Có phép</th>
                        <th>Không phép</th>
                        <th>Xếp loại</th>
                        <th>Học kỳ</th>
                        <th>Năm học</th>
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
                            <td><?php echo htmlspecialchars($row['maHS']); ?></td>
                            <td><?php echo htmlspecialchars($row['tenHocSinh']); ?></td>
                            <td><?php echo htmlspecialchars($row['tenLop']); ?></td>
                            <td><?php echo htmlspecialchars($row['khoiLop']); ?></td>
                            <td><strong><?php echo $row['diemTBChung'] ? number_format($row['diemTBChung'], 2) : '-'; ?></strong></td>
                            <td><?php echo $row['soNgayNghi'] ?? 0; ?></td>
                            <td><?php echo $row['soNgayCoPhep'] ?? 0; ?></td>
                            <td style="color: red;"><?php echo $row['soNgayKhongPhep'] ?? 0; ?></td>
                            <td>
                                <?php 
                                $xepLoai = $row['xepLoai'];
                                $classXepLoai = '';
                                switch($xepLoai) {
                                    case 'Giỏi': $classXepLoai = 'xep-loai-gioi'; break;
                                    case 'Khá': $classXepLoai = 'xep-loai-kha'; break;
                                    case 'Trung bình': $classXepLoai = 'xep-loai-tb'; break;
                                    case 'Yếu': $classXepLoai = 'xep-loai-yeu'; break;
                                }
                                ?>
                                <span class="xep-loai <?php echo $classXepLoai; ?>">
                                    <?php echo htmlspecialchars($xepLoai); ?>
                                </span>
                            </td>
                            <td><?php echo $row['hocKy'] ?? '-'; ?></td>
                            <td><?php echo $row['namHoc'] ?? '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
