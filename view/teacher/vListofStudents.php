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

// Load dữ liệu từ controller
require_once(__DIR__ . '/../../controller/cListofStudents.php');
require_once(__DIR__ . '/../../model/mConnect.php');

$connectModel = new mConnect();
$connection = $connectModel->mConnect();
$controller = new ControllerListofStudents($connection);

// Xử lý AJAX request cho chi tiết học sinh
if (isset($_GET['action']) && $_GET['action'] == 'getDetail' && isset($_GET['maHS'])) {
    header('Content-Type: application/json');
    $maHS = intval($_GET['maHS']);
    $result = $controller->getStudentDetail($maHS);
    echo json_encode($result);
    exit();
}

$maLop = isset($_GET['maLop']) ? $_GET['maLop'] : null;
$data = $controller->showStudents($maLop);

// Lấy thông tin từ session
$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';

// Xử lý phân trang
$studentsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalStudents = isset($data['students']) ? count($data['students']) : 0;
$totalPages = ceil($totalStudents / $studentsPerPage);
$offset = ($currentPage - 1) * $studentsPerPage;

// Lấy học sinh cho trang hiện tại
$studentsToDisplay = isset($data['students']) ? array_slice($data['students'], $offset, $studentsPerPage) : [];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách học sinh - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <!-- Header -->
            <div class="header-section">
                <div class="header-left">
                    <div class="header-left-icon">
                        <h2><i class="fas fa-users"></i></h2>
                        <h2>Danh sách lớp chủ nhiệm</h2>
                    </div>
                </div>
            </div>

            <!-- Main Card -->
            <div class="card">
                <?php if (isset($data['error'])): ?>
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-exclamation-circle"></i> Thông báo
                        </h2>
                    </div>
                    <div class="error-message">
                        <?php echo htmlspecialchars($data['error']); ?>
                    </div>
                <?php else: ?>
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-chalkboard-teacher"></i> Lớp <?php echo htmlspecialchars($data['classInfo']['tenLop']); ?>
                        </h2>
                        <div class="search-container">
                            <span class="search-result-info" id="searchResult"></span>
                            <div class="search-box">
                                <input type="text" id="searchInput" placeholder="Tìm kiếm theo tên hoặc mã học sinh" onkeyup="searchStudent()">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin lớp -->


                    <?php if (empty($data['students'])): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-slash"></i>
                            <p style="font-size: 18px; color: #999;">Lớp chưa có học sinh nào.</p>
                        </div>
                    <?php else: ?>
                        <!-- Search Box -->


                        <table class="common-table">
                            <thead>
                                <tr>
                                    <th class="small-cell">STT</th>
                                    <th class="small-cell">Mã HS</th>
                                    <th>Họ và tên</th>
                                    <th class="normal-cell">Ngày sinh</th>
                                    <th class="small-cell">Giới tính</th>
                                    <th class="center">Điểm TB</th>
                                    <th class="center">Hạnh kiểm</th>
                                    <th>Phụ huynh</th>
                                    <th class="normal-cell">SĐT</th>
                                    <th class="center">Trạng thái</th>
                                    <th class="center">Thao tác</th>
                                </tr>
                            </thead>

                            <tbody class="common-table-body">
                                <?php foreach ($studentsToDisplay as $index => $student):
                                    $stt = $offset + $index + 1;

                                    // Badge status theo mẫu (tuỳ ông xã đổi text/class)
                                    $statusClass = ($student['trangThaiHocTap'] == 'danghoc') ? 'badge-success' : 'cancelled';
                                    $statusText  = ($student['trangThaiHocTap'] == 'danghoc') ? 'Đang học' : 'Nghỉ học';
                                ?>
                                    <tr>
                                        <td class="small-cell">
                                            <p><?php echo $stt; ?></p>
                                        </td>

                                        <td class="small-cell">
                                            <p>#<?php echo htmlspecialchars($student['maHS']); ?></p>
                                        </td>

                                        <td>
                                            <div class="request-description">
                                                <span class="request-des-title">
                                                    <?php echo htmlspecialchars($student['hoTen']); ?>
                                                </span>
                                            </div>
                                        </td>

                                        <td class="normal-cell">
                                            <?php echo date('d/m/Y', strtotime($student['ngaySinh'])); ?>
                                        </td>

                                        <td class="small-cell">
                                            <?php echo htmlspecialchars($student['gioiTinh']); ?>
                                        </td>

                                        <td class="small-cell">
                                            <?php echo $student['diemTB'] ? number_format($student['diemTB'], 1) : '<span class="empty-info">Chưa có</span>'; ?>
                                        </td>

                                        <td class="center">
                                            <?php echo htmlspecialchars($student['hanhKiem']); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($student['tenPhuHuynh'] ?? 'N/A'); ?>
                                        </td>

                                        <td class="nowrap">
                                            <?php echo htmlspecialchars($student['sdtPhuHuynh'] ?? 'N/A'); ?>
                                        </td>

                                        <td class="center">
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td class="action-cell">
                                            <div class="action-buttons">
                                                <button type="button"
                                                    class="btn-view"
                                                    onclick="viewStudentDetail(<?php echo (int)$student['maHS']; ?>)"
                                                    title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>


                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <!-- Previous Button -->
                                <?php if ($currentPage > 1): ?>
                                    <a href="?page=<?php echo ($currentPage - 1); ?><?php echo isset($_GET['maLop']) ? '&maLop=' . $_GET['maLop'] : ''; ?>">
                                        <i class="fas fa-chevron-left"></i> Trước
                                    </a>
                                <?php else: ?>
                                    <span class="disabled"><i class="fas fa-chevron-left"></i> Trước</span>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $currentPage + 2);

                                if ($startPage > 1): ?>
                                    <a href="?page=1<?php echo isset($_GET['maLop']) ? '&maLop=' . $_GET['maLop'] : ''; ?>">1</a>
                                    <?php if ($startPage > 2): ?>
                                        <span>...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <?php if ($i == $currentPage): ?>
                                        <span class="current-page"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?><?php echo isset($_GET['maLop']) ? '&maLop=' . $_GET['maLop'] : ''; ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <span>...</span>
                                    <?php endif; ?>
                                    <a href="?page=<?php echo $totalPages; ?><?php echo isset($_GET['maLop']) ? '&maLop=' . $_GET['maLop'] : ''; ?>"><?php echo $totalPages; ?></a>
                                <?php endif; ?>

                                <!-- Next Button -->
                                <?php if ($currentPage < $totalPages): ?>
                                    <a href="?page=<?php echo ($currentPage + 1); ?><?php echo isset($_GET['maLop']) ? '&maLop=' . $_GET['maLop'] : ''; ?>">
                                        Sau <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">Sau <i class="fas fa-chevron-right"></i></span>
                                <?php endif; ?>
                            </div>

                            <!-- Pagination Info -->
                            <div class="pagination-info">
                                Hiển thị <?php echo $offset + 1; ?> - <?php echo min($offset + $studentsPerPage, $totalStudents); ?>
                                trong tổng số <?php echo $totalStudents; ?> học sinh
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Chi tiết học sinh -->
    <!-- MODAL CHI TIẾT HỌC SINH (THEO MODAL MẪU common-modal) -->
    <div class="common-modal" id="studentModal">
        <div class="assign-homework-modal-dialog">

            <!-- Header -->
            <div class="common-modal-header">
                <h5 class="common-modal-title">
                    <i class="fas fa-user-graduate"></i>
                    Thông tin chi tiết học sinh
                </h5>

                <button type="button"
                    class="btn-close-modal"
                    onclick="closeModal()">
                    <i class="fa-solid fa-x"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="common-modal-body" id="modalBody">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Đang tải thông tin...</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="common-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Đóng
                </button>
            </div>

        </div>
    </div>

    <script>
        // =========================
        // SEARCH (common-table)
        // =========================
        function searchStudent() {
            const input = document.getElementById('searchInput');
            const filter = (input?.value || '').toLowerCase().trim();

            const table = document.querySelector('.common-table');
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const tds = row.querySelectorAll('td');
                const maHSCell = tds[1]; // Mã HS
                const nameCell = tds[2]; // Họ và tên

                const maHSText = (maHSCell?.textContent || '').toLowerCase();
                const nameText = (nameCell?.textContent || '').toLowerCase();

                if (!filter || maHSText.includes(filter) || nameText.includes(filter)) {
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

        // =========================
        // MODAL: open + fetch detail
        // =========================
        function openModal() {
            const modal = document.getElementById('studentModal');
            if (!modal) return;
            modal.classList.add('show');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('studentModal');
            if (!modal) return;
            modal.classList.remove('show');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function viewStudentDetail(maHS) {
            const modalBody = document.getElementById('modalBody');

            openModal();

            if (modalBody) {
                modalBody.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Đang tải thông tin...</p>
                </div>
            `;
            }

            fetch(`?action=getDetail&maHS=${encodeURIComponent(maHS)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data || data.error) {
                        if (modalBody) {
                            modalBody.innerHTML = `
                            <div class="error-message">
                                ${data?.error ? data.error : 'Không có dữ liệu trả về'}
                            </div>
                        `;
                        }
                        return;
                    }
                    displayStudentDetail(data);
                })
                .catch(() => {
                    if (modalBody) {
                        modalBody.innerHTML = `
                        <div class="error-message">
                            Không thể tải thông tin học sinh
                        </div>
                    `;
                    }
                });
        }

        // =========================
        // RENDER DETAIL
        // =========================
        function displayStudentDetail(data) {
            const modalBody = document.getElementById('modalBody');
            if (!modalBody) return;

            const student = data.student || {};
            const grades = Array.isArray(data.grades) ? data.grades : [];
            const violations = Array.isArray(data.violations) ? data.violations : [];
            const awards = Array.isArray(data.awards) ? data.awards : [];

            const statusHtml = `
            <span class="badge ${student.trangThaiHocTap == 'danghoc' ? 'badge-success' : 'badge-danger'}">
                ${student.trangThaiHocTap == 'danghoc' ? 'Đang học' : 'Nghỉ học'}
            </span>
        `;

            let html = `
            <!-- Thông tin cá nhân -->
                <div class="info-modal-section">
                    <h3><i class="fas fa-id-card"></i> Thông tin cá nhân: </h3>
                    <div class="info-grid detail-info-student">
                        <div class="info-item">
                            <span class="info-label">Họ và tên: </span>
                            <span class="info-value">${escapeHtml(student.hoTen || 'N/A')}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Ngày sinh: </span>
                            <span class="info-value">${formatDate(student.ngaySinh)}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Giới tính: </span>
                            <span class="info-value">${escapeHtml(student.gioiTinh || 'N/A')}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Lớp: </span>
                            <span class="info-value">${escapeHtml(student.tenLop || 'N/A')} - Khối ${escapeHtml(student.khoiLop || 'N/A')}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">GVCN: </span>
                            <span class="info-value">${escapeHtml(student.tenGVCN || 'N/A')}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Địa chỉ: </span>
                            <span class="info-value">${escapeHtml(student.diaChi || 'N/A')}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Trạng thái: </span>
                            <span class="info-value">${statusHtml}</span>
                        </div>
                    </div>
                </div>   

            <!-- Thông tin phụ huynh -->
            <div class="info-modal-section">
                <h3><i class="fas fa-users"></i> Thông tin phụ huynh</h3>
                <div class="info-grid detail-info-student">
                    <div class="info-item">
                        <span class="info-label">Họ tên: </span>
                        <span class="info-value">${escapeHtml(student.tenPhuHuynh || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Số điện thoại: </span>
                        <span class="info-value">${escapeHtml(student.sdtPhuHuynh || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email: </span>
                        <span class="info-value">${escapeHtml(student.emailPhuHuynh || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Địa chỉ: </span>
                        <span class="info-value">${escapeHtml(student.diaChiPhuHuynh || 'N/A')}</span>
                    </div>
                </div>
            </div>

            <!-- Kết quả học tập -->
            <div class="info-modal-section ">
                <h3><i class="fas fa-graduation-cap"></i> Kết quả học tập</h3>
                ${grades.length > 0 ? displayGrades(grades) : '<p class="info-value non-detail-grade">Chưa có dữ liệu điểm</p>'}
            </div>
        `;

            // Khen thưởng
            if (awards.length > 0) {
                html += `
                <div class="info-modal-section">
                    <h3><i class="fas fa-award"></i> Khen thưởng (${awards.length})</h3>
                    <div class="modal-note-list">
                    ${awards.map(award => `
                        <div class="list-item">
                            <div class="list-item-header">
                                <span>${escapeHtml(award.noiDung || 'N/A')}</span>
                                <span class="badge badge-success">${escapeHtml(award.capKhenThuong || '')}</span>
                            </div>
                            <div class="list-item-content">
                               <span> <i class="fas fa-calendar"></i> <strong>Ngày khen thưởng: </strong> <p>${formatDate(award.ngayKhenThuong)}</p></span>
                                <span><i class="fas fa-certificate"></i> <strong>Hình thức khen thưởng: </strong> <p>${escapeHtml(award.hinhThuc || 'N/A')}</p></span>
                                <span><i class="fas fa-tag"></i> <strong>Lĩnh vực: </strong> <p>${escapeHtml(award.linhVuc || 'N/A')}</p></span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            }

            // Vi phạm
            if (violations.length > 0) {
                html += `
                <div class="info-modal-section">
                    <h3><i class="fas fa-exclamation-triangle"></i> Vi phạm (${violations.length})</h3>
                    <div class="modal-note-list">
                    ${violations.map(vp => `
                        <div class="list-item">
                            <div class="list-item-header">
                                <span>${escapeHtml(vp.loaiViPham || 'N/A')}</span>
                                <span class="badge ${vp.mucDoViPham === 'Nang' ? 'badge-danger' : 'badge-warning'}">
                                    ${escapeHtml(vp.mucDoViPham || '')}
                                </span>
                            </div>
                            <div class="list-item-content">
                               <span><i class="fas fa-calendar"></i> <strong>Ngày vi phạm: </strong> <p>${formatDate(vp.ngayViPham)}</p></span> 
                                <span><i class="fas fa-gavel"></i><strong> Hình thức xử lý: </strong> <p>${escapeHtml(vp.hinhThucXuLy || 'N/A')}</p></span>
                                <span><strong>Nội dung:</strong> <p>${escapeHtml(vp.noiDungViPham || 'N/A')}</p></span>
                            </div>
                        </div>
                    `).join('')}
                    </div>
                    
                </div>
            `;
            }

            modalBody.innerHTML = html;
        }

        // =========================
        // GRADES
        // =========================
        function displayGrades(grades) {
            const hk1 = grades.filter(g => String(g.hocKy) === '1');
            const hk2 = grades.filter(g => String(g.hocKy) === '2');

            let html = '';

            if (hk1.length > 0) {
                html += '<h4 style="margin: 15px 0 10px 0;">Học kỳ 1</h4>';
                html += generateGradesTable(hk1);
            }

            if (hk2.length > 0) {
                html += '<h4 style="margin: 15px 0 10px 0;">Học kỳ 2</h4>';
                html += generateGradesTable(hk2);
            }

            return html || '<p class="info-value">Chưa có dữ liệu điểm</p>';
        }

        function generateGradesTable(grades) {
            return `
            <table class="grades-table">
                <thead>
                    <tr>
                        <th>Môn học</th>
                        <th>TX1</th>
                        <th>TX2</th>
                        <th>TX3</th>
                        <th>TX4</th>
                        <th>Giữa kỳ</th>
                        <th>Cuối kỳ</th>
                        <th>TB</th>
                    </tr>
                </thead>
                <tbody>
                    ${grades.map(grade => `
                        <tr>
                            <td style="text-align:left;">${escapeHtml(grade.tenMonHoc || '')}</td>
                            <td>${toDash(grade.diemTX1)}</td>
                            <td>${toDash(grade.diemTX2)}</td>
                            <td>${toDash(grade.diemTX3)}</td>
                            <td>${toDash(grade.diemTX4)}</td>
                            <td>${toDash(grade.diemGiuaKy)}</td>
                            <td>${toDash(grade.diemCuoiKy)}</td>
                            <td><strong>${toDash(grade.tbDiem)}</strong></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        }

        // =========================
        // HELPERS
        // =========================
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'N/A';
            return date.toLocaleDateString('vi-VN');
        }

        function toDash(val) {
            return (val === null || val === undefined || val === '') ? '-' : escapeHtml(String(val));
        }

        function escapeHtml(str) {
            return String(str)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        // =========================
        // Close when click outside dialog
        // =========================
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('studentModal');
            const dialog = modal?.querySelector('.assign-homework-modal-dialog');
            if (!modal || !dialog) return;

            if (event.target === modal) closeModal();
        });

        // Close with ESC
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>


</body>

</html>