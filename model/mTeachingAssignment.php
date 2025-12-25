<?php
/**
 * Model: mTeachingAssignment.php
 * Quản lý các chức năng phân công giảng dạy
 * - Phân công học sinh đầu cấp vào lớp
 * - Phân công phòng học cho lớp
 * - Phân công giáo viên bộ môn (GVBM)
 * - Phân công giáo viên chủ nhiệm (GVCN)
 */

require_once(__DIR__ . '/mConnect.php');

class mTeachingAssignment
{
    private $conn;

    public function __construct()
    {
        $connect = new mConnect();
        $this->conn = $connect->mConnect();
    }

    public function __destruct()
    {
        if ($this->conn) {
            mysqli_close($this->conn);
        }
    }

    // ==================== PHÂN CÔNG HỌC SINH ĐẦU CẤP ====================

    /**
     * Lấy danh sách học sinh chưa có lớp (đầu cấp)
     */
    public function getStudentsWithoutClass()
    {
        $sql = "SELECT hs.maHS, hs.hoTen, hs.ngaySinh, hs.gioiTinh, hs.diaChi, hs.trangThaiHocTap
                FROM hocsinh hs
                WHERE hs.maLop IS NULL AND hs.trangThaiHocTap = 'danghoc'
                ORDER BY hs.hoTen ASC";
        $result = mysqli_query($this->conn, $sql);
        $students = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row;
        }
        return $students;
    }

    /**
     * Lấy danh sách lớp đầu cấp (khối 6)
     */
    public function getFirstYearClasses($namHoc = null)
    {
        if (!$namHoc) {
            $namHoc = $this->getCurrentSchoolYear();
        }
        $sql = "SELECT l.maLop, l.tenLop, l.siSo, l.namHoc, k.khoiLop, p.tenPhong
                FROM lophoc l
                JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN phancong_lop_phong plp ON l.maLop = plp.maLop AND plp.namHoc = l.namHoc
                LEFT JOIN phong p ON plp.maPhong = p.maPhong
                WHERE k.khoiLop = '6' AND l.namHoc = ?
                ORDER BY l.tenLop ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $namHoc);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $classes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $classes[] = $row;
        }
        return $classes;
    }

    /**
     * Lấy danh sách lớp chưa có học sinh
     */
    public function getEmptyClasses($maKhoi = null, $namHoc = null)
    {
        if (!$namHoc) {
            $namHoc = $this->getCurrentSchoolYear();
        }
        $sql = "SELECT l.maLop, l.tenLop, l.siSo, l.namHoc, k.khoiLop, p.tenPhong
                FROM lophoc l
                JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN phancong_lop_phong plp ON l.maLop = plp.maLop AND plp.namHoc = l.namHoc
                LEFT JOIN phong p ON plp.maPhong = p.maPhong
                WHERE l.siSo = 0 AND l.namHoc = ?";
        if ($maKhoi) {
            $sql .= " AND l.maKhoi = ?";
        }
        $sql .= " ORDER BY l.tenLop ASC";

        $stmt = mysqli_prepare($this->conn, $sql);
        if ($maKhoi) {
            mysqli_stmt_bind_param($stmt, "si", $namHoc, $maKhoi);
        } else {
            mysqli_stmt_bind_param($stmt, "s", $namHoc);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $classes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $classes[] = $row;
        }
        return $classes;
    }

    /**
     * Phân công học sinh vào lớp (thủ công)
     */
    public function assignStudentsToClass($studentIds, $maLop, $namHoc, $nguoiPhanCong, $loaiPhanCong = 'daucap')
    {
        mysqli_begin_transaction($this->conn);
        try {
            foreach ($studentIds as $maHS) {
                // Kiểm tra học sinh đã được phân công chưa
                $checkSql = "SELECT maHS FROM hocsinh WHERE maHS = ? AND maLop IS NOT NULL";
                $checkStmt = mysqli_prepare($this->conn, $checkSql);
                mysqli_stmt_bind_param($checkStmt, "i", $maHS);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);
                if (mysqli_num_rows($checkResult) > 0) {
                    throw new Exception("Học sinh mã $maHS đã được phân lớp trước đó.");
                }

                // Thêm vào bảng phân công
                $sql = "INSERT INTO phancong_hocsinh_lop (maHS, maLop, namHoc, loaiPhanCong, nguoiPhanCong, ghiChu)
                        VALUES (?, ?, ?, ?, ?, 'Phân công thủ công bởi BGH')";
                $stmt = mysqli_prepare($this->conn, $sql);
                mysqli_stmt_bind_param($stmt, "iissi", $maHS, $maLop, $namHoc, $loaiPhanCong, $nguoiPhanCong);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Lỗi phân công học sinh: " . mysqli_error($this->conn));
                }

                // Cập nhật maLop trong bảng hocsinh
                $updateSql = "UPDATE hocsinh SET maLop = ? WHERE maHS = ?";
                $updateStmt = mysqli_prepare($this->conn, $updateSql);
                mysqli_stmt_bind_param($updateStmt, "ii", $maLop, $maHS);
                mysqli_stmt_execute($updateStmt);
            }
            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Phân công học sinh thành công!'];
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Phân lớp tự động cho học sinh đầu cấp
     */
    public function autoAssignStudentsToClasses($options, $namHoc, $nguoiPhanCong)
    {
        mysqli_begin_transaction($this->conn);
        try {
            // Lấy danh sách học sinh chưa có lớp
            $students = $this->getStudentsWithoutClass();
            if (empty($students)) {
                throw new Exception("Không có học sinh nào cần phân lớp.");
            }

            // Lấy danh sách lớp đầu cấp
            $classes = $this->getFirstYearClasses($namHoc);
            if (empty($classes)) {
                throw new Exception("Không có lớp đầu cấp nào.");
            }

            // Phân loại học sinh theo giới tính nếu cần
            $maleStudents = [];
            $femaleStudents = [];
            if (isset($options['chia_deu_nam_nu']) && $options['chia_deu_nam_nu']) {
                foreach ($students as $student) {
                    if ($student['gioiTinh'] === 'Nam') {
                        $maleStudents[] = $student;
                    } else {
                        $femaleStudents[] = $student;
                    }
                }
                // Shuffle để phân ngẫu nhiên
                shuffle($maleStudents);
                shuffle($femaleStudents);
            } else {
                shuffle($students);
            }

            $numClasses = count($classes);
            $classIndex = 0;

            // Phân công theo tiêu chí
            if (isset($options['chia_deu_theo_lop']) && $options['chia_deu_theo_lop']) {
                // Chia đều học sinh theo số lớp hiện có
                if (isset($options['chia_deu_nam_nu']) && $options['chia_deu_nam_nu']) {
                    // Chia đều nam
                    foreach ($maleStudents as $student) {
                        $maLop = $classes[$classIndex % $numClasses]['maLop'];
                        $this->insertStudentAssignment($student['maHS'], $maLop, $namHoc, $nguoiPhanCong, 'daucap');
                        $classIndex++;
                    }
                    // Chia đều nữ
                    $classIndex = 0;
                    foreach ($femaleStudents as $student) {
                        $maLop = $classes[$classIndex % $numClasses]['maLop'];
                        $this->insertStudentAssignment($student['maHS'], $maLop, $namHoc, $nguoiPhanCong, 'daucap');
                        $classIndex++;
                    }
                } else {
                    foreach ($students as $student) {
                        $maLop = $classes[$classIndex % $numClasses]['maLop'];
                        $this->insertStudentAssignment($student['maHS'], $maLop, $namHoc, $nguoiPhanCong, 'daucap');
                        $classIndex++;
                    }
                }
            } elseif (isset($options['so_luong_hs_lop']) && $options['so_luong_hs_lop'] > 0) {
                // Chia theo số lượng học sinh/lớp
                $maxPerClass = (int)$options['so_luong_hs_lop'];
                $currentClassCount = [];
                foreach ($classes as $class) {
                    $currentClassCount[$class['maLop']] = 0;
                }

                $allStudents = isset($options['chia_deu_nam_nu']) && $options['chia_deu_nam_nu']
                    ? array_merge($maleStudents, $femaleStudents)
                    : $students;

                foreach ($allStudents as $student) {
                    foreach ($classes as $class) {
                        if ($currentClassCount[$class['maLop']] < $maxPerClass) {
                            $this->insertStudentAssignment($student['maHS'], $class['maLop'], $namHoc, $nguoiPhanCong, 'daucap');
                            $currentClassCount[$class['maLop']]++;
                            break;
                        }
                    }
                }
            }

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Phân lớp tự động thành công!'];
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function insertStudentAssignment($maHS, $maLop, $namHoc, $nguoiPhanCong, $loaiPhanCong)
    {
        // Thêm vào bảng phân công
        $sql = "INSERT INTO phancong_hocsinh_lop (maHS, maLop, namHoc, loaiPhanCong, nguoiPhanCong, ghiChu)
                VALUES (?, ?, ?, ?, ?, 'Phân lớp tự động bởi hệ thống')";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iissi", $maHS, $maLop, $namHoc, $loaiPhanCong, $nguoiPhanCong);
        mysqli_stmt_execute($stmt);

        // Cập nhật maLop trong bảng hocsinh
        $updateSql = "UPDATE hocsinh SET maLop = ? WHERE maHS = ?";
        $updateStmt = mysqli_prepare($this->conn, $updateSql);
        mysqli_stmt_bind_param($updateStmt, "ii", $maLop, $maHS);
        mysqli_stmt_execute($updateStmt);
    }

    /**
     * Lấy tất cả học sinh (có thể lọc theo trạng thái phân lớp)
     */
    public function getAllStudents($showOnlyWithoutClass = false)
    {
        if ($showOnlyWithoutClass) {
            return $this->getStudentsWithoutClass();
        }
        
        $sql = "SELECT hs.maHS, hs.hoTen, hs.ngaySinh, hs.gioiTinh, hs.diaChi, hs.trangThaiHocTap, 
                       hs.maLop, l.tenLop, k.khoiLop
                FROM hocsinh hs
                LEFT JOIN lophoc l ON hs.maLop = l.maLop
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE hs.trangThaiHocTap = 'danghoc'
                ORDER BY hs.maLop IS NULL DESC, k.khoiLop, l.tenLop, hs.hoTen ASC";
        $result = mysqli_query($this->conn, $sql);
        $students = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row;
        }
        return $students;
    }

    /**
     * Lấy danh sách lớp với thông tin phòng học
     */
    public function getClassesWithRoomInfo($namHoc = null, $showOnlyWithoutRoom = false)
    {
        if (!$namHoc) {
            $namHoc = $this->getCurrentSchoolYear();
        }
        
        $sql = "SELECT l.maLop, l.tenLop, l.siSo, l.namHoc, plp.maPhong, 
                       k.khoiLop, k.maKhoi, p.tenPhong, p.loaiPhong, p.soLuongSV
                FROM lophoc l
                JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN phancong_lop_phong plp ON l.maLop = plp.maLop AND plp.namHoc = l.namHoc
                LEFT JOIN phong p ON plp.maPhong = p.maPhong
                WHERE l.namHoc = ?";
        
        if ($showOnlyWithoutRoom) {
            $sql .= " AND plp.maPhong IS NULL";
        }
        
        $sql .= " ORDER BY k.khoiLop, l.tenLop ASC";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $namHoc);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $classes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $classes[] = $row;
        }
        return $classes;
    }

    /**
     * Thu hồi phân công phòng của lớp
     */
    public function revokeRoomAssignment($maLop, $namHoc = null)
    {
        if (!$namHoc) {
            $namHoc = $this->getCurrentSchoolYear();
        }
        $deleteSql = "DELETE FROM phancong_lop_phong WHERE maLop = ? AND namHoc = ?";
        $deleteStmt = mysqli_prepare($this->conn, $deleteSql);
        mysqli_stmt_bind_param($deleteStmt, "is", $maLop, $namHoc);
        if (mysqli_stmt_execute($deleteStmt)) {
            return ['success' => true, 'message' => 'Thu hồi phân công phòng thành công!'];
        }
        return ['success' => false, 'message' => 'Lỗi khi thu hồi phân công phòng!'];
    }

    /**
     * Thu hồi phân công lớp của học sinh
     */
    public function revokeStudentClass($maHS)
    {
        $updateSql = "UPDATE hocsinh SET maLop = NULL WHERE maHS = ?";
        $updateStmt = mysqli_prepare($this->conn, $updateSql);
        mysqli_stmt_bind_param($updateStmt, "i", $maHS);
        if (mysqli_stmt_execute($updateStmt)) {
            return ['success' => true, 'message' => 'Thu hồi phân công lớp thành công!'];
        }
        return ['success' => false, 'message' => 'Lỗi khi thu hồi phân công lớp!'];
    }

    // ==================== PHÂN CÔNG PHÒNG HỌC ====================

    /**
     * Lấy danh sách lớp chưa phân công phòng học
     */
    public function getClassesWithoutRoom($namHoc = null)
    {
        if (!$namHoc) {
            $namHoc = $this->getCurrentSchoolYear();
        }
        $sql = "SELECT l.maLop, l.tenLop, l.siSo, l.namHoc, k.khoiLop
                FROM lophoc l
                JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN phancong_lop_phong plp ON l.maLop = plp.maLop AND plp.namHoc = l.namHoc
                WHERE plp.maPhong IS NULL AND l.namHoc = ?
                ORDER BY k.khoiLop, l.tenLop ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $namHoc);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $classes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $classes[] = $row;
        }
        return $classes;
    }

    /**
     * Lấy danh sách tất cả phòng học
     */
    public function getAllRooms()
    {
        $sql = "SELECT p.maPhong, p.tenPhong, p.loaiPhong, p.dienTich, p.soLuongSV, p.trangThai
                FROM phong p
                ORDER BY p.tenPhong ASC";
        $result = mysqli_query($this->conn, $sql);
        $rooms = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rooms[] = $row;
        }
        return $rooms;
    }

    /**
     * Lấy danh sách phòng trống (chưa được phân cho lớp nào trong năm học)
     */
    public function getAvailableRooms($namHoc = null)
    {
        if (!$namHoc) {
            $namHoc = $this->getCurrentSchoolYear();
        }
        $sql = "SELECT p.maPhong, p.tenPhong, p.loaiPhong, p.dienTich, p.soLuongSV, p.trangThai
                FROM phong p
                WHERE p.trangThai = 'Hoatdong'
                AND p.maPhong NOT IN (
                    SELECT DISTINCT maPhong FROM phancong_lop_phong WHERE namHoc = ?
                )
                ORDER BY p.tenPhong ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $namHoc);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rooms = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rooms[] = $row;
        }
        return $rooms;
    }

    /**
     * Kiểm tra phòng có đang bảo trì không
     */
    public function isRoomUnderMaintenance($maPhong)
    {
        $sql = "SELECT trangThai FROM phong WHERE maPhong = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $maPhong);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row && $row['trangThai'] === 'Baotri';
    }

    /**
     * Kiểm tra phòng đã được phân cho lớp khác chưa (trong cùng năm học)
     */
    public function isRoomAssigned($maPhong, $namHoc, $excludeMaLop = null)
    {
        $sql = "SELECT maLop FROM phancong_lop_phong WHERE maPhong = ? AND namHoc = ?";
        if ($excludeMaLop) {
            $sql .= " AND maLop != ?";
        }
        $stmt = mysqli_prepare($this->conn, $sql);
        if ($excludeMaLop) {
            mysqli_stmt_bind_param($stmt, "isi", $maPhong, $namHoc, $excludeMaLop);
        } else {
            mysqli_stmt_bind_param($stmt, "is", $maPhong, $namHoc);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }

    /**
     * Phân công phòng học cho lớp
     */
    public function assignRoomToClass($maLop, $maPhong, $namHoc, $nguoiPhanCong)
    {
        // Kiểm tra phòng bảo trì
        if ($this->isRoomUnderMaintenance($maPhong)) {
            return ['success' => false, 'message' => 'Phòng học đang bảo trì, không thể phân công!'];
        }

        // Kiểm tra phòng đã được phân cho lớp khác chưa
        if ($this->isRoomAssigned($maPhong, $namHoc, $maLop)) {
            return ['success' => false, 'message' => 'Phòng học đã được phân cho lớp khác trong năm học này!'];
        }

        mysqli_begin_transaction($this->conn);
        try {
            // Xóa phân công cũ nếu có
            $deleteSql = "DELETE FROM phancong_lop_phong WHERE maLop = ? AND namHoc = ?";
            $deleteStmt = mysqli_prepare($this->conn, $deleteSql);
            mysqli_stmt_bind_param($deleteStmt, "is", $maLop, $namHoc);
            mysqli_stmt_execute($deleteStmt);

            // Thêm phân công mới
            $sql = "INSERT INTO phancong_lop_phong (maLop, maPhong, namHoc, nguoiPhanCong, ghiChu)
                    VALUES (?, ?, ?, ?, 'Phân công phòng học bởi BGH')";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "iisi", $maLop, $maPhong, $namHoc, $nguoiPhanCong);
            mysqli_stmt_execute($stmt);

            // Cập nhật maPhong trong bảng lophoc
            $updateSql = "UPDATE lophoc SET maPhong = ? WHERE maLop = ?";
            $updateStmt = mysqli_prepare($this->conn, $updateSql);
            mysqli_stmt_bind_param($updateStmt, "ii", $maPhong, $maLop);
            mysqli_stmt_execute($updateStmt);

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Phân công phòng học thành công!'];
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ==================== PHÂN CÔNG GIÁO VIÊN BỘ MÔN ====================

    /**
     * Lấy danh sách khối
     */
    public function getAllGrades()
    {
        $sql = "SELECT maKhoi, khoiLop FROM khoi ORDER BY khoiLop ASC";
        $result = mysqli_query($this->conn, $sql);
        $grades = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $grades[] = $row;
        }
        return $grades;
    }

    /**
     * Lấy danh sách lớp theo khối
     */
    public function getClassesByGrade($maKhoi, $namHoc = null)
    {
        if (!$namHoc) {
            $namHoc = $this->getCurrentSchoolYear();
        }
        $sql = "SELECT l.maLop, l.tenLop, l.siSo, k.khoiLop
                FROM lophoc l
                JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE l.maKhoi = ? AND l.namHoc = ?
                ORDER BY l.tenLop ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $maKhoi, $namHoc);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $classes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $classes[] = $row;
        }
        return $classes;
    }

    /**
     * Lấy danh sách môn học
     */
    public function getAllSubjects()
    {
        $sql = "SELECT maMonHoc, tenMonHoc, soTiet FROM monhoc ORDER BY tenMonHoc ASC";
        $result = mysqli_query($this->conn, $sql);
        $subjects = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $subjects[] = $row;
        }
        return $subjects;
    }

    /**
     * Lấy danh sách giáo viên
     */
    public function getAllTeachers()
    {
        $sql = "SELECT maGV, hoTen, toBoMon, email, soDienThoai FROM giaovien ORDER BY hoTen ASC";
        $result = mysqli_query($this->conn, $sql);
        $teachers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $teachers[] = $row;
        }
        return $teachers;
    }

    /**
     * Lấy danh sách giáo viên theo tổ bộ môn
     */
    public function getTeachersBySubject($toBoMon)
    {
        $sql = "SELECT maGV, hoTen, toBoMon FROM giaovien WHERE toBoMon = ? ORDER BY hoTen ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $toBoMon);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $teachers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $teachers[] = $row;
        }
        return $teachers;
    }

    /**
     * Lấy danh sách giáo viên theo mã môn học
     * Mapping tên môn học với tổ bộ môn của giáo viên
     */
    public function getTeachersByMonHoc($maMonHoc)
    {
        // Mapping tên môn học với tổ bộ môn
        $subjectMapping = [
            'Toán' => ['Toán'],
            'Vật lý' => ['Vật lý', 'Lý'],
            'Hóa học' => ['Hóa học', 'Hóa'],
            'Ngữ văn' => ['Ngữ văn', 'Văn'],
            'Lịch sử' => ['Lịch sử', 'Sử'],
            'Địa lý' => ['Địa lý', 'Địa'],
            'Sinh học' => ['Sinh học', 'Sinh'],
            'Tin học' => ['Tin học', 'Tin'],
            'Tiếng Anh' => ['Tiếng Anh', 'Anh'],
            'GDCD' => ['GDCD', 'Giáo dục công dân'],
            'Thể dục' => ['Thể dục'],
            'Công nghệ' => ['Công nghệ'],
            'Âm nhạc' => ['Âm nhạc'],
            'Mỹ thuật' => ['Mỹ thuật']
        ];

        // Lấy tên môn học từ mã môn học
        $sqlSubject = "SELECT tenMonHoc FROM monhoc WHERE maMonHoc = ?";
        $stmtSubject = mysqli_prepare($this->conn, $sqlSubject);
        mysqli_stmt_bind_param($stmtSubject, "i", $maMonHoc);
        mysqli_stmt_execute($stmtSubject);
        $resultSubject = mysqli_stmt_get_result($stmtSubject);
        $subjectRow = mysqli_fetch_assoc($resultSubject);
        
        if (!$subjectRow) {
            return [];
        }
        
        $tenMonHoc = $subjectRow['tenMonHoc'];
        
        // Tìm các tên tổ bộ môn tương ứng
        $toBoMonList = $subjectMapping[$tenMonHoc] ?? [$tenMonHoc];
        
        // Tạo placeholders cho IN clause
        $placeholders = implode(',', array_fill(0, count($toBoMonList), '?'));
        $types = str_repeat('s', count($toBoMonList));
        
        $sql = "SELECT maGV, hoTen, toBoMon FROM giaovien WHERE toBoMon IN ($placeholders) ORDER BY hoTen ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$toBoMonList);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $teachers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $teachers[] = $row;
        }
        return $teachers;
    }

    /**
     * Lấy danh sách môn học chưa được phân công GVBM cho lớp trong năm học và học kỳ
     * - Môn đã được phân công GVBM trong học kỳ đó sẽ bị ẩn
     * - Môn mà GVCN đang dạy cho lớp chủ nhiệm sẽ bị ẩn luôn
     */
    public function getUnassignedSubjectsForClass($maLop, $namHoc, $hocKy = null)
    {
        // Lấy môn học chưa được phân công cho lớp này trong học kỳ cụ thể
        // Ẩn môn mà GVCN đang dạy
        if ($hocKy) {
            $sql = "SELECT m.maMonHoc, m.tenMonHoc 
                    FROM monhoc m 
                    WHERE m.maMonHoc NOT IN (
                        SELECT DISTINCT pc.maMonHoc FROM phancong_gvbm pc 
                        WHERE pc.maLop = ? AND pc.namHoc = ? AND pc.hocKy = ?
                    )
                    AND m.maMonHoc NOT IN (
                        SELECT DISTINCT pcn.maMonHoc FROM phancong_gvcn pcn 
                        WHERE pcn.maLop = ? AND pcn.namHoc = ? AND pcn.maMonHoc IS NOT NULL
                    )
                    ORDER BY m.tenMonHoc";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "isisi", $maLop, $namHoc, $hocKy, $maLop, $namHoc);
        } else {
            // Nếu không chọn học kỳ, ẩn môn đã phân công ở bất kỳ học kỳ nào
            $sql = "SELECT m.maMonHoc, m.tenMonHoc 
                    FROM monhoc m 
                    WHERE m.maMonHoc NOT IN (
                        SELECT DISTINCT pc.maMonHoc FROM phancong_gvbm pc 
                        WHERE pc.maLop = ? AND pc.namHoc = ?
                    )
                    AND m.maMonHoc NOT IN (
                        SELECT DISTINCT pcn.maMonHoc FROM phancong_gvcn pcn 
                        WHERE pcn.maLop = ? AND pcn.namHoc = ? AND pcn.maMonHoc IS NOT NULL
                    )
                    ORDER BY m.tenMonHoc";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "isis", $maLop, $namHoc, $maLop, $namHoc);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $subjects = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $subjects[] = $row;
        }
        return $subjects;
    }

    /**
     * Lấy thông tin GVCN của lớp kèm môn học GVCN đang dạy
     */
    public function getHomeroomTeacherInfo($maLop, $namHoc)
    {
        // Lấy GVCN và môn học mà GVCN đang dạy cho lớp này (từ bảng phancong_gvcn)
        $sql = "SELECT pc.maGV, gv.hoTen, mh.maMonHoc, mh.tenMonHoc, gv.toBoMon
                FROM phancong_gvcn pc
                JOIN giaovien gv ON pc.maGV = gv.maGV
                LEFT JOIN monhoc mh ON pc.maMonHoc = mh.maMonHoc
                WHERE pc.maLop = ? AND pc.namHoc = ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $maLop, $namHoc);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_fetch_assoc($result);
    }

    /**
     * Lấy danh sách phân công GVBM theo điều kiện tìm kiếm
     */
    public function getSubjectTeacherAssignments($filters)
    {
        $sql = "SELECT pc.maPhanCong, pc.maGV, pc.maLop, pc.maMonHoc, pc.hocKy, pc.namHoc, pc.trangThai,
                       gv.hoTen as tenGV, l.tenLop, mh.tenMonHoc, k.khoiLop
                FROM phancong_gvbm pc
                JOIN giaovien gv ON pc.maGV = gv.maGV
                JOIN lophoc l ON pc.maLop = l.maLop
                JOIN monhoc mh ON pc.maMonHoc = mh.maMonHoc
                JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE pc.namHoc = ?";
        
        $params = [$filters['namHoc'] ?? $this->getCurrentSchoolYear()];
        $types = "s";

        if (!empty($filters['maKhoi'])) {
            $sql .= " AND l.maKhoi = ?";
            $params[] = $filters['maKhoi'];
            $types .= "i";
        }
        if (!empty($filters['maLop'])) {
            $sql .= " AND pc.maLop = ?";
            $params[] = $filters['maLop'];
            $types .= "i";
        }
        if (!empty($filters['maMonHoc'])) {
            $sql .= " AND pc.maMonHoc = ?";
            $params[] = $filters['maMonHoc'];
            $types .= "i";
        }
        if (!empty($filters['hocKy'])) {
            $sql .= " AND pc.hocKy = ?";
            $params[] = $filters['hocKy'];
            $types .= "i";
        }

        $sql .= " ORDER BY k.khoiLop, l.tenLop, mh.tenMonHoc, pc.hocKy";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $assignments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $assignments[] = $row;
        }
        return $assignments;
    }

    /**
     * Phân công giáo viên bộ môn
     */
    public function assignSubjectTeacher($data)
    {
        mysqli_begin_transaction($this->conn);
        try {
            $maGV = $data['maGV'];
            $maLop = $data['maLop'];
            $maMonHoc = $data['maMonHoc'];
            $namHoc = $data['namHoc'] ?? $this->getCurrentSchoolYear();
            $nguoiPhanCong = $data['nguoiPhanCong'];
            $apDungHaiKy = isset($data['apDungHaiKy']) && $data['apDungHaiKy'];

            $hocKyList = $apDungHaiKy ? [1, 2] : [$data['hocKy']];

            foreach ($hocKyList as $hocKy) {
                // Kiểm tra đã phân công chưa
                $checkSql = "SELECT maPhanCong FROM phancong_gvbm 
                             WHERE maLop = ? AND maMonHoc = ? AND hocKy = ? AND namHoc = ?";
                $checkStmt = mysqli_prepare($this->conn, $checkSql);
                mysqli_stmt_bind_param($checkStmt, "iiis", $maLop, $maMonHoc, $hocKy, $namHoc);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);

                if (mysqli_num_rows($checkResult) > 0) {
                    // Cập nhật phân công
                    $existingRow = mysqli_fetch_assoc($checkResult);
                    $updateSql = "UPDATE phancong_gvbm SET maGV = ?, nguoiPhanCong = ?, ngayPhanCong = NOW() 
                                  WHERE maPhanCong = ?";
                    $updateStmt = mysqli_prepare($this->conn, $updateSql);
                    mysqli_stmt_bind_param($updateStmt, "iii", $maGV, $nguoiPhanCong, $existingRow['maPhanCong']);
                    mysqli_stmt_execute($updateStmt);
                } else {
                    // Thêm phân công mới
                    $sql = "INSERT INTO phancong_gvbm (maGV, maLop, maMonHoc, hocKy, namHoc, nguoiPhanCong, ghiChu)
                            VALUES (?, ?, ?, ?, ?, ?, 'Phân công GVBM bởi BGH')";
                    $stmt = mysqli_prepare($this->conn, $sql);
                    mysqli_stmt_bind_param($stmt, "iiiisi", $maGV, $maLop, $maMonHoc, $hocKy, $namHoc, $nguoiPhanCong);
                    mysqli_stmt_execute($stmt);
                }
            }

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Phân công GVBM thành công!'];
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Cập nhật phân công GVBM
     */
    public function updateSubjectTeacherAssignment($maPhanCong, $maGV, $nguoiPhanCong)
    {
        $sql = "UPDATE phancong_gvbm SET maGV = ?, nguoiPhanCong = ?, ngayPhanCong = NOW() 
                WHERE maPhanCong = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $maGV, $nguoiPhanCong, $maPhanCong);
        if (mysqli_stmt_execute($stmt)) {
            return ['success' => true, 'message' => 'Cập nhật phân công thành công!'];
        }
        return ['success' => false, 'message' => 'Lỗi cập nhật: ' . mysqli_error($this->conn)];
    }

    /**
     * Xóa phân công GVBM
     */
    public function deleteSubjectTeacherAssignment($maPhanCong)
    {
        $sql = "DELETE FROM phancong_gvbm WHERE maPhanCong = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $maPhanCong);
        if (mysqli_stmt_execute($stmt)) {
            return ['success' => true, 'message' => 'Xóa phân công thành công!'];
        }
        return ['success' => false, 'message' => 'Lỗi xóa: ' . mysqli_error($this->conn)];
    }

    /**
     * Lấy chi tiết phân công GVBM
     */
    public function getSubjectTeacherAssignmentDetail($maPhanCong)
    {
        $sql = "SELECT pc.*, gv.hoTen as tenGV, l.tenLop, mh.tenMonHoc
                FROM phancong_gvbm pc
                JOIN giaovien gv ON pc.maGV = gv.maGV
                JOIN lophoc l ON pc.maLop = l.maLop
                JOIN monhoc mh ON pc.maMonHoc = mh.maMonHoc
                WHERE pc.maPhanCong = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $maPhanCong);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    // ==================== PHÂN CÔNG GIÁO VIÊN CHỦ NHIỆM ====================

    /**
     * Lấy danh sách phân công GVCN theo khối và năm học
     */
    public function getHomeroomTeacherAssignments($maKhoi = null, $namHoc = null)
    {
        if (!$namHoc) {
            $namHoc = $this->getCurrentSchoolYear();
        }
        $sql = "SELECT l.maLop, l.tenLop, l.siSo, k.khoiLop, 
                       pc.maPhanCongCN, pc.maGV, gv.hoTen as tenGV, pc.trangThai
                FROM lophoc l
                JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN phancong_gvcn pc ON l.maLop = pc.maLop AND pc.namHoc = ?
                LEFT JOIN giaovien gv ON pc.maGV = gv.maGV
                WHERE l.namHoc = ?";
        
        $params = [$namHoc, $namHoc];
        $types = "ss";

        if ($maKhoi) {
            $sql .= " AND l.maKhoi = ?";
            $params[] = $maKhoi;
            $types .= "i";
        }

        $sql .= " ORDER BY k.khoiLop, l.tenLop";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $assignments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $assignments[] = $row;
        }
        return $assignments;
    }

    /**
     * Lấy danh sách giáo viên chưa được phân công chủ nhiệm
     */
    public function getAvailableHomeroomTeachers($namHoc = null)
    {
        if (!$namHoc) {
            $namHoc = $this->getCurrentSchoolYear();
        }
        $sql = "SELECT gv.maGV, gv.hoTen, gv.toBoMon
                FROM giaovien gv
                WHERE gv.maGV NOT IN (
                    SELECT maGV FROM phancong_gvcn WHERE namHoc = ? AND trangThai = 'active'
                )
                ORDER BY gv.hoTen ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $namHoc);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $teachers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $teachers[] = $row;
        }
        return $teachers;
    }

    /**
     * Kiểm tra giáo viên đã làm chủ nhiệm lớp khác chưa
     */
    public function isTeacherAlreadyHomeroom($maGV, $namHoc, $excludeMaLop = null)
    {
        $sql = "SELECT maLop FROM phancong_gvcn WHERE maGV = ? AND namHoc = ? AND trangThai = 'active'";
        if ($excludeMaLop) {
            $sql .= " AND maLop != ?";
        }
        $stmt = mysqli_prepare($this->conn, $sql);
        if ($excludeMaLop) {
            mysqli_stmt_bind_param($stmt, "isi", $maGV, $namHoc, $excludeMaLop);
        } else {
            mysqli_stmt_bind_param($stmt, "is", $maGV, $namHoc);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }

    /**
     * Phân công giáo viên chủ nhiệm
     */
    public function assignHomeroomTeacher($maLop, $maGV, $namHoc, $nguoiPhanCong)
    {
        // Kiểm tra GV đã làm chủ nhiệm lớp khác chưa
        if ($this->isTeacherAlreadyHomeroom($maGV, $namHoc, $maLop)) {
            return ['success' => false, 'message' => 'Giáo viên này đã làm chủ nhiệm lớp khác trong năm học này!'];
        }

        mysqli_begin_transaction($this->conn);
        try {
            // Xóa phân công cũ của lớp này (nếu có)
            $deleteSql = "DELETE FROM phancong_gvcn WHERE maLop = ? AND namHoc = ?";
            $deleteStmt = mysqli_prepare($this->conn, $deleteSql);
            mysqli_stmt_bind_param($deleteStmt, "is", $maLop, $namHoc);
            mysqli_stmt_execute($deleteStmt);

            // Thêm phân công mới
            $sql = "INSERT INTO phancong_gvcn (maGV, maLop, namHoc, nguoiPhanCong, ghiChu)
                    VALUES (?, ?, ?, ?, 'Phân công GVCN bởi BGH')";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "iisi", $maGV, $maLop, $namHoc, $nguoiPhanCong);
            mysqli_stmt_execute($stmt);

            // Cập nhật maGV trong bảng lophoc
            $updateSql = "UPDATE lophoc SET maGV = ? WHERE maLop = ?";
            $updateStmt = mysqli_prepare($this->conn, $updateSql);
            mysqli_stmt_bind_param($updateStmt, "ii", $maGV, $maLop);
            mysqli_stmt_execute($updateStmt);

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Phân công GVCN thành công!'];
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Thu hồi/Xóa phân công giáo viên chủ nhiệm
     */
    public function revokeHomeroomTeacher($maLop, $namHoc)
    {
        mysqli_begin_transaction($this->conn);
        try {
            // Xóa phân công trong bảng phancong_gvcn
            $deleteSql = "DELETE FROM phancong_gvcn WHERE maLop = ? AND namHoc = ?";
            $deleteStmt = mysqli_prepare($this->conn, $deleteSql);
            mysqli_stmt_bind_param($deleteStmt, "is", $maLop, $namHoc);
            mysqli_stmt_execute($deleteStmt);

            // Cập nhật maGV = NULL trong bảng lophoc
            $updateSql = "UPDATE lophoc SET maGV = NULL WHERE maLop = ?";
            $updateStmt = mysqli_prepare($this->conn, $updateSql);
            mysqli_stmt_bind_param($updateStmt, "i", $maLop);
            mysqli_stmt_execute($updateStmt);

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Thu hồi phân công GVCN thành công!'];
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ==================== HÀM TIỆN ÍCH ====================

    /**
     * Lấy năm học hiện tại
     */
    public function getCurrentSchoolYear()
    {
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        
        if ($currentMonth >= 9) {
            return $currentYear . '-' . ($currentYear + 1);
        } else {
            return ($currentYear - 1) . '-' . $currentYear;
        }
    }

    /**
     * Lấy danh sách năm học
     */
    public function getSchoolYears()
    {
        $sql = "SELECT DISTINCT namHoc FROM lophoc ORDER BY namHoc DESC";
        $result = mysqli_query($this->conn, $sql);
        $years = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $years[] = $row['namHoc'];
        }
        return $years;
    }

    /**
     * Lấy danh sách tất cả lớp
     */
    public function getAllClasses($namHoc = null)
    {
        if (!$namHoc) {
            $namHoc = $this->getCurrentSchoolYear();
        }
        $sql = "SELECT l.maLop, l.tenLop, l.siSo, l.namHoc, k.khoiLop, p.tenPhong, plp.maPhong as maPhongPhanCong
                FROM lophoc l
                JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN phancong_lop_phong plp ON l.maLop = plp.maLop AND plp.namHoc = l.namHoc
                LEFT JOIN phong p ON plp.maPhong = p.maPhong
                WHERE l.namHoc = ?
                ORDER BY k.khoiLop, l.tenLop ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $namHoc);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $classes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $classes[] = $row;
        }
        return $classes;
    }

    /**
     * Lấy mã BGH từ mã tài khoản
     */
    public function getBGHByTaiKhoan($maTaiKhoan)
    {
        $sql = "SELECT maBGH FROM bgh WHERE maTaiKhoan = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $maTaiKhoan);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row ? $row['maBGH'] : null;
    }
}
?>
