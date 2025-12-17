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
?>

<style>
    .bgh-nav {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .bgh-nav h3 {
        color: white;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .bgh-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .bgh-nav ul li {
        margin-bottom: 10px;
    }

    .bgh-nav ul li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s;
        font-size: 14px;
    }

    .bgh-nav ul li a:hover,
    .bgh-nav ul li a.active {
        background: rgba(255, 255, 255, 0.2);
        transform: translateX(5px);
    }

    .bgh-nav ul li a i {
        width: 20px;
        text-align: center;
    }

    .nav-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.2);
        margin: 15px 0;
    }
</style>

<aside class="bgh-nav">
    <h3>
        <i class="fas fa-user-tie"></i>
        Menu Ban giám hiệu
    </h3>
    <ul>
        <li>
            <a href="../../controller/cBGHReport.php?action=index">
                <i class="fas fa-home"></i>
                <span>Trang chủ báo cáo</span>
            </a>
        </li>
        
        <div class="nav-divider"></div>
        
        <li>
            <a href="../../controller/cBGHReport.php?action=hoc-tap">
                <i class="fas fa-graduation-cap"></i>
                <span>Báo cáo kết quả học tập</span>
            </a>
        </li>
        
        <li>
            <a href="../../controller/cBGHReport.php?action=chuyen-can">
                <i class="fas fa-calendar-check"></i>
                <span>Báo cáo chuyên cần</span>
            </a>
        </li>
        
        <li>
            <a href="../../controller/cBGHReport.php?action=giang-day">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Báo cáo giảng dạy</span>
            </a>
        </li>
        
        <li>
            <a href="../../controller/cBGHReport.php?action=tong-hop">
                <i class="fas fa-file-contract"></i>
                <span>Báo cáo tổng hợp</span>
            </a>
        </li>
        
        <li>
            <a href="../../controller/cBGHReport.php?action=danh-gia">
                <i class="fas fa-star"></i>
                <span>Báo cáo kết quả đánh giá</span>
            </a>
        </li>
        
        <li>
            <a href="../../controller/cBGHReport.php?action=ket-qua-danh-gia">
                <i class="fas fa-medal"></i>
                <span>Báo cáo danh hiệu học sinh</span>
            </a>
        </li>
        
        <div class="nav-divider"></div>
        
        <li>
            <a href="../../controller/cBGHReport.php?action=thong-ke-diem">
                <i class="fas fa-chart-bar"></i>
                <span>Thống kê điểm môn học</span>
            </a>
        </li>
        
        <li>
            <a href="../../controller/cBGHReport.php?action=thong-ke-hoc-sinh">
                <i class="fas fa-users"></i>
                <span>Thống kê số liệu học sinh</span>
            </a>
        </li>
        
        <div class="nav-divider"></div>
        
        <li>
            <a href="../../controller/cChonDe.php?action=index">
                <i class="fas fa-file-alt"></i>
                <span>Chọn đề thi</span>
            </a>
        </li>
        
        <li>
            <a href="../../controller/cYeuCau.php?action=danhsach">
                <i class="fas fa-clipboard-check"></i>
                <span>Xử lý yêu cầu</span>
            </a>
        </li>
        
        <div class="nav-divider"></div>
        
        <li>
            <a href="../../public/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </a>
        </li>
    </ul>
</aside>

<script>
    // Highlight active menu item based on current URL
    document.addEventListener('DOMContentLoaded', function() {
        const currentUrl = window.location.href;
        const menuLinks = document.querySelectorAll('.bgh-nav ul li a');
        
        menuLinks.forEach(link => {
            if (currentUrl.includes(link.getAttribute('href'))) {
                link.classList.add('active');
            }
        });
    });
</script>
