<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

include_once("../model/mStudent.php");

// Kiểm tra đăng nhập
if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "hocsinh") {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$model = new mStudent();
$info = $model->getStudentInfoByAccount($_SESSION["tenDangNhap"]);

if (!$info) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin học sinh']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getYears':
        $years = $model->getAvailableYears($info['maHS']);
        echo json_encode([
            'success' => true, 
            'data' => $years,
            'debug' => [
                'maHS' => $info['maHS'],
                'count' => count($years)
            ]
        ]);
        break;
        
    case 'getSemesters':
        $namHoc = $_GET['namHoc'] ?? '';
        if (empty($namHoc)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu năm học']);
            exit;
        }
        $semesters = $model->getAvailableSemesters($info['maHS'], $namHoc);
        echo json_encode([
            'success' => true, 
            'data' => $semesters,
            'debug' => [
                'maHS' => $info['maHS'],
                'namHoc' => $namHoc,
                'count' => count($semesters)
            ]
        ]);
        break;
        
    case 'getGrades':
        $namHoc = $_GET['namHoc'] ?? '';
        $hocKy = $_GET['hocKy'] ?? '';
        
        if (empty($namHoc) || empty($hocKy)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
            exit;
        }
        
        $grades = $model->getDetailedGrades($info['maHS'], $namHoc, (int)$hocKy);
        echo json_encode([
            'success' => true, 
            'data' => $grades
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}
?>
