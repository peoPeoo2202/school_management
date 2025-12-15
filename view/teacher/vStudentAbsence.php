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

// Check if $data is not set, load from controller
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
    
    // Kiểm tra nếu là AJAX request
    if (isset($_POST['action'])) {
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
                    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                } else {
                    $result = $model->addAbsence($maHS, $ngayNghi, $hocKy, $namHoc, $loaiNghi, $lyDo, $maGV);
                    echo json_encode($result);
                }
                break;
                
            case 'update':
                $maNghiHoc = $_POST['maNghiHoc'] ?? null;
                $ngayNghi = $_POST['ngayNghi'] ?? null;
                $loaiNghi = $_POST['loaiNghi'] ?? 'cophep';
                $lyDo = $_POST['lyDo'] ?? '';
                
                if (!$maNghiHoc || !$ngayNghi) {
                    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                } else {
                    $result = $model->updateAbsence($maNghiHoc, $ngayNghi, $loaiNghi, $lyDo);
                    echo json_encode($result);
                }
                break;
                
            case 'delete':
                $maNghiHoc = $_POST['maNghiHoc'] ?? null;
                
                if (!$maNghiHoc) {
                    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                } else {
                    $result = $model->deleteAbsence($maNghiHoc);
                    echo json_encode($result);
                }
                break;
                
            case 'getDetails':
                $maHS = $_POST['maHS'] ?? null;
                $hocKy = $_POST['hocKy'] ?? null;
                $namHoc = $_POST['namHoc'] ?? null;
                
                if (!$maHS || !$hocKy || !$namHoc) {
                    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                } else {
                    $details = $model->getAbsenceDetails($maHS, $hocKy, $namHoc);
                    echo json_encode(['success' => true, 'data' => $details]);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
        $conn->close();
        exit();
    }
    
    // Lấy danh sách lớp chủ nhiệm của giáo viên
    $classes = $model->getClassesByTeacher($maGV);
    
    // Lấy maLop
    $maLop = $_GET['maLop'] ?? null;
    if (!$maLop) {
        if (empty($classes)) {
            $data = ['error' => 'Bạn chưa được phân công làm giáo viên chủ nhiệm lớp nào'];
        } else {
            $maLop = $classes[0]['maLop'];
        }
    }
    
    if ($maLop) {
        // Lấy thông tin lớp
        $classInfo = $model->getClassInfo($maLop);
        
        // Kiểm tra quyền
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            $data = ['error' => 'Bạn không có quyền xem thông tin của lớp này'];
        } else {
            // Lấy thông tin học kỳ và năm học
            $currentYear = date('Y');
            $defaultNamHoc = ($currentYear - 1) . '-' . $currentYear;
            $defaultHocKy = (date('m') <= 6) ? 2 : 1;
            
            $namHoc = $_GET['namHoc'] ?? $defaultNamHoc;
            $hocKy = $_GET['hocKy'] ?? $defaultHocKy;
            
            // Lấy danh sách học sinh
            $students = $model->getStudentAbsences($maLop, $hocKy, $namHoc);
            
            // Lấy danh sách năm học có dữ liệu
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

// Lấy thông tin từ session
$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý nghỉ học - Hệ thống Quản lý Giáo dục</title>
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

        .class-info {
            background: linear-gradient(135deg, #5081BE15 0%, #4a6fa515 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #5081BE;
        }

        .class-info h3 {
            color: #5081BE;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
        }

        .class-info p {
            margin: 8px 0;
            color: #555;
            font-size: 14px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: #5081BE;
            font-size: 20px;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th {
            background: linear-gradient(135deg, #5081BE 0%, #4a6fa5 100%);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }

        tr:hover {
            background-color: #f5f9fc;
        }

        /* Button Styles */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #5081BE 0%, #4a6fa5 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(80, 129, 190, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
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
            max-width: 600px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s;
            max-height: 95vh;
            overflow-y: auto;
        }

        @keyframes slideDown {
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
            background: linear-gradient(135deg, #5081BE 0%, #4a6fa5 100%);
            color: white;
            padding: 20px 24px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close:hover {
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
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

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Badge Styles */
        .badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pagination button:hover:not(:disabled) {
            background: #5081BE;
            color: white;
            border-color: #5081BE;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination span {
            color: #666;
            font-size: 14px;
        }

        /* Details List */
        .absence-details {
            margin-top: 15px;
        }

        .absence-item {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .absence-info {
            flex: 1;
        }

        .absence-date {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .absence-reason {
            font-size: 13px;
            color: #666;
        }

        .absence-actions {
            display: flex;
            gap: 8px;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #c62828;
        }

        /* Filter Group Styles */
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 13px;
            color: #666;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-group label i {
            font-size: 12px;
            color: #5081BE;
        }

        .filter-group select {
            padding: 10px 35px 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            min-width: 140px;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
        }

        .filter-group select:hover {
            border-color: #5081BE;
            box-shadow: 0 2px 8px rgba(80, 129, 190, 0.15);
        }

        .filter-group select:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 3px rgba(80, 129, 190, 0.1);
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 15px;
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
            <!-- Header -->
            <div class="header-section">
                <div class="header-left-icon">
                    <h2><i class="fas fa-calendar-times"></i></h2>
                    <h2>Quản lý nghỉ học</h2>
                </div>
            </div>

            <?php if (isset($data['error'])): ?>
                <div class="card">
                    <div class="error-message">
                        <strong>⚠️ Lỗi:</strong> <?php echo htmlspecialchars($data['error']); ?>
                    </div>
                </div>
            <?php elseif (empty($data['students'])): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-info-circle"></i> Thông báo
                        </div>
                    </div>
                    <div style="padding: 40px; text-align: center;">
                        <i class="fas fa-inbox" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
                        <h3 style="color: #666; margin-bottom: 10px;">Không có dữ liệu</h3>
                        <p style="color: #999;">Không có thông tin nghỉ học cho lớp <strong><?php echo htmlspecialchars($data['classInfo']['tenLop'] ?? ''); ?></strong> trong học kỳ <strong><?php echo $data['hocKy']; ?></strong> năm học <strong><?php echo htmlspecialchars($data['namHoc']); ?></strong></p>
                        <p style="color: #999; margin-top: 10px;"><em>Vui lòng chọn năm học và học kỳ có dữ liệu (Ví dụ: 2024-2025)</em></p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Danh sách học sinh -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-users"></i> Danh sách học sinh và nghỉ học - 
                            Lớp <?php echo htmlspecialchars($data['classInfo']['tenLop']); ?>
                        </h2>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div class="filter-group">
                                <label><i class="fas fa-book"></i> Học kỳ</label>
                                <select id="hocKyFilter" onchange="filterByPeriod()">
                                    <option value="1" <?php echo ($data['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                    <option value="2" <?php echo ($data['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label><i class="fas fa-graduation-cap"></i> Năm học</label>
                                <select id="namHocFilter" onchange="filterByPeriod()">
                                    <?php
                                    if (!empty($data['availableYears'])) {
                                        foreach ($data['availableYears'] as $year) {
                                            $selected = ($data['namHoc'] == $year) ? 'selected' : '';
                                            echo "<option value='$year' $selected>$year</option>";
                                        }
                                    } else {
                                        // Fallback nếu không có dữ liệu
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
                        </div>
                    </div>

                    <div class="table-container">
                        <table id="studentTable">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Họ và tên</th>
                                    <th>Nghỉ có phép</th>
                                    <th>Nghỉ không phép</th>
                                    <th>Tổng nghỉ</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <!-- Data will be filled by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination">
                        <button id="prevBtn" onclick="changePage(-1)">
                            <i class="fas fa-chevron-left"></i> Trước
                        </button>
                        <span id="pageInfo"></span>
                        <button id="nextBtn" onclick="changePage(1)">
                            Sau <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Absence Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Thêm nghỉ học</h3>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addForm">
                    <input type="hidden" id="addMaHS" name="maHS">
                    <input type="hidden" id="addHocKy" name="hocKy" value="<?php echo $data['hocKy'] ?? ''; ?>">
                    <input type="hidden" id="addNamHoc" name="namHoc" value="<?php echo $data['namHoc'] ?? ''; ?>">

                    <div class="form-group">
                        <label>Học sinh</label>
                        <input type="text" id="addHoTen" readonly>
                    </div>

                    <div class="form-group">
                        <label>Ngày nghỉ <span style="color: red;">*</span></label>
                        <input type="date" name="ngayNghi" required>
                    </div>

                    <div class="form-group">
                        <label>Loại nghỉ <span style="color: red;">*</span></label>
                        <select name="loaiNghi" required>
                            <option value="cophep">Có phép</option>
                            <option value="khongphep">Không phép</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Lý do</label>
                        <textarea name="lyDo" placeholder="Nhập lý do nghỉ..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="addAbsence()">
                    <i class="fas fa-save"></i> Lưu
                </button>
                <button type="button" class="btn btn-danger" onclick="closeAddModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-list"></i> Chi tiết nghỉ học</h3>
                <span class="close" onclick="closeDetailsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <h4 id="detailsStudentName"></h4>
                <div id="detailsList" class="absence-details">
                    <!-- Details will be filled by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeDetailsModal()">
                    <i class="fas fa-times"></i> Đóng
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Absence Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Sửa nghỉ học</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editMaNghiHoc" name="maNghiHoc">

                    <div class="form-group">
                        <label>Ngày nghỉ <span style="color: red;">*</span></label>
                        <input type="date" id="editNgayNghi" name="ngayNghi" required>
                    </div>

                    <div class="form-group">
                        <label>Loại nghỉ <span style="color: red;">*</span></label>
                        <select id="editLoaiNghi" name="loaiNghi" required>
                            <option value="cophep">Có phép</option>
                            <option value="khongphep">Không phép</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Lý do</label>
                        <textarea id="editLyDo" name="lyDo" placeholder="Nhập lý do nghỉ..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="updateAbsence()">
                    <i class="fas fa-save"></i> Cập nhật
                </button>
                <button type="button" class="btn btn-danger" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
            </div>
        </div>
    </div>

    <script>
        const students = <?php echo json_encode($data['students'] ?? []); ?>;
        
        // Lấy học kỳ và năm học từ URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const hocKy = urlParams.get('hocKy') || <?php echo json_encode($data['hocKy'] ?? 1); ?>;
        const namHoc = urlParams.get('namHoc') || <?php echo json_encode($data['namHoc'] ?? ''); ?>;
        
        let currentPage = 1;
        const studentsPerPage = 5;

        function displayStudents() {
            const tbody = document.getElementById('tableBody');
            
            // Kiểm tra nếu không có học sinh nào
            if (!students || students.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; display: block; margin-bottom: 15px;"></i>
                            <h3 style="color: #666; margin-bottom: 10px;">Không có dữ liệu</h3>
                            <p style="color: #999;">Không có thông tin nghỉ học cho năm học <strong>${namHoc}</strong> - Học kỳ <strong>${hocKy}</strong></p>
                            <p style="color: #999; margin-top: 10px;"><em>Vui lòng chọn năm học và học kỳ có dữ liệu (Ví dụ: 2024-2025)</em></p>
                        </td>
                    </tr>
                `;
                document.getElementById('prevBtn').disabled = true;
                document.getElementById('nextBtn').disabled = true;
                document.getElementById('pageInfo').textContent = 'Trang 0 / 0';
                return;
            }
            
            const start = (currentPage - 1) * studentsPerPage;
            const end = start + studentsPerPage;
            const pageStudents = students.slice(start, end);

            tbody.innerHTML = '';
            pageStudents.forEach((student, index) => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td>${start + index + 1}</td>
                    <td>${student.hoTen}</td>
                    <td><span class="badge badge-success">${student.soNghiCoPhep}</span></td>
                    <td><span class="badge badge-danger">${student.soNghiKhongPhep}</span></td>
                    <td><strong>${student.tongNghi}</strong></td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="openAddModal(${student.maHS}, '${student.hoTen}')">
                            <i class="fas fa-plus"></i> Thêm
                        </button>
                        <button class="btn btn-success btn-sm" onclick="viewDetails(${student.maHS}, '${student.hoTen}')">
                            <i class="fas fa-eye"></i> Chi tiết
                        </button>
                    </td>
                `;
            });

            updatePagination();
        }

        function updatePagination() {
            const totalPages = Math.ceil(students.length / studentsPerPage);
            document.getElementById('pageInfo').textContent = `Trang ${currentPage} / ${totalPages}`;
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = currentPage === totalPages;
        }

        function changePage(direction) {
            const totalPages = Math.ceil(students.length / studentsPerPage);
            currentPage += direction;
            if (currentPage < 1) currentPage = 1;
            if (currentPage > totalPages) currentPage = totalPages;
            displayStudents();
        }

        function openAddModal(maHS, hoTen) {
            // ƯU TIÊN: Lấy từ dropdown filter trước (giá trị người dùng đang chọn)
            const filterHocKy = document.getElementById('hocKyFilter');
            const filterNamHoc = document.getElementById('namHocFilter');
            
            // Lấy giá trị từ dropdown (ưu tiên cao nhất)
            let hocKyValue = filterHocKy ? filterHocKy.value : null;
            let namHocValue = filterNamHoc ? filterNamHoc.value : null;
            
            // Nếu dropdown không có giá trị, thử lấy từ URL
            if (!hocKyValue || !namHocValue) {
                const urlParams = new URLSearchParams(window.location.search);
                if (!hocKyValue) hocKyValue = urlParams.get('hocKy');
                if (!namHocValue) namHocValue = urlParams.get('namHoc');
            }
            
            // Nếu vẫn không có, dùng biến JS
            if (!hocKyValue) hocKyValue = hocKy;
            if (!namHocValue) namHocValue = namHoc;
            
            // Đảm bảo không bao giờ để trống (fallback cuối cùng)
            if (!hocKyValue) hocKyValue = '1';
            if (!namHocValue) namHocValue = '2024-2025';
            
            // Reset form
            document.getElementById('addForm').reset();
            
            // Set lại TẤT CẢ các giá trị sau khi reset
            document.getElementById('addMaHS').value = maHS;
            document.getElementById('addHoTen').value = hoTen;
            document.getElementById('addHocKy').value = hocKyValue;
            document.getElementById('addNamHoc').value = namHocValue;
            
            // Debug: Log để kiểm tra giá trị
            console.log('=== OPEN ADD MODAL ===');
            console.log('Học kỳ từ filter dropdown:', filterHocKy ? filterHocKy.value : 'NULL');
            console.log('Năm học từ filter dropdown:', filterNamHoc ? filterNamHoc.value : 'NULL');
            console.log('Học kỳ cuối cùng được set:', hocKyValue);
            console.log('Năm học cuối cùng được set:', namHocValue);
            console.log('Form addHocKy value:', document.getElementById('addHocKy').value);
            console.log('Form addNamHoc value:', document.getElementById('addNamHoc').value);
            
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        async function addAbsence() {
            const form = document.getElementById('addForm');
            const formData = new FormData(form);
            formData.append('action', 'add');

            // Debug: Kiểm tra dữ liệu được gửi đi
            console.log('=== DỮ LIỆU GỬI ĐI ===');
            console.log('hocKy input value:', document.getElementById('addHocKy').value);
            console.log('namHoc input value:', document.getElementById('addNamHoc').value);
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            try {
                const response = await fetch('vStudentAbsence.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    closeAddModal();
                    location.reload();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi thêm nghỉ học');
            }
        }

        async function viewDetails(maHS, hoTen) {
            const formData = new FormData();
            formData.append('action', 'getDetails');
            formData.append('maHS', maHS);
            formData.append('hocKy', hocKy);
            formData.append('namHoc', namHoc);

            try {
                const response = await fetch('vStudentAbsence.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    document.getElementById('detailsStudentName').textContent = hoTen;
                    const detailsList = document.getElementById('detailsList');
                    
                    if (result.data.length === 0) {
                        detailsList.innerHTML = '<p style="text-align: center; color: #999;">Chưa có nghỉ học</p>';
                    } else {
                        detailsList.innerHTML = result.data.map(item => `
                            <div class="absence-item">
                                <div class="absence-info">
                                    <div class="absence-date">
                                        ${new Date(item.ngayNghi).toLocaleDateString('vi-VN')} - 
                                        <span class="badge ${item.loaiNghi === 'cophep' ? 'badge-success' : 'badge-danger'}">
                                            ${item.loaiNghi === 'cophep' ? 'Có phép' : 'Không phép'}
                                        </span>
                                    </div>
                                    <div class="absence-reason">${item.lyDo || 'Không có lý do'}</div>
                                </div>
                                <div class="absence-actions">
                                    <button class="btn btn-warning btn-sm" onclick="editAbsence(${item.maNghiHoc}, '${item.ngayNghi}', '${item.loaiNghi}', '${(item.lyDo || '').replace(/'/g, "\\'")}')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteAbsence(${item.maNghiHoc})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    }

                    document.getElementById('detailsModal').style.display = 'block';
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi tải chi tiết');
            }
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        function editAbsence(maNghiHoc, ngayNghi, loaiNghi, lyDo) {
            document.getElementById('editMaNghiHoc').value = maNghiHoc;
            document.getElementById('editNgayNghi').value = ngayNghi;
            document.getElementById('editLoaiNghi').value = loaiNghi;
            document.getElementById('editLyDo').value = lyDo;
            
            closeDetailsModal();
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        async function updateAbsence() {
            const form = document.getElementById('editForm');
            const formData = new FormData(form);
            formData.append('action', 'update');

            try {
                const response = await fetch('vStudentAbsence.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    closeEditModal();
                    location.reload();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi cập nhật');
            }
        }

        async function deleteAbsence(maNghiHoc) {
            if (!confirm('Bạn có chắc chắn muốn xóa nghỉ học này?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('maNghiHoc', maNghiHoc);

            try {
                const response = await fetch('vStudentAbsence.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi xóa');
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Filter by period function
        function filterByPeriod() {
            const hocKy = document.getElementById('hocKyFilter').value;
            const namHoc = document.getElementById('namHocFilter').value;
            const maLop = <?php echo json_encode($data['currentClassId'] ?? ''); ?>;
            
            console.log('=== FILTER BY PERIOD ===');
            console.log('Học kỳ từ dropdown:', hocKy);
            console.log('Năm học từ dropdown:', namHoc);
            console.log('Năm học sau encode:', encodeURIComponent(namHoc));
            
            // Reload page with new parameters - encode namHoc to preserve special characters
            window.location.href = `vStudentAbsence.php?maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}`;
        }

        // Initialize display
        displayStudents();
    </script>
</body>

</html>
