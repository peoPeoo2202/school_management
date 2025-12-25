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

</head>

<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">

            <!-- HEADER -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-users"></i> Thống kê Điểm môn học</h2>
                    <p>Xem thống kê chi tiết kết quả học tập và xếp loại điểm</p>
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
                        <i class="fa-solid fa-filter"></i> Bộ lọc
                    </h2>
                </div>

                <form method="GET" action="" id="filter-form" class="filter-form">
                    <div class="filter-section"><input type="hidden" name="action" value="thong-ke-hoc-sinh">

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
                        <div class="stat-value exam-sup-default">
                            <?php echo array_sum(array_column($duLieuBaoCao, 'siSo')); ?>
                        </div>
                        <div class="stat-label">Tổng học sinh</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-finish">
                            <?php echo array_sum(array_column($duLieuBaoCao, 'soHSNam')); ?>
                        </div>
                        <div class="stat-label">HS Giỏi</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-primary">
                            <?php echo array_sum(array_column($duLieuBaoCao, 'soHSNu')); ?>
                        </div>
                        <div class="stat-label">HS Khá</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value exam-sup-upcoming">
                            <?php echo array_sum(array_column($duLieuBaoCao, 'soHSYeu')); ?>
                        </div>
                        <div class="stat-label">HS Trung Bình</div>
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

                            <!-- THÔNG TIN LỚP -->
                            <div class="class-block-info section-card">
                                <div class="section-title">
                                    <i class="fas fa-building"></i>
                                    Thông tin lớp
                                </div>
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
                            </div>

                            <!-- PERFORMANCE -->
                            <div class="performance-section section-card">
                                <div class="section-title">
                                    <i class="fas fa-chart-pie"></i>
                                    Xếp loại điểm môn <?php echo htmlspecialchars($lop['tenMonHoc']); ?> - <?php echo htmlspecialchars($lop['tenLop']); ?>
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