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
        <h2><i class="fas fa-users"></i> Hệ thống PH</h2>
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
            <!-- Thời khóa biểu -->
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

<style>
    .student-navbar {
        min-width: 250px;
        background: linear-gradient(180deg, #8e44ad 0%, #9b59b6 100%);
        color: white;
        padding: 0;
        position: relative;
        height: 100vh;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    .navbar-header {
        padding: 16px;
        background: #6c3483;
        border-bottom: 2px solid #9b59b6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .navbar-header h2 {
        margin: 0;
        font-size: 18px;
        color: #9b59b6;
    }

    .navbar-user {
        padding: 8px;
        border-bottom: 1px solid #6c3483;
        background: rgba(142, 68, 173, 0.1);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-avatar {
        font-size: 40px;
        color: #6c3483;
    }

    .user-details {
        flex: 1;
    }

    .user-name {
        margin: 0;
        font-weight: 500;
        font-size: 14px;
        color: white;
    }

    .user-role {
        margin: 0;
        font-size: 12px;
        color: #d7bde2;
    }

    .nav-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .navbar-menu {
        list-style: none;
        padding: 8px 0;
        margin: 0;
        flex: 1;
        overflow-y: auto;
    }

    .navbar-menu > li {
        margin: 0;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        text-decoration: none;
        color: #e8daef;
        transition: all 0.3s;
        position: relative;
        font-size: 16px;
    }

    .menu-item i {
        min-width: 16px;
        text-align: center;
    }

    .menu-item:hover {
        background: rgba(142, 68, 173, 0.2);
        padding-left: 24px;
        color: white;
    }

    .menu-item.active {
        background: #9b59b6;
        color: white;
        border-left: 2px solid #d7bde2;
        padding-left: 16px;
    }

    .navbar-footer {
        padding: 8px;
        margin-top: auto;
        border-top: 2px solid #d7bde2;
        background: #6c3483;
        font-size: 16px;
    }

    .logout-link {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #e8daef;
        text-decoration: none;
        transition: all 0.3s;
        padding: 8px;
        border-radius: 8px;
        border-left: 2px solid transparent;
    }

    .logout-link:hover {
        background: rgba(215, 189, 226, 0.2);
        color: white;
        border-left: 3px solid #d7bde2;
    }

    /* Scrollbar */
    .student-navbar::-webkit-scrollbar {
        display: none;
    }

    .student-navbar {
        scrollbar-width: none;
    }
</style>
