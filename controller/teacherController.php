<?php
session_start();
include_once("../model/mTeacher.php");

class cTeacher {
    public function cViewClassList() {
        if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "giaovien") {
            header("Location: ../login.php");
            exit;
        }

        $teacherModel = new mTeacher();

        // Lấy thông tin giáo viên theo tài khoản đăng nhập
        $info = $teacherModel->getTeacherInfoByAccount($_SESSION["tenDangNhap"]);

        // Nếu không có dữ liệu giáo viên
        if (!$info) {
            echo "<p style='color:red;'>Không tìm thấy thông tin giáo viên.</p>";
            exit;
        }

        // Lấy danh sách lớp
        $classes = $teacherModel->getClassListByTeacher($info["maGV"]);

        include("../view/teacher/index.php");
    }
}

// Gọi hàm hiển thị danh sách lớp
$controller = new cTeacher();
$controller->cViewClassList();
?>
