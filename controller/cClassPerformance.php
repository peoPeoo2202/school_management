<?php
session_start();
require_once(__DIR__ . '/../model/mClassPerformance.php');

class ControllerClassPerformance {
    private $model;
    
    public function __construct($connection) {
        $this->model = new ModelClassPerformance($connection);
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
     * Hiển thị kết quả học tập và rèn luyện của lớp
     */
    public function showClassPerformance($maLop = null) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['error' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        // Lấy danh sách lớp chủ nhiệm của giáo viên
        $classes = $this->model->getClassesByTeacher($maGV);
        
        // Nếu không có maLop, lấy lớp đầu tiên của giáo viên
        if (!$maLop) {
            if (empty($classes)) {
                return ['error' => 'Bạn chưa được phân công làm giáo viên chủ nhiệm lớp nào'];
            }
            $maLop = $classes[0]['maLop'];
        }
        
        // Lấy thông tin lớp
        $classInfo = $this->model->getClassInfo($maLop);
        
        // Kiểm tra giáo viên có phải là GVCN của lớp này không
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            return ['error' => 'Bạn không có quyền xem thông tin của lớp này'];
        }
        
        // Lấy thông tin học kỳ và năm học hiện tại
        $currentYear = date('Y');
        $namHoc = ($currentYear - 1) . '-' . $currentYear;
        $hocKy = (date('m') <= 6) ? 2 : 1;
        
        // Lấy các thống kê
        $averageScore = $this->model->getClassAverageScore($maLop, $hocKy, $namHoc);
        $yearlyAverage = $this->model->getClassYearlyAverage($maLop, $namHoc);
        $awardsCount = $this->model->getAwardsCount($maLop, $hocKy, $namHoc);
        $awardsByLevel = $this->model->getAwardsByLevel($maLop, $hocKy, $namHoc);
        
        // Lấy danh sách học sinh được khen thưởng theo cấp (chi tiết)
        $awardsByLevelDetail = [];
        foreach ($awardsByLevel as $award) {
            $capKhenThuong = $award['capKhenThuong'];
            $awardsByLevelDetail[$capKhenThuong] = $this->model->getAwardsByLevelDetail($maLop, $hocKy, $namHoc, $capKhenThuong);
        }
        
        $violationsCount = $this->model->getViolationsCount($maLop, $hocKy, $namHoc);
        
        // Lấy danh sách học sinh vi phạm theo mức độ
        $violationsNhe = $this->model->getViolationsByLevel($maLop, $hocKy, $namHoc, 'Nhe');
        $violationsTB = $this->model->getViolationsByLevel($maLop, $hocKy, $namHoc, 'Trung binh');
        $violationsNang = $this->model->getViolationsByLevel($maLop, $hocKy, $namHoc, 'Nang');
        
        $conductStats = $this->model->getConductStatistics($maLop, $hocKy, $namHoc);
        $academicStats = $this->model->getAcademicStatistics($maLop, $hocKy, $namHoc);
        
        // Lấy thống kê nghỉ học
        $absenceStats = $this->model->getAbsenceStatistics($maLop, $hocKy, $namHoc);
        $topAbsentStudents = $this->model->getTopAbsentStudents($maLop, $hocKy, $namHoc, 5);
        
        return [
            'classes' => $classes,
            'classInfo' => $classInfo,
            'currentClassId' => $maLop,
            'hocKy' => $hocKy,
            'namHoc' => $namHoc,
            'averageScore' => $averageScore,
            'yearlyAverage' => $yearlyAverage,
            'awardsCount' => $awardsCount,
            'awardsByLevel' => $awardsByLevel,
            'awardsByLevelDetail' => $awardsByLevelDetail,
            'violationsCount' => $violationsCount,
            'violationsNhe' => $violationsNhe,
            'violationsTB' => $violationsTB,
            'violationsNang' => $violationsNang,
            'conductStats' => $conductStats,
            'academicStats' => $academicStats,
            'absenceStats' => $absenceStats,
            'topAbsentStudents' => $topAbsentStudents
        ];
    }
}

// Xử lý request
require_once(__DIR__ . '/../model/mConnect.php');

// Tạo kết nối database sử dụng mConnect
$mConnect = new mConnect();
$conn = $mConnect->mConnect();

if (!$conn) {
    die("Kết nối thất bại!");
}

$controller = new ControllerClassPerformance($conn);

// Lấy maLop từ URL hoặc lấy lớp đầu tiên
$maLop = $_GET['maLop'] ?? null;
$data = $controller->showClassPerformance($maLop);
include(__DIR__ . '/../view/teacher/vClassPerformance.php');

$conn->close();
?>
