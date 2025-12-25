<?php
/**
 * Model: Phân công coi thi (Exam Proctor Assignment)
 * Chức năng: Quản lý phân công giáo viên coi thi theo kỳ thi
 * Actor: Tổ trưởng bộ môn (TTBM)
 * 
 * @author IMAX Team
 * @version 1.0
 * @date 2025-12-21
 */

require_once(__DIR__ . '/mConnect.php');

class mExamProctorAssignment
{
    private $connection;

    public function __construct()
    {
        $db = new mConnect();
        $this->connection = $db->mConnect();
    }

    /**
     * Lấy thông tin TTBM từ maTaiKhoan
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
     * Lấy danh sách khối
     */
    public function getAllGrades()
    {
        $sql = "SELECT maKhoi, khoiLop FROM khoi ORDER BY khoiLop";
        $result = $this->connection->query($sql);
        
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
        return $grades;
    }

    /**
     * Lấy danh sách lớp theo khối
     */
    public function getClassesByGrade($maKhoi)
    {
        $sql = "SELECT maLop, tenLop FROM lophoc WHERE maKhoi = ? ORDER BY tenLop";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maKhoi);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $classes = [];
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
        $stmt->close();
        return $classes;
    }

    /**
     * Lấy danh sách kỳ thi theo khối
     */
    public function getExamPeriodsByGrade($maKhoi = null)
    {
        $sql = "SELECT 
                    kt.maKyThi,
                    kt.tenKyThi,
                    kt.loaiKyThi,
                    kt.maKhoi,
                    k.khoiLop,
                    kt.hocKy,
                    kt.namHoc,
                    kt.ngayBatDau,
                    kt.ngayKetThuc,
                    kt.trangThai
                FROM kythi kt
                LEFT JOIN khoi k ON kt.maKhoi = k.maKhoi
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if ($maKhoi) {
            $sql .= " AND kt.maKhoi = ?";
            $params[] = $maKhoi;
            $types .= "i";
        }
        
        $sql .= " ORDER BY kt.ngayBatDau DESC";
        
        if (!empty($params)) {
            $stmt = $this->connection->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->connection->query($sql);
        }
        
        $exams = [];
        while ($row = $result->fetch_assoc()) {
            $exams[] = $row;
        }
        
        return $exams;
    }

    /**
     * Lấy danh sách môn thi theo kỳ thi
     */
    public function getSubjectsByExam($maKyThi)
    {
        $sql = "SELECT DISTINCT 
                    m.maMonHoc,
                    m.tenMonHoc
                FROM lichthi lt
                INNER JOIN monhoc m ON lt.maMonHoc = m.maMonHoc
                WHERE lt.maKyThi = ?
                ORDER BY m.tenMonHoc";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maKyThi);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        $stmt->close();
        return $subjects;
    }

    /**
     * Lấy danh sách ca thi (lịch thi) theo kỳ thi, môn học
     */
    public function getExamSchedules($maKyThi, $maMonHoc = null, $maLop = null)
    {
        $sql = "SELECT 
                    lt.maLichThi,
                    lt.maKyThi,
                    kt.tenKyThi,
                    kt.loaiKyThi,
                    lt.maMonHoc,
                    m.tenMonHoc,
                    lt.ngayThi,
                    lt.gioBatDau,
                    lt.gioKetThuc,
                    lt.maPhong,
                    p.tenPhong,
                    p.soLuongSV as sucChua,
                    lt.maGVCoiThi,
                    lt.trangThai,
                    (SELECT COUNT(*) FROM phancongcoithi pc 
                     WHERE pc.maPhong = lt.maPhong 
                     AND pc.ngayThi = lt.ngayThi 
                     AND pc.gioBatDau = lt.gioBatDau) as soGVDaPhanCong
                FROM lichthi lt
                INNER JOIN kythi kt ON lt.maKyThi = kt.maKyThi
                INNER JOIN monhoc m ON lt.maMonHoc = m.maMonHoc
                LEFT JOIN phong p ON lt.maPhong = p.maPhong
                WHERE lt.maKyThi = ?";
        
        $params = [$maKyThi];
        $types = "i";
        
        if ($maMonHoc) {
            $sql .= " AND lt.maMonHoc = ?";
            $params[] = $maMonHoc;
            $types .= "i";
        }
        
        $sql .= " ORDER BY lt.ngayThi, lt.gioBatDau";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $schedules = [];
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
        $stmt->close();
        return $schedules;
    }

    /**
     * Lấy danh sách giáo viên có thể coi thi
     * 
     * @param string $toBoMon Tổ bộ môn của TTBM
     * @param string $ngayThi Ngày thi
     * @param string $gioBatDau Giờ bắt đầu
     * @param string $gioKetThuc Giờ kết thúc
     * @param bool $chuaPhanCong Chỉ lấy GV chưa phân công
     */
    public function getAvailableTeachers($toBoMon, $ngayThi = null, $gioBatDau = null, $gioKetThuc = null, $chuaPhanCong = false)
    {
        // Query đơn giản hơn
        $sql = "SELECT 
                    gv.maGV,
                    gv.hoTen,
                    gv.email,
                    gv.soDienThoai,
                    gv.toBoMon
                FROM giaovien gv 
                WHERE gv.toBoMon = ?
                ORDER BY gv.hoTen";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $toBoMon);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $teachers = [];
        while ($row = $result->fetch_assoc()) {
            // Thêm thông tin bổ sung
            $row['soCaDaPhanCong'] = 0;
            $row['trungLich'] = 0;
            
            if ($ngayThi) {
                // Đếm số ca đã phân công trong ngày
                $countSql = "SELECT COUNT(*) as cnt FROM phancongcoithi WHERE maGV = ? AND ngayThi = ?";
                $countStmt = $this->connection->prepare($countSql);
                $countStmt->bind_param("is", $row['maGV'], $ngayThi);
                $countStmt->execute();
                $countResult = $countStmt->get_result()->fetch_assoc();
                $row['soCaDaPhanCong'] = $countResult['cnt'];
                $countStmt->close();
                
                // Kiểm tra trùng lịch
                if ($gioBatDau && $gioKetThuc) {
                    $conflictSql = "SELECT COUNT(*) as cnt FROM phancongcoithi 
                                    WHERE maGV = ? AND ngayThi = ?
                                    AND ((gioBatDau < ? AND gioKetThuc > ?)
                                         OR (gioBatDau < ? AND gioKetThuc > ?))";
                    $conflictStmt = $this->connection->prepare($conflictSql);
                    $conflictStmt->bind_param("isssss", $row['maGV'], $ngayThi, $gioKetThuc, $gioBatDau, $gioKetThuc, $gioBatDau);
                    $conflictStmt->execute();
                    $conflictResult = $conflictStmt->get_result()->fetch_assoc();
                    $row['trungLich'] = $conflictResult['cnt'];
                    $conflictStmt->close();
                }
            }
            
            // Lọc nếu cần
            if ($chuaPhanCong && $row['trungLich'] > 0) {
                continue;
            }
            
            $teachers[] = $row;
        }
        $stmt->close();
        return $teachers;
    }

    /**
     * Lấy danh sách GV đã phân công cho ca thi
     */
    public function getAssignedTeachers($maPhong, $ngayThi, $gioBatDau)
    {
        $sql = "SELECT 
                    pc.maPhanCong,
                    pc.maGV,
                    gv.hoTen,
                    gv.email,
                    pc.viTriCoiThi,
                    pc.trangThai
                FROM phancongcoithi pc
                INNER JOIN giaovien gv ON pc.maGV = gv.maGV
                WHERE pc.maPhong = ? AND pc.ngayThi = ? AND pc.gioBatDau = ?
                ORDER BY pc.viTriCoiThi";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("iss", $maPhong, $ngayThi, $gioBatDau);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $teachers = [];
        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }
        $stmt->close();
        return $teachers;
    }

    /**
     * Kiểm tra GV có bị trùng lịch không
     */
    public function checkTeacherConflict($maGV, $ngayThi, $gioBatDau, $gioKetThuc)
    {
        $sql = "SELECT 
                    pc.maPhanCong,
                    p.tenPhong,
                    pc.ngayThi,
                    pc.gioBatDau,
                    pc.gioKetThuc
                FROM phancongcoithi pc
                LEFT JOIN phong p ON pc.maPhong = p.maPhong
                WHERE pc.maGV = ? 
                AND pc.ngayThi = ?
                AND ((pc.gioBatDau <= ? AND pc.gioKetThuc > ?)
                     OR (pc.gioBatDau < ? AND pc.gioKetThuc >= ?)
                     OR (pc.gioBatDau >= ? AND pc.gioKetThuc <= ?))";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("isssssss", $maGV, $ngayThi, $gioBatDau, $gioBatDau, $gioKetThuc, $gioKetThuc, $gioBatDau, $gioKetThuc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $conflicts = [];
        while ($row = $result->fetch_assoc()) {
            $conflicts[] = $row;
        }
        $stmt->close();
        
        return $conflicts;
    }

    /**
     * Tạo phân công coi thi
     */
    public function createProctorAssignment($data)
    {
        // Nếu maLop = 0, lấy maLop đầu tiên từ database (để tránh FK error)
        $maLop = $data['maLop'];
        if ($maLop == 0 || $maLop == null) {
            $result = $this->connection->query("SELECT maLop FROM lophoc LIMIT 1");
            if ($result && $row = $result->fetch_assoc()) {
                $maLop = $row['maLop'];
            }
        }
        
        $sql = "INSERT INTO phancongcoithi 
                (maGV, maLop, maMonHoc, maPhong, loaiKyThi, ngayThi, gioBatDau, gioKetThuc, viTriCoiThi, trangThai)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("iiiisssss", 
            $data['maGV'],
            $maLop,
            $data['maMonHoc'],
            $data['maPhong'],
            $data['loaiKyThi'],
            $data['ngayThi'],
            $data['gioBatDau'],
            $data['gioKetThuc'],
            $data['viTriCoiThi']
        );
        
        $result = $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();
        
        return $result ? $insertId : false;
    }

    /**
     * Xóa phân công coi thi
     */
    public function deleteProctorAssignment($maPhanCong)
    {
        $sql = "DELETE FROM phancongcoithi WHERE maPhanCong = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $maPhanCong);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Gửi thông báo cho giáo viên
     * Note: Bảng thongbao không có maTaiKhoan, nên tạm skip
     */
    public function sendNotificationToTeacher($maGV, $noiDung, $loaiThongBao = 'phan_cong_coi_thi')
    {
        // Tạm thời không gửi thông báo vì bảng thongbao không đủ cấu trúc
        // Có thể implement sau khi bảng thongbao được cập nhật với maTaiKhoan, ngayTao
        return true;
    }

    /**
     * Phân công tự động
     */
    public function autoAssignProctors($maKyThi, $maMonHoc, $toBoMon, $soGVMoiCa = 2)
    {
        // Lấy danh sách ca thi chưa đủ GV
        $schedules = $this->getExamSchedules($maKyThi, $maMonHoc);
        $results = [];
        
        foreach ($schedules as $schedule) {
            if ($schedule['soGVDaPhanCong'] >= $soGVMoiCa) {
                continue; // Đã đủ GV
            }
            
            $canPhanCong = $soGVMoiCa - $schedule['soGVDaPhanCong'];
            
            // Lấy GV chưa bị trùng lịch
            $teachers = $this->getAvailableTeachers(
                $toBoMon,
                $schedule['ngayThi'],
                $schedule['gioBatDau'],
                $schedule['gioKetThuc'],
                true // Chỉ lấy GV chưa phân công
            );
            
            $assigned = 0;
            foreach ($teachers as $teacher) {
                if ($assigned >= $canPhanCong) break;
                
                // Kiểm tra trùng lịch
                $conflicts = $this->checkTeacherConflict(
                    $teacher['maGV'],
                    $schedule['ngayThi'],
                    $schedule['gioBatDau'],
                    $schedule['gioKetThuc']
                );
                
                if (empty($conflicts)) {
                    $viTri = ($schedule['soGVDaPhanCong'] + $assigned + 1);
                    
                    $data = [
                        'maGV' => $teacher['maGV'],
                        'maLop' => 0, // Sẽ cập nhật nếu cần
                        'maMonHoc' => $schedule['maMonHoc'],
                        'maPhong' => $schedule['maPhong'],
                        'loaiKyThi' => $schedule['loaiKyThi'],
                        'ngayThi' => $schedule['ngayThi'],
                        'gioBatDau' => $schedule['gioBatDau'],
                        'gioKetThuc' => $schedule['gioKetThuc'],
                        'viTriCoiThi' => "Giám thị " . $viTri
                    ];
                    
                    $insertId = $this->createProctorAssignment($data);
                    
                    if ($insertId) {
                        $assigned++;
                        $results[] = [
                            'schedule' => $schedule,
                            'teacher' => $teacher,
                            'success' => true
                        ];
                        
                        // Gửi thông báo
                        $noiDung = "Bạn được phân công coi thi môn {$schedule['tenMonHoc']} "
                                 . "vào ngày {$schedule['ngayThi']} "
                                 . "từ {$schedule['gioBatDau']} đến {$schedule['gioKetThuc']} "
                                 . "tại phòng {$schedule['tenPhong']}.";
                        $this->sendNotificationToTeacher($teacher['maGV'], $noiDung);
                    }
                }
            }
        }
        
        return $results;
    }

    /**
     * Lấy thống kê phân công coi thi theo tổ
     */
    public function getStatistics($toBoMon)
    {
        $stats = [];
        
        // Tổng phân công
        $sql = "SELECT COUNT(*) as total 
                FROM phancongcoithi pc
                INNER JOIN giaovien gv ON pc.maGV = gv.maGV
                WHERE gv.toBoMon = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $toBoMon);
        $stmt->execute();
        $stats['total'] = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        
        // Scheduled
        $sql = "SELECT COUNT(*) as scheduled 
                FROM phancongcoithi pc
                INNER JOIN giaovien gv ON pc.maGV = gv.maGV
                WHERE gv.toBoMon = ? AND pc.trangThai = 'scheduled'";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $toBoMon);
        $stmt->execute();
        $stats['scheduled'] = $stmt->get_result()->fetch_assoc()['scheduled'];
        $stmt->close();
        
        // Completed
        $sql = "SELECT COUNT(*) as completed 
                FROM phancongcoithi pc
                INNER JOIN giaovien gv ON pc.maGV = gv.maGV
                WHERE gv.toBoMon = ? AND pc.trangThai = 'completed'";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $toBoMon);
        $stmt->execute();
        $stats['completed'] = $stmt->get_result()->fetch_assoc()['completed'];
        $stmt->close();
        
        // Số GV trong tổ
        $sql = "SELECT COUNT(*) as teacherCount FROM giaovien WHERE toBoMon = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $toBoMon);
        $stmt->execute();
        $stats['teacherCount'] = $stmt->get_result()->fetch_assoc()['teacherCount'];
        $stmt->close();
        
        return $stats;
    }

    /**
     * Lấy danh sách năm học
     */
    public function getSchoolYears()
    {
        $sql = "SELECT DISTINCT namHoc FROM kythi ORDER BY namHoc DESC";
        $result = $this->connection->query($sql);
        
        $years = [];
        while ($row = $result->fetch_assoc()) {
            $years[] = $row['namHoc'];
        }
        return $years;
    }
}
