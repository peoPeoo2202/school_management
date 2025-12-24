<?php
/**
 * View: vEditSubjectTeacher.php
 * Sửa phân công Giáo viên Bộ môn
 */

// Kiểm tra xem được gọi từ controller hay không
if (!isset($assignment) || !isset($teachers)) {
    // Nếu gọi trực tiếp, redirect về trang danh sách
    header("Location: ../controller/cTeachingAssignment.php?action=subjectTeacherAssignment");
    exit();
}

// Session đã được khởi tạo từ controller, không cần gọi lại

// Kiểm tra đăng nhập và quyền
if (!isset($_SESSION['login']) || $_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    header("Location: ../public/index.php");
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa phân công GVBM - BGH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../teacher/style.css">
    <link rel="stylesheet" href="../teacher/style.css">
    <style>
        .info-group {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
      
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateBGH.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
        <div class="card">
        <div class="card-header">
            <h2>
                <i class="fas fa-edit"></i>
                Sửa phân công Giáo viên Bộ môn
            </h2>
        </div>
        <div class="card-body">
            <div class="info-group">
                <div class="info-item">
                    <span class="info-label">Học kỳ:</span>
                    <span class="info-value">
                        <span class="badge badge-primary">Học kỳ <?php echo $assignment['hocKy']; ?></span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Lớp:</span>
                    <span class="info-value"><?php echo htmlspecialchars($assignment['tenLop']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Môn học:</span>
                    <span class="info-value"><?php echo htmlspecialchars($assignment['tenMonHoc']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Năm học:</span>
                    <span class="info-value"><?php echo htmlspecialchars($assignment['namHoc']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">GV hiện tại:</span>
                    <span class="info-value"><?php echo htmlspecialchars($assignment['tenGV']); ?></span>
                </div>
            </div>

            <form action="../controller/cTeachingAssignment.php?action=updateSubjectTeacher" method="POST">
                <input type="hidden" name="maPhanCong" value="<?php echo $assignment['maPhanCong']; ?>">
                <input type="hidden" name="namHoc" value="<?php echo htmlspecialchars($assignment['namHoc']); ?>">
                
                <!-- Giữ lại các tham số bộ lọc -->
                <input type="hidden" name="returnMaKhoi" value="<?php echo $_GET['maKhoi'] ?? ''; ?>">
                <input type="hidden" name="returnMaLop" value="<?php echo $_GET['maLop'] ?? ''; ?>">
                <input type="hidden" name="returnMaMonHoc" value="<?php echo $_GET['maMonHoc'] ?? ''; ?>">
                <input type="hidden" name="returnHocKy" value="<?php echo $_GET['hocKy'] ?? ''; ?>">

                <div class="form-group">
                    <label for="maGV">
                        <i class="fas fa-user"></i> Chọn Giáo viên mới:
                    </label>
                    <select name="maGV" id="maGV" required>
                        <option value="">-- Chọn giáo viên --</option>
                        <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['maGV']; ?>" <?php echo $teacher['maGV'] == $assignment['maGV'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($teacher['hoTen']); ?> (<?php echo $teacher['toBoMon']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="btn-group">
                    <?php 
                    $cancelUrl = "../controller/cTeachingAssignment.php?action=subjectTeacherAssignment&search=1&namHoc=" . urlencode($assignment['namHoc']);
                    if (!empty($_GET['maKhoi'])) $cancelUrl .= "&maKhoi=" . $_GET['maKhoi'];
                    if (!empty($_GET['maLop'])) $cancelUrl .= "&maLop=" . $_GET['maLop'];
                    if (!empty($_GET['maMonHoc'])) $cancelUrl .= "&maMonHoc=" . $_GET['maMonHoc'];
                    if (!empty($_GET['hocKy'])) $cancelUrl .= "&hocKy=" . $_GET['hocKy'];
                    ?>
                    <a href="<?php echo $cancelUrl; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
        </div>
    </div>
</div>
</body>
</html>
