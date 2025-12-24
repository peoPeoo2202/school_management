<?php
/**
 * View: vRoomAssignment.php
 * Phân công phòng học cho lớp
 */

// Kiểm tra xem được gọi từ controller hay không
if (!isset($allRooms) || !isset($allClasses)) {
    header("Location: ../controller/cTeachingAssignment.php?action=roomAssignment");
    exit();
}

// Chỉ start session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập và quyền
if (!isset($_SESSION['login']) || $_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    header("Location: ../public/index.php");
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';

// Lấy thông báo nếu có
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$currentNamHoc = $namHoc ?? date('Y') . '-' . (date('Y') + 1);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công Phòng học - BGH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../student/style.css">
    <style>
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #11998e;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #11998e;
            color: white;
        }

        .btn-primary:hover {
            background: #0d7d74;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .breadcrumb {
            background: white;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .breadcrumb a {
            color: #11998e;
            text-decoration: none;
        }

        .breadcrumb span {
            color: #666;
            margin: 0 10px;
        }

        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-bar select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            min-width: 150px;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header.purple {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-header h3 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
            max-height: 550px;
            overflow-y: auto;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background: #f5f5f5;
        }

        tr.selected {
            background: #e8f5e9;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-warning {
            background: #fff3e0;
            color: #ef6c00;
        }

        .badge-danger {
            background: #ffebee;
            color: #c62828;
        }

        .badge-primary {
            background: #e3f2fd;
            color: #1565c0;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }

        .action-btn.select {
            background: #e3f2fd;
            color: #1565c0;
        }

        .action-btn.select:hover {
            background: #1565c0;
            color: white;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        .room-card {
            border: 2px solid #eee;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .room-card:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .room-card.selected {
            border-color: #28a745;
            background: #e8f5e9;
        }

        .room-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f5f5f5;
        }

        .room-info h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .room-info p {
            color: #666;
            font-size: 13px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateBGH.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
        <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-door-open"></i>
                Phân công Phòng học cho Lớp
            </h1>
            <div>
                <a href="../view/bgh/vTeachingAssignment.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="../view/bgh/vBGHDashboard.php"><i class="fas fa-home"></i> Trang chủ</a>
            <span>›</span>
            <a href="../view/bgh/vTeachingAssignment.php">Phân công Giảng dạy</a>
            <span>›</span>
            <strong>Phân công Phòng học</strong>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <label><strong>Năm học:</strong></label>
            <select id="namHocFilter" onchange="changeNamHoc()">
                <?php foreach ($schoolYears as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo $year === $currentNamHoc ? 'selected' : ''; ?>>
                    <?php echo $year; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Danh sách lớp chưa có phòng -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Lớp chưa có phòng học</h3>
                    <span class="badge" style="background: white; color: #11998e;">
                        <?php echo count($classesWithoutRoom); ?> lớp
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($classesWithoutRoom)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>Tất cả lớp đã được phân phòng học!</p>
                    </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã lớp</th>
                                <th>Tên lớp</th>
                                <th>Khối</th>
                                <th>Sĩ số</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classesWithoutRoom as $class): ?>
                            <tr id="class-<?php echo $class['maLop']; ?>">
                                <td><?php echo htmlspecialchars($class['maLop']); ?></td>
                                <td><strong><?php echo htmlspecialchars($class['tenLop']); ?></strong></td>
                                <td><?php echo htmlspecialchars($class['khoiLop']); ?></td>
                                <td><?php echo $class['siSo']; ?> HS</td>
                                <td>
                                    <button class="action-btn select" onclick="openRoomModal(<?php echo $class['maLop']; ?>, '<?php echo htmlspecialchars($class['tenLop']); ?>')">
                                        <i class="fas fa-plus"></i> Sắp phòng
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Danh sách tất cả phòng học -->
            <div class="card">
                <div class="card-header purple">
                    <h3><i class="fas fa-building"></i> Danh sách Phòng học</h3>
                    <span class="badge" style="background: white; color: #667eea;">
                        <?php echo count($allRooms); ?> phòng
                    </span>
                </div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã phòng</th>
                                <th>Tên phòng</th>
                                <th>Loại phòng</th>
                                <th>Sức chứa</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allRooms as $room): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['maPhong']); ?></td>
                                <td><strong><?php echo htmlspecialchars($room['tenPhong']); ?></strong></td>
                                <td><?php echo htmlspecialchars($room['loaiPhong']); ?></td>
                                <td><?php echo $room['soLuongSV']; ?> SV</td>
                                <td>
                                    <?php if ($room['trangThai'] === 'Hoatdong'): ?>
                                    <span class="badge badge-success">Hoạt động</span>
                                    <?php elseif ($room['trangThai'] === 'Baotri'): ?>
                                    <span class="badge badge-danger">Bảo trì</span>
                                    <?php else: ?>
                                    <span class="badge badge-warning"><?php echo $room['trangThai']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Danh sách lớp đã có phòng -->
        <div class="card" style="margin-top: 25px;">
            <div class="card-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3><i class="fas fa-check-circle"></i> Lớp đã được phân phòng</h3>
            </div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Mã lớp</th>
                            <th>Tên lớp</th>
                            <th>Khối</th>
                            <th>Phòng học</th>
                            <th>Sĩ số</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $assignedClasses = array_filter($allClasses, function($c) { return isset($c['tenPhong']) && $c['tenPhong'] !== null && $c['tenPhong'] !== ''; });
                        if (empty($assignedClasses)): 
                        ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: #666;">
                                <i class="fas fa-info-circle"></i> Chưa có lớp nào được phân phòng
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($assignedClasses as $class): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['maLop']); ?></td>
                            <td><strong><?php echo htmlspecialchars($class['tenLop']); ?></strong></td>
                            <td><?php echo htmlspecialchars($class['khoiLop']); ?></td>
                            <td>
                                <span class="badge badge-primary">
                                    <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($class['tenPhong']); ?>
                                </span>
                            </td>
                            <td><?php echo $class['siSo']; ?> HS</td>
                            <td>
                                <button class="action-btn select" onclick="openRoomModal(<?php echo $class['maLop']; ?>, '<?php echo htmlspecialchars($class['tenLop']); ?>')">
                                    <i class="fas fa-edit"></i> Đổi phòng
                                </button>
                                <button class="action-btn delete" onclick="confirmRevokeRoom(<?php echo $class['maLop']; ?>, '<?php echo htmlspecialchars($class['tenLop']); ?>')" style="background: #e74c3c;">
                                    <i class="fas fa-times"></i> Thu hồi
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal chọn phòng -->
    <div class="modal" id="roomModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-door-open"></i> Chọn phòng cho lớp: <span id="selectedClassName"></span></h3>
                <button class="modal-close" onclick="closeRoomModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="assignRoomForm" action="../controller/cTeachingAssignment.php?action=assignRoom" method="POST">
                    <input type="hidden" name="maLop" id="selectedClassId">
                    <input type="hidden" name="maPhong" id="selectedRoomId">
                    <input type="hidden" name="namHoc" value="<?php echo htmlspecialchars($currentNamHoc); ?>">

                    <div class="form-check">
                        <input type="checkbox" id="showAvailableOnly" onchange="toggleAvailableRooms()">
                        <label for="showAvailableOnly">Chỉ hiển thị phòng trống (chưa phân cho lớp nào)</label>
                    </div>

                    <div id="roomsList">
                        <?php foreach ($allRooms as $room): 
                            $isAssigned = !in_array($room, $availableRooms);
                            $isMaintenance = $room['trangThai'] === 'Baotri';
                            $isDisabled = $isMaintenance;
                        ?>
                        <div class="room-card <?php echo $isDisabled ? 'disabled' : ''; ?>" 
                             data-id="<?php echo $room['maPhong']; ?>"
                             data-available="<?php echo $isAssigned ? '0' : '1'; ?>"
                             data-maintenance="<?php echo $isMaintenance ? '1' : '0'; ?>"
                             onclick="<?php echo $isDisabled ? '' : 'selectRoom(this)'; ?>">
                            <div class="room-info">
                                <h4><?php echo htmlspecialchars($room['tenPhong']); ?></h4>
                                <p>
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($room['loaiPhong']); ?> |
                                    <i class="fas fa-users"></i> Sức chứa: <?php echo $room['soLuongSV']; ?> SV |
                                    <i class="fas fa-ruler-combined"></i> <?php echo $room['dienTich']; ?> m²
                                </p>
                            </div>
                            <div>
                                <?php if ($isMaintenance): ?>
                                <span class="badge badge-danger">Bảo trì</span>
                                <?php elseif ($isAssigned): ?>
                                <span class="badge badge-warning">Đã phân</span>
                                <?php else: ?>
                                <span class="badge badge-success">Trống</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="closeRoomModal()">Hủy</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Lưu phân công
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Change năm học
        function changeNamHoc() {
            const namHoc = document.getElementById('namHocFilter').value;
            window.location.href = '../controller/cTeachingAssignment.php?action=roomAssignment&namHoc=' + encodeURIComponent(namHoc);
        }

        // Open room modal
        function openRoomModal(maLop, tenLop) {
            document.getElementById('selectedClassId').value = maLop;
            document.getElementById('selectedClassName').textContent = tenLop;
            document.getElementById('selectedRoomId').value = '';
            document.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
            document.getElementById('roomModal').classList.add('active');
        }

        // Close room modal
        function closeRoomModal() {
            document.getElementById('roomModal').classList.remove('active');
        }

        // Toggle available rooms
        function toggleAvailableRooms() {
            const showAvailable = document.getElementById('showAvailableOnly').checked;
            document.querySelectorAll('.room-card').forEach(card => {
                if (showAvailable && card.dataset.available === '0') {
                    card.style.display = 'none';
                } else {
                    card.style.display = 'flex';
                }
            });
        }

        // Select room
        function selectRoom(element) {
            if (element.dataset.maintenance === '1') {
                alert('Phòng này đang bảo trì, không thể chọn!');
                return;
            }
            document.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('selectedRoomId').value = element.dataset.id;
        }

        // Validate form before submit
        document.getElementById('assignRoomForm').addEventListener('submit', function(e) {
            if (!document.getElementById('selectedRoomId').value) {
                e.preventDefault();
                alert('Vui lòng chọn một phòng học!');
            }
        });

        // Close modal on outside click
        document.getElementById('roomModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRoomModal();
            }
        });

        // Confirm revoke room assignment
        function confirmRevokeRoom(maLop, tenLop) {
            if (confirm('Bạn có chắc chắn muốn thu hồi phân công phòng học của lớp "' + tenLop + '"?')) {
                window.location.href = '../controller/cTeachingAssignment.php?action=revokeRoomAssignment&maLop=' + maLop + '&namHoc=<?php echo urlencode($currentNamHoc); ?>';
            }
        }
    </script>
        </div>
    </div>
</div>
</body>
</html>
