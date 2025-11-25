<?php
/**
 * Controller: Grading Assignment for TTBM
 * Purpose: Quản lý phân công chấm thi (CRUD)
 * Permissions: Chỉ dành cho Tổ trưởng bộ môn (TTBM)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../model/mGradingAssignment.php');

class cGradingAssignment
{
    private $mGrading;
    private $currentUser;
    private $ttbmInfo;

    public function __construct()
    {
        $this->mGrading = new mGradingAssignment();
        
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['maTaiKhoan'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Chưa đăng nhập'], 401);
            exit;
        }

        // Kiểm tra quyền TTBM
        if ($_SESSION['loaiTaiKhoan'] !== 'ttbm') {
            $this->jsonResponse(['success' => false, 'message' => 'Không có quyền truy cập'], 403);
            exit;
        }

        $this->currentUser = $_SESSION['maTaiKhoan'];
        
        // Lấy thông tin TTBM
        $this->ttbmInfo = $this->mGrading->getTTBMInfo($this->currentUser);
        
        if (!$this->ttbmInfo) {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy thông tin TTBM'], 403);
            exit;
        }
    }

    /**
     * Routing chính
     */
    public function handleRequest()
    {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'list':
                $this->listAssignments();
                break;
            case 'get':
                $this->getAssignment();
                break;
            case 'create':
                $this->createAssignment();
                break;
            case 'update':
                $this->updateAssignment();
                break;
            case 'delete':
                $this->deleteAssignment();
                break;
            case 'teachers':
                $this->getTeachers();
                break;
            case 'classes':
                $this->getClasses();
                break;
            case 'subjects':
                $this->getSubjects();
                break;
            case 'ttbm-info':
                $this->getTTBMInfo();
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Action không hợp lệ'], 400);
        }
    }

    /**
     * Danh sách phân công
     */
    private function listAssignments()
    {
        $filters = [
            'maGV' => $_GET['maGV'] ?? '',
            'maLop' => $_GET['maLop'] ?? '',
            'maMonHoc' => $_GET['maMonHoc'] ?? '',
            'loaiKiemTra' => $_GET['loaiKiemTra'] ?? '',
            'trangThai' => $_GET['trangThai'] ?? '',
            'toBoMon' => $this->ttbmInfo['toBoMon'] // Chỉ xem phân công của tổ mình
        ];

        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

        $assignments = $this->mGrading->getAllAssignments($filters, $page, $limit);
        $total = $this->mGrading->countAssignments($filters);

        $this->jsonResponse([
            'success' => true,
            'data' => $assignments,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'per_page' => $limit
            ]
        ]);
    }

    /**
     * Lấy chi tiết 1 phân công
     */
    private function getAssignment()
    {
        $maPhanCong = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maPhanCong <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $assignment = $this->mGrading->getAssignmentById($maPhanCong);

        if ($assignment) {
            // Kiểm tra xem phân công có thuộc tổ bộ môn của TTBM không
            if ($assignment['toBoMon'] !== $this->ttbmInfo['toBoMon']) {
                $this->jsonResponse(['success' => false, 'message' => 'Không có quyền xem phân công này'], 403);
                return;
            }
            
            $this->jsonResponse(['success' => true, 'data' => $assignment]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy phân công'], 404);
        }
    }

    /**
     * Tạo phân công mới
     */
    private function createAssignment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        // Validate required fields
        $required = ['maGV', 'maLop', 'maMonHoc', 'loaiKiemTra'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => "Thiếu trường bắt buộc: $field"
                ], 400);
                return;
            }
        }

        // Kiểm tra giáo viên có thuộc tổ bộ môn không
        $teachers = $this->mGrading->getTeachersByDepartment($this->ttbmInfo['toBoMon']);
        $teacherIds = array_column($teachers, 'maGV');
        
        if (!in_array(intval($_POST['maGV']), $teacherIds)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Giáo viên không thuộc tổ bộ môn của bạn'
            ], 403);
            return;
        }

        // Kiểm tra trùng lặp
        if ($this->mGrading->checkDuplicate(
            intval($_POST['maGV']),
            intval($_POST['maLop']),
            intval($_POST['maMonHoc']),
            $_POST['loaiKiemTra']
        )) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Phân công này đã tồn tại (trùng GV, lớp, môn, loại kiểm tra)'
            ], 400);
            return;
        }

        $data = [
            'maGV' => intval($_POST['maGV']),
            'maLop' => intval($_POST['maLop']),
            'maMonHoc' => intval($_POST['maMonHoc']),
            'loaiKiemTra' => trim($_POST['loaiKiemTra']),
            'ngayCham' => !empty($_POST['ngayCham']) ? $_POST['ngayCham'] : null,
            'hinhThucCham' => !empty($_POST['hinhThucCham']) ? trim($_POST['hinhThucCham']) : null,
            'trangThai' => !empty($_POST['trangThai']) ? trim($_POST['trangThai']) : 'pending'
        ];

        $maPhanCong = $this->mGrading->createAssignment($data);

        if ($maPhanCong) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Tạo phân công thành công',
                'data' => ['maPhanCong' => $maPhanCong]
            ], 201);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tạo phân công thất bại'
            ], 400);
        }
    }

    /**
     * Cập nhật phân công
     */
    private function updateAssignment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $maPhanCong = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maPhanCong <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Kiểm tra quyền sửa
        $existing = $this->mGrading->getAssignmentById($maPhanCong);
        if (!$existing || $existing['toBoMon'] !== $this->ttbmInfo['toBoMon']) {
            $this->jsonResponse(['success' => false, 'message' => 'Không có quyền sửa phân công này'], 403);
            return;
        }

        $data = [];
        
        if (isset($_POST['maGV'])) {
            // Kiểm tra giáo viên có thuộc tổ không
            $teachers = $this->mGrading->getTeachersByDepartment($this->ttbmInfo['toBoMon']);
            $teacherIds = array_column($teachers, 'maGV');
            
            if (!in_array(intval($_POST['maGV']), $teacherIds)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Giáo viên không thuộc tổ bộ môn của bạn'
                ], 403);
                return;
            }
            
            $data['maGV'] = intval($_POST['maGV']);
        }
        
        if (isset($_POST['maLop'])) $data['maLop'] = intval($_POST['maLop']);
        if (isset($_POST['maMonHoc'])) $data['maMonHoc'] = intval($_POST['maMonHoc']);
        if (isset($_POST['loaiKiemTra'])) $data['loaiKiemTra'] = trim($_POST['loaiKiemTra']);
        if (isset($_POST['ngayCham'])) $data['ngayCham'] = $_POST['ngayCham'];
        if (isset($_POST['hinhThucCham'])) $data['hinhThucCham'] = trim($_POST['hinhThucCham']);
        if (isset($_POST['trangThai'])) $data['trangThai'] = trim($_POST['trangThai']);

        // Kiểm tra trùng lặp nếu thay đổi thông tin quan trọng
        if (isset($data['maGV']) && isset($data['maLop']) && isset($data['maMonHoc']) && isset($data['loaiKiemTra'])) {
            if ($this->mGrading->checkDuplicate(
                $data['maGV'],
                $data['maLop'],
                $data['maMonHoc'],
                $data['loaiKiemTra'],
                $maPhanCong
            )) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Phân công này đã tồn tại (trùng GV, lớp, môn, loại kiểm tra)'
                ], 400);
                return;
            }
        }

        $result = $this->mGrading->updateAssignment($maPhanCong, $data);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cập nhật phân công thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Cập nhật phân công thất bại'
            ], 400);
        }
    }

    /**
     * Xóa phân công
     */
    private function deleteAssignment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $maPhanCong = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maPhanCong <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Kiểm tra quyền xóa
        $existing = $this->mGrading->getAssignmentById($maPhanCong);
        if (!$existing || $existing['toBoMon'] !== $this->ttbmInfo['toBoMon']) {
            $this->jsonResponse(['success' => false, 'message' => 'Không có quyền xóa phân công này'], 403);
            return;
        }

        $result = $this->mGrading->deleteAssignment($maPhanCong);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Xóa phân công thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Xóa phân công thất bại'
            ], 400);
        }
    }

    /**
     * Lấy danh sách giáo viên (chỉ của tổ mình)
     */
    private function getTeachers()
    {
        // Hiển thị tất cả giáo viên (không giới hạn theo tổ bộ môn)
        $teachers = $this->mGrading->getTeachersByDepartment(null);
        $this->jsonResponse(['success' => true, 'data' => $teachers]);
    }

    /**
     * Lấy danh sách lớp học
     */
    private function getClasses()
    {
        $classes = $this->mGrading->getAllClasses();
        $this->jsonResponse(['success' => true, 'data' => $classes]);
    }

    /**
     * Lấy danh sách môn học
     */
    private function getSubjects()
    {
        $subjects = $this->mGrading->getAllSubjects();
        $this->jsonResponse(['success' => true, 'data' => $subjects]);
    }

    /**
     * Lấy thông tin TTBM
     */
    private function getTTBMInfo()
    {
        $this->jsonResponse([
            'success' => true,
            'data' => $this->ttbmInfo
        ]);
    }

    /**
     * JSON Response helper
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Execute if accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $controller = new cGradingAssignment();
    $controller->handleRequest();
}
?>
