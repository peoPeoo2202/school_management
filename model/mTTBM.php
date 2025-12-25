<?php
include_once("mConnect.php");
include_once("mAccount.php");

/**
 * Model xử lý Tổ Trưởng Bộ Môn (ttbm)
 */
class mTTBM
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
     * Tạo TTBM mới (kèm tài khoản)
     * Lưu ý: TTBM phải là giáo viên (có maGV)
     * @param int $maGV - Mã giáo viên
     * @param array $accountData - Dữ liệu tài khoản: tenDangNhap, matKhau
     * @return array - ['success' => bool, 'maTTBM' => int, 'maTaiKhoan' => int, 'message' => string]
     */
    public function createTTBM($maGV, $accountData)
    {
        // Kiểm tra giáo viên tồn tại
        $checkGV = $this->conn->prepare("SELECT hoTen FROM giaovien WHERE maGV = ?");
        $checkGV->bind_param("i", $maGV);
        $checkGV->execute();
        $gvResult = $checkGV->get_result();
        
        if ($gvResult->num_rows === 0) {
            return ['success' => false, 'message' => 'Giáo viên không tồn tại'];
        }
        
        $gvData = $gvResult->fetch_assoc();
        $checkGV->close();

        // Kiểm tra tài khoản đã tồn tại chưa
        if ($this->mAccount->accountExists($accountData['tenDangNhap'])) {
            return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
        }

        // Bắt đầu transaction
        $this->conn->begin_transaction();

        try {
            // 1. Tạo tài khoản (với loại giaovien, nhóm TTBM)
            $maTaiKhoan = $this->mAccount->createAccount(
                $accountData['tenDangNhap'],
                $accountData['matKhau'],
                $gvData['hoTen'],
                'giaovien',
                3007 // maNhom TTBM
            );

            if (!$maTaiKhoan) {
                throw new Exception("Không thể tạo tài khoản");
            }

            // 2. Tạo TTBM với maTaiKhoan
            $sql = "INSERT INTO ttbm (maGV, maTaiKhoan) VALUES (?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param("ii", $maGV, $maTaiKhoan);

            if (!$stmt->execute()) {
                throw new Exception("Không thể tạo TTBM: " . $stmt->error);
            }

            $maTTBM = $this->conn->insert_id;
            $stmt->close();

            // Commit transaction
            $this->conn->commit();

            return [
                'success' => true,
                'maTTBM' => $maTTBM,
                'maTaiKhoan' => $maTaiKhoan,
                'message' => 'Tạo TTBM thành công'
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
     * Lấy thông tin TTBM theo maTTBM
     */
    public function getTTBMById($maTTBM)
    {
        $sql = "SELECT ttbm.*, gv.hoTen, gv.email, gv.soDienThoai, gv.toBoMon,
                       tk.tenDangNhap, tk.trangThaiTaiKhoan 
                FROM ttbm
                LEFT JOIN giaovien gv ON ttbm.maGV = gv.maGV
                LEFT JOIN taikhoan tk ON ttbm.maTaiKhoan = tk.maTaiKhoan
                WHERE ttbm.maTTBM = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maTTBM);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Lấy danh sách tất cả TTBM
     */
    public function getAllTTBM()
    {
        $sql = "SELECT ttbm.*, gv.hoTen, gv.toBoMon, tk.tenDangNhap, tk.trangThaiTaiKhoan 
                FROM ttbm
                LEFT JOIN giaovien gv ON ttbm.maGV = gv.maGV
                LEFT JOIN taikhoan tk ON ttbm.maTaiKhoan = tk.maTaiKhoan
                ORDER BY ttbm.maTTBM DESC";
        
        $result = $this->conn->query($sql);
        $ttbmList = [];
        while ($row = $result->fetch_assoc()) {
            $ttbmList[] = $row;
        }
        return $ttbmList;
    }

    /**
     * Xóa TTBM (cũng xóa tài khoản liên kết)
     */
    public function deleteTTBM($maTTBM)
    {
        $this->conn->begin_transaction();

        try {
            // Lấy maTaiKhoan trước
            $ttbm = $this->getTTBMById($maTTBM);
            if (!$ttbm) {
                throw new Exception("Không tìm thấy TTBM");
            }

            // Xóa TTBM
            $sql = "DELETE FROM ttbm WHERE maTTBM = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $maTTBM);
            if (!$stmt->execute()) {
                throw new Exception("Không thể xóa TTBM");
            }
            $stmt->close();

            // Xóa tài khoản nếu có
            if ($ttbm['maTaiKhoan']) {
                $this->mAccount->deleteAccount($ttbm['maTaiKhoan']);
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Xóa TTBM thành công'];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
