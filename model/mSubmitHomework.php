<?php
class mSubmitHomework {
    private $conn;

    public function __construct() {
        require_once(__DIR__ . '/mConnect.php');
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    public function getHomeworkById($maBaiTap) {
        $sql = "SELECT bt.*, l.tenLop, m.tenMonHoc
                FROM baitap bt
                LEFT JOIN lophoc l ON bt.maLop = l.maLop
                LEFT JOIN monhoc m ON bt.maMonHoc = m.maMonHoc
                WHERE bt.maBaiTap = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBaiTap);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getSubmissionByStudent($maBaiTap, $maHS) {
        $sql = "SELECT * FROM bainop WHERE maBaiTap = ? AND maHS = ? ORDER BY ngayNop DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $maBaiTap, $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function submitHomework($maBaiTap, $maHS, $tenFile, $duongDan, $noiDung) {
        // Kiểm tra đã nộp chưa
        $checkSql = "SELECT maBaiNop FROM bainop WHERE maBaiTap = ? AND maHS = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $maBaiTap, $maHS);
        $checkStmt->execute();
        $existingSubmission = $checkStmt->get_result()->fetch_assoc();

        if ($existingSubmission) {
            // Cập nhật bài nộp
            $sql = "UPDATE bainop SET tenFile = ?, duongDan = ?, noiDung = ?, ngayNop = NOW(), trangThai = 'Chuacham' 
                    WHERE maBaiTap = ? AND maHS = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssii", $tenFile, $duongDan, $noiDung, $maBaiTap, $maHS);
        } else {
            // Thêm bài nộp mới
            $sql = "INSERT INTO bainop (maBaiTap, maHS, tenFile, duongDan, noiDung, ngayNop, trangThai) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'Chuacham')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iisss", $maBaiTap, $maHS, $tenFile, $duongDan, $noiDung);
        }

        return $stmt->execute();
    }

    public function getSubjectsForStudent($maHS) {
        $sql = "SELECT DISTINCT 
                m.maMonHoc, 
                m.tenMonHoc,
                COALESCE(COUNT(DISTINCT bt.maBaiTap), 0) as tongBaiTap,
                COALESCE(COUNT(DISTINCT CASE 
                    WHEN bt.thoiGianNop < NOW() 
                    AND (bn.ngayNop IS NULL OR bn.ngayNop > bt.thoiGianNop)
                    AND (bt.choPhepNopTre = 0 OR bt.choPhepNopTre IS NULL)
                    THEN bt.maBaiTap 
                END), 0) as quaHan
                FROM hocsinh hs
                INNER JOIN lophoc l ON hs.maLop = l.maLop
                INNER JOIN phancong_giangday pg ON pg.maLop = l.maLop
                INNER JOIN monhoc m ON pg.maMonHoc = m.maMonHoc
                LEFT JOIN baitap bt ON bt.maMonHoc = m.maMonHoc AND bt.maLop = l.maLop
                LEFT JOIN bainop bn ON bt.maBaiTap = bn.maBaiTap AND bn.maHS = hs.maHS
                WHERE hs.maHS = ?
                AND pg.trangThai = 'active'
                GROUP BY m.maMonHoc, m.tenMonHoc
                ORDER BY m.tenMonHoc";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        return $subjects;
    }

    public function getHomeworkForStudent($maHS, $maMonHoc = null) {
        $sql = "SELECT DISTINCT
                bt.maBaiTap,
                bt.tenBaiTap, 
                bt.yeuCauBaiTap,
                bt.thoiGianNop,
                bt.choPhepNopTre,
                bt.tenFile,
                bt.duongDan,
                m.tenMonHoc, 
                l.tenLop,
                bn.maBaiNop, 
                bn.ngayNop, 
                bn.trangThai, 
                bn.diem, 
                bn.nhanXet,
                CASE WHEN bn.ngayNop IS NOT NULL THEN 1 ELSE 0 END as daNop
                FROM hocsinh hs
                INNER JOIN lophoc l ON hs.maLop = l.maLop
                INNER JOIN phancong_giangday pg ON pg.maLop = l.maLop
                INNER JOIN monhoc m ON pg.maMonHoc = m.maMonHoc
                LEFT JOIN baitap bt ON bt.maMonHoc = m.maMonHoc AND bt.maLop = l.maLop
                LEFT JOIN bainop bn ON bt.maBaiTap = bn.maBaiTap AND bn.maHS = hs.maHS
                WHERE hs.maHS = ?
                AND pg.trangThai = 'active'";
        
        if ($maMonHoc) {
            $sql .= " AND m.maMonHoc = ?";
        }
        
        // Chỉ hiển thị các bài tập đã được giao (có maBaiTap)
        $sql .= " AND bt.maBaiTap IS NOT NULL";
        $sql .= " ORDER BY bt.thoiGianNop DESC";
        
        $stmt = $this->conn->prepare($sql);
        if ($maMonHoc) {
            $stmt->bind_param("ii", $maHS, $maMonHoc);
        } else {
            $stmt->bind_param("i", $maHS);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $homeworks = [];
        while ($row = $result->fetch_assoc()) {
            $homeworks[] = $row;
        }
        return $homeworks;
    }
}
?>
