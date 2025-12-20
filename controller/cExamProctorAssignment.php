<?php
/**
 * Controller: Phân công coi thi (Exam Proctor Assignment)
 * Chức năng: Xử lý các yêu cầu phân công giáo viên coi thi
 * Actor: Tổ trưởng bộ môn (TTBM)
 * 
 * @author IMAX Team
 * @version 1.0
 * @date 2025-12-21
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập và quyền TTBM
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'ttbm') {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once(__DIR__ . '/../model/mExamProctorAssignment.php');

class cExamProctorAssignment
{
    private $model;
    private $ttbmInfo;

    public function __construct()
    {
        $this->model = new mExamProctorAssignment();
        
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
            // Khối
            case 'grades':
                $this->getGrades();
                break;
            
            // Lớp
            case 'classes':
                $this->getClasses();
                break;
            
            // Kỳ thi
            case 'exam-periods':
                $this->getExamPeriods();
                break;
            
            // Môn thi
            case 'subjects':
                $this->getSubjects();
                break;
            
            // Ca thi / Lịch thi
            case 'exam-schedules':
                $this->getExamSchedules();
                break;
            
            // Giáo viên
            case 'teachers':
                $this->getTeachers();
                break;
            
            // GV đã phân công
            case 'assigned-teachers':
                $this->getAssignedTeachers();
                break;
            
            // Kiểm tra trùng lịch
            case 'check-conflict':
                $this->checkConflict();
                break;
            
            // Phân công
            case 'create-assignment':
                $this->createAssignment();
                break;
            
            // Xóa phân công
            case 'delete-assignment':
                $this->deleteAssignment();
                break;
            
            // Phân công tự động
            case 'auto-assign':
                $this->autoAssign();
                break;
            
            // Thông tin TTBM
            case 'ttbm-info':
                $this->getTTBMInfo();
                break;
            
            // Thống kê
            case 'statistics':
                $this->getStatistics();
                break;
            
            // Năm học
            case 'school-years':
                $this->getSchoolYears();
                break;
            
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Action không hợp lệ'], 400);
        }
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
     * Lấy danh sách lớp theo khối
     */
    private function getClasses()
    {
        $maKhoi = isset($_GET['maKhoi']) ? intval($_GET['maKhoi']) : 0;
        
        if ($maKhoi <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Mã khối không hợp lệ'], 400);
            return;
        }
        
        $classes = $this->model->getClassesByGrade($maKhoi);
        $this->jsonResponse(['success' => true, 'data' => $classes]);
    }

    /**
     * Lấy danh sách kỳ thi
     */
    private function getExamPeriods()
    {
        $maKhoi = isset($_GET['maKhoi']) ? intval($_GET['maKhoi']) : null;
        
        $examPeriods = $this->model->getExamPeriodsByGrade($maKhoi);
        $this->jsonResponse(['success' => true, 'data' => $examPeriods]);
    }

    /**
     * Lấy danh sách môn thi theo kỳ thi
     */
    private function getSubjects()
    {
        $maKyThi = isset($_GET['maKyThi']) ? intval($_GET['maKyThi']) : 0;
        
        if ($maKyThi <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Mã kỳ thi không hợp lệ'], 400);
            return;
        }
        
        $subjects = $this->model->getSubjectsByExam($maKyThi);
        $this->jsonResponse(['success' => true, 'data' => $subjects]);
    }

    /**
     * Lấy danh sách ca thi / lịch thi
     */
    private function getExamSchedules()
    {
        $maKyThi = isset($_GET['maKyThi']) ? intval($_GET['maKyThi']) : 0;
        $maMonHoc = isset($_GET['maMonHoc']) ? intval($_GET['maMonHoc']) : null;
        $maLop = isset($_GET['maLop']) ? intval($_GET['maLop']) : null;
        
        if ($maKyThi <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Mã kỳ thi không hợp lệ'], 400);
            return;
        }
        
        $schedules = $this->model->getExamSchedules($maKyThi, $maMonHoc, $maLop);
        $this->jsonResponse(['success' => true, 'data' => $schedules]);
    }

    /**
     * Lấy danh sách giáo viên có thể coi thi
     */
    private function getTeachers()
    {
        $ngayThi = isset($_GET['ngayThi']) ? $_GET['ngayThi'] : null;
        $gioBatDau = isset($_GET['gioBatDau']) ? $_GET['gioBatDau'] : null;
        $gioKetThuc = isset($_GET['gioKetThuc']) ? $_GET['gioKetThuc'] : null;
        $chuaPhanCong = isset($_GET['chuaPhanCong']) && $_GET['chuaPhanCong'] === 'true';
        
        $teachers = $this->model->getAvailableTeachers(
            $this->ttbmInfo['toBoMon'],
            $ngayThi,
            $gioBatDau,
            $gioKetThuc,
            $chuaPhanCong
        );
        
        $this->jsonResponse(['success' => true, 'data' => $teachers]);
    }

    /**
     * Lấy danh sách GV đã phân công cho ca thi
     */
    private function getAssignedTeachers()
    {
        $maPhong = isset($_GET['maPhong']) ? intval($_GET['maPhong']) : 0;
        $ngayThi = isset($_GET['ngayThi']) ? $_GET['ngayThi'] : '';
        $gioBatDau = isset($_GET['gioBatDau']) ? $_GET['gioBatDau'] : '';
        
        if (!$maPhong || !$ngayThi || !$gioBatDau) {
            $this->jsonResponse(['success' => false, 'message' => 'Thiếu thông tin'], 400);
            return;
        }
        
        $teachers = $this->model->getAssignedTeachers($maPhong, $ngayThi, $gioBatDau);
        $this->jsonResponse(['success' => true, 'data' => $teachers]);
    }

    /**
     * Kiểm tra GV có bị trùng lịch không
     */
    private function checkConflict()
    {
        $maGV = isset($_GET['maGV']) ? intval($_GET['maGV']) : 0;
        $ngayThi = isset($_GET['ngayThi']) ? $_GET['ngayThi'] : '';
        $gioBatDau = isset($_GET['gioBatDau']) ? $_GET['gioBatDau'] : '';
        $gioKetThuc = isset($_GET['gioKetThuc']) ? $_GET['gioKetThuc'] : '';
        
        if (!$maGV || !$ngayThi || !$gioBatDau || !$gioKetThuc) {
            $this->jsonResponse(['success' => false, 'message' => 'Thiếu thông tin'], 400);
            return;
        }
        
        $conflicts = $this->model->checkTeacherConflict($maGV, $ngayThi, $gioBatDau, $gioKetThuc);
        
        $this->jsonResponse([
            'success' => true, 
            'hasConflict' => !empty($conflicts),
            'conflicts' => $conflicts
        ]);
    }

    /**
     * Tạo phân công coi thi
     */
    private function createAssignment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method không hợp lệ'], 405);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $required = ['maLichThi', 'maMonHoc', 'maPhong', 'loaiKyThi', 'ngayThi', 'gioBatDau', 'gioKetThuc', 'teachers'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                $this->jsonResponse(['success' => false, 'message' => "Thiếu trường: $field"], 400);
                return;
            }
        }
        
        if (empty($input['teachers']) || !is_array($input['teachers'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Chưa chọn giáo viên'], 400);
            return;
        }
        
        $assignedCount = 0;
        $errors = [];
        
        foreach ($input['teachers'] as $index => $maGV) {
            // Kiểm tra trùng lịch
            $conflicts = $this->model->checkTeacherConflict(
                $maGV,
                $input['ngayThi'],
                $input['gioBatDau'],
                $input['gioKetThuc']
            );
            
            if (!empty($conflicts)) {
                $errors[] = "Giáo viên (ID: $maGV) đã được phân công cho ca thi khác trùng thời gian";
                continue;
            }
            
            $data = [
                'maGV' => $maGV,
                'maLop' => $input['maLop'] ?? 0,
                'maMonHoc' => $input['maMonHoc'],
                'maPhong' => $input['maPhong'],
                'loaiKyThi' => $input['loaiKyThi'],
                'ngayThi' => $input['ngayThi'],
                'gioBatDau' => $input['gioBatDau'],
                'gioKetThuc' => $input['gioKetThuc'],
                'viTriCoiThi' => 'Giám thị ' . ($index + 1)
            ];
            
            $insertId = $this->model->createProctorAssignment($data);
            
            if ($insertId) {
                $assignedCount++;
                
                // Gửi thông báo
                $noiDung = "Bạn được phân công coi thi vào ngày {$input['ngayThi']} "
                         . "từ {$input['gioBatDau']} đến {$input['gioKetThuc']}.";
                $this->model->sendNotificationToTeacher($maGV, $noiDung);
            } else {
                $errors[] = "Lỗi khi phân công giáo viên (ID: $maGV)";
            }
        }
        
        if ($assignedCount > 0) {
            $this->jsonResponse([
                'success' => true,
                'message' => "Phân công thành công $assignedCount giáo viên",
                'assignedCount' => $assignedCount,
                'errors' => $errors
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Phân công không thành công',
                'errors' => $errors
            ]);
        }
    }

    /**
     * Xóa phân công
     */
    private function deleteAssignment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method không hợp lệ'], 405);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $maPhanCong = isset($input['maPhanCong']) ? intval($input['maPhanCong']) : 0;
        
        if ($maPhanCong <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Mã phân công không hợp lệ'], 400);
            return;
        }
        
        $result = $this->model->deleteProctorAssignment($maPhanCong);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Đã xóa phân công']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Lỗi khi xóa phân công']);
        }
    }

    /**
     * Phân công tự động
     */
    private function autoAssign()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method không hợp lệ'], 405);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $maKyThi = isset($input['maKyThi']) ? intval($input['maKyThi']) : 0;
        $maMonHoc = isset($input['maMonHoc']) ? intval($input['maMonHoc']) : null;
        $soGVMoiCa = isset($input['soGVMoiCa']) ? intval($input['soGVMoiCa']) : 2;
        
        if ($maKyThi <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Mã kỳ thi không hợp lệ'], 400);
            return;
        }
        
        $results = $this->model->autoAssignProctors(
            $maKyThi,
            $maMonHoc,
            $this->ttbmInfo['toBoMon'],
            $soGVMoiCa
        );
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Phân công tự động hoàn tất',
            'assignedCount' => count($results),
            'details' => $results
        ]);
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
    $controller = new cExamProctorAssignment();
    $controller->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi server: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
