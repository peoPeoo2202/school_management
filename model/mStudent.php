<?php
include_once("mConnect.php");
class mStudent
{
    private $conn;

    public function __construct()
    {
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    /** 
     * Lấy thông tin học sinh dựa vào tài khoản đăng nhập
     */
public function getStudentInfoByAccount($tenDangNhap)
{
    $sql = "SELECT hs.maHS, hs.hoTen, lh.tenLop, kh.khoiLop
            FROM taikhoan tk
            JOIN hocsinh hs ON tk.maTaiKhoan = hs.maHS
            JOIN lophoc lh ON hs.maLop = lh.maLop
            JOIN khoi kh ON lh.maKhoi = kh.maKhoi
            WHERE tk.tenDangNhap = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $tenDangNhap);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
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
        $sql = "SELECT mh.tenMonHoc, ld.thu, ld.tietBatDau, ld.tietKetThuc
                FROM hocsinh hs
                JOIN lichday ld ON hs.maLop = ld.maLop
                JOIN monhoc mh ON ld.maMonHoc = mh.maMonHoc
                WHERE hs.maHS = ?
                ORDER BY ld.thu, ld.tietBatDau";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        return $stmt->get_result();
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
