<?php
session_start();
require_once(__DIR__ . '/../model/mListofStudents.php');

class ControllerListofStudents {
    private $model;
    
    public function __construct($connection) {
        $this->model = new ModelListofStudents($connection);
    }
    
    /**
     * Kiểm tra quyền truy cập của giáo viên
     */
    private function checkTeacherAccess() {
        if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] != 'giaovien') {
            header('Location: ../index.php');
            exit();
        }
        return $_SESSION['maGV'] ?? null;
    }
    
    /**
     * Hiển thị danh sách học sinh của lớp chủ nhiệm
     */
    public function showStudents($maLop = null) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['error' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        // Nếu không có maLop, lấy lớp đầu tiên của giáo viên
        if (!$maLop) {
            $classes = $this->model->getClassesByTeacher($maGV);
            if (empty($classes)) {
                return ['error' => 'Bạn chưa được phân công làm giáo viên chủ nhiệm lớp nào'];
            }
            $maLop = $classes[0]['maLop'];
        }
        
        // Lấy thông tin lớp
        $classInfo = $this->model->getClassInfo($maLop);
        
        // Kiểm tra giáo viên có phải là GVCN của lớp này không
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            return ['error' => 'Bạn không có quyền xem danh sách học sinh của lớp này'];
        }
        
        // Lấy danh sách học sinh
        $students = $this->model->getStudentsByClass($maLop);
        
        // Lấy thêm thông tin điểm và hạnh kiểm
        $currentYear = date('Y');
        $namHoc = ($currentYear - 1) . '-' . $currentYear;
        $hocKy = (date('m') <= 6) ? 2 : 1;
        
        foreach ($students as &$student) {
            $student['diemTB'] = $this->model->getStudentAverageScore(
                $student['maHS'], 
                $hocKy, 
                $namHoc
            );
            $student['hanhKiem'] = $this->model->getStudentConduct(
                $student['maHS'], 
                $hocKy, 
                $namHoc
            );
        }
        
        return [
            'classInfo' => $classInfo,
            'students' => $students,
            'hocKy' => $hocKy,
            'namHoc' => $namHoc
        ];
    }
    
    /**
     * Lấy thông tin chi tiết học sinh
     */
    public function getStudentDetail($maHS) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['error' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        // Lấy thông tin chi tiết học sinh
        $studentInfo = $this->model->getStudentDetail($maHS);
        
        if (!$studentInfo) {
            return ['error' => 'Không tìm thấy thông tin học sinh'];
        }
        
        // Kiểm tra quyền truy cập (chỉ GVCN của lớp mới được xem)
        $classInfo = $this->model->getClassInfo($studentInfo['maLop']);
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            return ['error' => 'Bạn không có quyền xem thông tin học sinh này'];
        }
        
        // Lấy thêm thông tin điểm, vi phạm, khen thưởng
        $currentYear = date('Y');
        $namHoc = ($currentYear - 1) . '-' . $currentYear;
        
        $grades = $this->model->getStudentGrades($maHS, $namHoc);
        $violations = $this->model->getStudentViolations($maHS);
        $awards = $this->model->getStudentAwards($maHS);
        
        return [
            'student' => $studentInfo,
            'grades' => $grades,
            'violations' => $violations,
            'awards' => $awards
        ];
    }
}

// Xử lý request
// Sử dụng file config.php hiện có
require_once(__DIR__ . '/../config.php');

// Tạo kết nối database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$controller = new ControllerListofStudents($conn);

// Xử lý AJAX request cho chi tiết học sinh
if (isset($_GET['action']) && $_GET['action'] == 'getDetail' && isset($_GET['maHS'])) {
    header('Content-Type: application/json');
    $maHS = intval($_GET['maHS']);
    $result = $controller->getStudentDetail($maHS);
    echo json_encode($result);
    exit();
}

// Lấy maLop từ URL hoặc lấy lớp đầu tiên
$maLop = $_GET['maLop'] ?? null;
$data = $controller->showStudents($maLop);
include(__DIR__ . '/../view/teacher/vListofStudents.php');

$conn->close();
?>
