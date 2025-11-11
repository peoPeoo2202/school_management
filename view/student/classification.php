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

// Lấy xếp loại cả năm (học kỳ 1, 2 và cả năm)
$classifications = [];
foreach ([1, 2, 'canam'] as $hocKy) {
    $grades = [];
    if ($hocKy === 'canam') {
        // Tính điểm trung bình cả năm
        $yearlyGrades = $model->getYearlyGrades($maHS, $namHoc);
        if (!empty($yearlyGrades)) {
            $totalScore = 0;
            $count = 0;
            foreach ($yearlyGrades as $grade) {
                if ($grade['hocKy1'] !== null && $grade['hocKy2'] !== null) {
                    $totalScore += ($grade['hocKy1'] + $grade['hocKy2']) / 2;
                    $count++;
                } elseif ($grade['hocKy1'] !== null) {
                    $totalScore += $grade['hocKy1'];
                    $count++;
                } elseif ($grade['hocKy2'] !== null) {
                    $totalScore += $grade['hocKy2'];
                    $count++;
                }
            }
            $avgScore = $count > 0 ? $totalScore / $count : 0;
        } else {
            $avgScore = 0;
        }
    } else {
        // Tính điểm trung bình học kỳ
        $grades = $model->getDetailedGrades($maHS, $namHoc, $hocKy);
        if (!empty($grades)) {
            $totalScore = array_sum(array_column($grades, 'diemTB'));
            $avgScore = $totalScore / count($grades);
        } else {
            $avgScore = 0;
        }
    }
    
    // Xếp loại học lực
    if ($avgScore >= 8.0) {
        $hocLuc = 'Giỏi';
        $hocLucClass = 'excellent';
    } elseif ($avgScore >= 6.5) {
        $hocLuc = 'Khá';
        $hocLucClass = 'good';
    } elseif ($avgScore >= 5.0) {
        $hocLuc = 'Trung bình';
        $hocLucClass = 'average';
    } elseif ($avgScore > 0) {
        $hocLuc = 'Yếu';
        $hocLucClass = 'weak';
    } else {
        $hocLuc = 'Chưa có dữ liệu';
        $hocLucClass = 'no-data';
    }
    
    // Hạnh kiểm (giả định - có thể lấy từ database)
    $hanhKiem = 'Tốt';
    $hanhKiemClass = 'good';
    
    $classifications[$hocKy] = [
        'diemTB' => $avgScore > 0 ? number_format($avgScore, 2) : '-',
        'hocLuc' => $hocLuc,
        'hocLucClass' => $hocLucClass,
        'hanhKiem' => $hanhKiem,
        'hanhKiemClass' => $hanhKiemClass
    ];
}
?>

<div class="classification-container">
    <div class="classification-header">
        <h2>Xếp loại học sinh</h2>
        
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
            </div>
        </div>
        <?php endif; ?>
    </div>
