<?php
/**
 * CONTROLLER: Teacher Management
 * Handles CRUD operations for teacher information
 */

require_once '../model/mConnect.php';
require_once '../model/mTeacher.php';

header('Content-Type: application/json; charset=utf-8');

// Initialize database connection
$db = new mConnect();
$conn = $db->mConnect();

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listTeachers();
            break;
        
        case 'getDetail':
            getTeacherDetail();
            break;
        
        case 'create':
            createTeacher();
            break;
        
        case 'update':
            updateTeacher();
            break;
        
        case 'delete':
            deleteTeachers();
            break;
        
        case 'checkConstraints':
            checkTeacherConstraints();
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
 * List teachers with filters and pagination
 */
function listTeachers() {
    global $conn;
    
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($_GET['magv'])) {
        $where[] = "gv.maGV LIKE ?";
        $params[] = '%' . $_GET['magv'] . '%';
        $types .= 's';
    }
    
    if (!empty($_GET['hoten'])) {
        $where[] = "gv.hoTen LIKE ?";
        $params[] = '%' . $_GET['hoten'] . '%';
        $types .= 's';
    }
    
    if (!empty($_GET['tobomon'])) {
        $where[] = "gv.toBoMon = ?";
        $params[] = $_GET['tobomon'];
        $types .= 's';
    }
    
    if (!empty($_GET['gioitinh'])) {
        $where[] = "gv.gioiTinh = ?";
        $params[] = $_GET['gioitinh'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Count total records
    $countSql = "SELECT COUNT(*) as total FROM giaovien gv $whereClause";
    
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
    
    // Get teachers
    $sql = "SELECT gv.maGV as maGiaoVien, gv.hoTen, gv.gioiTinh, gv.toBoMon
            FROM giaovien gv
            $whereClause
            ORDER BY gv.hoTen
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
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'teachers' => $teachers,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);
}

/**
 * Get teacher detail
 */
function getTeacherDetail() {
    global $conn;
    
    $maGiaoVien = $_GET['maGiaoVien'] ?? '';
    
    if (empty($maGiaoVien)) {
        echo json_encode([
            'success' => false,
            'message' => 'Mã giáo viên không được để trống'
        ]);
        return;
    }
    
    $sql = "SELECT * FROM giaovien WHERE maGV = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $maGiaoVien);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy giáo viên'
        ]);
        return;
    }
    
    $teacher = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'teacher' => $teacher
    ]);
}

/**
 * Create new teacher
 */
function createTeacher() {
    global $conn;
    
    // Validate required fields
    $required = ['hoTen', 'gioiTinh', 'ngaySinh', 'boMon'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Trường $field không được để trống"
            ]);
            return;
        }
    }
    
    // Generate teacher ID
    $sql = "SELECT MAX(CAST(SUBSTRING(maGV, 3) AS UNSIGNED)) as maxId 
            FROM giaovien WHERE maGV LIKE 'GV%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $nextId = ($row['maxId'] ?? 0) + 1;
    $maGiaoVien = 'GV' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    
    // Insert teacher
    $sql = "INSERT INTO giaovien (
                maGV, hoTen, gioiTinh, ngaySinh, toBoMon,
                soDienThoai, email
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    // Assign to variables for bind_param
    $hoTen = $_POST['hoTen'];
    $gioiTinh = $_POST['gioiTinh'];
    $ngaySinh = $_POST['ngaySinh'];
    $toBoMon = $_POST['toBoMon'];
    $soDienThoai = $_POST['soDienThoai'] ?? null;
    $email = $_POST['email'] ?? null;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssss',
        $maGiaoVien,
        $hoTen,
        $gioiTinh,
        $ngaySinh,
        $toBoMon,
        $soDienThoai,
        $email
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Thêm giáo viên thành công',
            'maGiaoVien' => $maGiaoVien
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi thêm giáo viên: ' . $stmt->error
        ]);
    }
}

/**
 * Update teacher information
 */
function updateTeacher() {
    global $conn;
    
    $maGiaoVien = $_POST['maGiaoVien'] ?? '';
    
    if (empty($maGiaoVien)) {
        echo json_encode([
            'success' => false,
            'message' => 'Mã giáo viên không được để trống'
        ]);
        return;
    }
    
    // Validate required fields
    $required = ['hoTen', 'gioiTinh', 'ngaySinh', 'toBoMon'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Trường $field không được để trống"
            ]);
            return;
        }
    }
    
    $sql = "UPDATE giaovien SET
                hoTen = ?,
                gioiTinh = ?,
                ngaySinh = ?,
                toBoMon = ?,
                soDienThoai = ?,
                email = ?
            WHERE maGV = ?";
    
    // Assign to variables for bind_param
    $hoTen = $_POST['hoTen'];
    $gioiTinh = $_POST['gioiTinh'];
    $ngaySinh = $_POST['ngaySinh'];
    $toBoMon = $_POST['toBoMon'];
    $soDienThoai = $_POST['soDienThoai'] ?? null;
    $email = $_POST['email'] ?? null;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssss',
        $hoTen,
        $gioiTinh,
        $ngaySinh,
        $toBoMon,
        $soDienThoai,
        $email,
        $maGiaoVien
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật thông tin giáo viên thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi cập nhật: ' . $stmt->error
        ]);
    }
}

/**
 * Check if teacher has constraints (teaching assignments, etc.)
 */
function checkTeacherConstraints() {
    global $conn;
    
    $maGiaoVien = $_GET['maGiaoVien'] ?? '';
    
    if (empty($maGiaoVien)) {
        echo json_encode(['success' => true, 'hasConstraints' => false]);
        return;
    }
    
    // Check for teaching assignments
    $sql = "SELECT COUNT(*) as count FROM phancong WHERE maGiaoVien = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $maGiaoVien);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    echo json_encode([
        'success' => true,
        'hasConstraints' => $count > 0
    ]);
}

/**
 * Delete teachers (single or multiple)
 */
function deleteTeachers() {
    global $conn;
    
    $teacherIds = json_decode($_POST['teacherIds'] ?? '[]', true);
    
    if (empty($teacherIds)) {
        echo json_encode([
            'success' => false,
            'message' => 'Không có giáo viên nào được chọn'
        ]);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Check for constraints
        $checkSql = "SELECT COUNT(*) as count FROM phancong WHERE maGiaoVien IN (" . 
                    str_repeat('?,', count($teacherIds) - 1) . "?)";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param(str_repeat('s', count($teacherIds)), ...$teacherIds);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $assignmentCount = $checkResult->fetch_assoc()['count'];
        
        if ($assignmentCount > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể xóa giáo viên đã được phân công giảng dạy. Vui lòng xóa phân công trước.'
            ]);
            return;
        }
        
        // Delete related records if needed
        // (Add any related tables here)
        
        // Delete teachers
        $deleteSql = "DELETE FROM giaovien WHERE maGV IN (" . 
                    str_repeat('?,', count($teacherIds) - 1) . "?)";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param(str_repeat('s', count($teacherIds)), ...$teacherIds);
        $deleteStmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Xóa thành công ' . count($teacherIds) . ' giáo viên'
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
