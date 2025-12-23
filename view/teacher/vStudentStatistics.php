<?php
// Lấy thông tin từ session (đã được kiểm tra ở controller)
require_once(__DIR__ . '/../../config.php');
$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê số liệu học sinh - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="<?php echo VIEW_URL . '/teacher/style.css'; ?>">
    <style>
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
            background: linear-gradient(135deg, #52c234, #61d345);
            box-shadow: 0 4px 15px rgba(82, 194, 52, 0.4);
        }

        .perf-good {
            background: linear-gradient(135deg, #20c9f3, #17a2b8);
            box-shadow: 0 4px 15px rgba(32, 201, 243, 0.4);
        }

        .perf-average {
            background: linear-gradient(135deg, #ffd54f, #ffb300);
            color: #2c2c2c;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
        }

        .perf-average .perf-number,
        .perf-average .perf-label {
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.5);
        }

        .perf-weak {
            background: linear-gradient(135deg, #f48fb1, #e91e63);
            box-shadow: 0 4px 15px rgba(233, 30, 99, 0.4);
        }

        .perf-none {
            background: linear-gradient(135deg, #90a4ae, #607d8b);
            box-shadow: 0 4px 15px rgba(96, 125, 139, 0.3);
        }

        /* Grade Info Styles */
        .grade-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .grade-header {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .grade-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .grade-stat {
            text-align: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .grade-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 5px;
            font-weight: 500;
        }

        .grade-value {
            font-size: 24px;
            font-weight: bold;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .grade-value.high {
            color: #4ade80;
        }

        .grade-value.low {
            color: #f87171;
        }

        /* Performance Grid Styles */
        .performance-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-top: 15px;
        }

        .perf-item {
            text-align: center;
            padding: 18px 12px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .perf-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .perf-number {
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            line-height: 1;
        }

        .perf-label {
            font-size: 12px;
            line-height: 1.3;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .perf-label small {
            font-size: 10px;
            opacity: 0.9;
            display: block;
            margin-top: 2px;
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

            .grade-stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .performance-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }

            .perf-item {
                padding: 12px 8px;
            }

            .perf-number {
                font-size: 20px;
            }

            .perf-label {
                font-size: 10px;
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
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">

            <!-- HEADER -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-users"></i> Thống kê số liệu học sinh</h2>
                    <p>Theo dõi sĩ số, giới tính và kết quả học tập theo lớp.</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen ?? 'Giáo viên'); ?></p>
                </div>
            </div>

            <!-- CARD FILTER -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fa-solid fa-filter"></i> Bộ lọc thống kê
                    </h2>
                </div>

                <form method="GET" action="" id="filter-form" class="filter-form">
                    <input type="hidden" name="action" value="thong-ke-hoc-sinh">

                    <div class="filter-group">
                        <label for="maLop">Lớp</label>
                        <select name="maLop" id="maLop">
                            <option value="">Tất cả lớp</option>
                            <?php foreach ($danhSachLop as $lop): ?>
                                <option value="<?php echo $lop['maLop']; ?>"
                                    <?php echo ($_GET['maLop'] ?? '') == $lop['maLop'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lop['tenLop']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <div class="filter-actions-button">
                            <button type="submit" name="submit" value="1" class="btn btn-primary">
                                <i class="fas fa-search"></i> Lọc kết quả
                            </button>


                        </div>
                    </div>
                </form>
            </div>

            <?php if (!empty($duLieuBaoCao)): ?>

                <!-- STATS OVERVIEW -->
                <div class="stats-assign-row">
                    <div class="stat-item">
                        <div class="stat-value exam-sup-total">
                            <?php echo array_sum(array_column($duLieuBaoCao, 'siSo')); ?>
                        </div>
                        <div class="stat-label">Tổng học sinh</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-finish">
                            <?php echo array_sum(array_column($duLieuBaoCao, 'soHSNam')); ?>
                        </div>
                        <div class="stat-label">HS Nam</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-upcoming">
                            <?php echo array_sum(array_column($duLieuBaoCao, 'soHSNu')); ?>
                        </div>
                        <div class="stat-label">HS Nữ</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-danger">
                            <?php echo array_sum(array_column($duLieuBaoCao, 'soHSYeu')); ?>
                        </div>
                        <div class="stat-label">HS Yếu</div>
                    </div>
                </div>

                <!-- CARD CONTENT -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fa-solid fa-layer-group"></i> Thống kê theo lớp
                        </h2>
                        <?php if (isset($_GET['submit'])): ?>
                            <a href="cReport.php?action=xuat-excel&type=thong-ke-hoc-sinh&<?php echo http_build_query($_GET); ?>"
                                class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Xuất Excel
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php foreach ($duLieuBaoCao as $lop): ?>
                        <!-- MỖI LỚP = 1 BLOCK -->
                        <div class="class-block">

                            <!-- HEADER LỚP -->
                            <div class="class-header">
                                <div class="info-details-box">
                                    <div class="info-item">
                                        <p class="info-label">Lớp:</p>
                                        <p class="info-value"><?php echo htmlspecialchars($lop['tenLop']); ?></p>
                                    </div>
                                    <div class="info-item">
                                        <p class="info-label">Môn học:</p>
                                        <p class="info-value"><?php echo htmlspecialchars($lop['tenMonHoc']); ?></p>
                                    </div>
                                    <div class="info-item">
                                        <p class="info-label">Sĩ số:</p>
                                        <p class="info-value"><?php echo $lop['siSo']; ?> học sinh</p>
                                    </div>
                                </div>
                            </div>

                            <!-- GRID NAM / NỮ -->
                            <div class="status-grid-2">
                                <div class="statistics-box">
                                    <div>
                                        <div class="stat-value total-student-gender"><?php echo $lop['soHSNam']; ?></div>
                                        <div class="stat-label gender">Nam</div>
                                    </div>
                                    <div class="gender-bar gender-male"
                                        style="flex-basis: <?php echo $lop['siSo'] > 0 ? ($lop['soHSNam'] / $lop['siSo'] * 100) : 0; ?>%;">
                                        Nam:
                                        <?php echo $lop['siSo'] > 0 ? round($lop['soHSNam'] / $lop['siSo'] * 100, 1) : 0; ?>%
                                    </div>
                                </div>

                                <div class="statistics-box">
                                    <div>
                                        <div class="stat-value total-student-gender"><?php echo $lop['soHSNu']; ?></div>
                                        <div class="stat-label gender">Nữ</div>
                                    </div>
                                    <div class="gender-bar gender-female"
                                        style="flex-basis: <?php echo $lop['siSo'] > 0 ? ($lop['soHSNu'] / $lop['siSo'] * 100) : 0; ?>%;">
                                        Nữ:
                                        <?php echo $lop['siSo'] > 0 ? round($lop['soHSNu'] / $lop['siSo'] * 100, 1) : 0; ?>%
                                    </div>
                                </div>
                            </div>

                            <!-- PERFORMANCE -->
                            <div class="performance-section">
                                <div class="performance-title">
                                    <i class="fas fa-chart-pie"></i>
                                    Xếp loại điểm môn <?php echo htmlspecialchars($lop['tenMonHoc']); ?>
                                </div>

                                <div class="performance-bars">
                                    <div class="performance-category">
                                        <div class="category-title">Phân loại điểm</div>

                                        <div class="performance-item">
                                            <span>Giỏi (≥8.0)</span>
                                            <span class="perf-excellent"><?php echo $lop['soHSGioi']; ?></span>
                                        </div>

                                        <div class="performance-item">
                                            <span>Khá (6.5–7.9)</span>
                                            <span class="perf-good"><?php echo $lop['soHSKha']; ?></span>
                                        </div>

                                        <div class="performance-item">
                                            <span>TB (5.0–6.4)</span>
                                            <span class="perf-average"><?php echo $lop['soHSTB']; ?></span>
                                        </div>

                                        <div class="performance-item">
                                            <span>Yếu (&lt;5.0)</span>
                                            <span class="perf-weak"><?php echo $lop['soHSYeu']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div> <!-- END class-block -->
                    <?php endforeach; ?>
                </div>


            <?php else: ?>

                <!-- EMPTY STATE -->
                <div class="empty-state">
                    <div>
                        <i class="fas fa-info-circle"></i>
                        <p>Không có dữ liệu để hiển thị. Vui lòng kiểm tra lại bộ lọc.</p>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <script>
        document.getElementById('maLop').addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
    </script>
</body>


</html>