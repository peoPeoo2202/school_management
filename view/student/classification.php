<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once("../../model/mStudent.php");

if (!isset($_SESSION['login']) || $_SESSION['loaiTaiKhoan'] != 'hocsinh') {
    echo "<p class='error-message'>Bạn chưa đăng nhập.</p>";
    exit;
}

$model = new mStudent();
$info = $model->getStudentInfoByAccount($_SESSION["tenDangNhap"]);

if (!$info) {
    echo "<p class='error-message'>Không tìm thấy thông tin học sinh.</p>";
    exit;
}

$maHS = $info['maHS'];

// Lấy danh sách năm học
$availableYears = $model->getAvailableYears($maHS);

// Lấy năm học từ request
$namHoc = $_GET['namHoc'] ?? ($availableYears[0] ?? '2024-2025');

// Lấy kết nối database
$conn = $model->conn ?? (new mConnect())->mConnect();

// Đếm tổng số môn học trong khối của học sinh
function getTotalSubjects($conn, $maHS) {
    $sql = "SELECT COUNT(DISTINCT mh.maMonHoc) as totalSubjects
            FROM monhoc mh
            JOIN lophoc l ON l.maLop = (SELECT maLop FROM hocsinh WHERE maHS = ?)
            JOIN khoi k ON l.maKhoi = k.maKhoi";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $maHS);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['totalSubjects'] ?? 10;
}

// Tính điểm trung bình và học lực từ bảng bangdiem cho từng học kỳ
// CHỈ XẾP LOẠI KHI HỌC SINH CÓ ĐỦ ĐIỂM TẤT CẢ CÁC MÔN
function calculateHocLucFromBangDiem($conn, $maHS, $hocKy, $namHoc, $totalSubjects) {
    $sql = "SELECT 
                COUNT(*) as soMon,
                AVG(tbDiem) as diemTB,
                SUM(CASE WHEN tbDiem >= 8.0 THEN 1 ELSE 0 END) as monTren8,
                SUM(CASE WHEN tbDiem >= 6.5 THEN 1 ELSE 0 END) as monTren65,
                SUM(CASE WHEN tbDiem >= 5.0 THEN 1 ELSE 0 END) as monTren5,
                SUM(CASE WHEN tbDiem >= 3.5 THEN 1 ELSE 0 END) as monTren35
            FROM bangdiem 
            WHERE maHS = ? AND hocKy = ? AND namHoc = ? AND tbDiem IS NOT NULL";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $maHS, $hocKy, $namHoc);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$result || $result['soMon'] == 0) {
        return ['diemTB' => null, 'loaiHocLuc' => null];
    }
    
    // KIỂM TRA: Chỉ xếp loại khi có đủ điểm tất cả các môn
    if ($result['soMon'] < $totalSubjects) {
        return ['diemTB' => null, 'loaiHocLuc' => null];
    }
    
    $diemTB = $result['diemTB'];
    $soMon = $result['soMon'];
    
    // Xếp loại theo tiêu chí
    $loaiHocLuc = 'Chưa đạt';
    if ($result['monTren35'] == $soMon && $result['monTren65'] == $soMon && $result['monTren8'] >= 6) {
        $loaiHocLuc = 'Tốt';
    } elseif ($result['monTren35'] == $soMon && $result['monTren5'] == $soMon && $result['monTren65'] >= 6) {
        $loaiHocLuc = 'Khá';
    } elseif ($result['monTren35'] == $soMon && $result['monTren5'] >= 6) {
        $loaiHocLuc = 'Đạt';
    }
    
    return ['diemTB' => $diemTB, 'loaiHocLuc' => $loaiHocLuc];
}

$totalSubjects = getTotalSubjects($conn, $maHS);

// Tính học lực cho từng học kỳ
$hocLucHK1 = calculateHocLucFromBangDiem($conn, $maHS, 1, $namHoc, $totalSubjects);
$hocLucHK2 = calculateHocLucFromBangDiem($conn, $maHS, 2, $namHoc, $totalSubjects);

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

// Học kỳ 1 - CHỈ HIỂN THỊ HẠNH KIỂM KHI CÓ HỌC LỰC
$classifications[1] = [
    'diemTB' => $hocLucHK1['diemTB'] ? number_format($hocLucHK1['diemTB'], 2) : '-',
    'hocLuc' => $hocLucHK1['loaiHocLuc'] ?? 'Chưa có dữ liệu',
    'hocLucClass' => getHocLucClass($hocLucHK1['loaiHocLuc'] ?? null),
    'hanhKiem' => ($hocLucHK1['loaiHocLuc'] && $hanhKiemHK1 && $hanhKiemHK1['loaiHK']) ? $hanhKiemHK1['loaiHK'] : 'Chưa có dữ liệu',
    'hanhKiemClass' => ($hocLucHK1['loaiHocLuc'] && $hanhKiemHK1) ? getHanhKiemClass($hanhKiemHK1['loaiHK'] ?? null) : 'no-data'
];

