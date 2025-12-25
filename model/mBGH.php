<?php
include_once("mConnect.php");
include_once("mAccount.php");

/**
 * Model xử lý Ban giám hiệu (bgh)
 */
class mBGH
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
     * Tạo BGH mới (kèm tài khoản)
     * @param array $data - Dữ liệu BGH: hoTen, ngaySinh, gioiTinh, email, soDienThoai
     * @param array $accountData - Dữ liệu tài khoản: tenDangNhap, matKhau
     * @return array - ['success' => bool, 'maBGH' => int, 'maTaiKhoan' => int, 'message' => string]
     */
    public function createBGH($data, $accountData)
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
                'bangiamhieu',
                3005 // maNhom ban giám hiệu
            );

            if (!$maTaiKhoan) {
                throw new Exception("Không thể tạo tài khoản");
            }

            // 2. Tạo BGH với maTaiKhoan
            $sql = "INSERT INTO bgh (hoTen, ngaySinh, gioiTinh, email, soDienThoai, maTaiKhoan) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param(
                "sssssi",
                $data['hoTen'],
                $data['ngaySinh'],
                $data['gioiTinh'],
                $data['email'],
                $data['soDienThoai'],
                $maTaiKhoan
            );

            if (!$stmt->execute()) {
                throw new Exception("Không thể tạo BGH: " . $stmt->error);
            }

            $maBGH = $this->conn->insert_id;
            $stmt->close();

            // Commit transaction
            $this->conn->commit();

            return [
                'success' => true,
                'maBGH' => $maBGH,
                'maTaiKhoan' => $maTaiKhoan,
                'message' => 'Tạo Ban giám hiệu thành công'
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
     * Cập nhật thông tin BGH
     */
    public function updateBGH($maBGH, $data)
    {
        $sql = "UPDATE bgh SET 
                hoTen = ?,
                ngaySinh = ?,
                gioiTinh = ?,
                email = ?,
                soDienThoai = ?
                WHERE maBGH = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }

        $stmt->bind_param(
            "sssssi",
            $data['hoTen'],
            $data['ngaySinh'],
            $data['gioiTinh'],
            $data['email'],
            $data['soDienThoai'],
            $maBGH
        );

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Cập nhật BGH thành công'];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Lỗi: ' . $error];
        }
    }

    /**
     * Xóa BGH
     */
    public function deleteBGH($maBGH)
    {
        // Lấy maTaiKhoan trước khi xóa
        $sql = "SELECT maTaiKhoan FROM bgh WHERE maBGH = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBGH);
        $stmt->execute();
        $result = $stmt->get_result();
        $bgh = $result->fetch_assoc();
        $stmt->close();

        if (!$bgh) {
            return ['success' => false, 'message' => 'BGH không tồn tại'];
        }

        $this->conn->begin_transaction();

        try {
            // 1. Xóa BGH
            $sql = "DELETE FROM bgh WHERE maBGH = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $maBGH);
            
            if (!$stmt->execute()) {
                throw new Exception("Không thể xóa BGH");
            }
            $stmt->close();

            // 2. Xóa tài khoản
            if ($bgh['maTaiKhoan']) {
                $this->mAccount->deleteAccount($bgh['maTaiKhoan']);
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Xóa BGH thành công'];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Lấy thông tin BGH theo maBGH
     */
    public function getBGHById($maBGH)
    {
        $sql = "SELECT b.*, tk.tenDangNhap, tk.trangThaiTaiKhoan 
                FROM bgh b
                LEFT JOIN taikhoan tk ON b.maTaiKhoan = tk.maTaiKhoan
                WHERE b.maBGH = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBGH);
        $stmt->execute();
        $result = $stmt->get_result();
        $bgh = $result->fetch_assoc();
        $stmt->close();

        return $bgh;
    }

    /**
     * Lấy danh sách tất cả BGH
     */
    public function getAllBGH()
    {
        $sql = "SELECT b.*, tk.tenDangNhap, tk.trangThaiTaiKhoan 
                FROM bgh b
                LEFT JOIN taikhoan tk ON b.maTaiKhoan = tk.maTaiKhoan
                ORDER BY b.hoTen";
        
        $result = $this->conn->query($sql);
        $bghList = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $bghList[] = $row;
            }
        }
        
        return $bghList;
    }

    /**
     * Kiểm tra BGH có tồn tại không
     */
    public function bghExists($maBGH)
    {
        $sql = "SELECT COUNT(*) as count FROM bgh WHERE maBGH = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBGH);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] > 0;
    }
}
?>
