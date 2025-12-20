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
    <title>Quản lý Thời khóa biểu - Quản trị viên</title>
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
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

        .badge-date {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-time {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-room {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .required {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Quản lý Thời khóa biểu</h1>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-success" onclick="openCreateModal()">➕ Thêm TKB</button>
                <a href="index.php" class="btn btn-secondary">🏠 Dashboard</a>
            </div>
        </div>

        <div class="filters">
            <div class="filters-grid">
                <div class="form-group">
                    <label>Khối:</label>
                    <select id="filterKhoi" class="form-control" onchange="filterClassesByKhoi()">
                        <option value="">-- Tất cả --</option>
                        <option value="6">Khối 6</option>
                        <option value="7">Khối 7</option>
                        <option value="8">Khối 8</option>
                        <option value="9">Khối 9</option>
                        <option value="10">Khối 10</option>
                        <option value="11">Khối 11</option>
                        <option value="12">Khối 12</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lớp học:</label>
                    <select id="filterClass" class="form-control">
                        <option value="">-- Tất cả --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Học kỳ:</label>
                    <select id="filterHocKy" class="form-control">
                        <option value="">-- Tất cả --</option>
                        <option value="1">Học kỳ 1</option>
                        <option value="2">Học kỳ 2</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Năm học:</label>
                    <input type="text" id="filterNamHoc" class="form-control" placeholder="VD: 2024-2025" value="2024-2025">
                </div>
                <div class="form-group">
                    <label>Môn học:</label>
                    <select id="filterSubject" class="form-control">
                        <option value="">-- Tất cả --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Giáo viên:</label>
                    <select id="filterTeacher" class="form-control">
                        <option value="">-- Tất cả --</option>
                    </select>
                </div>
            </div>
            <button class="btn btn-primary" onclick="loadTimetables()">🔍 Tìm kiếm</button>
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
    <div id="timetableModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm Thời khóa biểu</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="timetableForm">
                    <input type="hidden" id="timetableId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Học kỳ: <span class="required">*</span></label>
                            <select id="hocKy" class="form-control" required>
                                <option value="">-- Chọn học kỳ --</option>
                                <option value="1">Học kỳ 1</option>
                                <option value="2">Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Năm học: <span class="required">*</span></label>
                            <input type="text" id="namHoc" class="form-control" required placeholder="VD: 2024-2025" value="2024-2025">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Lớp học: <span class="required">*</span></label>
                            <select id="maLop" class="form-control" required onchange="checkTeachingAssignment()">
                                <option value="">-- Chọn lớp --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Môn học: <span class="required">*</span></label>
                            <select id="maMonHoc" class="form-control" required onchange="checkTeachingAssignment()">
                                <option value="">-- Chọn môn --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Giáo viên: <span class="required">*</span></label>
                            <select id="maGV" class="form-control" required onchange="checkTeachingAssignment()">
                                <option value="">-- Chọn giáo viên --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Thứ trong tuần: <span class="required">*</span></label>
                            <select id="dayOfWeek" class="form-control" required onchange="loadAvailableRooms()">
                                <option value="">-- Chọn thứ --</option>
                                <option value="2">Thứ 2</option>
                                <option value="3">Thứ 3</option>
                                <option value="4">Thứ 4</option>
                                <option value="5">Thứ 5</option>
                                <option value="6">Thứ 6</option>
                                <option value="7">Thứ 7 (Sáng)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Tiết học: <span class="required">*</span></label>
                            <select id="period" class="form-control" required onchange="loadAvailableRooms()">
                                <option value="">-- Chọn tiết --</option>
                                <option value="1">Tiết 1 (07:00-07:45)</option>
                                <option value="2">Tiết 2 (07:50-08:35)</option>
                                <option value="3">Tiết 3 (08:40-09:25)</option>
                                <option value="4">Tiết 4 (09:55-10:40)</option>
                                <option value="5">Tiết 5 (10:45-11:30)</option>
                                <option value="6">Tiết 6 (12:00-12:45)</option>
                                <option value="7">Tiết 7 (12:50-13:35)</option>
                                <option value="8">Tiết 8 (13:40-14:25)</option>
                                <option value="9">Tiết 9 (14:55-15:40)</option>
                                <option value="10">Tiết 10 (15:45-16:30)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Thời gian bắt đầu:</label>
                            <input type="time" id="thoiGianHoc" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Phòng học: <span class="required">*</span></label>
                        <select id="phong" class="form-control" required>
                            <option value="">-- Chọn phòng --</option>
                        </select>
                        <small style="color: #6c757d; display: block; margin-top: 5px;">
                            💡 Chỉ hiển thị phòng khả dụng (không bảo trì, không trùng lịch)
                        </small>
                    </div>

                    <div id="assignmentWarning" style="display: none; padding: 10px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; margin-top: 10px;">
                        <strong>⚠️ Cảnh báo:</strong> <span id="assignmentWarningText"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                <button class="btn btn-primary" onclick="saveTimetable()">💾 Lưu</button>
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
            loadTimetables();
        });

        // Load all dropdown data
        function loadDropdowns() {
            // Load classes for filter
            fetch('../../controller/cTimetableManagement.php?action=classes')
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
            fetch('../../controller/cTimetableManagement.php?action=subjects')
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

            // Load teachers
            fetch('../../controller/cTimetableManagement.php?action=teachers')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const filterSelect = document.getElementById('filterTeacher');
                        const formSelect = document.getElementById('maGV');
                        
                        data.data.forEach(teacher => {
                            const optFilter = document.createElement('option');
                            optFilter.value = teacher.maGV;
                            optFilter.textContent = `${teacher.hoTen}${teacher.toBoMon ? ' (' + teacher.toBoMon + ')' : ''}`;
                            filterSelect.appendChild(optFilter);

                            const optForm = document.createElement('option');
                            optForm.value = teacher.maGV;
                            optForm.textContent = `${teacher.hoTen}${teacher.toBoMon ? ' (' + teacher.toBoMon + ')' : ''}`;
                            formSelect.appendChild(optForm);
                        });
                    }
                })
                .catch(error => console.error('Error loading teachers:', error));
        }

        // Load timetables list
        function loadTimetables(page = 1) {
            currentPage = page;
            const maKhoi = document.getElementById('filterKhoi').value;
            const maLop = document.getElementById('filterClass').value;
            const maMonHoc = document.getElementById('filterSubject').value;
            const maGV = document.getElementById('filterTeacher').value;
            const hocKy = document.getElementById('filterHocKy').value;
            const namHoc = document.getElementById('filterNamHoc').value;

            const params = new URLSearchParams({
                action: 'list',
                page: page,
                limit: 20,
                maKhoi: maKhoi,
                maLop: maLop,
                maMonHoc: maMonHoc,
                maGV: maGV,
                hocKy: hocKy,
                namHoc: namHoc
            });

            fetch(`../../controller/cTimetableManagement.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data);
                    if (data.success) {
                        displayTimetables(data.data);
                        updatePagination(data.pagination);
                    } else {
                        showError('Không thể tải danh sách thời khóa biểu');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Lỗi kết nối server');
                });
        }

        // Display timetables table
        function displayTimetables(timetables) {
            const container = document.getElementById('tableContent');
            
            if (timetables.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
                        <h3>Không tìm thấy thời khóa biểu</h3>
                        <p>Thử thay đổi bộ lọc hoặc thêm lịch học mới</p>
                    </div>
                `;
                return;
            }

            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>Mã TKB</th>
                            <th>HK/Năm</th>
                            <th>Lớp</th>
                            <th>Môn học</th>
                            <th>Giáo viên</th>
                            <th>Thứ/Tiết</th>
                            <th>Thời gian</th>
                            <th>Phòng</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            timetables.forEach(item => {
                // Map day_of_week to Vietnamese
                const dayNames = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
                const dayOfWeek = item.day_of_week ? dayNames[item.day_of_week - 1] : '';
                const period = item.period || '';
                const timeSlot = dayOfWeek && period ? `${dayOfWeek} - T${period}` : (item.tietHoc || '');
                
                html += `
                    <tr>
                        <td>${item.maTKB}</td>
                        <td><span class="badge badge-date">HK${item.hocKy || ''} - ${item.namHoc || ''}</span></td>
                        <td><strong>${item.tenLop || ''}</strong></td>
                        <td>${item.tenMonHoc || ''}</td>
                        <td>${item.tenGiaoVien || ''}</td>
                        <td><span class="badge badge-time">${timeSlot}</span></td>
                        <td>${item.thoiGianHoc || ''}</td>
                        <td><span class="badge badge-room">${item.phong || ''}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-warning" onclick="openEditModal(${item.maTKB})">✏️ Sửa</button>
                                <button class="btn btn-danger" onclick="deleteTimetable(${item.maTKB}, '${item.tenMonHoc}')">🗑️ Xóa</button>
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
                `Trang ${pagination.current_page} / ${pagination.total_pages} (Tổng: ${pagination.total_records} lịch học)`;
            
            document.getElementById('btnPrev').disabled = pagination.current_page === 1;
            document.getElementById('btnNext').disabled = pagination.current_page === pagination.total_pages;
            document.getElementById('paginationContainer').style.display = 'flex';
        }

        // Change page
        function changePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                loadTimetables(currentPage - 1);
            } else if (direction === 'next' && currentPage < totalPages) {
                loadTimetables(currentPage + 1);
            }
        }

        // Open create modal
        function openCreateModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = 'Thêm Thời khóa biểu';
            document.getElementById('timetableForm').reset();
            document.getElementById('timetableId').value = '';
            document.getElementById('timetableModal').style.display = 'block';
        }

        // Open edit modal
        function openEditModal(maTKB) {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = 'Sửa Thời khóa biểu';
            
            fetch(`../../controller/cTimetableManagement.php?action=get&id=${maTKB}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.data;
                        document.getElementById('timetableId').value = item.maTKB;
                        document.getElementById('hocKy').value = item.hocKy || '1';
                        document.getElementById('namHoc').value = item.namHoc || '2024-2025';
                        document.getElementById('maLop').value = item.maLop || '';
                        document.getElementById('maMonHoc').value = item.maMonHoc || '';
                        document.getElementById('maGV').value = item.maGV || '';
                        document.getElementById('dayOfWeek').value = item.day_of_week || '';
                        document.getElementById('period').value = item.period || '';
                        document.getElementById('thoiGianHoc').value = item.thoiGianHoc || '';
                        
                        // Load available rooms first, then set phong
                        loadAvailableRooms().then(() => {
                            document.getElementById('phong').value = item.phong || '';
                        });
                        
                        document.getElementById('timetableModal').style.display = 'block';
                    } else {
                        showError('Không thể tải thông tin thời khóa biểu');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Lỗi kết nối server');
                });
        }

        // Close modal
        function closeModal() {
            document.getElementById('timetableModal').style.display = 'none';
        }

        // Load available rooms
        function loadAvailableRooms() {
            const dayOfWeek = document.getElementById('dayOfWeek').value;
            const period = document.getElementById('period').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;

            if (!dayOfWeek || !period || !hocKy || !namHoc) {
                return Promise.resolve(); // Chưa đủ thông tin
            }

            const params = new URLSearchParams({
                action: 'available-rooms',
                dayOfWeek: dayOfWeek,
                period: period,
                hocKy: hocKy,
                namHoc: namHoc
            });

            return fetch(`../../controller/cTimetableManagement.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    const phongSelect = document.getElementById('phong');
                    phongSelect.innerHTML = '<option value="">-- Chọn phòng --</option>';

                    if (data.success && data.data.length > 0) {
                        data.data.forEach(room => {
                            const option = document.createElement('option');
                            option.value = room.tenPhong;
                            option.textContent = `${room.tenPhong} (${room.loaiPhong || 'Phòng học'}, ${room.sucChua || 'N/A'} chỗ)`;
                            phongSelect.appendChild(option);
                        });
                    } else {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = '⚠️ Không có phòng khả dụng';
                        option.disabled = true;
                        phongSelect.appendChild(option);
                    }
                })
                .catch(error => {
                    console.error('Error loading rooms:', error);
                });
        }

        // Check teaching assignment
        function checkTeachingAssignment() {
            const maLop = document.getElementById('maLop').value;
            const maMonHoc = document.getElementById('maMonHoc').value;
            const maGV = document.getElementById('maGV').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;

            const warning = document.getElementById('assignmentWarning');
            
            if (!maLop || !maMonHoc || !maGV || !hocKy || !namHoc) {
                warning.style.display = 'none';
                return;
            }

            // Simple check - could be enhanced with API call
            warning.style.display = 'block';
            document.getElementById('assignmentWarningText').textContent = 
                'Đảm bảo giáo viên được phân công dạy môn này cho lớp đã chọn.';
        }

        // Filter classes by khoi
        function filterClassesByKhoi() {
            const khoi = document.getElementById('filterKhoi').value;
            const classSelect = document.getElementById('filterClass');
            const allOptions = Array.from(classSelect.options);

            allOptions.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                    return;
                }

                if (!khoi) {
                    option.style.display = 'block';
                } else {
                    const optionText = option.textContent;
                    if (optionText.includes(`Khối ${khoi}`)) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                }
            });
        }

        // Save timetable
        function saveTimetable() {
            const formData = new FormData();
            formData.append('hocKy', document.getElementById('hocKy').value);
            formData.append('namHoc', document.getElementById('namHoc').value);
            formData.append('maLop', document.getElementById('maLop').value);
            formData.append('maMonHoc', document.getElementById('maMonHoc').value);
            formData.append('maGV', document.getElementById('maGV').value);
            formData.append('day_of_week', document.getElementById('dayOfWeek').value);
            formData.append('period', document.getElementById('period').value);
            formData.append('thoiGianHoc', document.getElementById('thoiGianHoc').value);
            formData.append('phong', document.getElementById('phong').value);

            // Validation
            if (!formData.get('hocKy') || !formData.get('namHoc') || 
                !formData.get('maLop') || !formData.get('maMonHoc') || 
                !formData.get('maGV') || !formData.get('day_of_week') || 
                !formData.get('period') || !formData.get('phong')) {
                showError('Vui lòng điền đầy đủ các trường bắt buộc (*)');
                return;
            }

            let url = '../../controller/cTimetableManagement.php?action=create';
            if (isEditMode) {
                const maTKB = document.getElementById('timetableId').value;
                url = `../../controller/cTimetableManagement.php?action=update&id=${maTKB}`;
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
                    loadTimetables(currentPage);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Lỗi kết nối server');
            });
        }

        // Delete timetable
        function deleteTimetable(maTKB, tenMonHoc) {
            if (!confirm(`Bạn có chắc muốn xóa lịch học "${tenMonHoc}"?\nThao tác này không thể hoàn tác.`)) {
                return;
            }

            fetch(`../../controller/cTimetableManagement.php?action=delete&id=${maTKB}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    loadTimetables(currentPage);
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
            const modal = document.getElementById('timetableModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
