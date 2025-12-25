<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập và quyền TTBM
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'ttbm') {
    header("Location: ../../public/index.php");
    exit;
}

// Lấy thông tin TTBM
require_once(__DIR__ . '/../../model/mGradingAssignment.php');
$mGrading = new mGradingAssignment();
$ttbmInfo = $mGrading->getTTBMInfo($_SESSION['maTaiKhoan']);

// Lấy danh sách giáo viên trong tổ
include_once("../../model/mConnect.php");
$db = new mConnect();
$conn = $db->mConnect();

$teachers = [];
if ($ttbmInfo && isset($ttbmInfo['toBoMon'])) {
    $toBoMon = $ttbmInfo['toBoMon'];
    $sql = "SELECT 
                gv.maGV,
                gv.hoTen,
                gv.email,
                gv.soDienThoai,
                gv.gioiTinh,
                gv.ngaySinh,
                (SELECT COUNT(*) FROM phancongchamdiem WHERE maGV = gv.maGV) as soPhanCongCham,
                (SELECT COUNT(*) FROM phancongcoithi WHERE maGV = gv.maGV) as soPhanCongCoi
            FROM giaovien gv
            WHERE gv.toBoMon = ?
            ORDER BY gv.hoTen ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $toBoMon);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách Giáo viên - Tổ <?php echo htmlspecialchars($ttbmInfo['toBoMon'] ?? ''); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header-content h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header-content p {
            opacity: 0.9;
            font-size: 14px;
        }

        .btn-back {
            padding: 10px 20px;
            border: 2px solid white;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: white;
            color: #9b59b6;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #9b59b6;
        }

        .stat-card .label {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }

        tbody tr {
            transition: background 0.3s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .teacher-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .teacher-email {
            color: #7f8c8d;
            font-size: 13px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-primary {
            background: #e8f4fd;
            color: #3498db;
        }

        .badge-success {
            background: #d4edda;
            color: #27ae60;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .search-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #dfe6e9;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #9b59b6;
            box-shadow: 0 0 0 3px rgba(155, 89, 182, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="page-header-content">
                <h1>👨‍🏫 Danh sách Giáo viên</h1>
                <p>Tổ bộ môn: <strong><?php echo htmlspecialchars($ttbmInfo['toBoMon'] ?? 'N/A'); ?></strong></p>
            </div>
            <a href="index.php" class="btn-back">← Quay lại Dashboard</a>
        </div>

        <div class="stats-summary">
            <div class="stat-card">
                <div class="number"><?php echo count($teachers); ?></div>
                <div class="label">Tổng giáo viên</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo array_sum(array_column($teachers, 'soPhanCongCham')); ?></div>
                <div class="label">Tổng phân công chấm</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo array_sum(array_column($teachers, 'soPhanCongCoi')); ?></div>
                <div class="label">Tổng phân công coi</div>
            </div>
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Tìm kiếm giáo viên theo tên, email, số điện thoại..." onkeyup="filterTable()">
        </div>

        <div class="table-container">
            <table id="teacherTable">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã GV</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Giới tính</th>
                        <th>Phân công chấm</th>
                        <th>Phân công coi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teachers)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <div>👨‍🏫</div>
                                <p>Không có giáo viên nào trong tổ</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($teachers as $index => $teacher): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($teacher['maGV']); ?></td>
                                <td>
                                    <div class="teacher-name"><?php echo htmlspecialchars($teacher['hoTen']); ?></div>
                                </td>
                                <td class="teacher-email"><?php echo htmlspecialchars($teacher['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($teacher['soDienThoai'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($teacher['gioiTinh'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge badge-primary"><?php echo $teacher['soPhanCongCham']; ?> phân công</span>
                                </td>
                                <td>
                                    <span class="badge badge-success"><?php echo $teacher['soPhanCongCoi']; ?> phân công</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('teacherTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const text = cells[j].textContent || cells[j].innerText;
                    if (text.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }
    </script>
</body>
</html>
