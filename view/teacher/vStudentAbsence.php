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

// Check if $data is not set, load from controller/model
if (!isset($data)) {
    require_once(__DIR__ . '/../../model/mStudentAbsence.php');
    require_once(__DIR__ . '/../../model/mConnect.php');

    $mConnect = new mConnect();
    $conn = $mConnect->mConnect();

    if (!$conn) {
        die("Kết nối thất bại!");
    }

    $model = new ModelStudentAbsence($conn);

    // Kiểm tra quyền truy cập
    if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] != 'giaovien') {
        header('Location: ../../public/index.php');
        exit();
    }

    $maGV = $_SESSION['maGV'] ?? null;

    // AJAX POST requests
    if (isset($_POST['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'add':
                $maHS = $_POST['maHS'] ?? null;
                $ngayNghi = $_POST['ngayNghi'] ?? null;
                $hocKy = $_POST['hocKy'] ?? null;
                $namHoc = $_POST['namHoc'] ?? null;
                $loaiNghi = $_POST['loaiNghi'] ?? 'cophep';
                $lyDo = $_POST['lyDo'] ?? '';

                if (!$maHS || !$ngayNghi || !$hocKy || !$namHoc) {
                    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc'], JSON_UNESCAPED_UNICODE);
                } else {
                    $result = $model->addAbsence($maHS, $ngayNghi, $hocKy, $namHoc, $loaiNghi, $lyDo, $maGV);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                }
                break;

            case 'update':
                $maNghiHoc = $_POST['maNghiHoc'] ?? null;
                $ngayNghi = $_POST['ngayNghi'] ?? null;
                $loaiNghi = $_POST['loaiNghi'] ?? 'cophep';
                $lyDo = $_POST['lyDo'] ?? '';

                if (!$maNghiHoc || !$ngayNghi) {
                    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc'], JSON_UNESCAPED_UNICODE);
                } else {
                    $result = $model->updateAbsence($maNghiHoc, $ngayNghi, $loaiNghi, $lyDo);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                }
                break;

            case 'delete':
                $maNghiHoc = $_POST['maNghiHoc'] ?? null;

                if (!$maNghiHoc) {
                    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc'], JSON_UNESCAPED_UNICODE);
                } else {
                    $result = $model->deleteAbsence($maNghiHoc);
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                }
                break;

            case 'getDetails':
                $maHS = $_POST['maHS'] ?? null;
                $hocKy = $_POST['hocKy'] ?? null;
                $namHoc = $_POST['namHoc'] ?? null;

                if (!$maHS || !$hocKy || !$namHoc) {
                    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc'], JSON_UNESCAPED_UNICODE);
                } else {
                    $details = $model->getAbsenceDetails($maHS, $hocKy, $namHoc);
                    echo json_encode(['success' => true, 'data' => $details], JSON_UNESCAPED_UNICODE);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
        }

        $conn->close();
        exit();
    }

    // Load page data
    $classes = $model->getClassesByTeacher($maGV);

    $maLop = $_GET['maLop'] ?? null;
    if (!$maLop) {
        if (empty($classes)) {
            $data = ['error' => 'Bạn chưa được phân công làm giáo viên chủ nhiệm lớp nào'];
        } else {
            $maLop = $classes[0]['maLop'];
        }
    }

    if ($maLop) {
        $classInfo = $model->getClassInfo($maLop);

        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            $data = ['error' => 'Bạn không có quyền xem thông tin của lớp này'];
        } else {
            $currentYear = date('Y');
            $defaultNamHoc = ($currentYear - 1) . '-' . $currentYear;
            $defaultHocKy = (date('m') <= 6) ? 2 : 1;

            $namHoc = $_GET['namHoc'] ?? $defaultNamHoc;
            $hocKy = $_GET['hocKy'] ?? $defaultHocKy;

            $students = $model->getStudentAbsences($maLop, $hocKy, $namHoc);
            $availableYears = $model->getAvailableYears();

            $data = [
                'classes' => $classes,
                'classInfo' => $classInfo,
                'currentClassId' => $maLop,
                'hocKy' => $hocKy,
                'namHoc' => $namHoc,
                'students' => $students,
                'maGV' => $maGV,
                'availableYears' => $availableYears
            ];
        }
    }

    $conn->close();
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quản lý nghỉ học - Hệ thống Quản lý</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">
            <div class="header-section">
                <div class="header-left">
                    <div class="header-left-icon">
                        <h2><i class="fas fa-calendar-times"></i></h2>
                        <h2>Quản lý nghỉ học</h2>
                    </div>
                    <p>Quản lý nghỉ học cho học sinh</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <?php if (isset($data['error'])): ?>
                <div class="card">
                    <div class="alert alert-error show">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($data['error']); ?>
                    </div>
                </div>
            <?php else: ?>

                <!-- CARD FILTER -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-filter"></i> Bộ lọc
                        </h2>
                    </div>

                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Học kỳ</label>
                            <select id="hocKyFilter" class="form-select" onchange="filterByPeriod()">
                                <option value="1" <?php echo ($data['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo ($data['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Năm học</label>
                            <select id="namHocFilter" class="form-select" onchange="filterByPeriod()">
                                <?php
                                if (!empty($data['availableYears'])) {
                                    foreach ($data['availableYears'] as $year) {
                                        $selected = ($data['namHoc'] == $year) ? 'selected' : '';
                                        echo "<option value='$year' $selected>$year</option>";
                                    }
                                } else {
                                    $currentYear = date('Y');
                                    for ($i = 0; $i < 5; $i++) {
                                        $startYear = $currentYear - $i - 1;
                                        $endYear = $currentYear - $i;
                                        $yearValue = $startYear . '-' . $endYear;
                                        $selected = ($data['namHoc'] == $yearValue) ? 'selected' : '';
                                        echo "<option value='$yearValue' $selected>$yearValue</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="filter-actions-button">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary" onclick="filterByPeriod()">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="maLop" value="<?php echo htmlspecialchars($data['currentClassId']); ?>" />

                <!-- CARD TABLE -->
                <div id="absenceContent">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Đang tải dữ liệu...</p>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL ADD -->
    <div id="addModal" class="common-modal">
        <div class="assign-homework-modal-dialog">
            <div class="common-modal-header">
                <h5 class="common-modal-title">
                    <i class="fas fa-plus-circle"></i> Thêm nghỉ học
                </h5>
                <button type="button" class="btn-close-modal" onclick="closeAddModal()">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>

            <div class="common-modal-body">
                <form id="addForm">
                    <input type="hidden" id="addMaHS" name="maHS">
                    <input type="hidden" id="addHocKy" name="hocKy" value="<?php echo htmlspecialchars($data['hocKy'] ?? 1); ?>">
                    <input type="hidden" id="addNamHoc" name="namHoc" value="<?php echo htmlspecialchars($data['namHoc'] ?? ''); ?>">

                    <div class="mb-3">
                        <label class="form-label">Học sinh</label>
                        <input type="text" id="addHoTen" class="form-control" readonly style="background:#f8f9fa;font-weight:600;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ngày nghỉ <span style="color:red">*</span></label>
                        <input type="date" name="ngayNghi" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Loại nghỉ <span style="color:red">*</span></label>
                        <select name="loaiNghi" class="form-select" required>
                            <option value="cophep">Có phép</option>
                            <option value="khongphep">Không phép</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lý do</label>
                        <textarea name="lyDo" class="form-control" placeholder="Nhập lý do nghỉ..."></textarea>
                    </div>
                </form>
            </div>

            <div class="common-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" onclick="addAbsence()">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DETAILS -->
    <div id="detailsModal" class="common-modal">
        <div class="assign-homework-modal-dialog">
            <div class="common-modal-header">
                <h5 class="common-modal-title">
                    <i class="fas fa-list"></i> Chi tiết nghỉ học
                </h5>
                <button type="button" class="btn-close-modal" onclick="closeDetailsModal()">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>

            <div class="common-modal-body">
                <h4 id="detailsStudentName" style="margin:0 0 12px 0;"></h4>
                <div id="detailsList"></div>
            </div>

            <div class="common-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDetailsModal()">
                    <i class="fas fa-times"></i> Đóng
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div id="editModal" class="common-modal">
        <div class="assign-homework-modal-dialog">
            <div class="common-modal-header">
                <h5 class="common-modal-title">
                    <i class="fas fa-edit"></i> Sửa nghỉ học
                </h5>
                <button type="button" class="btn-close-modal" onclick="closeEditModal()">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>

            <div class="common-modal-body">
                <form id="editForm">
                    <input type="hidden" id="editMaNghiHoc" name="maNghiHoc">

                    <div class="mb-3">
                        <label class="form-label">Ngày nghỉ <span style="color:red">*</span></label>
                        <input type="date" id="editNgayNghi" name="ngayNghi" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Loại nghỉ <span style="color:red">*</span></label>
                        <select id="editLoaiNghi" name="loaiNghi" class="form-select" required>
                            <option value="cophep">Có phép</option>
                            <option value="khongphep">Không phép</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lý do</label>
                        <textarea id="editLyDo" name="lyDo" class="form-control" placeholder="Nhập lý do nghỉ..."></textarea>
                    </div>
                </form>
            </div>

            <div class="common-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="btn btn-primary" onclick="updateAbsence()">
                    <i class="fas fa-save"></i> Cập nhật
                </button>
            </div>
        </div>
    </div>

    <script>
        // Data from PHP
        const students = <?php echo json_encode($data['students'] ?? [], JSON_UNESCAPED_UNICODE); ?>;
        let filteredStudents = [...students];
        const maLop = <?php echo json_encode($data['currentClassId'] ?? ''); ?>;

        let currentPage = 1;
        const itemsPerPage = 5;

        // giữ keyword search khi render lại (do innerHTML)
        window.__searchKeyword = '';

        // ============ RENDER TABLE + PAGINATION ============
        function renderAbsenceTable() {
            const container = document.getElementById('absenceContent');
            if (!container) return;

            // Header + search (luôn hiển thị)
            const headerHtml = `
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-users"></i> Danh sách nghỉ học - Lớp <?php echo htmlspecialchars($data['classInfo']['tenLop'] ?? ''); ?>
                    </h2>

                    <div class="search-container">
                        <span class="search-result-info" id="searchResult"></span>
                        <div class="search-box">
                            <input type="text"
                                id="searchInput"
                                placeholder="Tìm kiếm theo tên hoặc mã học sinh"
                                onkeyup="searchStudent()">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                </div>
        `;

            // Nếu không có dữ liệu sau filter/search
            if (!filteredStudents || filteredStudents.length === 0) {
                container.innerHTML = headerHtml + `
                <div style="padding:40px;text-align:center;">
                    <i class="fas fa-inbox" style="font-size:56px;color:#ddd;margin-bottom:14px;"></i>
                    <h3 style="color:#666;margin:0 0 8px 0;">Không có dữ liệu</h3>
                    <p style="color:#999;margin:0;">Không có học sinh phù hợp trong học kỳ/năm học đã chọn.</p>
                </div>
            </div>`;

                // restore keyword
                const input = document.getElementById('searchInput');
                if (input) input.value = window.__searchKeyword || '';

                // info
                const info = document.getElementById('searchResult');
                if (info && window.__searchKeyword) {
                    info.textContent = `Tìm thấy 0 học sinh`;
                }
                return;
            }

            const totalItems = filteredStudents.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);

            // đảm bảo currentPage hợp lệ
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
            const pageItems = filteredStudents.slice(startIndex, endIndex);

            let html = headerHtml + `
           
                <table class="common-table">
                    <thead>
                        <tr>
                            <th class="small-cell">STT</th>
                            <th >Mã học sinh</th>
                            <th class="large-cell">Họ và tên</th>
                            <th>Nghỉ có phép</th>
                            <th>Nghỉ không phép</th>
                            <th>Tổng nghỉ</th>
                            <th class="center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="common-table-body">
        `;

            pageItems.forEach((st, idx) => {
                const rowIndex = startIndex + idx + 1;
                html += `
                <tr class="table-normal-text">
                    <td class="small-cell"><p>${rowIndex}</p></td>
                    <td ><p>${escapeHtml(st.maHS ?? '')}</p></td>
                    <td>
                        <div class="request-description">
                            <span class="request-des-title">${escapeHtml(st.hoTen || '')}</span>
                        </div>
                    </td>

                    <td><span class="badge badge-success">${st.soNghiCoPhep ?? 0}</span></td>
                    <td><span class="badge badge-danger">${st.soNghiKhongPhep ?? 0}</span></td>
                    <td><strong>${st.tongNghi ?? 0}</strong></td>

                    <td class="action-cell">
                        <div class="action-buttons">
                            <button type="button" class="btn-edit"
                                onclick="openAddModal(${st.maHS}, '${escapeJs(st.hoTen || '')}')"
                                title="Thêm nghỉ học">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="btn-view"
                                onclick="viewDetails(${st.maHS}, '${escapeJs(st.hoTen || '')}')"
                                title="Chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            });

            html += `
                    </tbody>
                </table>
        `;

            // pagination
            if (totalPages > 1) {
                html += renderPagination(totalPages, totalItems, startIndex, endIndex);
            } else {
                html += `<div class="pagination-info">Hiển thị 1 - ${totalItems} trong tổng số ${totalItems} học sinh</div>`;
            }

            html += `</div>`; // close card
            container.innerHTML = html;

            // restore keyword sau khi render
            const input = document.getElementById('searchInput');
            if (input) input.value = window.__searchKeyword || '';

            // search result text
            const resultInfo = document.getElementById('searchResult');
            if (resultInfo) {
                if (window.__searchKeyword) {
                    resultInfo.textContent = `Tìm thấy ${filteredStudents.length} học sinh`;
                } else {
                    resultInfo.textContent = '';
                }
            }
        }

        function renderPagination(totalPages, totalItems, startIndex, endIndex) {
            let html = `<div class="pagination pagi-absence">`;

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
            html += `<div class="pagination-info">
            Hiển thị ${startIndex + 1} - ${endIndex} trong tổng số ${totalItems} học sinh
        </div>`;

            return html;
        }

        function changePage(page) {
            currentPage = page;
            renderAbsenceTable();
            document.getElementById('absenceContent')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        // ============ SEARCH ============
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
                const maCell = tds[2]; // Mã HS

                const nameText = (nameCell?.textContent || '').toLowerCase();
                const maText = (maCell?.textContent || '').toLowerCase();

                if (!filter || nameText.includes(filter) || maText.includes(filter)) {
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

        // ============ FILTER ============
        function filterByPeriod() {
            const hocKy = document.getElementById('hocKyFilter')?.value || '1';
            const namHoc = document.getElementById('namHocFilter')?.value || '';
            window.location.href = `vStudentAbsence.php?maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}`;
        }

        // ============ MODALS ============
        function openAddModal(maHS, hoTen) {
            document.getElementById('addForm').reset();
            document.getElementById('addMaHS').value = maHS;
            document.getElementById('addHoTen').value = hoTen;

            const hocKyValue = document.getElementById('hocKyFilter')?.value || '<?php echo htmlspecialchars($data['hocKy'] ?? 1); ?>';
            const namHocValue = document.getElementById('namHocFilter')?.value || '<?php echo htmlspecialchars($data['namHoc'] ?? ''); ?>';
            document.getElementById('addHocKy').value = hocKyValue;
            document.getElementById('addNamHoc').value = namHocValue;

            document.getElementById('addModal').classList.add('show');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
        }

        async function addAbsence() {
            const form = document.getElementById('addForm');
            const formData = new FormData(form);
            formData.append('action', 'add');

            try {
                const res = await fetch('vStudentAbsence.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    alert(result.message);
                    closeAddModal();
                    location.reload();
                } else {
                    alert('Lỗi: ' + (result.message || 'Không thể thêm'));
                }
            } catch (e) {
                alert('Lỗi: ' + e.message);
            }
        }

        async function viewDetails(maHS, hoTen) {
            const hocKy = document.getElementById('hocKyFilter')?.value || '<?php echo htmlspecialchars($data['hocKy'] ?? 1); ?>';
            const namHoc = document.getElementById('namHocFilter')?.value || '<?php echo htmlspecialchars($data['namHoc'] ?? ''); ?>';

            const formData = new FormData();
            formData.append('action', 'getDetails');
            formData.append('maHS', maHS);
            formData.append('hocKy', hocKy);
            formData.append('namHoc', namHoc);

            try {
                const res = await fetch('vStudentAbsence.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (!result.success) {
                    alert('Lỗi: ' + (result.message || 'Không tải được chi tiết'));
                    return;
                }

                document.getElementById('detailsStudentName').textContent = hoTen;

                const list = document.getElementById('detailsList');
                const data = result.data || [];

                if (data.length === 0) {
                    list.innerHTML = `<p style="text-align:center;color:#999;margin:0;">Chưa có nghỉ học</p>`;
                } else {
                    list.innerHTML = data.map(item => {
                        const badgeClass = item.loaiNghi === 'cophep' ? 'badge-success' : 'badge-danger';
                        const badgeText = item.loaiNghi === 'cophep' ? 'Có phép' : 'Không phép';
                        return `
                        <div class="absence-item" style="display:flex;justify-content:space-between;align-items:center;background:#f9f9f9;padding:12px;border-radius:8px;margin-bottom:10px;">
                            <div style="flex:1;">
                                <div style="font-weight:600;color:#333;margin-bottom:6px;">
                                    ${new Date(item.ngayNghi).toLocaleDateString('vi-VN')}
                                    <span class="badge ${badgeClass}" style="margin-left:8px;">${badgeText}</span>
                                </div>
                                <div style="font-size:13px;color:#666;">${escapeHtml(item.lyDo || 'Không có lý do')}</div>
                            </div>
                            <div class="action-buttons" style="display:flex;gap:8px;">
                                <button type="button" class="btn-view" title="Sửa"
                                    onclick="editAbsence(${item.maNghiHoc}, '${item.ngayNghi}', '${item.loaiNghi}', '${escapeJs(item.lyDo || '')}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn-delete" title="Xóa"
                                    onclick="deleteAbsence(${item.maNghiHoc})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    }).join('');
                }

                document.getElementById('detailsModal').classList.add('show');
            } catch (e) {
                alert('Lỗi: ' + e.message);
            }
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.remove('show');
        }

        function editAbsence(maNghiHoc, ngayNghi, loaiNghi, lyDo) {
            document.getElementById('editMaNghiHoc').value = maNghiHoc;
            document.getElementById('editNgayNghi').value = ngayNghi;
            document.getElementById('editLoaiNghi').value = loaiNghi;
            document.getElementById('editLyDo').value = lyDo;

            closeDetailsModal();
            document.getElementById('editModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }

        async function updateAbsence() {
            const form = document.getElementById('editForm');
            const formData = new FormData(form);
            formData.append('action', 'update');

            try {
                const res = await fetch('vStudentAbsence.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    alert(result.message);
                    closeEditModal();
                    location.reload();
                } else {
                    alert('Lỗi: ' + (result.message || 'Không thể cập nhật'));
                }
            } catch (e) {
                alert('Lỗi: ' + e.message);
            }
        }

        async function deleteAbsence(maNghiHoc) {
            if (!confirm('Bạn có chắc chắn muốn xóa nghỉ học này?')) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('maNghiHoc', maNghiHoc);

            try {
                const res = await fetch('vStudentAbsence.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Lỗi: ' + (result.message || 'Không thể xóa'));
                }
            } catch (e) {
                alert('Lỗi: ' + e.message);
            }
        }

        // close modal when click outside
        window.onclick = function(e) {
            const modals = ['addModal', 'detailsModal', 'editModal'].map(id => document.getElementById(id));
            modals.forEach(m => {
                if (m && e.target === m) m.classList.remove('show');
            });
        };

        // helpers
        function escapeHtml(str) {
            return String(str ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", "&#039;");
        }

        function escapeJs(str) {
            return String(str ?? '').replaceAll('\\', '\\\\').replaceAll("'", "\\'");
        }

        // init
        renderAbsenceTable();
    </script>

</body>

</html>