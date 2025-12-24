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
    <link rel="stylesheet" href="../../view/student/style.css">
    
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
                    // Cập nhật selected child nếu có thay đổi
                    if (isset($_GET['childIndex'])) {
                        $_SESSION['selectedChildIndex'] = intval($_GET['childIndex']);
                        $_SESSION['maHS'] = $children[$_SESSION['selectedChildIndex']]['maHS'];
                    }
                    
                    // Capture page content để có thể insert selector
                    ob_start();
                    $page = $_GET['page'] ?? 'timeTable';
                    if($page == 'grades'){
                        include_once(__DIR__ . '/../student/grades.php');
                    }elseif($page == 'classification'){
                        include_once(__DIR__ . '/../student/classification.php');
                    }else{
                        include_once(__DIR__ . '/../student/timeTable.php');
                    }
                    $pageContent = ob_get_clean();
                    
                    // XỬ LÝ THEO TRANG
                    if ($page === 'timeTable') {
                        // TRANG THỜI KHÓA BIỂU: Insert selector TRONG title-header, bên phải
                        $selectorInHeader = '<div class="child-selector-header">';
                        $selectorInHeader .= '<label><i class="fas fa-user-graduate"></i> Học sinh:</label>';
                        $selectorInHeader .= '<select name="childIndex" onchange="this.form.submit()">';
                        
                        foreach ($children as $index => $child) {
                            $selected = ($index == ($_GET['childIndex'] ?? $_SESSION['selectedChildIndex'])) ? 'selected' : '';
                            $selectorInHeader .= '<option value="' . $index . '" ' . $selected . '>' . htmlspecialchars($child['hoTen']) . '</option>';
                        }
                        
                        $selectorInHeader .= '</select>';
                        if (isset($_GET['page'])) {
                            $selectorInHeader .= '<input type="hidden" name="page" value="' . htmlspecialchars($_GET['page']) . '">';
                        }
                        $selectorInHeader .= '</div>';
                        
                        // Tìm title-header và thêm flexbox + selector
                        $pattern = '/<div[^>]*class="title-header"[^>]*>(.*?)<\/div>/is';
                        if (preg_match($pattern, $pageContent, $matches, PREG_OFFSET_CAPTURE)) {
                            $headerStart = $matches[0][1];
                            $headerEnd = $headerStart + strlen($matches[0][0]);
                            
                            // Thay thế div title-header để thêm flexbox
                            $oldHeader = $matches[0][0];
                            $newHeader = preg_replace(
                                '/<div([^>]*class="title-header"[^>]*)>/i',
                                '<div$1 style="display: flex; align-items: center; justify-content: space-between;">',
                                $oldHeader
                            );
                            // Insert selector trước tag đóng </div>
                            $newHeader = substr_replace($newHeader, $selectorInHeader, strlen($newHeader) - 6, 0);
                            
                            $pageContent = substr_replace($pageContent, $newHeader, $headerStart, strlen($oldHeader));
                        }
                    } else {
                        // CÁC TRANG KHÁC (grades, classification): Insert selector trong filter-form, cuối bên phải
                        $selectorInline = '<div class="child-selector-inline">';
                        $selectorInline .= '<label><i class="fas fa-user-graduate"></i> Học sinh:</label>';
                        $selectorInline .= '<select name="childIndex" onchange="this.form.submit()">';
                        
                        foreach ($children as $index => $child) {
                            $selected = ($index == ($_GET['childIndex'] ?? $_SESSION['selectedChildIndex'])) ? 'selected' : '';
                            $selectorInline .= '<option value="' . $index . '" ' . $selected . '>' . htmlspecialchars($child['hoTen']) . '</option>';
                        }
                        
                        $selectorInline .= '</select>';
                        if (isset($_GET['page'])) {
                            $selectorInline .= '<input type="hidden" name="page" value="' . htmlspecialchars($_GET['page']) . '">';
                        }
                        $selectorInline .= '</div>';
                        
                        // Tìm filter-form và insert selector ở cuối, trước tag đóng </form>
                        $pattern = '/<form[^>]*class="filter-form"[^>]*>(.*?)<\/form>/is';
                        if (preg_match($pattern, $pageContent, $matches, PREG_OFFSET_CAPTURE)) {
                            $formContent = $matches[1][0];
                            $formPos = $matches[1][1];
                            // Insert selector cuối cùng trong form, trước tag đóng
                            $newFormContent = $formContent . $selectorInline;
                            $pageContent = substr_replace($pageContent, $newFormContent, $formPos, strlen($formContent));
                        }
                    }
                    
                    echo $pageContent;
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