<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once("../../model/mStudent.php");

if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "hocsinh") {
    echo "<p style='color:red;'>Bạn chưa đăng nhập.</p>";
    exit;
}

$model = new mStudent();
$info = $model->getStudentInfoByAccount($_SESSION["tenDangNhap"]);

if (!$info) {
    echo "<p style='color: red;'>Không tìm thấy thông tin học sinh.</p>";
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

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xếp loại học sinh</title>
    <style>
        .classification-container {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .classification-header {
            margin-bottom: 25px;
        }
        
        .classification-header h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .filter-form label {
            font-weight: 500;
            color: #555;
        }
        
        .filter-form select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            min-width: 150px;
        }
        
        .filter-form button {
            padding: 8px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .filter-form button:hover {
            background: #0056b3;
        }
        
        .classification-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .classification-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4d5ef7;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        
        .classification-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        
        .classification-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .classification-item:last-child {
            border-bottom: none;
        }
        
        .classification-label {
            font-weight: 500;
            color: #666;
        }
        
        .classification-value {
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 4px;
        }
        
        .classification-value.excellent {
            background: #d4edda;
            color: #155724;
        }
        
        .classification-value.good {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .classification-value.average {
            background: #fff3cd;
            color: #856404;
        }
        
        .classification-value.weak {
            background: #f8d7da;
            color: #721c24;
        }
        
        .classification-value.no-data {
            background: #e2e3e5;
            color: #6c757d;
        }
        
        .no-data-message {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 16px;
        }
    </style>
</head>
<body>
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
                <div class="classification-card" style="border-left-color: #28a745;">
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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>
