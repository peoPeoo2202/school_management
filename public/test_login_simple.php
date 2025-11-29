<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Đăng Nhập Đơn Giản</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h2>Thông tin nhập vào:</h2>";
    echo "Username: <strong>$username</strong><br>";
    echo "Password: <strong>$password</strong><br>";
    echo "MD5 password: " . md5($password) . "<br><br>";
    
    // Test kết nối trực tiếp
    $conn = new mysqli("localhost", "root", "", "school_management");
    
    if ($conn->connect_error) {
        die("❌ Kết nối DB thất bại: " . $conn->connect_error);
    }
    
    echo "✅ Kết nối DB thành công<br><br>";
    
    // Query user
    $sql = "SELECT * FROM taikhoan WHERE tenDangNhap = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ Không tìm thấy username '$username'</p>";
    } else {
        $user = $result->fetch_assoc();
        
        echo "<h2>Thông tin user trong DB:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Trường</th><th>Giá trị</th></tr>";
        foreach ($user as $key => $value) {
            if ($key !== 'matKhau') {
                echo "<tr><td>$key</td><td>$value</td></tr>";
            }
        }
        echo "<tr><td>matKhau (hash)</td><td>" . substr($user['matKhau'], 0, 32) . "</td></tr>";
        echo "<tr><td>matKhau length</td><td>" . strlen($user['matKhau']) . "</td></tr>";
        echo "</table><br>";
        
        // Check password
        $hash = $user['matKhau'];
        $input_md5 = md5($password);
        
        echo "<h2>Kiểm tra mật khẩu:</h2>";
        echo "Hash trong DB: <code>$hash</code><br>";
        echo "MD5 input: <code>$input_md5</code><br>";
        echo "Match: " . ($hash === $input_md5 ? "<strong style='color: green;'>✅ ĐÚNG</strong>" : "<strong style='color: red;'>❌ SAI</strong>") . "<br><br>";
        
        // Check trạng thái
        $status = $user['trangThaiTaiKhoan'];
        echo "<h2>Kiểm tra trạng thái:</h2>";
        echo "Trạng thái: <code>$status</code> (kiểu: " . gettype($status) . ")<br>";
        
        if ($status == 1 || $status === 'active') {
            echo "✅ Trạng thái hợp lệ<br>";
        } else {
            echo "❌ Trạng thái không hợp lệ (cần = 1 hoặc 'active')<br>";
        }
        
        // Test với mUser.php
        echo "<hr><h2>Test với mUser.php:</h2>";
        include_once("../model/mUser.php");
        
        $mUser = new mUser();
        $result = $mUser->mLogin($username, $password);
        
        if ($result !== false) {
            echo "<p style='color: green; font-size: 20px; font-weight: bold;'>✅✅✅ ĐĂNG NHẬP THÀNH CÔNG!</p>";
            echo "<pre>";
            print_r($result);
            echo "</pre>";
            
            echo "<br><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Đi đến trang đăng nhập chính</a>";
        } else {
            echo "<p style='color: red; font-size: 20px; font-weight: bold;'>❌ ĐĂNG NHẬP THẤT BẠI VỚI mUser!</p>";
        }
    }
    
    $conn->close();
} else {
    ?>
    <form method="POST" style="max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;">
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Username:</label>
            <input type="text" name="username" value="admin" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Password:</label>
            <input type="password" name="password" value="password" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <button type="submit" style="width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer;">
            Test Đăng Nhập
        </button>
    </form>
    
    <div style="max-width: 400px; margin: 20px auto; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;">
        <h3 style="margin-top: 0;">💡 Hướng dẫn:</h3>
        <p>Nhập username và password để test. Mặc định:</p>
        <ul>
            <li><strong>Username:</strong> admin</li>
            <li><strong>Password:</strong> password</li>
        </ul>
    </div>
    <?php
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
        background: #f5f5f5;
    }
    h1, h2 {
        color: #333;
    }
    table {
        background: white;
        border-collapse: collapse;
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
        font-family: monospace;
    }
</style>
