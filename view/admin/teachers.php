<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['loaiTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

require_once '../../model/mConnect.php';

// Kết nối database
$db = new mConnect();
$conn = $db->mConnect();

// Lấy danh sách Tổ bộ môn (unique values)
$tobomon_query = "SELECT DISTINCT toBoMon FROM giaovien WHERE toBoMon IS NOT NULL ORDER BY toBoMon";
$tobomon_result = $conn->query($tobomon_query);
$tobomon_list = [];
if ($tobomon_result) {
    while ($row = $tobomon_result->fetch_assoc()) {
        $tobomon_list[] = $row['toBoMon'];
    }
}

// Đóng kết nối
$db->mDisconnect($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thông tin giáo viên - Admin</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="css/table-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-user-shield"></i>
                <h3>Quản trị viên</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="students.php" class="nav-link">
                    <i class="fas fa-user-graduate"></i>
                    <span>Học sinh</span>
                </a>
                <a href="teachers.php" class="nav-link active">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Giáo viên</span>
                </a>
                <a href="parents.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Phụ huynh</span>
                </a>
                <a href="schedule.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Lên lịch</span>
                </a>
                <a href="#" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Báo cáo</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <div class="breadcrumb">
                        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                        <span>/</span>
                        <span>Quản lý thông tin</span>
                        <span>/</span>
                        <span>Giáo viên</span>
                    </div>
                </div>
                <div class="header-right">
                    <button class="notification-btn" onclick="showNotifications()">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
                    </button>
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['hoTen'] ?? 'A', 0, 1)); ?>
                        </div>
                        <span><?php echo $_SESSION['hoTen'] ?? 'Admin Root'; ?></span>
                    </div>
                </div>
            </header>

            <!-- Page Title -->
            <div class="page-title">
                <h1><i class="fas fa-chalkboard-teacher"></i> Quản lý thông tin giáo viên</h1>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Thêm giáo viên mới
                </button>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label for="filter-magv">Mã giáo viên</label>
                        <input type="text" id="filter-magv" class="form-control" placeholder="Nhập mã giáo viên...">
                    </div>
                    <div class="filter-item">
                        <label for="filter-hoten">Họ và tên</label>
                        <input type="text" id="filter-hoten" class="form-control" placeholder="Nhập họ tên...">
                    </div>
                    <div class="filter-item">
                        <label for="filter-tobomon">Tổ bộ môn</label>
                        <select id="filter-tobomon" class="form-control">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($tobomon_list as $tobomon): ?>
                                <option value="<?php echo htmlspecialchars($tobomon); ?>">
                                    <?php echo htmlspecialchars($tobomon); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="filter-gioitinh">Giới tính</label>
                        <select id="filter-gioitinh" class="form-control">
                            <option value="">-- Tất cả --</option>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="searchTeachers()">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                    <button class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </button>
                </div>
            </div>

            <!-- Actions Bar -->
            <div class="actions-bar">
                <button class="btn btn-danger" id="delete-selected-btn" onclick="deleteSelected()" disabled>
                    <i class="fas fa-trash"></i> Xóa đã chọn (<span id="selected-count">0</span>)
                </button>
                <div class="pagination-controls">
                    <label for="records-per-page">Hiển thị:</label>
                    <select id="records-per-page" onchange="changeRecordsPerPage()">
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                    </select>
                    <span class="records-info">bản ghi/trang</span>
                </div>
            </div>

            <!-- Data Table -->
            <div class="table-container">
                <table class="data-table" id="teachers-table">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                            </th>
                            <th width="120">Mã GV</th>
                            <th>Họ và tên</th>
                            <th width="150">Tổ bộ môn</th>
                            <th width="100">Giới tính</th>
                            <th width="180">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="teachers-tbody">
                        <tr>
                            <td colspan="6" class="loading-row">
                                <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    Hiển thị <span id="showing-from">0</span> - <span id="showing-to">0</span> 
                    trong tổng số <span id="total-records">0</span> bản ghi
                </div>
                <div class="pagination" id="pagination">
                    <!-- Pagination buttons will be generated by JavaScript -->
                </div>
            </div>
        </main>
    </div>

    <!-- Modal: View/Edit Teacher Detail -->
    <div id="teacher-modal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2 id="modal-title">Chi tiết giáo viên</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="teacher-form">
                    <input type="hidden" id="teacher-id" name="maGiaoVien">
                    
                    <h3 class="form-section-title">Thông tin cơ bản</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="hoten">Họ và tên <span class="required">*</span></label>
                            <input type="text" id="hoten" name="hoTen" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="gioitinh">Giới tính <span class="required">*</span></label>
                            <select id="gioitinh" name="gioiTinh" class="form-control" required>
                                <option value="">-- Chọn --</option>
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ngaysinh">Ngày sinh <span class="required">*</span></label>
                            <input type="date" id="ngaysinh" name="ngaySinh" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="tobomon">Tổ bộ môn <span class="required">*</span></label>
                            <input type="text" id="tobomon" name="toBoMon" class="form-control" required list="tobomon-list">
                            <datalist id="tobomon-list">
                                <?php foreach ($tobomon_list as $tobomon): ?>
                                    <option value="<?php echo htmlspecialchars($tobomon); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="sdt">Số điện thoại</label>
                            <input type="tel" id="sdt" name="soDienThoai" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Đóng
                </button>
                <button type="button" class="btn btn-primary" id="save-btn" onclick="saveTeacher()">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Delete Confirmation -->
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
                <p id="delete-message">Bạn có chắc chắn muốn xóa giáo viên này không?</p>
                <p class="warning-text">Hành động này không thể hoàn tác!</p>
                <p id="constraint-warning" class="warning-text" style="display: none; color: #e67e22;">
                    ⚠️ Cảnh báo: Giáo viên có liên kết với dữ liệu khác trong hệ thống!
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button class="btn btn-danger" id="confirm-delete-btn">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script src="js/teachers.js"></script>
</body>
</html>
