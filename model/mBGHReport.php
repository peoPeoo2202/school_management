<?php
require_once 'mConnect.php';

/**
 * Model xử lý báo cáo cho Ban giám hiệu
 * BGH có thể xem tất cả các báo cáo của trường
 */
class mBGHReport {
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
    
    /**
     * Lấy danh sách tất cả các lớp trong trường
     */
    public function getDanhSachTatCaLop() {
        $sql = "SELECT lh.maLop, lh.tenLop, k.khoiLop, lh.siSo 
                FROM lophoc lh
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                ORDER BY k.khoiLop, lh.tenLop";
        return $this->executeQuery($sql);
    }
    
    /**
     * Lấy danh sách tất cả môn học
     */
    public function getDanhSachTatCaMonHoc() {
        $sql = "SELECT maMonHoc, tenMonHoc 
                FROM monhoc 
                ORDER BY tenMonHoc";
        return $this->executeQuery($sql);
    }
    
    /**
     * Lấy danh sách tất cả giáo viên
     */
    public function getDanhSachTatCaGiaoVien() {
        $sql = "SELECT gv.maGV, gv.hoTen, gv.toBoMon 
                FROM giaovien gv
                ORDER BY gv.hoTen";
        return $this->executeQuery($sql);
    }
    
    /**
     * Báo cáo kết quả học tập - BGH xem toàn bộ
     * Có thể lọc theo lớp, môn học, học kỳ, năm học
     */
    public function getBaoCaoKetQuaHocTap($maLop = null, $maMonHoc = null, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    lh.tenLop,
                    k.khoiLop,
                    mh.tenMonHoc,
                    bd.hocKy,
                    bd.namHoc,
                    AVG(CASE WHEN bd.loaiDiem = 'mieng' THEN bd.diem END) as diemMieng,
                    AVG(CASE WHEN bd.loaiDiem = '15phut' THEN bd.diem END) as diem15phut,
                    AVG(CASE WHEN bd.loaiDiem = '1tiet' THEN bd.diem END) as diem1tiet,
                    AVG(CASE WHEN bd.loaiDiem = 'giuaky' THEN bd.diem END) as diemGiuaKy,
                    AVG(CASE WHEN bd.loaiDiem = 'cuoiky' THEN bd.diem END) as diemCuoiKy,
                    dtb.diemTBMon as diemTrungBinh,
                    gv.hoTen as tenGiaoVien
                FROM bangdiem bd
                JOIN hocsinh hs ON bd.maHS = hs.maHS
                JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                LEFT JOIN diemtrungbinh dtb ON dtb.maHS = hs.maHS 
                    AND dtb.maMonHoc = bd.maMonHoc 
                    AND dtb.hocKy = bd.hocKy 
                    AND dtb.namHoc = bd.namHoc
                LEFT JOIN phancong_giangday pc ON pc.maLop = lh.maLop 
                    AND pc.maMonHoc = bd.maMonHoc 
                    AND pc.hocKy = bd.hocKy 
                    AND pc.namHoc = bd.namHoc
                LEFT JOIN giaovien gv ON pc.maGV = gv.maGV
                WHERE 1=1";
        
        $params = [];
        
        if ($maLop !== null && $maLop !== '') {
            $sql .= " AND lh.maLop = ?";
            $params[] = $maLop;
        }
        
        if ($maMonHoc !== null && $maMonHoc !== '') {
            $sql .= " AND bd.maMonHoc = ?";
            $params[] = $maMonHoc;
        }
        
        if ($hocKy !== null && $hocKy !== '') {
            $sql .= " AND bd.hocKy = ?";
            $params[] = $hocKy;
        }
        
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND bd.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY hs.maHS, lh.tenLop, k.khoiLop, mh.tenMonHoc, bd.hocKy, bd.namHoc, dtb.diemTBMon, gv.hoTen
                  ORDER BY lh.tenLop, hs.hoTen";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Báo cáo chuyên cần - BGH xem toàn bộ
     */
    public function getBaoCaoChuyenCan($maLop = null, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    lh.tenLop,
                    k.khoiLop,
                    nh.hocKy,
                    nh.namHoc,
                    COUNT(nh.maNghiHoc) as soNgayNghi,
                    SUM(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 ELSE 0 END) as soNgayCoPhep,
                    SUM(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 ELSE 0 END) as soNgayKhongPhep,
                    GROUP_CONCAT(DISTINCT nh.lyDo SEPARATOR '; ') as ghiChu,
                    gv.hoTen as giaoVienChuNhiem
                FROM hocsinh hs
                JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                LEFT JOIN nghihoc nh ON nh.maHS = hs.maHS
                LEFT JOIN giaovien gv ON lh.maGV = gv.maGV
                WHERE 1=1";
        
        $params = [];
        
        if ($maLop !== null && $maLop !== '') {
            $sql .= " AND lh.maLop = ?";
            $params[] = $maLop;
        }
        
        if ($hocKy !== null && $hocKy !== '') {
            $sql .= " AND nh.hocKy = ?";
            $params[] = $hocKy;
        }
        
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND nh.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY hs.maHS, hs.hoTen, lh.tenLop, k.khoiLop, nh.hocKy, nh.namHoc, gv.hoTen
                  ORDER BY lh.tenLop, hs.hoTen";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Báo cáo giảng dạy - BGH xem tiến độ giảng dạy của tất cả giáo viên
     * Dựa trên kế hoạch giảng dạy và buổi dạy thực tế
     */
    public function getBaoCaoGiangDay($maGV = null, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    gv.maGV,
                    gv.hoTen as tenGiaoVien,
                    gv.toBoMon,
                    lh.tenLop,
                    mh.tenMonHoc,
                    kh.hocKy,
                    kh.namHoc,
                    kh.tongSoTietKeHoach,
                    COALESCE(SUM(bd.soTietDay), 0) as soTietDaDay,
                    (kh.tongSoTietKeHoach - COALESCE(SUM(bd.soTietDay), 0)) as soTietConLai,
                    ROUND((COALESCE(SUM(bd.soTietDay), 0) / kh.tongSoTietKeHoach * 100), 2) as tyLeHoanThanh,
                    COUNT(DISTINCT hs.maHS) as siSo
                FROM kehoach_giangday kh
                JOIN giaovien gv ON kh.maGV = gv.maGV
                JOIN lophoc lh ON kh.maLop = lh.maLop
                JOIN monhoc mh ON kh.maMonHoc = mh.maMonHoc
                LEFT JOIN hocsinh hs ON hs.maLop = lh.maLop
                LEFT JOIN buoiday_thucte bd ON bd.maGV = kh.maGV 
                    AND bd.maLop = kh.maLop 
                    AND bd.maMonHoc = kh.maMonHoc
                    AND bd.hocKy = kh.hocKy
                    AND bd.namHoc = kh.namHoc
                    AND bd.trangThai = 'Hoan_thanh'
                WHERE 1=1";
        
        $params = [];
        
        if ($maGV !== null && $maGV !== '') {
            $sql .= " AND gv.maGV = ?";
            $params[] = $maGV;
        }
        
        if ($hocKy !== null && $hocKy !== '') {
            $sql .= " AND kh.hocKy = ?";
            $params[] = $hocKy;
        }
        
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND kh.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY gv.maGV, gv.hoTen, gv.toBoMon, lh.tenLop, mh.tenMonHoc, kh.hocKy, kh.namHoc, kh.tongSoTietKeHoach
                  ORDER BY gv.hoTen, lh.tenLop";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Báo cáo tổng hợp - Tổng hợp kết quả học tập toàn trường
     */
    public function getBaoCaoTongHop($hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    lh.tenLop,
                    k.khoiLop,
                    lh.siSo,
                    COUNT(DISTINCT hs.maHS) as soHocSinh,
                    COALESCE(dtb.hocKy, ?) as hocKy,
                    COALESCE(dtb.namHoc, ?) as namHoc,
                    AVG(dtb.diemTBMon) as diemTrungBinhLop,
                    SUM(CASE WHEN dtb.diemTBMon >= 8.0 THEN 1 ELSE 0 END) as soHSGioi,
                    SUM(CASE WHEN dtb.diemTBMon >= 6.5 AND dtb.diemTBMon < 8.0 THEN 1 ELSE 0 END) as soHSKha,
                    SUM(CASE WHEN dtb.diemTBMon >= 5.0 AND dtb.diemTBMon < 6.5 THEN 1 ELSE 0 END) as soHSTrungBinh,
                    SUM(CASE WHEN dtb.diemTBMon < 5.0 THEN 1 ELSE 0 END) as soHSYeu,
                    gv.hoTen as giaoVienChuNhiem
                FROM lophoc lh
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                LEFT JOIN hocsinh hs ON hs.maLop = lh.maLop
                LEFT JOIN diemtrungbinh dtb ON dtb.maHS = hs.maHS";
        
        $params = [];
        $whereAdded = false;
        
        if ($hocKy !== null && $hocKy !== '') {
            $sql .= " AND dtb.hocKy = ?";
            $params[] = $hocKy;
            $params[] = $hocKy; // for COALESCE
        } else {
            $params[] = 1; // default value for COALESCE
        }
        
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND dtb.namHoc = ?";
            $params[] = $namHoc;
            $params[] = $namHoc; // for COALESCE
        } else {
            $params[] = '2024-2025'; // default value for COALESCE
        }
        
        $sql .= " LEFT JOIN giaovien gv ON lh.maGV = gv.maGV
                  GROUP BY lh.maLop, lh.tenLop, k.khoiLop, lh.siSo, dtb.hocKy, dtb.namHoc, gv.hoTen
                  ORDER BY k.khoiLop, lh.tenLop";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Thống kê điểm môn học - BGH xem phân bố điểm theo môn
     */
    public function getThongKeDiemMonHoc($maMonHoc = null, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    mh.tenMonHoc,
                    dtb.hocKy,
                    dtb.namHoc,
                    COUNT(DISTINCT dtb.maHS) as tongSoHocSinh,
                    AVG(dtb.diemTBMon) as diemTrungBinh,
                    MAX(dtb.diemTBMon) as diemCaoNhat,
                    MIN(dtb.diemTBMon) as diemThapNhat,
                    SUM(CASE WHEN dtb.diemTBMon >= 8.0 THEN 1 ELSE 0 END) as soHSGioi,
                    SUM(CASE WHEN dtb.diemTBMon >= 6.5 AND dtb.diemTBMon < 8.0 THEN 1 ELSE 0 END) as soHSKha,
                    SUM(CASE WHEN dtb.diemTBMon >= 5.0 AND dtb.diemTBMon < 6.5 THEN 1 ELSE 0 END) as soHSTrungBinh,
                    SUM(CASE WHEN dtb.diemTBMon < 5.0 THEN 1 ELSE 0 END) as soHSYeu
                FROM diemtrungbinh dtb
                JOIN monhoc mh ON dtb.maMonHoc = mh.maMonHoc
                WHERE 1=1";
        
        $params = [];
        
        if ($maMonHoc !== null && $maMonHoc !== '') {
            $sql .= " AND mh.maMonHoc = ?";
            $params[] = $maMonHoc;
        }
        
        if ($hocKy !== null && $hocKy !== '') {
            $sql .= " AND dtb.hocKy = ?";
            $params[] = $hocKy;
        }
        
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND dtb.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY mh.tenMonHoc, dtb.hocKy, dtb.namHoc
                  ORDER BY mh.tenMonHoc";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Thống kê số liệu học sinh - BGH xem tổng quan về học sinh
     */
    public function getThongKeSoLieuHocSinh($maLop = null, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    lh.tenLop,
                    k.khoiLop,
                    lh.siSo,
                    COUNT(DISTINCT hs.maHS) as soHocSinhThucTe,
                    SUM(CASE WHEN hs.gioiTinh = 'Nam' THEN 1 ELSE 0 END) as soHSNam,
                    SUM(CASE WHEN hs.gioiTinh = 'Nu' THEN 1 ELSE 0 END) as soHSNu,
                    COALESCE(AVG(nghiCount.soNgayNghi), 0) as trungBinhNgayNghi,
                    gv.hoTen as giaoVienChuNhiem
                FROM lophoc lh
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                LEFT JOIN hocsinh hs ON hs.maLop = lh.maLop
                LEFT JOIN (
                    SELECT maHS, hocKy, namHoc, COUNT(*) as soNgayNghi
                    FROM nghihoc
                    GROUP BY maHS, hocKy, namHoc
                ) nghiCount ON nghiCount.maHS = hs.maHS";
        
        $params = [];
        
        if ($hocKy !== null && $hocKy !== '') {
            $sql .= " AND nghiCount.hocKy = ?";
            $params[] = $hocKy;
        }
        
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND nghiCount.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " LEFT JOIN giaovien gv ON lh.maGV = gv.maGV
                  WHERE 1=1";
        
        if ($maLop !== null && $maLop !== '') {
            $sql .= " AND lh.maLop = ?";
            $params[] = $maLop;
        }
        
        $sql .= " GROUP BY lh.maLop, lh.tenLop, k.khoiLop, lh.siSo, gv.hoTen
                  ORDER BY k.khoiLop, lh.tenLop";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Báo cáo kết quả đánh giá - Đánh giá tổng hợp học sinh
     */
    public function getBaoCaoKetQuaDanhGia($maLop = null, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    lh.tenLop,
                    k.khoiLop,
                    AVG(dtb.diemTBMon) as diemTBChung,
                    nghiCount.soNgayNghi,
                    nghiCount.soNgayCoPhep,
                    nghiCount.soNgayKhongPhep,
                    CASE 
                        WHEN AVG(dtb.diemTBMon) >= 8.0 AND COALESCE(nghiCount.soNgayNghi, 0) <= 5 THEN 'Giỏi'
                        WHEN AVG(dtb.diemTBMon) >= 6.5 AND COALESCE(nghiCount.soNgayNghi, 0) <= 10 THEN 'Khá'
                        WHEN AVG(dtb.diemTBMon) >= 5.0 AND COALESCE(nghiCount.soNgayNghi, 0) <= 15 THEN 'Trung bình'
                        ELSE 'Yếu'
                    END as xepLoai,
                    dtb.hocKy,
                    dtb.namHoc
                FROM hocsinh hs
                JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                LEFT JOIN diemtrungbinh dtb ON dtb.maHS = hs.maHS
                LEFT JOIN (
                    SELECT 
                        maHS, 
                        hocKy, 
                        namHoc, 
                        COUNT(*) as soNgayNghi,
                        SUM(CASE WHEN loaiNghi = 'cophep' THEN 1 ELSE 0 END) as soNgayCoPhep,
                        SUM(CASE WHEN loaiNghi = 'khongphep' THEN 1 ELSE 0 END) as soNgayKhongPhep
                    FROM nghihoc
                    GROUP BY maHS, hocKy, namHoc
                ) nghiCount ON nghiCount.maHS = hs.maHS 
                    AND nghiCount.hocKy = dtb.hocKy 
                    AND nghiCount.namHoc = dtb.namHoc
                WHERE 1=1";
        
        $params = [];
        
        if ($maLop !== null && $maLop !== '') {
            $sql .= " AND lh.maLop = ?";
            $params[] = $maLop;
        }
        
        if ($hocKy !== null && $hocKy !== '') {
            $sql .= " AND dtb.hocKy = ?";
            $params[] = $hocKy;
        }
        
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND dtb.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY hs.maHS, hs.hoTen, lh.tenLop, k.khoiLop, nghiCount.soNgayNghi, nghiCount.soNgayCoPhep, nghiCount.soNgayKhongPhep, dtb.hocKy, dtb.namHoc
                  ORDER BY lh.tenLop, hs.hoTen";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Lấy danh sách báo cáo đã nộp từ giáo viên
     */
    public function getBaoCaoDaNop($maGV = null, $loaiBaoCao = null, $tuNgay = null, $denNgay = null) {
        $sql = "SELECT 
                    bc.maBaoCao,
                    bc.tenBaoCao,
                    bc.loaiBaoCao,
                    bc.tenFile,
                    bc.duongDan,
                    bc.ngayNop,
                    bc.moTa,
                    gv.hoTen as tenGiaoVien,
                    gv.toBoMon,
                    CASE 
                        WHEN bc.loaiBaoCao = 'hoc-tap' THEN 'Báo cáo học tập'
                        WHEN bc.loaiBaoCao = 'chuyen-can' THEN 'Báo cáo chuyên cần'
                        WHEN bc.loaiBaoCao = 'giang-day' THEN 'Báo cáo giảng dạy'
                        WHEN bc.loaiBaoCao = 'danh-gia' THEN 'Báo cáo đánh giá'
                        ELSE bc.loaiBaoCao
                    END as tenLoaiBaoCao
                FROM baocao_danop bc
                JOIN giaovien gv ON bc.maGV = gv.maGV
                WHERE 1=1";
        
        $params = [];
        
        if ($maGV !== null && $maGV !== '') {
            $sql .= " AND bc.maGV = ?";
            $params[] = $maGV;
        }
        
        if ($loaiBaoCao !== null && $loaiBaoCao !== '') {
            $sql .= " AND bc.loaiBaoCao = ?";
            $params[] = $loaiBaoCao;
        }
        
        if ($tuNgay !== null && $tuNgay !== '') {
            $sql .= " AND DATE(bc.ngayNop) >= ?";
            $params[] = $tuNgay;
        }
        
        if ($denNgay !== null && $denNgay !== '') {
            $sql .= " AND DATE(bc.ngayNop) <= ?";
            $params[] = $denNgay;
        }
        
        $sql .= " ORDER BY bc.ngayNop DESC";
        
        return $this->executeQuery($sql, $params);
    }
}
?>
