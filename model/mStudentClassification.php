<?php
class ModelStudentClassification {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Lấy danh sách lớp của giáo viên chủ nhiệm
     */
    public function getClassesByTeacher($maGV) {
        $sql = "SELECT l.maLop, l.tenLop, l.siSo, l.namHoc, k.khoiLop
                FROM lophoc l
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                WHERE l.maGV = ?
                ORDER BY l.tenLop";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy danh sách môn học của khối
     */
    public function getSubjectsByClass($maLop) {
        // Lấy danh sách môn học từ bảng điểm của các học sinh trong lớp
        $sql = "SELECT DISTINCT mh.maMonHoc, mh.tenMonHoc
                FROM monhoc mh
                WHERE mh.maMonHoc IN (
                    SELECT DISTINCT bd.maMonHoc
                    FROM bangdiem bd
                    JOIN hocsinh hs ON bd.maHS = hs.maHS
                    WHERE hs.maLop = ?
                )
                ORDER BY mh.tenMonHoc";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = [
                'maMonHoc' => $row['maMonHoc'],
                'tenMonHoc' => $row['tenMonHoc']
            ];
        }
        
        // Nếu không có dữ liệu từ bangdiem, lấy tất cả môn học
        if (empty($subjects)) {
            $sqlAll = "SELECT maMonHoc, tenMonHoc FROM monhoc ORDER BY tenMonHoc";
            $stmtAll = $this->conn->prepare($sqlAll);
            $stmtAll->execute();
            $resultAll = $stmtAll->get_result();
            
            while ($row = $resultAll->fetch_assoc()) {
                $subjects[] = [
                    'maMonHoc' => $row['maMonHoc'],
                    'tenMonHoc' => $row['tenMonHoc']
                ];
            }
        }
        
