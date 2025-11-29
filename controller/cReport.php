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
            case 'download':
                $this->downloadFile();
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
    
    // Hiển thị danh sách báo cáo
    private function hienThiDanhSachBaoCao() {
        $maGV = $_SESSION['maGV'];
        $danhSachBaoCaoDaLuu = $this->model->getDanhSachBaoCao($maGV);
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
        $maLop = $_GET['maLop'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        $showResults = isset($_GET['submit']) && !empty($maMonHoc);
        
        $danhSachMonHoc = $this->model->getDanhSachMonHocCuaGiaoVien($maGV);
        $danhSachLop = $this->model->getDanhSachLopCuaGiaoVien($maGV);
        $duLieuBaoCao = null;
        $danhSachHocSinh = null;
        
        if ($showResults && $maMonHoc) {
            $duLieuBaoCao = $this->model->getThongKeDiemMonHoc($maGV, $maMonHoc, $hocKy, $namHoc, $maLop);
            $danhSachHocSinh = $this->model->getDanhSachHocSinhTheoMon($maGV, $maMonHoc, $hocKy, $namHoc, $maLop);
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
            $moTa = $_POST['moTa'] ?? '';
            $maGV = $_SESSION['maGV'];
            
            // Xử lý upload file
            if (isset($_FILES['fileBaoCao']) && $_FILES['fileBaoCao']['error'] === 0) {
                // Tạo đường dẫn tuyệt đối cho thư mục uploads
                $baseDir = dirname(dirname(__FILE__)); // Lấy thư mục gốc của project
                $uploadDir = $baseDir . '/uploads/reports/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $originalFileName = $_FILES['fileBaoCao']['name'];
                $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
                $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                // Kiểm tra loại file
                $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
                if (!in_array(strtolower($fileExtension), $allowedTypes)) {
                    header("Location: cReport.php?action=submit&error=invalid_file_type");
                    exit();
                }
                
                // Kiểm tra kích thước file (10MB)
                $maxFileSize = 10 * 1024 * 1024; // 10MB
                if ($_FILES['fileBaoCao']['size'] > $maxFileSize) {
                    header("Location: cReport.php?action=submit&error=file_too_large");
                    exit();
                }
                
                if (move_uploaded_file($_FILES['fileBaoCao']['tmp_name'], $uploadPath)) {
                    // Lưu thông tin báo cáo vào database
                    $result = $this->model->luuBaoCao($tenBaoCao, $loaiBaoCao, $originalFileName, $uploadPath, $moTa, $maGV);
                    
                    if ($result) {
                        header("Location: cReport.php?action=index&success=upload_success");
                    } else {
                        // Xóa file đã upload nếu lưu database thất bại
                        if (file_exists($uploadPath)) {
                            unlink($uploadPath);
                        }
                        header("Location: cReport.php?action=submit&error=save_failed");
                    }
                } else {
                    header("Location: cReport.php?action=submit&error=upload_failed");
                }
            } else {
                header("Location: cReport.php?action=submit&error=no_file");
            }
        }
    }
    
    // Download file báo cáo
    private function downloadFile() {
        if (!isset($_GET['id'])) {
            header("Location: cReport.php?action=index&error=invalid_file");
            exit();
        }
        
        $maBaoCao = (int)$_GET['id'];
        $maGV = $_SESSION['maGV'];
        
        // Lấy thông tin file từ database
        $baoCao = $this->model->getBaoCaoById($maBaoCao, $maGV);
        
        if (!$baoCao) {
            header("Location: cReport.php?action=index&error=file_not_found");
            exit();
        }
        
        $filePath = $baoCao['duongDan'];
        
        // Kiểm tra file có tồn tại không
        if (!file_exists($filePath)) {
            header("Location: cReport.php?action=index&error=file_not_exists");
            exit();
        }
        
        // Thiết lập header cho download
        $fileName = $baoCao['tenFile'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        // Xác định MIME type
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        $mimeType = $mimeTypes[strtolower($fileExtension)] ?? 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Đọc và xuất file
        readfile($filePath);
        exit();
    }
    
    // Xuất báo cáo ra Excel
    private function xuatExcel() {
        $loaiBaoCao = $_GET['type'] ?? '';
        $maGV = $_SESSION['maGV'];
        
        // Set headers for Excel download with proper encoding
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="bao_cao_' . $loaiBaoCao . '_' . date('Y-m-d_H-i-s') . '.xls"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; font-family: Arial, sans-serif; }';
        echo 'th, td { border: 1px solid #000; padding: 8px; text-align: center; }';
        echo 'th { background-color: #f0f0f0; font-weight: bold; }';
        echo 'h2, h3 { font-family: Arial, sans-serif; }';
        echo '</style>';
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
        $maLop = $_GET['maLop'] ?? null;
        $maMonHoc = $_GET['maMonHoc'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $duLieu = $this->model->getBaoCaoKetQuaHocTap($maGV, $maLop, $maMonHoc, $hocKy, $namHoc);
        
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
        $maLop = $_GET['maLop'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $duLieu = $this->model->getBaoCaoChuyenCan($maGV, $maLop, $hocKy, $namHoc);
        
        echo '<h2>BÁO CÁO CHUYÊN CẦN</h2>';
        echo '<table>';
        echo '<tr>';
        echo '<th>Họ tên</th>';
        echo '<th>Lớp</th>';
        echo '<th>Giới tính</th>';
        echo '<th>Nghỉ có phép</th>';
        echo '<th>Lý do</th>';
        echo '<th>Nghỉ không phép</th>';
        echo '<th>Tổng số nghỉ</th>';
        echo '<th>Xếp loại chuyên cần</th>';
        echo '</tr>';
        
        foreach ($duLieu as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['tenHocSinh']) . '</td>';
            echo '<td>' . htmlspecialchars($row['tenLop']) . '</td>';
            echo '<td>' . htmlspecialchars($row['gioiTinh']) . '</td>';
            echo '<td>' . $row['soNghiCoPhep'] . '</td>';
            echo '<td>' . (!empty($row['lyDoNghiCoPhep']) ? htmlspecialchars($row['lyDoNghiCoPhep']) : '-') . '</td>';
            echo '<td>' . $row['soNghiKhongPhep'] . '</td>';
            echo '<td>' . $row['tongSoNghi'] . '</td>';
            echo '<td>' . htmlspecialchars($row['xepLoaiChuyenCan']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    private function xuatExcelGiangDay($maGV) {
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $duLieu = $this->model->getBaoCaoGiangDay($maGV, $hocKy, $namHoc);
        
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
        $maMonHoc = $_GET['maMonHoc'] ?? null;
        $maLop = $_GET['maLop'] ?? null;
        $hocKy = $_GET['hocKy'] ?? null;
        $namHoc = $_GET['namHoc'] ?? '2024-2025';
        
        $danhSachMonHoc = $this->model->getDanhSachMonHocCuaGiaoVien($maGV);
        
        echo '<h2>THỐNG KÊ ĐIỂM MÔN HỌC</h2>';
        
        foreach ($danhSachMonHoc as $monHoc) {
            // Nếu có filter môn học, chỉ xuất môn đó
            if ($maMonHoc && $monHoc['maMonHoc'] != $maMonHoc) {
                continue;
            }
            
            $duLieu = $this->model->getThongKeDiemMonHoc($maGV, $monHoc['maMonHoc'], $hocKy, $namHoc, $maLop);
            
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
        $maLop = $_GET['maLop'] ?? null;
        
        $duLieu = $this->model->getThongKeSoLieuHocSinh($maGV, $maLop);
        
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