<?php
// Khởi động session trước khi require config
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../config.php');

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

// Xử lý các action AJAX từ controller
if (isset($_GET['action']) || isset($_POST['action'])) {
    require_once(__DIR__ . '/../../controller/cHomeworkDetail.php');
    $controller = new cHomeworkDetail();
    
    $action = $_GET['action'] ?? $_POST['action'];
    
    switch($action) {
        case 'getDetail':
            $controller->getDetail();
            exit();
        case 'grade':
            $controller->gradeSubmission();
            exit();
        case 'toggleLock':
            $controller->toggleLock();
            exit();
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}

// Lấy thông tin bài tập
require_once(__DIR__ . '/../../model/mHomeworkDetail.php');
$model = new mHomeworkDetail();

$maBaiTap = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($maBaiTap <= 0) {
    echo "<script>alert('ID bài tập không hợp lệ'); window.location.href='../../view/teacher/vAssignHomework.php';</script>";
    exit();
}

$homework = $model->getHomeworkDetail($maBaiTap);

if (!$homework) {
    echo "<script>alert('Không tìm thấy bài tập'); window.location.href='../../view/teacher/vAssignHomework.php';</script>";
    exit();
}

// Kiểm tra quyền: chỉ giáo viên giao bài mới được xem
if (!isset($_SESSION['maGV']) || $homework['maGV'] != $_SESSION['maGV']) {
    echo "<script>alert('Bạn không có quyền xem bài tập này'); window.location.href='../../view/teacher/vAssignHomework.php';</script>";
    exit();
}

$submissions = $model->getSubmissions($maBaiTap);
$students = $model->getStudentsList($homework['maLop']);
$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Bài tập - <?= htmlspecialchars($homework['tenBaiTap']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            overflow: hidden;
        }

        .main-wrapper {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            background: #f0f2f5;
        }

        .content-inner {
            flex: 1;
            padding: 24px 32px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .content-inner::-webkit-scrollbar {
            width: 8px;
        }

        .content-inner::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .content-inner::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .content-inner::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .back-button:hover {
            background: #545b62;
            transform: translateX(-5px);
        }

        .homework-info {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .homework-info h2 {
            color: #5081BE;
            margin: 0 0 20px 0;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-item label {
            font-weight: 600;
            color: #666;
            font-size: 13px;
            display: block;
            margin-bottom: 5px;
        }

        .info-item .value {
            color: #333;
            font-size: 15px;
            font-weight: 500;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.green { background: #28a745; }
        .stat-icon.blue { background: #5081BE; }
        .stat-icon.orange { background: #ffc107; }

        .stat-info h3 {
            color: #666;
            font-size: 13px;
            margin: 0 0 5px 0;
        }

        .stat-info .number {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .submissions-table {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .submissions-table h3 {
            color: #5081BE;
            margin: 0 0 20px 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        td {
            font-size: 14px;
            color: #666;
        }

        tr:hover {
            background: #f8f9fa;
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

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-primary {
            background: #5081BE;
            color: white;
        }

        .btn-primary:hover {
            background: #3d6a9e;
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
            color: #212529;
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

        .file-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: #17a2b8;
            color: white;
            border-radius: 6px;
            margin-left: 8px;
            transition: all 0.3s;
        }

        .file-link:hover {
            background: #138496;
            transform: scale(1.1);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin: 0;
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
            max-width: 550px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #5081BE 0%, #3d6a9e 100%);
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
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
        }

        .btn-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 24px;
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

        .mb-3 {
            margin-bottom: 16px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 3px rgba(80, 129, 190, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            background: #f8f9fa;
        }

        @media (max-width: 1200px) {
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .content-inner {
                padding: 16px;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>
        
        <div class="content-area">
            <div class="content-inner">
                <a href="<?= url('view/teacher/vAssignHomework.php') ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>

                <!-- Thông tin bài tập -->
                <div class="homework-info">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0;"><i class="fas fa-file-alt"></i> <?= htmlspecialchars($homework['tenBaiTap']) ?></h2>
                        <button onclick="toggleLockHomework()" class="btn <?= $homework['khoaBai'] == 1 ? 'btn-danger' : 'btn-warning' ?>" id="lockBtn">
                            <i class="fas fa-<?= $homework['khoaBai'] == 1 ? 'lock' : 'unlock' ?>"></i>
                            <?= $homework['khoaBai'] == 1 ? 'Mở khóa bài tập' : 'Khóa bài tập' ?>
                        </button>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Lớp:</label>
                            <div class="value"><?= htmlspecialchars($homework['tenLop']) ?></div>
                        </div>
                        <div class="info-item">
                            <label>Môn học:</label>
                            <div class="value"><?= htmlspecialchars($homework['tenMonHoc']) ?></div>
                        </div>
                        <div class="info-item">
                            <label>Hạn nộp:</label>
                            <div class="value"><?= date('d/m/Y H:i', strtotime($homework['thoiGianNop'])) ?></div>
                        </div>
                        <div class="info-item">
                            <label>Cho phép nộp trễ:</label>
                            <div class="value">
                                <?= $homework['choPhepNopTre'] == 1 ? '<span style="color: #28a745;">✓ Có</span>' : '<span style="color: #dc3545;">✗ Không</span>' ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($homework['yeuCauBaiTap']): ?>
                    <div style="margin-top: 20px;">
                        <label style="font-weight: 600; color: #666; font-size: 13px;">Yêu cầu:</label>
                        <p style="margin: 10px 0 0 0; color: #333;"><?= nl2br(htmlspecialchars($homework['yeuCauBaiTap'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($homework['tenFile'])): 
                        // Xác định icon và màu theo loại file
                        $fileExt = strtolower(pathinfo($homework['tenFile'], PATHINFO_EXTENSION));
                        $fileIcon = 'fa-file';
                        $iconColor = '#666';
                        
                        switch($fileExt) {
                            case 'pdf':
                                $fileIcon = 'fa-file-pdf';
                                $iconColor = '#d32f2f';
                                break;
                            case 'doc':
                            case 'docx':
                                $fileIcon = 'fa-file-word';
                                $iconColor = '#2b579a';
                                break;
                            case 'xls':
                            case 'xlsx':
                                $fileIcon = 'fa-file-excel';
                                $iconColor = '#217346';
                                break;
                            case 'ppt':
                            case 'pptx':
                                $fileIcon = 'fa-file-powerpoint';
                                $iconColor = '#d24726';
                                break;
                            case 'txt':
                                $fileIcon = 'fa-file-alt';
                                $iconColor = '#666';
                                break;
                            case 'zip':
                            case 'rar':
                            case '7z':
                                $fileIcon = 'fa-file-archive';
                                $iconColor = '#ffa500';
                                break;
                            case 'jpg':
                            case 'jpeg':
                            case 'png':
                            case 'gif':
                                $fileIcon = 'fa-file-image';
                                $iconColor = '#9c27b0';
                                break;
                            default:
                                $fileIcon = 'fa-file';
                                $iconColor = '#666';
                        }
                    ?>
                    <div style="margin-top: 20px; padding: 15px; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 8px;">
                        <label style="font-weight: 600; color: #2e7d32; font-size: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                            <i class="fas fa-paperclip"></i> File đính kèm của giáo viên:
                        </label>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="flex: 1; display: flex; align-items: center; gap: 10px;">
                                <i class="fas <?= $fileIcon ?>" style="font-size: 24px; color: <?= $iconColor ?>;"></i>
                                <span style="color: #2e7d32; font-weight: 500;"><?= htmlspecialchars($homework['tenFile']) ?></span>
                            </div>
                            <a href="<?= url('uploads/homework/' . $homework['tenFile']) ?>" 
                               class="btn btn-sm btn-success" 
                               download
                               style="padding: 8px 16px;">
                                <i class="fas fa-download"></i> Tải xuống
                            </a>
                            <a href="<?= url('uploads/homework/' . $homework['tenFile']) ?>" 
                               class="btn btn-sm btn-primary" 
                               target="_blank"
                               style="padding: 8px 16px;">
                                <i class="fas fa-eye"></i> Xem
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 8px;">
                        <label style="font-weight: 600; color: #856404; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-info-circle"></i> Không có file đính kèm
                        </label>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Thống kê -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Tổng số học sinh</h3>
                            <div class="number"><?= $homework['tongSoHocSinh'] ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Đã nộp bài</h3>
                            <div class="number"><?= $homework['soLuongNopBai'] ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Chưa nộp</h3>
                            <div class="number"><?= $homework['tongSoHocSinh'] - $homework['soLuongNopBai'] ?></div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách bài nộp -->
                <div class="submissions-table">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3><i class="fas fa-list"></i> Danh sách bài nộp</h3>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label for="statusFilter" style="font-weight: 600; color: #555;">Lọc trạng thái:</label>
                            <select id="statusFilter" onchange="filterSubmissions()" style="padding: 8px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; background: white; cursor: pointer;">
                                <option value="all">Tất cả</option>
                                <option value="Đã nộp" selected>Đã nộp</option>
                                <option value="Nộp trễ">Nộp trễ</option>
                                <option value="Chưa nộp">Chưa nộp</option>
                            </select>
                        </div>
                    </div>
                    <?php if($submissions->num_rows > 0): ?>
                    <table id="submissionsTable">
                        <thead>
                            <tr>
                                <th>Học sinh</th>
                                <th>Ngày nộp</th>
                                <th>Trạng thái</th>
                                <th>Điểm</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($sub = $submissions->fetch_assoc()): ?>
                            <tr data-status="<?= $sub['trangThaiNop'] ?>">
                                <td><?= htmlspecialchars($sub['tenHS']) ?></td>
                                <td><?= $sub['ngayNop'] ? date('d/m/Y H:i', strtotime($sub['ngayNop'])) : '-' ?></td>
                                <td>
                                    <span class="status-badge <?= strtolower($sub['trangThaiNop']) ?>">
                                        <?= $sub['trangThaiNop'] ?>
                                    </span>
                                </td>
                                <td><?= $sub['diem'] !== null ? number_format($sub['diem'], 1) : '-' ?></td>
                                <td>
                                    <?php if($sub['ngayNop']): ?>
                                    <button class="btn btn-sm btn-primary" onclick="openGradeModal(<?= $sub['maBaiNop'] ?>)">
                                        <i class="fas fa-pen"></i> Chấm điểm
                                    </button>
                                    <?php if($sub['tenFile']): ?>
                                    <a href="<?= url('uploads/submissions/' . $sub['tenFile']) ?>" 
                                       class="file-link" target="_blank" title="Xem file">
                                        <i class="fas fa-file-download"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php 
                            // Lấy danh sách mã HS đã nộp
                            $submissions->data_seek(0); // Reset con trỏ
                            $submittedStudents = [];
                            while($sub = $submissions->fetch_assoc()) {
                                $submittedStudents[] = $sub['maHS'];
                            }
                            
                            // Hiển thị học sinh chưa nộp
                            $students->data_seek(0); // Reset con trỏ
                            while($student = $students->fetch_assoc()): 
                                if (!in_array($student['maHS'], $submittedStudents)):
                            ?>
                            <tr data-status="Chưa nộp">
                                <td><?= htmlspecialchars($student['hoTen']) ?></td>
                                <td>-</td>
                                <td>
                                    <span class="status-badge pending">
                                        Chưa nộp
                                    </span>
                                </td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <?php 
                                endif;
                            endwhile; 
                            ?>
                        </tbody>
                    </table>
                    
                    <!-- Phân trang -->
                    <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px;">
                        <button type="button" id="prevBtn" onclick="changePage(-1)" class="btn btn-secondary btn-sm">
                            <i class="fas fa-chevron-left"></i> Trước
                        </button>
                        <span style="padding: 8px 15px; color: #666; font-weight: 600;">
                            Trang <span id="currentPage">1</span> / <span id="totalPages">1</span>
                        </span>
                        <button type="button" id="nextBtn" onclick="changePage(1)" class="btn btn-secondary btn-sm">
                            Sau <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Chưa có bài nộp nào</h3>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Chấm điểm -->
    <div class="modal" id="gradeModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h5 class="modal-title">Chấm điểm bài tập</h5>
                <button type="button" class="btn-close" onclick="closeGradeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="gradeForm">
                    <input type="hidden" name="maBaiNop" id="maBaiNop">
                    <div class="mb-3">
                        <label class="form-label">Học sinh</label>
                        <input type="text" id="studentName" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Điểm <span style="color: red;">*</span></label>
                        <input type="number" name="diem" id="diem" class="form-control" 
                               min="0" max="10" step="0.5" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nhận xét</label>
                        <textarea name="nhanXet" id="nhanXet" class="form-control" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeGradeModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-success" onclick="submitGrade()">
                    <i class="fas fa-save"></i> Lưu điểm
                </button>
            </div>
        </div>
    </div>

    <script>
        // Biến phân trang
        let currentPage = 1;
        const rowsPerPage = 10;
        let totalRows = 0;
        let allRows = [];

        function initPagination() {
            const table = document.getElementById('submissionsTable');
            if (!table) return;
            
            const tbody = table.getElementsByTagName('tbody')[0];
            allRows = Array.from(tbody.getElementsByTagName('tr')).filter(row => row.id !== 'noResultRow');
            totalRows = allRows.length;
            
            const totalPages = Math.ceil(totalRows / rowsPerPage);
            document.getElementById('totalPages').textContent = totalPages;
            
            showPage(1);
        }

        function showPage(page) {
            const totalPages = Math.ceil(totalRows / rowsPerPage);
            
            if (page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            if (totalPages === 0) page = 1;
            
            currentPage = page;
            
            // Ẩn tất cả các hàng
            allRows.forEach(row => row.style.display = 'none');
            
            // Hiển thị các hàng cho trang hiện tại
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            
            for (let i = start; i < end && i < totalRows; i++) {
                allRows[i].style.display = '';
            }
            
            document.getElementById('currentPage').textContent = page;
            document.getElementById('prevBtn').disabled = (page === 1);
            document.getElementById('nextBtn').disabled = (page === totalPages || totalPages === 0);
        }

        function changePage(delta) {
            showPage(currentPage + delta);
        }

        function openGradeModal(maBaiNop) {
            console.log('Opening modal for maBaiNop:', maBaiNop); // Debug
            
            if (!maBaiNop || maBaiNop <= 0) {
                alert('Mã bài nộp không hợp lệ: ' + maBaiNop);
                return;
            }
            
            fetch('<?= url("controller/cHomeworkDetail.php?action=getDetail") ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'maBaiNop=' + maBaiNop
            })
            .then(res => res.json())
            .then(data => {
                console.log('Submission data:', data); // Debug
                if(data.success) {
                    document.getElementById('maBaiNop').value = data.submission.maBaiNop;
                    document.getElementById('studentName').value = data.submission.tenHS;
                    document.getElementById('diem').value = data.submission.diem || '';
                    document.getElementById('nhanXet').value = data.submission.nhanXet || '';
                    document.getElementById('gradeModal').classList.add('show');
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Có lỗi xảy ra khi tải dữ liệu');
            });
        }

        function closeGradeModal() {
            document.getElementById('gradeModal').classList.remove('show');
        }

        function filterSubmissions() {
            const filterValue = document.getElementById('statusFilter').value;
            const table = document.getElementById('submissionsTable');
            if (!table) return;
            
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr')).filter(row => row.id !== 'noResultRow');
            
            // Ẩn tất cả các hàng trước
            rows.forEach(row => row.style.display = 'none');
            
            // Lọc các hàng theo trạng thái
            allRows = rows.filter(row => {
                const status = row.getAttribute('data-status');
                return filterValue === 'all' || status === filterValue;
            });
            
            totalRows = allRows.length;
            
            // Xóa thông báo "không tìm thấy" nếu có
            let noResultRow = document.getElementById('noResultRow');
            if (noResultRow) {
                noResultRow.remove();
            }
            
            // Hiển thị thông báo nếu không có kết quả
            if (totalRows === 0) {
                noResultRow = tbody.insertRow(0);
                noResultRow.id = 'noResultRow';
                const cell = noResultRow.insertCell(0);
                cell.colSpan = 5;
                cell.style.textAlign = 'center';
                cell.style.padding = '20px';
                cell.style.color = '#999';
                cell.style.fontStyle = 'italic';
                cell.innerHTML = '<i class="fas fa-search"></i> Không tìm thấy bài nộp với trạng thái này';
            }
            
            // Cập nhật phân trang
            const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;
            document.getElementById('totalPages').textContent = totalPages;
            
            // Hiển thị trang 1 sau khi lọc
            showPage(1);
        }
        
        // Khởi tạo phân trang khi trang được tải
        document.addEventListener('DOMContentLoaded', function() {
            initPagination();
        });

        function submitGrade() {
            const form = document.getElementById('gradeForm');
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const maBaiNop = formData.get('maBaiNop');
            
            console.log('Submitting grade for maBaiNop:', maBaiNop); // Debug
            console.log('FormData:', Array.from(formData.entries())); // Debug
            
            fetch('<?= url("controller/cHomeworkDetail.php?action=grade") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                console.log('Grade response:', data); // Debug
                alert(data.message);
                if(data.success) {
                    closeGradeModal();
                    location.reload();
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Có lỗi xảy ra khi chấm điểm');
            });
        }
        
        function toggleLockHomework() {
            const lockBtn = document.getElementById('lockBtn');
            const currentLockState = <?= $homework['khoaBai'] ?>;
            const action = currentLockState == 1 ? 'unlock' : 'lock';
            const confirmMsg = currentLockState == 1 
                ? 'Bạn có chắc muốn mở khóa bài tập này? Học sinh sẽ có thể nộp bài.' 
                : 'Bạn có chắc muốn khóa bài tập này? Học sinh sẽ không thể nộp bài.';
            
            if(!confirm(confirmMsg)) {
                return;
            }

            lockBtn.disabled = true;
            lockBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

            const formData = new FormData();
            formData.append('maBaiTap', <?= $maBaiTap ?>);
            formData.append('action', action);

            fetch('<?= url("controller/cHomeworkDetail.php?action=toggleLock") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if(data.success) {
                    location.reload();
                } else {
                    lockBtn.disabled = false;
                    lockBtn.innerHTML = action == 'lock' 
                        ? '<i class="fas fa-unlock"></i> Khóa bài tập' 
                        : '<i class="fas fa-lock"></i> Mở khóa bài tập';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Có lỗi xảy ra khi xử lý');
                lockBtn.disabled = false;
                lockBtn.innerHTML = action == 'lock' 
                    ? '<i class="fas fa-unlock"></i> Khóa bài tập' 
                    : '<i class="fas fa-lock"></i> Mở khóa bài tập';
            });
        }

        window.onclick = function(event) {
            const modal = document.getElementById('gradeModal');
            if (event.target == modal) {
                closeGradeModal();
            }
        }
    </script>
</body>
</html>
