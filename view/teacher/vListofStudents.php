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
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
        }

        .main-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .content-area {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
            overflow-x: hidden;
            height: 100vh;
            box-sizing: border-box;
        }

        .header-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
        }

        .header-left {
            display: flex;
            justify-content: space-between;
            flex-direction: column;
        }

        .header-left-icon {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #5081BE;
            font-weight: 600;
        }

        .header-left h2 {
            margin: 0;
            font-size: 24px;
        }

        .header-left p {
            color: #999;
            font-size: 14px;
            margin: 0;
        }

        .header-right {
            text-align: right;
        }

        .header-right .welcome-text {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: 500;
            margin-top: 8px;
        }

        .header-right .user-name {
            color: #5081BE;
            font-weight: 600;
            font-size: 16px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 24px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            padding-bottom: 16px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: #5081BE;
            font-size: 16px;
        }

        .class-info {
            background: linear-gradient(135deg, #5081BE15 0%, #4a6fa515 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #5081BE;
            text-align: left;
        }

        .class-info h3 {
            color: #5081BE;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
            text-align: left;
        }

        .class-info p {
            margin: 8px 0;
            color: #555;
            font-size: 14px;
            text-align: left;
        }

        .class-info strong {
            color: #333;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .students-table thead {
            background-color: #5081BE;
            color: white;
        }

        .students-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .students-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }

        .students-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .students-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status-active {
            background-color: #c8e6c9;
            color: #2e7d32;
        }

        .status-inactive {
            background-color: #ffcdd2;
            color: #c62828;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px 0;
        }

        .pagination a,
        .pagination span {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #5081BE;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background-color: #5081BE;
            color: white;
            border-color: #5081BE;
        }

        .pagination .current-page {
            background-color: #5081BE;
            color: white;
            border-color: #5081BE;
            cursor: default;
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination-info {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 15px;
        }

        /* Modal Styles */
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
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px 30px;
            background: linear-gradient(135deg, #5081BE 0%, #4a6fa5 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
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
            color: #ddd;
            transform: scale(1.1);
        }

        .modal-body {
            padding: 30px;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-section h3 {
            color: #5081BE;
            font-size: 16px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            font-size: 14px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
        }

        .grades-table th,
        .grades-table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }

        .grades-table th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #555;
        }

        .list-item {
            padding: 12px;
            background: #f9f9f9;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .list-item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .list-item-content {
            color: #666;
            line-height: 1.6;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .students-table tbody tr {
            cursor: pointer;
            transition: all 0.2s;
        }

        .students-table tbody tr:hover {
            background-color: #e3f2fd !important;
            transform: scale(1.01);
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .loading i {
            font-size: 32px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .main-wrapper {
                flex-direction: column;
            }

            .content-area {
                margin-left: 0;
                padding: 15px;
            }

            .header-section {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .header-right {
                text-align: center;
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
            <!-- Header -->
            <div class="header-section">
                <div class="header-left">
                    <div class="header-left-icon">
                        <h2><i class="fas fa-users"></i></h2>
                        <h2>Danh sách học sinh</h2>
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
                        <!-- <strong>⚠️ Lỗi:</strong>  -->
                        <?php echo htmlspecialchars($data['error']); ?>
                    </div>
                <?php else: ?>
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-chalkboard-teacher"></i> Lớp <?php echo htmlspecialchars($data['classInfo']['tenLop']); ?>
                        </h2>
                    </div>

                    <!-- Thông tin lớp -->
                    <div class="class-info">
                        <h3><i class="fas fa-info-circle"></i> Thông tin lớp học</h3>
                        <p><strong>Khối:</strong> <?php echo htmlspecialchars($data['classInfo']['khoiLop']); ?></p>
                        <p><strong>Sĩ số:</strong> <?php echo $data['classInfo']['siSo']; ?> học sinh</p>
                        <p><strong>Năm học:</strong> <?php echo htmlspecialchars($data['classInfo']['namHoc']); ?></p>
                        <p><strong>Học kỳ:</strong> <?php echo $data['hocKy']; ?></p>
                        <p><strong>Giáo viên chủ nhiệm:</strong> <?php echo htmlspecialchars($data['classInfo']['tenGVCN']); ?></p>
                    </div>

                    <?php if (empty($data['students'])): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-slash"></i>
                            <p style="font-size: 18px; color: #999;">Lớp chưa có học sinh nào.</p>
                        </div>
                    <?php else: ?>
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã HS</th>
                                    <th>Họ và tên</th>
                                    <th>Ngày sinh</th>
                                    <th>Giới tính</th>
                                    <th>Điểm TB</th>
                                    <th>Hạnh kiểm</th>
                                    <th>Phụ huynh</th>
                                    <th>SĐT</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($studentsToDisplay as $index => $student): 
                                    $stt = $offset + $index + 1;
                                ?>
                                    <tr onclick="viewStudentDetail(<?php echo $student['maHS']; ?>)">
                                        <td><?php echo $stt; ?></td>
                                        <td><?php echo htmlspecialchars($student['maHS']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($student['hoTen']); ?></strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($student['ngaySinh'])); ?></td>
                                        <td><?php echo htmlspecialchars($student['gioiTinh']); ?></td>
                                        <td><?php echo $student['diemTB'] ? number_format($student['diemTB'], 1) : 'Chưa có'; ?></td>
                                        <td><?php echo htmlspecialchars($student['hanhKiem']); ?></td>
                                        <td><?php echo htmlspecialchars($student['tenPhuHuynh'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['sdtPhuHuynh'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $student['trangThaiHocTap'] == 'danghoc' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $student['trangThaiHocTap'] == 'danghoc' ? 'Đang học' : 'Nghỉ học'; ?>
                                            </span>
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
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-graduate"></i> Thông tin chi tiết học sinh</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Đang tải thông tin...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewStudentDetail(maHS) {
            const modal = document.getElementById('studentModal');
            const modalBody = document.getElementById('modalBody');
            
            // Hiển thị modal và loading
            modal.style.display = 'block';
            modalBody.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Đang tải thông tin...</p>
                </div>
            `;
            
            // Gọi API lấy thông tin chi tiết
            fetch(`?action=getDetail&maHS=${maHS}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modalBody.innerHTML = `
                            <div class="error-message">
                                <strong>⚠️ Lỗi:</strong> ${data.error}
                            </div>
                        `;
                        return;
                    }
                    
                    displayStudentDetail(data);
                })
                .catch(error => {
                    modalBody.innerHTML = `
                        <div class="error-message">
                            <strong>⚠️ Lỗi:</strong> Không thể tải thông tin học sinh
                        </div>
                    `;
                });
        }

        function displayStudentDetail(data) {
            const student = data.student;
            const grades = data.grades;
            const violations = data.violations;
            const awards = data.awards;
            
            let html = `
                <!-- Thông tin cá nhân -->
                <div class="info-section">
                    <h3><i class="fas fa-id-card"></i> Thông tin cá nhân</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Họ và tên</span>
                            <span class="info-value">${student.hoTen}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Ngày sinh</span>
                            <span class="info-value">${formatDate(student.ngaySinh)}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Giới tính</span>
                            <span class="info-value">${student.gioiTinh}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Lớp</span>
                            <span class="info-value">${student.tenLop} - Khối ${student.khoiLop}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">GVCN</span>
                            <span class="info-value">${student.tenGVCN}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Địa chỉ</span>
                            <span class="info-value">${student.diaChi || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Trạng thái</span>
                            <span class="info-value">
                                <span class="status-badge ${student.trangThaiHocTap == 'danghoc' ? 'status-active' : 'status-inactive'}">
                                    ${student.trangThaiHocTap == 'danghoc' ? 'Đang học' : 'Nghỉ học'}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Thông tin phụ huynh -->
                <div class="info-section">
                    <h3><i class="fas fa-users"></i> Thông tin phụ huynh</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Họ tên</span>
                            <span class="info-value">${student.tenPhuHuynh || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Số điện thoại</span>
                            <span class="info-value">${student.sdtPhuHuynh || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value">${student.emailPhuHuynh || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Địa chỉ</span>
                            <span class="info-value">${student.diaChiPhuHuynh || 'N/A'}</span>
                        </div>
                    </div>
                </div>

                <!-- Kết quả học tập -->
                <div class="info-section">
                    <h3><i class="fas fa-graduation-cap"></i> Kết quả học tập</h3>
                    ${grades.length > 0 ? displayGrades(grades) : '<p class="info-value">Chưa có dữ liệu điểm</p>'}
                </div>

                <!-- Khen thưởng -->
                ${awards.length > 0 ? `
                <div class="info-section">
                    <h3><i class="fas fa-award"></i> Khen thưởng (${awards.length})</h3>
                    ${awards.map(award => `
                        <div class="list-item">
                            <div class="list-item-header">
                                <span>${award.noiDung}</span>
                                <span class="badge badge-success">${award.capKhenThuong}</span>
                            </div>
                            <div class="list-item-content">
                                <i class="fas fa-calendar"></i> ${formatDate(award.ngayKhenThuong)} | 
                                <i class="fas fa-certificate"></i> ${award.hinhThuc} | 
                                <i class="fas fa-tag"></i> ${award.linhVuc}
                            </div>
                        </div>
                    `).join('')}
                </div>
                ` : ''}

                <!-- Vi phạm -->
                ${violations.length > 0 ? `
                <div class="info-section">
                    <h3><i class="fas fa-exclamation-triangle"></i> Vi phạm (${violations.length})</h3>
                    ${violations.map(vp => `
                        <div class="list-item">
                            <div class="list-item-header">
                                <span>${vp.loaiViPham}</span>
                                <span class="badge ${vp.mucDoViPham === 'Nang' ? 'badge-danger' : 'badge-warning'}">
                                    ${vp.mucDoViPham}
                                </span>
                            </div>
                            <div class="list-item-content">
                                <i class="fas fa-calendar"></i> ${formatDate(vp.ngayViPham)} | 
                                <i class="fas fa-gavel"></i> ${vp.hinhThucXuLy}<br>
                                <strong>Nội dung:</strong> ${vp.noiDungViPham || 'N/A'}<br>
                                <strong>Người phát hiện:</strong> ${vp.nguoiPhatHien || 'N/A'}
                            </div>
                        </div>
                    `).join('')}
                </div>
                ` : ''}
            `;
            
            document.getElementById('modalBody').innerHTML = html;
        }

        function displayGrades(grades) {
            // Group by semester
            const hk1 = grades.filter(g => g.hocKy == 1);
            const hk2 = grades.filter(g => g.hocKy == 2);
            
            let html = '';
            
            if (hk1.length > 0) {
                html += '<h4 style="margin: 15px 0 10px 0;">Học kỳ 1</h4>';
                html += generateGradesTable(hk1);
            }
            
            if (hk2.length > 0) {
                html += '<h4 style="margin: 15px 0 10px 0;">Học kỳ 2</h4>';
                html += generateGradesTable(hk2);
            }
            
            return html;
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
                                <td style="text-align: left;">${grade.tenMonHoc}</td>
                                <td>${grade.diemTX1 || '-'}</td>
                                <td>${grade.diemTX2 || '-'}</td>
                                <td>${grade.diemTX3 || '-'}</td>
                                <td>${grade.diemTX4 || '-'}</td>
                                <td>${grade.diemGiuaKy || '-'}</td>
                                <td>${grade.diemCuoiKy || '-'}</td>
                                <td><strong>${grade.tbDiem || '-'}</strong></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN');
        }

        function closeModal() {
            document.getElementById('studentModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('studentModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>