        return $subjects;
    }
    
    /**
     * Lấy thông tin lớp
     */
    public function getClassInfo($maLop) {
        $sql = "SELECT l.*, k.khoiLop, gv.hoTen as tenGVCN
                FROM lophoc l
                LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                LEFT JOIN giaovien gv ON l.maGV = gv.maGV
                WHERE l.maLop = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Lấy danh sách học sinh và thông tin xếp loại (có danh hiệu)
     */
    public function getStudentsWithClassification($maLop, $hocKy, $namHoc) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen,
                    hs.ngaySinh,
                    hs.gioiTinh,
                    hk.loaiHK as hanhKiem,
                    hl.loaiHocLuc as hocLuc,
                    hl.diemTBHK1,
                    hl.diemTBHK2,
                    dh.tenDanhHieu as danhHieu,
                    CASE 
                        WHEN ? = 1 THEN hl.diemTBHK1
                        WHEN ? = 2 THEN hl.diemTBHK2
                        ELSE NULL
                    END as diemTBHocKy
                FROM hocsinh hs
                LEFT JOIN hanhkiem hk ON hs.maHS = hk.maHS AND hk.hocKy = ? AND hk.namHoc = ?
                LEFT JOIN hocluc hl ON hs.maHS = hl.maHS AND hl.namHoc = ?
                LEFT JOIN danhhieu dh ON hs.maDanhHieu = dh.maDanhHieu
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiissi", $hocKy, $hocKy, $hocKy, $namHoc, $namHoc, $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Lấy bảng điểm chi tiết của học sinh theo học kỳ
     */
    public function getStudentGradesDetail($maLop, $hocKy, $namHoc) {
        // Lấy danh sách tất cả các môn học từ bảng điểm
        $subjectsSql = "SELECT DISTINCT mh.maMonHoc, mh.tenMonHoc
                        FROM monhoc mh
                        WHERE mh.maMonHoc IN (
                            SELECT DISTINCT bd.maMonHoc
                            FROM bangdiem bd
                            JOIN hocsinh hs ON bd.maHS = hs.maHS
                            WHERE hs.maLop = ?
                        )
                        ORDER BY mh.tenMonHoc";
        
        $stmtSubjects = $this->conn->prepare($subjectsSql);
        $stmtSubjects->bind_param("i", $maLop);
        $stmtSubjects->execute();
        $resultSubjects = $stmtSubjects->get_result();
        
        $subjects = [];
        while ($subRow = $resultSubjects->fetch_assoc()) {
            $subjects[$subRow['maMonHoc']] = $subRow['tenMonHoc'];
        }
        $stmtSubjects->close();
        $totalSubjects = count($subjects);
        
        // Lấy danh sách học sinh và điểm của họ
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen,
                    bd.maMonHoc,
                    bd.tbDiem,
                    bd.nhanXet
                FROM hocsinh hs
                LEFT JOIN bangdiem bd ON hs.maHS = bd.maHS AND bd.hocKy = ? AND bd.namHoc = ?
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $hocKy, $namHoc, $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $maHS = $row['maHS'];
            if (!isset($students[$maHS])) {
                $students[$maHS] = [
                    'maHS' => $maHS,
                    'hoTen' => $row['hoTen'],
                    'grades' => [],
                    'totalScore' => 0,
                    'countSubjects' => 0,
                    'totalSubjectsRequired' => $totalSubjects
                ];
            }
            
            // Thêm điểm môn học nếu có
            if ($row['maMonHoc'] && $row['tbDiem'] !== null) {
                $tenMonHoc = $subjects[$row['maMonHoc']] ?? 'Unknown';
                $students[$maHS]['grades'][] = [
                    'maMonHoc' => $row['maMonHoc'],
                    'tenMonHoc' => $tenMonHoc,
                    'tbDiem' => $row['tbDiem'],
                    'nhanXet' => $row['nhanXet']
                ];
                $students[$maHS]['totalScore'] += $row['tbDiem'];
                $students[$maHS]['countSubjects']++;
            }
        }
        
        // Tính điểm trung bình CHỈ KHI ĐỦ TẤT CẢ CÁC MÔN
        foreach ($students as &$student) {
            // Kiểm tra xem học sinh đã có đủ điểm tất cả các môn chưa
            if ($student['countSubjects'] > 0 && $student['countSubjects'] >= $totalSubjects) {
                // Đủ điểm tất cả môn → tính điểm TB
                $student['diemTB_HS'] = round($student['totalScore'] / $student['countSubjects'], 2);
                $student['chuaDuDiem'] = false;
            } else {
                // Chưa đủ điểm hoặc không có môn nào → không tính điểm TB
                $student['diemTB_HS'] = null;
                $student['chuaDuDiem'] = true;
            }
        }
        
        return array_values($students);
    }
    
    /**
     * Tính toán và xếp loại học lực
     */
    public function calculateAcademicRanking($maHS, $hocKy, $namHoc) {
        // Lấy điểm các môn
        $sql = "SELECT tbDiem, nhanXet
                FROM bangdiem
                WHERE maHS = ? AND hocKy = ? AND namHoc = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $maHS, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grades = [];
        $totalScore = 0;
        $countSubjects = 0;
        $nullCount = 0; // Đếm số môn chưa có điểm
        $chuaDatCount = 0;
        $duoi35 = 0;
        $tren50 = 0;
        $tren65 = 0;
        $tren80 = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Kiểm tra môn có điểm chưa
            if ($row['tbDiem'] === null) {
                $nullCount++;
                continue;
            }
            
            $score = floatval($row['tbDiem']);
            $grades[] = $score;
            $totalScore += $score;
            $countSubjects++;
            
            // Đếm các tiêu chí
            if ($row['nhanXet'] && stripos($row['nhanXet'], 'chưa đạt') !== false) {
                $chuaDatCount++;
            }
            if ($score < 3.5) $duoi35++;
            if ($score >= 5.0) $tren50++;
            if ($score >= 6.5) $tren65++;
            if ($score >= 8.0) $tren80++;
        }
        
        // Nếu có môn nào chưa có điểm hoặc không có môn nào => không xếp loại
        if ($nullCount > 0 || $countSubjects == 0) {
            return [
                'ranking' => null, 
                'average' => 0, 
                'message' => $nullCount > 0 ? 'Còn thiếu điểm một số môn' : 'Chưa có điểm'
            ];
        }
        
        $average = $totalScore / $countSubjects;
        
        // Xếp loại theo tiêu chí
        $ranking = 'Chưa đạt';
        
        // Tốt: tất cả môn Đạt, các môn ≥ 6.5 và ít nhất 6 môn ≥ 8.0
        if ($chuaDatCount == 0 && $tren65 == $countSubjects && $tren80 >= 6) {
            $ranking = 'Tot';
        }
        // Khá: môn Đạt, các môn ≥ 5.0 và ít nhất 6 môn ≥ 6.5
        elseif ($chuaDatCount == 0 && $tren50 == $countSubjects && $tren65 >= 6) {
            $ranking = 'Khá';
        }
        // Đạt: có thể có môn chưa đạt, các môn ≥ 3.5 và ít nhất 6 môn ≥ 5.0
        elseif ($duoi35 == 0 && $tren50 >= 6) {
            $ranking = 'Dat';
        }
        
        return [
            'ranking' => $ranking,
            'average' => round($average, 2),
            'countSubjects' => $countSubjects,
            'tren80' => $tren80,
            'tren65' => $tren65,
            'tren50' => $tren50,
            'chuaDat' => $chuaDatCount
        ];
    }
    
    /**
     * Lưu xếp loại học lực vào database
     */
    public function saveAcademicRanking($maHS, $namHoc, $hocKy, $diemTB, $loaiHocLuc) {
        // Kiểm tra xem đã có bản ghi chưa
        $checkSql = "SELECT maHocLuc FROM hocluc WHERE maHS = ? AND namHoc = ?";
        $stmt = $this->conn->prepare($checkSql);
        $stmt->bind_param("is", $maHS, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update
            $row = $result->fetch_assoc();
            if ($hocKy == 1) {
                $sql = "UPDATE hocluc SET diemTBHK1 = ?, loaiHocLuc = ? WHERE maHocLuc = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("dsi", $diemTB, $loaiHocLuc, $row['maHocLuc']);
            } else {
                // Update HK2 và tính điểm TB cả năm
                $sql = "UPDATE hocluc SET diemTBHK2 = ?, 
                        diemTBCaNam = (COALESCE(diemTBHK1, 0) + ?) / 2,
                        loaiHocLuc = ? 
                        WHERE maHocLuc = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ddsi", $diemTB, $diemTB, $loaiHocLuc, $row['maHocLuc']);
            }
        } else {
            // Insert
            if ($hocKy == 1) {
                $sql = "INSERT INTO hocluc (maHS, namHoc, diemTBHK1, loaiHocLuc) VALUES (?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("isds", $maHS, $namHoc, $diemTB, $loaiHocLuc);
            } else {
                $sql = "INSERT INTO hocluc (maHS, namHoc, diemTBHK2, diemTBCaNam, loaiHocLuc) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("isdds", $maHS, $namHoc, $diemTB, $diemTB, $loaiHocLuc);
            }
        }
        
        return $stmt->execute();
    }
    
    /**
     * Lưu xếp loại cho tất cả học sinh trong lớp
     */
    public function saveAllClassifications($maLop, $hocKy, $namHoc) {
        // Lấy danh sách học sinh
        $sql = "SELECT maHS FROM hocsinh WHERE maLop = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $success = 0;
        $failed = 0;
        $skipped = 0; // Số học sinh bị bỏ qua do thiếu điểm
        
        while ($row = $result->fetch_assoc()) {
            $maHS = $row['maHS'];
            $rankingData = $this->calculateAcademicRanking($maHS, $hocKy, $namHoc);
            
            // Chỉ lưu nếu có thể xếp loại (không null)
            if ($rankingData['ranking'] && $rankingData['ranking'] !== null) {
                if ($this->saveAcademicRanking($maHS, $namHoc, $hocKy, $rankingData['average'], $rankingData['ranking'])) {
                    $success++;
                } else {
                    $failed++;
                }
            } else {
                // Bỏ qua học sinh thiếu điểm
                $skipped++;
            }
        }
        
        return [
            'success' => $success, 
            'failed' => $failed,
            'skipped' => $skipped
        ];
    }
    
    /**
     * Lấy bảng hạnh kiểm chi tiết của học sinh theo học kỳ
     */
    public function getStudentConductDetail($maLop, $hocKy, $namHoc) {
        // Đếm tổng số môn học trong khối
        $countMonSql = "SELECT COUNT(DISTINCT mh.maMonHoc) as totalSubjects
                        FROM monhoc mh
                        JOIN lophoc l ON l.maLop = ?
                        JOIN khoi k ON l.maKhoi = k.maKhoi";
        
        $stmtCount = $this->conn->prepare($countMonSql);
        $stmtCount->bind_param("i", $maLop);
        $stmtCount->execute();
        $resultCount = $stmtCount->get_result();
        $totalSubjects = $resultCount->fetch_assoc()['totalSubjects'] ?? 10;
        $stmtCount->close();
        
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen,
                    COALESCE(hk.soBuoiNghiCoPhep, 0) as soBuoiNghiCoPhep,
                    COALESCE(hk.soBuoiNghiKhongPhep, 0) as soBuoiNghiKhongPhep,
                    COALESCE(hk.soLanViPhamNhe, 0) as soLanViPhamNhe,
                    COALESCE(hk.soLanViPhamTB, 0) as soLanViPhamTB,
                    COALESCE(hk.soLanViPhamNang, 0) as soLanViPhamNang,
                    hk.loaiHK as hanhKiem,
                    (
                        SELECT 
                            CASE 
                                WHEN COUNT(*) < ? THEN NULL
                                WHEN COUNT(*) = 0 THEN NULL
                                WHEN AVG(bd.tbDiem) >= 8.0 AND SUM(CASE WHEN bd.tbDiem >= 6.5 THEN 1 ELSE 0 END) = COUNT(*) AND SUM(CASE WHEN bd.tbDiem >= 8.0 THEN 1 ELSE 0 END) >= 6 THEN 'Tốt'
                                WHEN AVG(bd.tbDiem) >= 6.5 AND SUM(CASE WHEN bd.tbDiem >= 5.0 THEN 1 ELSE 0 END) = COUNT(*) AND SUM(CASE WHEN bd.tbDiem >= 6.5 THEN 1 ELSE 0 END) >= 6 THEN 'Khá'
                                WHEN AVG(bd.tbDiem) >= 5.0 AND SUM(CASE WHEN bd.tbDiem >= 3.5 THEN 1 ELSE 0 END) = COUNT(*) AND SUM(CASE WHEN bd.tbDiem >= 5.0 THEN 1 ELSE 0 END) >= 6 THEN 'Đạt'
                                WHEN COUNT(*) >= ? THEN 'Chưa đạt'
                                ELSE NULL
                            END
                        FROM bangdiem bd 
                        WHERE bd.maHS = hs.maHS AND bd.hocKy = ? AND bd.namHoc = ? AND bd.tbDiem IS NOT NULL
                    ) as loaiHocLuc,
                    hk.nhanXet,
                    (SELECT COUNT(*) FROM bangdiem bd WHERE bd.maHS = hs.maHS AND bd.hocKy = ? AND bd.namHoc = ? AND bd.tbDiem IS NOT NULL) as soMonCoDiem
                FROM hocsinh hs
                LEFT JOIN hanhkiem hk ON hs.maHS = hk.maHS AND hk.hocKy = ? AND hk.namHoc = ?
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiisisisi", $totalSubjects, $totalSubjects, $hocKy, $namHoc, $hocKy, $namHoc, $hocKy, $namHoc, $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            // Chỉ hiển thị học lực khi có đủ điểm tất cả các môn
            if ($row['soMonCoDiem'] < $totalSubjects) {
                $row['loaiHocLuc'] = null; // Không đủ điểm -> không hiển thị học lực
            }
            $students[] = $row;
        }
        
        return $students;
    }
    
    /**
     * Tính toán và xếp loại hạnh kiểm
     */
    public function calculateConductRanking($maHS, $hocKy, $namHoc) {
        // Lấy thông tin nghỉ học và vi phạm
        $sql = "SELECT 
                    COALESCE(hk.soBuoiNghiCoPhep, 0) as nghiCoPhep,
                    COALESCE(hk.soBuoiNghiKhongPhep, 0) as nghiKhongPhep,
                    COALESCE(hk.soLanViPhamNhe, 0) as viPhamNhe,
                    COALESCE(hk.soLanViPhamTB, 0) as viPhamTB,
                    COALESCE(hk.soLanViPhamNang, 0) as viPhamNang,
                    hl.loaiHocLuc
                FROM hocsinh hs
                LEFT JOIN hanhkiem hk ON hs.maHS = hk.maHS AND hk.hocKy = ? AND hk.namHoc = ?
                LEFT JOIN hocluc hl ON hs.maHS = hl.maHS AND hl.namHoc = ?
                WHERE hs.maHS = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issi", $hocKy, $namHoc, $namHoc, $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if (!$data) {
            return [
                'ranking' => null,
                'message' => 'Chưa có dữ liệu'
            ];
        }
        
        $nghiCoPhep = intval($data['nghiCoPhep']);
        $nghiKhongPhep = intval($data['nghiKhongPhep']);
        $viPhamNhe = intval($data['viPhamNhe']);
        $viPhamTB = intval($data['viPhamTB']);
        $viPhamNang = intval($data['viPhamNang']);
        $hocLuc = $data['loaiHocLuc'];
        
        // Tổng số lần vi phạm
        $tongViPham = $viPhamNhe + $viPhamTB + $viPhamNang;
        
        // Xếp loại hạnh kiểm theo tiêu chí
        $ranking = 'Chưa đạt';
        
        // Chưa đạt: Nghỉ không phép >= 10 HOẶC vi phạm nặng > 0 HOẶC học lực Chưa đạt
        if ($nghiKhongPhep >= 10 || $viPhamNang > 0 || 
            ($hocLuc && strtolower($hocLuc) == 'chua dat')) {
            $ranking = 'Chưa đạt';
        }
        // Đạt: Nghỉ không phép 4-6, Nghỉ có phép >= 10, Vi phạm 2-3 lần (TB), Học lực Đạt hoặc Chưa đạt
        elseif ($nghiKhongPhep >= 4 && $nghiKhongPhep <= 6 && 
                $nghiCoPhep >= 10 && 
                $viPhamTB >= 2 && $viPhamTB <= 3 && $viPhamNang == 0 &&
                ($hocLuc && (strtolower($hocLuc) == 'dat' || strtolower($hocLuc) == 'chua dat'))) {
            $ranking = 'Đạt';
        }
        // Khá: Nghỉ không phép 3-5, Nghỉ có phép 6-10, Vi phạm nhẹ <= 1, Học lực từ Đạt trở lên
        elseif ($nghiKhongPhep >= 3 && $nghiKhongPhep <= 5 && 
                $nghiCoPhep >= 6 && $nghiCoPhep <= 10 && 
                $viPhamNhe <= 1 && $viPhamTB == 0 && $viPhamNang == 0 &&
                ($hocLuc && in_array(strtolower($hocLuc), ['dat', 'kha', 'tot']))) {
            $ranking = 'Khá';
        }
        // Tốt: Nghỉ không phép <= 3, Nghỉ có phép <= 5, Không vi phạm, Học lực từ Khá trở lên
        elseif ($nghiKhongPhep <= 3 && 
                $nghiCoPhep <= 5 && 
                $tongViPham == 0 &&
                ($hocLuc && in_array(strtolower($hocLuc), ['kha', 'tot']))) {
            $ranking = 'Tốt';
        }
        
        return [
            'ranking' => $ranking,
            'nghiCoPhep' => $nghiCoPhep,
            'nghiKhongPhep' => $nghiKhongPhep,
            'viPhamNhe' => $viPhamNhe,
            'viPhamTB' => $viPhamTB,
            'viPhamNang' => $viPhamNang,
            'tongViPham' => $tongViPham,
            'hocLuc' => $hocLuc
        ];
    }
    
    /**
     * Lưu xếp loại hạnh kiểm vào database
     */
    public function saveConductRanking($maHS, $namHoc, $hocKy, $loaiHK) {
        // Kiểm tra xem đã có bản ghi chưa
        $checkSql = "SELECT maHanhKiem FROM hanhkiem WHERE maHS = ? AND hocKy = ? AND namHoc = ?";
        $stmt = $this->conn->prepare($checkSql);
        $stmt->bind_param("iis", $maHS, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update
            $row = $result->fetch_assoc();
            $sql = "UPDATE hanhkiem SET loaiHK = ? WHERE maHanhKiem = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $loaiHK, $row['maHanhKiem']);
        } else {
            // Insert mới
            $sql = "INSERT INTO hanhkiem (maHS, hocKy, namHoc, loaiHK, soBuoiNghiCoPhep, soBuoiNghiKhongPhep) 
                    VALUES (?, ?, ?, ?, 0, 0)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiss", $maHS, $hocKy, $namHoc, $loaiHK);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Lưu xếp loại hạnh kiểm thủ công (do giáo viên chọn)
     */
    public function saveConductRankingManual($maHS, $namHoc, $hocKy, $loaiHK) {
        // Kiểm tra xem đã có bản ghi chưa
        $checkSql = "SELECT maHanhKiem FROM hanhkiem WHERE maHS = ? AND hocKy = ? AND namHoc = ?";
        $stmt = $this->conn->prepare($checkSql);
        $stmt->bind_param("iis", $maHS, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update
            $row = $result->fetch_assoc();
            $sql = "UPDATE hanhkiem SET loaiHK = ? WHERE maHanhKiem = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $loaiHK, $row['maHanhKiem']);
        } else {
            // Insert mới
            $sql = "INSERT INTO hanhkiem (maHS, hocKy, namHoc, loaiHK, soBuoiNghiCoPhep, soBuoiNghiKhongPhep, soLanViPhamNhe, soLanViPhamTB, soLanViPhamNang) 
                    VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiss", $maHS, $hocKy, $namHoc, $loaiHK);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Lưu xếp loại hạnh kiểm cho tất cả học sinh trong lớp
     */
    public function saveAllConductClassifications($maLop, $hocKy, $namHoc) {
        // Lấy danh sách học sinh
        $sql = "SELECT maHS FROM hocsinh WHERE maLop = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $success = 0;
        $failed = 0;
        
        while ($row = $result->fetch_assoc()) {
            $maHS = $row['maHS'];
            $rankingData = $this->calculateConductRanking($maHS, $hocKy, $namHoc);
            
            if ($rankingData['ranking'] && $rankingData['ranking'] !== null) {
                if ($this->saveConductRanking($maHS, $namHoc, $hocKy, $rankingData['ranking'])) {
                    $success++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }
        
        return ['success' => $success, 'failed' => $failed];
    }
    
    /**
     * Lưu cấu hình tiêu chí xếp loại hạnh kiểm tự động
     */
    public function saveConductCriteria($maGV, $criteria) {
        // Xóa cấu hình cũ của giáo viên này
        $deleteSql = "DELETE FROM hanhkiem_boloc WHERE maGV = ?";
        $stmt = $this->conn->prepare($deleteSql);
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        
        // Thêm cấu hình mới cho từng loại hạnh kiểm
        $insertSql = "INSERT INTO hanhkiem_boloc 
                      (maGV, loaiHK, maxNghiCoPhep, maxNghiKhongPhep, maxViPhamNhe, maxViPhamTB, maxViPhamNang, minHocLuc) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($insertSql);
        
        foreach ($criteria as $loaiHK => $rules) {
            $stmt->bind_param("isiiiiis", 
                $maGV, 
                $loaiHK, 
                $rules['maxNghiCoPhep'],
                $rules['maxNghiKhongPhep'],
                $rules['maxViPhamNhe'],
                $rules['maxViPhamTB'],
                $rules['maxViPhamNang'],
                $rules['minHocLuc']
            );
            $stmt->execute();
        }
        
        return true;
    }
    
    /**
     * Lấy cấu hình tiêu chí xếp loại hạnh kiểm của giáo viên
     */
    public function getConductCriteria($maGV) {
        $sql = "SELECT * FROM hanhkiem_boloc WHERE maGV = ? ORDER BY 
                CASE loaiHK 
                    WHEN 'Tốt' THEN 1 
                    WHEN 'Khá' THEN 2 
                    WHEN 'Đạt' THEN 3 
                    WHEN 'Chưa đạt' THEN 4 
                END";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maGV);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $criteria = [];
        while ($row = $result->fetch_assoc()) {
            $criteria[$row['loaiHK']] = [
                'maxNghiCoPhep' => $row['maxNghiCoPhep'],
                'maxNghiKhongPhep' => $row['maxNghiKhongPhep'],
                'maxViPhamNhe' => $row['maxViPhamNhe'],
                'maxViPhamTB' => $row['maxViPhamTB'],
                'maxViPhamNang' => $row['maxViPhamNang'],
                'minHocLuc' => $row['minHocLuc']
            ];
        }
        
        // Nếu chưa có cấu hình, trả về mặc định
        if (empty($criteria)) {
            $criteria = $this->getDefaultConductCriteria();
        }
        
        return $criteria;
    }
    
    /**
     * Lấy tiêu chí mặc định
     */
    private function getDefaultConductCriteria() {
        return [
            'Tốt' => [
                'maxNghiCoPhep' => 5,
                'maxNghiKhongPhep' => 3,
                'maxViPhamNhe' => 0,
                'maxViPhamTB' => 0,
                'maxViPhamNang' => 0,
                'minHocLuc' => 'Khá'
            ],
            'Khá' => [
                'maxNghiCoPhep' => 10,
                'maxNghiKhongPhep' => 5,
                'maxViPhamNhe' => 1,
                'maxViPhamTB' => 0,
                'maxViPhamNang' => 0,
                'minHocLuc' => 'Đạt'
            ],
            'Đạt' => [
                'maxNghiCoPhep' => 15,
                'maxNghiKhongPhep' => 6,
                'maxViPhamNhe' => 3,
                'maxViPhamTB' => 3,
                'maxViPhamNang' => 0,
                'minHocLuc' => 'Đạt'
            ],
            'Chưa đạt' => [
                'maxNghiCoPhep' => 999,
                'maxNghiKhongPhep' => 999,
                'maxViPhamNhe' => 999,
                'maxViPhamTB' => 999,
                'maxViPhamNang' => 999,
                'minHocLuc' => ''
            ]
        ];
    }
    
    /**
     * Xếp loại hạnh kiểm tự động theo tiêu chí tùy chỉnh
     */
    public function calculateConductRankingByCriteria($maHS, $hocKy, $namHoc, $criteria) {
        // Lấy thông tin nghỉ học và vi phạm
        $sql = "SELECT 
                    COALESCE(hk.soBuoiNghiCoPhep, 0) as nghiCoPhep,
                    COALESCE(hk.soBuoiNghiKhongPhep, 0) as nghiKhongPhep,
                    COALESCE(hk.soLanViPhamNhe, 0) as viPhamNhe,
                    COALESCE(hk.soLanViPhamTB, 0) as viPhamTB,
                    COALESCE(hk.soLanViPhamNang, 0) as viPhamNang,
                    hl.loaiHocLuc
                FROM hocsinh hs
                LEFT JOIN hanhkiem hk ON hs.maHS = hk.maHS AND hk.hocKy = ? AND hk.namHoc = ?
                LEFT JOIN hocluc hl ON hs.maHS = hl.maHS AND hl.namHoc = ?
                WHERE hs.maHS = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issi", $hocKy, $namHoc, $namHoc, $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if (!$data) {
            return null;
        }
        
        $nghiCoPhep = intval($data['nghiCoPhep']);
        $nghiKhongPhep = intval($data['nghiKhongPhep']);
        $viPhamNhe = intval($data['viPhamNhe']);
        $viPhamTB = intval($data['viPhamTB']);
        $viPhamNang = intval($data['viPhamNang']);
        $hocLuc = $data['loaiHocLuc'];
        
        // Chuẩn hóa tên học lực (có thể null nếu chưa có học lực)
        $hocLucNormalized = $this->normalizeHocLuc($hocLuc);
        
        // Map học lực thành số để so sánh
        $hocLucLevel = [
            'Tot' => 4,
            'Kha' => 3,
            'Dat' => 2,
            'Chua dat' => 1
        ];
        
        // Nếu chưa có học lực, gán level = 0 (chưa xác định)
        $currentHocLucLevel = $hocLucLevel[$hocLucNormalized] ?? 0;
        
        // Kiểm tra từ loại tốt nhất đến thấp nhất
        $rankings = ['Tốt', 'Khá', 'Đạt'];
        
        foreach ($rankings as $ranking) {
            if (!isset($criteria[$ranking])) continue;
            
            $rule = $criteria[$ranking];
            
            // Chuẩn hóa minHocLuc trong rule
            $minHocLucNormalized = $this->normalizeHocLuc($rule['minHocLuc']);
            $minHocLucLevel = $hocLucLevel[$minHocLucNormalized] ?? 0;
            
            // Kiểm tra các điều kiện
            $nghiCoPhepOk = $nghiCoPhep <= $rule['maxNghiCoPhep'];
            $nghiKhongPhepOk = $nghiKhongPhep <= $rule['maxNghiKhongPhep'];
            $viPhamNheOk = $viPhamNhe <= $rule['maxViPhamNhe'];
            $viPhamTBOk = $viPhamTB <= $rule['maxViPhamTB'];
            $viPhamNangOk = $viPhamNang <= $rule['maxViPhamNang'];
            
            // Nếu CHƯA CÓ HỌC LỰC (currentHocLucLevel = 0):
            // Chỉ xét điều kiện nghỉ học và vi phạm, BỎ QUA điều kiện học lực
            if ($currentHocLucLevel == 0) {
                // Xếp loại chỉ dựa trên nghỉ học và vi phạm
                if ($nghiCoPhepOk && $nghiKhongPhepOk && 
                    $viPhamNheOk && $viPhamTBOk && $viPhamNangOk) {
                    return $ranking;
                }
            } else {
                // Nếu ĐÃ CÓ HỌC LỰC: Xét tất cả các điều kiện bao gồm cả học lực
                $hocLucOk = $currentHocLucLevel >= $minHocLucLevel;
                
                if ($nghiCoPhepOk && $nghiKhongPhepOk && 
                    $viPhamNheOk && $viPhamTBOk && $viPhamNangOk && $hocLucOk) {
                    return $ranking;
                }
            }
        }
        
        // Nếu không thỏa mãn loại nào thì xếp "Chưa đạt"
        return 'Chưa đạt';
    }
    
    /**
     * Chuẩn hóa tên học lực
     */
    private function normalizeHocLuc($hocLuc) {
        if (!$hocLuc) return '';
        
        // Bỏ dấu và chuẩn hóa
        $normalized = mb_strtolower($hocLuc, 'UTF-8');
        $normalized = str_replace(['ố', 'ó', 'ò', 'õ', 'ọ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ'], 'o', $normalized);
        $normalized = str_replace(['ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'á', 'à', 'ả', 'ã', 'ạ'], 'a', $normalized);
        $normalized = str_replace(['đ'], 'd', $normalized);
        $normalized = str_replace(['ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ', 'ú', 'ù', 'ủ', 'ũ', 'ụ'], 'u', $normalized);
        $normalized = str_replace(' ', '', $normalized);
        
        // Map về các giá trị chuẩn
        $mapping = [
            'tot' => 'Tot',
            'kha' => 'Kha',
            'dat' => 'Dat',
            'chuadat' => 'Chua dat',
            'trungbinh' => 'Dat'
        ];
        
        return $mapping[$normalized] ?? '';
    }
    
    /**
     * Xếp loại hạnh kiểm tự động cho tất cả học sinh theo tiêu chí
     */
    public function autoClassifyAllConduct($maLop, $hocKy, $namHoc, $maGV) {
        // Lấy tiêu chí của giáo viên
        $criteria = $this->getConductCriteria($maGV);
        
        // Lấy danh sách học sinh
        $sql = "SELECT maHS FROM hocsinh WHERE maLop = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $success = 0;
        $failed = 0;
        $noData = 0; // Đếm học sinh chưa có dữ liệu
        
        while ($row = $result->fetch_assoc()) {
            $maHS = $row['maHS'];
            
            try {
                $ranking = $this->calculateConductRankingByCriteria($maHS, $hocKy, $namHoc, $criteria);
                
                if ($ranking) {
                    if ($this->saveConductRankingManual($maHS, $namHoc, $hocKy, $ranking)) {
                        $success++;
                    } else {
                        $failed++;
                    }
                } else {
                    $noData++;
                }
            } catch (Exception $e) {
                error_log("Error classifying conduct for student $maHS: " . $e->getMessage());
                $failed++;
            }
        }
        
        return [
            'success' => $success, 
            'failed' => $failed,
            'noData' => $noData
        ];
    }
    
    /**
     * Lấy thông tin danh hiệu
     */
    public function getAllTitles() {
        $sql = "SELECT maDanhHieu, tenDanhHieu, hocLucToiThieu, hanhKiemToiThieu, 
                       soViPhamToiDa, diemTBToiThieu, moTa 
                FROM danhhieu 
                ORDER BY maDanhHieu";
        
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Tính toán xếp loại danh hiệu cho học sinh
     */
    public function calculateTitleRanking($maHS, $hocKy, $namHoc) {
        // Lấy thông tin học lực và hạnh kiểm
        $sql = "SELECT 
                    hl.loaiHocLuc,
                    hl.diemTBHK1,
                    hl.diemTBHK2,
                    hl.diemTBCaNam,
                    hk.loaiHK as hanhKiem
                FROM hocsinh hs
                LEFT JOIN hocluc hl ON hs.maHS = hl.maHS AND hl.namHoc = ?
                LEFT JOIN hanhkiem hk ON hs.maHS = hk.maHS AND hk.hocKy = ? AND hk.namHoc = ?
                WHERE hs.maHS = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sisi", $namHoc, $hocKy, $namHoc, $maHS);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if (!$data) {
            return [
                'title' => null,
                'message' => 'Chưa có dữ liệu học lực/hạnh kiểm'
            ];
        }
        
        // Kiểm tra xem có học lực và hạnh kiểm không
        if (!$data['loaiHocLuc'] || !$data['hanhKiem']) {
            return [
                'title' => null,
                'maDanhHieu' => null,
                'message' => 'Chưa đủ dữ liệu để xếp loại danh hiệu'
            ];
        }
        
        $hocLuc = $this->normalizeHocLuc($data['loaiHocLuc']);
        $hanhKiem = $this->normalizeHocLuc($data['hanhKiem']);
        
        // Lấy điểm các môn để kiểm tra tiêu chí xuất sắc
        $gradeSql = "SELECT tbDiem FROM bangdiem 
                     WHERE maHS = ? AND hocKy = ? AND namHoc = ? 
                     AND tbDiem IS NOT NULL";
        
        $stmt = $this->conn->prepare($gradeSql);
        $stmt->bind_param("iis", $maHS, $hocKy, $namHoc);
        $stmt->execute();
        $gradeResult = $stmt->get_result();
        
        $monTren9 = 0;
        while ($row = $gradeResult->fetch_assoc()) {
            if (floatval($row['tbDiem']) >= 9.0) {
                $monTren9++;
            }
        }
        
        // Kiểm tra xem có bất kỳ học kỳ nào hạnh kiểm "Chưa đạt" không
        $checkChuaDatSql = "SELECT COUNT(*) as chuaDatCount 
                            FROM hanhkiem 
                            WHERE maHS = ? AND namHoc = ? 
                            AND (loaiHK LIKE '%Chưa đạt%' OR loaiHK LIKE '%Chua dat%')";
        $stmtCheck = $this->conn->prepare($checkChuaDatSql);
        $stmtCheck->bind_param("is", $maHS, $namHoc);
        $stmtCheck->execute();
        $checkResult = $stmtCheck->get_result();
        $checkData = $checkResult->fetch_assoc();
        $hasChuaDat = $checkData['chuaDatCount'] > 0;
        
        // Xếp loại danh hiệu
        $title = null;
        $maDanhHieu = null;
        
        // Nếu có bất kỳ học kỳ nào "Chưa đạt" → KHÔNG được danh hiệu
        if ($hasChuaDat) {
            return [
                'title' => null,
                'maDanhHieu' => null,
                'hocLuc' => $hocLuc,
                'hanhKiem' => $hanhKiem,
                'monTren9' => $monTren9,
                'diemTBHK' => $hocKy == 1 ? $data['diemTBHK1'] : $data['diemTBHK2'],
                'message' => 'Không đủ điều kiện (có học kỳ hạnh kiểm chưa đạt)'
            ];
        }
        
        // Danh hiệu xuất sắc: Hạnh kiểm Tốt, Học lực Tốt, có ít nhất 6 môn TB >= 9.0
        if ($hanhKiem == 'Tot' && $hocLuc == 'Tot' && $monTren9 >= 6) {
            $title = 'Học sinh xuất sắc';
            $maDanhHieu = 1001; // Mã danh hiệu xuất sắc
        }
        // Danh hiệu giỏi: Hạnh kiểm Tốt, Học lực Tốt
        elseif ($hanhKiem == 'Tot' && $hocLuc == 'Tot') {
            $title = 'Học sinh giỏi';
            $maDanhHieu = 1002; // Mã danh hiệu giỏi
        }
        
        return [
            'title' => $title,
            'maDanhHieu' => $maDanhHieu,
            'hocLuc' => $hocLuc,
            'hanhKiem' => $hanhKiem,
            'monTren9' => $monTren9,
            'diemTBHK' => $hocKy == 1 ? $data['diemTBHK1'] : $data['diemTBHK2']
        ];
    }
    
    /**
     * Lưu danh hiệu cho học sinh
     */
    public function saveTitleRanking($maHS, $maDanhHieu, $hocKy, $namHoc) {
        if (!$maDanhHieu) {
            return false;
        }
        
        // Kiểm tra xem đã có bản ghi chưa
        $checkSql = "SELECT maHS FROM hocsinh_danhhieu 
                     WHERE maHS = ? AND hocKy = ? AND namHoc = ?";
        $stmt = $this->conn->prepare($checkSql);
        $stmt->bind_param("iis", $maHS, $hocKy, $namHoc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update
            $sql = "UPDATE hocsinh_danhhieu 
                    SET maDanhHieu = ?, ngayCapNhat = CURRENT_TIMESTAMP 
                    WHERE maHS = ? AND hocKy = ? AND namHoc = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiis", $maDanhHieu, $maHS, $hocKy, $namHoc);
        } else {
            // Insert
            $sql = "INSERT INTO hocsinh_danhhieu (maHS, maDanhHieu, hocKy, namHoc, ghiChu) 
                    VALUES (?, ?, ?, ?, 'Xếp loại tự động')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiis", $maHS, $maDanhHieu, $hocKy, $namHoc);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Xếp loại danh hiệu cho tất cả học sinh trong lớp
     */
    public function classifyAllTitles($maLop, $hocKy, $namHoc) {
        // Lấy danh sách học sinh
        $sql = "SELECT maHS FROM hocsinh WHERE maLop = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $success = 0;
        $failed = 0;
        $noTitle = 0;
        
        while ($row = $result->fetch_assoc()) {
            $maHS = $row['maHS'];
            $titleData = $this->calculateTitleRanking($maHS, $hocKy, $namHoc);
            
            if ($titleData['maDanhHieu']) {
                if ($this->saveTitleRanking($maHS, $titleData['maDanhHieu'], $hocKy, $namHoc)) {
                    $success++;
                } else {
                    $failed++;
                }
            } else {
                $noTitle++;
            }
        }
        
        return [
            'success' => $success,
            'failed' => $failed,
            'noTitle' => $noTitle
        ];
    }
    
    /**
     * Lấy thông tin danh hiệu của học sinh - VERSION ĐƠN GIẢN NHẤT
     */
    public function getStudentTitles($maLop, $hocKy, $namHoc) {
        // Bước 1: Lấy danh sách học sinh
        $students = [];
        
        $sql = "SELECT maHS, hoTen FROM hocsinh 
                WHERE maLop = ? AND trangThaiHocTap = 'danghoc'
                ORDER BY hoTen";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];
        
        $stmt->bind_param("i", $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
        
        // Đếm tổng số môn học trong khối để biết học sinh cần có đủ bao nhiêu môn
        $countMonSql = "SELECT COUNT(DISTINCT mh.maMonHoc) as totalSubjects
                        FROM monhoc mh
                        JOIN lophoc l ON l.maLop = ?
                        JOIN khoi k ON l.maKhoi = k.maKhoi";
        $stmtCount = $this->conn->prepare($countMonSql);
        $stmtCount->bind_param("i", $maLop);
        $stmtCount->execute();
        $resultCount = $stmtCount->get_result();
        $totalSubjects = $resultCount->fetch_assoc()['totalSubjects'] ?? 10;
        $stmtCount->close();
        
        // Bước 2: Với mỗi học sinh, lấy thông tin bổ sung
        foreach ($students as &$student) {
            $maHS = $student['maHS'];
            
            // Học lực - Tính theo học kỳ cụ thể từ bảng bangdiem, CHỈ KHI CÓ ĐỦ ĐIỂM
            $sql2 = "SELECT 
                        COUNT(*) as soMonCoDiem,
                        CASE 
                            WHEN COUNT(*) < ? THEN NULL
                            WHEN COUNT(*) = 0 THEN NULL
                            WHEN AVG(bd.tbDiem) >= 8.0 AND SUM(CASE WHEN bd.tbDiem >= 6.5 THEN 1 ELSE 0 END) = COUNT(*) AND SUM(CASE WHEN bd.tbDiem >= 8.0 THEN 1 ELSE 0 END) >= 6 THEN 'Tốt'
                            WHEN AVG(bd.tbDiem) >= 6.5 AND SUM(CASE WHEN bd.tbDiem >= 5.0 THEN 1 ELSE 0 END) = COUNT(*) AND SUM(CASE WHEN bd.tbDiem >= 6.5 THEN 1 ELSE 0 END) >= 6 THEN 'Khá'
                            WHEN AVG(bd.tbDiem) >= 5.0 AND SUM(CASE WHEN bd.tbDiem >= 3.5 THEN 1 ELSE 0 END) = COUNT(*) AND SUM(CASE WHEN bd.tbDiem >= 5.0 THEN 1 ELSE 0 END) >= 6 THEN 'Đạt'
                            WHEN COUNT(*) >= ? THEN 'Chưa đạt'
                            ELSE NULL
                        END as loaiHocLuc
                     FROM bangdiem bd 
                     WHERE bd.maHS = ? AND bd.hocKy = ? AND bd.namHoc = ? AND bd.tbDiem IS NOT NULL";
            $stmt2 = $this->conn->prepare($sql2);
            if ($stmt2) {
                $stmt2->bind_param("iiiis", $totalSubjects, $totalSubjects, $maHS, $hocKy, $namHoc);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $row2 = $res2->fetch_assoc();
                $student['loaiHocLuc'] = $row2 ? $row2['loaiHocLuc'] : null;
                $stmt2->close();
            }
            
            // Hạnh kiểm - CHỈ LẤY KHI HỌC SINH ĐÃ CÓ HỌC LỰC
            $student['hanhKiem'] = null;
            if ($student['loaiHocLuc'] !== null) {
                $sql3 = "SELECT loaiHK FROM hanhkiem 
                         WHERE maHS = ? AND namHoc = ? AND hocKy = ?
                         LIMIT 1";
                $stmt3 = $this->conn->prepare($sql3);
                if ($stmt3) {
                    $stmt3->bind_param("isi", $maHS, $namHoc, $hocKy);
                    if ($stmt3->execute()) {
                        $res3 = $stmt3->get_result();
                        $row3 = $res3->fetch_assoc();
                        if ($row3) {
                            $student['hanhKiem'] = $row3['loaiHK'];
                        }
                    }
                    $stmt3->close();
                }
            }
            
            // Tính toán danh hiệu DỰA TRÊN học lực và hạnh kiểm HIỆN TẠI
            // Không lấy từ database cũ, mà tính toán lại theo tiêu chí
            $tenDanhHieu = null;
            
            $hocLuc = $student['loaiHocLuc'] ? $this->normalizeHocLuc($student['loaiHocLuc']) : '';
            $hanhKiem = $student['hanhKiem'] ? $this->normalizeHocLuc($student['hanhKiem']) : '';
            
            // CHỈ XẾP DANH HIỆU NẾU CẢ HỌC LỰC VÀ HẠNH KIỂM ĐỀU TỐT
            if ($hocLuc === 'Tot' && $hanhKiem === 'Tot') {
                // Kiểm tra số môn >= 9.0 để xét học sinh xuất sắc
                $sql4 = "SELECT COUNT(*) as monTren9 FROM bangdiem 
                         WHERE maHS = ? AND hocKy = ? AND namHoc = ? 
                         AND tbDiem >= 9.0";
                $stmt4 = $this->conn->prepare($sql4);
                if ($stmt4) {
                    $stmt4->bind_param("iis", $maHS, $hocKy, $namHoc);
                    $stmt4->execute();
                    $res4 = $stmt4->get_result();
                    $row4 = $res4->fetch_assoc();
                    $monTren9 = $row4 ? intval($row4['monTren9']) : 0;
                    $stmt4->close();
                    
                    // Xuất sắc: Tốt + Tốt + ít nhất 6 môn >= 9.0
                    if ($monTren9 >= 6) {
                        $tenDanhHieu = 'Học sinh xuất sắc';
                    } else {
                        // Giỏi: Tốt + Tốt (không đủ 6 môn >= 9.0)
                        $tenDanhHieu = 'Học sinh giỏi';
                    }
                }
            }
            // Nếu không đủ điều kiện (học lực hoặc hạnh kiểm không phải Tốt) → không có danh hiệu
            
            $student['tenDanhHieu'] = $tenDanhHieu;
        }
        
        return $students;
    }
}
?>
