<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once("../../model/mStudent.php");

if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "hocsinh") {
  echo "<p style='color:red;'>Bạn chưa đăng nhập.</p>";
  exit;
}

$model = new mStudent();
$info = $model->getStudentInfoByAccount($_SESSION["tenDangNhap"]);
$schedule = $model->getStudentSchedule($info['maHS']);

// Chuẩn bị mảng để hiển thị theo tiết – thứ
$timetable = [];
while ($row = $schedule->fetch_assoc()) {
  $tiet = $row['tietBatDau'];
  $thu = $row['thu'];
  $timetable[$tiet][$thu] = $row['tenMonHoc'];
}
?>

<h3>Thời khóa biểu - Lớp <?= $info['tenLop'] ?></h3>
<p><strong>Trường:</strong> THPT Nam Đàn 2 | <strong>Năm học:</strong> 2024–2025 | <strong>Học kỳ:</strong> I</p>

<table border="1" cellpadding="10" cellspacing="0" style="width:100%;border-collapse:collapse;text-align:center;">
  <thead style="background:#2f6bff;color:#fff;">
    <tr>
      <th>Tiết</th>
      <th>Thứ 2</th>
      <th>Thứ 3</th>
      <th>Thứ 4</th>
      <th>Thứ 5</th>
      <th>Thứ 6</th>
    </tr>
  </thead>
  <tbody>
    <?php
    for ($tiet = 1; $tiet <= 6; $tiet++) {
      echo "<tr><td>$tiet</td>";
      for ($thu = 2; $thu <= 6; $thu++) {
        $mon = isset($timetable[$tiet][$thu]) ? $timetable[$tiet][$thu] : "-";
        echo "<td>$mon</  d>";
      }
      echo "</tr>";
    }
    ?>
  </tbody>
</table>
