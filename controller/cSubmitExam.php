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
        session_start();
        
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
            'maMonHoc' => intval($_POST['maMonHoc'] ?? 0),
            'hocKy' => intval($_POST['hocKy'] ?? 0),
            'namHoc' => trim($_POST['namHoc'] ?? ''),
            'moTa' => trim($_POST['moTa'] ?? ''),
            'maGV' => $maGV
        ];

        // Thêm thông tin file nếu có
        if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $data['file'] = $_FILES['file'];
        }

        // Gọi model để thêm đề thi
        return $this->model->addExam($data);
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
        session_start();
        
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
            'maMonHoc' => intval($_POST['maMonHoc'] ?? 0),
            'hocKy' => intval($_POST['hocKy'] ?? 0),
            'namHoc' => trim($_POST['namHoc'] ?? ''),
            'moTa' => trim($_POST['moTa'] ?? ''),
            'maGV' => $maGV
        ];

        // Thêm thông tin file nếu có
        if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $data['file'] = $_FILES['file'];
        }

        // Gọi model để cập nhật
        return $this->model->updateExam($maDeThi, $data);
    }

    /**
     * Xử lý xóa đề thi
     */
    public function handleDeleteExam($maDeThi)
    {
        session_start();
        
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
    }

    /**
     * Xử lý download file đề thi
     */
    public function handleDownload($maDeThi)
    {
        session_start();
        
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
        session_start();
        
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
    $controller = new cSubmitExam();
    $action = $_GET['action'];

    switch ($action) {
        case 'add':
            $result = $controller->handleAddExam();
            header('Content-Type: application/json');
            echo json_encode($result);
            break;

        case 'update':
            if (isset($_GET['id'])) {
                $result = $controller->handleUpdateExam(intval($_GET['id']));
                header('Content-Type: application/json');
                echo json_encode($result);
            }
            break;

        case 'delete':
            if (isset($_GET['id'])) {
                $result = $controller->handleDeleteExam(intval($_GET['id']));
                header('Content-Type: application/json');
                echo json_encode($result);
            }
            break;

        case 'download':
            if (isset($_GET['id'])) {
                $controller->handleDownload(intval($_GET['id']));
            }
            break;

        case 'view':
            if (isset($_GET['id'])) {
                $controller->handleView(intval($_GET['id']));
            }
            break;

        default:
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Action không hợp lệ!'
            ]);
    }
    exit;
}
?>
