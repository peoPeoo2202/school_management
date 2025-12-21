<?php
/**
 * Model: Phân công chấm thi theo Kỳ thi (Exam Grading Assignment)
 * Chức năng: Quản lý phân công giáo viên chấm điểm theo kỳ thi
 * Actor: Tổ trưởng bộ môn (TTBM)
 * 
 * @author IMAX Team
 * @version 1.0
 * @date 2025-12-20
 */

require_once(__DIR__ . '/mConnect.php');

class mExamGradingAssignment
{
    private $connection;

    public function __construct()
    {
        $db = new mConnect();
        $this->connection = $db->mConnect();
    }

    /**
     * Lấy thông tin TTBM từ maTaiKhoan
     * 
     * @param int $maTaiKhoan Mã tài khoản
     * @return array|null Thông tin TTBM
     */
    public function getTTBMInfo($maTaiKhoan)
    {
        $sql = "SELECT 
                    t.maTTBM,
                    t.maGV,
                    t.maTaiKhoan,
                    gv.hoTen,
                    gv.toBoMon,
                    gv.email
                FROM ttbm t
                INNER JOIN giaovien gv ON t.maGV = gv.maGV
                WHERE t.maTaiKhoan = ?";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maTaiKhoan);
        $stmt->execute();
        $result = $stmt->get_result();
        $ttbm = $result->fetch_assoc();
        $stmt->close();

        return $ttbm;
    }

    /**
     * Lấy danh sách kỳ thi
     * 
     * @param array $filters Bộ lọc (maKhoi, hocKy, namHoc, trangThai)
     * @return array Danh sách kỳ thi
     */
    public function getExamPeriods($filters = [])
    {
        $sql = "SELECT 
                    kt.maKyThi,
                    kt.tenKyThi,
                    kt.loaiKyThi,
                    kt.maKhoi,
                    k.khoiLop,
                    kt.hocKy,
                    kt.namHoc,
                    kt.ngayBatDau,
                    kt.ngayKetThuc,
                    kt.trangThai,
                    kt.moTa
                FROM kythi kt
                LEFT JOIN khoi k ON kt.maKhoi = k.maKhoi
                WHERE 1=1";
        
        $params = [];
        $types = "";

        if (!empty($filters['maKhoi'])) {
            $sql .= " AND kt.maKhoi = ?";
            $params[] = $filters['maKhoi'];
            $types .= "i";
        }

        if (!empty($filters['hocKy'])) {
            $sql .= " AND kt.hocKy = ?";
            $params[] = $filters['hocKy'];
            $types .= "i";
        }

        if (!empty($filters['namHoc'])) {
            $sql .= " AND kt.namHoc = ?";
            $params[] = $filters['namHoc'];
            $types .= "s";
        }

        if (!empty($filters['trangThai'])) {
            $sql .= " AND kt.trangThai = ?";
            $params[] = $filters['trangThai'];
            $types .= "s";
        }

        $sql .= " ORDER BY kt.ngayBatDau DESC, kt.maKyThi DESC";

        if (!empty($params)) {
            $stmt = $this->connection->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->connection->query($sql);
        }

        $examPeriods = [];
        while ($row = $result->fetch_assoc()) {
            $examPeriods[] = $row;
        }

        if (isset($stmt)) {
            $stmt->close();
        }

        return $examPeriods;
    }

    /**
     * Lấy chi tiết kỳ thi theo mã
     * 
     * @param int $maKyThi Mã kỳ thi
     * @return array|null Thông tin kỳ thi
     */
    public function getExamPeriodById($maKyThi)
    {
        $sql = "SELECT 
                    kt.maKyThi,
                    kt.tenKyThi,
                    kt.loaiKyThi,
                    kt.maKhoi,
                    k.khoiLop,
                    kt.hocKy,
                    kt.namHoc,
                    kt.ngayBatDau,
                    kt.ngayKetThuc,
                    kt.trangThai,
                    kt.moTa
                FROM kythi kt
                LEFT JOIN khoi k ON kt.maKhoi = k.maKhoi
                WHERE kt.maKyThi = ?";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maKyThi);
        $stmt->execute();
        $result = $stmt->get_result();
        $exam = $result->fetch_assoc();
        $stmt->close();

        return $exam;
    }

