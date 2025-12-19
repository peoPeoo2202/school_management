<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../controller/cSubmitHomework.php';

// Session đã start ở index.php rồi
if (!isset($_SESSION['maHS'])) {
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; margin: 20px;'>
            <h3>Lỗi phiên làm việc</h3>
            <p>Không tìm thấy thông tin học sinh trong session.</p>
          </div>";
    exit;
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

$controller = new cSubmitHomework();
$maHS = $_SESSION['maHS'];
