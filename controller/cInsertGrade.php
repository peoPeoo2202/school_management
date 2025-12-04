<?php
session_start();
include_once(__DIR__ . "/../model/mInsertGrade.php");
include_once(__DIR__ . "/../model/mTeacher.php");

class cInsertGrade
{
    private $model;
    private $teacherModel;

    public function __construct()
    {
        $this->model = new mInsertGrade();
        $this->teacherModel = new mTeacher();
    }

    /**
     * Kiểm tra quyền truy cập của giáo viên
     */
    private function checkTeacherAccess()
    {
        if (!isset($_SESSION['maGV']) || !isset($_SESSION['loaiTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'giaovien') {
            header("Location: ../public/index.php");
            exit();
        }
    }

    /**
     * Hiển thị trang nhập điểm
     */
    public function showInsertGradePage()
    {
        $this->checkTeacherAccess();
        
        $maGV = $_SESSION['maGV'];
        
        // Lấy thông tin học kỳ hiện tại
        $currentSemester = $this->model->getCurrentSemester();
        
        // Tự động lấy môn học đầu tiên mà giáo viên dạy
        $subjects = $this->model->getSubjectsByTeacher($maGV);
        
        if (empty($subjects)) {
            $_SESSION['error'] = "Bạn chưa được phân công dạy môn học nào!";
            header("Location: dashboard.php");
            exit();
        }
        
        // Lấy môn học đầu tiên (hoặc môn duy nhất)
        $selectedSubject = $subjects[0]['maMonHoc'];
        $subjectName = $subjects[0]['tenMonHoc'];
        
        // Lấy danh sách năm học
        $years = $this->model->getAvailableYears();
        
        // Biến để lưu dữ liệu khi đã chọn
        $selectedClass = null;
        $selectedHocKy = $currentSemester['hocKy'];
        $selectedNamHoc = $currentSemester['namHoc'];
        $className = '';
        $students = [];
        
        // Lấy danh sách lớp của giáo viên dạy môn này
        $classes = $this->model->getClassesByTeacherAndSubject($maGV, $selectedSubject);
        
        // Lấy học kỳ và năm học từ GET nếu có
        if (isset($_GET['hocKy'])) {
            $selectedHocKy = intval($_GET['hocKy']);
        }
        if (isset($_GET['namHoc'])) {
            $selectedNamHoc = $_GET['namHoc'];
        }
        
        // Xử lý khi chọn lớp
        if (isset($_GET['maLop']) && !empty($_GET['maLop'])) {
            $selectedClass = intval($_GET['maLop']);
            
            // Kiểm tra quyền
            if (!$this->model->checkTeacherPermission($maGV, $selectedClass, $selectedSubject)) {
                $_SESSION['error'] = "Bạn không có quyền nhập điểm cho lớp này!";
                header("Location: cInsertGrade.php");
                exit();
            }
            
            // Lấy tên lớp
            foreach ($classes as $c) {
                if ($c['maLop'] == $selectedClass) {
                    $className = $c['tenLop'];
                    break;
                }
            }
            
            // Lấy danh sách học sinh và điểm
            $students = $this->model->getStudentsWithGrades($selectedClass, $selectedSubject, $selectedHocKy, $selectedNamHoc);
        }
        
        // Include view
        include_once(__DIR__ . "/../view/teacher/vInsertGrade.php");
    }

    /**
     * Xử lý lưu điểm
     */
    public function saveGrades()
    {
        $this->checkTeacherAccess();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=insertGrade");
            exit();
        }
        
        $maGV = $_SESSION['maGV'];
        $maMonHoc = isset($_POST['maMonHoc']) ? intval($_POST['maMonHoc']) : 0;
        $maLop = isset($_POST['maLop']) ? intval($_POST['maLop']) : 0;
        $hocKy = isset($_POST['hocKy']) ? intval($_POST['hocKy']) : 1;
        $namHoc = isset($_POST['namHoc']) ? $_POST['namHoc'] : '';
        
        // Validate dữ liệu cơ bản
        if (!$maMonHoc || !$maLop || !$namHoc) {
            $_SESSION['error'] = "Dữ liệu không hợp lệ!";
            header("Location: index.php?page=insertGrade");
            exit();
        }
        
        // Kiểm tra quyền
        if (!$this->model->checkTeacherPermission($maGV, $maLop, $maMonHoc)) {
            $_SESSION['error'] = "Bạn không có quyền nhập điểm cho lớp này!";
            header("Location: index.php?page=insertGrade");
            exit();
        }
        
        // Lấy dữ liệu điểm từ POST
        $gradesData = isset($_POST['grades']) ? $_POST['grades'] : [];
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        foreach ($gradesData as $maHS => $gradeArray) {
            // Validate từng cột điểm
            $validatedGrades = [];
            $hasError = false;
            $hasAnyGrade = false; // Kiểm tra xem có ít nhất 1 điểm được nhập không
            
            foreach (['diemTX1', 'diemTX2', 'diemTX3', 'diemTX4', 'diemGiuaKy', 'diemCuoiKy'] as $gradeType) {
                $value = isset($gradeArray[$gradeType]) ? trim($gradeArray[$gradeType]) : '';
                
                if ($value === '' || $value === null) {
                    $validatedGrades[$gradeType] = null;
                } else {
                    $hasAnyGrade = true; // Đánh dấu có ít nhất 1 điểm
                    
                    // Validate: phải là số, từ 0-10
                    if (!is_numeric($value)) {
                        $hasError = true;
                        $errors[] = "Học sinh #$maHS: $gradeType phải là số";
                        break;
                    }
                    
                    $numValue = floatval($value);
                    if ($numValue < 0 || $numValue > 10) {
                        $hasError = true;
                        $errors[] = "Học sinh #$maHS: $gradeType phải từ 0-10";
                        break;
                    }
                    
                    $validatedGrades[$gradeType] = $numValue;
                }
            }
            
            // Thêm nhận xét vào mảng grades
            $nhanXet = isset($gradeArray['nhanXet']) ? trim($gradeArray['nhanXet']) : '';
            $validatedGrades['nhanXet'] = !empty($nhanXet) ? $nhanXet : null;
            
            // Bỏ qua nếu có lỗi
            if ($hasError) {
                $errorCount++;
                continue;
            }
            
            // Bỏ qua nếu không có điểm nào được nhập
            if (!$hasAnyGrade) {
                continue;
            }
            
            // Lưu điểm
            $result = $this->model->saveGrades($maHS, $maMonHoc, $hocKy, $namHoc, $validatedGrades);
            
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Học sinh #$maHS: " . $result['message'];
            }
        }
        
        // Thông báo kết quả
        if ($successCount > 0) {
            $_SESSION['success'] = "Đã lưu điểm thành công!";
        }
        
        if ($errorCount > 0) {
            $_SESSION['error'] = "Có $errorCount lỗi khi lưu điểm: " . implode(", ", $errors);
        }
        
        // Redirect về trang nhập điểm với các tham số đã chọn
        header("Location: cInsertGrade.php?maLop=$maLop&hocKy=$hocKy&namHoc=$namHoc");
        exit();
    }

    /**
     * AJAX: Lấy danh sách lớp theo môn học
     */
    public function getClassesBySubject()
    {
        $this->checkTeacherAccess();
        
        header('Content-Type: application/json');
        
        $maGV = $_SESSION['maGV'];
        $maMonHoc = isset($_GET['maMonHoc']) ? intval($_GET['maMonHoc']) : 0;
        
        if (!$maMonHoc) {
            echo json_encode(['success' => false, 'message' => 'Mã môn học không hợp lệ']);
            exit();
        }
        
        $classes = $this->model->getClassesByTeacherAndSubject($maGV, $maMonHoc);
        echo json_encode(['success' => true, 'data' => $classes]);
        exit();
    }
}

// Xử lý request
if (isset($_GET['action'])) {
    $controller = new cInsertGrade();
    
    switch ($_GET['action']) {
        case 'save':
            $controller->saveGrades();
            break;
        case 'getClasses':
            $controller->getClassesBySubject();
            break;
        default:
            $controller->showInsertGradePage();
            break;
    }
} else {
    $controller = new cInsertGrade();
    $controller->showInsertGradePage();
}
?>