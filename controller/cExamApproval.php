<?php
/**
 * Controller: Duyệt đề thi (Exam Approval)
 * Chức năng: Xử lý các yêu cầu liên quan đến duyệt đề thi của TTBM
 * 
 * @author IMAX Team
 * @version 1.0
 * @date 2025-11-18
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug logging
error_log("=== cExamApproval Debug ===");
error_log("Session ID: " . session_id());
error_log("maTaiKhoan: " . ($_SESSION['maTaiKhoan'] ?? 'NOT SET'));
error_log("loaiTaiKhoan: " . ($_SESSION['loaiTaiKhoan'] ?? 'NOT SET'));
error_log("GET params: " . print_r($_GET, true));

// Kiểm tra đăng nhập
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'ttbm') {
    error_log("Session check FAILED!");
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập'], JSON_UNESCAPED_UNICODE);
    exit();
}

error_log("Session check PASSED!");

// Include model
require_once(__DIR__ . '/../model/mConnect.php');
require_once(__DIR__ . '/../model/mExamApproval.php');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Không hiển thị lỗi trực tiếp
ini_set('log_errors', 1);

class cExamApproval
{
    private $mExam;
    private $ttbmInfo;

    public function __construct()
    {
        try {
            error_log("Creating mExamApproval...");
            $this->mExam = new mExamApproval();
            error_log("mExamApproval created successfully");
            
            // Lấy thông tin TTBM
            error_log("Getting TTBM info for maTaiKhoan: " . $_SESSION['maTaiKhoan']);
            $this->ttbmInfo = $this->mExam->getTTBMInfo($_SESSION['maTaiKhoan']);
            error_log("TTBM info: " . print_r($this->ttbmInfo, true));
            
            if (!$this->ttbmInfo) {
                error_log("ERROR: TTBM info not found!");
                $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy thông tin TTBM'], 404);
                exit();
            }
        } catch (Exception $e) {
            error_log("EXCEPTION in __construct: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Lỗi khởi tạo: ' . $e->getMessage()], 500);
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
            case 'list':
                $this->getExams();
                break;
            case 'detail':
                $this->getExam();
                break;
            case 'create':
                $this->createExam();
                break;
            case 'update':
                $this->updateExam();
                break;
            case 'approve':
                $this->approveExam();
                break;
            case 'reject':
                $this->rejectExam();
                break;
            case 'delete':
                $this->deleteExam();
                break;
            case 'subjects':
                $this->getSubjects();
                break;
            case 'statistics':
                $this->getStatistics();
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Action không hợp lệ'], 400);
        }
    }

    /**
     * Lấy danh sách đề thi
     */
    private function getExams()
    {
        $trangThai = isset($_GET['trangThai']) ? $_GET['trangThai'] : null;
        
        // TTBM chỉ xem đề thi của tổ bộ môn mình
        $exams = $this->mExam->getExamsByDepartment($this->ttbmInfo['maTTBM'], $trangThai);
        
        $this->jsonResponse(['success' => true, 'data' => $exams]);
    }

    /**
     * Lấy chi tiết đề thi
     */
    private function getExam()
    {
        $maDeThi = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maDeThi <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $exam = $this->mExam->getExamById($maDeThi);

        if ($exam) {
            // Kiểm tra quyền truy cập
            if ($exam['maTTBM'] != $this->ttbmInfo['maTTBM']) {
                $this->jsonResponse(['success' => false, 'message' => 'Không có quyền xem đề thi này'], 403);
                return;
            }
            
            $this->jsonResponse(['success' => true, 'data' => $exam]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy đề thi'], 404);
        }
    }

    /**
     * Tạo đề thi mới
     */
    private function createExam()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Phương thức không hợp lệ'], 405);
            return;
        }

        // Lấy dữ liệu từ request
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate dữ liệu
        $required = ['tenDeThi', 'maMonHoc', 'hocKy', 'namHoc', 'loaiDeThi'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->jsonResponse(['success' => false, 'message' => "Thiếu trường: $field"], 400);
                return;
            }
        }

        // Thêm maTTBM
        $data['maTTBM'] = $this->ttbmInfo['maTTBM'];
        $data['noiDungDeThi'] = isset($data['noiDungDeThi']) ? $data['noiDungDeThi'] : '';
        $data['thoiGianLamBai'] = isset($data['thoiGianLamBai']) ? $data['thoiGianLamBai'] : '';

        // Tạo đề thi
        $result = $this->mExam->createExam($data);

        if ($result) {
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Tạo đề thi thành công',
                'data' => ['maDeThi' => $result]
            ]);
        } else {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Đề thi đã tồn tại hoặc không thể tạo'
            ], 400);
        }
    }

    /**
     * Cập nhật đề thi
     */
    private function updateExam()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Phương thức không hợp lệ'], 405);
            return;
        }

        $maDeThi = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maDeThi <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Kiểm tra quyền
        $exam = $this->mExam->getExamById($maDeThi);
        if (!$exam || $exam['maTTBM'] != $this->ttbmInfo['maTTBM']) {
            $this->jsonResponse(['success' => false, 'message' => 'Không có quyền cập nhật đề thi này'], 403);
            return;
        }

        if ($exam['trangThai'] !== 'Choduyet') {
            $this->jsonResponse(['success' => false, 'message' => 'Chỉ có thể cập nhật đề thi đang chờ duyệt'], 400);
            return;
        }

        // Lấy dữ liệu
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate
        $required = ['tenDeThi', 'maMonHoc', 'hocKy', 'namHoc', 'loaiDeThi'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->jsonResponse(['success' => false, 'message' => "Thiếu trường: $field"], 400);
                return;
            }
        }

        $data['noiDungDeThi'] = isset($data['noiDungDeThi']) ? $data['noiDungDeThi'] : '';
        $data['thoiGianLamBai'] = isset($data['thoiGianLamBai']) ? $data['thoiGianLamBai'] : '';

        // Cập nhật
        $result = $this->mExam->updateExam($maDeThi, $data);

        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Cập nhật đề thi thành công']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Không thể cập nhật đề thi'], 500);
        }
    }

    /**
     * Duyệt đề thi
     */
    private function approveExam()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Phương thức không hợp lệ'], 405);
            return;
        }

        $maDeThi = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maDeThi <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Kiểm tra quyền
        $exam = $this->mExam->getExamById($maDeThi);
        if (!$exam || $exam['maTTBM'] != $this->ttbmInfo['maTTBM']) {
            $this->jsonResponse(['success' => false, 'message' => 'Không có quyền duyệt đề thi này'], 403);
            return;
        }

        if ($exam['trangThai'] !== 'Choduyet') {
            $this->jsonResponse(['success' => false, 'message' => 'Đề thi không ở trạng thái chờ duyệt'], 400);
            return;
        }

        // Duyệt đề thi
        $result = $this->mExam->approveExam($maDeThi);

        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Duyệt đề thi thành công']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Không thể duyệt đề thi'], 500);
        }
    }

    /**
     * Từ chối đề thi
     */
    private function rejectExam()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Phương thức không hợp lệ'], 405);
            return;
        }

        $maDeThi = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maDeThi <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Lấy lý do từ chối
        $data = json_decode(file_get_contents('php://input'), true);
        $lyDo = isset($data['lyDo']) ? trim($data['lyDo']) : '';

        if (empty($lyDo)) {
            $this->jsonResponse(['success' => false, 'message' => 'Vui lòng nhập lý do từ chối'], 400);
            return;
        }

        // Kiểm tra quyền
        $exam = $this->mExam->getExamById($maDeThi);
        if (!$exam || $exam['maTTBM'] != $this->ttbmInfo['maTTBM']) {
            $this->jsonResponse(['success' => false, 'message' => 'Không có quyền từ chối đề thi này'], 403);
            return;
        }

        if ($exam['trangThai'] !== 'Choduyet') {
            $this->jsonResponse(['success' => false, 'message' => 'Đề thi không ở trạng thái chờ duyệt'], 400);
            return;
        }

        // Từ chối đề thi
        $result = $this->mExam->rejectExam($maDeThi, $lyDo);

        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Từ chối đề thi thành công']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Không thể từ chối đề thi'], 500);
        }
    }

    /**
     * Xóa đề thi
     */
    private function deleteExam()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Phương thức không hợp lệ'], 405);
            return;
        }

        $maDeThi = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maDeThi <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        // Kiểm tra quyền
        $exam = $this->mExam->getExamById($maDeThi);
        if (!$exam || $exam['maTTBM'] != $this->ttbmInfo['maTTBM']) {
            $this->jsonResponse(['success' => false, 'message' => 'Không có quyền xóa đề thi này'], 403);
            return;
        }

        if ($exam['trangThai'] !== 'Choduyet') {
            $this->jsonResponse(['success' => false, 'message' => 'Chỉ có thể xóa đề thi đang chờ duyệt'], 400);
            return;
        }

        // Xóa
        $result = $this->mExam->deleteExam($maDeThi);

        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Xóa đề thi thành công']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Không thể xóa đề thi'], 500);
        }
    }

    /**
     * Lấy danh sách môn học
     */
    private function getSubjects()
    {
        $subjects = $this->mExam->getAllSubjects();
        $this->jsonResponse(['success' => true, 'data' => $subjects]);
    }

    /**
     * Lấy thống kê
     */
    private function getStatistics()
    {
        $stats = $this->mExam->getStatistics($this->ttbmInfo['maTTBM']);
        $this->jsonResponse(['success' => true, 'data' => $stats]);
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
    $controller = new cExamApproval();
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
