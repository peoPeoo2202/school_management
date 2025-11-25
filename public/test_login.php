<?php
/**
 * File kiểm tra tài khoản và đăng nhập
 */

// Kết nối database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_management";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Kiểm tra Tài khoản trong Database</h2>";

// Kiểm tra tài khoản 'username'
$sql = "SELECT maTaiKhoan, tenDangNhap, LEFT(matKhau, 32) as matKhau_hash, 
        LENGTH(matKhau) as password_length, hoTen, loaiTaiKhoan, trangThaiTaiKhoan 
        FROM taikhoan 
        WHERE tenDangNhap IN ('username', 'admin', 'qtv1', 'userA', 'gv1')
        LIMIT 10";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr>
            <th>ID</th>
            <th>Username</th>
            <th>Password Hash (32 chars)</th>
            <th>Pass Length</th>
            <th>Họ tên</th>
            <th>Loại TK</th>
            <th>Trạng thái</th>
          </tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["maTaiKhoan"] . "</td>";
        echo "<td>" . $row["tenDangNhap"] . "</td>";
        echo "<td>" . $row["matKhau_hash"] . "</td>";
        echo "<td>" . $row["password_length"] . "</td>";
        echo "<td>" . $row["hoTen"] . "</td>";
        echo "<td>" . $row["loaiTaiKhoan"] . "</td>";
        echo "<td>" . $row["trangThaiTaiKhoan"] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không tìm thấy tài khoản nào!</p>";
}

echo "<hr>";
echo "<h2>Test Đăng nhập</h2>";

// Test password
$test_username = "admin";
$test_password = "password";

echo "<p><strong>Testing login:</strong> username = '$test_username', password = '$test_password'</p>";

$sql = "SELECT maTaiKhoan, tenDangNhap, matKhau, hoTen, loaiTaiKhoan, trangThaiTaiKhoan 
        FROM taikhoan 
        WHERE tenDangNhap = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $test_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<p>✅ Tìm thấy user: " . $user['tenDangNhap'] . "</p>";
    echo "<p>Họ tên: " . $user['hoTen'] . "</p>";
    echo "<p>Loại TK: " . $user['loaiTaiKhoan'] . "</p>";
    echo "<p>Trạng thái: " . $user['trangThaiTaiKhoan'] . "</p>";
    echo "<p>Password hash length: " . strlen($user['matKhau']) . "</p>";
    
    $hashedPassword = $user['matKhau'];
    
    // Check if MD5
    if (strlen($hashedPassword) === 32 && ctype_xdigit($hashedPassword)) {
        echo "<p>🔐 Password type: <strong>MD5</strong></p>";
        $md5_test = md5($test_password);
        echo "<p>MD5 của 'password': " . $md5_test . "</p>";
        echo "<p>Hash trong DB: " . $hashedPassword . "</p>";
        
        if (md5($test_password) === $hashedPassword) {
            echo "<p style='color: green;'>✅ MD5 MATCH - Đăng nhập thành công!</p>";
        } else {
            echo "<p style='color: red;'>❌ MD5 NOT MATCH - Sai mật khẩu!</p>";
        }
    } else {
        echo "<p>🔐 Password type: <strong>bcrypt</strong></p>";
        if (password_verify($test_password, $hashedPassword)) {
            echo "<p style='color: green;'>✅ bcrypt MATCH - Đăng nhập thành công!</p>";
        } else {
            echo "<p style='color: red;'>❌ bcrypt NOT MATCH - Sai mật khẩu!</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Không tìm thấy user '$test_username'</p>";
}

echo "<hr>";
echo "<h2>Tạo tài khoản test</h2>";

// Tạo tài khoản test với MD5
$test_user = "testuser";
$test_pass = "password";
$test_pass_md5 = md5($test_pass);

echo "<p>Tạo tài khoản: username='$test_user', password='$test_pass'</p>";
echo "<p>MD5 hash: $test_pass_md5</p>";

$sql_insert = "INSERT INTO taikhoan (tenDangNhap, matKhau, hoTen, loaiTaiKhoan, trangThaiTaiKhoan, maNhom) 
               VALUES (?, ?, 'Test User', 'quantrivien', 1, 3001)
               ON DUPLICATE KEY UPDATE matKhau = ?";

$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("sss", $test_user, $test_pass_md5, $test_pass_md5);

if ($stmt_insert->execute()) {
    echo "<p style='color: green;'>✅ Tạo/cập nhật tài khoản '$test_user' thành công!</p>";
    echo "<p>Bạn có thể đăng nhập với: username='testuser', password='password'</p>";
} else {
    echo "<p style='color: red;'>❌ Lỗi: " . $stmt_insert->error . "</p>";
}

$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
        background: #f5f5f5;
    }
    table {
        background: white;
        padding: 10px;
    }
    th {
        background: #667eea;
        color: white;
        padding: 10px;
    }
    td {
        padding: 8px;
    }
    h2 {
        color: #333;
        border-bottom: 2px solid #667eea;
        padding-bottom: 10px;
    }
</style>
