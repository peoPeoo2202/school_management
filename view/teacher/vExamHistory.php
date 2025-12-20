<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

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

require_once(__DIR__ . '/../../controller/cSubmitExam.php');

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
$maGV = $_SESSION['maGV'] ?? null;

if (!$maGV) {
    die("Không tìm thấy thông tin giáo viên!");
}

$controller = new cSubmitExam();

// Lấy danh sách môn học của giáo viên
$subjects = $controller->getTeacherSubjects($maGV);

// Lấy danh sách năm học
$schoolYears = $controller->getSchoolYears();

// Xử lý bộ lọc - BỎ môn học
$filters = [
    'hocKy' => isset($_GET['hocKy']) ? intval($_GET['hocKy']) : null,
    'namHoc' => isset($_GET['namHoc']) ? trim($_GET['namHoc']) : null,
    'trangThai' => isset($_GET['trangThai']) ? trim($_GET['trangThai']) : null
];

// Lấy danh sách đề thi
$exams = $controller->getTeacherExams($maGV, $filters);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đề thi - Hệ thống Quản lý</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
   
        .header h1 {
            font-size: 26px;
            font-weight: 600;
            color: #5081BE;
            margin: 0;
        }

        .breadcrumb {
            display: flex;
            gap: 10px;
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }

        .breadcrumb a {
            color: #5081BE;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .list-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #5081BE;
        }

        .filter-actions .btn {
            width: 100%;
            padding: 0;
            height: 41px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            box-sizing: border-box;
        }

        .btn-info {
            background: #2196f3;
            color: white;
        }

        .btn-info:hover {
            background: #1976d2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        }

        .btn-sm {
            padding: 6px 15px;
            font-size: 12px;
        }

        .btn-warning {
            background: #ff9800;
            color: white;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9f9f9;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            font-size: 13px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .empty-state p {
            font-size: 16px;
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
            font-size: 14px;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background: #5a6268;
            transform: translateX(-5px);
        }

        .exam-title-link {
            color: #5081BE;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }

        .exam-title-link:hover {
            color: #2d5a8c;
            text-decoration: underline;
        }

        .exam-title-text {
            color: #333;
            font-weight: 600;
        }

        /* Modal sửa đề thi */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #fefefe;
            margin: 50px auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s;
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
            background: linear-gradient(135deg, #5081BE 0%, #2d5a8c 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 20px;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group label .required {
            color: #e74c3c;
            margin-left: 3px;
        }

        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 3px rgba(80, 129, 190, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .current-file-info {
            padding: 10px 15px;
            background: #f0f7ff;
            border-left: 4px solid #5081BE;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .current-file-info strong {
            color: #5081BE;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: block;
            padding: 12px 15px;
            background: #f9f9f9;
            border: 2px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-label:hover {
            background: #f0f7ff;
            border-color: #5081BE;
        }

        .file-input-label i {
            margin-right: 8px;
            color: #5081BE;
        }

        .modal-footer {
            padding: 20px 25px;
            background: #f9f9f9;
            border-radius: 0 0 12px 12px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-footer .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .modal-footer .btn-primary {
            background: #5081BE;
            color: white;
        }

        .modal-footer .btn-primary:hover {
            background: #2d5a8c;
        }

        .modal-footer .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .modal-footer .btn-secondary:hover {
            background: #5a6268;
        }

        @media (max-width: 768px) {
            .main-wrapper {
                flex-direction: column;
            }

            .content-area {
                padding: 15px;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                flex-direction: row;
            }
        }

        @media (max-width: 1024px) and (min-width: 769px) {
            .filter-form {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-actions {
                grid-column: 1 / -1;
                flex-direction: row;
                justify-content: flex-start;
            }

            .filter-actions .btn {
                width: auto;
                min-width: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include('../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">
            <!-- Page Header -->
            <div class="header">
                <h1>Lịch sử đề thi đã gửi</h1>
            </div>

            <a href="vSubmitExam.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>

            <!-- List Section -->
            <div class="list-section">
                <h2 class="section-title"><i class="fas fa-list"></i> Danh sách đề thi</h2>

                <!-- Filter -->
                <div class="filter-section">
                    <form id="filterForm" method="GET">
                        <div class="filter-form">
                            <div class="form-group">
                                <label for="filter_hocKy">Học kỳ</label>
                                <select id="filter_hocKy" name="hocKy">
                                    <option value="">-- Tất cả --</option>
                                    <option value="1" <?php echo ($filters['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                    <option value="2" <?php echo ($filters['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="filter_namHoc">Năm học</label>
                                <select id="filter_namHoc" name="namHoc">
                                    <option value="">-- Tất cả --</option>
                                    <?php foreach ($schoolYears as $year): ?>
                                        <option value="<?php echo $year; ?>"
                                                <?php echo ($filters['namHoc'] == $year) ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="filter_trangThai">Trạng thái</label>
                                <select id="filter_trangThai" name="trangThai">
                                    <option value="">-- Tất cả --</option>
                                    <option value="Chuaduyet" <?php echo ($filters['trangThai'] == 'Chuaduyet') ? 'selected' : ''; ?>>Chờ duyệt</option>
                                    <option value="Daduyet" <?php echo ($filters['trangThai'] == 'Daduyet') ? 'selected' : ''; ?>>Đã duyệt</option>
                                    <option value="Dachon" <?php echo ($filters['trangThai'] == 'Dachon') ? 'selected' : ''; ?>>Đã chọn</option>
                                    <option value="Tuchoi" <?php echo ($filters['trangThai'] == 'Tuchoi') ? 'selected' : ''; ?>>Từ chối</option>
                                </select>
                            </div>

                            <div class="filter-actions">
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="fas fa-filter"></i> Lọc
                                </button>
                                <a href="vExamHistory.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <?php if (count($exams) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên đề thi</th>
                                    <th>HK/Năm học</th>
                                    <th>Tải File</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày gửi</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $stt = 1;
                                foreach ($exams as $exam): 
                                    $badgeClass = 'badge-warning';
                                    if ($exam['trangThai'] == 'Daduyet') {
                                        $badgeClass = 'badge-success';
                                    } elseif ($exam['trangThai'] == 'Dachon') {
                                        $badgeClass = 'badge-info';
                                    } elseif ($exam['trangThai'] == 'Tuchoi') {
                                        $badgeClass = 'badge-danger';
                                    }
                                    
                                    // Tạo link download
                                    $downloadLink = $exam['tenFile'] ? '../../controller/cSubmitExam.php?action=download&id=' . $exam['maDeThi'] : '#';
                                    
                                    // Tạo link để view file trực tiếp
                                    $viewLink = $exam['tenFile'] ? '../../controller/cSubmitExam.php?action=view&id=' . $exam['maDeThi'] : '#';
                                ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td>
                                        <?php if ($exam['tenFile']): ?>
                                            <a href="<?php echo $viewLink; ?>" 
                                               target="_blank"
                                               class="exam-title-link"
                                               title="Click để xem file">
                                                <?php echo htmlspecialchars($exam['tenDeThi']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="exam-title-text"><?php echo htmlspecialchars($exam['tenDeThi']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($exam['moTa']): ?>
                                            <br><small style="color: #999;"><?php echo htmlspecialchars(substr($exam['moTa'], 0, 50)) . (strlen($exam['moTa']) > 50 ? '...' : ''); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>HK<?php echo $exam['hocKy']; ?> / <?php echo $exam['namHoc']; ?></td>
                                    <td>
                                        <?php if ($exam['tenFile']): ?>
                                            <a href="javascript:void(0);" 
                                               onclick="downloadFile('<?php echo addslashes($downloadLink); ?>', '<?php echo addslashes($exam['tenDeThi'] . '.' . pathinfo($exam['tenFile'], PATHINFO_EXTENSION)); ?>')"
                                               class="btn btn-info btn-sm"
                                               title="Tải file">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #999;">Không có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo $exam['trangThaiText']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($exam['ngayTao'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($exam['trangThai'] == 'Chuaduyet'): ?>
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick='editExam(<?php echo json_encode($exam, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                                        title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="deleteExam(<?php echo $exam['maDeThi']; ?>)"
                                                        title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 12px;">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Chưa có đề thi nào được gửi</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal sửa đề thi -->
    <div id="editExamModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Sửa đề thi</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editExamForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="edit_maDeThi" name="maDeThi">
                    
                    <div class="form-group">
                        <label for="edit_tenDeThi">Tên đề thi <span class="required">*</span></label>
                        <input type="text" id="edit_tenDeThi" name="tenDeThi" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_loaiDeThi">Loại đề thi <span class="required">*</span></label>
                        <select id="edit_loaiDeThi" name="loaiDeThi" required>
                            <option value="de-thi-giua-ky">Đề thi giữa kỳ</option>
                            <option value="de-thi-cuoi-ky">Đề thi cuối kỳ</option>
                            <option value="de-thi-thu">Đề thi thử</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_hocKy">Học kỳ <span class="required">*</span></label>
                        <select id="edit_hocKy" name="hocKy" required>
                            <option value="1">Học kỳ 1</option>
                            <option value="2">Học kỳ 2</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_namHoc">Năm học <span class="required">*</span></label>
                        <select id="edit_namHoc" name="namHoc" required>
                            <?php foreach ($schoolYears as $year): ?>
                                <option value="<?php echo $year; ?>">
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_moTa">Mô tả</label>
                        <textarea id="edit_moTa" name="moTa"></textarea>
                    </div>

                    <div class="form-group">
                        <label>File hiện tại</label>
                        <div id="currentFileInfo" class="current-file-info"></div>
                        
                        <label>Thay đổi file (không bắt buộc)</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="edit_file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx">
                            <label for="edit_file" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span id="fileNameDisplay">Chọn file mới (PDF, DOC, DOCX, XLS, XLSX)</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Hàm download file tự động
        function downloadFile(url, filename) {
            // Tạo một thẻ a ẩn
            const link = document.createElement('a');
            link.href = url;
            link.download = filename || 'download';
            link.style.display = 'none';
            
            // Thêm vào DOM, click, và xóa
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Hiển thị tên file khi chọn
        document.getElementById('edit_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Chọn file mới (PDF, DOC, DOCX, XLS, XLSX)';
            document.getElementById('fileNameDisplay').textContent = fileName;
        });

        // Mở modal sửa đề thi
        function editExam(exam) {
            document.getElementById('edit_maDeThi').value = exam.maDeThi;
            document.getElementById('edit_tenDeThi').value = exam.tenDeThi;
            document.getElementById('edit_loaiDeThi').value = exam.loaiDeThi || 'de-thi';
            document.getElementById('edit_hocKy').value = exam.hocKy;
            document.getElementById('edit_namHoc').value = exam.namHoc;
            
            // Xử lý mô tả - không hiển thị "0" mà để placeholder
            const moTa = exam.moTa && exam.moTa !== '0' ? exam.moTa : '';
            document.getElementById('edit_moTa').value = moTa;
            
            // Hiển thị thông tin file hiện tại
            const fileInfo = document.getElementById('currentFileInfo');
            if (exam.tenFile) {
                fileInfo.innerHTML = '<strong>File:</strong> ' + exam.tenFile;
            } else {
                fileInfo.innerHTML = '<strong>Chưa có file</strong>';
            }
            
            // Reset file input
            document.getElementById('edit_file').value = '';
            document.getElementById('fileNameDisplay').textContent = 'Chọn file mới (PDF, DOC, DOCX, XLS, XLSX)';
            
            // Hiển thị modal
            document.getElementById('editExamModal').style.display = 'block';
        }

        // Đóng modal
        function closeEditModal() {
            document.getElementById('editExamModal').style.display = 'none';
        }

        // Đóng modal khi click bên ngoài
        window.onclick = function(event) {
            const modal = document.getElementById('editExamModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        // Xử lý submit form sửa
        document.getElementById('editExamForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const maDeThi = document.getElementById('edit_maDeThi').value;
            
            try {
                const response = await fetch('../../controller/cSubmitExam.php?action=update&id=' + maDeThi, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    closeEditModal();
                    window.location.reload();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                alert('Có lỗi xảy ra: ' + error.message);
            }
        });

        // Hàm xóa đề thi
        async function deleteExam(maDeThi) {
            if (!confirm('Bạn có chắc chắn muốn xóa đề thi này?')) {
                return;
            }
            
            try {
                const response = await fetch('../../controller/cSubmitExam.php?action=delete&id=' + maDeThi, {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    window.location.reload();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                alert('Có lỗi xảy ra: ' + error.message);
            }
        }
    </script>
</body>
</html>
