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
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
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

        <div id="alert-container"></div>

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

    <script>
        let currentPage = 1;
        let groups = [];

        // Load groups on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadGroups();
            loadAccounts();
        });

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
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
