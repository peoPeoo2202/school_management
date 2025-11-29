<?php
// Khởi động session trước khi require config
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Lấy thông tin bài tập
require_once(__DIR__ . '/../../model/mHomeworkDetail.php');
$model = new mHomeworkDetail();

$maBaiTap = isset($_GET['id']) ? intval($_GET['id']) : 0;
$homework = $model->getHomeworkDetail($maBaiTap);

if (!$homework) {
    header("Location: " . url('controller/cAssignHomework.php'));
    exit();
}

// Lấy danh sách bài nộp
$submissions = $model->getSubmissions($maBaiTap);

// Lấy danh sách học sinh trong lớp
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #5081BE;
        }

        .info-item label {
            font-weight: 600;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 8px;
        }

        .info-item .value {
            color: #333;
            font-size: 16px;
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
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.green { background: #28a745; }
        .stat-icon.orange { background: #ffc107; }
        .stat-icon.red { background: #dc3545; }
        .stat-icon.blue { background: #5081BE; }

        .stat-info h3 {
            margin: 0;
            font-size: 14px;
            color: #666;
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
            overflow-x: auto;
        }

        .submissions-table h3 {
            margin: 0 0 20px 0;
            color: #333;
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
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            background: #f9f9f9;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
        }

        td {
            color: #666;
            font-size: 14px;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.submitted {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.late {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.not-submitted {
            background: #f8d7da;
            color: #721c24;
        }

        .status-badge.graded {
            background: #cce5ff;
            color: #004085;
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
            gap: 5px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        .btn-primary {
            background: #5081BE;
            color: white;
        }

        .btn-primary:hover {
            background: #2d5a8c;
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
            background: #545b62;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
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
            max-width: 600px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #5081BE 0%, #2d5a8c 100%);
            border-radius: 16px 16px 0 0;
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
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 3px rgba(80, 129, 190, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 2px solid #f0f0f0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .file-link {
            color: #5081BE;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .file-link:hover {
            text-decoration: underline;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">
            <a href="<?php echo url('controller/cAssignHomework.php'); ?>" class="back-button">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>

            <!-- Thông tin bài tập -->
            <div class="homework-info">
                <h2 style="margin: 0 0 20px 0; color: #5081BE;">
                    <i class="fas fa-clipboard-list"></i> <?= htmlspecialchars($homework['tenBaiTap']) ?>
                </h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <label>Lớp</label>
                        <div class="value"><?= htmlspecialchars($homework['tenLop']) ?></div>
                    </div>
                    <div class="info-item">
                        <label>Môn học</label>
                        <div class="value"><?= htmlspecialchars($homework['tenMonHoc']) ?></div>
                    </div>
                    <div class="info-item">
                        <label>Hạn nộp</label>
                        <div class="value"><?= date('d/m/Y H:i', strtotime($homework['thoiGianNop'])) ?></div>
                    </div>
                    <div class="info-item">
                        <label>Cho phép nộp trễ</label>
                        <div class="value"><?= $homework['choPhepNopTre'] == 1 ? 'Có' : 'Không' ?></div>
                    </div>
                </div>

                <?php if(!empty($homework['yeuCauBaiTap'])): ?>
                <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                    <label style="font-weight: 600; color: #666; display: block; margin-bottom: 8px;">Yêu cầu bài tập:</label>
                    <div style="color: #333;"><?= nl2br(htmlspecialchars($homework['yeuCauBaiTap'])) ?></div>
                </div>
                <?php endif; ?>

                <?php if(!empty($homework['tenFile'])): ?>
                <div style="margin-top: 15px;">
                    <label style="font-weight: 600; color: #666;">File đính kèm:</label>
                    <a href="<?php echo url('controller/cAssignHomework.php?action=download&id=' . $homework['maBaiTap']); ?>" 
                       class="file-link" target="_blank">
                        <i class="fas fa-file-download"></i> <?= htmlspecialchars($homework['tenFile']) ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Thống kê -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Tổng học sinh</h3>
                        <div class="number"><?= $students->num_rows ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Đã nộp</h3>
                        <div class="number"><?= $homework['soLuongNopBai'] ?? 0 ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Chưa chấm</h3>
                        <div class="number" id="uncheckedCount">0</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Chưa nộp</h3>
                        <div class="number"><?= $students->num_rows - ($homework['soLuongNopBai'] ?? 0) ?></div>
                    </div>
                </div>
            </div>

            <div class="submissions-table">
                <h3>
                    <i class="fas fa-list"></i> Danh sách bài nộp
                </h3>

                <!-- Danh sách bài nộp -->
                <?php if($submissions->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Họ tên học sinh</th>
                            <th>Ngày nộp</th>
                            <th>Trạng thái</th>
                            <th>Điểm</th>
                            <th>File nộp</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stt = 1;
                        $unchecked = 0;
                        while($sub = $submissions->fetch_assoc()): 
                            $isLate = strtotime($sub['ngayNop']) > strtotime($homework['thoiGianNop']);
                            $statusClass = $sub['trangThai'] == 'Dacham' ? 'graded' : ($isLate ? 'late' : 'submitted');
                            $statusText = $sub['trangThai'] == 'Dacham' ? 'Đã chấm' : ($isLate ? 'Nộp trễ' : 'Đã nộp');
                            
                            if($sub['trangThai'] != 'Dacham') $unchecked++;
                        ?>
                        <tr>
                            <td><?= $stt++ ?></td>
                            <td><?= htmlspecialchars($sub['hoTen']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($sub['ngayNop'])) ?></td>
                            <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                            <td><?= $sub['diem'] ? number_format($sub['diem'], 1) : '<span style="color: #999;">Chưa chấm</span>' ?></td>
                            <td>
                                <?php if($sub['tenFile']): ?>
                                <a href="<?php echo url('controller/cHomeworkDetail.php?action=downloadSubmission&id=' . $sub['maBaiNop']); ?>" 
                                   class="file-link" target="_blank">
                                    <i class="fas fa-file-download"></i> <?= htmlspecialchars($sub['tenFile']) ?>
                                </a>
                                <?php else: ?>
                                <span style="color: #999;">Không có file</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick='openGradeModal(<?= htmlspecialchars(json_encode($sub)) ?>)'>
                                    <i class="fas fa-edit"></i> Chấm điểm
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <script>document.getElementById('uncheckedCount').textContent = <?= $unchecked ?>;</script>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Chưa có bài nộp nào</h3>
                    <p>Học sinh chưa nộp bài tập</p>
                </div>
                <?php endif; ?>
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
                    
                    <div class="form-group">
                        <label class="form-label">Học sinh</label>
                        <input type="text" class="form-control" id="studentName" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ngày nộp</label>
                        <input type="text" class="form-control" id="submitDate" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nội dung bài làm</label>
                        <textarea class="form-control" id="noiDung" readonly></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Điểm <span style="color: red;">*</span></label>
                        <input type="number" name="diem" id="diem" class="form-control" 
                               min="0" max="10" step="0.5" required 
                               placeholder="Nhập điểm (0-10)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nhận xét</label>
                        <textarea name="nhanXet" id="nhanXet" class="form-control" 
                                  rows="4" placeholder="Nhập nhận xét cho học sinh..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeGradeModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-success" onclick="submitGrade()">
                    <i class="fas fa-check"></i> Lưu điểm
                </button>
            </div>
        </div>
    </div>

    <script>
        function openGradeModal(submission) {
            document.getElementById('maBaiNop').value = submission.maBaiNop;
            document.getElementById('studentName').value = submission.hoTen;
            document.getElementById('submitDate').value = new Date(submission.ngayNop).toLocaleString('vi-VN');
            document.getElementById('noiDung').value = submission.noiDung || 'Không có nội dung văn bản';
            document.getElementById('diem').value = submission.diem || '';
            document.getElementById('nhanXet').value = submission.nhanXet || '';
            
            document.getElementById('gradeModal').classList.add('show');
        }

        function closeGradeModal() {
            document.getElementById('gradeModal').classList.remove('show');
            document.getElementById('gradeForm').reset();
        }

        function submitGrade() {
            const form = document.getElementById('gradeForm');
            
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);

            fetch('<?php echo url("controller/cHomeworkDetail.php?action=grade"); ?>', {
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
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Có lỗi xảy ra khi chấm điểm');
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
