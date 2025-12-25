<?php
/**
 * Controller: Permission Management
 * Quản lý phân quyền người dùng
 */

session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

require_once('../model/mConnect.php');

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // =============================================
    // ACCOUNT MANAGEMENT
    // =============================================
    case 'getAccounts':
        getAccounts();
        break;
    
    case 'getAccountPermissions':
        getAccountPermissions();
        break;
    
    case 'saveAccountPermissions':
        saveAccountPermissions();
        break;
    
    case 'saveBulkPermissions':
        saveBulkPermissions();
        break;
    
    // =============================================
    // GROUP MANAGEMENT
    // =============================================
    case 'getGroups':
        getGroups();
        break;
    
    case 'createGroup':
        createGroup();
        break;
    
    case 'updateGroup':
        updateGroup();
        break;
    
    case 'deleteGroup':
        deleteGroup();
        break;
    
    case 'getGroupPermissions':
        getGroupPermissions();
        break;
    
    case 'saveGroupPermissions':
        saveGroupPermissions();
        break;
    
    // =============================================
    // AUDIT LOG
    // =============================================
    case 'getAuditLog':
        getAuditLog();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

/**
 * Lấy danh sách tài khoản
 */
function getAccounts() {
    $dbObj = new mConnect();
    $db = $dbObj->mConnect();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
        return;
    }
    
    $search = $_GET['search'] ?? '';
    $groupFilter = $_GET['group'] ?? '';
    
    $sql = "SELECT t.maTaiKhoan, t.tenDangNhap, t.hoTen, t.email, t.loaiTaiKhoan, 
                   t.trangThaiTaiKhoan, t.maNhom,
                   n.tenNhom, n.quyenHan
            FROM taikhoan t
            LEFT JOIN nhomnguoidung n ON t.maNhom = n.maNhom
            WHERE t.trangThaiTaiKhoan != 'disabled'";
    
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $sql .= " AND (t.hoTen LIKE ? OR t.tenDangNhap LIKE ? OR t.maTaiKhoan LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    if (!empty($groupFilter) && $groupFilter !== 'all') {
        $sql .= " AND t.maNhom = ?";
        $params[] = $groupFilter;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY t.hoTen ASC";
    
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        // Parse quyenHan JSON
        $row['permissions'] = json_decode($row['quyenHan'], true) ?? [];
        unset($row['quyenHan']);
        $accounts[] = $row;
    }
    
    $stmt->close();
    $dbObj->mDisconnect($db);
    
    echo json_encode(['success' => true, 'data' => $accounts]);
}

/**
 * Lấy quyền của một tài khoản
 */
function getAccountPermissions() {
    $maTaiKhoan = $_GET['maTaiKhoan'] ?? 0;
    
    if (empty($maTaiKhoan)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã tài khoản']);
        return;
    }
    
    $dbObj = new mConnect();
    $db = $dbObj->mConnect();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
        return;
    }
    
    $sql = "SELECT t.maTaiKhoan, t.tenDangNhap, t.hoTen, t.loaiTaiKhoan, t.maNhom,
                   n.tenNhom, n.quyenHan
            FROM taikhoan t
            LEFT JOIN nhomnguoidung n ON t.maNhom = n.maNhom
            WHERE t.maTaiKhoan = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $maTaiKhoan);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    
    if (!$account) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
        $stmt->close();
        $dbObj->mDisconnect($db);
        return;
    }
    
    // Parse permissions từ nhóm
    $permissions = json_decode($account['quyenHan'], true) ?? [];
    
    $stmt->close();
    $dbObj->mDisconnect($db);
    
    echo json_encode([
        'success' => true, 
        'data' => [
            'account' => $account,
            'permissions' => $permissions
        ]
    ]);
}

/**
 * Lưu quyền cho tài khoản (thông qua thay đổi nhóm hoặc cập nhật quyền nhóm)
 */
