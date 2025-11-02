<?php
/**
 * File kiểm tra trạng thái đăng nhập
 * Sử dụng để test session và debug
 */
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm tra đăng nhập</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        h1 {
            color: #667eea;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid;
        }

        .status-box.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .status-box.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        .status-box.warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-table th,
        .info-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-table th {
            background: #f8f9ff;
            font-weight: 600;
            color: #667eea;
            width: 200px;
        }

        .info-table tr:hover {
            background: #f8f9ff;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.success {
            background: #d4edda;
            color: #155724;
        }

        .badge.error {
            background: #f8d7da;
            color: #721c24;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .icon {
            font-size: 24px;
        }

        .icon.success {
            color: #28a745;
        }

        .icon.error {
            color: #dc3545;
        }

        .icon.warning {
            color: #ffc107;
        }

        code {
            background: #f8f9ff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <?php if (isset($_SESSION['maTaiKhoan'])): ?>
                <span class="icon success">✅</span> Trạng thái đăng nhập
            <?php else: ?>
                <span class="icon error">❌</span> Trạng thái đăng nhập
            <?php endif; ?>
        </h1>

        <?php if (isset($_SESSION['maTaiKhoan'])): ?>
            <div class="status-box success">
                <strong>✅ Đã đăng nhập thành công!</strong><br>
                Bạn đã đăng nhập vào hệ thống.
            </div>
        <?php else: ?>
            <div class="status-box error">
                <strong>❌ Chưa đăng nhập!</strong><br>
                Vui lòng đăng nhập để sử dụng hệ thống.
            </div>
        <?php endif; ?>

        <!-- Kiểm tra quyền truy cập chức năng Tra cứu lịch dạy -->
        <?php 
        $canAccessTeachingSchedule = false;
        $accessMessage = '';
        
        if (!isset($_SESSION['maTaiKhoan']) || !isset($_SESSION['maGV'])) {
            $accessMessage = 'Không thể truy cập: Chưa đăng nhập hoặc không phải tài khoản giáo viên';
        } elseif (!isset($_SESSION['loaiTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'giaovien') {
            $accessMessage = 'Không thể truy cập: Chỉ dành cho tài khoản loại "giaovien"';
        } else {
            $canAccessTeachingSchedule = true;
            $accessMessage = 'Có thể truy cập chức năng Tra cứu lịch dạy';
        }
        ?>

        <?php if ($canAccessTeachingSchedule): ?>
            <div class="status-box success">
                <strong>✅ Quyền truy cập Tra cứu lịch dạy: CÓ</strong><br>
                <?php echo $accessMessage; ?>
            </div>
        <?php else: ?>
            <div class="status-box warning">
                <strong>⚠️ Quyền truy cập Tra cứu lịch dạy: KHÔNG</strong><br>
                <?php echo $accessMessage; ?>
            </div>
        <?php endif; ?>

        <h2 style="margin-bottom: 15px; color: #333;">📋 Thông tin Session:</h2>

        <table class="info-table">
            <tr>
                <th>Tham số</th>
                <th>Giá trị</th>
                <th>Trạng thái</th>
            </tr>
            <tr>
                <td><strong>maTaiKhoan</strong></td>
                <td><?php echo isset($_SESSION['maTaiKhoan']) ? $_SESSION['maTaiKhoan'] : '<em style="color: #999;">Không có</em>'; ?></td>
                <td>
                    <?php if (isset($_SESSION['maTaiKhoan'])): ?>
                        <span class="badge success">✓ Có</span>
                    <?php else: ?>
                        <span class="badge error">✗ Không</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>maGV</strong></td>
                <td><?php echo isset($_SESSION['maGV']) ? $_SESSION['maGV'] : '<em style="color: #999;">Không có</em>'; ?></td>
                <td>
                    <?php if (isset($_SESSION['maGV'])): ?>
                        <span class="badge success">✓ Có</span>
                    <?php else: ?>
                        <span class="badge error">✗ Không</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>loaiTaiKhoan</strong></td>
                <td><?php echo isset($_SESSION['loaiTaiKhoan']) ? '<code>' . $_SESSION['loaiTaiKhoan'] . '</code>' : '<em style="color: #999;">Không có</em>'; ?></td>
                <td>
                    <?php if (isset($_SESSION['loaiTaiKhoan']) && $_SESSION['loaiTaiKhoan'] === 'giaovien'): ?>
                        <span class="badge success">✓ Giáo viên</span>
                    <?php elseif (isset($_SESSION['loaiTaiKhoan'])): ?>
                        <span class="badge error">✗ <?php echo $_SESSION['loaiTaiKhoan']; ?></span>
                    <?php else: ?>
                        <span class="badge error">✗ Không</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>hoTen</strong></td>
                <td><?php echo isset($_SESSION['hoTen']) ? htmlspecialchars($_SESSION['hoTen']) : '<em style="color: #999;">Không có</em>'; ?></td>
                <td>
                    <?php if (isset($_SESSION['hoTen'])): ?>
                        <span class="badge success">✓ Có</span>
                    <?php else: ?>
                        <span class="badge error">✗ Không</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>tenDangNhap</strong></td>
                <td><?php echo isset($_SESSION['tenDangNhap']) ? htmlspecialchars($_SESSION['tenDangNhap']) : '<em style="color: #999;">Không có</em>'; ?></td>
                <td>
                    <?php if (isset($_SESSION['tenDangNhap'])): ?>
                        <span class="badge success">✓ Có</span>
                    <?php else: ?>
                        <span class="badge error">✗ Không</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <h2 style="margin-bottom: 15px; color: #333;">📊 Tất cả biến Session:</h2>
        <div style="background: #f8f9ff; padding: 15px; border-radius: 5px; overflow-x: auto;">
            <pre style="margin: 0; font-size: 12px;"><?php print_r($_SESSION); ?></pre>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
            <?php if ($canAccessTeachingSchedule): ?>
                <a href="../controller/cTeachingSchedule.php?action=dashboard" class="btn btn-primary">
                    🚀 Vào hệ thống Tra cứu lịch dạy
                </a>
            <?php else: ?>
                <button class="btn btn-secondary" disabled style="opacity: 0.6; cursor: not-allowed;">
                    🔒 Không có quyền truy cập
                </button>
            <?php endif; ?>
            
            <a href="../public/login.php" class="btn btn-secondary">
                🔑 Đăng nhập
            </a>

            <button onclick="location.reload()" class="btn btn-secondary">
                🔄 Làm mới
            </button>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #f8f9ff; border-radius: 5px;">
            <h3 style="color: #667eea; margin-bottom: 10px;">💡 Hướng dẫn:</h3>
            <ul style="line-height: 1.8; color: #666;">
                <li>Để truy cập chức năng <strong>Tra cứu lịch dạy</strong>, cần đáp ứng:</li>
                <li style="margin-left: 20px;">✓ Đã đăng nhập thành công</li>
                <li style="margin-left: 20px;">✓ Session có <code>maTaiKhoan</code></li>
                <li style="margin-left: 20px;">✓ Session có <code>maGV</code> (mã giáo viên)</li>
                <li style="margin-left: 20px;">✓ <code>loaiTaiKhoan</code> phải là <code>"giaovien"</code></li>
                <li style="margin-top: 10px;"><strong>Lưu ý:</strong> Các loại tài khoản khác (quantrivien, bangiamhieu, hocsinh, phuhuynh, ttbm) KHÔNG được truy cập chức năng này.</li>
            </ul>
        </div>
    </div>
</body>
</html>
