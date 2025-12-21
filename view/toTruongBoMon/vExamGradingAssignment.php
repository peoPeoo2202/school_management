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
    <title>Phân công chấm điểm - TTBM</title>
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
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header-content h1 {
            font-size: 26px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header-content p {
            opacity: 0.9;
            font-size: 14px;
        }

        .page-header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.5);
        }

        .btn-back:hover {
            background: white;
            color: #667eea;
        }

        .btn-logout {
            background: rgba(231, 76, 60, 0.2);
            color: white;
            border: 2px solid rgba(231, 76, 60, 0.5);
        }

        .btn-logout:hover {
            background: #e74c3c;
            border-color: #e74c3c;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219a52;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        /* Steps indicator */
        .steps-container {
            background: white;
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .steps {
            display: flex;
            justify-content: space-between;
            position: relative;
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: #e0e0e0;
            z-index: 0;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }

        .step.active .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .step.completed .step-number {
            background: #27ae60;
            color: white;
        }

        .step-label {
            font-size: 13px;
            color: #999;
            text-align: center;
            font-weight: 500;
        }

        .step.active .step-label,
        .step.completed .step-label {
            color: #333;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 18px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        /* Form */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Table */
        .table-container {
            overflow-x: auto;
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
            border-bottom: 1px solid #eee;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-info {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-warning {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-danger {
            background: #ffebee;
            color: #c62828;
        }

        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }

        .stat-card h3 {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .stat-card.pending { border-left-color: #f39c12; }
        .stat-card.success { border-left-color: #27ae60; }
        .stat-card.info { border-left-color: #3498db; }

        /* Checkbox */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* Teacher list */
        .teacher-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }

        .teacher-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .teacher-card:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .teacher-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        .teacher-card.assigned {
            border-color: #27ae60;
            background: rgba(39, 174, 96, 0.1);
        }

        .teacher-card .teacher-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .teacher-card .teacher-info {
            font-size: 13px;
            color: #666;
        }

        /* Modal */
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
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .modal-header h2 {
            color: #333;
            font-size: 20px;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            background: none;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .close-modal:hover {
            background: #f0f0f0;
            color: #333;
        }

        .modal-footer {
            margin-top: 25px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-warning {
            background: #fff3e0;
            color: #f57c00;
            border: 1px solid #ffe0b2;
        }

        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }

        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        .loading .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #e0e0e0;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .page-header-actions {
                justify-content: center;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .steps {
                flex-direction: column;
                gap: 20px;
            }

            .steps::before {
                display: none;
            }
        }

        /* Filter toggle */
        .filter-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-toggle:hover {
            background: #e9ecef;
        }

        .filter-toggle input {
            width: 18px;
            height: 18px;
        }

        /* Section hidden */
        .section-hidden {
            display: none;
        }

        .section-visible {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="page-header-content">
                <h1>📝 Phân công chấm điểm</h1>
                <p>Phân công giáo viên chấm điểm theo kỳ thi</p>
            </div>
            <div class="page-header-actions">
                <a href="vExamAssignment.php" class="btn btn-back">← Quay lại</a>
                <a href="../../public/logout.php" class="btn btn-logout">🚪 Đăng xuất</a>
            </div>
        </div>

        <!-- Steps -->
        <div class="steps-container">
            <div class="steps">
                <div class="step active" id="step1">
                    <div class="step-number">1</div>
                    <div class="step-label">Chọn kỳ thi</div>
                </div>
                <div class="step" id="step2">
                    <div class="step-number">2</div>
                    <div class="step-label">Tìm kiếm</div>
                </div>
                <div class="step" id="step3">
                    <div class="step-number">3</div>
                    <div class="step-label">Xem bài thi</div>
                </div>
                <div class="step" id="step4">
                    <div class="step-number">4</div>
                    <div class="step-label">Phân công GV</div>
                </div>
                <div class="step" id="step5">
                    <div class="step-number">5</div>
                    <div class="step-label">Xác nhận</div>
                </div>
            </div>
        </div>

        <!-- Step 1: Chọn kỳ thi -->
        <div class="card section-visible" id="section-exam-period">
            <div class="card-header">
                <h2>📋 Bước 1: Chọn kỳ thi</h2>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Năm học</label>
                        <select class="form-control" id="filterNamHoc">
                            <option value="">-- Tất cả --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Học kỳ</label>
                        <select class="form-control" id="filterHocKy">
                            <option value="">-- Tất cả --</option>
                            <option value="1">Học kỳ 1</option>
                            <option value="2">Học kỳ 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Khối</label>
                        <select class="form-control" id="filterKhoi">
                            <option value="">-- Tất cả --</option>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Kỳ thi</th>
                                <th>Loại</th>
                                <th>Khối</th>
                                <th>Học kỳ</th>
                                <th>Năm học</th>
                                <th>Thời gian</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="examPeriodList">
                            <tr>
                                <td colspan="8" class="loading">
                                    <div class="spinner"></div>
                                    <p>Đang tải danh sách kỳ thi...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Step 2: Tìm kiếm -->
        <div class="card section-hidden" id="section-search">
            <div class="card-header">
                <h2>🔍 Bước 2: Tìm kiếm bài thi cần chấm</h2>
                <button class="btn btn-sm btn-back" onclick="goToStep(1)">← Quay lại chọn kỳ thi</button>
            </div>
            <div class="card-body">
                <div class="alert alert-info" id="selectedExamInfo">
                    Kỳ thi đã chọn: <strong id="selectedExamName">-</strong>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Lớp <span style="color:red">*</span></label>
                        <select class="form-control" id="searchLop" required>
                            <option value="">-- Chọn lớp --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Môn thi <span style="color:red">*</span></label>
                        <select class="form-control" id="searchMonHoc" required>
                            <option value="">-- Chọn môn thi --</option>
                        </select>
                    </div>
                    <div class="form-group" style="justify-content: flex-end;">
                        <button class="btn btn-primary" onclick="searchExamPapers()">
                            🔍 Tìm kiếm
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Danh sách bài thi cần chấm -->
        <div class="card section-hidden" id="section-exam-papers">
            <div class="card-header">
                <h2>📄 Bước 3: Thông tin bài thi cần chấm</h2>
                <button class="btn btn-sm btn-back" onclick="goToStep(2)">← Quay lại tìm kiếm</button>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-card info">
                        <h3>Tổng số bài thi</h3>
                        <div class="number" id="totalPapers">0</div>
                    </div>
                    <div class="stat-card">
                        <h3>Kỳ thi</h3>
                        <div class="number" style="font-size:16px" id="examPeriodName">-</div>
                    </div>
                    <div class="stat-card pending">
                        <h3>Loại kỳ thi</h3>
                        <div class="number" style="font-size:16px" id="examPeriodType">-</div>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <strong>📌 Thông tin:</strong> Lớp <span id="infoLop">-</span> | Môn <span id="infoMonHoc">-</span> | Số học sinh: <span id="infoSoHS">0</span>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <button class="btn btn-success" onclick="goToStep(4)">
                        👨‍🏫 Phân công giáo viên chấm thi →
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 4: Phân công giáo viên -->
        <div class="card section-hidden" id="section-teachers">
            <div class="card-header">
                <h2>👨‍🏫 Bước 4: Chọn giáo viên chấm thi</h2>
                <button class="btn btn-sm btn-back" onclick="goToStep(3)">← Quay lại</button>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>💡 Lưu ý:</strong> Bạn có thể chọn tối đa 2 giáo viên để phân công chấm thi.
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="filter-toggle">
                        <input type="checkbox" id="filterUnassigned" onchange="loadTeachers()">
                        <span>Chỉ hiển thị giáo viên chưa được phân công</span>
                    </label>
                </div>

                <div class="teacher-list" id="teacherList">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Đang tải danh sách giáo viên...</p>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 25px;">
                    <button class="btn btn-success" onclick="goToStep(5)" id="btnConfirmTeachers" disabled>
                        ✓ Xác nhận phân công (<span id="selectedCount">0</span> GV)
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 5: Xác nhận -->
        <div class="card section-hidden" id="section-confirm">
            <div class="card-header">
                <h2>✅ Bước 5: Xác nhận phân công</h2>
                <button class="btn btn-sm btn-back" onclick="goToStep(4)">← Quay lại</button>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>⚠️ Xác nhận:</strong> Vui lòng kiểm tra lại thông tin trước khi xác nhận phân công.
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Thông tin</th>
                                <th>Giá trị</th>
                            </tr>
                        </thead>
                        <tbody id="confirmInfo">
                        </tbody>
                    </table>
                </div>

                <div style="text-align: center; margin-top: 25px;">
                    <button class="btn btn-danger" onclick="goToStep(4)" style="margin-right: 10px;">
                        ✕ Hủy
                    </button>
                    <button class="btn btn-success" onclick="submitAssignment()" id="btnSubmit">
                        ✓ Xác nhận phân công
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thông báo thành công -->
    <div class="modal" id="successModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✅ Phân công thành công!</h2>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <strong>Thông báo:</strong> Phân công giáo viên chấm thi thành công. Thông báo đã được gửi đến giáo viên được phân công.
                </div>
                <div id="successDetails"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="resetAndGoToStart()">
                    Phân công tiếp
                </button>
                <a href="index.php" class="btn btn-success">
                    Về Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        // Base URL từ PHP - lấy đường dẫn đến thư mục school_management
        const API_URL = '/code/school_management/controller/cExamGradingAssignment.php';
        
        // State
        let selectedExamPeriod = null;
        let selectedClass = null;
        let selectedSubject = null;
        let selectedTeachers = [];
        let examPaperCount = 0;
        let currentStep = 1;

        // Khởi tạo
        document.addEventListener('DOMContentLoaded', function() {
            loadSchoolYears();
            loadGrades();
            loadExamPeriods();
            loadSubjects();
        });

        // ===== LOAD DATA =====
        
        async function loadSchoolYears() {
            try {
                const response = await fetch(`${API_URL}?action=school-years`, {credentials: 'same-origin'});
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById('filterNamHoc');
                    result.data.forEach(year => {
                        const option = document.createElement('option');
                        option.value = year;
                        option.textContent = year;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading school years:', error);
            }
        }

        async function loadGrades() {
            try {
                const response = await fetch(`${API_URL}?action=grades`, {credentials: 'same-origin'});
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById('filterKhoi');
                    result.data.forEach(grade => {
                        const option = document.createElement('option');
                        option.value = grade.maKhoi;
                        option.textContent = `Khối ${grade.khoiLop}`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading grades:', error);
            }
        }

        async function loadExamPeriods() {
            const namHoc = document.getElementById('filterNamHoc').value;
            const hocKy = document.getElementById('filterHocKy').value;
            const maKhoi = document.getElementById('filterKhoi').value;

            let url = `${API_URL}?action=exam-periods`;
            if (namHoc) url += `&namHoc=${namHoc}`;
            if (hocKy) url += `&hocKy=${hocKy}`;
            if (maKhoi) url += `&maKhoi=${maKhoi}`;

            try {
                const response = await fetch(url, {credentials: 'same-origin'});
                const result = await response.json();
                
                const tbody = document.getElementById('examPeriodList');
                
                if (result.success && result.data.length > 0) {
                    tbody.innerHTML = result.data.map(exam => `
                        <tr>
                            <td><strong>${exam.tenKyThi}</strong></td>
                            <td><span class="badge badge-info">${exam.loaiKyThi}</span></td>
                            <td>Khối ${exam.khoiLop}</td>
                            <td>HK${exam.hocKy}</td>
                            <td>${exam.namHoc}</td>
                            <td>${formatDate(exam.ngayBatDau)} - ${formatDate(exam.ngayKetThuc)}</td>
                            <td>${getStatusBadge(exam.trangThai)}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="selectExamPeriod(${exam.maKyThi}, '${escapeHtml(exam.tenKyThi)}', '${exam.loaiKyThi}', ${exam.maKhoi})">
                                    Chọn →
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" class="empty-state">
                                <div class="icon">📋</div>
                                <h3>Không có kỳ thi nào</h3>
                                <p>Hãy thử thay đổi bộ lọc để tìm kỳ thi.</p>
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error loading exam periods:', error);
                document.getElementById('examPeriodList').innerHTML = `
                    <tr>
                        <td colspan="8" class="alert alert-danger">
                            Lỗi tải dữ liệu. Vui lòng thử lại.
                        </td>
                    </tr>
                `;
            }
        }

        async function loadClasses(maKhoi) {
            try {
                const response = await fetch(`${API_URL}?action=classes&maKhoi=${maKhoi}`, {credentials: 'same-origin'});
                const result = await response.json();
                
                const select = document.getElementById('searchLop');
                select.innerHTML = '<option value="">-- Chọn lớp --</option>';
                
                if (result.success) {
                    result.data.forEach(cls => {
                        const option = document.createElement('option');
                        option.value = cls.maLop;
                        option.textContent = cls.tenLop;
                        option.dataset.name = cls.tenLop;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        }

        async function loadSubjects() {
            try {
                const response = await fetch(`${API_URL}?action=subjects&byDepartment=true`, {credentials: 'same-origin'});
                const result = await response.json();
                
                const select = document.getElementById('searchMonHoc');
                select.innerHTML = '<option value="">-- Chọn môn thi --</option>';
                
                if (result.success) {
                    result.data.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.maMonHoc;
                        option.textContent = subject.tenMonHoc;
                        option.dataset.name = subject.tenMonHoc;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading subjects:', error);
            }
        }

        async function loadTeachers() {
            if (!selectedSubject) return;

            const chuaPhanCong = document.getElementById('filterUnassigned').checked;
            let url = `${API_URL}?action=teachers&maMonHoc=${selectedSubject.id}`;
            
            if (selectedExamPeriod && selectedClass) {
                url += `&maKyThi=${selectedExamPeriod.id}&maLop=${selectedClass.id}`;
            }
            
            if (chuaPhanCong) {
                url += `&chuaPhanCong=true`;
            }

            try {
                const response = await fetch(url, {credentials: 'same-origin'});
                const result = await response.json();
                
                const container = document.getElementById('teacherList');
                
                if (result.success && result.data.length > 0) {
                    container.innerHTML = result.data.map(teacher => {
                        const isSelected = selectedTeachers.includes(teacher.maGV);
                        const isAssigned = teacher.daPhanCong > 0;
                        
                        return `
                            <div class="teacher-card ${isSelected ? 'selected' : ''} ${isAssigned ? 'assigned' : ''}" 
                                 onclick="toggleTeacher(${teacher.maGV}, '${escapeHtml(teacher.hoTen)}')"
                                 data-id="${teacher.maGV}">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" ${isSelected ? 'checked' : ''} ${isAssigned ? 'disabled' : ''}>
                                    <div class="teacher-name">${teacher.hoTen}</div>
                                </div>
                                <div class="teacher-info">
                                    📧 ${teacher.email || '-'}<br>
                                    📱 ${teacher.soDienThoai || '-'}<br>
                                    📚 ${teacher.tenMonHoc}
                                    ${isAssigned ? '<br><span class="badge badge-success">Đã phân công</span>' : ''}
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="icon">👨‍🏫</div>
                            <h3>Không có giáo viên nào</h3>
                            <p>Không tìm thấy giáo viên dạy môn này trong tổ bộ môn của bạn.</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading teachers:', error);
            }
        }

        // ===== ACTIONS =====

        function selectExamPeriod(id, name, type, maKhoi) {
            selectedExamPeriod = { id, name, type, maKhoi };
            document.getElementById('selectedExamName').textContent = name;
            loadClasses(maKhoi);
            goToStep(2);
        }

        async function searchExamPapers() {
            const maLop = document.getElementById('searchLop').value;
            const maMonHoc = document.getElementById('searchMonHoc').value;

            if (!maLop || !maMonHoc) {
                alert('Vui lòng chọn đầy đủ Lớp và Môn thi');
                return;
            }

            const lopSelect = document.getElementById('searchLop');
            const monSelect = document.getElementById('searchMonHoc');
            
            selectedClass = { 
                id: maLop, 
                name: lopSelect.options[lopSelect.selectedIndex].dataset.name 
            };
            selectedSubject = { 
                id: maMonHoc, 
                name: monSelect.options[monSelect.selectedIndex].dataset.name 
            };

            try {
                const response = await fetch(`${API_URL}?action=exam-papers&maKyThi=${selectedExamPeriod.id}&maLop=${maLop}&maMonHoc=${maMonHoc}`, {credentials: 'same-origin'});
                const result = await response.json();

                if (result.success) {
                    examPaperCount = result.count;
                    
                    document.getElementById('totalPapers').textContent = result.count;
                    document.getElementById('examPeriodName').textContent = result.examPeriod.tenKyThi;
                    document.getElementById('examPeriodType').textContent = result.examPeriod.loaiKyThi;
                    document.getElementById('infoLop').textContent = selectedClass.name;
                    document.getElementById('infoMonHoc').textContent = selectedSubject.name;
                    document.getElementById('infoSoHS').textContent = result.count;

                    goToStep(3);
                } else {
                    alert(result.message || 'Lỗi tìm kiếm');
                }
            } catch (error) {
                console.error('Error searching exam papers:', error);
                alert('Lỗi kết nối. Vui lòng thử lại.');
            }
        }

        function toggleTeacher(id, name) {
            const card = document.querySelector(`.teacher-card[data-id="${id}"]`);
            
            // Không cho chọn nếu đã phân công
            if (card.classList.contains('assigned')) {
                return;
            }

            const index = selectedTeachers.indexOf(id);
            
            if (index > -1) {
                // Bỏ chọn
                selectedTeachers.splice(index, 1);
                card.classList.remove('selected');
                card.querySelector('input[type="checkbox"]').checked = false;
            } else {
                // Kiểm tra tối đa 2 GV
                if (selectedTeachers.length >= 2) {
                    alert('Chỉ được chọn tối đa 2 giáo viên!');
                    return;
                }
                // Chọn
                selectedTeachers.push(id);
                card.classList.add('selected');
                card.querySelector('input[type="checkbox"]').checked = true;
            }

            updateSelectedCount();
        }

        function updateSelectedCount() {
            document.getElementById('selectedCount').textContent = selectedTeachers.length;
            document.getElementById('btnConfirmTeachers').disabled = selectedTeachers.length === 0;
        }

        async function submitAssignment() {
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.textContent = 'Đang xử lý...';

            try {
                const response = await fetch(`${API_URL}?action=create-assignment`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        maKyThi: selectedExamPeriod.id,
                        maLop: selectedClass.id,
                        maMonHoc: selectedSubject.id,
                        teachers: selectedTeachers
                    })
                });

                const result = await response.json();

                if (result.success) {
                    document.getElementById('successDetails').innerHTML = `
                        <p>Số giáo viên được phân công: <strong>${result.assignedCount}</strong></p>
                    `;
                    document.getElementById('successModal').classList.add('active');
                } else {
                    alert(result.message || 'Lỗi phân công');
                    btn.disabled = false;
                    btn.textContent = '✓ Xác nhận phân công';
                }
            } catch (error) {
                console.error('Error submitting assignment:', error);
                alert('Lỗi kết nối. Vui lòng thử lại sau.');
                btn.disabled = false;
                btn.textContent = '✓ Xác nhận phân công';
            }
        }

        // ===== NAVIGATION =====

        function goToStep(step) {
            currentStep = step;
            
            // Hide all sections
            document.querySelectorAll('.card[id^="section-"]').forEach(section => {
                section.classList.remove('section-visible');
                section.classList.add('section-hidden');
            });

            // Update steps
            document.querySelectorAll('.step').forEach((stepEl, index) => {
                stepEl.classList.remove('active', 'completed');
                if (index + 1 < step) {
                    stepEl.classList.add('completed');
                } else if (index + 1 === step) {
                    stepEl.classList.add('active');
                }
            });

            // Show current section
            const sectionMap = {
                1: 'section-exam-period',
                2: 'section-search',
                3: 'section-exam-papers',
                4: 'section-teachers',
                5: 'section-confirm'
            };

            const sectionId = sectionMap[step];
            if (sectionId) {
                const section = document.getElementById(sectionId);
                section.classList.remove('section-hidden');
                section.classList.add('section-visible');
            }

            // Load data for specific steps
            if (step === 4) {
                loadTeachers();
            } else if (step === 5) {
                showConfirmInfo();
            }
        }

        function showConfirmInfo() {
            const teacherNames = [];
            document.querySelectorAll('.teacher-card.selected .teacher-name').forEach(el => {
                teacherNames.push(el.textContent);
            });

            document.getElementById('confirmInfo').innerHTML = `
                <tr>
                    <td><strong>Kỳ thi</strong></td>
                    <td>${selectedExamPeriod.name}</td>
                </tr>
                <tr>
                    <td><strong>Loại kỳ thi</strong></td>
                    <td>${selectedExamPeriod.type}</td>
                </tr>
                <tr>
                    <td><strong>Lớp</strong></td>
                    <td>${selectedClass.name}</td>
                </tr>
                <tr>
                    <td><strong>Môn thi</strong></td>
                    <td>${selectedSubject.name}</td>
                </tr>
                <tr>
                    <td><strong>Số bài thi cần chấm</strong></td>
                    <td>${examPaperCount} bài</td>
                </tr>
                <tr>
                    <td><strong>Giáo viên được phân công</strong></td>
                    <td>${teacherNames.join(', ') || '-'}</td>
                </tr>
            `;
        }

        function resetAndGoToStart() {
            // Reset state
            selectedExamPeriod = null;
            selectedClass = null;
            selectedSubject = null;
            selectedTeachers = [];
            examPaperCount = 0;

            // Reset form
            document.getElementById('searchLop').value = '';
            document.getElementById('searchMonHoc').value = '';
            document.getElementById('filterUnassigned').checked = false;

            // Close modal
            document.getElementById('successModal').classList.remove('active');
            
            // Reset submit button
            const btn = document.getElementById('btnSubmit');
            btn.disabled = false;
            btn.textContent = '✓ Xác nhận phân công';

            // Go to step 1
            goToStep(1);
            loadExamPeriods();
        }

        // ===== UTILITIES =====

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('vi-VN');
        }

        function getStatusBadge(status) {
            const badges = {
                'Dang_lap': '<span class="badge badge-warning">Đang lập</span>',
                'Dang_thi': '<span class="badge badge-info">Đang thi</span>',
                'Ket_thuc': '<span class="badge badge-success">Kết thúc</span>'
            };
            return badges[status] || status;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>"']/g, function(m) {
                return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m];
            });
        }

        // Filter change events
        document.getElementById('filterNamHoc').addEventListener('change', loadExamPeriods);
        document.getElementById('filterHocKy').addEventListener('change', loadExamPeriods);
        document.getElementById('filterKhoi').addEventListener('change', loadExamPeriods);

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
