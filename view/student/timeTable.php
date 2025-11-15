<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include_once("../../model/mStudent.php");

if (!isset($_SESSION["login"]) || $_SESSION["loaiTaiKhoan"] != "hocsinh") {
  echo "<p class='error-message'>Bạn chưa đăng nhập.</p>";
  exit;
}

$model = new mStudent();
$info = $model->getStudentInfoByAccount($_SESSION["tenDangNhap"]);

// Kiểm tra thông tin học sinh
if (!$info) {
  echo "<p class='error-message'>Không thể tải thời khóa biểu. Thông tin học sinh không hợp lệ.</p>";
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
  echo "<div class='warning-message'>";
  echo "<h3>Thời khóa biểu lớp: " . htmlspecialchars($info['tenLop']) . "</h3>";
  echo "<p><strong>Họ tên:</strong> " . htmlspecialchars($info['hoTen']) . "</p>";
  echo "<p>⚠ Chưa có thời khóa biểu cho lớp của bạn.</p>";
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
  $buoiNames = ['sang' => 'SÁNG', 'chieu' => 'CHIỀU'];

  foreach (['sang', 'chieu'] as $buoi):
  ?>

    <div class="timetable-section">
      <!-- <h4 class="timetable-section-title">
        <?= $buoiNames[$buoi] ?>
      </h4> -->

      <table class="timetable-table">
        <thead >
          <tr>
            <th class="buoi-column">Buổi</th>
            <th class="tiet-column">Tiết</th>
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
            if ($tiet === 1) {
              echo "<td class='buoi-cell' rowspan='5'>" . $buoiNames[$buoi] . "</td>";
            }
            // Cột tiết học với thời gian
            echo "<td class='tiet-cell'>";
            echo "Tiết $tiet<br>";
            if (isset($tietTimes[$buoi][$tiet])) {
              echo "<small class='timetable-time'>" .
                $tietTimes[$buoi][$tiet]['start'] . " - " .
                $tietTimes[$buoi][$tiet]['end'] . "</small>";
            }
            echo "</td>";

            for ($thu = 2; $thu <= 6; $thu++) {
              if (isset($timetable[$buoi][$tiet][$thu])) {
                $data = $timetable[$buoi][$tiet][$thu];
                echo "<td>";
                echo "<span class='timetable-subject'>" .
                  htmlspecialchars($data['monHoc']) . "</span>";
                if (!empty($data['giaoVien'])) {
                  echo "<span class='timetable-teacher'> " .
                    htmlspecialchars($data['giaoVien']) . "</span>";
                }
                echo "</td>";
              } else {
                echo "<td class='empty-cell'>-</td>";
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