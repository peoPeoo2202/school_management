<?php
/**
 * Model: Duyệt đề thi (Exam Approval)
 * Chức năng: Quản lý duyệt đề thi của TTBM
 * 
 * @author IMAX Team
 * @version 1.0
 * @date 2025-11-18
 */

class mExamApproval
{
    private $connection;

    public function __construct()
    {
        try {
            $conn = new mConnect();
            $this->connection = $conn->mConnect();
            
            if (!$this->connection) {
                throw new Exception("Không thể kết nối database");
            }
        } catch (Exception $e) {
            throw new Exception("Lỗi khởi tạo Model: " . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách đề thi theo tổ bộ môn
     * 
     * @param int|null $maTTBM Mã TTBM (null = tất cả)
     * @param string|null $trangThai Trạng thái lọc (null = tất cả)
     * @return array Danh sách đề thi
     */
    public function getExamsByDepartment($maTTBM = null, $trangThai = null)
    {
        try {
            $sql = "SELECT 
                        d.maDeThi,
                        d.tenDeThi,
                        d.maMonHoc,
                        m.tenMonHoc,
                        d.hocKy,
                        d.namHoc,
                        d.trangThai,
                        d.maBGH,
                        d.maTTBM,
                        t.maGV,
                        gv.hoTen as tenGV,
                        gv.toBoMon,
                        DATE_FORMAT(d.ngayTao, '%d/%m/%Y %H:%i') as ngayTao,
                        DATE_FORMAT(d.ngayDuyet, '%d/%m/%Y %H:%i') as ngayDuyet,
                        d.lyDoTuChoi,
                        d.noiDungDeThi,
                        d.thoiGianLamBai,
                        d.loaiDeThi
                    FROM dethi d
                    INNER JOIN monhoc m ON d.maMonHoc = m.maMonHoc
                    LEFT JOIN ttbm t ON d.maTTBM = t.maTTBM
                    LEFT JOIN giaovien gv ON t.maGV = gv.maGV
                    WHERE 1=1";

            $params = [];
            $types = "";

            if ($maTTBM !== null) {
                $sql .= " AND d.maTTBM = ?";
                $params[] = $maTTBM;
                $types .= "i";
            }

            if ($trangThai !== null && $trangThai !== '') {
                $sql .= " AND d.trangThai = ?";
                $params[] = $trangThai;
                $types .= "s";
            }

            $sql .= " ORDER BY 
                        CASE 
                            WHEN d.trangThai = 'Chuaduyet' THEN 1
                            WHEN d.trangThai = 'Daduyet' THEN 2
                            WHEN d.trangThai = 'Dachon' THEN 3
                            WHEN d.trangThai = 'Tuchoi' THEN 4
                            ELSE 5
                        END,
                        d.maDeThi DESC";

            if (!empty($params)) {
                $stmt = $this->connection->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . $this->connection->error);
                }
                $stmt->bind_param($types, ...$params);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $result = $stmt->get_result();
            } else {
                $result = $this->connection->query($sql);
                if (!$result) {
                    throw new Exception("Query failed: " . $this->connection->error);
                }
            }

            $exams = [];
            while ($row = $result->fetch_assoc()) {
                $exams[] = $row;
            }

            if (isset($stmt)) {
                $stmt->close();
            }

            return $exams;
        } catch (Exception $e) {
            error_log("getExamsByDepartment Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy thông tin chi tiết đề thi
     * 
     * @param int $maDeThi Mã đề thi
     * @return array|null Thông tin đề thi
     */
    public function getExamById($maDeThi)
    {
        $sql = "SELECT 
                    d.maDeThi,
                    d.tenDeThi,
                    d.maMonHoc,
                    m.tenMonHoc,
                    d.hocKy,
                    d.namHoc,
                    d.trangThai,
                    d.maBGH,
                    d.maTTBM,
                    t.maGV,
                    gv.hoTen as tenGV,
                    gv.toBoMon,
                    DATE_FORMAT(d.ngayTao, '%d/%m/%Y %H:%i') as ngayTao,
                    DATE_FORMAT(d.ngayDuyet, '%d/%m/%Y %H:%i') as ngayDuyet,
                    d.lyDoTuChoi,
                    d.noiDungDeThi,
                    d.thoiGianLamBai,
                    d.loaiDeThi
                FROM dethi d
                INNER JOIN monhoc m ON d.maMonHoc = m.maMonHoc
                LEFT JOIN ttbm t ON d.maTTBM = t.maTTBM
                LEFT JOIN giaovien gv ON t.maGV = gv.maGV
                WHERE d.maDeThi = ?";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maDeThi);
        $stmt->execute();
        $result = $stmt->get_result();
        $exam = $result->fetch_assoc();
        $stmt->close();

        return $exam;
    }

    /**
     * Tạo đề thi mới
     * 
     * @param array $data Dữ liệu đề thi
     * @return bool|int ID đề thi mới hoặc false
     */
    public function createExam($data)
    {
        // Kiểm tra trùng lặp
        if ($this->checkDuplicate($data['maMonHoc'], $data['hocKy'], $data['namHoc'], $data['loaiDeThi'])) {
            return false;
        }

        $sql = "INSERT INTO dethi (
                    tenDeThi, 
                    maMonHoc, 
                    hocKy, 
                    namHoc, 
                    trangThai, 
                    maTTBM,
                    noiDungDeThi,
                    thoiGianLamBai,
                    loaiDeThi,
                    ngayTao
                ) VALUES (?, ?, ?, ?, 'Choduyet', ?, ?, ?, ?, NOW())";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param(
            "siisisss",
            $data['tenDeThi'],
            $data['maMonHoc'],
            $data['hocKy'],
            $data['namHoc'],
            $data['maTTBM'],
            $data['noiDungDeThi'],
            $data['thoiGianLamBai'],
            $data['loaiDeThi']
        );

        $result = $stmt->execute();
        $insertId = $result ? $this->connection->insert_id : false;
        $stmt->close();

        return $insertId;
    }

    /**
     * Cập nhật đề thi
     * 
     * @param int $maDeThi Mã đề thi
     * @param array $data Dữ liệu cập nhật
     * @return bool Kết quả
     */
    public function updateExam($maDeThi, $data)
    {
        $sql = "UPDATE dethi SET 
                    tenDeThi = ?,
                    maMonHoc = ?,
                    hocKy = ?,
                    namHoc = ?,
                    noiDungDeThi = ?,
                    thoiGianLamBai = ?,
                    loaiDeThi = ?
                WHERE maDeThi = ? AND trangThai = 'Choduyet'";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param(
            "siissssi",
            $data['tenDeThi'],
            $data['maMonHoc'],
            $data['hocKy'],
            $data['namHoc'],
            $data['noiDungDeThi'],
            $data['thoiGianLamBai'],
            $data['loaiDeThi'],
            $maDeThi
        );

        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Duyệt đề thi
     * 
     * @param int $maDeThi Mã đề thi
     * @param int $maBGH Mã BGH duyệt (nếu có)
     * @return bool Kết quả
     */
    public function approveExam($maDeThi, $maBGH = null)
    {
        $sql = "UPDATE dethi SET 
                    trangThai = 'Daduyet',
                    ngayDuyet = NOW(),
                    lyDoTuChoi = NULL";
        
        if ($maBGH !== null) {
            $sql .= ", maBGH = ?";
        }
        
        $sql .= " WHERE maDeThi = ? AND trangThai = 'Choduyet'";

        $stmt = $this->connection->prepare($sql);
        
        if ($maBGH !== null) {
            $stmt->bind_param("ii", $maBGH, $maDeThi);
        } else {
            $stmt->bind_param("i", $maDeThi);
        }

        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Từ chối đề thi
     * 
     * @param int $maDeThi Mã đề thi
     * @param string $lyDo Lý do từ chối
     * @param int $maBGH Mã BGH từ chối (nếu có)
     * @return bool Kết quả
     */
    public function rejectExam($maDeThi, $lyDo, $maBGH = null)
    {
        $sql = "UPDATE dethi SET 
                    trangThai = 'Tuchoi',
                    lyDoTuChoi = ?,
                    ngayDuyet = NOW()";
        
        if ($maBGH !== null) {
            $sql .= ", maBGH = ?";
        }
        
        $sql .= " WHERE maDeThi = ? AND trangThai = 'Choduyet'";

        $stmt = $this->connection->prepare($sql);
        
        if ($maBGH !== null) {
            $stmt->bind_param("sii", $lyDo, $maBGH, $maDeThi);
        } else {
            $stmt->bind_param("si", $lyDo, $maDeThi);
        }

        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Xóa đề thi (chỉ được xóa nếu chưa duyệt)
     * 
     * @param int $maDeThi Mã đề thi
     * @return bool Kết quả
     */
    public function deleteExam($maDeThi)
    {
        $sql = "DELETE FROM dethi WHERE maDeThi = ? AND trangThai = 'Choduyet'";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maDeThi);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Kiểm tra trùng lặp đề thi
     * 
     * @param int $maMonHoc Mã môn học
     * @param int $hocKy Học kỳ
     * @param string $namHoc Năm học
     * @param string $loaiDeThi Loại đề thi
     * @param int|null $excludeId Loại trừ ID (khi update)
     * @return bool True nếu trùng
     */
    public function checkDuplicate($maMonHoc, $hocKy, $namHoc, $loaiDeThi, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM dethi 
                WHERE maMonHoc = ? 
                AND hocKy = ? 
                AND namHoc = ?
                AND loaiDeThi = ?
                AND trangThai != 'Tuchoi'";

        if ($excludeId !== null) {
            $sql .= " AND maDeThi != ?";
        }

        $stmt = $this->connection->prepare($sql);
        
        if ($excludeId !== null) {
            $stmt->bind_param("iissi", $maMonHoc, $hocKy, $namHoc, $loaiDeThi, $excludeId);
        } else {
            $stmt->bind_param("iiss", $maMonHoc, $hocKy, $namHoc, $loaiDeThi);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row['count'] > 0;
    }

    /**
     * Lấy danh sách môn học
     * 
     * @return array Danh sách môn học
     */
    public function getAllSubjects()
    {
        $sql = "SELECT maMonHoc, tenMonHoc 
                FROM monhoc 
                ORDER BY tenMonHoc ASC";
        
        $result = $this->connection->query($sql);
        $subjects = [];
        
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        
        return $subjects;
    }

    /**
     * Lấy thông tin TTBM từ maTaiKhoan
     * 
     * @param int $maTaiKhoan Mã tài khoản
     * @return array|null Thông tin TTBM
     */
    public function getTTBMInfo($maTaiKhoan)
    {
        $sql = "SELECT 
                    t.maTTBM,
                    t.maGV,
                    t.maTaiKhoan,
                    gv.hoTen,
                    gv.toBoMon,
                    gv.email
                FROM ttbm t
                INNER JOIN giaovien gv ON t.maGV = gv.maGV
                WHERE t.maTaiKhoan = ?";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maTaiKhoan);
        $stmt->execute();
        $result = $stmt->get_result();
        $ttbm = $result->fetch_assoc();
        $stmt->close();

        return $ttbm;
    }

    /**
     * Lấy thống kê đề thi theo trạng thái
     * 
     * @param int|null $maTTBM Mã TTBM (null = tất cả)
     * @return array Thống kê
     */
    public function getStatistics($maTTBM = null)
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN trangThai = 'Chuaduyet' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN trangThai = 'Daduyet' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN trangThai = 'Dachon' THEN 1 ELSE 0 END) as selected,
                    SUM(CASE WHEN trangThai = 'Tuchoi' THEN 1 ELSE 0 END) as rejected
                FROM dethi
                WHERE 1=1";

        if ($maTTBM !== null) {
            $sql .= " AND maTTBM = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->bind_param("i", $maTTBM);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->connection->query($sql);
        }

        $stats = $result->fetch_assoc();

        if (isset($stmt)) {
            $stmt->close();
        }

        return $stats;
    }

    /**
     * Lấy danh sách đề thi theo môn học
     * 
     * @param int $maMonHoc Mã môn học
     * @param int $maTTBM Mã TTBM
     * @return array Danh sách đề thi
     */
    public function getExamsBySubject($maMonHoc, $maTTBM)
    {
        $sql = "SELECT 
                    maDeThi,
                    tenDeThi,
                    hocKy,
                    namHoc,
                    trangThai,
                    loaiDeThi,
                    DATE_FORMAT(ngayTao, '%d/%m/%Y') as ngayTao
                FROM dethi
                WHERE maMonHoc = ? AND maTTBM = ?
                ORDER BY namHoc DESC, hocKy DESC, ngayTao DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("ii", $maMonHoc, $maTTBM);
        $stmt->execute();
        $result = $stmt->get_result();

        $exams = [];
        while ($row = $result->fetch_assoc()) {
            $exams[] = $row;
        }

        $stmt->close();
        return $exams;
    }
}
?>
