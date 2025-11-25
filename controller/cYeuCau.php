<?php
session_start();
require_once(__DIR__ . '/../model/mYeuCau.php');
require_once(__DIR__ . '/../model/mConnect.php');

class ControllerYeuCau {
    private $model;

    public function __construct() {
        $this->model = new ModelYeuCau();
    }

    /**
     * Kiểm tra quyền truy cập - Chỉ BGH
     */
    private function kiemTraQuyenTruyCap() {
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
    }

    /**
     * Lấy mã BGH từ mã tài khoản
     */
    private function layMaBGH() {
        if (!isset($_SESSION['maTaiKhoan'])) {
            return null;
        }
        
        // Tạm thời lấy từ database
        $mConnect = new mConnect();
        $conn = $mConnect->mConnect();
        
        $sql = "SELECT maBGH FROM bgh WHERE maTaiKhoan = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['maTaiKhoan']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            $mConnect->mDisconnect($conn);
            return $row['maBGH'];
        }
        
        mysqli_stmt_close($stmt);
        $mConnect->mDisconnect($conn);
        return null;
    }

    /**
     * Hiển thị danh sách yêu cầu
     */
    public function danhSachYeuCau() {
        $this->kiemTraQuyenTruyCap();

        // Lấy bộ lọc từ request
        $loaiYeuCau = isset($_GET['loaiYeuCau']) ? $_GET['loaiYeuCau'] : 'all';
        $trangThai = isset($_GET['trangThai']) ? $_GET['trangThai'] : 'Choxuly';
        $tuNgay = isset($_GET['tuNgay']) ? $_GET['tuNgay'] : null;
        $denNgay = isset($_GET['denNgay']) ? $_GET['denNgay'] : null;

        // Lấy danh sách yêu cầu
        $danhSachYeuCau = $this->model->layDanhSachYeuCau($loaiYeuCau, $trangThai, $tuNgay, $denNgay);
        
        // Lấy thống kê
        $thongKe = $this->model->thongKeYeuCau();

        // Hiển thị view
        require_once(__DIR__ . '/../view/bgh/vDanhSachYeuCau.php');
    }

    /**
     * Hiển thị chi tiết yêu cầu
     */
    public function chiTietYeuCau() {
        $this->kiemTraQuyenTruyCap();

        $maYeuCau = isset($_GET['maYeuCau']) ? $_GET['maYeuCau'] : null;
        
        if (!$maYeuCau) {
            header("Location: index.php?action=danhsach");
            exit();
        }

        // Lấy chi tiết yêu cầu
        $yeuCau = $this->model->layChiTietYeuCau($maYeuCau);
        
        if (!$yeuCau) {
            $_SESSION['error'] = "Không tìm thấy yêu cầu";
            header("Location: index.php?action=danhsach");
            exit();
        }

        // Lấy lịch sử xử lý
        $lichSuXuLy = $this->model->layLichSuXuLy($maYeuCau);

        // Hiển thị view
        require_once(__DIR__ . '/../view/bgh/vChiTietYeuCau.php');
    }

    /**
     * Xử lý chấp nhận yêu cầu
     */
    public function chapNhan() {
        $this->kiemTraQuyenTruyCap();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maYeuCau = isset($_POST['maYeuCau']) ? $_POST['maYeuCau'] : null;
            $ghiChu = isset($_POST['ghiChu']) ? trim($_POST['ghiChu']) : null;
            $maBGH = $this->layMaBGH();

            if (!$maBGH) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin BGH']);
                exit();
            }

            if (!$maYeuCau) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin yêu cầu']);
                exit();
            }

            $result = $this->model->chapNhanYeuCau($maYeuCau, $maBGH, $ghiChu);

            if ($result) {
                $_SESSION['success'] = "Chấp nhận yêu cầu thành công";
                echo json_encode(['success' => true, 'message' => 'Chấp nhận yêu cầu thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xử lý yêu cầu']);
            }
        }
    }

    /**
     * Xử lý từ chối yêu cầu
     */
    public function tuChoi() {
        $this->kiemTraQuyenTruyCap();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maYeuCau = isset($_POST['maYeuCau']) ? $_POST['maYeuCau'] : null;
            $lyDoTuChoi = isset($_POST['lyDoTuChoi']) ? trim($_POST['lyDoTuChoi']) : null;
            $ghiChu = isset($_POST['ghiChu']) ? trim($_POST['ghiChu']) : null;
            $maBGH = $this->layMaBGH();

            if (!$maBGH) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin BGH']);
                exit();
            }

            if (!$maYeuCau || !$lyDoTuChoi) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
                exit();
            }

            $result = $this->model->tuChoiYeuCau($maYeuCau, $maBGH, $lyDoTuChoi, $ghiChu);

            if ($result) {
                $_SESSION['success'] = "Từ chối yêu cầu thành công";
                echo json_encode(['success' => true, 'message' => 'Từ chối yêu cầu thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xử lý yêu cầu']);
            }
        }
    }

    /**
     * Route xử lý các action
     */
    public function handleRequest() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'danhsach';

        switch ($action) {
            case 'danhsach':
                $this->danhSachYeuCau();
                break;
            case 'chitiet':
                $this->chiTietYeuCau();
                break;
            case 'chapnhan':
                $this->chapNhan();
                break;
            case 'tuchoi':
                $this->tuChoi();
                break;
            default:
                $this->danhSachYeuCau();
                break;
        }
    }
}

// Xử lý request
$controller = new ControllerYeuCau();
$controller->handleRequest();
?>
