<?php
/**
 * Controller: Timetable Management for Admin
 * Purpose: Quản lý thời khóa biểu (CRUD)
 * Permissions: Chỉ dành cho Quản trị viên
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../model/mTimetableAdmin.php');

class cTimetableManagement
{
    private $mTimetable;
    private $currentUser;

    public function __construct()
    {
        $this->mTimetable = new mTimetableAdmin();
        
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['maTaiKhoan'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Chưa đăng nhập'], 401);
            exit;
        }

        // Kiểm tra quyền quản trị viên
        if ($_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
            $this->jsonResponse(['success' => false, 'message' => 'Không có quyền truy cập'], 403);
            exit;
        }

        $this->currentUser = $_SESSION['maTaiKhoan'];
    }

    /**
     * Routing chính
     */
    public function handleRequest()
    {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'list':
                $this->listTimetables();
                break;
            case 'get':
                $this->getTimetable();
                break;
            case 'create':
                $this->createTimetable();
                break;
            case 'update':
                $this->updateTimetable();
                break;
            case 'delete':
                $this->deleteTimetable();
                break;
            case 'classes':
                $this->getClasses();
                break;
            case 'subjects':
                $this->getSubjects();
                break;
            case 'teachers':
                $this->getTeachers();
                break;
            case 'weekly':
                $this->getWeeklyTimetable();
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Action không hợp lệ'], 400);
        }
    }

    /**
     * Danh sách thời khóa biểu
     */
    private function listTimetables()
    {
        $filters = [
            'maLop' => $_GET['maLop'] ?? '',
            'maMonHoc' => $_GET['maMonHoc'] ?? '',
            'maGV' => $_GET['maGV'] ?? '',
            'thuNgay' => $_GET['thuNgay'] ?? '',
            'tuNgay' => $_GET['tuNgay'] ?? '',
            'denNgay' => $_GET['denNgay'] ?? ''
        ];

        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

        $timetables = $this->mTimetable->getAllTimetables($filters, $page, $limit);
        $total = $this->mTimetable->countTimetables($filters);

        $this->jsonResponse([
            'success' => true,
            'data' => $timetables,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'per_page' => $limit
            ]
        ]);
    }

    /**
     * Lấy thông tin 1 thời khóa biểu
     */
    private function getTimetable()
    {
        $maTKB = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maTKB <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $timetable = $this->mTimetable->getTimetableById($maTKB);

        if ($timetable) {
            $this->jsonResponse(['success' => true, 'data' => $timetable]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy thời khóa biểu'], 404);
        }
    }

    /**
     * Tạo thời khóa biểu mới
     */
    private function createTimetable()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        // Validate required fields
        $required = ['maLop', 'maMonHoc', 'maGV', 'thuNgay', 'tietHoc'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => "Thiếu trường bắt buộc: $field"
                ], 400);
                return;
            }
        }

        // Check teacher conflict
        if ($this->mTimetable->checkTeacherConflict(
            intval($_POST['maGV']), 
            $_POST['thuNgay'], 
            $_POST['tietHoc']
        )) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Giáo viên đã có lịch dạy vào thời gian này'
            ], 400);
            return;
        }

        // Check class conflict
        if ($this->mTimetable->checkClassConflict(
            intval($_POST['maLop']), 
            $_POST['thuNgay'], 
            $_POST['tietHoc']
        )) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Lớp đã có lịch học vào thời gian này'
            ], 400);
            return;
        }

        $data = [
            'maLop' => intval($_POST['maLop']),
            'maMonHoc' => intval($_POST['maMonHoc']),
            'maGV' => intval($_POST['maGV']),
            'thuNgay' => $_POST['thuNgay'],
            'tietHoc' => trim($_POST['tietHoc']),
            'thoiGianHoc' => !empty($_POST['thoiGianHoc']) ? $_POST['thoiGianHoc'] : null,
            'phong' => !empty($_POST['phong']) ? trim($_POST['phong']) : null
        ];

        $maTKB = $this->mTimetable->createTimetable($data);

        if ($maTKB) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Tạo thời khóa biểu thành công',
                'data' => ['maTKB' => $maTKB]
            ], 201);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tạo thời khóa biểu thất bại'
            ], 400);
        }
    }

    /**
     * Cập nhật thời khóa biểu
     */
    private function updateTimetable()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $maTKB = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maTKB <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $data = [];
        
        if (isset($_POST['maLop'])) $data['maLop'] = intval($_POST['maLop']);
        if (isset($_POST['maMonHoc'])) $data['maMonHoc'] = intval($_POST['maMonHoc']);
        if (isset($_POST['maGV'])) $data['maGV'] = intval($_POST['maGV']);
        if (isset($_POST['thuNgay'])) $data['thuNgay'] = $_POST['thuNgay'];
        if (isset($_POST['tietHoc'])) $data['tietHoc'] = trim($_POST['tietHoc']);
        if (isset($_POST['thoiGianHoc'])) $data['thoiGianHoc'] = $_POST['thoiGianHoc'];
        if (isset($_POST['phong'])) $data['phong'] = trim($_POST['phong']);

        // Check conflicts if schedule details changed
        if (isset($data['maGV']) && isset($data['thuNgay']) && isset($data['tietHoc'])) {
            if ($this->mTimetable->checkTeacherConflict(
                $data['maGV'], 
                $data['thuNgay'], 
                $data['tietHoc'],
                $maTKB
            )) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Giáo viên đã có lịch dạy vào thời gian này'
                ], 400);
                return;
            }
        }

        if (isset($data['maLop']) && isset($data['thuNgay']) && isset($data['tietHoc'])) {
            if ($this->mTimetable->checkClassConflict(
                $data['maLop'], 
                $data['thuNgay'], 
                $data['tietHoc'],
                $maTKB
            )) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Lớp đã có lịch học vào thời gian này'
                ], 400);
                return;
            }
        }

        $result = $this->mTimetable->updateTimetable($maTKB, $data);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cập nhật thời khóa biểu thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Cập nhật thời khóa biểu thất bại'
            ], 400);
        }
    }

    /**
     * Xóa thời khóa biểu
     */
    private function deleteTimetable()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $maTKB = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maTKB <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $result = $this->mTimetable->deleteTimetable($maTKB);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Xóa thời khóa biểu thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Xóa thời khóa biểu thất bại'
            ], 400);
        }
    }

    /**
     * Lấy danh sách lớp học
     */
    private function getClasses()
    {
        $classes = $this->mTimetable->getAllClasses();
        $this->jsonResponse(['success' => true, 'data' => $classes]);
    }

    /**
     * Lấy danh sách môn học
     */
    private function getSubjects()
    {
        $subjects = $this->mTimetable->getAllSubjects();
        $this->jsonResponse(['success' => true, 'data' => $subjects]);
    }

    /**
     * Lấy danh sách giáo viên
     */
    private function getTeachers()
    {
        $teachers = $this->mTimetable->getAllTeachers();
        $this->jsonResponse(['success' => true, 'data' => $teachers]);
    }

    /**
     * Lấy thời khóa biểu theo tuần
     */
    private function getWeeklyTimetable()
    {
        $maLop = isset($_GET['maLop']) ? intval($_GET['maLop']) : 0;
        $startDate = $_GET['startDate'] ?? date('Y-m-d');

        if ($maLop <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Mã lớp không hợp lệ'], 400);
            return;
        }

        $timetables = $this->mTimetable->getWeeklyTimetable($maLop, $startDate);
        $this->jsonResponse(['success' => true, 'data' => $timetables]);
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
    $controller = new cTimetableManagement();
    $controller->handleRequest();
}
?>
