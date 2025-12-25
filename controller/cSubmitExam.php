<?php
/**
 * Controller xử lý đề thi
 * Xử lý các request từ view và gọi model tương ứng
 */

require_once(__DIR__ . '/../model/mConnect.php');
require_once(__DIR__ . '/../model/mSubmitExam.php');

class cSubmitExam
{
    private $model;

    public function __construct()
    {
        $this->model = new mSubmitExam();
    }

    /**
     * Lấy danh sách môn học của giáo viên
     */
    public function getTeacherSubjects($maGV)
    {
        return $this->model->getTeacherSubjects($maGV);
    }

    /**
     * Xử lý thêm đề thi mới
     */
    public function handleAddExam()
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Kiểm tra đăng nhập
            if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
                return [
                    'success' => false,
                    'message' => 'Vui lòng đăng nhập!'
                ];
            }

        // Kiểm tra quyền giáo viên
        if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
            return [
                'success' => false,
                'message' => 'Bạn không có quyền thực hiện chức năng này!'
            ];
        }

        $maGV = $_SESSION['maGV'] ?? null;
        if (!$maGV) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy thông tin giáo viên!'
            ];
        }

        // Kiểm tra method POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [
                'success' => false,
                'message' => 'Method không hợp lệ!'
            ];
        }

        // Lấy dữ liệu từ form
        $data = [
            'tenDeThi' => trim($_POST['tenDeThi'] ?? ''),
            'loaiDeThi' => trim($_POST['loaiDeThi'] ?? 'de-thi'),
            'hocKy' => intval($_POST['hocKy'] ?? 0),
            'namHoc' => trim($_POST['namHoc'] ?? ''),
            'moTa' => trim($_POST['moTa'] ?? ''),
            'maGV' => $maGV
        ];

        // Tự động lấy môn học mà giáo viên đang dạy
        $maMonHoc = $this->model->getTeacherMainSubject($maGV);
        
        if (!$maMonHoc) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy môn học bạn đang giảng dạy!'
            ];
        }
        
        $data['maMonHoc'] = $maMonHoc;

        // Thêm thông tin file nếu có
        if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $data['file'] = $_FILES['file'];
        }

            // Gọi model để thêm đề thi
            return $this->model->addExam($data);
        } catch (Exception $e) {
            error_log('Error in handleAddExam: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy danh sách đề thi của giáo viên
     */
    public function getTeacherExams($maGV, $filters = [])
    {
        return $this->model->getTeacherExams($maGV, $filters);
    }

    /**
     * Lấy chi tiết đề thi
     */
    public function getExamDetail($maDeThi, $maGV = null)
    {
        return $this->model->getExamDetail($maDeThi, $maGV);
    }

    /**
     * Xử lý cập nhật đề thi
     */
    public function handleUpdateExam($maDeThi)
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Kiểm tra đăng nhập
        if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            return [
                'success' => false,
                'message' => 'Vui lòng đăng nhập!'
            ];
        }

        // Kiểm tra quyền giáo viên
        if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
            return [
                'success' => false,
                'message' => 'Bạn không có quyền thực hiện chức năng này!'
            ];
        }

        $maGV = $_SESSION['maGV'] ?? null;
        if (!$maGV) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy thông tin giáo viên!'
            ];
        }

        // Kiểm tra method POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [
                'success' => false,
                'message' => 'Method không hợp lệ!'
            ];
        }

        // Lấy dữ liệu từ form
        $data = [
            'tenDeThi' => trim($_POST['tenDeThi'] ?? ''),
            'loaiDeThi' => trim($_POST['loaiDeThi'] ?? 'de-thi'),
            'hocKy' => intval($_POST['hocKy'] ?? 0),
            'namHoc' => trim($_POST['namHoc'] ?? ''),
            'moTa' => trim($_POST['moTa'] ?? ''),
            'maGV' => $maGV
        ];

        // Tự động lấy môn học mà giáo viên đang dạy
        $maMonHoc = $this->model->getTeacherMainSubject($maGV);
        
        if (!$maMonHoc) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy môn học bạn đang giảng dạy!'
            ];
        }
        
        $data['maMonHoc'] = $maMonHoc;

        // Thêm thông tin file nếu có
        if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $data['file'] = $_FILES['file'];
        }

            // Gọi model để cập nhật
            return $this->model->updateExam($maDeThi, $data);
        } catch (Exception $e) {
            error_log('Error in handleUpdateExam: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xử lý xóa đề thi
     */
    public function handleDeleteExam($maDeThi)
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Kiểm tra đăng nhập
        if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            return [
                'success' => false,
                'message' => 'Vui lòng đăng nhập!'
            ];
        }

        // Kiểm tra quyền giáo viên
        if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
            return [
                'success' => false,
                'message' => 'Bạn không có quyền thực hiện chức năng này!'
            ];
        }

        $maGV = $_SESSION['maGV'] ?? null;
        if (!$maGV) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy thông tin giáo viên!'
            ];
        }

            // Gọi model để xóa
            return $this->model->deleteExam($maDeThi, $maGV);
        } catch (Exception $e) {
            error_log('Error in handleDeleteExam: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xử lý download file đề thi
     */
    public function handleDownload($maDeThi)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            die('Vui lòng đăng nhập!');
        }

        $maGV = $_SESSION['maGV'] ?? null;
        
        // Lấy thông tin file
        $result = $this->model->downloadExamFile($maDeThi, $maGV);
        
        if (!$result['success']) {
            die($result['message']);
        }

        // Download file
        $filePath = $result['filePath'];
        $fileName = $result['fileName'];
        
        // Xác định content type
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $contentType = 'application/octet-stream';
        
        switch ($extension) {
            case 'pdf':
                $contentType = 'application/pdf';
                break;
            case 'doc':
                $contentType = 'application/msword';
                break;
            case 'docx':
                $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                break;
            case 'xls':
                $contentType = 'application/vnd.ms-excel';
                break;
            case 'xlsx':
                $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
        }

        // Set headers
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');

        // Output file
        readfile($filePath);
        exit;
    }

    /**
     * Xử lý view file đề thi trong trình duyệt
     */
    public function handleView($maDeThi)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            die('Vui lòng đăng nhập!');
        }

        $maGV = $_SESSION['maGV'] ?? null;
        
        // Lấy thông tin file
        $result = $this->model->downloadExamFile($maDeThi, $maGV);
        
        if (!$result['success']) {
            die($result['message']);
        }

        // View file
        $filePath = $result['filePath'];
        $fileName = $result['fileName'];
        
        // Xác định content type
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $contentType = 'application/octet-stream';
        
        switch ($extension) {
            case 'pdf':
                $contentType = 'application/pdf';
                break;
            case 'doc':
                $contentType = 'application/msword';
                break;
            case 'docx':
                $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                break;
            case 'xls':
                $contentType = 'application/vnd.ms-excel';
                break;
            case 'xlsx':
                $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
        }

        // Set headers để hiển thị inline
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Output file
        readfile($filePath);
        exit;
    }

    /**
     * Lấy danh sách năm học
     */
    public function getSchoolYears()
    {
        return $this->model->getSchoolYears();
    }
}

