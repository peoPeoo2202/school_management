<?php
/**
 * TIMETABLE MANAGEMENT - Lên Thời khóa biểu
 */

session_start();

if (!isset($_SESSION['loaiTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    header('Location: ../../public/index.php');
    exit;
}

require_once '../../model/mConnect.php';

$db = new mConnect();
$conn = $db->mConnect();

// Get data for dropdowns
$khoi_list = [];
$lop_list = [];
$monhoc_list = [];
$giaovien_list = [];
$phong_list = [];

if ($conn) {
    // Get khối
    $result = $conn->query("SELECT * FROM khoi ORDER BY khoiLop");
    while ($row = $result->fetch_assoc()) {
        $khoi_list[] = $row;
    }
    
    // Get lớp
    $result = $conn->query("SELECT l.*, k.khoiLop FROM lophoc l LEFT JOIN khoi k ON l.maKhoi = k.maKhoi ORDER BY k.khoiLop, l.tenLop");
    while ($row = $result->fetch_assoc()) {
        $lop_list[] = $row;
    }
    
    // Get môn học
    $result = $conn->query("SELECT * FROM monhoc ORDER BY tenMonHoc");
    while ($row = $result->fetch_assoc()) {
        $monhoc_list[] = $row;
    }
    
    // Get giáo viên
    $result = $conn->query("SELECT maGV, hoTen, toBoMon FROM giaovien ORDER BY hoTen");
    while ($row = $result->fetch_assoc()) {
        $giaovien_list[] = $row;
    }
    
    // Get phòng học
    $result = $conn->query("SELECT * FROM phong WHERE trangThai = 'Hoatdong' ORDER BY tenPhong");
    while ($row = $result->fetch_assoc()) {
        $phong_list[] = $row;
    }
    
    $db->mDisconnect($conn);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lên Thời khóa biểu - Admin</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="css/table-style.css">
    <link rel="stylesheet" href="css/schedule-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i> QLTH
                </a>
                <div class="sidebar-subtitle">Hệ thống Quản lý Trường học</div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">MENU CHÍNH</div>
                    
                    <div class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="students.php" class="nav-link">
                            <i class="fas fa-users-cog"></i>
                            <span>Quản lý thông tin</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="schedule.php" class="nav-link active">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Lên lịch</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="permissions.php" class="nav-link">
                            <i class="fas fa-user-shield"></i>
                            <span>Phân quyền</span>
                        </a>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">CÔNG CỤ</div>
                    
                    <div class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-chart-bar"></i>
                            <span>Báo cáo thống kê</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Cấu hình hệ thống</span>
                        </a>
                    </div>
                </div>
            </nav>

            <div class="sidebar-footer">
                <a href="../../public/index.php?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="breadcrumb">
                    <div class="breadcrumb-item">
                        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    </div>
                    <div class="breadcrumb-item">
                        <a href="schedule.php">Lên lịch</a>
                    </div>
                    <div class="breadcrumb-item">
                        <span>Thời khóa biểu</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="notification-wrapper">
                        <button class="notification-btn">
                            <i class="fas fa-bell"></i>
                            <span class="badge">3</span>
                        </button>
                    </div>
                    <div class="user-dropdown">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['hoTen'] ?? 'A', 0, 1)); ?></div>
                        <span class="user-name"><?php echo $_SESSION['hoTen'] ?? 'Admin Root'; ?></span>
                    </div>
                </div>
            </header>

            <!-- Page Title -->
            <div class="page-title">
                <h1><i class="fas fa-calendar-week"></i> Lên Thời khóa biểu</h1>
                <a href="schedule.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label for="filter-khoi">Khối</label>
                        <select id="filter-khoi" class="form-control" onchange="filterLopByKhoi()">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($khoi_list as $khoi): ?>
                                <option value="<?php echo $khoi['maKhoi']; ?>"><?php echo htmlspecialchars($khoi['khoiLop']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="filter-lop">Lớp</label>
                        <select id="filter-lop" class="form-control">
                            <option value="">-- Chọn lớp --</option>
                            <?php foreach ($lop_list as $lop): ?>
                                <option value="<?php echo $lop['maLop']; ?>" data-khoi="<?php echo $lop['maKhoi']; ?>">
                                    <?php echo htmlspecialchars($lop['tenLop']); ?> (Khối <?php echo $lop['khoiLop']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="filter-hocky">Học kỳ</label>
                        <select id="filter-hocky" class="form-control">
                            <option value="1">Học kỳ 1</option>
                            <option value="2">Học kỳ 2</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="filter-namhoc">Năm học</label>
                        <select id="filter-namhoc" class="form-control">
                            <option value="2024-2025" selected>2024-2025</option>
                            <option value="2025-2026">2025-2026</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="loadTimetable()">
                        <i class="fas fa-search"></i> Xem TKB
                    </button>
                    <button class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </button>
                </div>
            </div>

            <!-- Timetable Grid -->
            <div class="timetable-container" id="timetable-container" style="display: none;">
                <div class="timetable-header">
                    <h3 id="timetable-title">Thời khóa biểu lớp</h3>
                    <div class="timetable-actions">
                        <button class="btn btn-success" onclick="openAddLessonModal()">
                            <i class="fas fa-plus"></i> Thêm tiết học
                        </button>
                    </div>
                </div>
                
                <div class="timetable-grid">
                    <table class="timetable-table">
                        <thead>
                            <tr>
                                <th class="col-tiet">Tiết</th>
                                <th>Thứ 2</th>
                                <th>Thứ 3</th>
                                <th>Thứ 4</th>
                                <th>Thứ 5</th>
                                <th>Thứ 6</th>
                                <th>Thứ 7</th>
                            </tr>
                        </thead>
                        <tbody id="timetable-body">
                            <!-- Generated by JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <div class="timetable-legend">
                    <span class="legend-item"><span class="legend-color scheduled"></span> Đã xếp lịch</span>
                    <span class="legend-item"><span class="legend-color available"></span> Còn trống</span>
                </div>
            </div>

            <!-- Empty State -->
            <div class="empty-state" id="empty-state">
                <i class="fas fa-calendar-plus"></i>
                <h3>Chọn lớp để xem thời khóa biểu</h3>
                <p>Vui lòng chọn khối, lớp và học kỳ để bắt đầu lập thời khóa biểu</p>
            </div>
        </main>
    </div>

    <!-- Modal: Add/Edit Lesson -->
    <div id="lesson-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="lesson-modal-title">Thêm tiết học</h2>
                <button class="modal-close" onclick="closeLessonModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="lesson-form">
                    <input type="hidden" id="lesson-id" name="maLichDay">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lesson-thu">Thứ <span class="required">*</span></label>
                            <select id="lesson-thu" name="thu" class="form-control" required>
                                <option value="">-- Chọn --</option>
                                <option value="2">Thứ 2</option>
                                <option value="3">Thứ 3</option>
                                <option value="4">Thứ 4</option>
                                <option value="5">Thứ 5</option>
                                <option value="6">Thứ 6</option>
                                <option value="7">Thứ 7</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lesson-tiet-start">Tiết bắt đầu <span class="required">*</span></label>
                            <select id="lesson-tiet-start" name="tietBatDau" class="form-control" required>
                                <option value="">-- Chọn --</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>">Tiết <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lesson-tiet-end">Tiết kết thúc <span class="required">*</span></label>
                            <select id="lesson-tiet-end" name="tietKetThuc" class="form-control" required>
                                <option value="">-- Chọn --</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>">Tiết <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lesson-monhoc">Môn học <span class="required">*</span></label>
                            <select id="lesson-monhoc" name="maMonHoc" class="form-control" required>
                                <option value="">-- Chọn môn --</option>
                                <?php foreach ($monhoc_list as $mon): ?>
                                    <option value="<?php echo $mon['maMonHoc']; ?>"><?php echo htmlspecialchars($mon['tenMonHoc']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lesson-giaovien">Giáo viên <span class="required">*</span></label>
                            <select id="lesson-giaovien" name="maGV" class="form-control" required>
                                <option value="">-- Chọn giáo viên --</option>
                                <?php foreach ($giaovien_list as $gv): ?>
                                    <option value="<?php echo $gv['maGV']; ?>"><?php echo htmlspecialchars($gv['hoTen']); ?> (<?php echo $gv['toBoMon']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="lesson-phong">Phòng học <span class="required">*</span></label>
                        <select id="lesson-phong" name="maPhong" class="form-control" required>
                            <option value="">-- Chọn phòng --</option>
                            <?php foreach ($phong_list as $phong): ?>
                                <option value="<?php echo $phong['maPhong']; ?>"><?php echo htmlspecialchars($phong['tenPhong']); ?> (<?php echo $phong['loaiPhong']; ?> - <?php echo $phong['soLuongSV']; ?> chỗ)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="conflict-warning" class="alert alert-warning" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span id="conflict-message"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeLessonModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" onclick="saveLesson()">
                    <i class="fas fa-save"></i> Xác nhận lập lịch
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Confirm Delete -->
    <div id="delete-modal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>Xác nhận xóa</h2>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <p>Bạn có chắc chắn muốn xóa tiết học này không?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteLesson()">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <script src="js/timetable.js"></script>
</body>
</html>
