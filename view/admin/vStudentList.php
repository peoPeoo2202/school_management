<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Học sinh - Hệ thống Quản lý Trường học</title>
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        h1 {
            color: #333;
            font-size: 28px;
            margin: 0;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-back {
            background: #6c757d;
            color: white;
        }

        .btn-back:hover {
            background: #545b62;
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

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background: #f8f9fa;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            font-weight: 600;
            color: #495057;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 0;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #dee2e6;
            background: #f8f9fa;
            border-radius: 12px 12px 0 0;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #dee2e6;
            text-align: right;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 6px;
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

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }

        .pagination button.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <div class="header-left">
                <a href="../admin/index.php" class="btn btn-back">← Quay lại Dashboard</a>
                <h1>Quản lý Học sinh</h1>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                ➕ Thêm học sinh mới
            </button>
        </div>

        <div id="alert-container"></div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-group">
                <label>Họ tên</label>
                <input type="text" id="filter-name" placeholder="Nhập họ tên...">
            </div>
            <div class="filter-group">
                <label>Lớp</label>
                <select id="filter-class">
                    <option value="">Tất cả</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Giới tính</label>
                <select id="filter-gender">
                    <option value="">Tất cả</option>
                    <option value="Nam">Nam</option>
                    <option value="Nu">Nữ</option>
                    <option value="Khac">Khác</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Trạng thái</label>
                <select id="filter-status">
                    <option value="">Tất cả</option>
                    <option value="danghoc">Đang học</option>
                    <option value="dathoi">Đã thôi học</option>
                    <option value="baoluu">Bảo lưu</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" onclick="loadStudents()">Tìm kiếm</button>
                <button class="btn" style="background: #6c757d; color: white;" onclick="resetFilters()">Đặt lại</button>
            </div>
        </div>

        <!-- Table Section -->
        <div id="loading" class="loading" style="display: none;">
            Đang tải dữ liệu...
        </div>

        <table id="students-table">
            <thead>
                <tr>
                    <th>Mã HS</th>
                    <th>Họ tên</th>
                    <th>Ngày sinh</th>
                    <th>Giới tính</th>
                    <th>Lớp</th>
                    <th>Phụ huynh</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="students-tbody">
                <!-- Data loaded via JavaScript -->
            </tbody>
        </table>

        <div id="pagination-container" class="pagination"></div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="student-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Thêm học sinh mới</h3>
            </div>
            <div class="modal-body">
                <form id="student-form">
                    <input type="hidden" id="student-id" name="id">

                    <div class="form-group">
                        <label>Họ tên <span style="color: red;">*</span></label>
                        <input type="text" id="hoTen" name="hoTen" required>
                    </div>

                    <div class="form-group">
                        <label>Ngày sinh <span style="color: red;">*</span></label>
                        <input type="date" id="ngaySinh" name="ngaySinh" required>
                    </div>

                    <div class="form-group">
                        <label>Giới tính <span style="color: red;">*</span></label>
                        <select id="gioiTinh" name="gioiTinh" required>
                            <option value="">-- Chọn giới tính --</option>
                            <option value="Nam">Nam</option>
                            <option value="Nu">Nữ</option>
                            <option value="Khac">Khác</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <textarea id="diaChi" name="diaChi"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Lớp</label>
                        <select id="maLop" name="maLop">
                            <option value="">-- Chọn lớp --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Phụ huynh</label>
                        <select id="maPH" name="maPH">
                            <option value="">-- Chọn phụ huynh --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select id="trangThaiHocTap" name="trangThaiHocTap">
                            <option value="danghoc">Đang học</option>
                            <option value="baoluu">Bảo lưu</option>
                            <option value="dathoi">Đã thôi học</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn" onclick="closeModal()">Hủy</button>
                <button class="btn btn-primary" onclick="saveStudent()">Lưu</button>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let classes = [];
        let parents = [];

        // Load on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadClasses();
            loadParents();
            loadStudents();
        });

        async function loadClasses() {
            try {
                const response = await fetch('../../controller/cStudentManagement.php?action=classes');
                const text = await response.text();
                
                if (!response.ok) {
                    console.error('Failed to load classes');
                    return;
                }
                
                const result = JSON.parse(text);
                if (result.success) {
                    classes = result.data;
                    updateClassDropdowns();
                }
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        }

        async function loadParents() {
            try {
                const response = await fetch('../../controller/cStudentManagement.php?action=parents');
                const text = await response.text();
                
                if (!response.ok) {
                    console.error('Failed to load parents');
                    return;
                }
                
                const result = JSON.parse(text);
                if (result.success) {
                    parents = result.data;
                    updateParentDropdown();
                }
            } catch (error) {
                console.error('Error loading parents:', error);
            }
        }

        function updateClassDropdowns() {
            const filterSelect = document.getElementById('filter-class');
            const formSelect = document.getElementById('maLop');
            
            filterSelect.innerHTML = '<option value="">Tất cả</option>';
            formSelect.innerHTML = '<option value="">-- Chọn lớp --</option>';
            
            classes.forEach(cls => {
                const option1 = document.createElement('option');
                option1.value = cls.maLop;
                option1.textContent = cls.tenLop;
                filterSelect.appendChild(option1);
                
                const option2 = document.createElement('option');
                option2.value = cls.maLop;
                option2.textContent = cls.tenLop;
                formSelect.appendChild(option2);
            });
        }

        function updateParentDropdown() {
            const select = document.getElementById('maPH');
            select.innerHTML = '<option value="">-- Chọn phụ huynh --</option>';
            
            parents.forEach(parent => {
                const option = document.createElement('option');
                option.value = parent.maPH;
                option.textContent = `${parent.hoTen} - ${parent.soDienThoai}`;
                select.appendChild(option);
            });
        }

        async function loadStudents(page = 1) {
            currentPage = page;
            
            const filters = {
                hoTen: document.getElementById('filter-name').value,
                maLop: document.getElementById('filter-class').value,
                gioiTinh: document.getElementById('filter-gender').value,
                trangThaiHocTap: document.getElementById('filter-status').value,
                page: page,
                limit: 20
            };

            const queryString = new URLSearchParams(filters).toString();
            
            document.getElementById('loading').style.display = 'block';
            document.getElementById('students-table').style.opacity = '0.5';

            try {
                const response = await fetch(`../../controller/cStudentManagement.php?action=list&${queryString}`);
                const text = await response.text();
                console.log('Response:', text);
                
                if (!response.ok) {
                    try {
                        const errorData = JSON.parse(text);
                        showAlert('Lỗi: ' + (errorData.message || 'Unknown error'), 'error');
                    } catch {
                        showAlert('Lỗi HTTP ' + response.status, 'error');
                    }
                    return;
                }
                
                const result = JSON.parse(text);

                if (result.success) {
                    renderStudents(result.data);
                    renderPagination(result.pagination);
                } else {
                    showAlert('Lỗi tải dữ liệu: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            } finally {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('students-table').style.opacity = '1';
            }
        }

        function renderStudents(students) {
            const tbody = document.getElementById('students-tbody');
            
            if (students.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Không có dữ liệu</td></tr>';
                return;
            }

            tbody.innerHTML = students.map(student => `
                <tr>
                    <td>${student.maHS}</td>
                    <td>${student.hoTen}</td>
                    <td>${formatDate(student.ngaySinh)}</td>
                    <td>${student.gioiTinh || '-'}</td>
                    <td>${student.tenLop || '-'}</td>
                    <td>${student.tenPhuHuynh || '-'}</td>
                    <td><span class="badge ${getStatusBadge(student.trangThaiHocTap)}">${getStatusText(student.trangThaiHocTap)}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-info" onclick="viewStudent(${student.maHS})" title="Xem">👁️</button>
                            <button class="btn btn-sm btn-warning" onclick="editStudent(${student.maHS})" title="Sửa">✏️</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteStudent(${student.maHS})" title="Xóa">🗑️</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(pagination) {
            const container = document.getElementById('pagination-container');
            
            if (pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '';
            
            html += `<button onclick="loadStudents(1)" ${currentPage === 1 ? 'disabled' : ''}>«</button>`;
            html += `<button onclick="loadStudents(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>‹</button>`;
            
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === 1 || i === pagination.total_pages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += `<button onclick="loadStudents(${i})" class="${i === currentPage ? 'active' : ''}">${i}</button>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<button disabled>...</button>`;
                }
            }
            
            html += `<button onclick="loadStudents(${currentPage + 1})" ${currentPage === pagination.total_pages ? 'disabled' : ''}>›</button>`;
            html += `<button onclick="loadStudents(${pagination.total_pages})" ${currentPage === pagination.total_pages ? 'disabled' : ''}>»</button>`;
            
            container.innerHTML = html;
        }

        function openCreateModal() {
            document.getElementById('modal-title').textContent = 'Thêm học sinh mới';
            document.getElementById('student-form').reset();
            document.getElementById('student-id').value = '';
            document.getElementById('student-modal').style.display = 'block';
        }

        async function editStudent(maHS) {
            try {
                const response = await fetch(`../../controller/cStudentManagement.php?action=get&id=${maHS}`);
                const result = await response.json();

                if (result.success) {
                    const student = result.data;
                    
                    document.getElementById('modal-title').textContent = 'Sửa thông tin học sinh';
                    document.getElementById('student-id').value = student.maHS;
                    document.getElementById('hoTen').value = student.hoTen;
                    document.getElementById('ngaySinh').value = student.ngaySinh;
                    document.getElementById('gioiTinh').value = student.gioiTinh;
                    document.getElementById('diaChi').value = student.diaChi || '';
                    document.getElementById('maLop').value = student.maLop || '';
                    document.getElementById('maPH').value = student.maPH || '';
                    document.getElementById('trangThaiHocTap').value = student.trangThaiHocTap;
                    
                    document.getElementById('student-modal').style.display = 'block';
                } else {
                    showAlert('Lỗi: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            }
        }

        async function viewStudent(maHS) {
            // Implement view details if needed
            alert('Chức năng xem chi tiết đang phát triển');
        }

        async function saveStudent() {
            const form = document.getElementById('student-form');
            const formData = new FormData(form);
            const id = document.getElementById('student-id').value;

            const url = id 
                ? `../../controller/cStudentManagement.php?action=update&id=${id}`
                : '../../controller/cStudentManagement.php?action=create';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                console.log('Response:', text);
                
                if (!response.ok) {
                    try {
                        const errorData = JSON.parse(text);
                        showAlert('Lỗi: ' + (errorData.message || 'Unknown error'), 'error');
                    } catch {
                        showAlert('Lỗi HTTP ' + response.status, 'error');
                    }
                    return;
                }
                
                const result = JSON.parse(text);

                if (result.success) {
                    showAlert(result.message, 'success');
                    closeModal();
                    loadStudents(currentPage);
                } else {
                    showAlert('Lỗi: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            }
        }

        async function deleteStudent(maHS) {
            if (!confirm('Bạn có chắc muốn xóa học sinh này?\nHọc sinh sẽ chuyển sang trạng thái "Đã thôi học".')) return;

            try {
                const response = await fetch(`../../controller/cStudentManagement.php?action=delete&id=${maHS}`, {
                    method: 'POST'
                });
                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                    loadStudents(currentPage);
                } else {
                    showAlert('Lỗi: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            }
        }

        function closeModal() {
            document.getElementById('student-modal').style.display = 'none';
        }

        function resetFilters() {
            document.getElementById('filter-name').value = '';
            document.getElementById('filter-class').value = '';
            document.getElementById('filter-gender').value = '';
            document.getElementById('filter-status').value = '';
            loadStudents(1);
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

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN');
        }

        function getStatusText(status) {
            const statuses = {
                'danghoc': 'Đang học',
                'dathoi': 'Đã thôi học',
                'baoluu': 'Bảo lưu'
            };
            return statuses[status] || status;
        }

        function getStatusBadge(status) {
            const badges = {
                'danghoc': 'badge-success',
                'dathoi': 'badge-danger',
                'baoluu': 'badge-warning'
            };
            return badges[status] || '';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('student-modal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
