<?php
/**
 * Controller: Student Management for Admin
 * Purpose: Quản lý học sinh (CRUD)
 * Permissions: Chỉ dành cho Quản trị viên
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../model/mStudent.php');

class cStudentManagement
{
    private $mStudent;
    private $currentUser;

    public function __construct()
    {
        $this->mStudent = new mStudent();
        
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
                $this->listStudents();
                break;
            case 'get':
                $this->getStudent();
                break;
            case 'create':
                $this->createStudent();
                break;
            case 'update':
                $this->updateStudent();
                break;
            case 'delete':
                $this->deleteStudent();
                break;
            case 'classes':
                $this->getClasses();
                break;
            case 'parents':
                $this->getParents();
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Action không hợp lệ'], 400);
        }
    }

    /**
     * Danh sách học sinh
     */
    private function listStudents()
    {
        $filters = [
            'hoTen' => $_GET['hoTen'] ?? '',
            'maLop' => $_GET['maLop'] ?? '',
            'trangThaiHocTap' => $_GET['trangThaiHocTap'] ?? '',
            'gioiTinh' => $_GET['gioiTinh'] ?? ''
        ];

        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

        $students = $this->mStudent->getAllStudents($filters, $page, $limit);
        $total = $this->mStudent->countStudents($filters);

        $this->jsonResponse([
            'success' => true,
            'data' => $students,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'per_page' => $limit
            ]
        ]);
    }

    /**
     * Lấy thông tin 1 học sinh
     */
    private function getStudent()
    {
        $maHS = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maHS <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $student = $this->mStudent->getStudentById($maHS);

        if ($student) {
            $this->jsonResponse(['success' => true, 'data' => $student]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy học sinh'], 404);
        }
    }

    /**
     * Tạo học sinh mới
     */
    private function createStudent()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        // Validate required fields
        $required = ['hoTen', 'ngaySinh', 'gioiTinh'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => "Thiếu trường bắt buộc: $field"
                ], 400);
                return;
            }
        }

        // Xử lý thông tin phụ huynh nếu có
        $maPH = null;
        if (!empty($_POST['tenPhuHuynh']) && !empty($_POST['soDienThoaiPH'])) {
            $maPH = $this->findOrCreateParent(
                trim($_POST['tenPhuHuynh']), 
                trim($_POST['soDienThoaiPH'])
            );
        }

        $data = [
            'hoTen' => trim($_POST['hoTen']),
            'ngaySinh' => $_POST['ngaySinh'],
            'gioiTinh' => $_POST['gioiTinh'],
            'diaChi' => !empty($_POST['diaChi']) ? trim($_POST['diaChi']) : null,
            'trangThaiHocTap' => $_POST['trangThaiHocTap'] ?? 'danghoc',
            'maLop' => !empty($_POST['maLop']) ? intval($_POST['maLop']) : null,
            'maPH' => $maPH,
            'maTaiKhoan' => !empty($_POST['maTaiKhoan']) ? intval($_POST['maTaiKhoan']) : null
        ];

        $maHS = $this->mStudent->createStudentSimple($data);

        if ($maHS) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Tạo học sinh thành công',
                'data' => ['maHS' => $maHS]
            ], 201);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tạo học sinh thất bại'
            ], 400);
        }
    }

    /**
     * Cập nhật học sinh
     */
    private function updateStudent()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $maHS = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maHS <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Xử lý thông tin phụ huynh nếu có
        $maPH = null;
        if (!empty($_POST['tenPhuHuynh']) && !empty($_POST['soDienThoaiPH'])) {
            $maPH = $this->findOrCreateParent(
                trim($_POST['tenPhuHuynh']), 
                trim($_POST['soDienThoaiPH'])
            );
        }

        $data = [];
        
        if (isset($_POST['hoTen'])) $data['hoTen'] = trim($_POST['hoTen']);
        if (isset($_POST['ngaySinh'])) $data['ngaySinh'] = $_POST['ngaySinh'];
        if (isset($_POST['gioiTinh'])) $data['gioiTinh'] = $_POST['gioiTinh'];
        if (isset($_POST['diaChi'])) $data['diaChi'] = trim($_POST['diaChi']);
        if (isset($_POST['trangThaiHocTap'])) $data['trangThaiHocTap'] = $_POST['trangThaiHocTap'];
        if (isset($_POST['maLop'])) $data['maLop'] = $_POST['maLop'];
        if ($maPH) $data['maPH'] = $maPH;

        $result = $this->mStudent->updateStudent($maHS, $data);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cập nhật học sinh thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Cập nhật học sinh thất bại'
            ], 400);
        }
    }

    /**
     * Tìm hoặc tạo mới phụ huynh
     */
    private function findOrCreateParent($hoTen, $soDienThoai)
    {
        // Tìm phụ huynh theo số điện thoại
        $existingParent = $this->mStudent->findParentByPhone($soDienThoai);
        
        if ($existingParent) {
            // Nếu đã tồn tại, cập nhật tên nếu khác
            if ($existingParent['hoTen'] !== $hoTen) {
                $this->mStudent->updateParent($existingParent['maPH'], ['hoTen' => $hoTen]);
            }
            return $existingParent['maPH'];
        }
        
        // Nếu chưa tồn tại, tạo mới
        return $this->mStudent->createParent([
            'hoTen' => $hoTen,
            'soDienThoai' => $soDienThoai
        ]);
    }

    /**
     * Xóa học sinh
     */
    private function deleteStudent()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $maHS = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maHS <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $result = $this->mStudent->deleteStudent($maHS);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Xóa học sinh thành công (chuyển sang trạng thái đã thôi học)'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Xóa học sinh thất bại'
            ], 400);
        }
    }

    /**
     * Lấy danh sách lớp học
     */
    private function getClasses()
    {
        $classes = $this->mStudent->getAllClasses();
        $this->jsonResponse(['success' => true, 'data' => $classes]);
    }

    /**
     * Lấy danh sách phụ huynh
     */
    private function getParents()
    {
        $parents = $this->mStudent->getAllParents();
        $this->jsonResponse(['success' => true, 'data' => $parents]);
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
    $controller = new cStudentManagement();
    $controller->handleRequest();
}
?>
