<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
$maGV = $_SESSION['maGV'] ?? null;

// Load model
require_once(__DIR__ . '/../../model/mConnect.php');
require_once(__DIR__ . '/../../model/mStudentAward.php');

$mConnect = new mConnect();
$conn = $mConnect->mConnect();

if (!$conn) {
    die("Kết nối thất bại!");
}

$model = new ModelStudentAward($conn);

// Xử lý AJAX requests
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'getAwards':
            $maLop = intval($_GET['maLop'] ?? 0);
            $hocKy = intval($_GET['hocKy'] ?? 1);
            $namHoc = trim($_GET['namHoc'] ?? '');

            // Kiểm tra quyền
            $classInfo = $model->getClassInfo($maLop);
            if (!$classInfo || $classInfo['maGV'] != $maGV) {
                echo json_encode(['error' => 'Bạn không có quyền truy cập'], JSON_UNESCAPED_UNICODE);
                exit();
            }

            $students = $model->getStudentAwards($maLop, $hocKy, $namHoc);
            echo json_encode(['students' => $students], JSON_UNESCAPED_UNICODE);
            exit();

        case 'add':
            $data = [
                'maHS' => intval($_POST['maHS'] ?? 0),
                'lyDo' => trim($_POST['lyDo'] ?? ''),
                'capKhenThuong' => trim($_POST['capKhenThuong'] ?? ''),
                'ngayKhen' => $_POST['ngayKhen'] ?? date('Y-m-d'),
                'hocKy' => intval($_POST['hocKy'] ?? 1),
                'namHoc' => trim($_POST['namHoc'] ?? ''),
                'hinhThuc' => trim($_POST['hinhThuc'] ?? ''),
                'linhVuc' => trim($_POST['linhVuc'] ?? '')
            ];

            // Validate
            if (empty($data['maHS']) || empty($data['lyDo']) || empty($data['capKhenThuong'])) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin'], JSON_UNESCAPED_UNICODE);
                exit();
            }

            $result = $model->addAward($data);
            echo json_encode(
                $result ?
                    ['success' => true, 'message' => 'Thêm khen thưởng thành công'] :
                    ['success' => false, 'message' => 'Lỗi khi thêm khen thưởng'],
                JSON_UNESCAPED_UNICODE
            );
            exit();

        case 'update':
            $maKhenThuong = intval($_POST['maKhenThuong'] ?? 0);
            $data = [
                'lyDo' => trim($_POST['lyDo'] ?? ''),
                'capKhenThuong' => trim($_POST['capKhenThuong'] ?? ''),
                'ngayKhen' => $_POST['ngayKhen'] ?? date('Y-m-d'),
                'hinhThuc' => trim($_POST['hinhThuc'] ?? ''),
                'linhVuc' => trim($_POST['linhVuc'] ?? '')
            ];

            if (empty($data['lyDo']) || empty($data['capKhenThuong'])) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin'], JSON_UNESCAPED_UNICODE);
                exit();
            }

            $result = $model->updateAward($maKhenThuong, $data);
            echo json_encode(
                $result ?
                    ['success' => true, 'message' => 'Cập nhật khen thưởng thành công'] :
                    ['success' => false, 'message' => 'Lỗi khi cập nhật khen thưởng'],
                JSON_UNESCAPED_UNICODE
            );
            exit();

        case 'delete':
            $maKhenThuong = intval($_POST['maKhenThuong'] ?? 0);

            if (!$maKhenThuong) {
                echo json_encode(['success' => false, 'message' => 'Mã khen thưởng không hợp lệ'], JSON_UNESCAPED_UNICODE);
                exit();
            }

            $result = $model->deleteAward($maKhenThuong);
            echo json_encode(
                $result ?
                    ['success' => true, 'message' => 'Xóa khen thưởng thành công'] :
                    ['success' => false, 'message' => 'Lỗi khi xóa khen thưởng'],
                JSON_UNESCAPED_UNICODE
            );
            exit();

        default:
            echo json_encode(['error' => 'Action không hợp lệ'], JSON_UNESCAPED_UNICODE);
            exit();
    }
}

