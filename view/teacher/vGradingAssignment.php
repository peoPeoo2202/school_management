<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
require_once(__DIR__ . '/../../config.php');

// Kiểm tra đăng nhập và quyền TTBM
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'ttbm') {
    header("Location: " . url('public/index.php'));
    exit;
}

// Lấy thông tin TTBM
require_once(__DIR__ . '/../../model/mGradingAssignment.php');
$mGrading = new mGradingAssignment();
$ttbmInfo = $mGrading->getTTBMInfo($_SESSION['maTaiKhoan']);

// Lấy filter status từ URL
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Lấy danh sách phân công chấm điểm
include_once("../../model/mConnect.php");
$db = new mConnect();
$conn = $db->mConnect();

$assignments = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0
];

if ($ttbmInfo && isset($ttbmInfo['toBoMon'])) {
    $toBoMon = $ttbmInfo['toBoMon'];
    
    // Thống kê
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN pc.trangThai = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN pc.trangThai = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN pc.trangThai = 'completed' THEN 1 ELSE 0 END) as completed
            FROM phancongchamdiem pc
            INNER JOIN giaovien gv ON pc.maGV = gv.maGV
            WHERE gv.toBoMon = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $toBoMon);
    $stmt->execute();
    $statsResult = $stmt->get_result()->fetch_assoc();
    $stats = [
        'total' => $statsResult['total'] ?? 0,
        'pending' => $statsResult['pending'] ?? 0,
        'in_progress' => $statsResult['in_progress'] ?? 0,
        'completed' => $statsResult['completed'] ?? 0
    ];
    $stmt->close();
    
    // Lấy danh sách phân công
    $sql = "SELECT 
                pc.maPhanCong,
                pc.maGV,
                gv.hoTen as tenGV,
                gv.email,
                pc.maMonHoc,
                mh.tenMonHoc,
                pc.maKyThi,
                kt.tenKyThi,
                pc.maLop,
                lh.tenLop,
                pc.ngayPhanCong,
                pc.hanChamDiem,
                pc.trangThai,
                pc.ghiChu
            FROM phancongchamdiem pc
            INNER JOIN giaovien gv ON pc.maGV = gv.maGV
            LEFT JOIN monhoc mh ON pc.maMonHoc = mh.maMonHoc
            LEFT JOIN kythi kt ON pc.maKyThi = kt.maKyThi
            LEFT JOIN lophoc lh ON pc.maLop = lh.maLop
            WHERE gv.toBoMon = ?";
    
    $params = [$toBoMon];
    $types = "s";
    
    if ($filterStatus && in_array($filterStatus, ['pending', 'in_progress', 'completed'])) {
        $sql .= " AND pc.trangThai = ?";
        $params[] = $filterStatus;
        $types .= "s";
    }
    
    $sql .= " ORDER BY pc.ngayPhanCong DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
}

