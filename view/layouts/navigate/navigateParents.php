<?php
// Kiểm tra session
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

if ($_SESSION['loaiTaiKhoan'] !== 'phuhuynh') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Phụ huynh';
$maPH = $_SESSION['maPH'] ?? '';
?>

<nav class="student-navbar">
    <div class="navbar-header">
        <h2><i class="fas fa-graduation-cap"></i> Hệ thống HS</h2>
    </div>

    <div class="navbar-user">
        <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-details">
            <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
            <p class="user-role">Phụ huynh</p>
        </div>
    </div>
    <div class="nav-content">
        <ul class="navbar-menu">
            <!-- Dashboard -->
            <li>
                <a href="index.php?page=timeTable" class="menu-item <?php echo (!isset($_GET['page']) || $_GET['page'] == 'timeTable') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Thời khóa biểu</span>
                </a>
            </li>

            <!-- Kết quả học tập -->
            <li>
                <a href="index.php?page=grades" class="menu-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'grades') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Kết quả học tập</span>
                </a>
            </li>

            <!-- Xếp loại -->
            <li>
                <a href="index.php?page=classification" class="menu-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'classification') ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i>
                    <span>Xếp loại</span>
                </a>
            </li>
        </ul>

        <div class="navbar-footer">
            <a href="../../public/logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </a>
        </div>
    </div>

</nav>