<?php
/**
 * Controller for Schedule Management
 * Quản lý Thời khóa biểu và Lịch thi
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../model/mConnect.php';

// Create database connection
$dbObj = new mConnect();
$db = $dbObj->mConnect();

if (!$db) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi kết nối database'
    ]);
    exit;
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'getLopByKhoi':
            getLopByKhoi($db);
            break;
        case 'getTimetable':
            getTimetable($db);
            break;
        case 'getExamInfo':
            getExamInfo($db);
            break;
        case 'getExamSchedule':
            getExamSchedule($db);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'createLesson':
            createLesson($db);
            break;
        case 'updateLesson':
            updateLesson($db);
            break;
        case 'deleteLesson':
            deleteLesson($db);
            break;
        case 'createExamSchedule':
            createExamSchedule($db);
            break;
        case 'updateExamSchedule':
            updateExamSchedule($db);
            break;
        case 'deleteExamSchedule':
            deleteExamSchedule($db);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Get Lop list by Khoi
 */
function getLopByKhoi($db) {
    $maKhoi = intval($_GET['maKhoi'] ?? 0);
    
    if (!$maKhoi) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã khối']);
        return;
    }
    
    $query = "SELECT maLop, tenLop FROM lophoc WHERE maKhoi = ? ORDER BY tenLop";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $maKhoi);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Get Timetable for a class
 */
function getTimetable($db) {
    $maLop = intval($_GET['maLop'] ?? 0);
    $hocKy = intval($_GET['hocKy'] ?? 0);
    $namHoc = $_GET['namHoc'] ?? '';
    
    if (!$maLop || !$hocKy || !$namHoc) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin lọc']);
        return;
    }
    
    $query = "SELECT ld.*, mh.tenMonHoc, gv.hoTen as tenGV, p.tenPhong
              FROM lichday ld
              LEFT JOIN monhoc mh ON ld.maMonHoc = mh.maMonHoc
              LEFT JOIN giaovien gv ON ld.maGV = gv.maGV
              LEFT JOIN phong p ON ld.maPhong = p.maPhong
              WHERE ld.maLop = ? AND ld.hocKy = ? AND ld.namHoc = ? AND ld.trangThai = 'active'
              ORDER BY ld.thu, ld.tietBatDau";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("iis", $maLop, $hocKy, $namHoc);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Create new lesson
 */
function createLesson($db) {
    $maLop = intval($_POST['maLop'] ?? 0);
    $maGV = intval($_POST['maGV'] ?? 0);
    $maMonHoc = intval($_POST['maMonHoc'] ?? 0);
    $maPhong = intval($_POST['maPhong'] ?? 0);
    $thu = intval($_POST['thu'] ?? 0);
    $tietBatDau = intval($_POST['tietBatDau'] ?? 0);
    $tietKetThuc = intval($_POST['tietKetThuc'] ?? 0);
    $hocKy = intval($_POST['hocKy'] ?? 0);
    $namHoc = $_POST['namHoc'] ?? '';
    
    // Validate required fields
    if (!$maLop || !$maGV || !$maMonHoc || !$thu || !$tietBatDau || !$tietKetThuc || !$hocKy || !$namHoc) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        return;
    }
    
    // Check room conflict
    if ($maPhong) {
        $conflictRoom = checkRoomConflict($db, $maPhong, $thu, $tietBatDau, $tietKetThuc, $hocKy, $namHoc);
        if ($conflictRoom) {
            echo json_encode(['success' => false, 'message' => 'Phòng đã được sử dụng trong khung giờ này']);
            return;
        }
    }
    
    // Check teacher conflict
    $conflictTeacher = checkTeacherConflict($db, $maGV, $thu, $tietBatDau, $tietKetThuc, $hocKy, $namHoc);
    if ($conflictTeacher) {
        echo json_encode(['success' => false, 'message' => 'Giáo viên đã có lịch dạy trong khung giờ này']);
        return;
    }
    
    // Check class conflict
    $conflictClass = checkClassConflict($db, $maLop, $thu, $tietBatDau, $tietKetThuc, $hocKy, $namHoc);
    if ($conflictClass) {
        echo json_encode(['success' => false, 'message' => 'Lớp đã có tiết học trong khung giờ này']);
        return;
    }
    
    // Insert
    $query = "INSERT INTO lichday (maGV, maLop, maMonHoc, maPhong, thu, tietBatDau, tietKetThuc, hocKy, namHoc, trangThai)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $db->prepare($query);
    $stmt->bind_param("iiiiiiiss", $maGV, $maLop, $maMonHoc, $maPhong, $thu, $tietBatDau, $tietKetThuc, $hocKy, $namHoc);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Thêm tiết học thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm tiết học: ' . $db->error]);
    }
}

