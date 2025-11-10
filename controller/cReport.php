<?php
session_start();
require_once '../model/mReport.php';

class cReport {
    private $model;
    
    public function __construct() {
        $this->model = new mReport();
    }
    
    // Xử lý các yêu cầu báo cáo
    public function xuLyYeuCau() {
        if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            header("Location: ../public/index.php");
            exit();
        }
        
        if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
            header("Location: ../public/index.php?error=access_denied");
            exit();
        }
        
        $action = $_GET['action'] ?? 'index';
        $maGV = $_SESSION['maGV'];
        
        switch ($action) {
            case 'index':
                $this->hienThiDanhSachBaoCao();
                break;
            case 'academic':
            case 'hoc-tap':
                $this->xemBaoCaoKetQuaHocTap();
                break;
            case 'attendance':
            case 'chuyen-can':
                $this->xemBaoCaoChuyenCan();
                break;
            case 'teaching':
            case 'giang-day':
                $this->xemBaoCaoGiangDay();
                break;
            case 'grade_stats':
            case 'thong-ke-diem':
                $this->xemThongKeDiemMonHoc();
                break;
            case 'student_stats':
            case 'thong-ke-hoc-sinh':
                $this->xemThongKeSoLieuHocSinh();
                break;
            case 'submit':
            case 'nop-bao-cao':
                $this->nopBaoCao();
                break;
            case 'xu-ly-upload':
                $this->xuLyUploadBaoCao();
                break;
            case 'xuat-excel':
                $this->xuatExcel();
                break;
            case 'xuat-pdf':
                // PDF export functionality - can be implemented later
                header("Location: cReport.php?action=index&info=pdf_not_implemented");
                break;
            default:
                $this->hienThiDanhSachBaoCao();
                break;
        }
    }
    
    // Hiển thị danh sách các loại báo cáo
    private function hienThiDanhSachBaoCao() {
        $danhSachBaoCaoDaLuu = $this->model->getDanhSachBaoCao();
        include '../view/teacher/vReportList.php';
    }
    
    // Xem báo cáo kết quả học tập
    private function xemBaoCaoKetQuaHocTap() {
        $maGV = $_SESSION['maGV'];
        $maLop = $_GET['maLop'] ?? null;
        $maMonHoc = $_GET['maMonHoc'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $danhSachLop = $this->model->getDanhSachLopCuaGiaoVien($maGV);
        $danhSachMonHoc = $this->model->getDanhSachMonHocCuaGiaoVien($maGV);
        $duLieuBaoCao = $this->model->getBaoCaoKetQuaHocTap($maGV, $maLop, $maMonHoc, $hocKy, $namHoc);
        
        include '../view/teacher/vAcademicReport.php';
    }
    
    // Xem báo cáo chuyên cần
    private function xemBaoCaoChuyenCan() {
        $maGV = $_SESSION['maGV'];
        $maLop = $_GET['maLop'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $danhSachLop = $this->model->getDanhSachLopCuaGiaoVien($maGV);
        $duLieuBaoCao = $this->model->getBaoCaoChuyenCan($maGV, $maLop, $hocKy, $namHoc);
        
        include '../view/teacher/vAttendanceReport.php';
    }
    
    // Xem báo cáo giảng dạy
    private function xemBaoCaoGiangDay() {
        $maGV = $_SESSION['maGV'];
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $duLieuBaoCao = $this->model->getBaoCaoGiangDay($maGV, $hocKy, $namHoc);
        
        include '../view/teacher/vTeachingReport.php';
    }
    
    // Xem thống kê điểm môn học
    private function xemThongKeDiemMonHoc() {
        $maGV = $_SESSION['maGV'];
        $maMonHoc = $_GET['maMonHoc'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $danhSachMonHoc = $this->model->getDanhSachMonHocCuaGiaoVien($maGV);
        $duLieuBaoCao = null;
        
        if ($maMonHoc) {
            $duLieuBaoCao = $this->model->getThongKeDiemMonHoc($maGV, $maMonHoc, $hocKy, $namHoc);
        }
        
        include '../view/teacher/vGradeStatistics.php';
    }
    
    // Xem thống kê số liệu học sinh
    private function xemThongKeSoLieuHocSinh() {
        $maGV = $_SESSION['maGV'];
        $maLop = $_GET['maLop'] ?? null;
        
        $danhSachLop = $this->model->getDanhSachLopCuaGiaoVien($maGV);
        $duLieuBaoCao = $this->model->getThongKeSoLieuHocSinh($maGV, $maLop);
        
        include '../view/teacher/vStudentStatistics.php';
    }
    
    // Nộp báo cáo
    private function nopBaoCao() {
        include '../view/teacher/vSubmitReport.php';
    }
    
    // Xử lý upload báo cáo
    private function xuLyUploadBaoCao() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tenBaoCao = $_POST['tenBaoCao'] ?? '';
            $loaiBaoCao = $_POST['loaiBaoCao'] ?? '';
            $hocKy = $_POST['hocKy'] ?? 1;
            $namHoc = $_POST['namHoc'] ?? '2024-2025';
            $maLop = $_POST['maLop'] ?? null;
            $moTa = $_POST['moTa'] ?? '';
            
            // Xử lý upload file
            if (isset($_FILES['fileBaoCao']) && $_FILES['fileBaoCao']['error'] === 0) {
                $uploadDir = '../uploads/reports/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . $_FILES['fileBaoCao']['name'];
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['fileBaoCao']['tmp_name'], $uploadPath)) {
                    $noiDung = json_encode([
                        'file_name' => $fileName,
                        'file_path' => $uploadPath,
                        'upload_time' => date('Y-m-d H:i:s'),
                        'mo_ta' => $moTa
                    ]);
                    
                    $result = $this->model->luuBaoCao($tenBaoCao, $loaiBaoCao, $noiDung, $hocKy, $namHoc, $maLop, null);
                    
                    if ($result) {
                        header("Location: cReport.php?action=index&success=upload_success");
                    } else {
                        header("Location: cReport.php?action=nop-bao-cao&error=save_failed");
                    }
                } else {
                    header("Location: cReport.php?action=nop-bao-cao&error=upload_failed");
                }
            } else {
                header("Location: cReport.php?action=nop-bao-cao&error=no_file");
            }
        }
    }
    
    // Xuất báo cáo ra Excel
    private function xuatExcel() {
        $loaiBaoCao = $_GET['type'] ?? '';
        $maGV = $_SESSION['maGV'];
        
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="bao_cao_' . $loaiBaoCao . '_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8">';
        echo '<style>table { border-collapse: collapse; } th, td { border: 1px solid black; padding: 5px; }</style>';
        echo '</head><body>';
        
        switch ($loaiBaoCao) {
            case 'hoc-tap':
                $this->xuatExcelKetQuaHocTap($maGV);
                break;
            case 'chuyen-can':
                $this->xuatExcelChuyenCan($maGV);
                break;
            case 'giang-day':
                $this->xuatExcelGiangDay($maGV);
                break;
            case 'thong-ke-diem':
                $this->xuatExcelThongKeDiem($maGV);
                break;
            case 'thong-ke-hoc-sinh':
                $this->xuatExcelThongKeHocSinh($maGV);
                break;
        }
        
        echo '</body></html>';
        exit();
    }
    
    private function xuatExcelKetQuaHocTap($maGV) {
        $duLieu = $this->model->getBaoCaoKetQuaHocTap($maGV);
        
        echo '<h2>BÁO CÁO KẾT QUẢ HỌC TẬP</h2>';
        echo '<table>';
        echo '<tr>';
        echo '<th>Họ tên</th>';
        echo '<th>Lớp</th>';
        echo '<th>Môn học</th>';
        echo '<th>Học kỳ</th>';
        echo '<th>Năm học</th>';
        echo '<th>Điểm miệng</th>';
        echo '<th>Điểm 15 phút</th>';
        echo '<th>Điểm 1 tiết</th>';
        echo '<th>Điểm giữa kỳ</th>';
        echo '<th>Điểm cuối kỳ</th>';
        echo '<th>Điểm TB</th>';
        echo '</tr>';
        
        foreach ($duLieu as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['tenHocSinh']) . '</td>';
            echo '<td>' . htmlspecialchars($row['tenLop']) . '</td>';
            echo '<td>' . htmlspecialchars($row['tenMonHoc']) . '</td>';
            echo '<td>' . $row['hocKy'] . '</td>';
            echo '<td>' . htmlspecialchars($row['namHoc']) . '</td>';
            echo '<td>' . number_format($row['diemMieng'], 1) . '</td>';
            echo '<td>' . number_format($row['diem15phut'], 1) . '</td>';
            echo '<td>' . number_format($row['diem1tiet'], 1) . '</td>';
            echo '<td>' . number_format($row['diemGiuaKy'], 1) . '</td>';
            echo '<td>' . number_format($row['diemCuoiKy'], 1) . '</td>';
            echo '<td>' . number_format($row['diemTrungBinh'], 2) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    private function xuatExcelChuyenCan($maGV) {
        $duLieu = $this->model->getBaoCaoChuyenCan($maGV);
        
        echo '<h2>BÁO CÁO CHUYÊN CẦN</h2>';
        echo '<table>';
        echo '<tr>';
        echo '<th>Họ tên</th>';
        echo '<th>Lớp</th>';
        echo '<th>Nghỉ có phép</th>';
        echo '<th>Nghỉ không phép</th>';
        echo '<th>Tổng số nghỉ</th>';
        echo '<th>Hạnh kiểm</th>';
        echo '</tr>';
        
        foreach ($duLieu as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['tenHocSinh']) . '</td>';
            echo '<td>' . htmlspecialchars($row['tenLop']) . '</td>';
            echo '<td>' . $row['soNghiCoPhep'] . '</td>';
            echo '<td>' . $row['soNghiKhongPhep'] . '</td>';
            echo '<td>' . $row['tongSoNghi'] . '</td>';
            echo '<td>' . htmlspecialchars($row['tenHanhKiem']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    private function xuatExcelGiangDay($maGV) {
        $duLieu = $this->model->getBaoCaoGiangDay($maGV);
        
        echo '<h2>BÁO CÁO GIẢNG DẠY</h2>';
        echo '<table>';
        echo '<tr>';
        echo '<th>Lớp</th>';
        echo '<th>Môn học</th>';
        echo '<th>Phòng</th>';
        echo '<th>Thứ</th>';
        echo '<th>Tiết</th>';
        echo '<th>Học kỳ</th>';
        echo '<th>Năm học</th>';
        echo '<th>Bài tập đã giao</th>';
        echo '<th>Bài tập hoàn thành</th>';
        echo '</tr>';
        
        foreach ($duLieu as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['tenLop']) . '</td>';
            echo '<td>' . htmlspecialchars($row['tenMonHoc']) . '</td>';
            echo '<td>' . htmlspecialchars($row['tenPhong']) . '</td>';
            echo '<td>' . $row['thu'] . '</td>';
            echo '<td>' . $row['tietBatDau'] . '-' . $row['tietKetThuc'] . '</td>';
            echo '<td>' . $row['hocKy'] . '</td>';
            echo '<td>' . htmlspecialchars($row['namHoc']) . '</td>';
            echo '<td>' . $row['soBaiTapDaGiao'] . '</td>';
            echo '<td>' . $row['soBaiTapHoanThanh'] . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    private function xuatExcelThongKeDiem($maGV) {
        $danhSachMonHoc = $this->model->getDanhSachMonHocCuaGiaoVien($maGV);
        
        echo '<h2>THỐNG KÊ ĐIỂM MÔN HỌC</h2>';
        
        foreach ($danhSachMonHoc as $monHoc) {
            $duLieu = $this->model->getThongKeDiemMonHoc($maGV, $monHoc['maMonHoc']);
            
            if (!empty($duLieu)) {
                echo '<h3>Môn: ' . htmlspecialchars($monHoc['tenMonHoc']) . '</h3>';
                echo '<table>';
                echo '<tr>';
                echo '<th>Học kỳ</th>';
                echo '<th>Năm học</th>';
                echo '<th>Số học sinh</th>';
                echo '<th>Điểm TB</th>';
                echo '<th>Điểm cao nhất</th>';
                echo '<th>Điểm thấp nhất</th>';
                echo '<th>Số HS Giỏi</th>';
                echo '<th>Số HS Khá</th>';
                echo '<th>Số HS TB</th>';
                echo '<th>Số HS Yếu</th>';
                echo '</tr>';
                
                foreach ($duLieu as $row) {
                    echo '<tr>';
                    echo '<td>' . $row['hocKy'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['namHoc']) . '</td>';
                    echo '<td>' . $row['soHocSinh'] . '</td>';
                    echo '<td>' . number_format($row['diemTrungBinh'], 2) . '</td>';
                    echo '<td>' . number_format($row['diemCaoNhat'], 2) . '</td>';
                    echo '<td>' . number_format($row['diemThapNhat'], 2) . '</td>';
                    echo '<td>' . $row['soHSGioi'] . '</td>';
                    echo '<td>' . $row['soHSKha'] . '</td>';
                    echo '<td>' . $row['soHSTB'] . '</td>';
                    echo '<td>' . $row['soHSYeu'] . '</td>';
                    echo '</tr>';
                }
                
                echo '</table><br>';
            }
        }
    }
    
    private function xuatExcelThongKeHocSinh($maGV) {
        $duLieu = $this->model->getThongKeSoLieuHocSinh($maGV);
        
        echo '<h2>THỐNG KÊ SỐ LIỆU HỌC SINH</h2>';
        echo '<table>';
        echo '<tr>';
        echo '<th>Lớp</th>';
        echo '<th>Sĩ số</th>';
        echo '<th>HS Nam</th>';
        echo '<th>HS Nữ</th>';
        echo '<th>HS Giỏi</th>';
        echo '<th>HS Khá</th>';
        echo '<th>HS TB</th>';
        echo '<th>HS Yếu</th>';
        echo '<th>HK Tốt</th>';
        echo '<th>HK Khá</th>';
        echo '<th>HK TB</th>';
        echo '</tr>';
        
        foreach ($duLieu as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['tenLop']) . '</td>';
            echo '<td>' . $row['siSo'] . '</td>';
            echo '<td>' . $row['soHSNam'] . '</td>';
            echo '<td>' . $row['soHSNu'] . '</td>';
            echo '<td>' . $row['soHSGioi'] . '</td>';
            echo '<td>' . $row['soHSKha'] . '</td>';
            echo '<td>' . $row['soHSTB'] . '</td>';
            echo '<td>' . $row['soHSYeu'] . '</td>';
            echo '<td>' . $row['soHSHKTot'] . '</td>';
            echo '<td>' . $row['soHSHKKha'] . '</td>';
            echo '<td>' . $row['soHSHKTB'] . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
}

// Khởi tạo và xử lý controller
$controller = new cReport();
$controller->xuLyYeuCau();
?>