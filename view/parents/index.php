<?php
session_start();
include_once("../../model/mParent.php");

if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "phuhuynh") {
  header("Location: ../../public/index.php");
  exit;
}

$model = new mParent();

// Debug: Hiển thị thông tin session
echo "<!-- DEBUG SESSION: tenDangNhap = " . ($_SESSION["tenDangNhap"] ?? 'NULL') . " -->";
echo "<!-- DEBUG SESSION: loaiTaiKhoan = " . ($_SESSION["loaiTaiKhoan"] ?? 'NULL') . " -->";

// Debug: Kiểm tra tài khoản có tồn tại không
$conn = (new mConnect())->mConnect();
$checkAccountSql = "SELECT maTaiKhoan, hoTen, loaiTaiKhoan FROM taikhoan WHERE tenDangNhap = ?";
$checkStmt = $conn->prepare($checkAccountSql);
$checkStmt->bind_param("s", $_SESSION["tenDangNhap"]);
$checkStmt->execute();
$accountData = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();
echo "<!-- DEBUG ACCOUNT: " . print_r($accountData, true) . " -->";

// Debug: Kiểm tra bản ghi phuhuynh
if ($accountData) {
    $checkParentSql = "SELECT * FROM phuhuynh WHERE maTaiKhoan = ?";
    $checkStmt2 = $conn->prepare($checkParentSql);
    $checkStmt2->bind_param("i", $accountData['maTaiKhoan']);
    $checkStmt2->execute();
    $parentData = $checkStmt2->get_result()->fetch_assoc();
    $checkStmt2->close();
    echo "<!-- DEBUG PARENT RECORD: " . print_r($parentData, true) . " -->";
}

$info = $model->getParentInfoByAccount($_SESSION["tenDangNhap"]);

// Debug: Hiển thị kết quả query
echo "<!-- DEBUG INFO FROM MODEL: " . ($info ? print_r($info, true) : 'NULL') . " -->";

if (!$info) {
  echo "<div style='padding: 40px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 8px; margin: 20px;'>";
  echo "<h3><i class='fas fa-exclamation-triangle'></i> Không tìm thấy thông tin phụ huynh</h3>";
  echo "<p><strong>Tên đăng nhập:</strong> " . htmlspecialchars($_SESSION["tenDangNhap"]) . "</p>";
  echo "<p><strong>Loại tài khoản:</strong> " . htmlspecialchars($_SESSION["loaiTaiKhoan"]) . "</p>";
  if ($accountData) {
      echo "<p><strong>Tài khoản tồn tại:</strong> Có (maTaiKhoan = " . $accountData['maTaiKhoan'] . ")</p>";
      echo "<p><strong>Bản ghi phuhuynh:</strong> " . ($parentData ? "Có (maPH = " . $parentData['maPH'] . ")" : "KHÔNG TỒN TẠI") . "</p>";
      if (!$parentData) {
          echo "<p style='color: red; font-weight: bold;'>⚠️ Vấn đề: Tài khoản tồn tại nhưng không có bản ghi trong bảng phuhuynh với maTaiKhoan = " . $accountData['maTaiKhoan'] . "</p>";
          echo "<p>Giải pháp: Cần tạo bản ghi phuhuynh hoặc cập nhật maTaiKhoan trong bảng phuhuynh.</p>";
      }
  } else {
      echo "<p><strong>Tài khoản:</strong> KHÔNG TỒN TẠI</p>";
  }
  echo "<p><strong>Hướng dẫn debug:</strong> Xem HTML source (Ctrl+U) để xem thông tin chi tiết.</p>";
  echo "<p>Vui lòng liên hệ quản trị viên để được hỗ trợ.</p>";
  echo "<p><a href='../../public/logout.php' style='color: #856404; text-decoration: underline;'>Đăng xuất</a></p>";
  echo "</div>";
  exit;
}

// Lưu thông tin phụ huynh vào session - LUÔN cập nhật để đảm bảo đúng với tài khoản hiện tại
$_SESSION['maPH'] = $info['maPH'];
$_SESSION['hoTen'] = $info['hoTen'];

// Lấy danh sách con của phụ huynh
$children = $model->getStudentsByParent($info['maPH']);
$_SESSION['children'] = $children;

