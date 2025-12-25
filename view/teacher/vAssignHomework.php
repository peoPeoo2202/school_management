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

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-list-ul"></i> Danh sách bài tập</h2>
                </div>
                <form method="GET" action="" id="filterForm">
                    <div class="filter-section ">
                        <div class="filter-group">
                            <label>Lọc theo lớp</label>
                            <select name="maLop" id="filterClass" onchange="document.getElementById('filterForm').submit();">
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

                        <div class="filter-actions-button assign-homework-action">
                            <button type="button" class="btn btn-primary" onclick="openAddModal()">
                                <i class="fas fa-plus-circle"></i> Giao Bài Tập Mới
                            </button>
                        </div>
                    </div>
                </form><?php if ($homeworks->num_rows > 0): ?>
            </div>
            <div class="card-body">

                <?php
                            // Lưu tất cả bài tập vào mảng
                            $allHomeworks = [];
                            while ($hw = $homeworks->fetch_assoc()) {
                                $allHomeworks[] = $hw;
                            }
                            $totalHomeworks = count($allHomeworks);
                ?>
                <div class="content-grid grid-homework" id="homeworkGrid">
                    <?php foreach ($allHomeworks as $hw): ?>
                        <div class="assign-homework-card" data-homework-item>
                            <div class="assign-homework-card-header">
                                <h3><?= htmlspecialchars($hw['tenBaiTap']) ?></h3>
                            </div>
                            <div class="assign-homework-card-body">
                                <span>
                                    <strong>Lớp:</strong>
                                    <p> <?= htmlspecialchars($hw['tenLop']) ?></p>
                                </span>
                                <span>
                                    <strong>Môn học:</strong>
                                    <p> <?= htmlspecialchars($hw['tenMonHoc']) ?></p>
                                </span>
                                <span class="requirement">
                                    <strong>Yêu cầu:</strong>
                                    <p> <?= htmlspecialchars($hw['yeuCauBaiTap'] ?: 'Không có') ?></p>
                                </span>
                                <span>
                                    <strong>Hạn nộp:</strong>
                                    <p><?= date('d/m/Y H:i', strtotime($hw['thoiGianNop'])) ?></p>
                                </span>
                                <span>
                                    <strong>Nộp trễ:</strong>
                                    <p>
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
                                </span>
                                <span>
                                    <strong>Trạng thái:</strong>
                                    <p>
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
                                </span>
                                <span>
                                    <strong>Đã nộp:</strong>
                                    <p>
                                        <span class="status-assign-homework-badge <?= ($hw['soLuongNopBai'] > 0) ? 'completed' : 'pending' ?>">
                                            <?= $hw['soLuongNopBai'] ?? 0 ?> bài
                                        </span>
                                    </p>
                                </span>
                            </div>
                            <div class="assign-homework-card-footer">
                                <a href="<?= url('view/teacher/vHomeworkDetail.php?id=' . $hw['maBaiTap']) ?>"
                                    class="btn btn-assign-homework-info">
                                    <i class="fas fa-eye"></i> Chi tiết
                                </a>
                                <button class="btn btn-outlined" onclick="editHomework(<?= $hw['maBaiTap'] ?>)">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>
                                <button class="btn btn-delete-homework" onclick="deleteHomework(<?= $hw['maBaiTap'] ?>)">
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
                </div>
            <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- assign-homework-modal Thêm Bài Tập -->
    <div class="common-modal" id="add-assign-homework">
        <div class="assign-homework-modal-dialog">
            <div class="common-modal-header">
                <h5 class="common-modal-title">
                    <i class="fas fa-plus-circle"></i>
                    Giao Bài Tập Mới
                </h5>
                <button type="button" class="btn-close-modal" onclick="closeAddModal()"><i class="fa-solid fa-x"></i></button>
            </div>
            <div class="common-modal-body">
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
            <div class="common-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" id="submitBtn" onclick="submitHomework()">
                    <i class="fas fa-paper-plane"></i> Giao Bài Tập
                </button>
            </div>
        </div>
    </div>

    <!-- assign-homework-modal Sửa Bài Tập -->
    <!-- assign-homework-modal Sửa Bài Tập (ĐÃ SỬA) -->
    <div class="common-modal" id="edit-assign-homework">
        <div class="assign-homework-modal-dialog">
            <div class="common-modal-header">
                <h5 class="common-modal-title">
                    <i class="fas fa-edit"></i>
                    Sửa Bài Tập
                </h5>
                <button type="button" class="btn-close-modal" onclick="closeEditModal()">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>

            <div class="common-modal-body">
                <form id="editHomeworkForm" enctype="multipart/form-data">
                    <input type="hidden" name="maBaiTap" id="editMaBaiTap">

                    <div class="mb-3">
                        <label class="form-label">Tên bài tập<span>*</span></label>
                        <input type="text" name="tenBaiTap" id="editTenBaiTap"
                            class="form-control" placeholder="Nhập tên bài tập..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lớp<span>*</span></label>
                        <select name="maLop" id="editLop" class="form-select" required>
                            <option value="">-- Chọn lớp --</option>
                            <?php
                            $classes->data_seek(0);
                            while ($class = $classes->fetch_assoc()): ?>
                                <option value="<?= $class['maLop'] ?>">
                                    <?= htmlspecialchars($class['tenLop']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Yêu cầu bài tập</label>
                        <textarea name="yeuCauBaiTap" id="editYeuCau"
                            class="form-control" rows="4"
                            placeholder="Mô tả chi tiết yêu cầu bài tập..."></textarea>
                    </div>

                    <!-- FILE HIỆN TẠI -->
                    <div class="mb-3">
                        <label class="form-label">File hiện tại</label>
                        <div id="currentFile"
                            style="display:none; padding:10px; background:#e8f5e9; border-radius:8px; margin-bottom:10px;">
                            <i class="fas fa-file" style="color:#4caf50;"></i>
                            <span id="currentFileName"
                                style="color:#2e7d32; font-weight:600; margin-left:8px;"></span>
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
                            <input type="file" name="fileBaiTap" id="editFileBaiTap"
                                class="file-upload-input"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
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

                    <!-- THỜI GIAN -->
                    <div class="mb-3">
                        <label class="form-label">Thời gian nộp<span>*</span></label>
                        <input type="datetime-local" id="editThoiGianNop"
                            name="thoiGianNop" class="form-control" required>
                    </div>

                    <!-- CHECKBOX -->
                    <div class="mb-3">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="choPhepNopTre"
                                id="editChoPhepNopTre" value="1">
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

            <div class="common-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" id="updateBtn"
                    onclick="updateHomework()">
                    <i class="fas fa-save"></i> Cập Nhật
                </button>
            </div>
        </div>
    </div>

    <script>
        // assign-homework-modal functions
        function openAddModal() {
            document.getElementById('add-assign-homework').classList.add('show');
            document.getElementById('addHomeworkForm').reset();
        }

        function closeAddModal() {
            document.getElementById('add-assign-homework').classList.remove('show');
            document.getElementById('addHomeworkForm').reset();
            // Reset file upload display
            document.getElementById('fileSelected').classList.remove('show');
            document.getElementById('fileBaiTap').value = '';
        }

        // Edit assign-homework-modal functions
        function openEditModal() {
            document.getElementById('edit-assign-homework').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('edit-assign-homework').classList.remove('show');
            document.getElementById('editHomeworkForm').reset();
            document.getElementById('editFileSelected').classList.remove('show');
            document.getElementById('currentFile').style.display = 'none';
        }

        // File upload handling for ADD assign-homework-modal
        const fileUploadWrapper = document.getElementById('fileUploadWrapper');
        const fileInput = document.getElementById('fileBaiTap');
        const fileSelected = document.getElementById('fileSelected');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removeFileBtn = document.getElementById('removeFile');

        // Click to upload - fixed double-click issue
        fileUploadWrapper.addEventListener('click', (e) => {
            // Don't trigger if already clicking on the input itself or remove button
            if (e.target === fileInput || e.target.closest('.remove-file') || e.target.closest('input[type="file"]')) {
                return;
            }
            e.preventDefault();
            fileInput.click();
        });

        // File selected
        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
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

        // ========== FILE UPLOAD HANDLING FOR EDIT assign-homework-modal ==========
        const editFileUploadWrapper = document.getElementById('editFileUploadWrapper');
        const editFileInput = document.getElementById('editFileBaiTap');
        const editFileSelected = document.getElementById('editFileSelected');
        const editFileName = document.getElementById('editFileName');
        const editFileSize = document.getElementById('editFileSize');
        const editRemoveFileBtn = document.getElementById('editRemoveFile');

        // Click to upload - Edit assign-homework-modal - fixed double-click issue
        editFileUploadWrapper.addEventListener('click', (e) => {
            // Don't trigger if already clicking on the input itself or remove button
            if (e.target === editFileInput || e.target.closest('.remove-file') || e.target.closest('input[type="file"]')) {
                return;
            }
            e.preventDefault();
            editFileInput.click();
        });

        // File selected - Edit assign-homework-modal
        editFileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length > 0) {
                handleEditFile(e.target.files[0]);
            }
        });

        // Drag and drop - Edit assign-homework-modal
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

        // Remove file - Edit assign-homework-modal
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

            if (totalPages === 0) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'flex';
            let html = '<div class="pagination">';

            // Nút "Trước"
            if (currentPage > 1) {
                html += `<a href="javascript:changePage(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i> Trước</a>`;
            } else {
                html += `<span class="disabled"><i class="fas fa-chevron-left"></i> Trước</span>`;
            }

            // Số trang (hiển thị current ±2)
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                html += '<a href="javascript:changePage(1)">1</a>';
                if (startPage > 2) {
                    html += '<span>...</span>';
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    html += `<span class="current-page">${i}</span>`;
                } else {
                    html += `<a href="javascript:changePage(${i})">${i}</a>`;
                }
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += '<span>...</span>';
                }
                html += `<a href="javascript:changePage(${totalPages})">${totalPages}</a>`;
            }

            // Nút "Sau"
            if (currentPage < totalPages) {
                html += `<a href="javascript:changePage(${currentPage + 1})">
                    Sau <i class="fas fa-chevron-right"></i></a>`;
            } else {
                html += `<span class="disabled">Sau <i class="fas fa-chevron-right"></i></span>`;
            }

            html += '</div>';

            // Thêm thông tin trang
            const offset = (currentPage - 1) * itemsPerPage;
            html += `<div class="pagination-info">
                Hiển thị ${offset + 1} - ${Math.min(offset + itemsPerPage, totalItems)} 
                trong tổng số ${totalItems} bài tập</div>`;

            container.innerHTML = html;
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

        // Close assign-homework-modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('add-assign-homework');
            const editModal = document.getElementById('edit-assign-homework');

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