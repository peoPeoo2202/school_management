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

// Kiểm tra thông tin học sinh
if (!$info) {
  echo "<p style='color: red;'>Không thể tải thời khóa biểu. Thông tin học sinh không hợp lệ.</p>";
  exit;
}

// Kiểm tra đã được phân lớp chưa
if (!isset($info['maLop']) || !$info['maLop']) {
  echo "<p>Bạn chưa được phân lớp.</p>";
  exit;
}

// Lấy thời khóa biểu
$schedule = $model->getStudentSchedule($info['maHS']);

if (!$schedule || $schedule->num_rows === 0) {
  echo "<div style='padding:20px;'>";
  echo "<h3>Thời khóa biểu lớp: " . htmlspecialchars($info['tenLop']) . "</h3>";
  echo "<p><strong>Họ tên:</strong> " . htmlspecialchars($info['hoTen']) . "</p>";
  echo "<p style='color:orange;'>⚠ Chưa có thời khóa biểu cho lớp của bạn.</p>";
  echo "</div>";
  exit;
}

// Định nghĩa thời gian cho mỗi tiết học (buổi sáng: tiết 1-5, buổi chiều: tiết 1-5)
$tietTimes = [
  // Buổi sáng
  'sang' => [
    1 => ['start' => '07:30', 'end' => '08:15'],
    2 => ['start' => '08:20', 'end' => '09:05'],
    3 => ['start' => '09:30', 'end' => '10:15'],
    4 => ['start' => '10:20', 'end' => '11:05'],
    5 => ['start' => '11:10', 'end' => '11:55']
  ],
  // Buổi chiều
  'chieu' => [
    1 => ['start' => '13:30', 'end' => '14:15'],
    2 => ['start' => '14:20', 'end' => '15:05'],
    3 => ['start' => '15:30', 'end' => '16:15'],
    4 => ['start' => '16:20', 'end' => '17:05'],
    5 => ['start' => '17:10', 'end' => '17:55']
  ]
];

// Chuẩn bị mảng để hiển thị theo tiết – thứ – buổi
$timetable = ['sang' => [], 'chieu' => []];
$count = 0;

while ($row = $schedule->fetch_assoc()) {
  $count++;
  
  // Chuyển đổi DAYOFWEEK (1=CN, 2=T2,...,7=T7) sang thứ (2-6)
  $dayOfWeek = $row['thu'];
  $thu = $dayOfWeek;
  
  // Bỏ qua Chủ nhật (1) và Thứ 7 (7)
  if ($thu < 2 || $thu > 6) {
    continue;
  }
  
  // Phân tích tietHoc (ví dụ: "Tiết 1-2" => tiết 1 và 2)
  $tietHoc = $row['tietHoc'];
  
  // Xử lý nhiều định dạng: "Tiết 1-2", "Tiết 1", "1-2", "1"
  preg_match_all('/\d+/', $tietHoc, $matches);
  
  if (!empty($matches[0])) {
    $tietBatDau = (int)$matches[0][0];
    $tietKetThuc = isset($matches[0][1]) ? (int)$matches[0][1] : $tietBatDau;
    
    // Lưu thông tin cho từng tiết
    for ($tiet = $tietBatDau; $tiet <= $tietKetThuc; $tiet++) {
      // Xác định buổi và đánh số lại tiết
      if ($tiet <= 5) {
        $buoi = 'sang';
        $tietMoi = $tiet;
      } else {
        $buoi = 'chieu';
        $tietMoi = $tiet - 5; // Tiết 6->1, 7->2, 8->3, 9->4, 10->5
      }
      
      $timetable[$buoi][$tietMoi][$thu] = [
        'monHoc' => $row['tenMonHocFull'] ?: $row['tenMonHoc'],
        'giaoVien' => $row['tenGiaoVien']
      ];
    }
  }
}
?>

<div class="timetable-container">
  <div class="timetable-header">
    <h3>Thời khóa biểu lớp: <?= htmlspecialchars($info['tenLop']) ?></h3>
  </div>

  <?php 
  $buoiNames = ['sang' => 'BUỔI SÁNG', 'chieu' => 'BUỔI CHIỀU'];
  
  foreach (['sang', 'chieu'] as $buoi): 
  ?>
  
  <div style="margin-top:30px;">
    <h4 style="background:#4d5ef7;color:white;padding:10px;border-radius:5px;text-align:center;">
      <?= $buoiNames[$buoi] ?>
    </h4>
    
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%;border-collapse:collapse;text-align:center;margin-top:10px;">
      <thead style="background:#4d5ef7;color:#fff;">
        <tr>
          <th style="width:100px;">Tiết</th>
          <th>Thứ 2</th>
          <th>Thứ 3</th>
          <th>Thứ 4</th>
          <th>Thứ 5</th>
          <th>Thứ 6</th>
        </tr>
      </thead>
      <tbody>
        <?php
        for ($tiet = 1; $tiet <= 5; $tiet++) {
          echo "<tr>";
          
          // Cột tiết học với thời gian
          echo "<td style='font-weight:bold;background:#f0f0f0;'>";
          echo "Tiết $tiet<br>";
          if (isset($tietTimes[$buoi][$tiet])) {
            echo "<small style='color:#666;font-weight:normal;'>" . 
                 $tietTimes[$buoi][$tiet]['start'] . " - " . 
                 $tietTimes[$buoi][$tiet]['end'] . "</small>";
          }
          echo "</td>";
          
          for ($thu = 2; $thu <= 6; $thu++) {
            if (isset($timetable[$buoi][$tiet][$thu])) {
              $data = $timetable[$buoi][$tiet][$thu];
              echo "<td style='text-align:center;padding:12px 8px;background:#fff;'>";
              echo "<strong style='color:#4d5ef7;display:block;margin-bottom:4px;'>" . 
                   htmlspecialchars($data['monHoc']) . "</strong>";
              if (!empty($data['giaoVien'])) {
                echo "<small style='color:#666;display:block;'>👤 " . 
                     htmlspecialchars($data['giaoVien']) . "</small>";
              }
              echo "</td>";
            } else {
              echo "<td style='background:#fafafa;color:#ccc;'>-</td>";
            }
          }
          
          echo "</tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
  
  <?php endforeach; ?>
</div>

<style>
/* Container giống grades */
.timetable-container {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.timetable-header {
    margin-bottom: 25px;
}

.timetable-header h3 {
    color: #333;
    margin: 0;
    font-size: 1.5em;
}

/* Table styling */
table {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #ddd;
}

table td {
    vertical-align: middle;
    min-height: 70px;
}

table td small {
    font-size: 0.85em;
    line-height: 1.4;
}

table thead th {
    padding: 15px 10px;
    font-weight: 600;
}

table tbody tr:hover {
    background-color: #f5f5f5;
}

table tbody td:first-child {
    text-align: center;
    line-height: 1.5;
}

table tbody td:first-child small {
    display: block;
    margin-top: 4px;
    font-size: 0.8em;
}

h4 {
    margin: 0;
    font-size: 1.1em;
    letter-spacing: 1px;
}
</style>
