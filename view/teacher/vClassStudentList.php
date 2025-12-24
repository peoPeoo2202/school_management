<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

// Require model để lấy dữ liệu
require_once(__DIR__ . '/../../model/mTeacher.php');

$maGV = $_SESSION['maGV'] ?? null;
$maLop = $_GET['maLop'] ?? null;

if (!$maLop) {
    header("Location: ?action=viewClasses");
    exit();
}

$model = new mTeacher();

// Lấy thông tin lớp học
$classInfo = $model->getClassInfo($maLop);

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Số học sinh mỗi trang

// Lấy tổng số học sinh
$totalStudents = $model->countStudentsByClass($maLop);
$totalPages = ceil($totalStudents / $limit);

// Đảm bảo page hợp lệ
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Lấy danh sách học sinh với phân trang
$students = $model->getStudentsByClass($maLop, $page, $limit);

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách học sinh - Giáo viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <div class="header-section">
                <div class="header-left">
                    <h2>
                        <i class="fa-solid fa-users"></i>
                        Danh sách học sinh - Lớp <?php echo htmlspecialchars($classInfo['tenLop'] ?? ''); ?>
                    </h2>
                    <p>Xem danh sách học sinh của lớp học.</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <!-- Main Card -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">
                            <i class="fa-solid fa-info-circle"></i> 
                            Thông tin lớp học
                        </h2>
                    </div>
                    <a href="vClassList.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <?php if ($classInfo): ?>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Tên lớp:</span>
                            <span class="info-value"><?php echo htmlspecialchars($classInfo['tenLop']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Khối:</span>
                            <span class="info-value">Khối <?php echo htmlspecialchars($classInfo['khoiLop']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Sĩ số:</span>
                            <span class="info-value"><?php echo htmlspecialchars($classInfo['siSo']); ?> học sinh</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phòng học:</span>
                            <span class="info-value"><?php echo htmlspecialchars($classInfo['tenPhong'] ?? 'Chưa xếp'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">GVCN:</span>
                            <span class="info-value"><?php echo htmlspecialchars($classInfo['giaoVienChuNhiem']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Năm học:</span>
                            <span class="info-value"><?php echo htmlspecialchars($classInfo['namHoc']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Student List -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fa-solid fa-list-ul"></i> 
                        Danh sách học sinh (<?php echo $totalStudents; ?> học sinh)
                    </h2>
                </div>

                <?php if (!empty($students)): ?>
                    <table class="common-table">
                        <thead>
                            <tr>
                                <th class="small-cell">STT</th>
                                <th>Mã HS</th>
                                <th>Họ và tên</th>
                                <th>Ngày sinh</th>
                                <th class="center">Giới tính</th>
                                <th>Địa chỉ</th>
                                <th class="center">Trạng thái</th>
                            </tr>
                        </thead>

                        <tbody class="common-table-body">
                            <?php 
                            $startIndex = ($page - 1) * $limit;
                            foreach ($students as $index => $student): 
                            ?>
                                <tr>
                                    <!-- STT -->
                                    <td class="small-cell">
                                        <p><?php echo $startIndex + $index + 1; ?></p>
                                    </td>

                                    <!-- Mã HS -->
                                    <td>
                                        <span class="badge info">
                                            <?php echo htmlspecialchars($student['maHS']); ?>
                                        </span>
                                    </td>

                                    <!-- Họ tên -->
                                    <td>
                                        <div class="request-description">
                                            <span class="request-des-title">
                                                <?php echo htmlspecialchars($student['hoTen']); ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Ngày sinh -->
                                    <td>
                                        <?php 
                                        if ($student['ngaySinh']) {
                                            $date = new DateTime($student['ngaySinh']);
                                            echo $date->format('d/m/Y');
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>

                                    <!-- Giới tính -->
                                    <td class="center">
                                        <?php if ($student['gioiTinh'] == 'Nam'): ?>
                                            <i class="fas fa-mars" style="color: #3498db;"></i>
                                            Nam
                                        <?php else: ?>
                                            <i class="fas fa-venus" style="color: #e74c3c;"></i>
                                            Nữ
                                        <?php endif; ?>
                                    </td>

                                    <!-- Địa chỉ -->
                                    <td>
                                        <?php echo htmlspecialchars($student['diaChi'] ?? '-'); ?>
                                    </td>

                                    <!-- Trạng thái -->
                                    <td class="center">
                                        <?php if ($student['trangThaiHocTap'] == 'danghoc'): ?>
                                            <span class="badge completed">Đang học</span>
                                        <?php elseif ($student['trangThaiHocTap'] == 'nghihoc'): ?>
                                            <span class="badge pending">Nghỉ học</span>
                                        <?php else: ?>
                                            <span class="badge rejected">Đã thôi học</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination" style="display: flex; flex-direction: column; align-items: center; padding: 20px; border-top: 1px solid #e0e0e0; gap: 10px;">
                            <div class="pagination-controls" style="display: flex; gap: 8px; align-items: center;">
                                <!-- Trang đầu -->
                                <?php if ($page > 1): ?>
                                    <a href="?action=viewStudentList&maLop=<?php echo $maLop; ?>&page=1" 
                                       style="padding: 8px 12px; background: #5a9fd4; color: white; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; min-width: 36px; transition: background 0.3s;">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="padding: 8px 12px; background: #ccc; color: #888; border-radius: 5px; display: inline-flex; align-items: center; justify-content: center; min-width: 36px; cursor: not-allowed;">
                                        <i class="fas fa-angle-double-left"></i>
                                    </span>
                                <?php endif; ?>

                                <!-- Trang trước -->
                                <?php if ($page > 1): ?>
                                    <a href="?action=viewStudentList&maLop=<?php echo $maLop; ?>&page=<?php echo $page - 1; ?>" 
                                       style="padding: 8px 12px; background: #5a9fd4; color: white; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; min-width: 36px; transition: background 0.3s;">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="padding: 8px 12px; background: #ccc; color: #888; border-radius: 5px; display: inline-flex; align-items: center; justify-content: center; min-width: 36px; cursor: not-allowed;">
                                        <i class="fas fa-angle-left"></i>
                                    </span>
                                <?php endif; ?>

                                <?php
                                // Hiển thị các trang xung quanh trang hiện tại
                                $range = 2;
                                $start = max(1, $page - $range);
                                $end = min($totalPages, $page + $range);
                                
                                for ($i = $start; $i <= $end; $i++):
                                    $isActive = ($i == $page);
                                    $bgColor = $isActive ? '#2c5aa0' : '#5a9fd4';
                                ?>
                                    <a href="?action=viewStudentList&maLop=<?php echo $maLop; ?>&page=<?php echo $i; ?>" 
                                       style="padding: 8px 12px; background: <?php echo $bgColor; ?>; color: white; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; min-width: 36px; font-weight: <?php echo $isActive ? 'bold' : 'normal'; ?>; transition: background 0.3s;">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <!-- Trang sau -->
                                <?php if ($page < $totalPages): ?>
                                    <a href="?action=viewStudentList&maLop=<?php echo $maLop; ?>&page=<?php echo $page + 1; ?>" 
                                       style="padding: 8px 12px; background: #5a9fd4; color: white; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; min-width: 36px; transition: background 0.3s;">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="padding: 8px 12px; background: #ccc; color: #888; border-radius: 5px; display: inline-flex; align-items: center; justify-content: center; min-width: 36px; cursor: not-allowed;">
                                        <i class="fas fa-angle-right"></i>
                                    </span>
                                <?php endif; ?>

                                <!-- Trang cuối -->
                                <?php if ($page < $totalPages): ?>
                                    <a href="?action=viewStudentList&maLop=<?php echo $maLop; ?>&page=<?php echo $totalPages; ?>" 
                                       style="padding: 8px 12px; background: #5a9fd4; color: white; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; min-width: 36px; transition: background 0.3s;">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="padding: 8px 12px; background: #ccc; color: #888; border-radius: 5px; display: inline-flex; align-items: center; justify-content: center; min-width: 36px; cursor: not-allowed;">
                                        <i class="fas fa-angle-double-right"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="pagination-info" style="color: #666; font-size: 14px; text-align: center;">
                                Trang <?php echo $page; ?> / <?php echo $totalPages; ?> 
                                (Hiển thị <?php echo count($students); ?> / <?php echo $totalStudents; ?> học sinh)
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Chưa có học sinh nào</h3>
                        <p>Lớp học này chưa có học sinh</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>
