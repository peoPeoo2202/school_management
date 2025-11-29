<?php
require_once(__DIR__ . '/../../config.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: " . url('public/index.php'));
    exit();
}

// Kiểm tra quyền giáo viên
if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: " . url('public/index.php?error=access_denied'));
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giao Bài Tập - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <style>
        .main-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .content-area {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
            height: 100vh;
            box-sizing: border-box;
            background: #f5f5f5;
        }

        .page-header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .page-header h2 {
            color: #5081BE;
            margin: 0 0 8px 0;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header p {
            color: #999;
            margin: 0;
            font-size: 14px;
        }

        .action-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            justify-content: flex-start;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-primary {
            background: #5081BE;
            color: white;
        }

        .btn-primary:hover {
            background: #2d5a8c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(80, 129, 190, 0.3);
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .filter-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .filter-card h3 {
            margin: 0 0 16px 0;
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }

        .form-select:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 3px rgba(80, 129, 190, 0.1);
        }

        .homework-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .homework-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s;
        }

        .homework-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #5081BE 0%, #2d5a8c 100%);
            color: white;
            padding: 16px 20px;
        }

        .card-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        .card-body p {
            margin: 0 0 12px 0;
            font-size: 14px;
            color: #666;
            display: flex;
            gap: 8px;
        }

        .card-body p strong {
            color: #333;
            min-width: 80px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.completed {
            background: #d4edda;
            color: #155724;
        }

        .card-footer {
            padding: 16px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
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

        .empty-state {
            background: white;
            padding: 60px 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 24px;
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
            background: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s;
            backdrop-filter: blur(2px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-dialog {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            animation: slideDown 0.4s ease-out;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 16px 24px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #5081BE 0%, #2d5a8c 100%);
            border-radius: 16px 16px 0 0;
            flex-shrink: 0;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-title::before {
            content: "📝";
            font-size: 20px;
        }

        .btn-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: white;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
            font-weight: 300;
        }

        .btn-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 32px;
            background: #fafafa;
            overflow-y: auto;
            flex: 1;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 4px rgba(80, 129, 190, 0.1);
            background: white;
        }

        .form-control::placeholder {
            color: #bbb;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .mb-3 {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            display: block;
        }

        .form-label span {
            color: #dc3545;
            margin-left: 2px;
        }

        .modal-footer {
            padding: 20px 32px;
            border-top: 2px solid #f0f0f0;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            background: white;
            border-radius: 0 0 16px 16px;
            flex-shrink: 0;
        }

        .modal-footer .btn {
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            min-width: 120px;
        }

        @keyframes fadeIn {
            from { 
                opacity: 0; 
            }
            to { 
                opacity: 1; 
            }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100px) scale(0.9);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .file-link {
            color: #5081BE;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .file-link:hover {
            color: #2d5a8c;
            text-decoration: underline;
            gap: 8px;
        }

        .file-input-wrapper {
            position: relative;
        }

        .file-input-wrapper .file-upload-area {
            position: relative;
            border: 2px dashed #d0d0d0;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-wrapper .file-upload-area:hover {
            border-color: #5081BE;
            background: #f8f9fa;
        }

        .file-input-wrapper .file-upload-area.dragover {
            border-color: #5081BE;
            background: #e3f2fd;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-icon {
            font-size: 48px;
            color: #5081BE;
            margin-bottom: 12px;
        }

        .file-upload-text {
            font-size: 14px;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .file-upload-hint {
            font-size: 12px;
            color: #666;
            line-height: 1.5;
        }

        .file-name-display {
            margin-top: 12px;
            padding: 10px 14px;
            background: #e8f5e9;
            border: 1px solid #81c784;
            border-radius: 8px;
            font-size: 13px;
            color: #2e7d32;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .file-name-display.show {
            display: flex;
        }

        .file-name-display i.fa-file-alt {
            color: #43a047;
            font-size: 18px;
        }

        .file-name-display .file-info {
            flex: 1;
            font-weight: 500;
        }

        .file-name-display .remove-file {
            cursor: pointer;
            color: #fff;
            background: #ef5350;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .file-name-display .remove-file:hover {
            background: #e53935;
            transform: scale(1.05);
        }

        /* Scrollbar styling cho modal body */
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: rgba(80, 129, 190, 0.3);
            border-radius: 10px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: rgba(80, 129, 190, 0.5);
        }

        /* Firefox scrollbar */
        .modal-body {
            scrollbar-width: thin;
            scrollbar-color: rgba(80, 129, 190, 0.3) transparent;
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 16px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .page-header-right {
                width: 100%;
            }

            .page-header-right .btn {
                flex: 1;
            }

            .homework-grid {
                grid-template-columns: 1fr;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }

            .modal-dialog {
                width: 95%;
                margin: 10px;
                max-width: none;
            }

            .modal-header {
                padding: 20px;
            }

            .modal-body {
                padding: 20px;
            }

            .modal-footer {
                padding: 16px 20px;
                flex-direction: column;
            }

            .modal-footer .btn {
                width: 100%;
                min-width: auto;
            }
        }

        /* Datetime picker styling */
        input[type="datetime-local"] {
            position: relative;
            padding-right: 45px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%235081BE' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: calc(100% - 12px) center;
            background-size: 20px;
        }

        input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            position: absolute;
            right: 12px;
            cursor: pointer;
            opacity: 0;
            width: 20px;
            height: 20px;
        }

        /* Radio button styling */
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #5081BE;
            border-radius: 50%;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
        }

        .radio-option input[type="radio"]:checked {
            background: #5081BE;
            border-color: #5081BE;
        }

        .radio-option input[type="radio"]:checked::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
        }

        .radio-option input[type="radio"]:hover {
            border-color: #2d5a8c;
            box-shadow: 0 0 0 4px rgba(80, 129, 190, 0.1);
        }

        .radio-option label {
            cursor: pointer;
            user-select: none;
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .radio-option input[type="radio"]:checked + label {
            color: #5081BE;
        }

        /* Date time info helper */
        .datetime-helper {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            padding: 8px 12px;
            background: #e3f2fd;
            border-radius: 6px;
            font-size: 12px;
            color: #1565c0;
        }

        .datetime-helper i {
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">
            <!-- Page Header -->
            <div class="page-header">
                <h2><i class="fas fa-clipboard-list"></i> Giao Bài Tập</h2>
                <p>Quản lý và giao bài tập cho học sinh</p>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus-circle"></i> Giao Bài Tập Mới
                </button>
            </div>

            <!-- Filter Card -->
            <div class="filter-card">
                <form method="GET" action="">
                    <div class="filter-row">
                        <div class="form-group">
                            <label class="form-label">Lọc theo lớp</label>
                            <select name="maLop" id="filterClass" class="form-select">
                                <option value="">-- Tất cả lớp --</option>
                                <?php while($class = $classes->fetch_assoc()): ?>
                                    <option value="<?= $class['maLop'] ?>" 
                                        <?= (isset($_GET['maLop']) && $_GET['maLop'] == $class['maLop']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['tenLop']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-info" style="width: 100%;">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Homework Grid -->
            <?php if($homeworks->num_rows > 0): ?>
                <div class="homework-grid">
                    <?php while($hw = $homeworks->fetch_assoc()): ?>
                        <div class="homework-card">
                            <div class="card-header">
                                <h3><?= htmlspecialchars($hw['tenBaiTap']) ?></h3>
                            </div>
                            <div class="card-body">
                                <p><strong>Lớp:</strong> <?= htmlspecialchars($hw['tenLop']) ?></p>
                                <p><strong>Hạn nộp:</strong> <?= date('d/m/Y H:i', strtotime($hw['thoiGianNop'])) ?></p>
                                <p>
                                    <strong>Đã nộp:</strong> 
                                    <span class="status-badge <?= ($hw['soLuongNopBai'] ?? 0) > 0 ? 'completed' : 'pending' ?>">
                                        <?= $hw['soLuongNopBai'] ?? 0 ?> / <?= $hw['siSoLop'] ?? 0 ?> học sinh
                                    </span>
                                </p>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-sm btn-warning" onclick="editHomework(<?= $hw['maBaiTap'] ?>)">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteHomework(<?= $hw['maBaiTap'] ?>)">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                                <a href="<?php echo url('view/teacher/vHomeworkDetail.php?id=' . $hw['maBaiTap']); ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Chi tiết
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Chưa có bài tập nào</h3>
                    <p>Hãy bắt đầu bằng cách giao bài tập mới cho học sinh</p>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus-circle"></i> Giao Bài Tập Đầu Tiên
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Thêm Bài Tập -->
    <div class="modal" id="addHomeworkModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h5 class="modal-title">Giao Bài Tập Mới</h5>
                <button type="button" class="btn-close" onclick="closeAddModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addHomeworkForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Tên bài tập <span style="color: red;">*</span></label>
                        <input type="text" name="tenBaiTap" class="form-control" placeholder="Nhập tên bài tập" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lớp <span style="color: red;">*</span></label>
                        <select name="maLop" id="addClass" class="form-select form-control" required>
                            <option value="">-- Chọn lớp --</option>
                            <?php 
                            $classes->data_seek(0);
                            while($class = $classes->fetch_assoc()): ?>
                                <option value="<?= $class['maLop'] ?>"><?= htmlspecialchars($class['tenLop']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="datetime-helper" style="margin-top: 8px;">
                            <i class="fas fa-info-circle"></i>
                            <span>Môn học sẽ tự động được xác định dựa trên phân công giảng dạy của bạn</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yêu cầu bài tập</label>
                        <textarea name="yeuCauBaiTap" class="form-control" rows="4" placeholder="Mô tả yêu cầu bài tập..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File đính kèm</label>
                        <div class="file-input-wrapper">
                            <div class="file-upload-area" id="fileUploadArea">
                                <input type="file" name="file" id="add_file" class="form-control" 
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                       onchange="handleFileSelect(this)">
                                <div class="file-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="file-upload-text">Chọn file hoặc kéo thả vào đây</div>
                                <div class="file-upload-hint">
                                    Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX (Tối đa 10MB)
                                </div>
                            </div>
                            <div class="file-name-display" id="fileNameDisplay">
                                <i class="fas fa-file-alt"></i>
                                <span class="file-info" id="fileName"></span>
                                <span class="remove-file" onclick="removeFile()">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Thời gian nộp <span style="color: red;">*</span></label>
                        <input type="datetime-local" name="thoiGianNop" id="thoiGianNop" class="form-control" required>
                        <div class="datetime-helper">
                            <i class="fas fa-info-circle"></i>
                            <span>Chọn ngày và giờ hạn nộp bài tập</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cho phép nộp trễ</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="allowLate" name="choPhepNopTre" value="1" checked>
                                <label for="allowLate">Cho phép nộp trễ</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="notAllowLate" name="choPhepNopTre" value="0">
                                <label for="notAllowLate">Không cho phép nộp trễ</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="submitHomework()">
                    <i class="fas fa-check"></i> Giao Bài Tập
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Sửa Bài Tập -->
    <div class="modal" id="editHomeworkModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h5 class="modal-title">Sửa Bài Tập</h5>
                <button type="button" class="btn-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editHomeworkForm" enctype="multipart/form-data">
                    <input type="hidden" name="maBaiTap" id="edit_maBaiTap">
                    
                    <div class="mb-3">
                        <label class="form-label">Tên bài tập <span style="color: red;">*</span></label>
                        <input type="text" name="tenBaiTap" id="edit_tenBaiTap" class="form-control" placeholder="Nhập tên bài tập" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Lớp <span style="color: red;">*</span></label>
                        <select name="maLop" id="edit_maLop" class="form-select form-control" required>
                            <option value="">-- Chọn lớp --</option>
                            <?php 
                            $classes->data_seek(0);
                            while($class = $classes->fetch_assoc()): ?>
                                <option value="<?= $class['maLop'] ?>"><?= htmlspecialchars($class['tenLop']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="datetime-helper" style="margin-top: 8px;">
                            <i class="fas fa-info-circle"></i>
                            <span>Môn học sẽ tự động được xác định dựa trên phân công giảng dạy của bạn</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Yêu cầu bài tập</label>
                        <textarea name="yeuCauBaiTap" id="edit_yeuCauBaiTap" class="form-control" rows="4" placeholder="Mô tả yêu cầu bài tập..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">File đính kèm hiện tại</label>
                        <div id="edit_currentFile" class="file-name-display" style="display: none;">
                            <i class="fas fa-file-alt"></i>
                            <span class="file-info" id="edit_currentFileName"></span>
                            <span class="remove-file" onclick="removeCurrentFile()">
                                <i class="fas fa-times"></i> Xóa file cũ
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Thay đổi file đính kèm</label>
                        <div class="file-input-wrapper">
                            <div class="file-upload-area" id="editFileUploadArea">
                                <input type="file" name="file" id="edit_file" class="form-control" 
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                       onchange="handleEditFileSelect(this)">
                                <div class="file-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="file-upload-text">Chọn file mới hoặc kéo thả vào đây</div>
                                <div class="file-upload-hint">
                                    Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX (Tối đa 10MB)
                                </div>
                            </div>
                            <div class="file-name-display" id="editFileNameDisplay">
                                <i class="fas fa-file-alt"></i>
                                <span class="file-info" id="editFileName"></span>
                                <span class="remove-file" onclick="removeEditFile()">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Thời gian nộp <span style="color: red;">*</span></label>
                        <input type="datetime-local" name="thoiGianNop" id="edit_thoiGianNop" class="form-control" required>
                        <div class="datetime-helper">
                            <i class="fas fa-info-circle"></i>
                            <span>Chọn ngày và giờ hạn nộp bài tập</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cho phép nộp trễ</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="edit_allowLate" name="choPhepNopTre" value="1">
                                <label for="edit_allowLate">Cho phép nộp trễ</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="edit_notAllowLate" name="choPhepNopTre" value="0">
                                <label for="edit_notAllowLate">Không cho phép nộp trễ</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" onclick="submitEditHomework()">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('addHomeworkModal').classList.add('show');
        }

        function closeAddModal() {
            document.getElementById('addHomeworkModal').classList.remove('show');
            document.getElementById('addHomeworkForm').reset();
            document.getElementById('fileNameDisplay').classList.remove('show');
        }

        // Handle file select for ADD modal
        function handleFileSelect(input) {
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const fileName = document.getElementById('fileName');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                fileName.textContent = file.name;
                fileNameDisplay.classList.add('show');
            } else {
                fileNameDisplay.classList.remove('show');
            }
        }

        // Remove selected file from ADD modal
        function removeFile() {
            const fileInput = document.getElementById('add_file');
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            
            fileInput.value = '';
            fileNameDisplay.classList.remove('show');
        }

        // Handle file select for EDIT modal
        function handleEditFileSelect(input) {
            const fileNameDisplay = document.getElementById('editFileNameDisplay');
            const fileName = document.getElementById('editFileName');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                fileName.textContent = file.name;
                fileNameDisplay.classList.add('show');
            } else {
                fileNameDisplay.classList.remove('show');
            }
        }

        // Remove selected file from EDIT modal
        function removeEditFile() {
            const fileInput = document.getElementById('edit_file');
            const fileNameDisplay = document.getElementById('editFileNameDisplay');
            
            fileInput.value = '';
            fileNameDisplay.classList.remove('show');
        }

        // Remove current file (đánh dấu xóa file cũ)
        function removeCurrentFile() {
            if(confirm('Bạn có chắc chắn muốn xóa file đính kèm hiện tại?')) {
                document.getElementById('edit_currentFile').style.display = 'none';
                // Thêm input hidden để đánh dấu xóa file
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'removeCurrentFile';
                input.value = '1';
                document.getElementById('editHomeworkForm').appendChild(input);
            }
        }

        // Prevent default drag behaviors
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Submit homework với file (không cần load subjects nữa)
        function submitHomework() {
            const form = document.getElementById('addHomeworkForm');
            
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);
            
            fetch('<?php echo url("controller/cAssignHomework.php?action=create"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then data => {
                if(data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Có lỗi xảy ra khi giao bài tập');
            });
        }

        // Delete homework
        function deleteHomework(maBaiTap) {
            if(!confirm('Bạn có chắc chắn muốn xóa bài tập này?')) return;
            
            fetch('<?php echo url("controller/cAssignHomework.php?action=delete"); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'maBaiTap=' + maBaiTap
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if(data.success) location.reload();
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Có lỗi xảy ra khi xóa bài tập');
            });
        }

        // Edit homework
        function editHomework(maBaiTap) {
            fetch('<?php echo url("controller/cAssignHomework.php?action=getHomework&id="); ?>' + maBaiTap)
                .then(res => {
                    console.log('Response status:', res.status); // Debug log
                    return res.json();
                })
                .then(data => {
                    console.log('Response status:', res.status); // Debug log
                    console.log('Response data:', data); // Debug log
                    
                    if(data.success) {
                        const hw = data.homework;
                        
                        // Điền thông tin vào form
                        document.getElementById('edit_maBaiTap').value = hw.maBaiTap;
                        document.getElementById('edit_tenBaiTap').value = hw.tenBaiTap;
                        document.getElementById('edit_maLop').value = hw.maLop;
                        document.getElementById('edit_yeuCauBaiTap').value = hw.yeuCauBaiTap || '';
                        document.getElementById('edit_tenBaiTap').value = hw.tenBaiTap;
                        // Chuyển đổi format datetime
                        const dateTime = hw.thoiGianNop.replace(' ', 'T').slice(0, 16);
                        document.getElementById('edit_thoiGianNop').value = dateTime;
                        // Chuyển đổi format datetime
                        // Set radio button cho phép nộp trễ
                        if(hw.choPhepNopTre == 1) {
                            document.getElementById('edit_allowLate').checked = true;
                        } else {
                            document.getElementById('edit_notAllowLate').checked = true;
                        }   document.getElementById('edit_allowLate').checked = true;
                        } else {
                        // Hiển thị file hiện tại nếu có
                        if(hw.tenFile) {
                            document.getElementById('edit_currentFile').style.display = 'flex';
                            document.getElementById('edit_currentFileName').textContent = hw.tenFile;
                        } else {
                            document.getElementById('edit_currentFile').style.display = 'none';
                        }   document.getElementById('edit_currentFileName').textContent = hw.tenFile;
                        } else {
                        // Mở modal
                        console.log('Opening edit modal'); // Debug log
                        document.getElementById('editHomeworkModal').classList.add('show');
                    } else {
                        alert('Không thể tải thông tin bài tập: ' + data.message);
                    }   document.getElementById('editHomeworkModal').classList.add('show');
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Có lỗi xảy ra khi tải thông tin bài tập');
                });
        }

        function closeEditModal() {
            document.getElementById('editHomeworkModal').classList.remove('show');
            document.getElementById('editHomeworkForm').reset();
            document.getElementById('editFileNameDisplay').classList.remove('show');
            document.getElementById('edit_currentFile').style.display = 'none';
        }

        function submitEditHomework() {
            const form = document.getElementById('editHomeworkForm');
            
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);
            // Thêm maGV để tự động xác định môn học
            formData.append('maGV', '<?php echo $_SESSION['maGV']; ?>');
            const formData = new FormData(form);
            fetch('<?php echo url("controller/cAssignHomework.php?action=update"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }   location.reload();
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Có lỗi xảy ra khi cập nhật bài tập');
            });
        }

        // Drag and drop functionality for ADD modal
        const fileUploadArea = document.getElementById('fileUploadArea');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        ['dragenter', 'dragover'].forEach(eventName => {reventDefaults, false);
            fileUploadArea.addEventListener(eventName, () => {
                fileUploadArea.classList.add('dragover');
            }, false);'dragover'].forEach(eventName => {
        }); fileUploadArea.addEventListener(eventName, () => {
                fileUploadArea.classList.add('dragover');
        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, () => {
                fileUploadArea.classList.remove('dragover');
            }, false);'drop'].forEach(eventName => {
        }); fileUploadArea.addEventListener(eventName, () => {
                fileUploadArea.classList.remove('dragover');
        fileUploadArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            const fileInput = document.getElementById('add_file');
            const dt = e.dataTransfer;
            fileInput.files = files;
            handleFileSelect(fileInput);etElementById('add_file');
        }, false);
            fileInput.files = files;
        // Drag and drop for EDIT modal;
        const editFileUploadArea = document.getElementById('editFileUploadArea');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            editFileUploadArea.addEventListener(eventName, preventDefaults, false);
        });
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        ['dragenter', 'dragover'].forEach(eventName => {e, preventDefaults, false);
            editFileUploadArea.addEventListener(eventName, () => {
                editFileUploadArea.classList.add('dragover');
            }, false);'dragover'].forEach(eventName => {
        }); editFileUploadArea.addEventListener(eventName, () => {
                editFileUploadArea.classList.add('dragover');
        ['dragleave', 'drop'].forEach(eventName => {
            editFileUploadArea.addEventListener(eventName, () => {
                editFileUploadArea.classList.remove('dragover');
            }, false);'drop'].forEach(eventName => {
        }); editFileUploadArea.addEventListener(eventName, () => {
                editFileUploadArea.classList.remove('dragover');
        editFileUploadArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            const fileInput = document.getElementById('edit_file');
            const dt = e.dataTransfer;
            fileInput.files = files;
            handleEditFileSelect(fileInput);ementById('edit_file');
        }, false);
            fileInput.files = files;
        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addHomeworkModal');
            const editModal = document.getElementById('editHomeworkModal');
            ow.onclick = function(event) {
            if (event.target == addModal) {ementById('addHomeworkModal');
                closeAddModal();cument.getElementById('editHomeworkModal');
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }   if (event.target == editModal) {
                closeEditModal();
        // Set minimum datetime to now
        document.addEventListener('DOMContentLoaded', function() {
            const datetimeInput = document.getElementById('thoiGianNop');
            if (datetimeInput) {
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                datetimeInput.min = now.toISOString().slice(0, 16);
            }
        });
    </script>
</body>
</html>
