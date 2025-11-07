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
        $sql = "SELECT * FROM lop WHERE maGV = ?";
        $stmt = $this->conn->prepare($sql);
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
    public function getDetailedClassListByTeacher($maGV)
    {
        // Lấy dữ liệu từ lophoc + khoi + phong
        // JOIN với lichday để lấy môn học, tiết học, học kỳ
        $sql = "SELECT 
                    lh.maLop,
                    lh.tenLop,
                    lh.siSo,
                    lh.namHoc,
                    k.khoiLop,
                    ph.tenPhong,
                    COALESCE(mh.tenMonHoc, 'Chưa cập nhật') AS tenMonHoc,
                    COALESCE(ld.hocKy, 1) AS hocKy,
                    COALESCE((ld.tietKetThuc - ld.tietBatDau + 1), 0) AS soTietTrongTuan,
                    'Chưa cập nhật' AS giaoVienChuNhiem
                FROM lophoc lh
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                LEFT JOIN phong ph ON lh.maPhong = ph.maPhong
                LEFT JOIN lichday ld ON lh.maLop = ld.maLop AND lh.maGV = ld.maGV
                LEFT JOIN monhoc mh ON ld.maMonHoc = mh.maMonHoc
                WHERE lh.maGV = ?
                ORDER BY lh.tenLop";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            // Nếu query fail, log lỗi và trả về mảng rỗng
            error_log("SQL Error: " . $this->conn->error);
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
}
?>
