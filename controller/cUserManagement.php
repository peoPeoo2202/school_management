<?php
/**
 * Controller quản lý người dùng (User Management)
 * Xử lý tạo, cập nhật, xóa tài khoản và người dùng
 */

// Include các model cần thiết
require_once(__DIR__ . '/../model/mAccount.php');
require_once(__DIR__ . '/../model/mTeacher.php');
require_once(__DIR__ . '/../model/mStudent.php');
require_once(__DIR__ . '/../model/mParent.php');
require_once(__DIR__ . '/../model/mBGH.php');
require_once(__DIR__ . '/../model/mTTBM.php');

class cUserManagement
{
    private $mAccount;
    private $mTeacher;
    private $mStudent;
    private $mParent;
    private $mBGH;
    private $mTTBM;

    public function __construct()
    {
        $this->mAccount = new mAccount();
        $this->mTeacher = new mTeacher();
        $this->mStudent = new mStudent();
        $this->mParent = new mParent();
        $this->mBGH = new mBGH();
        $this->mTTBM = new mTTBM();
    }

    /**
     * Xử lý request tạo giáo viên
     * POST data: hoTen, ngaySinh, gioiTinh, email, soDienThoai, toBoMon, tenDangNhap, matKhau
     */
    public function handleCreateTeacher()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        // Validate dữ liệu đầu vào
        $required = ['hoTen', 'tenDangNhap', 'matKhau'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                return $this->jsonResponse(['success' => false, 'message' => "Thiếu trường: $field"], 400);
            }
        }

        $teacherData = [
            'hoTen' => $this->sanitize($_POST['hoTen']),
            'ngaySinh' => $_POST['ngaySinh'] ?? null,
            'gioiTinh' => $_POST['gioiTinh'] ?? 'Nam',
            'email' => $this->sanitize($_POST['email'] ?? ''),
            'soDienThoai' => $this->sanitize($_POST['soDienThoai'] ?? ''),
            'toBoMon' => $this->sanitize($_POST['toBoMon'] ?? '')
        ];

        $accountData = [
            'tenDangNhap' => $this->sanitize($_POST['tenDangNhap']),
            'matKhau' => $_POST['matKhau'] // Không sanitize password
        ];

        $result = $this->mTeacher->createTeacher($teacherData, $accountData);
        return $this->jsonResponse($result);
    }

    /**
     * Xử lý request tạo học sinh
     * POST data: hoTen, ngaySinh, gioiTinh, diaChi, maHocLuc, maHanhKiem, maPH, maLop, tenDangNhap, matKhau
     */
    public function handleCreateStudent()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $required = ['hoTen', 'tenDangNhap', 'matKhau', 'maLop'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                return $this->jsonResponse(['success' => false, 'message' => "Thiếu trường: $field"], 400);
            }
        }

        $studentData = [
            'hoTen' => $this->sanitize($_POST['hoTen']),
            'ngaySinh' => $_POST['ngaySinh'] ?? null,
            'gioiTinh' => $_POST['gioiTinh'] ?? 'Nam',
            'diaChi' => $this->sanitize($_POST['diaChi'] ?? ''),
            'maHocLuc' => (int)($_POST['maHocLuc'] ?? 9003),
            'maHanhKiem' => (int)($_POST['maHanhKiem'] ?? 9103),
            'maPH' => !empty($_POST['maPH']) ? (int)$_POST['maPH'] : null,
            'maLop' => (int)$_POST['maLop']
        ];

        $accountData = [
            'tenDangNhap' => $this->sanitize($_POST['tenDangNhap']),
            'matKhau' => $_POST['matKhau']
        ];

        $result = $this->mStudent->createStudent($studentData, $accountData);
        return $this->jsonResponse($result);
    }

    /**
     * Xử lý request tạo phụ huynh
     * POST data: hoTen, soDienThoai, email, diaChi, tenDangNhap, matKhau
     */
    public function handleCreateParent()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $required = ['hoTen', 'tenDangNhap', 'matKhau'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                return $this->jsonResponse(['success' => false, 'message' => "Thiếu trường: $field"], 400);
            }
        }

        $parentData = [
            'hoTen' => $this->sanitize($_POST['hoTen']),
            'soDienThoai' => $this->sanitize($_POST['soDienThoai'] ?? ''),
            'email' => $this->sanitize($_POST['email'] ?? ''),
            'diaChi' => $this->sanitize($_POST['diaChi'] ?? '')
        ];

        $accountData = [
            'tenDangNhap' => $this->sanitize($_POST['tenDangNhap']),
            'matKhau' => $_POST['matKhau']
        ];

        $result = $this->mParent->createParent($parentData, $accountData);
        return $this->jsonResponse($result);
    }

    /**
     * Xử lý request tạo BGH
     * POST data: hoTen, ngaySinh, gioiTinh, email, soDienThoai, tenDangNhap, matKhau
     */
    public function handleCreateBGH()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $required = ['hoTen', 'tenDangNhap', 'matKhau'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                return $this->jsonResponse(['success' => false, 'message' => "Thiếu trường: $field"], 400);
            }
        }

        $bghData = [
            'hoTen' => $this->sanitize($_POST['hoTen']),
            'ngaySinh' => $_POST['ngaySinh'] ?? null,
            'gioiTinh' => $_POST['gioiTinh'] ?? 'Nam',
            'email' => $this->sanitize($_POST['email'] ?? ''),
            'soDienThoai' => $this->sanitize($_POST['soDienThoai'] ?? '')
        ];

        $accountData = [
            'tenDangNhap' => $this->sanitize($_POST['tenDangNhap']),
            'matKhau' => $_POST['matKhau']
        ];

        $result = $this->mBGH->createBGH($bghData, $accountData);
        return $this->jsonResponse($result);
    }

    /**
     * Xử lý request tạo TTBM
     * POST data: maGV, tenDangNhap, matKhau
     */
    public function handleCreateTTBM()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $required = ['maGV', 'tenDangNhap', 'matKhau'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                return $this->jsonResponse(['success' => false, 'message' => "Thiếu trường: $field"], 400);
            }
        }

        $accountData = [
            'tenDangNhap' => $this->sanitize($_POST['tenDangNhap']),
            'matKhau' => $_POST['matKhau']
        ];

        $result = $this->mTTBM->createTTBM((int)$_POST['maGV'], $accountData);
        return $this->jsonResponse($result);
    }

    /**
     * Sanitize input
     */
    private function sanitize($data)
    {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    /**
     * Trả về JSON response
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Route handler chính
     */
    public function handleRequest()
    {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'create_teacher':
                return $this->handleCreateTeacher();
            case 'create_student':
                return $this->handleCreateStudent();
            case 'create_parent':
                return $this->handleCreateParent();
            case 'create_bgh':
                return $this->handleCreateBGH();
            case 'create_ttbm':
                return $this->handleCreateTTBM();
            default:
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Action không hợp lệ',
                    'available_actions' => [
                        'create_teacher',
                        'create_student',
                        'create_parent',
                        'create_bgh',
                        'create_ttbm'
                    ]
                ], 400);
        }
    }
}

// Chạy controller nếu được gọi trực tiếp
if (basename($_SERVER['PHP_SELF']) === 'cUserManagement.php') {
    $controller = new cUserManagement();
    $controller->handleRequest();
}
