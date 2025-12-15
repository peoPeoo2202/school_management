<?php
/**
 * Model xử lý đề thi
 * Chức năng: Quản lý việc thêm, sửa, xóa đề thi và upload file
 */

class mSubmitExam
{
    private $conn;

    public function __construct()
    {
        $m = new mConnect();
        $this->conn = $m->mConnect();
    }

    public function __destruct()
    {
        if ($this->conn) {
            mysqli_close($this->conn);
        }
    }

    /**
     * Lấy danh sách môn học mà giáo viên đang giảng dạy
     * @param int $maGV - Mã giáo viên
     * @return array - Danh sách môn học
     */
    public function getTeacherSubjects($maGV)
    {
        $sql = "SELECT DISTINCT mh.maMonHoc, mh.tenMonHoc 
                FROM monhoc mh
                INNER JOIN phancong_giangday pc ON mh.maMonHoc = pc.maMonHoc
                WHERE pc.maGV = ?
                ORDER BY mh.tenMonHoc";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $maGV);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $subjects = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $subjects[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $subjects;
    }

    /**
     * Lấy môn học chính mà giáo viên đang giảng dạy (tự động xác định)
     * @param int $maGV - Mã giáo viên
     * @return int|null - Mã môn học hoặc null
     */
    public function getTeacherMainSubject($maGV)
    {
        $sql = "SELECT maMonHoc 
                FROM phancong_giangday 
                WHERE maGV = ? AND trangThai = 'active' 
                LIMIT 1";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $maGV);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            return $row['maMonHoc'];
        }
        
