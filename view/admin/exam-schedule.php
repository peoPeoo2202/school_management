<?php
/**
 * EXAM SCHEDULE MANAGEMENT - Lên Lịch thi
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
$kythi_list = [];
$monhoc_list = [];
$giaovien_list = [];
$phong_list = [];

if ($conn) {
    // Get khối
    $result = $conn->query("SELECT * FROM khoi ORDER BY khoiLop");
    while ($row = $result->fetch_assoc()) {
        $khoi_list[] = $row;
    }
    
    // Get kỳ thi
    $result = $conn->query("SELECT kt.*, k.khoiLop FROM kythi kt LEFT JOIN khoi k ON kt.maKhoi = k.maKhoi ORDER BY kt.namHoc DESC, kt.hocKy, k.khoiLop");
    while ($row = $result->fetch_assoc()) {
        $kythi_list[] = $row;
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
    <title>Lên Lịch thi - Admin</title>
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
                        <span>Lịch thi</span>
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
                <h1><i class="fas fa-file-alt"></i> Lên Lịch thi</h1>
                <a href="schedule.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label for="filter-kythi">Kỳ thi</label>
                        <select id="filter-kythi" class="form-control">
                            <option value="">-- Chọn kỳ thi --</option>
                            <?php foreach ($kythi_list as $kt): ?>
                                <option value="<?php echo $kt['maKyThi']; ?>">
                                    <?php echo htmlspecialchars($kt['tenKyThi']); ?> (<?php echo $kt['namHoc']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="filter-monhoc">Môn thi</label>
                        <select id="filter-monhoc" class="form-control">
                            <option value="">-- Tất cả môn --</option>
                            <?php foreach ($monhoc_list as $mon): ?>
                                <option value="<?php echo $mon['maMonHoc']; ?>"><?php echo htmlspecialchars($mon['tenMonHoc']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="loadExamSchedule()">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                    <button class="btn btn-secondary" onclick="clearExamFilters()">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </button>
                </div>
            </div>

            <!-- Exam Info Card -->
            <div id="exam-info" class="exam-info-card" style="display: none;">
                <div class="exam-info-header">
                    <h3 id="exam-title">Thông tin kỳ thi</h3>
                    <span class="exam-status" id="exam-status">Đang lập</span>
                </div>
                <div class="exam-info-details">
                    <div class="info-item">
                        <i class="fas fa-calendar"></i>
                        <span id="exam-date-range">Từ ngày - Đến ngày</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-layer-group"></i>
                        <span id="exam-khoi">Khối</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-book"></i>
                        <span id="exam-hocky">Học kỳ</span>
                    </div>
                </div>
            </div>

            <!-- Exam Schedule Table -->
            <div class="content-section" id="exam-schedule-section" style="display: none;">
                <div class="section-header">
                    <h3>Danh sách lịch thi</h3>
                    <button class="btn btn-success" onclick="openAddExamModal()">
                        <i class="fas fa-plus"></i> Thêm lịch thi
                    </button>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Môn thi</th>
                                <th>Ngày thi</th>
                                <th>Giờ thi</th>
                                <th>Phòng thi</th>
                                <th>GV coi thi</th>
                                <th width="150">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="exam-tbody">
                            <tr>
                                <td colspan="7" class="empty-row">Chưa có lịch thi</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty State -->
            <div class="empty-state" id="exam-empty-state">
                <i class="fas fa-calendar-check"></i>
                <h3>Chọn kỳ thi để xem lịch</h3>
                <p>Vui lòng chọn kỳ thi để bắt đầu lập lịch thi</p>
            </div>
        </main>
    </div>

    <!-- Modal: Add/Edit Exam Schedule -->
    <div id="exam-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="exam-modal-title">Thêm lịch thi</h2>
                <button class="modal-close" onclick="closeExamModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="exam-form">
                    <input type="hidden" id="exam-schedule-id" name="maLichThi">
                    <input type="hidden" id="exam-kythi-id" name="maKyThi">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="exam-monhoc">Môn thi <span class="required">*</span></label>
                            <select id="exam-monhoc" name="maMonHoc" class="form-control" required>
                                <option value="">-- Chọn môn --</option>
                                <?php foreach ($monhoc_list as $mon): ?>
                                    <option value="<?php echo $mon['maMonHoc']; ?>"><?php echo htmlspecialchars($mon['tenMonHoc']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exam-ngay">Ngày thi <span class="required">*</span></label>
                            <input type="date" id="exam-ngay" name="ngayThi" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="exam-gio-start">Giờ bắt đầu <span class="required">*</span></label>
                            <input type="time" id="exam-gio-start" name="gioBatDau" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="exam-gio-end">Giờ kết thúc <span class="required">*</span></label>
                            <input type="time" id="exam-gio-end" name="gioKetThuc" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam-phong">Phòng thi <span class="required">*</span></label>
                        <select id="exam-phong" name="maPhong" class="form-control" required>
                            <option value="">-- Chọn phòng --</option>
                            <?php foreach ($phong_list as $phong): ?>
                                <option value="<?php echo $phong['maPhong']; ?>"><?php echo htmlspecialchars($phong['tenPhong']); ?> (<?php echo $phong['soLuongSV']; ?> chỗ)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam-giaovien">Giáo viên coi thi</label>
                        <select id="exam-giaovien" name="maGV[]" class="form-control" multiple size="5">
                            <?php foreach ($giaovien_list as $gv): ?>
                                <option value="<?php echo $gv['maGV']; ?>"><?php echo htmlspecialchars($gv['hoTen']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">Giữ Ctrl để chọn nhiều giáo viên</small>
                    </div>
                    
                    <div id="exam-conflict-warning" class="alert alert-warning" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span id="exam-conflict-message"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExamModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" onclick="saveExamSchedule()">
                    <i class="fas fa-save"></i> Xác nhận
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Confirm Delete -->
    <div id="exam-delete-modal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>Xác nhận xóa</h2>
                <button class="modal-close" onclick="closeExamDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <p>Bạn có chắc chắn muốn xóa lịch thi này không?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExamDeleteModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteExam()">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <script src="js/exam-schedule.js"></script>
</body>
</html>