// Học kỳ 2 - CHỈ HIỂN THỊ HẠNH KIỂM KHI CÓ HỌC LỰC
$classifications[2] = [
    'diemTB' => $hocLucHK2['diemTB'] ? number_format($hocLucHK2['diemTB'], 2) : '-',
    'hocLuc' => $hocLucHK2['loaiHocLuc'] ?? 'Chưa có dữ liệu',
    'hocLucClass' => getHocLucClass($hocLucHK2['loaiHocLuc'] ?? null),
    'hanhKiem' => ($hocLucHK2['loaiHocLuc'] && $hanhKiemHK2 && $hanhKiemHK2['loaiHK']) ? $hanhKiemHK2['loaiHK'] : 'Chưa có dữ liệu',
    'hanhKiemClass' => ($hocLucHK2['loaiHocLuc'] && $hanhKiemHK2) ? getHanhKiemClass($hanhKiemHK2['loaiHK'] ?? null) : 'no-data'
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

// Cả năm - Tính trung bình cả 2 học kỳ
$diemTBCaNam = null;
$loaiHocLucCaNam = null;
if ($hocLucHK1['diemTB'] && $hocLucHK2['diemTB']) {
    $diemTBCaNam = ($hocLucHK1['diemTB'] + $hocLucHK2['diemTB']) / 2;
    
    // Xếp loại cả năm dựa trên điểm TB cả năm và xếp loại từng kỳ
    if ($diemTBCaNam >= 8.0 && ($hocLucHK1['loaiHocLuc'] == 'Tốt' || $hocLucHK2['loaiHocLuc'] == 'Tốt')) {
        $loaiHocLucCaNam = 'Tốt';
    } elseif ($diemTBCaNam >= 6.5 && ($hocLucHK1['loaiHocLuc'] == 'Khá' || $hocLucHK2['loaiHocLuc'] == 'Khá')) {
        $loaiHocLucCaNam = 'Khá';
    } elseif ($diemTBCaNam >= 5.0) {
        $loaiHocLucCaNam = 'Đạt';
    } else {
        $loaiHocLucCaNam = 'Chưa đạt';
    }
} elseif ($hocLucHK1['diemTB']) {
    $diemTBCaNam = $hocLucHK1['diemTB'];
    $loaiHocLucCaNam = $hocLucHK1['loaiHocLuc'];
} elseif ($hocLucHK2['diemTB']) {
    $diemTBCaNam = $hocLucHK2['diemTB'];
    $loaiHocLucCaNam = $hocLucHK2['loaiHocLuc'];
}

// CẢ NĂM - CHỈ TÍNH KHI CẢ 2 HỌC KỲ ĐỀU CÓ HỌC LỰC
$hanhKiemCaNam = 'Chưa có dữ liệu';
$hanhKiemCaNamClass = 'no-data';
if ($loaiHocLucCaNam && $loaiHocLucCaNam !== 'Chưa có dữ liệu' && $hanhKiemHK1 && $hanhKiemHK2) {
    $hanhKiemCaNam = ($hanhKiemHK1['loaiHK'] == 'Tốt' && $hanhKiemHK2['loaiHK'] == 'Tốt') ? 'Tốt' : 'Khá';
    $hanhKiemCaNamClass = getHanhKiemClass($hanhKiemCaNam);
}

$classifications['canam'] = [
    'diemTB' => $diemTBCaNam ? number_format($diemTBCaNam, 2) : '-',
    'hocLuc' => $loaiHocLucCaNam ?? 'Chưa có dữ liệu',
    'hocLucClass' => getHocLucClass($loaiHocLucCaNam),
    'hanhKiem' => $hanhKiemCaNam,
    'hanhKiemClass' => $hanhKiemCaNamClass,
    'danhHieu' => ($loaiHocLucCaNam && $loaiHocLucCaNam !== 'Chưa có dữ liệu' && $danhHieu && $coDuDieuKienDanhHieu) ? $danhHieu['tenDanhHieu'] : null
];
?>
<div class="title-header">
    <i class="fas fa-star"></i>
    <h4>Xếp loại học sinh</h4>
</div>
<div class="container">

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

        </form>
    </div>

    <?php if (empty($availableYears)): ?>
        <div class="no-data-message">
            <p>Chưa có dữ liệu xếp loại cho năm học <?= $namHoc ?></p>
        </div>
    <?php else: ?>
        <div class="common-grid classification-grid">
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