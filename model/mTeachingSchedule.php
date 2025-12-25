<?php
require_once('mConnect.php');

class mTeachingSchedule
{
    private $conn;
    private $mConnect;

    public function __construct()
    {
        $this->mConnect = new mConnect();
        $this->conn = $this->mConnect->mConnect();
    }

    public function __destruct()
    {
        if ($this->conn) {
            $this->mConnect->mDisconnect($this->conn);
        }
    }

    /**
     * Lấy danh sách lớp học mà giáo viên đang giảng dạy
     * 
     * @param int $maGV Mã giáo viên
     * @param int|null $hocKy Học kỳ (1 hoặc 2)
     * @param string|null $namHoc Năm học (VD: 2024-2025)
     * @param int|null $maMonHoc Mã môn học
     * @param int|null $maKhoi Khối lớp (10, 11, 12)
     * @return array Danh sách lớp học
     */
    public function getTeacherClasses($maGV, $hocKy = null, $namHoc = null, $maMonHoc = null, $maKhoi = null)
    {
        $sql = "SELECT DISTINCT
                    l.maLop,
                    l.tenLop,
                    k.khoiLop,
                    l.siSo,
                    l.namHoc,
                    p.tenPhong,
                    gvcn.hoTen AS giaoVienChuNhiem,
                    mh.tenMonHoc,
                    mh.maMonHoc,
                    ld.hocKy,
                    COUNT(DISTINCT ld.maLichDay) AS soTietTrongTuan
                FROM lichday ld
                JOIN giaovien gv ON ld.maGV = gv.maGV
                JOIN lophoc l ON ld.maLop = l.maLop
                JOIN monhoc mh ON ld.maMonHoc = mh.maMonHoc
                JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN phong p ON l.maPhong = p.maPhong
                LEFT JOIN giaovien gvcn ON l.maGV = gvcn.maGV
                WHERE ld.maGV = ?
                  AND ld.trangThai = 'active'";

        $params = [$maGV];
        $types = "i";

        if ($hocKy !== null) {
            $sql .= " AND ld.hocKy = ?";
            $params[] = $hocKy;
            $types .= "i";
        }

        if ($namHoc !== null) {
            $sql .= " AND ld.namHoc = ?";
            $params[] = $namHoc;
            $types .= "s";
        }

        if ($maMonHoc !== null) {
            $sql .= " AND ld.maMonHoc = ?";
            $params[] = $maMonHoc;
            $types .= "i";
        }

        if ($maKhoi !== null) {
            $sql .= " AND k.maKhoi = ?";
            $params[] = $maKhoi;
            $types .= "i";
        }

        $sql .= " GROUP BY l.maLop, mh.maMonHoc, ld.hocKy
                  ORDER BY k.khoiLop, l.tenLop";

        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Lỗi prepare statement: ' . mysqli_error($this->conn)];
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $classes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $classes[] = $row;
        }

        mysqli_stmt_close($stmt);

