<?php
/**
 * View: Phân công coi thi (Exam Proctor Assignment)
 * Chức năng: Giao diện phân công giáo viên coi thi theo kỳ thi
 * Actor: Tổ trưởng bộ môn (TTBM)
 * 
 * Luồng chính:
 * 1. Chọn tiêu chí: Khối → Lớp → Kỳ thi → Môn thi → Ca thi
 * 2. Tìm kiếm ca thi
 * 3. Xem danh sách phòng thi
 * 4. Chọn GV coi thi (1-2 GV)
 * 5. Xác nhận phân công
 * 
 * @author IMAX Team
 * @version 1.0
 * @date 2025-12-21
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'ttbm') {
    header("Location: ../../public/index.php");
    exit;
}

// Lấy thông tin TTBM
require_once(__DIR__ . '/../../model/mExamProctorAssignment.php');
$model = new mExamProctorAssignment();
$ttbmInfo = $model->getTTBMInfo($_SESSION['maTaiKhoan']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công coi thi - TTBM</title>
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
        }

        .navbar {
            background: rgba(255,255,255,0.95);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
        }

        .navbar .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .navbar .user-name {
            color: #667eea;
            font-weight: 600;
        }

        .navbar .user-dept {
            color: #666;
            font-size: 14px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            font-size: 28px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
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
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Steps Progress */
        .steps-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
            z-index: 1;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-number {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }

        .step.active .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .step.completed .step-number {
            background: #28a745;
            color: white;
        }

        .step-label {
            font-size: 13px;
            color: #999;
            text-align: center;
        }

        .step.active .step-label,
        .step.completed .step-label {
            color: #333;
            font-weight: 500;
        }

        /* Main Content */
        .main-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            min-height: 500px;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        .step-title {
            font-size: 22px;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Filter Form */
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group label .required {
            color: #dc3545;
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
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            font-size: 13px;
            text-transform: uppercase;
        }

        tr:hover {
            background: #f8f9fa;
        }

        /* Badge */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
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

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-primary {
            background: #e7e9fd;
            color: #667eea;
        }

        /* Schedule Card */
        .schedule-list {
            display: grid;
            gap: 15px;
        }

        .schedule-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .schedule-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1);
        }

        .schedule-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .schedule-info h4 {
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }

        .schedule-info p {
            color: #666;
            font-size: 14px;
        }

        .schedule-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .schedule-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #555;
        }

        .schedule-detail .icon {
            font-size: 18px;
        }

        /* Teacher List */
        .teacher-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .teacher-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .teacher-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .teacher-card.selected {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.05);
        }

        .teacher-card.conflict {
            border-color: #dc3545;
            background: rgba(220, 53, 69, 0.05);
            cursor: not-allowed;
        }

        .teacher-card .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .teacher-card input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .teacher-card .teacher-name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .teacher-card .teacher-info {
            font-size: 13px;
            color: #666;
            line-height: 1.8;
        }

        .teacher-card .conflict-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
        }

        /* Summary Box */
        .summary-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
        }

        .summary-item .label {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }

        .summary-item .value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
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
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 20px;
            color: #666;
            margin-bottom: 10px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        /* Checkbox Filter */
        .filter-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }

        .filter-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .filter-checkbox label {
            font-size: 14px;
            color: #555;
            cursor: pointer;
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
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .modal-content .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .modal-content h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }

        .modal-content p {
            color: #666;
            margin-bottom: 25px;
        }

        /* Auto Assign Section */
        .auto-assign-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .auto-assign-section h4 {
            font-size: 16px;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .auto-assign-section p {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        /* Selected Count */
        .selected-count {
            font-size: 14px;
            color: #667eea;
            font-weight: 500;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="breadcrumb">
            <a href="index.php">🏠 Dashboard</a>
            <span>›</span>
            <a href="vExamAssignment.php">Phân công kỳ thi</a>
            <span>›</span>
            <strong>Phân công coi thi</strong>
        </div>
        <div class="user-info">
            <div>
                <div class="user-name"><?php echo htmlspecialchars($ttbmInfo['hoTen'] ?? 'TTBM'); ?></div>
                <div class="user-dept">Tổ bộ môn: <strong><?php echo htmlspecialchars($ttbmInfo['toBoMon'] ?? 'N/A'); ?></strong></div>
            </div>
            <a href="vExamAssignment.php" class="btn btn-secondary">← Quay lại</a>
            <a href="../../public/logout.php" class="btn btn-danger">🚪 Đăng xuất</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>👁️ Phân công coi thi</h1>
                    <p>Phân công giáo viên coi thi cho các ca thi trong kỳ thi</p>
                </div>
            </div>
        </div>

        <!-- Steps Progress -->
        <div class="steps-container">
            <div class="steps">
                <div class="step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">Chọn tiêu chí</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">Tìm kiếm</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">Chọn ca thi</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-label">Phân công GV</div>
                </div>
                <div class="step" data-step="5">
                    <div class="step-number">5</div>
                    <div class="step-label">Xác nhận</div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Step 1: Chọn tiêu chí -->
            <div class="step-content active" id="step1">
                <h2 class="step-title">📋 Bước 1: Chọn tiêu chí tìm kiếm</h2>
                
                <div class="filter-form">
                    <div class="form-group">
                        <label>KHỐI <span class="required">*</span></label>
                        <select id="filterKhoi" class="form-control" onchange="onKhoiChange()">
                            <option value="">-- Chọn khối --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>LỚP</label>
                        <select id="filterLop" class="form-control">
                            <option value="">-- Tất cả lớp --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>KỲ THI <span class="required">*</span></label>
                        <select id="filterKyThi" class="form-control" onchange="onKyThiChange()">
                            <option value="">-- Chọn kỳ thi --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>MÔN THI</label>
                        <select id="filterMonThi" class="form-control">
                            <option value="">-- Tất cả môn --</option>
                        </select>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <div></div>
                    <button class="btn btn-primary" onclick="searchSchedules()">
                        🔍 Tìm kiếm ca thi
                    </button>
                </div>
            </div>

            <!-- Step 2: Danh sách ca thi -->
            <div class="step-content" id="step2">
                <h2 class="step-title">🔍 Bước 2: Danh sách ca thi</h2>
                
                <div class="summary-box">
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="label">Kỳ thi</span>
                            <span class="value" id="summaryKyThi">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Môn thi</span>
                            <span class="value" id="summaryMonThi">Tất cả</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Số ca thi</span>
                            <span class="value" id="summarySoCaThi">0</span>
                        </div>
                    </div>
                </div>

                <!-- Auto Assign -->
                <div class="auto-assign-section">
                    <h4>⚡ Phân công tự động</h4>
                    <p>Hệ thống sẽ tự động phân công giáo viên cho các ca thi chưa có đủ giám thị.</p>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <div class="form-group" style="margin: 0;">
                            <select id="autoAssignCount" class="form-control" style="width: 150px;">
                                <option value="1">1 GV / ca</option>
                                <option value="2" selected>2 GV / ca</option>
                            </select>
                        </div>
                        <button class="btn btn-success" onclick="autoAssign()">
                            ⚡ Phân công tự động
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Ngày thi</th>
                                <th>Thời gian</th>
                                <th>Môn thi</th>
                                <th>Phòng thi</th>
                                <th>Sức chứa</th>
                                <th>GV đã phân công</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleList">
                        </tbody>
                    </table>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="goToStep(1)">← Quay lại</button>
                    <div></div>
                </div>
            </div>

            <!-- Step 3: Chọn GV coi thi -->
            <div class="step-content" id="step3">
                <h2 class="step-title">👨‍🏫 Bước 3: Chọn giáo viên coi thi</h2>
                
                <div class="summary-box">
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="label">Ngày thi</span>
                            <span class="value" id="infoNgayThi">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Thời gian</span>
                            <span class="value" id="infoThoiGian">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Phòng thi</span>
                            <span class="value" id="infoPhongThi">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Môn thi</span>
                            <span class="value" id="infoMonThi">-</span>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    ℹ️ Chọn tối đa <strong>2 giáo viên</strong> để phân công coi thi cho ca này. Giáo viên có lịch trùng sẽ được đánh dấu màu đỏ.
                </div>

                <div class="filter-checkbox">
                    <input type="checkbox" id="filterAvailable" onchange="loadTeachers()">
                    <label for="filterAvailable">Chỉ hiển thị giáo viên chưa được phân công</label>
                </div>

                <div class="selected-count">
                    Đã chọn: <span id="selectedTeacherCount">0</span>/2 giáo viên
                </div>

                <div class="teacher-list" id="teacherList">
                    <!-- Teachers will be loaded here -->
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="goToStep(2)">← Quay lại</button>
                    <button class="btn btn-primary" id="btnGoToConfirm" onclick="goToStep(4)" disabled>
                        Tiếp tục →
                    </button>
                </div>
            </div>

            <!-- Step 4: Xác nhận -->
            <div class="step-content" id="step4">
                <h2 class="step-title">✅ Bước 4: Xác nhận phân công</h2>
                
                <div class="summary-box">
                    <h4 style="margin-bottom: 15px;">📋 Thông tin phân công</h4>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="label">Ngày thi</span>
                            <span class="value" id="confirmNgayThi">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Thời gian</span>
                            <span class="value" id="confirmThoiGian">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Phòng thi</span>
                            <span class="value" id="confirmPhongThi">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Môn thi</span>
                            <span class="value" id="confirmMonThi">-</span>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <h4 style="margin-bottom: 15px;">👨‍🏫 Giáo viên được phân công</h4>
                    <div id="confirmTeacherList">
                        <!-- Selected teachers will be shown here -->
                    </div>
                </div>

                <div class="alert alert-warning" style="margin-top: 20px;">
                    ⚠️ Sau khi xác nhận, hệ thống sẽ gửi thông báo cho giáo viên được phân công.
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="goToStep(3)">← Quay lại</button>
                    <button class="btn btn-success" id="btnSubmit" onclick="submitAssignment()">
                        ✓ Xác nhận phân công
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal" id="successModal">
        <div class="modal-content">
            <div class="icon">✅</div>
            <h2>Phân công thành công!</h2>
            <p id="successMessage">Giáo viên đã được phân công coi thi và nhận thông báo.</p>
            <button class="btn btn-primary" onclick="resetForm()">Phân công tiếp</button>
            <button class="btn btn-secondary" onclick="window.location.href='vExamAssignment.php'" style="margin-left: 10px;">Quay về</button>
        </div>
    </div>

    <script>
        const API_URL = '/code/school_management/controller/cExamProctorAssignment.php';
        
        // State
        let currentStep = 1;
        let selectedSchedule = null;
        let selectedTeachers = [];
        let teachersData = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadGrades();
        });

        // ===== NAVIGATION =====
        function goToStep(step) {
            // Update steps UI
            document.querySelectorAll('.step').forEach((el, index) => {
                el.classList.remove('active', 'completed');
                if (index + 1 < step) {
                    el.classList.add('completed');
                } else if (index + 1 === step) {
                    el.classList.add('active');
                }
            });

            // Update content
            document.querySelectorAll('.step-content').forEach(el => {
                el.classList.remove('active');
            });
            document.getElementById(`step${step}`).classList.add('active');

            currentStep = step;
        }

        // ===== LOAD DATA =====
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

        async function onKhoiChange() {
            const maKhoi = document.getElementById('filterKhoi').value;
            
            // Reset dependent selects
            document.getElementById('filterLop').innerHTML = '<option value="">-- Tất cả lớp --</option>';
            document.getElementById('filterKyThi').innerHTML = '<option value="">-- Chọn kỳ thi --</option>';
            document.getElementById('filterMonThi').innerHTML = '<option value="">-- Tất cả môn --</option>';
            
            if (!maKhoi) return;
            
            // Load classes
            try {
                const response = await fetch(`${API_URL}?action=classes&maKhoi=${maKhoi}`, {credentials: 'same-origin'});
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById('filterLop');
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
            
            // Load exam periods
            try {
                const response = await fetch(`${API_URL}?action=exam-periods&maKhoi=${maKhoi}`, {credentials: 'same-origin'});
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById('filterKyThi');
                    result.data.forEach(exam => {
                        const option = document.createElement('option');
                        option.value = exam.maKyThi;
                        option.textContent = `${exam.tenKyThi} (${exam.loaiKyThi})`;
                        option.dataset.loai = exam.loaiKyThi;
                        option.dataset.ten = exam.tenKyThi;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading exam periods:', error);
            }
        }

        async function onKyThiChange() {
            const maKyThi = document.getElementById('filterKyThi').value;
            document.getElementById('filterMonThi').innerHTML = '<option value="">-- Tất cả môn --</option>';
            
            if (!maKyThi) return;
            
            try {
                const response = await fetch(`${API_URL}?action=subjects&maKyThi=${maKyThi}`, {credentials: 'same-origin'});
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById('filterMonThi');
                    result.data.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.maMonHoc;
                        option.textContent = subject.tenMonHoc;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading subjects:', error);
            }
        }

        // ===== SEARCH SCHEDULES =====
        async function searchSchedules() {
            const maKhoi = document.getElementById('filterKhoi').value;
            const maKyThi = document.getElementById('filterKyThi').value;
            
            if (!maKhoi || !maKyThi) {
                alert('Vui lòng chọn Khối và Kỳ thi');
                return;
            }
            
            const maMonHoc = document.getElementById('filterMonThi').value;
            const maLop = document.getElementById('filterLop').value;
            
            let url = `${API_URL}?action=exam-schedules&maKyThi=${maKyThi}`;
            if (maMonHoc) url += `&maMonHoc=${maMonHoc}`;
            if (maLop) url += `&maLop=${maLop}`;
            
            try {
                const response = await fetch(url, {credentials: 'same-origin'});
                const result = await response.json();
                
                const tbody = document.getElementById('scheduleList');
                
                // Update summary
                const kyThiSelect = document.getElementById('filterKyThi');
                const monThiSelect = document.getElementById('filterMonThi');
                document.getElementById('summaryKyThi').textContent = kyThiSelect.options[kyThiSelect.selectedIndex].text;
                document.getElementById('summaryMonThi').textContent = maMonHoc ? monThiSelect.options[monThiSelect.selectedIndex].text : 'Tất cả';
                document.getElementById('summarySoCaThi').textContent = result.data.length;
                
                if (result.success && result.data.length > 0) {
                    tbody.innerHTML = result.data.map(schedule => {
                        const status = schedule.soGVDaPhanCong >= 2 ? 'success' : 
                                      schedule.soGVDaPhanCong > 0 ? 'warning' : 'danger';
                        const statusText = schedule.soGVDaPhanCong >= 2 ? 'Đủ GV' : 
                                          schedule.soGVDaPhanCong > 0 ? 'Chưa đủ' : 'Chưa có';
                        
                        return `
                            <tr>
                                <td><strong>${formatDate(schedule.ngayThi)}</strong></td>
                                <td>${schedule.gioBatDau} - ${schedule.gioKetThuc}</td>
                                <td>${schedule.tenMonHoc}</td>
                                <td>${schedule.tenPhong || 'Chưa xác định'}</td>
                                <td>${schedule.sucChua || '-'} chỗ</td>
                                <td>${schedule.soGVDaPhanCong}/2</td>
                                <td><span class="badge badge-${status}">${statusText}</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick='selectSchedule(${JSON.stringify(schedule)})'>
                                        Phân công
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" class="empty-state">
                                <div class="icon">📅</div>
                                <h3>Không có ca thi nào</h3>
                                <p>Không tìm thấy lịch thi theo tiêu chí đã chọn.</p>
                            </td>
                        </tr>
                    `;
                }
                
                goToStep(2);
            } catch (error) {
                console.error('Error searching schedules:', error);
                alert('Lỗi kết nối. Vui lòng thử lại.');
            }
        }

        // ===== SELECT SCHEDULE =====
        function selectSchedule(schedule) {
            selectedSchedule = schedule;
            selectedTeachers = [];
            
            // Update info
            document.getElementById('infoNgayThi').textContent = formatDate(schedule.ngayThi);
            document.getElementById('infoThoiGian').textContent = `${schedule.gioBatDau} - ${schedule.gioKetThuc}`;
            document.getElementById('infoPhongThi').textContent = schedule.tenPhong || 'Chưa xác định';
            document.getElementById('infoMonThi').textContent = schedule.tenMonHoc;
            
            loadTeachers();
            goToStep(3);
        }

        // ===== LOAD TEACHERS =====
        async function loadTeachers() {
            if (!selectedSchedule) return;
            
            const chuaPhanCong = document.getElementById('filterAvailable').checked;
            
            let url = `${API_URL}?action=teachers`;
            url += `&ngayThi=${selectedSchedule.ngayThi}`;
            url += `&gioBatDau=${selectedSchedule.gioBatDau}`;
            url += `&gioKetThuc=${selectedSchedule.gioKetThuc}`;
            if (chuaPhanCong) url += `&chuaPhanCong=true`;
            
            try {
                const response = await fetch(url, {credentials: 'same-origin'});
                const result = await response.json();
                
                const container = document.getElementById('teacherList');
                teachersData = result.data || [];
                
                if (result.success && teachersData.length > 0) {
                    container.innerHTML = teachersData.map(teacher => {
                        const isSelected = selectedTeachers.includes(teacher.maGV);
                        const hasConflict = teacher.trungLich > 0;
                        
                        return `
                            <div class="teacher-card ${isSelected ? 'selected' : ''} ${hasConflict ? 'conflict' : ''}" 
                                 onclick="toggleTeacher(${teacher.maGV}, ${hasConflict})"
                                 data-id="${teacher.maGV}">
                                ${hasConflict ? '<span class="conflict-badge">Trùng lịch</span>' : ''}
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" ${isSelected ? 'checked' : ''} ${hasConflict ? 'disabled' : ''}>
                                    <div class="teacher-name">${teacher.hoTen}</div>
                                </div>
                                <div class="teacher-info">
                                    📧 ${teacher.email || '-'}<br>
                                    📱 ${teacher.soDienThoai || '-'}<br>
                                    📚 Tổ: ${teacher.toBoMon}<br>
                                    📋 Số ca đã phân công hôm nay: ${teacher.soCaDaPhanCong || 0}
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="icon">👨‍🏫</div>
                            <h3>Không có giáo viên</h3>
                            <p>Không tìm thấy giáo viên phù hợp trong tổ bộ môn.</p>
                        </div>
                    `;
                }
                
                updateSelectedCount();
            } catch (error) {
                console.error('Error loading teachers:', error);
            }
        }

        // ===== TOGGLE TEACHER =====
        function toggleTeacher(maGV, hasConflict) {
            if (hasConflict) {
                alert('Giáo viên này đã được phân công cho ca thi khác trùng thời gian!');
                return;
            }
            
            const index = selectedTeachers.indexOf(maGV);
            const card = document.querySelector(`.teacher-card[data-id="${maGV}"]`);
            
            if (index > -1) {
                selectedTeachers.splice(index, 1);
                card.classList.remove('selected');
                card.querySelector('input[type="checkbox"]').checked = false;
            } else {
                if (selectedTeachers.length >= 2) {
                    alert('Chỉ được chọn tối đa 2 giáo viên!');
                    return;
                }
                selectedTeachers.push(maGV);
                card.classList.add('selected');
                card.querySelector('input[type="checkbox"]').checked = true;
            }
            
            updateSelectedCount();
        }

        function updateSelectedCount() {
            document.getElementById('selectedTeacherCount').textContent = selectedTeachers.length;
            document.getElementById('btnGoToConfirm').disabled = selectedTeachers.length === 0;
        }

        // ===== GO TO CONFIRM =====
        document.getElementById('btnGoToConfirm').addEventListener('click', function() {
            if (selectedTeachers.length === 0) return;
            
            // Update confirm info
            document.getElementById('confirmNgayThi').textContent = formatDate(selectedSchedule.ngayThi);
            document.getElementById('confirmThoiGian').textContent = `${selectedSchedule.gioBatDau} - ${selectedSchedule.gioKetThuc}`;
            document.getElementById('confirmPhongThi').textContent = selectedSchedule.tenPhong || 'Chưa xác định';
            document.getElementById('confirmMonThi').textContent = selectedSchedule.tenMonHoc;
            
            // Show selected teachers
            const teacherListHtml = selectedTeachers.map((maGV, index) => {
                const teacher = teachersData.find(t => t.maGV === maGV);
                return `
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                        <strong>Giám thị ${index + 1}:</strong> ${teacher ? teacher.hoTen : 'N/A'}
                        <br><span style="color: #666; font-size: 13px;">Email: ${teacher ? teacher.email : '-'}</span>
                    </div>
                `;
            }).join('');
            document.getElementById('confirmTeacherList').innerHTML = teacherListHtml;
        });

        // ===== SUBMIT ASSIGNMENT =====
        async function submitAssignment() {
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.textContent = 'Đang xử lý...';
            
            const kyThiSelect = document.getElementById('filterKyThi');
            const loaiKyThi = kyThiSelect.options[kyThiSelect.selectedIndex].dataset.loai;
            
            try {
                const response = await fetch(`${API_URL}?action=create-assignment`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        maLichThi: selectedSchedule.maLichThi,
                        maLop: document.getElementById('filterLop').value || 0,
                        maMonHoc: selectedSchedule.maMonHoc,
                        maPhong: selectedSchedule.maPhong,
                        loaiKyThi: loaiKyThi,
                        ngayThi: selectedSchedule.ngayThi,
                        gioBatDau: selectedSchedule.gioBatDau,
                        gioKetThuc: selectedSchedule.gioKetThuc,
                        teachers: selectedTeachers
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('successMessage').textContent = 
                        `Đã phân công ${result.assignedCount} giáo viên coi thi thành công!`;
                    document.getElementById('successModal').classList.add('active');
                } else {
                    alert(result.message || 'Lỗi phân công');
                    btn.disabled = false;
                    btn.textContent = '✓ Xác nhận phân công';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Lỗi kết nối. Vui lòng thử lại.');
                btn.disabled = false;
                btn.textContent = '✓ Xác nhận phân công';
            }
        }

        // ===== AUTO ASSIGN =====
        async function autoAssign() {
            const maKyThi = document.getElementById('filterKyThi').value;
            const maMonHoc = document.getElementById('filterMonThi').value;
            const soGVMoiCa = document.getElementById('autoAssignCount').value;
            
            if (!confirm('Bạn có chắc muốn phân công tự động? Hệ thống sẽ tự động chọn giáo viên cho các ca thi chưa đủ giám thị.')) {
                return;
            }
            
            try {
                const response = await fetch(`${API_URL}?action=auto-assign`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        maKyThi: maKyThi,
                        maMonHoc: maMonHoc || null,
                        soGVMoiCa: parseInt(soGVMoiCa)
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`Phân công tự động thành công!\nSố phân công mới: ${result.assignedCount}`);
                    searchSchedules(); // Reload
                } else {
                    alert(result.message || 'Lỗi phân công tự động');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Lỗi kết nối. Vui lòng thử lại.');
            }
        }

        // ===== RESET FORM =====
        function resetForm() {
            selectedSchedule = null;
            selectedTeachers = [];
            document.getElementById('successModal').classList.remove('active');
            document.getElementById('btnSubmit').disabled = false;
            document.getElementById('btnSubmit').textContent = '✓ Xác nhận phân công';
            goToStep(1);
        }

        // ===== HELPERS =====
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('vi-VN', { weekday: 'long', year: 'numeric', month: '2-digit', day: '2-digit' });
        }
    </script>
</body>
</html>
