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
        $sql = "SELECT DISTINCT mh.maMonHoc, mh.tenMonHoc 
                FROM lichday ld
                JOIN monhoc mh ON ld.maMonHoc = mh.maMonHoc
                WHERE ld.maGV = ?
                ORDER BY mh.tenMonHoc";
        
        $stmt = $this->conn->prepare($sql);
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
        $sql = "SELECT DISTINCT lh.maLop, lh.tenLop, k.khoiLop, lh.namHoc
                FROM lichday ld
                JOIN lophoc lh ON ld.maLop = lh.maLop
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                WHERE ld.maGV = ? AND ld.maMonHoc = ?
                ORDER BY lh.tenLop";
        
        $stmt = $this->conn->prepare($sql);
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
                FROM lichday 
                WHERE maGV = ? AND maLop = ? AND maMonHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
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
        // Kiểm tra xem có ít nhất 1 điểm được nhập không
        $hasAnyGrade = false;
        foreach ($grades as $grade) {
            if ($grade !== null && $grade !== '') {
                $hasAnyGrade = true;
                break;
            }
        }
        
        if (!$hasAnyGrade) {
            return null;
        }
        
        // Tính điểm đánh giá thường xuyên (trung bình cộng TX1, TX2, TX3, TX4)
        $txScores = [];
        if ($grades['diemTX1'] !== null && $grades['diemTX1'] !== '') {
            $txScores[] = floatval($grades['diemTX1']);
        }
        if ($grades['diemTX2'] !== null && $grades['diemTX2'] !== '') {
            $txScores[] = floatval($grades['diemTX2']);
        }
        if ($grades['diemTX3'] !== null && $grades['diemTX3'] !== '') {
            $txScores[] = floatval($grades['diemTX3']);
        }
        if ($grades['diemTX4'] !== null && $grades['diemTX4'] !== '') {
            $txScores[] = floatval($grades['diemTX4']);
        }
        
        // Tính trung bình thường xuyên
        $diemTX = count($txScores) > 0 ? array_sum($txScores) / count($txScores) : null;
        
        // Lấy điểm giữa kỳ và cuối kỳ
        $diemGK = ($grades['diemGiuaKy'] !== null && $grades['diemGiuaKy'] !== '') ? floatval($grades['diemGiuaKy']) : null;
        $diemCK = ($grades['diemCuoiKy'] !== null && $grades['diemCuoiKy'] !== '') ? floatval($grades['diemCuoiKy']) : null;
        
        // Tính điểm trung bình môn: (ĐGTX × 1 + ĐGK × 2 + ĐCK × 3) / 6
        $sum = 0;
        $totalCoefficient = 0;
        
        if ($diemTX !== null) {
            $sum += $diemTX * 1;
            $totalCoefficient += 1;
        }
        
        if ($diemGK !== null) {
            $sum += $diemGK * 2;
            $totalCoefficient += 2;
        }
        
        if ($diemCK !== null) {
            $sum += $diemCK * 3;
            $totalCoefficient += 3;
        }
        
        return $totalCoefficient > 0 ? round($sum / $totalCoefficient, 2) : null;
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
            
            // Tính điểm trung bình
            $tbDiem = $this->calculateAverage($grades);
            
            // Kiểm tra xem bảng điểm đã tồn tại chưa
            $sql = "SELECT maBangDiem FROM bangdiem 
                    WHERE maHS = ? AND maMonHoc = ? AND hocKy = ? AND namHoc = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiis", $maHS, $maMonHoc, $hocKy, $namHoc);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();
            $stmt->close();
            
            if ($existing) {
                // Đã có điểm -> Cập nhật
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
                
                $nhanXet = isset($grades['nhanXet']) ? $grades['nhanXet'] : null;
                
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param(
                    "dddddddsi",
                    $grades['diemTX1'],
                    $grades['diemTX2'],
                    $grades['diemTX3'],
                    $grades['diemTX4'],
                    $grades['diemGiuaKy'],
                    $grades['diemCuoiKy'],
                    $tbDiem,
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