<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

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
$subjects = $controller->getTeacherSubjects($maGV);
$schoolYears = $controller->getSchoolYears();

$filters = [
    'hocKy' => isset($_GET['hocKy']) ? intval($_GET['hocKy']) : null,
    'namHoc' => isset($_GET['namHoc']) ? trim($_GET['namHoc']) : null,
    'trangThai' => isset($_GET['trangThai']) ? trim($_GET['trangThai']) : null
];

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

</head>

<body>
    <div class="main-wrapper">
        <?php include('../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">
            <!-- Header theo mẫu -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-history"></i> Lịch sử đề thi</h2>
                    <p>Quản lý danh sách đề thi đã gửi</p>
                </div>
                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>


            <!-- Card bộ lọc theo mẫu -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-filter"></i> Bộ lọc</h2>
                    <a  href="vSubmitExam.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <form id="filterForm" method="GET">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label for="filter_hocKy">Học kỳ</label>
                            <select id="filter_hocKy" name="hocKy">
                                <option value="">-- Tất cả --</option>
                                <option value="1" <?php echo ($filters['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo ($filters['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="filter_namHoc">Năm học</label>
                            <select id="filter_namHoc" name="namHoc">
                                <option value="">-- Tất cả --</option>
                                <?php foreach ($schoolYears as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo ($filters['namHoc'] == $year) ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="filter_trangThai">Trạng thái</label>
                            <select id="filter_trangThai" name="trangThai">
                                <option value="">-- Tất cả --</option>
                                <option value="Chuaduyet" <?php echo ($filters['trangThai'] == 'Chuaduyet') ? 'selected' : ''; ?>>Chờ duyệt</option>
                                <option value="Daduyet" <?php echo ($filters['trangThai'] == 'Daduyet') ? 'selected' : ''; ?>>Đã duyệt</option>
                                <option value="Dachon" <?php echo ($filters['trangThai'] == 'Dachon') ? 'selected' : ''; ?>>Đã chọn</option>
                                <option value="Tuchoi" <?php echo ($filters['trangThai'] == 'Tuchoi') ? 'selected' : ''; ?>>Từ chối</option>
                            </select>
                        </div>

                        <div class="filter-actions-button">
                            <label>&nbsp;</label>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Lọc
                            </button>

                            <a href="vExamHistory.php" class="btn btn-outlined">
                                <i class="fas fa-redo"></i> Đặt lại
                            </a>



                        </div>
                    </div>
                </form>
            </div>

            <!-- Card bảng danh sách theo mẫu -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-table"></i> Danh sách đề thi</h2>
                </div>

                <div class="card-body">
                    <?php if (count($exams) > 0): ?>
                        <table class="common-table ">
                            <thead>
                                <tr>
                                    <th class="small-cell">STT</th>
                                    <th>Tên đề thi</th>
                                    <th>HK/Năm học</th>
                                    <th>Tải File</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày gửi</th>
                                    <th class="action-cell">Thao tác</th>
                                </tr>
                            </thead>

                            <tbody class="common-table-body">
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

                                    $downloadLink = $exam['tenFile'] ? '../../controller/cSubmitExam.php?action=download&id=' . $exam['maDeThi'] : '#';
                                    $viewLink = $exam['tenFile'] ? '../../controller/cSubmitExam.php?action=view&id=' . $exam['maDeThi'] : '#';
                                ?>
                                    <tr>
                                        <td class="small-cell"><?php echo $stt++; ?></td>

                                        <td class="exam-title-cell">
                                            <div>
                                                <?php if ($exam['tenFile']): ?>
                                                    <p class="exam-title-text"><?php echo htmlspecialchars($exam['tenDeThi']); ?></p>
                                                <?php else: ?>
                                                    <span class="">
                                                        <p class="exam-title-text"><?php echo htmlspecialchars($exam['tenDeThi']); ?></p>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($exam['moTa']): ?>
                                                    <span>
                                                        <p class="exam-description">
                                                            <?php echo htmlspecialchars(substr($exam['moTa'], 0, 50)) . (strlen($exam['moTa']) > 50 ? '...' : ''); ?>
                                                        </p>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <td>
                                            <p class="exam-semester-year">
                                                HK<?php echo $exam['hocKy']; ?> / <?php echo $exam['namHoc']; ?>
                                            </p>
                                        </td>

                                        <td class="center">
                                            <?php if ($exam['tenFile']): ?>
                                                <a class="btn-download-file " href="javascript:void(0);"
                                                    onclick="downloadFile('<?php echo addslashes($downloadLink); ?>', '<?php echo addslashes($exam['tenDeThi'] . '.' . pathinfo($exam['tenFile'], PATHINFO_EXTENSION)); ?>')"
                                                    title="Tải file">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="empty-info">Không có</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="center">
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo $exam['trangThaiText']; ?>
                                            </span>
                                        </td>

                                        <td class="exam-year"><?php echo date('d/m/Y H:i', strtotime($exam['ngayTao'])); ?></td>

                                        <td class="action-cell">
                                            <div class="action-buttons">
                                                <?php if ($exam['trangThai'] == 'Chuaduyet'): ?>
                                                    <button"
                                                        onclick='editExam(<?php echo json_encode($exam, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                                        title="Sửa">
                                                        <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button"
                                                            onclick="deleteExam(<?php echo $exam['maDeThi']; ?>)"
                                                            title="Xóa">
                                                            <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="empty-info">-</span>
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

    <!-- Modal sửa đề thi (GIỮ NGUYÊN HTML) -->
    <div class="common-modal" id="editExamModal">
        <div class="assign-homework-modal-dialog ">
            <!-- HEADER giống modal mẫu -->
            <div class="common-modal-header">
                <h5 class="common-modal-title">
                    <i class="fas fa-edit"></i>
                    Sửa đề thi
                </h5>
                <button type="button" class="btn-close-modal-assign-homework" onclick="closeEditExamModal()">&times;</button>
            </div>

            <!-- BODY giống modal mẫu -->
            <div class="common-modal-body">
                <form id="editExamForm" enctype="multipart/form-data">
                    <input type="hidden" id="edit_maDeThi" name="maDeThi">

                    <div class="mb-3">
                        <label class="form-label" for="edit_tenDeThi">Tên đề thi <span class="required">*</span></label>
                        <input type="text" id="edit_tenDeThi" name="tenDeThi" class="form-control" placeholder="Nhập tên đề thi..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="edit_loaiDeThi">Loại đề thi <span class="required">*</span></label>
                        <select id="edit_loaiDeThi" name="loaiDeThi" class="form-select" required>
                            <option value="de-thi-giua-ky">Đề thi giữa kỳ</option>
                            <option value="de-thi-cuoi-ky">Đề thi cuối kỳ</option>
                            <option value="de-thi-thu">Đề thi thử</option>
                        </select>
                    </div>

                    <div class="row-2">
                        <div class="mb-3">
                            <label class="form-label" for="edit_hocKy">Học kỳ <span class="required">*</span></label>
                            <select id="edit_hocKy" name="hocKy" class="form-select" required>
                                <option value="1">Học kỳ 1</option>
                                <option value="2">Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_namHoc">Năm học <span class="required">*</span></label>
                            <select id="edit_namHoc" name="namHoc" class="form-select" required>
                                <?php foreach ($schoolYears as $year): ?>
                                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="edit_moTa">Mô tả</label>
                        <textarea id="edit_moTa" name="moTa" class="form-control" rows="4" placeholder="Nhập mô tả (nếu có)..."></textarea>
                    </div>

                    <!-- Khối file giống style modal mẫu -->
                    <div class="mb-3">
                        <label class="form-label">File hiện tại</label>

                        <!-- Giữ nguyên id để JS set innerHTML -->
                        <div id="currentFileInfo" class="current-file-info">

                        </div>

                        <label class="form-label">Thay đổi file (tùy chọn)</label>

                        <div class="file-upload-wrapper" id="editFileUploadWrapperExam">
                            <div class="file-upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="file-upload-text">
                                <h4>Chọn file mới hoặc kéo thả vào đây</h4>
                                <p>Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX (Tối đa 10MB)</p>
                            </div>

                            <!-- Giữ nguyên id="edit_file" -->
                            <input type="file"
                                id="edit_file"
                                name="file"
                                class="file-upload-input"
                                accept=".pdf,.doc,.docx,.xls,.xlsx">
                            <div class="file-upload-hint">Để trống nếu không muốn thay đổi file</div>
                        </div>

                        <!-- Giữ nguyên id="fileNameDisplay" -->
                        <div class="file-selected" id="editFileSelectedExam" style="display:none;">
                            <i class="fas fa-file-check"></i>
                            <div class="file-info">
                                <div class="file-name" id="fileNameDisplay">Chọn file mới (PDF, DOC, DOCX, XLS, XLSX)</div>
                            </div>
                            <button type="button" class="remove-file" id="editRemoveFileExam">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="common-modal-footer ">
                <button type="button" class="btn btn-delete" onclick="closeEditExamModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="submit" class="btn btn-primary" form="editExamForm">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </div>



    <script>
        function downloadFile(url, filename) {
            const link = document.createElement('a');
            link.href = url;
            link.download = filename || 'download';
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        const editFileInput = document.getElementById('edit_file');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const selectedBox = document.getElementById('editFileSelectedExam');
        const removeBtn = document.getElementById('editRemoveFileExam');

        editFileInput.addEventListener('change', function(e) {
            const file = e.target.files && e.target.files[0];
            fileNameDisplay.textContent = file ? file.name : 'Chọn file mới (PDF, DOC, DOCX, XLS, XLSX)';
            selectedBox.style.display = file ? 'flex' : 'none';
        });

        removeBtn.addEventListener('click', function() {
            editFileInput.value = '';
            fileNameDisplay.textContent = 'Chọn file mới (PDF, DOC, DOCX, XLS, XLSX)';
            selectedBox.style.display = 'none';
        });

        function openEditExamModal() {
            document.getElementById('editExamModal').classList.add('show');
        }

        function closeEditExamModal() {
            document.getElementById('editExamModal').classList.remove('show');
        }

        function editExam(exam) {
            document.getElementById('edit_maDeThi').value = exam.maDeThi;
            document.getElementById('edit_tenDeThi').value = exam.tenDeThi;
            document.getElementById('edit_loaiDeThi').value = exam.loaiDeThi || 'de-thi-giua-ky';
            document.getElementById('edit_hocKy').value = exam.hocKy;
            document.getElementById('edit_namHoc').value = exam.namHoc;

            const moTa = exam.moTa && exam.moTa !== '0' ? exam.moTa : '';
            document.getElementById('edit_moTa').value = moTa;

            // current file
            const fileInfo = document.getElementById('currentFileInfo');
            fileInfo.style.display = 'block';
            if (exam.tenFile) {
                fileInfo.classList.remove('empty');
                fileInfo.innerHTML = `
    <i class="fas fa-file"></i>
    <span class="file-name">${exam.tenFile}</span>
  `;
            } else {
                fileInfo.classList.add('empty');
                fileInfo.innerHTML = `<strong>Chưa có file</strong>`;
            }

            // reset new file selection
            editFileInput.value = '';
            fileNameDisplay.textContent = 'Chọn file mới (PDF, DOC, DOCX, XLS, XLSX)';
            selectedBox.style.display = 'none';

            openEditExamModal();
        }

        // ✅ click ra ngoài để đóng (không ghi đè window.onclick)
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('editExamModal');
            if (event.target === modal) closeEditExamModal();
        });

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
                    closeEditExamModal();
                    window.location.reload();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                alert('Có lỗi xảy ra: ' + error.message);
            }
        });

        async function deleteExam(maDeThi) {
            if (!confirm('Bạn có chắc chắn muốn xóa đề thi này?')) return;

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