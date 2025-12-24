<?php
/**
 * View: vHomeroomTeacherAssignment.php
 * Phân công Giáo viên Chủ nhiệm
 */

// Kiểm tra xem được gọi từ controller hay không
if (!isset($grades) || !isset($assignments)) {
    // Nếu gọi trực tiếp, redirect đến controller
    header("Location: ../controller/cTeachingAssignment.php?action=homeroomTeacherAssignment");
    exit();
}

// Session đã được khởi tạo từ controller, không cần gọi lại

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
$grades = $grades ?? [];
$assignments = $assignments ?? [];
$teachers = $teachers ?? [];
$showAvailable = $showAvailable ?? false;

// Sử dụng năm học từ controller (đã lấy từ DB)
$currentNamHoc = $namHoc ?? (!empty($schoolYears) ? $schoolYears[0] : '2024-2025');
$currentMaKhoi = $maKhoi ?? null;

// Kiểm tra đã chọn khối chưa
$hasSelectedGrade = !empty($currentMaKhoi);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công Giáo viên Chủ nhiệm - BGH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../student/style.css">
    <style>
        .container {
            max-width: 1400px;
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
            color: #0096c7;
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
            background: #0096c7;
            color: white;
        }

        .btn-primary:hover {
            background: #0077b6;
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .breadcrumb {
            background: white;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .breadcrumb a {
            color: #0096c7;
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
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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

        .filter-bar {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .filter-bar .form-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-bar label {
            font-weight: 600;
            color: #333;
            white-space: nowrap;
        }

        .filter-bar select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            min-width: 150px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .badge {
            padding: 6px 14px;
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

        .badge-danger {
            background: #ffebee;
            color: #c62828;
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

        .assignment-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .assignment-row select {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .class-info {
            display: flex;
            flex-direction: column;
        }

        .class-info .class-name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .class-info .class-detail {
            color: #666;
            font-size: 13px;
        }

        .gv-status {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .gv-name {
            font-weight: 500;
            color: #333;
        }

        .gv-unassigned {
            color: #ef6c00;
            font-style: italic;
        }

        /* Responsive table wrapper */
        .table-wrapper {
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-bar .form-group {
                flex-direction: column;
                align-items: stretch;
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
                <i class="fas fa-user-tie"></i>
                Phân công Giáo viên Chủ nhiệm
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
            <strong>Phân công GVCN</strong>
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
                <h3><i class="fas fa-filter"></i> Lọc theo Khối & Năm học</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="../controller/cTeachingAssignment.php" class="filter-bar">
                    <input type="hidden" name="action" value="homeroomTeacherAssignment">
                    
                    <div class="form-group">
                        <label for="maKhoi">Khối:</label>
                        <select name="maKhoi" id="maKhoi">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($grades as $grade): ?>
                            <option value="<?php echo $grade['maKhoi']; ?>" <?php echo $currentMaKhoi == $grade['maKhoi'] ? 'selected' : ''; ?>>
                                Khối <?php echo $grade['khoiLop']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="namHoc">Năm học:</label>
                        <select name="namHoc" id="namHoc">
                            <?php foreach ($schoolYears as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $currentNamHoc === $year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="showAvailable" id="showAvailable" <?php echo $showAvailable ? 'checked' : ''; ?>>
                        <label for="showAvailable">Chỉ hiện GV chưa phân công</label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Lọc
                    </button>
                </form>
            </div>
        </div>

        <!-- Assignment Table Card -->
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3><i class="fas fa-list"></i> Danh sách Phân công GVCN</h3>
                <?php 
                // Lọc assignments nếu showAvailable = true (chỉ hiện lớp chưa có GVCN)
                $displayAssignments = $showAvailable 
                    ? array_filter($assignments, function($a) { return empty($a['maGV']); })
                    : $assignments;
                ?>
                <?php if ($hasSelectedGrade): ?>
                <span class="badge" style="background: white; color: #667eea;">
                    <?php echo count($displayAssignments); ?> lớp
                </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$hasSelectedGrade): ?>
                <div class="empty-state">
                    <i class="fas fa-filter"></i>
                    <p>Vui lòng chọn <strong>Khối</strong> để xem danh sách lớp.</p>
                </div>
                <?php elseif (empty($displayAssignments)): ?>
                <div class="empty-state">
                    <i class="fas fa-info-circle"></i>
                    <p><?php echo $showAvailable ? 'Tất cả lớp đã được phân công GVCN.' : 'Không có dữ liệu lớp trong khối này.'; ?></p>
                </div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Khối</th>
                                <th>Lớp</th>
                                <th>Sĩ số</th>
                                <th>Giáo viên Chủ nhiệm hiện tại</th>
                                <th>Chọn GVCN mới</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($displayAssignments as $assignment): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-primary">Khối <?php echo htmlspecialchars($assignment['khoiLop']); ?></span>
                                </td>
                                <td>
                                    <div class="class-info">
                                        <span class="class-name"><?php echo htmlspecialchars($assignment['tenLop']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo $assignment['siSo']; ?> HS</td>
                                <td>
                                    <div class="gv-status">
                                        <?php if ($assignment['tenGV']): ?>
                                        <span class="gv-name">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($assignment['tenGV']); ?>
                                        </span>
                                        <span class="badge badge-success" style="margin-top: 5px;">Đã phân công</span>
                                        <?php else: ?>
                                        <span class="gv-unassigned">
                                            <i class="fas fa-exclamation-triangle"></i> Chưa phân công
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <form action="../controller/cTeachingAssignment.php?action=assignHomeroomTeacher" method="POST" class="assignment-row" id="form-<?php echo $assignment['maLop']; ?>">
                                        <input type="hidden" name="maLop" value="<?php echo $assignment['maLop']; ?>">
                                        <input type="hidden" name="namHoc" value="<?php echo htmlspecialchars($currentNamHoc); ?>">
                                        <input type="hidden" name="maKhoi" value="<?php echo $currentMaKhoi; ?>">
                                        <?php if ($showAvailable): ?>
                                        <input type="hidden" name="showAvailable" value="1">
                                        <?php endif; ?>
                                        <select name="maGV" id="gv-<?php echo $assignment['maLop']; ?>">
                                            <option value="">-- Chọn GV --</option>
                                            <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?php echo $teacher['maGV']; ?>" <?php echo ($assignment['maGV'] == $teacher['maGV']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($teacher['hoTen']); ?> (<?php echo $teacher['toBoMon']; ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="submit" form="form-<?php echo $assignment['maLop']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-save"></i> Lưu
                                        </button>
                                        <?php if ($assignment['maGV']): ?>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmRevoke(<?php echo $assignment['maLop']; ?>, '<?php echo htmlspecialchars($assignment['tenLop']); ?>', '<?php echo htmlspecialchars($currentNamHoc); ?>', '<?php echo $currentMaKhoi; ?>')">
                                            <i class="fas fa-times"></i> Thu hồi
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <h3><i class="fas fa-chart-pie"></i> Thống kê phân công</h3>
            </div>
            <div class="card-body">
                <?php
                $assigned = array_filter($assignments, function($a) { return !empty($a['tenGV']); });
                $unassigned = array_filter($assignments, function($a) { return empty($a['tenGV']); });
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 36px; font-weight: bold; color: #1565c0;"><?php echo count($assignments); ?></div>
                        <div style="color: #666;">Tổng số lớp</div>
                    </div>
                    <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 36px; font-weight: bold; color: #2e7d32;"><?php echo count($assigned); ?></div>
                        <div style="color: #666;">Đã phân công GVCN</div>
                    </div>
                    <div style="background: #fff3e0; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 36px; font-weight: bold; color: #ef6c00;"><?php echo count($unassigned); ?></div>
                        <div style="color: #666;">Chưa phân công</div>
                    </div>
                    <div style="background: #f3e5f5; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 36px; font-weight: bold; color: #7b1fa2;"><?php echo count($teachers); ?></div>
                        <div style="color: #666;">GV khả dụng</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validate before submit
        document.querySelectorAll('form[id^="form-"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const select = this.querySelector('select[name="maGV"]');
                if (!select.value) {
                    e.preventDefault();
                    alert('Vui lòng chọn giáo viên chủ nhiệm!');
                }
            });
        });

        // Confirm revoke assignment
        function confirmRevoke(maLop, tenLop, namHoc, maKhoi) {
            if (confirm('Bạn có chắc chắn muốn thu hồi phân công GVCN của lớp ' + tenLop + '?')) {
                let url = '../controller/cTeachingAssignment.php?action=revokeHomeroomTeacher&maLop=' + maLop + '&namHoc=' + encodeURIComponent(namHoc);
                if (maKhoi) url += '&maKhoi=' + maKhoi;
                window.location.href = url;
            }
        }
    </script>
        </div>
    </div>
</div>
</body>
</html>
