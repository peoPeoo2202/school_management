<?php
include_once("mConnect.php");

class mInsertGrade
{
    private $conn;

    public function __construct()
    {
        $db = new mConnect();
        $this->conn = $db->mConnect();
    }

    /**
     * Lấy danh sách môn học mà giáo viên dạy
     * @param int $maGV - Mã giáo viên
     * @return array Danh sách môn học
     */
    public function getSubjectsByTeacher($maGV)
    {
        $sql = "SELECT DISTINCT v.maMonHoc, v.tenMonHoc 
                FROM v_phancong_giangday v
                WHERE v.maGV = ?
                ORDER BY v.tenMonHoc";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getSubjectsByTeacher: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    }

    /**
     * Lấy danh sách lớp mà giáo viên dạy môn học cụ thể
     * @param int $maGV - Mã giáo viên
     * @param int $maMonHoc - Mã môn học
     * @return array Danh sách lớp
     */
    public function getClassesByTeacherAndSubject($maGV, $maMonHoc)
    {
        $sql = "SELECT DISTINCT v.maLop, v.tenLop, v.khoiLop, v.namHoc
                FROM v_phancong_giangday v
                WHERE v.maGV = ? AND v.maMonHoc = ?
                ORDER BY v.tenLop";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getClassesByTeacherAndSubject: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("ii", $maGV, $maMonHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    }

    /**
     * Lấy danh sách học sinh trong lớp với điểm của môn học cụ thể
     * @param int $maLop - Mã lớp
     * @param int $maMonHoc - Mã môn học
     * @param int $hocKy - Học kỳ
     * @param string $namHoc - Năm học
     * @return array Danh sách học sinh và điểm
     * 
     * Mapping cột điểm:
     * - diemTX1 = TX1 (Điểm Miệng)
     * - diemTX2 = TX2 (Điểm 15 phút lần 1)
     * - diemTX3 = TX3 (Điểm 15 phút lần 2)
     * - diemTX4 = TX4 (Điểm 1 tiết)
     * - diemGiuaKy = ĐĐĐG gk (Điểm Giữa Kỳ)
     * - diemCuoiKy = ĐĐĐG ck (Điểm Cuối Kỳ)
     */
    public function getStudentsWithGrades($maLop, $maMonHoc, $hocKy, $namHoc)
    {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen,
                    bd.maBangDiem,
                    bd.diemTX1,         -- TX1: Điểm Miệng
                    bd.diemTX2,         -- TX2: Điểm 15 phút lần 1
                    bd.diemTX3,         -- TX3: Điểm 15 phút lần 2
                    bd.diemTX4,         -- TX4: Điểm 1 tiết
                    bd.diemGiuaKy,      -- ĐĐĐG gk: Điểm Giữa Kỳ
                    bd.diemCuoiKy,      -- ĐĐĐG ck: Điểm Cuối Kỳ
                    bd.tbDiem,          -- ĐTB mhk: Điểm Trung Bình Môn Học Kỳ
                    bd.nhanXet          -- Nhận xét của giáo viên
                FROM hocsinh hs
                LEFT JOIN bangdiem bd ON hs.maHS = bd.maHS 
                    AND bd.maMonHoc = ? 
                    AND bd.hocKy = ? 
                    AND bd.namHoc = ?
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getStudentsWithGrades: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("iisi", $maMonHoc, $hocKy, $namHoc, $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    }

    /**
     * Kiểm tra giáo viên có quyền nhập điểm môn học cho lớp không
     * @param int $maGV - Mã giáo viên
     * @param int $maLop - Mã lớp
     * @param int $maMonHoc - Mã môn học
     * @return bool
     */
    public function checkTeacherPermission($maGV, $maLop, $maMonHoc)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM v_phancong_giangday 
                WHERE maGV = ? AND maLop = ? AND maMonHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in checkTeacherPermission: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("iii", $maGV, $maLop, $maMonHoc);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result['count'] > 0;
    }

    /**
     * Tính điểm trung bình môn học
     * @param array $grades - Mảng điểm gồm các cột điểm
     * @return float|null
     * 
     * Công thức tính điểm TB mới:
     * - Điểm đánh giá thường xuyên (ĐGTX) = Trung bình cộng của TX1, TX2, TX3, TX4
     * - ĐTB Môn = (ĐGTX × 1 + ĐĐĐGgk × 2 + ĐĐĐGck × 3) / 6
     * 
     * Trong đó:
     * - TX1, TX2, TX3, TX4: Điểm thường xuyên
     * - ĐĐĐG gk (Điểm Giữa Kỳ) - Hệ số 2
     * - ĐĐĐG ck (Điểm Cuối Kỳ) - Hệ số 3
     */
    private function calculateAverage($grades)
    {
        // Lấy điểm giữa kỳ và cuối kỳ
        $diemGK = ($grades['diemGiuaKy'] !== null && $grades['diemGiuaKy'] !== '') ? floatval($grades['diemGiuaKy']) : null;
        $diemCK = ($grades['diemCuoiKy'] !== null && $grades['diemCuoiKy'] !== '') ? floatval($grades['diemCuoiKy']) : null;
        
        // ĐIỀU KIỆN BẮT BUỘC: Phải có điểm GK VÀ CK mới tính được điểm trung bình
        if ($diemGK === null || $diemCK === null) {
            return null;
        }
        
        // Tính điểm trung bình môn theo công thức:
        // ĐTB = (TX1×1 + TX2×1 + TX3×1 + TX4×1 + GK×2 + CK×3) / (số TX có + 2 + 3)
        // Mỗi điểm TX có hệ số 1, không tính trung bình TX trước
        $sum = 0;
        $totalCoefficient = 0;
        
        // Cộng từng điểm TX với hệ số 1
        if ($grades['diemTX1'] !== null && $grades['diemTX1'] !== '') {
            $sum += floatval($grades['diemTX1']) * 1;
            $totalCoefficient += 1;
        }
        if ($grades['diemTX2'] !== null && $grades['diemTX2'] !== '') {
            $sum += floatval($grades['diemTX2']) * 1;
            $totalCoefficient += 1;
        }
        if ($grades['diemTX3'] !== null && $grades['diemTX3'] !== '') {
            $sum += floatval($grades['diemTX3']) * 1;
            $totalCoefficient += 1;
        }
        if ($grades['diemTX4'] !== null && $grades['diemTX4'] !== '') {
            $sum += floatval($grades['diemTX4']) * 1;
            $totalCoefficient += 1;
        }
        
        // Cộng điểm GK với hệ số 2
        $sum += $diemGK * 2;
        $totalCoefficient += 2;
        
        // Cộng điểm CK với hệ số 3
        $sum += $diemCK * 3;
        $totalCoefficient += 3;
        
        // Chia tổng cho tổng hệ số (8 hoặc 9 tùy số điểm TX)
        return round($sum / $totalCoefficient, 2);
    }

    /**
     * Lưu hoặc cập nhật điểm cho học sinh
     * @param int $maHS - Mã học sinh
     * @param int $maMonHoc - Mã môn học
     * @param int $hocKy - Học kỳ
     * @param string $namHoc - Năm học
     * @param array $grades - Mảng điểm
     * @return array Kết quả
     */
    public function saveGrades($maHS, $maMonHoc, $hocKy, $namHoc, $grades)
    {
        try {
            // Kiểm tra xem có ít nhất 1 điểm được nhập không
            $hasAnyGrade = false;
            foreach ($grades as $grade) {
                if ($grade !== null && $grade !== '') {
                    $hasAnyGrade = true;
                    break;
                }
            }
            
            // Nếu không có điểm nào, không làm gì cả
            if (!$hasAnyGrade) {
                return ['success' => true, 'message' => 'Không có điểm để lưu'];
            }
            
            // Tính điểm trung bình CHỈ KHI có đủ điểm GK và CK
            // Kiểm tra điều kiện bắt buộc: phải có cả điểm giữa kỳ và cuối kỳ
            $hasGK = isset($grades['diemGiuaKy']) && $grades['diemGiuaKy'] !== null && $grades['diemGiuaKy'] !== '';
            $hasCK = isset($grades['diemCuoiKy']) && $grades['diemCuoiKy'] !== null && $grades['diemCuoiKy'] !== '';
            
            // Chỉ tính điểm trung bình khi có đủ GK VÀ CK
            $tbDiem = ($hasGK && $hasCK) ? $this->calculateAverage($grades) : null;
            
            // Kiểm tra xem bảng điểm đã tồn tại chưa
            // Thêm LIMIT 1 để tránh lỗi khi có duplicate records
            $sql = "SELECT maBangDiem FROM bangdiem 
                    WHERE maHS = ? AND maMonHoc = ? AND hocKy = ? AND namHoc = ?
                    LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'message' => 'Lỗi SQL: ' . $this->conn->error];
            }
            $stmt->bind_param("iiis", $maHS, $maMonHoc, $hocKy, $namHoc);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();
            $stmt->close();
            
            if ($existing) {
                // Đã có điểm -> Đọc lại toàn bộ điểm hiện có từ database
                $sqlSelect = "SELECT diemTX1, diemTX2, diemTX3, diemTX4, diemGiuaKy, diemCuoiKy, nhanXet 
                              FROM bangdiem WHERE maBangDiem = ?";
                $stmtSelect = $this->conn->prepare($sqlSelect);
                if (!$stmtSelect) {
                    return ['success' => false, 'message' => 'Lỗi SQL SELECT: ' . $this->conn->error];
                }
                $stmtSelect->bind_param("i", $existing['maBangDiem']);
                $stmtSelect->execute();
                $currentGrades = $stmtSelect->get_result()->fetch_assoc();
                $stmtSelect->close();
                
                // Merge điểm mới với điểm cũ (ưu tiên điểm mới nếu có)
                $tx1 = isset($grades['diemTX1']) && $grades['diemTX1'] !== null && $grades['diemTX1'] !== '' 
                       ? $grades['diemTX1'] : $currentGrades['diemTX1'];
                $tx2 = isset($grades['diemTX2']) && $grades['diemTX2'] !== null && $grades['diemTX2'] !== '' 
                       ? $grades['diemTX2'] : $currentGrades['diemTX2'];
                $tx3 = isset($grades['diemTX3']) && $grades['diemTX3'] !== null && $grades['diemTX3'] !== '' 
                       ? $grades['diemTX3'] : $currentGrades['diemTX3'];
                $tx4 = isset($grades['diemTX4']) && $grades['diemTX4'] !== null && $grades['diemTX4'] !== '' 
                       ? $grades['diemTX4'] : $currentGrades['diemTX4'];
                $gk = isset($grades['diemGiuaKy']) && $grades['diemGiuaKy'] !== null && $grades['diemGiuaKy'] !== '' 
                      ? $grades['diemGiuaKy'] : $currentGrades['diemGiuaKy'];
                $ck = isset($grades['diemCuoiKy']) && $grades['diemCuoiKy'] !== null && $grades['diemCuoiKy'] !== '' 
                      ? $grades['diemCuoiKy'] : $currentGrades['diemCuoiKy'];
                $nhanXet = isset($grades['nhanXet']) && $grades['nhanXet'] !== null && $grades['nhanXet'] !== '' 
                           ? $grades['nhanXet'] : $currentGrades['nhanXet'];
                
                // Tính lại điểm trung bình dựa trên dữ liệu đầy đủ (cả cũ + mới)
                $mergedGrades = [
                    'diemTX1' => $tx1,
                    'diemTX2' => $tx2,
                    'diemTX3' => $tx3,
                    'diemTX4' => $tx4,
                    'diemGiuaKy' => $gk,
                    'diemCuoiKy' => $ck
                ];
                $tbDiemNew = $this->calculateAverage($mergedGrades);
                
                // UPDATE tất cả các cột với giá trị đã merge
                $sql = "UPDATE bangdiem SET 
                        diemTX1 = ?,
                        diemTX2 = ?,
                        diemTX3 = ?,
                        diemTX4 = ?,
                        diemGiuaKy = ?,
                        diemCuoiKy = ?,
                        tbDiem = ?,
                        nhanXet = ?
                        WHERE maBangDiem = ?";
                
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    return ['success' => false, 'message' => 'Lỗi SQL UPDATE: ' . $this->conn->error];
                }
                
                $stmt->bind_param(
                    "dddddddsi",
                    $tx1,
                    $tx2,
                    $tx3,
                    $tx4,
                    $gk,
                    $ck,
                    $tbDiemNew,
                    $nhanXet,
                    $existing['maBangDiem']
                );
                
                if ($stmt->execute()) {
                    $stmt->close();
                    return ['success' => true, 'message' => 'Cập nhật điểm thành công'];
                } else {
                    $error = $stmt->error;
                    $stmt->close();
                    return ['success' => false, 'message' => 'Lỗi UPDATE: ' . $error];
                }
            } else {
                // Chưa có điểm -> Thêm mới
                $sql = "INSERT INTO bangdiem 
                        (maHS, maMonHoc, hocKy, namHoc, diemTX1, diemTX2, diemTX3, 
                         diemTX4, diemGiuaKy, diemCuoiKy, tbDiem, nhanXet)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $nhanXet = isset($grades['nhanXet']) ? $grades['nhanXet'] : null;
                
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    return ['success' => false, 'message' => 'Lỗi SQL INSERT: ' . $this->conn->error];
                }
                $stmt->bind_param(
                    "iiisddddddds",
                    $maHS,
                    $maMonHoc,
                    $hocKy,
                    $namHoc,
                    $grades['diemTX1'],
                    $grades['diemTX2'],
                    $grades['diemTX3'],
                    $grades['diemTX4'],
                    $grades['diemGiuaKy'],
                    $grades['diemCuoiKy'],
                    $tbDiem,
                    $nhanXet
                );
                
                if ($stmt->execute()) {
                    $stmt->close();
                    return ['success' => true, 'message' => 'Thêm mới điểm thành công'];
                } else {
                    $error = $stmt->error;
                    $stmt->close();
                    return ['success' => false, 'message' => 'Lỗi INSERT: ' . $error];
                }
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * Lấy thông tin học kỳ hiện tại (có thể tùy chỉnh theo logic của trường)
     * @return array
     */
    public function getCurrentSemester()
    {
        // Logic đơn giản: lấy từ tháng hiện tại
        $month = date('n');
        $year = date('Y');
        
        // Tháng 8-12: Học kỳ 1, Tháng 1-5: Học kỳ 2
        if ($month >= 8 && $month <= 12) {
            $hocKy = 1;
            $namHoc = $year . '-' . ($year + 1);
        } else {
            $hocKy = 2;
            $namHoc = ($year - 1) . '-' . $year;
        }
        
        return [
            'hocKy' => $hocKy,
            'namHoc' => $namHoc
        ];
    }

    /**
     * Lấy danh sách năm học có sẵn
     * @return array
     */
    public function getAvailableYears()
    {
        $sql = "SELECT DISTINCT namHoc FROM lophoc ORDER BY namHoc DESC";
        $result = $this->conn->query($sql);
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row['namHoc'];
        }
        
        return $data;
    }

    public function __destruct()
    {
        if ($this->conn) {
            mysqli_close($this->conn);
        }
    }
}
?>