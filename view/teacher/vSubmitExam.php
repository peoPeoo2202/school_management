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
</head>

<body>
    <div class="main-wrapper">
        <?php include('../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">

            <!-- Header giống mẫu -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="<?php echo $pageIcon; ?>"></i> <?php echo $pageTitle; ?></h2>
                    <p>Quản lý và gửi đề thi cho học sinh</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-plus-circle"></i> Thêm đề thi mới</h2>
                        <a href="vExamHistory.php" class="btn btn-primary">
                            <i class="fas fa-history"></i>
                            <span>Lịch sử đề thi</span>
                        </a>
                    </div>
                    <div id="alert" class="alert"></div>

                    <form id="examForm" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="tenDeThi">Tên đề thi <span style="color:#e74c3c">*</span></label>
                                <input type="text" id="tenDeThi" name="tenDeThi" required
                                    placeholder="Ví dụ: Đề thi giữa kỳ 1 - Toán 6">
                            </div>

                            <div class="form-group">
                                <label for="loaiDeThi">Loại đề thi <span style="color:#e74c3c">*</span></label>
                                <select id="loaiDeThi" name="loaiDeThi" required>
                                    <option value="de-thi-giua-ky">Đề thi giữa kỳ</option>
                                    <option value="de-thi-cuoi-ky">Đề thi cuối kỳ</option>
                                    <option value="de-thi-thu">Đề thi thử</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="hocKy">Học kỳ <span style="color:#e74c3c">*</span></label>
                                <select id="hocKy" name="hocKy" required>
                                    <option value="">-- Chọn học kỳ --</option>
                                    <option value="1">Học kỳ 1</option>
                                    <option value="2">Học kỳ 2</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="namHoc">Năm học <span style="color:#e74c3c">*</span></label>
                                <select id="namHoc" name="namHoc" required>
                                    <option value="">-- Chọn năm học --</option>
                                    <?php foreach ($schoolYears as $year): ?>
                                        <option value="<?php echo $year; ?>" <?php echo ($year == $schoolYear) ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Mô tả chiếm full hàng -->
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="moTa">Mô tả</label>
                                <textarea id="moTa" name="moTa" placeholder="Nhập mô tả chi tiết về đề thi..."></textarea>
                            </div>

                            <!-- Upload chiếm full hàng -->
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="file">File đề thi <span style="color:#e74c3c">*</span></label>

                                <div class="file-upload-wrapper">
                                    <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
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

                                <div id="fileSelected" class="file-selected">
                                    <i class="fas fa-check-circle"></i>
                                    <span id="fileName"></span>
                                </div>
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

    <script>
        // Hiển thị file đã chọn
        document.getElementById('file').addEventListener('change', function() {
            const fileSelected = document.getElementById('fileSelected');
            const fileName = document.getElementById('fileName');

            if (this.files.length > 0) {
                const file = this.files[0];
                const maxSize = 10 * 1024 * 1024;

                if (file.size > maxSize) {
                    showAlert('File quá lớn! Kích thước tối đa là 10MB.', 'error');
                    this.value = '';
                    fileSelected.style.display = 'none';
                    return;
                }

                const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
                const fileExtension = file.name.split('.').pop().toLowerCase();

                if (!allowedExtensions.includes(fileExtension)) {
                    showAlert('Định dạng file không hợp lệ! Chỉ chấp nhận: PDF, DOC, DOCX, XLS, XLSX', 'error');
                    this.value = '';
                    fileSelected.style.display = 'none';
                    return;
                }

                fileName.textContent = file.name;
                fileSelected.style.display = 'flex'; // hoặc block tuỳ css
            } else {
                fileSelected.style.display = 'none';
            }
        });

        function showAlert(message, type) {
            const alert = document.getElementById('alert');

            if (type !== 'success') {
                alert.className = 'alert';
                alert.textContent = '';
                alert.style.display = 'none';
                return;
            }

            alert.className = 'alert alert-success show';
            alert.textContent = message;
            alert.style.display = 'block';
            alert.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }


        document.getElementById('examForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const fileInput = document.getElementById('file');
            if (!fileInput.files || fileInput.files.length === 0) {
                showAlert('Vui lòng chọn file đề thi!', 'error');
                fileInput.focus();
                return;
            }

            const formData = new FormData(this);
            const loading = document.getElementById('loading');
            const submitBtn = this.querySelector('button[type="submit"]');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';

            loading.classList.add('active');
            document.getElementById('alert').style.display = 'none';

            try {
                const response = await fetch('../../controller/cSubmitExam.php?action=add', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                loading.classList.remove('active');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi đề thi';

                showAlert(result.message, result.success ? 'success' : 'error');

                if (result.success) {
                    setTimeout(() => {
                        this.reset();
                        document.getElementById('fileSelected').style.display = 'none';
                        setTimeout(() => window.location.reload(), 1000);
                    }, 3000);
                }
            } catch (error) {
                loading.classList.remove('active');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi đề thi';
                showAlert('Có lỗi xảy ra: ' + error.message, 'error');
            }
        });

        document.getElementById('examForm').addEventListener('reset', function() {
            document.getElementById('fileSelected').style.display = 'none';
            document.getElementById('alert').style.display = 'none';
        });
    </script>

</body>

</html>