function saveAccountPermissions() {
    $maTaiKhoan = $_POST['maTaiKhoan'] ?? 0;
    $maNhom = $_POST['maNhom'] ?? null;
    $permissions = $_POST['permissions'] ?? [];
    
    if (empty($maTaiKhoan)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã tài khoản']);
        return;
    }
    
    $dbObj = new mConnect();
    $db = $dbObj->mConnect();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
        return;
    }
    
    try {
        $db->begin_transaction();
        
        // Nếu có maNhom, chỉ cập nhật nhóm của tài khoản
        if (!empty($maNhom)) {
            $sql = "UPDATE taikhoan SET maNhom = ?, ngayCapNhat = NOW() WHERE maTaiKhoan = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ii', $maNhom, $maTaiKhoan);
            $stmt->execute();
            $stmt->close();
        }
        
        // Nếu có danh sách permissions, cập nhật quyền của nhóm hiện tại
        if (!empty($permissions)) {
            // Lấy maNhom hiện tại của tài khoản
            $sql = "SELECT maNhom FROM taikhoan WHERE maTaiKhoan = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('i', $maTaiKhoan);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $currentGroup = $row['maNhom'] ?? null;
            $stmt->close();
            
            if ($currentGroup) {
                // Cập nhật quyền của nhóm
                $permissionsJson = json_encode($permissions);
                $sql = "UPDATE nhomnguoidung SET quyenHan = ?, ngayCapNhat = NOW(), nguoiCapNhat = ? WHERE maNhom = ?";
                $stmt = $db->prepare($sql);
                $adminId = $_SESSION['maTaiKhoan'];
                $stmt->bind_param('sii', $permissionsJson, $adminId, $currentGroup);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Ghi log
        logAction($db, 'capnhat', 'phanquyen', "Cập nhật quyền cho tài khoản ID: $maTaiKhoan");
        
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'Phân quyền thành công']);
        
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
    
    $dbObj->mDisconnect($db);
}

/**
 * Lưu quyền cho nhiều tài khoản cùng lúc
 */
function saveBulkPermissions() {
    $accountIds = $_POST['accountIds'] ?? [];
    $permissions = $_POST['permissions'] ?? [];
    
    if (empty($accountIds)) {
        echo json_encode(['success' => false, 'message' => 'Chưa chọn tài khoản nào']);
        return;
    }
    
    $dbObj = new mConnect();
    $db = $dbObj->mConnect();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
        return;
    }
    
    try {
        $db->begin_transaction();
        
        // Lấy danh sách các nhóm của các tài khoản được chọn
        $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
        $sql = "SELECT DISTINCT maNhom FROM taikhoan WHERE maTaiKhoan IN ($placeholders)";
        $stmt = $db->prepare($sql);
        $types = str_repeat('i', count($accountIds));
        $stmt->bind_param($types, ...$accountIds);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $groups = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['maNhom']) {
                $groups[] = $row['maNhom'];
            }
        }
        $stmt->close();
        
        // Cập nhật quyền cho từng nhóm
        $permissionsJson = json_encode($permissions);
        $adminId = $_SESSION['maTaiKhoan'];
        
        foreach ($groups as $groupId) {
            $sql = "UPDATE nhomnguoidung SET quyenHan = ?, ngayCapNhat = NOW(), nguoiCapNhat = ? WHERE maNhom = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('sii', $permissionsJson, $adminId, $groupId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Ghi log
        $accountCount = count($accountIds);
        logAction($db, 'capnhat', 'phanquyen', "Cập nhật quyền hàng loạt cho $accountCount tài khoản");
        
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => "Đã phân quyền cho $accountCount tài khoản"]);
        
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
    
    $dbObj->mDisconnect($db);
}

/**
 * Lấy danh sách nhóm người dùng
 */
function getGroups() {
    $dbObj = new mConnect();
    $db = $dbObj->mConnect();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
        return;
    }
    
    $sql = "SELECT n.*, 
                   (SELECT COUNT(*) FROM taikhoan t WHERE t.maNhom = n.maNhom AND t.trangThaiTaiKhoan != 'disabled') as soTaiKhoan
            FROM nhomnguoidung n
            ORDER BY n.tenNhom";
    
    $result = $db->query($sql);
    
    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $row['quyenHan'] = json_decode($row['quyenHan'], true) ?? [];
        $groups[] = $row;
    }
    
    $dbObj->mDisconnect($db);
    
    echo json_encode(['success' => true, 'data' => $groups]);
}

/**
 * Tạo nhóm mới
 */
