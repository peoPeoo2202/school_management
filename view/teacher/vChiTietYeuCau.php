<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once(__DIR__ . '/../../config.php');

// Kiểm tra đăng nhập và quyền
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true || $_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: " . url('public/index.php'));
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết yêu cầu - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #7f8c8d;
            font-size: 14px;
        }

        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-choxuly {
            background: #fff3e0;
            color: #e65100;
        }

        .status-dachapnhan {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-tuchoi {
            background: #ffebee;
            color: #c62828;
        }

        .content-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-size: 13px;
            color: #7f8c8d;
            font-weight: 500;
        }

        .info-value {
            font-size: 15px;
            color: #2c3e50;
            font-weight: 500;
        }

        .description-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #3498db;
            margin: 15px 0;
        }

        .description-box p {
            color: #495057;
            line-height: 1.6;
        }

        .file-attachment {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: #e8f5e9;
            border-radius: 6px;
            color: #2e7d32;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .file-attachment:hover {
            background: #c8e6c9;
            transform: translateY(-1px);
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -29px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #3498db;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #3498db;
        }

        .timeline-date {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
        }

        .timeline-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .timeline-text {
            font-size: 14px;
            color: #666;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-back {
            background: #95a5a6;
            color: white;
        }

        .btn-back:hover {
            background: #7f8c8d;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #2196f3;
        }

        .score-change {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            margin: 10px 0;
        }

        .score-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 20px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .score-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .score-value {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }

        .score-arrow {
            font-size: 24px;
            color: #3498db;
        }

        .empty-timeline {
            text-align: center;
            padding: 30px;
            color: #95a5a6;
        }
    </style>
