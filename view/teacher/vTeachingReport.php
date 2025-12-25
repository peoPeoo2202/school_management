<?php
require_once(__DIR__ . '/../../config.php');
// Lấy thông tin từ session (đã được kiểm tra ở controller)
$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo giảng dạy - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo VIEW_URL . '/teacher/style.css'; ?>">

</head>

<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">

            <!-- Header theo layout mẫu -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-chalkboard-teacher"></i> Báo cáo giảng dạy</h2>
                    <p>Theo dõi tiến độ giảng dạy theo học kỳ và năm học.</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen ?? 'Giáo viên'); ?></p>
                </div>
            </div>


            <!-- Card bộ lọc -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-filter"></i> Bộ lọc</h2>
                </div>

                <form method="GET" action="" id="filter-form" class="filter-form">
                    <input type="hidden" name="action" value="giang-day">

                    <div class="filter-group">
                        <label for="hocKy">Học kỳ</label>
                        <select name="hocKy" id="hocKy">
                            <option value="">Tất cả học kỳ</option>
                            <option value="1" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '1') ? 'selected' : ''; ?>>Học kỳ 1</option>
                            <option value="2" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '2') ? 'selected' : ''; ?>>Học kỳ 2</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="namHoc">Năm học</label>
                        <select name="namHoc" id="namHoc">
                            <option value="2024-2025" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option>
                            <option value="2023-2024" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2023-2024') ? 'selected' : ''; ?>>2023-2024</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <div class="filter-actions-button">
                            <button type="submit" form="filter-form" name="submit" value="1" class="btn btn-primary">
                                <i class="fas fa-search"></i> Lọc kết quả
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <?php
            $showResult = isset($_GET['submit']);
            $hasData = !empty($duLieuBaoCao);

            // Thống kê chỉ hiện khi bấm lọc và có dữ liệu
            $showStats = $showResult && $hasData;

            $soLop = 0;
            $soMon = 0;
            $tongTietKeHoach = 0;
            $tongTietDaDay = 0;
            $tongTietConLai = 0;

            if ($showStats) {
                $uniqueClasses = array_unique(array_column($duLieuBaoCao, 'tenLop'));
                $uniqueSubjects = array_unique(array_column($duLieuBaoCao, 'tenMonHoc'));
                $soLop = count($uniqueClasses);
                $soMon = count($uniqueSubjects);

                foreach ($duLieuBaoCao as $r) {
                    $tongTietKeHoach += intval($r['tongSoTietKeHoach'] ?? 0);
                    $tongTietDaDay += intval($r['soTietDaDay'] ?? 0);
                    $tongTietConLai += intval($r['soTietConLai'] ?? 0);
                }
            }
            ?>

            <?php if ($showStats): ?>
                <div class="stats-assign-row">
                    <div class="stat-item">
                        <div class="stat-value exam-sup-total">
                            <?php echo $soLop; ?>
                        </div>
                        <div class="stat-label">Số lớp giảng dạy</div>
                    </div>


                    <div class="stat-item">
                        <div class="stat-value exam-sup-finish">
                            <?php echo $tongTietDaDay; ?>
                        </div>
                        <div class="stat-label">Số tiết đã dạy</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-upcoming">
                            <?php echo $tongTietConLai; ?>
                        </div>
                        <div class="stat-label">Số tiết còn lại</div>
                    </div>
                </div>

            <?php endif; ?>

            <!-- Card kết quả -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-table"></i> Báo cáo tiến độ giảng dạy</h2>

                    <?php if ($showResult && $hasData): ?>
                        <?php
                        $params = $_GET;
                        $params['action'] = 'export';
                        $params['type'] = 'teaching';
                        ?>
                        <a href="../../controller/cReport.php?<?php echo http_build_query($params); ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Xuất Excel
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!$showResult): ?>
                    <div class="empty-state">
                        <div>
                            <i class="fas fa-info-circle"></i>
                            <p>Vui lòng chọn học kỳ, năm học, sau đó nhấn "Lọc kết quả".</p>
                        </div>
                    </div>

                <?php else: ?>

                    <?php if ($hasData): ?>
                        <div class="table-container">
                            <table class="common-table">
                                <thead>
                                    <tr>
                                        <th class="small-cell">STT</th>
                                        <th class="small-cell">Lớp</th>
                                        <th>Môn học</th>
                                        <th>Học kỳ</th>
                                        <th class="table-small-text">Năm học</th>
                                        <th class="small-cell">Tổng tiết</th>
                                        <th class="small-cell">Đã dạy</th>
                                        <th class="small-cell">Còn lại</th>
                                        <th>Tiến độ</th>
                                    </tr>
                                </thead>
                                <tbody class="common-table-body">
                                    <?php $stt = 1; ?>
                                    <?php foreach ($duLieuBaoCao as $row): ?>
                                        <?php
                                        $tong = intval($row['tongSoTietKeHoach'] ?? 0);
                                        $daDay = intval($row['soTietDaDay'] ?? 0);
                                        $conLai = intval($row['soTietConLai'] ?? 0);
                                        $completionRate = $tong > 0 ? ($daDay / $tong * 100) : 0;

                                        $progressClass = 'badge badge-danger';
                                        $progressColor = '#ff0000'; 

                                        if ($completionRate > 80) {
                                            $progressClass = 'badge badge-success';
                                            $progressColor = '#00C94B'; 
                                        } elseif ($completionRate > 50) {
                                            $progressClass = 'badge badge-primary';
                                            $progressColor = '#2563EB'; 
                                        } elseif ($completionRate > 25) {
                                            $progressClass = 'badge badge-warning';
                                            $progressColor = '#F59E0B';
                                        }

                                        ?>
                                        <tr>
                                            <td class="small-cell"><?php echo $stt++; ?></td>
                                            <td class="small-cell"><?php echo htmlspecialchars($row['tenLop']); ?></td>
                                            <td><?php echo htmlspecialchars($row['tenMonHoc']); ?></td>
                                            <td class="normal-cell"><?php echo intval($row['hocKy']); ?></td>
                                            <td ><?php echo htmlspecialchars($row['namHoc']); ?></td>
                                            <td class="normal-cell"><strong><?php echo $tong; ?></strong></td>
                                            <td class="normal-cell">
                                                <strong style="color:#00C94B;"><?php echo $daDay; ?></strong>
                                            </td>
                                            <td class="normal-cell">
                                                <strong style="color: <?php echo $conLai > 0 ? '#ff0000' : '#00C94B'; ?>;">
                                                    <?php echo $conLai; ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <div class="progress-wrapper">
                                                    <span class="progress-text">
                                                        <?php echo round($completionRate, 1); ?>%
                                                    </span>
                                                    <div class="progress-bar">
                                                        <div
                                                            class="progress-fill"
                                                            style="
                    width: <?php echo min(100, max(0, $completionRate)); ?>%;
                    background: <?php echo $progressColor; ?>;">
                                                        </div>
                                                    </div>


                                                </div>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>
                        <div class="empty-state">
                            <div>
                                <i class="fas fa-info-circle"></i>
                                <p>Không có dữ liệu kế hoạch giảng dạy để hiển thị. Vui lòng kiểm tra lại bộ lọc hoặc liên hệ quản trị viên.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>

        </div>
    </div>
</body>

</html>