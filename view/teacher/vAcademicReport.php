<?php
// Session đã được start từ controller, không cần start lại

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo kết quả học tập - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{
            margin: 0
        }
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

        .container {
            max-width: 100%;
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
            margin: 0;
            color: #5081BE;
        }

        .header h1 i {
            color: #5081BE;
        }

        .filter-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 32px;
        }

        .filter-title {
            font-size: 17px;
            font-weight: 600;
            color: #333;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            font-size: 17px;
        }

        .form-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #5081BE;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .report-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .report-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }

        .report-header h3 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-container {
            overflow-x: auto;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .report-table th,
        .report-table td {
            padding: 12px 8px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .report-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            position: sticky;
            top: 0;
            white-space: nowrap;
        }

        .report-table td {
            color: #666;
        }

        .report-table tr:hover {
            background: #f8f9fa;
        }

        .grade-excellent {
            color: #28a745;
            font-weight: 600;
        }

        .grade-good {
            color: #17a2b8;
            font-weight: 600;
        }

        .grade-average {
            color: #ffc107;
            font-weight: 600;
        }

        .grade-weak {
            color: #dc3545;
            font-weight: 600;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .stats-summary {
            background: #f8f9fa;
            padding: 20px 25px;
            border-top: 1px solid #eee;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .filter-buttons {
                justify-content: center;
            }

            .report-table {
                font-size: 12px;
            }

            .report-table th,
            .report-table td {
                padding: 8px 4px;
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
            <div class="container">
                <div class="header">
                    <h1>
                        <i class="fas fa-book"></i>
                        Báo cáo kết quả học tập
                    </h1>
                </div>

                <div class="filter-section">
                    <div class="filter-title">
                        <i class="fas fa-filter"></i>
                        Bộ lọc báo cáo
                    </div>
                    <form method="GET" action="../../controller/cReport.php" class="filter-form">
                        <input type="hidden" name="action" value="academic">

                        <div class="form-group">
                            <label for="maLop">Lớp:</label>
                            <select name="maLop" id="maLop">
                                <option value="">Tất cả lớp</option>
                                <?php foreach ($danhSachLop as $lop): ?>
                                    <option value="<?php echo $lop['maLop']; ?>"
                                        <?php echo (isset($_GET['maLop']) && $_GET['maLop'] == $lop['maLop']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lop['tenLop']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="maMonHoc">Môn học:</label>
                            <select name="maMonHoc" id="maMonHoc">
                                <option value="">Tất cả môn</option>
                                <?php foreach ($danhSachMonHoc as $monHoc): ?>
                                    <option value="<?php echo $monHoc['maMonHoc']; ?>"
                                        <?php echo (isset($_GET['maMonHoc']) && $_GET['maMonHoc'] == $monHoc['maMonHoc']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($monHoc['tenMonHoc']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="hocKy">Học kỳ:</label>
                            <select name="hocKy" id="hocKy">
                                <option value="">Tất cả học kỳ</option>
                                <option value="1" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '1') ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '2') ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="namHoc">Năm học:</label>
                            <select name="namHoc" id="namHoc">
                                <option value="2024-2025" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option>
                                <option value="2023-2024" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2023-2024') ? 'selected' : ''; ?>>2023-2024</option>
                            </select>
                        </div>
                    </form>

                    <div class="filter-buttons">
                        <button type="submit" form="filter-form" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Lọc kết quả
                        </button>
                        <a href="cReport.php?action=xuat-excel&type=hoc-tap&<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i>
                            Xuất Excel
                        </a>
                    </div>
                </div>

                <div class="report-section">
                    <div class="report-header">
                        <h3>
                            <i class="fas fa-table"></i>
                            Kết quả học tập
                        </h3>
                    </div>

                    <?php if (!empty($duLieuBaoCao)): ?>
                        <div class="table-container">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Họ tên</th>
                                        <th>Lớp</th>
                                        <th>Môn học</th>
                                        <th>Học kỳ</th>
                                        <th>Năm học</th>
                                        <th>Điểm miệng</th>
                                        <th>Điểm 15 phút</th>
                                        <th>Điểm 1 tiết</th>
                                        <th>Điểm giữa kỳ</th>
                                        <th>Điểm cuối kỳ</th>
                                        <th>Điểm TB</th>
                                        <th>Xếp loại</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stt = 1;
                                    $tongDiem = 0;
                                    $soHocSinh = count($duLieuBaoCao);
                                    ?>
                                    <?php foreach ($duLieuBaoCao as $row): ?>
                                        <?php
                                        $diemTB = floatval($row['diemTrungBinh']);
                                        $tongDiem += $diemTB;

                                        $xepLoai = '';
                                        $classXepLoai = '';
                                        if ($diemTB >= 8.0) {
                                            $xepLoai = 'Giỏi';
                                            $classXepLoai = 'grade-excellent';
                                        } elseif ($diemTB >= 6.5) {
                                            $xepLoai = 'Khá';
                                            $classXepLoai = 'grade-good';
                                        } elseif ($diemTB >= 5.0) {
                                            $xepLoai = 'Trung bình';
                                            $classXepLoai = 'grade-average';
                                        } else {
                                            $xepLoai = 'Yếu';
                                            $classXepLoai = 'grade-weak';
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $stt++; ?></td>
                                            <td><?php echo htmlspecialchars($row['tenHocSinh']); ?></td>
                                            <td><?php echo htmlspecialchars($row['tenLop']); ?></td>
                                            <td><?php echo htmlspecialchars($row['tenMonHoc']); ?></td>
                                            <td><?php echo $row['hocKy']; ?></td>
                                            <td><?php echo htmlspecialchars($row['namHoc']); ?></td>
                                            <td><?php echo $row['diemMieng'] ? number_format($row['diemMieng'], 1) : '-'; ?></td>
                                            <td><?php echo $row['diem15phut'] ? number_format($row['diem15phut'], 1) : '-'; ?></td>
                                            <td><?php echo $row['diem1tiet'] ? number_format($row['diem1tiet'], 1) : '-'; ?></td>
                                            <td><?php echo $row['diemGiuaKy'] ? number_format($row['diemGiuaKy'], 1) : '-'; ?></td>
                                            <td><?php echo $row['diemCuoiKy'] ? number_format($row['diemCuoiKy'], 1) : '-'; ?></td>
                                            <td><strong><?php echo number_format($diemTB, 2); ?></strong></td>
                                            <td><span class="<?php echo $classXepLoai; ?>"><?php echo $xepLoai; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="stats-summary">
                            <div class="stat-item">
                                <div class="stat-label">Tổng số học sinh</div>
                                <div class="stat-value"><?php echo $soHocSinh; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Điểm trung bình</div>
                                <div class="stat-value"><?php echo $soHocSinh > 0 ? number_format($tongDiem / $soHocSinh, 2) : '0'; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Số HS giỏi</div>
                                <div class="stat-value grade-excellent">
                                    <?php
                                    $soGioi = 0;
                                    foreach ($duLieuBaoCao as $row) {
                                        if (floatval($row['diemTrungBinh']) >= 8.0) $soGioi++;
                                    }
                                    echo $soGioi;
                                    ?>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Số HS khá</div>
                                <div class="stat-value grade-good">
                                    <?php
                                    $soKha = 0;
                                    foreach ($duLieuBaoCao as $row) {
                                        $diem = floatval($row['diemTrungBinh']);
                                        if ($diem >= 6.5 && $diem < 8.0) $soKha++;
                                    }
                                    echo $soKha;
                                    ?>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Số HS trung bình</div>
                                <div class="stat-value grade-average">
                                    <?php
                                    $soTB = 0;
                                    foreach ($duLieuBaoCao as $row) {
                                        $diem = floatval($row['diemTrungBinh']);
                                        if ($diem >= 5.0 && $diem < 6.5) $soTB++;
                                    }
                                    echo $soTB;
                                    ?>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Số HS yếu</div>
                                <div class="stat-value grade-weak">
                                    <?php
                                    $soYeu = 0;
                                    foreach ($duLieuBaoCao as $row) {
                                        if (floatval($row['diemTrungBinh']) < 5.0) $soYeu++;
                                    }
                                    echo $soYeu;
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-info-circle"></i>
                            Không có dữ liệu để hiển thị. Vui lòng chọn bộ lọc phù hợp.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            // Auto submit form when filter changes
            document.querySelectorAll('select').forEach(function(select) {
                select.addEventListener('change', function() {
                    document.querySelector('form').submit();
                });
            });
        </script>
</body>

</html>
