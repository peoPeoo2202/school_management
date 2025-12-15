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
            echo json_encode($result ? 
                ['success' => true, 'message' => 'Thêm khen thưởng thành công'] : 
                ['success' => false, 'message' => 'Lỗi khi thêm khen thưởng'], 
                JSON_UNESCAPED_UNICODE);
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
            echo json_encode($result ? 
                ['success' => true, 'message' => 'Cập nhật khen thưởng thành công'] : 
                ['success' => false, 'message' => 'Lỗi khi cập nhật khen thưởng'], 
                JSON_UNESCAPED_UNICODE);
            exit();
            
        case 'delete':
            $maKhenThuong = intval($_POST['maKhenThuong'] ?? 0);
            
            if (!$maKhenThuong) {
                echo json_encode(['success' => false, 'message' => 'Mã khen thưởng không hợp lệ'], JSON_UNESCAPED_UNICODE);
                exit();
            }
            
            $result = $model->deleteAward($maKhenThuong);
            echo json_encode($result ? 
                ['success' => true, 'message' => 'Xóa khen thưởng thành công'] : 
                ['success' => false, 'message' => 'Lỗi khi xóa khen thưởng'], 
                JSON_UNESCAPED_UNICODE);
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
            background: #f5f6fa;
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            background: #f5f6fa;
            box-sizing: border-box;
            height: 100vh;
        }

        .content-wrapper {
            max-width: 100%;
            margin: 0 auto;
            width: 100%;
            padding: 0;
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 15px;
            }
            
            .content-wrapper {
                padding: 0;
            }
            
            .filter-section {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .header-title i {
            font-size: 32px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .filter-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px solid #e9ecef;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-control:hover {
            border-color: #ced4da;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
        }

        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #0e8073 0%, #2dd868 100%);
        }

        .btn-danger {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #d32f3f 0%, #e74c3c 100%);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 8px;
        }

        .table-container {
            margin-top: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: visible;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th {
            padding: 16px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #5568d3;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        th:last-child {
            border-right: none;
        }

        th:first-child {
            border-radius: 12px 0 0 0;
        }

        th:last-child {
            border-radius: 0 12px 0 0;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f1f3f5;
            border-right: 1px solid #f1f3f5;
            font-size: 14px;
            vertical-align: middle;
            text-align: center;
        }

        td:last-child {
            border-right: none;
        }

        td:nth-child(2) {
            text-align: left;
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
            transform: scale(1.01);
        }

        tbody tr:last-child td:first-child {
            border-radius: 0 0 0 12px;
        }

        tbody tr:last-child td:last-child {
            border-radius: 0 0 12px 0;
        }

        .award-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .award-truong {
            background: linear-gradient(135deg, #f5af19 0%, #f12711 100%);
            color: white;
        }

        .award-huyen {
            background: linear-gradient(135deg, #bdc3c7 0%, #95a5a6 100%);
            color: white;
        }

        .award-tinh {
            background: linear-gradient(135deg, #cd7f32 0%, #8b4513 100%);
            color: white;
        }

        .award-quocgia {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 3% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 650px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
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
            padding: 25px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close {
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            line-height: 1;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            border-radius: 0 0 20px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            border-top: 2px solid #e9ecef;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .alert.show {
            display: flex;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .alert i {
            font-size: 20px;
        }

        .loading {
            text-align: center;
            padding: 60px 40px;
            color: #999;
        }

        .loading i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }

        .loading p {
            font-size: 16px;
            margin-top: 10px;
        }

        .student-awards {
            margin-top: 12px;
            padding-left: 25px;
        }

        .award-item {
            padding: 12px 15px;
            margin: 8px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #667eea;
            border-radius: 8px;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .award-item:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            border-left-color: #764ba2;
        }

        .award-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .award-date {
            color: #6c757d;
            font-size: 12px;
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .award-date i {
            margin-right: 5px;
            color: #667eea;
        }

        .no-awards {
            color: #adb5bd;
            font-style: italic;
            font-size: 13px;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .pagination-info {
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
        }

        .pagination {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .page-btn {
            padding: 8px 14px;
            border: 2px solid #e9ecef;
            background: white;
            color: #495057;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 40px;
            text-align: center;
        }

        .page-btn:hover:not(:disabled) {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .page-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8f9fa;
        }

        .page-ellipsis {
            padding: 8px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">
            <div class="content-wrapper">
            <div class="header-section">
                <h1 class="header-title">
                    <i class="fas fa-trophy"></i>
                    Quản lý Khen thưởng
                </h1>
            </div>

            <?php if (isset($data['error'])): ?>
                <div class="card">
                    <div class="alert alert-error show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $data['error']; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div id="alertMessage"></div>

                    <div class="filter-section">
                        <div class="form-group">
                            <label>Học kỳ</label>
                            <select id="hocKy" class="form-control" onchange="loadData()">
                                <option value="1" <?php echo ($data['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo ($data['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Năm học</label>
                            <select id="namHoc" class="form-control" onchange="loadData()">
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

                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary" onclick="openAddModal()">
                                <i class="fas fa-plus"></i> Thêm khen thưởng
                            </button>
                        </div>
                    </div>
                    <input type="hidden" id="maLop" value="<?php echo $data['currentClassId']; ?>">

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
    </div>

    <!-- Modal thêm/sửa khen thưởng -->
    <div id="awardModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm khen thưởng</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="awardForm">
                    <input type="hidden" id="maKhenThuong">
                    
                    <div class="form-group">
                        <label>Học sinh <span style="color: red;">*</span></label>
                        <select id="maHS" class="form-control" required>
                            <option value="">-- Chọn học sinh --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Lý do khen thưởng (Nội dung) <span style="color: red;">*</span></label>
                        <input type="text" id="lyDo" class="form-control" required 
                               placeholder="Ví dụ: Học sinh giỏi toàn diện">
                    </div>

                    <div class="form-group">
                        <label>Hình thức</label>
                        <select id="hinhThuc" class="form-control">
                            <option value="">-- Chọn hình thức --</option>
                            <option value="Giấy khen">Giấy khen</option>
                            <option value="Bằng khen">Bằng khen</option>
                            <option value="Huy chương">Huy chương</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Cấp khen thưởng <span style="color: red;">*</span></label>
                        <select id="capKhenThuong" class="form-control" required>
                            <option value="">-- Chọn cấp --</option>
                            <option value="truong">Cấp trường</option>
                            <option value="huyen">Cấp huyện</option>
                            <option value="tinh">Cấp tỉnh</option>
                            <option value="quocgia">Cấp quốc gia</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Lĩnh vực</label>
                        <select id="linhVuc" class="form-control">
                            <option value="">-- Chọn lĩnh vực --</option>
                            <option value="Học tập">Học tập</option>
                            <option value="Thể thao">Thể thao</option>
                            <option value="Văn nghệ">Văn nghệ</option>
                            <option value="Hoạt động xã hội">Hoạt động xã hội</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ngày khen</label>
                        <input type="date" id="ngayKhen" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                <button class="btn btn-primary" onclick="saveAward()">
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
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;">STT</th>
                                <th style="width: 180px;">Họ và tên</th>
                                <th style="width: 100px;">Hình thức</th>
                                <th style="width: 200px;">Nội dung</th>
                                <th style="width: 150px;">Cấp khen thưởng</th>
                                <th style="width: 120px;">Lĩnh vực</th>
                                <th style="width: 120px;">Ngày khen thưởng</th>
                                <th style="width: 100px;">Học kỳ</th>
                                <th style="width: 100px;">Năm học</th>
                                <th style="width: 150px;">Thao tác Sửa/Xoá</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            currentAwards.forEach((award, index) => {
                const rowIndex = startIndex + index + 1;
                html += `
                    <tr>
                        <td>${rowIndex}</td>
                        <td style="text-align: left;"><strong>${award.studentName}</strong></td>
                        <td>${award.hinhThuc || '-'}</td>
                        <td style="text-align: left;">${award.lyDo}</td>
                        <td>${formatAwardLevel(award.capKhenThuong)}</td>
                        <td>${award.linhVuc || '-'}</td>
                        <td>${formatDate(award.ngayKhen)}</td>
                        <td>${award.hocKy || '-'}</td>
                        <td>${award.namHoc || '-'}</td>
                        <td>
                            <div class="action-buttons" style="justify-content: center;">
                                <button class="btn btn-sm btn-primary" 
                                        onclick="editAward(${award.maKhenThuong})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="deleteAward(${award.maKhenThuong})">
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
            let html = `
                <div class="pagination-container">`;
            
            // Chỉ hiển thị nút điều hướng khi có nhiều hơn 1 trang
            if (totalPages > 1) {
                html += `
                    <div class="pagination">
                        <button class="page-btn" onclick="changePage(1)" ${currentPage === 1 ? 'disabled' : ''}>
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button class="page-btn" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                            <i class="fas fa-angle-left"></i>
                        </button>
                `;

                // Hiển thị các nút trang
                const maxVisiblePages = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
                
                if (endPage - startPage < maxVisiblePages - 1) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }

                if (startPage > 1) {
                    html += `<span class="page-ellipsis">...</span>`;
                }

                for (let i = startPage; i <= endPage; i++) {
                    html += `
                        <button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">
                            ${i}
                        </button>
                    `;
                }

                if (endPage < totalPages) {
                    html += `<span class="page-ellipsis">...</span>`;
                }

                html += `
                        <button class="page-btn" onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                            <i class="fas fa-angle-right"></i>
                        </button>
                        <button class="page-btn" onclick="changePage(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''}>
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>`;
            }
            
            html += `
                </div>
            `;

            return html;
        }

        function changePage(page) {
            currentPage = page;
            displayAwards();
            // Scroll to top of table
            document.getElementById('awardContent').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function formatAwardLevel(level) {
            const classes = {
                'truong': 'award-truong',
                'huyen': 'award-huyen',
                'tinh': 'award-tinh',
                'quocgia': 'award-quocgia'
            };
            
            const labels = {
                'truong': 'Cấp trường',
                'huyen': 'Cấp huyện',
                'tinh': 'Cấp tỉnh',
                'quocgia': 'Cấp quốc gia'
            };

            const className = classes[level] || '';
            const label = labels[level] || level;
            return `<span class="award-badge ${className}">${label}</span>`;
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
                alert('Vui lòng nhập lý do khen thưởng (Nội dung)');
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
