<?php
/**
 * View: vStudentAssignment.php
 * Phân công lớp cho học sinh đầu cấp
 */

// Kiểm tra xem được gọi từ controller hay không
if (!isset($studentsWithoutClass) || !isset($firstYearClasses)) {
    header("Location: ../controller/cTeachingAssignment.php?action=studentAssignment");
    exit();
}

// Chỉ start session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công Lớp cho Học sinh - BGH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../teacher/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding: 20px;
        }

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
            color: #1e3c72;
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
            background: #1e3c72;
            color: white;
        }

        .btn-primary:hover {
            background: #2a5298;
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

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
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
            position: sticky;
            top: 0;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .checkbox-cell {
            width: 40px;
            text-align: center;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary {
            background: #e3f2fd;
            color: #1565c0;
        }

        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
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

        .action-bar {
            background: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .selected-count {
            color: #666;
            font-size: 14px;
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
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .form-check label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .breadcrumb {
            background: white;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .breadcrumb a {
            color: #1e3c72;
            text-decoration: none;
        }

        .breadcrumb span {
            color: #666;
            margin: 0 10px;
        }

        .class-card {
            border: 2px solid #eee;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .class-card:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .class-card.selected {
            border-color: #28a745;
            background: #e8f5e9;
        }

        .class-card h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .class-card p {
            color: #666;
            font-size: 13px;
        }

        @media (max-width: 1200px) {
            .main-content {
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
                <i class="fas fa-user-graduate"></i>
                Phân công Lớp cho Học sinh Đầu cấp
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
            <strong>Phân công Lớp cho Học sinh</strong>
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

        <!-- Main Content -->
        <div class="main-content">
            <!-- Danh sách học sinh chưa có lớp -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Học sinh chưa có lớp</h3>
                    <button class="btn btn-warning" onclick="openAutoAssignModal()">
                        <i class="fas fa-magic"></i> Phân lớp tự động
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($studentsWithoutClass)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>Tất cả học sinh đã được phân lớp!</p>
                    </div>
                    <?php else: ?>
                    <form id="assignForm" action="../controller/cTeachingAssignment.php?action=assignStudents" method="POST">
                        <input type="hidden" name="namHoc" value="<?php echo htmlspecialchars($namHoc); ?>">
                        <input type="hidden" name="maLop" id="selectedClassId">
                        <table>
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                                    </th>
                                    <th>Mã HS</th>
                                    <th>Họ tên</th>
                                    <th>Ngày sinh</th>
                                    <th>Giới tính</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($studentsWithoutClass as $student): ?>
                                <tr>
                                    <td class="checkbox-cell">
                                        <input type="checkbox" name="studentIds[]" value="<?php echo $student['maHS']; ?>" class="student-checkbox">
                                    </td>
                                    <td><?php echo htmlspecialchars($student['maHS']); ?></td>
                                    <td><?php echo htmlspecialchars($student['hoTen']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($student['ngaySinh'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $student['gioiTinh'] === 'Nam' ? 'badge-primary' : 'badge-success'; ?>">
                                            <?php echo $student['gioiTinh'] === 'Nam' ? 'Nam' : 'Nữ'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                    <?php endif; ?>
                </div>
                <?php if (!empty($studentsWithoutClass)): ?>
                <div class="action-bar">
                    <span class="selected-count">Đã chọn: <strong id="selectedCount">0</strong> học sinh</span>
                    <button type="button" class="btn btn-success" onclick="openClassSelectModal()">
                        <i class="fas fa-arrow-right"></i> Chuyển tới lớp
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Danh sách lớp đầu cấp -->
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <h3><i class="fas fa-school"></i> Danh sách Lớp đầu cấp (Khối 6)</h3>
                    <span class="badge" style="background: white; color: #11998e;">
                        <?php echo count($firstYearClasses); ?> lớp
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($firstYearClasses)): ?>
                    <div class="empty-state">
                        <i class="fas fa-info-circle"></i>
                        <p>Chưa có lớp đầu cấp nào!</p>
                    </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã lớp</th>
                                <th>Tên lớp</th>
                                <th>Sĩ số</th>
                                <th>Phòng học</th>
                                <th>Năm học</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($firstYearClasses as $class): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['maLop']); ?></td>
                                <td><strong><?php echo htmlspecialchars($class['tenLop']); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $class['siSo'] > 0 ? 'badge-primary' : 'badge-warning'; ?>">
                                        <?php echo $class['siSo']; ?> HS
                                    </span>
                                </td>
                                <td><?php echo $class['tenPhong'] ?? '<em>Chưa phân</em>'; ?></td>
                                <td><?php echo htmlspecialchars($class['namHoc']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Danh sách học sinh đã có lớp (toàn trường) -->
        <div class="card" style="margin-top: 25px;">
            <div class="card-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3><i class="fas fa-user-check"></i> Học sinh đã có lớp</h3>
                <span class="badge" style="background: white; color: #4facfe;">
                    <?php 
                    $studentsWithClass = array_filter($allStudents ?? [], function($s) { return !empty($s['maLop']); });
                    echo count($studentsWithClass); 
                    ?> học sinh
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($studentsWithClass)): ?>
                <div class="empty-state">
                    <i class="fas fa-info-circle"></i>
                    <p>Chưa có học sinh nào được phân lớp!</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Mã HS</th>
                            <th>Họ tên</th>
                            <th>Ngày sinh</th>
                            <th>Giới tính</th>
                            <th>Lớp</th>
                            <th>Khối</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentsWithClass as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['maHS']); ?></td>
                            <td><strong><?php echo htmlspecialchars($student['hoTen']); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($student['ngaySinh'])); ?></td>
                            <td>
                                <span class="badge <?php echo $student['gioiTinh'] === 'Nam' ? 'badge-primary' : 'badge-success'; ?>">
                                    <?php echo $student['gioiTinh'] === 'Nam' ? 'Nam' : 'Nữ'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-primary">
                                    <?php echo htmlspecialchars($student['tenLop']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($student['khoiLop'] ?? ''); ?></td>
                            <td>
                                <button class="action-btn delete" onclick="confirmRevokeStudent(<?php echo $student['maHS']; ?>, '<?php echo htmlspecialchars($student['hoTen']); ?>')" style="background: #e74c3c; padding: 6px 12px; border: none; border-radius: 5px; color: white; cursor: pointer;">
                                    <i class="fas fa-times"></i> Thu hồi
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal chọn lớp -->
    <div class="modal" id="classSelectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-school"></i> Chọn lớp để phân công</h3>
                <button class="modal-close" onclick="closeClassSelectModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="showEmptyClasses" onchange="toggleEmptyClasses()">
                        Chỉ hiển thị lớp chưa có học sinh
                    </label>
                </div>
                
                <div id="allClassesList">
                    <?php foreach ($firstYearClasses as $class): ?>
                    <div class="class-card" data-id="<?php echo $class['maLop']; ?>" data-empty="<?php echo $class['siSo'] == 0 ? '1' : '0'; ?>" onclick="selectClass(this)">
                        <h4><?php echo htmlspecialchars($class['tenLop']); ?></h4>
                        <p>
                            <i class="fas fa-users"></i> Sĩ số: <?php echo $class['siSo']; ?> HS
                            <?php if ($class['tenPhong']): ?>
                            | <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($class['tenPhong']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button class="btn btn-secondary" onclick="closeClassSelectModal()">Hủy</button>
                    <button class="btn btn-success" onclick="submitAssignment()">
                        <i class="fas fa-save"></i> Lưu phân công
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal phân lớp tự động -->
    <div class="modal" id="autoAssignModal">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3><i class="fas fa-magic"></i> Phân lớp tự động</h3>
                <button class="modal-close" onclick="closeAutoAssignModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form action="../controller/cTeachingAssignment.php?action=autoAssignStudents" method="POST">
                    <input type="hidden" name="namHoc" value="<?php echo htmlspecialchars($namHoc); ?>">
                    
                    <div class="form-group">
                        <label><strong>Tiêu chí phân lớp:</strong></label>
                        <div class="form-check">
                            <input type="radio" name="criteria" id="criteria1" value="chia_deu_theo_lop" checked>
                            <label for="criteria1">Chia đều học sinh theo số lớp hiện có</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" name="criteria" id="criteria2" value="so_luong_hs_lop">
                            <label for="criteria2">Chia theo số lượng học sinh/lớp</label>
                        </div>
                    </div>

                    <div class="form-group" id="soLuongHSLopGroup" style="display: none;">
                        <label for="so_luong_hs_lop">Số học sinh tối đa mỗi lớp:</label>
                        <input type="number" name="so_luong_hs_lop" id="so_luong_hs_lop" class="form-control" value="40" min="1" max="50" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>

                    <div class="form-group">
                        <label><strong>Tùy chọn bổ sung:</strong></label>
                        <div class="form-check">
                            <input type="checkbox" name="chia_deu_nam_nu" id="chia_deu_nam_nu" checked>
                            <label for="chia_deu_nam_nu">Chia đều nam và nữ</label>
                        </div>
                    </div>

                    <div class="alert alert-info" style="background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9;">
                        <i class="fas fa-info-circle"></i>
                        Có <strong><?php echo count($studentsWithoutClass); ?></strong> học sinh chưa có lớp sẽ được phân vào <strong><?php echo count($firstYearClasses); ?></strong> lớp đầu cấp.
                    </div>

                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="closeAutoAssignModal()">Hủy</button>
                        <button type="submit" class="btn btn-success" name="chia_deu_theo_lop" value="1" id="submitAutoBtn">
                            <i class="fas fa-magic"></i> Phân lớp tự động
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle select all
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateSelectedCount();
        }

        // Update selected count
        function updateSelectedCount() {
            const count = document.querySelectorAll('.student-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = count;
        }

        // Add event listeners to checkboxes
        document.querySelectorAll('.student-checkbox').forEach(cb => {
            cb.addEventListener('change', updateSelectedCount);
        });

        // Open class select modal
        function openClassSelectModal() {
            const count = document.querySelectorAll('.student-checkbox:checked').length;
            if (count === 0) {
                alert('Vui lòng chọn ít nhất một học sinh!');
                return;
            }
            document.getElementById('classSelectModal').classList.add('active');
        }

        // Close class select modal
        function closeClassSelectModal() {
            document.getElementById('classSelectModal').classList.remove('active');
        }

        // Toggle empty classes filter
        function toggleEmptyClasses() {
            const showEmpty = document.getElementById('showEmptyClasses').checked;
            document.querySelectorAll('.class-card').forEach(card => {
                if (showEmpty && card.dataset.empty === '0') {
                    card.style.display = 'none';
                } else {
                    card.style.display = 'block';
                }
            });
        }

        // Select class
        let selectedClassId = null;
        function selectClass(element) {
            document.querySelectorAll('.class-card').forEach(c => c.classList.remove('selected'));
            element.classList.add('selected');
            selectedClassId = element.dataset.id;
        }

        // Submit assignment
        function submitAssignment() {
            if (!selectedClassId) {
                alert('Vui lòng chọn một lớp!');
                return;
            }
            document.getElementById('selectedClassId').value = selectedClassId;
            document.getElementById('assignForm').submit();
        }

        // Open auto assign modal
        function openAutoAssignModal() {
            document.getElementById('autoAssignModal').classList.add('active');
        }

        // Close auto assign modal
        function closeAutoAssignModal() {
            document.getElementById('autoAssignModal').classList.remove('active');
        }

        // Toggle số lượng HS/lớp input
        document.querySelectorAll('input[name="criteria"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const group = document.getElementById('soLuongHSLopGroup');
                if (this.value === 'so_luong_hs_lop') {
                    group.style.display = 'block';
                } else {
                    group.style.display = 'none';
                }
            });
        });

        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Confirm revoke student class
        function confirmRevokeStudent(maHS, hoTen) {
            if (confirm('Bạn có chắc chắn muốn thu hồi phân lớp của học sinh "' + hoTen + '"?')) {
                window.location.href = '../controller/cTeachingAssignment.php?action=revokeStudentClass&maHS=' + maHS;
            }
        }
    </script>
        </div>
    </div>
</div>
</body>
</html>
