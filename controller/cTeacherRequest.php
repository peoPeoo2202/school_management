<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../model/mYeuCau.php');
require_once(__DIR__ . '/../model/mConnect.php');

class ControllerTeacherRequest {
    private $model;

    public function __construct() {
        $this->model = new ModelYeuCau();
    }

    /**
     * Kiểm tra quyền truy cập - Chỉ giáo viên
     */
    private function kiemTraQuyenTruyCap() {
        if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            header("Location: ../public/index.php");
            exit();
        }
        
        if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
            header("Location: ../public/index.php?error=access_denied");
            exit();
        }
    }

    /**
     * Lấy mã giáo viên từ session
     */
    private function layMaGV() {
        return $_SESSION['maGV'] ?? null;
    }

    /**
     * Hiển thị danh sách yêu cầu của giáo viên
     */
    public function danhSachYeuCau() {
        $this->kiemTraQuyenTruyCap();
        
        $maGV = $this->layMaGV();
        if (!$maGV) {
            $_SESSION['error'] = "Không xác định được thông tin giáo viên";
            header("Location: dashboard.php");
            exit();
        }

        $loaiYeuCau = isset($_GET['loaiYeuCau']) ? $_GET['loaiYeuCau'] : 'all';
        $trangThai = isset($_GET['trangThai']) ? $_GET['trangThai'] : 'all';

        $danhSachYeuCau = $this->model->layDanhSachYeuCauCuaGV($maGV, $loaiYeuCau, $trangThai);
        
        require_once(__DIR__ . '/../view/teacher/vDanhSachYeuCau.php');
    }

    /**
     * Hiển thị form gửi yêu cầu sửa điểm
     */
    public function formSuaDiem() {
        $this->kiemTraQuyenTruyCap();
        
        $maGV = $this->layMaGV();
        if (!$maGV) {
            $_SESSION['error'] = "Không xác định được thông tin giáo viên";
            header("Location: dashboard.php");
            exit();
        }

        // Lấy danh sách môn học
        $danhSachMonHoc = $this->model->layMonHocCuaGV($maGV);
        
        require_once(__DIR__ . '/../view/teacher/vYeuCauSuaDiem.php');
    }

    /**
     * Lấy danh sách học sinh theo môn (AJAX)
     */
    public function layDanhSachHocSinh() {
        $this->kiemTraQuyenTruyCap();
        
        header('Content-Type: application/json');
        
        $maGV = $this->layMaGV();
        $maMonHoc = isset($_POST['maMonHoc']) ? $_POST['maMonHoc'] : null;
        $hocKy = isset($_POST['hocKy']) ? $_POST['hocKy'] : null;
        $namHoc = isset($_POST['namHoc']) ? $_POST['namHoc'] : null;
        
        if (!$maGV || !$maMonHoc || !$hocKy || !$namHoc) {
            echo json_encode(['success' => false, 'message' => 'Thiếu tham số']);
            exit();
        }
        
        $danhSach = $this->model->layHocSinhTheoMon($maGV, $maMonHoc, $hocKy, $namHoc);
        echo json_encode(['success' => true, 'data' => $danhSach]);
        exit();
    }

    /**
     * Lấy điểm của học sinh (AJAX)
     */
    public function layDiemHocSinh() {
        $this->kiemTraQuyenTruyCap();
        
        header('Content-Type: application/json');
        
        $maBangDiem = isset($_POST['maBangDiem']) ? $_POST['maBangDiem'] : null;
        
        if (!$maBangDiem) {
            echo json_encode(['success' => false, 'message' => 'Thiếu tham số']);
            exit();
        }
        
        $diem = $this->model->layDiemHocSinh($maBangDiem);
        
        if ($diem) {
            echo json_encode(['success' => true, 'data' => $diem]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy bảng điểm']);
        }
        exit();
    }

    /**
     * Xử lý gửi yêu cầu sửa điểm
     */
    public function xuLyGuiYeuCauSuaDiem() {
        $this->kiemTraQuyenTruyCap();
        
        $maGV = $this->layMaGV();
        if (!$maGV) {
            $_SESSION['error'] = "Không xác định được thông tin giáo viên";
            header("Location: dashboard.php?action=yeucau");
            exit();
        }

        // Validate dữ liệu
        $errors = [];
        
        if (empty($_POST['maMonHoc'])) {
            $errors[] = "Vui lòng chọn môn học";
        }
        if (empty($_POST['loaiDiem'])) {
            $errors[] = "Vui lòng chọn loại đánh giá";
        }
        if (empty($_POST['maHS'])) {
            $errors[] = "Vui lòng chọn học sinh";
        }
        if (empty($_POST['maBangDiem'])) {
            $errors[] = "Không tìm thấy bảng điểm của học sinh";
        }
        if (!isset($_POST['diemHienTai']) || $_POST['diemHienTai'] === '') {
            $errors[] = "Vui lòng nhập điểm hiện tại";
        }
        if (!isset($_POST['diemDeNghiSua']) || $_POST['diemDeNghiSua'] === '') {
            $errors[] = "Vui lòng nhập điểm đề nghị sửa";
        }
        if (empty($_POST['lyDoSuaDiem'])) {
            $errors[] = "Vui lòng nhập lý do sửa điểm";
        }
        if (empty($_POST['hocKy'])) {
            $errors[] = "Vui lòng chọn học kỳ";
        }
        if (empty($_POST['namHoc'])) {
            $errors[] = "Vui lòng chọn năm học";
        }

        // Xử lý upload file minh chứng
        $minhChung = null;
        if (isset($_FILES['minhChung']) && $_FILES['minhChung']['error'] == 0) {
            $uploadResult = $this->xuLyUploadFile($_FILES['minhChung']);
            if ($uploadResult['success']) {
                $minhChung = $uploadResult['filename'];
            } else {
                $errors[] = $uploadResult['message'];
            }
        } else if (isset($_POST['yeuCauMinhChung']) && $_POST['yeuCauMinhChung'] == '1') {
            // Nếu yêu cầu bắt buộc minh chứng nhưng không có file
            $errors[] = "Vui lòng tải lên file minh chứng";
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: dashboard.php?action=yeucau&type=suadiem");
            exit();
        }

        // Chuẩn bị dữ liệu
        $data = [
            'maGV' => $maGV,
            'maMonHoc' => $_POST['maMonHoc'],
            'tenMonHoc' => $_POST['tenMonHoc'],
            'maHS' => $_POST['maHS'],
            'maBangDiem' => $_POST['maBangDiem'],
            'loaiDiem' => $_POST['loaiDiem'],
            'diemHienTai' => $_POST['diemHienTai'],
            'diemDeNghiSua' => $_POST['diemDeNghiSua'],
            'lyDoSuaDiem' => $_POST['lyDoSuaDiem'],
            'hocKy' => $_POST['hocKy'],
            'namHoc' => $_POST['namHoc'],
            'minhChung' => $minhChung
        ];

        // Gửi yêu cầu
        if ($this->model->guiYeuCauSuaDiem($data)) {
            $_SESSION['success'] = "Yêu cầu sửa điểm đã được gửi thành công. Ban giám hiệu sẽ xem xét và xử lý.";
            header("Location: dashboard.php?action=danhsachyeucau");
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại sau.";
            header("Location: dashboard.php?action=yeucau&type=suadiem");
        }
        exit();
    }

    /**
     * Hiển thị form gửi yêu cầu nghỉ phép
     */
    public function formNghiPhep() {
        $this->kiemTraQuyenTruyCap();
        
        $maGV = $this->layMaGV();
        if (!$maGV) {
            $_SESSION['error'] = "Không xác định được thông tin giáo viên";
            header("Location: dashboard.php");
            exit();
        }
        
        require_once(__DIR__ . '/../view/teacher/vYeuCauNghiPhep.php');
    }

    /**
     * Xử lý gửi yêu cầu nghỉ phép
     */
    public function xuLyGuiYeuCauNghiPhep() {
        $this->kiemTraQuyenTruyCap();
        
        $maGV = $this->layMaGV();
        if (!$maGV) {
            $_SESSION['error'] = "Không xác định được thông tin giáo viên";
            header("Location: dashboard.php?action=yeucau");
            exit();
        }

        // Validate dữ liệu
        $errors = [];
        
        if (empty($_POST['ngayBatDauNghi'])) {
            $errors[] = "Vui lòng chọn ngày bắt đầu nghỉ";
        }
        if (empty($_POST['ngayKetThucNghi'])) {
            $errors[] = "Vui lòng chọn ngày kết thúc nghỉ";
        }
        if (!empty($_POST['ngayBatDauNghi']) && !empty($_POST['ngayKetThucNghi'])) {
            if (strtotime($_POST['ngayKetThucNghi']) < strtotime($_POST['ngayBatDauNghi'])) {
                $errors[] = "Ngày kết thúc phải sau ngày bắt đầu";
            }
        }
        if (empty($_POST['lyDo'])) {
            $errors[] = "Vui lòng nhập lý do nghỉ phép";
        }

        // Xử lý upload file minh chứng (không bắt buộc)
        $minhChung = null;
        if (isset($_FILES['minhChung']) && $_FILES['minhChung']['error'] == 0) {
            $uploadResult = $this->xuLyUploadFile($_FILES['minhChung']);
            if ($uploadResult['success']) {
                $minhChung = $uploadResult['filename'];
            } else {
                $errors[] = $uploadResult['message'];
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: dashboard.php?action=yeucau&type=nghiphep");
            exit();
        }

        // Chuẩn bị dữ liệu
        $data = [
            'maGV' => $maGV,
            'ngayBatDauNghi' => $_POST['ngayBatDauNghi'],
            'ngayKetThucNghi' => $_POST['ngayKetThucNghi'],
            'lyDo' => $_POST['lyDo'],
            'minhChung' => $minhChung
        ];

        // Gửi yêu cầu
        if ($this->model->guiYeuCauNghiPhep($data)) {
            $_SESSION['success'] = "Yêu cầu nghỉ phép đã được gửi thành công. Ban giám hiệu sẽ xem xét và xử lý.";
            header("Location: dashboard.php?action=danhsachyeucau");
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại sau.";
            header("Location: dashboard.php?action=yeucau&type=nghiphep");
        }
        exit();
    }

    /**
     * Hiển thị chi tiết yêu cầu
     */
    public function chiTietYeuCau() {
        $this->kiemTraQuyenTruyCap();
        
        $maGV = $this->layMaGV();
        $maYeuCau = isset($_GET['maYeuCau']) ? $_GET['maYeuCau'] : null;
        
        if (!$maYeuCau) {
            header("Location: dashboard.php?action=danhsachyeucau");
            exit();
        }

        $yeuCau = $this->model->layChiTietYeuCau($maYeuCau);
        
        if (!$yeuCau || $yeuCau['maGV'] != $maGV) {
            $_SESSION['error'] = "Không tìm thấy yêu cầu hoặc bạn không có quyền xem";
            header("Location: dashboard.php?action=danhsachyeucau");
            exit();
        }

        // Lấy lịch sử xử lý
        $lichSuXuLy = $this->model->layLichSuXuLy($maYeuCau);
        
        require_once(__DIR__ . '/../view/teacher/vChiTietYeuCau.php');
    }

    /**
     * Xử lý upload file
     */
    private function xuLyUploadFile($file) {
        $uploadDir = __DIR__ . '/../uploads/requests/';
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Kiểm tra định dạng file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            return [
                'success' => false,
                'message' => 'File không đúng định dạng. Chỉ chấp nhận file ảnh (JPG, PNG) hoặc PDF'
            ];
        }

        // Kiểm tra kích thước file (tối đa 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'Kích thước file vượt quá 5MB'
            ];
        }

        // Tạo tên file unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'request_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Upload file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'filename' => $filename
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể upload file. Vui lòng thử lại'
            ];
        }
    }

    /**
     * Tải xuống file minh chứng
     */
    public function downloadMinhChung() {
        $this->kiemTraQuyenTruyCap();
        
        $filename = isset($_GET['file']) ? $_GET['file'] : null;
        
        if (!$filename) {
            die('File không tồn tại');
        }

        $filepath = __DIR__ . '/../uploads/requests/' . $filename;
        
        if (!file_exists($filepath)) {
            die('File không tồn tại');
        }

        // Set headers để download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    }
}

// Xử lý routing
if (isset($_GET['ajax'])) {
    $controller = new ControllerTeacherRequest();
    
    switch ($_GET['ajax']) {
        case 'layDanhSachHocSinh':
            $controller->layDanhSachHocSinh();
            break;
        case 'layDiemHocSinh':
            $controller->layDiemHocSinh();
            break;
    }
}
?>
