<?php
/**
 * Controller: Account Management
 * Purpose: Quản lý tài khoản người dùng (CRUD, khóa/mở khóa, reset password)
 * Permissions: Chỉ dành cho Quản trị viên
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../model/mUser.php');

class cAccountManagement
{
    private $mUser;
    private $currentUser;

    public function __construct()
    {
        $this->mUser = new mUser();
        
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
                $this->listAccounts();
                break;
            case 'get':
                $this->getAccount();
                break;
            case 'create':
                $this->createAccount();
                break;
            case 'update':
                $this->updateAccount();
                break;
            case 'delete':
                $this->deleteAccount();
                break;
            case 'lock':
                $this->lockAccount();
                break;
            case 'unlock':
                $this->unlockAccount();
                break;
            case 'reset-password':
                $this->resetPassword();
                break;
            case 'check-username':
                $this->checkUsername();
                break;
            case 'check-email':
                $this->checkEmail();
                break;
            case 'groups':
                $this->getGroups();
                break;
            case 'students':
                $this->listStudents();
                break;
            case 'assign-student':
                $this->assignStudentAccount();
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Action không hợp lệ'], 400);
        }
    }

    /**
     * Lấy danh sách tài khoản với filter và phân trang
     * GET /controller/cAccountManagement.php?action=list
     */
    private function listAccounts()
    {
        $filters = [
            'tenDangNhap' => $_GET['tenDangNhap'] ?? '',
            'hoTen' => $_GET['hoTen'] ?? '',
            'loaiTaiKhoan' => $_GET['loaiTaiKhoan'] ?? '',
            'trangThaiTaiKhoan' => $_GET['trangThaiTaiKhoan'] ?? '',
            'maNhom' => $_GET['maNhom'] ?? ''
        ];

        // Loại bỏ filter rỗng
        $filters = array_filter($filters, function($value) {
            return $value !== '';
        });

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;

        $result = $this->mUser->getAllAccounts($filters, $page, $limit);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $result['data'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'totalPages' => $result['totalPages']
            ]
        ]);
    }

    /**
     * Lấy thông tin chi tiết 1 tài khoản
     * GET /controller/cAccountManagement.php?action=get&id=123
     */
    private function getAccount()
    {
        $id = $_GET['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $account = $this->mUser->getAccountById($id);
        
        if (!$account) {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy tài khoản'], 404);
            return;
        }

        $this->jsonResponse(['success' => true, 'data' => $account]);
    }

    /**
     * Tạo tài khoản mới
     * POST /controller/cAccountManagement.php?action=create
     */
    private function createAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        // Validate CSRF token
        if (!$this->validateCSRFToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'CSRF token không hợp lệ'], 403);
            return;
        }

        // Validate required fields
        $required = ['tenDangNhap', 'hoTen', 'loaiTaiKhoan'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->jsonResponse([
                    'success' => false, 
                    'message' => "Thiếu trường bắt buộc: $field"
                ], 400);
                return;
            }
        }

        // Validate username format
        if (!$this->validateUsername($_POST['tenDangNhap'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tên đăng nhập không hợp lệ (4-32 ký tự, chỉ chữ, số, dấu chấm, gạch dưới)'
            ], 400);
            return;
        }

        // Validate email nếu có
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Email không hợp lệ'
            ], 400);
            return;
        }

        // Validate phone nếu có
        if (!empty($_POST['soDienThoai']) && !$this->validatePhone($_POST['soDienThoai'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Số điện thoại không hợp lệ (9-11 chữ số)'
            ], 400);
            return;
        }

        // Generate password nếu không có
        $password = !empty($_POST['matKhau']) ? $_POST['matKhau'] : $this->mUser->generatePassword(12);

        // Validate password strength
        if (!$this->validatePassword($password)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số'
            ], 400);
            return;
        }

        $data = [
            'tenDangNhap' => trim($_POST['tenDangNhap']),
            'matKhau' => $password,
            'hoTen' => trim($_POST['hoTen']),
            'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
            'soDienThoai' => !empty($_POST['soDienThoai']) ? trim($_POST['soDienThoai']) : null,
            'loaiTaiKhoan' => $_POST['loaiTaiKhoan'],
            'trangThaiTaiKhoan' => $_POST['trangThaiTaiKhoan'] ?? 'active',
            'maNhom' => !empty($_POST['maNhom']) ? intval($_POST['maNhom']) : null,
            'nguoiTao' => $this->currentUser,
            'batBuocDoiMatKhau' => isset($_POST['batBuocDoiMatKhau']) ? intval($_POST['batBuocDoiMatKhau']) : 1
        ];

        $maTaiKhoan = $this->mUser->createAccount($data);

        if ($maTaiKhoan) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Tạo tài khoản thành công',
                'data' => [
                    'maTaiKhoan' => $maTaiKhoan,
                    'generatedPassword' => $password  // Trả về để hiển thị 1 lần
                ]
            ], 201);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tạo tài khoản thất bại (có thể username hoặc email đã tồn tại)'
            ], 400);
        }
    }

    /**
     * Cập nhật tài khoản
     * PUT/POST /controller/cAccountManagement.php?action=update&id=123
     */
    private function updateAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $id = $_GET['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Validate CSRF token
        if (!$this->validateCSRFToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'CSRF token không hợp lệ'], 403);
            return;
        }

        // Kiểm tra tài khoản tồn tại
        $existing = $this->mUser->getAccountById($id);
        if (!$existing) {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy tài khoản'], 404);
            return;
        }

        // Không cho phép admin tự xóa quyền admin của mình
        if ($id == $this->currentUser) {
            if (isset($_POST['loaiTaiKhoan']) && $_POST['loaiTaiKhoan'] !== 'quantrivien') {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Không thể thay đổi loại tài khoản của chính mình'
                ], 400);
                return;
            }
        }

        $data = [];

        // Chỉ cập nhật các trường được gửi lên
        if (isset($_POST['hoTen'])) {
            $data['hoTen'] = trim($_POST['hoTen']);
        }

        if (isset($_POST['email'])) {
            $email = trim($_POST['email']);
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(['success' => false, 'message' => 'Email không hợp lệ'], 400);
                return;
            }
            $data['email'] = $email;
        }

        if (isset($_POST['soDienThoai'])) {
            $phone = trim($_POST['soDienThoai']);
            if (!empty($phone) && !$this->validatePhone($phone)) {
                $this->jsonResponse(['success' => false, 'message' => 'Số điện thoại không hợp lệ'], 400);
                return;
            }
            $data['soDienThoai'] = $phone;
        }

        if (isset($_POST['loaiTaiKhoan'])) {
            $data['loaiTaiKhoan'] = $_POST['loaiTaiKhoan'];
        }

        if (isset($_POST['trangThaiTaiKhoan'])) {
            $data['trangThaiTaiKhoan'] = $_POST['trangThaiTaiKhoan'];
        }

        if (isset($_POST['maNhom'])) {
            $maNhom = $_POST['maNhom'];
            // Chỉ set maNhom nếu có giá trị hợp lệ
            if (!empty($maNhom) && is_numeric($maNhom)) {
                $data['maNhom'] = intval($maNhom);
            } else {
                // Nếu empty thì set NULL
                $data['maNhom'] = null;
            }
        }

        if (isset($_POST['batBuocDoiMatKhau'])) {
            $data['batBuocDoiMatKhau'] = intval($_POST['batBuocDoiMatKhau']);
        }

        $result = $this->mUser->updateAccount($id, $data, $this->currentUser);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cập nhật tài khoản thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Cập nhật tài khoản thất bại'
            ], 400);
        }
    }

    /**
     * Xóa tài khoản (soft delete)
     * DELETE/POST /controller/cAccountManagement.php?action=delete&id=123
     */
    private function deleteAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $id = $_GET['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Validate CSRF token
        if (!$this->validateCSRFToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'CSRF token không hợp lệ'], 403);
            return;
        }

        // Không cho phép xóa chính mình
        if ($id == $this->currentUser) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Không thể xóa tài khoản của chính mình'
            ], 400);
            return;
        }

        $result = $this->mUser->deleteAccount($id, $this->currentUser);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Xóa tài khoản thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Xóa tài khoản thất bại'
            ], 400);
        }
    }

    /**
     * Khóa tài khoản
     * POST /controller/cAccountManagement.php?action=lock&id=123
     */
    private function lockAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $id = $_GET['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Validate CSRF token
        if (!$this->validateCSRFToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'CSRF token không hợp lệ'], 403);
            return;
        }

        // Không cho phép khóa chính mình
        if ($id == $this->currentUser) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Không thể khóa tài khoản của chính mình'
            ], 400);
            return;
        }

        $lyDo = $_POST['lyDo'] ?? '';
        $result = $this->mUser->lockAccount($id, $this->currentUser, $lyDo);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Khóa tài khoản thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Khóa tài khoản thất bại'
            ], 400);
        }
    }

    /**
     * Mở khóa tài khoản
     * POST /controller/cAccountManagement.php?action=unlock&id=123
     */
    private function unlockAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $id = $_GET['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Validate CSRF token
        if (!$this->validateCSRFToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'CSRF token không hợp lệ'], 403);
            return;
        }

        $result = $this->mUser->unlockAccount($id, $this->currentUser);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Mở khóa tài khoản thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Mở khóa tài khoản thất bại'
            ], 400);
        }
    }

    /**
     * Reset mật khẩu
     * POST /controller/cAccountManagement.php?action=reset-password&id=123
     */
    private function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $id = $_GET['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Validate CSRF token
        if (!$this->validateCSRFToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'CSRF token không hợp lệ'], 403);
            return;
        }

        // Generate new password
        $newPassword = $this->mUser->generatePassword(12);

        $result = $this->mUser->resetPassword($id, $newPassword, $this->currentUser);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Reset mật khẩu thành công',
                'data' => [
                    'newPassword' => $newPassword  // Trả về để hiển thị 1 lần
                ]
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Reset mật khẩu thất bại'
            ], 400);
        }
    }

    /**
     * Kiểm tra username có tồn tại không
     * GET /controller/cAccountManagement.php?action=check-username&username=test&exclude=123
     */
    private function checkUsername()
    {
        $username = $_GET['username'] ?? '';
        $exclude = $_GET['exclude'] ?? null;

        if (empty($username)) {
            $this->jsonResponse(['success' => false, 'available' => false]);
            return;
        }

        $available = $this->mUser->checkUniqueUsername($username, $exclude);
        
        $this->jsonResponse([
            'success' => true,
            'available' => $available
        ]);
    }

    /**
     * Kiểm tra email có tồn tại không
     * GET /controller/cAccountManagement.php?action=check-email&email=test@example.com&exclude=123
     */
    private function checkEmail()
    {
        $email = $_GET['email'] ?? '';
        $exclude = $_GET['exclude'] ?? null;

        if (empty($email)) {
            $this->jsonResponse(['success' => true, 'available' => true]);
            return;
        }

        $available = $this->mUser->checkUniqueEmail($email, $exclude);
        
        $this->jsonResponse([
            'success' => true,
            'available' => $available
        ]);
    }

    /**
     * Lấy danh sách nhóm người dùng
     * GET /controller/cAccountManagement.php?action=groups
     */
    private function getGroups()
    {
        $groups = $this->mUser->getAllGroups();
        
        $this->jsonResponse([
            'success' => true,
            'data' => $groups
        ]);
    }

    /**
     * Lấy danh sách học sinh để cấp tài khoản
     * GET /controller/cAccountManagement.php?action=students
     */
    private function listStudents()
    {
        require_once(__DIR__ . '/../model/mStudent.php');
        $mStudent = new mStudent();

        $filters = [
            'maHocSinh' => $_GET['maHocSinh'] ?? '',
            'tenHocSinh' => $_GET['tenHocSinh'] ?? '',
            'maLop' => $_GET['maLop'] ?? '',
            'hasAccount' => $_GET['hasAccount'] ?? ''
        ];

        // Loại bỏ filter rỗng
        $filters = array_filter($filters, function($value) {
            return $value !== '';
        });

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;

        $result = $mStudent->getStudentsForAccountAssignment($filters, $page, $limit);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $result['data'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'totalPages' => $result['totalPages']
            ]
        ]);
    }

    /**
     * Cấp tài khoản cho học sinh
     * POST /controller/cAccountManagement.php?action=assign-student&maHocSinh=123
     */
    private function assignStudentAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        // Validate CSRF token
        if (!$this->validateCSRFToken()) {
            $this->jsonResponse(['success' => false, 'message' => 'CSRF token không hợp lệ'], 403);
            return;
        }

        $maHocSinh = $_GET['maHocSinh'] ?? null;
        
        if (!$maHocSinh) {
            $this->jsonResponse(['success' => false, 'message' => 'Mã học sinh không hợp lệ'], 400);
            return;
        }

        // Validate required fields
        $required = ['tenDangNhap', 'hoTen', 'matKhau'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->jsonResponse([
                    'success' => false, 
                    'message' => "Thiếu trường bắt buộc: $field"
                ], 400);
                return;
            }
        }

        // Validate username format
        if (!$this->validateUsername($_POST['tenDangNhap'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tên đăng nhập không hợp lệ (4-32 ký tự, chỉ chữ, số, dấu chấm, gạch dưới)'
            ], 400);
            return;
        }

        // Validate email nếu có
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Email không hợp lệ'
            ], 400);
            return;
        }

        // Validate phone nếu có
        if (!empty($_POST['soDienThoai']) && !$this->validatePhone($_POST['soDienThoai'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Số điện thoại không hợp lệ (9-11 chữ số)'
            ], 400);
            return;
        }

        // Validate password strength
        if (!$this->validatePassword($_POST['matKhau'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số'
            ], 400);
            return;
        }

        // Kiểm tra học sinh có tồn tại và chưa có tài khoản
        require_once(__DIR__ . '/../model/mStudent.php');
        $mStudent = new mStudent();
        $student = $mStudent->getStudentById($maHocSinh);

        if (!$student) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Không tìm thấy học sinh'
            ], 404);
            return;
        }

        if (!empty($student['maTaiKhoan'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Học sinh đã có tài khoản'
            ], 400);
            return;
        }

        // Tạo tài khoản
        $accountData = [
            'tenDangNhap' => trim($_POST['tenDangNhap']),
            'matKhau' => $_POST['matKhau'],
            'hoTen' => trim($_POST['hoTen']),
            'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
            'soDienThoai' => !empty($_POST['soDienThoai']) ? trim($_POST['soDienThoai']) : null,
            'loaiTaiKhoan' => 'hocsinh',
            'trangThaiTaiKhoan' => $_POST['trangThaiTaiKhoan'] ?? 'active',
            'maNhom' => !empty($_POST['maNhom']) ? intval($_POST['maNhom']) : null,
            'nguoiTao' => $this->currentUser,
            'batBuocDoiMatKhau' => isset($_POST['batBuocDoiMatKhau']) ? 1 : 0
        ];

        $maTaiKhoan = $this->mUser->createAccount($accountData);

        if (!$maTaiKhoan) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tạo tài khoản thất bại (có thể username hoặc email đã tồn tại)'
            ], 400);
            return;
        }

        // Cập nhật maTaiKhoan cho học sinh
        $updateResult = $mStudent->updateStudent($maHocSinh, ['maTaiKhoan' => $maTaiKhoan]);

        if (!$updateResult) {
            // Rollback: Xóa tài khoản vừa tạo
            $this->mUser->deleteAccount($maTaiKhoan, $this->currentUser);
            $this->jsonResponse([
                'success' => false,
                'message' => 'Cập nhật thông tin học sinh thất bại'
            ], 400);
            return;
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Cấp tài khoản cho học sinh thành công!',
            'data' => [
                'maTaiKhoan' => $maTaiKhoan,
                'tenDangNhap' => $_POST['tenDangNhap']
            ]
        ], 201);
    }

    /**
     * Validate username format
     */
    private function validateUsername($username)
    {
        // 4-32 characters, alphanumeric, dot, underscore
        return preg_match('/^[a-zA-Z0-9._]{4,32}$/', $username);
    }

    /**
     * Validate password strength
     */
    private function validatePassword($password)
    {
        // Min 8 characters, at least 1 uppercase, 1 lowercase, 1 number
        return strlen($password) >= 8 
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password);
    }

    /**
     * Validate phone number
     */
    private function validatePhone($phone)
    {
        // 9-11 digits
        return preg_match('/^[0-9]{9,11}$/', $phone);
    }

    /**
     * Validate CSRF Token
     */
    private function validateCSRFToken()
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($token) || !isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Generate CSRF Token
     */
    public static function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
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
    $controller = new cAccountManagement();
    $controller->handleRequest();
}
?>
