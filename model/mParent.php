<?php
include_once("mConnect.php");
include_once("mAccount.php");

/**
 * Model xử lý phụ huynh (phuhuynh)
 */
class mParent
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
     * Tạo phụ huynh mới (kèm tài khoản)
     * @param array $data - Dữ liệu phụ huynh: hoTen, soDienThoai, email, diaChi
     * @param array $accountData - Dữ liệu tài khoản: tenDangNhap, matKhau
     * @return array - ['success' => bool, 'maPH' => int, 'maTaiKhoan' => int, 'message' => string]
     */
    public function createParent($data, $accountData)
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
                'phuhuynh',
                3005 // maNhom phụ huynh
            );

            if (!$maTaiKhoan) {
                throw new Exception("Không thể tạo tài khoản");
            }

            // 2. Tạo phụ huynh với maTaiKhoan
            $sql = "INSERT INTO phuhuynh (hoTen, soDienThoai, email, maTaiKhoan, diaChi) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param(
                "sssis",
                $data['hoTen'],
                $data['soDienThoai'],
                $data['email'],
                $maTaiKhoan,
                $data['diaChi']
            );

            if (!$stmt->execute()) {
                throw new Exception("Không thể tạo phụ huynh: " . $stmt->error);
            }

            $maPH = $this->conn->insert_id;
            $stmt->close();

            // Commit transaction
            $this->conn->commit();

            return [
                'success' => true,
                'maPH' => $maPH,
                'maTaiKhoan' => $maTaiKhoan,
                'message' => 'Tạo phụ huynh thành công'
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
     * Cập nhật thông tin phụ huynh
     */
    public function updateParent($maPH, $data)
    {
        $sql = "UPDATE phuhuynh 
                SET hoTen = ?, soDienThoai = ?, email = ?, diaChi = ?
                WHERE maPH = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "ssssi",
            $data['hoTen'],
            $data['soDienThoai'],
            $data['email'],
            $data['diaChi'],
            $maPH
        );

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Lấy thông tin phụ huynh theo maPH
     */
    public function getParentById($maPH)
    {
        $sql = "SELECT ph.*, tk.tenDangNhap, tk.trangThaiTaiKhoan 
                FROM phuhuynh ph
                LEFT JOIN taikhoan tk ON ph.maTaiKhoan = tk.maTaiKhoan
                WHERE ph.maPH = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maPH);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Lấy danh sách tất cả phụ huynh
     */
    public function getAllParents()
    {
        $sql = "SELECT ph.*, tk.tenDangNhap, tk.trangThaiTaiKhoan 
                FROM phuhuynh ph
                LEFT JOIN taikhoan tk ON ph.maTaiKhoan = tk.maTaiKhoan
                ORDER BY ph.maPH DESC";
        
        $result = $this->conn->query($sql);
        $parents = [];
        while ($row = $result->fetch_assoc()) {
            $parents[] = $row;
        }
        return $parents;
    }

    /**
     * Lấy danh sách học sinh của phụ huynh
     */
    public function getStudentsByParent($maPH)
    {
        $sql = "SELECT hs.maHS, hs.hoTen, hs.ngaySinh, lh.tenLop, kh.khoiLop
                FROM hocsinh hs
                LEFT JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi kh ON lh.maKhoi = kh.maKhoi
                WHERE hs.maPH = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maPH);
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
     * Xóa phụ huynh (cũng xóa tài khoản liên kết)
     */
    public function deleteParent($maPH)
    {
        $this->conn->begin_transaction();

        try {
            // Lấy maTaiKhoan trước
            $parent = $this->getParentById($maPH);
            if (!$parent) {
                throw new Exception("Không tìm thấy phụ huynh");
            }

            // Xóa phụ huynh
            $sql = "DELETE FROM phuhuynh WHERE maPH = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $maPH);
            if (!$stmt->execute()) {
                throw new Exception("Không thể xóa phụ huynh");
            }
            $stmt->close();

            // Xóa tài khoản nếu có
            if ($parent['maTaiKhoan']) {
                $this->mAccount->deleteAccount($parent['maTaiKhoan']);
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Xóa phụ huynh thành công'];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
