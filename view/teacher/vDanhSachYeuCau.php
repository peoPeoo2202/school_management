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
$loaiYeuCau = $_GET['loaiYeuCau'] ?? 'all';
$trangThai = $_GET['trangThai'] ?? 'all';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách yêu cầu - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="main-wrapper">
        <?php include('../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">

            <!-- Header theo layout mẫu -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-list-alt"></i> Danh sách yêu cầu </h2>
                    <p>Theo dõi các yêu cầu sửa điểm và nghỉ phép của bạn</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen ?? 'Giáo viên'); ?></p>
                </div>
            </div>

            <!-- Card thống kê -->
            <?php if (!empty($danhSachYeuCau)): ?>
                <div class="stats-assign-row">
                    <div class="stat-item">
                        <div class="stat-value exam-sup-total"><?php echo count($danhSachYeuCau); ?></div>
                        <div class="stat-label">Tổng yêu cầu</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-upcoming">
                            <?php echo count(array_filter($danhSachYeuCau, fn($yc) => $yc['trangThai'] == 'Choxuly')); ?>
                        </div>
                        <div class="stat-label">Chờ xử lý</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-finish">
                            <?php echo count(array_filter($danhSachYeuCau, fn($yc) => $yc['trangThai'] == 'Dachapnhan')); ?>
                        </div>
                        <div class="stat-label">Đã chấp nhận</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-value exam-sup-danger">
                            <?php echo count(array_filter($danhSachYeuCau, fn($yc) => $yc['trangThai'] == 'Tuchoi')); ?>
                        </div>
                        <div class="stat-label">Từ chối</div>
                    </div>
                </div>
            <?php endif; ?>




            <!-- Card bộ lọc theo layout mẫu -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-filter"></i> Bộ lọc</h2>
                </div>

                <form method="GET" action="index.php" id="filterForm">
                    <input type="hidden" name="action" value="danhsachyeucau">

                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Loại yêu cầu</label>
                            <select name="loaiYeuCau">
                                <option value="all" <?php echo $loaiYeuCau == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                                <option value="SuaDiem" <?php echo $loaiYeuCau == 'SuaDiem' ? 'selected' : ''; ?>>Sửa điểm</option>
                                <option value="NghiPhep" <?php echo $loaiYeuCau == 'NghiPhep' ? 'selected' : ''; ?>>Nghỉ phép</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Trạng thái</label>
                            <select name="trangThai">
                                <option value="all" <?php echo $trangThai == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                                <option value="Choxuly" <?php echo $trangThai == 'Choxuly' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                <option value="Dachapnhan" <?php echo $trangThai == 'Dachapnhan' ? 'selected' : ''; ?>>Đã duyệt</option>
                                <option value="Tuchoi" <?php echo $trangThai == 'Tuchoi' ? 'selected' : ''; ?>>Từ chối</option>
                            </select>
                        </div>

                        <div class="filter-group filter-request-actions">
                            <label>&nbsp;</label>
                            <div class="filter-actions-button">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Lọc
                                </button>
                                <a href="index.php?action=danhsachyeucau" class="btn btn-outlined">
                                    <i class="fas fa-redo"></i> Đặt lại
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

            </div>

            <!-- Card danh sách -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-table"></i> Danh sách yêu cầu</h2>
                </div>
                <?php if (empty($danhSachYeuCau)): ?>
                    <div class="empty-state ">
                        <div>
                            <i class="fas fa-inbox"></i>
                            <p>Chưa có yêu cầu nào</p>
                        </div>
                    </div>
                <?php else: ?>
                    <table class="common-table">
                        <thead>
                            <tr>
                                <th class="small-cell">Mã YC</th>
                                <th>Loại yêu cầu</th>
                                <th>Mô tả</th>
                                <th>Ngày gửi</th>
                                <th class="center">Trạng thái</th>
                                <th class="small-cell">Minh chứng</th>
                                <th>Ngày xử lý</th>
                                <th>Người xử lý</th>
                                <th class="action-cell">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody class="common-table-body">
                            <?php foreach ($danhSachYeuCau as $yeuCau): ?>
                                <?php
                                $statusClass = [
                                    'Choxuly' => 'in-progress',
                                    'Dachapnhan' => 'completed',
                                    'Tuchoi' => 'cancelled'
                                ];
                                $statusText = [
                                    'Choxuly' => 'Chờ xử lý',
                                    'Dachapnhan' => 'Đã duyệt',
                                    'Tuchoi' => 'Từ chối'
                                ];

                                $minhChungFile = $yeuCau['minhChung'] ?? '';
                                ?>
                                <tr>
                                    <td class="small-cell">
                                        <p>#<?php echo $yeuCau['maYeuCau']; ?></p>
                                    </td>

                                    <td>
                                        <?php if ($yeuCau['loaiYeuCau'] == 'SuaDiem'): ?>
                                            <span class="type-request">
                                                Sửa điểm
                                            </span>
                                        <?php else: ?>
                                            <span class="type-request">
                                                Nghỉ phép
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="request-description">
                                            <span class="request-des-title">
                                                <?php echo htmlspecialchars(substr($yeuCau['moTa'], 0, 60)) . (strlen($yeuCau['moTa']) > 60 ? '...' : ''); ?>
                                            </span>

                                            <?php if ($yeuCau['loaiYeuCau'] == 'SuaDiem' && !empty($yeuCau['tenHS'])): ?>
                                                <div class="request-details">
                                                    <p><?php echo htmlspecialchars($yeuCau['tenHS']); ?></p>
                                                    &nbsp;-&nbsp;
                                                    <p><?php echo htmlspecialchars($yeuCau['tenMonHoc']); ?></p>
                                                </div>
                                            <?php elseif ($yeuCau['loaiYeuCau'] == 'NghiPhep'): ?>
                                                <span class="request-details">
                                                    <?php echo date('d/m/Y', strtotime($yeuCau['ngayBatDauNghi'])); ?>
                                                    -
                                                    <?php echo date('d/m/Y', strtotime($yeuCau['ngayKetThucNghi'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="request-text"><?php echo date('d/m/Y H:i', strtotime($yeuCau['ngayGui'])); ?></td>

                                    <td class="center">
                                        <span class="badge <?php echo $statusClass[$yeuCau['trangThai']]; ?>">
                                            <?php echo $statusText[$yeuCau['trangThai']]; ?>
                                        </span>
                                    </td>

                                    <td class="small-cell">
                                        <?php if (!empty($minhChungFile)): ?>
                                            <a href="<?php echo CONTROLLER_URL; ?>/download.php?maYeuCau=<?php echo $yeuCau['maYeuCau']; ?>"
                                                class="btn-download-file "
                                                title="Tải minh chứng">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="empty-info">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="nowrap">
                                        <?php echo $yeuCau['ngayXuLy'] ? date('d/m/Y H:i', strtotime($yeuCau['ngayXuLy'])) : '-'; ?>
                                    </td>

                                    <td>
                                        <?php echo $yeuCau['nguoiXuLy'] ? htmlspecialchars($yeuCau['nguoiXuLy']) : '-'; ?>
                                    </td>

                                    <td class="action-cell">
                                        <div class="action-buttons">
                                            <a href="index.php?action=chitietyeucau&maYeuCau=<?php echo $yeuCau['maYeuCau']; ?>"
                                                class="btn-view "
                                                title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </div>
    </div>
</body>

</html>