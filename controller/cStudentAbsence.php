<?php
require_once(__DIR__ . '/../model/mStudentAbsence.php');
require_once(__DIR__ . '/../model/mConnect.php');

class cStudentAbsence
{
    private $model;

    public function __construct()
    {
        $mConnect = new mConnect();
        $conn = $mConnect->mConnect();
        
        if (!$conn) {
            die("Kết nối thất bại!");
        }
        
        $this->model = new ModelStudentAbsence($conn);
    }

    /**
     * Hiển thị trang quản lý nghỉ học
     */
    public function index()
    {
        // Kiểm tra quyền truy cập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] != 'giaovien') {
            header('Location: ../../public/index.php');
            exit();
        }

        $maGV = $_SESSION['maGV'] ?? null;

        // Lấy danh sách lớp chủ nhiệm của giáo viên
        $classes = $this->model->getClassesByTeacher($maGV);

        // Lấy maLop
        $maLop = $_GET['maLop'] ?? null;
        if (!$maLop) {
            if (empty($classes)) {
                $data = ['error' => 'Bạn chưa được phân công làm giáo viên chủ nhiệm lớp nào'];
                require_once(__DIR__ . '/../view/teacher/vStudentAbsence.php');
                return;
            } else {
                $maLop = $classes[0]['maLop'];
            }
        }

        // Lấy thông tin lớp
        $classInfo = $this->model->getClassInfo($maLop);

        // Kiểm tra quyền
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            $data = ['error' => 'Bạn không có quyền xem thông tin của lớp này'];
            require_once(__DIR__ . '/../view/teacher/vStudentAbsence.php');
            return;
        }

        // Lấy thông tin học kỳ và năm học
        $currentYear = date('Y');
        $defaultNamHoc = ($currentYear - 1) . '-' . $currentYear;
        $defaultHocKy = (date('m') <= 6) ? 2 : 1;

        $namHoc = $_GET['namHoc'] ?? $defaultNamHoc;
        $hocKy = $_GET['hocKy'] ?? $defaultHocKy;
        
        // Đảm bảo namHoc luôn có format đầy đủ (YYYY-YYYY)
        $needsRedirect = false;
        if (strpos($namHoc, '-') === false && is_numeric($namHoc)) {
            // Nếu chỉ có năm đơn (VD: "2024"), chuyển thành format đầy đủ
            $namHoc = $namHoc . '-' . ($namHoc + 1);
            $needsRedirect = true;
        }
        
        // Redirect để cập nhật URL với giá trị đúng
        if ($needsRedirect && isset($_GET['namHoc'])) {
            header("Location: vStudentAbsence.php?maLop=" . urlencode($maLop) . "&hocKy=" . urlencode($hocKy) . "&namHoc=" . urlencode($namHoc));
            exit();
        }

        // Lấy danh sách học sinh
        $students = $this->model->getStudentAbsences($maLop, $hocKy, $namHoc);

        // Lấy danh sách năm học có dữ liệu
        $availableYears = $this->model->getAvailableYears();

        $data = [
            'classes' => $classes,
            'classInfo' => $classInfo,
            'currentClassId' => $maLop,
            'hocKy' => $hocKy,
            'namHoc' => $namHoc,
            'students' => $students,
            'maGV' => $maGV,
            'availableYears' => $availableYears
        ];

        require_once(__DIR__ . '/../view/teacher/vStudentAbsence.php');
    }

    /**
     * Xử lý AJAX requests
     */
    public function handleAjax()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] != 'giaovien') {
            echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
            exit();
        }

        $maGV = $_SESSION['maGV'] ?? null;
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'add':
                $this->addAbsence($maGV);
                break;

            case 'update':
                $this->updateAbsence();
                break;

            case 'delete':
                $this->deleteAbsence();
                break;

            case 'getDetails':
                $this->getAbsenceDetails();
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }

        exit();
    }

    /**
     * Thêm nghỉ học
     */
    private function addAbsence($maGV)
    {
        $maHS = $_POST['maHS'] ?? null;
        $ngayNghi = $_POST['ngayNghi'] ?? null;
        $hocKy = $_POST['hocKy'] ?? null;
        $namHoc = $_POST['namHoc'] ?? null;
        $loaiNghi = $_POST['loaiNghi'] ?? 'cophep';
        $lyDo = $_POST['lyDo'] ?? '';

        // Debug: Log dữ liệu nhận được - GIỮ NGUYÊN GIÁ TRỊ
        error_log("=== ADD ABSENCE DEBUG ===");
        error_log("maHS: " . var_export($maHS, true));
        error_log("ngayNghi: " . var_export($ngayNghi, true));
        error_log("hocKy: " . var_export($hocKy, true));
        error_log("namHoc RAW: " . var_export($namHoc, true));
        error_log("namHoc length: " . strlen($namHoc ?? ''));
        error_log("loaiNghi: " . var_export($loaiNghi, true));
        error_log("lyDo: " . var_export($lyDo, true));
        error_log("maGV: " . var_export($maGV, true));
        error_log("POST data: " . print_r($_POST, true));

        if (!$maHS || !$ngayNghi || !$hocKy || !$namHoc) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }

        $result = $this->model->addAbsence($maHS, $ngayNghi, $hocKy, $namHoc, $loaiNghi, $lyDo, $maGV);
        echo json_encode($result);
    }

    /**
     * Cập nhật nghỉ học
     */
    private function updateAbsence()
    {
        $maNghiHoc = $_POST['maNghiHoc'] ?? null;
        $ngayNghi = $_POST['ngayNghi'] ?? null;
        $loaiNghi = $_POST['loaiNghi'] ?? 'cophep';
        $lyDo = $_POST['lyDo'] ?? '';

        if (!$maNghiHoc || !$ngayNghi) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }

        $result = $this->model->updateAbsence($maNghiHoc, $ngayNghi, $loaiNghi, $lyDo);
        echo json_encode($result);
    }

    /**
     * Xóa nghỉ học
     */
    private function deleteAbsence()
    {
        $maNghiHoc = $_POST['maNghiHoc'] ?? null;

        if (!$maNghiHoc) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }

        $result = $this->model->deleteAbsence($maNghiHoc);
        echo json_encode($result);
    }

    /**
     * Lấy chi tiết nghỉ học
     */
    private function getAbsenceDetails()
    {
        $maHS = $_POST['maHS'] ?? null;
        $hocKy = $_POST['hocKy'] ?? null;
        $namHoc = $_POST['namHoc'] ?? null;

        if (!$maHS || !$hocKy || !$namHoc) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }

        $details = $this->model->getAbsenceDetails($maHS, $hocKy, $namHoc);
        echo json_encode(['success' => true, 'data' => $details]);
    }
}

// Xử lý request
if (isset($_POST['action'])) {
    $controller = new cStudentAbsence();
    $controller->handleAjax();
} else {
    $controller = new cStudentAbsence();
    $controller->index();
}
?>