</head>
<body>
    <?php include_once(__DIR__ . '/../layouts/teacher-layout-header.php'); ?>

    <div class="container">
        <div class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
            <span>/</span>
            <a href="index.php?action=danhsachyeucau">Danh sách yêu cầu</a>
            <span>/</span>
            <span>Chi tiết yêu cầu #<?php echo $yeuCau['maYeuCau']; ?></span>
        </div>

        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1><i class="fas fa-file-alt"></i> Chi tiết yêu cầu #<?php echo $yeuCau['maYeuCau']; ?></h1>
                </div>
                <div>
                    <?php
                    $statusClass = [
                        'Choxuly' => 'status-choxuly',
                        'Dachapnhan' => 'status-dachapnhan',
                        'Tuchoi' => 'status-tuchoi'
                    ];
                    $statusText = [
                        'Choxuly' => 'Chờ xử lý',
                        'Dachapnhan' => 'Đã chấp nhận',
                        'Tuchoi' => 'Từ chối'
                    ];
                    ?>
                    <span class="status-badge <?php echo $statusClass[$yeuCau['trangThai']]; ?>">
                        <?php echo $statusText[$yeuCau['trangThai']]; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Thông tin chung -->
        <div class="content-section">
            <h3 class="section-title">Thông tin chung</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Loại yêu cầu</span>
                    <span class="info-value">
                        <?php if ($yeuCau['loaiYeuCau'] == 'SuaDiem'): ?>
                            <i class="fas fa-edit" style="color: #3498db;"></i> Yêu cầu sửa điểm
                        <?php else: ?>
                            <i class="fas fa-calendar-times" style="color: #9b59b6;"></i> Yêu cầu nghỉ phép
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ngày gửi</span>
                    <span class="info-value">
                        <i class="fas fa-clock"></i>
                        <?php echo date('d/m/Y H:i', strtotime($yeuCau['ngayGui'])); ?>
                    </span>
                </div>
                <?php if ($yeuCau['ngayXuLy']): ?>
                    <div class="info-item">
                        <span class="info-label">Ngày xử lý</span>
                        <span class="info-value">
                            <i class="fas fa-check-circle"></i>
                            <?php echo date('d/m/Y H:i', strtotime($yeuCau['ngayXuLy'])); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Người xử lý</span>
                        <span class="info-value">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($yeuCau['nguoiXuLy']); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="description-box">
                <strong style="color: #2c3e50;">Mô tả:</strong>
                <p><?php echo htmlspecialchars($yeuCau['moTa']); ?></p>
            </div>
        </div>

        <!-- Chi tiết theo loại yêu cầu -->
        <?php if ($yeuCau['loaiYeuCau'] == 'SuaDiem'): ?>
            <div class="content-section">
                <h3 class="section-title">Thông tin sửa điểm</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Học sinh</span>
                        <span class="info-value">
                            <i class="fas fa-user-graduate"></i>
                            <?php echo htmlspecialchars($yeuCau['tenHS']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Môn học</span>
                        <span class="info-value">
                            <i class="fas fa-book"></i>
                            <?php echo htmlspecialchars($yeuCau['tenMonHoc']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Học kỳ - Năm học</span>
                        <span class="info-value">
                            <i class="fas fa-calendar-alt"></i>
                            HK<?php echo $yeuCau['hocKy']; ?> - <?php echo $yeuCau['namHoc']; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Loại điểm</span>
                        <span class="info-value">
                            <?php
                            $loaiDiemText = [
                                'diemMieng' => 'Điểm miệng',
                                'diem15Phut1' => 'Điểm 15 phút (lần 1)',
                                'diem15Phut2' => 'Điểm 15 phút (lần 2)',
                                'diem1Tiet' => 'Điểm 1 tiết',
                                'diemGiuaKy' => 'Điểm giữa kỳ',
                                'diemCuoiKy' => 'Điểm cuối kỳ'
                            ];
                            echo $loaiDiemText[$yeuCau['loaiDiem']] ?? $yeuCau['loaiDiem'];
                            ?>
                        </span>
                    </div>
                </div>

                <div class="score-change">
                    <div class="score-item">
                        <span class="score-label">Điểm hiện tại</span>
                        <span class="score-value" style="color: #e74c3c;">
                            <?php echo number_format($yeuCau['diemHienTai'], 2); ?>
                        </span>
                    </div>
                    <span class="score-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                    <div class="score-item">
                        <span class="score-label">Điểm đề nghị sửa</span>
                        <span class="score-value" style="color: #27ae60;">
                            <?php echo number_format($yeuCau['diemDeNghiSua'], 2); ?>
                        </span>
                    </div>
                </div>

                <div class="description-box">
                    <strong style="color: #2c3e50;">Lý do sửa điểm:</strong>
                    <p><?php echo nl2br(htmlspecialchars($yeuCau['lyDoSuaDiem'])); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="content-section">
                <h3 class="section-title">Thông tin nghỉ phép</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Ngày bắt đầu nghỉ</span>
                        <span class="info-value">
                            <i class="fas fa-calendar-day"></i>
                            <?php echo date('d/m/Y', strtotime($yeuCau['ngayBatDauNghi'])); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Ngày kết thúc nghỉ</span>
                        <span class="info-value">
                            <i class="fas fa-calendar-day"></i>
                            <?php echo date('d/m/Y', strtotime($yeuCau['ngayKetThucNghi'])); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Số ngày nghỉ</span>
                        <span class="info-value">
                            <?php
                            $start = new DateTime($yeuCau['ngayBatDauNghi']);
                            $end = new DateTime($yeuCau['ngayKetThucNghi']);
                            $diff = $start->diff($end);
                            echo $diff->days + 1;
                            ?> ngày
                        </span>
                    </div>
                </div>

                <div class="description-box">
                    <strong style="color: #2c3e50;">Lý do nghỉ phép:</strong>
                    <p><?php echo nl2br(htmlspecialchars($yeuCau['lyDoNghiPhep'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- File minh chứng -->
        <?php 
            // Lấy minh chứng từ bảng yeucau (đã được lưu khi gửi yêu cầu)
            $minhChung = $yeuCau['minhChung'] ?? '';
        ?>
        <?php if ($minhChung): ?>
            <div class="content-section">
                <h3 class="section-title">Minh chứng đính kèm</h3>
                <a href="<?php echo CONTROLLER_URL; ?>/download.php?maYeuCau=<?php echo $yeuCau['maYeuCau']; ?>" 
                   class="file-attachment">
                    <i class="fas fa-file-download"></i>
                    <?php echo htmlspecialchars($minhChung); ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Lịch sử xử lý -->
        <div class="content-section">
            <h3 class="section-title">Lịch sử xử lý</h3>
            <?php if (!empty($lichSuXuLy)): ?>
                <div class="timeline">
                    <?php foreach ($lichSuXuLy as $ls): ?>
                        <div class="timeline-item">
                            <div class="timeline-date">
                                <?php echo date('d/m/Y H:i', strtotime($ls['ngayXuLy'])); ?>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">
                                    <?php echo htmlspecialchars($ls['nguoiXuLy']); ?>
                                </div>
                                <div class="timeline-text">
                                    Chuyển trạng thái từ 
                                    <strong><?php echo $ls['trangThaiCu']; ?></strong> 
                                    sang 
                                    <strong><?php echo $ls['trangThaiMoi']; ?></strong>
                                    <?php if ($ls['lyDoTuChoi']): ?>
                                        <br><br><strong>Lý do từ chối:</strong> 
                                        <?php echo htmlspecialchars($ls['lyDoTuChoi']); ?>
                                    <?php endif; ?>
                                    <?php if ($ls['ghiChu']): ?>
                                        <br><br><strong>Ghi chú:</strong> 
                                        <?php echo htmlspecialchars($ls['ghiChu']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-timeline">
                    <i class="fas fa-history" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                    <p>Chưa có lịch sử xử lý</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top: 20px;">
            <a href="index.php?action=danhsachyeucau" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
        </div>
    </div>

    <?php include_once(__DIR__ . '/../layouts/teacher-layout-footer.php'); ?>
</body>
</html>
