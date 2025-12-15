<?php
class ModelStudentAbsence
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Lấy danh sách lớp chủ nhiệm của giáo viên
     */
    public function getClassesByTeacher($maGV)
    {
        $sql = "SELECT DISTINCT l.maLop, l.tenLop, k.khoiLop as tenKhoi 
                FROM lophoc l
                JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE l.maGV = ?
                ORDER BY k.khoiLop, l.tenLop";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getClassesByTeacher: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $classes = [];
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
        
        $stmt->close();
        return $classes;
    }

    /**
     * Lấy thông tin lớp học
     */
    public function getClassInfo($maLop)
    {
        $sql = "SELECT l.maLop, l.tenLop, k.khoiLop as tenKhoi, l.maGV, gv.hoTen as tenGV
                FROM lophoc l
                JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN giaovien gv ON l.maGV = gv.maGV
                WHERE l.maLop = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getClassInfo: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $classInfo = $result->fetch_assoc();
        $stmt->close();
        
        return $classInfo;
    }

    /**
     * Lấy danh sách học sinh và thông tin nghỉ học
     */
    public function getStudentAbsences($maLop, $hocKy, $namHoc)
    {
        // Debug: Log parameters
        error_log("=== getStudentAbsences QUERY ===");
        error_log("maLop: " . var_export($maLop, true));
        error_log("hocKy: " . var_export($hocKy, true));
        error_log("namHoc: " . var_export($namHoc, true));
        
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen,
                    COUNT(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 END) as soNghiCoPhep,
                    COUNT(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 END) as soNghiKhongPhep,
                    COUNT(nh.maNghiHoc) as tongNghi
                FROM hocsinh hs
                LEFT JOIN nghihoc nh ON hs.maHS = nh.maHS 
                    AND nh.hocKy = ? 
                    AND TRIM(nh.namHoc) = TRIM(?)
                WHERE hs.maLop = ? AND hs.trangThaiHocTap = 'danghoc'
                GROUP BY hs.maHS, hs.hoTen
                ORDER BY hs.hoTen";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getStudentAbsences: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("isi", $hocKy, $namHoc, $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        $stmt->close();
        return $students;
    }

    /**
     * Lấy chi tiết nghỉ học của một học sinh
     */
    public function getAbsenceDetails($maHS, $hocKy, $namHoc)
    {
        error_log("=== getAbsenceDetails ===");
        error_log("Params: maHS=$maHS, hocKy=$hocKy, namHoc='$namHoc'");
        
        $sql = "SELECT 
                    nh.maNghiHoc,
                    nh.ngayNghi,
                    nh.loaiNghi,
                    nh.lyDo,
                    gv.hoTen as nguoiDuyet
                FROM nghihoc nh
                LEFT JOIN giaovien gv ON nh.nguoiDuyet = gv.maGV
                WHERE nh.maHS = ? AND nh.hocKy = ? AND TRIM(nh.namHoc) = TRIM(?)
                ORDER BY nh.ngayNghi DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getAbsenceDetails: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("iis", $maHS, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $details = [];
        while ($row = $result->fetch_assoc()) {
            $details[] = $row;
        }
        
        $stmt->close();
        return $details;
    }

    /**
     * Thêm nghỉ học mới
     */
    public function addAbsence($maHS, $ngayNghi, $hocKy, $namHoc, $loaiNghi, $lyDo, $maGV)
    {
        // Debug: Log giá trị trước khi insert - GIỮ NGUYÊN GIÁ TRỊ
        error_log("=== MODEL addAbsence ===");
        error_log("namHoc received: " . var_export($namHoc, true));
        error_log("namHoc length: " . strlen($namHoc));
        error_log("namHoc type: " . gettype($namHoc));
        
        // Kiểm tra xem ngày nghỉ đã tồn tại chưa
        $checkSql = "SELECT maNghiHoc FROM nghihoc 
                     WHERE maHS = ? AND ngayNghi = ? AND hocKy = ? AND namHoc = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        if (!$checkStmt) {
            error_log("SQL Error in addAbsence (check): " . $this->conn->error);
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $this->conn->error];
        }
        
        $checkStmt->bind_param("isis", $maHS, $ngayNghi, $hocKy, $namHoc);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => 'Ngày nghỉ này đã được ghi nhận trước đó'];
        }
        $checkStmt->close();

        // Thêm nghỉ học mới
        $sql = "INSERT INTO nghihoc (maHS, ngayNghi, hocKy, namHoc, loaiNghi, lyDo, nguoiDuyet) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in addAbsence (insert): " . $this->conn->error);
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $this->conn->error];
        }
        
        error_log("About to insert with namHoc: " . $namHoc);
        $stmt->bind_param("isisssi", $maHS, $ngayNghi, $hocKy, $namHoc, $loaiNghi, $lyDo, $maGV);
        
        if ($stmt->execute()) {
            $insertedId = $this->conn->insert_id;
            error_log("Insert successful, ID: " . $insertedId);
            
            // Verify what was actually inserted
            $verifySql = "SELECT maHS, hocKy, namHoc, ngayNghi, loaiNghi, LENGTH(namHoc) as len FROM nghihoc WHERE maNghiHoc = ?";
            $verifyStmt = $this->conn->prepare($verifySql);
            $verifyStmt->bind_param("i", $insertedId);
            $verifyStmt->execute();
            $verifyResult = $verifyStmt->get_result();
            $verifyRow = $verifyResult->fetch_assoc();
            error_log("=== VERIFY INSERTED ===");
            error_log("Full record: " . print_r($verifyRow, true));
            error_log("namHoc: '" . $verifyRow['namHoc'] . "' (length: " . $verifyRow['len'] . ")");
            $verifyStmt->close();
            
            $stmt->close();
            return ['success' => true, 'message' => 'Thêm nghỉ học thành công'];
        } else {
            $error = $stmt->error;
            error_log("Insert failed: " . $error);
            $stmt->close();
            return ['success' => false, 'message' => 'Lỗi: ' . $error];
        }
    }

    /**
     * Cập nhật nghỉ học
     */
    public function updateAbsence($maNghiHoc, $ngayNghi, $loaiNghi, $lyDo)
    {
        $sql = "UPDATE nghihoc 
                SET ngayNghi = ?, loaiNghi = ?, lyDo = ?
                WHERE maNghiHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in updateAbsence: " . $this->conn->error);
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $this->conn->error];
        }
        
        $stmt->bind_param("sssi", $ngayNghi, $loaiNghi, $lyDo, $maNghiHoc);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Cập nhật nghỉ học thành công'];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Lỗi: ' . $error];
        }
    }

    /**
     * Xóa nghỉ học
     */
    public function deleteAbsence($maNghiHoc)
    {
        $sql = "DELETE FROM nghihoc WHERE maNghiHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in deleteAbsence: " . $this->conn->error);
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $this->conn->error];
        }
        
        $stmt->bind_param("i", $maNghiHoc);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Xóa nghỉ học thành công'];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Lỗi: ' . $error];
        }
    }

    /**
     * Lấy danh sách năm học có dữ liệu
     */
    public function getAvailableYears()
    {
        $sql = "SELECT DISTINCT namHoc FROM nghihoc ORDER BY namHoc DESC";
        
        $result = $this->conn->query($sql);
        
        $years = [];
        while ($row = $result->fetch_assoc()) {
            $namHoc = $row['namHoc'];
            // Chỉ lấy năm học có format đúng (có dấu gạch ngang)
            if (strpos($namHoc, '-') !== false) {
                $years[] = $namHoc;
            }
        }
        
        // Thêm năm hiện tại nếu chưa có
        $currentYear = date('Y');
        $currentNamHoc = ($currentYear - 1) . '-' . $currentYear;
        if (!in_array($currentNamHoc, $years)) {
            array_unshift($years, $currentNamHoc);
        }
        
        // Nếu không có năm nào, thêm một vài năm mặc định
        if (empty($years)) {
            for ($i = 0; $i < 3; $i++) {
                $startYear = $currentYear - $i - 1;
                $endYear = $currentYear - $i;
                $years[] = $startYear . '-' . $endYear;
            }
        }
        
        return $years;
    }

    /**
     * Lấy thống kê nghỉ học theo lớp
     */
    public function getClassAbsenceStatistics($maLop, $hocKy, $namHoc)
    {
        $sql = "SELECT 
                    COUNT(DISTINCT hs.maHS) as tongHocSinh,
                    COUNT(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 END) as tongNghiCoPhep,
                    COUNT(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 END) as tongNghiKhongPhep,
                    COUNT(nh.maNghiHoc) as tongNghi
                FROM hocsinh hs
                LEFT JOIN nghihoc nh ON hs.maHS = nh.maHS 
                    AND nh.hocKy = ? 
                    AND nh.namHoc = ?
                WHERE hs.maLop = ? AND hs.trangThaiHocTap = 'danghoc'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $hocKy, $namHoc, $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        return $stats;
    }
}
?>
