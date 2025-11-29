<?php
/**
 * Controller: Teacher Management for Admin
 * Purpose: Quản lý giáo viên (CRUD)
 * Permissions: Chỉ dành cho Quản trị viên
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../model/mTeacherAdmin.php');

class cTeacherManagement
{
    private $mTeacher;
    private $currentUser;

    public function __construct()
    {
        $this->mTeacher = new mTeacherAdmin();
        
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
                $this->listTeachers();
                break;
            case 'get':
                $this->getTeacher();
                break;
            case 'create':
                $this->createTeacher();
                break;
            case 'update':
                $this->updateTeacher();
                break;
            case 'delete':
                $this->deleteTeacher();
                break;
            case 'departments':
                $this->getDepartments();
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Action không hợp lệ'], 400);
        }
    }

    /**
     * Danh sách giáo viên
     */
    private function listTeachers()
    {
        $filters = [
            'hoTen' => $_GET['hoTen'] ?? '',
            'toBoMon' => $_GET['toBoMon'] ?? '',
            'gioiTinh' => $_GET['gioiTinh'] ?? ''
        ];

        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

        $teachers = $this->mTeacher->getAllTeachers($filters, $page, $limit);
        $total = $this->mTeacher->countTeachers($filters);

        $this->jsonResponse([
            'success' => true,
            'data' => $teachers,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'per_page' => $limit
            ]
        ]);
    }

    /**
     * Lấy thông tin 1 giáo viên
     */
    private function getTeacher()
    {
        $maGV = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maGV <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $teacher = $this->mTeacher->getTeacherById($maGV);

        if ($teacher) {
            $this->jsonResponse(['success' => true, 'data' => $teacher]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy giáo viên'], 404);
        }
    }

    /**
     * Tạo giáo viên mới
     */
    private function createTeacher()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        // Validate required fields
        if (empty($_POST['hoTen'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => "Thiếu trường bắt buộc: Họ tên"
            ], 400);
            return;
        }

        $data = [
            'hoTen' => trim($_POST['hoTen']),
            'ngaySinh' => !empty($_POST['ngaySinh']) ? $_POST['ngaySinh'] : null,
            'gioiTinh' => !empty($_POST['gioiTinh']) ? $_POST['gioiTinh'] : null,
            'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
            'soDienThoai' => !empty($_POST['soDienThoai']) ? trim($_POST['soDienThoai']) : null,
            'toBoMon' => !empty($_POST['toBoMon']) ? trim($_POST['toBoMon']) : null,
            'maTaiKhoan' => !empty($_POST['maTaiKhoan']) ? intval($_POST['maTaiKhoan']) : null
        ];

        $maGV = $this->mTeacher->createTeacher($data);

        if ($maGV) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Tạo giáo viên thành công',
                'data' => ['maGV' => $maGV]
            ], 201);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tạo giáo viên thất bại'
            ], 400);
        }
    }

    /**
     * Cập nhật giáo viên
     */
    private function updateTeacher()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $maGV = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maGV <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $data = [];
        
        if (isset($_POST['hoTen'])) $data['hoTen'] = trim($_POST['hoTen']);
        if (isset($_POST['ngaySinh'])) $data['ngaySinh'] = $_POST['ngaySinh'];
        if (isset($_POST['gioiTinh'])) $data['gioiTinh'] = $_POST['gioiTinh'];
        if (isset($_POST['email'])) $data['email'] = trim($_POST['email']);
        if (isset($_POST['soDienThoai'])) $data['soDienThoai'] = trim($_POST['soDienThoai']);
        if (isset($_POST['toBoMon'])) $data['toBoMon'] = trim($_POST['toBoMon']);

        $result = $this->mTeacher->updateTeacher($maGV, $data);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cập nhật giáo viên thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Cập nhật giáo viên thất bại'
            ], 400);
        }
    }

    /**
     * Xóa giáo viên
     */
    private function deleteTeacher()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $maGV = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maGV <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $result = $this->mTeacher->deleteTeacher($maGV);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Xóa giáo viên thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Xóa giáo viên thất bại'
            ], 400);
        }
    }

    /**
     * Lấy danh sách tổ bộ môn
     */
    private function getDepartments()
    {
        $departments = $this->mTeacher->getAllDepartments();
        $this->jsonResponse(['success' => true, 'data' => $departments]);
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
    $controller = new cTeacherManagement();
    $controller->handleRequest();
}
?>
