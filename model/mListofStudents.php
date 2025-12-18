<?php
class ModelListofStudents {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Lấy danh sách lớp của giáo viên chủ nhiệm
     */
    public function getClassesByTeacher($maGV) {
        $sql = "SELECT DISTINCT l.maLop, l.tenLop, l.siSo, l.namHoc, k.khoiLop
                FROM phancong_gvcn pc
                INNER JOIN lophoc l ON pc.maLop = l.maLop
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE pc.maGV = ? AND pc.trangThai = 'active'
                ORDER BY l.tenLop";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getClassesByTeacher: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy danh sách học sinh theo lớp
     */
    public function getStudentsByClass($maLop) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen,
                    hs.ngaySinh,
                    hs.gioiTinh,
                    hs.diaChi,
                    hs.trangThaiHocTap,
                    ph.hoTen as tenPhuHuynh,
                    ph.soDienThoai as sdtPhuHuynh,
                    ph.email as emailPhuHuynh
                FROM hocsinh hs
                LEFT JOIN phuhuynh ph ON hs.maPH = ph.maPH
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy điểm trung bình của học sinh trong học kỳ hiện tại
     */
    public function getStudentAverageScore($maHS, $hocKy, $namHoc) {
        $sql = "SELECT AVG(bd.tbDiem) as diemTB
                FROM bangdiem bd
                WHERE bd.maHS = ? AND bd.hocKy = ? AND bd.namHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $maHS, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['diemTB'] ?? null;
    }
    
    /**
     * Lấy hạnh kiểm của học sinh
     */
    public function getStudentConduct($maHS, $hocKy, $namHoc) {
        $sql = "SELECT loaiHK
                FROM hanhkiem
                WHERE maHS = ? AND hocKy = ? AND namHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $maHS, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['loaiHK'] ?? 'Chưa đánh giá';
    }
    
    /**
     * Lấy thông tin lớp học
     */
    public function getClassInfo($maLop) {
        $sql = "SELECT l.*, k.khoiLop, gv.hoTen as tenGVCN
                FROM lophoc l
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN giaovien gv ON l.maGV = gv.maGV
                WHERE l.maLop = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Lấy thông tin chi tiết học sinh
     */
    public function getStudentDetail($maHS) {
        $sql = "SELECT 
                    hs.*,
                    ph.hoTen as tenPhuHuynh,
                    ph.soDienThoai as sdtPhuHuynh,
                    ph.email as emailPhuHuynh,
                    ph.diaChi as diaChiPhuHuynh,
                    l.tenLop,
                    k.khoiLop,
                    gv.hoTen as tenGVCN
                FROM hocsinh hs
                LEFT JOIN phuhuynh ph ON hs.maPH = ph.maPH
                LEFT JOIN lophoc l ON hs.maLop = l.maLop
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN giaovien gv ON l.maGV = gv.maGV
                WHERE hs.maHS = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Lấy tổng hợp điểm của học sinh
     */
    public function getStudentGrades($maHS, $namHoc) {
        $sql = "SELECT 
                    mh.tenMonHoc,
                    bd.hocKy,
                    bd.diemTX1,
                    bd.diemTX2,
                    bd.diemTX3,
                    bd.diemTX4,
                    bd.diemGiuaKy,
                    bd.diemCuoiKy,
                    bd.tbDiem,
                    bd.nhanXet
                FROM bangdiem bd
                JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                WHERE bd.maHS = ? AND bd.namHoc = ?
                ORDER BY bd.hocKy, mh.tenMonHoc";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $maHS, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy danh sách vi phạm của học sinh
     */
    public function getStudentViolations($maHS) {
        $sql = "SELECT 
                    vp.ngayViPham,
                    vp.loaiViPham,
                    vp.mucDoViPham,
                    vp.noiDungViPham,
                    vp.hinhThucXuLy,
                    gv.hoTen as nguoiPhatHien
                FROM vipham vp
                LEFT JOIN giaovien gv ON vp.nguoiPhatHien = gv.maGV
                WHERE vp.maHS = ?
                ORDER BY vp.ngayViPham DESC
                LIMIT 5";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy danh sách khen thưởng của học sinh
     */
    public function getStudentAwards($maHS) {
        $sql = "SELECT 
                    kt.ngayKhenThuong,
                    kt.hinhThuc,
                    kt.noiDung,
                    kt.capKhenThuong,
                    kt.linhVuc
                FROM khenthuong kt
                WHERE kt.maHS = ?
                ORDER BY kt.ngayKhenThuong DESC
                LIMIT 5";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
