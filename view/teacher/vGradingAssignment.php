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

    // Lấy danh sách phân công chấm điểm của giáo viên
    $phanCongResult = $model->getGradingAssignment($maGV);

    $data = [
        'gradingAssignment' => [
            'success' => $phanCongResult['success'],
            'data' => $phanCongResult['data'] ?? [],
            'total' => $phanCongResult['total'] ?? 0
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
    <title>Phân công chấm điểm - Giáo viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <div class="header-section ">
                <div class="header-left">
                    <h2>
                        <i class="fas fa-pen-square"></i> Phân công chấm điểm
                    </h2>
                    <p>Theo dõi danh sách bài chấm điểm của bạn.</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <?php if ($data['gradingAssignment']['success']): ?>
                <div class="stats-assign-row">
                    <div class="stat-item">
                        <div class="stat-value assign-total"><?php echo $data['gradingAssignment']['total']; ?></div>
                        <div class="stat-label">Tổng phân công</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value assign-pending">
                            <?php
                            $pending = 0;
                            foreach ($data['gradingAssignment']['data'] as $item) {
                                if ($item['trangThai'] == 'pending') {
                                    $pending++;
                                }
                            }
                            echo $pending;
                            ?>
                        </div>
                        <div class="stat-label">Chưa bắt đầu</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value assign-inprogress">
                            <?php
                            $inProgress = 0;
                            foreach ($data['gradingAssignment']['data'] as $item) {
                                if ($item['trangThai'] == 'in_progress') {
                                    $inProgress++;
                                }
                            }
                            echo $inProgress;
                            ?>
                        </div>
                        <div class="stat-label">Đang làm</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value assign-finish">
                            <?php
                            $completed = 0;
                            foreach ($data['gradingAssignment']['data'] as $item) {
                                if ($item['trangThai'] == 'completed') {
                                    $completed++;
                                }
                            }
                            echo $completed;
                            ?>
                        </div>
                        <div class="stat-label">Hoàn thành</div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-list"></i> Danh sách phân công chấm điểm</h2>
                </div>
                <form method="GET" action="">
                    <input type="hidden" name="action" value="viewGradingAssignment">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Trạng thái</label>
                            <select name="trangThai">
                                <option value="">Tất cả</option>
                                <option value="pending" <?php echo ($data['filters']['trangThai'] == 'pending') ? 'selected' : ''; ?>>Chưa bắt đầu</option>
                                <option value="in_progress" <?php echo ($data['filters']['trangThai'] == 'in_progress') ? 'selected' : ''; ?>>Đang làm</option>
                                <option value="completed" <?php echo ($data['filters']['trangThai'] == 'completed') ? 'selected' : ''; ?>>Hoàn thành</option>
                                <option value="cancelled" <?php echo ($data['filters']['trangThai'] == 'cancelled') ? 'selected' : ''; ?>>Đã hủy</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Loại kiểm tra</label>
                            <select name="loaiKiemTra">
                                <option value="">Tất cả</option>
                                <option value="Miệng" <?php echo ($data['filters']['loaiKiemTra'] == 'Miệng') ? 'selected' : ''; ?>>Miệng</option>
                                <option value="15 phút" <?php echo ($data['filters']['loaiKiemTra'] == '15 phút') ? 'selected' : ''; ?>>15 phút</option>
                                <option value="1 tiết" <?php echo ($data['filters']['loaiKiemTra'] == '1 tiết') ? 'selected' : ''; ?>>1 tiết</option>
                                <option value="Giữa kỳ" <?php echo ($data['filters']['loaiKiemTra'] == 'Giữa kỳ') ? 'selected' : ''; ?>>Giữa kỳ</option>
                                <option value="Cuối kỳ" <?php echo ($data['filters']['loaiKiemTra'] == 'Cuối kỳ') ? 'selected' : ''; ?>>Cuối kỳ</option>
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
            <!-- Table -->
            <div class="card-body">
                <?php if ($data['gradingAssignment']['success'] && count($data['gradingAssignment']['data']) > 0): ?>
                    <table class="common-table">
                        <thead>
                            <tr>
                                <th class="assign-table-index">STT</th>
                                <th>Loại kiểm tra</th>
                                <th>Môn học</th>
                                <th>Lớp</th>
                                <th>Số lượng</th>
                                <th>Hình thức</th>
                                <th>Ngày chấm</th>
                                <th>Tiến độ</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="assign-table-body">
                            <?php foreach ($data['gradingAssignment']['data'] as $index => $item): ?>
                                <tr>
                                    <td class="assign-table-index"><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php
                                        $loaiKTClass = 'mieng';
                                        if ($item['loaiKiemTra'] == '15 phút') $loaiKTClass = 'phut15';
                                        elseif ($item['loaiKiemTra'] == '1 tiết') $loaiKTClass = 'tiet1';
                                        elseif ($item['loaiKiemTra'] == 'Giữa kỳ') $loaiKTClass = 'giuaky';
                                        elseif ($item['loaiKiemTra'] == 'Cuối kỳ') $loaiKTClass = 'cuoiky';
                                        ?>
                                        <span class="badge <?php echo $loaiKTClass; ?>">
                                            <?php echo htmlspecialchars($item['loaiKiemTra']); ?>
                                        </span>
                                    </td>
                                    <td class="assign-subject-name"><strong><?php echo htmlspecialchars($item['tenMonHoc']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['tenLop']); ?></td>
                                    <td>
                                        <i class="fas fa-users"></i>
                                        <?php echo $item['soDaCham']; ?>/<?php echo $item['soLuongHocSinh']; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['hinhThucCham']); ?></td>
                                    <td>
                                        <?php
                                        if ($item['ngayCham']) {
                                            echo date('d/m/Y', strtotime($item['ngayCham']));
                                        } else {
                                            echo '<span style="color: #999;">Chưa xác định</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="progress-container">
                                            <?php
                                            $percent = round($item['phanTramHoanThanh'] ?? 0);
                                            $progressClass = 'low';
                                            if ($percent >= 100) $progressClass = 'complete';
                                            elseif ($percent >= 70) $progressClass = 'high';
                                            elseif ($percent >= 40) $progressClass = 'medium';
                                            ?>
                                            <div class="progress-text"><?php echo $percent; ?>%</div>

                                            <div class="progress-bar">
                                                <div class="progress-fill <?php echo $progressClass; ?>"
                                                    style="width: <?php echo $percent; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo htmlspecialchars($item['trangThai']); ?>">
                                            <?php
                                            $statusLabels = [
                                                'pending' => 'Chưa bắt đầu',
                                                'in_progress' => 'Đang làm',
                                                'completed' => 'Hoàn thành',
                                                'cancelled' => 'Đã hủy'
                                            ];
                                            echo $statusLabels[$item['trangThai']] ?? $item['trangThai'];
                                            ?>
                                        </span>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>Không tìm thấy phân công chấm điểm nào</h3>
                        <p>Vui lòng thử lại với các tiêu chí lọc khác</p>
                    </div>
                <?php endif; ?>
            </div>
</body>

</html>