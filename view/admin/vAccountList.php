<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['maTaiKhoan'])) {
    header("Location: ../../public/index.php");
    exit;
}

// Kiểm tra quyền quản trị viên
if ($_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    echo "<h1>Không có quyền truy cập</h1>";
    exit;
}

require_once('../../controller/cAccountManagement.php');
$csrfToken = cAccountManagement::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tài khoản - Hệ thống Quản lý Trường học</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="./admin/css/account-management.css">
    <style>
        .account-management-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .header-section h1 {
            margin: 0;
            color: #333;
            font-size: 28px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-success {
            background: #28a745;
            color: white;
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
            padding: 5px 10px;
            font-size: 12px;
        }

        .filter-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
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
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-locked {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-disabled {
            background: #d6d8db;
            color: #383d41;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 12px;
        }

        .pagination span {
            color: #666;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .modal-header h3 {
            margin: 0;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .form-group input[type="checkbox"] {
            width: auto;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .tabs-container {
            display: flex;
            gap: 10px;
        }

        .tab-btn {
            padding: 12px 24px;
            background: #f8f9fa;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            background: #e9ecef;
            color: #333;
        }

        .tab-btn.active {
            background: white;
            color: #007bff;
            border-bottom-color: #007bff;
        }

        .tab-content {
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="account-management-container">
        <div class="header-section">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="../admin/index.php" class="btn" style="background: #6c757d; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                    ← Quay lại Dashboard
                </a>
                <h1 style="margin: 0;">Quản lý Tài khoản</h1>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="icon-plus"></i> Tạo tài khoản mới
            </button>
        </div>

        <!-- Tabs Section -->
        <div class="tabs-container" style="margin-bottom: 20px; border-bottom: 2px solid #e0e0e0;">
            <button class="tab-btn active" onclick="switchTab('assign')" id="tab-assign">
                Cấp tài khoản
            </button>
            <button class="tab-btn" onclick="switchTab('manage')" id="tab-manage">
                Tạo tài khoản
            </button>
        </div>

        <div id="alert-container"></div>

        <!-- Tab Content: Cấp tài khoản -->
        <div id="assign-account-tab" class="tab-content">
            <h3 style="margin-bottom: 20px;">Cấp tài khoản cho học sinh</h3>
            
            <div class="filter-section">
                <div class="filter-group">
                    <label>Mã học sinh</label>
                    <input type="text" id="filter-student-id" placeholder="Nhập mã học sinh...">
                </div>
                <div class="filter-group">
                    <label>Tên học sinh</label>
                    <input type="text" id="filter-student-name" placeholder="Nhập tên học sinh...">
                </div>
                <div class="filter-group">
                    <label>Lớp</label>
                    <select id="filter-student-class">
                        <option value="">Tất cả</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Trạng thái tài khoản</label>
                    <select id="filter-has-account">
                        <option value="">Tất cả</option>
                        <option value="no">Chưa có tài khoản</option>
                        <option value="yes">Đã có tài khoản</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="loadStudents()">Tìm kiếm</button>
                    <button class="btn btn-warning" onclick="resetStudentFilters()">Đặt lại</button>
                </div>
            </div>

            <div class="table-container">
                <div id="student-loading" class="loading" style="display: none;">
                    Đang tải dữ liệu...
                </div>
                <table id="students-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã HS</th>
                            <th>Tên học sinh</th>
                            <th>Ngày sinh</th>
                            <th>Lớp</th>
                            <th>Tài khoản</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="students-tbody">
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="student-pagination">
            </div>
        </div>

        <!-- Tab Content: Tạo tài khoản (existing content) -->
        <div id="manage-account-tab" class="tab-content" style="display: none;">

        <!-- Tab Content: Tạo tài khoản (existing content) -->
        <div id="manage-account-tab" class="tab-content" style="display: none;">
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-group">
                <label>Tên đăng nhập</label>
                <input type="text" id="filter-username" placeholder="Nhập tên đăng nhập...">
            </div>
            <div class="filter-group">
                <label>Họ tên</label>
                <input type="text" id="filter-fullname" placeholder="Nhập họ tên...">
            </div>
            <div class="filter-group">
                <label>Loại tài khoản</label>
                <select id="filter-role">
                    <option value="">Tất cả</option>
                    <option value="quantrivien">Quản trị viên</option>
                    <option value="bangiamhieu">Ban giám hiệu</option>
                    <option value="giaovien">Giáo viên</option>
                    <option value="hocsinh">Học sinh</option>
                    <option value="phuhuynh">Phụ huynh</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Trạng thái</label>
                <select id="filter-status">
                    <option value="">Tất cả</option>
                    <option value="active">Hoạt động</option>
                    <option value="locked">Đã khóa</option>
                    <option value="disabled">Vô hiệu hóa</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" onclick="loadAccounts()">Tìm kiếm</button>
                <button class="btn btn-warning" onclick="resetFilters()">Đặt lại</button>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <div id="loading" class="loading" style="display: none;">
                Đang tải dữ liệu...
            </div>
            <table id="accounts-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên đăng nhập</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Loại tài khoản</th>
                        <th>Nhóm</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="accounts-tbody">
                    <!-- Data loaded via JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination">
            <!-- Pagination loaded via JavaScript -->
        </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="account-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Tạo tài khoản mới</h3>
            </div>
            <div class="modal-body">
                <form id="account-form">
                    <input type="hidden" id="account-id" name="id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                    <div class="form-group">
                        <label>Tên đăng nhập <span style="color: red;">*</span></label>
                        <input type="text" id="tenDangNhap" name="tenDangNhap" required>
                    </div>

                    <div class="form-group">
                        <label>Họ tên <span style="color: red;">*</span></label>
                        <input type="text" id="hoTen" name="hoTen" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="email" name="email">
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" id="soDienThoai" name="soDienThoai">
                    </div>

                    <div class="form-group">
                        <label>Loại tài khoản <span style="color: red;">*</span></label>
                        <select id="loaiTaiKhoan" name="loaiTaiKhoan" required>
                            <option value="">-- Chọn loại tài khoản --</option>
                            <option value="quantrivien">Quản trị viên</option>
                            <option value="bangiamhieu">Ban giám hiệu</option>
                            <option value="giaovien">Giáo viên</option>
                            <option value="hocsinh">Học sinh</option>
                            <option value="phuhuynh">Phụ huynh</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Nhóm người dùng</label>
                        <select id="maNhom" name="maNhom">
                            <option value="">-- Chọn nhóm --</option>
                            <!-- Loaded via JavaScript -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select id="trangThaiTaiKhoan" name="trangThaiTaiKhoan">
                            <option value="active">Hoạt động</option>
                            <option value="locked">Đã khóa</option>
                            <option value="disabled">Vô hiệu hóa</option>
                        </select>
                    </div>

                    <div class="form-group" id="password-display" style="display: none;">
                        <label>Mật khẩu được tạo:</label>
                        <input type="text" id="generated-password" readonly style="background: #f8f9fa;">
                        <small style="color: #666;">Lưu mật khẩu này, sẽ không hiển thị lại!</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn" onclick="closeModal()">Hủy</button>
                <button class="btn btn-primary" onclick="saveAccount()">Lưu</button>
            </div>
        </div>
    </div>

    <!-- Assign Account Modal -->
    <div id="assign-account-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; margin: -30px -30px 20px -30px;">
                <h3 id="assign-modal-title" style="margin: 0; color: white;">Cấp tài khoản cho học sinh</h3>
            </div>
            <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
                <form id="assign-account-form">
                    <input type="hidden" id="assign-maHocSinh" name="maHocSinh">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="loaiTaiKhoan" value="hocsinh">

                    <!-- Thông tin học sinh -->
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; color: #333; font-size: 16px;">📋 Thông tin học sinh</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <strong>Mã HS:</strong> <span id="info-maHS">-</span>
                            </div>
                            <div>
                                <strong>Lớp:</strong> <span id="info-lop">-</span>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <strong>Họ tên:</strong> <span id="info-hoTen">-</span>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <strong>Ngày sinh:</strong> <span id="info-ngaySinh">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Form nhập thông tin tài khoản -->
                    <div class="form-group">
                        <label>Tên đăng nhập <span style="color: red;">*</span></label>
                        <input type="text" id="assign-tenDangNhap" name="tenDangNhap" required 
                               placeholder="Nhập tên đăng nhập (4-32 ký tự)">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Chỉ bao gồm chữ cái, số, dấu chấm và gạch dưới
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Họ tên <span style="color: red;">*</span></label>
                        <input type="text" id="assign-hoTen" name="hoTen" required 
                               placeholder="Họ và tên đầy đủ">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="assign-email" name="email" 
                               placeholder="email@example.com">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Có thể để trống, sẽ sử dụng cho việc khôi phục mật khẩu
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" id="assign-soDienThoai" name="soDienThoai" 
                               placeholder="0123456789" maxlength="11">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            9-11 chữ số
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu <span style="color: red;">*</span></label>
                        <div style="display: flex; gap: 10px; align-items: flex-start;">
                            <input type="text" id="assign-matKhau" name="matKhau" required 
                                   placeholder="Nhập hoặc tạo tự động" style="flex: 1;">
                            <button type="button" class="btn btn-warning" onclick="generateRandomPassword()" 
                                    style="white-space: nowrap;">
                                🎲 Tạo ngẫu nhiên
                            </button>
                        </div>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Tối thiểu 8 ký tự, bao gồm chữ hoa, chữ thường và số
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Nhóm người dùng</label>
                        <select id="assign-maNhom" name="maNhom">
                            <option value="">-- Chọn nhóm (tùy chọn) --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Trạng thái tài khoản <span style="color: red;">*</span></label>
                        <select id="assign-trangThaiTaiKhoan" name="trangThaiTaiKhoan" required>
                            <option value="active">Hoạt động</option>
                            <option value="locked">Đã khóa</option>
                            <option value="disabled">Vô hiệu hóa</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="assign-batBuocDoiMatKhau" name="batBuocDoiMatKhau" 
                                   value="1" checked style="margin-right: 8px; width: auto;">
                            <span>Bắt buộc đổi mật khẩu khi đăng nhập lần đầu</span>
                        </label>
                        <small style="color: #666; display: block; margin-top: 5px; margin-left: 28px;">
                            Khuyến nghị bật để bảo mật
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e0e0e0; padding-top: 20px; margin-top: 20px;">
                <button class="btn" onclick="closeAssignModal()" style="background: #6c757d; color: white;">
                    ❌ Hủy
                </button>
                <button class="btn btn-success" onclick="saveAssignAccount()">
                    ✅ Cấp tài khoản
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let currentStudentPage = 1;
        let groups = [];
        let currentTab = 'assign';

        // Load groups on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadGroups();
            loadClasses();
            loadStudents();
        });

        function switchTab(tab) {
            currentTab = tab;
            
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById('tab-' + tab).classList.add('active');
            
            // Update tab content
            if (tab === 'assign') {
                document.getElementById('assign-account-tab').style.display = 'block';
                document.getElementById('manage-account-tab').style.display = 'none';
                loadStudents();
            } else {
                document.getElementById('assign-account-tab').style.display = 'none';
                document.getElementById('manage-account-tab').style.display = 'block';
                loadAccounts();
            }
        }

        async function loadClasses() {
            try {
                const response = await fetch('../../controller/cStudentManagement.php?action=classes');
                if (!response.ok) return;
                
                const result = await response.json();
                if (result.success) {
                    const select = document.getElementById('filter-student-class');
                    select.innerHTML = '<option value="">Tất cả</option>';
                    result.data.forEach(cls => {
                        const option = document.createElement('option');
                        option.value = cls.maLop;
                        option.textContent = cls.tenLop;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        }

        async function loadStudents(page = 1) {
            currentStudentPage = page;
            
            const filters = {
                maHocSinh: document.getElementById('filter-student-id').value,
                tenHocSinh: document.getElementById('filter-student-name').value,
                maLop: document.getElementById('filter-student-class').value,
                hasAccount: document.getElementById('filter-has-account').value,
                page: page,
                limit: 20
            };

            const queryString = new URLSearchParams(filters).toString();
            
            document.getElementById('student-loading').style.display = 'block';
            document.getElementById('students-table').style.opacity = '0.5';

            try {
                const response = await fetch(`../../controller/cAccountManagement.php?action=students&${queryString}`);
                const result = await response.json();

                if (result.success) {
                    renderStudents(result.data);
                    renderStudentPagination(result.pagination);
                } else {
                    showAlert('Lỗi tải dữ liệu: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            } finally {
                document.getElementById('student-loading').style.display = 'none';
                document.getElementById('students-table').style.opacity = '1';
            }
        }

        function renderStudents(students) {
            const tbody = document.getElementById('students-tbody');
            
            if (students.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #666;">Không có dữ liệu</td></tr>';
                return;
            }

            tbody.innerHTML = students.map((student, index) => {
                const stt = (currentStudentPage - 1) * 20 + index + 1;
                return `
                    <tr>
                        <td>${stt}</td>
                        <td>${student.maHocSinh}</td>
                        <td>${student.tenHocSinh}</td>
                        <td>${formatDate(student.ngaySinh)}</td>
                        <td>${student.tenLop || '-'}</td>
                        <td>${student.tenDangNhap || '-'}</td>
                        <td>
                            ${student.maTaiKhoan 
                                ? `<span class="badge badge-${student.trangThaiTaiKhoan}">${getStatusText(student.trangThaiTaiKhoan)}</span>`
                                : '<span class="badge" style="background: #ffc107; color: #000;">Chưa có TK</span>'
                            }
                        </td>
                        <td>
                            <div class="actions">
                                ${!student.maTaiKhoan 
                                    ? `<button class="btn btn-sm btn-success" onclick="assignAccount('${student.maHocSinh}')" title="Cấp tài khoản">
                                        📝 Cấp TK
                                    </button>`
                                    : `<button class="btn btn-sm btn-primary" onclick="viewStudentAccount('${student.maHocSinh}')" title="Xem tài khoản">
                                        👁️ Xem
                                    </button>`
                                }
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderStudentPagination(pagination) {
            const container = document.getElementById('student-pagination');
            
            container.innerHTML = `
                <button class="btn" ${pagination.page <= 1 ? 'disabled' : ''} 
                        onclick="loadStudents(${pagination.page - 1})">Trước</button>
                <span>Trang ${pagination.page} / ${pagination.totalPages} (Tổng: ${pagination.total})</span>
                <button class="btn" ${pagination.page >= pagination.totalPages ? 'disabled' : ''} 
                        onclick="loadStudents(${pagination.page + 1})">Sau</button>
            `;
        }

        function resetStudentFilters() {
            document.getElementById('filter-student-id').value = '';
            document.getElementById('filter-student-name').value = '';
            document.getElementById('filter-student-class').value = '';
            document.getElementById('filter-has-account').value = '';
            loadStudents(1);
        }

        function assignAccount(maHocSinh) {
            // Load student info and open modal
            loadStudentInfoForAssign(maHocSinh);
        }

        async function loadStudentInfoForAssign(maHocSinh) {
            try {
                const response = await fetch(`../../controller/cStudentManagement.php?action=get&id=${maHocSinh}`);
                const result = await response.json();

                if (result.success) {
                    const student = result.data;
                    
                    // Fill student info
                    document.getElementById('assign-maHocSinh').value = student.maHS;
                    document.getElementById('info-maHS').textContent = student.maHS;
                    document.getElementById('info-hoTen').textContent = student.hoTen;
                    document.getElementById('info-ngaySinh').textContent = formatDate(student.ngaySinh);
                    document.getElementById('info-lop').textContent = student.tenLop || '-';
                    
                    // Pre-fill form with student data
                    document.getElementById('assign-hoTen').value = student.hoTen;
                    
                    // Generate suggested username (e.g., hs12345 or student's name without spaces)
                    const suggestedUsername = 'hs' + student.maHS;
                    document.getElementById('assign-tenDangNhap').value = suggestedUsername;
                    
                    // Reset other fields
                    document.getElementById('assign-email').value = '';
                    document.getElementById('assign-soDienThoai').value = '';
                    document.getElementById('assign-matKhau').value = '';
                    document.getElementById('assign-maNhom').value = '';
                    document.getElementById('assign-trangThaiTaiKhoan').value = 'active';
                    document.getElementById('assign-batBuocDoiMatKhau').checked = true;
                    
                    // Load groups for assign modal
                    updateAssignGroupDropdown();
                    
                    // Open modal
                    document.getElementById('assign-account-modal').style.display = 'block';
                } else {
                    showAlert('Lỗi: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            }
        }

        function updateAssignGroupDropdown() {
            const select = document.getElementById('assign-maNhom');
            select.innerHTML = '<option value="">-- Chọn nhóm (tùy chọn) --</option>';
            
            groups.forEach(group => {
                const option = document.createElement('option');
                option.value = group.maNhom;
                option.textContent = group.tenNhom;
                select.appendChild(option);
            });
        }

        function generateRandomPassword() {
            const length = 12;
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
            let password = "";
            
            // Ensure at least 1 uppercase, 1 lowercase, 1 number
            password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)];
            password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)];
            password += "0123456789"[Math.floor(Math.random() * 10)];
            
            // Fill the rest randomly
            for (let i = 3; i < length; i++) {
                password += charset[Math.floor(Math.random() * charset.length)];
            }
            
            // Shuffle the password
            password = password.split('').sort(() => Math.random() - 0.5).join('');
            
            document.getElementById('assign-matKhau').value = password;
        }

        async function saveAssignAccount() {
            const form = document.getElementById('assign-account-form');
            const formData = new FormData(form);
            const maHocSinh = document.getElementById('assign-maHocSinh').value;

            // Validate required fields
            const tenDangNhap = document.getElementById('assign-tenDangNhap').value.trim();
            const hoTen = document.getElementById('assign-hoTen').value.trim();
            const matKhau = document.getElementById('assign-matKhau').value;

            if (!tenDangNhap || !hoTen || !matKhau) {
                showAlert('Vui lòng điền đầy đủ các trường bắt buộc (*)!', 'error');
                return;
            }

            // Validate username format
            if (!/^[a-zA-Z0-9._]{4,32}$/.test(tenDangNhap)) {
                showAlert('Tên đăng nhập không hợp lệ (4-32 ký tự, chỉ chữ, số, dấu chấm và gạch dưới)!', 'error');
                return;
            }

            // Validate password strength
            if (matKhau.length < 8 || !/[A-Z]/.test(matKhau) || !/[a-z]/.test(matKhau) || !/[0-9]/.test(matKhau)) {
                showAlert('Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số!', 'error');
                return;
            }

            try {
                const response = await fetch(`../../controller/cAccountManagement.php?action=assign-student&maHocSinh=${maHocSinh}`, {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                console.log('Response:', text);
                
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    showAlert('Lỗi: Server không trả về dữ liệu hợp lệ', 'error');
                    return;
                }

                if (result.success) {
                    showAlert(result.message + '\nTên đăng nhập: ' + tenDangNhap + '\nMật khẩu: ' + matKhau, 'success');
                    closeAssignModal();
                    loadStudents(currentStudentPage);
                } else {
                    showAlert('Lỗi: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error assigning account:', error);
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            }
        }

        function closeAssignModal() {
            document.getElementById('assign-account-modal').style.display = 'none';
            document.getElementById('assign-account-form').reset();
        }

        function viewStudentAccount(maHocSinh) {
            // TODO: Implement view student account
            alert('Chức năng xem tài khoản học sinh ' + maHocSinh + ' sẽ được triển khai');
        }

        async function loadGroups() {
            try {
                const response = await fetch('../../controller/cAccountManagement.php?action=groups');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    showAlert('Lỗi: Server không trả về dữ liệu hợp lệ', 'error');
                    return;
                }
                
                if (result.success) {
                    groups = result.data;
                    updateGroupDropdown();
                }
            } catch (error) {
                console.error('Error loading groups:', error);
            }
        }

        function updateGroupDropdown() {
            const select = document.getElementById('maNhom');
            select.innerHTML = '<option value="">-- Chọn nhóm --</option>';
            
            groups.forEach(group => {
                const option = document.createElement('option');
                option.value = group.maNhom;
                option.textContent = group.tenNhom;
                select.appendChild(option);
            });
        }

        async function loadAccounts(page = 1) {
            currentPage = page;
            
            const filters = {
                tenDangNhap: document.getElementById('filter-username').value,
                hoTen: document.getElementById('filter-fullname').value,
                loaiTaiKhoan: document.getElementById('filter-role').value,
                trangThaiTaiKhoan: document.getElementById('filter-status').value,
                page: page,
                limit: 20
            };

            const queryString = new URLSearchParams(filters).toString();
            
            document.getElementById('loading').style.display = 'block';
            document.getElementById('accounts-table').style.opacity = '0.5';

            try {
                const response = await fetch(`../../controller/cAccountManagement.php?action=list&${queryString}`);
                const result = await response.json();

                if (result.success) {
                    renderAccounts(result.data);
                    renderPagination(result.pagination);
                } else {
                    showAlert('Lỗi tải dữ liệu: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            } finally {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('accounts-table').style.opacity = '1';
            }
        }

        function renderAccounts(accounts) {
            const tbody = document.getElementById('accounts-tbody');
            
            if (accounts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; color: #666;">Không có dữ liệu</td></tr>';
                return;
            }

            tbody.innerHTML = accounts.map(account => `
                <tr>
                    <td>${account.maTaiKhoan}</td>
                    <td>${account.tenDangNhap}</td>
                    <td>${account.hoTen}</td>
                    <td>${account.email || '-'}</td>
                    <td>${getRoleText(account.loaiTaiKhoan)}</td>
                    <td>${account.tenNhom || '-'}</td>
                    <td><span class="badge badge-${account.trangThaiTaiKhoan}">${getStatusText(account.trangThaiTaiKhoan)}</span></td>
                    <td>${formatDate(account.ngayTao)}</td>
                    <td>
                        <div class="actions">
                            <button class="btn btn-sm btn-primary" onclick="editAccount(${account.maTaiKhoan})" title="Sửa">
                                ✏️
                            </button>
                            ${account.trangThaiTaiKhoan === 'locked' 
                                ? `<button class="btn btn-sm btn-success" onclick="unlockAccount(${account.maTaiKhoan})" title="Mở khóa">🔓</button>`
                                : `<button class="btn btn-sm btn-warning" onclick="lockAccount(${account.maTaiKhoan})" title="Khóa">🔒</button>`
                            }
                            <button class="btn btn-sm btn-warning" onclick="resetAccountPassword(${account.maTaiKhoan})" title="Reset mật khẩu">
                                🔑
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteAccount(${account.maTaiKhoan})" title="Xóa">
                                🗑️
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(pagination) {
            const container = document.getElementById('pagination');
            
            container.innerHTML = `
                <button class="btn" ${pagination.page <= 1 ? 'disabled' : ''} 
                        onclick="loadAccounts(${pagination.page - 1})">Trước</button>
                <span>Trang ${pagination.page} / ${pagination.totalPages} (Tổng: ${pagination.total})</span>
                <button class="btn" ${pagination.page >= pagination.totalPages ? 'disabled' : ''} 
                        onclick="loadAccounts(${pagination.page + 1})">Sau</button>
            `;
        }

        function openCreateModal() {
            document.getElementById('modal-title').textContent = 'Tạo tài khoản mới';
            document.getElementById('account-form').reset();
            document.getElementById('account-id').value = '';
            document.getElementById('password-display').style.display = 'none';
            document.getElementById('account-modal').style.display = 'block';
        }

        async function editAccount(id) {
            try {
                const response = await fetch(`../../controller/cAccountManagement.php?action=get&id=${id}`);
                const result = await response.json();

                if (result.success) {
                    const account = result.data;
                    
                    document.getElementById('modal-title').textContent = 'Sửa tài khoản';
                    document.getElementById('account-id').value = account.maTaiKhoan;
                    document.getElementById('tenDangNhap').value = account.tenDangNhap;
                    document.getElementById('tenDangNhap').readOnly = true;
                    document.getElementById('hoTen').value = account.hoTen;
                    document.getElementById('email').value = account.email || '';
                    document.getElementById('soDienThoai').value = account.soDienThoai || '';
                    document.getElementById('loaiTaiKhoan').value = account.loaiTaiKhoan;
                    document.getElementById('maNhom').value = account.maNhom || '';
                    document.getElementById('trangThaiTaiKhoan').value = account.trangThaiTaiKhoan;
                    document.getElementById('password-display').style.display = 'none';
                    
                    document.getElementById('account-modal').style.display = 'block';
                } else {
                    showAlert('Lỗi: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            }
        }

        async function saveAccount() {
            const form = document.getElementById('account-form');
            const formData = new FormData(form);
            const id = document.getElementById('account-id').value;

            const url = id 
                ? `../../controller/cAccountManagement.php?action=update&id=${id}`
                : '../../controller/cAccountManagement.php?action=create';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                console.log('Response status:', response.status);
                console.log('Response text:', text);
                
                if (!response.ok) {
                    // Try to parse error message from JSON
                    try {
                        const errorData = JSON.parse(text);
                        showAlert('Lỗi: ' + (errorData.message || 'Unknown error'), 'error');
                    } catch {
                        showAlert('Lỗi HTTP ' + response.status + ': ' + text.substring(0, 200), 'error');
                    }
                    return;
                }
                
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    showAlert('Lỗi: Server không trả về dữ liệu hợp lệ. Kiểm tra console để biết chi tiết.', 'error');
                    return;
                }

                if (result.success) {
                    showAlert(result.message, 'success');
                    
                    if (result.data && result.data.generatedPassword) {
                        document.getElementById('generated-password').value = result.data.generatedPassword;
                        document.getElementById('password-display').style.display = 'block';
                        setTimeout(() => {
                            closeModal();
                            loadAccounts(currentPage);
                        }, 5000);
                    } else {
                        closeModal();
                        loadAccounts(currentPage);
                    }
                } else {
                    showAlert('Lỗi: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error saving account:', error);
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            }
        }

        async function lockAccount(id) {
            if (!confirm('Bạn có chắc muốn khóa tài khoản này?')) return;

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            formData.append('lyDo', 'Khóa bởi quản trị viên');

            try {
                const response = await fetch(`../../controller/cAccountManagement.php?action=lock&id=${id}`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                    loadAccounts(currentPage);
                } else {
                    showAlert('Lỗi: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            }
        }

        async function unlockAccount(id) {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');

            try {
                const response = await fetch(`../../controller/cAccountManagement.php?action=unlock&id=${id}`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                    loadAccounts(currentPage);
                } else {
                    showAlert('Lỗi: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            }
        }

        async function resetAccountPassword(id) {
            if (!confirm('Bạn có chắc muốn reset mật khẩu cho tài khoản này?')) return;

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');

            try {
                const response = await fetch(`../../controller/cAccountManagement.php?action=reset-password&id=${id}`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    const newPassword = result.data.newPassword;
                    alert(`Mật khẩu mới: ${newPassword}\n\nVui lòng lưu lại mật khẩu này!`);
                    showAlert(result.message, 'success');
                } else {
                    showAlert('Lỗi: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            }
        }

        async function deleteAccount(id) {
            if (!confirm('Bạn có chắc muốn xóa (vô hiệu hóa) tài khoản này?\nHành động này có thể ảnh hưởng đến dữ liệu liên quan.')) return;

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');

            try {
                const response = await fetch(`../../controller/cAccountManagement.php?action=delete&id=${id}`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                    loadAccounts(currentPage);
                } else {
                    showAlert('Lỗi: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            }
        }

        function closeModal() {
            document.getElementById('account-modal').style.display = 'none';
            document.getElementById('tenDangNhap').readOnly = false;
        }

        function resetFilters() {
            document.getElementById('filter-username').value = '';
            document.getElementById('filter-fullname').value = '';
            document.getElementById('filter-role').value = '';
            document.getElementById('filter-status').value = '';
            loadAccounts(1);
        }

        function showAlert(message, type) {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            container.appendChild(alert);

            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        function getRoleText(role) {
            const roles = {
                'quantrivien': 'Quản trị viên',
                'bangiamhieu': 'Ban giám hiệu',
                'giaovien': 'Giáo viên',
                'hocsinh': 'Học sinh',
                'phuhuynh': 'Phụ huynh'
            };
            return roles[role] || role;
        }

        function getStatusText(status) {
            const statuses = {
                'active': 'Hoạt động',
                'locked': 'Đã khóa',
                'disabled': 'Vô hiệu hóa'
            };
            return statuses[status] || status;
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleString('vi-VN');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('account-modal');
            const assignModal = document.getElementById('assign-account-modal');
            if (event.target === modal) {
                closeModal();
            }
            if (event.target === assignModal) {
                closeAssignModal();
            }
        }
    </script>
</body>
</html>