    /**
     * Lấy danh sách lớp theo khối
     * 
     * @param int $maKhoi Mã khối (optional)
     * @return array Danh sách lớp
     */
    public function getClassesByGrade($maKhoi = null)
    {
        $sql = "SELECT 
                    l.maLop,
                    l.tenLop,
                    l.maKhoi,
                    k.khoiLop,
                    (SELECT COUNT(*) FROM phancong_hocsinh_lop phl WHERE phl.maLop = l.maLop) as soHocSinh
                FROM lophoc l
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE 1=1";

        $params = [];
        $types = "";

        if ($maKhoi !== null) {
            $sql .= " AND l.maKhoi = ?";
            $params[] = $maKhoi;
            $types .= "i";
        }

        $sql .= " ORDER BY k.khoiLop ASC, l.tenLop ASC";

        if (!empty($params)) {
            $stmt = $this->connection->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->connection->query($sql);
        }

        $classes = [];
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }

        if (isset($stmt)) {
            $stmt->close();
        }

        return $classes;
    }

    /**
     * Lấy danh sách môn học theo tổ bộ môn
     * 
     * @param string $toBoMon Tổ bộ môn của TTBM
     * @return array Danh sách môn học
     */
    public function getSubjectsByDepartment($toBoMon = null)
    {
        // Lấy các môn học mà tổ bộ môn phụ trách
        $sql = "SELECT DISTINCT m.maMonHoc, m.tenMonHoc 
                FROM monhoc m";
        
        // Nếu có tổ bộ môn, lọc theo giáo viên trong tổ dạy môn đó
        if ($toBoMon !== null) {
            $sql .= " INNER JOIN phancong_gvbm pg ON m.maMonHoc = pg.maMonHoc
                      INNER JOIN giaovien gv ON pg.maGV = gv.maGV
                      WHERE gv.toBoMon = ?";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->bind_param("s", $toBoMon);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql .= " ORDER BY m.tenMonHoc ASC";
            $result = $this->connection->query($sql);
        }

        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }

        if (isset($stmt)) {
            $stmt->close();
        }

        return $subjects;
    }

    /**
     * Lấy tất cả môn học
     * 
     * @return array Danh sách môn học
     */
    public function getAllSubjects()
    {
        $sql = "SELECT maMonHoc, tenMonHoc FROM monhoc ORDER BY tenMonHoc ASC";
        $result = $this->connection->query($sql);
        
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        
        return $subjects;
    }

    /**
     * Lấy danh sách bài thi cần chấm theo kỳ thi, lớp, môn
     * 
     * @param int $maKyThi Mã kỳ thi
     * @param int $maLop Mã lớp
     * @param int $maMonHoc Mã môn học
     * @return array Danh sách bài thi
     */
    public function getExamPapersToGrade($maKyThi, $maLop, $maMonHoc)
    {
        // Lấy số học sinh trong lớp (số bài thi cần chấm)
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    l.tenLop,
                    m.tenMonHoc,
                    kt.tenKyThi,
                    kt.loaiKyThi
                FROM hocsinh hs
                INNER JOIN lophoc l ON hs.maLop = l.maLop
                CROSS JOIN monhoc m
                CROSS JOIN kythi kt
                WHERE hs.maLop = ? 
                AND m.maMonHoc = ?
                AND kt.maKyThi = ?
                ORDER BY hs.hoTen ASC";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("iii", $maLop, $maMonHoc, $maKyThi);
        $stmt->execute();
        $result = $stmt->get_result();

        $papers = [];
        while ($row = $result->fetch_assoc()) {
            $papers[] = $row;
        }
        $stmt->close();

        return $papers;
    }

    /**
     * Đếm số bài thi cần chấm
     * 
     * @param int $maKyThi Mã kỳ thi
     * @param int $maLop Mã lớp
     * @param int $maMonHoc Mã môn học
     * @return int Số bài thi
     */
    public function countExamPapers($maKyThi, $maLop, $maMonHoc)
    {
        $sql = "SELECT COUNT(*) as total
                FROM hocsinh hs
                WHERE hs.maLop = ?";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return intval($row['total']);
    }

    /**
     * Lấy danh sách giáo viên theo môn học và tổ bộ môn
     * 
     * @param int $maMonHoc Mã môn học
     * @param string $toBoMon Tổ bộ môn
     * @param bool $chuaPhanCong Chỉ lấy GV chưa phân công
     * @param int $maKyThi Mã kỳ thi (để kiểm tra đã phân công chưa)
     * @param int $maLop Mã lớp
     * @return array Danh sách giáo viên
     */
    public function getTeachersForGrading($maMonHoc, $toBoMon, $chuaPhanCong = false, $maKyThi = null, $maLop = null)
    {
        $sql = "SELECT DISTINCT
                    gv.maGV,
                    gv.hoTen,
                    gv.email,
                    gv.soDienThoai,
                    gv.toBoMon,
                    m.tenMonHoc";
        
        // Thêm subquery kiểm tra đã phân công chưa
        if ($maKyThi !== null && $maLop !== null) {
            $sql .= ", (SELECT COUNT(*) FROM phancongchamdiem pc 
                        WHERE pc.maGV = gv.maGV 
                        AND pc.maLop = ? 
                        AND pc.maMonHoc = ?
                        AND pc.loaiKiemTra = (SELECT loaiKyThi FROM kythi WHERE maKyThi = ?)) as daPhanCong";
        }

        $sql .= " FROM giaovien gv
                  INNER JOIN phancong_gvbm pg ON gv.maGV = pg.maGV
                  INNER JOIN monhoc m ON pg.maMonHoc = m.maMonHoc
                  WHERE pg.maMonHoc = ?
                  AND gv.toBoMon = ?";

        $params = [];
        $types = "";

        if ($maKyThi !== null && $maLop !== null) {
            $params[] = $maLop;
            $params[] = $maMonHoc;
            $params[] = $maKyThi;
            $types .= "iii";
        }

        $params[] = $maMonHoc;
        $params[] = $toBoMon;
        $types .= "is";

        // Lọc chỉ GV chưa phân công
        if ($chuaPhanCong && $maKyThi !== null && $maLop !== null) {
            $sql .= " AND gv.maGV NOT IN (
                        SELECT pc.maGV FROM phancongchamdiem pc 
                        WHERE pc.maLop = ? 
                        AND pc.maMonHoc = ?
                        AND pc.loaiKiemTra = (SELECT loaiKyThi FROM kythi WHERE maKyThi = ?)
                      )";
            $params[] = $maLop;
            $params[] = $maMonHoc;
            $params[] = $maKyThi;
            $types .= "iii";
        }

        $sql .= " ORDER BY gv.hoTen ASC";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $teachers = [];
        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }
        $stmt->close();

        return $teachers;
    }

    /**
     * Tạo phân công chấm thi
     * 
     * @param array $data Dữ liệu phân công
     * @return array Kết quả
     */
    public function createGradingAssignment($data)
    {
        $this->connection->begin_transaction();

        try {
            // Lấy thông tin kỳ thi
            $examPeriod = $this->getExamPeriodById($data['maKyThi']);
            if (!$examPeriod) {
                throw new Exception("Không tìm thấy kỳ thi");
            }

            $loaiKiemTra = $examPeriod['loaiKyThi'];
            $ngayCham = date('Y-m-d'); // Ngày hiện tại
            $assignedTeachers = [];

            // Phân công cho từng giáo viên
            foreach ($data['teachers'] as $maGV) {
                // Kiểm tra trùng lặp
                if ($this->checkDuplicateAssignment($maGV, $data['maLop'], $data['maMonHoc'], $loaiKiemTra)) {
                    continue; // Bỏ qua nếu đã phân công
                }

                $sql = "INSERT INTO phancongchamdiem (maGV, maLop, maMonHoc, loaiKiemTra, ngayCham, hinhThucCham, trangThai) 
                        VALUES (?, ?, ?, ?, ?, 'Tập thể', 'pending')";
                
                $stmt = $this->connection->prepare($sql);
                $stmt->bind_param("iiiis", $maGV, $data['maLop'], $data['maMonHoc'], $loaiKiemTra, $ngayCham);
                
                if (!$stmt->execute()) {
                    throw new Exception("Không thể tạo phân công cho giáo viên: " . $maGV);
                }
                
                $assignedTeachers[] = $maGV;
                $stmt->close();
            }

            if (empty($assignedTeachers)) {
                throw new Exception("Không có giáo viên nào được phân công (có thể đã được phân công trước đó)");
            }

            // Gửi thông báo cho các giáo viên được phân công
            foreach ($assignedTeachers as $maGV) {
                $this->sendNotificationToTeacher($maGV, $data['maKyThi'], $data['maLop'], $data['maMonHoc']);
            }

            $this->connection->commit();

            return [
                'success' => true,
                'message' => 'Phân công giáo viên thành công',
                'assignedCount' => count($assignedTeachers),
                'assignedTeachers' => $assignedTeachers
            ];

        } catch (Exception $e) {
            $this->connection->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Kiểm tra phân công trùng lặp
     * 
     * @param int $maGV Mã giáo viên
     * @param int $maLop Mã lớp
     * @param int $maMonHoc Mã môn học
     * @param string $loaiKiemTra Loại kiểm tra
     * @return bool True nếu đã tồn tại
     */
    public function checkDuplicateAssignment($maGV, $maLop, $maMonHoc, $loaiKiemTra)
    {
        $sql = "SELECT COUNT(*) as count FROM phancongchamdiem 
                WHERE maGV = ? AND maLop = ? AND maMonHoc = ? AND loaiKiemTra = ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("iiis", $maGV, $maLop, $maMonHoc, $loaiKiemTra);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return intval($row['count']) > 0;
    }

    /**
     * Gửi thông báo cho giáo viên được phân công
     * 
     * @param int $maGV Mã giáo viên
     * @param int $maKyThi Mã kỳ thi
     * @param int $maLop Mã lớp
     * @param int $maMonHoc Mã môn học
     * @return bool Kết quả
     */
    private function sendNotificationToTeacher($maGV, $maKyThi, $maLop, $maMonHoc)
    {
        // Lấy thông tin kỳ thi, lớp, môn học
        $examPeriod = $this->getExamPeriodById($maKyThi);
        
        $sqlClass = "SELECT tenLop FROM lophoc WHERE maLop = ?";
        $stmtClass = $this->connection->prepare($sqlClass);
        $stmtClass->bind_param("i", $maLop);
        $stmtClass->execute();
        $classResult = $stmtClass->get_result()->fetch_assoc();
        $stmtClass->close();

        $sqlSubject = "SELECT tenMonHoc FROM monhoc WHERE maMonHoc = ?";
        $stmtSubject = $this->connection->prepare($sqlSubject);
        $stmtSubject->bind_param("i", $maMonHoc);
        $stmtSubject->execute();
        $subjectResult = $stmtSubject->get_result()->fetch_assoc();
        $stmtSubject->close();

        $tieuDe = "Phân công chấm thi - " . ($examPeriod['tenKyThi'] ?? 'Kỳ thi');
        $noiDung = "Bạn đã được phân công chấm thi:\n";
        $noiDung .= "- Kỳ thi: " . ($examPeriod['tenKyThi'] ?? '') . "\n";
        $noiDung .= "- Lớp: " . ($classResult['tenLop'] ?? '') . "\n";
        $noiDung .= "- Môn: " . ($subjectResult['tenMonHoc'] ?? '') . "\n";
        $noiDung .= "- Loại: " . ($examPeriod['loaiKyThi'] ?? '') . "\n";
        $noiDung .= "Vui lòng kiểm tra và thực hiện chấm điểm đúng thời hạn.";

        // Tạo thông báo
        $sql = "INSERT INTO thongbao (tieuDe, noiDung, loaiThongBao, trangThai) 
                VALUES (?, ?, 'phancong_chamdiem', 'chuagui')";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("ss", $tieuDe, $noiDung);
        $result = $stmt->execute();
        $maThongBao = $this->connection->insert_id;
        $stmt->close();

        // Liên kết thông báo với giáo viên (nếu có bảng thongbao_nguoinhan)
        // Có thể mở rộng thêm nếu cần

        return $result;
    }

    /**
     * Lấy danh sách phân công chấm thi theo kỳ thi
     * 
     * @param int $maKyThi Mã kỳ thi
     * @param string $toBoMon Tổ bộ môn
     * @return array Danh sách phân công
     */
    public function getAssignmentsByExamPeriod($maKyThi, $toBoMon = null)
    {
        $examPeriod = $this->getExamPeriodById($maKyThi);
        if (!$examPeriod) {
            return [];
        }

        $sql = "SELECT 
                    pc.maPhanCong,
                    pc.maGV,
                    pc.maLop,
                    pc.maMonHoc,
                    pc.loaiKiemTra,
                    pc.ngayCham,
                    pc.hinhThucCham,
                    pc.trangThai,
                    gv.hoTen as tenGiaoVien,
                    gv.toBoMon,
                    l.tenLop,
                    m.tenMonHoc
                FROM phancongchamdiem pc
                INNER JOIN giaovien gv ON pc.maGV = gv.maGV
                INNER JOIN lophoc l ON pc.maLop = l.maLop
                INNER JOIN monhoc m ON pc.maMonHoc = m.maMonHoc
                WHERE pc.loaiKiemTra = ?";

        $params = [$examPeriod['loaiKyThi']];
        $types = "s";

        if ($toBoMon !== null) {
            $sql .= " AND gv.toBoMon = ?";
            $params[] = $toBoMon;
            $types .= "s";
        }

        $sql .= " ORDER BY l.tenLop ASC, m.tenMonHoc ASC";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        $stmt->close();

        return $assignments;
    }

    /**
     * Xóa phân công chấm thi
     * 
     * @param int $maPhanCong Mã phân công
     * @return array Kết quả
     */
    public function deleteAssignment($maPhanCong)
    {
        try {
            $sql = "DELETE FROM phancongchamdiem WHERE maPhanCong = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->bind_param("i", $maPhanCong);
            
            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => 'Xóa phân công thành công'];
            } else {
                throw new Exception("Không thể xóa phân công");
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Lấy danh sách khối
     * 
     * @return array Danh sách khối
     */
    public function getAllGrades()
    {
        $sql = "SELECT maKhoi, khoiLop FROM khoi ORDER BY khoiLop ASC";
        $result = $this->connection->query($sql);
        
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
        
        return $grades;
    }

    /**
     * Lấy danh sách năm học
     * 
     * @return array Danh sách năm học
     */
    public function getSchoolYears()
    {
        $sql = "SELECT DISTINCT namHoc FROM kythi ORDER BY namHoc DESC";
        $result = $this->connection->query($sql);
        
        $years = [];
        while ($row = $result->fetch_assoc()) {
            $years[] = $row['namHoc'];
        }
        
        // Thêm năm học mặc định nếu chưa có
        if (empty($years)) {
            $years[] = date('Y') . '-' . (date('Y') + 1);
        }
        
        return $years;
    }

    /**
     * Thống kê phân công theo tổ bộ môn
     * 
     * @param string $toBoMon Tổ bộ môn
     * @return array Thống kê
     */
    public function getStatistics($toBoMon)
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN pc.trangThai = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN pc.trangThai = 'in_progress' THEN 1 ELSE 0 END) as inProgress,
                    SUM(CASE WHEN pc.trangThai = 'completed' THEN 1 ELSE 0 END) as completed
                FROM phancongchamdiem pc
                INNER JOIN giaovien gv ON pc.maGV = gv.maGV
                WHERE gv.toBoMon = ?";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $toBoMon);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();

        return $stats;
    }
}
?>
