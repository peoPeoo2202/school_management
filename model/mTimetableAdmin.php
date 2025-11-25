<?php
/**
 * Model: Timetable Management for Admin
 * Purpose: Quản lý thời khóa biểu
 */

require_once(__DIR__ . '/mConnect.php');

class mTimetableAdmin
{
    private $connection;

    public function __construct()
    {
        $db = new mConnect();
        $this->connection = $db->mConnect();
    }

    /**
     * Lấy danh sách thời khóa biểu với phân trang và filter
     * 
     * @param array $filters Điều kiện lọc (maLop, maMonHoc, maGV, thuNgay)
     * @param int $page Trang hiện tại
     * @param int $limit Số bản ghi mỗi trang
     * @return array Danh sách thời khóa biểu
     */
    public function getAllTimetables($filters = [], $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT 
                    t.maTKB,
                    t.tietHoc,
                    t.thoiGianHoc,
                    t.thuNgay,
                    t.phong,
                    t.maLop,
                    t.maGV,
                    t.maMonHoc,
                    l.tenLop,
                    k.khoiLop,
                    m.tenMonHoc,
                    g.hoTen AS tenGiaoVien
                FROM thoikhoabieu t
                LEFT JOIN lophoc l ON t.maLop = l.maLop
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN monhoc m ON t.maMonHoc = m.maMonHoc
                LEFT JOIN giaovien g ON t.maGV = g.maGV
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Filter by class
        if (!empty($filters['maLop'])) {
            $sql .= " AND t.maLop = ?";
            $params[] = $filters['maLop'];
            $types .= "i";
        }
        
        // Filter by subject
        if (!empty($filters['maMonHoc'])) {
            $sql .= " AND t.maMonHoc = ?";
            $params[] = $filters['maMonHoc'];
            $types .= "i";
        }
        
        // Filter by teacher
        if (!empty($filters['maGV'])) {
            $sql .= " AND t.maGV = ?";
            $params[] = $filters['maGV'];
            $types .= "i";
        }
        
        // Filter by date
        if (!empty($filters['thuNgay'])) {
            $sql .= " AND t.thuNgay = ?";
            $params[] = $filters['thuNgay'];
            $types .= "s";
        }
        
        // Filter by date range
        if (!empty($filters['tuNgay'])) {
            $sql .= " AND t.thuNgay >= ?";
            $params[] = $filters['tuNgay'];
            $types .= "s";
        }
        
        if (!empty($filters['denNgay'])) {
            $sql .= " AND t.thuNgay <= ?";
            $params[] = $filters['denNgay'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY t.thuNgay ASC, t.thoiGianHoc ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->connection->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $timetables = [];
        while ($row = $result->fetch_assoc()) {
            $timetables[] = $row;
        }
        
        $stmt->close();
        return $timetables;
    }

    /**
     * Đếm tổng số bản ghi thời khóa biểu
     * 
     * @param array $filters Điều kiện lọc
     * @return int Tổng số bản ghi
     */
    public function countTimetables($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM thoikhoabieu t WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['maLop'])) {
            $sql .= " AND t.maLop = ?";
            $params[] = $filters['maLop'];
            $types .= "i";
        }
        
        if (!empty($filters['maMonHoc'])) {
            $sql .= " AND t.maMonHoc = ?";
            $params[] = $filters['maMonHoc'];
            $types .= "i";
        }
        
        if (!empty($filters['maGV'])) {
            $sql .= " AND t.maGV = ?";
            $params[] = $filters['maGV'];
            $types .= "i";
        }
        
        if (!empty($filters['thuNgay'])) {
            $sql .= " AND t.thuNgay = ?";
            $params[] = $filters['thuNgay'];
            $types .= "s";
        }
        
        if (!empty($filters['tuNgay'])) {
            $sql .= " AND t.thuNgay >= ?";
            $params[] = $filters['tuNgay'];
            $types .= "s";
        }
        
        if (!empty($filters['denNgay'])) {
            $sql .= " AND t.thuNgay <= ?";
            $params[] = $filters['denNgay'];
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
     * Lấy thông tin 1 thời khóa biểu
     * 
     * @param int $maTKB Mã thời khóa biểu
     * @return array|null Thông tin thời khóa biểu
     */
    public function getTimetableById($maTKB)
    {
        $sql = "SELECT 
                    t.*,
                    l.tenLop,
                    k.khoiLop,
                    m.tenMonHoc,
                    g.hoTen AS tenGiaoVien
                FROM thoikhoabieu t
                LEFT JOIN lophoc l ON t.maLop = l.maLop
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN monhoc m ON t.maMonHoc = m.maMonHoc
                LEFT JOIN giaovien g ON t.maGV = g.maGV
                WHERE t.maTKB = ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maTKB);
        $stmt->execute();
        $result = $stmt->get_result();
        $timetable = $result->fetch_assoc();
        $stmt->close();
        
        return $timetable;
    }

    /**
     * Tạo thời khóa biểu mới
     * 
     * @param array $data Dữ liệu thời khóa biểu
     * @return int|false Mã TKB mới hoặc false nếu thất bại
     */
    public function createTimetable($data)
    {
        $columns = [];
        $placeholders = [];
        $params = [];
        $types = "";
        
        // Build dynamic SQL
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $columns[] = $key;
                $placeholders[] = '?';
                $params[] = $value;
                
                // Determine type
                if (in_array($key, ['maLop', 'maGV', 'maMonHoc', 'maTaiKhoan'])) {
                    $types .= 'i';
                } else if ($key === 'thoiGianHoc') {
                    $types .= 's'; // time
                } else {
                    $types .= 's';
                }
            }
        }
        
