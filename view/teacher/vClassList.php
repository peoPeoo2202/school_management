<?php
session_start();

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

// Nếu $data không được định nghĩa, khởi tạo nó
if (!isset($data)) {
    $model = new mTeacher();
    
    // Lấy filter từ GET parameters
    $filters = [
        'hocKy' => $_GET['hocKy'] ?? '',
        'namHoc' => $_GET['namHoc'] ?? '',
        'maKhoi' => $_GET['maKhoi'] ?? ''
    ];

    // Lấy danh sách lớp chi tiết của giáo viên với filter
    $danhSachLop = $model->getDetailedClassListByTeacher($maGV, $filters);
    
    // Lấy danh sách khối từ database
    $danhSachKhoi = $model->getAllKhoi();

    $data = [
        'classes' => [
            'success' => !empty($danhSachLop),
            'data' => $danhSachLop ?? []
        ],
        'khoi' => $danhSachKhoi,
        'filters' => $filters
    ];
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách lớp - Giáo viên</title>
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
                <div class="header-left ">
                    <h2>
                        <i class="fa-solid fa-clipboard-user"></i>
                        Danh sách lớp
                    </h2>
                    <p>Xem và quản lý danh sách lớp học của bạn.</p>
                </div>
                <!-- <div class="header-left-icon"> -->

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <!-- Main Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-list-ul"></i> Danh sách lớp học đang giảng dạy</h2>
                </div>

                <!-- Filter Section -->
                <form method="GET" action="">
                    <input type="hidden" name="action" value="viewClasses">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Học kỳ</label>
                            <select name="hocKy">
                                <option value="">Tất cả</option>
                                <option value="1" <?php echo ($data['filters']['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo ($data['filters']['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Năm học</label>
                            <select name="namHoc">
                                <option value="">Tất cả</option>
                                <option value="2024-2025" <?php echo ($data['filters']['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option>
                                <option value="2023-2024" <?php echo ($data['filters']['namHoc'] == '2023-2024') ? 'selected' : ''; ?>>2023-2024</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Khối</label>
                            <select name="maKhoi">
                                <option value="">Tất cả</option>
                                <?php if (!empty($data['khoi'])): ?>
                                    <?php foreach ($data['khoi'] as $khoi): ?>
                                        <option value="<?php echo $khoi['maKhoi']; ?>" 
                                            <?php echo ($data['filters']['maKhoi'] == $khoi['maKhoi']) ? 'selected' : ''; ?>>
                                            Khối <?php echo htmlspecialchars($khoi['khoiLop']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="filter-actions-button">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                            <a href="?action=viewClasses" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Đặt lại
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Table -->

            </div>
            <div>
                <?php if ($data['classes']['success'] && count($data['classes']['data']) > 0): ?>
                    <table class="common-table">
                        <thead>
                            <tr>
                                <th class="small-cell">STT</th>
                                <th>Tên lớp</th>
                                <th class="center">Khối</th>
                                <th>Môn học</th>
                                <th class="center">Sĩ số</th>
                                <th>Phòng học</th>
                                <th>GVCN</th>
                                <th class="center">Số tiết / tuần</th>
                                <th class="center">Học kỳ</th>
                                <th class="nowrap">Năm học</th>
                                <th class="center">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody class="common-table-body">
                            <?php foreach ($data['classes']['data'] as $index => $class): ?>
                                <tr>
                                    <!-- STT -->
                                    <td class="small-cell">
                                        <p><?php echo $index + 1; ?></p>
                                    </td>

                                    <!-- Tên lớp -->
                                    <td>
                                        <div class="request-description">
                                            <span class="request-des-title">
                                                <?php echo htmlspecialchars($class['tenLop']); ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Khối -->
                                    <td class="center">
                                        <span class="badge info">
                                            Khối <?php echo htmlspecialchars($class['khoiLop']); ?>
                                        </span>
                                    </td>

                                    <!-- Môn học -->
                                    <td>
                                        <?php echo htmlspecialchars($class['tenMonHoc']); ?>
                                    </td>

                                    <!-- Sĩ số -->
                                    <td class="center">
                                        <i class="fas fa-users"></i>
                                        <?php echo htmlspecialchars($class['siSo']); ?>
                                    </td>

                                    <!-- Phòng học -->
                                    <td>
                                        <?php echo htmlspecialchars($class['tenPhong'] ?? 'Chưa xếp'); ?>
                                    </td>

                                    <!-- GVCN -->
                                    <td>
                                        <?php echo htmlspecialchars($class['giaoVienChuNhiem'] ?? 'Chưa có'); ?>
                                    </td>

                                    <!-- Số tiết -->
                                    <td class="center">
                                        <span class="badge completed">
                                            <?php echo htmlspecialchars($class['soTietTrongTuan']); ?> tiết
                                        </span>
                                    </td>

                                    <!-- Học kỳ -->
                                    <td class="center">
                                        HK <?php echo htmlspecialchars($class['hocKy']); ?>
                                    </td>

                                    <!-- Năm học -->
                                    <td class="nowrap">
                                        <?php echo htmlspecialchars($class['namHoc']); ?>
                                    </td>

                                    <!-- Thao tác -->
                                    <td class="center">
                                        <a href="index.php?action=viewStudentList&maLop=<?php echo $class['maLop']; ?>&tenLop=<?php echo urlencode($class['tenLop']); ?>" 
                                           class="btn-icon" title="Xem danh sách học sinh">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Không tìm thấy lớp học nào</h3>
                        <p>Vui lòng thử lại với các tiêu chí lọc khác</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
</body>

</html>