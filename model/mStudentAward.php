<?php
/**
 * Model quản lý khen thưởng học sinh
 */
class ModelStudentAward {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Lấy danh sách lớp của giáo viên chủ nhiệm
     */
    public function getClassesByTeacher($maGV) {
        $sql = "SELECT l.maLop, l.tenLop, l.siSo, l.namHoc, k.khoiLop
                FROM lophoc l
                INNER JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE l.maGV = ?
                ORDER BY l.tenLop";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maGV);
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
                INNER JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN giaovien gv ON l.maGV = gv.maGV
                WHERE l.maLop = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Lấy danh sách học sinh trong lớp
     */
    public function getStudentsByClass($maLop) {
        $sql = "SELECT maHS, hoTen, gioiTinh, ngaySinh
                FROM hocsinh
                WHERE maLop = ?
                ORDER BY hoTen";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy danh sách khen thưởng của học sinh
     */
    public function getStudentAwards($maLop, $hocKy, $namHoc) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen,
                    hs.gioiTinh,
                    kt.maKhenThuong,
                    kt.noiDung as lyDo,
                    kt.capKhenThuong,
                    kt.ngayKhenThuong as ngayKhen,
                    kt.hocKy,
                    kt.namHoc,
                    kt.hinhThuc,
                    kt.linhVuc
                FROM hocsinh hs
                LEFT JOIN khenthuong kt ON hs.maHS = kt.maHS 
                    AND kt.hocKy = ? 
                    AND kt.namHoc = ?
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen, kt.ngayKhenThuong DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $hocKy, $namHoc, $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Nhóm khen thưởng theo học sinh
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $maHS = $row['maHS'];
            
            if (!isset($students[$maHS])) {
                $students[$maHS] = [
                    'maHS' => $row['maHS'],
                    'hoTen' => $row['hoTen'],
                    'gioiTinh' => $row['gioiTinh'],
                    'awards' => []
                ];
            }
            
            if ($row['maKhenThuong']) {
                $students[$maHS]['awards'][] = [
                    'maKhenThuong' => $row['maKhenThuong'],
                    'lyDo' => $row['lyDo'],
                    'capKhenThuong' => $row['capKhenThuong'],
                    'ngayKhen' => $row['ngayKhen'],
                    'hocKy' => $row['hocKy'],
                    'namHoc' => $row['namHoc'],
                    'hinhThuc' => $row['hinhThuc'],
                    'linhVuc' => $row['linhVuc']
                ];
            }
        }
        
        return array_values($students);
    }
    
    /**
     * Thêm khen thưởng mới
     */
    public function addAward($data) {
        $sql = "INSERT INTO khenthuong (maHS, noiDung, capKhenThuong, ngayKhenThuong, hocKy, namHoc, hinhThuc, linhVuc)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isssisss", 
            $data['maHS'],
            $data['lyDo'],
            $data['capKhenThuong'],
            $data['ngayKhen'],
            $data['hocKy'],
            $data['namHoc'],
            $data['hinhThuc'],
            $data['linhVuc']
        );
        
        return $stmt->execute();
    }
    
    /**
     * Cập nhật khen thưởng
     */
    public function updateAward($maKhenThuong, $data) {
        $sql = "UPDATE khenthuong 
                SET noiDung = ?, capKhenThuong = ?, ngayKhenThuong = ?, hinhThuc = ?, linhVuc = ?
                WHERE maKhenThuong = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssi",
            $data['lyDo'],
            $data['capKhenThuong'],
            $data['ngayKhen'],
            $data['hinhThuc'],
            $data['linhVuc'],
            $maKhenThuong
        );
        
        return $stmt->execute();
    }
    
    /**
     * Xóa khen thưởng
     */
    public function deleteAward($maKhenThuong) {
        $sql = "DELETE FROM khenthuong WHERE maKhenThuong = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maKhenThuong);
        
        return $stmt->execute();
    }
    
    /**
     * Lấy chi tiết khen thưởng
     */
    public function getAwardDetail($maKhenThuong) {
        $sql = "SELECT kt.*, hs.hoTen, hs.gioiTinh
                FROM khenthuong kt
                INNER JOIN hocsinh hs ON kt.maHS = hs.maHS
                WHERE kt.maKhenThuong = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maKhenThuong);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
}
?>
