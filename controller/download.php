<?php
session_start();
require_once '../model/mReport.php';
require_once '../model/mYeuCau.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    http_response_code(403);
    exit('Access denied');
}

// Kiểm tra quyền truy cập (giáo viên hoặc ban giám hiệu)
if (!in_array($_SESSION['loaiTaiKhoan'], ['giaovien', 'bangiamhieu'])) {
    http_response_code(403);
    exit('Access denied');
}

$filePath = null;
$fileName = null;

// Xử lý tải file minh chứng yêu cầu
if (isset($_GET['maYeuCau'])) {
    $maYeuCau = $_GET['maYeuCau'];
    
    // Lấy thông tin yêu cầu
    $modelYeuCau = new ModelYeuCau();
    $yeuCau = $modelYeuCau->layChiTietYeuCau($maYeuCau);
    
    if (!$yeuCau) {
        http_response_code(404);
        exit('Không tìm thấy yêu cầu');
    }
    
    // Nếu là giáo viên, chỉ cho phép tải xuống yêu cầu của chính mình
    if ($_SESSION['loaiTaiKhoan'] === 'giaovien') {
        if ($yeuCau['maGV'] != $_SESSION['maGV']) {
            http_response_code(403);
            exit('Bạn không có quyền tải xuống file này');
        }
    }
    
    // Lấy tên file minh chứng
    $fileName = $yeuCau['minhChung'];
    
    if (empty($fileName)) {
        http_response_code(404);
        exit('Yêu cầu này không có file minh chứng');
    }
    
    $filePath = __DIR__ . '/../uploads/requests/' . $fileName;
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('File không tồn tại');
    }
}
// Xử lý tải file theo đường dẫn (cho BGH)
elseif (isset($_GET['file'])) {
    $filePath = urldecode($_GET['file']);
    
    // Kiểm tra file có tồn tại không
    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('File does not exist');
    }
    
    $fileName = basename($filePath);
}
// Xử lý tải file theo ID (cho giáo viên)
elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
        http_response_code(403);
        exit('Access denied');
    }
    
    $maBaoCao = (int)$_GET['id'];
    $maGV = $_SESSION['maGV'];
    
    // Lấy thông tin file từ database
    $model = new mReport();
    $baoCao = $model->getBaoCaoById($maBaoCao, $maGV);
    
    if (!$baoCao) {
        http_response_code(404);
        exit('File not found');
    }
    
    $filePath = $baoCao['duongDan'];
    $fileName = $baoCao['tenFile'];
    
    // Kiểm tra file có tồn tại không
    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('File does not exist');
    }
} else {
    http_response_code(400);
    exit('Invalid parameters');
}

// Xác định MIME type
$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
$mimeTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

$mimeType = $mimeTypes[strtolower($fileExtension)] ?? 'application/octet-stream';

// Đặt headers
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Xuất file
readfile($filePath);
exit();
?>