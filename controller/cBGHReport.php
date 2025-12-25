<?php
session_start();
require_once '../model/mBGHReport.php';

/**
 * Controller xử lý báo cáo cho Ban giám hiệu
 * BGH có quyền xem tất cả các báo cáo của trường
 */
class cBGHReport {
    private $model;
    
    public function __construct() {
        $this->model = new mBGHReport();
    }
    
    // Xử lý các yêu cầu báo cáo của BGH
    public function xuLyYeuCau() {
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            header("Location: ../public/index.php");
            exit();
        }
        
        // Kiểm tra quyền truy cập - Chỉ Ban giám hiệu
        if ($_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
            header("Location: ../public/index.php?error=access_denied");
            exit();
        }
        
        $action = $_GET['action'] ?? 'index';
        
        switch ($action) {
            case 'index':
                $this->hienThiDanhSachBaoCao();
                break;
            case 'hoc-tap':
                $this->xemBaoCaoKetQuaHocTap();
                break;
            case 'chuyen-can':
                $this->xemBaoCaoChuyenCan();
                break;
            case 'chi-tiet-nghi-hoc':
                $this->xemChiTietNghiHoc();
                break;
            case 'giang-day':
                $this->xemBaoCaoGiangDay();
                break;
            case 'tong-hop':
                $this->xemBaoCaoTongHop();
                break;
            case 'danh-gia':
                $this->xemBaoCaoKetQuaDanhGia();
                break;
            case 'thong-ke-diem':
                $this->xemThongKeDiemMonHoc();
                break;
            case 'thong-ke-hoc-sinh':
                $this->xemThongKeSoLieuHocSinh();
                break;
            case 'bao-cao-da-nop':
                $this->xemBaoCaoDaNop();
                break;
            case 'ket-qua-danh-gia':
                $this->xemBaoCaoDanhHieuHocSinh();
                break;
            case 'xuat-excel':
                $this->xuatExcel();
                break;
            case 'xuat-pdf':
                $this->xuatPDF();
                break;
            default:
                $this->hienThiDanhSachBaoCao();
                break;
        }
    }
    
    // Hiển thị danh sách các loại báo cáo
    private function hienThiDanhSachBaoCao() {
        include '../view/bgh/vBGHReportList.php';
    }
    
    // Xem báo cáo kết quả học tập
    private function xemBaoCaoKetQuaHocTap() {
        $maLop = $_GET['maLop'] ?? null;
        $maMonHoc = $_GET['maMonHoc'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $danhSachLop = $this->model->getDanhSachTatCaLop();
        $danhSachMonHoc = $this->model->getDanhSachTatCaMonHoc();
        $duLieuBaoCao = $this->model->getBaoCaoKetQuaHocTap($maLop, $maMonHoc, $hocKy, $namHoc);
        
        include '../view/bgh/vBGHAcademicReport.php';
    }
    
    // Xem báo cáo chuyên cần
    private function xemBaoCaoChuyenCan() {
        $maLop = $_GET['maLop'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $danhSachLop = $this->model->getDanhSachTatCaLop();
        $duLieuBaoCao = $this->model->getBaoCaoChuyenCan($maLop, $hocKy, $namHoc);
        
        include '../view/bgh/vBGHAttendanceReport.php';
    }
    
    // Xem chi tiết nghỉ học của học sinh (AJAX)
    private function xemChiTietNghiHoc() {
        $maHS = $_GET['maHS'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        if ($maHS) {
            $chiTietNghi = $this->model->getChiTietNghiHocCuaHocSinh($maHS, $hocKy, $namHoc);
            
            if (empty($chiTietNghi)) {
                echo '<p style="text-align: center; color: #6b7280; padding: 20px;">Học sinh không có ngày nghỉ nào.</p>';
            } else {
                echo '<table class="detail-table">';
                echo '<thead><tr><th>Ngày nghỉ</th><th>Loại nghỉ</th><th>Lý do</th><th>Người duyệt</th></tr></thead>';
                echo '<tbody>';
                foreach ($chiTietNghi as $row) {
                    $loaiClass = $row['loaiNghi'] == 'cophep' ? 'type-cophep' : 'type-khongphep';
                    $loaiText = $row['loaiNghi'] == 'cophep' ? 'Có phép' : 'Không phép';
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['ngayNghiFormat']) . '</td>';
                    echo '<td><span class="type-badge ' . $loaiClass . '">' . $loaiText . '</span></td>';
                    echo '<td style="text-align: left;">' . htmlspecialchars($row['lyDo'] ?? 'Không có lý do') . '</td>';
                    echo '<td>' . htmlspecialchars($row['nguoiDuyet'] ?? '-') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
        } else {
            echo '<p style="text-align: center; color: red;">Thiếu thông tin học sinh!</p>';
        }
        exit;
    }
    
    // Xem báo cáo giảng dạy
    private function xemBaoCaoGiangDay() {
        $maGV = $_GET['maGV'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $danhSachGiaoVien = $this->model->getDanhSachTatCaGiaoVien();
        $duLieuBaoCao = $this->model->getBaoCaoGiangDay($maGV, $hocKy, $namHoc);
        
        include '../view/bgh/vBGHTeachingReport.php';
    }
    
    // Xem báo cáo tổng hợp
    private function xemBaoCaoTongHop() {
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $duLieuBaoCao = $this->model->getBaoCaoTongHop($hocKy, $namHoc);
        $tongGiaoVien = $this->model->getTongSoGiaoVien();
        $tongMonHoc = $this->model->getTongSoMonHoc();
        $thongKeGioiTinh = $this->model->getThongKeHocSinhTheoGioiTinh($namHoc);
        
        include '../view/bgh/vBGHSummaryReport.php';
    }
    
    // Xem báo cáo kết quả đánh giá
    private function xemBaoCaoKetQuaDanhGia() {
        $maLop = $_GET['maLop'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $danhSachLop = $this->model->getDanhSachTatCaLop();
        $duLieuBaoCao = $this->model->getBaoCaoKetQuaDanhGia($maLop, $hocKy, $namHoc);
        
        include '../view/bgh/vBGHEvaluationReport.php';
    }
    
    // Xem thống kê điểm môn học
    private function xemThongKeDiemMonHoc() {
        $maMonHoc = $_GET['maMonHoc'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $danhSachMonHoc = $this->model->getDanhSachTatCaMonHoc();
        $duLieuThongKe = $this->model->getThongKeDiemMonHoc($maMonHoc, $hocKy, $namHoc);
        
        include '../view/bgh/vBGHGradeStatistics.php';
    }
    
    // Xem thống kê số liệu học sinh
    private function xemThongKeSoLieuHocSinh() {
        $maLop = $_GET['maLop'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $danhSachLop = $this->model->getDanhSachTatCaLop();
        $duLieuThongKe = $this->model->getThongKeSoLieuHocSinh($maLop, $hocKy, $namHoc);
        
        include '../view/bgh/vBGHStudentStatistics.php';
    }
    
    // Xem báo cáo đã nộp từ giáo viên
    private function xemBaoCaoDaNop() {
        $maGV = $_GET['maGV'] ?? null;
        $loaiBaoCao = $_GET['loaiBaoCao'] ?? null;
        $tuNgay = $_GET['tuNgay'] ?? null;
        $denNgay = $_GET['denNgay'] ?? null;
        
        $danhSachGiaoVien = $this->model->getDanhSachTatCaGiaoVien();
        $duLieuBaoCao = $this->model->getBaoCaoDaNop($maGV, $loaiBaoCao, $tuNgay, $denNgay);
        
        include '../view/bgh/vBGHSubmittedReports.php';
    }
    
    // Xuất báo cáo ra Excel
    private function xuatExcel() {
        $loaiBaoCao = $_GET['loai'] ?? '';
        $maLop = $_GET['maLop'] ?? null;
        $maMonHoc = $_GET['maMonHoc'] ?? null;
        $maGV = $_GET['maGV'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        // Lấy dữ liệu báo cáo dựa theo loại
        $duLieuBaoCao = [];
        $tenFile = '';
        
        switch ($loaiBaoCao) {
            case 'hoc-tap':
                $duLieuBaoCao = $this->model->getBaoCaoKetQuaHocTap($maLop, $maMonHoc, $hocKy, $namHoc);
                $tenFile = 'BaoCaoKetQuaHocTap_' . date('YmdHis') . '.xls';
                $tieuDe = 'BÁO CÁO KẾT QUẢ HỌC TẬP';
                $headers = ['Mã HS', 'Họ tên', 'Lớp', 'Khối', 'Môn học', 'Học kỳ', 'Năm học', 'Điểm miệng', 'Điểm 15p', 'Điểm 1 tiết', 'Điểm giữa kỳ', 'Điểm cuối kỳ', 'Điểm TB', 'Giáo viên'];
                break;
            case 'chuyen-can':
                $duLieuBaoCao = $this->model->getBaoCaoChuyenCan($maLop, $hocKy, $namHoc);
                $tenFile = 'BaoCaoChuyenCan_' . date('YmdHis') . '.xls';
                $tieuDe = 'BÁO CÁO CHUYÊN CẦN';
                $headers = ['Mã HS', 'Họ tên', 'Lớp', 'Khối', 'Học kỳ', 'Năm học', 'Số ngày nghỉ', 'Có phép', 'Không phép', 'Ghi chú', 'GVCN'];
                break;
            case 'giang-day':
                $duLieuBaoCao = $this->model->getBaoCaoGiangDay($maGV, $hocKy, $namHoc);
                $tenFile = 'BaoCaoGiangDay_' . date('YmdHis') . '.xls';
                $tieuDe = 'BÁO CÁO TIẾN ĐỘ GIẢNG DẠY';
                $headers = ['Mã GV', 'Họ tên GV', 'Tổ bộ môn', 'Lớp', 'Môn học', 'Học kỳ', 'Năm học', 'Số tiết KH', 'Số tiết đã dạy', 'Số tiết còn lại', 'Tỷ lệ hoàn thành (%)', 'Sĩ số'];
                break;
            case 'tong-hop':
                $duLieuBaoCao = $this->model->getBaoCaoTongHop($hocKy, $namHoc);
                $tenFile = 'BaoCaoTongHop_' . date('YmdHis') . '.xls';
                $tieuDe = 'BÁO CÁO TỔNG HỢP';
                $headers = ['Lớp', 'Khối', 'Sĩ số', 'Số HS thực tế', 'Học kỳ', 'Năm học', 'Điểm TB lớp', 'HS Giỏi', 'HS Khá', 'HS TB', 'HS Yếu', 'GVCN'];
                break;
            case 'danh-gia':
                $duLieuBaoCao = $this->model->getBaoCaoKetQuaDanhGia($maLop, $hocKy, $namHoc);
                $tenFile = 'BaoCaoKetQuaDanhGia_' . date('YmdHis') . '.xls';
                $tieuDe = 'BÁO CÁO KẾT QUẢ ĐÁNH GIÁ';
                $headers = ['Mã HS', 'Họ tên', 'Lớp', 'Khối', 'Điểm TB chung', 'Ngày nghỉ', 'Có phép', 'Không phép', 'Xếp loại', 'Học kỳ', 'Năm học'];
                break;
            case 'thong-ke-diem':
                $duLieuBaoCao = $this->model->getThongKeDiemMonHoc($maMonHoc, $hocKy, $namHoc);
                $tenFile = 'ThongKeDiemMonHoc_' . date('YmdHis') . '.xls';
                $tieuDe = 'THỐNG KÊ ĐIỂM MÔN HỌC';
                $headers = ['Môn học', 'Học kỳ', 'Năm học', 'Tổng số HS', 'Điểm TB', 'Điểm cao nhất', 'Điểm thấp nhất', 'HS Giỏi', 'HS Khá', 'HS TB', 'HS Yếu'];
                break;
            case 'thong-ke-hoc-sinh':
                $duLieuBaoCao = $this->model->getThongKeSoLieuHocSinh($maLop, $hocKy, $namHoc);
                $tenFile = 'ThongKeSoLieuHocSinh_' . date('YmdHis') . '.xls';
                $tieuDe = 'THỐNG KÊ SỐ LIỆU HỌC SINH';
                $headers = ['Lớp', 'Khối', 'Sĩ số', 'Số HS thực tế', 'HS Nam', 'HS Nữ', 'TB ngày nghỉ', 'GVCN'];
                break;
            default:
                header("Location: cBGHReport.php?action=index&error=invalid_report_type");
                exit();
        }
        
        // Xuất Excel
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=\"$tenFile\"");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        
        echo "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'></head><body>";
        echo "<table border='1'>";
        echo "<tr><th colspan='" . count($headers) . "' style='text-align:center; font-size:16px; font-weight:bold;'>$tieuDe</th></tr>";
        echo "<tr>";
        foreach ($headers as $header) {
            echo "<th style='background-color:#4CAF50; color:white; font-weight:bold;'>$header</th>";
        }
        echo "</tr>";
        
        foreach ($duLieuBaoCao as $row) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars($cell ?? '') . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table></body></html>";
        exit();
    }
    
    // ========== BÁO CÁO KẾT QUẢ ĐÁNH GIÁ HỌC SINH ==========
    
    /**
     * Xem báo cáo kết quả đánh giá danh hiệu học sinh
     */
    private function xemBaoCaoDanhHieuHocSinh() {
        $namHoc = $_POST['namHoc'] ?? '2024-2025';
        $maKhoi = !empty($_POST['maKhoi']) ? intval($_POST['maKhoi']) : null;
        $maLop = !empty($_POST['maLop']) ? intval($_POST['maLop']) : null;
        
        $danhSachKhoi = $this->model->layDanhSachKhoi();
        $danhSachLop = $this->model->layDanhSachLop($maKhoi);
        $ketQua = $this->model->layKetQuaHocSinh($namHoc, $maLop, $maKhoi);
        $thongKe = $this->model->thongKeDanhHieu($namHoc, $maLop, $maKhoi);
        $danhSachDanhHieu = $this->model->layDanhSachDanhHieu();
        
        $_SESSION['dataKetQuaDanhGia'] = [
            'ketQua' => $ketQua,
            'danhSachKhoi' => $danhSachKhoi,
            'danhSachLop' => $danhSachLop,
            'thongKe' => $thongKe,
            'namHoc' => $namHoc,
            'maKhoi' => $maKhoi,
            'maLop' => $maLop,
            'danhSachDanhHieu' => $danhSachDanhHieu
        ];
        
        require_once '../view/bgh/vBGHHonorTitleReport.php';
    }
    
    // Xuất báo cáo ra PDF
    private function xuatPDF() {
        // TODO: Implement PDF export using library like TCPDF or FPDF
        header("Location: cBGHReport.php?action=index&info=pdf_not_implemented");
        exit();
    }
}

// Khởi tạo controller và xử lý yêu cầu
$controller = new cBGHReport();
$controller->xuLyYeuCau();
?>
