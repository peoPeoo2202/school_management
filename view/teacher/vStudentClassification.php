<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

// Kiểm tra quyền giáo viên
if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

// Nếu chưa có $data, include controller để xử lý
if (!isset($data)) {
    define('INCLUDED_FROM_VIEW', true); // Đánh dấu được gọi từ view
    ob_start(); // Bắt đầu buffer để tránh controller render view
    require_once(__DIR__ . '/../../controller/cStudentClassification.php');
    ob_end_clean(); // Xóa buffer
    // Controller đã set biến $data, bây giờ view sẽ render
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xếp loại học sinh - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
        }

        .main-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .content-area {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
            overflow-x: hidden;
            height: 100vh;
            box-sizing: border-box;
        }

        .header-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header-left-icon {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #5081BE;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header-left-icon h2 {
            margin: 0;
            font-size: 24px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }

        .filter-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #34495e;
            font-size: 14px;
        }

        .form-control {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 2px rgba(80, 129, 190, 0.1);
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 0;
        }

        .tab-btn {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            color: #5081BE;
        }

        .tab-btn.active {
            color: #5081BE;
            border-bottom-color: #5081BE;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px; /* Giảm từ 1200px xuống 800px */
        }

        .data-table thead {
            background: #5081BE;
            color: white;
        }

        .data-table th {
            padding: 10px 8px; /* Giảm padding từ 12px xuống 10px 8px */
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        .data-table td {
            padding: 10px 8px; /* Giảm padding từ 12px xuống 10px 8px */
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px; /* Giảm từ 14px xuống 13px */
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        .ranking-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .ranking-tot {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .ranking-kha {
            background: #bbdefb;
            color: #1976d2;
        }

        .ranking-dat {
            background: #fff9c4;
            color: #f57f17;
        }

        .ranking-chuadat {
            background: #ffcdd2;
            color: #c62828;
        }
        
        .ranking-chuaxeploai {
            background: #e0e0e0;
            color: #757575;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-primary {
            background: #5081BE;
            color: white;
        }

        .btn-primary:hover {
            background: #4070a8;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .loading i {
            font-size: 32px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .conduct-select {
            padding: 8px 32px 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            background-color: white;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
            cursor: pointer;
            min-width: 120px;
            transition: all 0.3s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .conduct-select:hover {
            border-color: #5081BE;
            box-shadow: 0 2px 4px rgba(80, 129, 190, 0.1);
        }

        .conduct-select:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 3px rgba(80, 129, 190, 0.15);
            background-color: #f8f9ff;
        }

        .conduct-select.changed {
            border-color: #27ae60;
            background-color: #e8f5e9;
            box-shadow: 0 2px 8px rgba(39, 174, 96, 0.2);
            animation: pulse 0.5s ease;
        }

        .conduct-select.changed:hover {
            border-color: #229954;
        }

        /* Màu sắc theo từng loại hạnh kiểm */
        .conduct-select option[value="Tốt"] {
            background-color: #c8e6c9;
            color: #2e7d32;
            font-weight: 600;
        }

        .conduct-select option[value="Khá"] {
            background-color: #bbdefb;
            color: #1976d2;
            font-weight: 600;
        }

        .conduct-select option[value="Đạt"] {
            background-color: #fff9c4;
            color: #f57f17;
            font-weight: 600;
        }

        .conduct-select option[value="Chưa đạt"] {
            background-color: #ffcdd2;
            color: #c62828;
            font-weight: 600;
        }

        /* Animation khi thay đổi */
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        /* Disabled state */
        .conduct-select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Icon indicator khi đã thay đổi */
        .conduct-select.changed::after {
            content: '✓';
            position: absolute;
            right: 30px;
            color: #27ae60;
            font-weight: bold;
            pointer-events: none;
        }

        /* Wrapper cho select để thêm icon */
        .select-wrapper {
            position: relative;
            display: inline-block;
        }

        .select-wrapper.changed::after {
            content: '✓';
            position: absolute;
            right: 35px;
            top: 50%;
            transform: translateY(-50%);
            color: #27ae60;
            font-weight: bold;
            font-size: 16px;
            pointer-events: none;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-50%) scale(0.5);
            }
            to {
                opacity: 1;
                transform: translateY(-50%) scale(1);
            }
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-primary {
            background: #5081BE;
            color: white;
        }

        .btn-primary:hover {
            background: #4070a8;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .loading i {
            font-size: 32px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
        }

        .modal-header {
            padding: 20px;
            background: linear-gradient(135deg, #5081BE 0%, #4a6fa5 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 20px;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .close:hover {
            color: #ddd;
        }

        .modal-body {
            padding: 24px;
        }

        .criteria-section {
            margin-bottom: 24px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #5081BE;
        }

        .criteria-section h3 {
            color: #5081BE;
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .criteria-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .criteria-item {
            display: flex;
            flex-direction: column;
        }

        .criteria-item label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
            font-size: 13px;
        }

        .criteria-item input,
        .criteria-item select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .criteria-item input:focus,
        .criteria-item select:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 2px rgba(80, 129, 190, 0.1);
        }

        .modal-footer {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 13px;
            color: #1565c0;
        }

        .title-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
            border: 2px solid;
        }
        
        .title-xuatsac {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #b8860b;
            border-color: #ffa500;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
        }
        
        .title-gioi {
            background: linear-gradient(135deg, #90EE90 0%, #98FB98 100%);
            color: #228B22;
            border-color: #32CD32;
            box-shadow: 0 2px 8px rgba(144, 238, 144, 0.3);
        }
        
        .title-none {
            background: #f5f5f5;
            color: #999;
            border-color: #ddd;
        }
        
        .criteria-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .criteria-info h4 {
            margin-top: 0;
            color: #856404;
            font-size: 15px;
        }
        
        .criteria-info ul {
            margin: 10px 0 0 20px;
            color: #856404;
        }
        
        .criteria-info ul li {
            margin-bottom: 5px;
        }

        /* Pagination styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            padding: 15px 0;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #555;
            transition: all 0.3s;
            min-width: 40px;
        }

        .pagination button:hover:not(:disabled) {
            background: #5081BE;
            color: white;
            border-color: #5081BE;
        }

        .pagination button.active {
            background: #5081BE;
            color: white;
            border-color: #5081BE;
        }

        .pagination button:disabled {
            background: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
        }

        .pagination .page-info {
            padding: 8px 15px;
            font-size: 14px;
            color: #666;
        }

        @media (max-width: 768px) {
            .main-wrapper {
                flex-direction: column;
            }

            .content-area {
                margin-left: 0;
                padding: 15px;
            }

            .filter-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <!-- Header -->
            <div class="header-section">
                <div class="header-left">
                    <div class="header-left-icon">
                        <h2><i class="fas fa-star"></i></h2>
                        <h2>Xếp loại học sinh</h2>
                    </div>
                </div>
            </div>

            <?php if (isset($data['error'])): ?>
                <div class="card">
                    <div class="alert alert-error">
                        <!-- <strong>⚠️ Lỗi:</strong>  -->
                        <?php echo htmlspecialchars($data['error']); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div id="alertMessage"></div>
                    
                    <!-- Thông tin lớp chủ nhiệm -->
                    <div class="class-info" style="background: linear-gradient(135deg, #5081BE15 0%, #4a6fa515 100%); padding: 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #5081BE;">
                        <h3 style="color: #5081BE; margin-bottom: 10px; font-size: 16px; font-weight: 600;">
                            <i class="fas fa-info-circle"></i> Thông tin lớp chủ nhiệm
                        </h3>
                        <p style="margin: 8px 0; color: #555; font-size: 14px;">
                            <strong>Lớp:</strong> <?php echo htmlspecialchars($data['classInfo']['tenLop']); ?> - Khối <?php echo htmlspecialchars($data['classInfo']['khoiLop']); ?>
                        </p>
                        <p style="margin: 8px 0; color: #555; font-size: 14px;">
                            <strong>Sĩ số:</strong> <?php echo $data['classInfo']['siSo']; ?> học sinh
                        </p>
                        <p style="margin: 8px 0; color: #555; font-size: 14px;">
                            <strong>Năm học:</strong> <?php echo htmlspecialchars($data['classInfo']['namHoc']); ?>
                        </p>
                        <input type="hidden" id="maLop" value="<?php echo $data['currentClassId']; ?>">
                        <input type="hidden" id="namHoc" value="<?php echo $data['namHoc']; ?>">
                    </div>

                    <!-- Chọn học kỳ đánh giá -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
                        <div style="margin-bottom: 0;">
                            <label for="hocKy" style="font-size: 16px; color: white; margin-bottom: 12px; display: block; font-weight: 600;">
                                <i class="fas fa-calendar-check" style="margin-right: 8px;"></i>
                                Học kỳ đánh giá xếp loại
                            </label>
                            <div style="display: flex; align-items: center; gap: 15px; background: white; padding: 12px 15px; border-radius: 8px; width: fit-content;">
                                <select name="hocKy" id="hocKy" onchange="loadData()" 
                                    style="border: 2px solid #667eea; border-radius: 6px; padding: 10px 40px 10px 15px; font-size: 15px; font-weight: 600; color: #2c3e50; cursor: pointer; background: white; outline: none; appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;12&quot; height=&quot;12&quot; viewBox=&quot;0 0 12 12&quot;><path fill=&quot;%23667eea&quot; d=&quot;M6 9L1 4h10z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 12px center;">
                                    <option value="1" <?php echo ($data['hocKy'] == 1) ? 'selected' : ''; ?>>📚 Học kỳ 1</option>
                                    <option value="2" <?php echo ($data['hocKy'] == 2) ? 'selected' : ''; ?>>📚 Học kỳ 2</option>
                                </select>
                                <span style="font-size: 13px; color: #667eea; font-style: italic;">
                                    <i class="fas fa-info-circle"></i> Áp dụng cho tất cả các tab
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Buttons -->
                    <div class="tab-buttons">
                        <button class="tab-btn active" onclick="switchTab('academic')">
                            <i class="fas fa-graduation-cap"></i> Học lực
                        </button>
                        <button class="tab-btn" onclick="switchTab('conduct')">
                            <i class="fas fa-star"></i> Hạnh kiểm
                        </button>
                        <button class="tab-btn" onclick="switchTab('title')">
                            <i class="fas fa-award"></i> Danh hiệu
                        </button>
                    </div>

                    <!-- Tab: Xếp loại học lực -->
                    <div id="tab-academic" class="tab-content active">
                        <div id="academicContent"></div>
                    </div>

                    <!-- Tab: Xếp loại hạnh kiểm -->
                    <div id="tab-conduct" class="tab-content">
                        <div class="action-buttons">
                            <button class="btn btn-primary" onclick="openCriteriaModal()">
                                <i class="fas fa-cog"></i> Xếp loại tự động
                            </button>
                            <button class="btn btn-success" onclick="saveConductManual()">
                                <i class="fas fa-save"></i> Lưu xếp loại thủ công
                            </button>
                        </div>
                        <div id="conductContent"></div>
                    </div>

                    <!-- Tab: Xếp loại danh hiệu -->
                    <div id="tab-title" class="tab-content">
                        <div id="titleContent"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal cấu hình tiêu chí -->
    <div id="criteriaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-sliders-h"></i> Cấu hình tiêu chí xếp loại hạnh kiểm tự động</h2>
                <span class="close" onclick="closeCriteriaModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="info-box">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Hướng dẫn:</strong> Thiết lập các tiêu chí tối đa cho mỗi loại hạnh kiểm. 
                    Hệ thống sẽ tự động xếp loại học sinh dựa trên các tiêu chí này.
                </div>

                <!-- Tốt -->
                <div class="criteria-section" style="border-left-color: #27ae60;">
                    <h3><i class="fas fa-star" style="color: #27ae60;"></i> Hạnh kiểm Tốt</h3>
                    <div class="criteria-grid">
                        <div class="criteria-item">
                            <label>Nghỉ có phép (tối đa)</label>
                            <input type="number" id="tot-cophep" min="0" value="5">
                        </div>
                        <div class="criteria-item">
                            <label>Nghỉ không phép (tối đa)</label>
                            <input type="number" id="tot-khongphep" min="0" value="3">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nhẹ (tối đa)</label>
                            <input type="number" id="tot-nhe" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm TB (tối đa)</label>
                            <input type="number" id="tot-tb" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nặng (tối đa)</label>
                            <input type="number" id="tot-nang" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Học lực tối thiểu</label>
                            <select id="tot-hocluc">
                                <option value="Tốt">Tốt</option>
                                <option value="Khá" selected>Khá</option>
                                <option value="Đạt">Đạt</option>
                                <option value="">Không yêu cầu</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Khá -->
                <div class="criteria-section" style="border-left-color: #3498db;">
                    <h3><i class="fas fa-star" style="color: #3498db;"></i> Hạnh kiểm Khá</h3>
                    <div class="criteria-grid">
                        <div class="criteria-item">
                            <label>Nghỉ có phép (tối đa)</label>
                            <input type="number" id="kha-cophep" min="0" value="10">
                        </div>
                        <div class="criteria-item">
                            <label>Nghỉ không phép (tối đa)</label>
                            <input type="number" id="kha-khongphep" min="0" value="5">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nhẹ (tối đa)</label>
                            <input type="number" id="kha-nhe" min="0" value="1">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm TB (tối đa)</label>
                            <input type="number" id="kha-tb" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nặng (tối đa)</label>
                            <input type="number" id="kha-nang" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Học lực tối thiểu</label>
                            <select id="kha-hocluc">
                                <option value="Tốt">Tốt</option>
                                <option value="Khá">Khá</option>
                                <option value="Đạt" selected>Đạt</option>
                                <option value="">Không yêu cầu</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Đạt -->
                <div class="criteria-section" style="border-left-color: #f39c12;">
                    <h3><i class="fas fa-star" style="color: #f39c12;"></i> Hạnh kiểm Đạt</h3>
                    <div class="criteria-grid">
                        <div class="criteria-item">
                            <label>Nghỉ có phép (tối đa)</label>
                            <input type="number" id="dat-cophep" min="0" value="15">
                        </div>
                        <div class="criteria-item">
                            <label>Nghỉ không phép (tối đa)</label>
                            <input type="number" id="dat-khongphep" min="0" value="6">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nhẹ (tối đa)</label>
                            <input type="number" id="dat-nhe" min="0" value="3">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm TB (tối đa)</label>
                            <input type="number" id="dat-tb" min="0" value="3">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nặng (tối đa)</label>
                            <input type="number" id="dat-nang" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Học lực tối thiểu</label>
                            <select id="dat-hocluc">
                                <option value="Tốt">Tốt</option>
                                <option value="Khá">Khá</option>
                                <option value="Đạt" selected>Đạt</option>
                                <option value="">Không yêu cầu</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeCriteriaModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button class="btn btn-success" onclick="saveCriteriaAndClassify()">
                    <i class="fas fa-check"></i> Lưu và áp dụng
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentTab = 'academic';
        let currentPage = 1;
        const itemsPerPage = 10;
        let totalStudents = 0;
        let allStudents = [];
        let needsDataRefresh = false; // Cờ đánh dấu cần reload dữ liệu

        function switchTab(tab) {
            console.log(`[switchTab] Switching to ${tab}, needsDataRefresh: ${needsDataRefresh}`);
            
            currentTab = tab;
            currentPage = 1; // Reset về trang 1 khi chuyển tab
            
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            
            // Ẩn/hiện dropdown học kỳ (Danh hiệu tính theo cả năm)
            const hocKyGroup = document.querySelector('.form-group:has(#hocKy)');
            if (hocKyGroup) {
                if (tab === 'title') {
                    hocKyGroup.style.display = 'none';
                } else {
                    hocKyGroup.style.display = 'flex';
                }
            }
            
            // LUÔN force reload cho tab Danh hiệu để lấy dữ liệu mới nhất
            const shouldForceReload = needsDataRefresh || (tab === 'title');
            console.log(`[switchTab] Force reload: ${shouldForceReload}`);
            
            // Load data - force reload nếu có thay đổi HOẶC đang chuyển sang tab Danh hiệu
            loadData(shouldForceReload);
            needsDataRefresh = false; // Reset cờ sau khi reload
        }

        function loadData(forceReload = false) {
            const maLop = document.getElementById('maLop').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            
            let contentDiv = 'academicContent';
            if (currentTab === 'conduct') contentDiv = 'conductContent';
            else if (currentTab === 'title') contentDiv = 'titleContent';
            
            document.getElementById(contentDiv).innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><p>Đang tải dữ liệu...</p></div>';
            
            // Thêm timestamp nếu force reload để bypass cache
            const timestamp = forceReload ? '&_t=' + new Date().getTime() : '';
            fetch(`?action=getData&type=${currentTab}&maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}${timestamp}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log(`[${currentTab}] Data received:`, data);
                    
                    if (data.error) {
                        document.getElementById(contentDiv).innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${data.error}</div>`;
                        console.error('Server error:', data.error);
                        return;
                    }
                    
                    // Kiểm tra dữ liệu trả về
                    if (!data.students || !Array.isArray(data.students)) {
                        document.getElementById(contentDiv).innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Dữ liệu không hợp lệ</div>`;
                        console.error('Invalid data structure:', data);
                        return;
                    }
                    
                    console.log(`[${currentTab}] Students count:`, data.students.length);
                    
                    // Lưu toàn bộ danh sách học sinh
                    allStudents = data.students;
                    totalStudents = allStudents.length;
                    
                    // Hiển thị dữ liệu với phân trang
                    if (currentTab === 'academic') {
                        displayAcademic();
                    } else if (currentTab === 'conduct') {
                        displayConduct();
                    } else if (currentTab === 'title') {
                        displayTitles();
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    document.getElementById(contentDiv).innerHTML = `<div class="alert alert-error"><i class="fas fa-times-circle"></i> Lỗi khi tải dữ liệu: ${error.message}</div>`;
                });
        }

        function getPaginatedStudents() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            return allStudents.slice(startIndex, endIndex);
        }

        function createPagination() {
            const totalPages = Math.ceil(totalStudents / itemsPerPage);
            
            if (totalPages <= 1) {
                return ''; // Không hiển thị phân trang nếu chỉ có 1 trang
            }
            
            let html = '<div class="pagination">';
            
            // Nút Previous
            html += `<button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                        <i class="fas fa-chevron-left"></i>
                    </button>`;
            
            // Hiển thị các nút trang
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    html += `<button onclick="changePage(${i})" class="${i === currentPage ? 'active' : ''}">${i}</button>`;
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    html += '<span class="page-info">...</span>';
                }
            }
            
            // Nút Next
            html += `<button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                        <i class="fas fa-chevron-right"></i>
                    </button>`;
            
            // Thông tin trang
            html += `<span class="page-info">Trang ${currentPage}/${totalPages} (Tổng: ${totalStudents} HS)</span>`;
            
            html += '</div>';
            return html;
        }

        function changePage(page) {
            const totalPages = Math.ceil(totalStudents / itemsPerPage);
            if (page < 1 || page > totalPages) return;
            
            currentPage = page;
            
            // Hiển thị lại dữ liệu với trang mới
            if (currentTab === 'academic') {
                displayAcademic();
            } else if (currentTab === 'conduct') {
                displayConduct();
            } else if (currentTab === 'title') {
                displayTitles();
            }
        }

        function displayAcademic() {
            const students = getPaginatedStudents();
            
            if (allStudents.length === 0) {
                document.getElementById('academicContent').innerHTML = '<div class="alert alert-error">Chưa có dữ liệu điểm. Vui lòng nhập điểm trước khi xếp loại.</div>';
                return;
            }

            // Lấy danh sách tên môn từ học sinh đầu tiên
            let subjectNames = [];
            if (allStudents[0] && allStudents[0].grades && allStudents[0].grades.length > 0) {
                subjectNames = allStudents[0].grades.map(grade => grade.tenMonHoc || '');
            }

            let html = `
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th rowspan="2" style="min-width: 40px; width: 40px;">STT</th>
                                <th rowspan="2" style="min-width: 150px; width: 180px;">Họ tên</th>
                                <th colspan="${subjectNames.length}" style="text-align: center;">Điểm trung bình các môn</th>
                                <th rowspan="2" style="min-width: 60px; width: 70px;">ĐTB</th>
                                <th rowspan="2" style="min-width: 80px; width: 100px;">Xếp loại</th>
                            </tr>
                            <tr>
            `;

            // Hiển thị tên môn học
            subjectNames.forEach(subjectName => {
                html += `<th style="min-width: 50px; width: 60px; text-align: center;">${subjectName}</th>`;
            });

            html += `
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            const startIndex = (currentPage - 1) * itemsPerPage;
            students.forEach((student, index) => {
                const globalIndex = startIndex + index + 1;
                html += `
                    <tr>
                        <td style="text-align: center;">${globalIndex}</td>
                        <td style="text-align: left;">${student.hoTen}</td>
                `;
                
                // Hiển thị điểm các môn
                student.grades.forEach(grade => {
                    if (grade.tbDiem === null || grade.tbDiem === undefined || grade.tbDiem === '') {
                        html += `<td style="color: #999; text-align: center;">-</td>`;
                    } else {
                        html += `<td style="text-align: center;">${parseFloat(grade.tbDiem).toFixed(1)}</td>`;
                    }
                });
                
                // Thêm các cột trống nếu học sinh có ít môn hơn
                for (let i = student.grades.length; i < subjectNames.length; i++) {
                    html += `<td style="color: #999; text-align: center;">-</td>`;
                }
                
                // Hiển thị điểm TB và xếp loại
                // Backend chỉ trả về diemTB_HS khi học sinh có đủ điểm tất cả các môn
                let avgDisplay = '<span style="color: #999;">-</span>';
                let rankingDisplay = '<span style="color: #999;">-</span>';
                
                if (student.diemTB_HS !== null && student.diemTB_HS !== undefined) {
                    avgDisplay = `<strong>${parseFloat(student.diemTB_HS).toFixed(2)}</strong>`;
                    
                    if (student.ranking && student.ranking.ranking && student.ranking.ranking !== 'Chưa xếp loại' && student.ranking.ranking !== null) {
                        rankingDisplay = formatRanking(student.ranking.ranking);
                    }
                }
                
                html += `
                        <td style="text-align: center;">${avgDisplay}</td>
                        <td style="text-align: center;">${rankingDisplay}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            // Thêm phân trang
            html += createPagination();
            
            document.getElementById('academicContent').innerHTML = html;
        }

        // Lưu trữ thay đổi hạnh kiểm
        let conductChanges = {};

        function displayConduct() {
            const students = getPaginatedStudents();
            
            if (allStudents.length === 0) {
                document.getElementById('conductContent').innerHTML = '<div class="alert alert-error">Chưa có dữ liệu học sinh.</div>';
                return;
            }

            // Reset changes
            conductChanges = {};

            let html = `
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th rowspan="2" style="min-width: 40px; width: 40px;">STT</th>
                                <th rowspan="2" style="min-width: 150px; width: 180px;">Họ tên</th>
                                <th colspan="2" style="text-align: center;">Số buổi nghỉ</th>
                                <th colspan="3" style="text-align: center;">Số lần vi phạm</th>
                                <th rowspan="2" style="min-width: 80px; width: 90px;">Học lực</th>
                                <th rowspan="2" style="min-width: 100px; width: 120px;">Xếp loại HK</th>
                                <th rowspan="2" style="min-width: 120px; width: 140px;">Xếp loại thủ công</th>
                            </tr>
                            <tr>
                                <th style="min-width: 60px; width: 70px;">Có phép</th>
                                <th style="min-width: 70px; width: 85px;">Không phép</th>
                                <th style="min-width: 50px; width: 60px;">Nhẹ</th>
                                <th style="min-width: 70px; width: 80px;">Trung bình</th>
                                <th style="min-width: 50px; width: 60px;">Nặng</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            const startIndex = (currentPage - 1) * itemsPerPage;
            students.forEach((student, index) => {
                const globalIndex = startIndex + index + 1;
                
                // Lấy giá trị xếp loại: Ưu tiên DB, nếu không có thì lấy từ tính toán tự động
                // CHỈ HIỂN THỊ XẾP LOẠI TỰ ĐỘNG NẾU HỌC SINH ĐÃ CÓ HỌC LỰC
                let currentRanking = student.hanhKiem;
                
                if (!currentRanking && student.loaiHocLuc && student.conductRanking && student.conductRanking.ranking) {
                    currentRanking = student.conductRanking.ranking;
                }
                
                if (!currentRanking) {
                    currentRanking = null;
                }
                
                const normalizedRanking = normalizeRankingForSelect(currentRanking);
                
                html += `
                    <tr>
                        <td style="text-align: center;">${globalIndex}</td>
                        <td style="text-align: left;">${student.hoTen}</td>
                        <td style="text-align: center;">${student.soBuoiNghiCoPhep}</td>
                        <td style="text-align: center;">${student.soBuoiNghiKhongPhep}</td>
                        <td style="text-align: center;">${student.soLanViPhamNhe}</td>
                        <td style="text-align: center;">${student.soLanViPhamTB}</td>
                        <td style="text-align: center;">${student.soLanViPhamNang}</td>
                        <td style="text-align: center;">${student.loaiHocLuc ? formatRanking(student.loaiHocLuc) : '<span style="color: #999;">-</span>'}</td>
                        <td style="text-align: center;">${formatRanking(currentRanking)}</td>
                        <td style="text-align: center;">
                            <div class="select-wrapper" id="wrapper-${student.maHS}">
                                <select class="conduct-select" 
                                        data-mahs="${student.maHS}" 
                                        data-original="${currentRanking || ''}"
                                        onchange="markConductChanged(this)">
                                    <option value="">-- Chọn --</option>
                                    <option value="Tốt" ${normalizedRanking === 'Tốt' ? 'selected' : ''}>Tốt</option>
                                    <option value="Khá" ${normalizedRanking === 'Khá' ? 'selected' : ''}>Khá</option>
                                    <option value="Đạt" ${normalizedRanking === 'Đạt' ? 'selected' : ''}>Đạt</option>
                                    <option value="Chưa đạt" ${normalizedRanking === 'Chưa đạt' ? 'selected' : ''}>Chưa đạt</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            // Thêm phân trang
            html += createPagination();
            
            document.getElementById('conductContent').innerHTML = html;
        }

        function normalizeRankingForSelect(ranking) {
            if (!ranking) return '';
            
            const normalized = ranking.toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/đ/g, "d")
                .replace(/\s+/g, "");
            
            const mapping = {
                'tot': 'Tốt',
                'kha': 'Khá',
                'dat': 'Đạt',
                'chuadat': 'Chưa đạt'
            };
            
            return mapping[normalized] || ranking;
        }

        function normalizeRanking(ranking) {
            if (!ranking) return '';
            
            return ranking.toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/đ/g, "d")
                .replace(/\s+/g, "");
        }

        function markConductChanged(selectElement) {
            const maHS = selectElement.dataset.mahs;
            const loaiHK = selectElement.value;
            const originalValue = selectElement.dataset.original;
            
            // Chuẩn hóa để so sánh
            const normalizedOriginal = normalizeRanking(originalValue);
            const normalizedNew = normalizeRanking(loaiHK);
            
            // Chỉ đánh dấu thay đổi nếu giá trị khác với giá trị gốc
            if (normalizedOriginal !== normalizedNew) {
                // Đánh dấu đã thay đổi cho select
                selectElement.classList.add('changed');
                
                // Đánh dấu đã thay đổi cho wrapper
                const wrapper = document.getElementById('wrapper-' + maHS);
                if (wrapper) {
                    wrapper.classList.add('changed');
                }
                
                // Lưu vào object thay đổi
                conductChanges[maHS] = loaiHK;
            } else {
                // Nếu chọn lại giá trị gốc thì bỏ đánh dấu
                selectElement.classList.remove('changed');
                
                const wrapper = document.getElementById('wrapper-' + maHS);
                if (wrapper) {
                    wrapper.classList.remove('changed');
                }
                
                // Xóa khỏi object thay đổi
                delete conductChanges[maHS];
            }
        }

        function saveConductManual() {
            // Kiểm tra có thay đổi nào không
            if (Object.keys(conductChanges).length === 0) {
                const alertDiv = document.getElementById('alertMessage');
                alertDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-info-circle"></i> Chưa có thay đổi nào để lưu. Vui lòng chọn xếp loại hạnh kiểm cho học sinh.</div>';
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 3000);
                return;
            }

            if (!confirm(`Bạn có chắc chắn muốn lưu xếp loại hạnh kiểm cho ${Object.keys(conductChanges).length} học sinh?`)) {
                return;
            }
            
            const maLop = document.getElementById('maLop').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            
            // Chuyển đổi object thành array
            const conductData = Object.keys(conductChanges).map(maHS => ({
                maHS: maHS,
                loaiHK: conductChanges[maHS]
            }));
            
            // Hiển thị loading
            const alertDiv = document.getElementById('alertMessage');
            alertDiv.innerHTML = '<div class="alert" style="background: #e3f2fd; border: 1px solid #90caf9; color: #1565c0;"><i class="fas fa-spinner fa-spin"></i> Đang lưu dữ liệu...</div>';
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=saveConductManual&maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}&conductData=${encodeURIComponent(JSON.stringify(conductData))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alertDiv.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i> ${data.message}</div>`;
                    
                    // Reset changes
                    conductChanges = {};
                    
                    // Clear cache dữ liệu cũ để buộc reload
                    allStudents = [];
                    totalStudents = 0;
                    
                    // Đánh dấu cần refresh dữ liệu cho các tab khác
                    needsDataRefresh = true;
                    
                    // Reload data của tab hiện tại với force reload
                    setTimeout(() => {
                        loadData(true); // Force reload với timestamp
                    }, 1000);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
                }
                
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 5000);
            })
            .catch(error => {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Lỗi khi lưu dữ liệu. Vui lòng thử lại.</div>';
                
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 5000);
            });
        }

        function formatRanking(ranking) {
            if (!ranking || ranking === null) {
                return '<span style="color: #999;">-</span>';
            }
            
            // Chuẩn hóa tên xếp loại (bỏ dấu, viết thường)
            const normalizedRanking = ranking.toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/đ/g, "d")
                .replace(/\s+/g, "");
            
            const classes = {
                'tot': 'ranking-tot',
                'tốt': 'ranking-tot',
                'kha': 'ranking-kha',
                'khá': 'ranking-kha',
                'dat': 'ranking-dat',
                'đạt': 'ranking-dat',
                'trungbinh': 'ranking-dat',
                'tb': 'ranking-dat',
                'chuadat': 'ranking-chuadat',
                'yeu': 'ranking-chuadat'
            };
            
            const labels = {
                'tot': 'Tốt',
                'tốt': 'Tốt',
                'kha': 'Khá',
                'khá': 'Khá',
                'dat': 'Đạt',
                'đạt': 'Đạt',
                'trungbinh': 'TB',
                'tb': 'TB',
                'chuadat': 'Chưa đạt',
                'yeu': 'Yếu'
            };
            
            const cssClass = classes[normalizedRanking] || 'ranking-chuaxeploai';
            const label = labels[normalizedRanking] || ranking;
            
            return `<span class="ranking-badge ${cssClass}">${label}</span>`;
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN');
        }

        // Load data on page load
        window.onload = function() {
            loadData();
        };

        function openCriteriaModal() {
            // Load cấu hình hiện tại
            fetch('?action=getCriteria')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.criteria) {
                        loadCriteriaToForm(data.criteria);
                    }
                    document.getElementById('criteriaModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('criteriaModal').style.display = 'block';
                });
        }

        function closeCriteriaModal() {
            document.getElementById('criteriaModal').style.display = 'none';
        }

        function loadCriteriaToForm(criteria) {
            const rankings = ['tot', 'kha', 'dat'];
            const rankingMap = {
                'tot': 'Tốt',
                'kha': 'Khá',
                'dat': 'Đạt'
            };
            
            rankings.forEach(rank => {
                const rankingName = rankingMap[rank];
                if (criteria[rankingName]) {
                    const c = criteria[rankingName];
                    document.getElementById(`${rank}-cophep`).value = c.maxNghiCoPhep || 0;
                    document.getElementById(`${rank}-khongphep`).value = c.maxNghiKhongPhep || 0;
                    document.getElementById(`${rank}-nhe`).value = c.maxViPhamNhe || 0;
                    document.getElementById(`${rank}-tb`).value = c.maxViPhamTB || 0;
                    document.getElementById(`${rank}-nang`).value = c.maxViPhamNang || 0;
                    document.getElementById(`${rank}-hocluc`).value = c.minHocLuc || '';
                }
            });
        }

        function getCriteriaFromForm() {
            return {
                'Tốt': {
                    maxNghiCoPhep: parseInt(document.getElementById('tot-cophep').value) || 0,
                    maxNghiKhongPhep: parseInt(document.getElementById('tot-khongphep').value) || 0,
                    maxViPhamNhe: parseInt(document.getElementById('tot-nhe').value) || 0,
                    maxViPhamTB: parseInt(document.getElementById('tot-tb').value) || 0,
                    maxViPhamNang: parseInt(document.getElementById('tot-nang').value) || 0,
                    minHocLuc: document.getElementById('tot-hocluc').value
                },
                'Khá': {
                    maxNghiCoPhep: parseInt(document.getElementById('kha-cophep').value) || 0,
                    maxNghiKhongPhep: parseInt(document.getElementById('kha-khongphep').value) || 0,
                    maxViPhamNhe: parseInt(document.getElementById('kha-nhe').value) || 0,
                    maxViPhamTB: parseInt(document.getElementById('kha-tb').value) || 0,
                    maxViPhamNang: parseInt(document.getElementById('kha-nang').value) || 0,
                    minHocLuc: document.getElementById('kha-hocluc').value
                },
                'Đạt': {
                    maxNghiCoPhep: parseInt(document.getElementById('dat-cophep').value) || 0,
                    maxNghiKhongPhep: parseInt(document.getElementById('dat-khongphep').value) || 0,
                    maxViPhamNhe: parseInt(document.getElementById('dat-nhe').value) || 0,
                    maxViPhamTB: parseInt(document.getElementById('dat-tb').value) || 0,
                    maxViPhamNang: parseInt(document.getElementById('dat-nang').value) || 0,
                    minHocLuc: document.getElementById('dat-hocluc').value
                },
                'Chưa đạt': {
                    maxNghiCoPhep: 999,
                    maxNghiKhongPhep: 999,
                    maxViPhamNhe: 999,
                    maxViPhamTB: 999,
                    maxViPhamNang: 999,
                    minHocLuc: ''
                }
            };
        }

        function saveCriteriaAndClassify() {
            const criteria = getCriteriaFromForm();
            const maLop = document.getElementById('maLop').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            
            if (!confirm('Bạn có chắc chắn muốn lưu cấu hình và áp dụng xếp loại tự động cho tất cả học sinh?')) {
                return;
            }
            
            const alertDiv = document.getElementById('alertMessage');
            alertDiv.innerHTML = '<div class="alert" style="background: #e3f2fd; border: 1px solid #90caf9; color: #1565c0;"><i class="fas fa-spinner fa-spin"></i> Đang xử lý...</div>';
            
            // Đóng modal trước
            closeCriteriaModal();
            
            // Bước 1: Lưu cấu hình
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=saveCriteria&criteria=${encodeURIComponent(JSON.stringify(criteria))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Bước 2: Áp dụng xếp loại tự động
                    return fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=autoClassify&maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}`
                    });
                } else {
                    throw new Error(data.message || 'Lỗi khi lưu cấu hình');
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alertDiv.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i> ${data.message}</div>`;
                    
                    // Reload dữ liệu sau 1 giây để thấy kết quả
                    setTimeout(() => {
                        loadData();
                        alertDiv.innerHTML = '';
                    }, 1500);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
                    setTimeout(() => {
                        alertDiv.innerHTML = '';
                    }, 5000);
                }
            })
            .catch(error => {
                closeCriteriaModal();
                console.error('Error:', error);
                alertDiv.innerHTML = `<div class="alert alert-error"><i class="fas fa-times-circle"></i> ${error.message || 'Lỗi khi xử lý. Vui lòng thử lại.'}</div>`;
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 5000);
            });
        }

        function displayTitles() {
            console.log('[displayTitles] Called with allStudents length:', allStudents.length);
            
            const students = getPaginatedStudents();
            
            if (!allStudents || allStudents.length === 0) {
                console.warn('[displayTitles] No students data');
                document.getElementById('titleContent').innerHTML = `
                    <div class="alert alert-error">
                        <i class="fas fa-info-circle"></i> Chưa có dữ liệu học sinh trong lớp này.
                    </div>`;
                return;
            }

            console.log('[displayTitles] Displaying', students.length, 'students');

            let html = `
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="min-width: 40px; width: 40px;">STT</th>
                                <th style="min-width: 150px; width: 180px;">Họ tên</th>
                                <th style="min-width: 80px; width: 100px;">Học lực</th>
                                <th style="min-width: 80px; width: 100px;">Hạnh kiểm</th>
                                <th style="min-width: 130px; width: 160px;">Danh hiệu</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            const startIndex = (currentPage - 1) * itemsPerPage;
            students.forEach((student, index) => {
                const globalIndex = startIndex + index + 1;
                
                // Xử lý dữ liệu null/undefined
                const hoTen = student.hoTen || 'N/A';
                const loaiHocLuc = student.loaiHocLuc || null;
                const hanhKiem = student.hanhKiem || null;
                const tenDanhHieu = student.tenDanhHieu || null;
                
                html += `
                    <tr>
                        <td style="text-align: center;">${globalIndex}</td>
                        <td style="text-align: left;">${hoTen}</td>
                        <td style="text-align: center;">${loaiHocLuc ? formatRanking(loaiHocLuc) : '<span style="color: #999;">-</span>'}</td>
                        <td style="text-align: center;">${hanhKiem ? formatRanking(hanhKiem) : '<span style="color: #999;">-</span>'}</td>
                        <td style="text-align: center;">${tenDanhHieu ? formatTitle(tenDanhHieu) : '<span style="color: #999;">Chưa xếp</span>'}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            // Thêm phân trang
            html += createPagination();
            
            document.getElementById('titleContent').innerHTML = html;
            console.log('[displayTitles] Table rendered successfully');
        }

        function formatTitle(title) {
            if (!title) {
                return '<span style="color: #999;">-</span>';
            }
            
            if (title.includes('xuất sắc') || title.includes('Xuất sắc')) {
                return `<span class="title-badge title-xuatsac"><i class="fas fa-trophy"></i> ${title}</span>`;
            } else if (title.includes('giỏi') || title.includes('Giỏi')) {
                return `<span class="title-badge title-gioi"><i class="fas fa-medal"></i> ${title}</span>`;
            }
            
            return `<span class="title-badge">${title}</span>`;
        }

        // Xếp loại danh hiệu tự động
        function classifyAllTitles() {
            const maLop = document.getElementById('maLop').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            
            if (!confirm('Bạn có chắc chắn muốn xếp loại danh hiệu tự động cho tất cả học sinh?\n\nTiêu chí:\n• Học sinh xuất sắc: Học lực Tốt + Hạnh kiểm Tốt + ít nhất 6 môn ≥ 9.0\n• Học sinh giỏi: Học lực Tốt + Hạnh kiểm Tốt')) {
                return;
            }
            
            const alertDiv = document.getElementById('alertMessage');
            alertDiv.innerHTML = '<div class="alert" style="background: #e3f2fd; border: 1px solid #90caf9; color: #1565c0;"><i class="fas fa-spinner fa-spin"></i> Đang xếp loại danh hiệu...</div>';
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=classifyTitles&maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alertDiv.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i> ${data.message}</div>`;
                    
                    // Đánh dấu cần refresh và reload data
                    needsDataRefresh = true;
                    setTimeout(() => {
                        loadData(true);
                        alertDiv.innerHTML = '';
                    }, 1500);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
                    setTimeout(() => {
                        alertDiv.innerHTML = '';
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Lỗi khi xếp loại danh hiệu. Vui lòng thử lại.</div>';
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 5000);
            });
        }

        // ...existing code for other functions...

        // Load data on page load
        window.onload = function() {
            loadData();
        };

        // ...existing code...
    </script>
</body>

</html>
