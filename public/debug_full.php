<?php
echo "<h1>Debug Đăng Nhập</h1>";
echo "<p>Đang kiểm tra kết nối database...</p>";

// Test 1: Kết nối trực tiếp
$conn = new mysqli("localhost", "root", "", "school_management");

if ($conn->connect_error) {
    die("❌ Kết nối thất bại: " . $conn->connect_error);
}

echo "✅ Kết nối database thành công!<br><br>";

// Test 2: Kiểm tra bảng taikhoan
$sql = "SELECT COUNT(*) as total FROM taikhoan";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "📊 Tổng số tài khoản trong DB: " . $row['total'] . "<br><br>";

// Test 3: Liệt kê một số tài khoản
echo "<h2>Danh sách tài khoản mẫu:</h2>";
$sql = "SELECT tenDangNhap, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, LENGTH(matKhau) as pass_len 
        FROM taikhoan LIMIT 10";
$result = $conn->query($sql);

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Username</th><th>Họ tên</th><th>Loại TK</th><th>Trạng thái</th><th>Pass Length</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['tenDangNhap'] . "</td>";
    echo "<td>" . $row['hoTen'] . "</td>";
    echo "<td>" . $row['loaiTaiKhoan'] . "</td>";
    echo "<td>" . $row['trangThaiTaiKhoan'] . "</td>";
    echo "<td>" . $row['pass_len'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

// Test 4: Test login với admin/password
echo "<h2>Test Đăng Nhập (admin/password)</h2>";

$test_user = "admin";
$test_pass = "password";

$sql = "SELECT * FROM taikhoan WHERE tenDangNhap = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $test_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ Tìm thấy user: <strong>" . $user['tenDangNhap'] . "</strong><br>";
    echo "Họ tên: " . $user['hoTen'] . "<br>";
    echo "Loại TK: " . $user['loaiTaiKhoan'] . "<br>";
    echo "Trạng thái: " . $user['trangThaiTaiKhoan'] . "<br>";
    
    $hash = $user['matKhau'];
    $md5_test = md5($test_pass);
    
    echo "<br>Hash trong DB: " . substr($hash, 0, 20) . "...<br>";
    echo "MD5('password'): " . substr($md5_test, 0, 20) . "...<br>";
    
    if ($hash === $md5_test) {
        echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✅✅✅ MẬT KHẨU ĐÚNG!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold; font-size: 18px;'>❌ MẬT KHẨU SAI!</p>";
        echo "Hash length: " . strlen($hash) . "<br>";
    }
} else {
    echo "❌ Không tìm thấy user '$test_user'<br>";
}

// Test 5: Test code mUser.php
echo "<hr><h2>Test mUser.php</h2>";

include_once("../model/mUser.php");

try {
    $mUser = new mUser();
    echo "✅ Class mUser khởi tạo thành công<br><br>";
    
    echo "Đang test login với admin/password...<br>";
    $result = $mUser->mLogin("admin", "password");
    
    if ($result !== false) {
        echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✅✅✅ ĐĂNG NHẬP THÀNH CÔNG VỚI mUser!</p>";
        echo "Thông tin user:<br>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        echo "<p style='color: red; font-weight: bold; font-size: 18px;'>❌ ĐĂNG NHẬP THẤT BẠI VỚI mUser!</p>";
    }
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}

// Test 6: Tạo tài khoản username/password
echo "<hr><h2>Tạo/Cập nhật tài khoản username/password</h2>";

$new_user = "username";
$new_pass = "password";
$new_hash = md5($new_pass);

// Kiểm tra tồn tại
$check = $conn->query("SELECT * FROM taikhoan WHERE tenDangNhap = 'username'");

if ($check->num_rows > 0) {
    echo "ℹ️ Tài khoản 'username' đã tồn tại, cập nhật mật khẩu...<br>";
    $sql = "UPDATE taikhoan SET matKhau = ? WHERE tenDangNhap = 'username'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $new_hash);
    if ($stmt->execute()) {
        echo "✅ Cập nhật thành công!<br>";
    }
} else {
    echo "ℹ️ Tạo mới tài khoản 'username'...<br>";
    $sql = "INSERT INTO taikhoan (tenDangNhap, matKhau, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom) 
            VALUES (?, ?, 'Username Test', 'quantrivien', 1, 3001)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $new_user, $new_hash);
    if ($stmt->execute()) {
        echo "✅ Tạo mới thành công!<br>";
    } else {
        echo "❌ Lỗi: " . $stmt->error . "<br>";
    }
}

echo "<br><strong>Bây giờ bạn có thể đăng nhập với:</strong><br>";
echo "Username: <code>username</code> hoặc <code>admin</code><br>";
echo "Password: <code>password</code><br><br>";

echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>🔐 Đi đến trang đăng nhập</a>";

$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
        background: #f5f5f5;
    }
    h1 { color: #333; }
    h2 { 
        color: #667eea; 
        margin-top: 30px;
        padding-bottom: 10px;
        border-bottom: 2px solid #667eea;
    }
    table {
        background: white;
        margin: 10px 0;
    }
    th {
        background: #667eea;
        color: white;
        padding: 10px;
    }
    td {
        padding: 8px;
    }
    code {
        background: #f4f4f4;
        padding: 2px 6px;
        border-radius: 3px;
        color: #d63031;
        font-weight: bold;
    }
</style>
