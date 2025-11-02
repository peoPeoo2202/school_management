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
            $types = str_repeat('s', count($params)); // Assume all strings for simplicity
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
            $types = str_repeat('s', count($params)); // Assume all strings for simplicity
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    
    // Lấy báo cáo kết quả học tập theo môn học
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
                    ((AVG(CASE WHEN bd.loaiDiem = 'mieng' THEN bd.diem END) * 1) +
                     (AVG(CASE WHEN bd.loaiDiem = '15phut' THEN bd.diem END) * 1) +
                     (AVG(CASE WHEN bd.loaiDiem = '1tiet' THEN bd.diem END) * 2) +
                     (AVG(CASE WHEN bd.loaiDiem = 'giuaky' THEN bd.diem END) * 2) +
                     (AVG(CASE WHEN bd.loaiDiem = 'cuoiky' THEN bd.diem END) * 3)) / 9 as diemTrungBinh
                FROM bangdiem bd
                JOIN hocsinh hs ON bd.maHS = hs.maHS
                JOIN lophoc lh ON hs.maLop = lh.maLop
                JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                WHERE bd.maGV = ?";
        
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
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    lh.tenLop,
                    COUNT(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 END) as soNghiCoPhep,
                    COUNT(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 END) as soNghiKhongPhep,
                    COUNT(nh.maNghiHoc) as tongSoNghi,
                    hk.tenHanhKiem,
                    hk.loaiHK
                FROM hocsinh hs
                JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN nghihoc nh ON hs.maHS = nh.maHS
                LEFT JOIN hanhkiem hk ON hs.maHanhKiem = hk.maHanhKiem
                WHERE lh.maGV = ?";
        
        $params = [$maGV];
        
        if ($maLop) {
            $sql .= " AND hs.maLop = ?";
            $params[] = $maLop;
        }
        
        $sql .= " GROUP BY hs.maHS
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
    
    // Lấy thống kê điểm môn học
    public function getThongKeDiemMonHoc($maGV, $maMonHoc, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    mh.tenMonHoc,
                    bd.hocKy,
                    bd.namHoc,
                    COUNT(DISTINCT bd.maHS) as soHocSinh,
                    AVG(bd.diem) as diemTrungBinh,
                    MAX(bd.diem) as diemCaoNhat,
                    MIN(bd.diem) as diemThapNhat,
                    COUNT(CASE WHEN bd.diem >= 8.0 THEN 1 END) as soHSGioi,
                    COUNT(CASE WHEN bd.diem >= 6.5 AND bd.diem < 8.0 THEN 1 END) as soHSKha,
                    COUNT(CASE WHEN bd.diem >= 5.0 AND bd.diem < 6.5 THEN 1 END) as soHSTB,
                    COUNT(CASE WHEN bd.diem < 5.0 THEN 1 END) as soHSYeu
                FROM bangdiem bd
                JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                WHERE bd.maGV = ? AND bd.maMonHoc = ?";
        
        $params = [$maGV, $maMonHoc];
        
        if ($hocKy) {
            $sql .= " AND bd.hocKy = ?";
            $params[] = $hocKy;
        }
        if ($namHoc) {
            $sql .= " AND bd.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY bd.maMonHoc, bd.hocKy, bd.namHoc";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy thống kê số liệu học sinh
    public function getThongKeSoLieuHocSinh($maGV, $maLop = null) {
        $sql = "SELECT 
                    lh.maLop,
                    lh.tenLop,
                    lh.siSo,
                    COUNT(CASE WHEN hs.gioiTinh = 'Nam' THEN 1 END) as soHSNam,
                    COUNT(CASE WHEN hs.gioiTinh = 'Nu' THEN 1 END) as soHSNu,
                    COUNT(CASE WHEN hl.loaiHocLuc = 'Gioi' THEN 1 END) as soHSGioi,
                    COUNT(CASE WHEN hl.loaiHocLuc = 'Kha' THEN 1 END) as soHSKha,
                    COUNT(CASE WHEN hl.loaiHocLuc = 'TB' THEN 1 END) as soHSTB,
                    COUNT(CASE WHEN hl.loaiHocLuc = 'Yeu' THEN 1 END) as soHSYeu,
                    COUNT(CASE WHEN hk.loaiHK = 'Tot' THEN 1 END) as soHSHKTot,
                    COUNT(CASE WHEN hk.loaiHK = 'Kha' THEN 1 END) as soHSHKKha,
                    COUNT(CASE WHEN hk.loaiHK = 'TB' THEN 1 END) as soHSHKTB
                FROM lophoc lh
                LEFT JOIN hocsinh hs ON lh.maLop = hs.maLop
                LEFT JOIN hocluc hl ON hs.maHocLuc = hl.maHocLuc
                LEFT JOIN hanhkiem hk ON hs.maHanhKiem = hk.maHanhKiem
                WHERE lh.maGV = ?";
        
        $params = [$maGV];
        
        if ($maLop) {
            $sql .= " AND lh.maLop = ?";
            $params[] = $maLop;
        }
        
        $sql .= " GROUP BY lh.maLop
                  ORDER BY lh.tenLop";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy danh sách lớp của giáo viên
    public function getDanhSachLopCuaGiaoVien($maGV) {
        $sql = "SELECT DISTINCT lh.maLop, lh.tenLop 
                FROM lophoc lh 
                WHERE lh.maGV = ? 
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
    public function luuBaoCao($tenBaoCao, $loaiBaoCao, $noiDung, $hocKy, $namHoc, $maLop = null, $maKhoi = null) {
        $sql = "INSERT INTO baocao (tenBaoCao, loaiBaoCao, noiDung, hocKy, namHoc, maLop, maKhoi) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        return $this->executeNonQuery($sql, [$tenBaoCao, $loaiBaoCao, $noiDung, $hocKy, $namHoc, $maLop, $maKhoi]);
    }
    
    // Lấy danh sách báo cáo đã lưu
    public function getDanhSachBaoCao($loaiBaoCao = null, $hocKy = null, $namHoc = null) {
        $sql = "SELECT * FROM baocao WHERE 1=1";
        $params = [];
        
        if ($loaiBaoCao) {
            $sql .= " AND loaiBaoCao = ?";
            $params[] = $loaiBaoCao;
        }
        if ($hocKy) {
            $sql .= " AND hocKy = ?";
            $params[] = $hocKy;
        }
        if ($namHoc) {
            $sql .= " AND namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " ORDER BY maBaoCao DESC";
        
        return $this->executeQuery($sql, $params);
    }
}
?>