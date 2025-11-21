<?php
// Lấy thông tin từ session (đã được kiểm tra ở controller)
$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê điểm môn học - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        * {
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
            color: #5081BE;
        }

        .header h1 i {
            color: #ffc107;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #545b62;
        }

        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filter-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }

        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
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

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
            color: white;
        }

        .stat-icon.students {
            background: linear-gradient(135deg, #5081BE, #2d5a8c);
        }

        .stat-icon.average {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .stat-icon.high {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .stat-icon.low {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .report-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
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
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .report-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            white-space: nowrap;
        }

        .report-table td {
            color: #666;
        }

        .report-table tr:hover {
            background: #f8f9fa;
        }

        .distribution-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }

        .distribution-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .distribution-chart {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .grade-bar {
            text-align: center;
        }

        .grade-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 10px;
        }

        .bar-container {
            height: 150px;
            display: flex;
            align-items: end;
            justify-content: center;
            margin-bottom: 10px;
        }

        .bar {
            width: 60px;
            border-radius: 6px 6px 0 0;
            display: flex;
            align-items: end;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
            padding-bottom: 5px;
            transition: all 0.3s;
        }

        .bar:hover {
            transform: scale(1.05);
        }

        .bar.excellent {
            background: linear-gradient(to top, #28a745, #20c997);
        }

        .bar.good {
            background: linear-gradient(to top, #17a2b8, #20c997);
        }

        .bar.average {
            background: linear-gradient(to top, #ffc107, #fd7e14);
        }

        .bar.weak {
            background: linear-gradient(to top, #dc3545, #e83e8c);
        }

        .grade-count {
            color: #666;
            font-size: 14px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
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

            .stats-cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .distribution-chart {
                grid-template-columns: repeat(2, 1fr);
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
                        <i class="fas fa-chart-line"></i>
                        Thống kê điểm môn học
                    </h1>
                </div>

                <div class="filter-section">
                    <div class="filter-title">
                        <i class="fas fa-filter"></i>
                        Chọn môn học để thống kê
                    </div>
                    <form method="GET" action="" class="filter-form" id="filter-form">
                        <input type="hidden" name="action" value="thong-ke-diem">

                        <div class="form-group">
                            <label for="maMonHoc">Môn học: <span style="color: #dc3545;">*</span></label>
                            <?php if (count($danhSachMonHoc) == 1): ?>
                                <!-- Nếu chỉ dạy 1 môn, hiển thị tên môn và set hidden input -->
                                <div style="padding: 10px; background: #f8f9fa; border-radius: 6px; border: 1px solid #ddd;">
                                    <strong><?php echo htmlspecialchars($danhSachMonHoc[0]['tenMonHoc']); ?></strong>
                                </div>
                                <input type="hidden" name="maMonHoc" value="<?php echo $danhSachMonHoc[0]['maMonHoc']; ?>">
                            <?php else: ?>
                                <!-- Nếu dạy nhiều môn, cho phép chọn -->
                                <select name="maMonHoc" id="maMonHoc" required>
                                    <option value="">Chọn môn học</option>
                                    <?php foreach ($danhSachMonHoc as $monHoc): ?>
                                        <option value="<?php echo $monHoc['maMonHoc']; ?>"
                                            <?php echo (isset($_GET['maMonHoc']) && $_GET['maMonHoc'] == $monHoc['maMonHoc']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($monHoc['tenMonHoc']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="maLop">Lớp: <span style="color: #dc3545;">*</span></label>
                            <select name="maLop" id="maLop" required>
                                <option value="">Chọn lớp</option>
                                <?php foreach ($danhSachLop as $lop): ?>
                                    <option value="<?php echo $lop['maLop']; ?>"
                                        <?php echo (isset($_GET['maLop']) && $_GET['maLop'] == $lop['maLop']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lop['tenLop']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="hocKy">Học kỳ: <span style="color: #dc3545;">*</span></label>
                            <select name="hocKy" id="hocKy" required>
                                <option value="">Chọn học kỳ</option>
                                <option value="1" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '1') ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '2') ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="namHoc">Năm học: <span style="color: #dc3545;">*</span></label>
                            <select name="namHoc" id="namHoc" required>
                                <option value="">Chọn năm học</option>
                                <option value="2024-2025" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option>
                                <option value="2023-2024" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2023-2024') ? 'selected' : ''; ?>>2023-2024</option>
                            </select>
                        </div>
                    </form>

                    <div class="filter-buttons">
                        <button type="submit" form="filter-form" class="btn btn-primary" name="submit" value="1">
                            <i class="fas fa-chart-bar"></i>
                            Xem thống kê
                        </button>
                        <?php if (isset($_GET['submit']) && !empty($duLieuBaoCao)): ?>
                            <?php 
                            $params = $_GET;
                            $params['action'] = 'xuat-excel';
                            $params['type'] = 'thong-ke-diem';
                            unset($params['submit']);
                            ?>
                            <a href="?<?php echo http_build_query($params); ?>" class="btn btn-success">
                                <i class="fas fa-file-excel"></i>
                                Xuất Excel
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!isset($_GET['submit'])): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Vui lòng chọn môn học, lớp, học kỳ và năm học, sau đó nhấn "Xem thống kê".
                    </div>
                <?php elseif (!empty($duLieuBaoCao)): ?>
                    <?php 
                    // Lấy bản ghi đầu tiên hoặc tổng hợp dữ liệu
                    $row = $duLieuBaoCao[0]; 
                    ?>
                        <!-- Stats Cards -->
                        <div class="stats-cards">
                            <div class="stat-card">
                                <div class="stat-icon students">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-value"><?php echo $row['soHocSinh']; ?></div>
                                <div class="stat-label">Tổng học sinh</div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-icon average">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($row['diemTrungBinh'], 2); ?></div>
                                <div class="stat-label">Điểm trung bình</div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-icon high">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($row['diemCaoNhat'], 1); ?></div>
                                <div class="stat-label">Điểm cao nhất</div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-icon low">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($row['diemThapNhat'], 1); ?></div>
                                <div class="stat-label">Điểm thấp nhất</div>
                            </div>
                        </div>

                        <!-- Detailed Table -->
                        <div class="report-section">
                            <div class="report-header">
                                <h3>
                                    <i class="fas fa-table"></i>
                                    Chi tiết thống kê - <?php echo htmlspecialchars($row['tenMonHoc']); ?>
                                </h3>
                            </div>
                            <div class="table-container">
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Môn học</th>
                                            <th>Lớp</th>
                                            <th>Học kỳ</th>
                                            <th>Năm học</th>
                                            <th>Số học sinh</th>
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
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['tenMonHoc']); ?></td>
                                            <td><?php echo htmlspecialchars($row['tenLop'] ?? 'Tất cả'); ?></td>
                                            <td><?php echo $row['hocKy']; ?></td>
                                            <td><?php echo htmlspecialchars($row['namHoc']); ?></td>
                                            <td><?php echo $row['soHocSinh']; ?></td>
                                            <td><strong><?php echo number_format($row['diemTrungBinh'], 2); ?></strong></td>
                                            <td><?php echo number_format($row['diemCaoNhat'], 1); ?></td>
                                            <td><?php echo number_format($row['diemThapNhat'], 1); ?></td>
                                            <td><span style="color: #28a745; font-weight: 600;"><?php echo $row['soHSGioi']; ?></span></td>
                                            <td><span style="color: #17a2b8; font-weight: 600;"><?php echo $row['soHSKha']; ?></span></td>
                                            <td><span style="color: #ffc107; font-weight: 600;"><?php echo $row['soHSTB']; ?></span></td>
                                            <td><span style="color: #dc3545; font-weight: 600;"><?php echo $row['soHSYeu']; ?></span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Danh sách học sinh -->
                        <?php if (!empty($danhSachHocSinh)): ?>
                        <div class="report-section" style="margin-top: 30px;">
                            <div class="report-header">
                                <h3>
                                    <i class="fas fa-users"></i>
                                    Danh sách học sinh
                                </h3>
                            </div>
                            <div class="table-container">
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Mã HS</th>
                                            <th>Họ và tên</th>
                                            <th>Lớp</th>
                                            <th>Điểm TB môn</th>
                                            <th>Xếp loại</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $stt = 1;
                                        foreach ($danhSachHocSinh as $hs): 
                                            // Xác định màu sắc theo xếp loại
                                            $mauXepLoai = '';
                                            switch($hs['xepLoai']) {
                                                case 'Giỏi':
                                                    $mauXepLoai = 'color: #28a745; font-weight: 600;';
                                                    break;
                                                case 'Khá':
                                                    $mauXepLoai = 'color: #17a2b8; font-weight: 600;';
                                                    break;
                                                case 'Trung bình':
                                                    $mauXepLoai = 'color: #ffc107; font-weight: 600;';
                                                    break;
                                                case 'Yếu':
                                                    $mauXepLoai = 'color: #dc3545; font-weight: 600;';
                                                    break;
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo $stt++; ?></td>
                                            <td><?php echo $hs['maHS']; ?></td>
                                            <td style="text-align: left; padding-left: 15px;"><?php echo htmlspecialchars($hs['tenHocSinh']); ?></td>
                                            <td><?php echo htmlspecialchars($hs['tenLop']); ?></td>
                                            <td><strong><?php echo number_format($hs['diemTBMon'], 2); ?></strong></td>
                                            <td><span style="<?php echo $mauXepLoai; ?>"><?php echo $hs['xepLoai']; ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Distribution Chart -->
                        <div class="distribution-section">
                            <div class="distribution-title">
                                <i class="fas fa-chart-pie"></i>
                                Phân bố xếp loại học sinh
                            </div>
                            <div class="distribution-chart">
                                <div class="grade-bar">
                                    <div class="grade-label">Giỏi (≥ 8.0)</div>
                                    <div class="bar-container">
                                        <div class="bar excellent" style="height: <?php echo ($row['soHocSinh'] > 0) ? ($row['soHSGioi'] / $row['soHocSinh'] * 100) : 0; ?>%;">
                                            <?php echo $row['soHSGioi']; ?>
                                        </div>
                                    </div>
                                    <div class="grade-count">
                                        <?php echo $row['soHocSinh'] > 0 ? round($row['soHSGioi'] / $row['soHocSinh'] * 100, 1) : 0; ?>%
                                    </div>
                                </div>

                                <div class="grade-bar">
                                    <div class="grade-label">Khá (6.5 - 7.9)</div>
                                    <div class="bar-container">
                                        <div class="bar good" style="height: <?php echo ($row['soHocSinh'] > 0) ? ($row['soHSKha'] / $row['soHocSinh'] * 100) : 0; ?>%;">
                                            <?php echo $row['soHSKha']; ?>
                                        </div>
                                    </div>
                                    <div class="grade-count">
                                        <?php echo $row['soHocSinh'] > 0 ? round($row['soHSKha'] / $row['soHocSinh'] * 100, 1) : 0; ?>%
                                    </div>
                                </div>

                                <div class="grade-bar">
                                    <div class="grade-label">TB (5.0 - 6.4)</div>
                                    <div class="bar-container">
                                        <div class="bar average" style="height: <?php echo ($row['soHocSinh'] > 0) ? ($row['soHSTB'] / $row['soHocSinh'] * 100) : 0; ?>%;">
                                            <?php echo $row['soHSTB']; ?>
                                        </div>
                                    </div>
                                    <div class="grade-count">
                                        <?php echo $row['soHocSinh'] > 0 ? round($row['soHSTB'] / $row['soHocSinh'] * 100, 1) : 0; ?>%
                                    </div>
                                </div>

                                <div class="grade-bar">
                                    <div class="grade-label">Yếu (< 5.0)</div>
                                            <div class="bar-container">
                                                <div class="bar weak" style="height: <?php echo ($row['soHocSinh'] > 0) ? ($row['soHSYeu'] / $row['soHocSinh'] * 100) : 0; ?>%;">
                                                    <?php echo $row['soHSYeu']; ?>
                                                </div>
                                            </div>
                                            <div class="grade-count">
                                                <?php echo $row['soHocSinh'] > 0 ? round($row['soHSYeu'] / $row['soHocSinh'] * 100, 1) : 0; ?>%
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-chart-line"></i><br><br>
                        Không có dữ liệu thống kê cho môn học đã chọn.<br>
                        Vui lòng kiểm tra lại học kỳ và năm học.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <script>
        // Form validation is handled by HTML5 required attributes
        // User must manually click "Xem thống kê" button to submit
    </script>
</body>

</html>