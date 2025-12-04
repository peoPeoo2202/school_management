<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Remove session_start() - already started in index.php
require_once '../../controller/cSubmitHomework.php';

// Debug: Check session
if (!isset($_SESSION['maHS'])) {
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; margin: 20px;'>";
    echo "<h3>Lỗi phiên làm việc</h3>";
    echo "<p>Không tìm thấy thông tin học sinh trong session.</p>";
    echo "<p>Session hiện tại: <pre>" . print_r($_SESSION, true) . "</pre></p>";
    echo "</div>";
    exit;
}

$controller = new cSubmitHomework();
$maHS = $_SESSION['maHS'];

// Debug: Show maHS
echo "<!-- DEBUG: maHS = " . $maHS . " -->";

// Check if viewing specific homework
if (isset($_GET['id'])) {
    $maBaiTap = intval($_GET['id']);
    
    // Get homework details
    $homework = $controller->getHomeworkDetails($maBaiTap);
    if (!$homework) {
        echo "<p style='color: red; padding: 20px;'>Không tìm thấy bài tập!</p>";
        exit;
    }

    // Get submission if exists
    $submission = $controller->getStudentSubmission($maBaiTap, $maHS);

    // Handle submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
        $result = $controller->submitHomework($maBaiTap, $maHS, $_FILES['file'], $_POST['noiDung']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        // Refresh submission data
        $submission = $controller->getStudentSubmission($maBaiTap, $maHS);
    }
    ?>

    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nộp bài tập</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
            
            /* Back button at top */
            .back-button-top {
                margin: 20px auto;
                max-width: 1000px;
                padding: 0 20px;
            }
            
            .btn-back-top {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 20px;
                background: #6c757d;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 600;
                font-size: 14px;
                transition: all 0.3s ease;
            }
            
            .btn-back-top:hover {
                background: #5a6268;
                transform: translateX(-3px);
            }
            
            /* Container giống form giáo viên */
            .form-container {
                max-width: 1000px;
                margin: 0 auto 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .form-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 25px 30px;
                border-radius: 8px 8px 0 0;
            }
            
            .form-header h1 {
                font-size: 24px;
                font-weight: 600;
                margin: 0;
            }
            
            .form-body {
                padding: 30px;
            }
            
            /* Thông tin bài tập */
            .homework-details {
                background: #f8f9fa;
                border-left: 4px solid #667eea;
                padding: 20px;
                margin-bottom: 30px;
                border-radius: 4px;
            }
            
            .detail-row {
                display: flex;
                padding: 10px 0;
                border-bottom: 1px solid #e9ecef;
            }
            
            .detail-row:last-child {
                border-bottom: none;
            }
            
            .detail-label {
                font-weight: 600;
                color: #495057;
                min-width: 150px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .detail-label i {
                color: #667eea;
                width: 20px;
            }
            
            .detail-value {
                color: #212529;
                flex: 1;
            }
            
            /* Status badge */
            .status-badge {
                display: inline-block;
                padding: 6px 14px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
            }
            
            .status-danop { background: #d4edda; color: #155724; }
            .status-tre { background: #f8d7da; color: #721c24; }
            .status-chuacham { background: #fff3cd; color: #856404; }
            .status-dacham { background: #cfe2ff; color: #084298; }
            
            /* Submitted info */
            .submitted-section {
                background: #e7f3ff;
                border-left: 4px solid #0d6efd;
                padding: 20px;
                margin-bottom: 25px;
                border-radius: 4px;
            }
            
            .submitted-section h3 {
                color: #0d6efd;
                font-size: 18px;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            /* Form fields */
            .form-section {
                margin-bottom: 30px;
            }
            
            .form-section h3 {
                color: #212529;
                font-size: 18px;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 2px solid #e9ecef;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label {
                display: block;
                font-weight: 600;
                color: #495057;
                margin-bottom: 8px;
                font-size: 14px;
            }
            
            .form-group textarea {
                width: 100%;
                padding: 12px;
                border: 1px solid #ced4da;
                border-radius: 4px;
                font-family: inherit;
                font-size: 14px;
                resize: vertical;
                min-height: 120px;
                transition: border-color 0.15s ease-in-out;
            }
            
            .form-group textarea:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            }
            
            /* File upload giống giáo viên */
            .file-upload-area {
                border: 2px dashed #ced4da;
                border-radius: 8px;
                padding: 40px 20px;
                text-align: center;
                background: #f8f9fa;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .file-upload-area:hover {
                border-color: #667eea;
                background: #f0f4ff;
            }
            
            .file-upload-area.dragover {
                border-color: #667eea;
                background: #e7f3ff;
            }
            
            .file-upload-icon {
                font-size: 48px;
                color: #667eea;
                margin-bottom: 15px;
            }
            
            .file-upload-text {
                font-size: 16px;
                color: #495057;
                margin-bottom: 8px;
            }
            
            .file-upload-hint {
                font-size: 13px;
                color: #6c757d;
            }
            
            .file-upload-area input[type="file"] {
                display: none;
            }
            
            .selected-file {
                display: none;
                margin-top: 15px;
                padding: 12px;
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 4px;
            }
            
            .selected-file.show {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .file-icon {
                font-size: 24px;
                color: #667eea;
            }
            
            .file-info {
                flex: 1;
            }
            
            .file-name {
                font-weight: 600;
                color: #212529;
                margin-bottom: 4px;
            }
            
            .file-size {
                font-size: 13px;
                color: #6c757d;
            }
            
            .remove-file {
                background: #dc3545;
                color: white;
                border: none;
                padding: 6px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
            }
            
            .remove-file:hover {
                background: #c82333;
            }
            
            /* Buttons */
            .form-actions {
                display: flex;
                gap: 12px;
                padding-top: 20px;
                border-top: 1px solid #e9ecef;
                margin-top: 30px;
            }
            
            .btn {
                padding: 12px 24px;
                border: none;
                border-radius: 6px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                text-decoration: none;
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            }
            
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            
            .btn-secondary:hover {
                background: #5a6268;
            }
            
            /* Message */
            .message {
                padding: 15px 20px;
                border-radius: 6px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .message.success {
                background: #d4edda;
                color: #155724;
                border-left: 4px solid #28a745;
            }
            
            .message.error {
                background: #f8d7da;
                color: #721c24;
                border-left: 4px solid #dc3545;
            }
            
            .message i {
                font-size: 20px;
            }
            
            .breadcrumb {
                background: white;
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 14px;
            }
            
            .breadcrumb a {
                color: #667eea;
                text-decoration: none;
                transition: color 0.3s;
            }
            
            .breadcrumb a:hover {
                color: #764ba2;
                text-decoration: underline;
            }
            
            .breadcrumb .separator {
                color: #6c757d;
            }
            
            .breadcrumb .current {
                color: #495057;
                font-weight: 600;
            }
            
            .homework-page {
                padding: 20px;
                background: #f8f9fa;
                min-height: 100vh;
            }
            
            .page-header {
                background: white;
                padding: 25px 30px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                margin-bottom: 25px;
            }
            
            .page-header h2 {
                color: #2c3e50;
                font-size: 28px;
                font-weight: 600;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .page-header h2 i {
                color: #667eea;
            }
            
            /* Search and filter section */
            .filter-section {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                margin-bottom: 25px;
            }
            
            .search-box {
                position: relative;
                max-width: 500px;
            }
            
            .search-box input {
                width: 100%;
                padding: 12px 45px 12px 20px;
                border: 2px solid #e9ecef;
                border-radius: 25px;
                font-size: 15px;
                transition: all 0.3s ease;
            }
            
            .search-box input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            
            .search-box i {
                position: absolute;
                right: 20px;
                top: 50%;
                transform: translateY(-50%);
                color: #6c757d;
                font-size: 16px;
            }
            
            .filter-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin-top: 15px;
            }
            
            .filter-btn {
                padding: 8px 16px;
                border: 2px solid #e9ecef;
                background: white;
                border-radius: 20px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                color: #495057;
            }
            
            .filter-btn:hover {
                border-color: #667eea;
                color: #667eea;
            }
            
            .filter-btn.active {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-color: #667eea;
                color: white;
            }
            
            .subject-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
            }
            
            .subject-card {
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                cursor: pointer;
                text-decoration: none;
                display: block;
                color: inherit; /* Thêm dòng này */
            }
            
            .subject-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                text-decoration: none; /* Thêm dòng này */
            }
            
            .subject-card.hidden {
                display: none;
            }
            
            .subject-header {
                padding: 25px 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-align: center;
            }
            
            .subject-icon {
                font-size: 48px;
                margin-bottom: 15px;
            }
            
            .subject-name {
                font-size: 20px;
                font-weight: 600;
                margin: 0;
            }
            
            .subject-body {
                padding: 20px;
            }
            
            .subject-stats {
                display: flex;
                justify-content: space-around;
                margin-bottom: 20px;
            }
            
            .stat-item {
                text-align: center;
            }
            
            .stat-number {
                font-size: 32px;
                font-weight: 700;
                display: block;
                margin-bottom: 8px;
            }
            
            .stat-number.total {
                color: #667eea;
            }
            
            .stat-number.overdue {
                color: #dc3545;
            }
            
            .stat-label {
                font-size: 13px;
                color: #6c757d;
                font-weight: 600;
            }
            
            .subject-footer {
                padding: 15px 20px;
                background: #f8f9fa;
                text-align: center;
                border-top: 1px solid #e9ecef;
            }
            
            .view-homework-btn {
                color: #667eea;
                font-weight: 600;
                font-size: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                pointer-events: none; /* Thêm dòng này để không chặn click của thẻ cha */
            }
            
            .empty-state {
                background: white;
                padding: 60px 20px;
                text-align: center;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .empty-state i {
                font-size: 64px;
                color: #dee2e6;
                margin-bottom: 20px;
            }
            
            .empty-state h3 {
                color: #6c757d;
                font-size: 20px;
                margin-bottom: 10px;
            }
            
            .empty-state p {
                color: #adb5bd;
                font-size: 14px;
            }
            
            .no-results {
                background: white;
                padding: 40px 20px;
                text-align: center;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .no-results i {
                font-size: 48px;
                color: #dee2e6;
                margin-bottom: 15px;
            }
            
            .no-results h3 {
                color: #6c757d;
                font-size: 18px;
                margin-bottom: 8px;
            }
            
            .no-results p {
                color: #adb5bd;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <!-- Back button at top -->
        <div class="back-button-top">
            <a href="index.php?page=submitHomework" class="btn-back-top">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
        </div>

        <div class="form-container">
            <div class="form-header">
                <h1><i class="fas fa-file-upload"></i> Nộp bài tập</h1>
            </div>
            
            <div class="form-body">
                <?php if (isset($message)): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Thông tin bài tập -->
                <div class="homework-details">
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-book"></i>
                            <span>Tên bài tập:</span>
                        </div>
                        <div class="detail-value"><?php echo htmlspecialchars($homework['tenBaiTap']); ?></div>
                    </div>
                    <!-- <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-users"></i>
                            <span>Lớp:</span>
                        </div>
                        <div class="detail-value"><?php echo htmlspecialchars($homework['tenLop'] ?? 'N/A'); ?></div>
                    </div> -->
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Môn học:</span>
                        </div>
                        <div class="detail-value"><?php echo htmlspecialchars($homework['tenMonHoc'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-clock"></i>
                            <span>Hạn nộp:</span>
                        </div>
                        <div class="detail-value" style="color: #dc3545; font-weight: 600;">
                            <?php echo date('d/m/Y H:i', strtotime($homework['thoiGianNop'])); ?>
                        </div>
                    </div>
                    <?php if ($homework['yeuCauBaiTap']): ?>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-list-ul"></i>
                            <span>Yêu cầu:</span>
                        </div>
                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($homework['yeuCauBaiTap'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($homework['tenFile']): ?>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-paperclip"></i>
                            <span>File bài tập:</span>
                        </div>
                        <div class="detail-value">
                            <a href="../../<?php echo htmlspecialchars($homework['duongDan']); ?>" download style="color: #667eea; text-decoration: none;">
                                <i class="fas fa-download"></i> <?php echo htmlspecialchars($homework['tenFile']); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Thông tin bài đã nộp -->
                <?php if ($submission): ?>
                <div class="submitted-section">
                    <h3><i class="fas fa-check-circle"></i> Bài làm đã nộp</h3>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Thời gian nộp:</span>
                        </div>
                        <div class="detail-value"><?= date('d/m/Y H:i:s', strtotime($submission['ngayNop'])) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-info-circle"></i>
                            <span>Trạng thái:</span>
                        </div>
                        <div class="detail-value">
                            <span class="status-badge status-<?= strtolower($submission['trangThai']) ?>">
                                <?php 
                                $status = ['Danop' => 'Đã nộp', 'Tre' => 'Nộp trễ', 'Chuacham' => 'Chưa chấm', 'Dacham' => 'Đã chấm'];
                                echo $status[$submission['trangThai']] ?? $submission['trangThai']; 
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($submission['noiDung']): ?>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-align-left"></i>
                            <span>Nội dung:</span>
                        </div>
                        <div class="detail-value"><?= nl2br(htmlspecialchars($submission['noiDung'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($submission['tenFile']): ?>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-file"></i>
                            <span>File đã nộp:</span>
                        </div>
                        <div class="detail-value">
                            <a href="../../<?= htmlspecialchars($submission['duongDan']) ?>" download style="color: #0d6efd; text-decoration: none;">
                                <i class="fas fa-download"></i> <?= htmlspecialchars($submission['tenFile']) ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($submission['diem'] !== null): ?>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-star"></i>
                            <span>Điểm số:</span>
                        </div>
                        <div class="detail-value">
                            <span style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 8px 16px; border-radius: 25px; font-size: 18px; font-weight: 700; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);">
                                <i class="fas fa-trophy"></i>
                                <?= number_format($submission['diem'], 1) ?>/10
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($submission['nhanXet']): ?>
                    <div class="detail-row" style="align-items: flex-start;">
                        <div class="detail-label" style="margin-top: 8px;">
                            <i class="fas fa-comment-dots"></i>
                            <span>Nhận xét của giáo viên:</span>
                        </div>
                        <div class="detail-value">
                            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 15px; border-radius: 6px;">
                                <i class="fas fa-quote-left" style="color: #ffc107; font-size: 14px; margin-right: 8px;"></i>
                                <span style="color: #856404; line-height: 1.6;"><?= nl2br(htmlspecialchars($submission['nhanXet'])) ?></span>
                                <i class="fas fa-quote-right" style="color: #ffc107; font-size: 14px; margin-left: 8px;"></i>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($submission['diem'] === null && $submission['trangThai'] === 'Chuacham'): ?>
                    <div style="margin-top: 15px; padding: 12px; background: #e7f3ff; border-left: 4px solid #0d6efd; border-radius: 6px;">
                        <i class="fas fa-info-circle" style="color: #0d6efd; margin-right: 8px;"></i>
                        <span style="color: #084298; font-size: 14px;">Bài làm của bạn đang chờ giáo viên chấm điểm</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Form nộp bài -->
                <div class="form-section">
                    <h3><?php echo $submission ? 'Nộp lại bài tập' : 'Nộp bài tập'; ?></h3>
                    
                    <form method="POST" enctype="multipart/form-data" id="submitForm">
                        <div class="form-group">
                            <label for="noiDung">
                                <i class="fas fa-align-left"></i> Nội dung bài làm (tùy chọn)
                            </label>
                            <textarea name="noiDung" id="noiDung" placeholder="Nhập nội dung bài làm hoặc ghi chú của bạn..."><?php echo $submission ? htmlspecialchars($submission['noiDung']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-cloud-upload-alt"></i> Tải lên file bài làm
                            </label>
                            <div class="file-upload-area" id="fileUploadArea" onclick="document.getElementById('fileInput').click()">
                                <div class="file-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="file-upload-text">
                                    Kéo thả file vào đây hoặc click để chọn file
                                </div>
                                <div class="file-upload-hint">
                                    Định dạng: PDF, Word, Ảnh (JPG, PNG), File nén (ZIP, RAR). Tối đa 10MB
                                </div>
                                <input type="file" name="file" id="fileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip,.rar">
                            </div>
                            <div class="selected-file" id="selectedFile">
                                <div class="file-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="file-info">
                                    <div class="file-name" id="fileName"></div>
                                    <div class="file-size" id="fileSize"></div>
                                </div>
                                <button type="button" class="remove-file" onclick="removeFile()">
                                    <i class="fas fa-times"></i> Xóa
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Nộp bài
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            // File upload handling
            const fileInput = document.getElementById('fileInput');
            const fileUploadArea = document.getElementById('fileUploadArea');
            const selectedFile = document.getElementById('selectedFile');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');

            fileInput.addEventListener('change', handleFileSelect);

            // Drag and drop
            fileUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileUploadArea.classList.add('dragover');
            });

            fileUploadArea.addEventListener('dragleave', () => {
                fileUploadArea.classList.remove('dragover');
            });

            fileUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                fileUploadArea.classList.remove('dragover');
                fileInput.files = e.dataTransfer.files;
                handleFileSelect();
            });

            function handleFileSelect() {
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    fileName.textContent = file.name;
                    fileSize.textContent = formatFileSize(file.size);
                    selectedFile.classList.add('show');
                }
            }

            function removeFile() {
                fileInput.value = '';
                selectedFile.classList.remove('show');
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            }

            // Form validation
            document.getElementById('submitForm').addEventListener('submit', function(e) {
                const noiDung = document.getElementById('noiDung').value.trim();
                const hasFile = fileInput.files.length > 0;

                if (!hasFile && !noiDung) {
                    e.preventDefault();
                    alert('Vui lòng tải lên file hoặc nhập nội dung bài làm!');
                    return false;
                }

                if (hasFile) {
                    const file = fileInput.files[0];
                    const maxSize = 10 * 1024 * 1024;
                    if (file.size > maxSize) {
                        e.preventDefault();
                        alert('File quá lớn! Vui lòng chọn file nhỏ hơn 10MB.');
                        return false;
                    }
                }

                if (!confirm('Bạn có chắc chắn muốn nộp bài?')) {
                    e.preventDefault();
                    return false;
                }
            });
        </script>
    </body>
    </html>

    <?php
    exit;
} elseif (isset($_GET['subject'])) {
    // Show homework list for specific subject
    $maMonHoc = intval($_GET['subject']);
    $homeworks = $controller->getAllHomeworkForStudent($maHS, $maMonHoc);
    $subjects = $controller->getAllSubjectsForStudent($maHS);
    
    // Get subject name
    $subjectName = '';
    foreach ($subjects as $subj) {
        if ($subj['maMonHoc'] == $maMonHoc) {
            $subjectName = $subj['tenMonHoc'];
            break;
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Danh sách bài tập - <?php echo htmlspecialchars($subjectName); ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
            
            .homework-page {
                padding: 20px;
                background: #f8f9fa;
                min-height: 100vh;
            }
            
            .page-header {
                background: white;
                padding: 25px 30px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                margin-bottom: 25px;
            }
            
            .page-header h2 {
                color: #2c3e50;
                font-size: 28px;
                font-weight: 600;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .page-header h2 i {
                color: #667eea;
            }
            
            .breadcrumb {
                background: white;
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 14px;
            }
            
            .breadcrumb a {
                color: #667eea;
                text-decoration: none;
                transition: color 0.3s;
            }
            
            .breadcrumb a:hover {
                color: #764ba2;
                text-decoration: underline;
            }
            
            .breadcrumb .separator {
                color: #6c757d;
            }
            
            .breadcrumb .current {
                color: #495057;
                font-weight: 600;
            }
            
            .homework-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 20px;
            }
            
            .homework-item {
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                border-left: 4px solid #667eea;
            }
            
            .homework-item:hover {
                transform: translateY(-4px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            .homework-header-card {
                padding: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-align: center;
            }
            
            .homework-title {
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 8px;
            }
            
            .homework-meta {
                display: flex;
                justify-content: center;
                gap: 15px;
                font-size: 13px;
                opacity: 0.95;
            }
            
            .homework-meta span {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .homework-body {
                padding: 20px;
            }
            
            .homework-info-grid {
                display: grid;
                gap: 12px;
                margin-bottom: 15px;
            }
            
            .info-row-card {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                font-size: 14px;
            }
            
            .info-row-card i {
                color: #667eea;
                width: 18px;
                margin-top: 2px;
            }
            
            .info-label-card {
                font-weight: 600;
                color: #495057;
                min-width: 80px;
            }
            
            .info-value-card {
                color: #212529;
                flex: 1;
            }
            
            .deadline-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
            }
            
            .deadline-ok {
                background: #d4edda;
                color: #155724;
            }
            
            .deadline-warning {
                background: #f8d7da;
                color: #721c24;
            }
            
            .submission-status {
                background: #f8f9fa;
                padding: 12px;
                border-radius: 6px;
                margin: 15px 0;
            }
            
            .status-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
            }
            
            .status-row:last-child {
                margin-bottom: 0;
            }
            
            .status-label {
                font-size: 13px;
                color: #6c757d;
            }
            
            .status-value {
                font-weight: 600;
                font-size: 14px;
            }
            
            .score-badge {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 6px 14px;
                border-radius: 20px;
                font-size: 15px;
                font-weight: 700;
            }
            
            .homework-actions {
                display: flex;
                gap: 10px;
                padding-top: 15px;
                border-top: 1px solid #e9ecef;
                margin-top: 15px;
            }
            
            .btn-card {
                flex: 1;
                padding: 10px;
                border: none;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                text-align: center;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
            }
            
            .btn-submit {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            
            .btn-submit:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
            }
            
            .btn-view {
                background: #0d6efd;
                color: white;
            }
            
            .btn-view:hover {
                background: #0b5ed7;
            }
            
            .btn-disabled {
                background: #e9ecef;
                color: #6c757d;
                cursor: not-allowed;
            }
            
            .empty-state {
                background: white;
                padding: 60px 20px;
                text-align: center;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .empty-state i {
                font-size: 64px;
                color: #dee2e6;
                margin-bottom: 20px;
            }
            
            .empty-state h3 {
                color: #6c757d;
                font-size: 20px;
                margin-bottom: 10px;
            }
            
            .empty-state p {
                color: #adb5bd;
                font-size: 14px;
            }
            
            .no-results {
                background: white;
                padding: 40px 20px;
                text-align: center;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .no-results i {
                font-size: 48px;
                color: #dee2e6;
                margin-bottom: 15px;
            }
            
            .no-results h3 {
                color: #6c757d;
                font-size: 18px;
                margin-bottom: 8px;
            }
            
            .no-results p {
                color: #adb5bd;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="homework-page">
            <!-- Breadcrumb -->
            <div class="page-header">
                <h2><i class="fas fa-clipboard-list"></i> Danh sách bài tập - <?php echo htmlspecialchars($subjectName); ?></h2>
            </div>

            <div class="breadcrumb">
                <a href="index.php?page=submitHomework">
                    <i class="fas fa-home"></i> Danh sách môn học
                </a>
                <span class="separator">/</span>
                <span class="current"><?php echo htmlspecialchars($subjectName); ?></span>
            </div>


            <?php if (empty($homeworks)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Chưa có bài tập được giao</h3>
                    <p>Môn <?php echo htmlspecialchars($subjectName); ?> hiện tại chưa có bài tập nào được giao.</p>
                    <a href="index.php?page=submitHomework" class="btn-card btn-submit" style="margin-top: 20px; display: inline-flex;">
                        <i class="fas fa-arrow-left"></i> Quay lại danh sách môn học
                    </a>
                </div>
            <?php else: ?>
                <div class="homework-grid">
                    <?php foreach ($homeworks as $hw): 
                        $deadline = strtotime($hw['thoiGianNop']);
                        $now = time();
                        $isLate = $now > $deadline;
                        $canSubmit = !$isLate || $hw['choPhepNopTre'] == 1;
                    ?>
                        <div class="homework-item">
                            <div class="homework-header-card">
                                <div class="homework-title">
                                    <?php echo htmlspecialchars($hw['tenBaiTap']); ?>
                                </div>
                                <div class="homework-meta">
                                    <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($hw['tenMonHoc'] ?? 'N/A'); ?></span>
                                    <span><i class="fas fa-users"></i> <?php echo htmlspecialchars($hw['tenLop'] ?? 'N/A'); ?></span>
                                </div>
                            </div>

                            <div class="homework-body">
                                <div class="homework-info-grid">
                                    <div class="info-row-card">
                                        <i class="fas fa-clock"></i>
                                        <div>
                                            <div class="info-label-card">Hạn nộp:</div>
                                            <div class="info-value-card">
                                                <span class="deadline-badge <?php echo $isLate ? 'deadline-warning' : 'deadline-ok'; ?>">
                                                    <i class="fas fa-<?php echo $isLate ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                                                    <?php echo date('d/m/Y H:i', $deadline); ?>
                                                    <?php if ($isLate): ?>(Đã quá hạn)<?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($hw['yeuCauBaiTap']): ?>
                                    <div class="info-row-card">
                                        <i class="fas fa-list-ul"></i>
                                        <div>
                                            <div class="info-label-card">Yêu cầu:</div>
                                            <div class="info-value-card">
                                                <?php echo htmlspecialchars(substr($hw['yeuCauBaiTap'], 0, 80)); ?>
                                                <?php echo strlen($hw['yeuCauBaiTap']) > 80 ? '...' : ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($hw['daNop']): ?>
                                <div class="submission-status">
                                    <div class="status-row">
                                        <span class="status-label">Trạng thái:</span>
                                        <span class="status-value" style="color: #28a745;">
                                            <i class="fas fa-check-circle"></i>
                                            <?php 
                                            $statusText = ['Danop' => 'Đã nộp', 'Tre' => 'Nộp trễ', 'Chuacham' => 'Chưa chấm', 'Dacham' => 'Đã chấm'];
                                            echo $statusText[$hw['trangThai']] ?? $hw['trangThai'];
                                            ?>
                                        </span>
                                    </div>
                                    <?php if ($hw['ngayNop']): ?>
                                    <div class="status-row">
                                        <span class="status-label">Đã nộp lúc:</span>
                                        <span class="status-value"><?php echo date('d/m/Y H:i', strtotime($hw['ngayNop'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($hw['diem']): ?>
                                    <div class="status-row">
                                        <span class="status-label">Điểm số:</span>
                                        <span class="score-badge"><?php echo $hw['diem']; ?>/10</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <div class="homework-actions">
                                    <?php if (!$hw['daNop']): ?>
                                        <?php if ($canSubmit): ?>
                                            <a href="index.php?page=submitHomework&id=<?php echo $hw['maBaiTap']; ?>" class="btn-card btn-submit">
                                                <i class="fas fa-upload"></i> Nộp bài
                                            </a>
                                        <?php else: ?>
                                            <button class="btn-card btn-disabled" disabled>
                                                <i class="fas fa-lock"></i> Đã hết hạn
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="index.php?page=submitHomework&id=<?php echo $hw['maBaiTap']; ?>" class="btn-card btn-view">
                                            <i class="fas fa-eye"></i> Xem chi tiết
                                        </a>
                                        <?php if ($canSubmit): ?>
                                            <a href="index.php?page=submitHomework&id=<?php echo $hw['maBaiTap']; ?>" class="btn-card btn-submit">
                                                <i class="fas fa-redo"></i> Nộp lại
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>

    <?php
    exit;
} else {
    // Show subject list
    $subjects = $controller->getAllSubjectsForStudent($maHS);
    ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .homework-page {
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .page-header h2 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-header h2 i {
            color: #667eea;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .search-box {
            position: relative;
            max-width: 500px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-box i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 16px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #495057;
        }
        
        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
        }
        
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .subject-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: block;
            color: inherit; /* Thêm dòng này */
        }
        
        .subject-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            text-decoration: none; /* Thêm dòng này */
        }
        
        .subject-card.hidden {
            display: none;
        }
        
        .subject-header {
            padding: 25px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }
        
        .subject-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .subject-name {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }
        
        .subject-body {
            padding: 20px;
        }
        
        .subject-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            display: block;
            margin-bottom: 8px;
        }
        
        .stat-number.total {
            color: #667eea;
        }
        
        .stat-number.overdue {
            color: #dc3545;
        }
        
        .stat-label {
            font-size: 13px;
            color: #6c757d;
            font-weight: 600;
        }
        
        .subject-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .view-homework-btn {
            color: #667eea;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            pointer-events: none; /* Thêm dòng này để không chặn click của thẻ cha */
        }
        
        .empty-state {
            background: white;
            padding: 60px 20px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .empty-state i {
            font-size: 64px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #6c757d;
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #adb5bd;
            font-size: 14px;
        }
        
        .no-results {
            background: white;
            padding: 40px 20px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .no-results i {
            font-size: 48px;
            color: #dee2e6;
            margin-bottom: 15px;
        }
        
        .no-results h3 {
            color: #6c757d;
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .no-results p {
            color: #adb5bd;
            font-size: 14px;
        }
    </style>

    <div class="homework-page">
        <div class="page-header">
            <h2><i class="fas fa-book"></i> Làm bài tập</h2>
        </div>

        <?php if (empty($subjects)): ?>
            <div class="empty-state">
                <i class="fas fa-graduation-cap"></i>
                <h3>Không có môn học</h3>
                <p>Không tìm thấy môn học nào trong hệ thống.</p>
            </div>
        <?php else: ?>
            <!-- Search and Filter Section -->
            <div class="filter-section">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Tìm kiếm môn học...">
                    <i class="fas fa-search"></i>
                </div>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">
                        <i class="fas fa-th"></i> Tất cả
                    </button>
                    <button class="filter-btn" data-filter="have-homework">
                        <i class="fas fa-clipboard-check"></i> Có bài tập
                    </button>
                    <button class="filter-btn" data-filter="no-homework">
                        <i class="fas fa-inbox"></i> Chưa có bài tập
                    </button>
                    <button class="filter-btn" data-filter="overdue">
                        <i class="fas fa-exclamation-triangle"></i> Có bài quá hạn
                    </button>
                </div>
            </div>

            <div class="subject-grid" id="subjectGrid">
                <?php foreach ($subjects as $subject): ?>
                    <?php if ($subject['tongBaiTap'] > 0): ?>
                        <a href="index.php?page=submitHomework&subject=<?php echo $subject['maMonHoc']; ?>" 
                           class="subject-card"
                           data-name="<?php echo strtolower(htmlspecialchars($subject['tenMonHoc'])); ?>"
                           data-total="<?php echo $subject['tongBaiTap']; ?>"
                           data-overdue="<?php echo $subject['quaHan']; ?>"
                           style="text-decoration: none; color: inherit;">
                            <div class="subject-header" style="pointer-events: none;">
                                <div class="subject-icon">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <h3 class="subject-name"><?php echo htmlspecialchars($subject['tenMonHoc']); ?></h3>
                            </div>
                            
                            <div class="subject-body" style="pointer-events: none;">
                                <div class="subject-stats">
                                    <div class="stat-item">
                                        <span class="stat-number total"><?php echo $subject['tongBaiTap']; ?></span>
                                        <span class="stat-label">Số bài tập</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number overdue"><?php echo $subject['quaHan']; ?></span>
                                        <span class="stat-label">Quá hạn</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="subject-footer" style="pointer-events: none;">
                                <span class="view-homework-btn">
                                    <span>Xem chi tiết</span>
                                    <i class="fas fa-arrow-right"></i>
                                </span>
                            </div>
                        </a>
                    <?php else: ?>
                        <!-- Thẻ môn học không có bài tập - KHÔNG thể click -->
                        <div class="subject-card"
                             data-name="<?php echo strtolower(htmlspecialchars($subject['tenMonHoc'])); ?>"
                             data-total="0"
                             data-overdue="0"
                             style="opacity: 0.7; cursor: not-allowed;">
                            <div class="subject-header" style="background: linear-gradient(135deg, #9e9e9e 0%, #757575 100%);">
                                <div class="subject-icon">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <h3 class="subject-name"><?php echo htmlspecialchars($subject['tenMonHoc']); ?></h3>
                            </div>
                            
                            <div class="subject-body">
                                <div class="subject-stats">
                                    <div class="stat-item">
                                        <span class="stat-number" style="color: #9e9e9e;">0</span>
                                        <span class="stat-label">Số bài tập</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number" style="color: #9e9e9e;">0</span>
                                        <span class="stat-label">Quá hạn</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="subject-footer" style="background: #f5f5f5;">
                                <span class="view-homework-btn" style="color: #9e9e9e;">
                                    <i class="fas fa-inbox"></i>
                                    <span>Chưa có bài tập</span>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- No results message -->
            <div class="no-results" id="noResults" style="display: none;">
                <i class="fas fa-search"></i>
                <h3>Không tìm thấy kết quả</h3>
                <p>Thử tìm kiếm với từ khóa khác hoặc thay đổi bộ lọc</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Hàm chuẩn hóa tiếng Việt (bỏ dấu)
        function removeVietnameseTones(str) {
            str = str.toLowerCase();
            str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, 'a');
            str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, 'e');
            str = str.replace(/ì|í|ị|ỉ|ĩ/g, 'i');
            str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, 'o');
            str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, 'u');
            str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, 'y');
            str = str.replace(/đ/g, 'd');
            return str;
        }

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const subjectCards = document.querySelectorAll('.subject-card');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const noResults = document.getElementById('noResults');
        const subjectGrid = document.getElementById('subjectGrid');
        
        let currentFilter = 'all';
        
        // Search function
        searchInput.addEventListener('input', function() {
            const searchTerm = removeVietnameseTones(this.value.toLowerCase());
            filterSubjects(searchTerm, currentFilter);
        });
        
        // Filter buttons
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                currentFilter = this.dataset.filter;
                const searchTerm = removeVietnameseTones(searchInput.value.toLowerCase());
                filterSubjects(searchTerm, currentFilter);
            });
        });
        
        function filterSubjects(searchTerm, filter) {
            let visibleCount = 0;
            
            subjectCards.forEach(card => {
                const name = card.dataset.name;
                const nameNormalized = removeVietnameseTones(name);
                const total = parseInt(card.dataset.total);
                const overdue = parseInt(card.dataset.overdue);
                
                // Check search term (so sánh cả có dấu và không dấu)
                const matchesSearch = searchTerm === '' || 
                                     name.includes(searchTerm) || 
                                     nameNormalized.includes(searchTerm);
                
                // Check filter
                let matchesFilter = true;
                if (filter === 'have-homework') {
                    matchesFilter = total > 0;
                } else if (filter === 'no-homework') {
                    matchesFilter = total === 0;
                } else if (filter === 'overdue') {
                    matchesFilter = overdue > 0;
                }
                
                // Show or hide card
                if (matchesSearch && matchesFilter) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });
            
            // Show/hide no results message
            if (visibleCount === 0) {
                subjectGrid.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                subjectGrid.style.display = 'grid';
                noResults.style.display = 'none';
            }
        }
    </script>

    <?php
}
?>