// Xác định tiêu đề trang theo filter
$pageTitle = 'Tất cả Phân công Chấm điểm';
$headerColor = '#667eea';
if ($filterStatus === 'pending') {
    $pageTitle = 'Phân công Chưa bắt đầu';
    $headerColor = '#f39c12';
} elseif ($filterStatus === 'in_progress') {
    $pageTitle = 'Phân công Đang chấm';
    $headerColor = '#3498db';
} elseif ($filterStatus === 'completed') {
    $pageTitle = 'Phân công Hoàn thành';
    $headerColor = '#27ae60';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - TTBM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>

        .page-header {
            background: linear-gradient(135deg, <?php echo $headerColor; ?> 0%, <?php echo $headerColor; ?>dd 100%);
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

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }

        .btn-back:hover {
            background: white;
            color: <?php echo $headerColor; ?>;
        }

        .stats-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stat-tab {
            flex: 1;
            min-width: 180px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 3px solid transparent;
        }

        .stat-tab:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .stat-tab.active {
            border-color: <?php echo $headerColor; ?>;
        }

        .stat-tab .number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-tab .label {
            font-size: 13px;
            color: #7f8c8d;
        }

        .stat-tab.all .number { color: #667eea; }
        .stat-tab.pending .number { color: #f39c12; }
        .stat-tab.progress .number { color: #3498db; }
        .stat-tab.completed .number { color: #27ae60; }

        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-bar input, .filter-bar select {
            padding: 10px 15px;
            border: 1px solid #dfe6e9;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-bar input {
            flex: 1;
            min-width: 250px;
        }

        .filter-bar input:focus, .filter-bar select:focus {
            outline: none;
            border-color: <?php echo $headerColor; ?>;
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
            background: linear-gradient(135deg, <?php echo $headerColor; ?> 0%, <?php echo $headerColor; ?>dd 100%);
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

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-progress {
            background: #cce5ff;
            color: #004085;
        }

        .badge-completed {
            background: #d4edda;
            color: #155724;
        }

        .teacher-info {
            display: flex;
            flex-direction: column;
        }

        .teacher-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .teacher-email {
            font-size: 12px;
            color: #7f8c8d;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .deadline {
            display: flex;
            flex-direction: column;
        }

        .deadline-date {
            font-weight: 500;
        }

        .deadline-warning {
            font-size: 11px;
            color: #e74c3c;
        }

        .deadline-ok {
            font-size: 11px;
            color: #27ae60;
        }

        @media (max-width: 768px) {
            .stats-tabs {
                flex-direction: column;
            }

            .stat-tab {
                min-width: 100%;
            }

            .filter-bar {
                flex-direction: column;
            }

            .filter-bar input {
                width: 100%;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTTBM.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
        <div class="page-header">
            <div class="page-header-content">
                <h1>📋 <?php echo $pageTitle; ?></h1>
                <p>Tổ bộ môn: <strong><?php echo htmlspecialchars($ttbmInfo['toBoMon'] ?? 'N/A'); ?></strong> | Tổng: <?php echo count($assignments); ?> phân công</p>
            </div>
            <div class="header-actions">
                <a href="index.php" class="btn btn-back">← Quay lại Dashboard</a>
            </div>
        </div>

        <!-- Stats Tabs -->
        <div class="stats-tabs">
            <a href="vGradingAssignment.php" class="stat-tab all <?php echo $filterStatus === '' ? 'active' : ''; ?>">
                <div class="number"><?php echo $stats['total']; ?></div>
                <div class="label">Tất cả</div>
            </a>
            <a href="vGradingAssignment.php?status=pending" class="stat-tab pending <?php echo $filterStatus === 'pending' ? 'active' : ''; ?>">
                <div class="number"><?php echo $stats['pending']; ?></div>
                <div class="label">Chưa bắt đầu</div>
            </a>
            <a href="vGradingAssignment.php?status=in_progress" class="stat-tab progress <?php echo $filterStatus === 'in_progress' ? 'active' : ''; ?>">
                <div class="number"><?php echo $stats['in_progress']; ?></div>
                <div class="label">Đang chấm</div>
            </a>
            <a href="vGradingAssignment.php?status=completed" class="stat-tab completed <?php echo $filterStatus === 'completed' ? 'active' : ''; ?>">
                <div class="number"><?php echo $stats['completed']; ?></div>
                <div class="label">Hoàn thành</div>
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="🔍 Tìm kiếm theo tên GV, môn học, lớp..." onkeyup="filterTable()">
            <select id="sortSelect" onchange="sortTable()">
                <option value="">-- Sắp xếp --</option>
                <option value="date-desc">Ngày phân công (Mới nhất)</option>
                <option value="date-asc">Ngày phân công (Cũ nhất)</option>
                <option value="deadline">Hạn chấm điểm</option>
                <option value="teacher">Tên giáo viên</option>
            </select>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table id="assignmentTable">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Giáo viên</th>
                        <th>Môn học</th>
                        <th>Kỳ thi</th>
                        <th>Lớp</th>
                        <th>Ngày phân công</th>
                        <th>Hạn chấm</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignments)): ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <div class="icon">📋</div>
                                <p>Không có phân công nào<?php echo $filterStatus ? ' ở trạng thái này' : ''; ?></p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assignments as $index => $assignment): ?>
                            <?php
                            $isOverdue = false;
                            $daysLeft = null;
                            if ($assignment['hanChamDiem'] && $assignment['trangThai'] !== 'completed') {
                                $deadline = strtotime($assignment['hanChamDiem']);
                                $today = strtotime('today');
                                $daysLeft = floor(($deadline - $today) / (60 * 60 * 24));
                                $isOverdue = $daysLeft < 0;
                            }
                            ?>
                            <tr data-date="<?php echo $assignment['ngayPhanCong']; ?>" data-deadline="<?php echo $assignment['hanChamDiem']; ?>" data-teacher="<?php echo htmlspecialchars($assignment['tenGV']); ?>">
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div class="teacher-info">
                                        <span class="teacher-name"><?php echo htmlspecialchars($assignment['tenGV'] ?? '-'); ?></span>
                                        <span class="teacher-email"><?php echo htmlspecialchars($assignment['email'] ?? ''); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($assignment['tenMonHoc'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($assignment['tenKyThi'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($assignment['tenLop'] ?? '-'); ?></td>
                                <td><?php echo $assignment['ngayPhanCong'] ? date('d/m/Y', strtotime($assignment['ngayPhanCong'])) : '-'; ?></td>
                                <td>
                                    <div class="deadline">
                                        <span class="deadline-date"><?php echo $assignment['hanChamDiem'] ? date('d/m/Y', strtotime($assignment['hanChamDiem'])) : '-'; ?></span>
                                        <?php if ($assignment['hanChamDiem'] && $assignment['trangThai'] !== 'completed'): ?>
                                            <?php if ($isOverdue): ?>
                                                <span class="deadline-warning">⚠️ Quá hạn <?php echo abs($daysLeft); ?> ngày</span>
                                            <?php elseif ($daysLeft <= 3): ?>
                                                <span class="deadline-warning">⏰ Còn <?php echo $daysLeft; ?> ngày</span>
                                            <?php else: ?>
                                                <span class="deadline-ok">✓ Còn <?php echo $daysLeft; ?> ngày</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = 'badge-pending';
                                    $statusText = 'Chưa bắt đầu';
                                    if ($assignment['trangThai'] === 'in_progress') {
                                        $badgeClass = 'badge-progress';
                                        $statusText = 'Đang chấm';
                                    } elseif ($assignment['trangThai'] === 'completed') {
                                        $badgeClass = 'badge-completed';
                                        $statusText = 'Hoàn thành';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($assignment['ghiChu'] ?? '-'); ?></td>
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
            const table = document.getElementById('assignmentTable');
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

        function sortTable() {
            const sortBy = document.getElementById('sortSelect').value;
            const table = document.getElementById('assignmentTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr'));

            if (!sortBy || rows.length <= 1) return;

            rows.sort((a, b) => {
                if (a.classList.contains('empty-state') || !a.dataset.date) return 0;
                
                switch(sortBy) {
                    case 'date-desc':
                        return new Date(b.dataset.date) - new Date(a.dataset.date);
                    case 'date-asc':
                        return new Date(a.dataset.date) - new Date(b.dataset.date);
                    case 'deadline':
                        const deadlineA = a.dataset.deadline || '9999-12-31';
                        const deadlineB = b.dataset.deadline || '9999-12-31';
                        return new Date(deadlineA) - new Date(deadlineB);
                    case 'teacher':
                        return (a.dataset.teacher || '').localeCompare(b.dataset.teacher || '');
                    default:
                        return 0;
                }
            });

            rows.forEach(row => tbody.appendChild(row));
        }
    </script>
        </div>
    </div>
</body>
</html>
