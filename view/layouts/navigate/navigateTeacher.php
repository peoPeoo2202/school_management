<?php
// Load config
require_once(__DIR__ . '/../../../config.php');

// Kiểm tra session
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: " . url('public/index.php'));
    exit();
}

if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: " . url('public/index.php?error=access_denied'));
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
$maGV = $_SESSION['maGV'] ?? '';
?>

<nav class="teacher-navbar">
    <div class="navbar-header">
        <h2><i class="fas fa-chalkboard-teacher"></i> Hệ thống GV</h2>
        <button class="navbar-toggle" id="navbarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="navbar-user">
        <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-details">
            <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
            <p class="user-role">Giáo viên</p>
        </div>
    </div>

    <ul class="navbar-menu">
        <!-- Dashboard -->
        <li>
            <a href="<?php echo url('view/teacher/dashboard.php'); ?>" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Lịch dạy -->
        <li>
            <a href="../../view/teacher/vTeachingSchedule.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'vTeachingSchedule.php') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Lịch dạy</span>
            </a>
        </li>

        <!-- Danh sách lớp -->
        <li>
            <a href="<?php echo url('view/teacher/vClassList.php'); ?>" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'vClassList.php') ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                <span>Danh sách lớp</span>
            </a>
        </li>

        <!-- Phân công coi thi -->
        <li>
            <a href="<?php echo url('view/teacher/vExamSupervision.php'); ?>" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'vExamSupervision.php') ? 'active' : ''; ?>">
                <i class="fas fa-eye"></i>
                <span>Phân công coi thi</span>
            </a>
        </li>

        <!-- Phân công chấm điểm -->
        <li>
            <a href="<?php echo url('view/teacher/vGradingAssignment.php'); ?>" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'vGradingAssignment.php') ? 'active' : ''; ?>">
                <i class="fas fa-pen-square"></i>
                <span>Phân công chấm điểm</span>
            </a>
        </li>

        <!-- Gửi yêu cầu (với dropdown) -->
        <li class="menu-parent">
            <a href="#" class="menu-item menu-toggle" data-submenu="requests">
                <i class="fas fa-paper-plane"></i>
                <span>Gửi yêu cầu</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <ul class="submenu" id="requests-submenu">
                <li>
                    <a href="<?php echo url('controller/cTeacherRequest.php?action=grade_correction_form'); ?>" class="submenu-item">
                        <i class="fas fa-edit"></i>
                        <span>Yêu cầu sửa điểm</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('controller/cTeacherRequest.php?action=leave_request_form'); ?>" class="submenu-item">
                        <i class="fas fa-calendar-times"></i>
                        <span>Xin nghỉ phép</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('controller/cTeacherRequest.php?action=request_list'); ?>" class="submenu-item">
                        <i class="fas fa-list-alt"></i>
                        <span>Danh sách yêu cầu</span>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Báo cáo & Thống kê (với dropdown) -->
        <li class="menu-parent">
            <a href="#" class="menu-item menu-toggle" data-submenu="reports">
                <i class="fas fa-chart-bar"></i>
                <span>Báo cáo & Thống kê</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <ul class="submenu" id="reports-submenu">
                <li>
                    <a href="<?php echo url('controller/cReport.php?action=academic'); ?>" class="submenu-item <?php echo (isset($_GET['action']) && $_GET['action'] == 'academic') ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        <span>Báo cáo kết quả học tập</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('controller/cReport.php?action=attendance'); ?>" class="submenu-item <?php echo (isset($_GET['action']) && $_GET['action'] == 'attendance') ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Báo cáo chuyên cần</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('controller/cReport.php?action=teaching'); ?>" class="submenu-item <?php echo (isset($_GET['action']) && $_GET['action'] == 'teaching') ? 'active' : ''; ?>">
                        <i class="fas fa-chalkboard"></i>
                        <span>Báo cáo giảng dạy</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('controller/cReport.php?action=grade_stats'); ?>" class="submenu-item <?php echo (isset($_GET['action']) && $_GET['action'] == 'grade_stats') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Thống kê điểm môn học</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('controller/cReport.php?action=student_stats'); ?>" class="submenu-item <?php echo (isset($_GET['action']) && $_GET['action'] == 'student_stats') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Thống kê học sinh</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('controller/cReport.php?action=submit'); ?>" class="submenu-item <?php echo (isset($_GET['action']) && $_GET['action'] == 'submit') ? 'active' : ''; ?>">
                        <i class="fas fa-upload"></i>
                        <span>Nộp báo cáo</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>

    <div class="navbar-footer">
        <a href="<?php echo url('public/logout.php'); ?>" class="logout-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Đăng xuất</span>
        </a>
    </div>
