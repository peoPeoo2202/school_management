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
    <style>

        .header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 32px;
            font-size: 14px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #5081BE;
        }

        .container {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .nav-tab {
            padding: 12px 25px;
            background: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            color: #5081BE;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .nav-tab:hover {
            background: #5081BE;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(80, 129, 190, 0.3);
        }

        .nav-tab.active {
            background: #5081BE;
            color: white;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        

        .btn-primary {
            background: #5081BE;
            color: white;
        }

        .btn-primary:hover {
            background: #2d5a8c;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .exam-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .exam-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s;
        }

        .exam-card:hover {
            border-color: #5081BE;
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(80, 129, 190, 0.2);
        }

        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .exam-subject {
            font-size: 16px;
            font-weight: 700;
            color: #5081BE;
        }

        .exam-type {
            display: inline-block;
            padding: 8px 16px;
            background: #2d5a8c;
            color: white;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
        }

        /* .exam-details {
            margin-bottom: 16px;
        } */

        .exam-detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 400;
            color: #666;
        }

        .exam-detail-item i {
            width: 24px;
            color: #5081BE;
        }

        .exam-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
            margin-top: 16px;
        }

        .badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge.scheduled {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge.in-progress {
            background: #fff3cd;
            color: #856404;
        }

        .badge.completed {
            background: #d4edda;
            color: #155724;
        }

        .badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .badge.urgent {
            background: #f8d7da;
            color: #721c24;
        }

        .badge.soon {
            background: #fff3cd;
            color: #856404;
        }

        .empty-state {
            text-align: center;
            padding: 60px 24px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 24px;
            opacity: 0.5;
        }

        .stats-row {
            display: flex;
            justify-content: space-around;
            background: #f8f9ff;
            border-radius: 8px;
            margin-bottom: 24px;
            background-color: white;
            border-radius: 16px;
            padding: 32px;
        }
        

        

    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <div class="header">
                <h1><i class="fas fa-eye"></i> Phân công coi thi</h1>
            </div>

            <!-- Statistics -->
            <?php if ($data['examSupervision']['success']): ?>
                <div class="stats-row">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $data['examSupervision']['total']; ?></div>
                            <div class="stat-label">Tổng số ca thi</div>
                        </div>
                    <div class="stat-item">
                        <div class="stat-value">
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
                        <div class="stat-label">Ca thi sắp tới</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
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
                        <div class="stat-label">Đã hoàn thành</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Main Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-list"></i> Danh sách phân công coi thi</h2>
                </div>

                <!-- Filter Section -->
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

                <!-- Exam Cards Grid -->
                <?php if ($data['examSupervision']['success'] && count($data['examSupervision']['data']) > 0): ?>
                    <div class="exam-grid">
                        <?php foreach ($data['examSupervision']['data'] as $exam): ?>
                            <div class="exam-card">
                                <div class="exam-header">
                                    <div class="exam-subject"><?php echo htmlspecialchars($exam['tenMonHoc']); ?></div>
                                    <div class="exam-type"><?php echo htmlspecialchars($exam['loaiKyThi']); ?></div>
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