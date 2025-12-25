<?php
/**
 * Controller: Phân công chấm thi theo Kỳ thi (Exam Grading Assignment)
 * Chức năng: Xử lý các yêu cầu phân công giáo viên chấm điểm theo kỳ thi
 * Actor: Tổ trưởng bộ môn (TTBM)
 * 
 * @author IMAX Team
 * @version 1.0
 * @date 2025-12-20
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Tạm thời log session để kiểm tra
error_log("Session maTaiKhoan: " . ($_SESSION['maTaiKhoan'] ?? 'NOT SET'));
error_log("Session loaiTaiKhoan: " . ($_SESSION['loaiTaiKhoan'] ?? 'NOT SET'));

// Kiểm tra đăng nhập và quyền TTBM
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'ttbm') {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập. Session: ' . ($_SESSION['loaiTaiKhoan'] ?? 'NULL')], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once(__DIR__ . '/../model/mExamGradingAssignment.php');

class cExamGradingAssignment
{
    private $model;
    private $ttbmInfo;

    public function __construct()
    {
        $this->model = new mExamGradingAssignment();
        
        // Lấy thông tin TTBM
        $this->ttbmInfo = $this->model->getTTBMInfo($_SESSION['maTaiKhoan']);
        
        if (!$this->ttbmInfo) {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy thông tin Tổ trưởng bộ môn']);
            exit();
        }
    }

    /**
     * Route các action
     */
    public function handleRequest()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        switch ($action) {
            // Kỳ thi
            case 'exam-periods':
                $this->getExamPeriods();
                break;
            case 'exam-period-detail':
                $this->getExamPeriodDetail();
                break;
            
            // Lớp học
            case 'classes':
                $this->getClasses();
                break;
            
            // Môn học
            case 'subjects':
                $this->getSubjects();
                break;
            
            // Bài thi cần chấm
            case 'exam-papers':
                $this->getExamPapers();
                break;
            
            // Giáo viên
            case 'teachers':
                $this->getTeachers();
                break;
            
            // Phân công
            case 'create-assignment':
                $this->createAssignment();
                break;
            case 'assignments':
                $this->getAssignments();
                break;
            case 'delete-assignment':
                $this->deleteAssignment();
                break;
            
            // Thông tin TTBM
            case 'ttbm-info':
                $this->getTTBMInfo();
                break;
            
            // Thống kê
            case 'statistics':
                $this->getStatistics();
                break;
            
            // Dữ liệu phụ trợ
            case 'grades':
                $this->getGrades();
                break;
            case 'school-years':
                $this->getSchoolYears();
                break;
            
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Action không hợp lệ'], 400);
        }
    }

    /**
     * Lấy danh sách kỳ thi
     */
    private function getExamPeriods()
    {
        $filters = [];
        
        if (!empty($_GET['maKhoi'])) {
            $filters['maKhoi'] = intval($_GET['maKhoi']);
        }
        if (!empty($_GET['hocKy'])) {
            $filters['hocKy'] = intval($_GET['hocKy']);
        }
        if (!empty($_GET['namHoc'])) {
            $filters['namHoc'] = $_GET['namHoc'];
        }
        if (!empty($_GET['trangThai'])) {
            $filters['trangThai'] = $_GET['trangThai'];
        }

        $examPeriods = $this->model->getExamPeriods($filters);
        $this->jsonResponse(['success' => true, 'data' => $examPeriods]);
    }

    /**
     * Lấy chi tiết kỳ thi
     */
    private function getExamPeriodDetail()
    {
        $maKyThi = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maKyThi <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID kỳ thi không hợp lệ'], 400);
            return;
        }

        $examPeriod = $this->model->getExamPeriodById($maKyThi);

        if ($examPeriod) {
            $this->jsonResponse(['success' => true, 'data' => $examPeriod]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy kỳ thi'], 404);
        }
    }

    /**
     * Lấy danh sách lớp theo khối
     */
    private function getClasses()
    {
        $maKhoi = isset($_GET['maKhoi']) ? intval($_GET['maKhoi']) : null;
        
        $classes = $this->model->getClassesByGrade($maKhoi);
        $this->jsonResponse(['success' => true, 'data' => $classes]);
    }

    /**
     * Lấy danh sách môn học
     */
    private function getSubjects()
    {
        $byDepartment = isset($_GET['byDepartment']) && $_GET['byDepartment'] === 'true';
        
        if ($byDepartment && $this->ttbmInfo['toBoMon']) {
            $subjects = $this->model->getSubjectsByDepartment($this->ttbmInfo['toBoMon']);
        } else {
            $subjects = $this->model->getAllSubjects();
        }
        
        $this->jsonResponse(['success' => true, 'data' => $subjects]);
    }

    /**
     * Lấy danh sách bài thi cần chấm
     */
    private function getExamPapers()
    {
        $maKyThi = isset($_GET['maKyThi']) ? intval($_GET['maKyThi']) : 0;
        $maLop = isset($_GET['maLop']) ? intval($_GET['maLop']) : 0;
        $maMonHoc = isset($_GET['maMonHoc']) ? intval($_GET['maMonHoc']) : 0;

        if ($maKyThi <= 0 || $maLop <= 0 || $maMonHoc <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Thiếu thông tin tìm kiếm'], 400);
            return;
        }

        $papers = $this->model->getExamPapersToGrade($maKyThi, $maLop, $maMonHoc);
        $count = $this->model->countExamPapers($maKyThi, $maLop, $maMonHoc);
        $examPeriod = $this->model->getExamPeriodById($maKyThi);

        $this->jsonResponse([
            'success' => true, 
            'data' => $papers,
            'count' => $count,
            'examPeriod' => $examPeriod
        ]);
    }

    /**
     * Lấy danh sách giáo viên để phân công
     */
    private function getTeachers()
    {
        $maMonHoc = isset($_GET['maMonHoc']) ? intval($_GET['maMonHoc']) : 0;
        $maKyThi = isset($_GET['maKyThi']) ? intval($_GET['maKyThi']) : null;
        $maLop = isset($_GET['maLop']) ? intval($_GET['maLop']) : null;
        $chuaPhanCong = isset($_GET['chuaPhanCong']) && $_GET['chuaPhanCong'] === 'true';

        if ($maMonHoc <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Mã môn học không hợp lệ'], 400);
            return;
        }

        if (!$this->ttbmInfo['toBoMon']) {
            $this->jsonResponse(['success' => false, 'message' => 'Không xác định được tổ bộ môn'], 400);
            return;
        }

        $teachers = $this->model->getTeachersForGrading(
            $maMonHoc, 
            $this->ttbmInfo['toBoMon'],
            $chuaPhanCong,
            $maKyThi,
            $maLop
        );

        $this->jsonResponse(['success' => true, 'data' => $teachers]);
    }

    /**
     * Tạo phân công chấm thi
     */
    private function createAssignment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Phương thức không hợp lệ'], 405);
            return;
        }

        // Lấy dữ liệu từ request
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Validate dữ liệu
        $required = ['maKyThi', 'maLop', 'maMonHoc', 'teachers'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_array($data[$field]) && empty($data[$field])) || (!is_array($data[$field]) && $data[$field] === '')) {
                $this->jsonResponse(['success' => false, 'message' => "Thiếu trường: $field"], 400);
                return;
            }
        }

        // Kiểm tra teachers là mảng
        if (!is_array($data['teachers']) || empty($data['teachers'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Vui lòng chọn ít nhất một giáo viên'], 400);
            return;
        }

        // Giới hạn tối đa 2 giáo viên
        if (count($data['teachers']) > 2) {
            $this->jsonResponse(['success' => false, 'message' => 'Chỉ được phân công tối đa 2 giáo viên'], 400);
            return;
        }

        // Kiểm tra giáo viên có thuộc tổ bộ môn không
        foreach ($data['teachers'] as $maGV) {
            $teachers = $this->model->getTeachersForGrading(
                $data['maMonHoc'], 
                $this->ttbmInfo['toBoMon']
            );
            $teacherIds = array_column($teachers, 'maGV');
            
            if (!in_array(intval($maGV), $teacherIds)) {
                $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Giáo viên không thuộc tổ bộ môn hoặc không dạy môn này'
                ], 403);
                return;
            }
        }

        // Tạo phân công
        $result = $this->model->createGradingAssignment($data);

        if ($result['success']) {
            $this->jsonResponse($result);
        } else {
            $this->jsonResponse($result, 500);
        }
    }

    /**
     * Lấy danh sách phân công theo kỳ thi
     */
    private function getAssignments()
    {
        $maKyThi = isset($_GET['maKyThi']) ? intval($_GET['maKyThi']) : 0;

        if ($maKyThi <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Mã kỳ thi không hợp lệ'], 400);
            return;
        }

        $assignments = $this->model->getAssignmentsByExamPeriod($maKyThi, $this->ttbmInfo['toBoMon']);
        $this->jsonResponse(['success' => true, 'data' => $assignments]);
    }

    /**
     * Xóa phân công
     */
    private function deleteAssignment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->jsonResponse(['success' => false, 'message' => 'Phương thức không hợp lệ'], 405);
            return;
        }

        $maPhanCong = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maPhanCong <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID phân công không hợp lệ'], 400);
            return;
        }

        $result = $this->model->deleteAssignment($maPhanCong);

        if ($result['success']) {
            $this->jsonResponse($result);
        } else {
            $this->jsonResponse($result, 500);
        }
    }

    /**
     * Lấy thông tin TTBM
     */
    private function getTTBMInfo()
    {
        $this->jsonResponse(['success' => true, 'data' => $this->ttbmInfo]);
    }

    /**
     * Lấy thống kê
     */
    private function getStatistics()
    {
        $stats = $this->model->getStatistics($this->ttbmInfo['toBoMon']);
        $this->jsonResponse(['success' => true, 'data' => $stats]);
    }

    /**
     * Lấy danh sách khối
     */
    private function getGrades()
    {
        $grades = $this->model->getAllGrades();
        $this->jsonResponse(['success' => true, 'data' => $grades]);
    }

    /**
     * Lấy danh sách năm học
     */
    private function getSchoolYears()
    {
        $years = $this->model->getSchoolYears();
        $this->jsonResponse(['success' => true, 'data' => $years]);
    }

    /**
     * Trả về JSON response
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Khởi tạo controller và xử lý request
try {
    $controller = new cExamGradingAssignment();
    $controller->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi server: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
