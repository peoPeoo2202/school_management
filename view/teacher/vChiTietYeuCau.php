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
    <link rel="stylesheet" href="style.css">
    <style>
      
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include('../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">

            <!-- Header theo layout mẫu -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-file-alt"></i> Chi tiết yêu cầu </h2>
                    <p>Xem đầy đủ thông tin yêu cầu đã gửi</p>
                </div>
                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>

            </div>
            <h4 class="header-link">
                <i class="fa-solid fa-bars"></i>
                <a href="index.php?action=danhsachyeucau">
                    Danh sách Yêu cầu
                </a>
                <span class="separator">/</span>
                <span class="current-subject">
                    Yêu cầu #<?php echo $yeuCau['maYeuCau']; ?>
                </span>
            </h4>
            <div class="info-grid">
                <!-- Card Thông tin chung -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Thông tin chung</h2>
                    </div>

                    <div class="info-details-request">
                        <div class="info-item">
                            <p class="info-label">Loại yêu cầu: </p>
                            <p class="info-value">
                                <?php if ($yeuCau['loaiYeuCau'] == 'SuaDiem'): ?>
                                    Yêu cầu sửa điểm
                                <?php else: ?>
                                    Yêu cầu nghỉ phép
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="info-item">
                            <p class="info-label">Mô tả:</p>
                            <p class="info-value"><?php echo htmlspecialchars($yeuCau['moTa']); ?></p>
                        </div>
                        <div class="info-item">
                            <?php
                            $statusClass = [
                                'Choxuly' => 'in-progress',
                                'Dachapnhan' => 'completed',
                                'Tuchoi' => 'cancelled'
                            ];
                            $statusText = [
                                'Choxuly' => 'Chờ xử lý',
                                'Dachapnhan' => 'Đã chấp nhận',
                                'Tuchoi' => 'Từ chối'
                            ];
                            ?>
                            <p class="info-label">Trạng thái:</p>
                            <p class="badge <?php echo $statusClass[$yeuCau['trangThai']]; ?>">
                                <?php echo $statusText[$yeuCau['trangThai']]; ?>
                            </p>
                        </div>
                        <div class="info-item">
                            <p class="info-label">Ngày gửi: </p>
                            <p class="info-value">
                                <?php echo date('d/m/Y H:i', strtotime($yeuCau['ngayGui'])); ?>
                            </p>
                        </div>

                        <?php if ($yeuCau['ngayXuLy']): ?>
                            <div class="info-item">
                                <p class="info-label">Ngày xử lý</p>
                                <p class="info-value">
                                    <?php echo date('d/m/Y H:i', strtotime($yeuCau['ngayXuLy'])); ?>
                                </p>
                            </div>

                            <div class="info-item">
                                <p class="info-label">Người xử lý: </p>
                                <span class="info-value nguoi-xu-ly">
                                    <p><?php echo htmlspecialchars($yeuCau['nguoiXuLy']); ?></p>
                                </span>
                                </span>
                            </div>
                        <?php endif; ?>


                        <?php if ($yeuCau['loaiYeuCau'] === 'SuaDiem'): ?>
                            <div class="info-item">
                                <p class="info-label">Lý do sửa điểm:</p>
                                <p class="info-value">
                                    <?php echo nl2br(htmlspecialchars($yeuCau['lyDoSuaDiem'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card chi tiết theo loại yêu cầu -->
                <?php if ($yeuCau['loaiYeuCau'] == 'SuaDiem'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-edit"></i> Thông tin sửa điểm</h2>
                        </div>
                        <div class="info-details-request">
                            <div class="info-item">
                                <span class="info-label">Họ và tên học sinh: </span>
                                <span class="info-value"></i> <?php echo htmlspecialchars($yeuCau['tenHS']); ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Môn học: </span>
                                <span class="info-value"><?php echo htmlspecialchars($yeuCau['tenMonHoc']); ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Học kỳ / Năm học: </span>
                                <span class="info-value"> HK<?php echo $yeuCau['hocKy']; ?> / <?php echo $yeuCau['namHoc']; ?></span>
                            </div>

                            <div class="info-item">
                                <p class="info-label">Loại điểm: </p>
                                <p class="info-value">
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
                                </p>
                            </div>
                            <div class="info-item">
                                <p class="info-label">Điểm hiện tại: </p>
                                <p class="info-value text-danger"><?php echo number_format($yeuCau['diemHienTai'], 2); ?>
                                </p>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Điểm đề nghị sửa: </span>
                                <span class="info-value text-success"><?php echo number_format($yeuCau['diemDeNghiSua'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-calendar-times"></i> Thông tin nghỉ phép</h2>
                        </div>

                        <div class="info-details-request">
                            <div class="info-item">
                                <span class="info-label">Ngày bắt đầu nghỉ: </span>
                                <span class="info-value"><?php echo date('d/m/Y', strtotime($yeuCau['ngayBatDauNghi'])); ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Ngày kết thúc nghỉ: </span>
                                <span class="info-value"><?php echo date('d/m/Y', strtotime($yeuCau['ngayKetThucNghi'])); ?></span>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Số ngày nghỉ: </span>
                                <span class="info-value">
                                    <?php
                                    $start = new DateTime($yeuCau['ngayBatDauNghi']);
                                    $end = new DateTime($yeuCau['ngayKetThucNghi']);
                                    $diff = $start->diff($end);
                                    echo $diff->days + 1;
                                    ?> ngày
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label nowrap label-top">Lý do nghỉ phép: </span>
                                <p class="info-value"><?php echo nl2br(htmlspecialchars($yeuCau['lyDoNghiPhep'])); ?></p>
                            </div>
                        </div>



                    </div>
                <?php endif; ?>
            </div>

            <!-- Card minh chứng -->
            <?php $minhChung = $yeuCau['minhChung'] ?? ''; ?>
            <?php if ($minhChung): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-paperclip"></i> Minh chứng đính kèm</h2>
                    </div>
                    <a href="<?php echo CONTROLLER_URL; ?>/download.php?maYeuCau=<?php echo $yeuCau['maYeuCau']; ?>"
                        class="file-attachment">
                        <i class="fas fa-file-download"></i>
                        <?php echo htmlspecialchars($minhChung); ?>
                    </a>

                </div>
            <?php endif; ?>

            <!-- Card lịch sử xử lý -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-history"></i> Lịch sử xử lý</h2>
                </div>
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
                                        Chuyển trạng thái từ <strong><?php echo $ls['trangThaiCu']; ?></strong>
                                        sang <strong><?php echo $ls['trangThaiMoi']; ?></strong>
                                        <?php if ($ls['lyDoTuChoi']): ?>
                                            <br><br><strong>Lý do từ chối:</strong> <?php echo htmlspecialchars($ls['lyDoTuChoi']); ?>
                                        <?php endif; ?>
                                        <?php if ($ls['ghiChu']): ?>
                                            <br><br><strong>Ghi chú:</strong> <?php echo htmlspecialchars($ls['ghiChu']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>Chưa có lịch sử xử lý</p>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>
</body>

</html>