<?php
// Navigation menu cho Ban giám hiệu
// Kiểm tra session và quyền truy cập

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true || $_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    echo '
    <aside class="sidebar">
      <ul>
        <li><a href="#">Không có quyền truy cập</a></li>
      </ul>
    </aside>';
    return;
}

$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';
?>

<nav class="teacher-navbar">
    <div class="navbar-header">
        <h2><i class="fas fa-user-tie"></i> Hệ thống BGH</h2>
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
            <p class="user-role">Ban giám hiệu</p>
        </div>
    </div>

    <ul class="navbar-menu">
        <!-- Trang chủ báo cáo -->
        <li>
            <a href="../../controller/cBGHReport.php?action=index" class="menu-item">
                <i class="fas fa-home"></i>
                <span>Trang chủ báo cáo</span>
            </a>
        </li>

        <!-- Báo cáo học tập & chuyên cần -->
        <li class="menu-parent">
            <a href="#" class="menu-item menu-toggle" data-submenu="academic-reports">
                <i class="fas fa-graduation-cap"></i>
                <span>Báo cáo học tập</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <ul class="submenu" id="academic-reports-submenu">
                <li>
                    <a href="../../controller/cBGHReport.php?action=hoc-tap" class="submenu-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Kết quả học tập</span>
                    </a>
                </li>
                <li>
                    <a href="../../controller/cBGHReport.php?action=chuyen-can" class="submenu-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>Báo cáo chuyên cần</span>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Báo cáo giảng dạy -->
        <li>
            <a href="../../controller/cBGHReport.php?action=giang-day" class="menu-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Báo cáo giảng dạy</span>
            </a>
        </li>

        <!-- Báo cáo tổng hợp & Đánh giá -->
        <li class="menu-parent">
            <a href="#" class="menu-item menu-toggle" data-submenu="evaluation-reports">
                <i class="fas fa-star"></i>
                <span>Báo cáo đánh giá</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <ul class="submenu" id="evaluation-reports-submenu">
                <li>
                    <a href="../../controller/cBGHReport.php?action=tong-hop" class="submenu-item">
                        <i class="fas fa-file-contract"></i>
                        <span>Báo cáo tổng hợp</span>
                    </a>
                </li>
                <li>
                    <a href="../../controller/cBGHReport.php?action=danh-gia" class="submenu-item">
                        <i class="fas fa-star"></i>
                        <span>Báo cáo kết quả đánh giá</span>
                    </a>
                </li>
                <li>
                    <a href="../../controller/cBGHReport.php?action=ket-qua-danh-gia" class="submenu-item">
                        <i class="fas fa-medal"></i>
                        <span>Danh hiệu học sinh</span>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Thống kê -->
        <li class="menu-parent">
            <a href="#" class="menu-item menu-toggle" data-submenu="statistics">
                <i class="fas fa-chart-bar"></i>
                <span>Thống kê</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <ul class="submenu" id="statistics-submenu">
                <li>
                    <a href="../../controller/cBGHReport.php?action=thong-ke-diem" class="submenu-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Thống kê điểm môn học</span>
                    </a>
                </li>
                <li>
                    <a href="../../controller/cBGHReport.php?action=thong-ke-hoc-sinh" class="submenu-item">
                        <i class="fas fa-users"></i>
                        <span>Thống kê học sinh</span>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Quản lý đề thi & Yêu cầu -->
        <li class="menu-parent">
            <a href="#" class="menu-item menu-toggle" data-submenu="exam-management">
                <i class="fas fa-file-alt"></i>
                <span>Quản lý đề thi</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <ul class="submenu" id="exam-management-submenu">
                <li>
                    <a href="../../controller/cChonDe.php?action=index" class="submenu-item">
                        <i class="fas fa-file-alt"></i>
                        <span>Chọn đề thi</span>
                    </a>
                </li>
                <li>
                    <a href="../../controller/cYeuCau.php?action=danhsach" class="submenu-item">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Xử lý yêu cầu</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>

    <div class="navbar-footer">
        <a href="../../public/logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Đăng xuất</span>
        </a>
    </div>
</nav>

<script>
    // Flag để chắc chắn script chỉ run 1 lần
    if (!window.__navigateBGHInitialized) {
        window.__navigateBGHInitialized = true;

        document.addEventListener('DOMContentLoaded', function() {
            const menuToggles = document.querySelectorAll('.teacher-navbar .menu-toggle');
            // Toggle submenu
            menuToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();

                    const dataSubmenu = this.dataset.submenu;
                    const submenuId = dataSubmenu + '-submenu';
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
            const menuItems = document.querySelectorAll('.teacher-navbar .menu-item:not(.menu-toggle)');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        document.querySelector('.teacher-navbar').classList.remove('open');
                    }
                });
            });
        });
    }
</script>
