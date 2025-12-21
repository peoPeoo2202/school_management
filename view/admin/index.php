<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

// Lấy thông tin người dùng
$userName = $_SESSION['hoTen'] ?? 'Administrator';
$userInitial = strtoupper(substr($userName, 0, 1));

// Kết nối database để lấy thống kê
require_once('../../model/mConnect.php');
$db = new mConnect();
$conn = $db->mConnect();

// Đếm số lượng
$studentsCount = 0;
$teachersCount = 0;
$parentsCount = 0;
$classCount = 0;
$departmentCount = 0;
$linkedParentsCount = 0;
$activeAccountsCount = 0;
$totalClassesCount = 0;

if ($conn) {
    $studentsCount = $conn->query("SELECT COUNT(*) as total FROM hocsinh")->fetch_assoc()['total'] ?? 0;
    $teachersCount = $conn->query("SELECT COUNT(*) as total FROM giaovien")->fetch_assoc()['total'] ?? 0;
    $parentsCount = $conn->query("SELECT COUNT(*) as total FROM phuhuynh")->fetch_assoc()['total'] ?? 0;
    $classCount = $conn->query("SELECT COUNT(DISTINCT maLop) as total FROM hocsinh")->fetch_assoc()['total'] ?? 0;
    $departmentCount = $conn->query("SELECT COUNT(DISTINCT toBoMon) as total FROM giaovien WHERE toBoMon IS NOT NULL")->fetch_assoc()['total'] ?? 0;
    $linkedParentsCount = $conn->query("SELECT COUNT(*) as total FROM hocsinh WHERE maPH IS NOT NULL AND maPH != ''")->fetch_assoc()['total'] ?? 0;
    $activeAccountsCount = $conn->query("SELECT COUNT(*) as total FROM taikhoan WHERE trangThaiTaiKhoan = 'active'")->fetch_assoc()['total'] ?? 0;
    $totalClassesCount = $conn->query("SELECT COUNT(DISTINCT maLop) as total FROM lophoc")->fetch_assoc()['total'] ?? 0;
    
    $db->mDisconnect($conn);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị viên - Hệ thống Quản lý Trường học</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <!-- ===================================
             SIDEBAR NAVIGATION
             =================================== -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i> QLTH
                </a>
                <div class="sidebar-subtitle">Hệ thống Quản lý Trường học</div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">MENU CHÍNH</div>
                    
                    <div class="nav-item">
                        <a href="index.php" class="nav-link active">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="students.php" class="nav-link">
                            <i class="fas fa-users-cog"></i>
                            <span>Quản lý thông tin</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="schedule.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Lên lịch</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="permissions.php" class="nav-link">
                            <i class="fas fa-user-shield"></i>
                            <span>Phân quyền</span>
                        </a>
                    </div>
                </div>

            </nav>

            <div class="sidebar-footer">
                <a href="../../public/index.php?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </aside>

        <!-- ===================================
             MAIN CONTENT
             =================================== -->
        <main class="main-content">
            <!-- HEADER -->
            <header class="header">
                <div class="breadcrumb">
                    <div class="breadcrumb-item">
                        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    </div>
                    <span class="breadcrumb-separator">/</span>
                    <div class="breadcrumb-item">
                        <span class="breadcrumb-current">Trang chủ</span>
                    </div>
                </div>

                <div class="header-actions">
                    <button class="notification-btn" title="Thông báo">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>

                    <div class="user-info">
                        <div class="user-avatar"><?php echo $userInitial; ?></div>
                        <div class="user-details">
                            <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                            <div class="user-role">Quản trị viên</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- CONTENT -->
            <section class="content">
                <h1 class="page-title">Chào mừng trở lại, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>!</h1>
                <p class="page-subtitle">Trang tổng quan quản lý hệ thống trường học</p>

                <div class="cards-grid">
                    <!-- CARD: Quản lý học sinh -->
                    <div class="card" onclick="window.location.href='students.php'">
                        <div class="card-icon students">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3 class="card-title">Quản lý thông tin học sinh</h3>
                        <p class="card-description">
                            Quản lý hồ sơ, theo dõi quá trình học tập, khen thưởng, kỷ luật và vi phạm của học sinh
                        </p>
                        <div class="card-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $studentsCount; ?></div>
                                <div class="stat-label">Học sinh</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $classCount; ?></div>
                                <div class="stat-label">Lớp học</div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD: Quản lý giáo viên -->
                    <div class="card" onclick="window.location.href='teachers.php'">
                        <div class="card-icon teachers">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h3 class="card-title">Quản lý thông tin giáo viên</h3>
                        <p class="card-description">
                            Quản lý hồ sơ giáo viên, phân công giảng dạy, lịch dạy và đánh giá năng lực
                        </p>
                        <div class="card-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $teachersCount; ?></div>
                                <div class="stat-label">Giáo viên</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $departmentCount; ?></div>
                                <div class="stat-label">Tổ bộ môn</div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD: Quản lý phụ huynh -->
                    <div class="card" onclick="window.location.href='parents.php'">
                        <div class="card-icon parents">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="card-title">Quản lý thông tin phụ huynh</h3>
                        <p class="card-description">
                            Quản lý thông tin liên hệ phụ huynh, kết nối với học sinh và theo dõi tương tác
                        </p>
                        <div class="card-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $parentsCount; ?></div>
                                <div class="stat-label">Phụ huynh</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $linkedParentsCount; ?></div>
                                <div class="stat-label">Đã liên kết</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Section -->
                <div style="margin-top: 40px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2 style="color: #2c3e50; margin-bottom: 20px; font-size: 22px;">
                        <i class="fas fa-chart-line" style="color: #3498db; margin-right: 10px;"></i>
                        Thống kê nhanh
                    </h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div style="padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white;">
                            <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;">
                                <?php echo $studentsCount + $teachersCount + $parentsCount; ?>
                            </div>
                            <div style="font-size: 14px; opacity: 0.9;">Tổng người dùng</div>
                        </div>
                        <div style="padding: 20px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 8px; color: white;">
                            <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;">
                                <?php echo $activeAccountsCount; ?>
                            </div>
                            <div style="font-size: 14px; opacity: 0.9;">Tài khoản hoạt động</div>
                        </div>
                        <div style="padding: 20px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 8px; color: white;">
                            <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;">
                                <?php echo $totalClassesCount; ?>
                            </div>
                            <div style="font-size: 14px; opacity: 0.9;">Lớp học</div>
                        </div>
                        <div style="padding: 20px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-radius: 8px; color: white;">
                            <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;">
                                <?php echo date('Y'); ?>-<?php echo date('Y') + 1; ?>
                            </div>
                            <div style="font-size: 14px; opacity: 0.9;">Năm học hiện tại</div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Smooth scroll và active states
        document.addEventListener('DOMContentLoaded', function() {
            // Notification button
            document.querySelector('.notification-btn').addEventListener('click', function() {
                alert('Bạn có 3 thông báo mới:\n\n1. Cập nhật danh sách học sinh lớp 6A\n2. Yêu cầu phê duyệt báo cáo\n3. Lịch họp Ban giám hiệu');
            });

            // Nav manage info - hiển thị submenu (tương lai)
            const navManageInfo = document.getElementById('nav-manage-info');
            if (navManageInfo) {
                navManageInfo.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Tương lai: Toggle submenu
                    alert('Chức năng Quản lý thông tin:\n- Học sinh\n- Giáo viên\n- Phụ huynh');
                });
            }

            // Card hover effects
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.cursor = 'pointer';
                });
            });

            console.log('Admin Dashboard loaded successfully! 🎉');
        });
    </script>
</body>
</html>
