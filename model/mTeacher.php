<?php
include_once("mConnect.php");
include_once("mAccount.php");

class mTeacher
{
    private $conn;
    private $mAccount;

    public function __construct()
    {
        $db = new mConnect();
        $this->conn = $db->mConnect();
        $this->mAccount = new mAccount();
    }

    /* ===============================
       LẤY THÔNG TIN GIÁO VIÊN
    =============================== */
    public function getTeacherInfoByAccount($tenDangNhap)
    {
        $sql = "SELECT gv.*
                FROM giaovien gv
                JOIN taikhoan tk ON gv.maTaiKhoan = tk.maTaiKhoan
                WHERE tk.tenDangNhap = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $tenDangNhap);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    /* ===============================
       LẤY DANH SÁCH LỚP GIÁO VIÊN PHỤ TRÁCH
    =============================== */
    public function getClassListByTeacher($maGV)
    {
        $sql = "SELECT * FROM lophoc WHERE maGV = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    }

    /* ===============================
       LẤY DANH SÁCH LỚP CHI TIẾT (kèm thông tin đầy đủ từ lophoc)
    =============================== */
    public function getDetailedClassListByTeacher($maGV, $filters = [])
    {
        // Sử dụng view v_phancong_giangday để lấy dữ liệu phân công giảng dạy
        $sql = "SELECT DISTINCT
                    v.maLop,
                    v.tenLop,
                    v.khoiLop,
                    v.maKhoi,
                    v.tenMonHoc,
                    v.hocKy,
                    v.namHoc,
                    v.soTiet,
                    l.siSo,
                    COALESCE(p.tenPhong, 'Chưa xếp') AS tenPhong,
                    COALESCE(gvcn.hoTen, 'Chưa có') AS giaoVienChuNhiem,
                    v.soTiet AS soTietTrongTuan
                FROM v_phancong_giangday v
                LEFT JOIN lophoc l ON v.maLop = l.maLop
                LEFT JOIN phong p ON l.maPhong = p.maPhong
                LEFT JOIN giaovien gvcn ON l.maGV = gvcn.maGV
                WHERE v.maGV = ?";
        
        $params = [$maGV];
        $types = "i";
        
        // Thêm điều kiện filter
        if (!empty($filters['hocKy'])) {
            $sql .= " AND v.hocKy = ?";
            $params[] = $filters['hocKy'];
            $types .= "i";
        }
        
        if (!empty($filters['namHoc'])) {
            $sql .= " AND v.namHoc = ?";
            $params[] = $filters['namHoc'];
            $types .= "s";
        }
        
        if (!empty($filters['maKhoi'])) {
            $sql .= " AND v.maKhoi = ?";
            $params[] = $filters['maKhoi'];
            $types .= "i";
        }
        
        $sql .= " ORDER BY v.tenLop, v.tenMonHoc";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        
        return $data;
    }

    /* ===============================
       CÁC HÀM KHÁC (tạo, sửa, xóa)
    =============================== */
    public function createTeacher($data, $accountData)
    {
        if ($this->mAccount->accountExists($accountData['tenDangNhap'])) {
            return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
        }

        $this->conn->begin_transaction();
        try {
            $maTaiKhoan = $this->mAccount->createAccount(
                $accountData['tenDangNhap'],
                $accountData['matKhau'],
                $data['hoTen'],
                'giaovien',
                3003
            );

            $sql = "INSERT INTO giaovien (hoTen, ngaySinh, gioiTinh, email, soDienThoai, maTaiKhoan, toBoMon) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "sssssss",
                $data['hoTen'],
                $data['ngaySinh'],
                $data['gioiTinh'],
                $data['email'],
                $data['soDienThoai'],
                $maTaiKhoan,
                $data['toBoMon']
            );
            $stmt->execute();
            $maGV = $this->conn->insert_id;
            $stmt->close();

            $this->conn->commit();
            return ['success' => true, 'maGV' => $maGV, 'maTaiKhoan' => $maTaiKhoan];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateTeacher($maGV, $data)
    {
        $sql = "UPDATE giaovien SET hoTen=?, ngaySinh=?, gioiTinh=?, email=?, soDienThoai=?, toBoMon=? WHERE maGV=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssi", $data['hoTen'], $data['ngaySinh'], $data['gioiTinh'], $data['email'], $data['soDienThoai'], $data['toBoMon'], $maGV);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function deleteTeacher($maGV)
    {
        $this->conn->begin_transaction();
        try {
            $teacher = $this->getTeacherById($maGV);
            if (!$teacher) throw new Exception("Không tìm thấy giáo viên");

            $stmt = $this->conn->prepare("DELETE FROM giaovien WHERE maGV = ?");
            $stmt->bind_param("i", $maGV);
            $stmt->execute();
            $stmt->close();

            if (!empty($teacher['maTaiKhoan'])) {
                $this->mAccount->deleteAccount($teacher['maTaiKhoan']);
            }

            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getTeacherById($maGV)
    {
        $sql = "SELECT * FROM giaovien WHERE maGV = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /* ===============================
       LẤY DANH SÁCH KHỐI
    =============================== */
    public function getAllKhoi()
    {
        $sql = "SELECT maKhoi, khoiLop FROM khoi ORDER BY khoiLop ASC";
        $result = $this->conn->query($sql);
        
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    /* ===============================
       LẤY DANH SÁCH HỌC SINH THEO LỚP
    =============================== */
    public function getStudentsByClass($maLop, $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT hs.maHS, hs.hoTen, hs.ngaySinh, hs.gioiTinh, hs.diaChi, 
                       hs.trangThaiHocTap, l.tenLop, k.khoiLop
                FROM hocsinh hs
                JOIN lophoc l ON hs.maLop = l.maLop
                JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen ASC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("iii", $maLop, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        
        return $data;
    }

    /* ===============================
       ĐẾM TỔNG SỐ HỌC SINH THEO LỚP
    =============================== */
    public function countStudentsByClass($maLop)
    {
        $sql = "SELECT COUNT(*) as total FROM hocsinh WHERE maLop = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result['total'] ?? 0;
    }

    /* ===============================
       LẤY THÔNG TIN LỚP HỌC
    =============================== */
    public function getClassInfo($maLop)
    {
        $sql = "SELECT l.maLop, l.tenLop, l.siSo, l.namHoc, 
                       k.khoiLop, p.tenPhong,
                       COALESCE(gv.hoTen, 'Chưa có') AS giaoVienChuNhiem
                FROM lophoc l
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN phong p ON l.maPhong = p.maPhong
                LEFT JOIN giaovien gv ON l.maGV = gv.maGV
                WHERE l.maLop = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }
}
?>
