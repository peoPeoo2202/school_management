<?php
/**
 * Script tạo tài khoản username/password
 */

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "school_management";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Tạo tài khoản username/password
$new_username = "username";
$new_password = "password";
$password_hash = md5($new_password); // MD5 để tương thích với hệ thống hiện tại

// Kiểm tra tài khoản đã tồn tại chưa
$check_sql = "SELECT tenDangNhap FROM taikhoan WHERE tenDangNhap = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $new_username);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    echo "<h2>Tài khoản '$new_username' đã tồn tại!</h2>";
    echo "<p>Cập nhật mật khẩu...</p>";
    
    $update_sql = "UPDATE taikhoan SET matKhau = ? WHERE tenDangNhap = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $password_hash, $new_username);
    
    if ($update_stmt->execute()) {
        echo "<p style='color: green;'>✅ Cập nhật mật khẩu thành công!</p>";
    } else {
        echo "<p style='color: red;'>❌ Lỗi cập nhật: " . $update_stmt->error . "</p>";
    }
} else {
    echo "<h2>Tạo tài khoản mới</h2>";
    
    $insert_sql = "INSERT INTO taikhoan (tenDangNhap, matKhau, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom) 
                   VALUES (?, ?, 'User Test', 'quantrivien', 1, 3001)";
    
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ss", $new_username, $password_hash);
    
    if ($insert_stmt->execute()) {
        echo "<p style='color: green;'>✅ Tạo tài khoản thành công!</p>";
    } else {
        echo "<p style='color: red;'>❌ Lỗi tạo tài khoản: " . $insert_stmt->error . "</p>";
    }
}

echo "<hr>";
echo "<h3>Thông tin đăng nhập:</h3>";
echo "<ul>";
echo "<li><strong>Username:</strong> $new_username</li>";
echo "<li><strong>Password:</strong> $new_password</li>";
echo "<li><strong>Loại tài khoản:</strong> quantrivien (Admin)</li>";
echo "<li><strong>MD5 Hash:</strong> $password_hash</li>";
echo "</ul>";

echo "<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Đi đến trang đăng nhập</a></p>";

$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        padding: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }
    
    h2, h3 {
        color: #333;
    }
    
    .container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
</style>