// Load initial data for page display
$data = [];
$classes = $model->getClassesByTeacher($maGV);

if (empty($classes)) {
    $data['error'] = 'Bạn chưa được phân công làm giáo viên chủ nhiệm lớp nào';
} else {
    // Lấy maLop từ URL hoặc lớp đầu tiên
    $maLop = $_GET['maLop'] ?? $classes[0]['maLop'];

    // Lấy thông tin lớp
    $classInfo = $model->getClassInfo($maLop);

    // Kiểm tra quyền
    if (!$classInfo || $classInfo['maGV'] != $maGV) {
        $data['error'] = 'Bạn không có quyền xem thông tin lớp này';
    } else {
        // Lấy thông tin học kỳ và năm học hiện tại
        $currentYear = date('Y');
        $namHoc = ($currentYear - 1) . '-' . $currentYear;
        $hocKy = (date('m') <= 6) ? 2 : 1;

        $data = [
            'classes' => $classes,
            'classInfo' => $classInfo,
            'currentClassId' => $maLop,
            'hocKy' => $hocKy,
            'namHoc' => $namHoc
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Khen thưởng - Hệ thống Quản lý</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">
            <div class="header-section">
                <div class="header-left">
                    <div class="header-left-icon">
                        <h2><i class="fas fa-trophy"></i></h2>
                        <h2>Quản lý Khen thưởng</h2>
                    </div>
                    <p>Quản lý khen thưởng cho học sinh</p>
                </div>
                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <?php if (isset($data['error'])): ?>
                <div class="card">
                    <div class="alert alert-error show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $data['error']; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fa-solid fa-filter"></i> Bộ lọc
                        </h2>

                    </div>

                    <form method="GET" action="../../controller/cStudentAward.php">
                        <input type="hidden" name="action" value="list">
                        <div class="filter-section">
                            <div class="filter-group">
                                <label>Học kỳ</label>
                                <select id="hocKy" name="hocKy" class="form-control" onchange="loadData()">
                                    <option value="1" <?php echo ($data['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                    <option value="2" <?php echo ($data['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Năm học</label>
                                <select id="namHoc" name="namHoc" class="form-control" onchange="loadData()">
                                    <?php
                                    $currentYear = date('Y');
                                    $startYear = 2020; // Năm bắt đầu
                                    for ($year = $currentYear; $year >= $startYear; $year--) {
                                        $namHoc = ($year - 1) . '-' . $year;
                                        $selected = ($namHoc == $data['namHoc']) ? 'selected' : '';
                                        echo "<option value='$namHoc' $selected>$namHoc</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="filter-actions-button">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-filter"></i> Lọc
                                </button>
                                <button class="btn btn-primary" onclick="openAddModal()">
                                    <i class="fas fa-plus"></i> Thêm khen thưởng
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div id="alertMessage"></div>
                <input type="hidden" id="maLop" value="<?php echo $data['currentClassId']; ?>">

                <div class="card">
                    <div id="awardContent">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Đang tải dữ liệu...</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal thêm/sửa khen thưởng -->
    <div class="common-modal" id="awardModal">
        <div class="assign-homework-modal-dialog">
            <div class="common-modal-header">
                <h5 class="common-modal-title" id="modalTitle">
                    <i class="fas fa-award"></i>
                    Thêm khen thưởng
                </h5>
                <button type="button" class="btn-close-modal" onclick="closeModal()"><i class="fa-solid fa-x"></i></button>
            </div>
            <div class="common-modal-body">
                <form id="awardForm">
                    <input type="hidden" id="maKhenThuong">

                    <div class="mb-3">
                        <label class="form-label">Học sinh <span>*</span></label>
                        <select id="maHS" class="form-select" required>
                            <option value="">-- Chọn học sinh --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Giải thưởng <span>*</span></label>
                        <input type="text" id="lyDo" class="form-control" required
                            placeholder="Ví dụ: Học sinh giỏi toàn diện">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Hình thức</label>
                        <select id="hinhThuc" class="form-select">
                            <option value="">-- Chọn hình thức --</option>
                            <option value="Giấy khen">Giấy khen</option>
                            <option value="Bằng khen">Bằng khen</option>
                            <option value="Huy chương">Huy chương</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cấp khen thưởng <span>*</span></label>
                        <select id="capKhenThuong" class="form-select" required>
                            <option value="">-- Chọn cấp --</option>
                            <option value="truong">Cấp trường</option>
                            <option value="huyen">Cấp huyện</option>
                            <option value="tinh">Cấp tỉnh</option>
                            <option value="quocgia">Cấp quốc gia</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lĩnh vực</label>
                        <select id="linhVuc" class="form-select">
                            <option value="">-- Chọn lĩnh vực --</option>
                            <option value="Học tập">Học tập</option>
                            <option value="Thể thao">Thể thao</option>
                            <option value="Văn nghệ">Văn nghệ</option>
                            <option value="Hoạt động xã hội">Hoạt động xã hội</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ngày khen</label>
                        <input type="date" id="ngayKhen" class="form-control"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </form>
            </div>
            <div class="common-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" onclick="saveAward()">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </div>
    </div>

    <script>
        let students = [];
        let currentPage = 1;
        let itemsPerPage = 5;
        let totalItems = 0;

        function loadData() {
            const maLop = document.getElementById('maLop').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;

            document.getElementById('awardContent').innerHTML =
                '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Đang tải dữ liệu...</p></div>';

            fetch(`?action=getAwards&maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showAlert(data.error, 'error');
                        document.getElementById('awardContent').innerHTML = '';
                    } else {
                        students = data.students || [];
                        currentPage = 1; // Reset về trang 1 khi load data mới
                        displayAwards();
                    }
                })
                .catch(error => {
                    showAlert('Lỗi khi tải dữ liệu: ' + error.message, 'error');
                });
        }

        function displayAwards() {
            if (!students || students.length === 0) {
                document.getElementById('awardContent').innerHTML =
                    '<div class="loading"><p>Không có dữ liệu học sinh</p></div>';
                return;
            }

            // Tạo danh sách phẳng của tất cả các khen thưởng
            let allAwards = [];
            students.forEach((student) => {
                if (student.awards && student.awards.length > 0) {
                    student.awards.forEach((award) => {
                        allAwards.push({
                            ...award,
                            studentName: student.hoTen,
                            maHS: student.maHS
                        });
                    });
                }
            });

            totalItems = allAwards.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);

            // Đảm bảo currentPage hợp lệ
            if (currentPage > totalPages && totalPages > 0) {
                currentPage = totalPages;
            }
            if (currentPage < 1) {
                currentPage = 1;
            }

            // Lấy dữ liệu cho trang hiện tại
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const currentAwards = allAwards.slice(startIndex, endIndex);

            let html = `
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-award"></i> Danh sách khen thưởng
                    </h2>
                </div>
                <div class="table-container">
                    <table class="common-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Họ và tên</th>
                                <th class="normal-cell">Hình thức</th>
                                <th>Nội dung</th>
                                <th class="large-cell">Cấp khen thưởng</th>
                                <th class="normal-cell">Lĩnh vực</th>
                                <th class="small-cell">Ngày khen</th>
                                <th>Học kỳ</th>
                                <th class="normal-cell">Năm học</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="common-table-body">
            `;

            currentAwards.forEach((award, index) => {
                const rowIndex = startIndex + index + 1;
                html += `
                    <tr class="table-normal-text">
                        <td >
                            <p>${rowIndex}</p>
                        </td>
                        <td>
                            <div class="request-description">
                                <span class="request-des-title">${award.studentName}</span>
                            </div>
                        </td>
                        <td class="normal-cell">${award.hinhThuc || '-'}</td>
                        <td class="table-strong-text">${award.lyDo}</td>
                        <td class="large-cell">
                            <span class="badge badge-${award.capKhenThuong}">
                                ${formatAwardLevel(award.capKhenThuong)}
                            </span>
                        </td>
                        <td class="small-cell">${award.linhVuc || '-'}</td>
                        <td class="small-cell">${formatDate(award.ngayKhen)}</td>
                        <td>${award.hocKy || '-'}</td>
                        <td class="small-cell">${award.namHoc || '-'}</td>
                        <td class="action-cell">
                            <div class="action-buttons">
                                <button class="btn-view" 
                                        onclick="editAward(${award.maKhenThuong})"
                                        title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-delete" 
                                        onclick="deleteAward(${award.maKhenThuong})"
                                        title="Xoá">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            // Thêm phân trang - luôn hiển thị thông tin
            if (totalItems > 0) {
                html += renderPagination(totalPages);
            }

            document.getElementById('awardContent').innerHTML = html;
        }

        function renderPagination(totalPages) {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, totalItems);

            let html = `<div class="pagination pagi-award">`;

            // Previous Button
            if (currentPage > 1) {
                html += `<a href="javascript:changePage(${currentPage - 1})"><i class="fas fa-chevron-left"></i> Trước</a>`;
            } else {
                html += `<span class="disabled"><i class="fas fa-chevron-left"></i> Trước</span>`;
            }

            // Page Numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                html += `<a href="javascript:changePage(1)">1</a>`;
                if (startPage > 2) {
                    html += `<span>...</span>`;
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
                    html += `<span>...</span>`;
                }
                html += `<a href="javascript:changePage(${totalPages})">${totalPages}</a>`;
            }

            // Next Button
            if (currentPage < totalPages) {
                html += `<a href="javascript:changePage(${currentPage + 1})">Sau <i class="fas fa-chevron-right"></i></a>`;
            } else {
                html += `<span class="disabled">Sau <i class="fas fa-chevron-right"></i></span>`;
            }

            html += `</div>`;

            // Pagination Info
            html += `<div class="pagination-info">
                Hiển thị ${startIndex + 1} - ${endIndex} trong tổng số ${totalItems} khen thưởng
            </div>`;

            return html;
        }

        function changePage(page) {
            currentPage = page;
            displayAwards();
            // Scroll to top of table
            document.getElementById('awardContent').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        function formatAwardLevel(level) {
            const classes = {
                'truong': 'badge badge-primary',
                'huyen': 'badge badge-success',
                'tinh': 'badge in-progress',
                'quocgia': 'badge badge-danger'
            };

            const labels = {
                'truong': 'Cấp trường',
                'huyen': 'Cấp huyện',
                'tinh': 'Cấp tỉnh',
                'quocgia': 'Cấp quốc gia'
            };

            const className = classes[level] || '';
            const label = labels[level] || level;
            return `<span class="badge ${className}">${label}</span>`;
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN');
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Thêm khen thưởng';
            document.getElementById('awardForm').reset();
            document.getElementById('maKhenThuong').value = '';

            // Load danh sách học sinh vào select
            const select = document.getElementById('maHS');
            select.innerHTML = '<option value="">-- Chọn học sinh --</option>';
            students.forEach(student => {
                select.innerHTML += `<option value="${student.maHS}">${student.hoTen}</option>`;
            });

            document.getElementById('awardModal').style.display = 'block';
        }

        function openAddModalForStudent(maHS) {
            openAddModal();
            document.getElementById('maHS').value = maHS;
        }

        function editAward(maKhenThuong) {
            // Tìm thông tin khen thưởng
            let award = null;
            for (let student of students) {
                if (student.awards) {
                    award = student.awards.find(a => a.maKhenThuong == maKhenThuong);
                    if (award) {
                        // Lưu maHS để không cho thay đổi
                        award.maHS = student.maHS;
                        break;
                    }
                }
            }

            if (!award) {
                showAlert('Không tìm thấy thông tin khen thưởng', 'error');
                return;
            }

            document.getElementById('modalTitle').textContent = 'Sửa khen thưởng';
            document.getElementById('maKhenThuong').value = award.maKhenThuong;
            document.getElementById('lyDo').value = award.lyDo;
            document.getElementById('capKhenThuong').value = award.capKhenThuong;
            document.getElementById('ngayKhen').value = award.ngayKhen;
            document.getElementById('hinhThuc').value = award.hinhThuc || '';
            document.getElementById('linhVuc').value = award.linhVuc || '';

            // Load danh sách học sinh và disable - hiển thị tên học sinh
            const studentName = students.find(s => s.maHS == award.maHS)?.hoTen || 'Học sinh';
            const select = document.getElementById('maHS');
            select.innerHTML = `<option value="${award.maHS}" selected disabled>${studentName}</option>`;

            document.getElementById('awardModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('awardModal').style.display = 'none';
        }

        function saveAward() {
            const maKhenThuong = document.getElementById('maKhenThuong').value;
            const maHS = document.getElementById('maHS').value;
            const lyDo = document.getElementById('lyDo').value.trim();
            const capKhenThuong = document.getElementById('capKhenThuong').value;
            const ngayKhen = document.getElementById('ngayKhen').value;
            const hinhThuc = document.getElementById('hinhThuc').value;
            const linhVuc = document.getElementById('linhVuc').value;

            // Validate học sinh (khi thêm mới)
            if (!maKhenThuong && !maHS) {
                alert('Vui lòng chọn học sinh');
                return;
            }

            // Validate lý do khen thưởng
            if (!lyDo) {
                alert('Vui lòng nhập giải thưởng');
                return;
            }

            // Validate hình thức
            if (!hinhThuc) {
                alert('Vui lòng chọn hình thức khen thưởng');
                return;
            }

            // Validate cấp khen thưởng
            if (!capKhenThuong) {
                alert('Vui lòng chọn cấp khen thưởng');
                return;
            }

            // Validate lĩnh vực
            if (!linhVuc) {
                alert('Vui lòng chọn lĩnh vực');
                return;
            }

            // Validate ngày khen
            if (!ngayKhen) {
                alert('Vui lòng chọn ngày khen thưởng');
                return;
            }

            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;

            const formData = new FormData();
            formData.append('action', maKhenThuong ? 'update' : 'add');
            if (maKhenThuong) formData.append('maKhenThuong', maKhenThuong);
            if (!maKhenThuong) formData.append('maHS', maHS);
            formData.append('lyDo', lyDo);
            formData.append('capKhenThuong', capKhenThuong);
            formData.append('ngayKhen', ngayKhen);
            formData.append('hocKy', hocKy);
            formData.append('namHoc', namHoc);
            formData.append('hinhThuc', hinhThuc);
            formData.append('linhVuc', linhVuc);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        closeModal();
                        loadData();
                    } else {
                        showAlert(data.message, 'error');
                    }
                })
                .catch(error => {
                    showAlert('Lỗi: ' + error.message, 'error');
                });
        }

        function deleteAward(maKhenThuong) {
            if (!confirm('Bạn có chắc chắn muốn xóa khen thưởng này?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('maKhenThuong', maKhenThuong);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        loadData();
                    } else {
                        showAlert(data.message, 'error');
                    }
                })
                .catch(error => {
                    showAlert('Lỗi: ' + error.message, 'error');
                });
        }

        function showAlert(message, type) {
            const alertDiv = document.getElementById('alertMessage');
            alertDiv.innerHTML = `<div class="alert alert-${type} show">${message}</div>`;

            setTimeout(() => {
                alertDiv.innerHTML = '';
            }, 5000);
        }

        // Load data khi trang được tải
        window.onload = function() {
            loadData();
        };

        // Đóng modal khi click bên ngoài
        window.onclick = function(event) {
            const modal = document.getElementById('awardModal');
            if (event.target == modal) {
                closeModal();
            }
        };
    </script>
</body>

</html>