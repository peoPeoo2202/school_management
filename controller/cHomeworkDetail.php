<?php
// Kiểm tra và khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../model/mHomeworkDetail.php');

class cHomeworkDetail {
    private $model;

    public function __construct() {
        $this->model = new mHomeworkDetail();
    }

    // Hiển thị trang chi tiết bài tập
    public function index() {
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['maGV'])) {
            header("Location: ../view/login.php");
            exit();
        }

        $maBaiTap = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($maBaiTap <= 0) {
            header("Location: ../controller/cAssignHomework.php");
            exit();
        }

        // Lấy thông tin bài tập
        $homework = $this->model->getHomeworkDetail($maBaiTap);
        
        if (!$homework) {
            header("Location: ../controller/cAssignHomework.php");
            exit();
        }

        // Kiểm tra quyền: chỉ giáo viên giao bài mới được xem
        if ($homework['maGV'] != $_SESSION['maGV']) {
            echo "<script>alert('Bạn không có quyền xem bài tập này'); window.location.href='../controller/cAssignHomework.php';</script>";
            exit();
        }

        // Lấy danh sách bài nộp
        $submissions = $this->model->getSubmissions($maBaiTap);
        
        // Lấy danh sách học sinh trong lớp
        $students = $this->model->getStudentsList($homework['maLop']);

        include(__DIR__ . '/../view/teacher/vHomeworkDetail.php');
    }

    // Chấm điểm bài nộp (AJAX) - ADD BETTER VALIDATION
    public function grade() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['maGV'])) {
            echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
            return;
        }

        $maBaiNop = isset($_POST['maBaiNop']) ? intval($_POST['maBaiNop']) : 0;
        $diem = isset($_POST['diem']) ? floatval($_POST['diem']) : null;
        $nhanXet = isset($_POST['nhanXet']) ? trim($_POST['nhanXet']) : '';

        // Validate chi tiết hơn
        if ($maBaiNop <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã bài nộp không hợp lệ (ID: ' . $maBaiNop . ')']);
            error_log("Invalid maBaiNop: " . $maBaiNop);
            return;
        }

        if ($diem === null || $diem < 0 || $diem > 10) {
            echo json_encode(['success' => false, 'message' => 'Điểm phải từ 0 đến 10']);
            return;
        }

        $maGV = $_SESSION['maGV'];

        $result = $this->model->gradeSubmission($maBaiNop, $diem, $nhanXet, $maGV);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Chấm điểm thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi chấm điểm. Vui lòng kiểm tra lại mã bài nộp.']);
            error_log("gradeSubmission failed for maBaiNop: " . $maBaiNop);
        }
    }

    // Lấy thông tin bài nộp để hiển thị modal chấm điểm (AJAX)
    public function getSubmissionDetail() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['maGV']) || !isset($_POST['maBaiNop'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $maBaiNop = intval($_POST['maBaiNop']);
        $submission = $this->model->getSubmissionDetail($maBaiNop);
        
        if ($submission) {
            echo json_encode(['success' => true, 'submission' => $submission]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài nộp']);
        }
    }
}

// Xử lý routing
$controller = new cHomeworkDetail();
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

switch ($action) {
    case 'index':
        $controller->index();
        break;
    case 'grade':
        $controller->grade();
        break;
    case 'getDetail':
        $controller->getSubmissionDetail();
        break;
    default:
        $controller->index();
}
?>
