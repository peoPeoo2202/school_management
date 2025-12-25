<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Giáo viên - Danh sách lớp</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header style="background:#2f6bff;color:white;padding:10px 20px;">
  <h3>Xin chào giáo viên, <strong><?= $info['hoTen'] ?></strong></h3>
</header>

<div class="container" style="padding:20px;">
  <h2>Danh sách lớp phụ trách</h2>

  <?php if ($classes->num_rows > 0): ?>
  <table border="1" cellpadding="10" cellspacing="0" style="border-collapse:collapse;width:100%;">
    <thead style="background:#2f6bff;color:#fff;">
      <tr>
        <th>Mã lớp</th>
        <th>Tên lớp</th>
        <th>Khối</th>
        <th>Sĩ số</th>
        <th>Năm học</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $classes->fetch_assoc()): ?>
        <tr>
          <td><?= $row['maLop'] ?></td>
          <td><?= $row['tenLop'] ?></td>
          <td><?= $row['khoiLop'] ?></td>
          <td><?= $row['siSo'] ?></td>
          <td><?= $row['namHoc'] ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p style="color:red;">Bạn chưa được phân công làm chủ nhiệm lớp nào.</p>
  <?php endif; ?>
</div>

<footer style="background:#2f6bff;color:white;text-align:center;padding:10px;">
  © 2025 Hệ thống Quản lý Giáo dục
</footer>

</body>
</html>
