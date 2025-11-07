<?php
// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nộp báo cáo - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .content-area {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            overflow-y: auto;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header h1 i {
            color: #28a745;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #545b62;
        }

        .upload-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .file-upload {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }

        .file-upload.dragover {
            border-color: #667eea;
            background: #e3f2fd;
        }

        .file-upload-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }

        .file-upload-text {
            color: #666;
            margin-bottom: 10px;
        }

        .file-upload-button {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .file-upload-button:hover {
            background: #5a6fd8;
        }

        .file-info {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
            display: none;
        }

        .file-info.show {
            display: block;
        }

        .file-name {
            font-weight: 500;
            color: #155724;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-size {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }

        .submit-section {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #28a745;
            color: white;
        }

        .btn-primary:hover {
            background: #218838;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .instructions h3 {
            color: #856404;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .instructions ul {
            color: #856404;
            margin-left: 20px;
        }

        .instructions li {
            margin-bottom: 8px;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .required {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .submit-section {
                flex-direction: column;
            }

            .file-upload {
                padding: 20px;
            }

            .file-upload-icon {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>
        
        <div class="content-area">
            <div class="container">
                <div class="header">
                    <h1>
                        <i class="fas fa-upload"></i>
                        Nộp báo cáo
                    </h1>
                    <div class="user-info">
                        <span>
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($hoTen); ?>
                        </span>
                    </div>
                </div>

                <a href="../../controller/cReport.php?action=list" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại danh sách báo cáo
                </a>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <?php
                        switch ($_GET['error']) {
                            case 'upload_failed':
                                echo '<i class="fas fa-exclamation-circle"></i> Lỗi upload file! Vui lòng thử lại.';
                                break;
                            case 'save_failed':
                                echo '<i class="fas fa-exclamation-circle"></i> Lỗi lưu báo cáo! Vui lòng thử lại.';
                                break;
                            case 'no_file':
                                echo '<i class="fas fa-exclamation-circle"></i> Vui lòng chọn file báo cáo để upload!';
                                break;
                            default:
                                echo '<i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra! Vui lòng thử lại.';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <div class="instructions">
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        Hướng dẫn nộp báo cáo
                    </h3>
                    <ul>
                        <li>Chọn loại báo cáo phù hợp với nội dung bạn muốn nộp</li>
                        <li>File upload hỗ trợ các định dạng: PDF, DOC, DOCX, XLS, XLSX</li>
                        <li>Kích thước file tối đa: 10MB</li>
                        <li>Điền đầy đủ thông tin bắt buộc được đánh dấu (*)</li>
                        <li>Mô tả chi tiết nội dung báo cáo để Ban Giám Hiệu dễ dàng xem xét</li>
                    </ul>
                </div>

                <div class="upload-section">
                    <div class="section-title">
                        <i class="fas fa-file-upload"></i>
                        Thông tin báo cáo
                    </div>

                    <form action="../../controller/cReport.php?action=submit" method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="form-group">
                            <label for="tenBaoCao">Tên báo cáo <span class="required">*</span></label>
                            <input type="text" id="tenBaoCao" name="tenBaoCao" required 
                                   placeholder="Nhập tên báo cáo">
                        </div>

                        <div class="form-group">
                            <label for="loaiBaoCao">Loại báo cáo <span class="required">*</span></label>
                            <select id="loaiBaoCao" name="loaiBaoCao" required>
                                <option value="">Chọn loại báo cáo</option>
                                <option value="hoc-tap">Báo cáo kết quả học tập</option>
                                <option value="chuyen-can">Báo cáo chuyên cần</option>
                                <option value="giang-day">Báo cáo giảng dạy</option>
                                <option value="tong-hop">Báo cáo tổng hợp</option>
                                <option value="danh-gia">Báo cáo kết quả đánh giá</option>
                                <option value="thong-ke-diem">Thống kê điểm môn học</option>
                                <option value="thong-ke-hoc-sinh">Thống kê số liệu học sinh</option>
                                <option value="khac">Khác</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="hocKy">Học kỳ <span class="required">*</span></label>
                            <select id="hocKy" name="hocKy" required>
                                <option value="1" selected>Học kỳ 1</option>
                                <option value="2">Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="namHoc">Năm học <span class="required">*</span></label>
                            <select id="namHoc" name="namHoc" required>
                                <option value="2024-2025" selected>2024-2025</option>
                                <option value="2023-2024">2023-2024</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="maLop">Lớp (tùy chọn)</label>
                            <select id="maLop" name="maLop">
                                <option value="">Áp dụng cho tất cả lớp</option>
                                <!-- Danh sách lớp sẽ được load bằng JavaScript -->
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="moTa">Mô tả báo cáo</label>
                            <textarea id="moTa" name="moTa" 
                                      placeholder="Mô tả chi tiết nội dung báo cáo, mục đích và những điểm quan trọng cần lưu ý..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="fileBaoCao">File báo cáo <span class="required">*</span></label>
                            <div class="file-upload" id="fileUpload">
                                <div class="file-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="file-upload-text">
                                    Kéo thả file vào đây hoặc click để chọn file
                                </div>
                                <button type="button" class="file-upload-button" onclick="document.getElementById('fileBaoCao').click()">
                                    <i class="fas fa-folder-open"></i>
                                    Chọn file
                                </button>
                                <input type="file" id="fileBaoCao" name="fileBaoCao" 
                                       accept=".pdf,.doc,.docx,.xls,.xlsx" 
                                       style="display: none;" required>
                            </div>
                            <div class="file-info" id="fileInfo">
                                <div class="file-name" id="fileName"></div>
                                <div class="file-size" id="fileSize"></div>
                            </div>
                        </div>

                        <div class="submit-section">
                            <a href="../../controller/cReport.php?action=list" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Hủy bỏ
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Nộp báo cáo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // File upload handling
        const fileInput = document.getElementById('fileBaoCao');
        const fileUpload = document.getElementById('fileUpload');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');

        // File input change event
        fileInput.addEventListener('change', function(e) {
            handleFileSelect(e.target.files[0]);
        });

        // Drag and drop events
        fileUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUpload.classList.add('dragover');
        });

        fileUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
        });

        fileUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(files[0]);
            }
        });

        function handleFileSelect(file) {
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                const allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ];

                if (file.size > maxSize) {
                    alert('File quá lớn! Kích thước tối đa là 10MB.');
                    fileInput.value = '';
                    return;
                }

                if (!allowedTypes.includes(file.type)) {
                    alert('Định dạng file không được hỗ trợ! Vui lòng chọn file PDF, DOC, DOCX, XLS hoặc XLSX.');
                    fileInput.value = '';
                    return;
                }

                // Display file info
                fileName.innerHTML = `<i class="fas fa-file"></i> ${file.name}`;
                fileSize.textContent = `Kích thước: ${(file.size / 1024 / 1024).toFixed(2)} MB`;
                fileInfo.classList.add('show');
            }
        }

        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const requiredFields = ['tenBaoCao', 'loaiBaoCao', 'hocKy', 'namHoc', 'fileBaoCao'];
            let isValid = true;

            requiredFields.forEach(function(fieldName) {
                const field = document.getElementById(fieldName);
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#ddd';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Vui lòng điền đầy đủ các trường bắt buộc!');
            }
        });

        // Auto generate report name based on type and semester
        document.getElementById('loaiBaoCao').addEventListener('change', function() {
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            const loai = this.value;
            
            let tenBaoCao = '';
            switch(loai) {
                case 'hoc-tap':
                    tenBaoCao = `Báo cáo kết quả học tập HK${hocKy} ${namHoc}`;
                    break;
                case 'chuyen-can':
                    tenBaoCao = `Báo cáo chuyên cần HK${hocKy} ${namHoc}`;
                    break;
                case 'giang-day':
                    tenBaoCao = `Báo cáo giảng dạy HK${hocKy} ${namHoc}`;
                    break;
                case 'tong-hop':
                    tenBaoCao = `Báo cáo tổng hợp HK${hocKy} ${namHoc}`;
                    break;
                case 'danh-gia':
                    tenBaoCao = `Báo cáo đánh giá HK${hocKy} ${namHoc}`;
                    break;
                case 'thong-ke-diem':
                    tenBaoCao = `Thống kê điểm môn học HK${hocKy} ${namHoc}`;
                    break;
                case 'thong-ke-hoc-sinh':
                    tenBaoCao = `Thống kê số liệu học sinh HK${hocKy} ${namHoc}`;
                    break;
            }
            
            if (tenBaoCao && !document.getElementById('tenBaoCao').value.trim()) {
                document.getElementById('tenBaoCao').value = tenBaoCao;
            }
        });
    </script>
</body>
</html>
</body>
</html>
