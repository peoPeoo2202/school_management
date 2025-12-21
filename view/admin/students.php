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

// Lấy danh sách Khối (from khoi table)
$khoi_query = "SELECT maKhoi, khoiLop FROM khoi ORDER BY khoiLop";
$khoi_result = $conn->query($khoi_query);
$khoi_list = [];
if ($khoi_result) {
    while ($row = $khoi_result->fetch_assoc()) {
        $khoi_list[] = $row;
    }
}

// Lấy danh sách Lớp (sẽ filter theo khối bằng AJAX)
$lop_query = "SELECT l.maLop, l.tenLop, l.maKhoi, k.khoiLop FROM lophoc l LEFT JOIN khoi k ON l.maKhoi = k.maKhoi ORDER BY k.khoiLop, l.tenLop";
$lop_result = $conn->query($lop_query);
$lop_list = [];
if ($lop_result) {
    while ($row = $lop_result->fetch_assoc()) {
        $lop_list[] = $row;
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
    <title>Quản lý thông tin học sinh - Admin</title>
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
                        <a href="students.php" class="nav-link active">
                            <i class="fas fa-user-graduate"></i>
                            <span>Học sinh</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="teachers.php" class="nav-link">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Giáo viên</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="parents.php" class="nav-link">
                            <i class="fas fa-user-friends"></i>
                            <span>Phụ huynh</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="schedule.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Lên lịch</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="#" class="nav-link">
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
                <a href="../../public/logout.php" class="logout-btn">
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
                    <span class="breadcrumb-separator">/</span>
                    <div class="breadcrumb-item">
                        <a href="index.php">Quản lý thông tin</a>
                    </div>
                    <span class="breadcrumb-separator">/</span>
                    <div class="breadcrumb-item">
                        <span class="breadcrumb-current">Học sinh</span>
                    </div>
                </div>

                <div class="header-actions">
                    <button class="notification-btn" title="Thông báo">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>

                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['hoTen'] ?? 'A', 0, 1)); ?></div>
                        <div class="user-details">
                            <div class="user-name"><?php echo htmlspecialchars($_SESSION['hoTen'] ?? 'Administrator'); ?></div>
                            <div class="user-role">Quản trị viên</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <section class="content">
                <h1 class="page-title"><i class="fas fa-user-graduate"></i> Quản lý thông tin học sinh</h1>
                <p class="page-subtitle">Quản lý hồ sơ, theo dõi quá trình học tập, khen thưởng, kỷ luật và vi phạm của học sinh</p>

                <!-- Action Bar -->
                <div class="content-actions">
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Thêm học sinh mới
                    </button>
                </div>

                <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label for="filter-khoi">Khối</label>
                        <select id="filter-khoi" class="form-control" onchange="filterLopByKhoi()">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($khoi_list as $khoi): ?>
                                <option value="<?php echo htmlspecialchars($khoi['maKhoi']); ?>">
                                    Khối <?php echo htmlspecialchars($khoi['khoiLop']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="filter-lop">Lớp học</label>
                        <select id="filter-lop" class="form-control">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($lop_list as $lop): ?>
                                <option value="<?php echo $lop['maLop']; ?>" data-khoi="<?php echo $lop['maKhoi']; ?>">
                                    <?php echo htmlspecialchars($lop['tenLop']); ?>
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
                    <div class="filter-item">
                        <label for="filter-mahs">Mã học sinh</label>
                        <input type="text" id="filter-mahs" class="form-control" placeholder="Nhập mã học sinh...">
                    </div>
                    <div class="filter-item">
                        <label for="filter-hoten">Họ và tên</label>
                        <input type="text" id="filter-hoten" class="form-control" placeholder="Nhập họ tên...">
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="searchStudents()">
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
                <table class="data-table" id="students-table">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                            </th>
                            <th width="120">Mã HS</th>
                            <th>Họ và tên</th>
                            <th width="100">Giới tính</th>
                            <th width="120">Ngày sinh</th>
                            <th width="150">Lớp</th>
                            <th width="80">Khối</th>
                            <th width="180">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="students-tbody">
                        <tr>
                            <td colspan="8" class="loading-row">
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

            </section>
        </main>
    </div>

    <!-- Modal: View/Edit Student Detail -->
    <div id="student-modal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2 id="modal-title">Chi tiết học sinh</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Tab Navigation -->
                <div class="tab-navigation">
                    <button class="tab-btn active" onclick="switchTab('detail')">Chi tiết hồ sơ</button>
                    <button class="tab-btn" onclick="switchTab('academic')">Quá trình học tập</button>
                    <button class="tab-btn" onclick="switchTab('awards')">Khen thưởng/Kỷ luật</button>
                    <button class="tab-btn" onclick="switchTab('violations')">Vi phạm/Nghỉ học</button>
                </div>

                <!-- Tab: Chi tiết hồ sơ -->
                <div id="tab-detail" class="tab-content active">
                    <form id="student-form">
                        <input type="hidden" id="student-id" name="maHocSinh">
                        
                        <h3 class="form-section-title">Thông tin chung</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="khoi">Khối <span class="required">*</span></label>
                                <select id="khoi" name="khoi" class="form-control" required>
                                    <option value="">-- Chọn khối --</option>
                                    <?php foreach ($khoi_list as $khoi): ?>
                                        <option value="<?php echo htmlspecialchars($khoi['maKhoi']); ?>">
                                            Khối <?php echo htmlspecialchars($khoi['khoiLop']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="lop">Lớp <span class="required">*</span></label>
                                <select id="lop" name="maLop" class="form-control" required>
                                    <option value="">-- Chọn lớp --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="trangthai">Trạng thái</label>
                                <select id="trangthai" name="trangThai" class="form-control">
                                    <option value="Đang học">Đang học</option>
                                    <option value="Bảo lưu">Bảo lưu</option>
                                    <option value="Đã thôi học">Đã thôi học</option>
                                </select>
                            </div>
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
                                <label for="tinh">Tỉnh/Thành <span class="required">*</span></label>
                                <input type="text" id="tinh" name="tinhThanh" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="xa">Xã/Phường</label>
                                <input type="text" id="xa" name="xaPhuong" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="ngayvaotruong">Ngày vào trường <span class="required">*</span></label>
                                <input type="date" id="ngayvaotruong" name="ngayVaoTruong" class="form-control" required>
                            </div>
                        </div>

                        <h3 class="form-section-title">Thông tin cá nhân</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="dantoc">Dân tộc</label>
                                <input type="text" id="dantoc" name="danToc" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="sdtnha">Số điện thoại nhà</label>
                                <input type="tel" id="sdtnha" name="sdtNha" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="sdtdidong">Số điện thoại di động</label>
                                <input type="tel" id="sdtdidong" name="sdtDiDong" class="form-control">
                            </div>
                        </div>

                        <h3 class="form-section-title">Thông tin gia đình</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="hotencha">Họ tên cha</label>
                                <input type="text" id="hotencha" name="hoTenCha" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="nghenghiepcha">Nghề nghiệp cha</label>
                                <input type="text" id="nghenghiepcha" name="ngheNghiepCha" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="hotenme">Họ tên mẹ</label>
                                <input type="text" id="hotenme" name="hoTenMe" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="nghenghiepme">Nghề nghiệp mẹ</label>
                                <input type="text" id="nghenghiepme" name="ngheNghiepMe" class="form-control">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab: Quá trình học tập -->
                <div id="tab-academic" class="tab-content">
                    <div id="academic-content" class="data-content">
                        <p class="empty-state">Chọn năm học để xem bảng điểm</p>
                    </div>
                </div>

                <!-- Tab: Khen thưởng/Kỷ luật -->
                <div id="tab-awards" class="tab-content">
                    <div id="awards-content" class="data-content">
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Hình thức</th>
                                    <th>Địa điểm</th>
                                    <th>Nội dung</th>
                                </tr>
                            </thead>
                            <tbody id="awards-tbody">
                                <tr>
                                    <td colspan="4" class="empty-state">Chưa có dữ liệu</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Vi phạm/Nghỉ học -->
                <div id="tab-violations" class="tab-content">
                    <div id="violations-content" class="data-content">
                        <h4>Vi phạm</h4>
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th>Ngày vi phạm</th>
                                    <th>Lỗi</th>
                                    <th>Số lần</th>
                                </tr>
                            </thead>
                            <tbody id="violations-tbody">
                                <tr>
                                    <td colspan="3" class="empty-state">Chưa có dữ liệu</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h4>Nghỉ học</h4>
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th>Ngày nghỉ</th>
                                    <th>Lý do</th>
                                    <th>Tổng số ngày nghỉ</th>
                                </tr>
                            </thead>
                            <tbody id="absences-tbody">
                                <tr>
                                    <td colspan="3" class="empty-state">Chưa có dữ liệu</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Đóng
                </button>
                <button type="button" class="btn btn-primary" id="save-btn" onclick="saveStudent()">
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
                <p id="delete-message">Bạn có chắc chắn muốn xóa học sinh này không?</p>
                <p class="warning-text">Hành động này không thể hoàn tác!</p>
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

    <script src="js/students.js"></script>
</body>
</html>
