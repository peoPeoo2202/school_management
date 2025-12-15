<?php
/**
 * Model quản lý vi phạm học sinh
 */
class ModelStudentViolation {
    private $conn;
    private $lastError = '';
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Lấy lỗi cuối cùng
     */
    public function getLastError() {
        return $this->lastError;
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
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $data;
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
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return $data;
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
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $data;
    }
    
    /**
     * Lấy danh sách vi phạm của học sinh
     */
    public function getStudentViolations($maLop, $hocKy, $namHoc) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen,
                    hs.gioiTinh,
                    vp.maViPham,
                    vp.loaiViPham,
                    vp.mucDoViPham,
                    vp.noiDungViPham,
                    vp.hinhThucXuLy,
                    vp.ngayViPham,
                    vp.hocKy,
                    vp.namHoc,
                    gv.hoTen as nguoiPhatHien
                FROM hocsinh hs
                LEFT JOIN vipham vp ON hs.maHS = vp.maHS 
                    AND vp.hocKy = ? 
                    AND vp.namHoc = ?
                LEFT JOIN giaovien gv ON vp.nguoiPhatHien = gv.maGV
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen, vp.ngayViPham DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $hocKy, $namHoc, $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Nhóm vi phạm theo học sinh
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $maHS = $row['maHS'];
            
            if (!isset($students[$maHS])) {
                $students[$maHS] = [
                    'maHS' => $row['maHS'],
                    'hoTen' => $row['hoTen'],
                    'gioiTinh' => $row['gioiTinh'],
                    'violations' => []
                ];
            }
            
            if ($row['maViPham']) {
                $students[$maHS]['violations'][] = [
                    'maViPham' => $row['maViPham'],
                    'loaiViPham' => $row['loaiViPham'],
                    'mucDoViPham' => $row['mucDoViPham'],
                    'noiDungViPham' => $row['noiDungViPham'],
                    'hinhThucXuLy' => $row['hinhThucXuLy'],
                    'ngayViPham' => $row['ngayViPham'],
                    'hocKy' => $row['hocKy'],
                    'namHoc' => $row['namHoc'],
                    'nguoiPhatHien' => $row['nguoiPhatHien']
                ];
            }
        }
        
        $stmt->close();
        return array_values($students);
    }
    
    /**
     * Thêm vi phạm mới
     */
    public function addViolation($data) {
        $sql = "INSERT INTO vipham (maHS, loaiViPham, mucDoViPham, noiDungViPham, hinhThucXuLy, ngayViPham, nguoiPhatHien, hocKy, namHoc)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isssssiis", 
            $data['maHS'],
            $data['loaiViPham'],
            $data['mucDoViPham'],
            $data['noiDungViPham'],
            $data['hinhThucXuLy'],
            $data['ngayViPham'],
            $data['nguoiPhatHien'],
            $data['hocKy'],
            $data['namHoc']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Cập nhật vi phạm
     */
    public function updateViolation($maViPham, $data) {
        // Kiểm tra xem vi phạm có tồn tại không
        $checkSql = "SELECT maViPham FROM vipham WHERE maViPham = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $maViPham);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $this->lastError = "Vi phạm không tồn tại";
            $checkStmt->close();
            return false;
        }
        $checkStmt->close();
        
        // Thực hiện cập nhật
        $sql = "UPDATE vipham 
                SET loaiViPham = ?, 
                    mucDoViPham = ?,
                    noiDungViPham = ?,
                    hinhThucXuLy = ?,
                    ngayViPham = ?
                WHERE maViPham = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->lastError = "Prepare update failed: " . $this->conn->error;
            return false;
        }
        
        $stmt->bind_param("sssssi",
            $data['loaiViPham'],
            $data['mucDoViPham'],
            $data['noiDungViPham'],
            $data['hinhThucXuLy'],
            $data['ngayViPham'],
            $maViPham
        );
        
        $result = $stmt->execute();
        
        if (!$result) {
            $this->lastError = "Execute failed: " . $stmt->error;
            $stmt->close();
            return false;
        }
        
        $stmt->close();
        
        // Cập nhật thành công (không cần check affected_rows vì có thể = 0 khi data không đổi)
        return true;
    }
    
    /**
     * Xóa vi phạm
     */
    public function deleteViolation($maViPham) {
        $sql = "DELETE FROM vipham WHERE maViPham = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maViPham);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Lấy thông tin chi tiết vi phạm
     */
    public function getViolationById($maViPham) {
        $sql = "SELECT * FROM vipham WHERE maViPham = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maViPham);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return $data;
    }
}
