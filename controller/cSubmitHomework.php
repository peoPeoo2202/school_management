<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../model/mSubmitHomework.php');

class cSubmitHomework {
    private $model;

    public function __construct() {
        $this->model = new mSubmitHomework();
    }

    public function getHomeworkDetails($maBaiTap) {
        return $this->model->getHomeworkById($maBaiTap);
    }

    public function getStudentSubmission($maBaiTap, $maHS) {
        return $this->model->getSubmissionByStudent($maBaiTap, $maHS);
    }

    public function submitHomework($maBaiTap, $maHS, $file, $noiDung) {
        // Đặt timezone chính xác
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        
        // Kiểm tra thời hạn và quyền nộp bài TRƯỚC KHI xử lý
        $homework = $this->model->getHomeworkById($maBaiTap);
        if (!$homework) {
            return ['success' => false, 'message' => 'Không tìm thấy bài tập'];
        }

        // Kiểm tra xem bài tập có bị khóa không
        if (isset($homework['khoaBai']) && $homework['khoaBai'] == 1) {
            return ['success' => false, 'message' => 'Bài tập này đã bị khóa. Học sinh không được phép nộp bài!'];
        }
        
        // Kiểm tra đã nộp bài chưa
        $existingSubmission = $this->model->getSubmissionByStudent($maBaiTap, $maHS);
        
        // Kiểm tra đã quá hạn chưa - So sánh DATETIME chính xác
        $deadline = strtotime($homework['thoiGianNop']);
        $currentTime = time();
        $isOverdue = $currentTime > $deadline;
        
        // Nếu quá hạn: kiểm tra có cho phép nộp trễ không
        if ($isOverdue) {
            // Nếu KHÔNG cho phép nộp trễ -> CHẶN
            if (!isset($homework['choPhepNopTre']) || $homework['choPhepNopTre'] != 1) {
                return [
                    'success' => false, 
                    'message' => 'Bài tập đã hết hạn nộp (Hạn: ' . date('d/m/Y H:i', $deadline) . '). Bài tập này không cho phép nộp trễ!'
                ];
            }
            // Nếu cho phép nộp trễ -> CHO PHÉP tiếp tục (không return)
        }
        
        // Xử lý upload file
        $tenFile = null;
        $duongDan = null;

        if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/submissions/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $tenFile = time() . '_' . uniqid() . '_' . basename($file['name']);
            $duongDan = 'uploads/submissions/' . $tenFile;

            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $tenFile)) {
                return ['success' => false, 'message' => 'Không thể upload file'];
            }
        }

        // Lưu bài nộp
        $result = $this->model->submitHomework($maBaiTap, $maHS, $tenFile, $duongDan, $noiDung);

        if ($result) {
            $statusMessage = $isOverdue ? 'Nộp bài thành công (Nộp trễ)' : 'Nộp bài thành công';
            return ['success' => true, 'message' => $statusMessage];
        } else {
            return ['success' => false, 'message' => 'Có lỗi khi nộp bài'];
        }
    }

    // Lấy tất cả môn học của học sinh (bao gồm cả môn chưa có bài tập)
    public function getAllSubjectsForStudent($maHS) {
        return $this->model->getSubjectsForStudent($maHS);
    }

    public function getAllHomeworkForStudent($maHS, $maMonHoc = null) {
        return $this->model->getHomeworkForStudent($maHS, $maMonHoc);
    }
}
?>
