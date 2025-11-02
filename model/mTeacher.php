<?php
include_once("mConnect.php");

class mTeacher {
    private $conn;

    public function __construct() {
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    /**
     * Lấy thông tin giáo viên dựa vào tên đăng nhập
     */
    public function getTeacherInfoByAccount($tenDangNhap) {
        $sql = "SELECT gv.maGV, gv.hoTen, gv.email, gv.soDienThoai
                FROM giaovien gv
                JOIN taikhoan tk ON gv.maGV = tk.maTaiKhoan
                WHERE tk.tenDangNhap = ?";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            die("Lỗi prepare: " . $this->conn->error);
        }

        $stmt->bind_param("s", $tenDangNhap);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Lấy danh sách lớp mà giáo viên chủ nhiệm
     */
    public function getClassListByTeacher($maGV) {
        $sql = "SELECT lh.maLop, lh.tenLop, lh.siSo, kh.khoiLop, lh.namHoc
                FROM lophoc lh
                JOIN khoi kh ON lh.maKhoi = kh.maKhoi
                WHERE lh.maGV = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function __destruct() {
        if ($this->conn) $this->conn->close();
    }
}
?>
