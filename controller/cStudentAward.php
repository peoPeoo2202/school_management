<?php
session_start();
require_once(__DIR__ . '/../model/mStudentAward.php');

class ControllerStudentAward {
    private $model;
    
    public function __construct($connection) {
        $this->model = new ModelStudentAward($connection);
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
     * Hiển thị trang quản lý khen thưởng
     */
    public function showAwards($maLop = null) {
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
            'namHoc' => $namHoc
        ];
    }
    
    /**
     * Lấy danh sách khen thưởng
     */
    public function getAwards($maLop, $hocKy, $namHoc) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['error' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        $classInfo = $this->model->getClassInfo($maLop);
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            return ['error' => 'Bạn không có quyền truy cập'];
        }
        
        $students = $this->model->getStudentAwards($maLop, $hocKy, $namHoc);
        return ['students' => $students];
    }
    
    /**
     * Thêm khen thưởng
     */
    public function addAward($data) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        // Validate dữ liệu
        if (empty($data['maHS']) || empty($data['lyDo']) || empty($data['capKhenThuong'])) {
            return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin'];
        }
        
        if ($this->model->addAward($data)) {
            return ['success' => true, 'message' => 'Thêm khen thưởng thành công'];
        } else {
            return ['success' => false, 'message' => 'Lỗi khi thêm khen thưởng'];
        }
    }
    
    /**
     * Cập nhật khen thưởng
     */
    public function updateAward($maKhenThuong, $data) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        if ($this->model->updateAward($maKhenThuong, $data)) {
            return ['success' => true, 'message' => 'Cập nhật khen thưởng thành công'];
        } else {
            return ['success' => false, 'message' => 'Lỗi khi cập nhật khen thưởng'];
        }
    }
    
    /**
     * Xóa khen thưởng
     */
    public function deleteAward($maKhenThuong) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        if ($this->model->deleteAward($maKhenThuong)) {
            return ['success' => true, 'message' => 'Xóa khen thưởng thành công'];
        } else {
            return ['success' => false, 'message' => 'Lỗi khi xóa khen thưởng'];
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

$controller = new ControllerStudentAward($conn);

// Xử lý AJAX request
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    // GET requests
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'getAwards':
                $maLop = intval($_GET['maLop'] ?? 0);
                $hocKy = intval($_GET['hocKy'] ?? 1);
                $namHoc = $_GET['namHoc'] ?? '';
                
                $result = $controller->getAwards($maLop, $hocKy, $namHoc);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            default:
                echo json_encode(['error' => 'Action không hợp lệ'], JSON_UNESCAPED_UNICODE);
                break;
        }
    }
    
    // POST requests
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
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
                
                $result = $controller->addAward($data);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            case 'update':
                $maKhenThuong = intval($_POST['maKhenThuong'] ?? 0);
                $data = [
                    'lyDo' => trim($_POST['lyDo'] ?? ''),
                    'capKhenThuong' => trim($_POST['capKhenThuong'] ?? ''),
                    'ngayKhen' => $_POST['ngayKhen'] ?? date('Y-m-d'),
                    'hinhThuc' => trim($_POST['hinhThuc'] ?? ''),
                    'linhVuc' => trim($_POST['linhVuc'] ?? '')
                ];
                
                $result = $controller->updateAward($maKhenThuong, $data);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            case 'delete':
                $maKhenThuong = intval($_POST['maKhenThuong'] ?? 0);
                $result = $controller->deleteAward($maKhenThuong);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            default:
                echo json_encode(['error' => 'Action không hợp lệ'], JSON_UNESCAPED_UNICODE);
                break;
        }
    }
    
    $conn->close();
    exit();
}

// Hiển thị trang (chỉ khi không phải AJAX request)
$maLop = $_GET['maLop'] ?? null;
$data = $controller->showAwards($maLop);
include(__DIR__ . '/../view/teacher/vStudentAward.php');

$conn->close();
?>