/**
 * Update lesson
 */
function updateLesson($db) {
    $maLichDay = intval($_POST['maLichDay'] ?? 0);
    $maGV = intval($_POST['maGV'] ?? 0);
    $maMonHoc = intval($_POST['maMonHoc'] ?? 0);
    $maPhong = intval($_POST['maPhong'] ?? 0);
    $thu = intval($_POST['thu'] ?? 0);
    $tietBatDau = intval($_POST['tietBatDau'] ?? 0);
    $tietKetThuc = intval($_POST['tietKetThuc'] ?? 0);
    
    if (!$maLichDay) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã lịch dạy']);
        return;
    }
    
    // Get existing lesson info
    $query = "SELECT * FROM lichday WHERE maLichDay = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $maLichDay);
    $stmt->execute();
    $result = $stmt->get_result();
    $lesson = $result->fetch_assoc();
    
    if (!$lesson) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tiết học']);
        return;
    }
    
    // Check room conflict (exclude current lesson)
    if ($maPhong) {
        $conflictRoom = checkRoomConflict($db, $maPhong, $thu, $tietBatDau, $tietKetThuc, $lesson['hocKy'], $lesson['namHoc'], $maLichDay);
        if ($conflictRoom) {
            echo json_encode(['success' => false, 'message' => 'Phòng đã được sử dụng trong khung giờ này']);
            return;
        }
    }
    
    // Check teacher conflict (exclude current lesson)
    $conflictTeacher = checkTeacherConflict($db, $maGV, $thu, $tietBatDau, $tietKetThuc, $lesson['hocKy'], $lesson['namHoc'], $maLichDay);
    if ($conflictTeacher) {
        echo json_encode(['success' => false, 'message' => 'Giáo viên đã có lịch dạy trong khung giờ này']);
        return;
    }
    
    // Check class conflict (exclude current lesson)
    $conflictClass = checkClassConflict($db, $lesson['maLop'], $thu, $tietBatDau, $tietKetThuc, $lesson['hocKy'], $lesson['namHoc'], $maLichDay);
    if ($conflictClass) {
        echo json_encode(['success' => false, 'message' => 'Lớp đã có tiết học trong khung giờ này']);
        return;
    }
    
    // Update
    $query = "UPDATE lichday SET maGV = ?, maMonHoc = ?, maPhong = ?, thu = ?, tietBatDau = ?, tietKetThuc = ? WHERE maLichDay = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("iiiiiii", $maGV, $maMonHoc, $maPhong, $thu, $tietBatDau, $tietKetThuc, $maLichDay);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật tiết học thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . $db->error]);
    }
}

/**
 * Delete lesson
 */
function deleteLesson($db) {
    $maLichDay = intval($_POST['maLichDay'] ?? 0);
    
    if (!$maLichDay) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã lịch dạy']);
        return;
    }
    
    // Soft delete - set status to inactive
    $query = "UPDATE lichday SET trangThai = 'inactive' WHERE maLichDay = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $maLichDay);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Xóa tiết học thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa: ' . $db->error]);
    }
}

/**
 * Check room conflict
 */
