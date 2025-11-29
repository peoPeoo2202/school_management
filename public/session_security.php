<?php
/**
 * Enhanced Session Security Middleware
 * Features:
 * - Session validation and timeout (15 minutes)
 * - Login attempt rate limiting
 * - Account lockout after failed attempts
 * - CSRF token generation and validation
 */

if (session_status() === PHP_SESSION_NONE) {
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['maTaiKhoan']) && isset($_SESSION['tenDangNhap']);
}

/**
 * Check if session has timed out (15 minutes of inactivity)
 */
function checkSessionTimeout() {
    $timeout = 15 * 60; // 15 minutes in seconds
    
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
        // Session timed out
        session_unset();
        session_destroy();
        return true;
    }
    
    $_SESSION['LAST_ACTIVITY'] = time();
    return false;
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin($redirectUrl = '/school_management/public/index.php') {
    if (checkSessionTimeout()) {
        $_SESSION['error_message'] = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
        header("Location: $redirectUrl");
        exit;
    }
    
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = 'Vui lòng đăng nhập để tiếp tục.';
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole($allowedRoles, $redirectUrl = '/school_management/public/index.php') {
    requireLogin($redirectUrl);
    
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    if (!in_array($_SESSION['loaiTaiKhoan'], $allowedRoles)) {
        http_response_code(403);
        die('Không có quyền truy cập.');
    }
}

/**
 * Require admin role
 */
function requireAdmin($redirectUrl = '/school_management/public/index.php') {
    requireRole('quantrivien', $redirectUrl);
}

/**
 * Rate limiting for login attempts
 * Returns true if rate limit exceeded
 */
function checkLoginRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    $key = 'login_attempts_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 0,
            'first_attempt' => time()
        ];
    }
    
    $attempts = &$_SESSION[$key];
    
    // Reset if time window has passed
    if (time() - $attempts['first_attempt'] > $timeWindow) {
        $attempts['count'] = 0;
        $attempts['first_attempt'] = time();
    }
    
    $attempts['count']++;
    
    return $attempts['count'] > $maxAttempts;
}

/**
 * Reset login rate limit
 */
function resetLoginRateLimit($identifier) {
    $key = 'login_attempts_' . md5($identifier);
    unset($_SESSION[$key]);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    
    if (empty($token) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate session ID to prevent session fixation
 */
function regenerateSessionId() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Get user info from session
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'maTaiKhoan' => $_SESSION['maTaiKhoan'] ?? null,
        'tenDangNhap' => $_SESSION['tenDangNhap'] ?? null,
        'hoTen' => $_SESSION['hoTen'] ?? null,
        'loaiTaiKhoan' => $_SESSION['loaiTaiKhoan'] ?? null,
        'maNhom' => $_SESSION['maNhom'] ?? null
    ];
}

/**
 * Check if user has specific permission
 * @param string $permission - Permission to check
 * @return bool
 */
function hasPermission($permission) {
    // Admin has all permissions
    if (isset($_SESSION['loaiTaiKhoan']) && $_SESSION['loaiTaiKhoan'] === 'quantrivien') {
        return true;
    }
    
    // Check in session permissions array
    if (isset($_SESSION['permissions']) && is_array($_SESSION['permissions'])) {
        return in_array($permission, $_SESSION['permissions']);
    }
    
    return false;
}

/**
 * Sanitize output to prevent XSS
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get and clear flash message
 */
function getFlashMessage($type = 'error_message') {
    $message = $_SESSION[$type] ?? null;
    unset($_SESSION[$type]);
    return $message;
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'error_message') {
    $_SESSION[$type] = $message;
}

/**
 * Log security event
 */
function logSecurityEvent($event, $details = '') {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $user = $_SESSION['tenDangNhap'] ?? 'guest';
    
    $logEntry = sprintf(
        "[%s] Event: %s | User: %s | IP: %s | Details: %s | UserAgent: %s\n",
        $timestamp,
        $event,
        $user,
        $ip,
        $details,
        $userAgent
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Enhanced login function with security features
 */
function performLogin($username, $password) {
    require_once(__DIR__ . '/../model/mUser.php');
    
    // Check rate limit
    $identifier = $username . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    if (checkLoginRateLimit($identifier)) {
        logSecurityEvent('LOGIN_RATE_LIMIT', "Username: $username");
        return [
            'success' => false,
            'message' => 'Quá nhiều lần đăng nhập sai. Vui lòng thử lại sau 5 phút.'
        ];
    }
    
    $mUser = new mUser();
    $user = $mUser->mLogin($username, $password);
    
    if (!$user) {
        // Failed login
        $mUser->incrementFailedLogin($username);
        logSecurityEvent('LOGIN_FAILED', "Username: $username");
        
        return [
            'success' => false,
            'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.'
        ];
    }
    
    // Check account status
    if ($user['trangThaiTaiKhoan'] === 'locked' || $user['trangThaiTaiKhoan'] == 0) {
        logSecurityEvent('LOGIN_LOCKED', "Username: $username");
        return [
            'success' => false,
            'message' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.'
        ];
    }
    
    if ($user['trangThaiTaiKhoan'] === 'disabled') {
        logSecurityEvent('LOGIN_DISABLED', "Username: $username");
        return [
            'success' => false,
            'message' => 'Tài khoản đã bị vô hiệu hóa.'
        ];
    }
    
    // Successful login
    session_regenerate_id(true);
    
    $_SESSION['maTaiKhoan'] = $user['maTaiKhoan'];
    $_SESSION['tenDangNhap'] = $user['tenDangNhap'];
    $_SESSION['hoTen'] = $user['hoTen'];
    $_SESSION['loaiTaiKhoan'] = $user['loaiTaiKhoan'];
    $_SESSION['maNhom'] = $user['maNhom'];
    $_SESSION['LAST_ACTIVITY'] = time();
    
    // Reset failed login counter
    $mUser->resetFailedLogin($user['maTaiKhoan']);
    
    // Reset rate limit
    resetLoginRateLimit($identifier);
    
    // Log activity
    $mUser->logActivity($user['maTaiKhoan'], 'dangnhap', 'taikhoan', 'Đăng nhập thành công');
    logSecurityEvent('LOGIN_SUCCESS', "Username: $username");
    
    return [
        'success' => true,
        'user' => $user
    ];
}

/**
 * Logout function
 */
function performLogout() {
    if (isset($_SESSION['maTaiKhoan'])) {
        require_once(__DIR__ . '/../model/mUser.php');
        $mUser = new mUser();
        $mUser->logActivity($_SESSION['maTaiKhoan'], 'dangxuat', 'taikhoan', 'Đăng xuất');
        logSecurityEvent('LOGOUT', "User ID: " . $_SESSION['maTaiKhoan']);
    }
    
    session_unset();
    session_destroy();
    
    // Start new session for flash messages
    session_start();
    session_regenerate_id(true);
}
?>
