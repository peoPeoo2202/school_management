<?php
/**
 * Model: Grading Assignment for TTBM (Tổ trưởng bộ môn)
 * Purpose: Quản lý phân công chấm thi/chấm điểm
 */

require_once(__DIR__ . '/mConnect.php');

class mGradingAssignment
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
                    g.hoTen,
                    g.toBoMon
                FROM ttbm t
                LEFT JOIN giaovien g ON t.maGV = g.maGV
                WHERE t.maTaiKhoan = ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maTaiKhoan);
        $stmt->execute();
        $result = $stmt->get_result();
        $info = $result->fetch_assoc();
        $stmt->close();
        
        return $info;
    }

    /**
     * Lấy danh sách phân công chấm điểm với filter
     * 
     * @param array $filters Điều kiện lọc
     * @param int $page Trang hiện tại
     * @param int $limit Số bản ghi mỗi trang
     * @return array Danh sách phân công
     */
    public function getAllAssignments($filters = [], $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT 
                    pc.maPhanCong,
                    pc.maGV,
                    pc.maLop,
                    pc.maMonHoc,
                    pc.loaiKiemTra,
                    pc.ngayCham,
                    pc.hinhThucCham,
                    pc.trangThai,
                    gv.hoTen AS tenGiaoVien,
                    gv.toBoMon,
                    l.tenLop,
                    k.khoiLop,
                    m.tenMonHoc
                FROM phancongchamdiem pc
                LEFT JOIN giaovien gv ON pc.maGV = gv.maGV
                LEFT JOIN lophoc l ON pc.maLop = l.maLop
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN monhoc m ON pc.maMonHoc = m.maMonHoc
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Filter by teacher
        if (!empty($filters['maGV'])) {
            $sql .= " AND pc.maGV = ?";
            $params[] = $filters['maGV'];
            $types .= "i";
        }
        
        // Filter by class
        if (!empty($filters['maLop'])) {
            $sql .= " AND pc.maLop = ?";
            $params[] = $filters['maLop'];
            $types .= "i";
        }
        
        // Filter by subject
        if (!empty($filters['maMonHoc'])) {
            $sql .= " AND pc.maMonHoc = ?";
            $params[] = $filters['maMonHoc'];
            $types .= "i";
        }
        
        // Filter by exam type
        if (!empty($filters['loaiKiemTra'])) {
            $sql .= " AND pc.loaiKiemTra = ?";
            $params[] = $filters['loaiKiemTra'];
            $types .= "s";
        }
        
        // Filter by status
        if (!empty($filters['trangThai'])) {
            $sql .= " AND pc.trangThai = ?";
            $params[] = $filters['trangThai'];
            $types .= "s";
        }
        
        // Filter by department (toBoMon)
        if (!empty($filters['toBoMon'])) {
            $sql .= " AND gv.toBoMon = ?";
            $params[] = $filters['toBoMon'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY pc.ngayCham DESC, pc.maPhanCong DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->connection->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
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
     * Đếm tổng số phân công
     * 
     * @param array $filters Điều kiện lọc
     * @return int Tổng số
     */
    public function countAssignments($filters = [])
    {
        $sql = "SELECT COUNT(*) as total 
                FROM phancongchamdiem pc
                LEFT JOIN giaovien gv ON pc.maGV = gv.maGV
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['maGV'])) {
            $sql .= " AND pc.maGV = ?";
            $params[] = $filters['maGV'];
            $types .= "i";
        }
        
        if (!empty($filters['maLop'])) {
            $sql .= " AND pc.maLop = ?";
            $params[] = $filters['maLop'];
            $types .= "i";
        }
        
        if (!empty($filters['maMonHoc'])) {
            $sql .= " AND pc.maMonHoc = ?";
            $params[] = $filters['maMonHoc'];
            $types .= "i";
        }
        
        if (!empty($filters['loaiKiemTra'])) {
            $sql .= " AND pc.loaiKiemTra = ?";
            $params[] = $filters['loaiKiemTra'];
            $types .= "s";
        }
        
        if (!empty($filters['trangThai'])) {
            $sql .= " AND pc.trangThai = ?";
            $params[] = $filters['trangThai'];
            $types .= "s";
        }
        
        if (!empty($filters['toBoMon'])) {
            $sql .= " AND gv.toBoMon = ?";
            $params[] = $filters['toBoMon'];
            $types .= "s";
        }
        
        $stmt = $this->connection->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return intval($row['total']);
    }

    /**
     * Lấy chi tiết 1 phân công
     * 
     * @param int $maPhanCong Mã phân công
     * @return array|null Chi tiết phân công
     */
    public function getAssignmentById($maPhanCong)
    {
        $sql = "SELECT 
                    pc.*,
                    gv.hoTen AS tenGiaoVien,
                    gv.toBoMon,
                    l.tenLop,
                    k.khoiLop,
                    m.tenMonHoc
                FROM phancongchamdiem pc
                LEFT JOIN giaovien gv ON pc.maGV = gv.maGV
                LEFT JOIN lophoc l ON pc.maLop = l.maLop
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN monhoc m ON pc.maMonHoc = m.maMonHoc
                WHERE pc.maPhanCong = ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maPhanCong);
        $stmt->execute();
        $result = $stmt->get_result();
        $assignment = $result->fetch_assoc();
        $stmt->close();
        
        return $assignment;
    }

    /**
     * Lấy danh sách giáo viên theo tổ bộ môn
     * 
     * @param string|null $toBoMon Tổ bộ môn (null = tất cả)
     * @return array Danh sách giáo viên
     */
    public function getTeachersByDepartment($toBoMon = null)
    {
        $sql = "SELECT maGV, hoTen, toBoMon 
                FROM giaovien";
        
        if ($toBoMon !== null) {
            $sql .= " WHERE toBoMon = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->bind_param("s", $toBoMon);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql .= " ORDER BY toBoMon ASC, hoTen ASC";
            $result = $this->connection->query($sql);
        }
        
        $teachers = [];
        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
        
        return $teachers;
    }

    /**
     * Lấy danh sách lớp học
     * 
     * @return array Danh sách lớp
     */
    public function getAllClasses()
    {
        $sql = "SELECT l.maLop, l.tenLop, k.khoiLop 
                FROM lophoc l
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                ORDER BY k.khoiLop ASC, l.tenLop ASC";
        
        $result = $this->connection->query($sql);
        $classes = [];
        
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
        
        return $classes;
    }

    /**
     * Lấy danh sách môn học
     * 
     * @return array Danh sách môn học
     */
    public function getAllSubjects()
    {
        $sql = "SELECT maMonHoc, tenMonHoc 
                FROM monhoc 
                ORDER BY tenMonHoc ASC";
        
        $result = $this->connection->query($sql);
        $subjects = [];
        
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        
        return $subjects;
    }

    /**
     * Kiểm tra xem giáo viên có bị trùng phân công không
     */
    public function checkDuplicate($maGV, $maLop, $maMonHoc, $loaiKiemTra, $excludeMaPhanCong = null)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM phancongchamdiem 
                WHERE maGV = ? AND maLop = ? AND maMonHoc = ? AND loaiKiemTra = ?";
        
        $params = [$maGV, $maLop, $maMonHoc, $loaiKiemTra];
        $types = "iiss";
        
        if ($excludeMaPhanCong !== null) {
            $sql .= " AND maPhanCong != ?";
            $params[] = $excludeMaPhanCong;
            $types .= "i";
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return intval($row['count']) > 0;
    }
}
?>
