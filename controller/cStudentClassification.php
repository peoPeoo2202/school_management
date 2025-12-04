<?php
session_start();

// Bật hiển thị lỗi để debug (tắt khi production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Tắt hiển thị lỗi ra màn hình
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

require_once(__DIR__ . '/../model/mStudentClassification.php');

class ControllerStudentClassification {
    private $model;
    
    public function __construct($connection) {
        $this->model = new ModelStudentClassification($connection);
    }
    
    /**
     * Kiểm tra quyền truy cập
     */
    private function checkTeacherAccess() {
        if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] != 'giaovien') {
            header('Location: ../index.php');
            exit();
        }
        return $_SESSION['maGV'] ?? null;
    }
    
    /**
     * Hiển thị trang xếp loại
     */
    public function showClassification() {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['error' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        // Lấy danh sách lớp chủ nhiệm
        $classes = $this->model->getClassesByTeacher($maGV);
        
        if (empty($classes)) {
            return ['error' => 'Bạn chưa được phân công làm giáo viên chủ nhiệm lớp nào'];
        }
        
        // Lấy lớp đầu tiên (lớp chủ nhiệm)
        $maLop = $classes[0]['maLop'];
        $classInfo = $this->model->getClassInfo($maLop);
        
        // Lấy thông tin học kỳ
        $currentYear = date('Y');
        $namHoc = ($currentYear - 1) . '-' . $currentYear;
        $hocKy = (date('m') <= 6) ? 2 : 1;
        
        return [
            'classInfo' => $classInfo,
            'currentClassId' => $maLop,
            'hocKy' => $hocKy,
            'namHoc' => $namHoc
        ];
    }
    
    /**
     * Lấy dữ liệu theo loại xếp loại (cập nhật để bao gồm danh hiệu)
     */
    public function getClassificationData($maLop, $hocKy, $namHoc, $type = 'overview') {
        try {
            $maGV = $this->checkTeacherAccess();
            
            if (!$maGV) {
                return ['error' => 'Không tìm thấy thông tin giáo viên'];
            }
            
            $classInfo = $this->model->getClassInfo($maLop);
            if (!$classInfo || $classInfo['maGV'] != $maGV) {
                return ['error' => 'Bạn không có quyền truy cập'];
            }
            
            if ($type === 'academic') {
                // Lấy bảng điểm chi tiết
                $students = $this->model->getStudentGradesDetail($maLop, $hocKy, $namHoc);
                
                // Tính xếp loại cho từng học sinh
                foreach ($students as &$student) {
                    $ranking = $this->model->calculateAcademicRanking($student['maHS'], $hocKy, $namHoc);
                    $student['ranking'] = $ranking;
                }
                
                return ['students' => $students];
            } elseif ($type === 'conduct') {
                // Lấy bảng hạnh kiểm chi tiết
                $students = $this->model->getStudentConductDetail($maLop, $hocKy, $namHoc);
                
                // Tính xếp loại hạnh kiểm cho từng học sinh
                foreach ($students as &$student) {
                    $ranking = $this->model->calculateConductRanking($student['maHS'], $hocKy, $namHoc);
                    $student['conductRanking'] = $ranking;
                }
                
                return ['students' => $students];
            } elseif ($type === 'title') {
                // Lấy thông tin danh hiệu
                $students = $this->model->getStudentTitles($maLop, $hocKy, $namHoc);
                
                // Tính toán danh hiệu cho từng học sinh
                foreach ($students as &$student) {
                    $titleData = $this->model->calculateTitleRanking($student['maHS'], $hocKy, $namHoc);
                    $student['calculatedTitle'] = $titleData;
                }
                
                return ['students' => $students];
            } else {
                // Overview
                $students = $this->model->getStudentsWithClassification($maLop, $hocKy, $namHoc);
                
                // Lấy danh hiệu đã xếp loại từ bảng hocsinh_danhhieu
                foreach ($students as &$student) {
                    $titleSql = "SELECT dh.tenDanhHieu, hsd.ghiChu 
                                FROM hocsinh_danhhieu hsd
                                JOIN danhhieu dh ON hsd.maDanhHieu = dh.maDanhHieu
                                WHERE hsd.maHS = ? AND hsd.hocKy = ? AND hsd.namHoc = ?";
                    $stmt = $this->model->conn->prepare($titleSql);
                    $stmt->bind_param("iis", $student['maHS'], $hocKy, $namHoc);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $titleData = $result->fetch_assoc();
                    
                    $student['danhHieu'] = $titleData ? $titleData['tenDanhHieu'] : null;
                    $student['ghiChuDanhHieu'] = $titleData ? $titleData['ghiChu'] : null;
                }
                
                return ['students' => $students];
            }
        } catch (Exception $e) {
            // Log lỗi
            error_log("Error in getClassificationData: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return ['error' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }
    
    /**
     * Lưu xếp loại học lực
     */
    public function saveClassifications($maLop, $hocKy, $namHoc) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        $classInfo = $this->model->getClassInfo($maLop);
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            return ['success' => false, 'message' => 'Bạn không có quyền xếp loại'];
        }
        
        $result = $this->model->saveAllClassifications($maLop, $hocKy, $namHoc);
        
        if ($result['success'] > 0) {
            $message = "Đã xếp loại thành công {$result['success']} học sinh";
            if ($result['skipped'] > 0) {
                $message .= ". Bỏ qua {$result['skipped']} học sinh chưa đủ điểm";
            }
            if ($result['failed'] > 0) {
                $message .= " ({$result['failed']} học sinh lỗi)";
            }
            return ['success' => true, 'message' => $message];
        } else {
            $message = 'Không có học sinh nào được xếp loại';
            if ($result['skipped'] > 0) {
                $message .= ". {$result['skipped']} học sinh chưa đủ điểm để xếp loại";
            }
            return ['success' => false, 'message' => $message];
        }
    }
    
    /**
     * Lưu xếp loại hạnh kiểm
     */
    public function saveConductClassifications($maLop, $hocKy, $namHoc) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        $classInfo = $this->model->getClassInfo($maLop);
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            return ['success' => false, 'message' => 'Bạn không có quyền xếp loại'];
        }
        
        $result = $this->model->saveAllConductClassifications($maLop, $hocKy, $namHoc);
        
        if ($result['success'] > 0) {
            return [
                'success' => true,
                'message' => "Đã xếp loại hạnh kiểm thành công cho {$result['success']} học sinh" .
                            ($result['failed'] > 0 ? " ({$result['failed']} học sinh lỗi)" : "")
            ];
        } else {
            return ['success' => false, 'message' => 'Không có học sinh nào được xếp loại hạnh kiểm'];
        }
    }
    
    /**
     * Lưu xếp loại hạnh kiểm thủ công
     */
    public function saveConductManual($data) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        $maLop = intval($data['maLop'] ?? 0);
        $hocKy = intval($data['hocKy'] ?? 1);
        $namHoc = $data['namHoc'] ?? '';
        $conductData = json_decode($data['conductData'] ?? '[]', true);
        
        if (empty($conductData)) {
            return ['success' => false, 'message' => 'Không có dữ liệu để lưu'];
        }
        
        // Kiểm tra quyền
        $classInfo = $this->model->getClassInfo($maLop);
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            return ['success' => false, 'message' => 'Bạn không có quyền xếp loại'];
        }
        
        $success = 0;
        $failed = 0;
        
        foreach ($conductData as $item) {
            $maHS = intval($item['maHS'] ?? 0);
            $loaiHK = $item['loaiHK'] ?? '';
            
            // Validate loại hạnh kiểm
            if (!in_array($loaiHK, ['Tốt', 'Tot', 'Khá', 'Kha', 'Đạt', 'Dat', 'Chưa đạt'])) {
                $failed++;
                continue;
            }
            
            // Chuẩn hóa loại hạnh kiểm
            $loaiHK = $this->normalizeConduct($loaiHK);
            
            if ($this->model->saveConductRankingManual($maHS, $namHoc, $hocKy, $loaiHK)) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        if ($success > 0) {
            return [
                'success' => true,
                'message' => "Đã lưu xếp loại hạnh kiểm cho {$success} học sinh" .
                            ($failed > 0 ? " ({$failed} học sinh lỗi)" : "")
            ];
        } else {
            return ['success' => false, 'message' => 'Không thể lưu xếp loại hạnh kiểm'];
        }
    }
    
    /**
     * Chuẩn hóa loại hạnh kiểm
     */
    private function normalizeConduct($loaiHK) {
        $mapping = [
            'Tốt' => 'Tốt',
            'Tot' => 'Tốt',
            'Khá' => 'Khá',
            'Kha' => 'Khá',
            'Đạt' => 'Đạt',
            'Dat' => 'Đạt',
            'Chưa đạt' => 'Chưa đạt',
            'Chua dat' => 'Chưa đạt'
        ];
        
        return $mapping[$loaiHK] ?? $loaiHK;
    }
    
    /**
     * Lưu cấu hình tiêu chí xếp loại hạnh kiểm
     */
    public function saveConductCriteriaConfig($data) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        $criteriaData = json_decode($data['criteria'] ?? '{}', true);
        
        if (empty($criteriaData)) {
            return ['success' => false, 'message' => 'Dữ liệu tiêu chí không hợp lệ'];
        }
        
        // Validate dữ liệu
        $validRankings = ['Tốt', 'Khá', 'Đạt', 'Chưa đạt'];
        foreach ($criteriaData as $ranking => $rules) {
            if (!in_array($ranking, $validRankings)) {
                return ['success' => false, 'message' => 'Loại hạnh kiểm không hợp lệ'];
            }
        }
        
        if ($this->model->saveConductCriteria($maGV, $criteriaData)) {
            return ['success' => true, 'message' => 'Đã lưu cấu hình tiêu chí thành công'];
        } else {
            return ['success' => false, 'message' => 'Lỗi khi lưu cấu hình'];
        }
    }
    
    /**
     * Lấy cấu hình tiêu chí xếp loại hạnh kiểm
     */
    public function getConductCriteriaConfig() {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        $criteria = $this->model->getConductCriteria($maGV);
        return ['success' => true, 'criteria' => $criteria];
    }
    
    /**
     * Xếp loại hạnh kiểm tự động theo tiêu chí
     */
    public function autoClassifyConduct($maLop, $hocKy, $namHoc) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        // Kiểm tra quyền
        $classInfo = $this->model->getClassInfo($maLop);
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            return ['success' => false, 'message' => 'Bạn không có quyền xếp loại'];
        }
        
        $result = $this->model->autoClassifyAllConduct($maLop, $hocKy, $namHoc, $maGV);
        
        if ($result['success'] > 0) {
            return [
                'success' => true,
                'message' => "Đã xếp loại tự động thành công cho {$result['success']} học sinh" .
                            ($result['failed'] > 0 ? " ({$result['failed']} học sinh lỗi)" : "")
            ];
        } else {
            return ['success' => false, 'message' => 'Không thể xếp loại tự động'];
        }
    }
    
    /**
     * Xếp loại danh hiệu cho tất cả học sinh
     */
    public function classifyTitles($maLop, $hocKy, $namHoc) {
        $maGV = $this->checkTeacherAccess();
        
        if (!$maGV) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin giáo viên'];
        }
        
        $classInfo = $this->model->getClassInfo($maLop);
        if (!$classInfo || $classInfo['maGV'] != $maGV) {
            return ['success' => false, 'message' => 'Bạn không có quyền xếp loại'];
        }
        
        $result = $this->model->classifyAllTitles($maLop, $hocKy, $namHoc);
        
        if ($result['success'] > 0) {
            $message = "Đã xếp danh hiệu thành công cho {$result['success']} học sinh";
            if ($result['noTitle'] > 0) {
                $message .= ". {$result['noTitle']} học sinh chưa đủ điều kiện";
            }
            if ($result['failed'] > 0) {
                $message .= " ({$result['failed']} lỗi)";
            }
            return ['success' => true, 'message' => $message];
        } else {
            return [
                'success' => false, 
                'message' => "Không có học sinh nào được xếp danh hiệu. " .
                            "{$result['noTitle']} học sinh chưa đủ điều kiện"
            ];
        }
    }
}

