<?php
session_start();
include_once("../../model/mStudent.php");

if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "hocsinh") {
  header("Location: ../../public/index.php");
  exit;
}

$model = new mStudent();
$info = $model->getStudentInfoByAccount($_SESSION["tenDangNhap"]);

if (!$info) {
  echo "<p style='color: red;'>Không tìm thấy thông tin học sinh. Vui lòng liên hệ quản trị viên.</p>";
  exit;
}

$pageTitle = "Trang chủ học sinh";
?>

<?php include_once('../layouts/header.php'); ?>

<header>
  <h3>Xin chào học sinh, <strong><?= htmlspecialchars($info['hoTen']) ?></strong></h3>
  <a href="../../public/logout.php" class="logout-btn">
    <i class="fas fa-sign-out-alt"></i>
    Đăng xuất
  </a>
</header>

<div class="container">
  <!-- Sidebar -->
  <div class="sidebar-container">
    <?php include('../layouts/navigate/navigateStudent.php'); ?>
  </div>

  <!-- Main content -->
  <div class="main" id="content-right">
   <?php
      $page = $_GET['page'] ?? 'timeTable';
      if($page == 'grades'){
        include_once('grades.php');
      }elseif($page == 'yearGrades'){
        include_once('yearGrades.php');
      }elseif($page == 'classification'){
        include_once('classification.php');
      }else{
        include_once('timeTable.php');
      }
    ?>
  </div>
</div>

<?php include_once('../layouts/footer.php'); ?>
