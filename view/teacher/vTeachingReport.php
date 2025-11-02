<?php
// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo giảng dạy - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header h1 i {
            color: #fd7e14;
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

        .schedule-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .overview-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .overview-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 20px;
            color: white;
        }

        .overview-icon.classes {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .overview-icon.subjects {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .overview-icon.assignments {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .overview-icon.completed {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
        }

        .overview-number {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .overview-label {
            color: #666;
            font-size: 14px;
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
            white-space: nowrap;
        }

        .report-table td {
            color: #666;
        }

        .report-table tr:hover {
            background: #f8f9fa;
        }

        .assignment-progress {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-bar {
            flex: 1;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            transition: width 0.3s;
        }

        .progress-text {
            font-size: 12px;
            color: #666;
            white-space: nowrap;
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

            .filter-buttons {
                justify-content: center;
            }

            .schedule-overview {
                grid-template-columns: repeat(2, 1fr);
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
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-chalkboard-teacher"></i>
                Báo cáo giảng dạy
            </h1>
            <div class="user-info">
                <span>
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($hoTen); ?>
                </span>
            </div>
        </div>

        <a href="cReport.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Quay lại danh sách báo cáo
        </a>

        <div class="filter-section">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                Bộ lọc báo cáo
            </div>
            <form method="GET" action="cReport.php" class="filter-form">
                <input type="hidden" name="action" value="giang-day">
                
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
                <a href="cReport.php?action=xuat-excel&type=giang-day&<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                    <i class="fas fa-file-excel"></i>
                    Xuất Excel
                </a>
            </div>
        </div>

        <?php if (!empty($duLieuBaoCao)): ?>
            <!-- Overview Cards -->
            <div class="schedule-overview">
                <div class="overview-card">
                    <div class="overview-icon classes">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="overview-number">
                        <?php 
                        $uniqueClasses = array_unique(array_column($duLieuBaoCao, 'tenLop'));
                        echo count($uniqueClasses);
                        ?>
                    </div>
                    <div class="overview-label">Số lớp giảng dạy</div>
                </div>
                
                <div class="overview-card">
                    <div class="overview-icon subjects">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="overview-number">
                        <?php 
                        $uniqueSubjects = array_unique(array_column($duLieuBaoCao, 'tenMonHoc'));
                        echo count($uniqueSubjects);
                        ?>
                    </div>
                    <div class="overview-label">Số môn học</div>
                </div>
                
                <div class="overview-card">
                    <div class="overview-icon assignments">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="overview-number">
                        <?php 
                        $totalTietDaDay = array_sum(array_column($duLieuBaoCao, 'soTietDaDay'));
                        echo $totalTietDaDay;
                        ?>
                    </div>
                    <div class="overview-label">Số tiết đã dạy</div>
                </div>
                
                <div class="overview-card">
                    <div class="overview-icon completed">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="overview-number">
                        <?php 
                        $totalTietConLai = array_sum(array_column($duLieuBaoCao, 'soTietConLai'));
                        echo $totalTietConLai;
                        ?>
                    </div>
                    <div class="overview-label">Số tiết còn lại</div>
                </div>
            </div>

            <!-- Teaching Schedule Table -->
            <div class="report-section">
                <div class="report-header">
                    <h3>
                        <i class="fas fa-table"></i>
                        Báo cáo tiến độ giảng dạy
                    </h3>
                </div>
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Lớp</th>
                                <th>Môn học</th>
                                <th>Học kỳ</th>
                                <th>Năm học</th>
                                <th>Tổng số tiết</th>
                                <th>Số tiết đã dạy</th>
                                <th>Số tiết còn lại</th>
                                <th>Tiến độ hoàn thành</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stt = 1;
                            foreach ($duLieuBaoCao as $row): 
                                $completionRate = $row['tongSoTietKeHoach'] > 0 ? 
                                    ($row['soTietDaDay'] / $row['tongSoTietKeHoach'] * 100) : 0;
                                
                                // Xác định màu sắc dựa trên tiến độ
                                $progressColor = '';
                                if ($completionRate >= 90) {
                                    $progressColor = '#28a745'; // Xanh lá
                                } elseif ($completionRate >= 70) {
                                    $progressColor = '#ffc107'; // Vàng
                                } elseif ($completionRate >= 50) {
                                    $progressColor = '#fd7e14'; // Cam
                                } else {
                                    $progressColor = '#dc3545'; // Đỏ
                                }
                            ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td><?php echo htmlspecialchars($row['tenLop']); ?></td>
                                <td><?php echo htmlspecialchars($row['tenMonHoc']); ?></td>
                                <td><?php echo $row['hocKy']; ?></td>
                                <td><?php echo htmlspecialchars($row['namHoc']); ?></td>
                                <td><strong><?php echo $row['tongSoTietKeHoach']; ?></strong></td>
                                <td>
                                    <span style="color: #28a745; font-weight: 600;">
                                        <?php echo $row['soTietDaDay']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="color: <?php echo $row['soTietConLai'] > 0 ? '#dc3545' : '#28a745'; ?>; font-weight: 600;">
                                        <?php echo $row['soTietConLai']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="assignment-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $completionRate; ?>%; background: <?php echo $progressColor; ?>;"></div>
                                        </div>
                                        <span class="progress-text" style="color: <?php echo $progressColor; ?>; font-weight: 600;">
                                            <?php echo round($completionRate, 1); ?>%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-info-circle"></i>
                Không có dữ liệu kế hoạch giảng dạy để hiển thị. 
                <br>Vui lòng kiểm tra lại bộ lọc hoặc liên hệ quản trị viên để thiết lập kế hoạch giảng dạy.
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto submit form when filter changes
        document.querySelectorAll('select').forEach(function(select) {
            select.addEventListener('change', function() {
                document.querySelector('form').submit();
            });
        });

        // Tooltip for progress bars
        document.querySelectorAll('.progress-bar').forEach(function(bar) {
            const progressText = bar.parentElement.querySelector('.progress-text');
            const progressFill = bar.querySelector('.progress-fill');
            
            bar.addEventListener('mouseenter', function() {
                bar.style.transform = 'scaleY(1.5)';
                bar.style.transition = 'transform 0.2s';
            });
            
            bar.addEventListener('mouseleave', function() {
                bar.style.transform = 'scaleY(1)';
            });
        });
    </script>
</body>
</html>