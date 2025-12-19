<?php
include_once("mConnect.php");
include_once("mAccount.php");

class mStudent
{
    private $conn;
    private $mAccount;

    public function __construct()
    {
        $db = new mConnect();
        $this->conn = $db->mConnect();
        $this->mAccount = new mAccount();
    }

    /** 
     * Lấy thông tin học sinh dựa vào tài khoản đăng nhập
     */
public function getStudentInfoByAccount($tenDangNhap)
{
    if (!$this->conn) {
        return null;
    }

    $sql = "SELECT hs.maHS, hs.hoTen, hs.ngaySinh, hs.gioiTinh, hs.diaChi, 
                   hs.trangThaiHocTap, hs.maLop, l.tenLop, k.khoiLop
            FROM hocsinh hs
            LEFT JOIN lophoc l ON hs.maLop = l.maLop
            LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
            JOIN taikhoan tk ON hs.maTaiKhoan = tk.maTaiKhoan
            WHERE tk.tenDangNhap = ? AND tk.trangThaiTaiKhoan = 1";
    
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $this->conn->error);
        return null;
    }

    $stmt->bind_param("s", $tenDangNhap);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("DEBUG getStudentInfoByAccount: No student found for tenDangNhap=" . $tenDangNhap);
        $stmt->close();
        return null;
    }

    $info = $result->fetch_assoc();
    error_log("DEBUG getStudentInfoByAccount: tenDangNhap=" . $tenDangNhap . " => maHS=" . $info['maHS'] . ", hoTen=" . $info['hoTen']);
    $stmt->close();
    return $info;
}

    /**
     * Lấy điểm trung bình của học sinh theo từng môn
     */
    public function getStudentGrades($maHS)
    {
        $sql = "SELECT mh.tenMonHoc, 
                       ROUND(AVG(bd.diem),2) AS diemTB
                FROM bangdiem bd
                JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                WHERE bd.maHS = ?
                GROUP BY mh.tenMonHoc";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Lấy thời khóa biểu của học sinh theo mã học sinh
     */
    public function getStudentSchedule($maHS)
    {
        if (!$this->conn || !$maHS) {
            return false;
        }

        // Lấy maLop từ maHS
        $sql = "SELECT maLop FROM hocsinh WHERE maHS = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return false;
        }
        
        $row = $result->fetch_assoc();
        $maLop = $row['maLop'];
        $stmt->close();
        
        if (!$maLop) {
            return false;
        }

        // Lấy thời khóa biểu từ bảng thoikhoabieu
        // Chuyển đổi thuNgay (date) thành thứ (2-6)
        $sql = "SELECT 
                    tkb.maTKB,
                    DAYOFWEEK(tkb.thuNgay) as thu,
                    tkb.tietHoc,
                    tkb.tenMonHoc,
                    tkb.gv as tenGiaoVien,
                    tkb.phong as tenPhong,
                    tkb.thuNgay,
                    mh.tenMonHoc as tenMonHocFull
                FROM thoikhoabieu tkb
                LEFT JOIN monhoc mh ON tkb.maMonHoc = mh.maMonHoc
                WHERE tkb.maLop = ?
                ORDER BY tkb.thuNgay, tkb.thoiGianHoc";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result;
    }

    /**
     * Tạo học sinh mới (kèm tài khoản)
     * @param array $data - Dữ liệu học sinh: hoTen, ngaySinh, gioiTinh, diaChi, maHocLuc, maHanhKiem, maPH, maLop
     * @param array $accountData - Dữ liệu tài khoản: tenDangNhap, matKhau
     * @return array - ['success' => bool, 'maHS' => int, 'maTaiKhoan' => int, 'message' => string]
     */
    public function createStudent($data, $accountData)
    {
        // Kiểm tra tài khoản đã tồn tại chưa
        if ($this->mAccount->accountExists($accountData['tenDangNhap'])) {
            return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
        }

        // Bắt đầu transaction
        $this->conn->begin_transaction();

        try {
            // 1. Tạo tài khoản
            $maTaiKhoan = $this->mAccount->createAccount(
                $accountData['tenDangNhap'],
                $accountData['matKhau'],
                $data['hoTen'],
                'hocsinh',
                3004 // maNhom học sinh
            );

            if (!$maTaiKhoan) {
                throw new Exception("Không thể tạo tài khoản");
            }

            // 2. Tạo học sinh với maTaiKhoan
            $sql = "INSERT INTO hocsinh (hoTen, ngaySinh, gioiTinh, diaChi, trangThaiHocTap, maHocLuc, maHanhKiem, maPH, maTaiKhoan, maLop) 
                    VALUES (?, ?, ?, ?, 'danghoc', ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param(
                "ssssiiii",
                $data['hoTen'],
                $data['ngaySinh'],
                $data['gioiTinh'],
                $data['diaChi'],
                $data['maHocLuc'],
                $data['maHanhKiem'],
                $data['maPH'],
                $maTaiKhoan,
                $data['maLop']
            );

            if (!$stmt->execute()) {
                throw new Exception("Không thể tạo học sinh: " . $stmt->error);
            }

            $maHS = $this->conn->insert_id;
            $stmt->close();

            // Commit transaction
            $this->conn->commit();

            return [
                'success' => true,
                'maHS' => $maHS,
                'maTaiKhoan' => $maTaiKhoan,
                'message' => 'Tạo học sinh thành công'
            ];

        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Cập nhật thông tin học sinh
     */
    public function updateStudent($maHS, $data)
    {
        $sql = "UPDATE hocsinh 
                SET hoTen = ?, ngaySinh = ?, gioiTinh = ?, diaChi = ?, maHocLuc = ?, maHanhKiem = ?, maPH = ?, maLop = ?
                WHERE maHS = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "ssssiiiii",
            $data['hoTen'],
            $data['ngaySinh'],
            $data['gioiTinh'],
            $data['diaChi'],
            $data['maHocLuc'],
            $data['maHanhKiem'],
            $data['maPH'],
            $data['maLop'],
            $maHS
        );

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Lấy thông tin học sinh theo maHS
     */
    public function getStudentById($maHS)
    {
        $sql = "SELECT hs.*, lh.tenLop, kh.khoiLop, tk.tenDangNhap, tk.trangThaiTaiKhoan,
                       ph.hoTen as tenPhuHuynh, ph.soDienThoai as sdtPhuHuynh,
                       gv.hoTen as tenGVCN
                FROM hocsinh hs
                LEFT JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi kh ON lh.maKhoi = kh.maKhoi
                LEFT JOIN taikhoan tk ON hs.maTaiKhoan = tk.maTaiKhoan
                LEFT JOIN phuhuynh ph ON hs.maPH = ph.maPH
                LEFT JOIN giaovien gv ON lh.maGV = gv.maGV
                WHERE hs.maHS = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Lấy danh sách tất cả học sinh
     */
    /**
     * Lấy danh sách tất cả học sinh với filter và phân trang
     */
    public function getAllStudents($filters = [], $page = 1, $limit = 20)
    {
        if (!$this->conn) {
            return [];
        }

        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT hs.*, lh.tenLop, kh.khoiLop, tk.tenDangNhap,
                       ph.hoTen as tenPhuHuynh, ph.soDienThoai as sdtPhuHuynh
                FROM hocsinh hs
                LEFT JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi kh ON lh.maKhoi = kh.maKhoi
                LEFT JOIN taikhoan tk ON hs.maTaiKhoan = tk.maTaiKhoan
                LEFT JOIN phuhuynh ph ON hs.maPH = ph.maPH
                WHERE 1=1";

        $params = [];
        $types = '';

        // Filter by name
        if (!empty($filters['hoTen'])) {
            $sql .= " AND hs.hoTen LIKE ?";
            $params[] = '%' . $filters['hoTen'] . '%';
            $types .= 's';
        }

        // Filter by class
        if (!empty($filters['maLop'])) {
            $sql .= " AND hs.maLop = ?";
            $params[] = intval($filters['maLop']);
            $types .= 'i';
        }

        // Filter by status
        if (!empty($filters['trangThaiHocTap'])) {
            $sql .= " AND hs.trangThaiHocTap = ?";
            $params[] = $filters['trangThaiHocTap'];
            $types .= 's';
        }

        // Filter by gender
        if (!empty($filters['gioiTinh'])) {
            $sql .= " AND hs.gioiTinh = ?";
            $params[] = $filters['gioiTinh'];
            $types .= 's';
        }

        $sql .= " ORDER BY hs.maHS DESC LIMIT ? OFFSET ?";
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
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        $stmt->close();
        return $students;
    }

    /**
     * Đếm tổng số học sinh
     */
    public function countStudents($filters = [])
    {
        if (!$this->conn) {
            return 0;
        }

        $sql = "SELECT COUNT(*) as total FROM hocsinh WHERE 1=1";
        
        $params = [];
        $types = '';

        if (!empty($filters['hoTen'])) {
            $sql .= " AND hoTen LIKE ?";
            $params[] = '%' . $filters['hoTen'] . '%';
            $types .= 's';
        }

        if (!empty($filters['maLop'])) {
            $sql .= " AND maLop = ?";
            $params[] = intval($filters['maLop']);
            $types .= 'i';
        }

        if (!empty($filters['trangThaiHocTap'])) {
            $sql .= " AND trangThaiHocTap = ?";
            $params[] = $filters['trangThaiHocTap'];
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
     * Xóa học sinh (cũng xóa tài khoản liên kết)
     */
    public function deleteStudent($maHS)
    {
        $this->conn->begin_transaction();

        try {
            // Lấy maTaiKhoan trước
            $student = $this->getStudentById($maHS);
            if (!$student) {
                throw new Exception("Không tìm thấy học sinh");
            }

            // Xóa học sinh
            $sql = "DELETE FROM hocsinh WHERE maHS = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $maHS);
            if (!$stmt->execute()) {
                throw new Exception("Không thể xóa học sinh");
            }
            $stmt->close();

            // Xóa tài khoản nếu có
            if ($student['maTaiKhoan']) {
                $this->mAccount->deleteAccount($student['maTaiKhoan']);
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Xóa học sinh thành công'];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Lấy danh sách năm học có trong bảng điểm của học sinh
     */
    public function getAvailableYears($maHS)
    {
        if (!$this->conn || !$maHS) {
            error_log("getAvailableYears: conn or maHS is null");
            return [];
        }

        $sql = "SELECT DISTINCT namHoc 
                FROM bangdiem 
                WHERE maHS = ? 
                ORDER BY namHoc DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("getAvailableYears prepare failed: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $years = [];
        while ($row = $result->fetch_assoc()) {
            $years[] = $row['namHoc'];
        }
        $stmt->close();
        
        error_log("getAvailableYears: Found " . count($years) . " years for maHS=$maHS");
        return $years;
    }

    /**
     * Lấy danh sách học kỳ theo năm học và mã học sinh
     * Luôn trả về [1, 2, 'canam'] bất kể có dữ liệu hay không
     */
    public function getAvailableSemesters($maHS, $namHoc)
    {
        // Luôn trả về học kỳ 1, 2 và cả năm
        return [1, 2, 'canam'];
    }

    /**
     * Lấy kết quả học tập chi tiết theo học kỳ và năm học
     */
    public function getDetailedGrades($maHS, $namHoc, $hocKy)
    {
        if (!$this->conn || !$maHS || !$namHoc || !$hocKy) {
            error_log("getDetailedGrades: Invalid parameters");
            return [];
        }

        $sql = "SELECT 
                    mh.tenMonHoc,
                    bd.diemTX1 as diemThuongXuyen1,
                    bd.diemTX2 as diemThuongXuyen2,
                    bd.diemTX3 as diemThuongXuyen3,
                    bd.diemTX4 as diemThuongXuyen4,
                    bd.diemGiuaKy,
                    bd.diemCuoiKy,
                    bd.tbDiem as diemTB
                FROM bangdiem bd
                INNER JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                WHERE bd.maHS = ? AND bd.namHoc = ? AND bd.hocKy = ?
                ORDER BY mh.tenMonHoc";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("getDetailedGrades prepare failed: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param("isi", $maHS, $namHoc, $hocKy);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
        $stmt->close();
        
        error_log("getDetailedGrades: Found " . count($grades) . " grades for maHS=$maHS, namHoc=$namHoc, hocKy=$hocKy");
        return $grades;
    }

    /**
     * Lấy điểm trung bình cả năm học theo môn
     * Trả về điểm TB học kỳ 1 và học kỳ 2 riêng biệt
     */
    public function getYearlyGrades($maHS, $namHoc)
    {
        if (!$this->conn || !$maHS || !$namHoc) {
            error_log("getYearlyGrades: Invalid parameters");
            return [];
        }

        // Lấy điểm TB của từng học kỳ
        $sql = "SELECT 
                    mh.tenMonHoc,
                    bd.hocKy,
                    bd.tbDiem as diemTB
                FROM bangdiem bd
                INNER JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                WHERE bd.maHS = ? AND bd.namHoc = ?
                ORDER BY mh.tenMonHoc, bd.hocKy";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("getYearlyGrades prepare failed: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param("is", $maHS, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Tổ chức dữ liệu theo môn học
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $tenMonHoc = $row['tenMonHoc'];
            $hocKy = $row['hocKy'];
            
            if (!isset($grades[$tenMonHoc])) {
                $grades[$tenMonHoc] = [
                    'tenMonHoc' => $tenMonHoc,
                    'hocKy1' => null,
                    'hocKy2' => null
                ];
            }
            
            if ($hocKy == 1) {
                $grades[$tenMonHoc]['hocKy1'] = $row['diemTB'];
            } else if ($hocKy == 2) {
                $grades[$tenMonHoc]['hocKy2'] = $row['diemTB'];
            }
        }
        $stmt->close();
        
        // Chuyển từ associative array sang indexed array
        $result = array_values($grades);
        
        error_log("getYearlyGrades: Found " . count($result) . " yearly grades for maHS=$maHS, namHoc=$namHoc");
        return $result;
    }

    /**
     * Ngắt kết nối
     */
    
    /**
     * Lấy danh sách tất cả các lớp học
     */
    public function getAllClasses()
    {
        if (!$this->conn) {
            return [];
        }

        $sql = "SELECT lh.maLop, lh.tenLop, k.khoiLop 
                FROM lophoc lh 
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi 
                ORDER BY k.khoiLop, lh.tenLop";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [];
        }
        
        $classes = [];
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
        
        return $classes;
    }

    /**
     * Lấy danh sách tất cả phụ huynh
     */
    public function getAllParents()
    {
        if (!$this->conn) {
            return [];
        }

        $sql = "SELECT maPH, hoTen, soDienThoai, email 
                FROM phuhuynh 
                ORDER BY hoTen";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [];
        }
        
        $parents = [];
        while ($row = $result->fetch_assoc()) {
            $parents[] = $row;
        }
        
        return $parents;
    }

    /**
     * Tìm phụ huynh theo số điện thoại
     */
    public function findParentByPhone($soDienThoai)
    {
        if (!$this->conn) {
            return null;
        }

        $sql = "SELECT maPH, hoTen, soDienThoai, email 
                FROM phuhuynh 
                WHERE soDienThoai = ?
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param('s', $soDienThoai);
        $stmt->execute();
        $result = $stmt->get_result();
        $parent = $result->fetch_assoc();
        $stmt->close();
        
        return $parent;
    }

    /**
     * Tạo phụ huynh mới
     */
    public function createParent($data)
    {
        if (!$this->conn) {
            return false;
        }

        $sql = "INSERT INTO phuhuynh (hoTen, soDienThoai) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('ss', $data['hoTen'], $data['soDienThoai']);

        if ($stmt->execute()) {
            $maPH = $stmt->insert_id;
            $stmt->close();
            return $maPH;
        }

        $stmt->close();
        return false;
    }

    /**
     * Cập nhật thông tin phụ huynh
     */
    public function updateParent($maPH, $data)
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

        if (isset($data['soDienThoai'])) {
            $updates[] = 'soDienThoai = ?';
            $params[] = $data['soDienThoai'];
            $types .= 's';
        }

        if (isset($data['email'])) {
            $updates[] = 'email = ?';
            $params[] = $data['email'];
            $types .= 's';
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $maPH;
        $types .= 'i';

        $sql = "UPDATE phuhuynh SET " . implode(', ', $updates) . " WHERE maPH = ?";
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
     * Tạo học sinh mới (cho admin - không cần tạo account)
     */
    public function createStudentSimple($data)
    {
        if (!$this->conn) {
            return false;
        }

        $columns = ['hoTen', 'ngaySinh', 'gioiTinh', 'trangThaiHocTap'];
        $values = [
            $data['hoTen'],
            $data['ngaySinh'],
            $data['gioiTinh'],
            $data['trangThaiHocTap'] ?? 'danghoc'
        ];
        $types = 'ssss';

        if (!empty($data['diaChi'])) {
            $columns[] = 'diaChi';
            $values[] = $data['diaChi'];
            $types .= 's';
        }

        if (!empty($data['maLop'])) {
            $columns[] = 'maLop';
            $values[] = intval($data['maLop']);
            $types .= 'i';
        }

        if (!empty($data['maPH'])) {
            $columns[] = 'maPH';
            $values[] = intval($data['maPH']);
            $types .= 'i';
        }

        if (!empty($data['maTaiKhoan'])) {
            $columns[] = 'maTaiKhoan';
            $values[] = intval($data['maTaiKhoan']);
            $types .= 'i';
        }

        $columnsList = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        $sql = "INSERT INTO hocsinh ($columnsList) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            $maHS = $stmt->insert_id;
            $stmt->close();
            return $maHS;
        }

        $stmt->close();
        return false;
    }

    /**
     * Lấy danh sách học sinh để cấp tài khoản (có thông tin tài khoản)
     */
    public function getStudentsForAccountAssignment($filters = [], $page = 1, $limit = 20)
    {
        if (!$this->conn) {
            return ['data' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'totalPages' => 0];
        }

        // Count total
        $countSql = "SELECT COUNT(*) as total 
                     FROM hocsinh hs
                     LEFT JOIN lophoc lh ON hs.maLop = lh.maLop
                     LEFT JOIN taikhoan tk ON hs.maTaiKhoan = tk.maTaiKhoan
                     WHERE 1=1";

        $params = [];
        $types = '';

        // Apply filters to count
        if (!empty($filters['maHocSinh'])) {
            $countSql .= " AND hs.maHS LIKE ?";
            $params[] = '%' . $filters['maHocSinh'] . '%';
            $types .= 's';
        }

        if (!empty($filters['tenHocSinh'])) {
            $countSql .= " AND hs.hoTen LIKE ?";
            $params[] = '%' . $filters['tenHocSinh'] . '%';
            $types .= 's';
        }

        if (!empty($filters['maLop'])) {
            $countSql .= " AND hs.maLop = ?";
            $params[] = intval($filters['maLop']);
            $types .= 'i';
        }

        if (!empty($filters['hasAccount'])) {
            if ($filters['hasAccount'] === 'no') {
                $countSql .= " AND hs.maTaiKhoan IS NULL";
            } else if ($filters['hasAccount'] === 'yes') {
                $countSql .= " AND hs.maTaiKhoan IS NOT NULL";
            }
        }

        $stmt = $this->conn->prepare($countSql);
        if (!$stmt) {
            return ['data' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'totalPages' => 0];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        $stmt->close();

        // Get data
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT hs.maHS, hs.hoTen as tenHocSinh, hs.ngaySinh, 
                       lh.tenLop, hs.maLop, hs.maTaiKhoan,
                       tk.tenDangNhap, tk.trangThaiTaiKhoan
                FROM hocsinh hs
                LEFT JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN taikhoan tk ON hs.maTaiKhoan = tk.maTaiKhoan
                WHERE 1=1";

        $params2 = [];
        $types2 = '';

        if (!empty($filters['maHocSinh'])) {
            $sql .= " AND hs.maHS LIKE ?";
            $params2[] = '%' . $filters['maHocSinh'] . '%';
            $types2 .= 's';
        }

        if (!empty($filters['tenHocSinh'])) {
            $sql .= " AND hs.hoTen LIKE ?";
            $params2[] = '%' . $filters['tenHocSinh'] . '%';
            $types2 .= 's';
        }

        if (!empty($filters['maLop'])) {
            $sql .= " AND hs.maLop = ?";
            $params2[] = intval($filters['maLop']);
            $types2 .= 'i';
        }

        if (!empty($filters['hasAccount'])) {
            if ($filters['hasAccount'] === 'no') {
                $sql .= " AND hs.maTaiKhoan IS NULL";
            } else if ($filters['hasAccount'] === 'yes') {
                $sql .= " AND hs.maTaiKhoan IS NOT NULL";
            }
        }

        $sql .= " ORDER BY hs.maHS DESC LIMIT ? OFFSET ?";
        $params2[] = $limit;
        $params2[] = $offset;
        $types2 .= 'ii';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['data' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'totalPages' => 0];
        }

        if (!empty($params2)) {
            $stmt->bind_param($types2, ...$params2);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        $stmt->close();

        $totalPages = ceil($total / $limit);

        return [
            'data' => $students,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages
        ];
    }

    /**
     * Lấy xếp loại học lực từ bảng hocluc
     */
    public function getHocLuc($maHS, $namHoc)
    {
        $sql = "SELECT diemTBHK1, diemTBHK2, diemTBCaNam, loaiHocLuc, soMonDuoi5, nhanXet
                FROM hocluc
                WHERE maHS = ? AND namHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("is", $maHS, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }
        
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }

    /**
     * Lấy xếp loại hạnh kiểm từ bảng hanhkiem
     */
    public function getHanhKiem($maHS, $hocKy, $namHoc)
    {
        $sql = "SELECT loaiHK, soBuoiNghiCoPhep, soBuoiNghiKhongPhep, 
                       soLanViPhamNhe, soLanViPhamTB, soLanViPhamNang, nhanXet
                FROM hanhkiem
                WHERE maHS = ? AND hocKy = ? AND namHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("iis", $maHS, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }
        
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }

    /**
     * Lấy danh hiệu của học sinh từ bảng hocsinh JOIN danhhieu
     */
    public function getDanhHieu($maHS)
    {
        $sql = "SELECT dh.maDanhHieu, dh.tenDanhHieu, dh.moTa
                FROM hocsinh hs
                LEFT JOIN danhhieu dh ON hs.maDanhHieu = dh.maDanhHieu
                WHERE hs.maHS = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }
        
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }

    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
