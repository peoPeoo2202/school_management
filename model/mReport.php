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
    // Mỗi giáo viên chỉ xem được điểm của lớp và môn mà họ được phân công dạy
    public function getBaoCaoKetQuaHocTap($maGV, $maLop = null, $maMonHoc = null, $hocKy = null, $namHoc = null) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    lh.tenLop,
                    mh.tenMonHoc,
                    bd.hocKy,
                    bd.namHoc,
                    bd.diemMieng,
                    bd.diem15Phut1,
                    bd.diem15Phut2,
                    bd.diem1Tiet,
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
                    END as xepLoai
                FROM phancong_giangday pc
                JOIN lophoc lh ON pc.maLop = lh.maLop
                JOIN hocsinh hs ON hs.maLop = lh.maLop
                JOIN monhoc mh ON pc.maMonHoc = mh.maMonHoc
                LEFT JOIN bangdiem bd ON bd.maHS = hs.maHS 
                    AND bd.maMonHoc = pc.maMonHoc 
                    AND bd.hocKy = pc.hocKy 
                    AND bd.namHoc = pc.namHoc
                WHERE pc.maGV = ? AND pc.trangThai = 'active'";
        
        $params = [$maGV];
        
        if ($maLop) {
            $sql .= " AND pc.maLop = ?";
            $params[] = $maLop;
        }
        if ($maMonHoc) {
            $sql .= " AND pc.maMonHoc = ?";
            $params[] = $maMonHoc;
        }
        if ($hocKy) {
            $sql .= " AND pc.hocKy = ?";
            $params[] = $hocKy;
        }
        if ($namHoc) {
            $sql .= " AND pc.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " ORDER BY lh.tenLop, hs.hoTen, mh.tenMonHoc";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy báo cáo chuyên cần dựa vào phân công lớp và bảng nghỉ học
    // Giáo viên chỉ xem chuyên cần của học sinh trong các lớp được phân công dạy
    public function getBaoCaoChuyenCan($maGV, $maLop = null, $hocKy = null, $namHoc = null) {
        // Đặt giá trị mặc định cho năm học nếu không có
        if (!$namHoc) {
            $namHoc = '2024-2025';
        }
        
        $sql = "SELECT DISTINCT
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    lh.tenLop,
                    hs.gioiTinh,
                    hs.ngaySinh,
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
                    GROUP_CONCAT(DISTINCT CASE WHEN nh.loaiNghi = 'cophep' THEN DATE_FORMAT(nh.ngayNghi, '%d/%m/%Y') END ORDER BY nh.ngayNghi SEPARATOR ', ') as danhSachNghiCoPhep,
                    GROUP_CONCAT(DISTINCT CASE WHEN nh.loaiNghi = 'cophep' THEN nh.lyDo END ORDER BY nh.ngayNghi SEPARATOR ', ') as lyDoNghiCoPhep,
                    GROUP_CONCAT(DISTINCT CASE WHEN nh.loaiNghi = 'khongphep' THEN DATE_FORMAT(nh.ngayNghi, '%d/%m/%Y') END ORDER BY nh.ngayNghi SEPARATOR ', ') as danhSachNghiKhongPhep
                FROM phancong_giangday pc
                JOIN lophoc lh ON pc.maLop = lh.maLop
                JOIN hocsinh hs ON hs.maLop = lh.maLop
                LEFT JOIN nghihoc nh ON nh.maHS = hs.maHS 
                    AND nh.namHoc = pc.namHoc";
        
        // Thêm điều kiện lọc học kỳ trong JOIN nếu có
        if ($hocKy) {
            $sql .= " AND nh.hocKy = ?";
        }
        
        $sql .= " WHERE pc.maGV = ? AND pc.namHoc = ? AND pc.trangThai = 'active'";
        
        $params = [];
        if ($hocKy) {
            $params[] = $hocKy;
        }
        $params[] = $maGV;
        $params[] = $namHoc;
        
        // Thêm điều kiện lọc lớp
        if ($maLop) {
            $sql .= " AND pc.maLop = ?";
            $params[] = $maLop;
        }
        
        // Thêm điều kiện lọc học kỳ trong WHERE nếu có
        if ($hocKy) {
            $sql .= " AND pc.hocKy = ?";
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
    // Dựa vào bảng bangdiem và tính toán thống kê điểm trung bình
    public function getThongKeDiemMonHoc($maGV, $maMonHoc, $hocKy = null, $namHoc = null, $maLop = null) {
        $sql = "SELECT 
                    mh.tenMonHoc,
                    bd.hocKy,
                    bd.namHoc,
                    lh.tenLop,
                    COUNT(DISTINCT bd.maHS) as soHocSinh,
                    ROUND(AVG(bd.tbDiem), 2) as diemTrungBinh,
                    MAX(bd.tbDiem) as diemCaoNhat,
                    MIN(bd.tbDiem) as diemThapNhat,
                    COUNT(CASE WHEN bd.tbDiem >= 9.0 THEN 1 END) as soHSXuatSac,
                    COUNT(CASE WHEN bd.tbDiem >= 8.0 AND bd.tbDiem < 9.0 THEN 1 END) as soHSGioi,
                    COUNT(CASE WHEN bd.tbDiem >= 6.5 AND bd.tbDiem < 8.0 THEN 1 END) as soHSKha,
                    COUNT(CASE WHEN bd.tbDiem >= 5.0 AND bd.tbDiem < 6.5 THEN 1 END) as soHSTB,
                    COUNT(CASE WHEN bd.tbDiem >= 3.5 AND bd.tbDiem < 5.0 THEN 1 END) as soHSYeu,
                    COUNT(CASE WHEN bd.tbDiem < 3.5 AND bd.tbDiem IS NOT NULL THEN 1 END) as soHSKem
                FROM phancong_giangday pc
                JOIN lophoc lh ON pc.maLop = lh.maLop
                JOIN hocsinh hs ON hs.maLop = lh.maLop
                JOIN monhoc mh ON pc.maMonHoc = mh.maMonHoc
                LEFT JOIN bangdiem bd ON bd.maHS = hs.maHS 
                    AND bd.maMonHoc = pc.maMonHoc 
                    AND bd.hocKy = pc.hocKy 
                    AND bd.namHoc = pc.namHoc
                WHERE pc.maGV = ? AND pc.maMonHoc = ? AND pc.trangThai = 'active'";
        
        $params = [$maGV, $maMonHoc];
        
        if ($maLop) {
            $sql .= " AND pc.maLop = ?";
            $params[] = $maLop;
        }
        if ($hocKy) {
            $sql .= " AND pc.hocKy = ?";
            $params[] = $hocKy;
        }
        if ($namHoc) {
            $sql .= " AND pc.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " GROUP BY lh.tenLop, mh.tenMonHoc, bd.hocKy, bd.namHoc
                  ORDER BY lh.tenLop";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy danh sách học sinh theo môn học (cho thống kê)
    // Dựa vào phân công giảng dạy và bảng điểm
    public function getDanhSachHocSinhTheoMon($maGV, $maMonHoc, $hocKy = null, $namHoc = null, $maLop = null) {
        $sql = "SELECT 
                    hs.maHS,
                    hs.hoTen as tenHocSinh,
                    lh.tenLop,
                    bd.tbDiem as diemTBMon,
                    CASE 
                        WHEN bd.tbDiem >= 9.0 THEN 'Xuất sắc'
                        WHEN bd.tbDiem >= 8.0 THEN 'Giỏi'
                        WHEN bd.tbDiem >= 6.5 THEN 'Khá'
                        WHEN bd.tbDiem >= 5.0 THEN 'Trung bình'
                        WHEN bd.tbDiem >= 3.5 THEN 'Yếu'
                        WHEN bd.tbDiem IS NOT NULL THEN 'Kém'
                        ELSE 'Chưa có điểm'
                    END as xepLoai,
                    bd.diemMieng,
                    bd.diem15Phut1,
                    bd.diem15Phut2,
                    bd.diem1Tiet,
                    bd.diemGiuaKy,
                    bd.diemCuoiKy
                FROM phancong_giangday pc
                JOIN lophoc lh ON pc.maLop = lh.maLop
                JOIN hocsinh hs ON hs.maLop = lh.maLop
                LEFT JOIN bangdiem bd ON bd.maHS = hs.maHS 
                    AND bd.maMonHoc = pc.maMonHoc 
                    AND bd.hocKy = pc.hocKy 
                    AND bd.namHoc = pc.namHoc
                WHERE pc.maGV = ? AND pc.maMonHoc = ? AND pc.trangThai = 'active'";
        
        $params = [$maGV, $maMonHoc];
        
        if ($maLop) {
            $sql .= " AND pc.maLop = ?";
            $params[] = $maLop;
        }
        if ($hocKy) {
            $sql .= " AND pc.hocKy = ?";
            $params[] = $hocKy;
        }
        if ($namHoc) {
            $sql .= " AND pc.namHoc = ?";
            $params[] = $namHoc;
        }
        
        $sql .= " ORDER BY lh.tenLop, hs.hoTen";
        
        return $this->executeQuery($sql, $params);
    }
    
    // Lấy thống kê số liệu học sinh theo điểm môn học
    // Dựa vào phân công giảng dạy và bảng điểm
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
                    COUNT(CASE WHEN bd.tbDiem >= 8.0 THEN 1 END) as soHSGioi,
                    COUNT(CASE WHEN bd.tbDiem >= 6.5 AND bd.tbDiem < 8.0 THEN 1 END) as soHSKha,
                    COUNT(CASE WHEN bd.tbDiem >= 5.0 AND bd.tbDiem < 6.5 THEN 1 END) as soHSTB,
                    COUNT(CASE WHEN bd.tbDiem < 5.0 AND bd.tbDiem IS NOT NULL THEN 1 END) as soHSYeu,
                    COUNT(CASE WHEN bd.tbDiem IS NULL THEN 1 END) as soHSChuaCoDiem,
                    0 as soHSHKTot,
                    0 as soHSHKKha,
                    0 as soHSHKTB,
                    ROUND(AVG(bd.tbDiem), 2) as diemTBLop,
                    MAX(bd.tbDiem) as diemCaoNhat,
                    MIN(bd.tbDiem) as diemThapNhat
                FROM phancong_giangday pc
                JOIN lophoc lh ON pc.maLop = lh.maLop
                JOIN hocsinh hs ON hs.maLop = lh.maLop
                JOIN monhoc mh ON pc.maMonHoc = mh.maMonHoc
                LEFT JOIN bangdiem bd ON bd.maHS = hs.maHS 
                    AND bd.maMonHoc = pc.maMonHoc 
                    AND bd.hocKy = pc.hocKy 
                    AND bd.namHoc = pc.namHoc
                WHERE pc.maGV = ? AND pc.hocKy = ? AND pc.namHoc = ? AND pc.trangThai = 'active'";
        
        $params = [$maGV, $hocKy, $namHoc];
        
        if ($maLop) {
            $sql .= " AND pc.maLop = ?";
            $params[] = $maLop;
        }
        
        if ($maMonHoc) {
            $sql .= " AND pc.maMonHoc = ?";
            $params[] = $maMonHoc;
        }
        
        $sql .= " GROUP BY lh.maLop, lh.tenLop, mh.maMonHoc, mh.tenMonHoc, lh.siSo
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
    
    // Lấy danh sách môn học của giáo viên dựa vào phân công giảng dạy
    public function getDanhSachMonHocCuaGiaoVien($maGV) {
        $sql = "SELECT DISTINCT mh.maMonHoc, mh.tenMonHoc 
                FROM monhoc mh 
                JOIN phancong_giangday pc ON mh.maMonHoc = pc.maMonHoc 
                WHERE pc.maGV = ? AND pc.trangThai = 'active'
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