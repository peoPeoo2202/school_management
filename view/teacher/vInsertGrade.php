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
                        <div id="paginationContainer"></div>

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

    <script>
        const studentsPerPage = 10;
        let currentPage = 1;
        let allRows = [];
        let totalRows = 0;

        // Hàm kiểm tra và chỉ cho phép nhập số
        function validateGradeInput(event) {
            const input = event.target;
            let value = input.value;
            
            // Chỉ cho phép số và dấu chấm
            value = value.replace(/[^0-9.]/g, '');
            
            // Chỉ cho phép một dấu chấm duy nhất
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            
            // Giới hạn 2 chữ số sau dấu chấm
            if (parts.length === 2 && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].substring(0, 2);
            }
            
            // Giới hạn giá trị từ 0 đến 10
            const numValue = parseFloat(value);
            if (!isNaN(numValue) && numValue > 10) {
                value = '10';
            }
            
            input.value = value;
        }

        // Hàm ngăn chặn nhập ký tự không hợp lệ
        function preventInvalidChar(event) {
            const key = event.key;
            const value = event.target.value;
            
            // Cho phép các phím điều khiển
            if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(key)) {
                return true;
            }
            
            // Chỉ cho phép số và dấu chấm
            if (!/^[0-9.]$/.test(key)) {
                event.preventDefault();
                return false;
            }
            
            // Không cho phép nhiều hơn 1 dấu chấm
            if (key === '.' && value.includes('.')) {
                event.preventDefault();
                return false;
            }
            
            return true;
        }

        // Áp dụng validation cho tất cả các ô nhập điểm
        function initGradeInputValidation() {
            const gradeInputs = document.querySelectorAll('.grade-input');
            gradeInputs.forEach(input => {
                input.addEventListener('input', validateGradeInput);
                input.addEventListener('keydown', preventInvalidChar);
                
                // Thêm thuộc tính để hiển thị bàn phím số trên mobile
                input.setAttribute('inputmode', 'decimal');
            });
        }

        function initPagination() {
            const tbody = document.querySelector('.common-table tbody');
            if (!tbody) return;

            allRows = Array.from(tbody.querySelectorAll('tr'));
            totalRows = allRows.length;

            if (totalRows <= studentsPerPage) {
                document.getElementById('paginationContainer').innerHTML = '';
                return;
            }

            renderPagination();
            showPage(1);
        }

        function renderPagination() {
            const totalPages = Math.ceil(totalRows / studentsPerPage);
            const container = document.getElementById('paginationContainer');

            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '<div class="pagination">';

            // Previous Button
            if (currentPage > 1) {
                html += `<a href="javascript:showPage(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i> Trước
                </a>`;
            } else {
                html += `<span class="disabled"><i class="fas fa-chevron-left"></i> Trước</span>`;
            }

            // Page Numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                html += `<a href="javascript:showPage(1)">1</a>`;
                if (startPage > 2) {
                    html += `<span>...</span>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    html += `<span class="current-page">${i}</span>`;
                } else {
                    html += `<a href="javascript:showPage(${i})">${i}</a>`;
                }
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `<span>...</span>`;
                }
                html += `<a href="javascript:showPage(${totalPages})">${totalPages}</a>`;
            }

            // Next Button
            if (currentPage < totalPages) {
                html += `<a href="javascript:showPage(${currentPage + 1})">
                    Sau <i class="fas fa-chevron-right"></i>
                </a>`;
            } else {
                html += `<span class="disabled">Sau <i class="fas fa-chevron-right"></i></span>`;
            }

            html += '</div>';

            // Pagination Info
            const offset = (currentPage - 1) * studentsPerPage;
            html += `<div class="pagination-info">
                Hiển thị ${offset + 1} - ${Math.min(offset + studentsPerPage, totalRows)} 
                trong tổng số ${totalRows} học sinh
            </div>`;

            container.innerHTML = html;
        }

        function showPage(page) {
            const totalPages = Math.ceil(totalRows / studentsPerPage);

            if (page < 1) page = 1;
            if (page > totalPages) page = totalPages;

            currentPage = page;

            // Hide all rows
            allRows.forEach(row => {
                row.style.display = 'none';
            });

            // Show rows for current page
            const start = (page - 1) * studentsPerPage;
            const end = start + studentsPerPage;

            for (let i = start; i < end && i < totalRows; i++) {
                allRows[i].style.display = '';
            }

            // Update pagination
            renderPagination();

            // Scroll to table
            document.querySelector('.grade-table-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Initialize pagination when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initPagination();
            initGradeInputValidation();
        });
    </script>

</body>

</html>