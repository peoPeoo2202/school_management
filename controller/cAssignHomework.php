<?php
// Kiểm tra và khởi tạo session một lần duy nhất
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
        
        // Lấy danh sách lớp
        $classes = $this->model->getTeacherClasses($maGV);
        
        // Lấy danh sách bài tập (chỉ lọc theo lớp, môn học tự động)
        $maLop = isset($_GET['maLop']) ? $_GET['maLop'] : null;
        $homeworks = $this->model->getHomeworkList($maGV, $maLop);
        
        // Subjects không cần thiết nữa
        $subjects = null;
        
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

    // Hàm chuyển đổi tiếng Việt có dấu sang không dấu
    private function removeVietnameseTones($str) {
        $vietnameseTones = [
            'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
            'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
            'ì', 'í', 'ị', 'ỉ', 'ĩ',
            'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
            'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
            'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
            'đ',
            'À', 'Á', 'Ạ', 'Ả', 'Ã', 'Â', 'Ầ', 'Ấ', 'Ậ', 'Ẩ', 'Ẫ', 'Ă', 'Ằ', 'Ắ', 'Ặ', 'Ẳ', 'Ẵ',
            'È', 'É', 'Ẹ', 'Ẻ', 'Ẽ', 'Ê', 'Ề', 'Ế', 'Ệ', 'Ể', 'Ễ',
            'Ì', 'Í', 'Ị', 'Ỉ', 'Ĩ',
            'Ò', 'Ó', 'Ọ', 'Ỏ', 'Õ', 'Ô', 'Ồ', 'Ố', 'Ộ', 'Ổ', 'Ỗ', 'Ơ', 'Ờ', 'Ớ', 'Ợ', 'Ở', 'Ỡ',
            'Ù', 'Ú', 'Ụ', 'Ủ', 'Ũ', 'Ư', 'Ừ', 'Ứ', 'Ự', 'Ử', 'Ữ',
            'Ỳ', 'Ý', 'Ỵ', 'Ỷ', 'Ỹ',
            'Đ'
        ];
        
        $noTones = [
            'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
            'y', 'y', 'y', 'y', 'y',
            'd',
            'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A',
            'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E',
            'I', 'I', 'I', 'I', 'I',
            'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O',
            'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U',
            'Y', 'Y', 'Y', 'Y', 'Y',
            'D'
        ];
        
        return str_replace($vietnameseTones, $noTones, $str);
    }

    // Hàm tạo slug từ tên bài tập - CẢI TIẾN HOÀN TOÀN
    private function createSlug($text) {
        // Nếu rỗng, trả về tên mặc định
        if (empty(trim($text))) {
            return 'bai-tap';
        }
        
        // Chuyển sang chữ thường
        $text = mb_strtolower($text, 'UTF-8');
        
        // Bỏ dấu tiếng Việt
        $text = $this->removeVietnameseTones($text);
        
        // Thay thế khoảng trắng và ký tự đặc biệt bằng dấu gạch ngang
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        
        // Loại bỏ dấu gạch ngang ở đầu và cuối
        $text = trim($text, '-');
        
        // Nếu sau khi xử lý vẫn rỗng, dùng tên mặc định
        if (empty($text)) {
            return 'bai-tap';
        }
        
        // Giới hạn độ dài (tối đa 50 ký tự)
        if (strlen($text) > 50) {
            $text = substr($text, 0, 50);
            $text = trim($text, '-');
        }
        
        return $text;
    }

    // Xử lý upload file - CẢI TIẾN
    private function handleFileUpload($file, $tenBaiTap = '') {
        $allowedTypes = ['application/pdf', 'application/msword', 
                         'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                         'application/vnd.ms-excel',
                         'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                         'application/vnd.ms-powerpoint',
                         'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                         'text/plain'];
        
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        // Kiểm tra kích thước
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File vượt quá 10MB'];
        }
        
        // Kiểm tra loại file
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Loại file không được hỗ trợ'];
        }
        
        // Tạo thư mục upload nếu chưa có
        $uploadDir = __DIR__ . '/../uploads/homework/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Lấy extension từ file gốc
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Tạo slug từ tên bài tập
        $slug = $this->createSlug($tenBaiTap);
        
        // Thêm timestamp để tránh trùng lặp
        $timestamp = time();
        $fileName = $slug . '-' . $timestamp . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        // Kiểm tra nếu file vẫn tồn tại (rất hiếm), thêm random string
        if (file_exists($filePath)) {
            $randomStr = substr(md5(uniqid(rand(), true)), 0, 8);
            $fileName = $slug . '-' . $timestamp . '-' . $randomStr . '.' . $extension;
            $filePath = $uploadDir . $fileName;
        }
        
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'success' => true,
                'tenFile' => $fileName,
                'duongDan' => $filePath
            ];
        } else {
            return ['success' => false, 'message' => 'Không thể upload file'];
        }
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

        $data = [
            'tenBaiTap' => trim($_POST['tenBaiTap']),
            'yeuCauBaiTap' => trim($_POST['yeuCauBaiTap']),
            'thoiGianNop' => $_POST['thoiGianNop'],
            'maLop' => $_POST['maLop'],
            'maMonHoc' => $_POST['maMonHoc'],
            'maGV' => $_SESSION['maGV'],
            'choPhepNopTre' => isset($_POST['choPhepNopTre']) ? (int)$_POST['choPhepNopTre'] : 0
        ];

        // Validate
        if (empty($data['tenBaiTap']) || empty($data['thoiGianNop']) || 
            empty($data['maLop']) || empty($data['maMonHoc'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
            return;
        }

        // Xử lý upload file nếu có - TRUYỀN TÊN BÀI TẬP VÀO
        if (isset($_FILES['fileBaiTap']) && $_FILES['fileBaiTap']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleFileUpload($_FILES['fileBaiTap'], $data['tenBaiTap']);
            
            if ($uploadResult['success']) {
                $data['tenFile'] = $uploadResult['tenFile'];
                $data['duongDan'] = $uploadResult['duongDan'];
            } else {
                echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
                return;
            }
        }

        if ($this->model->createHomework($data)) {
            echo json_encode(['success' => true, 'message' => 'Giao bài tập thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi giao bài tập']);
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
        
        if ($this->model->deleteHomework($maBaiTap)) {
            echo json_encode(['success' => true, 'message' => 'Xóa bài tập thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa bài tập']);
        }
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

    // Lấy chi tiết bài tập (AJAX)
    public function getDetail() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['maGV']) || !isset($_POST['maBaiTap'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $maBaiTap = $_POST['maBaiTap'];
        $homework = $this->model->getHomeworkById($maBaiTap);
        
        if ($homework) {
            echo json_encode(['success' => true, 'homework' => $homework]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài tập']);
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

        $maBaiTap = $_POST['maBaiTap'];
        $data = [
            'tenBaiTap' => trim($_POST['tenBaiTap']),
            'yeuCauBaiTap' => trim($_POST['yeuCauBaiTap']),
            'thoiGianNop' => $_POST['thoiGianNop'],
            'maLop' => $_POST['maLop'],
            'maMonHoc' => $_POST['maMonHoc'],
            'choPhepNopTre' => isset($_POST['choPhepNopTre']) ? (int)$_POST['choPhepNopTre'] : 0
        ];

        // Validate
        if (empty($data['tenBaiTap']) || empty($data['thoiGianNop']) || 
            empty($data['maLop']) || empty($data['maMonHoc'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
            return;
        }

        // Xử lý upload file mới nếu có - TRUYỀN TÊN BÀI TẬP VÀO
        if (isset($_FILES['fileBaiTap']) && $_FILES['fileBaiTap']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleFileUpload($_FILES['fileBaiTap'], $data['tenBaiTap']);
            
            if ($uploadResult['success']) {
                $data['tenFile'] = $uploadResult['tenFile'];
                $data['duongDan'] = $uploadResult['duongDan'];
                
                // Xóa file cũ nếu có
                $oldHomework = $this->model->getHomeworkById($maBaiTap);
                if ($oldHomework && !empty($oldHomework['duongDan']) && file_exists($oldHomework['duongDan'])) {
                    @unlink($oldHomework['duongDan']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
                return;
            }
        }

        if ($this->model->updateHomework($maBaiTap, $data)) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật bài tập thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật']);
        }
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
    case 'getDetail':
        $controller->getDetail();
        break;
    case 'create':
        $controller->create();
        break;
    case 'update':
        $controller->update();
        break;
    case 'delete':
        $controller->delete();
        break;
    default:
        $controller->index();
}
?>