        if (empty($columns)) {
            return false;
        }
        
        $sql = "INSERT INTO thoikhoabieu (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $maTKB = $stmt->insert_id;
            $stmt->close();
            return $maTKB;
        }
        
        $stmt->close();
        return false;
    }

    /**
     * Cập nhật thời khóa biểu
     * 
     * @param int $maTKB Mã thời khóa biểu
     * @param array $data Dữ liệu cập nhật
     * @return bool Kết quả cập nhật
     */
    public function updateTimetable($maTKB, $data)
    {
        $updates = [];
        $params = [];
        $types = "";
        
        foreach ($data as $key => $value) {
            $updates[] = "$key = ?";
            $params[] = $value;
            
            if (in_array($key, ['maLop', 'maGV', 'maMonHoc', 'maTaiKhoan'])) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE thoikhoabieu SET " . implode(', ', $updates) . " WHERE maTKB = ?";
        $params[] = $maTKB;
        $types .= 'i';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Xóa thời khóa biểu
     * 
     * @param int $maTKB Mã thời khóa biểu
     * @return bool Kết quả xóa
     */
    public function deleteTimetable($maTKB)
    {
        $sql = "DELETE FROM thoikhoabieu WHERE maTKB = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maTKB);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Lấy danh sách tất cả lớp học
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
     * Lấy danh sách tất cả môn học
     * 
     * @return array Danh sách môn học
     */
    public function getAllSubjects()
    {
        $sql = "SELECT maMonHoc, tenMonHoc, soTiet 
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
     * Lấy danh sách tất cả giáo viên
     * 
     * @return array Danh sách giáo viên
     */
    public function getAllTeachers()
    {
        $sql = "SELECT maGV, hoTen, toBoMon 
                FROM giaovien 
                ORDER BY hoTen ASC";
        
        $result = $this->connection->query($sql);
        $teachers = [];
        
        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }
        
        return $teachers;
    }

    /**
     * Kiểm tra trùng lịch cho giáo viên
     * 
     * @param int $maGV Mã giáo viên
     * @param string $thuNgay Ngày học
     * @param string $tietHoc Tiết học
     * @param int|null $excludeMaTKB Loại trừ mã TKB (dùng khi update)
     * @return bool True nếu bị trùng
     */
    public function checkTeacherConflict($maGV, $thuNgay, $tietHoc, $excludeMaTKB = null)
    {
        $sql = "SELECT COUNT(*) as count FROM thoikhoabieu 
                WHERE maGV = ? AND thuNgay = ? AND tietHoc = ?";
        
        $params = [$maGV, $thuNgay, $tietHoc];
        $types = "iss";
        
        if ($excludeMaTKB !== null) {
            $sql .= " AND maTKB != ?";
            $params[] = $excludeMaTKB;
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

    /**
     * Kiểm tra trùng lịch cho lớp
     * 
     * @param int $maLop Mã lớp
     * @param string $thuNgay Ngày học
     * @param string $tietHoc Tiết học
     * @param int|null $excludeMaTKB Loại trừ mã TKB (dùng khi update)
     * @return bool True nếu bị trùng
     */
    public function checkClassConflict($maLop, $thuNgay, $tietHoc, $excludeMaTKB = null)
    {
        $sql = "SELECT COUNT(*) as count FROM thoikhoabieu 
                WHERE maLop = ? AND thuNgay = ? AND tietHoc = ?";
        
        $params = [$maLop, $thuNgay, $tietHoc];
        $types = "iss";
        
        if ($excludeMaTKB !== null) {
            $sql .= " AND maTKB != ?";
            $params[] = $excludeMaTKB;
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

    /**
     * Lấy thời khóa biểu theo tuần (từ thứ 2 đến thứ 7)
     * 
     * @param int $maLop Mã lớp
     * @param string $startDate Ngày bắt đầu tuần (thứ 2)
     * @return array Thời khóa biểu theo tuần
     */
    public function getWeeklyTimetable($maLop, $startDate)
    {
        // Calculate end date (Saturday)
        $endDate = date('Y-m-d', strtotime($startDate . ' +5 days'));
        
        $sql = "SELECT 
                    t.*,
                    m.tenMonHoc,
                    g.hoTen AS tenGiaoVien
                FROM thoikhoabieu t
                LEFT JOIN monhoc m ON t.maMonHoc = m.maMonHoc
                LEFT JOIN giaovien g ON t.maGV = g.maGV
                WHERE t.maLop = ? 
                AND t.thuNgay BETWEEN ? AND ?
                ORDER BY t.thuNgay ASC, t.thoiGianHoc ASC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("iss", $maLop, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $timetables = [];
        while ($row = $result->fetch_assoc()) {
            $timetables[] = $row;
        }
        
        $stmt->close();
        return $timetables;
    }
}
?>
