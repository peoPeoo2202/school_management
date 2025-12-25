<?php
/**
 * File cấu hình đường dẫn cho toàn bộ hệ thống
 * Tự động phát hiện BASE_URL dựa trên vị trí thực tế của project
 */

// Khởi động session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ĐẶT TIMEZONE CHO TOÀN HỆ THỐNG - QUAN TRỌNG!
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Lấy thư mục gốc của project (nơi file config.php đang nằm)
define('ROOT_PATH', __DIR__);

// Tính toán BASE_URL tự động
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Chuẩn hóa đường dẫn (chuyển tất cả \ thành /)
$documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$currentPath = str_replace('\\', '/', ROOT_PATH);

// Lấy đường dẫn tương đối từ DOCUMENT_ROOT
$scriptPath = str_replace($documentRoot, '', $currentPath);

// Đảm bảo bắt đầu bằng /
if ($scriptPath && $scriptPath[0] !== '/') {
    $scriptPath = '/' . $scriptPath;
}

define('BASE_URL', $protocol . '://' . $host . $scriptPath);

// Các đường dẫn con
define('CONTROLLER_URL', BASE_URL . '/controller');
define('VIEW_URL', BASE_URL . '/view');
define('PUBLIC_URL', BASE_URL . '/public');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Đường dẫn file hệ thống (cho PHP include)
define('CONTROLLER_PATH', ROOT_PATH . '/controller');
define('MODEL_PATH', ROOT_PATH . '/model');
define('VIEW_PATH', ROOT_PATH . '/view');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

/**
 * Hàm helper tạo URL
 * @param string $path - Đường dẫn tương đối từ BASE_URL
 * @return string - URL đầy đủ
 */
function url($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

/**
 * Hàm helper tạo asset URL (cho CSS, JS, images)
 * @param string $path - Đường dẫn tương đối
 * @return string - URL đầy đủ
 */
function asset($path = '') {
    $path = ltrim($path, '/');
    return PUBLIC_URL . '/' . $path;
}
?>
