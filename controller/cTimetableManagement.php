<?php
/**
 * Controller: Timetable Management for Admin
 * Purpose: Quản lý thời khóa biểu (CRUD)
 * Permissions: Chỉ dành cho Quản trị viên
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../model/mTimetableAdmin.php');

class cTimetableManagement
{
    private $mTimetable;
    private $currentUser;

    public function __construct()
    {
        $this->mTimetable = new mTimetableAdmin();
        
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
                $this->listTimetables();
                break;
            case 'get':
                $this->getTimetable();
                break;
            case 'create':
                $this->createTimetable();
                break;
            case 'update':
                $this->updateTimetable();
                break;
            case 'delete':
                $this->deleteTimetable();
                break;
            case 'classes':
                $this->getClasses();
                break;
            case 'subjects':
                $this->getSubjects();
                break;
            case 'teachers':
                $this->getTeachers();
                break;
            case 'weekly':
                $this->getWeeklyTimetable();
                break;
            case 'available-rooms':
                $this->getAvailableRooms();
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Action không hợp lệ'], 400);
        }
    }

    /**
     * Danh sách thời khóa biểu
     */
    private function listTimetables()
    {
        try {
            $filters = [
                'maKhoi' => $_GET['maKhoi'] ?? '',
                'maLop' => $_GET['maLop'] ?? '',
                'maMonHoc' => $_GET['maMonHoc'] ?? '',
                'maGV' => $_GET['maGV'] ?? '',
                'hocKy' => $_GET['hocKy'] ?? '',
                'namHoc' => $_GET['namHoc'] ?? '',
                'thuNgay' => $_GET['thuNgay'] ?? '',
                'tuNgay' => $_GET['tuNgay'] ?? '',
                'denNgay' => $_GET['denNgay'] ?? ''
            ];

            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

            $timetables = $this->mTimetable->getAllTimetables($filters, $page, $limit);
            $total = $this->mTimetable->countTimetables($filters);

            $this->jsonResponse([
                'success' => true,
                'data' => $timetables,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_records' => $total,
                    'per_page' => $limit
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error in listTimetables: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Lỗi khi tải dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin 1 thời khóa biểu
     */
    private function getTimetable()
    {
        $maTKB = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maTKB <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $timetable = $this->mTimetable->getTimetableById($maTKB);

        if ($timetable) {
            $this->jsonResponse(['success' => true, 'data' => $timetable]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy thời khóa biểu'], 404);
        }
    }

    /**
     * Tạo thời khóa biểu mới
     */
    private function createTimetable()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
                return;
            }

            // Log received data for debugging
            error_log("createTimetable - Received POST data: " . print_r($_POST, true));

            // Validate required fields
            $required = ['maLop', 'maMonHoc', 'maGV', 'day_of_week', 'period', 'hocKy', 'namHoc'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    error_log("createTimetable - Missing field: $field");
                    $this->jsonResponse([
                        'success' => false,
                        'message' => "Thiếu trường bắt buộc: $field"
                    ], 400);
                    return;
                }
            }

            // Parse input
            $maLop = intval($_POST['maLop']);
            $maMonHoc = intval($_POST['maMonHoc']);
            $maGV = intval($_POST['maGV']);
            $dayOfWeek = intval($_POST['day_of_week']);
            $period = intval($_POST['period']);
            $hocKy = intval($_POST['hocKy']);
            $namHoc = trim($_POST['namHoc']);
            $phong = !empty($_POST['phong']) ? trim($_POST['phong']) : null;

            error_log("createTimetable - Parsed: maLop=$maLop, maMonHoc=$maMonHoc, maGV=$maGV, day=$dayOfWeek, period=$period, hocKy=$hocKy, namHoc=$namHoc, phong=$phong");

            // Validate time slot
            $validation = $this->mTimetable->validateTimeSlot($dayOfWeek, $period);
            if (!$validation['valid']) {
                error_log("createTimetable - Time slot validation failed: " . $validation['message']);
                $this->jsonResponse([
                    'success' => false,
                    'message' => $validation['message']
                ], 400);
                return;
            }

            // Check teaching assignment exists
            $assignmentExists = $this->mTimetable->checkTeachingAssignment($maLop, $maMonHoc, $maGV, $hocKy, $namHoc);
            error_log("createTimetable - Teaching assignment check: " . ($assignmentExists ? 'EXISTS' : 'NOT FOUND'));
            
            // TEMPORARY: Make this a soft warning instead of hard blocker
            // TODO: Re-enable strict validation after populating lichday table
            if (!$assignmentExists) {
                error_log("createTimetable - WARNING: No teaching assignment found, but allowing creation for testing");
                // Uncomment below to re-enable strict validation:
                // $this->jsonResponse([
                //     'success' => false,
                //     'message' => 'Không tìm thấy phân công giảng dạy (ASSIGNMENT_NOT_FOUND). Vui lòng kiểm tra lại lớp, môn, giáo viên và học kỳ.'
                // ], 400);
                // return;
            }

        // Check room status if provided
        if ($phong) {
            $roomStatus = $this->mTimetable->checkRoomStatus($phong);
            if ($roomStatus === 'MAINTENANCE') {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Phòng học đang bảo trì (ROOM_MAINTENANCE), không thể xếp lịch.'
                ], 400);
                return;
            }

            // Check room conflict
            if ($this->mTimetable->checkRoomConflict($phong, $dayOfWeek, $period, $hocKy, $namHoc)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Phòng học đã có lớp khác vào thời gian này (ROOM_CONFLICT)'
                ], 400);
                return;
            }
        }

        // Check teacher conflict
        if ($this->mTimetable->checkTeacherConflict($maGV, $dayOfWeek, $period, $hocKy, $namHoc)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Giáo viên đã có lịch dạy vào thời gian này (TEACHER_CONFLICT)'
            ], 400);
            return;
        }

        // Check class conflict
        if ($this->mTimetable->checkClassConflict($maLop, $dayOfWeek, $period, $hocKy, $namHoc)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Lớp đã có lịch học vào thời gian này (CLASS_SLOT_CONFLICT)'
            ], 400);
            return;
        }

        $data = [
            'maLop' => $maLop,
            'maMonHoc' => $maMonHoc,
            'maGV' => $maGV,
            'day_of_week' => $dayOfWeek,
            'period' => $period,
            'hocKy' => $hocKy,
            'namHoc' => $namHoc,
            'phong' => $phong,
            'thoiGianHoc' => !empty($_POST['thoiGianHoc']) ? $_POST['thoiGianHoc'] : null,
            // Keep old fields for compatibility
            'thuNgay' => !empty($_POST['thuNgay']) ? $_POST['thuNgay'] : null,
            'tietHoc' => !empty($_POST['tietHoc']) ? trim($_POST['tietHoc']) : "Tiết $period"
        ];

        $maTKB = $this->mTimetable->createTimetable($data);

        if ($maTKB) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Tạo thời khóa biểu thành công',
                'data' => ['maTKB' => $maTKB]
            ], 201);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tạo thời khóa biểu thất bại'
            ], 400);
        }
        
        } catch (Exception $e) {
            error_log("createTimetable - Exception: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thời khóa biểu
     */
    private function updateTimetable()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $maTKB = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maTKB <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $data = [];
        
        if (isset($_POST['maLop'])) $data['maLop'] = intval($_POST['maLop']);
        if (isset($_POST['maMonHoc'])) $data['maMonHoc'] = intval($_POST['maMonHoc']);
        if (isset($_POST['maGV'])) $data['maGV'] = intval($_POST['maGV']);
        if (isset($_POST['day_of_week'])) $data['day_of_week'] = intval($_POST['day_of_week']);
        if (isset($_POST['period'])) $data['period'] = intval($_POST['period']);
        if (isset($_POST['hocKy'])) $data['hocKy'] = intval($_POST['hocKy']);
        if (isset($_POST['namHoc'])) $data['namHoc'] = trim($_POST['namHoc']);
        if (isset($_POST['thoiGianHoc'])) $data['thoiGianHoc'] = $_POST['thoiGianHoc'];
        if (isset($_POST['phong'])) $data['phong'] = trim($_POST['phong']);
        
        // Keep old fields for compatibility
        if (isset($_POST['thuNgay'])) $data['thuNgay'] = $_POST['thuNgay'];
        if (isset($_POST['tietHoc'])) $data['tietHoc'] = trim($_POST['tietHoc']);

        // Get current values if not provided in POST
        $currentData = $this->mTimetable->getTimetableById($maTKB);
        if (!$currentData) {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy thời khóa biểu'], 404);
            return;
        }

        $dayOfWeek = $data['day_of_week'] ?? $currentData['day_of_week'];
        $period = $data['period'] ?? $currentData['period'];
        $hocKy = $data['hocKy'] ?? $currentData['hocKy'];
        $namHoc = $data['namHoc'] ?? $currentData['namHoc'];
        $maGV = $data['maGV'] ?? $currentData['maGV'];
        $maLop = $data['maLop'] ?? $currentData['maLop'];
        $phong = $data['phong'] ?? $currentData['phong'];

        // Check conflicts if schedule details changed
        if ($dayOfWeek && $period && $hocKy && $namHoc) {
            // Check room status if provided
            if ($phong) {
                $roomStatus = $this->mTimetable->checkRoomStatus($phong);
                if ($roomStatus === 'MAINTENANCE') {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Phòng học đang bảo trì (ROOM_MAINTENANCE)'
                    ], 400);
                    return;
                }

                // Check room conflict
                if ($this->mTimetable->checkRoomConflict($phong, $dayOfWeek, $period, $hocKy, $namHoc, $maTKB)) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Phòng học đã có lớp khác vào thời gian này (ROOM_CONFLICT)'
                    ], 400);
                    return;
                }
            }

            // Check teacher conflict
            if ($maGV && $this->mTimetable->checkTeacherConflict($maGV, $dayOfWeek, $period, $hocKy, $namHoc, $maTKB)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Giáo viên đã có lịch dạy vào thời gian này (TEACHER_CONFLICT)'
                ], 400);
                return;
            }

            // Check class conflict
            if ($maLop && $this->mTimetable->checkClassConflict($maLop, $dayOfWeek, $period, $hocKy, $namHoc, $maTKB)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Lớp đã có lịch học vào thời gian này (CLASS_SLOT_CONFLICT)'
                ], 400);
                return;
            }
        }

        $result = $this->mTimetable->updateTimetable($maTKB, $data);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cập nhật thời khóa biểu thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Cập nhật thời khóa biểu thất bại'
            ], 400);
        }
    }

    /**
     * Xóa thời khóa biểu
     */
    private function deleteTimetable()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $maTKB = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($maTKB <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            return;
        }

        $result = $this->mTimetable->deleteTimetable($maTKB);

        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Xóa thời khóa biểu thành công'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Xóa thời khóa biểu thất bại'
            ], 400);
        }
    }

    /**
     * Lấy danh sách lớp học
     */
    private function getClasses()
    {
        $classes = $this->mTimetable->getAllClasses();
        $this->jsonResponse(['success' => true, 'data' => $classes]);
    }

    /**
     * Lấy danh sách môn học
     */
    private function getSubjects()
    {
        $subjects = $this->mTimetable->getAllSubjects();
        $this->jsonResponse(['success' => true, 'data' => $subjects]);
    }

    /**
     * Lấy danh sách giáo viên
     */
    private function getTeachers()
    {
        $teachers = $this->mTimetable->getAllTeachers();
        $this->jsonResponse(['success' => true, 'data' => $teachers]);
    }

    /**
     * Lấy thời khóa biểu theo tuần
     */
    private function getWeeklyTimetable()
    {
        $maLop = isset($_GET['maLop']) ? intval($_GET['maLop']) : 0;
        $startDate = $_GET['startDate'] ?? date('Y-m-d');

        if ($maLop <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Mã lớp không hợp lệ'], 400);
            return;
        }

        $timetables = $this->mTimetable->getWeeklyTimetable($maLop, $startDate);
        $this->jsonResponse(['success' => true, 'data' => $timetables]);
    }

    /**
     * Lấy danh sách phòng học khả dụng
     */
    private function getAvailableRooms()
    {
        try {
            $dayOfWeek = isset($_GET['dayOfWeek']) ? intval($_GET['dayOfWeek']) : 0;
            $period = isset($_GET['period']) ? intval($_GET['period']) : 0;
            $hocKy = isset($_GET['hocKy']) ? intval($_GET['hocKy']) : 0;
            $namHoc = $_GET['namHoc'] ?? '';

            if ($dayOfWeek <= 0 || $period <= 0 || $hocKy <= 0 || empty($namHoc)) {
                $this->jsonResponse(['success' => false, 'message' => 'Thiếu thông tin bắt buộc'], 400);
                return;
            }

            $rooms = $this->mTimetable->getAvailableRooms($dayOfWeek, $period, $hocKy, $namHoc);
            $this->jsonResponse(['success' => true, 'data' => $rooms]);
        } catch (Exception $e) {
            error_log("Error in getAvailableRooms: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Lỗi khi tải danh sách phòng: ' . $e->getMessage()
            ], 500);
        }
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
    $controller = new cTimetableManagement();
    $controller->handleRequest();
}
?>
