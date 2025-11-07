<?php

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
    <title>Thống kê số liệu học sinh - Hệ thống Quản lý Giáo dục</title>
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

        .container {
            max-width: 100%;
        }

        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #667eea;
        }

        .header h1 i {
            color: #6f42c1;
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
            background: #667eea;
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

        .class-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .class-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .class-card:hover {
            transform: translateY(-5px);
        }

        .class-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .class-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .class-info h3 {
            color: #333;
            margin-bottom: 5px;
        }

        .class-info .siso {
            color: #666;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
        }

        .gender-chart {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .gender-bar {
            flex: 1;
            height: 40px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .gender-male {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }

        .gender-female {
            background: linear-gradient(135deg, #e83e8c, #dc3545);
        }

        .performance-section {
            margin-top: 20px;
        }

        .performance-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .performance-bars {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .performance-category {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .category-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .performance-items {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .performance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .performance-label {
            color: #666;
        }

        .performance-value {
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            color: white;
            font-size: 12px;
        }

        .perf-excellent {
            background: #28a745;
        }

        .perf-good {
            background: #17a2b8;
        }

        .perf-average {
            background: #ffc107;
            color: #333;
        }

        .perf-weak {
            background: #dc3545;
        }

        .summary-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .summary-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .summary-number {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .summary-label {
            color: #666;
            font-size: 14px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
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

            .class-cards {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .performance-bars {
                grid-template-columns: 1fr;
            }

            .summary-stats {
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
                        <i class="fas fa-users"></i>
                        Thống kê số liệu học sinh
                    </h1>
                </div>

                <div class="filter-section">
                    <div class="filter-title">
                        <i class="fas fa-filter"></i>
                        Bộ lọc thống kê
                    </div>
                    <form method="GET" action="../../controller/cReport.php" class="filter-form">
                        <input type="hidden" name="action" value="student_stats">

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
                    </form>

                    <div class="filter-buttons">
                        <button type="submit" form="filter-form" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Lọc kết quả
                        </button>
                        <a href="cReport.php?action=xuat-excel&type=thong-ke-hoc-sinh&<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i>
                            Xuất Excel
                        </a>
                    </div>
                </div>

                <?php if (!empty($duLieuBaoCao)): ?>
                    <div class="class-cards">
                        <?php foreach ($duLieuBaoCao as $lop): ?>
                            <div class="class-card">
                                <div class="class-header">
                                    <div class="class-icon">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div class="class-info">
                                        <h3><?php echo htmlspecialchars($lop['tenLop']); ?></h3>
                                        <div class="siso">Sĩ số: <?php echo $lop['siSo']; ?> học sinh</div>
                                    </div>
                                </div>

                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $lop['soHSNam']; ?></div>
                                        <div class="stat-label">Nam</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $lop['soHSNu']; ?></div>
                                        <div class="stat-label">Nữ</div>
                                    </div>
                                </div>

                                <div class="gender-chart">
                                    <div class="gender-bar gender-male" style="flex-basis: <?php echo $lop['siSo'] > 0 ? ($lop['soHSNam'] / $lop['siSo'] * 100) : 0; ?>%;">
                                        Nam: <?php echo $lop['siSo'] > 0 ? round($lop['soHSNam'] / $lop['siSo'] * 100, 1) : 0; ?>%
                                    </div>
                                    <div class="gender-bar gender-female" style="flex-basis: <?php echo $lop['siSo'] > 0 ? ($lop['soHSNu'] / $lop['siSo'] * 100) : 0; ?>%;">
                                        Nữ: <?php echo $lop['siSo'] > 0 ? round($lop['soHSNu'] / $lop['siSo'] * 100, 1) : 0; ?>%
                                    </div>
                                </div>

                                <div class="performance-section">
                                    <div class="performance-title">
                                        <i class="fas fa-chart-pie"></i>
                                        Phân loại học sinh
                                    </div>
                                    <div class="performance-bars">
                                        <div class="performance-category">
                                            <div class="category-title">Học lực</div>
                                            <div class="performance-items">
                                                <div class="performance-item">
                                                    <span class="performance-label">Giỏi:</span>
                                                    <span class="performance-value perf-excellent"><?php echo $lop['soHSGioi']; ?></span>
                                                </div>
                                                <div class="performance-item">
                                                    <span class="performance-label">Khá:</span>
                                                    <span class="performance-value perf-good"><?php echo $lop['soHSKha']; ?></span>
                                                </div>
                                                <div class="performance-item">
                                                    <span class="performance-label">TB:</span>
                                                    <span class="performance-value perf-average"><?php echo $lop['soHSTB']; ?></span>
                                                </div>
                                                <div class="performance-item">
                                                    <span class="performance-label">Yếu:</span>
                                                    <span class="performance-value perf-weak"><?php echo $lop['soHSYeu']; ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="performance-category">
                                            <div class="category-title">Hạnh kiểm</div>
                                            <div class="performance-items">
                                                <div class="performance-item">
                                                    <span class="performance-label">Tốt:</span>
                                                    <span class="performance-value perf-excellent"><?php echo $lop['soHSHKTot']; ?></span>
                                                </div>
                                                <div class="performance-item">
                                                    <span class="performance-label">Khá:</span>
                                                    <span class="performance-value perf-good"><?php echo $lop['soHSHKKha']; ?></span>
                                                </div>
                                                <div class="performance-item">
                                                    <span class="performance-label">TB:</span>
                                                    <span class="performance-value perf-average"><?php echo $lop['soHSHKTB']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Summary Section -->
                    <div class="summary-section">
                        <div class="summary-title">
                            <i class="fas fa-chart-bar"></i>
                            Tổng kết thống kê
                        </div>
                        <div class="summary-stats">
                            <div class="summary-item">
                                <div class="summary-number">
                                    <?php
                                    $tongSiSo = array_sum(array_column($duLieuBaoCao, 'siSo'));
                                    echo $tongSiSo;
                                    ?>
                                </div>
                                <div class="summary-label">Tổng học sinh</div>
                            </div>

                            <div class="summary-item">
                                <div class="summary-number">
                                    <?php
                                    $tongNam = array_sum(array_column($duLieuBaoCao, 'soHSNam'));
                                    echo $tongNam;
                                    ?>
                                </div>
                                <div class="summary-label">Tổng HS Nam</div>
                            </div>

                            <div class="summary-item">
                                <div class="summary-number">
                                    <?php
                                    $tongNu = array_sum(array_column($duLieuBaoCao, 'soHSNu'));
                                    echo $tongNu;
                                    ?>
                                </div>
                                <div class="summary-label">Tổng HS Nữ</div>
                            </div>

                            <div class="summary-item">
                                <div class="summary-number">
                                    <?php
                                    $tongGioi = array_sum(array_column($duLieuBaoCao, 'soHSGioi'));
                                    echo $tongGioi;
                                    ?>
                                </div>
                                <div class="summary-label">Tổng HS Giỏi</div>
                            </div>

                            <div class="summary-item">
                                <div class="summary-number">
                                    <?php
                                    $tongKha = array_sum(array_column($duLieuBaoCao, 'soHSKha'));
                                    echo $tongKha;
                                    ?>
                                </div>
                                <div class="summary-label">Tổng HS Khá</div>
                            </div>

                            <div class="summary-item">
                                <div class="summary-number">
                                    <?php
                                    $tongHKTot = array_sum(array_column($duLieuBaoCao, 'soHSHKTot'));
                                    echo $tongHKTot;
                                    ?>
                                </div>
                                <div class="summary-label">Tổng HK Tốt</div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i>
                        Không có dữ liệu để hiển thị. Vui lòng kiểm tra lại bộ lọc.
                    </div>
                <?php endif; ?>
            </div>

            <script>
                // Auto submit form when filter changes
                document.getElementById('maLop').addEventListener('change', function() {
                    document.querySelector('form').submit();
                });
            </script>
</body>

</html>