// Nếu có con, lưu thông tin con đầu tiên làm mặc định (có thể thay đổi sau)
if (!empty($children)) {
    $_SESSION['selectedChildIndex'] = $_SESSION['selectedChildIndex'] ?? 0;
    $_SESSION['maHS'] = $children[$_SESSION['selectedChildIndex']]['maHS'];
} else {
    $_SESSION['maHS'] = null;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ phụ huynh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Reset body margin */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        /* Main wrapper with flexbox layout */
        .main-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        
        /* Sidebar Navigation - Fixed position */
        .sidebar-nav {
            width: 250px;
            min-width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
            flex-shrink: 0;
        }
        
        /* Content area - Takes remaining space */
        .content-area {
            flex: 1;
            margin-left: 250px;
            min-height: 100vh;
            padding: 30px;
            background: #f5f5f5;
            width: calc(100% - 250px);
        }
        
        /* Responsive design for mobile */
        @media (max-width: 768px) {
            .sidebar-nav {
                transform: translateX(-250px);
                transition: transform 0.3s ease;
            }
            
            .sidebar-nav.active {
                transform: translateX(0);
            }
            
            .content-area {
                margin-left: 0;
                width: 100%;
            }
            
            .navbar-toggle {
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: #8e44ad;
                color: white;
                border: none;
                padding: 12px 16px;
                border-radius: 8px;
                cursor: pointer;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            }
            
            .navbar-toggle:hover {
                background: #9b59b6;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <div class="sidebar-nav">
            <?php include(__DIR__ . '/../layouts/navigate/navigateParent.php'); ?>
        </div>

        <!-- Main Content -->
        <div class="content-area">
            <?php
                // Nếu không có con, hiển thị thông báo
                if (empty($children)) {
                    echo '<div style="padding: 40px; text-align: center; background: white; border-radius: 8px; margin: 20px;">';
                    echo '<i class="fas fa-exclamation-circle" style="font-size: 48px; color: #f39c12; margin-bottom: 20px;"></i>';
                    echo '<h3 style="color: #333;">Chưa có thông tin con em</h3>';
                    echo '<p style="color: #666;">Vui lòng liên hệ nhà trường để cập nhật thông tin.</p>';
                    echo '</div>';
                } else {
                    // Hiển thị selector chọn con
                    echo '<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                    echo '<form method="GET" style="display: flex; align-items: center; gap: 15px;">';
                    echo '<label style="font-weight: 600; color: #333;"><i class="fas fa-user-graduate"></i> Xem thông tin của:</label>';
                    echo '<select name="childIndex" onchange="this.form.submit()" style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">';
                    
                    foreach ($children as $index => $child) {
                        $selected = ($index == ($_GET['childIndex'] ?? $_SESSION['selectedChildIndex'])) ? 'selected' : '';
                        echo '<option value="' . $index . '" ' . $selected . '>' . htmlspecialchars($child['hoTen']) . ' - ' . htmlspecialchars($child['tenLop']) . '</option>';
                    }
                    
                    echo '</select>';
                    // Keep current page parameter
                    if (isset($_GET['page'])) {
                        echo '<input type="hidden" name="page" value="' . htmlspecialchars($_GET['page']) . '">';
                    }
                    echo '</form>';
                    echo '</div>';
                    
                    // Cập nhật selected child nếu có thay đổi
                    if (isset($_GET['childIndex'])) {
                        $_SESSION['selectedChildIndex'] = intval($_GET['childIndex']);
                        $_SESSION['maHS'] = $children[$_SESSION['selectedChildIndex']]['maHS'];
                    }
                    
                    // Hiển thị nội dung theo page
                    $page = $_GET['page'] ?? 'timeTable';
                    if($page == 'grades'){
                        include_once('grades.php');
                    }elseif($page == 'classification'){
                        include_once('classification.php');
                    }else{
                        include_once('timeTable.php');
                    }
                }
            ?>
        </div>
    </div>

    <script>
        // Menu toggle functionality for mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar-nav');
            
            // Create toggle button for mobile
            if (window.innerWidth <= 768) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'navbar-toggle';
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                document.body.appendChild(toggleBtn);
                
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('active');
                });
                
                // Close sidebar when clicking outside
                document.addEventListener('click', function(e) {
                    if (!sidebar.contains(e.target) && !e.target.classList.contains('navbar-toggle')) {
                        sidebar.classList.remove('active');
                    }
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>