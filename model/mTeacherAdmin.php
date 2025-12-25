<?php
/**
 * Model: Teacher Management Extension
 * Purpose: Thêm các methods cho quản lý giáo viên (admin)
 */

require_once(__DIR__ . '/mConnect.php');

class mTeacherAdmin
{
    private $conn;

    public function __construct()
    {
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    /**
     * Lấy danh sách giáo viên với filter và phân trang
     */
    public function getAllTeachers($filters = [], $page = 1, $limit = 20)
    {
        if (!$this->conn) {
            return [];
        }

        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT gv.*, tk.tenDangNhap
                FROM giaovien gv
                LEFT JOIN taikhoan tk ON gv.maTaiKhoan = tk.maTaiKhoan
                WHERE 1=1";

        $params = [];
        $types = '';

        // Filter by name
        if (!empty($filters['hoTen'])) {
            $sql .= " AND gv.hoTen LIKE ?";
            $params[] = '%' . $filters['hoTen'] . '%';
            $types .= 's';
        }

        // Filter by toBoMon
        if (!empty($filters['toBoMon'])) {
            $sql .= " AND gv.toBoMon = ?";
            $params[] = $filters['toBoMon'];
            $types .= 's';
        }

        // Filter by gender
        if (!empty($filters['gioiTinh'])) {
            $sql .= " AND gv.gioiTinh = ?";
            $params[] = $filters['gioiTinh'];
            $types .= 's';
        }

        $sql .= " ORDER BY gv.maGV DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
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
     * Đếm tổng số giáo viên
     */
    public function countTeachers($filters = [])
    {
        if (!$this->conn) {
            return 0;
        }

        $sql = "SELECT COUNT(*) as total FROM giaovien WHERE 1=1";
        
        $params = [];
        $types = '';

        if (!empty($filters['hoTen'])) {
            $sql .= " AND hoTen LIKE ?";
            $params[] = '%' . $filters['hoTen'] . '%';
            $types .= 's';
        }

        if (!empty($filters['toBoMon'])) {
            $sql .= " AND toBoMon = ?";
            $params[] = $filters['toBoMon'];
            $types .= 's';
        }

        if (!empty($filters['gioiTinh'])) {
            $sql .= " AND gioiTinh = ?";
            $params[] = $filters['gioiTinh'];
            $types .= 's';
        }

        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return 0;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['total'];
    }

    /**
     * Lấy thông tin chi tiết 1 giáo viên
     */
    public function getTeacherById($maGV)
    {
        if (!$this->conn) {
            return null;
        }

        $sql = "SELECT gv.*, tk.tenDangNhap,
                GROUP_CONCAT(lh.tenLop ORDER BY lh.tenLop SEPARATOR ', ') as lopChuNhiem
                FROM giaovien gv
                LEFT JOIN taikhoan tk ON gv.maTaiKhoan = tk.maTaiKhoan
                LEFT JOIN lophoc lh ON gv.maGV = lh.maGV
                WHERE gv.maGV = ?
                GROUP BY gv.maGV";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param('i', $maGV);
        $stmt->execute();
        $result = $stmt->get_result();
        $teacher = $result->fetch_assoc();
        $stmt->close();
        
        return $teacher;
    }

    /**
     * Tạo giáo viên mới
     */
    public function createTeacher($data)
    {
        if (!$this->conn) {
            return false;
        }

        $columns = ['hoTen'];
        $values = [$data['hoTen']];
        $types = 's';

        if (!empty($data['ngaySinh'])) {
            $columns[] = 'ngaySinh';
            $values[] = $data['ngaySinh'];
            $types .= 's';
        }

        if (!empty($data['gioiTinh'])) {
            $columns[] = 'gioiTinh';
            $values[] = $data['gioiTinh'];
            $types .= 's';
        }

        if (!empty($data['email'])) {
            $columns[] = 'email';
            $values[] = $data['email'];
            $types .= 's';
        }

        if (!empty($data['soDienThoai'])) {
            $columns[] = 'soDienThoai';
            $values[] = $data['soDienThoai'];
            $types .= 's';
        }

        if (!empty($data['toBoMon'])) {
            $columns[] = 'toBoMon';
            $values[] = $data['toBoMon'];
            $types .= 's';
        }

        if (!empty($data['maTaiKhoan'])) {
            $columns[] = 'maTaiKhoan';
            $values[] = intval($data['maTaiKhoan']);
            $types .= 'i';
        }

        $columnsList = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        $sql = "INSERT INTO giaovien ($columnsList) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            $maGV = $stmt->insert_id;
            $stmt->close();
            return $maGV;
        }

        $stmt->close();
        return false;
    }

    /**
     * Cập nhật thông tin giáo viên
     */
    public function updateTeacher($maGV, $data)
    {
        if (!$this->conn) {
            return false;
        }

        $updates = [];
        $params = [];
        $types = '';

        if (isset($data['hoTen'])) {
            $updates[] = 'hoTen = ?';
            $params[] = $data['hoTen'];
            $types .= 's';
        }

        if (isset($data['ngaySinh'])) {
            $updates[] = 'ngaySinh = ?';
            $params[] = $data['ngaySinh'];
            $types .= 's';
        }

        if (isset($data['gioiTinh'])) {
            $updates[] = 'gioiTinh = ?';
            $params[] = $data['gioiTinh'];
            $types .= 's';
        }

        if (isset($data['email'])) {
            $updates[] = 'email = ?';
            $params[] = $data['email'];
            $types .= 's';
        }

        if (isset($data['soDienThoai'])) {
            $updates[] = 'soDienThoai = ?';
            $params[] = $data['soDienThoai'];
            $types .= 's';
        }

        if (isset($data['toBoMon'])) {
            $updates[] = 'toBoMon = ?';
            $params[] = $data['toBoMon'];
            $types .= 's';
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $maGV;
        $types .= 'i';

        $sql = "UPDATE giaovien SET " . implode(', ', $updates) . " WHERE maGV = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param($types, ...$params);
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Xóa giáo viên
     */
    public function deleteTeacher($maGV)
    {
        if (!$this->conn) {
            return false;
        }

        $sql = "DELETE FROM giaovien WHERE maGV = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('i', $maGV);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Lấy danh sách tổ bộ môn
     */
    public function getAllDepartments()
    {
        if (!$this->conn) {
            return [];
        }

        $sql = "SELECT DISTINCT toBoMon FROM giaovien WHERE toBoMon IS NOT NULL ORDER BY toBoMon";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [];
        }
        
        $departments = [];
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row['toBoMon'];
        }
        
        return $departments;
    }

    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
