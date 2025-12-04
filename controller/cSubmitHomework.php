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
            return ['success' => true, 'message' => 'Nộp bài thành công'];
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