</nav>

<style>
    .teacher-navbar {
        min-width: 250px;
        background: linear-gradient(180deg, #2d5a8c 0%, #5081BE 100%);
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
        background: #1a2f42;
        border-bottom: 2px solid #5081BE;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .navbar-header h2 {
        margin: 0;
        font-size: 18px;
        color: #5081BE;
    }

    .navbar-toggle {
        display: none;
    }

    .navbar-user {
        padding: 8px;
        border-bottom: 1px solid #1a2f42;
        background: rgba(80, 129, 190, 0.1);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-avatar {
        font-size: 40px;
        color: #1a2f42;
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
        color: #95a5a6;
    }

    .navbar-menu {
        list-style: none;
        padding: 8px 0;
        margin: 0;
        flex: 1;
        overflow-y: auto;
    }

    .navbar-menu>li {
        margin: 0;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        text-decoration: none;
        color: #d4dce6;
        transition: all 0.3s;
        position: relative;
        font-size: 16px;
    }

    .menu-item i:first-child {
        min-width: 16px;
        text-align: center;
    }

    .menu-item:hover {
        background: rgba(80, 129, 190, 0.2);
        padding-left: 24px;
        color: white;
    }

    .menu-item.active {
        background: #5081BE;
        color: white;
        border-left: 2px solid #7EBADA;
        padding-left: 16px;
    }

    .menu-toggle {
        position: relative;
    }

    .menu-toggle i:last-child {
        margin-left: auto;
        transition: transform 0.3s;
    }

    .menu-toggle.open i:last-child {
        transform: rotate(90deg);
    }

    .submenu {
        list-style: none;
        padding: 0;
        margin: 0;
        background: rgba(0, 0, 0, 0.1);
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s;
    }

    .submenu.open {
        max-height: 500px;
    }

    .submenu-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 16px 16px 8px 32px;
        text-decoration: none;
        color: #a8b4c6;
        transition: all 0.3s;
        font-size: 14px;
    }

    .submenu-item i {
        width: 16px;
        text-align: center;
    }

    .submenu-item:hover {
        color: white;
        padding-left: 42px;
    }

    .navbar-footer {
        padding: 8px;
        margin-top: auto;
        border-top: 2px solid #7EBADA;
        background: #1a2f42;
        font-size: 16px;
    }

    .logout-link {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #CADEFA;
        text-decoration: none;
        transition: all 0.3s;
        padding: 8px;
        border-radius: 8px;
        border-left: 2px solid transparent;
    }

    .logout-link:hover {
        background: rgba(126, 186, 218, 0.2);
        color: #E0F2FC;
        border-left: 3px solid #7EBADA;
    }

    /* Scrollbar */
    .teacher-navbar::-webkit-scrollbar {
        display: none;
    }

    /* Firefox */
    .teacher-navbar {
        scrollbar-width: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .teacher-navbar {
            width: 250px;
            margin-left: -250px;
            transition: margin-left 0.3s;
        }

        .teacher-navbar.open {
            margin-left: 0;
        }

        .navbar-toggle {
            display: block;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle submenu
        const menuToggles = document.querySelectorAll('.menu-toggle');
        menuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const submenuId = this.dataset.submenu + '-submenu';
                const submenu = document.getElementById(submenuId);

                this.classList.toggle('open');
                submenu.classList.toggle('open');
            });
        });

        // Mobile navbar toggle
        const navbarToggle = document.getElementById('navbarToggle');
        if (navbarToggle) {
            navbarToggle.addEventListener('click', function() {
                document.querySelector('.teacher-navbar').classList.toggle('open');
            });
        }

        // Close navbar on mobile when menu item clicked
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    document.querySelector('.teacher-navbar').classList.remove('open');
                }
            });
        });
    });
</script>