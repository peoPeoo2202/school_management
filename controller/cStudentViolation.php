<?php
session_start();
require_once(__DIR__ . '/../model/mStudentViolation.php');

class ControllerStudentViolation {
    private $model;
    
    public function __construct($connection) {
        $this->model = new ModelStudentViolation($connection);
    }
    
    /**
     * Kiểm tra quyền truy cập của giáo viên
     */
    private function checkTeacherAccess() {
        if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] != 'giaovien') {
            header('Location: ../../public/index.php');
            exit();
        }
        return $_SESSION['maGV'] ?? null;
    }
    
    /**
     * Hiển thị trang quản lý vi phạm
     */
    public function showViolations($maLop = null) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['error' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        // Lấy danh sách lớp chủ nhiệm
        $classes = $this->model->getClassesByTeacher($maGV);
        
        if (empty($classes)) {
            return ['error' => 'Bạn chưa được phân công làm giáo viên chủ nhiệm lớp nào'];
        }
        
        // Nếu không có maLop, lấy lớp đầu tiên
        if (!$maLop) {
            $maLop = $classes[0]['maLop'];
        }
        
        // Lấy thông tin lớp
        $classInfo = $this->model->getClassInfo($maLop);
        
        // Kiểm tra quyền
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            return ['error' => 'Bạn không có quyền xem thông tin lớp này'];
        }
        
        // Lấy thông tin học kỳ và năm học
        $currentYear = date('Y');
        $namHoc = ($currentYear - 1) . '-' . $currentYear;
        $hocKy = (date('m') <= 6) ? 2 : 1;
        
        return [
            'classes' => $classes,
            'classInfo' => $classInfo,
            'currentClassId' => $maLop,
            'hocKy' => $hocKy,
            'namHoc' => $namHoc,
            'maGV' => $maGV
        ];
    }
    
    /**
     * Lấy danh sách vi phạm
     */
    public function getViolations($maLop, $hocKy, $namHoc) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['error' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        $classInfo = $this->model->getClassInfo($maLop);
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            return ['error' => 'Bạn không có quyền truy cập'];
        }
        
        $students = $this->model->getStudentViolations($maLop, $hocKy, $namHoc);
        return ['students' => $students];
    }
    
    /**
     * Lấy chi tiết một vi phạm
     */
    public function getViolationDetail($maViPham) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['error' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        $violation = $this->model->getViolationById($maViPham);
        
        if (!$violation) {
            return ['error' => 'Không tìm thấy vi phạm'];
        }
        
        return ['violation' => $violation];
    }
    
    /**
     * Thêm vi phạm
     */
    public function addViolation($data) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        // Validate dữ liệu
        if (empty($data['maHS']) || empty($data['loaiViPham']) || empty($data['mucDoViPham'])) {
            return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin'];
        }
        
        // Thêm người phát hiện
        $data['nguoiPhatHien'] = $maGV;
        
        if ($this->model->addViolation($data)) {
            return ['success' => true, 'message' => 'Thêm vi phạm thành công'];
        } else {
            return ['success' => false, 'message' => 'Lỗi khi thêm vi phạm'];
        }
    }
    
    /**
     * Cập nhật vi phạm
     */
    public function updateViolation($maViPham, $data) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        // Validate dữ liệu
        if (empty($data['loaiViPham']) || empty($data['mucDoViPham']) || empty($data['hinhThucXuLy'])) {
            return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc'];
        }
        
        if ($this->model->updateViolation($maViPham, $data)) {
            return ['success' => true, 'message' => 'Cập nhật vi phạm thành công'];
        } else {
            $error = $this->model->getLastError();
            return ['success' => false, 'message' => 'Lỗi khi cập nhật vi phạm', 'debug' => $error];
        }
    }
    
    /**
     * Xóa vi phạm
     */
    public function deleteViolation($maViPham) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        if ($this->model->deleteViolation($maViPham)) {
            return ['success' => true, 'message' => 'Xóa vi phạm thành công'];
        } else {
            return ['success' => false, 'message' => 'Lỗi khi xóa vi phạm'];
        }
    }
}

// Xử lý request
require_once(__DIR__ . '/../model/mConnect.php');

$mConnect = new mConnect();
$conn = $mConnect->mConnect();

if (!$conn) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Kết nối cơ sở dữ liệu thất bại'], JSON_UNESCAPED_UNICODE);
    exit();
}

$controller = new ControllerStudentViolation($conn);

// Xử lý AJAX request
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    // Enable error display as JSON
    error_reporting(E_ALL);
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        http_response_code(500);
        echo json_encode([
            'error' => 'PHP Error: ' . $errstr,
            'file' => $errfile,
            'line' => $errline
        ], JSON_UNESCAPED_UNICODE);
        exit();
    });
    
    // GET requests
    if (isset($_GET['action'])) {
        $action = trim($_GET['action']);
        
        switch ($action) {
            case 'getViolations':
                $maLop = intval($_GET['maLop'] ?? 0);
                $hocKy = intval($_GET['hocKy'] ?? 1);
                $namHoc = $_GET['namHoc'] ?? '';
                
                $result = $controller->getViolations($maLop, $hocKy, $namHoc);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            case 'getDetail':
                $maViPham = intval($_GET['maViPham'] ?? 0);
                $result = $controller->getViolationDetail($maViPham);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            default:
                echo json_encode(['error' => 'Action không hợp lệ: ' . $action], JSON_UNESCAPED_UNICODE);
                break;
        }
        exit(); // Exit sau GET requests
    }
    
    // POST requests
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $data = [
                    'maHS' => intval($_POST['maHS'] ?? 0),
                    'loaiViPham' => trim($_POST['loaiViPham'] ?? ''),
                    'mucDoViPham' => trim($_POST['mucDoViPham'] ?? ''),
                    'noiDungViPham' => trim($_POST['noiDungViPham'] ?? ''),
                    'hinhThucXuLy' => trim($_POST['hinhThucXuLy'] ?? ''),
                    'ngayViPham' => $_POST['ngayViPham'] ?? date('Y-m-d'),
                    'hocKy' => intval($_POST['hocKy'] ?? 1),
                    'namHoc' => trim($_POST['namHoc'] ?? '')
                ];
                
                $result = $controller->addViolation($data);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            case 'update':
                $maViPham = intval($_POST['maViPham'] ?? 0);
                $data = [
                    'loaiViPham' => trim($_POST['loaiViPham'] ?? ''),
                    'mucDoViPham' => trim($_POST['mucDoViPham'] ?? ''),
                    'noiDungViPham' => trim($_POST['noiDungViPham'] ?? ''),
                    'hinhThucXuLy' => trim($_POST['hinhThucXuLy'] ?? ''),
                    'ngayViPham' => $_POST['ngayViPham'] ?? date('Y-m-d')
                ];
                
                $result = $controller->updateViolation($maViPham, $data);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            case 'delete':
                $maViPham = intval($_POST['maViPham'] ?? 0);
                $result = $controller->deleteViolation($maViPham);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            default:
                echo json_encode(['error' => 'Action không hợp lệ'], JSON_UNESCAPED_UNICODE);
                break;
        }
    }
    
    exit();
}

// Hiển thị trang - chỉ khi KHÔNG phải AJAX request
if (!isset($_GET['action']) && !isset($_POST['action'])) {
    $maLop = intval($_GET['maLop'] ?? 0);
    $data = $controller->showViolations($maLop);

    require_once(__DIR__ . '/../view/teacher/vStudentViolation.php');
}
