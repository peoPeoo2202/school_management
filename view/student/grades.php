<?php
session_start();
include_once("../../model/mStudent.php");

if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "hocsinh") {
  echo "<p style='color:red;'>Bạn chưa đăng nhập.</p>";
  exit;
}

$model = new mStudent();
$info = $model->getStudentInfoByAccount($_SESSION["tenDangNhap"]);

// Lấy điểm trung bình từng môn (có thể mở rộng nếu bạn tách học kỳ)
$grades = $model->getStudentGrades($info['maHS']);
?>

<h3>Kết quả học tập - Lớp <?= $info['tenLop'] ?></h3>
<p><strong>Trường:</strong> THPT Nam Đàn 2 | <strong>Năm học:</strong> 2024–2025 | <strong>Học kỳ:</strong> Cả năm</p>

<table border="1" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;text-align:center;">
  <thead style="background:#2f6bff;color:#fff;">
    <tr>
      <th>Môn học</th>
      <th>HK1</th>
      <th>HK2</th>
      <th>Trung bình</th>
    </tr>
  </thead>
  <tbody>
    <?php
    if ($grades->num_rows > 0) {
      while ($r = $grades->fetch_assoc()) {
        // Giả sử hiện tại bạn mới có điểm HK1 → cho HK2 trống, TB = HK1
        $hk1 = $r['diemTB'];
        $hk2 = "-";
        $tb = $r['diemTB'];
        echo "<tr>
                <td>{$r['tenMonHoc']}</td>
                <td>{$hk1}</td>
                <td>{$hk2}</td>
                <td>{$tb}</td>
              </tr>";
      }
    } else {
      echo "<tr><td colspan='4'>Chưa có dữ liệu điểm</td></tr>";
    }
    ?>
  </tbody>
</table>
