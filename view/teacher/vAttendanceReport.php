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
    <title>Báo cáo chuyên cần - Hệ thống Quản lý Giáo dục</title>
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
                    <h2><i class="fas fa-clipboard-check"></i> Báo cáo chuyên cần</h2>
                    <p>Theo dõi tình hình chuyên cần theo lớp, học kỳ và năm học.</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen ?? 'Giáo viên'); ?></p>
                </div>
            </div>

            <!-- Card bộ lọc (giống mẫu) -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-filter"></i> Bộ lọc</h2>
                </div>

                <form method="GET" action="" class="filter-form" id="filter-form">
                    <div class="filter-section">
                        <input type="hidden" name="action" value="chuyen-can">

                        <div class="filter-group">
                            <label for="tenMonHoc">Môn học</label>
                            <div class="report-subject-name">
                                <p>
                                    <?php echo isset($danhSachMonHoc[0]) ? htmlspecialchars($danhSachMonHoc[0]['tenMonHoc']) : 'Toán'; ?>
                                </p>
                            </div>
                        </div>

                        <div class="filter-group">
                            <label for="maLop">Lớp <span style="color:#dc3545;">*</span></label>
                            <select name="maLop" id="maLop" required>
                                <option value="">Lớp</option>
                                <?php foreach ($danhSachLop as $lop): ?>
                                    <option value="<?php echo $lop['maLop']; ?>"
                                        <?php echo (isset($_GET['maLop']) && $_GET['maLop'] == $lop['maLop']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lop['tenLop']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="hocKy">Học kỳ <span style="color:#dc3545;">*</span></label>
                            <select name="hocKy" id="hocKy" required>
                                <option value="">Học kỳ</option>
                                <option value="1" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '1') ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '2') ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="namHoc">Năm học <span style="color:#dc3545;">*</span></label>
                            <select name="namHoc" id="namHoc" required>
                                <option value="">Năm học</option>
                                <option value="2024-2025" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option>
                                <option value="2023-2024" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2023-2024') ? 'selected' : ''; ?>>2023-2024</option>
                            </select>
                        </div>

                        
                            <div class="filter-actions-button">
                                <button type="submit" class="btn btn-primary" name="submit" value="1">
                                    <i class="fas fa-search"></i> Xem kết quả
                                </button>
                           
                        </div>
                    </div>
                </form>
            </div>

            <?php
            // Thống kê chỉ hiện khi bấm xem kết quả và có dữ liệu
            $showStats = isset($_GET['submit']) && !empty($duLieuBaoCao);

            $soHocSinh = 0;
            $tongNghiCoPhep = 0;
            $tongNghiKhongPhep = 0;

            if ($showStats) {
                $soHocSinh = count($duLieuBaoCao);
                foreach ($duLieuBaoCao as $row) {
                    $tongNghiCoPhep += intval($row['soNghiCoPhep'] ?? 0);
                    $tongNghiKhongPhep += intval($row['soNghiKhongPhep'] ?? 0);
                }
            }
            ?>

            <?php if ($showStats): ?>
                <!-- Card thống kê (giống layout mẫu học tập) -->

                <div class="stats-assign-row">
                    <div class="stat-item">
                        <div class="stat-value exam-sup-primary">
                            <?php echo $soHocSinh; ?>
                        </div>
                        <div class="stat-label">Tổng số học sinh</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-upcoming">
                            <?php echo ($tongNghiCoPhep + $tongNghiKhongPhep); ?>
                        </div>
                        <div class="stat-label">Tổng số nghỉ</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-finish">
                            <?php echo $tongNghiCoPhep; ?>
                        </div>
                        <div class="stat-label">Nghỉ có phép</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-danger">
                            <?php echo $tongNghiKhongPhep; ?>
                        </div>
                        <div class="stat-label">Nghỉ không phép</div>
                    </div>
                </div>

            <?php endif; ?>

            <!-- Card kết quả (giống mẫu) -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-table"></i> Tình hình chuyên cần</h2>

                    <?php if (isset($_GET['submit']) && !empty($duLieuBaoCao)): ?>
                        <?php
                        $params = $_GET;
                        $params['action'] = 'xuat-excel';
                        $params['type'] = 'chuyen-can';
                        unset($params['submit']);
                        ?>
                        <a href="?<?php echo http_build_query($params); ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Xuất Excel
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!isset($_GET['submit'])): ?>
                    <div class="empty-state">
                        <div>
                            <i class="fas fa-info-circle"></i>
                            <p>Vui lòng chọn lớp, học kỳ và năm học, sau đó nhấn "Xem kết quả".</p>
                        </div>
                    </div>

                <?php else: ?>

                    <?php if (!empty($duLieuBaoCao)): ?>

                        <table class="common-table">
                            <thead>
                                <tr>
                                    <th class="small-cell">STT</th>
                                    <th class="large-cell">Họ tên</th>
                                    <th>Lớp</th>
                                    <th class="normal-cell">Tổng số nghỉ</th>

                                    <th class="normal-cell">Nghỉ có phép</th>

                                    <th>Nghỉ không phép</th>
                                    <th>Lý do</th>
                                </tr>
                            </thead>
                            <tbody class="common-table-body">
                                <?php $stt = 1; ?>
                                <?php foreach ($duLieuBaoCao as $row): ?>
                                    <tr class="table-normal-text">
                                        <td class="small-cell"><?php echo $stt++; ?></td>
                                        <td class="large-cell"><?php echo htmlspecialchars($row['tenHocSinh']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tenLop']); ?></td>
                                        <td class="normal-cell"><strong><?php echo intval($row['tongSoNghi'] ?? 0); ?></strong></td>
                                        <td class="normal-cell"><?php echo intval($row['soNghiCoPhep'] ?? 0); ?></td>

                                        <td><?php echo intval($row['soNghiKhongPhep'] ?? 0); ?></td>

                                        <?php
                                        $lyDo = trim($row['lyDoNghiCoPhep'] ?? '');
                                        $align = $lyDo !== '' ? 'left' : 'center';
                                        ?>
                                        <td style="text-align: <?php echo $align; ?>;">
                                            <?php echo $lyDo !== '' ? htmlspecialchars($lyDo) : '-'; ?>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>


                    <?php else: ?>
                        <div class="empty-state">
                            <div>
                                <i class="fas fa-info-circle"></i>
                                <p>Không có dữ liệu để hiển thị. Vui lòng kiểm tra lại bộ lọc.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>

        </div>
    </div>
</body>


</html>