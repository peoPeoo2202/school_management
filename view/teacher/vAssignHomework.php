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

// Xử lý các action AJAX từ controller
if (isset($_GET['action']) || isset($_POST['action'])) {
    require_once(__DIR__ . '/../../controller/cAssignHomework.php');
    $controller = new cAssignHomework();

    $action = $_GET['action'] ?? $_POST['action'];

    switch ($action) {
        case 'create':
            $controller->create();
            exit();
        case 'delete':
            $controller->delete();
            exit();
        case 'getDetail':
            $controller->getDetail();
            exit();
        case 'update':
            $controller->update();
            exit();
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}

// Load dữ liệu cho view
require_once(__DIR__ . '/../../model/mAssignHomework.php');
$model = new mAssignHomework();
$maGV = $_SESSION['maGV'];

// Lấy danh sách lớp
$classes = $model->getTeacherClasses($maGV);

// Lấy danh sách bài tập (chỉ lọc theo lớp, môn học tự động)
$maLop = isset($_GET['maLop']) ? $_GET['maLop'] : null;
$homeworks = $model->getHomeworkList($maGV, $maLop);

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
    <link rel="stylesheet" href="style.css">
    <style>
        .action-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr;
            max-width: 400px;
            gap: 15px;
        }

        .homework-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .homework-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s;
        }

        .homework-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #5081BE 0%, #3d6a9e 100%);
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
            align-items: flex-start;
        }

        .card-body p:last-child {
            margin-bottom: 0;
        }

        .card-body p strong {
            color: #333;
            min-width: 80px;
            flex-shrink: 0;
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

        .empty-state {
            background: white;
            padding: 60px 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
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
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s;
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
            max-width: 650px;
            max-height: 90vh;
            overflow: hidden;
            animation: slideDown 0.3s;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 24px 28px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #5081BE 0%, #3d6a9e 100%);
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-title i {
            font-size: 22px;
        }

        .btn-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: white;
            padding: 0;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .btn-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 28px;
            max-height: calc(90vh - 160px);
            overflow-y: auto;
        }

        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 4px rgba(80, 129, 190, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 4px rgba(80, 129, 190, 0.1);
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
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 14px;
            display: block;
        }

        .form-label span {
            color: #dc3545;
            margin-left: 3px;
        }

        /* Custom File Upload */
        .file-upload-wrapper {
            position: relative;
            border: 2px dashed #c0c0c0;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-upload-wrapper:hover {
            border-color: #5081BE;
            background: #f0f5fa;
        }

        .file-upload-wrapper.drag-over {
            border-color: #5081BE;
            background: #e8f0ff;
        }

        .file-upload-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .file-upload-icon::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: inherit;
            border-radius: inherit;
            opacity: 0.3;
            filter: blur(10px);
        }

        .file-upload-icon i {
            font-size: 36px;
            color: white;
            position: relative;
            z-index: 1;
        }

        .file-upload-text {
            margin-bottom: 8px;
        }

        .file-upload-text h4 {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 5px 0;
        }

        .file-upload-text p {
            color: #666;
            font-size: 13px;
            margin: 0;
        }

        .file-upload-input {
            display: none;
        }

        .file-upload-hint {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }

        .file-selected {
            margin-top: 15px;
            padding: 12px 15px;
            background: #e8f5e9;
            border: 1px solid #81c784;
            border-radius: 8px;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .file-selected.show {
            display: flex;
        }

        .file-selected i {
            color: #4caf50;
            font-size: 20px;
        }

        .file-info {
            flex: 1;
        }

        .file-info .file-name {
            color: #2e7d32;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 3px;
        }

        .file-info .file-size {
            color: #66bb6a;
            font-size: 12px;
        }

        .remove-file {
            background: #ffebee;
            border: none;
            color: #f44336;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .remove-file:hover {
            background: #f44336;
            color: white;
        }

        .modal-footer {
            padding: 20px 28px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            background: #f8f9fa;
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Checkbox Style */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
            cursor: pointer;
        }

        .checkbox-wrapper:hover {
            background: #e8f0ff;
            border-color: #5081BE;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #5081BE;
        }

        .checkbox-wrapper label {
            cursor: pointer;
            font-weight: 500;
            color: #333;
            font-size: 14px;
            margin: 0;
            user-select: none;
        }

        .checkbox-wrapper .checkbox-hint {
            color: #666;
            font-size: 12px;
            font-weight: normal;
            margin-left: 5px;
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
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .content-inner {
                padding: 16px;
            }

            .homework-grid {
                grid-template-columns: 1fr;
            }

            .filter-row {
                max-width: 100%;
            }

            .modal-dialog {
                width: 95%;
                margin: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content Area -->
        <div class="content-area">
            <div class="header-section ">
                <div class="header-left assign-homework-title">
                    <div class="header-left-icon">
                        <h2><i class="fas fa-clipboard-list"></i></h2>
                        <h2> Giao Bài Tập</h2>
                    </div>
                    <div>
                        <p>Quản lý và giao bài tập cho học sinh</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus-circle"></i> Giao Bài Tập Mới
                </button>
            </div>

            <!-- Filter Card -->
            <div class="filter-card">
                <form method="GET" action="" id="filterForm">
                    <div class="filter-row">
                        <div class="form-group">
                            <label class="form-label">Lọc theo lớp</label>
                            <select name="maLop" id="filterClass" class="form-select" onchange="document.getElementById('filterForm').submit();">
                                <option value="">-- Tất cả lớp --</option>
                                <?php
                                $classes->data_seek(0);
                                while ($class = $classes->fetch_assoc()): ?>
                                    <option value="<?= $class['maLop'] ?>"
                                        <?= (isset($_GET['maLop']) && $_GET['maLop'] == $class['maLop']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['tenLop']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Homework Grid -->
            <?php if ($homeworks->num_rows > 0): ?>
                <?php
                // Lưu tất cả bài tập vào mảng
                $allHomeworks = [];
                while ($hw = $homeworks->fetch_assoc()) {
                    $allHomeworks[] = $hw;
                }
                $totalHomeworks = count($allHomeworks);
                ?>
                <div class="homework-grid" id="homeworkGrid">
                    <?php foreach ($allHomeworks as $hw): ?>
                        <div class="homework-card" data-homework-item style="display: none;">
                            <div class="card-header">
                                <h3><?= htmlspecialchars($hw['tenBaiTap']) ?></h3>
                            </div>
                            <div class="card-body">
                                <p><strong>Lớp:</strong> <?= htmlspecialchars($hw['tenLop']) ?></p>
                                <p><strong>Môn học:</strong> <?= htmlspecialchars($hw['tenMonHoc']) ?></p>
                                <p><strong>Yêu cầu:</strong> <?= htmlspecialchars($hw['yeuCauBaiTap'] ?: 'Không có') ?></p>
                                <p><strong>Hạn nộp:</strong> <?= date('d/m/Y H:i', strtotime($hw['thoiGianNop'])) ?></p>
                                <p><strong>Nộp trễ:</strong>
                                    <?php if (isset($hw['choPhepNopTre']) && $hw['choPhepNopTre'] == 1): ?>
                                        <span style="color: #28a745; font-weight: 600;">
                                            <i class="fas fa-check-circle"></i> Cho phép
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #dc3545; font-weight: 600;">
                                            <i class="fas fa-times-circle"></i> Không cho phép
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <p><strong>Trạng thái:</strong>
                                    <?php if (isset($hw['anBai']) && $hw['anBai'] == 1): ?>
                                        <span style="color: #ff9800; font-weight: 600;">
                                            <i class="fas fa-eye-slash"></i> Đang ẩn
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #28a745; font-weight: 600;">
                                            <i class="fas fa-eye"></i> Hiển thị
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <strong>Đã nộp:</strong>
                                    <span class="status-badge <?= ($hw['soLuongNopBai'] > 0) ? 'completed' : 'pending' ?>">
                                        <?= $hw['soLuongNopBai'] ?? 0 ?> bài
                                    </span>
                                </p>
                            </div>
                            <div class="card-footer">
                                <a href="<?= url('view/teacher/vHomeworkDetail.php?id=' . $hw['maBaiTap']) ?>"
                                    class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Chi tiết
                                </a>
                                <button class="btn btn-sm btn-warning" onclick="editHomework(<?= $hw['maBaiTap'] ?>)">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteHomework(<?= $hw['maBaiTap'] ?>)">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination: Luôn render, JavaScript sẽ quyết định hiển/ẩn -->
                <div id="paginationContainer" style="margin-top: 30px; display: flex; justify-content: center; align-items: center; gap: 10px;">
                    <!-- Pagination buttons will be generated by JavaScript -->
                </div>

                <script>
                    // Dữ liệu từ PHP
                    const totalHomeworksFromPHP = <?php echo $totalHomeworks; ?>;
                    console.log('Total homeworks from PHP:', totalHomeworksFromPHP);
                </script>
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
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i>
                    Giao Bài Tập Mới
                </h5>
                <button type="button" class="btn-close" onclick="closeAddModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addHomeworkForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Tên bài tập<span>*</span></label>
                        <input type="text" name="tenBaiTap" class="form-control" placeholder="Nhập tên bài tập..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lớp<span>*</span></label>
                        <select name="maLop" id="addClass" class="form-select" required>
                            <option value="">-- Chọn lớp --</option>
                            <?php
                            $classes->data_seek(0);
                            while ($class = $classes->fetch_assoc()): ?>
                                <option value="<?= $class['maLop'] ?>"><?= htmlspecialchars($class['tenLop']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Yêu cầu bài tập</label>
                        <textarea name="yeuCauBaiTap" class="form-control" rows="4" placeholder="Mô tả chi tiết yêu cầu bài tập..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Chọn file hoặc kéo thả vào đây</label>
                        <div class="file-upload-wrapper" id="fileUploadWrapper">
                            <div class="file-upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="file-upload-text">
                                <h4>Chọn file hoặc kéo thả vào đây</h4>
                                <p>Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX (Tối đa 10MB)</p>
                            </div>
                            <input type="file" name="fileBaiTap" id="fileBaiTap" class="file-upload-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                            <div class="file-upload-hint">Click hoặc kéo thả file vào đây</div>
                        </div>
                        <div class="file-selected" id="fileSelected">
                            <i class="fas fa-file-check"></i>
                            <div class="file-info">
                                <div class="file-name" id="fileName"></div>
                                <div class="file-size" id="fileSize"></div>
                            </div>
                            <button type="button" class="remove-file" id="removeFile">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Thời gian nộp<span>*</span></label>
                        <input type="datetime-local" name="thoiGianNop" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="choPhepNopTre" id="choPhepNopTre" value="1">
                            <label for="choPhepNopTre">
                                Cho phép học sinh nộp trễ
                                <span class="checkbox-hint">(Sau thời hạn nộp bài)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="anBai" id="anBai" value="1">
                            <label for="anBai">
                                Ẩn bài tập
                                <span class="checkbox-hint">(Bài tập sẽ không hiển thị cho học sinh)</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" id="submitBtn" onclick="submitHomework()">
                    <i class="fas fa-paper-plane"></i> Giao Bài Tập
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Sửa Bài Tập -->
    <div class="modal" id="editHomeworkModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i>
                    Sửa Bài Tập
                </h5>
                <button type="button" class="btn-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editHomeworkForm" enctype="multipart/form-data">
                    <input type="hidden" name="maBaiTap" id="editMaBaiTap">

                    <div class="mb-3">
                        <label class="form-label">Tên bài tập<span>*</span></label>
                        <input type="text" name="tenBaiTap" id="editTenBaiTap" class="form-control" placeholder="Nhập tên bài tập..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lớp<span>*</span></label>
                        <select name="maLop" id="editLop" class="form-select" required>
                            <option value="">-- Chọn lớp --</option>
                            <?php
                            $classes->data_seek(0);
                            while ($class = $classes->fetch_assoc()): ?>
                                <option value="<?= $class['maLop'] ?>"><?= htmlspecialchars($class['tenLop']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Yêu cầu bài tập</label>
                        <textarea name="yeuCauBaiTap" id="editYeuCau" class="form-control" rows="4" placeholder="Mô tả chi tiết yêu cầu bài tập..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File hiện tại</label>
                        <div id="currentFile" style="display: none; padding: 10px; background: #e8f5e9; border-radius: 8px; margin-bottom: 10px;">
                            <i class="fas fa-file" style="color: #4caf50;"></i>
                            <span id="currentFileName" style="color: #2e7d32; font-weight: 600; margin-left: 8px;"></span>
                        </div>

                        <label class="form-label">Thay đổi file (tùy chọn)</label>
                        <div class="file-upload-wrapper" id="editFileUploadWrapper">
                            <div class="file-upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="file-upload-text">
                                <h4>Chọn file mới hoặc kéo thả vào đây</h4>
                                <p>Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX (Tối đa 10MB)</p>
                            </div>
                            <input type="file" name="fileBaiTap" id="editFileBaiTap" class="file-upload-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                            <div class="file-upload-hint">Để trống nếu không muốn thay đổi file</div>
                        </div>
                        <div class="file-selected" id="editFileSelected">
                            <i class="fas fa-file-check"></i>
                            <div class="file-info">
                                <div class="file-name" id="editFileName"></div>
                                <div class="file-size" id="editFileSize"></div>
                            </div>
                            <button type="button" class="remove-file" id="editRemoveFile">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Thời gian nộp<span>*</span></label>
                        <input type="datetime-local" name="thoiGianNop" id="editThoiGianNop" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="choPhepNopTre" id="editChoPhepNopTre" value="1">
                            <label for="editChoPhepNopTre">
                                Cho phép học sinh nộp trễ
                                <span class="checkbox-hint">(Sau thời hạn nộp bài)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="anBai" id="editAnBai" value="1">
                            <label for="editAnBai">
                                Ẩn bài tập
                                <span class="checkbox-hint">(Bài tập sẽ không hiển thị cho học sinh)</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" id="updateBtn" onclick="updateHomework()">
                    <i class="fas fa-save"></i> Cập Nhật
                </button>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('addHomeworkModal').classList.add('show');
            document.getElementById('addHomeworkForm').reset();
        }

        function closeAddModal() {
            document.getElementById('addHomeworkModal').classList.remove('show');
            document.getElementById('addHomeworkForm').reset();
            // Reset file upload display
            document.getElementById('fileSelected').classList.remove('show');
            document.getElementById('fileBaiTap').value = '';
        }

        // Edit modal functions
        function openEditModal() {
            document.getElementById('editHomeworkModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editHomeworkModal').classList.remove('show');
            document.getElementById('editHomeworkForm').reset();
            document.getElementById('editFileSelected').classList.remove('show');
            document.getElementById('currentFile').style.display = 'none';
        }

        // File upload handling for ADD modal
        const fileUploadWrapper = document.getElementById('fileUploadWrapper');
        const fileInput = document.getElementById('fileBaiTap');
        const fileSelected = document.getElementById('fileSelected');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removeFileBtn = document.getElementById('removeFile');

        // Click to upload
        fileUploadWrapper.addEventListener('click', () => {
            fileInput.click();
        });

        // File selected
        fileInput.addEventListener('change', (e) => {
            handleFile(e.target.files[0]);
        });

        // Drag and drop
        fileUploadWrapper.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadWrapper.classList.add('drag-over');
        });

        fileUploadWrapper.addEventListener('dragleave', () => {
            fileUploadWrapper.classList.remove('drag-over');
        });

        fileUploadWrapper.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadWrapper.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;

                handleFile(file);
            }
        });

        // Remove file
        removeFileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fileInput.value = '';
            fileSelected.classList.remove('show');
        });

        function handleFile(file) {
            if (!file) return;

            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('File vượt quá 10MB!');
                fileInput.value = '';
                return;
            }

            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileSelected.classList.add('show');
        }

        // ========== FILE UPLOAD HANDLING FOR EDIT MODAL ==========
        const editFileUploadWrapper = document.getElementById('editFileUploadWrapper');
        const editFileInput = document.getElementById('editFileBaiTap');
        const editFileSelected = document.getElementById('editFileSelected');
        const editFileName = document.getElementById('editFileName');
        const editFileSize = document.getElementById('editFileSize');
        const editRemoveFileBtn = document.getElementById('editRemoveFile');

        // Click to upload - Edit modal
        editFileUploadWrapper.addEventListener('click', () => {
            editFileInput.click();
        });

        // File selected - Edit modal
        editFileInput.addEventListener('change', (e) => {
            handleEditFile(e.target.files[0]);
        });

        // Drag and drop - Edit modal
        editFileUploadWrapper.addEventListener('dragover', (e) => {
            e.preventDefault();
            editFileUploadWrapper.classList.add('drag-over');
        });

        editFileUploadWrapper.addEventListener('dragleave', () => {
            editFileUploadWrapper.classList.remove('drag-over');
        });

        editFileUploadWrapper.addEventListener('drop', (e) => {
            e.preventDefault();
            editFileUploadWrapper.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                editFileInput.files = dataTransfer.files;

                handleEditFile(file);
            }
        });

        // Remove file - Edit modal
        editRemoveFileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            editFileInput.value = '';
            editFileSelected.classList.remove('show');
        });

        function handleEditFile(file) {
            if (!file) return;

            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('File vượt quá 10MB!');
                editFileInput.value = '';
                return;
            }

            editFileName.textContent = file.name;
            editFileSize.textContent = formatFileSize(file.size);
            editFileSelected.classList.add('show');
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Submit homework
        function submitHomework() {
            const form = document.getElementById('addHomeworkForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

            fetch('<?php echo url("controller/cAssignHomework.php?action=create"); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        closeAddModal();
                        location.reload();
                    } else {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Có lỗi khi giao bài tập');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        }

        // Delete homework
        function deleteHomework(maBaiTap) {
            if (!confirm('Bạn có chắc chắn muốn xóa bài tập này?')) return;

            fetch('<?php echo url("controller/cAssignHomework.php?action=delete"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'maBaiTap=' + maBaiTap
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Có lỗi xảy ra khi xóa bài tập');
                });
        }

        // Edit homework - CẢI TIẾN
        function editHomework(maBaiTap) {
            fetch('<?php echo url("controller/cAssignHomework.php?action=getDetail"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'maBaiTap=' + maBaiTap
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const hw = data.homework;

                        // Fill form data
                        document.getElementById('editMaBaiTap').value = hw.maBaiTap;
                        document.getElementById('editTenBaiTap').value = hw.tenBaiTap;
                        document.getElementById('editYeuCau').value = hw.yeuCauBaiTap || '';
                        document.getElementById('editLop').value = hw.maLop;

                        // Convert datetime to local format
                        const date = new Date(hw.thoiGianNop);
                        const localDatetime = new Date(date.getTime() - date.getTimezoneOffset() * 60000)
                            .toISOString()
                            .slice(0, 16);
                        document.getElementById('editThoiGianNop').value = localDatetime;

                        // Set checkbox
                        document.getElementById('editChoPhepNopTre').checked = hw.choPhepNopTre == 1;
                        document.getElementById('editAnBai').checked = hw.anBai == 1;

                        // Reset file input và ẩn file selected
                        document.getElementById('editFileBaiTap').value = '';
                        document.getElementById('editFileSelected').classList.remove('show');

                        // Show current file if exists
                        if (hw.tenFile) {
                            document.getElementById('currentFile').style.display = 'block';
                            document.getElementById('currentFileName').textContent = hw.tenFile;
                        } else {
                            document.getElementById('currentFile').style.display = 'none';
                        }

                        openEditModal();
                    } else {
                        alert('Không thể tải thông tin bài tập');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Có lỗi xảy ra');
                });
        }

        // Update homework
        function updateHomework() {
            const form = document.getElementById('editHomeworkForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const updateBtn = document.getElementById('updateBtn');
            const originalText = updateBtn.innerHTML;

            updateBtn.disabled = true;
            updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...';

            fetch('<?php echo url("controller/cAssignHomework.php?action=update"); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        closeEditModal();
                        location.reload();
                    } else {
                        updateBtn.disabled = false;
                        updateBtn.innerHTML = originalText;
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Có lỗi khi cập nhật bài tập');
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = originalText;
                });
        }

        // ========== PAGINATION ==========
        let currentPage = 1;
        const itemsPerPage = 10;
        let totalPages = 1;

        function initPagination() {
            const homeworkCards = document.querySelectorAll('[data-homework-item]');
            const totalItems = homeworkCards.length;
            totalPages = Math.ceil(totalItems / itemsPerPage);

            console.log('Init pagination - Total items:', totalItems, 'Total pages:', totalPages);

            if (totalItems > 0) {
                displayPage(1);
                renderPagination();
            } else {
                console.log('No homework cards found!');
            }
        }

        function displayPage(page) {
            const homeworkCards = document.querySelectorAll('[data-homework-item]');
            const startIndex = (page - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;

            console.log(`Displaying page ${page}: showing items ${startIndex + 1} to ${Math.min(endIndex, homeworkCards.length)}`);

            homeworkCards.forEach((card, index) => {
                if (index >= startIndex && index < endIndex) {
                    card.style.display = 'block';
                    card.style.visibility = 'visible';
                    card.style.opacity = '1';
                } else {
                    card.style.display = 'none';
                    card.style.visibility = 'hidden';
                    card.style.opacity = '0';
                }
            });

            currentPage = page;
            renderPagination();
        }

        function changePage(page) {
            if (page >= 1 && page <= totalPages && page !== currentPage) {
                displayPage(page);
                // Cuộn lên đầu trang
                const grid = document.querySelector('.homework-grid');
                if (grid) {
                    grid.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        }

        function renderPagination() {
            const container = document.getElementById('paginationContainer');

            if (!container) {
                console.error('Pagination container not found!');
                return;
            }

            console.log('Rendering pagination - Total pages:', totalPages, 'Current page:', currentPage);

            // Chỉ ẩn khi không có items, vẫn hiển thị khi có 1 trang để user thấy
            if (totalPages === 0) {
                container.style.display = 'none';
                console.log('No items, hiding pagination');
                return;
            }

            // Nếu chỉ có 1 trang, vẫn hiển thị pagination để rõ ràng
            container.style.display = 'flex';
            let html = '';

            // Nút "Trước"
            const prevDisabled = currentPage === 1;
            html += `
                <button onclick="changePage(${currentPage - 1})" 
                        ${prevDisabled ? 'disabled' : ''}
                        style="
                            padding: 10px 16px;
                            border: 1px solid ${prevDisabled ? '#e0e0e0' : '#d0d0d0'};
                            background: white;
                            color: ${prevDisabled ? '#999' : '#5081BE'};
                            border-radius: 6px;
                            cursor: ${prevDisabled ? 'not-allowed' : 'pointer'};
                            font-size: 14px;
                            transition: all 0.2s;
                        "
                        ${prevDisabled ? '' : 'onmouseover="this.style.borderColor=\'#5081BE\'; this.style.background=\'#f5f8fa\'" onmouseout="this.style.borderColor=\'#d0d0d0\'; this.style.background=\'white\'"'}>
                    <i class="fas fa-chevron-left"></i> Trước
                </button>
            `;

            // Hiển thị các nút số trang (tối đa 5 nút)
            for (let i = 1; i <= Math.min(totalPages, 5); i++) {
                html += createPageButton(i);
            }

            // Nút "Sau"
            const nextDisabled = currentPage === totalPages;
            html += `
                <button onclick="changePage(${currentPage + 1})" 
                        ${nextDisabled ? 'disabled' : ''}
                        style="
                            padding: 10px 16px;
                            border: 1px solid ${nextDisabled ? '#e0e0e0' : '#d0d0d0'};
                            background: white;
                            color: ${nextDisabled ? '#999' : '#5081BE'};
                            border-radius: 6px;
                            cursor: ${nextDisabled ? 'not-allowed' : 'pointer'};
                            font-size: 14px;
                            transition: all 0.2s;
                        "
                        ${nextDisabled ? '' : 'onmouseover="this.style.borderColor=\'#5081BE\'; this.style.background=\'#f5f8fa\'" onmouseout="this.style.borderColor=\'#d0d0d0\'; this.style.background=\'white\'"'}>
                    Sau <i class="fas fa-chevron-right"></i>
                </button>
            `;

            container.innerHTML = html;
        }

        function createPageButton(pageNum) {
            const isActive = pageNum === currentPage;
            return `
                <button onclick="changePage(${pageNum})" 
                        style="
                            min-width: 42px;
                            height: 42px;
                            padding: 0;
                            border: 1px solid ${isActive ? '#5081BE' : '#d0d0d0'};
                            background: ${isActive ? '#5081BE' : 'white'};
                            color: ${isActive ? 'white' : '#333'};
                            border-radius: 6px;
                            cursor: pointer;
                            font-size: 15px;
                            font-weight: 500;
                            transition: all 0.2s;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                        "
                        ${isActive ? '' : 'onmouseover="this.style.background=\'#f5f8fa\'; this.style.borderColor=\'#5081BE\'" onmouseout="this.style.background=\'white\'; this.style.borderColor=\'#d0d0d0\'"'}>
                    ${pageNum}
                </button>
            `;
        }

        // Khởi tạo phân trang khi trang được tải
        window.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing pagination...');
            setTimeout(function() {
                const cards = document.querySelectorAll('[data-homework-item]');
                console.log('Found cards:', cards.length);
                if (cards.length > 0) {
                    initPagination();
                }
            }, 100);
        });

        // Backup: khởi tạo ngay nếu DOM đã sẵn sàng
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            setTimeout(function() {
                const cards = document.querySelectorAll('[data-homework-item]');
                if (cards.length > 0) {
                    initPagination();
                }
            }, 100);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addHomeworkModal');
            const editModal = document.getElementById('editHomeworkModal');

            if (event.target == addModal) {
                closeAddModal();
            }

            if (event.target == editModal) {
                closeEditModal();
            }
        }
    </script>
</body>

</html>