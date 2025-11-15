<?php
require_once 'mConnect.php';

class mReport {
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
            // Tự động phát hiện type của từng parameter
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
            die("Prepare failed: " . mysqli_error($this->connection));
        }
        
        if (!empty($params)) {
            // Tự động phát hiện type của từng parameter
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
    
    // Lấy báo cáo kết quả học tập theo môn học (theo phân công giảng dạy)
    public function getBaoCaoKetQuaHocTap($maGV, $maLop = null, $maMonHoc = null, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    lh.tenLop,
                    mh.tenMonHoc,
                    bd.hocKy,
                    bd.namHoc,
                    AVG(CASE WHEN bd.loaiDiem = 'mieng' THEN bd.diem END) as diemMieng,
                    AVG(CASE WHEN bd.loaiDiem = '15phut' THEN bd.diem END) as diem15phut,
                    AVG(CASE WHEN bd.loaiDiem = '1tiet' THEN bd.diem END) as diem1tiet,
                    AVG(CASE WHEN bd.loaiDiem = 'giuaky' THEN bd.diem END) as diemGiuaKy,
                    AVG(CASE WHEN bd.loaiDiem = 'cuoiky' THEN bd.diem END) as diemCuoiKy,
                    dtb.diemTBMon as diemTrungBinh
                FROM bangdiem bd
                JOIN hocsinh hs ON bd.maHS = hs.maHS
                JOIN lophoc lh ON hs.maLop = lh.maLop
                JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                JOIN phancong_giangday pc ON pc.maGV = ? 
                    AND pc.maLop = lh.maLop 
                    AND pc.maMonHoc = bd.maMonHoc 
                    AND pc.hocKy = bd.hocKy 
                    AND pc.namHoc = bd.namHoc
                    AND pc.trangThai = 'active'
                LEFT JOIN diemtrungbinh dtb ON dtb.maHS = hs.maHS 
                    AND dtb.maMonHoc = bd.maMonHoc 
                    AND dtb.hocKy = bd.hocKy 
                    AND dtb.namHoc = bd.namHoc
                WHERE 1=1";
        
        $params = [$maGV];
        
        if ($maLop) {
            $sql .= " AND hs.maLop = ?";
            $params[] = $maLop;
        }
        if ($maMonHoc) {
            $sql .= " AND bd.maMonHoc = ?";
            $params[] = $maMonHoc;
        }
        if ($hocKy) {
            $sql .= " AND bd.hocKy = ?";
            $params[] = $hocKy;
        }
        if ($namHoc) {
            $sql .= " AND bd.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY hs.maHS, mh.maMonHoc, bd.hocKy, bd.namHoc
                  ORDER BY lh.tenLop, hs.hoTen";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy báo cáo chuyên cần
    public function getBaoCaoChuyenCan($maGV, $maLop = null, $hocKy = null, $namHoc = null) {
        // Đặt giá trị mặc định cho năm học nếu không có
        if (!$namHoc) {
            $namHoc = '2024-2025';
        }
        
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    lh.tenLop,
                    hs.gioiTinh,
                    hs.ngaySinh,
                    SUM(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 ELSE 0 END) as soNghiCoPhep,
                    SUM(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 ELSE 0 END) as soNghiKhongPhep,
                    COUNT(nh.maNghiHoc) as tongSoNghi,
                    CASE 
                        WHEN SUM(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 ELSE 0 END) = 0 
                             AND SUM(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 ELSE 0 END) <= 3 THEN 'Tốt'
                        WHEN SUM(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 ELSE 0 END) <= 1 
                             AND SUM(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 ELSE 0 END) <= 5 THEN 'Khá'
                        WHEN SUM(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 ELSE 0 END) <= 3 
                             AND SUM(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 ELSE 0 END) <= 10 THEN 'Trung bình'
                        ELSE 'Yếu'
                    END as xepLoaiChuyenCan
                FROM nghihoc nh
                JOIN hocsinh hs ON nh.maHS = hs.maHS
                JOIN lophoc lh ON hs.maLop = lh.maLop
                JOIN phancong_giangday pc ON pc.maLop = lh.maLop 
                    AND pc.maGV = ? 
                    AND pc.trangThai = 'active'
                    AND pc.namHoc = ?
                WHERE nh.nguoiDuyet IS NOT NULL 
                    AND nh.namHoc = ?";
        
        $params = [$maGV, $namHoc, $namHoc];
        
        // Thêm điều kiện lọc
        if ($maLop) {
            $sql .= " AND hs.maLop = ?";
            $params[] = $maLop;
        }
        
        if ($hocKy) {
            $sql .= " AND nh.hocKy = ?";
            $params[] = $hocKy;
        }
        
        $sql .= " GROUP BY hs.maHS, hs.hoTen, lh.tenLop, hs.gioiTinh, hs.ngaySinh
                  ORDER BY lh.tenLop, hs.hoTen";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy báo cáo giảng dạy
    public function getBaoCaoGiangDay($maGV, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    kh.maKeHoach,
                    lh.tenLop,
                    mh.tenMonHoc,
                    kh.hocKy,
                    kh.namHoc,
                    kh.tongSoTietKeHoach,
                    COALESCE(SUM(bt.soTietDay), 0) as soTietDaDay,
                    (kh.tongSoTietKeHoach - COALESCE(SUM(bt.soTietDay), 0)) as soTietConLai
                FROM kehoach_giangday kh
                JOIN lophoc lh ON kh.maLop = lh.maLop
                JOIN monhoc mh ON kh.maMonHoc = mh.maMonHoc
                LEFT JOIN buoiday_thucte bt ON kh.maGV = bt.maGV 
                    AND kh.maLop = bt.maLop 
                    AND kh.maMonHoc = bt.maMonHoc 
                    AND kh.hocKy = bt.hocKy 
                    AND kh.namHoc = bt.namHoc
                    AND bt.trangThai = 'Hoan_thanh'
                WHERE kh.maGV = ?";
        
        $params = [$maGV];
        
        if ($hocKy) {
            $sql .= " AND kh.hocKy = ?";
            $params[] = $hocKy;
        }
        if ($namHoc) {
            $sql .= " AND kh.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY kh.maKeHoach, kh.maGV, kh.maLop, kh.maMonHoc, kh.hocKy, kh.namHoc
                  ORDER BY lh.tenLop, mh.tenMonHoc";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy thống kê điểm môn học (theo phân công giảng dạy)
    public function getThongKeDiemMonHoc($maGV, $maMonHoc, $hocKy = null, $namHoc = null, $maLop = null) {
        $sql = "SELECT 
                    mh.tenMonHoc,
                    dtb.hocKy,
                    dtb.namHoc,
                    COUNT(DISTINCT dtb.maHS) as soHocSinh,
                    ROUND(AVG(dtb.diemTBMon), 2) as diemTrungBinh,
                    MAX(dtb.diemTBMon) as diemCaoNhat,
                    MIN(dtb.diemTBMon) as diemThapNhat,
                    COUNT(CASE WHEN dtb.diemTBMon >= 8.0 THEN 1 END) as soHSGioi,
                    COUNT(CASE WHEN dtb.diemTBMon >= 6.5 AND dtb.diemTBMon < 8.0 THEN 1 END) as soHSKha,
                    COUNT(CASE WHEN dtb.diemTBMon >= 5.0 AND dtb.diemTBMon < 6.5 THEN 1 END) as soHSTB,
                    COUNT(CASE WHEN dtb.diemTBMon < 5.0 THEN 1 END) as soHSYeu
                FROM diemtrungbinh dtb
                JOIN monhoc mh ON dtb.maMonHoc = mh.maMonHoc
                JOIN hocsinh hs ON dtb.maHS = hs.maHS
                JOIN lophoc lh ON hs.maLop = lh.maLop
                JOIN phancong_giangday pc ON pc.maGV = ? 
                    AND pc.maLop = lh.maLop 
                    AND pc.maMonHoc = dtb.maMonHoc 
                    AND pc.hocKy = dtb.hocKy 
                    AND pc.namHoc = dtb.namHoc
                    AND pc.trangThai = 'active'
                WHERE dtb.maMonHoc = ? AND dtb.diemTBMon IS NOT NULL";
        
        $params = [$maGV, $maMonHoc];
        
        if ($maLop) {
            $sql .= " AND lh.maLop = ?";
            $params[] = $maLop;
        }
        if ($hocKy) {
            $sql .= " AND dtb.hocKy = ?";
            $params[] = $hocKy;
        }
        if ($namHoc) {
            $sql .= " AND dtb.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY dtb.maMonHoc, dtb.hocKy, dtb.namHoc";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy danh sách học sinh theo môn học (cho thống kê)
    public function getDanhSachHocSinhTheoMon($maGV, $maMonHoc, $hocKy = null, $namHoc = null, $maLop = null) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    lh.tenLop,
                    dtb.diemTBMon,
                    CASE 
                        WHEN dtb.diemTBMon >= 8.0 THEN 'Giỏi'
                        WHEN dtb.diemTBMon >= 6.5 THEN 'Khá'
                        WHEN dtb.diemTBMon >= 5.0 THEN 'Trung bình'
                        ELSE 'Yếu'
                    END as xepLoai
                FROM diemtrungbinh dtb
                JOIN hocsinh hs ON dtb.maHS = hs.maHS
                JOIN lophoc lh ON hs.maLop = lh.maLop
                JOIN phancong_giangday pc ON pc.maGV = ? 
                    AND pc.maLop = lh.maLop 
                    AND pc.maMonHoc = dtb.maMonHoc 
                    AND pc.hocKy = dtb.hocKy 
                    AND pc.namHoc = dtb.namHoc
                    AND pc.trangThai = 'active'
                WHERE dtb.maMonHoc = ? AND dtb.diemTBMon IS NOT NULL";
        
        $params = [$maGV, $maMonHoc];
        
        if ($maLop) {
            $sql .= " AND lh.maLop = ?";
            $params[] = $maLop;
        }
        if ($hocKy) {
            $sql .= " AND dtb.hocKy = ?";
            $params[] = $hocKy;
        }
        if ($namHoc) {
            $sql .= " AND dtb.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " ORDER BY lh.tenLop, hs.hoTen";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy thống kê số liệu học sinh theo điểm môn học
    public function getThongKeSoLieuHocSinh($maGV, $maLop = null, $maMonHoc = null, $hocKy = null, $namHoc = null) {
        // Đặt giá trị mặc định cho năm học nếu không có
        if (!$namHoc) {
            $namHoc = '2024-2025';
        }
        if (!$hocKy) {
            $hocKy = 1;
        }
        
        $sql = "SELECT 
                    lh.maLop,
                    lh.tenLop,
                    mh.maMonHoc,
                    mh.tenMonHoc,
                    lh.siSo,
                    COUNT(DISTINCT hs.maHS) as soHSThucTe,
                    COUNT(CASE WHEN hs.gioiTinh = 'Nam' THEN 1 END) as soHSNam,
                    COUNT(CASE WHEN hs.gioiTinh = 'Nu' THEN 1 END) as soHSNu,
                    COUNT(CASE WHEN dtb.diemTBMon >= 8.0 THEN 1 END) as soHSDiemGioi,
                    COUNT(CASE WHEN dtb.diemTBMon >= 6.5 AND dtb.diemTBMon < 8.0 THEN 1 END) as soHSDiemKha,
                    COUNT(CASE WHEN dtb.diemTBMon >= 5.0 AND dtb.diemTBMon < 6.5 THEN 1 END) as soHSDiemTB,
                    COUNT(CASE WHEN dtb.diemTBMon < 5.0 AND dtb.diemTBMon IS NOT NULL THEN 1 END) as soHSDiemYeu,
                    COUNT(CASE WHEN dtb.diemTBMon IS NULL THEN 1 END) as soHSChuaCoDiem,
                    ROUND(AVG(dtb.diemTBMon), 2) as diemTBLop,
                    MAX(dtb.diemTBMon) as diemCaoNhat,
                    MIN(dtb.diemTBMon) as diemThapNhat
                FROM lophoc lh
                JOIN hocsinh hs ON lh.maLop = hs.maLop
                JOIN phancong_giangday pc ON pc.maLop = lh.maLop 
                    AND pc.maGV = ? 
                    AND pc.trangThai = 'active'
                    AND pc.hocKy = ?
                    AND pc.namHoc = ?
                JOIN monhoc mh ON pc.maMonHoc = mh.maMonHoc
                LEFT JOIN diemtrungbinh dtb ON dtb.maHS = hs.maHS 
                    AND dtb.maMonHoc = pc.maMonHoc 
                    AND dtb.hocKy = pc.hocKy 
                    AND dtb.namHoc = pc.namHoc
                WHERE 1=1";
        
        $params = [$maGV, $hocKy, $namHoc];
        
        if ($maLop) {
            $sql .= " AND lh.maLop = ?";
            $params[] = $maLop;
        }
        
        if ($maMonHoc) {
            $sql .= " AND mh.maMonHoc = ?";
            $params[] = $maMonHoc;
        }
        
        $sql .= " GROUP BY lh.maLop, mh.maMonHoc
                  ORDER BY lh.tenLop, mh.tenMonHoc";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy danh sách lớp của giáo viên
    public function getDanhSachLopCuaGiaoVien($maGV) {
        $sql = "SELECT DISTINCT lh.maLop, lh.tenLop 
                FROM lophoc lh 
                JOIN phancong_giangday pc ON pc.maLop = lh.maLop 
                    AND pc.maGV = ? 
                    AND pc.trangThai = 'active'
                ORDER BY lh.tenLop";
        return $this->executeQuery($sql, [$maGV]);
    }
    
    // Lấy danh sách môn học của giáo viên
    public function getDanhSachMonHocCuaGiaoVien($maGV) {
        $sql = "SELECT DISTINCT mh.maMonHoc, mh.tenMonHoc 
                FROM monhoc mh 
                JOIN lichday ld ON mh.maMonHoc = ld.maMonHoc 
                WHERE ld.maGV = ? 
                ORDER BY mh.tenMonHoc";
        return $this->executeQuery($sql, [$maGV]);
    }
    
    // Lưu báo cáo vào hệ thống
    public function luuBaoCao($tenBaoCao, $loaiBaoCao, $tenFile, $duongDan, $moTa, $maGV) {
        $sql = "INSERT INTO baocao_danop (tenBaoCao, loaiBaoCao, tenFile, duongDan, moTa, maGV) 
                VALUES (?, ?, ?, ?, ?, ?)";
        return $this->executeNonQuery($sql, [$tenBaoCao, $loaiBaoCao, $tenFile, $duongDan, $moTa, $maGV]);
    }
    
    // Lấy danh sách báo cáo đã lưu
    public function getDanhSachBaoCao($maGV, $loaiBaoCao = null) {
        $sql = "SELECT maBaoCao, tenBaoCao, loaiBaoCao, tenFile, duongDan, ngayNop, moTa, ngayTao 
                FROM baocao_danop 
                WHERE maGV = ?";
        $params = [$maGV];
        
        if ($loaiBaoCao) {
            $sql .= " AND loaiBaoCao = ?";
            $params[] = $loaiBaoCao;
        }
        
        $sql .= " ORDER BY ngayNop DESC";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy thông tin báo cáo theo ID
    public function getBaoCaoById($maBaoCao, $maGV) {
        $sql = "SELECT maBaoCao, tenBaoCao, loaiBaoCao, tenFile, duongDan, ngayNop, moTa 
                FROM baocao_danop 
                WHERE maBaoCao = ? AND maGV = ?";
        $result = $this->executeQuery($sql, [$maBaoCao, $maGV]);
        return !empty($result) ? $result[0] : null;
    }
}
?>