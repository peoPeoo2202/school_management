<?php
session_start();
require_once '../model/mChonDe.php';

/**
 * Controller xử lý chức năng chọn đề thi của Ban giám hiệu
 */
class cChonDe {
    private $model;
    
    public function __construct() {
        $this->model = new mChonDe();
    }
    
    /**
     * Xử lý các yêu cầu chọn đề
     */
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
                $this->hienThiTrangChinh();
                break;
            case 'danh-sach-ky-thi':
                $this->layDanhSachKyThi();
                break;
            case 'danh-sach-de-thi':
                $this->layDanhSachDeThi();
                break;
            case 'chi-tiet-de-thi':
                $this->xemChiTietDeThi();
                break;
            case 'chon-de-thi':
                $this->chonDeThi();
                break;
            case 'bo-chon-de-thi':
                $this->boChonDeThi();
                break;
            case 'de-thi-da-chon':
                $this->layDeThiDaChon();
                break;
            case 'cap-nhat-khoi-de-thi':
                $this->capNhatKhoiDeThi();
                break;
            case 'de-thi-theo-khoi':
                $this->layDeThiTheoKhoi();
                break;
            case 'xem-file-de-thi':
                $this->xemFileDeThi();
                break;
            case 'tai-file-de-thi':
                $this->taiFileDeThi();
                break;
            default:
                $this->hienThiTrangChinh();
                break;
        }
    }
    
    /**
     * Hiển thị trang chính chọn đề
     */
    public function hienThiTrangChinh() {
        try {
            // Lấy danh sách khối
            $danhSachKhoi = $this->model->layDanhSachKhoi();
            
            // Lấy danh sách kỳ thi có thể chọn đề
            $danhSachKyThi = $this->model->layDanhSachKyThi();
            
            // Truyền dữ liệu cho view
            $data = [
                'danhSachKhoi' => $danhSachKhoi,
                'danhSachKyThi' => $danhSachKyThi,
                'title' => 'Chọn đề thi',
                'message' => $_GET['message'] ?? '',
                'error' => $_GET['error'] ?? ''
            ];
            
            include '../view/bgh/vChonDe.php';
            
        } catch (Exception $e) {
            header("Location: ?error=" . urlencode("Lỗi: " . $e->getMessage()));
        }
    }
    
    /**
     * Lấy danh sách kỳ thi theo khối (AJAX)
     */
    public function layDanhSachKyThi() {
        try {
            $maKhoi = $_GET['maKhoi'] ?? null;
            
            $danhSachKyThi = $this->model->layDanhSachKyThi($maKhoi);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $danhSachKyThi
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    /**
     * Lấy danh sách đề thi theo kỳ thi (AJAX)
     */
    public function layDanhSachDeThi() {
        try {
            $maKyThi = $_GET['maKyThi'] ?? null;
            
            if (!$maKyThi) {
                throw new Exception("Vui lòng chọn kỳ thi!");
            }
            
            $danhSachDeThi = $this->model->layDanhSachDeThi($maKyThi);
            $thongTinKyThi = $this->model->layThongTinKyThi($maKyThi);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $danhSachDeThi,
                'kyThi' => $thongTinKyThi
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    /**
     * Xem chi tiết đề thi
     */
    public function xemChiTietDeThi() {
        try {
            $maDeThi = $_GET['maDeThi'] ?? null;
            $maKyThi = $_GET['maKyThi'] ?? null;
            
            if (!$maDeThi) {
                throw new Exception("Không tìm thấy đề thi!");
            }
            
            $chiTietDeThi = $this->model->layChiTietDeThi($maDeThi);
            $thongTinKyThi = null;
            
            if ($maKyThi) {
                $thongTinKyThi = $this->model->layThongTinKyThi($maKyThi);
            }
            
            if (!$chiTietDeThi) {
                throw new Exception("Không tìm thấy thông tin đề thi!");
            }
            
            // Truyền dữ liệu cho view
            $data = [
                'deThi' => $chiTietDeThi,
                'kyThi' => $thongTinKyThi,
                'maKyThi' => $maKyThi,
                'title' => 'Chi tiết đề thi'
            ];
            
            include '../view/bgh/vChiTietDeThi.php';
            
        } catch (Exception $e) {
            header("Location: ?error=" . urlencode("Lỗi: " . $e->getMessage()));
        }
    }
    
    /**
     * Chọn đề thi cho kỳ thi
     */
    public function chonDeThi() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Phương thức không hợp lệ!");
            }
            
            $maKyThi = $_POST['maKyThi'] ?? null;
            $maDeThi = $_POST['maDeThi'] ?? null;
            $maBGH = $_SESSION['maBGH'] ?? null;
            // Fallback: tra cứu maBGH từ bảng bgh theo maTaiKhoan nếu thiếu trong session
            if (!$maBGH && isset($_SESSION['maTaiKhoan'])) {
                try {
                    $conn = (new mConnect())->mConnect();
                    $stmt = mysqli_prepare($conn, "SELECT maBGH FROM bgh WHERE maTaiKhoan = ?");
                    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['maTaiKhoan']);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    if ($res) {
                        $row = mysqli_fetch_assoc($res);
                        if ($row && isset($row['maBGH'])) {
                            $maBGH = $row['maBGH'];
                            $_SESSION['maBGH'] = $maBGH; // cache lại
                        }
                    }
                    mysqli_stmt_close($stmt);
                } catch (Exception $e) {
                    // ignore, will error out below if still missing
                }
            }
            
            if (!$maKyThi || !$maDeThi) {
                throw new Exception("Thông tin không đầy đủ!");
            }
            
            if (!$maBGH) {
                throw new Exception("Không xác định được thông tin Ban giám hiệu!");
            }
            
            // Thực hiện chọn đề thi
            $this->model->chonDeThi($maKyThi, $maDeThi, $maBGH);
            
            // Chuyển hướng với thông báo thành công
            header("Location: ?action=index&message=" . urlencode("Đã chọn đề thi thành công!"));
            
        } catch (Exception $e) {
            header("Location: ?action=index&error=" . urlencode("Lỗi: " . $e->getMessage()));
        }
        exit();
    }
    
    /**
     * Bỏ chọn đề thi
     */
    public function boChonDeThi() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Phương thức không hợp lệ!");
            }
            
            $maKyThi = $_POST['maKyThi'] ?? null;
            $maDeThi = $_POST['maDeThi'] ?? null;
            
            if (!$maKyThi || !$maDeThi) {
                throw new Exception("Thông tin không đầy đủ!");
            }
            
            // Thực hiện bỏ chọn đề thi
            $this->model->boChonDeThi($maKyThi, $maDeThi);
            
            // Chuyển hướng với thông báo thành công
            header("Location: ?action=index&message=" . urlencode("Đã bỏ chọn đề thi thành công!"));
            
        } catch (Exception $e) {
            header("Location: ?action=index&error=" . urlencode("Lỗi: " . $e->getMessage()));
        }
        exit();
    }
    
    /**
     * Xem danh sách đề thi đã chọn
     */
    public function xemDeThiDaChon() {
        try {
            $maKyThi = $_GET['maKyThi'] ?? null;
            
            if (!$maKyThi) {
                throw new Exception("Vui lòng chọn kỳ thi!");
            }
            
            $deThiDaChon = $this->model->layDeThiDaChon($maKyThi);
            $thongTinKyThi = $this->model->layThongTinKyThi($maKyThi);
            
            // Truyền dữ liệu cho view
            $data = [
                'deThiDaChon' => $deThiDaChon,
                'kyThi' => $thongTinKyThi,
                'title' => 'Đề thi đã chọn'
            ];
            
            include '../view/bgh/vDeThiDaChon.php';
            
        } catch (Exception $e) {
            header("Location: ?error=" . urlencode("Lỗi: " . $e->getMessage()));
        }
    }
    
    /**
     * Lấy danh sách đề thi đã chọn (AJAX)
     */
    public function layDeThiDaChon() {
        header('Content-Type: application/json');
        
        try {
            $maKyThi = $_GET['maKyThi'] ?? null;
            
            if (!$maKyThi) {
                throw new Exception("Thiếu thông tin kỳ thi!");
            }
            
            $deThiDaChon = $this->model->layDeThiDaChon($maKyThi);
            
            echo json_encode([
                'success' => true,
                'data' => $deThiDaChon
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    /**
     * Cập nhật khối cho đề thi
     */
    public function capNhatKhoiDeThi() {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Phương thức không được hỗ trợ!");
            }
            
            $maDeThi = $_POST['maDeThi'] ?? null;
            $maKhoi = $_POST['maKhoi'] ?? null;
            
            if (!$maDeThi || !$maKhoi) {
                throw new Exception("Thiếu thông tin đề thi hoặc khối!");
            }
            
            $result = $this->model->capNhatKhoiDeThi($maDeThi, $maKhoi);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cập nhật khối cho đề thi thành công!'
                ]);
            } else {
                throw new Exception("Không thể cập nhật khối cho đề thi!");
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    /**
     * Lấy danh sách đề thi theo khối
     */
    public function layDeThiTheoKhoi() {
        header('Content-Type: application/json');
        
        try {
            $maKhoi = $_GET['maKhoi'] ?? null;
            $hocKy = $_GET['hocKy'] ?? null;
            $namHoc = $_GET['namHoc'] ?? null;
            
            if (!$maKhoi) {
                throw new Exception("Thiếu thông tin khối!");
            }
            
            $danhSachDeThi = $this->model->layDeThiTheoKhoi($maKhoi, $hocKy, $namHoc);
            
            echo json_encode([
                'success' => true,
                'data' => $danhSachDeThi
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    /**
     * Xem file đề thi (mở trong cửa sổ mới)
     */
    public function xemFileDeThi() {
        try {
            $maDeThi = $_GET['maDeThi'] ?? null;
            
            if (!$maDeThi) {
                throw new Exception("Không tìm thấy thông tin đề thi!");
            }
            
            // Kiểm tra quyền truy cập
            if (!$this->model->kiemTraQuyenTruCapFile($maDeThi, $_SESSION['loaiTaiKhoan'] ?? null)) {
                throw new Exception("Bạn không có quyền xem file này!");
            }
            
            // Lấy thông tin file
            $thongTinFile = $this->model->layThongTinFileDeThi($maDeThi);
            
            if (!$thongTinFile) {
                throw new Exception("Không tìm thấy file đề thi!");
            }
            
            // Xử lý đường dẫn file - chuyển đổi đường dẫn tương đối thành tuyệt đối
            $filePath = $thongTinFile['duongDan'];
            if (!file_exists($filePath)) {
                // Thử với đường dẫn tương đối từ thư mục controller
                $filePath = '../' . $thongTinFile['duongDan'];
                if (!file_exists($filePath)) {
                    throw new Exception("File đề thi không tồn tại: " . $thongTinFile['duongDan']);
                }
            }
            
            // Lấy thông tin file
            $fileName = $thongTinFile['tenFile'];
            $fileSize = filesize($filePath);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Xác định MIME type
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt' => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'txt' => 'text/plain',
                'rtf' => 'application/rtf'
            ];
            
            $mimeType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';
            
            // Gửi header cho việc xem file
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . $fileSize);
            header('Content-Disposition: inline; filename="' . $fileName . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Đọc và xuất file
            readfile($filePath);
            exit();
            
        } catch (Exception $e) {
            header("Location: ?error=" . urlencode("Lỗi: " . $e->getMessage()));
            exit();
        }
    }
    
    /**
     * Tải xuống file đề thi
     */
    public function taiFileDeThi() {
        try {
            $maDeThi = $_GET['maDeThi'] ?? null;
            
            if (!$maDeThi) {
                throw new Exception("Không tìm thấy thông tin đề thi!");
            }
            
            // Kiểm tra quyền truy cập
            if (!$this->model->kiemTraQuyenTruCapFile($maDeThi, $_SESSION['loaiTaiKhoan'] ?? null)) {
                throw new Exception("Bạn không có quyền tải file này!");
            }
            
            // Lấy thông tin file
            $thongTinFile = $this->model->layThongTinFileDeThi($maDeThi);
            
            if (!$thongTinFile) {
                throw new Exception("Không tìm thấy file đề thi!");
            }
            
            // Xử lý đường dẫn file - chuyển đổi đường dẫn tương đối thành tuyệt đối
            $filePath = $thongTinFile['duongDan'];
            if (!file_exists($filePath)) {
                // Thử với đường dẫn tương đối từ thư mục controller
                $filePath = '../' . $thongTinFile['duongDan'];
                if (!file_exists($filePath)) {
                    throw new Exception("File đề thi không tồn tại: " . $thongTinFile['duongDan']);
                }
            }
            
            // Lấy thông tin file
            $fileName = $thongTinFile['tenFile'];
            $fileSize = filesize($filePath);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Tạo tên file tải xuống
            $downloadName = sanitize_filename($thongTinFile['tenDeThi'] . '_' . $thongTinFile['tenMonHoc']) . '.' . $fileExtension;
            
            // Xác định MIME type
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt' => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'txt' => 'text/plain',
                'rtf' => 'application/rtf'
            ];
            
            $mimeType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';
            
            // Gửi header cho việc tải xuống
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . $fileSize);
            header('Content-Disposition: attachment; filename="' . $downloadName . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Đọc và xuất file
            readfile($filePath);
            exit();
            
        } catch (Exception $e) {
            header("Location: ?error=" . urlencode("Lỗi: " . $e->getMessage()));
            exit();
        }
    }
}

/**
 * Hàm hỗ trợ làm sạch tên file
 */
function sanitize_filename($filename) {
    // Loại bỏ các ký tự không hợp lệ
    $filename = preg_replace('/[^a-zA-Z0-9À-ỹ\s\-_.]/', '', $filename);
    // Thay thế khoảng trắng bằng dấu gạch dưới
    $filename = preg_replace('/\s+/', '_', $filename);
    // Loại bỏ nhiều dấu gạch dưới liên tiếp
    $filename = preg_replace('/_+/', '_', $filename);
    // Cắt bỏ dấu gạch dưới ở đầu và cuối
    return trim($filename, '_');
}

// Khởi tạo và xử lý yêu cầu
$controller = new cChonDe();
$controller->xuLyYeuCau();
?>