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
    <title>Duyệt Đề Thi - TTBM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header-content h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header-content p {
            opacity: 0.9;
            font-size: 14px;
        }

        .page-header-actions {
            display: flex;
            gap: 10px;
        }

        .btn-back, .btn-logout {
            padding: 10px 20px;
            border: 2px solid white;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: white;
            color: #667eea;
        }

        .btn-logout {
            background: rgba(231, 76, 60, 0.2);
            border-color: #e74c3c;
        }

        .btn-logout:hover {
            background: #e74c3c;
            border-color: #e74c3c;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-card.pending { border-left-color: #f39c12; }
        .stat-card.approved { border-left-color: #27ae60; }
        .stat-card.rejected { border-left-color: #e74c3c; }

        .toolbar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
        }

        select, input[type="text"] {
            padding: 10px 15px;
            border: 1px solid #dfe6e9;
            border-radius: 6px;
            font-size: 14px;
            min-width: 180px;
            transition: all 0.3s;
        }

        select:focus, input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #d68910;
        }

        .btn-info {
            background: #3498db;
            color: white;
        }

        .btn-info:hover {
            background: #2980b9;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }

        tbody tr {
            transition: background 0.3s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-approved {
            background: #d4edda;
            color: #155724;
        }

        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
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
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }

        .modal-header h2 {
            color: #2c3e50;
            font-size: 24px;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #7f8c8d;
            background: none;
            border: none;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .close-modal:hover {
            background: #ecf0f1;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #dfe6e9;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group.required label::after {
            content: " *";
            color: #e74c3c;
        }

        .modal-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .exam-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
        }

        .info-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .info-item .value {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .filters {
                flex-direction: column;
            }

            .info-row {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-content">
                <h1>📝 Duyệt Đề Thi</h1>
                <p>Tổ trưởng bộ môn - Xem và phê duyệt đề thi của giáo viên</p>
            </div>
            <div class="page-header-actions">
                <a href="index.php" class="btn-back" onclick="return confirmExit()">← Quay lại</a>
                <a href="../../public/logout.php" class="btn-logout" onclick="return confirmExit()">🚪 Đăng xuất</a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Tổng số đề thi</h3>
                <div class="number" id="totalExams">0</div>
            </div>
            <div class="stat-card pending">
                <h3>Chờ duyệt</h3>
                <div class="number" id="pendingExams">0</div>
            </div>
            <div class="stat-card approved">
                <h3>Đã duyệt</h3>
                <div class="number" id="approvedExams">0</div>
            </div>
            <div class="stat-card rejected">
                <h3>Từ chối</h3>
                <div class="number" id="rejectedExams">0</div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="filters">
                <div class="filter-group">
                    <label>Trạng thái</label>
                    <select id="filterStatus">
                        <option value="">Tất cả</option>
                        <option value="Chuaduyet" selected>Chờ duyệt</option>
                        <option value="Daduyet">Đã duyệt</option>
                        <option value="Dachon">Đã chọn</option>
                        <option value="Tuchoi">Từ chối</option>
                    </select>
                </div>
            </div>
            <div style="color: #7f8c8d; font-style: italic;">
                * Hiển thị đề thi do giáo viên trong tổ bộ môn nộp
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table id="examTable">
                <thead>
                    <tr>
                        <th>Mã ĐT</th>
                        <th>Tên đề thi</th>
                        <th>Môn học</th>
                        <th>GV nộp</th>
                        <th>Kỳ thi</th>
                        <th>File đính kèm</th>
                        <th>Trạng thái</th>
                        <th>Ngày nộp</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="examTableBody">
                    <tr>
                        <td colspan="9" class="loading">Đang tải dữ liệu...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Xem chi tiết -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('detailModal')">&times;</button>
            <div class="modal-header">
                <h2>Chi tiết đề thi</h2>
            </div>
            <div id="examDetail"></div>
            <div class="modal-footer">
                <button class="btn" onclick="closeModal('detailModal')">Đóng</button>
            </div>
        </div>
    </div>

    <!-- Modal: Từ chối -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('rejectModal')">&times;</button>
            <div class="modal-header">
                <h2>Từ chối đề thi</h2>
            </div>
            <form id="rejectForm">
                <input type="hidden" id="rejectExamId">
                <div class="form-group required">
                    <label>Lý do từ chối</label>
                    <textarea id="lyDoTuChoi" required placeholder="Nhập lý do từ chối..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('rejectModal')">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xác nhận từ chối</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_URL = '../../controller/cExamApproval.php';
        let subjects = [];
        let isProcessing = false; // Đánh dấu đang xử lý duyệt/từ chối

        // Xác nhận thoát khi đang xử lý
        function confirmExit() {
            if (isProcessing) {
                return confirm('Bạn đang trong quá trình duyệt đề thi. Bạn có chắc muốn thoát?');
            }
            return true;
        }

        // Cảnh báo khi đóng tab/cửa sổ
        window.addEventListener('beforeunload', function(e) {
            if (isProcessing) {
                e.preventDefault();
                e.returnValue = 'Bạn đang trong quá trình duyệt đề thi. Bạn có chắc muốn thoát?';
                return e.returnValue;
            }
        });

        // Load dữ liệu khi trang tải
        document.addEventListener('DOMContentLoaded', function() {
            loadStatistics();
            loadExams();

            // Filter change
            document.getElementById('filterStatus').addEventListener('change', loadExams);

            // Form submit
            document.getElementById('rejectForm').addEventListener('submit', submitReject);
        });

        // Load thống kê
        async function loadStatistics() {
            try {
                const response = await fetch(`${API_URL}?action=statistics`, {
                    credentials: 'same-origin'
                });
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('totalExams').textContent = result.data.total || 0;
                    document.getElementById('pendingExams').textContent = result.data.pending || 0;
                    document.getElementById('approvedExams').textContent = result.data.approved || 0;
                    document.getElementById('rejectedExams').textContent = result.data.rejected || 0;
                }
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        // Load danh sách đề thi
        async function loadExams() {
            const status = document.getElementById('filterStatus').value;
            const tbody = document.getElementById('examTableBody');
            
            tbody.innerHTML = '<tr><td colspan="9" class="loading">Đang tải dữ liệu...</td></tr>';

            try {
                let url = `${API_URL}?action=list`;
                if (status) {
                    url += `&trangThai=${status}`;
                }

                const response = await fetch(url, {
                    credentials: 'same-origin'
                });
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    isProcessing = false; // Reset processing state after load
                    tbody.innerHTML = result.data.map(exam => `
                        <tr>
                            <td>${exam.maDeThi}</td>
                            <td>${exam.tenDeThi}</td>
                            <td>${exam.tenMonHoc}</td>
                            <td>${exam.tenGVNop || '-'}</td>
                            <td>${exam.tenKyThi || (exam.hocKy ? `HK${exam.hocKy} - ${exam.namHoc}` : '-')}</td>
                            <td>
                                ${exam.tenFile ? `
                                    <a href="../../uploads/exams/${exam.duongDan || exam.tenFile}" 
                                       target="_blank" class="btn btn-sm btn-info" 
                                       title="Tải file đề thi">
                                        📄 ${exam.tenFile.length > 20 ? exam.tenFile.substring(0,20) + '...' : exam.tenFile}
                                    </a>
                                ` : '<span style="color:#999">Không có file</span>'}
                            </td>
                            <td>${getStatusBadge(exam.trangThai)}</td>
                            <td>${exam.ngayTao || '-'}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-info btn-sm" onclick="viewDetail(${exam.maDeThi})" title="Xem chi tiết">
                                        👁️
                                    </button>
                                    ${exam.trangThai === 'Chuaduyet' ? `
                                        <button class="btn btn-success btn-sm" onclick="approveExam(${exam.maDeThi})" title="Duyệt">
                                            ✅ Duyệt
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="openRejectModal(${exam.maDeThi})" title="Từ chối">
                                            ❌ Từ chối
                                        </button>
                                    ` : ''}
                                </div>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="9" class="empty-state">
                                <div>📋</div>
                                <p>Không có đề thi ${status === 'Chuaduyet' ? 'chờ duyệt' : ''}</p>
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error loading exams:', error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <p style="color: #e74c3c;">Lỗi tải dữ liệu</p>
                        </td>
                    </tr>
                `;
            }
        }

        // Xem chi tiết
        async function viewDetail(id) {
            try {
                const response = await fetch(`${API_URL}?action=detail&id=${id}`, {
                    credentials: 'same-origin'
                });
                const result = await response.json();

                if (result.success) {
                    const exam = result.data;
                    const detailHTML = `
                        <div class="info-row">
                            <div class="info-item">
                                <label>Mã đề thi</label>
                                <div class="value">${exam.maDeThi}</div>
                            </div>
                            <div class="info-item">
                                <label>Tên đề thi</label>
                                <div class="value">${exam.tenDeThi}</div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-item">
                                <label>Môn học</label>
                                <div class="value">${exam.tenMonHoc}</div>
                            </div>
                            <div class="info-item">
                                <label>Giáo viên nộp</label>
                                <div class="value">${exam.tenGVNop || '-'} ${exam.emailGV ? `(${exam.emailGV})` : ''}</div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-item">
                                <label>Kỳ thi</label>
                                <div class="value">${exam.tenKyThi || '-'}</div>
                            </div>
                            <div class="info-item">
                                <label>Học kỳ / Năm học</label>
                                <div class="value">${exam.hocKy ? `HK${exam.hocKy} - ${exam.namHoc}` : '-'}</div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-item">
                                <label>Loại đề thi</label>
                                <div class="value">${getLoaiDeThiText(exam.loaiDeThi)}</div>
                            </div>
                            <div class="info-item">
                                <label>Trạng thái</label>
                                <div class="value">${getStatusBadge(exam.trangThai)}</div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-item">
                                <label>Ngày nộp</label>
                                <div class="value">${exam.ngayTao || '-'}</div>
                            </div>
                            <div class="info-item">
                                <label>Ngày duyệt</label>
                                <div class="value">${exam.ngayDuyet || '-'}</div>
                            </div>
                        </div>
                        ${exam.tenFile ? `
                            <div class="form-group">
                                <label>📎 File đề thi đính kèm</label>
                                <div class="exam-content">
                                    <a href="../../uploads/exams/${exam.duongDan || exam.tenFile}" 
                                       target="_blank" class="btn btn-primary">
                                        📄 Tải xuống: ${exam.tenFile}
                                    </a>
                                </div>
                            </div>
                        ` : ''}
                        ${exam.moTa ? `
                            <div class="form-group">
                                <label>Mô tả / Ghi chú</label>
                                <div class="exam-content">${exam.moTa}</div>
                            </div>
                        ` : ''}
                        ${exam.lyDoDuyet && exam.trangThai === 'Tuchoi' ? `
                            <div class="form-group">
                                <label>❌ Lý do từ chối</label>
                                <div class="exam-content" style="background: #f8d7da; color: #721c24;">
                                    ${exam.lyDoDuyet}
                                </div>
                            </div>
                        ` : ''}
                    `;

                    document.getElementById('examDetail').innerHTML = detailHTML;
                    
                    // Nếu đang chờ duyệt, đánh dấu đang xử lý
                    if (exam.trangThai === 'Chuaduyet') {
                        isProcessing = true;
                    }
                    
                    openModal('detailModal');
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error loading detail:', error);
                alert('Lỗi tải dữ liệu');
            }
        }

        // Duyệt đề thi
        async function approveExam(id) {
            if (!confirm('Bạn có chắc muốn DUYỆT đề thi này?\n\nSau khi duyệt, đề thi sẽ có thể được sử dụng cho kỳ thi.')) {
                return;
            }

            isProcessing = true;
            
            try {
                const response = await fetch(`${API_URL}?action=approve&id=${id}`, {
                    method: 'POST',
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.message + '\n\nThông báo đã được gửi đến giáo viên nộp đề.');
                    isProcessing = false;
                    loadExams();
                    loadStatistics();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                console.error('Error approving exam:', error);
                alert('❌ Lỗi hệ thống khi duyệt đề thi. Vui lòng thử lại sau.');
            }
        }

        // Mở modal từ chối
        function openRejectModal(id) {
            document.getElementById('rejectExamId').value = id;
            document.getElementById('lyDoTuChoi').value = '';
            isProcessing = true;
            openModal('rejectModal');
        }

        // Submit từ chối
        async function submitReject(e) {
            e.preventDefault();

            const id = document.getElementById('rejectExamId').value;
            const lyDo = document.getElementById('lyDoTuChoi').value.trim();

            if (!lyDo) {
                alert('Vui lòng nhập lý do từ chối');
                return;
            }

            try {
                const response = await fetch(`${API_URL}?action=reject&id=${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ lyDo })
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.message + '\n\nThông báo đã được gửi đến giáo viên nộp đề để chỉnh sửa và nộp lại.');
                    isProcessing = false;
                    closeModal('rejectModal');
                    loadExams();
                    loadStatistics();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                console.error('Error rejecting exam:', error);
                alert('❌ Lỗi hệ thống khi từ chối đề thi. Vui lòng thử lại sau.');
            }
        }

        // Utility functions
        function getStatusBadge(status) {
            const badges = {
                'Chuaduyet': '<span class="badge badge-pending">Chờ duyệt</span>',
                'Daduyet': '<span class="badge badge-approved">Đã duyệt</span>',
                'Dachon': '<span class="badge badge-info">Đã chọn</span>',
                'Tuchoi': '<span class="badge badge-rejected">Từ chối</span>'
            };
            return badges[status] || status;
        }

        function getLoaiDeThiText(loai) {
            const texts = {
                '15phut': '15 phút',
                '1tiet': '1 tiết',
                'giuaky': 'Giữa kỳ',
                'cuoiky': 'Cuối kỳ',
                'de-thi': 'Đề thi'
            };
            return texts[loai] || loai || '-';
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            // Reset processing state khi đóng modal
            if (modalId === 'detailModal' || modalId === 'rejectModal') {
                isProcessing = false;
            }
        }

        // Close modal khi click outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                if (isProcessing) {
                    if (!confirm('Bạn đang xem/duyệt đề thi. Bạn có chắc muốn đóng?')) {
                        return;
                    }
                }
                event.target.classList.remove('active');
                isProcessing = false;
            }
        }
    </script>
</body>
</html>