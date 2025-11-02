<?php
include_once("mConnect.php");

class mUser
{
    private $conn;

    public function __construct()
    {
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    /**
     * Đăng nhập người dùng (an toàn với prepared statements)
     * Hỗ trợ cả MD5 (legacy) và bcrypt (mới)
     * @param string $name - Tên đăng nhập
     * @param string $pass - Mật khẩu (plaintext)
     * @return array|false - Thông tin user hoặc false nếu sai
     */
    public function mLogin($name, $pass)
    {
        if (!$this->conn) {
            return false;
        }

        // Sử dụng prepared statement để tránh SQL injection
        $sql = "SELECT maTaiKhoan, tenDangNhap, matKhau, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom 
                FROM taikhoan 
                WHERE tenDangNhap = ? AND trangThaiTaiKhoan = 1";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return false; // User không tồn tại
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Kiểm tra mật khẩu
        $hashedPassword = $user['matKhau'];
        
        // Kiểm tra xem có phải MD5 không (32 ký tự hex)
        if (strlen($hashedPassword) === 32 && ctype_xdigit($hashedPassword)) {
            // Legacy MD5 password
            if (md5($pass) === $hashedPassword) {
                // Đăng nhập thành công với MD5 - nên migrate sang bcrypt
                unset($user['matKhau']); // Không trả về hash
                return $user;
            } else {
                return false; // Sai mật khẩu
            }
        } else {
            // Bcrypt password (mới)
            if (password_verify($pass, $hashedPassword)) {
                unset($user['matKhau']); // Không trả về hash
                return $user;
            } else {
                return false; // Sai mật khẩu
            }
        }
    }

    /**
     * Lấy thông tin user theo maTaiKhoan
     */
    public function getUserById($maTaiKhoan)
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
     * Lấy thông tin user theo tenDangNhap
     */
    public function getUserByUsername($tenDangNhap)
    {
        $sql = "SELECT maTaiKhoan, tenDangNhap, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom 
                FROM taikhoan WHERE tenDangNhap = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $tenDangNhap);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Lấy mã giáo viên từ maTaiKhoan
     * @param int $maTaiKhoan
     * @return int|null - maGV hoặc null nếu không phải giáo viên
     */
    public function getTeacherIdByAccountId($maTaiKhoan)
    {
        $sql = "SELECT maGV FROM giaovien WHERE maTaiKhoan = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $maTaiKhoan);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['maGV'];
        }
        
        $stmt->close();
        return null;
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