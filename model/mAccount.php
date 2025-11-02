<?php
include_once("mConnect.php");

/**
 * Model xử lý tài khoản (taikhoan)
 * Tạo, cập nhật, xóa tài khoản người dùng
 */
class mAccount
{
    private $conn;

    public function __construct()
    {
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    /**
     * Tạo tài khoản mới
     * @param string $tenDangNhap - Tên đăng nhập (username)
     * @param string $matKhau - Mật khẩu (sẽ được hash bằng password_hash)
     * @param string $hoTen - Họ tên đầy đủ
     * @param string $loaiTaiKhoan - Loại: 'hocsinh','giaovien','phuhuynh','bangiamhieu','quantrivien'
     * @param int $maNhom - Mã nhóm người dùng (quyền hạn)
     * @return int|false - Trả về maTaiKhoan vừa tạo hoặc false nếu lỗi
     */
    public function createAccount($tenDangNhap, $matKhau, $hoTen, $loaiTaiKhoan, $maNhom = null)
    {
        // Hash mật khẩu bằng bcrypt (thay vì MD5)
        $hashedPassword = password_hash($matKhau, PASSWORD_BCRYPT);
        
        // Mặc định maNhom theo loaiTaiKhoan nếu không truyền
        if ($maNhom === null) {
            $maNhom = $this->getDefaultMaNhom($loaiTaiKhoan);
        }

        $sql = "INSERT INTO taikhoan (tenDangNhap, matKhau, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom) 
                VALUES (?, ?, ?, ?, 1, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("ssssi", $tenDangNhap, $hashedPassword, $hoTen, $loaiTaiKhoan, $maNhom);
        
        if ($stmt->execute()) {
            $maTaiKhoan = $this->conn->insert_id;
            $stmt->close();
            return $maTaiKhoan;
        } else {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Lấy maNhom mặc định theo loaiTaiKhoan
     */
    private function getDefaultMaNhom($loaiTaiKhoan)
    {
        $mapping = [
            'quantrivien' => 3001,
            'bangiamhieu' => 3002,
            'giaovien' => 3003,
            'hocsinh' => 3004,
            'phuhuynh' => 3005
        ];
        return $mapping[$loaiTaiKhoan] ?? 3003;
    }

    /**
     * Kiểm tra xem tài khoản đã tồn tại chưa
     */
    public function accountExists($tenDangNhap)
    {
        $sql = "SELECT maTaiKhoan FROM taikhoan WHERE tenDangNhap = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $tenDangNhap);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Cập nhật trạng thái tài khoản (kích hoạt/vô hiệu hóa)
     */
    public function updateAccountStatus($maTaiKhoan, $trangThai)
    {
        $sql = "UPDATE taikhoan SET trangThaiTaiKhoan = ? WHERE maTaiKhoan = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $trangThai, $maTaiKhoan);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Xóa tài khoản (soft delete hoặc hard delete)
     */
    public function deleteAccount($maTaiKhoan)
    {
        // Xóa thật (hard delete) - do FK constraints sẽ SET NULL tại các bảng liên quan
        $sql = "DELETE FROM taikhoan WHERE maTaiKhoan = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maTaiKhoan);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Lấy thông tin tài khoản theo maTaiKhoan
     */
    public function getAccountById($maTaiKhoan)
    {
        $sql = "SELECT maTaiKhoan, tenDangNhap, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom 
                FROM taikhoan WHERE maTaiKhoan = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maTaiKhoan);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Đổi mật khẩu tài khoản
     */
    public function changePassword($maTaiKhoan, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE taikhoan SET matKhau = ? WHERE maTaiKhoan = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $maTaiKhoan);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
