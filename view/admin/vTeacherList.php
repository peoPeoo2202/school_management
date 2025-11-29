<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    header("Location: ../../public/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Giáo viên - Quản trị viên</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
            color: #333;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .filters {
            padding: 25px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
            color: #495057;
        }

        .form-control {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .table-container {
            padding: 30px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-buttons .btn {
            padding: 6px 12px;
            font-size: 13px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #dee2e6;
        }

        .pagination button {
            padding: 8px 15px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pagination button:hover:not(:disabled) {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination .page-info {
            font-size: 14px;
            color: #495057;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 22px;
            font-weight: 600;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close:hover {
            transform: scale(1.2);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px 25px;
            background: #f8f9fa;
            border-radius: 0 0 10px 10px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
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
            color: #6c757d;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-male {
            background: #cce5ff;
            color: #004085;
        }

        .badge-female {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Quản lý Giáo viên</h1>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-success" onclick="openCreateModal()">➕ Thêm Giáo viên</button>
                <a href="index.php" class="btn btn-secondary">🏠 Dashboard</a>
            </div>
        </div>

        <div class="filters">
            <div class="filters-grid">
                <div class="form-group">
                    <label>Họ tên:</label>
                    <input type="text" id="filterName" class="form-control" placeholder="Tìm theo tên...">
                </div>
                <div class="form-group">
                    <label>Tổ bộ môn:</label>
                    <select id="filterDepartment" class="form-control">
                        <option value="">-- Tất cả --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Giới tính:</label>
                    <select id="filterGender" class="form-control">
                        <option value="">-- Tất cả --</option>
                        <option value="Nam">Nam</option>
                        <option value="Nữ">Nữ</option>
                    </select>
                </div>
            </div>
            <button class="btn btn-primary" onclick="loadTeachers()">🔍 Tìm kiếm</button>
        </div>

        <div class="table-container">
            <div id="alertContainer"></div>
            <div id="tableContent">
                <div class="loading">Đang tải dữ liệu...</div>
            </div>
        </div>

        <div class="pagination" id="paginationContainer" style="display: none;">
            <button onclick="changePage('prev')" id="btnPrev">« Trước</button>
            <span class="page-info" id="pageInfo"></span>
            <button onclick="changePage('next')" id="btnNext">Sau »</button>
        </div>
    </div>

    <!-- Modal Create/Edit -->
    <div id="teacherModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm Giáo viên</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="teacherForm">
                    <input type="hidden" id="teacherId">
                    
                    <div class="form-group">
                        <label>Họ tên: <span style="color: red;">*</span></label>
                        <input type="text" id="hoTen" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Ngày sinh:</label>
                        <input type="date" id="ngaySinh" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Giới tính:</label>
                        <select id="gioiTinh" class="form-control">
                            <option value="">-- Chọn --</option>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" id="email" class="form-control" placeholder="example@school.edu.vn">
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại:</label>
                        <input type="text" id="soDienThoai" class="form-control" placeholder="0901234567">
                    </div>

                    <div class="form-group">
                        <label>Tổ bộ môn:</label>
                        <input type="text" id="toBoMon" class="form-control" placeholder="VD: Toán, Văn, Anh...">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                <button class="btn btn-primary" onclick="saveTeacher()">💾 Lưu</button>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let isEditMode = false;

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDepartments();
            loadTeachers();
        });

        // Load danh sách tổ bộ môn
        function loadDepartments() {
            fetch('../../controller/cTeacherManagement.php?action=departments')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('filterDepartment');
                        data.data.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.toBoMon;
                            option.textContent = dept.toBoMon;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading departments:', error));
        }

        // Load danh sách giáo viên
        function loadTeachers(page = 1) {
            currentPage = page;
            const hoTen = document.getElementById('filterName').value;
            const toBoMon = document.getElementById('filterDepartment').value;
            const gioiTinh = document.getElementById('filterGender').value;

            const params = new URLSearchParams({
                action: 'list',
                page: page,
                limit: 20,
                hoTen: hoTen,
                toBoMon: toBoMon,
                gioiTinh: gioiTinh
            });

            fetch(`../../controller/cTeacherManagement.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data);
                    if (data.success) {
                        displayTeachers(data.data);
                        updatePagination(data.pagination);
                    } else {
                        showError('Không thể tải danh sách giáo viên');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Lỗi kết nối server');
                });
        }

        // Display teachers table
        function displayTeachers(teachers) {
            const container = document.getElementById('tableContent');
            
            if (teachers.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
                        <h3>Không tìm thấy giáo viên</h3>
                        <p>Thử thay đổi bộ lọc hoặc thêm giáo viên mới</p>
                    </div>
                `;
                return;
            }

            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>Mã GV</th>
                            <th>Họ tên</th>
                            <th>Ngày sinh</th>
                            <th>Giới tính</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Tổ bộ môn</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            teachers.forEach(teacher => {
                const genderBadge = teacher.gioiTinh === 'Nam' ? 'badge-male' : 'badge-female';
                html += `
                    <tr>
                        <td>${teacher.maGV}</td>
                        <td><strong>${teacher.hoTen || ''}</strong></td>
                        <td>${teacher.ngaySinh || ''}</td>
                        <td>${teacher.gioiTinh ? `<span class="badge ${genderBadge}">${teacher.gioiTinh}</span>` : ''}</td>
                        <td>${teacher.email || ''}</td>
                        <td>${teacher.soDienThoai || ''}</td>
                        <td>${teacher.toBoMon || ''}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-warning" onclick="openEditModal(${teacher.maGV})">✏️ Sửa</button>
                                <button class="btn btn-danger" onclick="deleteTeacher(${teacher.maGV}, '${teacher.hoTen}')">🗑️ Xóa</button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            container.innerHTML = html;
        }

        // Update pagination
        function updatePagination(pagination) {
            totalPages = pagination.total_pages;
            document.getElementById('pageInfo').textContent = 
                `Trang ${pagination.current_page} / ${pagination.total_pages} (Tổng: ${pagination.total_records} giáo viên)`;
            
            document.getElementById('btnPrev').disabled = pagination.current_page === 1;
            document.getElementById('btnNext').disabled = pagination.current_page === pagination.total_pages;
            document.getElementById('paginationContainer').style.display = 'flex';
        }

        // Change page
        function changePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                loadTeachers(currentPage - 1);
            } else if (direction === 'next' && currentPage < totalPages) {
                loadTeachers(currentPage + 1);
            }
        }

        // Open create modal
        function openCreateModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = 'Thêm Giáo viên';
            document.getElementById('teacherForm').reset();
            document.getElementById('teacherId').value = '';
            document.getElementById('teacherModal').style.display = 'block';
        }

        // Open edit modal
        function openEditModal(maGV) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = 'Sửa Giáo viên';
            
            fetch(`../../controller/cTeacherManagement.php?action=get&id=${maGV}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const teacher = data.data;
                        document.getElementById('teacherId').value = teacher.maGV;
                        document.getElementById('hoTen').value = teacher.hoTen || '';
                        document.getElementById('ngaySinh').value = teacher.ngaySinh || '';
                        document.getElementById('gioiTinh').value = teacher.gioiTinh || '';
                        document.getElementById('email').value = teacher.email || '';
                        document.getElementById('soDienThoai').value = teacher.soDienThoai || '';
                        document.getElementById('toBoMon').value = teacher.toBoMon || '';
                        document.getElementById('teacherModal').style.display = 'block';
                    } else {
                        showError('Không thể tải thông tin giáo viên');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Lỗi kết nối server');
                });
        }

        // Close modal
        function closeModal() {
            document.getElementById('teacherModal').style.display = 'none';
        }

        // Save teacher
        function saveTeacher() {
            const formData = new FormData();
            formData.append('hoTen', document.getElementById('hoTen').value);
            formData.append('ngaySinh', document.getElementById('ngaySinh').value);
            formData.append('gioiTinh', document.getElementById('gioiTinh').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('soDienThoai', document.getElementById('soDienThoai').value);
            formData.append('toBoMon', document.getElementById('toBoMon').value);

            let url = '../../controller/cTeacherManagement.php?action=create';
            if (isEditMode) {
                const maGV = document.getElementById('teacherId').value;
                url = `../../controller/cTeacherManagement.php?action=update&id=${maGV}`;
            }

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    closeModal();
                    loadTeachers(currentPage);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Lỗi kết nối server');
            });
        }

        // Delete teacher
        function deleteTeacher(maGV, hoTen) {
            if (!confirm(`Bạn có chắc muốn xóa giáo viên "${hoTen}"?\nThao tác này không thể hoàn tác.`)) {
                return;
            }

            fetch(`../../controller/cTeacherManagement.php?action=delete&id=${maGV}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    loadTeachers(currentPage);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Lỗi kết nối server');
            });
        }

        // Show success message
        function showSuccess(message) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success';
            alert.textContent = message;
            alert.style.display = 'block';
            
            const container = document.getElementById('alertContainer');
            container.innerHTML = '';
            container.appendChild(alert);
            
            setTimeout(() => alert.remove(), 5000);
        }

        // Show error message
        function showError(message) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-error';
            alert.textContent = message;
            alert.style.display = 'block';
            
            const container = document.getElementById('alertContainer');
            container.innerHTML = '';
            container.appendChild(alert);
            
            setTimeout(() => alert.remove(), 5000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('teacherModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
