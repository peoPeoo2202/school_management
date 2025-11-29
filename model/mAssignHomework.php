<?php
class mAssignHomework {
    private $conn;

    public function __construct() {
        require_once(__DIR__ . '/mConnect.php');
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    // Lấy danh sách lớp mà giáo viên đang dạy
    public function getTeacherClasses($maGV) {
        $sql = "SELECT DISTINCT l.maLop, l.tenLop, l.namHoc 
                FROM lophoc l
                INNER JOIN phancong_giangday pc ON l.maLop = pc.maLop
                WHERE pc.maGV = ? AND pc.trangThai = 'active'
                ORDER BY l.tenLop";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Lấy danh sách môn học mà giáo viên dạy cho lớp
    public function getTeacherSubjects($maGV, $maLop) {
        $sql = "SELECT DISTINCT m.maMonHoc, m.tenMonHoc
                FROM monhoc m
                INNER JOIN phancong_giangday pc ON m.maMonHoc = pc.maMonHoc
                WHERE pc.maGV = ? AND pc.maLop = ? AND pc.trangThai = 'active'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $maGV, $maLop);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Lấy môn học mà giáo viên dạy cho lớp cụ thể (chỉ lấy 1 môn đầu tiên)
    public function getTeacherSubjectForClass($maGV, $maLop) {
        $sql = "SELECT DISTINCT m.maMonHoc, m.tenMonHoc
                FROM monhoc m
                INNER JOIN phancong_giangday pc ON m.maMonHoc = pc.maMonHoc
                WHERE pc.maGV = ? AND pc.maLop = ? AND pc.trangThai = 'active'
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $maGV, $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Thêm bài tập mới - tự động xác định môn học
    public function createHomework($data) {
        try {
            // Validate dữ liệu
            if (empty($data['tenBaiTap']) || empty($data['thoiGianNop']) || empty($data['maLop'])) {
                return [
                    'success' => false,
                    'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc!'
                ];
            }

            // Tự động lấy môn học từ phân công giảng dạy
            $maGV = $data['maGV'];
            $maLop = $data['maLop'];
            
            $subjectInfo = $this->getTeacherSubjectForClass($maGV, $maLop);
            
            if (!$subjectInfo) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy môn học bạn đang dạy cho lớp này. Vui lòng kiểm tra lại phân công giảng dạy.'
                ];
            }
            
            $maMonHoc = $subjectInfo['maMonHoc'];

            // Xử lý file nếu có
            $tenFile = NULL;
            $duongDan = NULL;
            
            if (isset($data['file']) && is_array($data['file']) && $data['file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->uploadFile($data['file']);
                if (!$uploadResult['success']) {
                    return $uploadResult;
                }
                $tenFile = $uploadResult['fileName'];
                $duongDan = $uploadResult['filePath'];
                error_log("File uploaded successfully: " . $tenFile);
            }

            // Lấy giá trị cho phép nộp trễ (mặc định là 1 - cho phép)
            $choPhepNopTre = isset($data['choPhepNopTre']) ? (int)$data['choPhepNopTre'] : 1;

            $sql = "INSERT INTO baitap (tenBaiTap, yeuCauBaiTap, thoiGianNop, maLop, maMonHoc, trangThai, choPhepNopTre, tenFile, duongDan) 
                    VALUES (?, ?, ?, ?, ?, 'Chuanoop', ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return [
                    'success' => false,
                    'message' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $this->conn->error
                ];
            }
            
            $stmt->bind_param("sssiiiss", 
                $data['tenBaiTap'],
                $data['yeuCauBaiTap'],
                $data['thoiGianNop'],
                $maLop,
                $maMonHoc,
                $choPhepNopTre,
                $tenFile,
                $duongDan
            );
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Giao bài tập thành công!'
                ];
            } else {
                error_log("Execute failed: " . $stmt->error);
                // Xóa file nếu insert DB thất bại
                if ($duongDan && file_exists($duongDan)) {
                    unlink($duongDan);
                }
                return [
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi lưu bài tập: ' . $stmt->error
                ];
            }
        } catch (Exception $e) {
            error_log("Exception in createHomework: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload file bài tập
     */
    private function uploadFile($file) {
        // Log thông tin file
        error_log("Starting file upload for: " . $file['name']);
        error_log("File size: " . $file['size']);
        error_log("File type: " . $file['type']);
        
        // Kiểm tra lỗi upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File vượt quá kích thước cho phép trong php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File vượt quá kích thước cho phép trong form',
                UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
                UPLOAD_ERR_NO_FILE => 'Không có file nào được upload',
                UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
                UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file vào disk',
                UPLOAD_ERR_EXTENSION => 'Extension PHP chặn upload file'
            ];
            
            $errorMsg = $errorMessages[$file['error']] ?? 'Lỗi không xác định: ' . $file['error'];
            error_log("Upload error: " . $errorMsg);
            
            return [
                'success' => false,
                'message' => 'Lỗi khi upload file: ' . $errorMsg
            ];
        }

        // Kiểm tra kích thước file (max 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            error_log("File too large: " . $file['size']);
            return [
                'success' => false,
                'message' => 'File quá lớn! Kích thước tối đa là 10MB.'
            ];
        }

        // Kiểm tra file có thực sự tồn tại
        if (!file_exists($file['tmp_name'])) {
            error_log("Temp file does not exist: " . $file['tmp_name']);
            return [
                'success' => false,
                'message' => 'File tạm không tồn tại!'
            ];
        }

        // Kiểm tra định dạng file
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log("Invalid extension: " . $fileExtension);
            return [
                'success' => false,
                'message' => 'Chỉ chấp nhận file: ' . implode(', ', $allowedExtensions)
            ];
        }

