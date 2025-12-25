<?php
/**
 * CONTROLLER: Parent Management
 * Handles CRUD operations for parent information
 */

require_once '../model/mConnect.php';
require_once '../model/mParent.php';

header('Content-Type: application/json; charset=utf-8');

// Initialize database connection
$db = new mConnect();
$conn = $db->mConnect();

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listParents();
            break;
        
        case 'getDetail':
            getParentDetail();
            break;
        
        case 'getAllStudents':
            getAllStudents();
            break;
        
        case 'create':
            createParent();
            break;
        
        case 'update':
            updateParent();
            break;
        
        case 'delete':
            deleteParents();
            break;
        
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action không hợp lệ'
            ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}

/**
 * List parents with filters and pagination
 */
function listParents() {
    global $conn;
    
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($_GET['maph'])) {
        $where[] = "ph.maPH LIKE ?";
        $params[] = '%' . $_GET['maph'] . '%';
        $types .= 's';
    }
    
    if (!empty($_GET['hoten'])) {
        $where[] = "ph.hoTen LIKE ?";
        $params[] = '%' . $_GET['hoten'] . '%';
        $types .= 's';
    }
    
    if (!empty($_GET['sdt'])) {
        $where[] = "ph.soDienThoai LIKE ?";
        $params[] = '%' . $_GET['sdt'] . '%';
        $types .= 's';
    }
    
    if (!empty($_GET['hotenhs'])) {
        $where[] = "hs.hoTen LIKE ?";
        $params[] = '%' . $_GET['hotenhs'] . '%';
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Count total records
    $countSql = "SELECT COUNT(DISTINCT ph.maPH) as total 
                 FROM phuhuynh ph
                 LEFT JOIN hocsinh hs ON ph.maPH = hs.maPH
                 $whereClause";
    
    if (!empty($params)) {
        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
    } else {
        $countResult = $conn->query($countSql);
        $total = $countResult->fetch_assoc()['total'];
    }
    
    // Get parents with student count
    $sql = "SELECT ph.maPH as maPhuHuynh, ph.hoTen, ph.soDienThoai, ph.email,
                   COUNT(DISTINCT hs.maHS) as soHocSinh
            FROM phuhuynh ph
            LEFT JOIN hocsinh hs ON ph.maPH = hs.maPH
            $whereClause
            GROUP BY ph.maPH
            ORDER BY ph.hoTen
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $parents = [];
    while ($row = $result->fetch_assoc()) {
        $parents[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'parents' => $parents,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);
}

/**
 * Get parent detail with linked students
 */
function getParentDetail() {
    global $conn;
    
    $maPhuHuynh = $_GET['maPhuHuynh'] ?? '';
    
    if (empty($maPhuHuynh)) {
        echo json_encode([
            'success' => false,
            'message' => 'Mã phụ huynh không được để trống'
        ]);
        return;
    }
    
    // Get parent info
    $sql = "SELECT maPH as maPhuHuynh, hoTen, soDienThoai, email, diaChi FROM phuhuynh WHERE maPH = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $maPhuHuynh);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy phụ huynh'
        ]);
        return;
    }
    
    $parent = $result->fetch_assoc();
    
    // Get linked students
    $studentsSql = "SELECT hs.maHS as maHocSinh, hs.hoTen, l.tenLop, k.khoiLop as khoi
                    FROM hocsinh hs
                    LEFT JOIN lophoc l ON hs.maLop = l.maLop
                    LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
                    WHERE hs.maPH = ?";
    $studentsStmt = $conn->prepare($studentsSql);
    $studentsStmt->bind_param('s', $maPhuHuynh);
    $studentsStmt->execute();
    $studentsResult = $studentsStmt->get_result();
    
    $linkedStudents = [];
    while ($row = $studentsResult->fetch_assoc()) {
        $linkedStudents[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'parent' => $parent,
        'linkedStudents' => $linkedStudents
    ]);
}

/**
 * Get all students for selector dropdown
 */
function getAllStudents() {
    global $conn;
    
    $sql = "SELECT hs.maHS as maHocSinh, hs.hoTen, l.tenLop, k.khoiLop as khoi
            FROM hocsinh hs
            LEFT JOIN lophoc l ON hs.maLop = l.maLop
            LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
            ORDER BY k.khoiLop, l.tenLop, hs.hoTen";
    
    $result = $conn->query($sql);
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'students' => $students
    ]);
}

/**
 * Create new parent
 */
