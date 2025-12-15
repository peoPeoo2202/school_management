<?php
// Bật hiển thị lỗi để debug
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

// Xử lý bộ lọc
$filters = [
    'maMonHoc' => isset($_GET['maMonHoc']) ? intval($_GET['maMonHoc']) : null,
    'hocKy' => isset($_GET['hocKy']) ? intval($_GET['hocKy']) : null,
    'namHoc' => isset($_GET['namHoc']) ? trim($_GET['namHoc']) : null,
    'trangThai' => isset($_GET['trangThai']) ? trim($_GET['trangThai']) : null
];

// Lấy danh sách đề thi
$exams = $controller->getTeacherExams($maGV, $filters);

// Lấy năm học hiện tại
$currentYear = date('Y');
$currentMonth = date('n');
$schoolYear = $currentMonth >= 9 ? "$currentYear-" . ($currentYear + 1) : ($currentYear - 1) . "-$currentYear";

// Thiết lập thông tin trang
$pageTitle = "Gửi đề thi";
$pageIcon = "fas fa-file-upload";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Hệ thống Quản lý</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .main-wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .content-area {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
        }

        .header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 32px;
        }

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

        .content-wrapper {
            max-width: 100%;
            margin: 0 auto;
        }

        .form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #5081BE;
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
            min-height: 100px;
        }

        .file-upload-wrapper {
            position: relative;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-upload-wrapper:hover {
            border-color: #5081BE;
            background: #f0f7ff;
        }

        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-icon {
            font-size: 48px;
            color: #5081BE;
            margin-bottom: 10px;
        }

        .file-upload-text {
            color: #666;
            font-size: 14px;
        }

        .file-upload-info {
            margin-top: 10px;
            font-size: 12px;
            color: #999;
        }

        .file-selected {
            margin-top: 15px;
            padding: 10px 15px;
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 6px;
            color: #2e7d32;
            font-size: 14px;
            display: none;
        }

        .file-selected i {
            margin-right: 8px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: #5081BE;
            color: white;
        }

        .btn-primary:hover {
            background: #3d6a9e;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(80, 129, 190, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .btn-success {
            background: #4caf50;
            color: white;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-warning {
            background: #ff9800;
            color: white;
        }

        .btn-info {
            background: #2196f3;
            color: white;
        }

        .btn-sm {
            padding: 6px 15px;
            font-size: 12px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        /* Filter Section */
        .filter-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-form .form-group {
            margin-bottom: 0;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        /* Table */
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

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            animation: slideDown 0.3s ease;
            font-weight: 500;
        }

        .alert.show {
            display: block;
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

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading */
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #5081BE;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-wrapper {
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .main-wrapper {
                flex-direction: column;
            }

            .content-area {
                padding: 15px;
            }

            .header, .form-section {
                padding: 20px;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .history-button-wrapper {
                text-align: center;
                margin-bottom: 20px;
            }

            .btn-history {
                width: 100%;
                justify-content: center;
            }

            .form-section {
                margin-top: 0;
            }
        }

        .history-button-wrapper {
            text-align: right;
            margin-bottom: 20px;
        }

        .btn-history {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-history:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-history i {
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include('../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">
            <!-- Page Header -->
            <div class="header">
                <h1><i class="<?php echo $pageIcon; ?>"></i> <?php echo $pageTitle; ?></h1>
            </div>

            <!-- Content -->
            <div class="content-wrapper">
                <!-- History Button -->
                <div class="history-button-wrapper">
                    <a href="vExamHistory.php" class="btn-history">
                        <i class="fas fa-history"></i>
                        <span>Lịch sử đề thi</span>
                    </a>
                </div>

                <!-- Form Section -->
                <div class="form-section">
                    <h2 class="section-title"><i class="fas fa-plus-circle"></i> Thêm đề thi mới</h2>
                    
                    <div id="alert" class="alert"></div>

                    <form id="examForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="tenDeThi">Tên đề thi <span class="required">*</span></label>
                            <input type="text" id="tenDeThi" name="tenDeThi" required 
                                   placeholder="Ví dụ: Đề thi giữa kỳ 1 - Toán 10">
                        </div>

                        <div class="form-group">
                            <label for="loaiDeThi">Loại đề thi <span class="required">*</span></label>
                            <select id="loaiDeThi" name="loaiDeThi" required>
                                <option value="de-thi-giua-ky">Đề thi giữa kỳ</option>
                                <option value="de-thi-cuoi-ky">Đề thi cuối kỳ</option>
                                <option value="de-thi-thu">Đề thi thử</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="hocKy">Học kỳ <span class="required">*</span></label>
                            <select id="hocKy" name="hocKy" required>
                                <option value="">-- Chọn học kỳ --</option>
                                <option value="1">Học kỳ 1</option>
                                <option value="2">Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="namHoc">Năm học <span class="required">*</span></label>
                            <select id="namHoc" name="namHoc" required>
                                <option value="">-- Chọn năm học --</option>
                                <?php foreach ($schoolYears as $year): ?>
                                    <option value="<?php echo $year; ?>" 
                                        <?php echo ($year == $schoolYear) ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="moTa">Mô tả</label>
                            <textarea id="moTa" name="moTa" 
                                      placeholder="Nhập mô tả chi tiết về đề thi..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="file">File đề thi <span class="required">*</span></label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="file" name="file" 
                                       accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                                <div class="file-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="file-upload-text">
                                    <strong>Chọn file hoặc kéo thả vào đây</strong>
                                </div>
                                <div class="file-upload-info">
                                    Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX (Tối đa 10MB)
                                </div>
                            </div>
                            <div id="fileSelected" class="file-selected">
                                <i class="fas fa-check-circle"></i>
                                <span id="fileName"></span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Gửi đề thi
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Làm mới
                            </button>
                        </div>
                    </form>

                    <div id="loading" class="loading">
                        <div class="spinner"></div>
                        <p>Đang xử lý...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Xử lý hiển thị file đã chọn
        document.getElementById('file').addEventListener('change', function(e) {
            const fileSelected = document.getElementById('fileSelected');
            const fileName = document.getElementById('fileName');
            
            if (this.files.length > 0) {
                const file = this.files[0];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                // Kiểm tra kích thước file
                if (file.size > maxSize) {
                    showAlert('File quá lớn! Kích thước tối đa là 10MB.', 'error');
                    this.value = '';
                    fileSelected.style.display = 'none';
                    return;
                }
                
                // Kiểm tra định dạng file
                const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedExtensions.includes(fileExtension)) {
                    showAlert('Định dạng file không hợp lệ! Chỉ chấp nhận: PDF, DOC, DOCX, XLS, XLSX', 'error');
                    this.value = '';
                    fileSelected.style.display = 'none';
                    return;
                }
                
                fileName.textContent = file.name;
                fileSelected.style.display = 'block';
            } else {
                fileSelected.style.display = 'none';
            }
        });

        // Hàm hiển thị thông báo
        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = 'alert alert-' + type + ' show';
            alert.textContent = message;
            alert.style.display = 'block';
            
            // Cuộn lên đầu trang để thấy thông báo
            alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Xử lý submit form
        document.getElementById('examForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Kiểm tra file đã được chọn chưa
            const fileInput = document.getElementById('file');
            if (!fileInput.files || fileInput.files.length === 0) {
                showAlert('Vui lòng chọn file đề thi!', 'error');
                fileInput.focus();
                return;
            }
            
            const formData = new FormData(this);
            const loading = document.getElementById('loading');
            const alert = document.getElementById('alert');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Disable nút submit
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
            
            // Hiển thị loading
            loading.classList.add('active');
            alert.style.display = 'none';
            
            try {
                const response = await fetch('../../controller/cSubmitExam.php?action=add', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                // Ẩn loading
                loading.classList.remove('active');
                
                // Enable lại nút submit
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi đề thi';
                
                // Hiển thị thông báo
                showAlert(result.message, result.success ? 'success' : 'error');
                
                if (result.success) {
                    // Reset form sau 3 giây
                    setTimeout(() => {
                        this.reset();
                        document.getElementById('fileSelected').style.display = 'none';
                        
                        // Reload trang sau thêm 1 giây nữa
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }, 3000);
                }
            } catch (error) {
                loading.classList.remove('active');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi đề thi';
                showAlert('Có lỗi xảy ra: ' + error.message, 'error');
            }
        });

        // Xử lý reset form
        document.getElementById('examForm').addEventListener('reset', function() {
            document.getElementById('fileSelected').style.display = 'none';
            document.getElementById('alert').style.display = 'none';
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

        // Hàm sửa đề thi (chuyển đến trang edit hoặc mở modal)
        function editExam(maDeThi) {
            alert('Chức năng sửa đề thi đang được phát triển.\nMã đề thi: ' + maDeThi);
            // TODO: Implement edit functionality
        }
    </script>
</body>
</html>
