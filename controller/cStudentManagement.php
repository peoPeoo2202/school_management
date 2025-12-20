<?php
/**
 * CONTROLLER: Student Management
 * Handles CRUD operations for student information
 */

require_once '../model/mConnect.php';
require_once '../model/mStudent.php';

header('Content-Type: application/json; charset=utf-8');

// Initialize database connection
$db = new mConnect();
$conn = $db->mConnect();

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listStudents();
            break;
        
        case 'getDetail':
            getStudentDetail();
            break;
        
        case 'getLopByKhoi':
            getLopByKhoi();
            break;
        
        case 'create':
            createStudent();
            break;
        
        case 'update':
            updateStudent();
            break;
        
        case 'delete':
            deleteStudents();
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
 * List students with filters and pagination
 */
function listStudents() {
    global $conn;
    
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($_GET['khoi'])) {
        $where[] = "l.maKhoi = ?";
        $params[] = $_GET['khoi'];
        $types .= 'i';
    }
    
    if (!empty($_GET['lop'])) {
        $where[] = "hs.maLop = ?";
        $params[] = $_GET['lop'];
        $types .= 's';
    }
    
    if (!empty($_GET['gioitinh'])) {
        $where[] = "hs.gioiTinh = ?";
        $params[] = $_GET['gioitinh'];
        $types .= 's';
    }
    
    if (!empty($_GET['mahs'])) {
        $where[] = "hs.maHS LIKE ?";
        $params[] = '%' . $_GET['mahs'] . '%';
        $types .= 's';
    }
    
    if (!empty($_GET['hoten'])) {
        $where[] = "hs.hoTen LIKE ?";
        $params[] = '%' . $_GET['hoten'] . '%';
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Count total records
    $countSql = "SELECT COUNT(*) as total 
                 FROM hocsinh hs 
                 LEFT JOIN lophoc l ON hs.maLop = l.maLop
                 LEFT JOIN khoi k ON l.maKhoi = k.maKhoi 
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
    
    // Get students
    $sql = "SELECT hs.maHS as maHocSinh, hs.hoTen, hs.gioiTinh, hs.ngaySinh, 
                   l.tenLop, k.khoiLop as khoi
            FROM hocsinh hs
            LEFT JOIN lophoc l ON hs.maLop = l.maLop
            LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
            $whereClause
            ORDER BY k.khoiLop, l.tenLop, hs.hoTen
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
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);
}

/**
 * Get student detail with related data
 */
function getStudentDetail() {
    global $conn;
    
    $maHocSinh = $_GET['maHocSinh'] ?? '';
    
    if (empty($maHocSinh)) {
        echo json_encode([
            'success' => false,
            'message' => 'Mã học sinh không được để trống'
        ]);
        return;
    }
    
    // Get student basic info
    $sql = "SELECT hs.*, l.tenLop, l.maKhoi, k.khoiLop as khoi
            FROM hocsinh hs
            LEFT JOIN lophoc l ON hs.maLop = l.maLop
            LEFT JOIN khoi k ON l.maKhoi = k.maKhoi
            WHERE hs.maHS = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $maHocSinh);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy học sinh'
        ]);
        return;
    }
    
    $student = $result->fetch_assoc();
    
    // Get academic records (if table exists)
    $academic = [];
    // TODO: Implement academic records query based on your schema
    
    // Get awards (khen thưởng)
    $awards = [];
    $awardsSql = "SELECT * FROM khenthuong WHERE maHS = ? ORDER BY ngayKhenThuong DESC";
    $awardsStmt = $conn->prepare($awardsSql);
    $awardsStmt->bind_param('s', $maHocSinh);
    $awardsStmt->execute();
    $awardsResult = $awardsStmt->get_result();
    while ($row = $awardsResult->fetch_assoc()) {
        $awards[] = $row;
    }
    
    // Get violations (vi phạm)
    $violations = [];
    $violationsSql = "SELECT * FROM vipham WHERE maHS = ? ORDER BY ngayViPham DESC";
    $violationsStmt = $conn->prepare($violationsSql);
    $violationsStmt->bind_param('s', $maHocSinh);
    $violationsStmt->execute();
    $violationsResult = $violationsStmt->get_result();
    while ($row = $violationsResult->fetch_assoc()) {
        $violations[] = $row;
    }
    
    // Get absences (nghỉ học)
    $absences = [];
    $absencesSql = "SELECT * FROM nghihoc WHERE maHS = ? ORDER BY ngayNghi DESC";
    $absencesStmt = $conn->prepare($absencesSql);
    $absencesStmt->bind_param('s', $maHocSinh);
    $absencesStmt->execute();
    $absencesResult = $absencesStmt->get_result();
    while ($row = $absencesResult->fetch_assoc()) {
        $absences[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'student' => $student,
        'academic' => $academic,
        'awards' => $awards,
        'violations' => $violations,
        'absences' => $absences
    ]);
}

/**
 * Get classes by grade (khối)
 */
function getLopByKhoi() {
    global $conn;
    
    $maKhoi = $_GET['khoi'] ?? '';
    
    if (empty($maKhoi)) {
        echo json_encode([
            'success' => false,
            'message' => 'Khối không được để trống'
        ]);
        return;
    }
    
    $sql = "SELECT maLop, tenLop FROM lophoc WHERE maKhoi = ? ORDER BY tenLop";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $maKhoi);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'classes' => $classes
    ]);
}

