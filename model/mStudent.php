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
        $stmt->close();
        return null;
    }

    $info = $result->fetch_assoc();
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
                       ph.hoTen as tenPhuHuynh, ph.soDienThoai as sdtPhuHuynh
                FROM hocsinh hs
                LEFT JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi kh ON lh.maKhoi = kh.maKhoi
                LEFT JOIN taikhoan tk ON hs.maTaiKhoan = tk.maTaiKhoan
                LEFT JOIN phuhuynh ph ON hs.maPH = ph.maPH
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
    public function getAllStudents()
    {
        $sql = "SELECT hs.*, lh.tenLop, kh.khoiLop, tk.tenDangNhap
                FROM hocsinh hs
                LEFT JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi kh ON lh.maKhoi = kh.maKhoi
                LEFT JOIN taikhoan tk ON hs.maTaiKhoan = tk.maTaiKhoan
                ORDER BY hs.maHS DESC";
        
        $result = $this->conn->query($sql);
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        return $students;
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
                    MAX(CASE WHEN bd.loaiDiem = 'mieng' THEN bd.diem END) as diemMieng,
                    MAX(CASE WHEN bd.loaiDiem = '15phut' THEN bd.diem END) as diem15Phut,
                    MAX(CASE WHEN bd.loaiDiem = '1tiet' THEN bd.diem END) as diem1Tiet,
                    MAX(CASE WHEN bd.loaiDiem = 'giuaky' THEN bd.diem END) as diemGiuaKy,
                    MAX(CASE WHEN bd.loaiDiem = 'cuoiky' THEN bd.diem END) as diemCuoiKy,
                    ROUND(
                        (COALESCE(SUM(CASE WHEN bd.loaiDiem = 'mieng' THEN bd.diem * 1 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = '15phut' THEN bd.diem * 1 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = '1tiet' THEN bd.diem * 2 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = 'giuaky' THEN bd.diem * 2 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = 'cuoiky' THEN bd.diem * 3 END), 0)) /
                        (COALESCE(SUM(CASE WHEN bd.loaiDiem = 'mieng' THEN 1 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = '15phut' THEN 1 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = '1tiet' THEN 2 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = 'giuaky' THEN 2 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = 'cuoiky' THEN 3 END), 0) + 0.0001),
                    2) as diemTB
                FROM bangdiem bd
                INNER JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                WHERE bd.maHS = ? AND bd.namHoc = ? AND bd.hocKy = ?
                GROUP BY mh.maMonHoc, mh.tenMonHoc
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
                    ROUND(
                        (COALESCE(SUM(CASE WHEN bd.loaiDiem = 'mieng' THEN bd.diem * 1 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = '15phut' THEN bd.diem * 1 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = '1tiet' THEN bd.diem * 2 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = 'giuaky' THEN bd.diem * 2 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = 'cuoiky' THEN bd.diem * 3 END), 0)) /
                        (COALESCE(SUM(CASE WHEN bd.loaiDiem = 'mieng' THEN 1 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = '15phut' THEN 1 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = '1tiet' THEN 2 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = 'giuaky' THEN 2 END), 0) +
                         COALESCE(SUM(CASE WHEN bd.loaiDiem = 'cuoiky' THEN 3 END), 0) + 0.0001),
                    2) as diemTB
                FROM bangdiem bd
                INNER JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                WHERE bd.maHS = ? AND bd.namHoc = ?
                GROUP BY mh.maMonHoc, mh.tenMonHoc, bd.hocKy
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
            if (!isset($grades[$tenMonHoc])) {
                $grades[$tenMonHoc] = [
                    'tenMonHoc' => $tenMonHoc,
                    'hocKy1' => null,
                    'hocKy2' => null
                ];
            }
            
            if ($row['hocKy'] == 1) {
                $grades[$tenMonHoc]['hocKy1'] = $row['diemTB'];
            } else if ($row['hocKy'] == 2) {
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
    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