function checkRoomConflict($db, $maPhong, $thu, $tietBatDau, $tietKetThuc, $hocKy, $namHoc, $excludeId = null) {
    $query = "SELECT COUNT(*) as count FROM lichday 
              WHERE maPhong = ? AND thu = ? AND hocKy = ? AND namHoc = ? AND trangThai = 'active'
              AND NOT (tietKetThuc < ? OR tietBatDau > ?)";
    
    if ($excludeId) {
        $query .= " AND maLichDay != ?";
    }
    
    $stmt = $db->prepare($query);
    
    if ($excludeId) {
        $stmt->bind_param("iiisiiii", $maPhong, $thu, $hocKy, $namHoc, $tietBatDau, $tietKetThuc, $excludeId);
    } else {
        $stmt->bind_param("iiisii", $maPhong, $thu, $hocKy, $namHoc, $tietBatDau, $tietKetThuc);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

/**
 * Check teacher conflict
 */
function checkTeacherConflict($db, $maGV, $thu, $tietBatDau, $tietKetThuc, $hocKy, $namHoc, $excludeId = null) {
    $query = "SELECT COUNT(*) as count FROM lichday 
              WHERE maGV = ? AND thu = ? AND hocKy = ? AND namHoc = ? AND trangThai = 'active'
              AND NOT (tietKetThuc < ? OR tietBatDau > ?)";
    
    if ($excludeId) {
        $query .= " AND maLichDay != ?";
    }
    
    $stmt = $db->prepare($query);
    
    if ($excludeId) {
        $stmt->bind_param("iiisiiii", $maGV, $thu, $hocKy, $namHoc, $tietBatDau, $tietKetThuc, $excludeId);
    } else {
        $stmt->bind_param("iiisii", $maGV, $thu, $hocKy, $namHoc, $tietBatDau, $tietKetThuc);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

/**
 * Check class conflict
 */
function checkClassConflict($db, $maLop, $thu, $tietBatDau, $tietKetThuc, $hocKy, $namHoc, $excludeId = null) {
    $query = "SELECT COUNT(*) as count FROM lichday 
              WHERE maLop = ? AND thu = ? AND hocKy = ? AND namHoc = ? AND trangThai = 'active'
              AND NOT (tietKetThuc < ? OR tietBatDau > ?)";
    
    if ($excludeId) {
        $query .= " AND maLichDay != ?";
    }
    
    $stmt = $db->prepare($query);
    
    if ($excludeId) {
        $stmt->bind_param("iiisiiii", $maLop, $thu, $hocKy, $namHoc, $tietBatDau, $tietKetThuc, $excludeId);
    } else {
        $stmt->bind_param("iiisii", $maLop, $thu, $hocKy, $namHoc, $tietBatDau, $tietKetThuc);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

/**
 * Get Exam Info
 */
function getExamInfo($db) {
    $maKyThi = intval($_GET['maKyThi'] ?? 0);
    
    if (!$maKyThi) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã kỳ thi']);
        return;
    }
    
    $query = "SELECT kt.*, k.khoiLop 
              FROM kythi kt
              LEFT JOIN khoi k ON kt.maKhoi = k.maKhoi
              WHERE kt.maKyThi = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $maKyThi);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if ($data) {
        // Convert status for display
        $statusMap = [
            'Dang_lap' => 'Đang lập',
            'Dang_thi' => 'Đang diễn ra',
            'Ket_thuc' => 'Hoàn thành'
        ];
        $data['trangThai'] = $statusMap[$data['trangThai']] ?? $data['trangThai'];
        
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy kỳ thi']);
    }
}

/**
 * Get Exam Schedule
 */
function getExamSchedule($db) {
    $maKyThi = intval($_GET['maKyThi'] ?? 0);
    $maMonHoc = intval($_GET['maMonHoc'] ?? 0);
    
    if (!$maKyThi) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã kỳ thi']);
        return;
    }
    
    // Check if lichthi table exists, if not create it
    $tableCheck = $db->query("SHOW TABLES LIKE 'lichthi'");
    if ($tableCheck->num_rows == 0) {
        // Create lichthi table
        $createTable = "CREATE TABLE `lichthi` (
            `maLichThi` int(11) NOT NULL AUTO_INCREMENT,
            `maKyThi` int(11) NOT NULL,
            `maMonHoc` int(11) NOT NULL,
            `ngayThi` date NOT NULL,
            `gioBatDau` time NOT NULL,
            `gioKetThuc` time NOT NULL,
            `maPhong` int(11) DEFAULT NULL,
            `maGVCoiThi` varchar(255) DEFAULT NULL COMMENT 'Comma-separated list of teacher IDs',
            `ghiChu` text DEFAULT NULL,
            `trangThai` varchar(50) DEFAULT 'active',
            `ngayTao` datetime DEFAULT current_timestamp(),
            `ngayCapNhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`maLichThi`),
            KEY `maKyThi` (`maKyThi`),
            KEY `maMonHoc` (`maMonHoc`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $db->query($createTable);
    }
    
    $query = "SELECT lt.*, mh.tenMonHoc, p.tenPhong,
              (SELECT GROUP_CONCAT(gv.hoTen SEPARATOR ', ') 
               FROM giaovien gv 
               WHERE FIND_IN_SET(gv.maGV, lt.maGVCoiThi)) as giaoVienCoiThi
              FROM lichthi lt
              LEFT JOIN monhoc mh ON lt.maMonHoc = mh.maMonHoc
              LEFT JOIN phong p ON lt.maPhong = p.maPhong
              WHERE lt.maKyThi = ? AND lt.trangThai = 'active'";
    
    if ($maMonHoc) {
        $query .= " AND lt.maMonHoc = ?";
    }
    
    $query .= " ORDER BY lt.ngayThi, lt.gioBatDau";
    
    $stmt = $db->prepare($query);
    
    if ($maMonHoc) {
        $stmt->bind_param("ii", $maKyThi, $maMonHoc);
    } else {
        $stmt->bind_param("i", $maKyThi);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Create Exam Schedule
 */
function createExamSchedule($db) {
    $maKyThi = intval($_POST['maKyThi'] ?? 0);
    $maMonHoc = intval($_POST['maMonHoc'] ?? 0);
    $ngayThi = $_POST['ngayThi'] ?? '';
    $gioBatDau = $_POST['gioBatDau'] ?? '';
    $gioKetThuc = $_POST['gioKetThuc'] ?? '';
    $maPhong = intval($_POST['maPhong'] ?? 0);
    $maGVCoiThi = $_POST['maGVCoiThi'] ?? '';
    $ghiChu = $_POST['ghiChu'] ?? '';
    
    if (!$maKyThi || !$maMonHoc || !$ngayThi || !$gioBatDau || !$gioKetThuc) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
        return;
    }
    
    // Check room conflict for exam
    if ($maPhong) {
        $conflictRoom = checkExamRoomConflict($db, $maPhong, $ngayThi, $gioBatDau, $gioKetThuc);
        if ($conflictRoom) {
            echo json_encode(['success' => false, 'message' => 'Phòng đã được sử dụng trong khung giờ này']);
            return;
        }
    }
    
    $query = "INSERT INTO lichthi (maKyThi, maMonHoc, ngayThi, gioBatDau, gioKetThuc, maPhong, maGVCoiThi, ghiChu, trangThai)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $db->prepare($query);
    
    $maPhongVal = $maPhong ?: null;
    $stmt->bind_param("iisssiss", $maKyThi, $maMonHoc, $ngayThi, $gioBatDau, $gioKetThuc, $maPhongVal, $maGVCoiThi, $ghiChu);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Thêm lịch thi thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm lịch thi: ' . $db->error]);
    }
}

/**
 * Update Exam Schedule
 */
function updateExamSchedule($db) {
    $maLichThi = intval($_POST['maLichThi'] ?? 0);
    $maMonHoc = intval($_POST['maMonHoc'] ?? 0);
    $ngayThi = $_POST['ngayThi'] ?? '';
    $gioBatDau = $_POST['gioBatDau'] ?? '';
    $gioKetThuc = $_POST['gioKetThuc'] ?? '';
    $maPhong = intval($_POST['maPhong'] ?? 0);
    $maGVCoiThi = $_POST['maGVCoiThi'] ?? '';
    $ghiChu = $_POST['ghiChu'] ?? '';
    
    if (!$maLichThi) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã lịch thi']);
        return;
    }
    
    // Check room conflict for exam (exclude current)
    if ($maPhong) {
        $conflictRoom = checkExamRoomConflict($db, $maPhong, $ngayThi, $gioBatDau, $gioKetThuc, $maLichThi);
        if ($conflictRoom) {
            echo json_encode(['success' => false, 'message' => 'Phòng đã được sử dụng trong khung giờ này']);
            return;
        }
    }
    
    $query = "UPDATE lichthi SET maMonHoc = ?, ngayThi = ?, gioBatDau = ?, gioKetThuc = ?, maPhong = ?, maGVCoiThi = ?, ghiChu = ? WHERE maLichThi = ?";
    $stmt = $db->prepare($query);
    
    $maPhongVal = $maPhong ?: null;
    $stmt->bind_param("isssissi", $maMonHoc, $ngayThi, $gioBatDau, $gioKetThuc, $maPhongVal, $maGVCoiThi, $ghiChu, $maLichThi);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật lịch thi thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . $db->error]);
    }
}

/**
 * Delete Exam Schedule
 */
function deleteExamSchedule($db) {
    $maLichThi = intval($_POST['maLichThi'] ?? 0);
    
    if (!$maLichThi) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã lịch thi']);
        return;
    }
    
    // Soft delete
    $query = "UPDATE lichthi SET trangThai = 'inactive' WHERE maLichThi = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $maLichThi);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Xóa lịch thi thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa: ' . $db->error]);
    }
}

/**
 * Check exam room conflict
 */
function checkExamRoomConflict($db, $maPhong, $ngayThi, $gioBatDau, $gioKetThuc, $excludeId = null) {
    $query = "SELECT COUNT(*) as count FROM lichthi 
              WHERE maPhong = ? AND ngayThi = ? AND trangThai = 'active'
              AND NOT (gioKetThuc <= ? OR gioBatDau >= ?)";
    
    if ($excludeId) {
        $query .= " AND maLichThi != ?";
    }
    
    $stmt = $db->prepare($query);
    
    if ($excludeId) {
        $stmt->bind_param("isssi", $maPhong, $ngayThi, $gioBatDau, $gioKetThuc, $excludeId);
    } else {
        $stmt->bind_param("isss", $maPhong, $ngayThi, $gioBatDau, $gioKetThuc);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

// Close connection
$dbObj->mDisconnect($db);
?>
