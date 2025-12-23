<?php
require_once(__DIR__ . '/../../config.php');
// Lấy thông tin từ session (đã được kiểm tra ở controller)
$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nộp báo cáo - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo VIEW_URL . '/teacher/style.css'; ?>">
    <style>
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
            padding: 32px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 32px;
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
            border-color: #5081BE;
            box-shadow: 0 0 0 2px rgba(80, 129, 190, 0.1);
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
            border-color: #5081BE;
            background: #f8f9fa;
        }

        .file-upload.dragover {
            border-color: #5081BE;
            background: #e3f2fd;
        }

        .file-upload-icon {
            font-size: 48px;
            color: #5081BE;
            margin-bottom: 15px;
        }

        .file-upload-text {
            color: #666;
            margin-bottom: 10px;
        }

        .file-upload-button {
            background: #5081BE;
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

            <!-- Header theo layout mẫu -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-upload"></i> Nộp báo cáo</h2>
                    <p>Gửi báo cáo đến Ban giám hiệu</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <!-- Back button (đổi sang style nút cho đồng bộ) -->
            <a href="cReport.php?action=index" class="btn btn-secondary" style="width: fit-content;">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách báo cáo
            </a>

            <!-- Alert error (giữ logic GET error của em, nhưng bọc theo alert mẫu) -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error" style="margin-top:16px;">
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
                        case 'invalid_file_type':
                            echo '<i class="fas fa-exclamation-circle"></i> Loại file không được hỗ trợ! Chỉ chấp nhận PDF, DOC, DOCX, XLS, XLSX.';
                            break;
                        case 'file_too_large':
                            echo '<i class="fas fa-exclamation-circle"></i> File quá lớn! Kích thước tối đa là 10MB.';
                            break;
                        default:
                            echo '<i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra! Vui lòng thử lại.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Card: Lưu ý (đúng mẫu) -->
            <div class="card instructions" style="margin-top:16px;">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-info-circle"></i> Hướng dẫn nộp báo cáo</h2>
                </div>
                <ul class="instructions-list">
                    <li>Chọn loại báo cáo phù hợp với nội dung bạn muốn nộp</li>
                    <li>File upload hỗ trợ: PDF, DOC, DOCX, XLS, XLSX</li>
                    <li>Kích thước file tối đa: 10MB</li>
                    <li>Điền đầy đủ thông tin bắt buộc được đánh dấu (*)</li>
                    <li>Mô tả rõ nội dung để Ban giám hiệu dễ dàng xem xét</li>
                </ul>
            </div>

            <!-- FORM theo layout mẫu -->
            <form class="form-nghi-phep"
                  action="cReport.php?action=xu-ly-upload"
                  method="POST"
                  enctype="multipart/form-data"
                  id="uploadForm">

                <!-- Card: Thông tin báo cáo -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-file-upload"></i> Thông tin báo cáo</h2>
                    </div>

                    <div class="form-section">
                        <div class="form-grid">

                            <div class="form-group">
                                <label for="tenBaoCao">Tên báo cáo <span class="required">*</span></label>
                                <input type="text"
                                       id="tenBaoCao"
                                       name="tenBaoCao"
                                       class="form-control"
                                       required
                                       placeholder="Nhập tên báo cáo">
                            </div>

                            <div class="form-group">
                                <label for="loaiBaoCao">Loại báo cáo <span class="required">*</span></label>
                                <select id="loaiBaoCao" name="loaiBaoCao" class="form-control" required>
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
                                <select id="hocKy" name="hocKy" class="form-control" required>
                                    <option value="1" selected>Học kỳ 1</option>
                                    <option value="2">Học kỳ 2</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="namHoc">Năm học <span class="required">*</span></label>
                                <select id="namHoc" name="namHoc" class="form-control" required>
                                    <option value="2024-2025" selected>2024-2025</option>
                                    <option value="2023-2024">2023-2024</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="maLop">Lớp (tùy chọn)</label>
                                <select id="maLop" name="maLop" class="form-control">
                                    <option value="">Áp dụng cho tất cả lớp</option>
                                    <!-- Danh sách lớp sẽ load bằng JS -->
                                </select>
                            </div>

                            <!-- mô tả full hàng -->
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="moTa">Mô tả báo cáo</label>
                                <textarea id="moTa"
                                          name="moTa"
                                          class="form-control"
                                          placeholder="Mô tả chi tiết nội dung báo cáo, mục đích và những điểm quan trọng cần lưu ý..."></textarea>
                            </div>

                            <!-- upload full hàng -->
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="fileBaoCao">File báo cáo <span class="required">*</span></label>

                                <!-- Upload theo layout mẫu -->
                                <div class="file-upload-wrapper" id="fileUpload">
                                    <input type="file"
                                           id="fileBaoCao"
                                           name="fileBaoCao"
                                           accept=".pdf,.doc,.docx,.xls,.xlsx"
                                           required>
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="upload-title">
                                        <strong>Chọn file hoặc kéo thả vào đây</strong>
                                    </div>
                                    <div class="upload-sub">
                                        Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX (Tối đa 10MB)
                                    </div>
                                </div>

                                <div id="fileSelected" class="file-selected" style="display:none;">
                                    <i class="fas fa-check-circle"></i>
                                    <span id="fileName"></span>
                                    <span id="fileSize" style="margin-left:10px;color:#777;font-size:12px;"></span>
                                </div>

                                <div class="help-text">
                                    <i class="fas fa-lightbulb"></i>
                                    <strong>Gợi ý:</strong> Đặt tên file rõ ràng (loại báo cáo - ngày/tháng - lớp nếu có).
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Actions theo layout mẫu (đặt TRONG form, TRONG card) -->
                    <div class="form-actions">
                        <a href="../../controller/cReport.php?action=list" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy bỏ
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Nộp báo cáo
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>

    <script>
        // ===== File upload handling (đổi theo file-upload-wrapper mẫu) =====
        const fileInput = document.getElementById('fileBaoCao');
        const fileUpload = document.getElementById('fileUpload');
        const fileSelected = document.getElementById('fileSelected');
        const fileNameEl = document.getElementById('fileName');
        const fileSizeEl = document.getElementById('fileSize');

        fileInput.addEventListener('change', function(e) {
            handleFileSelect(e.target.files[0]);
        });

        // Drag & drop cho wrapper (giữ được drag/drop như code cũ)
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
            if (files && files.length > 0) {
                fileInput.files = files;
                handleFileSelect(files[0]);
            }
        });

        function handleFileSelect(file) {
            if (!file) {
                fileSelected.style.display = 'none';
                return;
            }

            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedExtensions = ['pdf','doc','docx','xls','xlsx'];
            const ext = file.name.split('.').pop().toLowerCase();

            if (file.size > maxSize) {
                alert('File quá lớn! Kích thước tối đa là 10MB.');
                fileInput.value = '';
                fileSelected.style.display = 'none';
                return;
            }

            if (!allowedExtensions.includes(ext)) {
                alert('Định dạng file không được hỗ trợ! Vui lòng chọn file PDF, DOC, DOCX, XLS hoặc XLSX.');
                fileInput.value = '';
                fileSelected.style.display = 'none';
                return;
            }

            fileNameEl.textContent = file.name;
            fileSizeEl.textContent = `(${(file.size / 1024 / 1024).toFixed(2)} MB)`;
            fileSelected.style.display = 'flex';
        }

        // ===== Validation (giữ logic cũ nhưng sửa border theo class form-control) =====
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const requiredFields = ['tenBaoCao', 'loaiBaoCao', 'fileBaoCao'];
            let isValid = true;

            requiredFields.forEach(function(id) {
                const field = document.getElementById(id);
                const value = (field.value || '').trim();

                if (!value) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Vui lòng điền đầy đủ các trường bắt buộc!');
            }
        });

        // ===== Auto name theo loại (giữ như cũ) =====
        document.getElementById('loaiBaoCao').addEventListener('change', function() {
            const loai = this.value;
            const currentDate = new Date();
            const dateStr = currentDate.toLocaleDateString('vi-VN');

            let tenBaoCao = '';
            switch (loai) {
                case 'hoc-tap': tenBaoCao = `Báo cáo kết quả học tập - ${dateStr}`; break;
                case 'chuyen-can': tenBaoCao = `Báo cáo chuyên cần - ${dateStr}`; break;
                case 'giang-day': tenBaoCao = `Báo cáo giảng dạy - ${dateStr}`; break;
                case 'tong-hop': tenBaoCao = `Báo cáo tổng hợp - ${dateStr}`; break;
                case 'danh-gia': tenBaoCao = `Báo cáo đánh giá - ${dateStr}`; break;
                case 'thong-ke-diem': tenBaoCao = `Thống kê điểm môn học - ${dateStr}`; break;
                case 'thong-ke-hoc-sinh': tenBaoCao = `Thống kê số liệu học sinh - ${dateStr}`; break;
                case 'khac': tenBaoCao = `Báo cáo khác - ${dateStr}`; break;
            }

            const tenInput = document.getElementById('tenBaoCao');
            if (tenBaoCao && !tenInput.value.trim()) {
                tenInput.value = tenBaoCao;
            }
        });
    </script>
</body>


</html>
</body>

</html>