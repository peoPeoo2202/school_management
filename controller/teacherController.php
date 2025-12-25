<?php
require_once(__DIR__ . '/../model/mTeacher.php');

class cTeacher {
    /**
     * Lấy thông tin lớp chủ nhiệm của giáo viên
     * Trả về array với các thông tin: tenLop, siSo, soNam, soNu
     */
    public function getInfoLopChuNhiem($maGV, $maNhom) {
        // Chỉ lấy thông tin nếu là giáo viên chủ nhiệm (maNhom === 3006)
        if ($maNhom != 3006 || empty($maGV)) {
            return null;
        }

        require_once(__DIR__ . '/../model/mConnect.php');
        $db = new mConnect();
        $conn = $db->mConnect();
        
        // Lấy thông tin lớp chủ nhiệm
        $query = "
            SELECT l.maLop, l.tenLop, l.siSo
            FROM lophoc l
            INNER JOIN phancong_gvcn p ON l.maLop = p.maLop
            WHERE p.maGV = ? AND p.trangThai = 'active' AND p.namHoc = '2024-2025'
            LIMIT 1
        ";
        
        $result = $conn->prepare($query);
        if (!$result) {
            return null;
        }
        
        $result->bind_param('i', $maGV);
        $result->execute();
        $row = $result->get_result()->fetch_assoc();
        $result->close();
        
        if (!$row) {
            return null;
        }

        // Đếm số nam và số nữ trong lớp
        $queryStats = "
            SELECT 
                SUM(CASE WHEN gioiTinh = 'Nam' THEN 1 ELSE 0 END) as soNam,
                SUM(CASE WHEN gioiTinh = 'Nu' THEN 1 ELSE 0 END) as soNu
            FROM hocsinh
            WHERE maLop = ? AND trangThaiHocTap = 'danghoc'
        ";
        
        $stmtStats = $conn->prepare($queryStats);
        if (!$stmtStats) {
            return $row; // Trả về thông tin lớp nếu query thống kê thất bại
        }
        
        $stmtStats->bind_param('i', $row['maLop']);
        $stmtStats->execute();
        $statsResult = $stmtStats->get_result()->fetch_assoc();
        $stmtStats->close();

        // Gộp dữ liệu thống kê vào thông tin lớp
        $row['soNam'] = $statsResult['soNam'] ?? 0;
        $row['soNu'] = $statsResult['soNu'] ?? 0;

        $conn->close();
        return $row;
    }

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

        include(__DIR__ . '/../view/teacher/index.php');
    }
}

// Gọi hàm hiển thị danh sách lớp
$controller = new cTeacher();
$controller->cViewClassList();
?>
