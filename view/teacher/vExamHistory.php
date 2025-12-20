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

    <style>
        .btn-info {
            background: #2196f3;
            color: white;
        }

        .btn-info:hover {
            background: #1976d2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.25);
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

        /* Responsive */
   
        /* ===== Modal (GIỮ NGUYÊN style ông xã) ===== */
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
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
    </style>
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

            <!-- Card thao tác nhanh -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-bolt"></i> Thao tác nhanh</h2>
                    <a href="vSubmitExam.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </div>

            <!-- Card bộ lọc theo mẫu -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-filter"></i> Bộ lọc</h2>
                </div>

                <div class="card-body">
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

                            <div class="filter-group">
                                <label>&nbsp;</label>
                                <div class="filter-actions">
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-filter"></i> Lọc
                                    </button>
                                    <a href="vExamHistory.php" class="btn btn-secondary">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card bảng danh sách theo mẫu -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-table"></i> Danh sách đề thi</h2>
                </div>

                <div class="card-body">
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

                                        $downloadLink = $exam['tenFile'] ? '../../controller/cSubmitExam.php?action=download&id=' . $exam['maDeThi'] : '#';
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
                                                    <br>
                                                    <small style="color: #999;">
                                                        <?php echo htmlspecialchars(substr($exam['moTa'], 0, 50)) . (strlen($exam['moTa']) > 50 ? '...' : ''); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>HK<?php echo $exam['hocKy']; ?> / <?php echo $exam['namHoc']; ?></td>
                                            <td>
                                                <?php if ($exam['tenFile']): ?>
                                                    <a href="javascript:void(0);"
                                                        onclick="downloadFile('<?php echo addslashes($downloadLink); ?>', '<?php echo addslashes($exam['tenDeThi'] . '.' . pathinfo($exam['tenFile'], PATHINFO_EXTENSION)); ?>')"
                                                        class="btn-sm btn-info"
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
                                                        <button class="btn-sm btn-warning"
                                                            onclick='editExam(<?php echo json_encode($exam, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                                            title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-sm btn-danger"
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
    </div>

    <!-- Modal sửa đề thi (GIỮ NGUYÊN HTML) -->
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
        function downloadFile(url, filename) {
            const link = document.createElement('a');
            link.href = url;
            link.download = filename || 'download';
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        document.getElementById('edit_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Chọn file mới (PDF, DOC, DOCX, XLS, XLSX)';
            document.getElementById('fileNameDisplay').textContent = fileName;
        });

        function editExam(exam) {
            document.getElementById('edit_maDeThi').value = exam.maDeThi;
            document.getElementById('edit_tenDeThi').value = exam.tenDeThi;
            document.getElementById('edit_loaiDeThi').value = exam.loaiDeThi || 'de-thi';
            document.getElementById('edit_hocKy').value = exam.hocKy;
            document.getElementById('edit_namHoc').value = exam.namHoc;

            const moTa = exam.moTa && exam.moTa !== '0' ? exam.moTa : '';
            document.getElementById('edit_moTa').value = moTa;

            const fileInfo = document.getElementById('currentFileInfo');
            if (exam.tenFile) {
                fileInfo.innerHTML = '<strong>File:</strong> ' + exam.tenFile;
            } else {
                fileInfo.innerHTML = '<strong>Chưa có file</strong>';
            }

            document.getElementById('edit_file').value = '';
            document.getElementById('fileNameDisplay').textContent = 'Chọn file mới (PDF, DOC, DOCX, XLS, XLSX)';

            document.getElementById('editExamModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editExamModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editExamModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

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
