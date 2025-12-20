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

$maGV = $_SESSION['maGV'] ?? null;

// Nếu $data không được định nghĩa, khởi tạo nó
if (!isset($data)) {
    require_once(__DIR__ . '/../../model/mTeachingSchedule.php');

    $model = new mTeachingSchedule();

    // Lấy danh sách phân công coi thi của giáo viên
    $examResult = $model->getExamSupervision($maGV);

    $data = [
        'examSupervision' => [
            'success' => $examResult['success'],
            'data' => $examResult['data'] ?? [],
            'total' => $examResult['total'] ?? 0
        ],
        'filters' => []
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công coi thi - Giáo viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <div class="header-section header-assign">
                <div class="header-left ">
                    <h2>
                        <i class="fas fa-eye"></i> Phân công coi thi
                    </h2>
                    <p>Tra cứu thông tin phân công coi thi.</p>
                </div>
                <!-- <div class="header-left-icon"> -->

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>

            </div>
            <?php if ($data['examSupervision']['success']): ?>
                <div class="stats-assign-row">
                    <div class="stat-item">
                        <div class="stat-value exam-sup-total"><?php echo $data['examSupervision']['total']; ?></div>
                        <div class="stat-label">Tổng số ca thi</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value exam-sup-upcoming">
                            <?php
                            $upcoming = 0;
                            foreach ($data['examSupervision']['data'] as $exam) {
                                if ($exam['trangThai'] == 'scheduled' && $exam['soNgayConLai'] >= 0) {
                                    $upcoming++;
                                }
                            }
                            echo $upcoming;
                            ?>
                        </div>
                        <div class="stat-label ">Ca thi sắp tới</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value exam-sup-finish">
                            <?php
                            $completed = 0;
                            foreach ($data['examSupervision']['data'] as $exam) {
                                if ($exam['trangThai'] == 'completed') {
                                    $completed++;
                                }
                            }
                            echo $completed;
                            ?>
                        </div>
                        <div class="stat-label ">Đã hoàn thành</div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-list"></i> Danh sách phân công coi thi</h2>
                </div>
                <form method="GET" action="">
                    <input type="hidden" name="action" value="viewExamSupervision">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Trạng thái</label>
                            <select name="trangThai">
                                <option value="">Tất cả</option>
                                <option value="scheduled" <?php echo ($data['filters']['trangThai'] == 'scheduled') ? 'selected' : ''; ?>>Đã lên lịch</option>
                                <option value="in_progress" <?php echo ($data['filters']['trangThai'] == 'in_progress') ? 'selected' : ''; ?>>Đang diễn ra</option>
                                <option value="completed" <?php echo ($data['filters']['trangThai'] == 'completed') ? 'selected' : ''; ?>>Đã hoàn thành</option>
                                <option value="cancelled" <?php echo ($data['filters']['trangThai'] == 'cancelled') ? 'selected' : ''; ?>>Đã hủy</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Loại kỳ thi</label>
                            <select name="loaiKyThi">
                                <option value="">Tất cả</option>
                                <option value="Giữa kỳ" <?php echo ($data['filters']['loaiKyThi'] == 'Giữa kỳ') ? 'selected' : ''; ?>>Giữa kỳ</option>
                                <option value="Cuối kỳ" <?php echo ($data['filters']['loaiKyThi'] == 'Cuối kỳ') ? 'selected' : ''; ?>>Cuối kỳ</option>
                                <option value="Kiểm tra 15 phút" <?php echo ($data['filters']['loaiKyThi'] == 'Kiểm tra 15 phút') ? 'selected' : ''; ?>>Kiểm tra 15 phút</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Học kỳ</label>
                            <select name="hocKy">
                                <option value="">Tất cả</option>
                                <option value="1" <?php echo ($data['filters']['hocKy'] == 1) ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo ($data['filters']['hocKy'] == 2) ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if ($data['examSupervision']['success'] && count($data['examSupervision']['data']) > 0): ?>
                    <div class="content-grid">
                        <?php foreach ($data['examSupervision']['data'] as $exam): ?>
                            <div class="exam-card">
                                <div class="exam-card-header">
                                    <div class="exam-card-subject"><?php echo htmlspecialchars($exam['tenMonHoc']); ?></div>
                                    <div class="exam-card-type"><?php echo htmlspecialchars($exam['loaiKyThi']); ?></div>
                                </div>

                                <div class="exam-details">
                                    <div class="exam-detail-item">
                                        <i class="fas fa-door-open"></i>
                                        <span><strong>Lớp:</strong> <?php echo htmlspecialchars($exam['tenLop']); ?></span>
                                    </div>
                                    <div class="exam-detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><strong>Phòng:</strong> <?php echo htmlspecialchars($exam['tenPhong']); ?></span>
                                    </div>
                                    <div class="exam-detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><strong>Ngày:</strong> <?php echo date('d/m/Y', strtotime($exam['ngayThi'])); ?></span>
                                    </div>
                                    <div class="exam-detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span><strong>Giờ:</strong>
                                            <?php echo date('H:i', strtotime($exam['gioBatDau'])); ?> -
                                            <?php echo date('H:i', strtotime($exam['gioKetThuc'])); ?>
                                        </span>
                                    </div>
                                    <div class="exam-detail-item">
                                        <i class="fas fa-user-tie"></i>
                                        <span><strong>Vị trí:</strong> <?php echo htmlspecialchars($exam['viTriCoiThi']); ?></span>
                                    </div>
                                </div>

                                <div class="exam-footer">
                                    <span class="badge <?php echo htmlspecialchars($exam['trangThai']); ?>">
                                        <?php
                                        $statusLabels = [
                                            'scheduled' => 'Đã lên lịch',
                                            'in_progress' => 'Đang diễn ra',
                                            'completed' => 'Hoàn thành',
                                            'cancelled' => 'Đã hủy'
                                        ];
                                        echo $statusLabels[$exam['trangThai']] ?? $exam['trangThai'];
                                        ?>
                                    </span>
                                    <?php if ($exam['soNgayConLai'] >= 0 && $exam['soNgayConLai'] <= 1 && $exam['trangThai'] == 'scheduled'): ?>
                                        <span class="badge urgent">
                                            <i class="fas fa-exclamation-triangle"></i> Sắp diễn ra
                                        </span>
                                    <?php elseif ($exam['soNgayConLai'] > 1 && $exam['soNgayConLai'] <= 3 && $exam['trangThai'] == 'scheduled'): ?>
                                        <span class="badge soon">
                                            Còn <?php echo $exam['soNgayConLai']; ?> ngày
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard"></i>
                        <h3>Không tìm thấy phân công coi thi nào</h3>
                        <p>Vui lòng thử lại với các tiêu chí lọc khác</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

</body>

</html>