        mysqli_stmt_close($stmt);
        return null;
    }

    /**
     * Lấy tất cả môn học (cho admin/TTBM)
     * @return array - Danh sách môn học
     */
    public function getAllSubjects()
    {
        $sql = "SELECT maMonHoc, tenMonHoc FROM monhoc ORDER BY tenMonHoc";
        $result = mysqli_query($this->conn, $sql);
        
        $subjects = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $subjects[] = $row;
        }
        
        return $subjects;
    }

    /**
     * Thêm đề thi mới
     * @param array $data - Dữ liệu đề thi
     * @return array - Kết quả thực hiện
     */
    public function addExam($data)
    {
        // Validate dữ liệu đầu vào
        if (empty($data['tenDeThi']) || empty($data['maMonHoc']) || 
            empty($data['hocKy']) || empty($data['namHoc'])) {
            return [
                'success' => false,
                'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc!'
            ];
        }

        // Kiểm tra file bắt buộc
        if (!isset($data['file']) || $data['file']['error'] === UPLOAD_ERR_NO_FILE) {
            return [
                'success' => false,
                'message' => 'Vui lòng chọn file đề thi!'
            ];
        }

        // Lấy tên môn học để đặt tên file
        $sqlMonHoc = "SELECT tenMonHoc FROM monhoc WHERE maMonHoc = ?";
        $stmtMonHoc = mysqli_prepare($this->conn, $sqlMonHoc);
        mysqli_stmt_bind_param($stmtMonHoc, "i", $data['maMonHoc']);
        mysqli_stmt_execute($stmtMonHoc);
        $resultMonHoc = mysqli_stmt_get_result($stmtMonHoc);
        $rowMonHoc = mysqli_fetch_assoc($resultMonHoc);
        $tenMonHoc = $rowMonHoc['tenMonHoc'] ?? 'mon-hoc';
        mysqli_stmt_close($stmtMonHoc);
        
        // Xử lý upload file với metadata
        $fileMetadata = [
            'tenMonHoc' => $tenMonHoc,
            'hocKy' => $data['hocKy'],
            'loaiDeThi' => $data['loaiDeThi'] ?? 'de-thi'
        ];
        
        $uploadResult = $this->uploadFile($data['file'], $fileMetadata);
        if (!$uploadResult['success']) {
            return $uploadResult;
        }
        
        $tenFile = $uploadResult['fileName'];
        $duongDan = $uploadResult['filePath'];

        // Thêm vào database
        $sql = "INSERT INTO dethi (tenDeThi, loaiDeThi, tenFile, duongDan, moTa, 
                maGV, maMonHoc, hocKy, namHoc, trangThai, ngayTao) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Chuaduyet', NOW())";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        
        $loaiDeThi = $data['loaiDeThi'] ?? 'de-thi';
        
        mysqli_stmt_bind_param($stmt, "ssssiiiis", 
            $data['tenDeThi'],
            $loaiDeThi,
            $tenFile,
            $duongDan,
            $data['moTa'],
            $data['maGV'],
            $data['maMonHoc'],
            $data['hocKy'],
            $data['namHoc']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $maDeThi = mysqli_insert_id($this->conn);
            mysqli_stmt_close($stmt);
            
            return [
                'success' => true,
                'message' => '✅ Gửi đề thi thành công! Đề thi của bạn đang chờ duyệt.',
                'maDeThi' => $maDeThi
            ];
        } else {
            mysqli_stmt_close($stmt);
            
            // Xóa file nếu upload thành công nhưng insert DB thất bại
            if ($duongDan && file_exists($duongDan)) {
                unlink($duongDan);
            }
            
            return [
                'success' => false,
                'message' => 'Lỗi khi lưu đề thi: ' . mysqli_error($this->conn)
            ];
        }
    }

    /**
     * Tạo tên file có cấu trúc từ metadata
     * @param array $metadata - Thông tin đề thi
     * @param string $extension - Phần mở rộng file
     * @return string - Tên file
     */
    private function generateFileName($metadata, $extension)
    {
        // Chuyển đổi tiếng Việt sang không dấu và thay khoảng trắng bằng gạch ngang
        $tenMonHoc = $metadata['tenMonHoc'] ?? 'mon-hoc';
        $tenMonHoc = $this->removeVietnameseTones($tenMonHoc);
        $tenMonHoc = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $tenMonHoc));
        $tenMonHoc = trim($tenMonHoc, '-');
        
        // Xử lý loại đề thi
        $loaiDeThi = $metadata['loaiDeThi'] ?? 'de-thi';
        $loaiDeThiMap = [
            'de-thi-giua-ky' => 'giua-ky',
            'de-thi-cuoi-ky' => 'cuoi-ky',
            'de-thi-thu' => 'thi-thu',
            'de-thi' => 'de-thi'
        ];
        $loaiDeThiText = $loaiDeThiMap[$loaiDeThi] ?? 'de-thi';
        
        // Học kỳ
        $hocKy = $metadata['hocKy'] ?? '';
        
        // Tạo tên file: de-thi-[loai]-[ten-mon]-hoc-ky-[X]_timestamp.ext
        $parts = [$loaiDeThiText, $tenMonHoc];
        
        if (!empty($hocKy)) {
            $parts[] = 'hoc-ky-' . $hocKy;
        }
        
        $parts[] = time() . '_' . uniqid();
        
        return implode('-', $parts) . '.' . $extension;
    }
    
    /**
     * Chuyển đổi tiếng Việt có dấu sang không dấu
     * @param string $str - Chuỗi cần chuyển đổi
     * @return string - Chuỗi không dấu
     */
    private function removeVietnameseTones($str)
    {
        $vietnamese = [
            'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
            'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
            'ì', 'í', 'ị', 'ỉ', 'ĩ',
            'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
            'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
            'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
            'đ',
            'À', 'Á', 'Ạ', 'Ả', 'Ã', 'Â', 'Ầ', 'Ấ', 'Ậ', 'Ẩ', 'Ẫ', 'Ă', 'Ằ', 'Ắ', 'Ặ', 'Ẳ', 'Ẵ',
            'È', 'É', 'Ẹ', 'Ẻ', 'Ẽ', 'Ê', 'Ề', 'Ế', 'Ệ', 'Ể', 'Ễ',
            'Ì', 'Í', 'Ị', 'Ỉ', 'Ĩ',
            'Ò', 'Ó', 'Ọ', 'Ỏ', 'Õ', 'Ô', 'Ồ', 'Ố', 'Ộ', 'Ổ', 'Ỗ', 'Ơ', 'Ờ', 'Ớ', 'Ợ', 'Ở', 'Ỡ',
            'Ù', 'Ú', 'Ụ', 'Ủ', 'Ũ', 'Ư', 'Ừ', 'Ứ', 'Ự', 'Ử', 'Ữ',
            'Ỳ', 'Ý', 'Ỵ', 'Ỷ', 'Ỹ',
            'Đ'
        ];
        
        $replacements = [
            'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
            'y', 'y', 'y', 'y', 'y',
            'd',
            'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A',
            'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E',
            'I', 'I', 'I', 'I', 'I',
            'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O',
            'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U',
            'Y', 'Y', 'Y', 'Y', 'Y',
            'D'
        ];
        
        return str_replace($vietnamese, $replacements, $str);
    }

    /**
     * Upload file đề thi
     * @param array $file - Thông tin file từ $_FILES
     * @param array $metadata - Thông tin để đặt tên file (tenMonHoc, hocKy, loaiDeThi)
     * @return array - Kết quả upload
     */
    private function uploadFile($file, $metadata = [])
    {
        // Kiểm tra lỗi upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Lỗi khi upload file!'
            ];
        }

        // Kiểm tra kích thước file (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'message' => 'File quá lớn! Kích thước tối đa là 10MB.'
            ];
        }

        // Kiểm tra định dạng file
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($mimeType, $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
            return [
                'success' => false,
                'message' => 'Chỉ chấp nhận file PDF, Word (doc, docx) hoặc Excel (xls, xlsx)!'
            ];
        }

        // Tạo thư mục uploads/exams nếu chưa có
        $uploadDir = __DIR__ . '/../uploads/exams/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Tạo tên file có cấu trúc dựa trên metadata
        $fileName = $this->generateFileName($metadata, $fileExtension);
        $filePath = $uploadDir . $fileName;

        // Di chuyển file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'success' => true,
                'fileName' => $fileName,
                'filePath' => $filePath
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể lưu file!'
            ];
        }
    }

    /**
     * Lấy danh sách đề thi của giáo viên
     * @param int $maGV - Mã giáo viên
     * @param array $filters - Bộ lọc (môn học, học kỳ, năm học, trạng thái)
     * @return array - Danh sách đề thi
     */
    public function getTeacherExams($maGV, $filters = [])
    {
        $sql = "SELECT dt.*, mh.tenMonHoc, 
                CASE 
                    WHEN dt.trangThai = 'Chuaduyet' THEN 'Chờ duyệt'
                    WHEN dt.trangThai = 'Daduyet' THEN 'Đã duyệt'
                    WHEN dt.trangThai = 'Dachon' THEN 'Đã chọn'
                    WHEN dt.trangThai = 'Tuchoi' THEN 'Từ chối'
                    ELSE dt.trangThai
                END as trangThaiText
                FROM dethi dt
                INNER JOIN monhoc mh ON dt.maMonHoc = mh.maMonHoc
                WHERE dt.maGV = ?";
        
        $params = [$maGV];
        $types = "i";
        
        // Áp dụng bộ lọc
        if (!empty($filters['maMonHoc'])) {
            $sql .= " AND dt.maMonHoc = ?";
            $params[] = $filters['maMonHoc'];
            $types .= "i";
        }
        
        if (!empty($filters['hocKy'])) {
            $sql .= " AND dt.hocKy = ?";
            $params[] = $filters['hocKy'];
            $types .= "i";
        }
        
        if (!empty($filters['namHoc'])) {
            $sql .= " AND dt.namHoc = ?";
            $params[] = $filters['namHoc'];
            $types .= "s";
        }
        
        if (!empty($filters['trangThai'])) {
            $sql .= " AND dt.trangThai = ?";
            $params[] = $filters['trangThai'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY dt.ngayTao DESC";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $exams = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $exams[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $exams;
    }

    /**
     * Lấy thông tin chi tiết đề thi
     * @param int $maDeThi - Mã đề thi
     * @param int $maGV - Mã giáo viên (để kiểm tra quyền)
     * @return array|null - Thông tin đề thi
     */
    public function getExamDetail($maDeThi, $maGV = null)
    {
        $sql = "SELECT dt.*, mh.tenMonHoc, gv.hoTen as tenGiaoVien
                FROM dethi dt
                INNER JOIN monhoc mh ON dt.maMonHoc = mh.maMonHoc
                LEFT JOIN giaovien gv ON dt.maGV = gv.maGV
                WHERE dt.maDeThi = ?";
        
        if ($maGV !== null) {
            $sql .= " AND dt.maGV = ?";
        }
        
        $stmt = mysqli_prepare($this->conn, $sql);
        
        if ($maGV !== null) {
            mysqli_stmt_bind_param($stmt, "ii", $maDeThi, $maGV);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $maDeThi);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exam = mysqli_fetch_assoc($result);
        
        mysqli_stmt_close($stmt);
        return $exam;
    }

    /**
     * Cập nhật đề thi
     * @param int $maDeThi - Mã đề thi
     * @param array $data - Dữ liệu cập nhật
     * @return array - Kết quả thực hiện
     */
    public function updateExam($maDeThi, $data)
    {
        // Lấy thông tin đề thi hiện tại
        $currentExam = $this->getExamDetail($maDeThi, $data['maGV']);
        if (!$currentExam) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy đề thi hoặc bạn không có quyền chỉnh sửa!'
            ];
        }

        // Chỉ cho phép sửa đề thi có trạng thái 'Chuaduyet' hoặc 'Tuchoi'
        if (!in_array($currentExam['trangThai'], ['Chuaduyet', 'Tuchoi'])) {
            return [
                'success' => false,
                'message' => 'Chỉ có thể sửa đề thi đang chờ duyệt hoặc bị từ chối!'
            ];
        }

        $tenFile = $currentExam['tenFile'];
        $duongDan = $currentExam['duongDan'];
        
        // Xử lý upload file mới nếu có
        if (isset($data['file']) && $data['file']['error'] === UPLOAD_ERR_OK) {
            // Lấy tên môn học để đặt tên file
            $sqlMonHoc = "SELECT tenMonHoc FROM monhoc WHERE maMonHoc = ?";
            $stmtMonHoc = mysqli_prepare($this->conn, $sqlMonHoc);
            mysqli_stmt_bind_param($stmtMonHoc, "i", $data['maMonHoc']);
            mysqli_stmt_execute($stmtMonHoc);
            $resultMonHoc = mysqli_stmt_get_result($stmtMonHoc);
            $rowMonHoc = mysqli_fetch_assoc($resultMonHoc);
            $tenMonHoc = $rowMonHoc['tenMonHoc'] ?? 'mon-hoc';
            mysqli_stmt_close($stmtMonHoc);
            
            // Xử lý upload file với metadata
            $fileMetadata = [
                'tenMonHoc' => $tenMonHoc,
                'hocKy' => $data['hocKy'],
                'loaiDeThi' => $data['loaiDeThi'] ?? 'de-thi'
            ];
            
            $uploadResult = $this->uploadFile($data['file'], $fileMetadata);
            if (!$uploadResult['success']) {
                return $uploadResult;
            }
            
            // Xóa file cũ
            if ($duongDan && file_exists($duongDan)) {
                unlink($duongDan);
            }
            
            $tenFile = $uploadResult['fileName'];
            $duongDan = $uploadResult['filePath'];
        }

        // Cập nhật database
        $sql = "UPDATE dethi 
                SET tenDeThi = ?, loaiDeThi = ?, moTa = ?, tenFile = ?, duongDan = ?, 
                    maMonHoc = ?, hocKy = ?, namHoc = ?, 
                    trangThai = 'Chuaduyet', ngayCapNhat = NOW()
                WHERE maDeThi = ? AND maGV = ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        
        $loaiDeThi = $data['loaiDeThi'] ?? 'de-thi';
        
        mysqli_stmt_bind_param($stmt, "sssssiisii",
            $data['tenDeThi'],
            $loaiDeThi,
            $data['moTa'],
            $tenFile,
            $duongDan,
            $data['maMonHoc'],
            $data['hocKy'],
            $data['namHoc'],
            $maDeThi,
            $data['maGV']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return [
                'success' => true,
                'message' => 'Cập nhật đề thi thành công!'
            ];
        } else {
            mysqli_stmt_close($stmt);
            return [
                'success' => false,
                'message' => 'Lỗi khi cập nhật đề thi: ' . mysqli_error($this->conn)
            ];
        }
    }

    /**
     * Xóa đề thi
     * @param int $maDeThi - Mã đề thi
     * @param int $maGV - Mã giáo viên (để kiểm tra quyền)
     * @return array - Kết quả thực hiện
     */
    public function deleteExam($maDeThi, $maGV)
    {
        // Lấy thông tin đề thi
        $exam = $this->getExamDetail($maDeThi, $maGV);
        if (!$exam) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy đề thi hoặc bạn không có quyền xóa!'
            ];
        }

        // Chỉ cho phép xóa đề thi có trạng thái 'Chuaduyet' hoặc 'Tuchoi'
        if (!in_array($exam['trangThai'], ['Chuaduyet', 'Tuchoi'])) {
            return [
                'success' => false,
                'message' => 'Chỉ có thể xóa đề thi đang chờ duyệt hoặc bị từ chối!'
            ];
        }

        // Xóa file
        if ($exam['duongDan'] && file_exists($exam['duongDan'])) {
            unlink($exam['duongDan']);
        }

        // Xóa khỏi database
        $sql = "DELETE FROM dethi WHERE maDeThi = ? AND maGV = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $maDeThi, $maGV);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return [
                'success' => true,
                'message' => 'Xóa đề thi thành công!'
            ];
        } else {
            mysqli_stmt_close($stmt);
            return [
                'success' => false,
                'message' => 'Lỗi khi xóa đề thi: ' . mysqli_error($this->conn)
            ];
        }
    }

    /**
     * Download file đề thi
     * @param int $maDeThi - Mã đề thi
     * @param int $maGV - Mã giáo viên (để kiểm tra quyền, null nếu không cần)
     * @return array - Thông tin file để download
     */
    public function downloadExamFile($maDeThi, $maGV = null)
    {
        $exam = $this->getExamDetail($maDeThi, $maGV);
        
        if (!$exam) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy đề thi!'
            ];
        }

        if (empty($exam['duongDan']) || !file_exists($exam['duongDan'])) {
            return [
                'success' => false,
                'message' => 'File không tồn tại!'
            ];
        }

        return [
            'success' => true,
            'filePath' => $exam['duongDan'],
            'fileName' => $exam['tenFile'],
            'originalName' => $exam['tenDeThi']
        ];
    }

    /**
     * Lấy danh sách năm học từ database
     * @return array - Danh sách năm học
     */
    public function getSchoolYears()
    {
        // Lấy các năm học từ bảng lophoc (chính xác hơn)
        $sql = "SELECT DISTINCT namHoc 
                FROM lophoc 
                WHERE namHoc IS NOT NULL AND namHoc != ''
                UNION
                SELECT DISTINCT namHoc 
                FROM dethi 
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
}
?>