        return [
            'success' => true,
            'data' => $classes,
            'total' => count($classes)
        ];
    }

    /**
     * Lấy lịch dạy (thời khóa biểu) của giáo viên
     * 
     * @param int $maGV Mã giáo viên
     * @param int $hocKy Học kỳ
     * @param string $namHoc Năm học
     * @param int|null $maLop Mã lớp (để lọc theo lớp cụ thể)
     * @param int|null $thu Thứ trong tuần (2-8, để lọc theo ngày)
     * @return array Lịch dạy
     */
    public function getTeachingSchedule($maGV, $hocKy, $namHoc, $maLop = null, $thu = null)
    {
        $sql = "SELECT 
                    ld.maLichDay,
                    ld.thu,
                    ld.tietBatDau,
                    ld.tietKetThuc,
                    mh.tenMonHoc,
                    mh.maMonHoc,
                    l.tenLop,
                    l.maLop,
                    p.tenPhong,
                    p.maPhong,
                    ld.trangThai,
                    CONCAT('Tiết ', ld.tietBatDau, '-', ld.tietKetThuc) AS tietHoc,
                    CASE 
                        WHEN ld.tietBatDau = 1 THEN '07:00'
                        WHEN ld.tietBatDau = 2 THEN '07:50'
                        WHEN ld.tietBatDau = 3 THEN '08:50'
                        WHEN ld.tietBatDau = 4 THEN '09:40'
                        WHEN ld.tietBatDau = 5 THEN '10:35'
                        WHEN ld.tietBatDau = 6 THEN '13:30'
                        WHEN ld.tietBatDau = 7 THEN '14:20'
                        WHEN ld.tietBatDau = 8 THEN '15:20'
                        WHEN ld.tietBatDau = 9 THEN '16:10'
                        WHEN ld.tietBatDau = 10 THEN '17:00'
                    END AS gioBatDau
                FROM lichday ld
                JOIN monhoc mh ON ld.maMonHoc = mh.maMonHoc
                JOIN lophoc l ON ld.maLop = l.maLop
                JOIN phong p ON ld.maPhong = p.maPhong
                WHERE ld.maGV = ?
                  AND ld.hocKy = ?
                  AND ld.namHoc = ?
                  AND ld.trangThai = 'active'";

        $params = [$maGV, $hocKy, $namHoc];
        $types = "iis";

        if ($maLop !== null) {
            $sql .= " AND ld.maLop = ?";
            $params[] = $maLop;
            $types .= "i";
        }

        if ($thu !== null) {
            $sql .= " AND ld.thu = ?";
            $params[] = $thu;
            $types .= "i";
        }

        $sql .= " ORDER BY ld.thu, ld.tietBatDau";

        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Lỗi prepare statement: ' . mysqli_error($this->conn)];
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $schedule = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $schedule[] = $row;
        }

        mysqli_stmt_close($stmt);

        return [
            'success' => true,
            'data' => $schedule,
            'total' => count($schedule)
        ];
    }

    /**
     * Lấy danh sách phân công coi thi của giáo viên
     * 
     * @param int $maGV Mã giáo viên
     * @param int|null $hocKy Học kỳ
     * @param string|null $namHoc Năm học
     * @param string|null $trangThai Trạng thái (scheduled, in_progress, completed, cancelled)
     * @param string|null $loaiKyThi Loại kỳ thi (Giữa kỳ, Cuối kỳ, Kiểm tra 15 phút)
     * @return array Danh sách phân công coi thi
     */
    public function getExamSupervision($maGV, $hocKy = null, $namHoc = null, $trangThai = null, $loaiKyThi = null)
    {
        $sql = "SELECT 
                    pc.maPhanCong,
                    pc.loaiKyThi,
                    mh.tenMonHoc,
                    mh.maMonHoc,
                    l.tenLop,
                    l.maLop,
                    p.tenPhong,
                    p.maPhong,
                    pc.ngayThi,
                    pc.gioBatDau,
                    pc.gioKetThuc,
                    pc.viTriCoiThi,
                    pc.trangThai,
                    CONCAT(DATE_FORMAT(pc.ngayThi, '%d/%m/%Y'), ' ', 
                           TIME_FORMAT(pc.gioBatDau, '%H:%i'), '-', 
                           TIME_FORMAT(pc.gioKetThuc, '%H:%i')) AS thoiGianThi,
                    DATEDIFF(pc.ngayThi, CURDATE()) AS soNgayConLai
                FROM phancongcoithi pc
                JOIN monhoc mh ON pc.maMonHoc = mh.maMonHoc
                JOIN lophoc l ON pc.maLop = l.maLop
                JOIN phong p ON pc.maPhong = p.maPhong
                WHERE pc.maGV = ?";

        $params = [$maGV];
        $types = "i";

        // Lưu ý: Bảng phancongcoithi không có cột hocKy và namHoc
        // Không filter theo 2 tham số này (giữ lại để tương thích signature)

        if ($trangThai !== null) {
            $sql .= " AND pc.trangThai = ?";
            $params[] = $trangThai;
            $types .= "s";
        }

        if ($loaiKyThi !== null) {
            $sql .= " AND pc.loaiKyThi = ?";
            $params[] = $loaiKyThi;
            $types .= "s";
        }

        $sql .= " ORDER BY pc.ngayThi, pc.gioBatDau";

        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Lỗi prepare statement: ' . mysqli_error($this->conn)];
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $examSupervision = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $examSupervision[] = $row;
        }

        mysqli_stmt_close($stmt);

        return [
            'success' => true,
            'data' => $examSupervision,
            'total' => count($examSupervision)
        ];
    }

    /**
     * Lấy danh sách phân công chấm điểm của giáo viên
     * 
     * @param int $maGV Mã giáo viên
     * @param int|null $hocKy Học kỳ
     * @param string|null $namHoc Năm học
     * @param string|null $trangThai Trạng thái (pending, in_progress, completed, cancelled)
     * @param string|null $loaiKiemTra Loại kiểm tra (Miệng, 15 phút, 1 tiết, Giữa kỳ, Cuối kỳ)
     * @return array Danh sách phân công chấm điểm
     */
    public function getGradingAssignment($maGV, $hocKy = null, $namHoc = null, $trangThai = null, $loaiKiemTra = null)
    {
        $sql = "SELECT 
                    pc.maPhanCong,
                    pc.loaiKiemTra,
                    mh.tenMonHoc,
                    mh.maMonHoc,
                    l.tenLop,
                    l.maLop,
                    l.siSo AS soLuongHocSinh,
                    pc.ngayCham,
                    pc.hinhThucCham,
                    pc.trangThai,
                    -- Lưu ý: Tạm thời set giá trị mặc định vì cấu trúc bảng bangdiem chưa rõ
                    0 AS soDaCham,
                    0 AS phanTramHoanThanh
                FROM phancongchamdiem pc
                JOIN monhoc mh ON pc.maMonHoc = mh.maMonHoc
                JOIN lophoc l ON pc.maLop = l.maLop
                WHERE pc.maGV = ?";

        $params = [$maGV];
        $types = "i";

        // Lưu ý: Bảng phancongchamdiem không có cột hocKy và namHoc
        // Không filter theo 2 tham số này (giữ lại để tương thích signature)

        if ($trangThai !== null) {
            $sql .= " AND pc.trangThai = ?";
            $params[] = $trangThai;
            $types .= "s";
        }

        if ($loaiKiemTra !== null) {
            $sql .= " AND pc.loaiKiemTra = ?";
            $params[] = $loaiKiemTra;
            $types .= "s";
        }

        $sql .= " ORDER BY 
                    FIELD(pc.trangThai, 'in_progress', 'pending', 'completed', 'cancelled'),
                    pc.ngayCham";

        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Lỗi prepare statement: ' . mysqli_error($this->conn)];
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $gradingAssignment = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $gradingAssignment[] = $row;
        }

        mysqli_stmt_close($stmt);

        return [
            'success' => true,
            'data' => $gradingAssignment,
            'total' => count($gradingAssignment)
        ];
    }

    /**
     * Lấy thông tin tổng quan cho dashboard giáo viên
     * 
     * @param int $maGV Mã giáo viên
     * @param int $hocKy Học kỳ hiện tại
     * @param string $namHoc Năm học hiện tại
     * @return array Thông tin tổng quan
     */
    public function getDashboardSummary($maGV, $hocKy, $namHoc)
    {
        $summary = [
            'success' => true,
            'data' => []
        ];

        // Đếm số lớp đang dạy
        $sqlClasses = "SELECT COUNT(DISTINCT ld.maLop) as soLop
                       FROM lichday ld
                       WHERE ld.maGV = ? AND ld.hocKy = ? AND ld.namHoc = ? 
                         AND ld.trangThai = 'active'";
        $stmt = mysqli_prepare($this->conn, $sqlClasses);
        mysqli_stmt_bind_param($stmt, "iis", $maGV, $hocKy, $namHoc);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $summary['data']['soLopDangDay'] = $row['soLop'];
        mysqli_stmt_close($stmt);

        // Đếm số tiết dạy trong tuần
        $sqlLessons = "SELECT COUNT(*) as soTiet
                       FROM lichday ld
                       WHERE ld.maGV = ? AND ld.hocKy = ? AND ld.namHoc = ?
                         AND ld.trangThai = 'active'";
        $stmt = mysqli_prepare($this->conn, $sqlLessons);
        mysqli_stmt_bind_param($stmt, "iis", $maGV, $hocKy, $namHoc);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $summary['data']['soTietTrongTuan'] = $row['soTiet'];
        mysqli_stmt_close($stmt);

        // Đếm số ca thi sắp tới (trong vòng 7 ngày)
        // Lưu ý: Bảng phancongcoithi không có cột hocKy và namHoc, chỉ lọc theo maGV
        $sqlExams = "SELECT COUNT(*) as soCaThi
                     FROM phancongcoithi pc
                     WHERE pc.maGV = ?
                       AND pc.trangThai = 'scheduled'
                       AND pc.ngayThi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        $stmt = mysqli_prepare($this->conn, $sqlExams);
        mysqli_stmt_bind_param($stmt, "i", $maGV);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $summary['data']['soCaThiSapToi'] = $row['soCaThi'];
        mysqli_stmt_close($stmt);

        // Đếm số phân công chấm đang làm
        // Lưu ý: Bảng phancongchamdiem không có cột hocKy và namHoc, chỉ lọc theo maGV
        $sqlGrading = "SELECT COUNT(*) as soChamDangLam
                       FROM phancongchamdiem pc
                       WHERE pc.maGV = ?
                         AND pc.trangThai IN ('pending', 'in_progress')";
        $stmt = mysqli_prepare($this->conn, $sqlGrading);
        mysqli_stmt_bind_param($stmt, "i", $maGV);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $summary['data']['soChamDangLam'] = $row['soChamDangLam'];
        mysqli_stmt_close($stmt);

        return $summary;
    }

    /**
     * Lấy chi tiết một tiết dạy
     * 
     * @param int $maLichDay Mã lịch dạy
     * @return array Chi tiết lịch dạy
     */
    public function getScheduleDetail($maLichDay)
    {
        $sql = "SELECT 
                    ld.*,
                    mh.tenMonHoc,
                    l.tenLop,
                    p.tenPhong,
                    gv.hoTen AS tenGiaoVien,
                    k.khoiLop
                FROM lichday ld
                JOIN monhoc mh ON ld.maMonHoc = mh.maMonHoc
                JOIN lophoc l ON ld.maLop = l.maLop
                JOIN phong p ON ld.maPhong = p.maPhong
                JOIN giaovien gv ON ld.maGV = gv.maGV
                JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE ld.maLichDay = ?";

        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Lỗi prepare statement: ' . mysqli_error($this->conn)];
        }

        mysqli_stmt_bind_param($stmt, "i", $maLichDay);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            return [
                'success' => true,
                'data' => $row
            ];
        }

        mysqli_stmt_close($stmt);
        return [
            'success' => false,
            'message' => 'Không tìm thấy lịch dạy'
        ];
    }

    /**
     * Lấy danh sách lớp từ view phân công giảng dạy
     * 
     * @param int $maGV Mã giáo viên
     * @param string|null $namHoc Năm học (VD: 2024-2025)
     * @return array Danh sách lớp học
     */
    public function getClassesFromPhanCong($maGV, $namHoc = null)
    {
        $sql = "SELECT DISTINCT
                    maLop,
                    tenLop,
                    khoiLop,
                    maMonHoc,
                    tenMonHoc,
                    hocKy
                FROM v_phancong_giangday
                WHERE maGV = ?
                  AND trangThai = 'active'";

        $params = [$maGV];
        $types = "i";

        if ($namHoc !== null) {
            $sql .= " AND namHoc = ?";
            $params[] = $namHoc;
            $types .= "s";
        }

        $sql .= " ORDER BY khoiLop, tenLop, hocKy";

        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Lỗi prepare statement: ' . mysqli_error($this->conn)];
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $classes = [];
        $uniqueClasses = []; // Để loại bỏ trùng lặp theo maLop

        while ($row = mysqli_fetch_assoc($result)) {
            // Chỉ thêm nếu chưa có lớp này trong danh sách
            if (!isset($uniqueClasses[$row['maLop']])) {
                $uniqueClasses[$row['maLop']] = true;
                $classes[] = $row;
            }
        }

        mysqli_stmt_close($stmt);

        return [
            'success' => true,
            'data' => $classes,
            'total' => count($classes)
        ];
    }
}
?>
