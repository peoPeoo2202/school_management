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
// Nếu bị mất maNhom thì lấy lại từ DB theo maTaiKhoan
if (empty($_SESSION['maNhom']) && !empty($_SESSION['maTaiKhoan'])) {
    require_once(__DIR__ . '/../../../model/mUser.php');
    $m = new mUser();
    $u = $m->getUserById((int)$_SESSION['maTaiKhoan']);
    if (!empty($u) && isset($u['maNhom'])) {
        $_SESSION['maNhom'] = $u['maNhom'];
    }
}

$maNhom = $_SESSION['maNhom'] ?? null;
echo "<!-- DEBUG maNhom: " . ($_SESSION['maNhom'] ?? 'NULL') . " -->";

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
            <a href="<?php echo url('view/teacher/index.php'); ?>" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Lớp chủ nhiệm (với dropdown) -->
        <?php if ($maNhom == 3006): ?>
            <li class="menu-parent">
                <a href="#" class="menu-item menu-toggle" data-submenu="requests">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Lớp chủ nhiệm</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <ul class="submenu" id="requests-submenu">
                    <li>
                        <a href="../../view/teacher/vListofStudents.php" class="submenu-item">
                            <i class="fas fa-users"></i>
                            <span>Danh sách học sinh</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../view/teacher/vClassPerformance.php" class="submenu-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Kết quả học tập lớp</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../view/teacher/vStudentClassification.php" class="submenu-item">
                            <i class="fas fa-star"></i>
                            <span>Xếp loại học sinh</span>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>


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
                <i class="fa-solid fa-clipboard-user"></i>
                <span>Danh sách lớp</span>
            </a>
        </li>
        <!-- Giao bài tập -->
        <li>
            <a href="<?php echo url('view/teacher/vAssignHomework.php'); ?>" class="menu-item">
                <i class="fas fa-clipboard-list"></i>
                <span>Giao bài tập</span>
            </a>
        </li>
        <!-- Nhập điểm -->
        <li>
            <a href="<?php echo url('view/teacher/vInsertGrade.php'); ?>" class="menu-item">
                <i class="fas fa-edit"></i>
                <span>Nhập điểm</span>
            </a>
        </li>
        <!-- Gửi đề thi -->
        <li>
            <a href="<?php echo url('view/teacher/vSubmitExam.php'); ?>" class="menu-item">
                <i class="fas fa-file-upload"></i>
                <span>Gửi đề thi</span>
            </a>
        </li>

        <!-- Tra cứu giảng dạy -->
        <li class="menu-parent"> <a href="#" class="menu-item menu-toggle" data-submenu="searchTeaching"> <i class="fa-solid fa-magnifying-glass"></i> <span>Tra cứu giảng dạy</span> <i class="fas fa-chevron-right"></i> </a>
            <ul class="submenu" id="searchTeaching-submenu">
                <li> <a href="<?php echo url('view/teacher/vExamSupervision.php'); ?>" class="submenu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'vExamSupervision.php') ? 'active' : ''; ?>"> <i class="fas fa-eye"></i> <span>Phân công coi thi</span> </a> </li>
                <li> <a href="<?php echo url('view/teacher/vGradingAssignment.php'); ?>" class="submenu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'vGradingAssignment.php') ? 'active' : ''; ?>"> <i class="fas fa-pen-square"></i> <span>Phân công chấm điểm</span> </a> </li>
            </ul>
        </li>
        <!-- Gửi yêu cầu -->
        <li class="menu-parent">
            <a href="#" class="menu-item menu-toggle" data-submenu="requests">
                <i class="fas fa-paper-plane"></i>
                <span>Gửi yêu cầu</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <ul class="submenu" id="requests-submenu">
                <li>
                    <a href="index.php?action=yeucau&type=suadiem" class="submenu-item">
                        <i class="fas fa-edit"></i>
                        <span>Yêu cầu sửa điểm</span>
                    </a>
                </li>
                <li>
                    <a href="index.php?action=yeucau&type=nghiphep" class="submenu-item">
                        <i class="fas fa-calendar-times"></i>
                        <span>Xin nghỉ phép</span>
                    </a>
                </li>
                <li>
                    <a href="index.php?action=danhsachyeucau" class="submenu-item">
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