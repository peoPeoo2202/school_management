<?php
require_once 'mConnect.php';

class mChonDe {
    private $connection;
    
    public function __construct() {
        $mConnect = new mConnect();
        $this->connection = $mConnect->mConnect();
    }
    
    private function executeQuery($sql, $params = []) {
        $stmt = mysqli_prepare($this->connection, $sql);
        
        if (!$stmt) {
            die("Prepare failed: " . mysqli_error($this->connection));
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result) {
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            mysqli_stmt_close($stmt);
            return $data;
        } else {
            mysqli_stmt_close($stmt);
            return [];
        }
    }
    
    private function executeNonQuery($sql, $params = []) {
        $stmt = mysqli_prepare($this->connection, $sql);
        
        if (!$stmt) {
            return false;
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        return $result;
    }
    
    /**
     * Lấy danh sách các khối
     */
    public function layDanhSachKhoi() {
        $sql = "SELECT maKhoi, khoiLop FROM khoi ORDER BY khoiLop";
        return $this->executeQuery($sql);
    }
    
    /**
     * Lấy danh sách kỳ thi theo khối và trạng thái cho phép chọn đề
     */
    public function layDanhSachKyThi($maKhoi = null) {
        $sql = "SELECT kt.maKyThi, kt.tenKyThi, kt.loaiKyThi, kt.hocKy, kt.namHoc, 
                       kt.ngayBatDau, kt.ngayKetThuc, k.khoiLop
                FROM kythi kt
                INNER JOIN khoi k ON kt.maKhoi = k.maKhoi
                WHERE kt.trangThai IN ('Dang_lap', 'Dang_thi')";
        
        $params = [];
        if ($maKhoi) {
            $sql .= " AND kt.maKhoi = ?";
            $params[] = $maKhoi;
        }
        
        $sql .= " ORDER BY k.khoiLop, FIELD(kt.loaiKyThi, 'Giữa kỳ', 'Cuối kỳ', 'Thi lại'), kt.ngayBatDau DESC";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Lấy danh sách đề thi đã được duyệt theo khối và kỳ thi
     * Chỉ lấy những đề đã được duyệt (trangThai = 'Daduyet')
     */
    public function layDanhSachDeThi($maKyThi) {
        $sql = "SELECT 
                        dt.maDeThi, 
                        dt.tenDeThi, 
                        mh.tenMonHoc, 
                        dt.hocKy, 
                        dt.namHoc,
                        dt.trangThai, 
                        gv.hoTen as tenGiaoVien, 
                        k.khoiLop as khoiLop,
                        kt.loaiKyThi as loaiKyThi,
                        dt.tenFile, 
                        dt.duongDan, 
                        dt.moTa,
                        dt.ngayDuyet, 
                        dt.lyDoDuyet,
                        gv_ttbm.hoTen as tenTTBM,
                        CASE WHEN dk.maDeThi IS NULL THEN 0 ELSE 1 END AS daChon
                FROM dethi dt
                INNER JOIN monhoc mh ON dt.maMonHoc = mh.maMonHoc
                LEFT JOIN giaovien gv ON dt.maGV = gv.maGV
                INNER JOIN kythi kt ON kt.maKyThi = ?
                LEFT JOIN khoi k ON dt.maKhoi = k.maKhoi
                LEFT JOIN ttbm ON dt.maTTBM = ttbm.maTTBM
                LEFT JOIN giaovien gv_ttbm ON ttbm.maGV = gv_ttbm.maGV
                LEFT JOIN dethi_kythi dk ON dk.maDeThi = dt.maDeThi AND dk.maKyThi = kt.maKyThi
                WHERE dt.trangThai IN ('Daduyet','Dachon')
                AND dt.maKhoi = kt.maKhoi
                AND dt.hocKy = kt.hocKy 
                AND dt.namHoc = kt.namHoc
                ORDER BY mh.tenMonHoc, dt.tenDeThi";
        
        return $this->executeQuery($sql, [$maKyThi]);
    }
    
    /**
     * Lấy thông tin chi tiết một đề thi
     */
    public function layChiTietDeThi($maDeThi) {
        $sql = "SELECT dt.*, mh.tenMonHoc, gv.hoTen as tenGiaoVien,
                       ttbm.maTTBM, gv_ttbm.hoTen as tenTTBM
                FROM dethi dt
                INNER JOIN monhoc mh ON dt.maMonHoc = mh.maMonHoc
                LEFT JOIN giaovien gv ON dt.maGV = gv.maGV
                LEFT JOIN ttbm ON dt.maTTBM = ttbm.maTTBM
                LEFT JOIN giaovien gv_ttbm ON ttbm.maGV = gv_ttbm.maGV
                WHERE dt.maDeThi = ?";
        
        $result = $this->executeQuery($sql, [$maDeThi]);
        return $result ? $result[0] : null;
    }
    
    /**
     * Lấy thông tin kỳ thi
     */
    public function layThongTinKyThi($maKyThi) {
        $sql = "SELECT kt.*, k.khoiLop
                FROM kythi kt
                INNER JOIN khoi k ON kt.maKhoi = k.maKhoi
                WHERE kt.maKyThi = ?";
        
        $result = $this->executeQuery($sql, [$maKyThi]);
        return $result ? $result[0] : null;
    }
    
    /**
     * Chọn đề thi cho kỳ thi
     */
    public function chonDeThi($maKyThi, $maDeThi, $maBGH) {
        try {
            // Bắt đầu transaction
            mysqli_autocommit($this->connection, FALSE);
            
            // Kiểm tra xem đề thi đã được chọn cho kỳ thi này chưa
            $sqlCheck = "SELECT COUNT(*) as count FROM dethi_kythi 
                        WHERE maKyThi = ? AND maDeThi = ?";
            $checkResult = $this->executeQuery($sqlCheck, [$maKyThi, $maDeThi]);
            
            if ($checkResult && $checkResult[0]['count'] > 0) {
                throw new Exception("Đề thi này đã được chọn cho kỳ thi!");
            }
            
            // Thêm vào bảng dethi_kythi
            $sqlInsert = "INSERT INTO dethi_kythi (maDeThi, maKyThi, maBGH, ngayChon) 
                         VALUES (?, ?, ?, NOW())";
            
            if (!$this->executeNonQuery($sqlInsert, [$maDeThi, $maKyThi, $maBGH])) {
                throw new Exception("Lỗi khi lưu thông tin chọn đề thi!");
            }
            
            // Cập nhật trạng thái đề thi thành "Dachon" và lưu maKyThi
            $sqlUpdate = "UPDATE dethi SET trangThai = 'Dachon', maKyThi = ? 
                         WHERE maDeThi = ?";
            
            if (!$this->executeNonQuery($sqlUpdate, [$maKyThi, $maDeThi])) {
                throw new Exception("Lỗi khi cập nhật trạng thái đề thi!");
            }
            
            // Kiểm tra xem đã chọn đủ đề thi cho kỳ thi chưa
            $this->kiemTraVaCapNhatTrangThaiKyThi($maKyThi);
            
            // Commit transaction
            mysqli_commit($this->connection);
            mysqli_autocommit($this->connection, TRUE);
            return true;
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            mysqli_rollback($this->connection);
            mysqli_autocommit($this->connection, TRUE);
            throw $e;
        }
    }
    
    /**
     * Bỏ chọn đề thi
     */
    public function boChonDeThi($maKyThi, $maDeThi) {
        try {
            // Bắt đầu transaction
            mysqli_autocommit($this->connection, FALSE);
            
            // Xóa khỏi bảng dethi_kythi
            $sqlDelete = "DELETE FROM dethi_kythi 
                         WHERE maKyThi = ? AND maDeThi = ?";
            
            if (!$this->executeNonQuery($sqlDelete, [$maKyThi, $maDeThi])) {
                throw new Exception("Lỗi khi xóa thông tin đề thi đã chọn!");
            }
            
            // Cập nhật trạng thái đề thi về "Daduyet" và xóa maKyThi
            $sqlUpdate = "UPDATE dethi SET trangThai = 'Daduyet', maKyThi = NULL 
                         WHERE maDeThi = ?";
            
            if (!$this->executeNonQuery($sqlUpdate, [$maDeThi])) {
                throw new Exception("Lỗi khi cập nhật trạng thái đề thi!");
            }
            
            // Cập nhật lại trạng thái kỳ thi
            $sqlUpdateKyThi = "UPDATE kythi SET trangThai = 'Dang_lap' 
                              WHERE maKyThi = ?";
            $this->executeNonQuery($sqlUpdateKyThi, [$maKyThi]);
            
            // Commit transaction
            mysqli_commit($this->connection);
            mysqli_autocommit($this->connection, TRUE);
            return true;
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            mysqli_rollback($this->connection);
            mysqli_autocommit($this->connection, TRUE);
            throw $e;
        }
    }
    
    /**
     * Kiểm tra và cập nhật trạng thái kỳ thi nếu đã chọn đủ đề
     */
    private function kiemTraVaCapNhatTrangThaiKyThi($maKyThi) {
        // Đếm số môn học có đề thi theo kỳ thi
        $sqlCountMon = "SELECT COUNT(DISTINCT dt.maMonHoc) as soMonHoc
                       FROM dethi dt
                       INNER JOIN kythi kt ON kt.maKyThi = ?
                       WHERE (dt.trangThai = 'Daduyet' OR dt.trangThai = 'Dachon' OR dt.trangThai = 'Choduyet')
                       AND dt.hocKy = kt.hocKy 
                       AND dt.namHoc = kt.namHoc";
        
        $countMon = $this->executeQuery($sqlCountMon, [$maKyThi]);
        $soMonHoc = $countMon ? $countMon[0]['soMonHoc'] : 0;
        
        // Đếm số đề thi đã chọn cho kỳ thi này
        $sqlCountDeThi = "SELECT COUNT(*) as soDeThiDaChon
                         FROM dethi_kythi 
                         WHERE maKyThi = ?";
        
        $countDeThi = $this->executeQuery($sqlCountDeThi, [$maKyThi]);
        $soDeThiDaChon = $countDeThi ? $countDeThi[0]['soDeThiDaChon'] : 0;
        
        // Nếu đã chọn đủ đề thi (ít nhất 1 đề mỗi môn)
        if ($soDeThiDaChon >= $soMonHoc && $soMonHoc > 0) {
            // Cập nhật trạng thái kỳ thi (sử dụng trạng thái hiện có)
            $sqlUpdate = "UPDATE kythi SET trangThai = 'Dang_thi' 
                         WHERE maKyThi = ?";
            $this->executeNonQuery($sqlUpdate, [$maKyThi]);
        }
    }
    
    /**
     * Lấy danh sách đề thi đã chọn cho kỳ thi
     */
    public function layDeThiDaChon($maKyThi) {
        $sql = "SELECT dt.maDeThi, dt.tenDeThi, mh.tenMonHoc, 
                       dtkt.ngayChon, dtkt.ghiChu, bgh.hoTen as tenBGH,
                       gv.hoTen as tenGiaoVien,
                       kt.loaiKyThi, k.khoiLop,
                       dt.tenFile, dt.duongDan, dt.moTa,
                       dt.hocKy, dt.namHoc
                FROM dethi_kythi dtkt
                INNER JOIN dethi dt ON dtkt.maDeThi = dt.maDeThi
                INNER JOIN monhoc mh ON dt.maMonHoc = mh.maMonHoc
                INNER JOIN kythi kt ON dtkt.maKyThi = kt.maKyThi
                LEFT JOIN khoi k ON kt.maKhoi = k.maKhoi
                LEFT JOIN bgh ON dtkt.maBGH = bgh.maBGH
                LEFT JOIN giaovien gv ON dt.maGV = gv.maGV
                WHERE dtkt.maKyThi = ?
                ORDER BY mh.tenMonHoc, dt.tenDeThi";
        
        return $this->executeQuery($sql, [$maKyThi]);
    }
    
    /**
     * Cập nhật khối cho đề thi - Chức năng này không khả dụng do thiếu cột maKhoi trong bảng dethi
     */
    public function capNhatKhoiDeThi($maDeThi, $maKhoi) {
        // Không thể cập nhật vì bảng dethi không có cột maKhoi
        return false;
    }
    
    /**
     * Lấy danh sách đề thi theo khối (chỉ đề đã duyệt hoặc đã chọn)
     */
    public function layDeThiTheoKhoi($maKhoi, $hocKy = null, $namHoc = null) {
        $sql = "SELECT dt.maDeThi, dt.tenDeThi, mh.tenMonHoc, dt.hocKy, dt.namHoc,
                       dt.trangThai, gv.hoTen as tenGiaoVien,
                       k.khoiLop,
                       ttbm.maTTBM, gv_ttbm.hoTen as tenTTBM,
                       dt.ngayDuyet, dt.lyDoDuyet
                FROM dethi dt
                INNER JOIN monhoc mh ON dt.maMonHoc = mh.maMonHoc
                LEFT JOIN giaovien gv ON dt.maGV = gv.maGV
                LEFT JOIN khoi k ON dt.maKhoi = k.maKhoi
                LEFT JOIN ttbm ON dt.maTTBM = ttbm.maTTBM
                LEFT JOIN giaovien gv_ttbm ON ttbm.maGV = gv_ttbm.maGV
                WHERE dt.maKhoi = ?
                AND (dt.trangThai = 'Daduyet' OR dt.trangThai = 'Dachon')";
        
        $params = [$maKhoi];
        
        if ($hocKy) {
            $sql .= " AND dt.hocKy = ?";
            $params[] = $hocKy;
        }
        
        if ($namHoc) {
            $sql .= " AND dt.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " ORDER BY mh.tenMonHoc, dt.tenDeThi";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Lấy thông tin file đề thi để tải xuống
     */
    public function layThongTinFileDeThi($maDeThi) {
        $sql = "SELECT dt.maDeThi, dt.tenDeThi, dt.tenFile, dt.duongDan, 
                       mh.tenMonHoc, dt.moTa
                FROM dethi dt
                INNER JOIN monhoc mh ON dt.maMonHoc = mh.maMonHoc
                WHERE dt.maDeThi = ? AND dt.tenFile IS NOT NULL AND dt.duongDan IS NOT NULL";
        
        $result = $this->executeQuery($sql, [$maDeThi]);
        return $result ? $result[0] : null;
    }
    
    /**
     * Kiểm tra quyền truy cập file đề thi
     */
    public function kiemTraQuyenTruCapFile($maDeThi, $loaiTaiKhoan = null) {
        // Lấy thông tin đề thi và trạng thái
        $sql = "SELECT dt.maDeThi, dt.trangThai, dt.tenFile, dt.duongDan
                FROM dethi dt
                WHERE dt.maDeThi = ?";
        
        $result = $this->executeQuery($sql, [$maDeThi]);
        
        if (!$result || empty($result)) {
            return false;
        }
        
        $deThi = $result[0];
        
        // Kiểm tra file có tồn tại không
        if (empty($deThi['tenFile']) || empty($deThi['duongDan'])) {
            return false;
        }
        
        // Chỉ cho phép xem đề thi đã được duyệt hoặc đã chọn
        if ($deThi['trangThai'] !== 'Daduyet' && $deThi['trangThai'] !== 'Dachon') {
            return false;
        }
        
        return true;
    }
}
?>