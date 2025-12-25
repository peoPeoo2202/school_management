<?php
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['loaiTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

require_once '../../model/mConnect.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thông tin phụ huynh - Admin</title>
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
                <a href="teachers.php" class="nav-link">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Giáo viên</span>
                </a>
                <a href="parents.php" class="nav-link active">
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
                        <span>Phụ huynh</span>
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
                <h1><i class="fas fa-user-friends"></i> Quản lý thông tin phụ huynh</h1>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Thêm phụ huynh mới
                </button>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label for="filter-maph">Mã phụ huynh</label>
                        <input type="text" id="filter-maph" class="form-control" placeholder="Nhập mã phụ huynh...">
                    </div>
                    <div class="filter-item">
                        <label for="filter-hoten">Họ và tên</label>
                        <input type="text" id="filter-hoten" class="form-control" placeholder="Nhập họ tên...">
                    </div>
                    <div class="filter-item">
                        <label for="filter-sdt">Số điện thoại</label>
                        <input type="text" id="filter-sdt" class="form-control" placeholder="Nhập số điện thoại...">
                    </div>
                    <div class="filter-item">
                        <label for="filter-hoten-hs">Họ tên học sinh liên kết</label>
                        <input type="text" id="filter-hoten-hs" class="form-control" placeholder="Nhập họ tên học sinh...">
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="searchParents()">
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
                <table class="data-table" id="parents-table">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                            </th>
                            <th width="120">Mã PH</th>
                            <th>Họ và tên</th>
                            <th width="150">Số điện thoại</th>
                            <th width="200">Email</th>
                            <th width="100">Số HS</th>
                            <th width="180">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="parents-tbody">
                        <tr>
                            <td colspan="7" class="loading-row">
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

    <!-- Modal: View/Edit Parent Detail -->
    <div id="parent-modal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2 id="modal-title">Chi tiết phụ huynh</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Tab Navigation -->
                <div class="tab-navigation">
                    <button class="tab-btn active" onclick="switchTab('info')">Thông tin cá nhân</button>
                    <button class="tab-btn" onclick="switchTab('students')">Học sinh liên kết</button>
                </div>

                <!-- Tab: Thông tin cá nhân -->
                <div id="tab-info" class="tab-content active">
                    <form id="parent-form">
                        <input type="hidden" id="parent-id" name="maPhuHuynh">
                        
                        <h3 class="form-section-title">Thông tin cơ bản</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="hoten">Họ và tên <span class="required">*</span></label>
                                <input type="text" id="hoten" name="hoTen" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="sdt">Số điện thoại <span class="required">*</span></label>
                                <input type="tel" id="sdt" name="soDienThoai" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="diachi">Địa chỉ</label>
                                <input type="text" id="diachi" name="diaChi" class="form-control">
                            </div>
                        </div>

                        <h3 class="form-section-title">Học sinh liên kết <span class="required">*</span></h3>
                        <div id="students-selector">
                            <div class="student-link-item">
                                <select class="form-control student-select" name="linkedStudents[]">
                                    <option value="">-- Chọn học sinh --</option>
                                </select>
                                <button type="button" class="btn btn-success btn-sm" onclick="addStudentLink()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab: Học sinh liên kết -->
                <div id="tab-students" class="tab-content">
                    <div id="students-content" class="data-content">
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th>Mã HS</th>
                                    <th>Họ và tên</th>
                                    <th>Lớp</th>
                                    <th>Khối</th>
                                </tr>
                            </thead>
                            <tbody id="students-tbody">
                                <tr>
                                    <td colspan="4" class="empty-state">Chưa có học sinh liên kết</td>
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
                <button type="button" class="btn btn-primary" id="save-btn" onclick="saveParent()">
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
                <p id="delete-message">Bạn có chắc chắn muốn xóa phụ huynh này không?</p>
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

    <style>
        .student-link-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .student-link-item .student-select {
            flex: 1;
        }
        
        .btn-sm {
            padding: 8px 12px;
            font-size: 13px;
        }
        
        .btn-remove {
            background: #e74c3c;
            color: white;
        }
        
        .btn-remove:hover {
            background: #c0392b;
        }
    </style>

    <script src="js/parents.js"></script>
</body>
</html>
