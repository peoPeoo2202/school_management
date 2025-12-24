<?php
/**
 * View: vSubjectTeacherAssignment.php
 * Phân công Giáo viên Bộ môn
 */

// Kiểm tra xem được gọi từ controller hay không
if (!isset($grades) || !isset($subjects)) {
    // Nếu gọi trực tiếp, redirect đến controller
    header("Location: ../controller/cTeachingAssignment.php?action=subjectTeacherAssignment");
    exit();
}

// Kiểm tra đăng nhập và quyền
if (!isset($_SESSION['login']) || $_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    header("Location: ../public/index.php");
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';

// Lấy thông báo nếu có
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Đảm bảo các biến tồn tại
$schoolYears = $schoolYears ?? [];
$filters = $filters ?? [];
$assignments = $assignments ?? [];
$classes = $classes ?? [];
$grades = $grades ?? [];
$subjects = $subjects ?? [];
$teachers = $teachers ?? [];

// Sử dụng năm học từ controller (đã lấy từ DB)
$currentNamHoc = $filters['namHoc'] ?? $namHoc ?? '2024-2025';

// Kiểm tra đã chọn đủ bộ lọc chưa (ít nhất phải chọn lớp hoặc môn học)
$hasFilters = !empty($filters['maLop']) || !empty($filters['maMonHoc']);
$isSearching = isset($_GET['search']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công Giáo viên Bộ môn - BGH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../student/style.css"></style>
    <style>

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #f5576c;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #f5576c;
            color: white;
        }

        .btn-primary:hover {
            background: #e04556;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .breadcrumb {
            background: white;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .breadcrumb a {
            color: #f5576c;
            text-decoration: none;
        }

        .breadcrumb span {
            color: #666;
            margin: 0 10px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .card-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group select, .form-group input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #fafafa;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-primary {
            background: #e3f2fd;
            color: #1565c0;
        }

        .badge-warning {
            background: #fff3e0;
            color: #ef6c00;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateBGH.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
        <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-book-reader"></i>
                Phân công Giáo viên Bộ môn
            </h1>
            <div>
                <a href="../view/bgh/vTeachingAssignment.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="../view/bgh/vBGHDashboard.php"><i class="fas fa-home"></i> Trang chủ</a>
            <span>›</span>
            <a href="../view/bgh/vTeachingAssignment.php">Phân công Giảng dạy</a>
            <span>›</span>
            <strong>Phân công GVBM</strong>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Filter Card -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-search"></i> Tìm kiếm & Lọc</h3>
                <button class="btn btn-success" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Thêm phân công
                </button>
            </div>
            <div class="card-body">
                <form method="GET" action="../controller/cTeachingAssignment.php">
                    <input type="hidden" name="action" value="subjectTeacherAssignment">
                    <input type="hidden" name="search" value="1">
                    
                    <div class="filter-form">
                        <div class="form-group">
                            <label for="namHoc">Năm học</label>
                            <select name="namHoc" id="namHoc" onchange="this.form.submit()">
                                <?php if (!empty($schoolYears)): ?>
                                    <?php foreach ($schoolYears as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo ($filters['namHoc'] ?? '') === $year ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="<?php echo $currentNamHoc; ?>" selected><?php echo $currentNamHoc; ?></option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="maKhoi">Khối</label>
                            <select name="maKhoi" id="maKhoi" onchange="loadClasses()">
                                <option value="">-- Tất cả --</option>
                                <?php foreach ($grades as $grade): ?>
                                <option value="<?php echo $grade['maKhoi']; ?>" <?php echo ($filters['maKhoi'] ?? '') == $grade['maKhoi'] ? 'selected' : ''; ?>>
                                    Khối <?php echo $grade['khoiLop']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="maLop">Lớp</label>
                            <select name="maLop" id="maLop">
                                <option value="">-- Tất cả --</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['maLop']; ?>" <?php echo ($filters['maLop'] ?? '') == $class['maLop'] ? 'selected' : ''; ?>>
                                    <?php echo $class['tenLop']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="maMonHoc">Môn học</label>
                            <select name="maMonHoc" id="maMonHoc">
                                <option value="">-- Tất cả --</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['maMonHoc']; ?>" <?php echo ($filters['maMonHoc'] ?? '') == $subject['maMonHoc'] ? 'selected' : ''; ?>>
                                    <?php echo $subject['tenMonHoc']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="hocKy">Học kỳ</label>
                            <select name="hocKy" id="hocKy">
                                <option value="">-- Tất cả --</option>
                                <option value="1" <?php echo ($filters['hocKy'] ?? '') == '1' ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo ($filters['hocKy'] ?? '') == '2' ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Card -->
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3><i class="fas fa-list"></i> Kết quả phân công</h3>
                <?php if ($isSearching && $hasFilters): ?>
                <span class="badge" style="background: white; color: #667eea;">
                    <?php echo count($assignments); ?> phân công
                </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$isSearching || !$hasFilters): ?>
                <div class="empty-state">
                    <i class="fas fa-filter"></i>
                    <p>Vui lòng chọn bộ lọc để tìm kiếm phân công.</p>
                    <p><small>Chọn <strong>Lớp</strong> hoặc <strong>Môn học</strong> rồi nhấn "Tìm kiếm".</small></p>
                </div>
                <?php elseif (empty($assignments)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Không tìm thấy phân công GVBM theo bộ lọc đã chọn.</p>
                    <p><small>Nhấn "Thêm phân công" để bắt đầu.</small></p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Học kỳ</th>
                            <th>Lớp</th>
                            <th>Môn học</th>
                            <th>Giáo viên</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td>
                                <span class="badge badge-primary">HK<?php echo $assignment['hocKy']; ?></span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($assignment['tenLop']); ?></strong></td>
                            <td><?php echo htmlspecialchars($assignment['tenMonHoc']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['tenGV']); ?></td>
                            <td>
                                <?php if ($assignment['trangThai'] === 'active'): ?>
                                <span class="badge badge-success">Đang dạy</span>
                                <?php elseif ($assignment['trangThai'] === 'completed'): ?>
                                <span class="badge badge-primary">Hoàn thành</span>
                                <?php else: ?>
                                <span class="badge badge-warning"><?php echo $assignment['trangThai']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="../controller/cTeachingAssignment.php?action=editSubjectTeacher&id=<?php echo $assignment['maPhanCong']; ?>&namHoc=<?php echo urlencode($filters['namHoc'] ?? ''); ?>&maKhoi=<?php echo $filters['maKhoi'] ?? ''; ?>&maLop=<?php echo $filters['maLop'] ?? ''; ?>&maMonHoc=<?php echo $filters['maMonHoc'] ?? ''; ?>&hocKy=<?php echo $filters['hocKy'] ?? ''; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $assignment['maPhanCong']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal thêm phân công -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Thêm phân công GVBM</h3>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form action="../controller/cTeachingAssignment.php?action=assignSubjectTeacher" method="POST">
                    <input type="hidden" name="namHoc" value="<?php echo htmlspecialchars($currentNamHoc); ?>">
                    <input type="hidden" name="maKhoi" id="modalMaKhoi">
                    
                    <div class="form-group">
                        <label for="modalKhoi">Khối *</label>
                        <select name="khoi" id="modalKhoi" required onchange="loadModalClasses()">
                            <option value="">-- Chọn khối --</option>
                            <?php foreach ($grades as $grade): ?>
                            <option value="<?php echo $grade['maKhoi']; ?>">Khối <?php echo $grade['khoiLop']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modalLop">Lớp *</label>
                        <select name="maLop" id="modalLop" required onchange="loadUnassignedSubjects()">
                            <option value="">-- Chọn khối trước --</option>
                        </select>
                    </div>

                    <!-- Thông tin GVCN -->
                    <div id="gvcnInfo" class="alert alert-info" style="display: none; background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                        <i class="fas fa-info-circle"></i> <span id="gvcnText"></span>
                    </div>

                    <div class="form-group">
                        <label for="modalHocKy">Học kỳ *</label>
                        <select name="hocKy" id="modalHocKy" required onchange="loadUnassignedSubjects()">
                            <option value="1">Học kỳ 1</option>
                            <option value="2">Học kỳ 2</option>
                        </select>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="apDungHaiKy" id="apDungHaiKy">
                        <label for="apDungHaiKy">Áp dụng cho cả 2 học kỳ</label>
                    </div>

                    <div class="form-group">
                        <label for="modalMonHoc">Môn học *</label>
                        <select name="maMonHoc" id="modalMonHoc" required onchange="loadTeachersBySubject()">
                            <option value="">-- Chọn lớp trước --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modalGV">Giáo viên *</label>
                        <select name="maGV" id="modalGV" required>
                            <option value="">-- Chọn môn học trước --</option>
                        </select>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Hủy</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Lưu phân công
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Biến lưu thông tin GVCN
        let currentGVCN = null;

        // Load classes when grade changes (search form)
        function loadClasses() {
            const maKhoi = document.getElementById('maKhoi').value;
            const namHoc = document.getElementById('namHoc').value;
            const lopSelect = document.getElementById('maLop');
            
            lopSelect.innerHTML = '<option value="">-- Tất cả --</option>';
            
            if (maKhoi) {
                fetch(`../controller/cTeachingAssignment.php?action=getClassesByGrade&maKhoi=${maKhoi}&namHoc=${encodeURIComponent(namHoc)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            data.data.forEach(cls => {
                                const option = document.createElement('option');
                                option.value = cls.maLop;
                                option.textContent = cls.tenLop;
                                lopSelect.appendChild(option);
                            });
                        }
                    });
            }
        }

        // Load classes in modal
        function loadModalClasses() {
            const maKhoi = document.getElementById('modalKhoi').value;
            const namHoc = '<?php echo $currentNamHoc; ?>';
            const lopSelect = document.getElementById('modalLop');
            
            document.getElementById('modalMaKhoi').value = maKhoi;
            lopSelect.innerHTML = '<option value="">-- Chọn lớp --</option>';
            
            // Reset các dropdown phía dưới
            document.getElementById('modalMonHoc').innerHTML = '<option value="">-- Chọn lớp trước --</option>';
            document.getElementById('modalGV').innerHTML = '<option value="">-- Chọn môn học trước --</option>';
            document.getElementById('gvcnInfo').style.display = 'none';
            currentGVCN = null;
            
            if (maKhoi) {
                fetch(`../controller/cTeachingAssignment.php?action=getClassesByGrade&maKhoi=${maKhoi}&namHoc=${encodeURIComponent(namHoc)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            data.data.forEach(cls => {
                                const option = document.createElement('option');
                                option.value = cls.maLop;
                                option.textContent = cls.tenLop;
                                lopSelect.appendChild(option);
                            });
                        }
                    });
            }
        }

        // Load môn học chưa được phân công cho lớp
        function loadUnassignedSubjects() {
            const maLop = document.getElementById('modalLop').value;
            const namHoc = '<?php echo $currentNamHoc; ?>';
            const hocKy = document.getElementById('modalHocKy').value;
            const monHocSelect = document.getElementById('modalMonHoc');
            const gvSelect = document.getElementById('modalGV');
            const gvcnInfoDiv = document.getElementById('gvcnInfo');
            
            monHocSelect.innerHTML = '<option value="">-- Đang tải... --</option>';
            gvSelect.innerHTML = '<option value="">-- Chọn môn học trước --</option>';
            gvcnInfoDiv.style.display = 'none';
            currentGVCN = null;
            
            if (maLop) {
                fetch(`../controller/cTeachingAssignment.php?action=getUnassignedSubjects&maLop=${maLop}&namHoc=${encodeURIComponent(namHoc)}&hocKy=${hocKy}`)
                    .then(response => response.json())
                    .then(data => {
                        monHocSelect.innerHTML = '<option value="">-- Chọn môn học --</option>';
                        if (data.success && data.data.length > 0) {
                            data.data.forEach(subject => {
                                const option = document.createElement('option');
                                option.value = subject.maMonHoc;
                                option.textContent = subject.tenMonHoc;
                                // Đánh dấu môn của GVCN nếu có
                                if (data.gvcn && data.gvcn.maMonHoc == subject.maMonHoc) {
                                    option.textContent += ' ⭐ (môn của GVCN)';
                                    option.style.fontWeight = 'bold';
                                }
                                monHocSelect.appendChild(option);
                            });
                            
                            // Hiển thị thông tin GVCN nếu có
                            if (data.gvcn && data.gvcn.hoTen) {
                                currentGVCN = data.gvcn;
                                let gvcnText = `GVCN: <strong>${data.gvcn.hoTen}</strong>`;
                                if (data.gvcn.tenMonHoc) {
                                    gvcnText += ` - dạy môn <strong>${data.gvcn.tenMonHoc}</strong>`;
                                }
                                document.getElementById('gvcnText').innerHTML = gvcnText;
                                gvcnInfoDiv.style.display = 'block';
                            }
                        } else {
                            monHocSelect.innerHTML = '<option value="">-- Tất cả môn đã được phân công --</option>';
                        }
                    })
                    .catch(error => {
                        monHocSelect.innerHTML = '<option value="">-- Lỗi tải dữ liệu --</option>';
                    });
            } else {
                monHocSelect.innerHTML = '<option value="">-- Chọn lớp trước --</option>';
            }
        }

        // Load teachers by subject in modal
        function loadTeachersBySubject() {
            const maMonHoc = document.getElementById('modalMonHoc').value;
            const gvSelect = document.getElementById('modalGV');
            
            gvSelect.innerHTML = '<option value="">-- Đang tải... --</option>';
            
            if (maMonHoc) {
                fetch(`../controller/cTeachingAssignment.php?action=getTeachersBySubject&maMonHoc=${maMonHoc}`)
                    .then(response => response.json())
                    .then(data => {
                        gvSelect.innerHTML = '<option value="">-- Chọn giáo viên --</option>';
                        if (data.success && data.data.length > 0) {
                            data.data.forEach(teacher => {
                                const option = document.createElement('option');
                                option.value = teacher.maGV;
                                option.textContent = `${teacher.hoTen} (${teacher.toBoMon})`;
                                
                                // Nếu là GVCN của lớp và đang chọn môn của GVCN, đề xuất
                                if (currentGVCN && currentGVCN.maGV == teacher.maGV && currentGVCN.maMonHoc == maMonHoc) {
                                    option.textContent += ' ⭐ GVCN';
                                    option.style.fontWeight = 'bold';
                                    option.selected = true; // Tự động chọn GVCN
                                }
                                gvSelect.appendChild(option);
                            });
                        } else {
                            gvSelect.innerHTML = '<option value="">-- Không có giáo viên dạy môn này --</option>';
                        }
                    })
                    .catch(error => {
                        gvSelect.innerHTML = '<option value="">-- Lỗi tải dữ liệu --</option>';
                    });
            } else {
                gvSelect.innerHTML = '<option value="">-- Chọn môn học trước --</option>';
            }
        }

        // Open add modal
        function openAddModal() {
            // Reset form
            document.getElementById('modalKhoi').value = '';
            document.getElementById('modalLop').innerHTML = '<option value="">-- Chọn khối trước --</option>';
            document.getElementById('modalMonHoc').innerHTML = '<option value="">-- Chọn lớp trước --</option>';
            document.getElementById('modalGV').innerHTML = '<option value="">-- Chọn môn học trước --</option>';
            document.getElementById('gvcnInfo').style.display = 'none';
            document.getElementById('modalHocKy').value = '1';
            document.getElementById('apDungHaiKy').checked = false;
            currentGVCN = null;
            
            document.getElementById('addModal').classList.add('active');
        }

        // Close add modal
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        // Confirm delete
        function confirmDelete(maPhanCong) {
            if (confirm('Bạn có chắc chắn muốn xóa phân công này?')) {
                const namHoc = '<?php echo urlencode($filters['namHoc'] ?? ''); ?>';
                const maKhoi = '<?php echo $filters['maKhoi'] ?? ''; ?>';
                const maLop = '<?php echo $filters['maLop'] ?? ''; ?>';
                const maMonHoc = '<?php echo $filters['maMonHoc'] ?? ''; ?>';
                const hocKy = '<?php echo $filters['hocKy'] ?? ''; ?>';
                
                let url = `../controller/cTeachingAssignment.php?action=deleteSubjectTeacher&id=${maPhanCong}&confirm=1`;
                if (namHoc) url += `&namHoc=${namHoc}`;
                if (maKhoi) url += `&maKhoi=${maKhoi}`;
                if (maLop) url += `&maLop=${maLop}`;
                if (maMonHoc) url += `&maMonHoc=${maMonHoc}`;
                if (hocKy) url += `&hocKy=${hocKy}`;
                
                window.location.href = url;
            }
        }

        // Close modal on outside click
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
    </script>
        </div>
    </div>
</div>
</body>
</html>
