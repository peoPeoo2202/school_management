<?php
// Khởi động session trước khi require config
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../config.php');

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

// Xử lý các action AJAX từ controller
if (isset($_GET['action']) || isset($_POST['action'])) {
    require_once(__DIR__ . '/../../controller/cHomeworkDetail.php');
    $controller = new cHomeworkDetail();

    $action = $_GET['action'] ?? $_POST['action'];

    switch ($action) {
        case 'getDetail':
            $controller->getDetail();
            exit();
        case 'grade':
            $controller->gradeSubmission();
            exit();
        case 'toggleLock':
            $controller->toggleLock();
            exit();
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}

// Lấy thông tin bài tập
require_once(__DIR__ . '/../../model/mHomeworkDetail.php');
$model = new mHomeworkDetail();

$maBaiTap = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($maBaiTap <= 0) {
    echo "<script>alert('ID bài tập không hợp lệ'); window.location.href='../../view/teacher/vAssignHomework.php';</script>";
    exit();
}

$homework = $model->getHomeworkDetail($maBaiTap);

if (!$homework) {
    echo "<script>alert('Không tìm thấy bài tập'); window.location.href='../../view/teacher/vAssignHomework.php';</script>";
    exit();
}

// Kiểm tra quyền: chỉ giáo viên giao bài mới được xem
if (!isset($_SESSION['maGV']) || $homework['maGV'] != $_SESSION['maGV']) {
    echo "<script>alert('Bạn không có quyền xem bài tập này'); window.location.href='../../view/teacher/vAssignHomework.php';</script>";
    exit();
}

$submissions = $model->getSubmissions($maBaiTap);
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
    <link rel="stylesheet" href="style.css">
    <style>

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
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
            max-width: 550px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #5081BE 0%, #3d6a9e 100%);
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
            max-height: calc(90vh - 160px);
            overflow-y: auto;
        }

        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .mb-3 {
            margin-bottom: 16px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 3px rgba(80, 129, 190, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            background: #f8f9fa;
        }

        @media (max-width: 1200px) {
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .content-inner {
                padding: 16px;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th,
            td {
                padding: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">

            <!-- Header theo layout mẫu 1 -->
            <div class="header-section">
                <div class="header-left">
                    <h2>
                        <i class="fas fa-file-alt"></i> Chi tiết bài tập
                    </h2>
                    <p>Theo dõi bài tập và danh sách bài nộp.</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen ?? 'Giáo viên'); ?></p>
                </div>
            </div>

            <!-- Card: Nút quay lại + Thông tin bài tập -->
            <div class="card">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                    <h2 class="card-title" style="margin:0;">
                        <i class="fas fa-info-circle"></i> Thông tin bài tập
                    </h2>

                    <div class="filter-actions-button">
                        <button onclick="toggleLockHomework()"
                            class="btn <?= $homework['khoaBai'] == 1 ? ' btn-danger' : 'btn-warning' ?>"
                            id="lockBtn">
                            <i class="fas fa-<?= $homework['khoaBai'] == 1 ? 'lock' : 'unlock' ?>"></i>
                            <?= $homework['khoaBai'] == 1 ? 'Mở khóa bài tập' : 'Khóa bài tập' ?>
                        </button>
                        <a href="<?= url('view/teacher/vAssignHomework.php') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>

                <!-- Thông tin bài tập (giữ nội dung cũ, chỉ đổi wrapper) -->


                <div class="info-details-box homework-assign">
                    <div class="info-item homework-assign">
                        <p class="info-label">Tên bài tập:</p>
                        <p class="info-value"><?= htmlspecialchars($homework['tenBaiTap']) ?></p>
                    </div>

                    <div class="info-item homework-assign">
                        <p class="info-label">Lớp:</p>
                        <p class="info-value"><?= htmlspecialchars($homework['tenLop']) ?></p>
                    </div>

                    <div class="info-item homework-assign">
                        <p class="info-label">Môn học:</p>
                        <p class="info-value"><?= htmlspecialchars($homework['tenMonHoc']) ?></p>
                    </div>

                    <div class="info-item homework-assign">
                        <p class="info-label">Hạn nộp:</p>
                        <p class="info-value"><?= date('d/m/Y H:i', strtotime($homework['thoiGianNop'])) ?></p>
                    </div>

                    <div class="info-item homework-assign">
                        <p class="info-label">Cho phép nộp trễ:</p>
                        <span class="info-value">
                            <?= $homework['choPhepNopTre'] == 1
                                ? '<span class="icon-sucess"><i class="fa-solid fa-circle-check"></i><p>Có</p></span>'
                                : '<span class="icon-danger"><i class="fa-solid fa-circle-xmark"></i><p>Không</p></span>' ?>
                        </span>
                    </div>

                    <?php if (!empty($homework['yeuCauBaiTap'])): ?>
                        <div class="info-item info-item-full homework-assign">
                            <p class="info-label">Yêu cầu:</p>
                            <p class="info-value">
                                <?= nl2br(htmlspecialchars($homework['yeuCauBaiTap'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php
                    if (!empty($homework['tenFile'])):
                        $fileExt = strtolower(pathinfo($homework['tenFile'], PATHINFO_EXTENSION));
                        $fileIcon = 'fa-file';
                        $iconColor = '#666';

                        switch ($fileExt) {
                            case 'pdf':
                                $fileIcon = 'fa-file-pdf';
                                $iconColor = '#d32f2f';
                                break;
                            case 'doc':
                            case 'docx':
                                $fileIcon = 'fa-file-word';
                                $iconColor = '#2b579a';
                                break;
                            case 'xls':
                            case 'xlsx':
                                $fileIcon = 'fa-file-excel';
                                $iconColor = '#217346';
                                break;
                            case 'ppt':
                            case 'pptx':
                                $fileIcon = 'fa-file-powerpoint';
                                $iconColor = '#d24726';
                                break;
                            case 'txt':
                                $fileIcon = 'fa-file-alt';
                                $iconColor = '#666';
                                break;
                            case 'zip':
                            case 'rar':
                            case '7z':
                                $fileIcon = 'fa-file-archive';
                                $iconColor = '#ffa500';
                                break;
                            case 'jpg':
                            case 'jpeg':
                            case 'png':
                            case 'gif':
                                $fileIcon = 'fa-file-image';
                                $iconColor = '#9c27b0';
                                break;
                            default:
                                $fileIcon = 'fa-file';
                                $iconColor = '#666';
                        }
                    ?>

                        <div class="info-item info-item-full">
                            <p class="info-label">File đính kèm:</p>

                            <div class="attachment-in-box">
                                <div class="attachment-info">
                                    <i class="fas <?= $fileIcon ?> attachment-icon" style="color: <?= $iconColor ?>;"></i>
                                    <span class="attachment-filename"><?= htmlspecialchars($homework['tenFile']) ?></span>
                                </div>

                                <div class="attachment-actions">
                                    <a href="<?= url('uploads/homework/' . $homework['tenFile']) ?>"
                                        class="file-attachment"
                                        download>
                                        <i class="fas fa-download"></i> Tải xuống
                                    </a>

                                    <a href="<?= url('uploads/homework/' . $homework['tenFile']) ?>"
                                        class="file-attachment"
                                        target="_blank">
                                        <i class="fas fa-eye"></i> Xem
                                    </a>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="info-item info-item-full">
                            <div class="attachment-empty">
                                <label class="attachment-empty-label">
                                    <i class="fas fa-info-circle"></i>
                                    Không có file đính kèm
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- GIỮ LẠI stats-cards (yêu cầu của ông xã) -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Tổng số học sinh</h3>
                        <div class="number"><?= $homework['tongSoHocSinh'] ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Đã nộp bài</h3>
                        <div class="number"><?= $homework['soLuongNopBai'] ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Chưa nộp</h3>
                        <div class="number"><?= $homework['tongSoHocSinh'] - $homework['soLuongNopBai'] ?></div>
                    </div>
                </div>
            </div>

            <!-- Card: Danh sách bài nộp -->
            <div class="card">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                    <h2 class="card-title" style="margin:0;">
                        <i class="fas fa-list"></i> Danh sách bài nộp
                    </h2>

                    <div class="filter-status">
                        <label for="statusFilter" class="filter-status-label">
                            Lọc trạng thái:
                        </label>

                        <select id="statusFilter"
                            class="filter-status-select"
                            onchange="filterSubmissions()">
                            <option value="all">Tất cả</option>
                            <option value="Đã nộp" selected>Đã nộp</option>
                            <option value="Nộp trễ">Nộp trễ</option>
                            <option value="Chưa nộp">Chưa nộp</option>
                        </select>
                    </div>

                </div>

                <?php if ($submissions->num_rows > 0): ?>
                    <table class="common-table" id="submissionsTable">
                        <thead>
                            <tr>
                                <th>Học sinh</th>
                                <th>Ngày nộp</th>
                                <th>Trạng thái</th>
                                <th>Điểm</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($sub = $submissions->fetch_assoc()): ?>
                                <tr data-status="<?= $sub['trangThaiNop'] ?>">
                                    <td><?= htmlspecialchars($sub['tenHS']) ?></td>
                                    <td><?= $sub['ngayNop'] ? date('d/m/Y H:i', strtotime($sub['ngayNop'])) : '-' ?></td>
                                    <td>
                                        <span class="badge <?= strtolower($sub['trangThaiNop']) ?>">
                                            <?= $sub['trangThaiNop'] ?>
                                        </span>
                                    </td>
                                    <td><?= $sub['diem'] !== null ? number_format($sub['diem'], 1) : '-' ?></td>
                                    <td>
                                        <?php if ($sub['ngayNop']): ?>
                                            <button class="btn btn-sm btn-primary" onclick="openGradeModal(<?= $sub['maBaiNop'] ?>)">
                                                <i class="fas fa-pen"></i> Chấm điểm
                                            </button>
                                            <?php if ($sub['tenFile']): ?>
                                                <a href="<?= url('uploads/submissions/' . $sub['tenFile']) ?>"
                                                    class="btn btn-sm btn-secondary" target="_blank" title="Xem file">
                                                    <i class="fas fa-file-download"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:#999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                            <?php
                            // Reset và dựng học sinh chưa nộp (giữ logic cũ)
                            $submissions->data_seek(0);
                            $submittedStudents = [];
                            while ($sub = $submissions->fetch_assoc()) {
                                $submittedStudents[] = $sub['maHS'];
                            }

                            $students->data_seek(0);
                            while ($student = $students->fetch_assoc()):
                                if (!in_array($student['maHS'], $submittedStudents)):
                            ?>
                                    <tr data-status="Chưa nộp">
                                        <td><?= htmlspecialchars($student['hoTen']) ?></td>
                                        <td>-</td>
                                        <td>
                                            <span class="badge in-progress">Chưa nộp</span>
                                        </td>
                                        <td>-</td>
                                        <td>-</td>
                                    </tr>
                            <?php
                                endif;
                            endwhile;
                            ?>
                        </tbody>
                    </table>

                    <!-- Phân trang -->
                    <div id="paginationContainer"></div>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Chưa có bài nộp nào</h3>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Modal Chấm điểm (GIỮ NGUYÊN như code cũ của ông xã) -->
    <div class="modal" id="gradeModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h5 class="modal-title">Chấm điểm bài tập</h5>
                <button type="button" class="btn-close" onclick="closeGradeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="gradeForm">
                    <input type="hidden" name="maBaiNop" id="maBaiNop">
                    <div class="mb-3">
                        <label class="form-label">Học sinh</label>
                        <input type="text" id="studentName" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Điểm <span style="color: red;">*</span></label>
                        <input type="number" name="diem" id="diem" class="form-control"
                            min="0" max="10" step="0.5" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nhận xét</label>
                        <textarea name="nhanXet" id="nhanXet" class="form-control" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeGradeModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-success" onclick="submitGrade()">
                    <i class="fas fa-save"></i> Lưu điểm
                </button>
            </div>
        </div>
    </div>

    <script>
        // Biến phân trang
        let currentPage = 1;
        const rowsPerPage = 10;
        let totalRows = 0;
        let allRows = [];

        function initPagination() {
            const table = document.getElementById('submissionsTable');
            if (!table) return;

            const tbody = table.getElementsByTagName('tbody')[0];
            allRows = Array.from(tbody.getElementsByTagName('tr')).filter(row => row.id !== 'noResultRow');
            totalRows = allRows.length;

            const totalPages = Math.ceil(totalRows / rowsPerPage);
            showPage(1);
            renderPagination();
        }

        function showPage(page) {
            const totalPages = Math.ceil(totalRows / rowsPerPage);

            if (page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            if (totalPages === 0) page = 1;

            currentPage = page;

            // Ẩn tất cả các hàng
            allRows.forEach(row => row.style.display = 'none');

            // Hiển thị các hàng cho trang hiện tại
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            for (let i = start; i < end && i < totalRows; i++) {
                allRows[i].style.display = '';
            }

            renderPagination();
        }

        function changePage(page) {
            showPage(page);
        }

        function renderPagination() {
            const totalPages = Math.ceil(totalRows / rowsPerPage);
            const container = document.getElementById('paginationContainer');
            
            if (!container) return;
            
            if (totalPages === 0) {
                container.style.display = 'none';
                return;
            }
            
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
            const offset = (currentPage - 1) * rowsPerPage;
            html += `<div class="pagination-info">
                Hiển thị ${offset + 1} - ${Math.min(offset + rowsPerPage, totalRows)} 
                trong tổng số ${totalRows} bài nộp</div>`;
            
            container.innerHTML = html;
        }

        function openGradeModal(maBaiNop) {
            console.log('Opening modal for maBaiNop:', maBaiNop); // Debug

            if (!maBaiNop || maBaiNop <= 0) {
                alert('Mã bài nộp không hợp lệ: ' + maBaiNop);
                return;
            }

            fetch('<?= url("controller/cHomeworkDetail.php?action=getDetail") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'maBaiNop=' + maBaiNop
                })
                .then(res => res.json())
                .then(data => {
                    console.log('Submission data:', data); // Debug
                    if (data.success) {
                        document.getElementById('maBaiNop').value = data.submission.maBaiNop;
                        document.getElementById('studentName').value = data.submission.tenHS;
                        document.getElementById('diem').value = data.submission.diem || '';
                        document.getElementById('nhanXet').value = data.submission.nhanXet || '';
                        document.getElementById('gradeModal').classList.add('show');
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Có lỗi xảy ra khi tải dữ liệu');
                });
        }

        function closeGradeModal() {
            document.getElementById('gradeModal').classList.remove('show');
        }

        function filterSubmissions() {
            const filterValue = document.getElementById('statusFilter').value;
            const table = document.getElementById('submissionsTable');
            if (!table) return;

            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr')).filter(row => row.id !== 'noResultRow');

            // Ẩn tất cả các hàng trước
            rows.forEach(row => row.style.display = 'none');

            // Lọc các hàng theo trạng thái
            allRows = rows.filter(row => {
                const status = row.getAttribute('data-status');
                return filterValue === 'all' || status === filterValue;
            });

            totalRows = allRows.length;

            // Xóa thông báo "không tìm thấy" nếu có
            let noResultRow = document.getElementById('noResultRow');
            if (noResultRow) {
                noResultRow.remove();
            }

            // Hiển thị thông báo nếu không có kết quả
            if (totalRows === 0) {
                noResultRow = tbody.insertRow(0);
                noResultRow.id = 'noResultRow';
                const cell = noResultRow.insertCell(0);
                cell.colSpan = 5;
                cell.style.textAlign = 'center';
                cell.style.padding = '20px';
                cell.style.color = '#999';
                cell.style.fontStyle = 'italic';
                cell.innerHTML = '<i class="fas fa-search"></i> Không tìm thấy bài nộp với trạng thái này';
            }

            // Cập nhật phân trang
            const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;
            document.getElementById('totalPages').textContent = totalPages;

            // Hiển thị trang 1 sau khi lọc
            showPage(1);
        }

        // Khởi tạo phân trang khi trang được tải
        document.addEventListener('DOMContentLoaded', function() {
            initPagination();
        });

        function submitGrade() {
            const form = document.getElementById('gradeForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const maBaiNop = formData.get('maBaiNop');

            console.log('Submitting grade for maBaiNop:', maBaiNop); // Debug
            console.log('FormData:', Array.from(formData.entries())); // Debug

            fetch('<?= url("controller/cHomeworkDetail.php?action=grade") ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    console.log('Grade response:', data); // Debug
                    alert(data.message);
                    if (data.success) {
                        closeGradeModal();
                        location.reload();
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Có lỗi xảy ra khi chấm điểm');
                });
        }

        function toggleLockHomework() {
            const lockBtn = document.getElementById('lockBtn');
            const currentLockState = <?= $homework['khoaBai'] ?>;
            const action = currentLockState == 1 ? 'unlock' : 'lock';
            const confirmMsg = currentLockState == 1 ?
                'Bạn có chắc muốn mở khóa bài tập này? Học sinh sẽ có thể nộp bài.' :
                'Bạn có chắc muốn khóa bài tập này? Học sinh sẽ không thể nộp bài.';

            if (!confirm(confirmMsg)) {
                return;
            }

            lockBtn.disabled = true;
            lockBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

            const formData = new FormData();
            formData.append('maBaiTap', <?= $maBaiTap ?>);
            formData.append('action', action);

            fetch('<?= url("controller/cHomeworkDetail.php?action=toggleLock") ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        location.reload();
                    } else {
                        lockBtn.disabled = false;
                        lockBtn.innerHTML = action == 'lock' ?
                            '<i class="fas fa-unlock"></i> Khóa bài tập' :
                            '<i class="fas fa-lock"></i> Mở khóa bài tập';
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Có lỗi xảy ra khi xử lý');
                    lockBtn.disabled = false;
                    lockBtn.innerHTML = action == 'lock' ?
                        '<i class="fas fa-unlock"></i> Khóa bài tập' :
                        '<i class="fas fa-lock"></i> Mở khóa bài tập';
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