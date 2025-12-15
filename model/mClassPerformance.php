<?php
class ModelClassPerformance {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Lấy điểm trung bình cả lớp theo học kỳ
     */
    public function getClassAverageScore($maLop, $hocKy, $namHoc) {
        $sql = "SELECT AVG(bd.tbDiem) as diemTBLop, COUNT(DISTINCT bd.maHS) as soHocSinh
                FROM bangdiem bd
                JOIN hocsinh hs ON bd.maHS = hs.maHS
                WHERE hs.maLop = ? AND bd.hocKy = ? AND bd.namHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $maLop, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Lấy điểm trung bình cả năm của lớp
     */
    public function getClassYearlyAverage($maLop, $namHoc) {
        $sql = "SELECT AVG(hl.diemTBCaNam) as diemTBCaNam
                FROM hocluc hl
                JOIN hocsinh hs ON hl.maHS = hs.maHS
                WHERE hs.maLop = ? AND hl.namHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $maLop, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['diemTBCaNam'] ?? null;
    }
    
    /**
     * Lấy số lượng học sinh được khen thưởng
     */
    public function getAwardsCount($maLop, $hocKy, $namHoc) {
        $sql = "SELECT COUNT(DISTINCT kt.maHS) as soHSKhenThuong,
                       COUNT(kt.maKhenThuong) as tongKhenThuong
                FROM khenthuong kt
                JOIN hocsinh hs ON kt.maHS = hs.maHS
                WHERE hs.maLop = ? AND kt.hocKy = ? AND kt.namHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $maLop, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Lấy chi tiết khen thưởng theo cấp
     */
    public function getAwardsByLevel($maLop, $hocKy, $namHoc) {
        $sql = "SELECT kt.capKhenThuong, COUNT(*) as soLuong
                FROM khenthuong kt
                JOIN hocsinh hs ON kt.maHS = hs.maHS
                WHERE hs.maLop = ? AND kt.hocKy = ? AND kt.namHoc = ?
                GROUP BY kt.capKhenThuong
                ORDER BY 
                    CASE kt.capKhenThuong
                        WHEN 'quocgia' THEN 1
                        WHEN 'tinh' THEN 2
                        WHEN 'huyen' THEN 3
                        WHEN 'truong' THEN 4
                        ELSE 5
                    END";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $maLop, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy số lượng học sinh vi phạm
     */
    public function getViolationsCount($maLop, $hocKy, $namHoc) {
        $sql = "SELECT COUNT(DISTINCT vp.maHS) as soHSViPham,
                       COUNT(vp.maViPham) as tongViPham,
                       SUM(CASE WHEN vp.mucDoViPham = 'Nhe' THEN 1 ELSE 0 END) as viPhamNhe,
                       SUM(CASE WHEN vp.mucDoViPham = 'Trung binh' THEN 1 ELSE 0 END) as viPhamTB,
                       SUM(CASE WHEN vp.mucDoViPham = 'Nang' THEN 1 ELSE 0 END) as viPhamNang
                FROM vipham vp
                JOIN hocsinh hs ON vp.maHS = hs.maHS
                WHERE hs.maLop = ? AND vp.hocKy = ? AND vp.namHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $maLop, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Lấy thống kê hạnh kiểm của lớp
     */
    public function getConductStatistics($maLop, $hocKy, $namHoc) {
        $sql = "SELECT 
                    hk.loaiHK,
                    COUNT(*) as soLuong,
                    (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM hanhkiem hk2 
                        JOIN hocsinh hs2 ON hk2.maHS = hs2.maHS 
                        WHERE hs2.maLop = ? AND hk2.hocKy = ? AND hk2.namHoc = ?)) as tyLe
                FROM hanhkiem hk
                JOIN hocsinh hs ON hk.maHS = hs.maHS
                WHERE hs.maLop = ? AND hk.hocKy = ? AND hk.namHoc = ?
                GROUP BY hk.loaiHK
                ORDER BY 
                    CASE hk.loaiHK
                        WHEN 'Tot' THEN 1
                        WHEN 'Kha' THEN 2
                        WHEN 'TB' THEN 3
                        WHEN 'Yeu' THEN 4
                        ELSE 5
                    END";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iisiis", $maLop, $hocKy, $namHoc, $maLop, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy thống kê học lực của lớp
     */
    public function getAcademicStatistics($maLop, $hocKy, $namHoc) {
        $sql = "SELECT 
                    CASE
                        WHEN bd.tbDiem >= 9.0 THEN 'Xuất sắc'
                        WHEN bd.tbDiem >= 8.0 THEN 'Giỏi'
                        WHEN bd.tbDiem >= 6.5 THEN 'Khá'
                        WHEN bd.tbDiem >= 5.0 THEN 'Trung bình'
                        WHEN bd.tbDiem >= 3.5 THEN 'Yếu'
                        ELSE 'Kém'
                    END as xepLoai,
                    COUNT(DISTINCT bd.maHS) as soLuong
                FROM bangdiem bd
                JOIN hocsinh hs ON bd.maHS = hs.maHS
                WHERE hs.maLop = ? AND bd.hocKy = ? AND bd.namHoc = ?
                GROUP BY xepLoai
                ORDER BY 
                    CASE xepLoai
                        WHEN 'Xuất sắc' THEN 1
                        WHEN 'Giỏi' THEN 2
                        WHEN 'Khá' THEN 3
                        WHEN 'Trung bình' THEN 4
                        WHEN 'Yếu' THEN 5
                        WHEN 'Kém' THEN 6
                    END";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $maLop, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy thông tin lớp
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
     * Lấy danh sách lớp của giáo viên chủ nhiệm
     */
    public function getClassesByTeacher($maGV) {
        $sql = "SELECT l.maLop, l.tenLop, l.siSo, l.namHoc, k.khoiLop
                FROM lophoc l
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE l.maGV = ?
                ORDER BY l.tenLop";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy danh sách học sinh vi phạm theo mức độ (có thông tin chi tiết)
     */
    public function getViolationsByLevel($maLop, $hocKy, $namHoc, $mucDoViPham) {
        $sql = "SELECT 
                    hs.hoTen,
                    COUNT(vp.maViPham) as soLanViPham,
                    GROUP_CONCAT(DISTINCT vp.loaiViPham ORDER BY vp.ngayViPham DESC SEPARATOR ', ') as cacLoaiViPham,
                    GROUP_CONCAT(
                        CONCAT(DATE_FORMAT(vp.ngayViPham, '%d/%m'), ': ', vp.loaiViPham)
                        ORDER BY vp.ngayViPham DESC 
                        SEPARATOR ' | '
                    ) as chiTietViPham
                FROM vipham vp
                JOIN hocsinh hs ON vp.maHS = hs.maHS
                WHERE hs.maLop = ? AND vp.hocKy = ? AND vp.namHoc = ? AND vp.mucDoViPham = ?
                GROUP BY hs.maHS, hs.hoTen
                ORDER BY soLanViPham DESC, hs.hoTen";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiss", $maLop, $hocKy, $namHoc, $mucDoViPham);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy danh sách học sinh được khen thưởng theo cấp (có thông tin chi tiết)
     */
    public function getAwardsByLevelDetail($maLop, $hocKy, $namHoc, $capKhenThuong) {
        $sql = "SELECT 
                    hs.hoTen,
                    COUNT(kt.maKhenThuong) as soLanKhenThuong,
                    GROUP_CONCAT(DISTINCT kt.linhVuc ORDER BY kt.ngayKhenThuong DESC SEPARATOR ', ') as cacLinhVuc,
                    GROUP_CONCAT(
                        CONCAT(DATE_FORMAT(kt.ngayKhenThuong, '%d/%m'), ': ', kt.noiDung)
                        ORDER BY kt.ngayKhenThuong DESC 
                        SEPARATOR ' | '
                    ) as chiTietKhenThuong
                FROM khenthuong kt
                JOIN hocsinh hs ON kt.maHS = hs.maHS
                WHERE hs.maLop = ? AND kt.hocKy = ? AND kt.namHoc = ? AND kt.capKhenThuong = ?
                GROUP BY hs.maHS, hs.hoTen
                ORDER BY soLanKhenThuong DESC, hs.hoTen";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiss", $maLop, $hocKy, $namHoc, $capKhenThuong);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy thống kê nghỉ học của lớp
     */
    public function getAbsenceStatistics($maLop, $hocKy, $namHoc) {
        $sql = "SELECT 
                    COUNT(DISTINCT nh.maHS) as soHSNghi,
                    COUNT(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 END) as tongNghiCoPhep,
                    COUNT(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 END) as tongNghiKhongPhep,
                    COUNT(nh.maNghiHoc) as tongSoNgayNghi
                FROM nghihoc nh
                JOIN hocsinh hs ON nh.maHS = hs.maHS
                WHERE hs.maLop = ? AND nh.hocKy = ? AND nh.namHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $maLop, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Lấy danh sách học sinh nghỉ nhiều nhất
     */
    public function getTopAbsentStudents($maLop, $hocKy, $namHoc, $limit = 5) {
        $sql = "SELECT 
                    hs.hoTen,
                    COUNT(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 END) as soNghiCoPhep,
                    COUNT(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 END) as soNghiKhongPhep,
                    COUNT(nh.maNghiHoc) as tongNghi
                FROM nghihoc nh
                JOIN hocsinh hs ON nh.maHS = hs.maHS
                WHERE hs.maLop = ? AND nh.hocKy = ? AND nh.namHoc = ?
                GROUP BY hs.maHS, hs.hoTen
                HAVING tongNghi > 0
                ORDER BY tongNghi DESC, soNghiKhongPhep DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iisi", $maLop, $hocKy, $namHoc, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
