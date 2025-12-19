<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['maTaiKhoan'])) {
    header("Location: ../../public/index.php");
    exit;
}

// Kiểm tra quyền quản trị viên
if ($_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    echo "<h1>Không có quyền truy cập</h1>";
    exit;
}

$accountId = $_GET['id'] ?? null;

if (!$accountId) {
    echo "<h1>Không tìm thấy tài khoản</h1>";
    exit;
}

require_once('../../model/mUser.php');
$mUser = new mUser();
$account = $mUser->getAccountById($accountId);

if (!$account) {
    echo "<h1>Không tìm thấy tài khoản</h1>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết tài khoản - <?php echo htmlspecialchars($account['tenDangNhap']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        h1 {
            color: #333;
            font-size: 24px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            background: #6c757d;
            color: white;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5a6268;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-section h2 {
            color: #555;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
            padding: 10px 0;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
        }
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        .badge-locked {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-disabled {
            background: #d6d8db;
            color: #383d41;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Chi tiết tài khoản</h1>
            <a href="vAccountList.php" class="btn">← Quay lại</a>
        </div>

        <div class="info-section">
            <h2>Thông tin cơ bản</h2>
            <div class="info-grid">
                <div class="info-label">Mã tài khoản:</div>
                <div class="info-value"><?php echo htmlspecialchars($account['maTaiKhoan']); ?></div>

                <div class="info-label">Tên đăng nhập:</div>
                <div class="info-value"><?php echo htmlspecialchars($account['tenDangNhap']); ?></div>

                <div class="info-label">Họ tên:</div>
                <div class="info-value"><?php echo htmlspecialchars($account['hoTen']); ?></div>

                <div class="info-label">Email:</div>
                <div class="info-value"><?php echo $account['email'] ? htmlspecialchars($account['email']) : '-'; ?></div>

                <div class="info-label">Số điện thoại:</div>
                <div class="info-value"><?php echo $account['soDienThoai'] ? htmlspecialchars($account['soDienThoai']) : '-'; ?></div>
            </div>
        </div>

        <div class="info-section">
            <h2>Phân quyền</h2>
            <div class="info-grid">
                <div class="info-label">Loại tài khoản:</div>
                <div class="info-value">
                    <?php
                    $roles = [
                        'quantrivien' => 'Quản trị viên',
                        'bangiamhieu' => 'Ban giám hiệu',
                        'ttbm' => 'Tổ trưởng bộ môn',
                        'giaovien' => 'Giáo viên',
                        'hocsinh' => 'Học sinh',
                        'phuhuynh' => 'Phụ huynh'
                    ];
                    echo $roles[$account['loaiTaiKhoan']] ?? $account['loaiTaiKhoan'];
                    ?>
                </div>

                <div class="info-label">Nhóm người dùng:</div>
                <div class="info-value"><?php echo $account['tenNhom'] ?? '-'; ?></div>

                <div class="info-label">Trạng thái:</div>
                <div class="info-value">
                    <?php
                    $statusClass = 'badge-' . $account['trangThaiTaiKhoan'];
                    $statusText = [
                        'active' => 'Hoạt động',
                        'locked' => 'Đã khóa',
                        'disabled' => 'Vô hiệu hóa'
                    ];
                    echo '<span class="badge ' . $statusClass . '">' . 
                         ($statusText[$account['trangThaiTaiKhoan']] ?? $account['trangThaiTaiKhoan']) . 
                         '</span>';
                    ?>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h2>Thông tin bảo mật</h2>
            <div class="info-grid">
                <div class="info-label">Ngày tạo:</div>
                <div class="info-value"><?php echo $account['ngayTao'] ? date('d/m/Y H:i:s', strtotime($account['ngayTao'])) : '-'; ?></div>

                <div class="info-label">Ngày cập nhật:</div>
                <div class="info-value"><?php echo $account['ngayCapNhat'] ? date('d/m/Y H:i:s', strtotime($account['ngayCapNhat'])) : '-'; ?></div>

                <div class="info-label">Lần đăng nhập cuối:</div>
                <div class="info-value"><?php echo $account['lanDangNhapCuoi'] ? date('d/m/Y H:i:s', strtotime($account['lanDangNhapCuoi'])) : '-'; ?></div>

                <div class="info-label">Số lần đăng nhập sai:</div>
                <div class="info-value"><?php echo $account['soLanDangNhapSai'] ?? 0; ?></div>

                <div class="info-label">Bắt buộc đổi mật khẩu:</div>
                <div class="info-value"><?php echo ($account['batBuocDoiMatKhau'] ?? 0) ? 'Có' : 'Không'; ?></div>
            </div>
        </div>

        <?php if (!empty($account['nguoiTaoInfo']) || !empty($account['nguoiCapNhatInfo'])): ?>
        <div class="info-section">
            <h2>Lịch sử thay đổi</h2>
            <div class="info-grid">
                <?php if (!empty($account['nguoiTaoInfo'])): ?>
                <div class="info-label">Người tạo:</div>
                <div class="info-value"><?php echo htmlspecialchars($account['nguoiTaoInfo']); ?></div>
                <?php endif; ?>

                <?php if (!empty($account['nguoiCapNhatInfo'])): ?>
                <div class="info-label">Người cập nhật:</div>
                <div class="info-value"><?php echo htmlspecialchars($account['nguoiCapNhatInfo']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
