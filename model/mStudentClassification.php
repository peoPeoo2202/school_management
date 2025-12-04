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
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen,
                    mh.tenMonHoc,
                    bd.tbDiem,
                    bd.nhanXet,
                    AVG(bd.tbDiem) OVER (PARTITION BY hs.maHS) as diemTB_HS
                FROM hocsinh hs
                LEFT JOIN bangdiem bd ON hs.maHS = bd.maHS AND bd.hocKy = ? AND bd.namHoc = ?
                LEFT JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen, mh.tenMonHoc";
        
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
                    'diemTB_HS' => $row['diemTB_HS'],
                    'grades' => []
                ];
            }
            if ($row['tenMonHoc']) {
                $students[$maHS]['grades'][] = [
                    'tenMonHoc' => $row['tenMonHoc'],
                    'tbDiem' => $row['tbDiem'],
                    'nhanXet' => $row['nhanXet']
                ];
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
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen,
                    COALESCE(hk.soBuoiNghiCoPhep, 0) as soBuoiNghiCoPhep,
                    COALESCE(hk.soBuoiNghiKhongPhep, 0) as soBuoiNghiKhongPhep,
                    COALESCE(hk.soLanViPhamNhe, 0) as soLanViPhamNhe,
                    COALESCE(hk.soLanViPhamTB, 0) as soLanViPhamTB,
                    COALESCE(hk.soLanViPhamNang, 0) as soLanViPhamNang,
                    hk.loaiHK as hanhKiem,
                    hl.loaiHocLuc,
                    hk.nhanXet
                FROM hocsinh hs
                LEFT JOIN hanhkiem hk ON hs.maHS = hk.maHS AND hk.hocKy = ? AND hk.namHoc = ?
                LEFT JOIN hocluc hl ON hs.maHS = hl.maHS AND hl.namHoc = ?
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issi", $hocKy, $namHoc, $namHoc, $maLop);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
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
        
        // Chuẩn hóa tên học lực
        $hocLucNormalized = $this->normalizeHocLuc($hocLuc);
        
        // Map học lực thành số để so sánh
        $hocLucLevel = [
            'Tot' => 4,
            'Kha' => 3,
            'Dat' => 2,
            'Chua dat' => 1
        ];
        
        $currentHocLucLevel = $hocLucLevel[$hocLucNormalized] ?? 0;
        
        // Kiểm tra từ loại tốt nhất đến thấp nhất
        $rankings = ['Tốt', 'Khá', 'Đạt'];
        
        foreach ($rankings as $ranking) {
            if (!isset($criteria[$ranking])) continue;
            
            $rule = $criteria[$ranking];
            
            // Chuẩn hóa minHocLuc trong rule
            $minHocLucNormalized = $this->normalizeHocLuc($rule['minHocLuc']);
            $minHocLucLevel = $hocLucLevel[$minHocLucNormalized] ?? 0;
            
            // Kiểm tra TẤT CẢ các điều kiện (dùng AND)
            // Học sinh phải thỏa mãn tất cả các điều kiện mới được xếp loại đó
            if ($nghiCoPhep <= $rule['maxNghiCoPhep'] &&
                $nghiKhongPhep <= $rule['maxNghiKhongPhep'] &&
                $viPhamNhe <= $rule['maxViPhamNhe'] &&
                $viPhamTB <= $rule['maxViPhamTB'] &&
                $viPhamNang <= $rule['maxViPhamNang'] &&
                $currentHocLucLevel >= $minHocLucLevel) {
                return $ranking;
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
        
        while ($row = $result->fetch_assoc()) {
            $maHS = $row['maHS'];
            $ranking = $this->calculateConductRankingByCriteria($maHS, $hocKy, $namHoc, $criteria);
            
            if ($ranking) {
                if ($this->saveConductRankingManual($maHS, $namHoc, $hocKy, $ranking)) {
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
        
        // Xếp loại danh hiệu
        $title = null;
        $maDanhHieu = null;
        
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
     * Lấy thông tin danh hiệu của học sinh
     */
    public function getStudentTitles($maLop, $hocKy, $namHoc) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen,
                    hl.loaiHocLuc,
                    CASE 
                        WHEN ? = 1 THEN hl.diemTBHK1
                        WHEN ? = 2 THEN hl.diemTBHK2
                        ELSE hl.diemTBCaNam
                    END as diemTB,
                    hk.loaiHK as hanhKiem,
                    dh.tenDanhHieu,
                    hsd.ghiChu,
                    hsd.ngayTao,
                    (SELECT COUNT(*) FROM bangdiem bd 
                     WHERE bd.maHS = hs.maHS 
                     AND bd.hocKy = ? 
                     AND bd.namHoc = ? 
                     AND bd.tbDiem >= 9.0) as soMonTren9
                FROM hocsinh hs
                LEFT JOIN hocluc hl ON hs.maHS = hl.maHS AND hl.namHoc = ?
                LEFT JOIN hanhkiem hk ON hs.maHS = hk.maHS AND hk.hocKy = ? AND hk.namHoc = ?
                LEFT JOIN hocsinh_danhhieu hsd ON hs.maHS = hsd.maHS AND hsd.hocKy = ? AND hsd.namHoc = ?
                LEFT JOIN danhhieu dh ON hsd.maDanhHieu = dh.maDanhHieu
                WHERE hs.maLop = ?
                ORDER BY hs.hoTen";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiissiissi", 
            $hocKy, $hocKy, $hocKy, $namHoc, 
            $namHoc, $hocKy, $namHoc, $hocKy, $namHoc, 
            $maLop
        );
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