function createGroup() {
    $tenNhom = trim($_POST['tenNhom'] ?? '');
    $moTa = trim($_POST['moTa'] ?? '');
    $quyenHan = $_POST['quyenHan'] ?? [];
    
    if (empty($tenNhom)) {
        echo json_encode(['success' => false, 'message' => 'Tên nhóm không được để trống']);
        return;
    }
    
    $dbObj = new mConnect();
    $db = $dbObj->mConnect();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
        return;
    }
    
    try {
        // Kiểm tra tên nhóm đã tồn tại
        $sql = "SELECT maNhom FROM nhomnguoidung WHERE tenNhom = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $tenNhom);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Tên nhóm đã tồn tại']);
            $stmt->close();
            $dbObj->mDisconnect($db);
            return;
        }
        $stmt->close();
        
        // Tạo mã nhóm mới
        $sql = "SELECT MAX(maNhom) as maxId FROM nhomnguoidung";
        $result = $db->query($sql);
        $row = $result->fetch_assoc();
        $newId = ($row['maxId'] ?? 3000) + 1;
        
        // Thêm nhóm mới
        $quyenHanJson = json_encode($quyenHan);
        $adminId = $_SESSION['maTaiKhoan'];
        
        $sql = "INSERT INTO nhomnguoidung (maNhom, tenNhom, moTa, quyenHan, nguoiTao) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('isssi', $newId, $tenNhom, $moTa, $quyenHanJson, $adminId);
        $stmt->execute();
        $stmt->close();
        
        // Ghi log
        logAction($db, 'them', 'nhomnguoidung', "Tạo nhóm mới: $tenNhom (ID: $newId)");
        
        echo json_encode(['success' => true, 'message' => 'Tạo nhóm thành công', 'maNhom' => $newId]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
    
    $dbObj->mDisconnect($db);
}

/**
 * Cập nhật nhóm
 */
function updateGroup() {
    $maNhom = $_POST['maNhom'] ?? 0;
    $tenNhom = trim($_POST['tenNhom'] ?? '');
    $moTa = trim($_POST['moTa'] ?? '');
    
    if (empty($maNhom) || empty($tenNhom)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
        return;
    }
    
    $dbObj = new mConnect();
    $db = $dbObj->mConnect();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
        return;
    }
    
    try {
        // Kiểm tra tên nhóm đã tồn tại (trừ nhóm hiện tại)
        $sql = "SELECT maNhom FROM nhomnguoidung WHERE tenNhom = ? AND maNhom != ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $tenNhom, $maNhom);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Tên nhóm đã tồn tại']);
            $stmt->close();
            $dbObj->mDisconnect($db);
            return;
        }
        $stmt->close();
        
        // Cập nhật nhóm
        $adminId = $_SESSION['maTaiKhoan'];
        $sql = "UPDATE nhomnguoidung SET tenNhom = ?, moTa = ?, nguoiCapNhat = ? WHERE maNhom = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('ssii', $tenNhom, $moTa, $adminId, $maNhom);
        $stmt->execute();
        $stmt->close();
        
        // Ghi log
        logAction($db, 'capnhat', 'nhomnguoidung', "Cập nhật nhóm: $tenNhom (ID: $maNhom)");
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật nhóm thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
    
    $dbObj->mDisconnect($db);
}

/**
 * Xóa nhóm
 */
