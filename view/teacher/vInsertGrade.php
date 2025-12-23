<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

// Kiểm tra quyền giáo viên
if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

// Nếu chưa có dữ liệu, include controller để xử lý
if (!isset($classes) || !isset($selectedHocKy) || !isset($selectedNamHoc)) {
    define('INCLUDED_FROM_VIEW', true);
    require_once(__DIR__ . '/../../controller/cInsertGrade.php');
    $controller = new cInsertGrade();
    $controller->showInsertGradePage();
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập Điểm Học Sinh - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            color: #333;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pagination button:hover:not(:disabled) {
            background: #5081BE;
            color: white;
            border-color: #5081BE;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination .page-info {
            padding: 8px 15px;
            color: #666;
            font-weight: 600;
        }

    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content Area -->
        <div class="content-area">
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-edit"></i> Nhập điểm học sinh</h2>
                    <p>Quản lý và nhập điểm cho học sinh</p>
                </div>
                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-filter"></i> Bộ lọc</h2>
                </div>

                <form method="GET" action="" id="filterForm">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label for="maLop">Lớp <span style="color:red">*</span></label>
                            <select name="maLop" id="maLop" class="form-control" required>
                                <option value="">-- Chọn lớp --</option>
                                <?php if (!empty($classes)): ?>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['maLop']; ?>"
                                            <?php echo ($selectedClass == $class['maLop']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['tenLop']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Chọn học kỳ -->
                        <div class="filter-group">
                            <label for="hocKy">Học kỳ<span style="color:red">*</span></label>
                            <select name="hocKy" id="hocKy" class="form-control" required>
                                <option value="1" <?php echo ($selectedHocKy == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo ($selectedHocKy == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <!-- Chọn năm học -->
                        <div class="filter-group">
                            <label for="namHoc">Năm học<span style="color:red">*</span></label>
                            <select name="namHoc" id="namHoc" class="form-control" required>
                                <?php if (!empty($years)): ?>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?php echo $year; ?>"
                                            <?php echo ($selectedNamHoc == $year) ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary" id="btnFilter">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                        </div>
                    </div>



                </form>
            </div>
            <?php if (!empty($selectedClass) && !empty($selectedSubject)): ?>
                <div class="semester-info">
                    <strong>📚 Đang nhập điểm:</strong>
                    <p>
                        <?php
                        echo htmlspecialchars($subjectName ?? '') . " - " . htmlspecialchars($className ?? '') .
                            " - Học kỳ $selectedHocKy - Năm học $selectedNamHoc";
                        ?>
                    </p>

                </div>
            <?php endif; ?>
            <?php if (!empty($students)): ?>

                <div class="card-body">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fa-solid fa-table"></i> Bảng nhập điểm</h2>
                        <button type="submit" class="btn btn-success" form="gradeForm">
                            <i class="fas fa-save"></i> Lưu điểm
                        </button>
                    </div>

                    <form method="POST" action="?action=save" id="gradeForm">
                        <input type="hidden" name="maMonHoc" value="<?php echo $selectedSubject; ?>">
                        <input type="hidden" name="maLop" value="<?php echo $selectedClass; ?>">
                        <input type="hidden" name="hocKy" value="<?php echo $selectedHocKy; ?>">
                        <input type="hidden" name="namHoc" value="<?php echo $selectedNamHoc; ?>">
                        <div class="grade-table-container">
                            <table class="common-table">
                                <thead>
                                    <tr>
                                        <th class="small-cell" rowspan="2">STT</th>
                                        <th rowspan="2">Tên học sinh</th>
                                        <th colspan="4">ĐĐG TX</th>
                                        <th rowspan="2">ĐĐG GK</th>
                                        <th rowspan="2">ĐĐG CK</th>
                                        <th rowspan="2">ĐTB Môn</th>
                                        <th rowspan="2">Nhận xét</th>
                                    </tr>
                                    <tr>
                                        <th>TX1</th>
                                        <th>TX2</th>
                                        <th>TX3</th>
                                        <th>TX4</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student): ?>
                                        <tr>
                                            <td class="small-cell"><?php echo $index + 1; ?></td>
                                            <td class="student-name"><?php echo htmlspecialchars($student['hoTen']); ?></td>
                                            <td>
                                                <input type="text" class="grade-input"
                                                    name="grades[<?php echo $student['maHS']; ?>][diemTX1]"
                                                    value="<?php echo $student['diemTX1'] !== null ? $student['diemTX1'] : ''; ?>"
                                                    onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            <td>
                                                <input type="text" class="grade-input"
                                                    name="grades[<?php echo $student['maHS']; ?>][diemTX2]"
                                                    value="<?php echo $student['diemTX2'] !== null ? $student['diemTX2'] : ''; ?>"
                                                    onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            <td>
                                                <input type="text" class="grade-input"
                                                    name="grades[<?php echo $student['maHS']; ?>][diemTX3]"
                                                    value="<?php echo $student['diemTX3'] !== null ? $student['diemTX3'] : ''; ?>"
                                                    onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            <td>
                                                <input type="text" class="grade-input"
                                                    name="grades[<?php echo $student['maHS']; ?>][diemTX4]"
                                                    value="<?php echo $student['diemTX4'] !== null ? $student['diemTX4'] : ''; ?>"
                                                    onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            <td>
                                                <input type="text" class="grade-input"
                                                    name="grades[<?php echo $student['maHS']; ?>][diemGiuaKy]"
                                                    value="<?php echo $student['diemGiuaKy'] !== null ? $student['diemGiuaKy'] : ''; ?>"
                                                    onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            <td>
                                                <input type="text" class="grade-input"
                                                    name="grades[<?php echo $student['maHS']; ?>][diemCuoiKy]"
                                                    value="<?php echo $student['diemCuoiKy'] !== null ? $student['diemCuoiKy'] : ''; ?>"
                                                    onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            <td>
                                                <?php
                                                $tb = $student['tbDiem']; // có thể null
                                                $isWarning = ($tb !== null && (float)$tb <= 5);
                                                ?>
                                                <span
                                                    class="average-display <?php echo $isWarning ? 'warning-grade' : ''; ?>"
                                                    id="avg-<?php echo $student['maHS']; ?>">
                                                    <?php echo $tb !== null ? number_format((float)$tb, 2) : '-'; ?>
                                                </span>
                                            </td>


                                            <td>
                                                <textarea class="comment-input"
                                                    name="grades[<?php echo $student['maHS']; ?>][nhanXet]"
                                                    rows="2"
                                                    placeholder="Nhập nhận xét..."><?php echo isset($student['nhanXet']) ? htmlspecialchars($student['nhanXet']) : ''; ?></textarea>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang -->
                        <div class="pagination">
                            <button type="button" id="prevBtn" onclick="changePage(-1)">
                                <i class="fas fa-chevron-left"></i> Trước
                            </button>
                            <span class="page-info">
                                Trang <span id="currentPage">1</span> / <span id="totalPages">1</span>
                            </span>
                            <button type="button" id="nextBtn" onclick="changePage(1)">
                                Sau <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>

                        <p class="notice-grade">
                            <i class="fas fa-info-circle"></i>
                            Điểm được tính với hệ số: Điểm đánh giá thường xuyên (×1), Điểm đánh giá giữa kỳ (×2), Điểm đánh giá cuối kỳ (×3)
                        </p>
                    </form>
                </div>

            <?php elseif (!empty($selectedClass) && !empty($selectedSubject)): ?>
                <div class="card">
                    <div class="no-data">
                        <i class="fas fa-users-slash"></i>
                        <p>Không có học sinh nào trong lớp này.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="no-data">
                        <i class="fas fa-hand-pointer"></i>
                        <p>Vui lòng chọn lớp để bắt đầu nhập điểm.</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

   

</body>

</html>