        // Tạo thư mục uploads/homework nếu chưa có
        $uploadDir = __DIR__ . '/../uploads/homework/';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                error_log("Failed to create directory: " . $uploadDir);
                return [
                    'success' => false,
                    'message' => 'Không thể tạo thư mục upload!'
                ];
            }
            error_log("Created directory: " . $uploadDir);
        }

        // Kiểm tra quyền ghi
        if (!is_writable($uploadDir)) {
            error_log("Directory not writable: " . $uploadDir);
            return [
                'success' => false,
                'message' => 'Thư mục upload không có quyền ghi!'
            ];
        }

        // Tạo tên file unique và an toàn
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $fileName = time() . '_' . uniqid() . '_' . $safeName . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;

        error_log("Attempting to move file to: " . $filePath);

        // Di chuyển file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            error_log("File uploaded successfully: " . $filePath);
            
            // Kiểm tra file đã được tạo
            if (!file_exists($filePath)) {
                error_log("File exists check failed after upload");
                return [
                    'success' => false,
                    'message' => 'File upload thành công nhưng không tìm thấy!'
                ];
            }
            
            return [
                'success' => true,
                'fileName' => $fileName,
                'filePath' => $filePath,
                'originalName' => $file['name']
            ];
        } else {
            error_log("move_uploaded_file failed");
            error_log("Source: " . $file['tmp_name']);
            error_log("Destination: " . $filePath);
            error_log("Last error: " . error_get_last()['message'] ?? 'No error info');
            
            return [
                'success' => false,
                'message' => 'Không thể di chuyển file! Kiểm tra quyền thư mục.'
            ];
        }
    }

    // Lấy danh sách bài tập của giáo viên
    public function getHomeworkList($maGV, $maLop = null, $maMonHoc = null) {
        $sql = "SELECT DISTINCT bt.*, l.tenLop, m.tenMonHoc, l.siSo as siSoLop,
                       (SELECT COUNT(*) FROM bainop WHERE maBaiTap = bt.maBaiTap) as soLuongNopBai
                FROM baitap bt
                INNER JOIN lophoc l ON bt.maLop = l.maLop
                INNER JOIN monhoc m ON bt.maMonHoc = m.maMonHoc
                WHERE bt.maMonHoc IN (
                    SELECT DISTINCT pc.maMonHoc 
                    FROM phancong_giangday pc 
                    WHERE pc.maGV = ? AND pc.trangThai = 'active'
                )
                AND bt.maLop IN (
                    SELECT DISTINCT pc.maLop 
                    FROM phancong_giangday pc 
                    WHERE pc.maGV = ? AND pc.trangThai = 'active'
                )";
        
        $params = [$maGV, $maGV];
        $types = "ii";
        
        if ($maLop) {
            $sql .= " AND bt.maLop = ?";
            $params[] = $maLop;
            $types .= "i";
        }
        
        if ($maMonHoc) {
            $sql .= " AND bt.maMonHoc = ?";
            $params[] = $maMonHoc;
            $types .= "i";
        }
        
        $sql .= " ORDER BY bt.thoiGianNop DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Lấy thông tin chi tiết bài tập
    public function getHomeworkById($maBaiTap) {
        $sql = "SELECT bt.*, l.tenLop, m.tenMonHoc
                FROM baitap bt
                INNER JOIN lophoc l ON bt.maLop = l.maLop
                INNER JOIN monhoc m ON bt.maMonHoc = m.maMonHoc
                WHERE bt.maBaiTap = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBaiTap);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Cập nhật bài tập
    public function updateHomework($maBaiTap, $data) {
        try {
            // Lấy thông tin bài tập hiện tại
            $currentHomework = $this->getHomeworkById($maBaiTap);
            if (!$currentHomework) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy bài tập!'
                ];
            }

            $tenFile = $currentHomework['tenFile'];
            $duongDan = $currentHomework['duongDan'];
            
            // Xóa file cũ nếu được yêu cầu
            if (isset($data['removeCurrentFile']) && $data['removeCurrentFile']) {
                if ($duongDan && file_exists($duongDan)) {
                    unlink($duongDan);
                }
                $tenFile = NULL;
                $duongDan = NULL;
            }
            
            // Xử lý upload file mới nếu có
            if (isset($data['file']) && is_array($data['file']) && $data['file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->uploadFile($data['file']);
                if (!$uploadResult['success']) {
                    return $uploadResult;
                }
                
                // Xóa file cũ khi có file mới
                if ($duongDan && file_exists($duongDan)) {
                    unlink($duongDan);
                }
                
                $tenFile = $uploadResult['fileName'];
                $duongDan = $uploadResult['filePath'];
            }

            // Lấy giá trị cho phép nộp trễ
            $choPhepNopTre = isset($data['choPhepNopTre']) ? (int)$data['choPhepNopTre'] : 1;

            $sql = "UPDATE baitap 
                    SET tenBaiTap = ?, yeuCauBaiTap = ?, thoiGianNop = ?, 
                        maLop = ?, maMonHoc = ?, choPhepNopTre = ?, tenFile = ?, duongDan = ?
                    WHERE maBaiTap = ?";
            
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $this->conn->error
                ];
            }
            
            $stmt->bind_param("sssiiissi",
                $data['tenBaiTap'],
                $data['yeuCauBaiTap'],
                $data['thoiGianNop'],
                $data['maLop'],
                $data['maMonHoc'],
                $choPhepNopTre,
                $tenFile,
                $duongDan,
                $maBaiTap
            );
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Cập nhật bài tập thành công!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật: ' . $this->conn->error
                ];
            }
        } catch (Exception $e) {
            error_log("Exception in updateHomework: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }

    // Xóa bài tập và file đính kèm
    public function deleteHomework($maBaiTap) {
        // Lấy thông tin bài tập
        $homework = $this->getHomeworkById($maBaiTap);
        if (!$homework) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy bài tập!'
            ];
        }

        // Xóa file nếu có
        if ($homework['duongDan'] && file_exists($homework['duongDan'])) {
            unlink($homework['duongDan']);
        }

        $sql = "DELETE FROM baitap WHERE maBaiTap = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBaiTap);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Xóa bài tập thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa: ' . $this->conn->error
            ];
        }
    }

    /**
     * Download file bài tập
     */
    public function downloadHomeworkFile($maBaiTap) {
        $homework = $this->getHomeworkById($maBaiTap);
        
        if (!$homework) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy bài tập!'
            ];
        }

        if (empty($homework['duongDan']) || !file_exists($homework['duongDan'])) {
            return [
                'success' => false,
                'message' => 'File không tồn tại!'
            ];
        }

        return [
            'success' => true,
            'filePath' => $homework['duongDan'],
            'fileName' => $homework['tenFile'],
            'originalName' => $homework['tenBaiTap']
        ];
    }

    /**
     * Lấy danh sách năm học
     */
    public function getSchoolYears() {
        $sql = "SELECT DISTINCT namHoc 
                FROM lophoc 
                WHERE namHoc IS NOT NULL AND namHoc != ''
                ORDER BY namHoc DESC";
        
        $result = mysqli_query($this->conn, $sql);
        
        $years = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $years[] = $row['namHoc'];
        }
        
        // Nếu không có dữ liệu, tạo danh sách 3 năm
        if (empty($years)) {
            $currentYear = date('Y');
            $currentMonth = date('n');
            
            if ($currentMonth >= 9) {
                $years[] = ($currentYear - 1) . "-" . $currentYear;
                $years[] = $currentYear . "-" . ($currentYear + 1);
                $years[] = ($currentYear + 1) . "-" . ($currentYear + 2);
            } else {
                $years[] = ($currentYear - 2) . "-" . ($currentYear - 1);
                $years[] = ($currentYear - 1) . "-" . $currentYear;
                $years[] = $currentYear . "-" . ($currentYear + 1);
            }
        }
        
        return $years;
    }

    // Lấy danh sách lớp và môn học mà giáo viên đang dạy (combo)
    public function getTeacherClassesWithSubjects($maGV) {
        $sql = "SELECT DISTINCT l.maLop, l.tenLop, l.namHoc, m.maMonHoc, m.tenMonHoc
                FROM lophoc l
                INNER JOIN phancong_giangday pc ON l.maLop = pc.maLop
                INNER JOIN monhoc m ON m.maMonHoc = pc.maMonHoc
                WHERE pc.maGV = ? AND pc.trangThai = 'active'
                ORDER BY l.tenLop, m.tenMonHoc";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>
