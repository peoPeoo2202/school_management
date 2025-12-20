<?php
class mSubmitHomework
{
    private $conn;

    public function __construct()
    {
        require_once(__DIR__ . '/mConnect.php');
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    public function getHomeworkById($maBaiTap)
    {
        $sql = "SELECT bt.*, l.tenLop, m.tenMonHoc
                FROM baitap bt
                LEFT JOIN lophoc l ON bt.maLop = l.maLop
                LEFT JOIN monhoc m ON bt.maMonHoc = m.maMonHoc
                WHERE bt.maBaiTap = ? AND (bt.anBai IS NULL OR bt.anBai = 0)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maBaiTap);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getSubmissionByStudent($maBaiTap, $maHS)
    {
        // Debug: Log parameters
        error_log("DEBUG getSubmissionByStudent: maBaiTap = " . $maBaiTap . ", maHS = " . $maHS);

        $sql = "SELECT * FROM bainop WHERE maBaiTap = ? AND maHS = ? ORDER BY ngayNop DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $maBaiTap, $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        $submission = $result->fetch_assoc();

        // Debug: Log result
        if ($submission) {
            error_log("DEBUG getSubmissionByStudent: Found submission maBaiNop=" . $submission['maBaiNop'] . " for maHS=" . $submission['maHS']);
        } else {
            error_log("DEBUG getSubmissionByStudent: No submission found");
        }

        return $submission;
    }

    public function submitHomework($maBaiTap, $maHS, $tenFile, $duongDan, $noiDung)
    {
        // Đặt timezone chính xác
        date_default_timezone_set('Asia/Ho_Chi_Minh');

        // Lấy thông tin bài tập để kiểm tra deadline
        $hwSql = "SELECT thoiGianNop FROM baitap WHERE maBaiTap = ?";
        $hwStmt = $this->conn->prepare($hwSql);
        $hwStmt->bind_param("i", $maBaiTap);
        $hwStmt->execute();
        $hwResult = $hwStmt->get_result()->fetch_assoc();

        // Xác định trạng thái dựa vào thời gian nộp
        $trangThai = 'Chuacham'; // Mặc định
        if ($hwResult) {
            $deadline = strtotime($hwResult['thoiGianNop']);
            $currentTime = time();

            // Nếu nộp sau deadline thì trạng thái là "Tre" (Nộp trễ)
            if ($currentTime > $deadline) {
                $trangThai = 'Tre';
            }
        }

        // Kiểm tra đã nộp chưa
        $checkSql = "SELECT maBaiNop FROM bainop WHERE maBaiTap = ? AND maHS = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $maBaiTap, $maHS);
        $checkStmt->execute();
        $existingSubmission = $checkStmt->get_result()->fetch_assoc();

        if ($existingSubmission) {
            // Cập nhật bài nộp
            $sql = "UPDATE bainop SET tenFile = ?, duongDan = ?, noiDung = ?, ngayNop = NOW(), trangThai = ? 
                    WHERE maBaiTap = ? AND maHS = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssii", $tenFile, $duongDan, $noiDung, $trangThai, $maBaiTap, $maHS);
        } else {
            // Thêm bài nộp mới
            $sql = "INSERT INTO bainop (maBaiTap, maHS, tenFile, duongDan, noiDung, ngayNop, trangThai) 
                    VALUES (?, ?, ?, ?, ?, NOW(), ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iissss", $maBaiTap, $maHS, $tenFile, $duongDan, $noiDung, $trangThai);
        }

        return $stmt->execute();
    }

    public function getSubjectsForStudent($maHS)
    {
        // Lấy thông tin lớp của học sinh
        $lop = null;
        $lopSql = "SELECT maLop FROM hocsinh WHERE maHS = ?";
        $lopStmt = $this->conn->prepare($lopSql);
        $lopStmt->bind_param("i", $maHS);
        $lopStmt->execute();
        $lopResult = $lopStmt->get_result();
        if ($lopRow = $lopResult->fetch_assoc()) {
            $lop = $lopRow['maLop'];
        }

        if (!$lop) {
            return []; // Không tìm thấy lớp
        }

        // Lấy TẤT CẢ môn học trong hệ thống và thông tin bài tập của lớp đó
        // Đếm bài tập dựa trên bảng baitap (không cần phân công giảng dạy)
        // Logic quá hạn: Tính TẤT CẢ bài đã qua deadline
        $sql = "SELECT DISTINCT 
        m.maMonHoc, 
        m.tenMonHoc,

        COALESCE(COUNT(DISTINCT bt.maBaiTap), 0) as tongBaiTap,

        COALESCE(COUNT(DISTINCT bn.maBaiTap), 0) as daNop,

        COALESCE(COUNT(DISTINCT CASE 
            WHEN bt.maBaiTap IS NOT NULL
             AND bt.thoiGianNop < NOW()
             AND bn.maBaiTap IS NULL
            THEN bt.maBaiTap
        END), 0) as quaHan,

        CASE 
            WHEN pg.maPhanCong IS NOT NULL THEN 1 
            ELSE 0 
        END as duocPhanCong

        FROM monhoc m
        LEFT JOIN phancong_giangday pg ON pg.maMonHoc = m.maMonHoc 
            AND pg.maLop = ? 
            AND pg.trangThai = 'active'

        LEFT JOIN baitap bt ON bt.maMonHoc = m.maMonHoc 
            AND bt.maLop = ?
            AND (bt.anBai IS NULL OR bt.anBai = 0)

        LEFT JOIN bainop bn ON bt.maBaiTap = bn.maBaiTap AND bn.maHS = ?

        GROUP BY m.maMonHoc, m.tenMonHoc
        ORDER BY m.tenMonHoc";


        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getSubjectsForStudent: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param("iii", $lop, $lop, $maHS);

        $stmt->execute();
        $result = $stmt->get_result();

        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        return $subjects;
    }

    public function getHomeworkForStudent($maHS, $maMonHoc = null)
    {
        // Debug: Log maHS để kiểm tra
        error_log("DEBUG getHomeworkForStudent: maHS = " . $maHS . ", maMonHoc = " . ($maMonHoc ?? 'NULL'));

        // Subquery để lấy maBaiNop của bản nộp MỚI NHẤT cho mỗi bài tập
        // Sử dụng MAX(maBaiNop) để đảm bảo chỉ lấy 1 bản ghi duy nhất
        $sql = "SELECT 
                bt.maBaiTap,
                bt.tenBaiTap, 
                bt.yeuCauBaiTap,
                bt.thoiGianNop,
                bt.choPhepNopTre,
                bt.khoaBai,
                bt.tenFile,
                bt.duongDan,
                m.tenMonHoc, 
                l.tenLop,
                bn_latest.maBaiNop, 
                bn_latest.ngayNop, 
                bn_latest.trangThai, 
                bn_latest.diem, 
                bn_latest.nhanXet,
                CASE WHEN bn_latest.ngayNop IS NOT NULL THEN 1 ELSE 0 END as daNop
                FROM hocsinh hs
                INNER JOIN lophoc l ON hs.maLop = l.maLop
                INNER JOIN baitap bt ON bt.maLop = l.maLop AND (bt.anBai IS NULL OR bt.anBai = 0)
                INNER JOIN monhoc m ON bt.maMonHoc = m.maMonHoc
                LEFT JOIN (
                    -- Subquery: Lấy bản nộp MỚI NHẤT (maBaiNop MAX trong ngayNop MAX) cho mỗi bài tập
                    SELECT bn1.*
                    FROM bainop bn1
                    INNER JOIN (
                        SELECT maBaiTap, maHS, MAX(maBaiNop) as maxMaBaiNop
                        FROM bainop
                        WHERE maHS = ?
                        GROUP BY maBaiTap, maHS
                    ) bn2 ON bn1.maBaiTap = bn2.maBaiTap 
                        AND bn1.maHS = bn2.maHS 
                        AND bn1.maBaiNop = bn2.maxMaBaiNop
                ) bn_latest ON bt.maBaiTap = bn_latest.maBaiTap AND bn_latest.maHS = ?
                WHERE hs.maHS = ?";

        if ($maMonHoc) {
            $sql .= " AND m.maMonHoc = ?";
        }

        $sql .= " ORDER BY bt.thoiGianNop DESC";

        $stmt = $this->conn->prepare($sql);
        if ($maMonHoc) {
            $stmt->bind_param("iiii", $maHS, $maHS, $maHS, $maMonHoc);
        } else {
            $stmt->bind_param("iii", $maHS, $maHS, $maHS);
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
