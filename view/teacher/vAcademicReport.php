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
    <title>Báo cáo kết quả học tập - Hệ thống Quản lý Giáo dục</title>
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
                    <h2><i class="fas fa-book"></i> Báo cáo kết quả học tập</h2>
                    <p>Theo dõi kết quả học tập theo lớp, học kỳ và năm học.</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen ?? 'Giáo viên'); ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-filter"></i> Bộ lọc </h2>
                </div>

                <form method="GET" action="" class="filter-form" id="   ">
                    <div class="filter-section">
                        <input type="hidden" name="action" value="hoc-tap">

                        <div class="filter-group">
                            <label for="tenMonHoc">Môn học</label>
                            <div class="report-subject-name">
                                <p><?php echo isset($danhSachMonHoc[0]) ? htmlspecialchars($danhSachMonHoc[0]['tenMonHoc']) : 'Toán'; ?></p>
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

                        <!-- actions giống layout mẫu -->
                        <div class="filter-actions-button ">

                            <button type="submit" class="btn btn-primary" name="submit" value="1">
                                <i class="fas fa-search"></i> Xem kết quả
                            </button>
                        </div>
                    </div>


                </form>
            </div>
            <?php
            $showStats = isset($_GET['submit']) && !empty($duLieuBaoCao);
            ?>

            <?php if ($showStats): ?>
                <?php
                $soHocSinh = count($duLieuBaoCao);
                $tongDiem = 0;
                $soGioi = 0;
                $soYeu = 0;

                foreach ($duLieuBaoCao as $row) {
                    $diem = floatval($row['diemTrungBinh'] ?? 0);
                    $tongDiem += $diem;
                    if ($diem >= 8.0) $soGioi++;
                    if ($diem < 5.0) $soYeu++;
                }
                ?>

                <div class="stats-assign-row">
                    <div class="stat-item">
                        <div class="stat-value exam-sup-primary">
                            <?php echo $soHocSinh; ?>
                        </div>
                        <div class="stat-label">Tổng số học sinh</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-upcoming">
                            <?php echo $soHocSinh > 0 ? number_format($tongDiem / $soHocSinh, 2) : '0'; ?>
                        </div>
                        <div class="stat-label">Điểm trung bình</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-finish">
                            <?php echo $soGioi; ?>
                        </div>
                        <div class="stat-label">Số HS giỏi</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-danger">
                            <?php echo $soYeu; ?>
                        </div>
                        <div class="stat-label">Số HS yếu</div>
                    </div>
                </div>

            <?php endif; ?>
            <!-- Card kết quả -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-table"></i> Kết quả học tập</h2>
                    <?php if (isset($_GET['submit']) && !empty($duLieuBaoCao)): ?>
                        <?php
                        $params = $_GET;
                        $params['action'] = 'xuat-excel';
                        $params['type'] = 'hoc-tap';
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
                        <div>
                            <table class="common-table">
                                <thead>
                                    <tr>
                                        <th class="small-cell">STT</th>
                                        <th>Họ tên</th>
                                        <th class="small-cell">Lớp</th>
                                        <th class="table-subject-name">Môn học</th>
                                        <th class="small-cell">Học kỳ</th>
                                        <th class="table-small-text">Năm học</th>
                                        <th class="small-cell">Điểm TX1</th>
                                        <th class="small-cell">Điểm TX2</th>
                                        <th class="small-cell">Điểm TX3</th>
                                        <th class="small-cell">Điểm TX4</th>
                                        <th class="small-cell">Điểm giữa kỳ</th>
                                        <th class="small-cell">Điểm cuối kỳ</th>
                                        <th class="small-cell">Điểm TB</th>
                                        <th class="range-cell">Xếp loại</th>
                                    </tr>
                                </thead>
                                <tbody class="common-table-body">
                                    <?php
                                    $stt = 1;
                                    ?>
                                    <?php foreach ($duLieuBaoCao as $row): ?>
                                        <?php
                                        $diemTB = floatval($row['diemTrungBinh']);
                                        $tongDiem += $diemTB;

                                        $xepLoai = '';
                                        $classXepLoai = '';
                                        if ($diemTB >= 9.0) {
                                            $xepLoai = 'Xuất sắc';
                                            $classXepLoai = 'grade-excellent';
                                        } elseif ($diemTB >= 8.0) {
                                            $xepLoai = 'Giỏi';
                                            $classXepLoai = 'badge badge-success';
                                        } elseif ($diemTB >= 6.5) {
                                            $xepLoai = 'Khá';
                                            $classXepLoai = 'badge badge-primary';
                                        } elseif ($diemTB >= 5.0) {
                                            $xepLoai = 'Trung bình';
                                            $classXepLoai = 'badge badge-warning';
                                        } else {
                                            $xepLoai = 'Yếu';
                                            $classXepLoai = 'badge badge-danger';
                                        }
                                        ?>
                                        <tr>
                                            <td class="small-cell"><?php echo $stt++; ?></td>
                                            <td><?php echo htmlspecialchars($row['tenHocSinh']); ?></td>
                                            <td class="small-cell"><?php echo htmlspecialchars($row['tenLop']); ?></td>
                                            <td class="table-small-text"><?php echo htmlspecialchars($row['tenMonHoc']); ?></td>
                                            <td class="small-cell"><?php echo $row['hocKy']; ?></td>
                                            <td class="table-small-text"><?php echo htmlspecialchars($row['namHoc']); ?></td>

                                            <td class="small-cell"><?php echo isset($row['diemTX1']) && $row['diemTX1'] !== null ? number_format($row['diemTX1'], 1) : '-'; ?></td>
                                            <td class="small-cell"><?php echo isset($row['diemTX2']) && $row['diemTX2'] !== null ? number_format($row['diemTX2'], 1) : '-'; ?></td>
                                            <td class="small-cell"><?php echo isset($row['diemTX3']) && $row['diemTX3'] !== null ? number_format($row['diemTX3'], 1) : '-'; ?></td>
                                            <td class="small-cell"><?php echo isset($row['diemTX4']) && $row['diemTX4'] !== null ? number_format($row['diemTX4'], 1) : '-'; ?></td>
                                            <td class="small-cell"><?php echo isset($row['diemGiuaKy']) && $row['diemGiuaKy'] !== null ? number_format($row['diemGiuaKy'], 1) : '-'; ?></td>
                                            <td class="small-cell"><?php echo isset($row['diemCuoiKy']) && $row['diemCuoiKy'] !== null ? number_format($row['diemCuoiKy'], 1) : '-'; ?></td>

                                            <td class="small-cell"> <strong class="<?php echo $diemTB <= 5 ? 'low-avg-grade' : ''; ?>">
                                                    <?php echo number_format($diemTB, 2); ?>
                                                </strong></td>
                                            <td class="range-cell">
                                                <span class="<?php echo $classXepLoai; ?>">
                                                    <?php echo $xepLoai; ?>
                                                </span>
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