function deleteGroup() {
    $maNhom = $_POST['maNhom'] ?? 0;
    
    if (empty($maNhom)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã nhóm']);
        return;
    }
    
    // Không cho xóa các nhóm hệ thống
    $protectedGroups = [3001, 3002, 3003, 3004, 3005];
    if (in_array($maNhom, $protectedGroups)) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa nhóm hệ thống']);
        return;
    }
    
    $dbObj = new mConnect();
    $db = $dbObj->mConnect();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
        return;
    }
    
    try {
        // Kiểm tra xem nhóm có tài khoản không
        $sql = "SELECT COUNT(*) as count FROM taikhoan WHERE maNhom = ? AND trangThaiTaiKhoan != 'disabled'";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $maNhom);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa nhóm đang có tài khoản']);
            $dbObj->mDisconnect($db);
            return;
        }
        
        // Lấy tên nhóm để ghi log
        $sql = "SELECT tenNhom FROM nhomnguoidung WHERE maNhom = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $maNhom);
        $stmt->execute();
        $result = $stmt->get_result();
        $groupInfo = $result->fetch_assoc();
        $stmt->close();
        
        // Xóa nhóm
        $sql = "DELETE FROM nhomnguoidung WHERE maNhom = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $maNhom);
        $stmt->execute();
        $stmt->close();
        
        // Ghi log
        $tenNhom = $groupInfo['tenNhom'] ?? 'Unknown';
        logAction($db, 'xoa', 'nhomnguoidung', "Xóa nhóm: $tenNhom (ID: $maNhom)");
        
        echo json_encode(['success' => true, 'message' => 'Xóa nhóm thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
    
    $dbObj->mDisconnect($db);
}

/**
 * Lấy quyền của nhóm
 */
function getGroupPermissions() {
    $maNhom = $_GET['maNhom'] ?? 0;
    
    if (empty($maNhom)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã nhóm']);
        return;
    }
    
    $dbObj = new mConnect();
    $db = $dbObj->mConnect();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
        return;
    }
    
    $sql = "SELECT * FROM nhomnguoidung WHERE maNhom = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $maNhom);
    $stmt->execute();
    $result = $stmt->get_result();
    $group = $result->fetch_assoc();
    $stmt->close();
    
    if (!$group) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhóm']);
        $dbObj->mDisconnect($db);
        return;
    }
    
    $group['quyenHan'] = json_decode($group['quyenHan'], true) ?? [];
    
    $dbObj->mDisconnect($db);
    
    echo json_encode(['success' => true, 'data' => $group]);
}

/**
 * Lưu quyền cho nhóm
 */
function saveGroupPermissions() {
    $maNhom = $_POST['maNhom'] ?? 0;
    $permissions = $_POST['permissions'] ?? [];
    
    if (empty($maNhom)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã nhóm']);
        return;
    }
    
    $dbObj = new mConnect();
    $db = $dbObj->mConnect();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
        return;
    }
    
    try {
        $permissionsJson = json_encode($permissions);
        $adminId = $_SESSION['maTaiKhoan'];
        
        $sql = "UPDATE nhomnguoidung SET quyenHan = ?, nguoiCapNhat = ? WHERE maNhom = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('sii', $permissionsJson, $adminId, $maNhom);
        $stmt->execute();
        $stmt->close();
        
        // Ghi log
        logAction($db, 'capnhat', 'nhomnguoidung', "Cập nhật quyền cho nhóm ID: $maNhom");
        
        echo json_encode(['success' => true, 'message' => 'Lưu quyền thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
    
    $dbObj->mDisconnect($db);
}

/**
 * Lấy lịch sử thay đổi quyền
 */
function getAuditLog() {
    $dbObj = new mConnect();
    $db = $dbObj->mConnect();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
        return;
    }
    
    $sql = "SELECT l.*, t.hoTen as nguoiThucHien
            FROM lichsunhapxuat l
            LEFT JOIN taikhoan t ON l.maTaiKhoan = t.maTaiKhoan
            WHERE l.bangDuLieu IN ('phanquyen', 'nhomnguoidung', 'taikhoan')
            ORDER BY l.thoiGian DESC
            LIMIT 100";
    
    $result = $db->query($sql);
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    $dbObj->mDisconnect($db);
    
    echo json_encode(['success' => true, 'data' => $logs]);
}

/**
 * Ghi log hành động
 */
function logAction($db, $action, $table, $note) {
    $maTaiKhoan = $_SESSION['maTaiKhoan'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Tạo ID mới
    $result = $db->query("SELECT MAX(maLichSu) as maxId FROM lichsunhapxuat");
    $row = $result->fetch_assoc();
    $newId = ($row['maxId'] ?? 30000) + 1;
    
    $sql = "INSERT INTO lichsunhapxuat (maLichSu, maTaiKhoan, hanhDong, bangDuLieu, ghiChu, diaChiIP, userAgent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('iisssss', $newId, $maTaiKhoan, $action, $table, $note, $ip, $userAgent);
    $stmt->execute();
    $stmt->close();
}
