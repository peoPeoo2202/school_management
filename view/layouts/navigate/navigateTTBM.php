<?php
// Navigation menu cho Tổ trưởng bộ môn (TTBM)
// Kiểm tra session và quyền truy cập

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true || $_SESSION['loaiTaiKhoan'] !== 'ttbm') {
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
    .ttbm-nav {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .ttbm-nav h3 {
        color: white;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .ttbm-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .ttbm-nav ul li {
        margin-bottom: 10px;
    }

    .ttbm-nav ul li a {
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

    .ttbm-nav ul li a:hover,
    .ttbm-nav ul li a.active {
        background: rgba(255, 255, 255, 0.2);
        transform: translateX(5px);
    }

    .ttbm-nav ul li a i {
        width: 20px;
        text-align: center;
    }

    .nav-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.2);
        margin: 15px 0;
    }
</style>

<aside class="ttbm-nav">
    <h3>
        <i class="fas fa-user-graduate"></i>
        Menu Tổ trưởng bộ môn
    </h3>
    <ul>
        <li>
            <a href="index.php">
                <i class="fas fa-home"></i>
                <span>Trang chủ</span>
            </a>
        </li>
        
        <div class="nav-divider"></div>
        
        <li>
            <a href="vGradingAssignment.php">
                <i class="fas fa-clipboard-check"></i>
                <span>Phân công chấm thi</span>
            </a>
        </li>
        
        <li>
            <a href="vExamApproval.php">
                <i class="fas fa-file-alt"></i>
                <span>Duyệt đề thi</span>
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
