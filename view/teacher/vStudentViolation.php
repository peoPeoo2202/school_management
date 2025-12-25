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
require_once(__DIR__ . '/../../model/mStudentViolation.php');

$mConnect = new mConnect();
$conn = $mConnect->mConnect();

if (!$conn) {
    die("Kết nối thất bại!");
}

$model = new ModelStudentViolation($conn);

// Load initial data for page display
$data = [];
$classes = $model->getClassesByTeacher($maGV);

if (empty($classes)) {
    $data['error'] = 'Bạn chưa được phân công làm giáo viên chủ nhiệm lớp nào';
} else {
    // Lấy maLop từ URL hoặc lớp đầu tiên
    $maLop = intval($_GET['maLop'] ?? 0);
    if ($maLop == 0) {
        $maLop = $classes[0]['maLop'];
    }

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
    <title>Quản lý Vi phạm - Hệ thống Quản lý</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>

    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">
            <div class="header-section">
                <div class="header-left">
                    <div class="header-left-icon">
                        <h2><i class="fas fa-exclamation-triangle"></i></h2>
                        <h2>Quản lý Vi phạm</h2>
                    </div>
                    <p>Quản lý vi phạm cho học sinh</p>
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
                            <i class="fas fa-filter"></i> Bộ lọc
                        </h2>
                        
                    </div>

                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Học kỳ</label>
                            <select id="hocKy" class="form-select" onchange="loadData()">
                                <option value="1" <?php echo ($data['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo ($data['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Năm học</label>
                            <select id="namHoc" class="form-select" onchange="loadData()">
                                <?php
                                $currentYear = date('Y');
                                for ($i = 0; $i < 5; $i++) {
                                    $startYear = $currentYear - $i - 1;
                                    $endYear = $currentYear - $i;
                                    $namHocOption = $startYear . '-' . $endYear;
                                    $selected = ($namHocOption == $data['namHoc']) ? 'selected' : '';
                                    echo "<option value='$namHocOption' $selected>$namHocOption</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="filter-actions-button">
                            <button type="button" class="btn btn-secondary" onclick="openAddModal(0,'')">
                                <i class="fas fa-plus"></i> Thêm vi phạm
                            </button>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="maLop" value="<?php echo $data['currentClassId']; ?>">



                <div id="violationContent">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Đang tải dữ liệu...</p>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
    </div>

    <!-- Modal thêm vi phạm -->
    <div id="addModal" class="common-modal">
        <div class="assign-homework-modal-dialog">
            <div class="common-modal-header">
                <h5 class="common-modal-title">
                    <i class="fas fa-plus-circle"></i> Thêm vi phạm
                </h5>
                <button type="button" class="btn-close-modal" onclick="closeAddModal()">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>

            <div class="common-modal-body">
                <div id="addForm">
                    <div class="form-group mb-3">
                        <label class="form-label">Học sinh <span style="color: red;">*</span></label>
                        <select class="form-select" id="addMaHS">
                            <option value="">-- Chọn học sinh --</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Loại vi phạm <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="addLoaiViPham"
                            placeholder="VD: Nói chuyện trong giờ học, Đi trễ...">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Nội dung vi phạm</label>
                        <textarea class="form-control" id="addNoiDungViPham"
                            placeholder="Mô tả chi tiết về vi phạm..."></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Mức độ vi phạm <span style="color: rRed;">*</span></label>
                        <select class="form-select" id="addMucDoViPham">
                            <option value="">-- Chọn mức độ --</option>
                            <option value="Nhẹ">Nhẹ</option>
                            <option value="Trung bình">Trung bình</option>
                            <option value="Nặng">Nặng</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Hình thức xử lý <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="addHinhThucXuLy"
                            placeholder="VD: Nhắc nhở, Cảnh cáo, Kiểm điểm...">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Ngày vi phạm <span style="color: red;">*</span></label>
                        <input type="date" class="form-control" id="addNgayViPham">
                    </div>
                </div>
            </div>

            <div class="common-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" onclick="addViolation()">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </div>
    </div>

    <!-- Modal sửa vi phạm -->
    <div id="editModal" class="common-modal">
        <div class="assign-homework-modal-dialog">
            <div class="common-modal-header">
                <h5 class="common-modal-title">
                    <i class="fas fa-edit"></i> Chỉnh sửa vi phạm
                </h5>
                <button type="button" class="btn-close-modal" onclick="closeEditModal()">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>

            <div class="common-modal-body">
                <div id="editForm">
                    <input type="hidden" id="editMaViPham">

                    <div class="form-group mb-3">
                        <label class="form-label">Loại vi phạm <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="editLoaiViPham">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Nội dung vi phạm</label>
                        <textarea class="form-control" id="editNoiDungViPham"></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Mức độ vi phạm <span style="color: red;">*</span></label>
                        <select class="form-select" id="editMucDoViPham">
                            <option value="">-- Chọn mức độ --</option>
                            <option value="Nhẹ">Nhẹ</option>
                            <option value="Trung bình">Trung bình</option>
                            <option value="Nặng">Nặng</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Hình thức xử lý <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="editHinhThucXuLy">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Ngày vi phạm <span style="color: red;">*</span></label>
                        <input type="date" class="form-control" id="editNgayViPham">
                    </div>
                </div>
            </div>

            <div class="common-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" onclick="saveEdit()">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </div>

    <script>
        let students = [];
        let currentPage = 1;
        let itemsPerPage = 5; // giống award
        let totalItems = 0;

        function loadData() {
            const maLop = document.getElementById('maLop');
            const hocKy = document.getElementById('hocKy');
            const namHoc = document.getElementById('namHoc');

            if (!maLop || !hocKy || !namHoc) {
                console.error('Missing form elements');
                showAlert('Lỗi: Không tìm thấy các thành phần form', 'error');
                return;
            }

            document.getElementById('violationContent').innerHTML =
                '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Đang tải dữ liệu...</p></div>';

            const url = `../../controller/cStudentViolation.php?action=getViolations&maLop=${maLop.value}&hocKy=${hocKy.value}&namHoc=${encodeURIComponent(namHoc.value)}`;
            console.log('Fetching:', url);

            fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    console.log('Response status:', res.status);
                    console.log('Response headers:', res.headers.get('content-type'));

                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error(`HTTP ${res.status}: ${text}`);
                        });
                    }
                    return res.json();
                })
                .then(data => {
                    console.log('Data received:', data);
                    if (data.error) {
                        showAlert(data.error, 'error');
                        document.getElementById('violationContent').innerHTML = '';
                        return;
                    }
                    students = data.students || [];
                    currentPage = 1;
                    displayViolations();
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    showAlert('Lỗi khi tải dữ liệu: ' + err.message, 'error');
                    document.getElementById('violationContent').innerHTML = '';
                });
        }

        function searchStudent() {
            const input = document.getElementById('searchInput');
            const filter = (input?.value || '').toLowerCase().trim();

            const table = document.querySelector('.common-table');
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const tds = row.querySelectorAll('td');
                const nameCell = tds[1]; // Họ và tên
                const loaiCell = tds[2]; // Loại vi phạm

                const nameText = (nameCell?.textContent || '').toLowerCase();
                const loaiText = (loaiCell?.textContent || '').toLowerCase();

                if (!filter || nameText.includes(filter) || loaiText.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const searchResult = document.getElementById('searchResult');
            if (searchResult) {
                searchResult.textContent = filter ? `Tìm thấy ${visibleCount} kết quả` : '';
            }
        }
        function displayViolations() {
            const container = document.getElementById('violationContent');
            if (!container) return;

            if (!students || students.length === 0) {
                container.innerHTML = '<div class="loading"><p>Không có dữ liệu học sinh</p></div>';
                return;
            }

            // Flatten: gom tất cả vi phạm thành 1 mảng
            const allViolations = [];
            students.forEach(st => {
                if (Array.isArray(st.violations) && st.violations.length > 0) {
                    st.violations.forEach(v => {
                        allViolations.push({
                            ...v,
                            studentName: st.hoTen,
                            maHS: st.maHS
                        });
                    });
                }
            });

            totalItems = allViolations.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);

            // đảm bảo currentPage hợp lệ
            if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            // slice dữ liệu trang hiện tại
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const currentViolations = allViolations.slice(startIndex, endIndex);

            let html = `
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-exclamation-triangle"></i> Danh sách vi phạm
                    </h2>
                    <div class="search-container">
                    <span class="search-result-info" id="searchResult"></span>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Tìm kiếm theo tên hoặc mã học sinh" onkeyup="searchStudent()">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
                   
                </div>

                <div class="table-container">
                    <table class="common-table">
                        <thead>
                            <tr>
                                <th class="small-cell">STT</th>
                                <th>Họ và tên</th>
                                <th class="normal-cell">Loại vi phạm</th>
                                <th>Nội dung</th>
                                <th class="normal-cell">Mức độ</th>
                                <th class="normal-cell">Hình thức xử lý</th>
                                <th class="small-cell">Ngày vi phạm</th>
                                <th class="small-cell">Học kỳ</th>
                                <th class="normal-cell">Năm học</th>
                                <th class="center">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody class="common-table-body">
            `;

            if (totalItems === 0) {
                html += `
                <tr>
                    <td colspan="10" style="text-align:center;color:#999;font-style:italic;padding:18px;">
                        Chưa có vi phạm nào trong học kỳ/năm học này.
                    </td>
                </tr>
            `;
            } else {
                currentViolations.forEach((v, idx) => {
                    const rowIndex = startIndex + idx + 1;
                    const badgeInfo = getViolationBadge(v.mucDoViPham);

                    html += `
                    <tr class="table-normal-text">
                        <td class="small-cell"><p>${rowIndex}</p></td>

                        <td>
                            <div class="request-description">
                                <span class="request-des-title">${escapeHtml(v.studentName || '')}</span>
                            </div>
                        </td>

                        <td class="normal-cell">${escapeHtml(v.loaiViPham || '-')}</td>

                        <td class="table-strong-text">${escapeHtml(v.noiDungViPham || '-')}</td>

                        <td class="normal-cell">
                            <span class="badge ${badgeInfo.className}">${badgeInfo.label}</span>
                        </td>

                        <td class="normal-cell">${escapeHtml(v.hinhThucXuLy || '-')}</td>

                        <td class="small-cell">${formatDate(v.ngayViPham)}</td>

                        <td class="small-cell">${escapeHtml(String(v.hocKy ?? '-') )}</td>

                        <td class="normal-cell">${escapeHtml(v.namHoc || '-')}</td>

                        <td class="action-cell">
                            <div class="action-buttons">
                                <button class="btn-view"
                                        onclick="editViolation(${v.maViPham})"
                                        title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-delete"
                                        onclick="deleteViolation(${v.maViPham})"
                                        title="Xoá">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                });
            }

            html += `
                        </tbody>
                    </table>
                </div>
        `;

            // pagination giống award
            if (totalItems > 0) {
                html += renderViolationPagination(totalPages);
            }

            html += `
            </div>
            `;

            container.innerHTML = html;
        }

        function renderViolationPagination(totalPages) {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, totalItems);

            let html = `<div class="pagination pagi-violation">`;

            // Prev
            if (currentPage > 1) {
                html += `<a href="javascript:changePage(${currentPage - 1})"><i class="fas fa-chevron-left"></i> Trước</a>`;
            } else {
                html += `<span class="disabled"><i class="fas fa-chevron-left"></i> Trước</span>`;
            }

            // Numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                html += `<a href="javascript:changePage(1)">1</a>`;
                if (startPage > 2) html += `<span>...</span>`;
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) html += `<span class="current-page">${i}</span>`;
                else html += `<a href="javascript:changePage(${i})">${i}</a>`;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) html += `<span>...</span>`;
                html += `<a href="javascript:changePage(${totalPages})">${totalPages}</a>`;
            }

            // Next
            if (currentPage < totalPages) {
                html += `<a href="javascript:changePage(${currentPage + 1})">Sau <i class="fas fa-chevron-right"></i></a>`;
            } else {
                html += `<span class="disabled">Sau <i class="fas fa-chevron-right"></i></span>`;
            }

            html += `</div>`;

            // Info
            html += `<div class="pagination-info">
            Hiển thị ${startIndex + 1} - ${endIndex} trong tổng số ${totalItems} vi phạm
        </div>`;

            return html;
        }

        function changePage(page) {
            currentPage = page;
            displayViolations();
            document.getElementById('violationContent')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        // =========================
        // BADGE MỨC ĐỘ (đồng bộ class)
        // =========================
        function getViolationBadge(level) {
            // chuẩn hoá
            const raw = (level || '').toString().trim().toLowerCase();

            // support dữ liệu có thể là: "Nhẹ", "Trung bình", "Nặng" hoặc "nhe", "trung-binh", "nang"
            if (raw === 'nhẹ' || raw === 'nhe') {
                return {
                    className: 'badge-warning',
                    label: 'Nhẹ'
                };
            }
            if (raw === 'trung bình' || raw === 'trung-binh' || raw === 'trungbinh') {
                return {
                    className: 'badge-warning',
                    label: 'Trung bình'
                };
            }
            if (raw === 'nặng' || raw === 'nang') {
                return {
                    className: 'badge-danger',
                    label: 'Nặng'
                };
            }
            return {
                className: 'badge-warning',
                label: level || '-'
            };
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '-';
            return date.toLocaleDateString('vi-VN');
        }

        // escape HTML để khỏi XSS khi render
        function escapeHtml(str) {
            return String(str ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        // =========================
        // MODAL FUNCTIONS
        // =========================
        function openAddModal(maHS = 0, hoTen = '') {
            const modal = document.getElementById('addModal');
            const selectMaHS = document.getElementById('addMaHS');
            
            // Load danh sách học sinh vào select
            selectMaHS.innerHTML = '<option value="">-- Chọn học sinh --</option>';
            students.forEach(student => {
                selectMaHS.innerHTML += `<option value="${student.maHS}">${student.hoTen}</option>`;
            });
            
            // Nếu có maHS được truyền vào, chọn học sinh đó
            if (maHS > 0) {
                selectMaHS.value = maHS;
            }
            
            // Reset các trường khác
            document.getElementById('addLoaiViPham').value = '';
            document.getElementById('addNoiDungViPham').value = '';
            document.getElementById('addMucDoViPham').value = '';
            document.getElementById('addHinhThucXuLy').value = '';
            document.getElementById('addNgayViPham').value = new Date().toISOString().split('T')[0];
            
            if (modal) modal.classList.add('show');
        }

        function closeAddModal() {
            const modal = document.getElementById('addModal');
            if (modal) modal.classList.remove('show');
        }

        function editViolation(maViPham) {
            const url = `../../controller/cStudentViolation.php?action=getDetail&maViPham=${maViPham}`;
            console.log('Edit URL:', url);

            fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    console.log('Edit Response status:', res.status);
                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error(`HTTP ${res.status}: ${text}`);
                        });
                    }
                    return res.json();
                })
                .then(data => {
                    console.log('Edit Data received:', data);
                    if (data.error) {
                        showAlert(data.error, 'error');
                        return;
                    }
                    const v = data.violation;
                    document.getElementById('editMaViPham').value = v.maViPham;
                    document.getElementById('editLoaiViPham').value = v.loaiViPham || '';
                    document.getElementById('editNoiDungViPham').value = v.noiDungViPham || '';
                    document.getElementById('editMucDoViPham').value = v.mucDoViPham || '';
                    document.getElementById('editHinhThucXuLy').value = v.hinhThucXuLy || '';
                    document.getElementById('editNgayViPham').value = v.ngayViPham || '';

                    const modal = document.getElementById('editModal');
                    if (modal) modal.classList.add('show');
                })
                .catch(err => {
                    console.error('Edit Fetch error:', err);
                    showAlert('Lỗi: ' + err.message, 'error');
                });
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            if (modal) modal.classList.remove('show');
        }

        function addViolation() {
            const maHS = document.getElementById('addMaHS').value;
            const loaiViPham = document.getElementById('addLoaiViPham').value.trim();
            const noiDungViPham = document.getElementById('addNoiDungViPham').value.trim();
            const mucDoViPham = document.getElementById('addMucDoViPham').value;
            const hinhThucXuLy = document.getElementById('addHinhThucXuLy').value.trim();
            const ngayViPham = document.getElementById('addNgayViPham').value;

            if (!maHS || !loaiViPham || !mucDoViPham || !hinhThucXuLy || !ngayViPham) {
                showAlert('Vui lòng điền đầy đủ thông tin', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('maHS', maHS);
            formData.append('loaiViPham', loaiViPham);
            formData.append('noiDungViPham', noiDungViPham);
            formData.append('mucDoViPham', mucDoViPham);
            formData.append('hinhThucXuLy', hinhThucXuLy);
            formData.append('ngayViPham', ngayViPham);
            formData.append('hocKy', document.getElementById('hocKy').value);
            formData.append('namHoc', document.getElementById('namHoc').value);

            fetch('../../controller/cStudentViolation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        closeAddModal();
                        loadData();
                    } else {
                        showAlert(data.message || 'Lỗi khi thêm vi phạm', 'error');
                    }
                })
                .catch(err => showAlert('Lỗi: ' + err.message, 'error'));
        }

        function saveEdit() {
            const maViPham = document.getElementById('editMaViPham').value;
            const loaiViPham = document.getElementById('editLoaiViPham').value.trim();
            const noiDungViPham = document.getElementById('editNoiDungViPham').value.trim();
            const mucDoViPham = document.getElementById('editMucDoViPham').value;
            const hinhThucXuLy = document.getElementById('editHinhThucXuLy').value.trim();
            const ngayViPham = document.getElementById('editNgayViPham').value;

            if (!loaiViPham || !mucDoViPham || !hinhThucXuLy || !ngayViPham) {
                showAlert('Vui lòng điền đầy đủ thông tin', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('maViPham', maViPham);
            formData.append('loaiViPham', loaiViPham);
            formData.append('noiDungViPham', noiDungViPham);
            formData.append('mucDoViPham', mucDoViPham);
            formData.append('hinhThucXuLy', hinhThucXuLy);
            formData.append('ngayViPham', ngayViPham);

            fetch('../../controller/cStudentViolation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        closeEditModal();
                        loadData();
                    } else {
                        showAlert(data.message || 'Lỗi khi cập nhật', 'error');
                    }
                })
                .catch(err => showAlert('Lỗi: ' + err.message, 'error'));
        }

        function deleteViolation(maViPham) {
            if (!confirm('Bạn có chắc chắn muốn xóa vi phạm này?')) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('maViPham', maViPham);

            fetch('../../controller/cStudentViolation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        loadData();
                    } else {
                        showAlert(data.message || 'Lỗi khi xóa', 'error');
                    }
                })
                .catch(err => showAlert('Lỗi: ' + err.message, 'error'));
        }

        function showAlert(message, type = 'success') {
            // TODO: implement alert notification
            if (type === 'success') {
                console.log('✓ ' + message);
                alert(message);
            } else {
                console.error('✗ ' + message);
                alert(message);
            }
        }

        // Load data khi trang được tải
        window.onload = function() {
            loadData();
        };

        // đóng modal khi click ngoài
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const addModal = document.getElementById('addModal');
            if (event.target === editModal) closeEditModal();
            if (event.target === addModal) closeAddModal();
        };
    </script>

</body>

</html>