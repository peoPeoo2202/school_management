<?php
// File này được include từ index.php, session đã được khởi tạo
include_once("../../model/mStudent.php");

// Kiểm tra maHS từ session (đã được set bởi index.php)
if (!isset($_SESSION['maHS']) || !$_SESSION['maHS']) {
    echo "<p class='error-message'>Không có thông tin học sinh.</p>";
    exit;
}

$model = new mStudent();
$maHS = $_SESSION['maHS'];

// Lấy thông tin học sinh theo maHS
$info = $model->getStudentById($maHS);

if (!$info) {
    echo "<p class='error-message'>Không tìm thấy thông tin học sinh.</p>";
    exit;
}

// Lấy danh sách năm học
$availableYears = $model->getAvailableYears($maHS);

// Lấy năm học từ request
$namHoc = $_GET['namHoc'] ?? ($availableYears[0] ?? '2024-2025');

// Lấy dữ liệu học lực từ bảng hocluc
$hocLucData = $model->getHocLuc($maHS, $namHoc);

// Lấy dữ liệu hạnh kiểm từ bảng hanhkiem
$hanhKiemHK1 = $model->getHanhKiem($maHS, 1, $namHoc);
$hanhKiemHK2 = $model->getHanhKiem($maHS, 2, $namHoc);

// Lấy danh hiệu từ bảng danhhieu (qua cột maDanhHieu trong bảng hocsinh)
$danhHieu = $model->getDanhHieu($maHS);

// Helper function để map loại học lực sang class CSS
function getHocLucClass($loaiHocLuc) {
    if (!$loaiHocLuc) return 'no-data';
    $normalized = strtolower(trim($loaiHocLuc));
    if (strpos($normalized, 'tot') !== false || strpos($normalized, 'gioi') !== false) return 'excellent';
    if (strpos($normalized, 'kha') !== false) return 'good';
    if (strpos($normalized, 'dat') !== false || strpos($normalized, 'trung binh') !== false) return 'average';
    return 'weak';
}

// Helper function để map loại hạnh kiểm sang class CSS
function getHanhKiemClass($loaiHK) {
    if (!$loaiHK) return 'no-data';
    $normalized = strtolower(trim($loaiHK));
    if (strpos($normalized, 'tot') !== false) return 'excellent';
    if (strpos($normalized, 'kha') !== false) return 'good';
    if (strpos($normalized, 'dat') !== false) return 'average';
    return 'weak';
}

// Chuẩn bị dữ liệu xếp loại cho 3 kỳ (HK1, HK2, Cả năm)
$classifications = [];

// Học kỳ 1
$classifications[1] = [
    'diemTB' => $hocLucData && $hocLucData['diemTBHK1'] ? number_format($hocLucData['diemTBHK1'], 2) : '-',
    'hocLuc' => $hocLucData && $hocLucData['loaiHocLuc'] ? $hocLucData['loaiHocLuc'] : 'Chưa có dữ liệu',
    'hocLucClass' => getHocLucClass($hocLucData['loaiHocLuc'] ?? null),
    'hanhKiem' => $hanhKiemHK1 && $hanhKiemHK1['loaiHK'] ? $hanhKiemHK1['loaiHK'] : 'Chưa có dữ liệu',
    'hanhKiemClass' => getHanhKiemClass($hanhKiemHK1['loaiHK'] ?? null)
];

// Học kỳ 2
$classifications[2] = [
    'diemTB' => $hocLucData && $hocLucData['diemTBHK2'] ? number_format($hocLucData['diemTBHK2'], 2) : '-',
    'hocLuc' => $hocLucData && $hocLucData['loaiHocLuc'] ? $hocLucData['loaiHocLuc'] : 'Chưa có dữ liệu',
    'hocLucClass' => getHocLucClass($hocLucData['loaiHocLuc'] ?? null),
    'hanhKiem' => $hanhKiemHK2 && $hanhKiemHK2['loaiHK'] ? $hanhKiemHK2['loaiHK'] : 'Chưa có dữ liệu',
    'hanhKiemClass' => getHanhKiemClass($hanhKiemHK2['loaiHK'] ?? null)
];

// Kiểm tra điều kiện để nhận danh hiệu
// Nếu có bất kỳ học kỳ nào hạnh kiểm "Chưa đạt" thì KHÔNG được nhận danh hiệu
$hanhKiem1 = $hanhKiemHK1['loaiHK'] ?? null;
$hanhKiem2 = $hanhKiemHK2['loaiHK'] ?? null;

$coDuDieuKienDanhHieu = true;
if ($hanhKiem1 && (stripos($hanhKiem1, 'chua') !== false || stripos($hanhKiem1, 'chưa') !== false)) {
    $coDuDieuKienDanhHieu = false;
}
if ($hanhKiem2 && (stripos($hanhKiem2, 'chua') !== false || stripos($hanhKiem2, 'chưa') !== false)) {
    $coDuDieuKienDanhHieu = false;
}

