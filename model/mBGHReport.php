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
     * Hiển thị điểm và giáo viên được phân công dạy cho từng lớp và môn
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
                    bd.diemTX1,
                    bd.diemTX2,
                    bd.diemTX3,
                    bd.diemTX4,
                    bd.diemGiuaKy,
                    bd.diemCuoiKy,
                    bd.tbDiem as diemTrungBinh,
                    CASE 
                        WHEN bd.tbDiem >= 9.0 THEN 'Xuất sắc'
                        WHEN bd.tbDiem >= 8.0 THEN 'Giỏi'
                        WHEN bd.tbDiem >= 6.5 THEN 'Khá'
                        WHEN bd.tbDiem >= 5.0 THEN 'Trung bình'
                        WHEN bd.tbDiem >= 3.5 THEN 'Yếu'
                        WHEN bd.tbDiem IS NOT NULL THEN 'Kém'
                        ELSE 'Chưa có điểm'
                    END as xepLoai,
                    gv.hoTen as tenGiaoVien,
                    gv.toBoMon
                FROM hocsinh hs
                JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                LEFT JOIN bangdiem bd ON bd.maHS = hs.maHS
                LEFT JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                LEFT JOIN (
                    SELECT DISTINCT maLop, maMonHoc, maGV, hocKy, namHoc
                    FROM v_phancong_giangday
                    WHERE trangThai = 'active'
                ) pc ON pc.maLop = lh.maLop 
                    AND pc.maMonHoc = bd.maMonHoc 
                    AND pc.hocKy = bd.hocKy 
                    AND pc.namHoc = bd.namHoc
                LEFT JOIN giaovien gv ON pc.maGV = gv.maGV
                WHERE bd.maBangDiem IS NOT NULL";
        
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
        
        $sql .= " ORDER BY lh.tenLop, hs.hoTen, mh.tenMonHoc, bd.hocKy";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Báo cáo chuyên cần - BGH xem toàn bộ
     * Dựa vào bảng nghihoc để thống kê số ngày nghỉ của học sinh với lý do chi tiết
     */
    public function getBaoCaoChuyenCan($maLop = null, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    lh.tenLop,
                    k.khoiLop,
                    COALESCE(SUM(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 ELSE 0 END), 0) as soNghiCoPhep,
                    COALESCE(SUM(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 ELSE 0 END), 0) as soNghiKhongPhep,
                    COALESCE(COUNT(nh.maNghiHoc), 0) as tongSoNghi,
                    CASE 
                        WHEN COALESCE(SUM(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 ELSE 0 END), 0) = 0 
                             AND COALESCE(SUM(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 ELSE 0 END), 0) <= 3 THEN 'Tốt'
                        WHEN COALESCE(SUM(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 ELSE 0 END), 0) <= 1 
                             AND COALESCE(SUM(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 ELSE 0 END), 0) <= 5 THEN 'Khá'
                        WHEN COALESCE(SUM(CASE WHEN nh.loaiNghi = 'khongphep' THEN 1 ELSE 0 END), 0) <= 3 
                             AND COALESCE(SUM(CASE WHEN nh.loaiNghi = 'cophep' THEN 1 ELSE 0 END), 0) <= 10 THEN 'Trung bình'
                        ELSE 'Yếu'
                    END as xepLoaiChuyenCan,
                    gv.hoTen as giaoVienChuNhiem
                FROM lophoc lh
                JOIN hocsinh hs ON hs.maLop = lh.maLop
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                LEFT JOIN nghihoc nh ON nh.maHS = hs.maHS";
        
        $params = [];
        
        // Thêm điều kiện lọc trong JOIN
        if ($hocKy !== null && $hocKy !== '') {
            $sql .= " AND nh.hocKy = ?";
            $params[] = $hocKy;
        }
        
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND nh.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " LEFT JOIN giaovien gv ON lh.maGV = gv.maGV
                  WHERE 1=1";
        
        if ($maLop !== null && $maLop !== '') {
            $sql .= " AND lh.maLop = ?";
            $params[] = $maLop;
        }
        
        $sql .= " GROUP BY hs.maHS, hs.hoTen, lh.tenLop, k.khoiLop, gv.hoTen
                  ORDER BY lh.tenLop, hs.hoTen";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Lấy chi tiết ngày nghỉ của một học sinh
     */
    public function getChiTietNghiHocCuaHocSinh($maHS, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    nh.ngayNghi,
                    nh.loaiNghi,
                    nh.lyDo,
                    gv.hoTen as nguoiDuyet,
                    DATE_FORMAT(nh.ngayNghi, '%d/%m/%Y') as ngayNghiFormat
                FROM nghihoc nh
                LEFT JOIN giaovien gv ON nh.nguoiDuyet = gv.maGV
                WHERE nh.maHS = ?";
        
        $params = [$maHS];
        
        if ($hocKy !== null && $hocKy !== '') {
            $sql .= " AND nh.hocKy = ?";
            $params[] = $hocKy;
        }
        
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND nh.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " ORDER BY nh.ngayNghi DESC";
        
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
                    lh.siSo
                FROM kehoach_giangday kh
                JOIN giaovien gv ON kh.maGV = gv.maGV
                JOIN lophoc lh ON kh.maLop = lh.maLop
                JOIN monhoc mh ON kh.maMonHoc = mh.maMonHoc
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
        
        $sql .= " GROUP BY gv.maGV, gv.hoTen, gv.toBoMon, lh.tenLop, mh.tenMonHoc, kh.hocKy, kh.namHoc, kh.tongSoTietKeHoach, lh.siSo
                  ORDER BY gv.hoTen, lh.tenLop";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Báo cáo tổng hợp - Tổng hợp kết quả học tập toàn trường
     */
    public function getBaoCaoTongHop($hocKy = null, $namHoc = null) {
        // Query to get overall class statistics based on hocluc table
        $sql = "SELECT 
                    lh.tenLop,
                    k.khoiLop,
                    lh.siSo,
                    COUNT(DISTINCT hs.maHS) as soHocSinh,";
        
        // Determine which average column to use based on hocKy
        if ($hocKy == '1') {
            $sql .= " AVG(hl.diemTBHK1) as diemTrungBinhLop,
                    SUM(CASE WHEN hl.diemTBHK1 >= 9.0 THEN 1 ELSE 0 END) as soHSXuatSac,
                    SUM(CASE WHEN hl.diemTBHK1 >= 8.0 AND hl.diemTBHK1 < 9.0 THEN 1 ELSE 0 END) as soHSGioi,
                    SUM(CASE WHEN hl.diemTBHK1 >= 6.5 AND hl.diemTBHK1 < 8.0 THEN 1 ELSE 0 END) as soHSKha,
                    SUM(CASE WHEN hl.diemTBHK1 >= 5.0 AND hl.diemTBHK1 < 6.5 THEN 1 ELSE 0 END) as soHSTrungBinh,
                    SUM(CASE WHEN hl.diemTBHK1 < 5.0 AND hl.diemTBHK1 IS NOT NULL THEN 1 ELSE 0 END) as soHSYeu,";
        } elseif ($hocKy == '2') {
            $sql .= " AVG(hl.diemTBHK2) as diemTrungBinhLop,
                    SUM(CASE WHEN hl.diemTBHK2 >= 9.0 THEN 1 ELSE 0 END) as soHSXuatSac,
                    SUM(CASE WHEN hl.diemTBHK2 >= 8.0 AND hl.diemTBHK2 < 9.0 THEN 1 ELSE 0 END) as soHSGioi,
                    SUM(CASE WHEN hl.diemTBHK2 >= 6.5 AND hl.diemTBHK2 < 8.0 THEN 1 ELSE 0 END) as soHSKha,
                    SUM(CASE WHEN hl.diemTBHK2 >= 5.0 AND hl.diemTBHK2 < 6.5 THEN 1 ELSE 0 END) as soHSTrungBinh,
                    SUM(CASE WHEN hl.diemTBHK2 < 5.0 AND hl.diemTBHK2 IS NOT NULL THEN 1 ELSE 0 END) as soHSYeu,";
        } else {
            // Default to year average
            $sql .= " AVG(hl.diemTBCaNam) as diemTrungBinhLop,
                    SUM(CASE WHEN hl.diemTBCaNam >= 9.0 THEN 1 ELSE 0 END) as soHSXuatSac,
                    SUM(CASE WHEN hl.diemTBCaNam >= 8.0 AND hl.diemTBCaNam < 9.0 THEN 1 ELSE 0 END) as soHSGioi,
                    SUM(CASE WHEN hl.diemTBCaNam >= 6.5 AND hl.diemTBCaNam < 8.0 THEN 1 ELSE 0 END) as soHSKha,
                    SUM(CASE WHEN hl.diemTBCaNam >= 5.0 AND hl.diemTBCaNam < 6.5 THEN 1 ELSE 0 END) as soHSTrungBinh,
                    SUM(CASE WHEN hl.diemTBCaNam < 5.0 AND hl.diemTBCaNam IS NOT NULL THEN 1 ELSE 0 END) as soHSYeu,";
        }
        
        $sql .= " gv.hoTen as giaoVienChuNhiem
                FROM lophoc lh
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                LEFT JOIN hocsinh hs ON hs.maLop = lh.maLop
                LEFT JOIN giaovien gv ON lh.maGV = gv.maGV
                LEFT JOIN hocluc hl ON hl.maHS = hs.maHS";
        
        $params = [];
        
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND hl.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY lh.maLop, lh.tenLop, k.khoiLop, lh.siSo, gv.hoTen
                  ORDER BY k.khoiLop, lh.tenLop";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Thống kê điểm môn học - BGH xem phân bố điểm theo môn
     * Dựa vào bảng bangdiem để thống kê
     */
    public function getThongKeDiemMonHoc($maMonHoc = null, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    mh.tenMonHoc,
                    bd.hocKy,
                    bd.namHoc,
                    COUNT(DISTINCT bd.maHS) as tongSoHocSinh,
                    ROUND(AVG(bd.tbDiem), 2) as diemTrungBinh,
                    MAX(bd.tbDiem) as diemCaoNhat,
                    MIN(bd.tbDiem) as diemThapNhat,
                    SUM(CASE WHEN bd.tbDiem >= 9.0 THEN 1 ELSE 0 END) as soHSXuatSac,
                    SUM(CASE WHEN bd.tbDiem >= 8.0 AND bd.tbDiem < 9.0 THEN 1 ELSE 0 END) as soHSGioi,
                    SUM(CASE WHEN bd.tbDiem >= 6.5 AND bd.tbDiem < 8.0 THEN 1 ELSE 0 END) as soHSKha,
                    SUM(CASE WHEN bd.tbDiem >= 5.0 AND bd.tbDiem < 6.5 THEN 1 ELSE 0 END) as soHSTrungBinh,
                    SUM(CASE WHEN bd.tbDiem >= 3.5 AND bd.tbDiem < 5.0 THEN 1 ELSE 0 END) as soHSYeu,
                    SUM(CASE WHEN bd.tbDiem < 3.5 AND bd.tbDiem IS NOT NULL THEN 1 ELSE 0 END) as soHSKem
                FROM bangdiem bd
                JOIN monhoc mh ON bd.maMonHoc = mh.maMonHoc
                WHERE bd.tbDiem IS NOT NULL";
        
        $params = [];
        
        if ($maMonHoc !== null && $maMonHoc !== '') {
            $sql .= " AND mh.maMonHoc = ?";
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
        
        $sql .= " GROUP BY mh.tenMonHoc, bd.hocKy, bd.namHoc
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
                    AVG(bd.tbDiem) as diemTBChung,
                    nghiCount.soNgayNghi,
                    nghiCount.soNgayCoPhep,
                    nghiCount.soNgayKhongPhep,
                    CASE 
                        WHEN AVG(bd.tbDiem) >= 8.0 AND COALESCE(nghiCount.soNgayNghi, 0) <= 5 THEN 'Giỏi'
                        WHEN AVG(bd.tbDiem) >= 6.5 AND COALESCE(nghiCount.soNgayNghi, 0) <= 10 THEN 'Khá'
                        WHEN AVG(bd.tbDiem) >= 5.0 AND COALESCE(nghiCount.soNgayNghi, 0) <= 15 THEN 'Trung bình'
                        ELSE 'Yếu'
                    END as xepLoai,
                    bd.hocKy,
                    bd.namHoc
                FROM hocsinh hs
                JOIN lophoc lh ON hs.maLop = lh.maLop
                LEFT JOIN khoi k ON lh.maKhoi = k.maKhoi
                LEFT JOIN bangdiem bd ON bd.maHS = hs.maHS
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
                    AND nghiCount.hocKy = bd.hocKy 
                    AND nghiCount.namHoc = bd.namHoc
                WHERE 1=1";
        
        $params = [];
        
        if ($maLop !== null && $maLop !== '') {
            $sql .= " AND lh.maLop = ?";
            $params[] = $maLop;
        }
        
        if ($hocKy !== null && $hocKy !== '') {
            $sql .= " AND bd.hocKy = ?";
            $params[] = $hocKy;
        }
        
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND bd.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY hs.maHS, hs.hoTen, lh.tenLop, k.khoiLop, nghiCount.soNgayNghi, nghiCount.soNgayCoPhep, nghiCount.soNgayKhongPhep, bd.hocKy, bd.namHoc
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
    
    // ========== BÁO CÁO KẾT QUẢ ĐÁNH GIÁ HỌC SINH ==========
    
    /**
     * Lấy danh sách tất cả danh hiệu với tiêu chí
     */
    public function layDanhSachDanhHieu() {
        $sql = "SELECT * FROM danhhieu ORDER BY maDanhHieu";
        return $this->executeQuery($sql);
    }
    
    /**
     * Lấy kết quả học tập và rèn luyện của học sinh theo năm học
     */
    public function layKetQuaHocSinh($namHoc, $maLop = null, $maKhoi = null) {
        $sql = "SELECT 
            hs.maHS,
            hs.hoTen,
            hs.ngaySinh,
            hs.gioiTinh,
            l.maLop,
            l.tenLop,
            l.maKhoi,
            k.khoiLop as tenKhoi,
            hl.diemTBHK1,
            hl.diemTBHK2,
            hl.diemTBCaNam,
            hl.loaiHocLuc,
            hl.soMonDuoi5,
            hk1.loaiHK as hanhKiemHK1,
            hk1.soBuoiNghiCoPhep as nghiCoPhepHK1,
            hk1.soBuoiNghiKhongPhep as nghiKhongPhepHK1,
            hk2.loaiHK as hanhKiemHK2,
            hk2.soBuoiNghiCoPhep as nghiCoPhepHK2,
            hk2.soBuoiNghiKhongPhep as nghiKhongPhepHK2,
            (IFNULL(hk1.soBuoiNghiCoPhep, 0) + IFNULL(hk2.soBuoiNghiCoPhep, 0)) as tongNghiCoPhep,
            (IFNULL(hk1.soBuoiNghiKhongPhep, 0) + IFNULL(hk2.soBuoiNghiKhongPhep, 0)) as tongNghiKhongPhep,
            dh.tenDanhHieu as danhHieuHienTai
        FROM hocsinh hs
        LEFT JOIN lophoc l ON hs.maLop = l.maLop
        LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
        LEFT JOIN hocluc hl ON hs.maHS = hl.maHS AND hl.namHoc = ?
        LEFT JOIN hanhkiem hk1 ON hs.maHS = hk1.maHS AND hk1.hocKy = 1 AND hk1.namHoc = ?
        LEFT JOIN hanhkiem hk2 ON hs.maHS = hk2.maHS AND hk2.hocKy = 2 AND hk2.namHoc = ?
        LEFT JOIN (
            SELECT hdd.maHS, hdd.maDanhHieu, hdd.namHoc
            FROM hocsinh_danhhieu hdd
            INNER JOIN (
                SELECT maHS, MAX(namHoc) as maxNamHoc
                FROM hocsinh_danhhieu
                GROUP BY maHS
            ) latest ON hdd.maHS = latest.maHS AND hdd.namHoc = latest.maxNamHoc
        ) hdd ON hs.maHS = hdd.maHS
        LEFT JOIN danhhieu dh ON hdd.maDanhHieu = dh.maDanhHieu
        WHERE hs.trangThaiHocTap = 'danghoc'";

        $params = [$namHoc, $namHoc, $namHoc];

        if ($maLop) {
            $sql .= " AND hs.maLop = ?";
            $params[] = $maLop;
        }

        if ($maKhoi) {
            $sql .= " AND l.maKhoi = ?";
            $params[] = $maKhoi;
        }

        $sql .= " ORDER BY l.tenLop, hs.maHS, hs.hoTen";

        $ketQua = $this->executeQuery($sql, $params);

        // Thêm thông tin vi phạm, khen thưởng và đề xuất danh hiệu
        foreach ($ketQua as &$hs) {
            // Đếm vi phạm
            $sqlVP = "SELECT COUNT(*) as soViPham FROM vipham WHERE maHS = ? AND namHoc = ?";
            $vp = $this->executeQuery($sqlVP, [$hs['maHS'], $namHoc]);
            $hs['soViPham'] = $vp[0]['soViPham'] ?? 0;

            // Đếm khen thưởng
            $sqlKT = "SELECT COUNT(*) as soKhenThuong,
                      MAX(CASE 
                          WHEN capKhenThuong = 'quocgia' THEN 4
                          WHEN capKhenThuong = 'tinh' THEN 3
                          WHEN capKhenThuong = 'huyen' THEN 2
                          WHEN capKhenThuong = 'truong' THEN 1
                          ELSE 0
                      END) as capKhenThuongCaoNhat
                      FROM khenthuong WHERE maHS = ? AND namHoc = ?";
            $kt = $this->executeQuery($sqlKT, [$hs['maHS'], $namHoc]);
            $hs['soKhenThuong'] = $kt[0]['soKhenThuong'] ?? 0;
            $hs['capKhenThuongCaoNhat'] = $kt[0]['capKhenThuongCaoNhat'] ?? 0;

            // Xác định hạnh kiểm cả năm
            $hs['hanhKiemCaNam'] = $hs['hanhKiemHK2'] ? $hs['hanhKiemHK2'] : $hs['hanhKiemHK1'];

            // Đề xuất danh hiệu
            $hs['danhHieuDeXuat'] = $this->xetDanhHieu($hs);
        }

        return $ketQua;
    }
    
    /**
     * Xét danh hiệu phù hợp cho học sinh dựa trên tiêu chí
     */
    private function xetDanhHieu($hocSinh) {
        $sql = "SELECT * FROM danhhieu ORDER BY 
                CASE 
                    WHEN hocLucToiThieu = 'Xuat sac' THEN 5
                    WHEN hocLucToiThieu = 'Gioi' THEN 4
                    WHEN hocLucToiThieu = 'Kha' THEN 3
                    WHEN hocLucToiThieu = 'TB' THEN 2
                    ELSE 1
                END DESC,
                diemTBToiThieu DESC";
        
        $danhSachDanhHieu = $this->executeQuery($sql);
        
        foreach ($danhSachDanhHieu as $dh) {
            if ($this->kiemTraDieuKienDanhHieu($hocSinh, $dh)) {
                return $dh;
            }
        }
        
        return null;
    }
    
    /**
     * Kiểm tra học sinh có đủ điều kiện danh hiệu không
     */
    private function kiemTraDieuKienDanhHieu($hs, $dh) {
        // 1. Kiểm tra học lực
        if (!$this->kiemTraHocLuc($hs['loaiHocLuc'], $dh['hocLucToiThieu'])) {
            return false;
        }
        
        // 2. Kiểm tra điểm TB
        if ($dh['diemTBToiThieu'] && $hs['diemTBCaNam'] < $dh['diemTBToiThieu']) {
            return false;
        }
        
        // 3. Kiểm tra hạnh kiểm
        if (!$this->kiemTraHanhKiem($hs['hanhKiemCaNam'], $dh['hanhKiemToiThieu'])) {
            return false;
        }
        
        // 4. Kiểm tra số vi phạm
        if ($hs['soViPham'] > $dh['soViPhamToiDa']) {
            return false;
        }
        
        // 5. Kiểm tra yêu cầu khen thưởng
        if ($dh['yeuCauKhenThuong'] == 1 && $hs['soKhenThuong'] == 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * So sánh học lực
     */
    private function kiemTraHocLuc($hocLucHS, $hocLucYeuCau) {
        $thuTu = [
            'Xuat sac' => 5,
            'Gioi' => 4,
            'Kha' => 3,
            'TB' => 2,
            'Yeu' => 1,
            'Kem' => 0
        ];
        
        $capHS = $thuTu[$hocLucHS] ?? 0;
        $capYC = $thuTu[$hocLucYeuCau] ?? 0;
        
        return $capHS >= $capYC;
    }
    
    /**
     * So sánh hạnh kiểm
     */
    private function kiemTraHanhKiem($hanhKiemHS, $hanhKiemYeuCau) {
        $thuTu = [
            'Tot' => 3,
            'Kha' => 2,
            'TB' => 1,
            'Yeu' => 0
        ];
        
        $capHS = $thuTu[$hanhKiemHS] ?? 0;
        $capYC = $thuTu[$hanhKiemYeuCau] ?? 0;
        
        return $capHS >= $capYC;
    }
    
    /**
     * Lấy danh sách lớp học
     */
    public function layDanhSachLop($maKhoi = null) {
        $sql = "SELECT l.maLop, l.tenLop, l.maKhoi, k.khoiLop as tenKhoi 
                FROM lophoc l 
                JOIN khoi k ON l.maKhoi = k.maKhoi";
        
        $params = [];
        
        if ($maKhoi) {
            $sql .= " WHERE l.maKhoi = ?";
            $params[] = $maKhoi;
        }
        
        $sql .= " ORDER BY k.khoiLop, l.tenLop";
        
        return $this->executeQuery($sql, $params);
    }
    
    /**
     * Lấy danh sách khối
     */
    public function layDanhSachKhoi() {
        $sql = "SELECT maKhoi, khoiLop as tenKhoi FROM khoi ORDER BY maKhoi";
        return $this->executeQuery($sql);
    }
    
    /**
     * Thống kê danh hiệu theo lớp/khối
     */
    public function thongKeDanhHieu($namHoc, $maLop = null, $maKhoi = null) {
        $hocSinhs = $this->layKetQuaHocSinh($namHoc, $maLop, $maKhoi);
        
        $thongKe = [
            'tongSo' => count($hocSinhs),
            'danhHieu' => [],
            'hocLuc' => [],
            'hanhKiem' => [],
            'coKhenThuong' => 0,
            'coViPham' => 0
        ];
        
        foreach ($hocSinhs as $hs) {
            // Thống kê danh hiệu hiện tại (ưu tiên trường tenDanhHieu hoặc danhHieuHienTai)
            $tenDH = $hs['tenDanhHieu'] ?? ($hs['danhHieuHienTai'] ?? null);
            if ($tenDH) {
                $thongKe['danhHieu'][$tenDH] = ($thongKe['danhHieu'][$tenDH] ?? 0) + 1;
            }
            // Thống kê học lực
            if ($hs['loaiHocLuc']) {
                $thongKe['hocLuc'][$hs['loaiHocLuc']] = ($thongKe['hocLuc'][$hs['loaiHocLuc']] ?? 0) + 1;
            }
            // Thống kê hạnh kiểm
            if ($hs['hanhKiemCaNam']) {
                $thongKe['hanhKiem'][$hs['hanhKiemCaNam']] = ($thongKe['hanhKiem'][$hs['hanhKiemCaNam']] ?? 0) + 1;
            }
            // Đếm học sinh có khen thưởng
            if ($hs['soKhenThuong'] > 0) {
                $thongKe['coKhenThuong']++;
            }
            // Đếm học sinh có vi phạm
            if ($hs['soViPham'] > 0) {
                $thongKe['coViPham']++;
            }
        }
        
        return $thongKe;
    }
    
    /**
     * Lấy tổng số giáo viên trong trường
     */
    public function getTongSoGiaoVien() {
        $sql = "SELECT COUNT(*) as total FROM giaovien";
        $result = $this->executeQuery($sql);
        return $result[0]['total'] ?? 0;
    }
    
    /**
     * Lấy tổng số môn học
     */
    public function getTongSoMonHoc() {
        $sql = "SELECT COUNT(*) as total FROM monhoc";
        $result = $this->executeQuery($sql);
        return $result[0]['total'] ?? 0;
    }
    
    /**
     * Lấy thống kê học sinh theo giới tính
     */
    public function getThongKeHocSinhTheoGioiTinh($namHoc = null) {
        $sql = "SELECT 
                    gioiTinh,
                    COUNT(*) as soLuong
                FROM hocsinh hs
                JOIN lophoc lh ON hs.maLop = lh.maLop
                WHERE 1=1";
        
        $params = [];
        if ($namHoc !== null && $namHoc !== '') {
            $sql .= " AND lh.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY gioiTinh";
        
        $result = $this->executeQuery($sql, $params);
        
        $data = ['Nam' => 0, 'Nu' => 0];
        foreach ($result as $row) {
            $data[$row['gioiTinh']] = $row['soLuong'];
        }
        
        return $data;
    }
}
?>
