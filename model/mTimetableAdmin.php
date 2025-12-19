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
        
        try {
            // Check if new columns exist
            $columnCheck = $this->connection->query("SHOW COLUMNS FROM thoikhoabieu LIKE 'hocKy'");
            $hasNewColumns = ($columnCheck && $columnCheck->num_rows > 0);
            
            if ($hasNewColumns) {
                $sql = "SELECT 
                            t.maTKB,
                            COALESCE(t.hocKy, 1) as hocKy,
                            COALESCE(t.namHoc, '2024-2025') as namHoc,
                            COALESCE(t.day_of_week, DAYOFWEEK(t.thuNgay)) as day_of_week,
                            COALESCE(t.period, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(t.tietHoc, ' ', -1), '-', 1) AS UNSIGNED)) as period,
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
            } else {
                // Fallback for old schema - safe extraction
                $sql = "SELECT 
                            t.maTKB,
                            1 as hocKy,
                            '2024-2025' as namHoc,
                            IFNULL(DAYOFWEEK(t.thuNgay), 1) as day_of_week,
                            1 as period,
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
            }
        
        $params = [];
        $types = "";
        
        // Filter by grade (khoi) - via lophoc table
        if (!empty($filters['maKhoi'])) {
            $sql .= " AND k.khoiLop = ?";
            $params[] = $filters['maKhoi'];
            $types .= "i";
        }
        
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
        
        // Filter by hocKy/namHoc - only if columns exist
        if ($hasNewColumns) {
            if (!empty($filters['hocKy'])) {
                $sql .= " AND COALESCE(t.hocKy, 1) = ?";
                $params[] = $filters['hocKy'];
                $types .= "i";
            }
            
            if (!empty($filters['namHoc'])) {
                $sql .= " AND COALESCE(t.namHoc, '2024-2025') = ?";
                $params[] = $filters['namHoc'];
                $types .= "s";
            }
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
        
        // ORDER BY - use appropriate columns based on schema
        if ($hasNewColumns) {
            $sql .= " ORDER BY COALESCE(t.day_of_week, DAYOFWEEK(t.thuNgay)) ASC, COALESCE(t.period, 1) ASC, t.thuNgay ASC LIMIT ? OFFSET ?";
        } else {
            $sql .= " ORDER BY t.thuNgay ASC, t.thoiGianHoc ASC LIMIT ? OFFSET ?";
        }
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->connection->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $this->connection->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        $timetables = [];
        while ($row = $result->fetch_assoc()) {
            $timetables[] = $row;
        }
        
        $stmt->close();
        return $timetables;
        
        } catch (Exception $e) {
            error_log("Error in getAllTimetables: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Đếm tổng số bản ghi thời khóa biểu
     * 
     * @param array $filters Điều kiện lọc
     * @return int Tổng số bản ghi
     */
    public function countTimetables($filters = [])
    {
        // Check if new columns exist
        $columnCheck = $this->connection->query("SHOW COLUMNS FROM thoikhoabieu LIKE 'hocKy'");
        $hasNewColumns = ($columnCheck && $columnCheck->num_rows > 0);
        
        // Need JOIN with lophoc and khoi for grade filter
        $sql = "SELECT COUNT(*) as total 
                FROM thoikhoabieu t
                LEFT JOIN lophoc l ON t.maLop = l.maLop
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Filter by grade (khoi)
        if (!empty($filters['maKhoi'])) {
            $sql .= " AND k.khoiLop = ?";
            $params[] = $filters['maKhoi'];
            $types .= "i";
        }
        
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
        
        // Only filter by hocKy/namHoc if columns exist
        if ($hasNewColumns) {
            if (!empty($filters['hocKy'])) {
                $sql .= " AND COALESCE(t.hocKy, 1) = ?";
                $params[] = $filters['hocKy'];
                $types .= "i";
            }
            
            if (!empty($filters['namHoc'])) {
                $sql .= " AND COALESCE(t.namHoc, '2024-2025') = ?";
                $params[] = $filters['namHoc'];
                $types .= "s";
            }
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
        
        if ($stmt === false) {
            error_log("countTimetables prepare failed: " . $this->connection->error);
            return 0;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log("countTimetables execute failed: " . $stmt->error);
            $stmt->close();
            return 0;
        }
        
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
        // Check if new columns exist in database
        $columnCheck = $this->connection->query("SHOW COLUMNS FROM thoikhoabieu LIKE 'hocKy'");
        $hasNewColumns = ($columnCheck && $columnCheck->num_rows > 0);
        
        // Chuyển đổi thuNgay sang day_of_week nếu cần
        if (isset($data['thuNgay']) && !isset($data['day_of_week'])) {
            $data['day_of_week'] = date('N', strtotime($data['thuNgay'])); // 1=Monday, 7=Sunday
            // Chuyển sang 1=Sunday, 2=Monday,...,7=Saturday (theo MySQL DAYOFWEEK)
            $data['day_of_week'] = ($data['day_of_week'] % 7) + 1;
        }
        
        // Chuyển đổi tietHoc sang period nếu cần
        if (isset($data['tietHoc']) && !isset($data['period'])) {
            // Parse tietHoc: "Tiết 1", "Tiết 1-2", "1", "1-2"
            preg_match('/\d+/', $data['tietHoc'], $matches);
            if (!empty($matches)) {
                $data['period'] = (int)$matches[0];
            }
        }
        
        // Filter out new columns if they don't exist in database
        if (!$hasNewColumns) {
            error_log("createTimetable - Database doesn't have new columns, removing hocKy, namHoc, day_of_week, period from INSERT");
            unset($data['hocKy']);
            unset($data['namHoc']);
            unset($data['day_of_week']);
            unset($data['period']);
        }
        
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
                if (in_array($key, ['maLop', 'maGV', 'maMonHoc', 'maTaiKhoan', 'hocKy', 'day_of_week', 'period'])) {
                    $types .= 'i';
                } else if ($key === 'thoiGianHoc') {
                    $types .= 's'; // time
                } else {
                    $types .= 's';
                }
            }
        }
        
        if (empty($columns)) {
            error_log("createTimetable - No columns to insert");
            return false;
        }
        
        $sql = "INSERT INTO thoikhoabieu (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        error_log("createTimetable - SQL: $sql");
        error_log("createTimetable - Params: " . print_r($params, true));
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $maTKB = $stmt->insert_id;
            $stmt->close();
            
            // Populate redundant columns (tenMonHoc, lop, gv) from related tables
            $this->populateRedundantData($maTKB);
            
            return $maTKB;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Populate redundant columns in thoikhoabieu from related tables
     * 
     * @param int $maTKB Mã thời khóa biểu
     * @return bool Success status
     */
    private function populateRedundantData($maTKB)
    {
        // Check if these columns exist
        $tenMonHocCheck = $this->connection->query("SHOW COLUMNS FROM thoikhoabieu LIKE 'tenMonHoc'");
        $lopCheck = $this->connection->query("SHOW COLUMNS FROM thoikhoabieu LIKE 'lop'");
        $gvCheck = $this->connection->query("SHOW COLUMNS FROM thoikhoabieu LIKE 'gv'");
        $thuNgayCheck = $this->connection->query("SHOW COLUMNS FROM thoikhoabieu LIKE 'thuNgay'");
        
        if (!$tenMonHocCheck || $tenMonHocCheck->num_rows == 0) {
            // Columns don't exist, skip
            return true;
        }
        
        // Update with data from related tables
        $sql = "UPDATE thoikhoabieu t
                LEFT JOIN monhoc m ON t.maMonHoc = m.maMonHoc
                LEFT JOIN lophoc l ON t.maLop = l.maLop
                LEFT JOIN giaovien g ON t.maGV = g.maGV
                SET ";
        
        $updates = [];
        if ($tenMonHocCheck && $tenMonHocCheck->num_rows > 0) {
            $updates[] = "t.tenMonHoc = m.tenMonHoc";
        }
        if ($lopCheck && $lopCheck->num_rows > 0) {
            $updates[] = "t.lop = l.tenLop";
        }
        if ($gvCheck && $gvCheck->num_rows > 0) {
            $updates[] = "t.gv = g.hoTen";
        }
        
        // Set thuNgay to current date if NULL (as placeholder)
        if ($thuNgayCheck && $thuNgayCheck->num_rows > 0) {
            $updates[] = "t.thuNgay = COALESCE(t.thuNgay, CURDATE())";
        }
        
        if (empty($updates)) {
            return true;
        }
        
        $sql .= implode(', ', $updates) . " WHERE t.maTKB = ?";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            error_log("populateRedundantData - Prepare failed: " . $this->connection->error);
            return false;
        }
        
        $stmt->bind_param("i", $maTKB);
        $result = $stmt->execute();
        $stmt->close();
        
        error_log("populateRedundantData - Updated maTKB=$maTKB, result=" . ($result ? 'success' : 'failed'));
        
        return $result;
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
     * @param int $dayOfWeek Thứ trong tuần (1-7)
     * @param int $period Tiết học (1-10)
     * @param int $hocKy Học kỳ
     * @param string $namHoc Năm học
     * @param int|null $excludeMaTKB Loại trừ mã TKB (dùng khi update)
     * @return bool True nếu bị trùng
     */
    public function checkTeacherConflict($maGV, $dayOfWeek, $period, $hocKy, $namHoc, $excludeMaTKB = null)
    {
        // Check if new columns exist
        $columnCheck = $this->connection->query("SHOW COLUMNS FROM thoikhoabieu LIKE 'day_of_week'");
        $hasNewColumns = ($columnCheck && $columnCheck->num_rows > 0);
        
        if (!$hasNewColumns) {
            // Old schema - cannot check conflicts reliably, return false (allow)
            error_log("checkTeacherConflict - Old schema detected, skipping conflict check");
            return false;
        }
        
        $sql = "SELECT COUNT(*) as count FROM thoikhoabieu 
                WHERE maGV = ? AND day_of_week = ? AND period = ? 
                AND hocKy = ? AND namHoc = ?";
        
        $params = [$maGV, $dayOfWeek, $period, $hocKy, $namHoc];
        $types = "iiiss";
        
        if ($excludeMaTKB !== null) {
            $sql .= " AND maTKB != ?";
            $params[] = $excludeMaTKB;
            $types .= "i";
        }
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            error_log("checkTeacherConflict - Prepare failed: " . $this->connection->error);
            return false;
        }
        
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
     * @param int $dayOfWeek Thứ trong tuần (1-7)
     * @param int $period Tiết học (1-10)
     * @param int $hocKy Học kỳ
     * @param string $namHoc Năm học
     * @param int|null $excludeMaTKB Loại trừ mã TKB (dùng khi update)
     * @return bool True nếu bị trùng
     */
    public function checkClassConflict($maLop, $dayOfWeek, $period, $hocKy, $namHoc, $excludeMaTKB = null)
    {
        // Check if new columns exist
        $columnCheck = $this->connection->query("SHOW COLUMNS FROM thoikhoabieu LIKE 'day_of_week'");
        $hasNewColumns = ($columnCheck && $columnCheck->num_rows > 0);
        
        if (!$hasNewColumns) {
            // Old schema - cannot check conflicts reliably, return false (allow)
            error_log("checkClassConflict - Old schema detected, skipping conflict check");
            return false;
        }
        
        $sql = "SELECT COUNT(*) as count FROM thoikhoabieu 
                WHERE maLop = ? AND day_of_week = ? AND period = ? 
                AND hocKy = ? AND namHoc = ?";
        
        $params = [$maLop, $dayOfWeek, $period, $hocKy, $namHoc];
        $types = "iiiss";
        
        if ($excludeMaTKB !== null) {
            $sql .= " AND maTKB != ?";
            $params[] = $excludeMaTKB;
            $types .= "i";
        }
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            error_log("checkClassConflict - Prepare failed: " . $this->connection->error);
            return false;
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return intval($row['count']) > 0;
    }

    /**
     * Kiểm tra trùng lịch cho phòng học
     * 
     * @param string $phong Mã/tên phòng học
     * @param int $dayOfWeek Thứ trong tuần (1=CN, 2=T2,...,7=T7)
     * @param int $period Tiết học (1-10)
     * @param int $hocKy Học kỳ
     * @param string $namHoc Năm học
     * @param int|null $excludeMaTKB Loại trừ mã TKB (dùng khi update)
     * @return bool True nếu bị trùng
     */
    public function checkRoomConflict($phong, $dayOfWeek, $period, $hocKy, $namHoc, $excludeMaTKB = null)
    {
        if (empty($phong)) {
            return false;
        }
        
        // Check if new columns exist
        $columnCheck = $this->connection->query("SHOW COLUMNS FROM thoikhoabieu LIKE 'day_of_week'");
        $hasNewColumns = ($columnCheck && $columnCheck->num_rows > 0);
        
        if (!$hasNewColumns) {
            // Old schema - cannot check conflicts reliably, return false (allow)
            error_log("checkRoomConflict - Old schema detected, skipping conflict check");
            return false;
        }
        
        $sql = "SELECT COUNT(*) as count FROM thoikhoabieu 
                WHERE phong = ? AND day_of_week = ? AND period = ? 
                AND hocKy = ? AND namHoc = ?";
        
        $params = [$phong, $dayOfWeek, $period, $hocKy, $namHoc];
        $types = "siiss";
        
        if ($excludeMaTKB !== null) {
            $sql .= " AND maTKB != ?";
            $params[] = $excludeMaTKB;
            $types .= "i";
        }
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            error_log("checkRoomConflict - Prepare failed: " . $this->connection->error);
            return false;
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return intval($row['count']) > 0;
    }

    /**
     * Kiểm tra trạng thái phòng học
     * 
     * @param string $phong Mã/tên phòng học
     * @return string|null 'AVAILABLE', 'MAINTENANCE', hoặc null nếu không tìm thấy
     */
    public function checkRoomStatus($phong)
    {
        if (empty($phong)) {
            return null;
        }
        
        $sql = "SELECT trangThai FROM phong WHERE tenPhong = ? OR maPhong = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("ss", $phong, $phong);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['trangThai'];
        }
        
        $stmt->close();
        return 'AVAILABLE'; // Mặc định nếu không tìm thấy trong bảng phòng
    }

    /**
     * Kiểm tra phân công giảng dạy hợp lệ
     * 
     * @param int $maLop Mã lớp
     * @param int $maMonHoc Mã môn học
     * @param int $maGV Mã giáo viên
     * @param int $hocKy Học kỳ
     * @param string $namHoc Năm học
     * @return bool True nếu tồn tại phân công hợp lệ
     */
    public function checkTeachingAssignment($maLop, $maMonHoc, $maGV, $hocKy, $namHoc)
    {
        // Check if lichday has hocKy and namHoc columns
        $hocKyCheck = $this->connection->query("SHOW COLUMNS FROM lichday LIKE 'hocKy'");
        $namHocCheck = $this->connection->query("SHOW COLUMNS FROM lichday LIKE 'namHoc'");
        
        $hasHocKy = ($hocKyCheck && $hocKyCheck->num_rows > 0);
        $hasNamHoc = ($namHocCheck && $namHocCheck->num_rows > 0);
        
        if ($hasHocKy && $hasNamHoc) {
            // Full check with semester
            $sql = "SELECT COUNT(*) as count FROM lichday 
                    WHERE maLop = ? AND maMonHoc = ? AND maGV = ? 
                    AND hocKy = ? AND namHoc = ?";
            
            // Check if trangThai column exists
            $statusCheck = $this->connection->query("SHOW COLUMNS FROM lichday LIKE 'trangThai'");
            if ($statusCheck && $statusCheck->num_rows > 0) {
                $sql .= " AND trangThai = 'active'";
            }
            
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                error_log("checkTeachingAssignment - Prepare failed: " . $this->connection->error);
                return false;
            }
            
            $stmt->bind_param("iiiis", $maLop, $maMonHoc, $maGV, $hocKy, $namHoc);
        } else {
            // Fallback: check without semester (less strict)
            error_log("checkTeachingAssignment - Using fallback query (no hocKy/namHoc in lichday)");
            $sql = "SELECT COUNT(*) as count FROM lichday 
                    WHERE maLop = ? AND maMonHoc = ? AND maGV = ?";
            
            // Check if trangThai column exists
            $statusCheck = $this->connection->query("SHOW COLUMNS FROM lichday LIKE 'trangThai'");
            if ($statusCheck && $statusCheck->num_rows > 0) {
                $sql .= " AND trangThai = 'active'";
            }
            
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                error_log("checkTeachingAssignment - Prepare failed: " . $this->connection->error);
                return false;
            }
            
            $stmt->bind_param("iii", $maLop, $maMonHoc, $maGV);
        }
        
        if (!$stmt->execute()) {
            error_log("checkTeachingAssignment - Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $count = intval($row['count']);
        error_log("checkTeachingAssignment - Found $count assignments");
        
        return $count > 0;
    }

    /**
     * Lấy danh sách phòng học khả dụng
     * 
     * @param int $dayOfWeek Thứ trong tuần
     * @param int $period Tiết học
     * @param int $hocKy Học kỳ
     * @param string $namHoc Năm học
     * @return array Danh sách phòng khả dụng
     */
    public function getAvailableRooms($dayOfWeek, $period, $hocKy, $namHoc)
    {
        // Check if new columns exist
        $columnCheck = $this->connection->query("SHOW COLUMNS FROM thoikhoabieu LIKE 'hocKy'");
        $hasNewColumns = ($columnCheck && $columnCheck->num_rows > 0);
        
        // Lấy tất cả phòng - check columns first
        $roomColumns = "p.maPhong, p.tenPhong";
        
        // Check optional columns
        $loaiPhongCheck = $this->connection->query("SHOW COLUMNS FROM phong LIKE 'loaiPhong'");
        if ($loaiPhongCheck && $loaiPhongCheck->num_rows > 0) {
            $roomColumns .= ", p.loaiPhong";
        } else {
            $roomColumns .= ", NULL as loaiPhong";
        }
        
        $sucChuaCheck = $this->connection->query("SHOW COLUMNS FROM phong LIKE 'sucChua'");
        if ($sucChuaCheck && $sucChuaCheck->num_rows > 0) {
            $roomColumns .= ", p.sucChua";
        } else {
            // Try soLuongSV instead
            $soLuongCheck = $this->connection->query("SHOW COLUMNS FROM phong LIKE 'soLuongSV'");
            if ($soLuongCheck && $soLuongCheck->num_rows > 0) {
                $roomColumns .= ", p.soLuongSV as sucChua";
            } else {
                $roomColumns .= ", NULL as sucChua";
            }
        }
        
        $trangThaiCheck = $this->connection->query("SHOW COLUMNS FROM phong LIKE 'trangThai'");
        if ($trangThaiCheck && $trangThaiCheck->num_rows > 0) {
            $roomColumns .= ", p.trangThai";
        } else {
            $roomColumns .= ", 'AVAILABLE' as trangThai";
        }
        
        $sql = "SELECT {$roomColumns}
                FROM phong p
                WHERE 1=1";
        
        // Filter by status if column exists
        if ($trangThaiCheck && $trangThaiCheck->num_rows > 0) {
            $sql .= " AND p.trangThai != 'MAINTENANCE'";
        }
        
        if ($hasNewColumns) {
            // Use new schema
            $sql .= " AND p.maPhong NOT IN (
                        SELECT DISTINCT CAST(t.phong AS UNSIGNED) 
                        FROM thoikhoabieu t
                        WHERE t.day_of_week = ? 
                        AND t.period = ?
                        AND t.hocKy = ?
                        AND t.namHoc = ?
                        AND t.phong IS NOT NULL
                        AND t.phong != ''
                    )";
            $sql .= " ORDER BY p.tenPhong ASC";
            
            $stmt = $this->connection->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("iiis", $dayOfWeek, $period, $hocKy, $namHoc);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $rooms = [];
                while ($row = $result->fetch_assoc()) {
                    $rooms[] = $row;
                }
                
                $stmt->close();
                return $rooms;
            }
        }
        
        // Fallback: return all rooms for old schema (can't filter by time slot)
        $sql .= " ORDER BY p.tenPhong ASC";
        $stmt = $this->connection->prepare($sql);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            
            $rooms = [];
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }
            
            $stmt->close();
            return $rooms;
        }
        
        return [];
    }

    /**
     * Validate day_of_week và period
     * 
     * @param int $dayOfWeek Thứ trong tuần (1-7)
     * @param int $period Tiết học (1-10)
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateTimeSlot($dayOfWeek, $period)
    {
        if ($dayOfWeek < 1 || $dayOfWeek > 7) {
            return [
                'valid' => false,
                'message' => 'Thứ trong tuần phải từ 1 (Chủ nhật) đến 7 (Thứ bảy)'
            ];
        }
        
        if ($period < 1 || $period > 10) {
            return [
                'valid' => false,
                'message' => 'Tiết học phải từ 1 đến 10'
            ];
        }
        
        return ['valid' => true, 'message' => ''];
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
