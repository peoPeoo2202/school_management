<?php
class mAssignHomework {
    private $conn;

    public function __construct() {
        require_once(__DIR__ . '/mConnect.php');
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    // Lấy danh sách lớp mà giáo viên đang dạy
    public function getTeacherClasses($maGV) {
        $sql = "SELECT DISTINCT l.maLop, l.tenLop, l.namHoc 
                FROM lophoc l
                INNER JOIN phancong_giangday pc ON l.maLop = pc.maLop
                WHERE pc.maGV = ? AND pc.trangThai = 'active'
                ORDER BY l.tenLop";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Lấy danh sách môn học mà giáo viên dạy cho lớp
    public function getTeacherSubjects($maGV, $maLop) {
        $sql = "SELECT DISTINCT m.maMonHoc, m.tenMonHoc
                FROM monhoc m
                INNER JOIN phancong_giangday pc ON m.maMonHoc = pc.maMonHoc
                WHERE pc.maGV = ? AND pc.maLop = ? AND pc.trangThai = 'active'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $maGV, $maLop);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Lấy môn học mà giáo viên dạy cho lớp (tự động xác định)
    public function getTeacherSubjectForClass($maGV, $maLop) {
        $sql = "SELECT maMonHoc 
                FROM phancong_giangday 
                WHERE maGV = ? AND maLop = ? AND trangThai = 'active' 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $maGV, $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['maMonHoc'];
        }
        
        return null;
    }

    // Thêm bài tập mới - FIX bind_param
    public function createHomework($data) {
        // Xác định giá trị anBai
        $anBai = isset($data['anBai']) ? (int)$data['anBai'] : 0;
        
        // Cast các giá trị integer để đảm bảo đúng kiểu
        $maLop = (int)$data['maLop'];
        $maMonHoc = (int)$data['maMonHoc'];
        $maGV = (int)$data['maGV'];
        $choPhepNopTre = (int)$data['choPhepNopTre'];
        
        if (isset($data['tenFile']) && isset($data['duongDan'])) {
            $sql = "INSERT INTO baitap (tenBaiTap, yeuCauBaiTap, thoiGianNop, maLop, maMonHoc, maGV, tenFile, duongDan, choPhepNopTre, anBai) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("sssiisssii", 
                $data['tenBaiTap'],
                $data['yeuCauBaiTap'],
                $data['thoiGianNop'],
                $maLop,
                $maMonHoc,
                $maGV,
                $data['tenFile'],
                $data['duongDan'],
                $choPhepNopTre,
                $anBai
            );
        } else {
            $sql = "INSERT INTO baitap (tenBaiTap, yeuCauBaiTap, thoiGianNop, maLop, maMonHoc, maGV, choPhepNopTre, anBai) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("sssiiii", 
                $data['tenBaiTap'],
                $data['yeuCauBaiTap'],
                $data['thoiGianNop'],
                $maLop,
                $maMonHoc,
                $maGV,
                $choPhepNopTre,
                $anBai
            );
        }
        
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return false;
        }
        
        return true;
    }

    // Lấy danh sách bài tập của giáo viên
    public function getHomeworkList($maGV, $maLop = null) {
        $sql = "SELECT DISTINCT bt.*, l.tenLop, m.tenMonHoc,
                COALESCE((SELECT COUNT(*) FROM bainop bn WHERE bn.maBaiTap = bt.maBaiTap), 0) as soLuongNopBai
                FROM baitap bt
                INNER JOIN lophoc l ON bt.maLop = l.maLop
                INNER JOIN monhoc m ON bt.maMonHoc = m.maMonHoc
                WHERE bt.maGV = ?";
        
        $params = [$maGV];
        $types = "i";
        
        if ($maLop) {
            $sql .= " AND bt.maLop = ?";
            $params[] = $maLop;
            $types .= "i";
        }
        
        $sql .= " ORDER BY bt.thoiGianNop DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Lấy thông tin chi tiết bài tập
    public function getHomeworkById($maBaiTap) {
        $sql = "SELECT bt.*, l.tenLop, m.tenMonHoc
                FROM baitap bt
                INNER JOIN lophoc l ON bt.maLop = l.maLop
                INNER JOIN monhoc m ON bt.maMonHoc = m.maMonHoc
                WHERE bt.maBaiTap = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBaiTap);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Cập nhật bài tập
    public function updateHomework($maBaiTap, $data) {
        // Xác định giá trị anBai
        $anBai = isset($data['anBai']) ? (int)$data['anBai'] : 0;
        
        // Cast các giá trị integer
        $maLop = (int)$data['maLop'];
        $maMonHoc = (int)$data['maMonHoc'];
        $choPhepNopTre = (int)$data['choPhepNopTre'];
        $maBaiTap = (int)$maBaiTap;
        
        if (isset($data['tenFile']) && isset($data['duongDan'])) {
            // Cập nhật bao gồm file mới và anBai
            $sql = "UPDATE baitap 
                    SET tenBaiTap = ?, yeuCauBaiTap = ?, thoiGianNop = ?, 
                        maLop = ?, maMonHoc = ?, tenFile = ?, duongDan = ?, choPhepNopTre = ?, anBai = ?
                    WHERE maBaiTap = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssiissiii",
                $data['tenBaiTap'],
                $data['yeuCauBaiTap'],
                $data['thoiGianNop'],
                $maLop,
                $maMonHoc,
                $data['tenFile'],
                $data['duongDan'],
                $choPhepNopTre,
                $anBai,
                $maBaiTap
            );
        } else {
            // Cập nhật không thay đổi file nhưng có anBai
            $sql = "UPDATE baitap 
                    SET tenBaiTap = ?, yeuCauBaiTap = ?, thoiGianNop = ?, 
                        maLop = ?, maMonHoc = ?, choPhepNopTre = ?, anBai = ?
                    WHERE maBaiTap = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssiiiii",
                $data['tenBaiTap'],
                $data['yeuCauBaiTap'],
                $data['thoiGianNop'],
                $maLop,
                $maMonHoc,
                $choPhepNopTre,
                $anBai,
                $maBaiTap
            );
        }
        
        return $stmt->execute();
    }

    // Xóa bài tập
    public function deleteHomework($maBaiTap) {
        $sql = "DELETE FROM baitap WHERE maBaiTap = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBaiTap);
        return $stmt->execute();
    }

    // Cập nhật trạng thái bài tập
    public function updateHomeworkStatus($maBaiTap, $trangThai) {
        $sql = "UPDATE baitap SET trangThai = ? WHERE maBaiTap = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $trangThai, $maBaiTap);
        return $stmt->execute();
    }
}
?>