/**
 * Create new student
 */
function createStudent() {
    global $conn;
    
    // Validate required fields
    $required = ['hoTen', 'gioiTinh', 'ngaySinh', 'maLop', 'tinhThanh', 'ngayVaoTruong'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Trường $field không được để trống"
            ]);
            return;
        }
    }
    
    // Generate student ID (auto-increment or custom logic)
    $sql = "SELECT MAX(CAST(SUBSTRING(maHS, 3) AS UNSIGNED)) as maxId 
            FROM hocsinh WHERE maHS LIKE 'HS%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $nextId = ($row['maxId'] ?? 0) + 1;
    $maHocSinh = 'HS' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    
    // Insert student
    $sql = "INSERT INTO hocsinh (
                maHS, hoTen, gioiTinh, ngaySinh, maLop, 
                tinhThanh, xaPhuong, ngayVaoTruong, trangThai,
                danToc, sdtNha, sdtDiDong, 
                hoTenCha, ngheNghiepCha, hoTenMe, ngheNghiepMe
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssssssssssss',
        $maHocSinh,
        $_POST['hoTen'],
        $_POST['gioiTinh'],
        $_POST['ngaySinh'],
        $_POST['maLop'],
        $_POST['tinhThanh'],
        $_POST['xaPhuong'] ?? null,
        $_POST['ngayVaoTruong'],
        $_POST['trangThai'] ?? 'Đang học',
        $_POST['danToc'] ?? null,
        $_POST['sdtNha'] ?? null,
        $_POST['sdtDiDong'] ?? null,
        $_POST['hoTenCha'] ?? null,
        $_POST['ngheNghiepCha'] ?? null,
        $_POST['hoTenMe'] ?? null,
        $_POST['ngheNghiepMe'] ?? null
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Thêm học sinh thành công',
            'maHocSinh' => $maHocSinh
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi thêm học sinh: ' . $stmt->error
        ]);
    }
}

/**
 * Update student information
 */
function updateStudent() {
    global $conn;
    
    $maHocSinh = $_POST['maHocSinh'] ?? '';
    
    if (empty($maHocSinh)) {
        echo json_encode([
            'success' => false,
            'message' => 'Mã học sinh không được để trống'
        ]);
        return;
    }
    
    // Validate required fields
    $required = ['hoTen', 'gioiTinh', 'ngaySinh', 'maLop', 'tinhThanh', 'ngayVaoTruong'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Trường $field không được để trống"
            ]);
            return;
        }
    }
    
    $sql = "UPDATE hocsinh SET
                hoTen = ?,
                gioiTinh = ?,
                ngaySinh = ?,
                maLop = ?,
                tinhThanh = ?,
                xaPhuong = ?,
                ngayVaoTruong = ?,
                trangThai = ?,
                danToc = ?,
                sdtNha = ?,
                sdtDiDong = ?,
                hoTenCha = ?,
                ngheNghiepCha = ?,
                hoTenMe = ?,
                ngheNghiepMe = ?
            WHERE maHS = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssssssssssss',
        $_POST['hoTen'],
        $_POST['gioiTinh'],
        $_POST['ngaySinh'],
        $_POST['maLop'],
        $_POST['tinhThanh'],
        $_POST['xaPhuong'] ?? null,
        $_POST['ngayVaoTruong'],
        $_POST['trangThai'] ?? 'Đang học',
        $_POST['danToc'] ?? null,
        $_POST['sdtNha'] ?? null,
        $_POST['sdtDiDong'] ?? null,
        $_POST['hoTenCha'] ?? null,
        $_POST['ngheNghiepCha'] ?? null,
        $_POST['hoTenMe'] ?? null,
        $_POST['ngheNghiepMe'] ?? null,
        $maHocSinh
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật thông tin học sinh thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi cập nhật: ' . $stmt->error
        ]);
    }
}

/**
 * Delete students (single or multiple)
 */
function deleteStudents() {
    global $conn;
    
    $studentIds = json_decode($_POST['studentIds'] ?? '[]', true);
    
    if (empty($studentIds)) {
        echo json_encode([
            'success' => false,
            'message' => 'Không có học sinh nào được chọn'
        ]);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Check for constraints (e.g., grades, awards, etc.)
        $checkSql = "SELECT COUNT(*) as count FROM diem WHERE maHS IN (" . 
                    str_repeat('?,', count($studentIds) - 1) . "?)";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param(str_repeat('s', count($studentIds)), ...$studentIds);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $gradeCount = $checkResult->fetch_assoc()['count'];
        
        if ($gradeCount > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể xóa học sinh đã có điểm trong hệ thống'
            ]);
            return;
        }
        
        // Delete related records first
        $tables = ['khenthuong', 'vipham', 'nghihoc', 'diemdanh'];
        foreach ($tables as $table) {
            $deleteSql = "DELETE FROM $table WHERE maHS IN (" . 
                        str_repeat('?,', count($studentIds) - 1) . "?)";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param(str_repeat('s', count($studentIds)), ...$studentIds);
            $deleteStmt->execute();
        }
        
        // Delete students
        $deleteSql = "DELETE FROM hocsinh WHERE maHS IN (" . 
                    str_repeat('?,', count($studentIds) - 1) . "?)";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param(str_repeat('s', count($studentIds)), ...$studentIds);
        $deleteStmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Xóa thành công ' . count($studentIds) . ' học sinh'
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
