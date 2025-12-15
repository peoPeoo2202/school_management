<?php
class mHomeworkDetail {
    private $conn;

    public function __construct() {
        require_once(__DIR__ . '/mConnect.php');
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    // Lấy thông tin chi tiết bài tập
    public function getHomeworkDetail($maBaiTap) {
        $sql = "SELECT bt.*, l.tenLop, m.tenMonHoc, gv.hoTen as tenGV,
                COUNT(DISTINCT CASE WHEN bn.maHS IS NOT NULL THEN bn.maHS END) as soLuongNopBai,
                COUNT(DISTINCT hs.maHS) as tongSoHocSinh
                FROM baitap bt
                INNER JOIN lophoc l ON bt.maLop = l.maLop
                INNER JOIN monhoc m ON bt.maMonHoc = m.maMonHoc
                LEFT JOIN giaovien gv ON bt.maGV = gv.maGV
                LEFT JOIN bainop bn ON bt.maBaiTap = bn.maBaiTap
                LEFT JOIN hocsinh hs ON l.maLop = hs.maLop
                WHERE bt.maBaiTap = ?
                GROUP BY bt.maBaiTap";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBaiTap);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Lấy danh sách bài nộp của học sinh - FIX
    public function getSubmissions($maBaiTap) {
        $sql = "SELECT bn.maBaiNop, bn.maBaiTap, bn.maHS, bn.tenFile, bn.duongDan, 
                bn.ngayNop, bn.trangThai, bn.diem, bn.nhanXet, bn.ngayCham,
                hs.hoTen as tenHS, hs.maHS,
                CASE 
                    WHEN bn.ngayNop IS NULL THEN 'Chưa nộp'
                    WHEN bn.ngayNop > bt.thoiGianNop THEN 'Nộp trễ'
                    ELSE 'Đã nộp'
                END as trangThaiNop,
                CASE 
                    WHEN bn.diem IS NOT NULL THEN 'Đã chấm'
                    WHEN bn.ngayNop IS NOT NULL THEN 'Chưa chấm'
                    ELSE 'Chưa nộp'
                END as trangThaiCham
                FROM bainop bn
                INNER JOIN hocsinh hs ON bn.maHS = hs.maHS
                INNER JOIN baitap bt ON bn.maBaiTap = bt.maBaiTap
                WHERE bn.maBaiTap = ? AND bn.maBaiNop > 0
                ORDER BY bn.ngayNop DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBaiTap);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Lấy danh sách học sinh trong lớp (để hiển thị cả học sinh chưa nộp)
    public function getStudentsList($maLop) {
        $sql = "SELECT hs.maHS, hs.hoTen
                FROM hocsinh hs
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Chấm điểm bài nộp - ADD VALIDATION
    public function gradeSubmission($maBaiNop, $diem, $nhanXet, $maGV) {
        // Kiểm tra maBaiNop có tồn tại không
        $checkSql = "SELECT maBaiNop FROM bainop WHERE maBaiNop = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $maBaiNop);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $sql = "UPDATE bainop 
                SET diem = ?, nhanXet = ?, ngayCham = NOW(), maGV = ?, trangThai = 'Dacham'
                WHERE maBaiNop = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("dsii", $diem, $nhanXet, $maGV, $maBaiNop);
        return $stmt->execute();
    }

    // Lấy thông tin bài nộp để chấm
    public function getSubmissionDetail($maBaiNop) {
        $sql = "SELECT bn.*, hs.hoTen as tenHS, bt.tenBaiTap
                FROM bainop bn
                INNER JOIN hocsinh hs ON bn.maHS = hs.maHS
                INNER JOIN baitap bt ON bn.maBaiTap = bt.maBaiTap
                WHERE bn.maBaiNop = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBaiNop);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Khóa/mở khóa bài tập
    public function toggleLockHomework($maBaiTap, $khoaBai) {
        $sql = "UPDATE baitap SET khoaBai = ? WHERE maBaiTap = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $khoaBai, $maBaiTap);
        return $stmt->execute();
    }
}
?>