// Xử lý request
require_once(__DIR__ . '/../config.php');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        throw new Exception("Kết nối thất bại: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    $controller = new ControllerStudentClassification($conn);

    // Xử lý AJAX request
    if (isset($_GET['action']) && $_GET['action'] == 'getData' && isset($_GET['type'])) {
        header('Content-Type: application/json; charset=utf-8');
        
        $maLop = intval($_GET['maLop'] ?? 0);
        $hocKy = intval($_GET['hocKy'] ?? 1);
        $namHoc = $_GET['namHoc'] ?? '';
        $type = $_GET['type'];
        
        $result = $controller->getClassificationData($maLop, $hocKy, $namHoc, $type);
        
        // Đảm bảo output sạch
        ob_clean();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Xử lý lưu xếp loại
    if (isset($_POST['action']) && $_POST['action'] == 'save') {
        header('Content-Type: application/json; charset=utf-8');
        $maLop = intval($_POST['maLop'] ?? 0);
        $hocKy = intval($_POST['hocKy'] ?? 1);
        $namHoc = $_POST['namHoc'] ?? '';
        
        $result = $controller->saveClassifications($maLop, $hocKy, $namHoc);
        
        ob_clean();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Xử lý lưu xếp loại hạnh kiểm
    if (isset($_POST['action']) && $_POST['action'] == 'saveConduct') {
        header('Content-Type: application/json');
        $maLop = intval($_POST['maLop'] ?? 0);
        $hocKy = intval($_POST['hocKy'] ?? 1);
        $namHoc = $_POST['namHoc'] ?? '';
        
        $result = $controller->saveConductClassifications($maLop, $hocKy, $namHoc);
        echo json_encode($result);
        exit();
    }

    // Xử lý lưu xếp loại hạnh kiểm thủ công
    if (isset($_POST['action']) && $_POST['action'] == 'saveConductManual') {
        header('Content-Type: application/json');
        $result = $controller->saveConductManual($_POST);
        echo json_encode($result);
        exit();
    }

    // Xử lý lưu cấu hình tiêu chí
    if (isset($_POST['action']) && $_POST['action'] == 'saveCriteria') {
        header('Content-Type: application/json');
        $result = $controller->saveConductCriteriaConfig($_POST);
        echo json_encode($result);
        exit();
    }

    // Xử lý lấy cấu hình tiêu chí
    if (isset($_GET['action']) && $_GET['action'] == 'getCriteria') {
        header('Content-Type: application/json');
        $result = $controller->getConductCriteriaConfig();
        echo json_encode($result);
        exit();
    }

    // Xử lý xếp loại tự động
    if (isset($_POST['action']) && $_POST['action'] == 'autoClassify') {
        header('Content-Type: application/json');
        $maLop = intval($_POST['maLop'] ?? 0);
        $hocKy = intval($_POST['hocKy'] ?? 1);
        $namHoc = $_POST['namHoc'] ?? '';
        
        $result = $controller->autoClassifyConduct($maLop, $hocKy, $namHoc);
        echo json_encode($result);
        exit();
    }

    // Xử lý xếp loại danh hiệu
    if (isset($_POST['action']) && $_POST['action'] == 'classifyTitles') {
        header('Content-Type: application/json; charset=utf-8');
        $maLop = intval($_POST['maLop'] ?? 0);
        $hocKy = intval($_POST['hocKy'] ?? 1);
        $namHoc = $_POST['namHoc'] ?? '';
        
        $result = $controller->classifyTitles($maLop, $hocKy, $namHoc);
        
        ob_clean();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Hiển thị trang
    $data = $controller->showClassification();
    include(__DIR__ . '/../view/teacher/vStudentClassification.php');

    $conn->close();
} catch (Exception $e) {
    // Nếu là AJAX request, trả về JSON error
    if (isset($_GET['action']) || isset($_POST['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();
        echo json_encode(['error' => 'Lỗi hệ thống: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } else {
        // Nếu không phải AJAX, hiển thị trang lỗi
        echo "Lỗi hệ thống: " . htmlspecialchars($e->getMessage());
    }
    exit();
}
?>
