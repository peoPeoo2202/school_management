<?php
/**
 * Controller: cTeachingAssignment.php
 * Xử lý các yêu cầu phân công giảng dạy
 */

session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../public/index.php");
    exit();
}

// Kiểm tra quyền truy cập - Chỉ Ban giám hiệu
if ($_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    header("Location: ../public/index.php?error=access_denied");
    exit();
}

require_once(__DIR__ . '/../model/mTeachingAssignment.php');

$model = new mTeachingAssignment();
$action = $_GET['action'] ?? $_POST['action'] ?? 'index';

// Lấy thông tin người dùng - cần lấy maBGH từ maTaiKhoan
$maTaiKhoan = $_SESSION['maTaiKhoan'] ?? null;
$nguoiPhanCong = $model->getBGHByTaiKhoan($maTaiKhoan);

switch ($action) {
    // ==================== TRANG CHÍNH ====================
    case 'index':
        header("Location: ../view/bgh/vTeachingAssignment.php");
        exit();

    // ==================== PHÂN CÔNG HỌC SINH ĐẦU CẤP ====================
    case 'studentAssignment':
        $schoolYears = $model->getSchoolYears();
        // Mặc định lấy năm học đầu tiên có dữ liệu
        $namHoc = $_GET['namHoc'] ?? (!empty($schoolYears) ? $schoolYears[0] : $model->getCurrentSchoolYear());
        $showOnlyWithoutClass = isset($_GET['showOnlyWithoutClass']);
        $allStudents = $model->getAllStudents($showOnlyWithoutClass);
        $studentsWithoutClass = $model->getStudentsWithoutClass();
        $firstYearClasses = $model->getFirstYearClasses($namHoc);
        $emptyClasses = $model->getEmptyClasses(null, $namHoc);
        $allClasses = $model->getAllClasses($namHoc);
        include('../view/bgh/vStudentAssignment.php');
        break;

    case 'assignStudents':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentIds = $_POST['studentIds'] ?? [];
            $maLop = $_POST['maLop'] ?? null;
            $namHoc = $_POST['namHoc'] ?? $model->getCurrentSchoolYear();
            $loaiPhanCong = $_POST['loaiPhanCong'] ?? 'daucap';

            if (empty($studentIds) || !$maLop) {
                $_SESSION['error'] = 'Vui lòng chọn học sinh và lớp!';
                header("Location: cTeachingAssignment.php?action=studentAssignment");
                exit();
            }

            $result = $model->assignStudentsToClass($studentIds, $maLop, $namHoc, $nguoiPhanCong, $loaiPhanCong);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header("Location: cTeachingAssignment.php?action=studentAssignment");
            exit();
        }
        break;

    case 'autoAssignStudents':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $options = [
                'chia_deu_theo_lop' => isset($_POST['chia_deu_theo_lop']),
                'chia_deu_nam_nu' => isset($_POST['chia_deu_nam_nu']),
                'so_luong_hs_lop' => $_POST['so_luong_hs_lop'] ?? 0
            ];
            $namHoc = $_POST['namHoc'] ?? $model->getCurrentSchoolYear();

            $result = $model->autoAssignStudentsToClasses($options, $namHoc, $nguoiPhanCong);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header("Location: cTeachingAssignment.php?action=studentAssignment");
            exit();
        }
        break;

    // ==================== PHÂN CÔNG PHÒNG HỌC ====================
    case 'roomAssignment':
        $schoolYears = $model->getSchoolYears();
        // Mặc định lấy năm học đầu tiên có dữ liệu, nếu không có thì lấy năm hiện tại
        $namHoc = $_GET['namHoc'] ?? (!empty($schoolYears) ? $schoolYears[0] : $model->getCurrentSchoolYear());
        $showOnlyWithoutRoom = isset($_GET['showOnlyWithoutRoom']);
        $classesWithRoomInfo = $model->getClassesWithRoomInfo($namHoc, $showOnlyWithoutRoom);
        $classesWithoutRoom = $model->getClassesWithoutRoom($namHoc);
        $allRooms = $model->getAllRooms();
        $availableRooms = $model->getAvailableRooms($namHoc);
        $allClasses = $model->getAllClasses($namHoc);
        include('../view/bgh/vRoomAssignment.php');
        break;

    case 'assignRoom':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maLop = $_POST['maLop'] ?? null;
            $maPhong = $_POST['maPhong'] ?? null;
            $namHoc = $_POST['namHoc'] ?? $model->getCurrentSchoolYear();
            
            if (!$maLop || !$maPhong) {
                $_SESSION['error'] = 'Vui lòng chọn lớp và phòng!';
            } else {
                $result = $model->assignRoomToClass($maLop, $maPhong, $namHoc, $nguoiPhanCong);
                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                } else {
                    $_SESSION['error'] = $result['message'];
                }
            }
            header("Location: cTeachingAssignment.php?action=roomAssignment&namHoc=" . urlencode($namHoc));
            exit();
        }
        break;

    case 'revokeRoomAssignment':
        $maLop = $_GET['maLop'] ?? null;
        $namHoc = $_GET['namHoc'] ?? $model->getCurrentSchoolYear();
        
        if ($maLop) {
            $result = $model->revokeRoomAssignment($maLop, $namHoc);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        header("Location: cTeachingAssignment.php?action=roomAssignment&namHoc=" . urlencode($namHoc));
        exit();

    case 'revokeStudentClass':
        $maHS = $_GET['maHS'] ?? null;
        
        if ($maHS) {
            $result = $model->revokeStudentClass($maHS);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        header("Location: cTeachingAssignment.php?action=studentAssignment");
        exit();

    // ==================== PHÂN CÔNG GIÁO VIÊN BỘ MÔN ====================
    case 'subjectTeacherAssignment':
        $schoolYears = $model->getSchoolYears();
        // Năm học mặc định: lấy từ GET hoặc năm đầu tiên trong danh sách (năm gần nhất có data)
        $namHoc = $_GET['namHoc'] ?? (!empty($schoolYears) ? $schoolYears[0] : $model->getCurrentSchoolYear());
        $grades = $model->getAllGrades();
        $subjects = $model->getAllSubjects();
        $teachers = $model->getAllTeachers();
        
        // Lấy các tham số tìm kiếm
        $filters = [
            'namHoc' => $namHoc,
            'maKhoi' => $_GET['maKhoi'] ?? null,
            'maLop' => $_GET['maLop'] ?? null,
            'maMonHoc' => $_GET['maMonHoc'] ?? null,
            'hocKy' => $_GET['hocKy'] ?? null
        ];
        
        // Luôn lấy assignments (mặc định hiển thị tất cả theo năm học)
        $assignments = $model->getSubjectTeacherAssignments($filters);
        
        // Lấy danh sách lớp theo khối nếu có chọn khối, nếu không lấy tất cả
        $classes = [];
        if (!empty($filters['maKhoi'])) {
            $classes = $model->getClassesByGrade($filters['maKhoi'], $namHoc);
        } else {
            $classes = $model->getAllClasses($namHoc);
        }
        
        include('../view/bgh/vSubjectTeacherAssignment.php');
        break;

    case 'getClassesByGrade':
        // AJAX endpoint
        header('Content-Type: application/json');
        $maKhoi = $_GET['maKhoi'] ?? null;
        $namHoc = $_GET['namHoc'] ?? $model->getCurrentSchoolYear();
        
        if ($maKhoi) {
            $classes = $model->getClassesByGrade($maKhoi, $namHoc);
            echo json_encode(['success' => true, 'data' => $classes]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chưa chọn khối']);
        }
        exit();

    case 'getTeachersBySubject':
        // AJAX endpoint - Lấy giáo viên theo môn học
        header('Content-Type: application/json');
        $maMonHoc = $_GET['maMonHoc'] ?? null;
        
        if ($maMonHoc) {
            $teachers = $model->getTeachersByMonHoc($maMonHoc);
            echo json_encode(['success' => true, 'data' => $teachers]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chưa chọn môn học']);
        }
        exit();

    case 'getUnassignedSubjects':
        // AJAX endpoint - Lấy môn học chưa được phân công cho lớp
        header('Content-Type: application/json');
        $maLop = $_GET['maLop'] ?? null;
        $namHoc = $_GET['namHoc'] ?? $model->getCurrentSchoolYear();
        $hocKy = $_GET['hocKy'] ?? null;
        
        if ($maLop) {
            $subjects = $model->getUnassignedSubjectsForClass($maLop, $namHoc, $hocKy);
            $gvcnInfo = $model->getHomeroomTeacherInfo($maLop, $namHoc);
            echo json_encode([
                'success' => true, 
                'data' => $subjects,
                'gvcn' => $gvcnInfo
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chưa chọn lớp']);
        }
        exit();

    case 'assignSubjectTeacher':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'maGV' => $_POST['maGV'] ?? null,
                'maLop' => $_POST['maLop'] ?? null,
                'maMonHoc' => $_POST['maMonHoc'] ?? null,
                'hocKy' => $_POST['hocKy'] ?? 1,
                'namHoc' => $_POST['namHoc'] ?? $model->getCurrentSchoolYear(),
                'apDungHaiKy' => isset($_POST['apDungHaiKy']),
                'nguoiPhanCong' => $nguoiPhanCong
            ];

            if (!$data['maGV'] || !$data['maLop'] || !$data['maMonHoc']) {
                $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin!';
                header("Location: cTeachingAssignment.php?action=subjectTeacherAssignment");
                exit();
            }

            $result = $model->assignSubjectTeacher($data);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
            // Redirect với các tham số tìm kiếm hiện tại
            $redirectUrl = "cTeachingAssignment.php?action=subjectTeacherAssignment&search=1";
            $redirectUrl .= "&namHoc=" . urlencode($data['namHoc']);
            if (!empty($_POST['maKhoi'])) $redirectUrl .= "&maKhoi=" . $_POST['maKhoi'];
            if (!empty($_POST['maMonHoc'])) $redirectUrl .= "&maMonHoc=" . $_POST['maMonHoc'];
            
            header("Location: " . $redirectUrl);
            exit();
        }
        break;

    case 'editSubjectTeacher':
        $maPhanCong = $_GET['id'] ?? null;
        if ($maPhanCong) {
            $assignment = $model->getSubjectTeacherAssignmentDetail($maPhanCong);
            $teachers = $model->getAllTeachers();
            include('../view/bgh/vEditSubjectTeacher.php');
        } else {
            header("Location: cTeachingAssignment.php?action=subjectTeacherAssignment");
        }
        break;

    case 'updateSubjectTeacher':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maPhanCong = $_POST['maPhanCong'] ?? null;
            $maGV = $_POST['maGV'] ?? null;

            if (!$maPhanCong || !$maGV) {
                $_SESSION['error'] = 'Dữ liệu không hợp lệ!';
                header("Location: cTeachingAssignment.php?action=subjectTeacherAssignment");
                exit();
            }

            $result = $model->updateSubjectTeacherAssignment($maPhanCong, $maGV, $nguoiPhanCong);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
            // Giữ lại các tham số bộ lọc
            $redirectUrl = "cTeachingAssignment.php?action=subjectTeacherAssignment&search=1";
            if (!empty($_POST['namHoc'])) $redirectUrl .= "&namHoc=" . urlencode($_POST['namHoc']);
            if (!empty($_POST['returnMaKhoi'])) $redirectUrl .= "&maKhoi=" . $_POST['returnMaKhoi'];
            if (!empty($_POST['returnMaLop'])) $redirectUrl .= "&maLop=" . $_POST['returnMaLop'];
            if (!empty($_POST['returnMaMonHoc'])) $redirectUrl .= "&maMonHoc=" . $_POST['returnMaMonHoc'];
            if (!empty($_POST['returnHocKy'])) $redirectUrl .= "&hocKy=" . $_POST['returnHocKy'];
            
            header("Location: " . $redirectUrl);
            exit();
        }
        break;

    case 'deleteSubjectTeacher':
        $maPhanCong = $_GET['id'] ?? null;
        if ($maPhanCong && $_SERVER['REQUEST_METHOD'] === 'GET') {
            // Hiển thị trang xác nhận hoặc xóa trực tiếp nếu có confirm
            if (isset($_GET['confirm']) && $_GET['confirm'] == '1') {
                $result = $model->deleteSubjectTeacherAssignment($maPhanCong);
                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                } else {
                    $_SESSION['error'] = $result['message'];
                }
            }
        }
        
        // Giữ lại các tham số bộ lọc
        $redirectUrl = "cTeachingAssignment.php?action=subjectTeacherAssignment&search=1";
        if (!empty($_GET['namHoc'])) $redirectUrl .= "&namHoc=" . urlencode($_GET['namHoc']);
        if (!empty($_GET['maKhoi'])) $redirectUrl .= "&maKhoi=" . $_GET['maKhoi'];
        if (!empty($_GET['maLop'])) $redirectUrl .= "&maLop=" . $_GET['maLop'];
        if (!empty($_GET['maMonHoc'])) $redirectUrl .= "&maMonHoc=" . $_GET['maMonHoc'];
        if (!empty($_GET['hocKy'])) $redirectUrl .= "&hocKy=" . $_GET['hocKy'];
        
        header("Location: " . $redirectUrl);
        exit();

    // ==================== PHÂN CÔNG GIÁO VIÊN CHỦ NHIỆM ====================
    case 'homeroomTeacherAssignment':
        $schoolYears = $model->getSchoolYears();
        // Năm học mặc định: lấy từ GET hoặc năm đầu tiên trong danh sách
        $namHoc = $_GET['namHoc'] ?? (!empty($schoolYears) ? $schoolYears[0] : $model->getCurrentSchoolYear());
        $maKhoi = $_GET['maKhoi'] ?? null;
        $showAvailable = isset($_GET['showAvailable']);
        
        $grades = $model->getAllGrades();
        $assignments = $model->getHomeroomTeacherAssignments($maKhoi, $namHoc);
        
        if ($showAvailable) {
            $teachers = $model->getAvailableHomeroomTeachers($namHoc);
        } else {
            $teachers = $model->getAllTeachers();
        }
        
        include('../view/bgh/vHomeroomTeacherAssignment.php');
        break;

    case 'assignHomeroomTeacher':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maLop = $_POST['maLop'] ?? null;
            $maGV = $_POST['maGV'] ?? null;
            $namHoc = $_POST['namHoc'] ?? $model->getCurrentSchoolYear();

            if (!$maLop || !$maGV) {
                $_SESSION['error'] = 'Vui lòng chọn lớp và giáo viên!';
                header("Location: cTeachingAssignment.php?action=homeroomTeacherAssignment");
                exit();
            }

            $result = $model->assignHomeroomTeacher($maLop, $maGV, $namHoc, $nguoiPhanCong);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
            $redirectUrl = "cTeachingAssignment.php?action=homeroomTeacherAssignment&namHoc=" . urlencode($namHoc);
            if (!empty($_POST['maKhoi'])) $redirectUrl .= "&maKhoi=" . $_POST['maKhoi'];
            if (!empty($_POST['showAvailable'])) $redirectUrl .= "&showAvailable=1";
            
            header("Location: " . $redirectUrl);
            exit();
        }
        break;

    case 'revokeHomeroomTeacher':
        $maLop = $_GET['maLop'] ?? null;
        $namHoc = $_GET['namHoc'] ?? $model->getCurrentSchoolYear();
        $maKhoi = $_GET['maKhoi'] ?? null;
        
        if ($maLop) {
            $result = $model->revokeHomeroomTeacher($maLop, $namHoc);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        } else {
            $_SESSION['error'] = 'Thiếu thông tin lớp học!';
        }
        
        $redirectUrl = "cTeachingAssignment.php?action=homeroomTeacherAssignment&namHoc=" . urlencode($namHoc);
        if ($maKhoi) $redirectUrl .= "&maKhoi=" . $maKhoi;
        
        header("Location: " . $redirectUrl);
        exit();

    default:
        header("Location: ../view/bgh/vTeachingAssignment.php");
        exit();
}
?>
