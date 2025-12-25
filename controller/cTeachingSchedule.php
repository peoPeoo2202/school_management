<?php
session_start();
require_once('../model/mTeachingSchedule.php');

class cTeachingSchedule
{
    private $model;

    public function __construct()
    {
        $this->model = new mTeachingSchedule();
    }

    /**
     * Kiểm tra đăng nhập và quyền truy cập
     * CHỈ CHO PHÉP tài khoản loaiTaiKhoan = 'giaovien'
     */
    private function checkTeacherAccess()
    {
        // Kiểm tra session đăng nhập
        if (!isset($_SESSION['maTaiKhoan']) || !isset($_SESSION['maGV'])) {
            return [
                'success' => false,
                'message' => 'Vui lòng đăng nhập với tài khoản giáo viên',
                'redirect' => '../public/login.php'
            ];
        }

        // Kiểm tra loại tài khoản phải là 'giaovien'
        if (!isset($_SESSION['loaiTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'giaovien') {
            return [
                'success' => false,
                'message' => 'Chức năng này chỉ dành cho giáo viên. Loại tài khoản của bạn: ' . ($_SESSION['loaiTaiKhoan'] ?? 'không xác định'),
                'redirect' => '../public/index.php'
            ];
        }

        return ['success' => true];
    }

    /**
     * Xử lý request và điều hướng đến action tương ứng
     */
    public function handleRequest()
    {
        // Kiểm tra quyền truy cập
        $accessCheck = $this->checkTeacherAccess();
        if (!$accessCheck['success']) {
            if (isset($accessCheck['redirect'])) {
                header('Location: ' . $accessCheck['redirect']);
                exit();
            }
            echo json_encode($accessCheck);
            return;
        }

        $action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

        switch ($action) {
            case 'dashboard':
                $this->viewDashboard();
                break;
            case 'viewClasses':
                $this->viewClasses();
                break;
            case 'viewSchedule':
                $this->viewSchedule();
                break;
            case 'viewExamSupervision':
                $this->viewExamSupervision();
                break;
            case 'viewGradingAssignment':
                $this->viewGradingAssignment();
                break;
            case 'getScheduleDetail':
                $this->getScheduleDetail();
                break;
            case 'api_getClasses':
                $this->apiGetClasses();
                break;
            case 'api_getSchedule':
                $this->apiGetSchedule();
                break;
            case 'api_getExamSupervision':
                $this->apiGetExamSupervision();
                break;
            case 'api_getGradingAssignment':
                $this->apiGetGradingAssignment();
                break;
            default:
                $this->viewDashboard();
                break;
        }
    }

    /**
     * Hiển thị Dashboard tổng quan
     */
    public function viewDashboard()
    {
        $maGV = $_SESSION['maGV'];
        
        // Lấy học kỳ và năm học hiện tại từ session hoặc mặc định
        $hocKy = isset($_SESSION['hocKy']) ? $_SESSION['hocKy'] : 1;
        $namHoc = isset($_SESSION['namHoc']) ? $_SESSION['namHoc'] : '2024-2025';

        // Lấy thông tin tổng quan
        $summary = $this->model->getDashboardSummary($maGV, $hocKy, $namHoc);

        // Lấy lịch dạy tuần này (theo ngày hiện tại)
        $currentDay = date('N') + 1; // 1=Monday -> 2 (Thứ 2)
        if ($currentDay == 8) $currentDay = 2; // Sunday -> Thứ 2
        $scheduleToday = $this->model->getTeachingSchedule($maGV, $hocKy, $namHoc, null, $currentDay);

        // Lấy ca thi sắp tới
        $upcomingExams = $this->model->getExamSupervision($maGV, $hocKy, $namHoc, 'scheduled');
        // Lọc chỉ lấy 5 ca gần nhất
        if ($upcomingExams['success'] && count($upcomingExams['data']) > 0) {
            $upcomingExams['data'] = array_slice($upcomingExams['data'], 0, 5);
        }

        // Lấy phân công chấm đang làm
        $ongoingGrading = $this->model->getGradingAssignment($maGV, $hocKy, $namHoc, 'in_progress');

        // Truyền dữ liệu sang view
        $data = [
            'summary' => $summary,
            'scheduleToday' => $scheduleToday,
            'upcomingExams' => $upcomingExams,
            'ongoingGrading' => $ongoingGrading,
            'hocKy' => $hocKy,
            'namHoc' => $namHoc
        ];

        require_once('../view/teacher/vTeacherDashboard.php');
    }

    /**
     * Hiển thị danh sách lớp học
     */
    public function viewClasses()
    {
        $maGV = $_SESSION['maGV'];
        
        // Lấy tham số lọc từ GET
        $hocKy = isset($_GET['hocKy']) ? intval($_GET['hocKy']) : (isset($_SESSION['hocKy']) ? $_SESSION['hocKy'] : null);
        $namHoc = isset($_GET['namHoc']) ? trim($_GET['namHoc']) : (isset($_SESSION['namHoc']) ? $_SESSION['namHoc'] : null);
        $maMonHoc = isset($_GET['maMonHoc']) ? intval($_GET['maMonHoc']) : null;
        $maKhoi = isset($_GET['maKhoi']) ? intval($_GET['maKhoi']) : null;

        // Lấy danh sách lớp
        $classes = $this->model->getTeacherClasses($maGV, $hocKy, $namHoc, $maMonHoc, $maKhoi);

        // Truyền dữ liệu sang view
        $data = [
            'classes' => $classes,
            'filters' => [
                'hocKy' => $hocKy,
                'namHoc' => $namHoc,
                'maMonHoc' => $maMonHoc,
                'maKhoi' => $maKhoi
            ]
        ];

        require_once('../view/teacher/vClassList.php');
    }

    /**
     * Hiển thị lịch dạy (thời khóa biểu)
     */
    public function viewSchedule()
    {
        $maGV = $_SESSION['maGV'];
        
        // Lấy tham số lọc từ GET
        $hocKy = isset($_GET['hocKy']) ? intval($_GET['hocKy']) : (isset($_SESSION['hocKy']) ? $_SESSION['hocKy'] : 1);
        $namHoc = isset($_GET['namHoc']) ? trim($_GET['namHoc']) : (isset($_SESSION['namHoc']) ? $_SESSION['namHoc'] : '2024-2025');
        $maLop = isset($_GET['maLop']) ? intval($_GET['maLop']) : null;
        $thu = isset($_GET['thu']) ? intval($_GET['thu']) : null;

        // Lấy lịch dạy
        $schedule = $this->model->getTeachingSchedule($maGV, $hocKy, $namHoc, $maLop, $thu);

        // Lấy danh sách lớp từ view phân công giảng dạy
        $classes = $this->model->getClassesFromPhanCong($maGV, $namHoc);

        // Tổ chức dữ liệu thành grid (theo thứ và tiết)
        $scheduleGrid = $this->organizeScheduleGrid($schedule['data']);

        // Truyền dữ liệu sang view
        $data = [
            'schedule' => $schedule,
            'scheduleGrid' => $scheduleGrid,
            'classes' => $classes,
            'filters' => [
                'hocKy' => $hocKy,
                'namHoc' => $namHoc,
                'maLop' => $maLop,
                'thu' => $thu
            ]
        ];

        require_once('../view/teacher/vTeachingSchedule.php');
    }

    /**
     * Hiển thị phân công coi thi
     */
    public function viewExamSupervision()
    {
        $maGV = $_SESSION['maGV'];
        
        // Lấy tham số lọc từ GET
        $hocKy = isset($_GET['hocKy']) ? intval($_GET['hocKy']) : (isset($_SESSION['hocKy']) ? $_SESSION['hocKy'] : null);
        $namHoc = isset($_GET['namHoc']) ? trim($_GET['namHoc']) : (isset($_SESSION['namHoc']) ? $_SESSION['namHoc'] : null);
        $trangThai = isset($_GET['trangThai']) ? trim($_GET['trangThai']) : null;
        $loaiKyThi = isset($_GET['loaiKyThi']) ? trim($_GET['loaiKyThi']) : null;

        // Lấy danh sách phân công coi thi
        $examSupervision = $this->model->getExamSupervision($maGV, $hocKy, $namHoc, $trangThai, $loaiKyThi);

        // Truyền dữ liệu sang view
        $data = [
            'examSupervision' => $examSupervision,
            'filters' => [
                'hocKy' => $hocKy,
                'namHoc' => $namHoc,
                'trangThai' => $trangThai,
                'loaiKyThi' => $loaiKyThi
            ]
        ];

        require_once('../view/teacher/vExamSupervision.php');
    }

    /**
     * Hiển thị phân công chấm điểm
     */
    public function viewGradingAssignment()
    {
        $maGV = $_SESSION['maGV'];
        
        // Lấy tham số lọc từ GET
        $hocKy = isset($_GET['hocKy']) ? intval($_GET['hocKy']) : (isset($_SESSION['hocKy']) ? $_SESSION['hocKy'] : null);
        $namHoc = isset($_GET['namHoc']) ? trim($_GET['namHoc']) : (isset($_SESSION['namHoc']) ? $_SESSION['namHoc'] : null);
        $trangThai = isset($_GET['trangThai']) ? trim($_GET['trangThai']) : null;
        $loaiKiemTra = isset($_GET['loaiKiemTra']) ? trim($_GET['loaiKiemTra']) : null;

        // Lấy danh sách phân công chấm điểm
        $gradingAssignment = $this->model->getGradingAssignment($maGV, $hocKy, $namHoc, $trangThai, $loaiKiemTra);

        // Truyền dữ liệu sang view
        $data = [
            'gradingAssignment' => $gradingAssignment,
            'filters' => [
                'hocKy' => $hocKy,
                'namHoc' => $namHoc,
                'trangThai' => $trangThai,
                'loaiKiemTra' => $loaiKiemTra
            ]
        ];

        require_once('../view/teacher/vGradingAssignment.php');
    }

    /**
     * Lấy chi tiết một tiết dạy (AJAX)
     */
    public function getScheduleDetail()
    {
        header('Content-Type: application/json');
        
        if (!isset($_GET['maLichDay'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Thiếu mã lịch dạy'
            ]);
            return;
        }

        $maLichDay = intval($_GET['maLichDay']);
        $result = $this->model->getScheduleDetail($maLichDay);
        
        echo json_encode($result);
    }

    /**
     * Tổ chức lịch dạy thành grid (theo thứ và tiết)
     */
    private function organizeScheduleGrid($scheduleData)
    {
        $grid = [];
        
        // Khởi tạo grid trống (Thứ 2-8, Tiết 1-10)
        for ($thu = 2; $thu <= 8; $thu++) {
            for ($tiet = 1; $tiet <= 10; $tiet++) {
                $grid[$thu][$tiet] = null;
            }
        }

        // Điền dữ liệu vào grid
        foreach ($scheduleData as $item) {
            $thu = $item['thu'];
            $tietBatDau = $item['tietBatDau'];
            $tietKetThuc = $item['tietKetThuc'];

            // Đánh dấu các tiết từ tietBatDau đến tietKetThuc
            for ($tiet = $tietBatDau; $tiet <= $tietKetThuc; $tiet++) {
                if ($tiet == $tietBatDau) {
                    // Tiết đầu tiên lưu toàn bộ thông tin
                    $grid[$thu][$tiet] = $item;
                } else {
                    // Các tiết sau đánh dấu là "merged"
                    $grid[$thu][$tiet] = 'merged';
                }
            }
        }

        return $grid;
    }

    // ============ API ENDPOINTS ============

    /**
     * API: Lấy danh sách lớp học (JSON)
     */
    public function apiGetClasses()
    {
        header('Content-Type: application/json');
        
        $accessCheck = $this->checkTeacherAccess();
        if (!$accessCheck['success']) {
            echo json_encode($accessCheck);
            return;
        }

        $maGV = $_SESSION['maGV'];
        $hocKy = isset($_GET['hocKy']) ? intval($_GET['hocKy']) : null;
        $namHoc = isset($_GET['namHoc']) ? trim($_GET['namHoc']) : null;
        $maMonHoc = isset($_GET['maMonHoc']) ? intval($_GET['maMonHoc']) : null;
        $maKhoi = isset($_GET['maKhoi']) ? intval($_GET['maKhoi']) : null;

        $result = $this->model->getTeacherClasses($maGV, $hocKy, $namHoc, $maMonHoc, $maKhoi);
        echo json_encode($result);
    }

    /**
     * API: Lấy lịch dạy (JSON)
     */
    public function apiGetSchedule()
    {
        header('Content-Type: application/json');
        
        $accessCheck = $this->checkTeacherAccess();
        if (!$accessCheck['success']) {
            echo json_encode($accessCheck);
            return;
        }

        $maGV = $_SESSION['maGV'];
        $hocKy = isset($_GET['hocKy']) ? intval($_GET['hocKy']) : 1;
        $namHoc = isset($_GET['namHoc']) ? trim($_GET['namHoc']) : '2024-2025';
        $maLop = isset($_GET['maLop']) ? intval($_GET['maLop']) : null;
        $thu = isset($_GET['thu']) ? intval($_GET['thu']) : null;

        $result = $this->model->getTeachingSchedule($maGV, $hocKy, $namHoc, $maLop, $thu);
        echo json_encode($result);
    }

    /**
     * API: Lấy phân công coi thi (JSON)
     */
    public function apiGetExamSupervision()
    {
        header('Content-Type: application/json');
        
        $accessCheck = $this->checkTeacherAccess();
        if (!$accessCheck['success']) {
            echo json_encode($accessCheck);
            return;
        }

        $maGV = $_SESSION['maGV'];
        $hocKy = isset($_GET['hocKy']) ? intval($_GET['hocKy']) : null;
        $namHoc = isset($_GET['namHoc']) ? trim($_GET['namHoc']) : null;
        $trangThai = isset($_GET['trangThai']) ? trim($_GET['trangThai']) : null;
        $loaiKyThi = isset($_GET['loaiKyThi']) ? trim($_GET['loaiKyThi']) : null;

        $result = $this->model->getExamSupervision($maGV, $hocKy, $namHoc, $trangThai, $loaiKyThi);
        echo json_encode($result);
    }

    /**
     * API: Lấy phân công chấm điểm (JSON)
     */
    public function apiGetGradingAssignment()
    {
        header('Content-Type: application/json');
        
        $accessCheck = $this->checkTeacherAccess();
        if (!$accessCheck['success']) {
            echo json_encode($accessCheck);
            return;
        }

        $maGV = $_SESSION['maGV'];
        $hocKy = isset($_GET['hocKy']) ? intval($_GET['hocKy']) : null;
        $namHoc = isset($_GET['namHoc']) ? trim($_GET['namHoc']) : null;
        $trangThai = isset($_GET['trangThai']) ? trim($_GET['trangThai']) : null;
        $loaiKiemTra = isset($_GET['loaiKiemTra']) ? trim($_GET['loaiKiemTra']) : null;

        $result = $this->model->getGradingAssignment($maGV, $hocKy, $namHoc, $trangThai, $loaiKiemTra);
        echo json_encode($result);
    }
}

// Xử lý request khi file được gọi trực tiếp
if (basename($_SERVER['PHP_SELF']) == 'cTeachingSchedule.php') {
    $controller = new cTeachingSchedule();
    $controller->handleRequest();
}
?>