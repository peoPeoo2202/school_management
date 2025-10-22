<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng nhập - Hệ thống Quản lý Giáo dục</title>
  <link rel="stylesheet" href="./css/style.css">
</head>
<body class="login-body">

<div class="login-container">
  <h2>HỆ THỐNG QUẢN LÝ GIÁO DỤC</h2>
  <form action="#" method="post" class="login-form">
    <label for="username">Tên đăng nhập</label>
    <input type="text" id="username" placeholder="Nhập tên đăng nhập">

    <label for="password">Mật khẩu</label>
    <input type="password" id="password" placeholder="Nhập mật khẩu">

    <label for="role">Vai trò</label>
    <select id="role">
      <option value="student">Học sinh</option>
      <option value="teacher">Giáo viên</option>
      <option value="parent">Phụ huynh</option>
      <option value="manager">Ban giám hiệu</option>
    </select>

    <button type="submit">Đăng nhập</button>
  </form>
</div>

</body>
</html>
