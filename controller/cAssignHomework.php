<?php
session_start();
require_once(__DIR__ . '/../model/mAssignHomework.php');

class cAssignHomework {
    private $model;

    public function __construct() {
        $this->model = new mAssignHomework();
    }

    // Hiển thị trang giao bài tập
    public function index() {
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['maGV'])) {
            header("Location: ../view/login.php");
            exit();
        }

        $maGV = $_SESSION['maGV'];
        
        // Lấy danh sách lớp và môn học combo
        $classesWithSubjects = $this->model->getTeacherClassesWithSubjects($maGV);
        
        // Lấy danh sách bài tập
        $maLop = isset($_GET['maLop']) ? $_GET['maLop'] : null;
        $maMonHoc = isset($_GET['maMonHoc']) ? $_GET['maMonHoc'] : null;
        $homeworks = $this->model->getHomeworkList($maGV, $maLop, $maMonHoc);
        
        // Lấy danh sách lớp riêng cho filter
        $classes = $this->model->getTeacherClasses($maGV);
        
        // Nếu có lớp được chọn, lấy môn học
        $subjects = null;
        if ($maLop) {
            $subjects = $this->model->getTeacherSubjects($maGV, $maLop);
        }
        
        include(__DIR__ . '/../view/teacher/vAssignHomework.php');
    }

    // Lấy môn học theo lớp (AJAX)
    public function getSubjectsByClass() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['maGV']) || !isset($_POST['maLop'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $maGV = $_SESSION['maGV'];
        $maLop = $_POST['maLop'];
        
        $subjects = $this->model->getTeacherSubjects($maGV, $maLop);
        $data = [];
        
        while ($row = $subjects->fetch_assoc()) {
            $data[] = $row;
        }
        
        echo json_encode(['success' => true, 'subjects' => $data]);
    }

    // Thêm bài tập mới
    public function create() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['maGV'])) {
            echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
            return;
        }

        try {
            // Debug: Log dữ liệu nhận được
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));

            $data = [
                'maGV' => $_SESSION['maGV'], // Thêm maGV để tự động xác định môn học
                'tenBaiTap' => trim($_POST['tenBaiTap'] ?? ''),
                'yeuCauBaiTap' => trim($_POST['yeuCauBaiTap'] ?? ''),
                'thoiGianNop' => $_POST['thoiGianNop'] ?? '',
                'maLop' => $_POST['maLop'] ?? '',
                'choPhepNopTre' => isset($_POST['choPhepNopTre']) ? intval($_POST['choPhepNopTre']) : 1
            ];

            // Kiểm tra dữ liệu bắt buộc (không cần maMonHoc nữa)
            if (empty($data['tenBaiTap']) || empty($data['thoiGianNop']) || empty($data['maLop'])) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc!'
                ]);
                return;
            }

            // Thêm file nếu có
            if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $data['file'] = $_FILES['file'];
                    error_log("File will be uploaded: " . $_FILES['file']['name']);
                } else {
                    error_log("File upload error code: " . $_FILES['file']['error']);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Lỗi upload file: ' . $_FILES['file']['error']
                    ]);
                    return;
                }
            }

            $result = $this->model->createHomework($data);
            error_log("Create result: " . print_r($result, true));
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Exception in create: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
    }

    // Xóa bài tập
    public function delete() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['maBaiTap'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        if (!isset($_SESSION['maGV'])) {
            echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
            return;
        }

        $maBaiTap = $_POST['maBaiTap'];
        $result = $this->model->deleteHomework($maBaiTap);
        echo json_encode($result);
    }

    // Lấy thông tin bài tập để chỉnh sửa
    public function edit() {
        if (!isset($_GET['maBaiTap'])) {
            header("Location: ../controller/cAssignHomework.php");
            exit();
        }

        $maBaiTap = $_GET['maBaiTap'];
        $homework = $this->model->getHomeworkById($maBaiTap);
        
        if (!$homework) {
            header("Location: ../controller/cAssignHomework.php");
            exit();
        }

        $maGV = $_SESSION['maGV'];
        $classes = $this->model->getTeacherClasses($maGV);
        $subjects = $this->model->getTeacherSubjects($maGV, $homework['maLop']);
        
        include(__DIR__ . '/../view/teacher/vEditHomework.php');
    }

    // Lấy thông tin bài tập (AJAX)
    public function getHomework() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['maGV']) || !isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $maBaiTap = intval($_GET['id']);
        $homework = $this->model->getHomeworkById($maBaiTap);
        
        if ($homework) {
            echo json_encode([
                'success' => true,
                'homework' => $homework
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy bài tập'
            ]);
        }
    }

    // Cập nhật bài tập
    public function update() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['maGV']) || !isset($_POST['maBaiTap'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        try {
            $maBaiTap = intval($_POST['maBaiTap']);
            $maGV = $_POST['maGV'] ?? $_SESSION['maGV'];
            $maLop = $_POST['maLop'];
            
            // Tự động lấy môn học từ phân công giảng dạy
            $subjectInfo = $this->model->getTeacherSubjectForClass($maGV, $maLop);
            
            if (!$subjectInfo) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy môn học bạn đang dạy cho lớp này.'
                ]);
                return;
            }
            
            $data = [
                'tenBaiTap' => trim($_POST['tenBaiTap']),
                'yeuCauBaiTap' => trim($_POST['yeuCauBaiTap'] ?? ''),
                'thoiGianNop' => $_POST['thoiGianNop'],
                'maLop' => $maLop,
                'maMonHoc' => $subjectInfo['maMonHoc'], // Tự động xác định
                'choPhepNopTre' => isset($_POST['choPhepNopTre']) ? intval($_POST['choPhepNopTre']) : 1,
                'removeCurrentFile' => isset($_POST['removeCurrentFile']) ? true : false
            ];

            // Thêm file mới nếu có
            if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $data['file'] = $_FILES['file'];
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Lỗi upload file: ' . $_FILES['file']['error']
                    ]);
                    return;
                }
            }

            $result = $this->model->updateHomework($maBaiTap, $data);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Exception in update: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Download file bài tập
     */
    public function download() {
        if (!isset($_SESSION['maGV']) || !isset($_GET['id'])) {
            die('Invalid request');
        }

        $maBaiTap = intval($_GET['id']);
        $result = $this->model->downloadHomeworkFile($maBaiTap);
        
        if (!$result['success']) {
            die($result['message']);
        }

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
            case 'jpg':
            case 'jpeg':
                $contentType = 'image/jpeg';
                break;
            case 'png':
                $contentType = 'image/png';
                break;
        }

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');

        readfile($filePath);
        exit;
    }

    /**
     * Lấy danh sách năm học
     */
    public function getSchoolYears() {
        return $this->model->getSchoolYears();
    }
}

// Xử lý routing
$controller = new cAssignHomework();
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

switch ($action) {
    case 'index':
        $controller->index();
        break;
    case 'getSubjects':
        $controller->getSubjectsByClass();
        break;
    case 'create':
        $controller->create();
        break;
    case 'getHomework':
        $controller->getHomework();
        break;
    case 'update':
        $controller->update();
        break;
    case 'delete':
        $controller->delete();
        break;
    case 'download':
        $controller->download();
        break;
    default:
        $controller->index();
}
?>
