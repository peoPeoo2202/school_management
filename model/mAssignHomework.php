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

    // Thêm bài tập mới - FIX bind_param
    public function createHomework($data) {
        if (isset($data['tenFile']) && isset($data['duongDan'])) {
            $sql = "INSERT INTO baitap (tenBaiTap, yeuCauBaiTap, thoiGianNop, maLop, maMonHoc, maGV, tenFile, duongDan, choPhepNopTre) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("sssiiissi", 
                $data['tenBaiTap'],
                $data['yeuCauBaiTap'],
                $data['thoiGianNop'],
                $data['maLop'],
                $data['maMonHoc'],
                $data['maGV'],
                $data['tenFile'],
                $data['duongDan'],
                $data['choPhepNopTre']
            );
        } else {
            $sql = "INSERT INTO baitap (tenBaiTap, yeuCauBaiTap, thoiGianNop, maLop, maMonHoc, maGV, choPhepNopTre) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("sssiiii", 
                $data['tenBaiTap'],
                $data['yeuCauBaiTap'],
                $data['thoiGianNop'],
                $data['maLop'],
                $data['maMonHoc'],
                $data['maGV'],
                $data['choPhepNopTre']
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
        if (isset($data['tenFile']) && isset($data['duongDan'])) {
            // Cập nhật bao gồm file mới
            $sql = "UPDATE baitap 
                    SET tenBaiTap = ?, yeuCauBaiTap = ?, thoiGianNop = ?, 
                        maLop = ?, maMonHoc = ?, tenFile = ?, duongDan = ?, choPhepNopTre = ?
                    WHERE maBaiTap = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssiissii",
                $data['tenBaiTap'],
                $data['yeuCauBaiTap'],
                $data['thoiGianNop'],
                $data['maLop'],
                $data['maMonHoc'],
                $data['tenFile'],
                $data['duongDan'],
                $data['choPhepNopTre'],
                $maBaiTap
            );
        } else {
            // Cập nhật không thay đổi file - FIX: sửa bind_param từ "sssiii" thành "sssiiii"
            $sql = "UPDATE baitap 
                    SET tenBaiTap = ?, yeuCauBaiTap = ?, thoiGianNop = ?, 
                        maLop = ?, maMonHoc = ?, choPhepNopTre = ?
                    WHERE maBaiTap = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssiiii",
                $data['tenBaiTap'],
                $data['yeuCauBaiTap'],
                $data['thoiGianNop'],
                $data['maLop'],
                $data['maMonHoc'],
                $data['choPhepNopTre'],
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
