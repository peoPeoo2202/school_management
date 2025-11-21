<?php
require_once 'mConnect.php';

class ModelYeuCau {
    private $connection;

    public function __construct() {
        $mConnect = new mConnect();
        $this->connection = $mConnect->mConnect();
    }

    private function executeQuery($sql, $params = []) {
        $stmt = mysqli_prepare($this->connection, $sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . mysqli_error($this->connection));
            return [];
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
            error_log("Prepare failed: " . mysqli_error($this->connection));
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
     * Lấy danh sách tất cả yêu cầu với bộ lọc
     */
    public function layDanhSachYeuCau($loaiYeuCau = null, $trangThai = null, $tuNgay = null, $denNgay = null) {
        try {
            $sql = "SELECT 
                        yc.maYeuCau,
                        yc.moTa,
                        yc.trangThai,
                        yc.loaiYeuCau,
                        yc.ngayGui,
                        yc.ngayXuLy,
                        yc.minhChung,
                        gv.maGV,
                        gv.hoTen as tenGV,
                        gv.email as emailGV,
                        gv.soDienThoai as sdtGV,
                        bgh.hoTen as nguoiXuLy,
                        ycnp.ngayBatDauNghi,
                        ycnp.ngayKetThucNghi,
                        ycnp.lyDo as lyDoNghiPhep,
                        ycsd.diemHienTai,
                        ycsd.diemDeNghiSua,
                        ycsd.lyDoSuaDiem,
                        ycsd.maHS,
                        ycsd.maMonHoc,
                        ycsd.hocKy,
                        ycsd.namHoc,
                        ycsd.loaiDiem
                    FROM yeucau yc
                    LEFT JOIN yeucaunghiphep ycnp ON yc.maYeuCauNghiPhep = ycnp.maYeuCau
                    LEFT JOIN yeucausuadiem ycsd ON yc.maYeuCauSuaDiem = ycsd.maYeuCau
                    LEFT JOIN giaovien gv ON (ycnp.maGV = gv.maGV OR ycsd.maGV = gv.maGV)
                    LEFT JOIN bgh ON yc.maBGH_XuLy = bgh.maBGH
                    WHERE 1=1";
            
            $params = [];
            
            // Lọc theo loại yêu cầu
            if ($loaiYeuCau && $loaiYeuCau !== 'all') {
                $sql .= " AND yc.loaiYeuCau = ?";
                $params[] = $loaiYeuCau;
            }
            
            // Lọc theo trạng thái
            if ($trangThai && $trangThai !== 'all') {
                $sql .= " AND yc.trangThai = ?";
                $params[] = $trangThai;
            }
            
            // Lọc theo ngày gửi (từ ngày)
            if ($tuNgay) {
                $sql .= " AND DATE(yc.ngayGui) >= ?";
                $params[] = $tuNgay;
            }
            
            // Lọc theo ngày gửi (đến ngày)
            if ($denNgay) {
                $sql .= " AND DATE(yc.ngayGui) <= ?";
                $params[] = $denNgay;
            }
            
            $sql .= " ORDER BY yc.ngayGui DESC";
            
            return $this->executeQuery($sql, $params);
        } catch (Exception $e) {
            error_log("Lỗi layDanhSachYeuCau: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy chi tiết một yêu cầu
     */
    public function layChiTietYeuCau($maYeuCau) {
        try {
            $sql = "SELECT 
                        yc.*,
                        gv.maGV,
                        gv.hoTen as tenGV,
                        gv.email as emailGV,
                        gv.soDienThoai as sdtGV,
                        gv.toBoMon,
                        bgh.hoTen as nguoiXuLy,
                        ycnp.ngayBatDauNghi,
                        ycnp.ngayKetThucNghi,
                        ycnp.lyDo as lyDoNghiPhep,
                        ycsd.diemHienTai,
                        ycsd.diemDeNghiSua,
                        ycsd.lyDoSuaDiem,
                        ycsd.maHS,
                        ycsd.maBangDiem,
                        ycsd.maMonHoc,
                        ycsd.hocKy,
                        ycsd.namHoc,
                        ycsd.loaiDiem,
                        hs.hoTen as tenHS,
                        mh.tenMonHoc
                    FROM yeucau yc
                    LEFT JOIN yeucaunghiphep ycnp ON yc.maYeuCauNghiPhep = ycnp.maYeuCau
                    LEFT JOIN yeucausuadiem ycsd ON yc.maYeuCauSuaDiem = ycsd.maYeuCau
                    LEFT JOIN giaovien gv ON (ycnp.maGV = gv.maGV OR ycsd.maGV = gv.maGV)
                    LEFT JOIN bgh ON yc.maBGH_XuLy = bgh.maBGH
                    LEFT JOIN hocsinh hs ON ycsd.maHS = hs.maHS
                    LEFT JOIN monhoc mh ON ycsd.maMonHoc = mh.maMonHoc
                    WHERE yc.maYeuCau = ?";
            
            $result = $this->executeQuery($sql, [$maYeuCau]);
            return !empty($result) ? $result[0] : null;
        } catch (Exception $e) {
            error_log("Lỗi layChiTietYeuCau: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Chấp nhận yêu cầu
     */
    public function chapNhanYeuCau($maYeuCau, $maBGH, $ghiChu = null) {
        try {
            mysqli_begin_transaction($this->connection);
            
            // Lấy thông tin yêu cầu
            $yeuCau = $this->layChiTietYeuCau($maYeuCau);
            if (!$yeuCau) {
                throw new Exception("Không tìm thấy yêu cầu");
            }
            
            // Cập nhật trạng thái yêu cầu
            $sql = "UPDATE yeucau 
                    SET trangThai = 'Dachapnhan', 
                        ngayXuLy = NOW(), 
                        maBGH_XuLy = ?
                    WHERE maYeuCau = ?";
            
            if (!$this->executeNonQuery($sql, [$maBGH, $maYeuCau])) {
                throw new Exception("Không thể cập nhật trạng thái yêu cầu");
            }
            
            // Nếu là yêu cầu sửa điểm, cập nhật điểm vào bảng bangdiem
            if ($yeuCau['loaiYeuCau'] == 'SuaDiem' && $yeuCau['maBangDiem']) {
                $this->capNhatDiem($yeuCau);
            }
            
            // Lưu lịch sử xử lý
            $this->luuLichSuXuLy($maYeuCau, $maBGH, $yeuCau['trangThai'], 'Dachapnhan', null, $ghiChu);
            
            mysqli_commit($this->connection);
            return true;
        } catch (Exception $e) {
            mysqli_rollback($this->connection);
            error_log("Lỗi chapNhanYeuCau: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Từ chối yêu cầu
     */
    public function tuChoiYeuCau($maYeuCau, $maBGH, $lyDoTuChoi, $ghiChu = null) {
        try {
            mysqli_begin_transaction($this->connection);
            
            // Lấy trạng thái cũ
            $yeuCau = $this->layChiTietYeuCau($maYeuCau);
            if (!$yeuCau) {
                throw new Exception("Không tìm thấy yêu cầu");
            }
            
            // Cập nhật trạng thái yêu cầu
            $sql = "UPDATE yeucau 
                    SET trangThai = 'Tuchoi', 
                        ngayXuLy = NOW(), 
                        maBGH_XuLy = ?
                    WHERE maYeuCau = ?";
            
            if (!$this->executeNonQuery($sql, [$maBGH, $maYeuCau])) {
                throw new Exception("Không thể cập nhật trạng thái yêu cầu");
            }
            
            // Lưu lịch sử xử lý
            $this->luuLichSuXuLy($maYeuCau, $maBGH, $yeuCau['trangThai'], 'Tuchoi', $lyDoTuChoi, $ghiChu);
            
            mysqli_commit($this->connection);
            return true;
        } catch (Exception $e) {
            mysqli_rollback($this->connection);
            error_log("Lỗi tuChoiYeuCau: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật điểm vào bảng bangdiem
     */
    private function capNhatDiem($yeuCau) {
        $loaiDiem = $yeuCau['loaiDiem'];
        $diemMoi = $yeuCau['diemDeNghiSua'];
        $maBangDiem = $yeuCau['maBangDiem'];
        
        // Cập nhật điểm tương ứng
        $sql = "UPDATE bangdiem SET $loaiDiem = ? WHERE maBangDiem = ?";
        $this->executeNonQuery($sql, [$diemMoi, $maBangDiem]);
        
        // Tính lại điểm trung bình
        $this->tinhLaiDiemTrungBinh($maBangDiem);
    }

    /**
     * Tính lại điểm trung bình
     */
    private function tinhLaiDiemTrungBinh($maBangDiem) {
        $sql = "UPDATE bangdiem 
                SET tbDiem = (
                    COALESCE(diemMieng, 0) + 
                    COALESCE(diem15Phut1, 0) + 
                    COALESCE(diem15Phut2, 0) + 
                    COALESCE(diem1Tiet, 0) * 2 + 
                    COALESCE(diemGiuaKy, 0) * 2 + 
                    COALESCE(diemCuoiKy, 0) * 3
                ) / 10
                WHERE maBangDiem = ?";
        $this->executeNonQuery($sql, [$maBangDiem]);
    }

    /**
     * Lưu lịch sử xử lý yêu cầu
     */
    private function luuLichSuXuLy($maYeuCau, $maBGH, $trangThaiCu, $trangThaiMoi, $lyDoTuChoi = null, $ghiChu = null) {
        $sql = "INSERT INTO lichsu_xuly_yeucau 
                (maYeuCau, maBGH, trangThaiCu, trangThaiMoi, lyDoTuChoi, ghiChu, ngayXuLy) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $this->executeNonQuery($sql, [$maYeuCau, $maBGH, $trangThaiCu, $trangThaiMoi, $lyDoTuChoi, $ghiChu]);
    }

    /**
     * Lấy lịch sử xử lý của một yêu cầu
     */
    public function layLichSuXuLy($maYeuCau) {
        try {
            $sql = "SELECT 
                        ls.*,
                        bgh.hoTen as nguoiXuLy
                    FROM lichsu_xuly_yeucau ls
                    LEFT JOIN bgh ON ls.maBGH = bgh.maBGH
                    WHERE ls.maYeuCau = ?
                    ORDER BY ls.ngayXuLy DESC";
            
            return $this->executeQuery($sql, [$maYeuCau]);
        } catch (Exception $e) {
            error_log("Lỗi layLichSuXuLy: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Thống kê số lượng yêu cầu theo trạng thái
     */
    public function thongKeYeuCau() {
        try {
            $sql = "SELECT 
                        trangThai,
                        loaiYeuCau,
                        COUNT(*) as soLuong
                    FROM yeucau
                    GROUP BY trangThai, loaiYeuCau";
            
            return $this->executeQuery($sql);
        } catch (Exception $e) {
            error_log("Lỗi thongKeYeuCau: " . $e->getMessage());
            return [];
        }
    }
}
?>
