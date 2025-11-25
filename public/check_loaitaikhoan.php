<?php
// Quick debug - kiểm tra loại tài khoản trong DB
$conn = new mysqli("localhost", "root", "", "school_management");

$sql = "SELECT tenDangNhap, loaiTaiKhoan, trangThaiTaiKhoan 
        FROM taikhoan 
        WHERE tenDangNhap IN ('admin', 'username', 'qtv1')";

$result = $conn->query($sql);

echo "<h2>Thông tin tài khoản trong Database:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Username</th><th>loaiTaiKhoan</th><th>trangThaiTaiKhoan</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>" . $row['tenDangNhap'] . "</strong></td>";
    echo "<td><code>" . $row['loaiTaiKhoan'] . "</code></td>";
    echo "<td>" . $row['trangThaiTaiKhoan'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>⚠️ Lưu ý:</h3>";
echo "<p>Trong switch-case của index.php, cần dùng giá trị <strong>chính xác</strong> của loaiTaiKhoan.</p>";
echo "<p>Ví dụ: nếu DB lưu là 'quantrivien', thì case phải là 'quantrivien', không phải 'admin'.</p>";

$conn->close();
?>

<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    table { background: white; }
    th { background: #667eea; color: white; padding: 12px; }
    code { background: #ffffcc; padding: 3px 8px; font-weight: bold; color: #d63031; }
</style>