function createParent() {
    global $conn;
    
    // Validate required fields
    $required = ['hoTen', 'soDienThoai', 'ngheNghiep'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Trường $field không được để trống"
            ]);
            return;
        }
    }
    
    // Validate linked students
    $linkedStudents = $_POST['linkedStudents'] ?? [];
    $linkedStudents = array_filter($linkedStudents); // Remove empty values
    
    if (empty($linkedStudents)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng chọn ít nhất một học sinh liên kết'
        ]);
        return;
    }
    
    // Generate parent ID
    $sql = "SELECT MAX(CAST(SUBSTRING(maPH, 3) AS UNSIGNED)) as maxId 
            FROM phuhuynh WHERE maPH LIKE 'PH%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $nextId = ($row['maxId'] ?? 0) + 1;
    $maPhuHuynh = 'PH' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    
    $conn->begin_transaction();
    
    try {
        // Insert parent
        $sql = "INSERT INTO phuhuynh (
                    maPH, hoTen, soDienThoai, email, diaChi
                ) VALUES (?, ?, ?, ?, ?)";
        
        // Assign to variables for bind_param
        $hoTen = $_POST['hoTen'];
        $soDienThoai = $_POST['soDienThoai'];
        $email = $_POST['email'] ?? null;
        $diaChi = $_POST['diaChi'] ?? null;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss',
            $maPhuHuynh,
            $hoTen,
            $soDienThoai,
            $email,
            $diaChi
        );
        $stmt->execute();
        
        // Update students with parent link
        foreach ($linkedStudents as $maHocSinh) {
            $updateSql = "UPDATE hocsinh 
                         SET maPH = ?
                         WHERE maHS = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param('ss', $maPhuHuynh, $maHocSinh);
            $updateStmt->execute();
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Thêm phụ huynh thành công',
            'maPhuHuynh' => $maPhuHuynh
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi thêm phụ huynh: ' . $e->getMessage()
        ]);
    }
}

/**
 * Update parent information
 */
function updateParent() {
    global $conn;
    
    $maPhuHuynh = $_POST['maPhuHuynh'] ?? '';
    
    if (empty($maPhuHuynh)) {
        echo json_encode([
            'success' => false,
            'message' => 'Mã phụ huynh không được để trống'
        ]);
        return;
    }
    
    // Validate required fields
    $required = ['hoTen', 'soDienThoai'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Trường $field không được để trống"
            ]);
            return;
        }
    }
    
    // Validate linked students
    $linkedStudents = $_POST['linkedStudents'] ?? [];
    $linkedStudents = array_filter($linkedStudents);
    
    if (empty($linkedStudents)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng chọn ít nhất một học sinh liên kết'
        ]);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Update parent info
        $sql = "UPDATE phuhuynh SET
                    hoTen = ?,
                    soDienThoai = ?,
                    email = ?,
                    diaChi = ?
                WHERE maPH = ?";
        
        // Assign to variables for bind_param
        $hoTen = $_POST['hoTen'];
        $soDienThoai = $_POST['soDienThoai'];
        $email = $_POST['email'] ?? null;
        $diaChi = $_POST['diaChi'] ?? null;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss',
            $hoTen,
            $soDienThoai,
            $email,
            $diaChi,
            $maPhuHuynh
        );
        $stmt->execute();
        
        // Remove parent from all students first
        $removeSql = "UPDATE hocsinh 
                     SET maPH = NULL
                     WHERE maPH = ?";
        $removeStmt = $conn->prepare($removeSql);
        $removeStmt->bind_param('s', $maPhuHuynh);
        $removeStmt->execute();
        
        // Add parent to selected students
        foreach ($linkedStudents as $maHocSinh) {
            $updateSql = "UPDATE hocsinh 
                         SET maPH = ?
                         WHERE maHS = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param('ss', $maPhuHuynh, $maHocSinh);
            $updateStmt->execute();
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật thông tin phụ huynh thành công'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi cập nhật: ' . $e->getMessage()
        ]);
    }
}

/**
 * Delete parents (single or multiple)
 */
function deleteParents() {
    global $conn;
    
    $parentIds = json_decode($_POST['parentIds'] ?? '[]', true);
    
    if (empty($parentIds)) {
        echo json_encode([
            'success' => false,
            'message' => 'Không có phụ huynh nào được chọn'
        ]);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Remove parent links from students
        foreach ($parentIds as $maPhuHuynh) {
            $removeSql = "UPDATE hocsinh 
                         SET maPH = NULL
                         WHERE maPH = ?";
            $removeStmt = $conn->prepare($removeSql);
            $removeStmt->bind_param('s', $maPhuHuynh);
            $removeStmt->execute();
        }
        
        // Delete parents
        $deleteSql = "DELETE FROM phuhuynh WHERE maPH IN (" . 
                    str_repeat('?,', count($parentIds) - 1) . "?)";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param(str_repeat('s', count($parentIds)), ...$parentIds);
        $deleteStmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Xóa thành công ' . count($parentIds) . ' phụ huynh'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi xóa: ' . $e->getMessage()
        ]);
    }
}
?>
