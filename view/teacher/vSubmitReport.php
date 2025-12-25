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

            <!-- Back button -->


            <!-- Alert error -->
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

                <!-- giống mẫu nghỉ phép: ul không cần class, để CSS chung áp -->
                <ul>
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

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-file-upload"></i> Thông tin báo cáo</h2>
                        <a href="cReport.php?action=index" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại danh sách báo cáo
                        </a>
                    </div>

                    <div class="form-section">
                        <div class="form-grid">

                            <div class="form-group">
                                <label for="tenBaoCao">Tên báo cáo <span style="color:#e74c3c">*</span></label>
                                <input type="text"
                                    id="tenBaoCao"
                                    name="tenBaoCao"
                                    class="form-control"
                                    required
                                    placeholder="Nhập tên báo cáo">
                            </div>

                            <div class="form-group">
                                <label for="loaiBaoCao">Loại báo cáo <span style="color:#e74c3c">*</span></label>
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
                                <label for="hocKy">Học kỳ <span style="color:#e74c3c">*</span></label>
                                <select id="hocKy" name="hocKy" class="form-control" required>
                                    <option value="1" selected>Học kỳ 1</option>
                                    <option value="2">Học kỳ 2</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="namHoc">Năm học <span style="color:#e74c3c">*</span></label>
                                <select id="namHoc" name="namHoc" class="form-control" required>
                                    <option value="2024-2025" selected>2024-2025</option>
                                    <option value="2023-2024">2023-2024</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="maLop">Lớp (tùy chọn)</label>
                                <select id="maLop" name="maLop" class="form-control">
                                    <option value="">Áp dụng cho tất cả lớp</option>
                                </select>
                            </div>

                            <!-- mô tả FULL hàng (đúng mẫu) -->
                            <div class="form-group" style="grid-column:1 / -1;">
                                <label for="moTa">Mô tả báo cáo</label>
                                <textarea id="moTa"
                                    name="moTa"
                                    class="form-control"
                                    placeholder="Mô tả chi tiết nội dung báo cáo, mục đích và những điểm quan trọng cần lưu ý..."></textarea>
                            </div>

                            <!-- upload FULL hàng (đúng mẫu) -->
                            <div class="form-group" style="grid-column:1 / -1;">
                                <label for="fileBaoCao">File báo cáo <span style="color:#e74c3c">*</span></label>

                                <!-- Upload giống mẫu em gửi -->
                                <div class="file-upload-wrapper">
                                    <input type="file"
                                        id="fileBaoCao"
                                        name="fileBaoCao"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx"
                                        required>

                                    <div style="font-size:42px;color:#5081BE;margin-bottom:8px;">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>

                                    <div style="color:#666;font-size:14px;">
                                        <strong>Chọn file hoặc kéo thả vào đây</strong>
                                    </div>

                                    <div style="margin-top:6px;font-size:12px;color:#999;">
                                        Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX (Tối đa 10MB)
                                    </div>
                                </div>

                                <div id="fileSelected" class="file-selected" style="display:none;">
                                    <i class="fas fa-check-circle"></i>
                                    <span id="fileName"></span>
                                    <span id="fileSize" style="margin-left:10px;color:#777;font-size:12px;"></span>
                                </div>
                                <br>
                                <div class="help-text">
                                    <i class="fas fa-lightbulb"></i>
                                    <strong>Gợi ý:</strong> Đặt tên file rõ ràng để dễ quản lý.
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Actions theo layout mẫu -->
                    <div class="form-actions">
                        <a href="cReport.php?action=index" class="btn btn-secondary">
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
        const form = document.getElementById('uploadForm');

        const fileInput = document.getElementById('fileBaoCao');
        const fileWrapper = document.querySelector('.file-upload-wrapper');

        const fileSelected = document.getElementById('fileSelected');
        const fileNameEl = document.getElementById('fileName');
        const fileSizeEl = document.getElementById('fileSize');

        const tenBaoCaoInput = document.getElementById('tenBaoCao');
        const loaiBaoCaoSelect = document.getElementById('loaiBaoCao');

        function resetFileUI() {
            fileSelected.style.display = 'none';
            fileNameEl.textContent = '';
            fileSizeEl.textContent = '';
        }

        function handleFileSelect(file) {
            if (!file) {
                resetFileUI();
                return;
            }

            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
            const ext = (file.name.split('.').pop() || '').toLowerCase();

            if (file.size > maxSize) {
                alert('File quá lớn! Kích thước tối đa là 10MB.');
                fileInput.value = '';
                resetFileUI();
                return;
            }

            if (!allowedExtensions.includes(ext)) {
                alert('Định dạng file không được hỗ trợ! Vui lòng chọn file PDF, DOC, DOCX, XLS hoặc XLSX.');
                fileInput.value = '';
                resetFileUI();
                return;
            }

            fileNameEl.textContent = file.name;
            fileSizeEl.textContent = `(${(file.size / 1024 / 1024).toFixed(2)} MB)`;
            fileSelected.style.display = 'flex';
        }

        // Click wrapper mở chọn file (đúng cảm giác mẫu)
        if (fileWrapper) {
            fileWrapper.addEventListener('click', (e) => {
                if (e.target !== fileInput) fileInput.click();
            });
        }

        // Change file
        fileInput.addEventListener('change', (e) => {
            handleFileSelect(e.target.files && e.target.files[0]);
        });

        // Drag & drop
        if (fileWrapper) {
            fileWrapper.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileWrapper.classList.add('dragover');
            });

            fileWrapper.addEventListener('dragleave', (e) => {
                e.preventDefault();
                fileWrapper.classList.remove('dragover');
            });

            fileWrapper.addEventListener('drop', (e) => {
                e.preventDefault();
                fileWrapper.classList.remove('dragover');

                const files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    fileInput.files = dt.files;
                    handleFileSelect(files[0]);
                }
            });
        }

        // Validate submit
        form.addEventListener('submit', function(e) {
            const requiredIds = ['tenBaoCao', 'loaiBaoCao', 'fileBaoCao'];
            let isValid = true;

            requiredIds.forEach((id) => {
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

        // Auto name
        loaiBaoCaoSelect.addEventListener('change', function() {
            const loai = this.value;
            const dateStr = new Date().toLocaleDateString('vi-VN');

            const map = {
                'hoc-tap': `Báo cáo kết quả học tập - ${dateStr}`,
                'chuyen-can': `Báo cáo chuyên cần - ${dateStr}`,
                'giang-day': `Báo cáo giảng dạy - ${dateStr}`,
                'tong-hop': `Báo cáo tổng hợp - ${dateStr}`,
                'danh-gia': `Báo cáo đánh giá - ${dateStr}`,
                'thong-ke-diem': `Thống kê điểm môn học - ${dateStr}`,
                'thong-ke-hoc-sinh': `Thống kê số liệu học sinh - ${dateStr}`,
                'khac': `Báo cáo khác - ${dateStr}`,
            };

            if (!tenBaoCaoInput.value.trim() && map[loai]) {
                tenBaoCaoInput.value = map[loai];
            }
        });
    </script>
</body>



</html>
</body>

</html>