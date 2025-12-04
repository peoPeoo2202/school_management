<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập và quyền TTBM
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'ttbm') {
    header("Location: ../../public/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công chấm thi - TTBM</title>
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
            max-width: 1600px;
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
            white-space: nowrap;
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
            margin: 3% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
            max-height: 90vh;
            overflow-y: auto;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-progress {
            background: #cce5ff;
            color: #004085;
        }

        .badge-completed {
            background: #d4edda;
            color: #155724;
        }

        .required {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Phân công chấm thi</h1>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-success" onclick="openCreateModal()">➕ Thêm phân công</button>
                <a href="index.php" class="btn btn-secondary">🏠 Dashboard</a>
            </div>
        </div>

        <div class="filters">
            <div class="filters-grid">
                <div class="form-group">
                    <label>Giáo viên:</label>
                    <select id="filterTeacher" class="form-control">
                        <option value="">-- Tất cả --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lớp học:</label>
                    <select id="filterClass" class="form-control">
                        <option value="">-- Tất cả --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Môn học:</label>
                    <select id="filterSubject" class="form-control">
                        <option value="">-- Tất cả --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Loại kiểm tra:</label>
                    <select id="filterExamType" class="form-control">
                        <option value="">-- Tất cả --</option>
                        <option value="15phut">Kiểm tra 15 phút</option>
                        <option value="1tiet">Kiểm tra 1 tiết</option>
                        <option value="giuaky">Giữa kỳ</option>
                        <option value="cuoiky">Cuối kỳ</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Trạng thái:</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">-- Tất cả --</option>
                        <option value="pending">Chưa bắt đầu</option>
                        <option value="in_progress">Đang chấm</option>
                        <option value="completed">Hoàn thành</option>
                    </select>
                </div>
            </div>
            <button class="btn btn-primary" onclick="loadAssignments()">🔍 Tìm kiếm</button>
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
    <div id="assignmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm phân công chấm thi</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="assignmentForm">
                    <input type="hidden" id="assignmentId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Giáo viên: <span class="required">*</span></label>
                            <select id="maGV" class="form-control" required>
                                <option value="">-- Chọn giáo viên --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Lớp học: <span class="required">*</span></label>
                            <select id="maLop" class="form-control" required>
                                <option value="">-- Chọn lớp --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Môn học: <span class="required">*</span></label>
                            <select id="maMonHoc" class="form-control" required>
                                <option value="">-- Chọn môn --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Loại kiểm tra: <span class="required">*</span></label>
                            <select id="loaiKiemTra" class="form-control" required>
                                <option value="">-- Chọn loại --</option>
                                <option value="15phut">Kiểm tra 15 phút</option>
                                <option value="1tiet">Kiểm tra 1 tiết</option>
                                <option value="giuaky">Giữa kỳ</option>
                                <option value="cuoiky">Cuối kỳ</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày chấm:</label>
                            <input type="date" id="ngayCham" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Hình thức chấm:</label>
                            <select id="hinhThucCham" class="form-control">
                                <option value="">-- Chọn --</option>
                                <option value="Cá nhân">Cá nhân</option>
                                <option value="Tập thể">Tập thể</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Trạng thái:</label>
                        <select id="trangThai" class="form-control">
                            <option value="pending">Chưa bắt đầu</option>
                            <option value="in_progress">Đang chấm</option>
                            <option value="completed">Hoàn thành</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                <button class="btn btn-primary" onclick="saveAssignment()">💾 Lưu</button>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let isEditMode = false;

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDropdowns();
            loadAssignments();
        });

        // Load all dropdown data
        function loadDropdowns() {
            // Load teachers (chỉ của tổ mình)
            fetch('../../controller/cGradingAssignment.php?action=teachers')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const filterSelect = document.getElementById('filterTeacher');
                        const formSelect = document.getElementById('maGV');
                        
                        data.data.forEach(teacher => {
                            const optFilter = document.createElement('option');
                            optFilter.value = teacher.maGV;
                            optFilter.textContent = teacher.hoTen;
                            filterSelect.appendChild(optFilter);

                            const optForm = document.createElement('option');
                            optForm.value = teacher.maGV;
                            optForm.textContent = teacher.hoTen;
                            formSelect.appendChild(optForm);
                        });
                    }
                })
                .catch(error => console.error('Error loading teachers:', error));

            // Load classes
            fetch('../../controller/cGradingAssignment.php?action=classes')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const filterSelect = document.getElementById('filterClass');
                        const formSelect = document.getElementById('maLop');
                        
                        data.data.forEach(cls => {
                            const optFilter = document.createElement('option');
                            optFilter.value = cls.maLop;
                            optFilter.textContent = `${cls.khoiLop} - ${cls.tenLop}`;
                            filterSelect.appendChild(optFilter);

                            const optForm = document.createElement('option');
                            optForm.value = cls.maLop;
                            optForm.textContent = `${cls.khoiLop} - ${cls.tenLop}`;
                            formSelect.appendChild(optForm);
                        });
                    }
                })
                .catch(error => console.error('Error loading classes:', error));

            // Load subjects
            fetch('../../controller/cGradingAssignment.php?action=subjects')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const filterSelect = document.getElementById('filterSubject');
                        const formSelect = document.getElementById('maMonHoc');
                        
                        data.data.forEach(subject => {
                            const optFilter = document.createElement('option');
                            optFilter.value = subject.maMonHoc;
                            optFilter.textContent = subject.tenMonHoc;
                            filterSelect.appendChild(optFilter);

                            const optForm = document.createElement('option');
                            optForm.value = subject.maMonHoc;
                            optForm.textContent = subject.tenMonHoc;
                            formSelect.appendChild(optForm);
                        });
                    }
                })
                .catch(error => console.error('Error loading subjects:', error));
        }

        // Load assignments list
        function loadAssignments(page = 1) {
            currentPage = page;
            const maGV = document.getElementById('filterTeacher').value;
            const maLop = document.getElementById('filterClass').value;
            const maMonHoc = document.getElementById('filterSubject').value;
            const loaiKiemTra = document.getElementById('filterExamType').value;
            const trangThai = document.getElementById('filterStatus').value;

            const params = new URLSearchParams({
                action: 'list',
                page: page,
                limit: 20,
                maGV: maGV,
                maLop: maLop,
                maMonHoc: maMonHoc,
                loaiKiemTra: loaiKiemTra,
                trangThai: trangThai
            });

            fetch(`../../controller/cGradingAssignment.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data);
                    if (data.success) {
                        displayAssignments(data.data);
                        updatePagination(data.pagination);
                    } else {
                        showError('Không thể tải danh sách phân công');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Lỗi kết nối server');
                });
        }

        // Display assignments table
        function displayAssignments(assignments) {
            const container = document.getElementById('tableContent');
            
            if (assignments.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
                        <h3>Không tìm thấy phân công</h3>
                        <p>Thử thay đổi bộ lọc hoặc thêm phân công mới</p>
                    </div>
                `;
                return;
            }

            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>Mã PC</th>
                            <th>Giáo viên</th>
                            <th>Lớp</th>
                            <th>Môn học</th>
                            <th>Loại kiểm tra</th>
                            <th>Ngày chấm</th>
                            <th>Hình thức</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            assignments.forEach(item => {
                const statusClass = item.trangThai === 'completed' ? 'badge-completed' : 
                                  (item.trangThai === 'in_progress' ? 'badge-progress' : 'badge-pending');
                const statusText = item.trangThai === 'completed' ? 'Hoàn thành' : 
                                 (item.trangThai === 'in_progress' ? 'Đang chấm' : 'Chưa bắt đầu');
                
                html += `
                    <tr>
                        <td>${item.maPhanCong}</td>
                        <td><strong>${item.tenGiaoVien || ''}</strong></td>
                        <td>${item.tenLop || ''}</td>
                        <td>${item.tenMonHoc || ''}</td>
                        <td>${item.loaiKiemTra || ''}</td>
                        <td>${item.ngayCham || ''}</td>
                        <td>${item.hinhThucCham || ''}</td>
                        <td><span class="badge ${statusClass}">${statusText}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-warning" onclick="openEditModal(${item.maPhanCong})">✏️ Sửa</button>
                                <button class="btn btn-danger" onclick="deleteAssignment(${item.maPhanCong}, '${item.tenGiaoVien}')">🗑️ Xóa</button>
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
                `Trang ${pagination.current_page} / ${pagination.total_pages} (Tổng: ${pagination.total_records} phân công)`;
            
            document.getElementById('btnPrev').disabled = pagination.current_page === 1;
            document.getElementById('btnNext').disabled = pagination.current_page === pagination.total_pages;
            document.getElementById('paginationContainer').style.display = 'flex';
        }

        // Change page
        function changePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                loadAssignments(currentPage - 1);
            } else if (direction === 'next' && currentPage < totalPages) {
                loadAssignments(currentPage + 1);
            }
        }

        // Open create modal
        function openCreateModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = 'Thêm phân công chấm thi';
            document.getElementById('assignmentForm').reset();
            document.getElementById('assignmentId').value = '';
            document.getElementById('assignmentModal').style.display = 'block';
        }

        // Open edit modal
        function openEditModal(maPhanCong) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = 'Sửa phân công chấm thi';
            
            fetch(`../../controller/cGradingAssignment.php?action=get&id=${maPhanCong}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.data;
                        document.getElementById('assignmentId').value = item.maPhanCong;
                        document.getElementById('maGV').value = item.maGV || '';
                        document.getElementById('maLop').value = item.maLop || '';
                        document.getElementById('maMonHoc').value = item.maMonHoc || '';
                        document.getElementById('loaiKiemTra').value = item.loaiKiemTra || '';
                        document.getElementById('ngayCham').value = item.ngayCham || '';
                        document.getElementById('hinhThucCham').value = item.hinhThucCham || '';
                        document.getElementById('trangThai').value = item.trangThai || 'pending';
                        document.getElementById('assignmentModal').style.display = 'block';
                    } else {
                        showError('Không thể tải thông tin phân công');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Lỗi kết nối server');
                });
        }

        // Close modal
        function closeModal() {
            document.getElementById('assignmentModal').style.display = 'none';
        }

        // Save assignment
        function saveAssignment() {
            const formData = new FormData();
            formData.append('maGV', document.getElementById('maGV').value);
            formData.append('maLop', document.getElementById('maLop').value);
            formData.append('maMonHoc', document.getElementById('maMonHoc').value);
            formData.append('loaiKiemTra', document.getElementById('loaiKiemTra').value);
            formData.append('ngayCham', document.getElementById('ngayCham').value);
            formData.append('hinhThucCham', document.getElementById('hinhThucCham').value);
            formData.append('trangThai', document.getElementById('trangThai').value);

            let url = '../../controller/cGradingAssignment.php?action=create';
            if (isEditMode) {
                const maPhanCong = document.getElementById('assignmentId').value;
                url = `../../controller/cGradingAssignment.php?action=update&id=${maPhanCong}`;
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
                    loadAssignments(currentPage);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Lỗi kết nối server');
            });
        }

        // Delete assignment
        function deleteAssignment(maPhanCong, tenGiaoVien) {
            if (!confirm(`Bạn có chắc muốn xóa phân công của giáo viên "${tenGiaoVien}"?\nThao tác này không thể hoàn tác.`)) {
                return;
            }

            fetch(`../../controller/cGradingAssignment.php?action=delete&id=${maPhanCong}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    loadAssignments(currentPage);
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
            const modal = document.getElementById('assignmentModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
