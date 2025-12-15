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
    define('INCLUDED_FROM_VIEW', true); // Đánh dấu được gọi từ view
    require_once(__DIR__ . '/../../controller/cInsertGrade.php');
    
    // Gọi controller để set các biến
    $controller = new cInsertGrade();
    $controller->showInsertGradePage();
}

// Lấy thông tin từ session
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
        html,
        body {
            margin: 0;
            padding: 0;
        }

        .main-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .content-area {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
            overflow-x: hidden;
            height: 100vh;
            box-sizing: border-box;
        }

        .header-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header-left-icon {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #5081BE;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header-left-icon h2 {
            margin: 0;
            font-size: 24px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .filter-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #34495e;
            font-size: 14px;
        }

        .form-control {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 2px rgba(80, 129, 190, 0.1);
        }

        .semester-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #5081BE;
        }

        .semester-info strong {
            color: #2c3e50;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }

        .button-group-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .left-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-primary {
            background: #5081BE;
            color: white;
        }

        .btn-primary:hover {
            background: #4070a8;
        }

        .grade-table-container {
            overflow-x: auto;
        }

        .grade-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .grade-table thead {
            background: #5081BE;
            color: white;
        }

        .grade-table th {
            padding: 12px 8px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            border: 1px solid #4070a8;
        }

        .grade-table td {
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .grade-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .grade-table tbody tr:hover {
            background: #e9ecef;
        }

        .grade-input {
            width: 60px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-size: 13px;
        }

        .grade-input:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 2px rgba(80, 129, 190, 0.1);
        }

        .grade-input.error {
            border-color: #e74c3c;
        }

        .comment-input {
            width: 150px;
            min-width: 150px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
        }

        .student-name {
            text-align: left;
            font-weight: 500;
            color: #2c3e50;
            padding-left: 12px;
        }

        .average-display {
            font-weight: 600;
            color: #27ae60;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .no-data i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .info-text {
            color: #7f8c8d;
            font-size: 13px;
            font-style: italic;
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

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

        @media (max-width: 768px) {
            .main-wrapper {
                flex-direction: column;
            }

            .content-area {
                margin-left: 0;
                padding: 15px;
            }

            .filter-section {
                grid-template-columns: 1fr;
            }

            .button-group-top {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <!-- Header -->
            <div class="header-section">
                <div class="header-left-icon">
                    <h2><i class="fas fa-edit"></i></h2>
                    <h2>Nhập Điểm Học Sinh</h2>
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

                <!-- Bộ lọc -->
                <form method="GET" action="" id="filterForm">
                    <div class="filter-section">
                        <!-- Chọn lớp -->
                        <div class="form-group">
                            <label for="maLop"><i class="fas fa-chalkboard"></i> Lớp <span style="color: red;">*</span></label>
                            <select name="maLop" id="maLop" class="form-control" required onchange="this.form.submit()">
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
                        <div class="form-group">
                            <label for="hocKy"><i class="fas fa-calendar-alt"></i> Học kỳ</label>
                            <select name="hocKy" id="hocKy" class="form-control" onchange="this.form.submit()">
                                <option value="1" <?php echo ($selectedHocKy == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo ($selectedHocKy == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <!-- Chọn năm học -->
                        <div class="form-group">
                            <label for="namHoc"><i class="fas fa-calendar"></i> Năm học</label>
                            <select name="namHoc" id="namHoc" class="form-control" onchange="this.form.submit()">
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
                    </div>

                    <?php if (!empty($selectedClass) && !empty($selectedSubject)): ?>
                        <div class="semester-info">
                            <strong>📚 Đang nhập điểm:</strong> 
                            <?php 
                                echo htmlspecialchars($subjectName ?? '') . " - " . htmlspecialchars($className ?? '') . 
                                     " - Học kỳ $selectedHocKy - Năm học $selectedNamHoc";
                            ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($students)): ?>
                <!-- Bảng nhập điểm -->
                <div class="card">
                    <form method="POST" action="?action=save" id="gradeForm">
                        <input type="hidden" name="maMonHoc" value="<?php echo $selectedSubject; ?>">
                        <input type="hidden" name="maLop" value="<?php echo $selectedClass; ?>">
                        <input type="hidden" name="hocKy" value="<?php echo $selectedHocKy; ?>">
                        <input type="hidden" name="namHoc" value="<?php echo $selectedNamHoc; ?>">

                        <!-- Nút hành động ở trên -->
                        <div class="button-group-top">
                            <div class="left-buttons">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Lưu điểm
                                </button>
                                <button type="button" class="btn btn-primary" onclick="window.location.href=window.location.pathname">
                                    <i class="fas fa-sync-alt"></i> Làm mới
                                </button>
                            </div>
                            <div class="page-info">
                                <span>Hiển thị <span id="currentRange"></span> / <?php echo count($students); ?> học sinh</span>
                            </div>
                        </div>

                        <div class="grade-table-container">
                            <table class="grade-table">
                                <thead>
                                    <tr>
                                        <th rowspan="2">STT</th>
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
                                            <td><?php echo $index + 1; ?></td>
                                            <td class="student-name"><?php echo htmlspecialchars($student['hoTen']); ?></td>
                                            
                                            <!-- Điểm TX -->
                                            <td>
                                                <input type="text" 
                                                       class="grade-input" 
                                                       name="grades[<?php echo $student['maHS']; ?>][diemTX1]"
                                                       value="<?php echo $student['diemTX1'] !== null ? $student['diemTX1'] : ''; ?>"
                                                       onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       class="grade-input" 
                                                       name="grades[<?php echo $student['maHS']; ?>][diemTX2]"
                                                       value="<?php echo $student['diemTX2'] !== null ? $student['diemTX2'] : ''; ?>"
                                                       onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       class="grade-input" 
                                                       name="grades[<?php echo $student['maHS']; ?>][diemTX3]"
                                                       value="<?php echo $student['diemTX3'] !== null ? $student['diemTX3'] : ''; ?>"
                                                       onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       class="grade-input" 
                                                       name="grades[<?php echo $student['maHS']; ?>][diemTX4]"
                                                       value="<?php echo $student['diemTX4'] !== null ? $student['diemTX4'] : ''; ?>"
                                                       onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            
                                            <!-- Điểm giữa kỳ -->
                                            <td>
                                                <input type="text" 
                                                       class="grade-input" 
                                                       name="grades[<?php echo $student['maHS']; ?>][diemGiuaKy]"
                                                       value="<?php echo $student['diemGiuaKy'] !== null ? $student['diemGiuaKy'] : ''; ?>"
                                                       onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            
                                            <!-- Điểm cuối kỳ -->
                                            <td>
                                                <input type="text" 
                                                       class="grade-input" 
                                                       name="grades[<?php echo $student['maHS']; ?>][diemCuoiKy]"
                                                       value="<?php echo $student['diemCuoiKy'] !== null ? $student['diemCuoiKy'] : ''; ?>"
                                                       onchange="calculateAverage(<?php echo $student['maHS']; ?>)">
                                            </td>
                                            
                                            <!-- Điểm trung bình -->
                                            <td>
                                                <span class="average-display" id="avg-<?php echo $student['maHS']; ?>">
                                                    <?php echo $student['tbDiem'] !== null ? number_format($student['tbDiem'], 2) : '-'; ?>
                                                </span>
                                            </td>
                                            
                                            <!-- Nhận xét -->
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

                        <p class="info-text">
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
    // Biến phân trang
    let currentPage = 1;
    const rowsPerPage = 10;
    let totalRows = 0;

    function initPagination() {
        const tbody = document.querySelector('.grade-table tbody');
        if (!tbody) return;
        
        const rows = tbody.querySelectorAll('tr');
        totalRows = rows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        
        document.getElementById('totalPages').textContent = totalPages;
        showPage(1);
    }

    function showPage(page) {
        const tbody = document.querySelector('.grade-table tbody');
        if (!tbody) return;
        
        const rows = tbody.querySelectorAll('tr');
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        
        if (page < 1) page = 1;
        if (page > totalPages) page = totalPages;
        
        currentPage = page;
        
        rows.forEach(row => row.style.display = 'none');
        
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        for (let i = start; i < end && i < totalRows; i++) {
            rows[i].style.display = '';
        }
        
        document.getElementById('currentPage').textContent = page;
        document.getElementById('currentRange').textContent = `${start + 1}-${Math.min(end, totalRows)}`;
        
        document.getElementById('prevBtn').disabled = (page === 1);
        document.getElementById('nextBtn').disabled = (page === totalPages);
    }

    function changePage(delta) {
        showPage(currentPage + delta);
    }

    // Lưu giá trị ban đầu để so sánh thay đổi
    const originalValues = new Map();
    
    document.addEventListener('DOMContentLoaded', function() {
        initPagination();
        
        const gradeInputs = document.querySelectorAll('.grade-input');
        const commentInputs = document.querySelectorAll('.comment-input');
        
        // Lưu giá trị ban đầu của tất cả các input
        gradeInputs.forEach(input => {
            originalValues.set(input.name, input.value);
        });
        commentInputs.forEach(input => {
            originalValues.set(input.name, input.value);
        });
        
        // Tính điểm trung bình cho tất cả học sinh khi load trang
        const studentIds = new Set();
        gradeInputs.forEach(input => {
            const match = input.name.match(/grades\[(\d+)\]/);
            if (match) {
                studentIds.add(match[1]);
            }
        });
        studentIds.forEach(maHS => {
            calculateAverage(maHS);
        });
        
        gradeInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                // Chỉ cho phép số và dấu chấm
                value = value.replace(/[^0-9.]/g, '');
                
                // Chỉ cho phép một dấu chấm
                const parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }
                
                e.target.value = value;
                
                // Chỉ đánh dấu lỗi, KHÔNG tự động thay đổi giá trị khi đang nhập
                if (value !== '' && value !== '.') {
                    const numValue = parseFloat(value);
                    if (!isNaN(numValue) && (numValue < 0 || numValue > 10)) {
                        e.target.classList.add('error');
                    } else {
                        e.target.classList.remove('error');
                    }
                }
                
                // Đánh dấu đã thay đổi
                markAsChanged(e.target);
            });
            
            input.addEventListener('blur', function(e) {
                let value = e.target.value.trim();
                
                // Bỏ qua nếu rỗng
                if (value === '' || value === '.') {
                    e.target.value = '';
                    e.target.classList.remove('error');
                    markAsChanged(e.target);
                    return;
                }
                
                const numValue = parseFloat(value);
                
                // Kiểm tra giá trị hợp lệ
                if (isNaN(numValue)) {
                    e.target.value = '';
                    e.target.classList.remove('error');
                    markAsChanged(e.target);
                    return;
                }
                
                // Giới hạn giá trị trong khoảng 0-10
                let finalValue = numValue;
                if (finalValue < 0) {
                    finalValue = 0;
                } else if (finalValue > 10) {
                    finalValue = 10;
                }
                
                // Format với 2 chữ số thập phân
                e.target.value = finalValue.toFixed(2);
                e.target.classList.remove('error');
                markAsChanged(e.target);
            });
        });
        
        // Đánh dấu thay đổi cho textarea nhận xét
        commentInputs.forEach(input => {
            // Lắng nghe input event để đánh dấu ngay khi gõ
            input.addEventListener('input', function(e) {
                markAsChanged(e.target);
            });
            // Vẫn giữ change event cho trường hợp paste hoặc autofill
            input.addEventListener('change', function(e) {
                markAsChanged(e.target);
            });
        });
    });
    
    function markAsChanged(element) {
        const currentValue = element.value;
        const originalValue = originalValues.get(element.name) || '';
        
        if (currentValue !== originalValue) {
            element.setAttribute('data-changed', 'true');
            element.style.backgroundColor = '#fff9e6'; // Highlight màu vàng nhạt
        } else {
            element.removeAttribute('data-changed');
            element.style.backgroundColor = '';
        }
    }

    function calculateAverage(maHS) {
        // Lấy giá trị và kiểm tra chính xác (không dùng ||)
        const getValue = (selector) => {
            const value = document.querySelector(selector).value.trim();
            return value === '' ? null : parseFloat(value);
        };
        
        const tx1 = getValue(`input[name="grades[${maHS}][diemTX1]"]`);
        const tx2 = getValue(`input[name="grades[${maHS}][diemTX2]"]`);
        const tx3 = getValue(`input[name="grades[${maHS}][diemTX3]"]`);
        const tx4 = getValue(`input[name="grades[${maHS}][diemTX4]"]`);
        const diemGK = getValue(`input[name="grades[${maHS}][diemGiuaKy]"]`);
        const diemCK = getValue(`input[name="grades[${maHS}][diemCuoiKy]"]`);
        
        // ĐIỀU KIỆN BẮT BUỘC: Phải có điểm GK VÀ điểm CK mới tính được ĐTB
        if (diemGK === null || diemCK === null) {
            document.getElementById(`avg-${maHS}`).textContent = '-';
            document.getElementById(`avg-${maHS}`).style.color = '#999';
            return;
        }
        
        // Tính điểm trung bình môn theo công thức:
        // Mỗi điểm TX có hệ số 1, GK hệ số 2, CK hệ số 3
        // ĐTB = (TX1×1 + TX2×1 + TX3×1 + TX4×1 + GK×2 + CK×3) / (số TX có + 2 + 3)
        let sum = 0;
        let totalCoefficient = 0;
        
        // Cộng từng điểm TX với hệ số 1
        if (tx1 !== null) {
            sum += tx1 * 1;
            totalCoefficient += 1;
        }
        if (tx2 !== null) {
            sum += tx2 * 1;
            totalCoefficient += 1;
        }
        if (tx3 !== null) {
            sum += tx3 * 1;
            totalCoefficient += 1;
        }
        if (tx4 !== null) {
            sum += tx4 * 1;
            totalCoefficient += 1;
        }
        
        // Cộng điểm GK với hệ số 2
        sum += diemGK * 2;
        totalCoefficient += 2;
        
        // Cộng điểm CK với hệ số 3
        sum += diemCK * 3;
        totalCoefficient += 3;
        
        // Tính trung bình (chia 8 hoặc 9 tùy số điểm TX)
        const average = (sum / totalCoefficient).toFixed(2);
        
        document.getElementById(`avg-${maHS}`).textContent = average;
        document.getElementById(`avg-${maHS}`).style.color = '#27ae60';
    }

    document.getElementById('gradeForm')?.addEventListener('submit', function(e) {
        const inputs = this.querySelectorAll('.grade-input');
        let hasError = false;
        
        inputs.forEach(input => {
            if (input.value !== '') {
                const value = parseFloat(input.value);
                if (isNaN(value) || value < 0 || value > 10) {
                    hasError = true;
                    input.classList.add('error');
                }
            }
        });
        
        if (hasError) {
            e.preventDefault();
            alert('Vui lòng kiểm tra lại các điểm đã nhập! Điểm phải là số từ 0 đến 10.');
            return false;
        }
        
        // Chỉ gửi dữ liệu đã thay đổi
        e.preventDefault();
        
        const allInputs = this.querySelectorAll('.grade-input, .comment-input');
        const changedData = {};
        let hasChanges = false;
        
        allInputs.forEach(input => {
            if (input.getAttribute('data-changed') === 'true') {
                const nameParts = input.name.match(/grades\[(\d+)\]\[(\w+)\]/);
                if (nameParts) {
                    const maHS = nameParts[1];
                    const field = nameParts[2];
                    
                    if (!changedData[maHS]) {
                        changedData[maHS] = {};
                    }
                    changedData[maHS][field] = input.value;
                    hasChanges = true;
                }
            }
        });
        
        if (!hasChanges) {
            alert('Không có thay đổi nào để lưu!');
            return false;
        }
        
        console.log('Dữ liệu đã thay đổi:', changedData);
        
        // Disable tất cả input KHÔNG thay đổi để không gửi lên server
        allInputs.forEach(input => {
            if (input.getAttribute('data-changed') !== 'true') {
                input.disabled = true;
            } else {
                console.log('Input đã thay đổi:', input.name, '=', input.value);
            }
        });
        
        // Submit form - chỉ gửi các input đã thay đổi (không disabled)
        this.submit();
    });
    </script>
</body>

</html>

