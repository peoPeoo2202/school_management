<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once("../../model/mStudent.php");

// Cho phép cả học sinh và phụ huynh truy cập
if (!isset($_SESSION["login"]) || !in_array($_SESSION["loaiTaiKhoan"], ['hocsinh', 'phuhuynh'])) {
    echo "<p class='error-message'>Bạn chưa đăng nhập.</p>";
    exit;
}

$model = new mStudent();

// Nếu là phụ huynh, sử dụng maHS từ session (đã được set ở index.php của phụ huynh)
// Nếu là học sinh, lấy thông tin từ tài khoản
if ($_SESSION["loaiTaiKhoan"] == 'phuhuynh') {
    if (!isset($_SESSION['maHS'])) {
        echo "<p class='error-message'>Không tìm thấy thông tin học sinh.</p>";
        exit;
    }
    $info = $model->getStudentInfoById($_SESSION['maHS']);
} else {
    $info = $model->getStudentInfoByAccount($_SESSION["tenDangNhap"]);
}

if (!$info) {
    echo "<p class='error-message'>Không tìm thấy thông tin học sinh.</p>";
    exit;
}

$maHS = $info['maHS'];

// Lấy danh sách năm học
$availableYears = $model->getAvailableYears($maHS);

// Lấy năm học và học kỳ từ request
$namHoc = $_GET['namHoc'] ?? ($availableYears[0] ?? '2023-2024');
$hocKy = $_GET['hocKy'] ?? 1;

// Lấy danh sách học kỳ (luôn có 1, 2, cả năm)
$semesters = $model->getAvailableSemesters($maHS, $namHoc);

// Lấy điểm theo học kỳ hoặc cả năm
if ($hocKy === 'canam') {
    $grades = $model->getYearlyGrades($maHS, $namHoc);
} else {
    $grades = $model->getDetailedGrades($maHS, $namHoc, $hocKy);
}
?>
<div class="title-header">
    <i class="fas fa-chart-line"></i>
    <h4>Kết quả học tập</h4>
</div>
<div class="container">
    <div class="grades-header">
        <form method="GET" action="" class="filter-form" id="gradesFilterForm">
            <input type="hidden" name="page" value="grades">
            <?php if (isset($_GET['childIndex'])): ?>
                <input type="hidden" name="childIndex" value="<?= htmlspecialchars($_GET['childIndex']) ?>">
            <?php endif; ?>
            <div>

            </div>
            <div>
                <label>Năm học:</label>
                <select name="namHoc" id="namHoc" onchange="document.getElementById('gradesFilterForm').submit()">
                    <?php foreach ($availableYears as $year): ?>
                        <option value="<?= $year ?>" <?= $year == $namHoc ? 'selected' : '' ?>>
                            <?= $year ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Học kỳ:</label>
                <select name="hocKy" id="hocKy" onchange="document.getElementById('gradesFilterForm').submit()">
                    <?php foreach ($semesters as $semester): ?>
                        <?php if ($semester === 'canam'): ?>
                            <option value="canam" <?= $hocKy === 'canam' ? 'selected' : '' ?>>
                                Cả năm
                            </option>
                        <?php else: ?>
                            <option value="<?= $semester ?>" <?= $hocKy == $semester ? 'selected' : '' ?>>
                                Học kỳ <?= $semester ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if (empty($grades)): ?>
        <div class="no-data">
            <i class="fas fa-inbox"></i>
            <p>Không có dữ liệu điểm cho <?= $hocKy === 'canam' ? 'cả năm' : 'học kỳ ' . $hocKy ?> năm học <?= $namHoc ?></p>
        </div>
    <?php else: ?>
        <?php if ($hocKy === 'canam'): ?>
            <!-- Bảng điểm cả năm - 3 cột: Môn học | Học kỳ 1 | Học kỳ 2 -->
            <table class="grades-table">
                <thead>
                    <tr>
                        <th>Môn học</th>
                        <th>Học kỳ 1</th>
                        <th>Học kỳ 2</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $grade): ?>
                        <tr>
                            <td><?= htmlspecialchars($grade['tenMonHoc']) ?></td>
                            <td><?= $grade['hocKy1'] !== null ? number_format($grade['hocKy1'], 2) : '-' ?></td>
                            <td><?= $grade['hocKy2'] !== null ? number_format($grade['hocKy2'], 2) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Bảng điểm theo học kỳ - Hiển thị đầy đủ các loại điểm -->
            <table class="grades-table">
                <thead>
                    <tr class="grades-table-header">
                        <th>STT</th>
                        <th>Môn học</th>
                        <th>Điểm TX1</th>
                        <th>Điểm TX2</th>
                        <th>Điểm TX3</th>
                        <th>Điểm TX4</th>
                        <th>Điểm giữa kỳ</th>
                        <th>Điểm cuối kỳ</th>
                        <th>Điểm trung bình</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $stt = 1; ?>
                    <?php foreach ($grades as $grade): ?>
                        <tr class="grades-table-body">
                            <td><?= $stt++ ?></td>
                            <td class="subject-name"><?= htmlspecialchars($grade['tenMonHoc']) ?></td>
                            <td><?= $grade['diemThuongXuyen1'] ?? '-' ?></td>
                            <td><?= $grade['diemThuongXuyen2'] ?? '-' ?></td>
                            <td><?= $grade['diemThuongXuyen3'] ?? '-' ?></td>
                            <td><?= $grade['diemThuongXuyen4'] ?? '-' ?></td>
                            <td><?= $grade['diemGiuaKy'] ?? '-' ?></td>
                            <td><?= $grade['diemCuoiKy'] ?? '-' ?></td>
                            <td><strong><?= number_format($grade['diemTB'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>