// Xử lý các action từ request - chỉ chạy khi được gọi trực tiếp
if (basename($_SERVER['PHP_SELF']) === 'cSubmitExam.php' && isset($_GET['action'])) {
    // Ngăn chặn tất cả output không mong muốn (errors, warnings, notices)
    ob_start();
    
    // Tắt hiển thị lỗi ra màn hình (chỉ log)
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    
    $controller = new cSubmitExam();
    $action = $_GET['action'];

    switch ($action) {
        case 'add':
            // Clear bất kỳ output buffer nào trước đó
            ob_clean();
            $result = $controller->handleAddExam();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;

        case 'update':
            if (isset($_GET['id'])) {
                ob_clean();
                $result = $controller->handleUpdateExam(intval($_GET['id']));
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'delete':
            if (isset($_GET['id'])) {
                ob_clean();
                $result = $controller->handleDeleteExam(intval($_GET['id']));
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'download':
            if (isset($_GET['id'])) {
                ob_end_clean(); // Clear buffer trước khi download file
                $controller->handleDownload(intval($_GET['id']));
            }
            break;

        case 'view':
            if (isset($_GET['id'])) {
                ob_end_clean(); // Clear buffer trước khi view file
                $controller->handleView(intval($_GET['id']));
            }
            break;

        default:
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Action không hợp lệ!'
            ], JSON_UNESCAPED_UNICODE);
    }
    
    // Kết thúc output buffering và gửi response
    ob_end_flush();
    exit;
}
?>
