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
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(235, 51, 73, 0.3);
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
            border-color: #eb3349;
            box-shadow: 0 0 0 3px rgba(235, 51, 73, 0.1);
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
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #d32f3f 0%, #e74c3c 100%);
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
            overflow-x: auto;
            overflow-y: visible;
        }

        table {
            width: 100%;
            min-width: 1400px;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 20px;
        }
        
        .pagination button {
            padding: 8px 16px;
            border: 2px solid #eb3349;
            background: white;
            color: #eb3349;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .pagination button:hover:not(:disabled) {
            background: #eb3349;
            color: white;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            border-color: #ccc;
            color: #ccc;
        }
        
        .pagination span {
            font-weight: 600;
            color: #495057;
        }
        
        .inline-input {
            width: 100%;
            padding: 8px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .inline-input:focus {
            outline: none;
            border-color: #eb3349;
            box-shadow: 0 0 0 3px rgba(235, 51, 73, 0.1);
        }
        
        .inline-select {
            width: 100%;
            padding: 8px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .inline-select:focus {
            outline: none;
            border-color: #eb3349;
            box-shadow: 0 0 0 3px rgba(235, 51, 73, 0.1);
        }
        
        .add-violation-row {
            background: #f8f9fa !important;
            border: 2px dashed #dee2e6 !important;
        }
        
        .add-violation-row td {
            padding: 12px !important;
        }

        thead {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
        }

        th {
            padding: 16px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #d32f3f;
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

        td:nth-child(2), td:nth-child(4) {
            text-align: left;
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: linear-gradient(90deg, #fff5f5 0%, #ffe9e9 100%);
            transform: scale(1.01);
        }

        tbody tr:last-child td:first-child {
            border-radius: 0 0 0 12px;
        }

        tbody tr:last-child td:last-child {
            border-radius: 0 0 12px 0;
        }

        .violation-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .violation-nhe {
            background: linear-gradient(135deg, #f5af19 0%, #f12711 100%);
            color: white;
        }

        .violation-trung-binh {
            background: linear-gradient(135deg, #FF8008 0%, #FFC837 100%);
            color: white;
        }

        .violation-nang {
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
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
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
            color: #eb3349;
            margin-bottom: 15px;
        }

        .loading p {
            font-size: 16px;
            margin-top: 10px;
        }

        .no-violations {
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

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
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
                    <i class="fas fa-exclamation-triangle"></i>
                    Quản lý Vi phạm
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
                            <label><i class="fas fa-calendar-alt"></i> Học kỳ</label>
                            <select id="hocKy" class="form-control" onchange="loadData()">
                                <option value="1" <?php echo ($data['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo ($data['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-graduation-cap"></i> Năm học</label>
                            <select id="namHoc" class="form-control" onchange="loadData()">
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
                    </div>
                    <input type="hidden" id="maLop" value="<?php echo $data['currentClassId']; ?>">

                    <div id="violationContent">
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

    <!-- Modal thêm vi phạm -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Thêm vi phạm</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="addForm">
                    <input type="hidden" id="addMaHS">
                    
                    <div class="form-group">
                        <label>Học sinh</label>
                        <input type="text" class="form-control" id="addHoTen" readonly 
                               style="background: #f8f9fa; font-weight: bold;">
                    </div>
                    
                    <div class="form-group">
                        <label>Loại vi phạm <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="addLoaiViPham"
                               placeholder="VD: Nói chuyện trong giờ học, Đi trễ...">
                    </div>

                    <div class="form-group">
                        <label>Nội dung vi phạm</label>
                        <textarea class="form-control" id="addNoiDungViPham" 
                                  placeholder="Mô tả chi tiết về vi phạm..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Mức độ vi phạm <span style="color: red;">*</span></label>
                        <select class="form-control" id="addMucDoViPham">
                            <option value="">-- Chọn mức độ --</option>
                            <option value="Nhẹ">Nhẹ</option>
                            <option value="Trung bình">Trung bình</option>
                            <option value="Nặng">Nặng</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Hình thức xử lý <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="addHinhThucXuLy"
                               placeholder="VD: Nhắc nhở, Cảnh cáo, Kiểm điểm...">
                    </div>

                    <div class="form-group">
                        <label>Ngày vi phạm <span style="color: red;">*</span></label>
                        <input type="date" class="form-control" id="addNgayViPham">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
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
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Chỉnh sửa vi phạm</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="editForm">
                    <input type="hidden" id="editMaViPham">
                    
                    <div class="form-group">
                        <label>Loại vi phạm <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="editLoaiViPham">
                    </div>

                    <div class="form-group">
                        <label>Nội dung vi phạm</label>
                        <textarea class="form-control" id="editNoiDungViPham"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Mức độ vi phạm <span style="color: red;">*</span></label>
                        <select class="form-control" id="editMucDoViPham">
                            <option value="">-- Chọn mức độ --</option>
                            <option value="Nhẹ">Nhẹ</option>
                            <option value="Trung bình">Trung bình</option>
                            <option value="Nặng">Nặng</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Hình thức xử lý <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="editHinhThucXuLy">
                    </div>

                    <div class="form-group">
                        <label>Ngày vi phạm <span style="color: red;">*</span></label>
                        <input type="date" class="form-control" id="editNgayViPham">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
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
        const studentsPerPage = 5;

        function loadData() {
            const maLop = document.getElementById('maLop').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;

            document.getElementById('violationContent').innerHTML = 
                '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Đang tải dữ liệu...</p></div>';

            fetch(`../../controller/cStudentViolation.php?action=getViolations&maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showAlert(data.error, 'error');
                        document.getElementById('violationContent').innerHTML = '';
                    } else {
                        students = data.students || [];
                        currentPage = 1;
                        displayViolations();
                    }
                })
                .catch(error => {
                    showAlert('Lỗi khi tải dữ liệu: ' + error.message, 'error');
                });
        }

        function displayViolations() {
            if (!students || students.length === 0) {
                document.getElementById('violationContent').innerHTML = 
                    '<div class="loading"><p>Không có dữ liệu học sinh</p></div>';
                return;
            }

            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            
            // Tính toán phân trang
            const startIndex = (currentPage - 1) * studentsPerPage;
            const endIndex = startIndex + studentsPerPage;
            const paginatedStudents = students.slice(startIndex, endIndex);
            const totalPages = Math.ceil(students.length / studentsPerPage);

            let html = `
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;">STT</th>
                                <th style="width: 180px;">HỌ VÀ TÊN</th>
                                <th style="width: 150px;">LOẠI VI PHẠM</th>
                                <th style="width: 200px;">NỘI DUNG</th>
                                <th style="width: 120px;">MỨC ĐỘ</th>
                                <th style="width: 150px;">HÌNH THỨC XỬ LÝ</th>
                                <th style="width: 120px;">NGÀY VI PHẠM</th>
                                <th style="width: 100px;">HỌC KỲ</th>
                                <th style="width: 100px;">NĂM HỌC</th>
                                <th style="width: 150px;">THAO TÁC</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            paginatedStudents.forEach((student, index) => {
                const rowNum = startIndex + index + 1;
                
                if (student.violations && student.violations.length > 0) {
                    // Học sinh có vi phạm - hiển thị từng vi phạm
                    student.violations.forEach((violation, vIndex) => {
                        html += `
                            <tr>
                                ${vIndex === 0 ? `<td rowspan="${student.violations.length}">${rowNum}</td>` : ''}
                                ${vIndex === 0 ? `<td rowspan="${student.violations.length}" style="text-align: left;"><strong>${student.hoTen}</strong></td>` : ''}
                                <td style="text-align: left;">${violation.loaiViPham || '-'}</td>
                                <td style="text-align: left;">${violation.noiDungViPham || '-'}</td>
                                <td>${formatViolationLevel(violation.mucDoViPham)}</td>
                                <td>${violation.hinhThucXuLy || '-'}</td>
                                <td>${formatDate(violation.ngayViPham)}</td>
                                <td>${violation.hocKy || '-'}</td>
                                <td>${violation.namHoc || '-'}</td>
                                <td>
                                    <div class="action-buttons" style="justify-content: center;">
                                        ${vIndex === 0 ? `
                                        <button class="btn btn-sm btn-success" 
                                                onclick="openAddModal(${student.maHS}, '${student.hoTen}')"
                                                title="Thêm vi phạm">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        ` : ''}
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="editViolation(${violation.maViPham})"
                                                title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteViolation(${violation.maViPham})"
                                                title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    // Học sinh chưa có vi phạm - chỉ hiển thị 1 dòng với nút Thêm
                    html += `
                        <tr>
                            <td>${rowNum}</td>
                            <td style="text-align: left;"><strong>${student.hoTen}</strong></td>
                            <td colspan="7" style="text-align: center; color: #999; font-style: italic;">-</td>
                            <td>
                                <button class="btn btn-sm btn-success" 
                                        onclick="openAddModal(${student.maHS}, '${student.hoTen}')"
                                        title="Thêm vi phạm">
                                    <i class="fas fa-plus"></i> Thêm
                                </button>
                            </td>
                        </tr>
                    `;
                }
            });

            html += `
                        </tbody>
                    </table>
                    
                    <div class="pagination">
                        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                            <i class="fas fa-chevron-left"></i> Trước
                        </button>
                        <span>Trang ${currentPage} / ${totalPages} (Tổng ${students.length} học sinh)</span>
                        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                            Sau <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            `;

            document.getElementById('violationContent').innerHTML = html;
        }
        
        function changePage(page) {
            const totalPages = Math.ceil(students.length / studentsPerPage);
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                displayViolations();
            }
        }

        function formatViolationLevel(level) {
            const classes = {
                'Nhẹ': 'violation-nhe',
                'Trung bình': 'violation-trung-binh',
                'Nặng': 'violation-nang'
            };
            
            const className = classes[level] || '';
            return `<span class="violation-badge ${className}">${level}</span>`;
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN');
        }
        
        function openAddModal(maHS, hoTen) {
            document.getElementById('addMaHS').value = maHS;
            document.getElementById('addHoTen').value = hoTen;
            document.getElementById('addLoaiViPham').value = '';
            document.getElementById('addNoiDungViPham').value = '';
            document.getElementById('addMucDoViPham').value = '';
            document.getElementById('addHinhThucXuLy').value = '';
            document.getElementById('addNgayViPham').value = '<?php echo date('Y-m-d'); ?>';
            
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function addViolation() {
            const maHS = document.getElementById('addMaHS').value;
            const loaiViPham = document.getElementById('addLoaiViPham').value.trim();
            const noiDung = document.getElementById('addNoiDungViPham').value.trim();
            const mucDo = document.getElementById('addMucDoViPham').value;
            const hinhThuc = document.getElementById('addHinhThucXuLy').value.trim();
            const ngayViPham = document.getElementById('addNgayViPham').value;
            
            if (!loaiViPham || !mucDo || !hinhThuc) {
                showAlert('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
                return;
            }
            
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('maHS', maHS);
            formData.append('loaiViPham', loaiViPham);
            formData.append('noiDungViPham', noiDung);
            formData.append('mucDoViPham', mucDo);
            formData.append('hinhThucXuLy', hinhThuc);
            formData.append('ngayViPham', ngayViPham);
            formData.append('hocKy', hocKy);
            formData.append('namHoc', namHoc);
            
            fetch('../../controller/cStudentViolation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    closeAddModal();
                    loadData();
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Lỗi khi thêm vi phạm: ' + error.message, 'error');
            });
        }

        function editViolation(maViPham) {
            // Tìm thông tin vi phạm
            let violation = null;
            for (let student of students) {
                if (student.violations) {
                    violation = student.violations.find(v => v.maViPham == maViPham);
                    if (violation) {
                        break;
                    }
                }
            }

            if (!violation) {
                showAlert('Không tìm thấy thông tin vi phạm', 'error');
                return;
            }

            document.getElementById('editMaViPham').value = violation.maViPham;
            document.getElementById('editLoaiViPham').value = violation.loaiViPham;
            document.getElementById('editMucDoViPham').value = violation.mucDoViPham;
            document.getElementById('editNoiDungViPham').value = violation.noiDungViPham || '';
            document.getElementById('editHinhThucXuLy').value = violation.hinhThucXuLy || '';
            document.getElementById('editNgayViPham').value = violation.ngayViPham;
            
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function saveEdit() {
            console.log('=== saveEdit() được gọi ===');
            
            const maViPham = document.getElementById('editMaViPham').value;
            const loaiViPham = document.getElementById('editLoaiViPham').value.trim();
            const mucDoViPham = document.getElementById('editMucDoViPham').value;
            const noiDungViPham = document.getElementById('editNoiDungViPham').value.trim();
            const hinhThucXuLy = document.getElementById('editHinhThucXuLy').value.trim();
            const ngayViPham = document.getElementById('editNgayViPham').value;

            console.log('=== Dữ liệu form ===');
            console.log('maViPham:', maViPham, typeof maViPham);
            console.log('loaiViPham:', loaiViPham, 'length:', loaiViPham.length);
            console.log('mucDoViPham:', mucDoViPham, 'length:', mucDoViPham.length);
            console.log('noiDungViPham:', noiDungViPham, 'length:', noiDungViPham.length);
            console.log('hinhThucXuLy:', hinhThucXuLy, 'length:', hinhThucXuLy.length);
            console.log('ngayViPham:', ngayViPham);

            if (!loaiViPham || !mucDoViPham || !hinhThucXuLy || !ngayViPham) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc (Loại vi phạm, Mức độ, Hình thức xử lý, Ngày vi phạm)');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('maViPham', maViPham);
            formData.append('loaiViPham', loaiViPham);
            formData.append('mucDoViPham', mucDoViPham);
            formData.append('noiDungViPham', noiDungViPham);
            formData.append('hinhThucXuLy', hinhThucXuLy);
            formData.append('ngayViPham', ngayViPham);

            console.log('Đang gửi request...');

            console.log('=== Gửi request đến controller ===');
            
            fetch('../../controller/cStudentViolation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('=== Response nhận được ===');
                console.log('Status:', response.status);
                console.log('Status Text:', response.statusText);
                console.log('Headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('=== Response Body ===');
                console.log('Length:', text.length);
                console.log('Content:', text);
                
                try {
                    const data = JSON.parse(text);
                    console.log('=== Parsed JSON ===');
                    console.log(data);
                    
                    if (data.success) {
                        alert(data.message);
                        closeEditModal();
                        loadData();
                    } else {
                        let errorMsg = 'Lỗi: ' + data.message;
                        if (data.debug) {
                            console.error('=== Debug Info ===');
                            console.error(data.debug);
                            errorMsg += '\n\nChi tiết: ' + data.debug;
                        }
                        alert(errorMsg);
                    }
                } catch (e) {
                    console.error('=== JSON Parse Error ===');
                    console.error('Error:', e);
                    console.error('Raw Text:', text);
                    alert('Lỗi: Phản hồi không phải JSON hợp lệ\n\nĐã log chi tiết vào Console (F12)');
                }
            })
            .catch(error => {
                console.error('=== Fetch Error ===');
                console.error(error);
                alert('Lỗi kết nối: ' + error.message);
            });
        }

        function deleteViolation(maViPham) {
            if (!confirm('Bạn có chắc chắn muốn xóa vi phạm này?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('maViPham', maViPham);

            fetch('../../controller/cStudentViolation.php', {
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
            const editModal = document.getElementById('editModal');
            const addModal = document.getElementById('addModal');
            if (event.target == editModal) {
                closeEditModal();
            }
            if (event.target == addModal) {
                closeAddModal();
            }
        };
    </script>
</body>
</html>