// Cả năm
$classifications['canam'] = [
    'diemTB' => $hocLucData && $hocLucData['diemTBCaNam'] ? number_format($hocLucData['diemTBCaNam'], 2) : '-',
    'hocLuc' => $hocLucData && $hocLucData['loaiHocLuc'] ? $hocLucData['loaiHocLuc'] : 'Chưa có dữ liệu',
    'hocLucClass' => getHocLucClass($hocLucData['loaiHocLuc'] ?? null),
    'hanhKiem' => ($hanhKiemHK1 && $hanhKiemHK2) ? 
                  ($hanhKiemHK1['loaiHK'] == 'Tốt' && $hanhKiemHK2['loaiHK'] == 'Tốt' ? 'Tốt' : 'Khá') : 
                  'Chưa có dữ liệu',
    'hanhKiemClass' => ($hanhKiemHK1 && $hanhKiemHK2) ? 
                       getHanhKiemClass($hanhKiemHK1['loaiHK'] == 'Tốt' && $hanhKiemHK2['loaiHK'] == 'Tốt' ? 'Tốt' : 'Khá') : 
                       'no-data',
    'danhHieu' => ($danhHieu && $coDuDieuKienDanhHieu) ? $danhHieu['tenDanhHieu'] : null
];
?>
<div class="title-header">
    <i class="fas fa-star"></i>
    <h4>Xếp loại học sinh</h4>
</div>
<div class="classification-container">

    <div class="classification-header">


        <form method="GET" action="" class="filter-form">
            <input type="hidden" name="page" value="classification">

            <label>Năm học:</label>
            <select name="namHoc" id="namHoc">
                <?php foreach ($availableYears as $year): ?>
                    <option value="<?= $year ?>" <?= $year == $namHoc ? 'selected' : '' ?>>
                        <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Xem xếp loại</button>
        </form>
    </div>

    <?php if (empty($availableYears)): ?>
        <div class="no-data-message">
            <p>Chưa có dữ liệu xếp loại cho năm học <?= $namHoc ?></p>
        </div>
    <?php else: ?>
        <div class="classification-grid">
            <!-- Học kỳ 1 -->
            <div class="classification-card">
                <h3>Học kỳ 1</h3>
                <div class="classification-item">
                    <span class="classification-label">Điểm trung bình:</span>
                    <span class="classification-value"><?= $classifications[1]['diemTB'] ?></span>
                </div>
                <div class="classification-item">
                    <span class="classification-label">Học lực:</span>
                    <span class="classification-value <?= $classifications[1]['hocLucClass'] ?>">
                        <?= $classifications[1]['hocLuc'] ?>
                    </span>
                </div>
                <div class="classification-item">
                    <span class="classification-label">Hạnh kiểm:</span>
                    <span class="classification-value <?= $classifications[1]['hanhKiemClass'] ?>">
                        <?= $classifications[1]['hanhKiem'] ?>
                    </span>
                </div>
            </div>

            <!-- Học kỳ 2 -->
            <div class="classification-card">
                <h3>Học kỳ 2</h3>
                <div class="classification-item">
                    <span class="classification-label">Điểm trung bình:</span>
                    <span class="classification-value"><?= $classifications[2]['diemTB'] ?></span>
                </div>
                <div class="classification-item">
                    <span class="classification-label">Học lực:</span>
                    <span class="classification-value <?= $classifications[2]['hocLucClass'] ?>">
                        <?= $classifications[2]['hocLuc'] ?>
                    </span>
                </div>
                <div class="classification-item">
                    <span class="classification-label">Hạnh kiểm:</span>
                    <span class="classification-value <?= $classifications[2]['hanhKiemClass'] ?>">
                        <?= $classifications[2]['hanhKiem'] ?>
                    </span>
                </div>
            </div>

            <!-- Cả năm -->

        </div>
        <div class="classification-card card-full-year">
            <h3>Cả năm</h3>
            <div class="classification-item">
                <span class="classification-label">Điểm trung bình:</span>
                <span class="classification-value"><?= $classifications['canam']['diemTB'] ?></span>
            </div>
            <div class="classification-item">
                <span class="classification-label">Học lực:</span>
                <span class="classification-value <?= $classifications['canam']['hocLucClass'] ?>">
                    <?= $classifications['canam']['hocLuc'] ?>
                </span>
            </div>
            <div class="classification-item">
                <span class="classification-label">Hạnh kiểm:</span>
                <span class="classification-value <?= $classifications['canam']['hanhKiemClass'] ?>">
                    <?= $classifications['canam']['hanhKiem'] ?>
                </span>
            </div>
            <?php if ($classifications['canam']['danhHieu']): ?>
            <div class="classification-item">
                <span class="classification-label">Danh hiệu:</span>
                <span class="classification-value excellent">
                    <i class="fas fa-trophy"></i> <?= $classifications['canam']['danhHieu'] ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>