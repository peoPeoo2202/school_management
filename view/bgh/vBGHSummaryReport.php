<?php 
$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';

// Tính toán thống kê tổng quan
$tongLop = 0; $tongHocSinh = 0; $diemTBChung = 0; $tyLeGioi = 0;
$hsNam = $thongKeGioiTinh['Nam'] ?? 0;
$hsNu = $thongKeGioiTinh['Nu'] ?? 0;
$hsXuatSac = 0; $hsGioi = 0; $hsKha = 0; $hsTrungBinh = 0; $hsYeu = 0;

if (!empty($duLieuBaoCao)) {
    $tongLop = count($duLieuBaoCao);
    $tongDiem = 0; $demLopCoDiem = 0;
    
    foreach ($duLieuBaoCao as $row) {
        $tongHocSinh += $row['soHocSinh'];
        if ($row['diemTrungBinhLop']) {
            $tongDiem += $row['diemTrungBinhLop'];
            $demLopCoDiem++;
        }
        $hsXuatSac += ($row['soHSXuatSac'] ?? 0);
        $hsGioi += ($row['soHSGioi'] ?? 0);
        $hsKha += ($row['soHSKha'] ?? 0);
        $hsTrungBinh += ($row['soHSTrungBinh'] ?? 0);
        $hsYeu += ($row['soHSYeu'] ?? 0);
    }
    $diemTBChung = $demLopCoDiem > 0 ? round($tongDiem / $demLopCoDiem, 2) : 0;
    $tyLeGioi = $tongHocSinh > 0 ? round((($hsXuatSac + $hsGioi) / $tongHocSinh) * 100, 1) : 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Tổng Hợp Toàn Trường</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../teacher/style.css">
    <style>
      
        .container { max-width: 1800px; margin: 0 auto; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .content-area { flex: 1; padding: 20px; }
        .header { background: white; padding: 25px 35px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #2d3748; font-size: 28px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
        .header h1 i { color: #667eea; font-size: 32px; }
        .header-info { color: #6b7280; font-size: 14px; margin-top: 5px; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        
        /* Stats Grid - 6 cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1); display: flex; align-items: center; gap: 20px; transition: all 0.3s; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; right: 0; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; transform: translate(30%, -30%); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15); }
        .stat-icon { width: 70px; height: 70px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 30px; color: white; flex-shrink: 0; }
        .stat-icon.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .stat-icon.cyan { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
        .stat-details { flex: 1; z-index: 1; }
        .stat-details h3 { color: #6b7280; font-size: 13px; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-details .number { color: #1f2937; font-size: 32px; font-weight: 700; line-height: 1; }
        .stat-details .sub-info { color: #10b981; font-size: 13px; font-weight: 600; margin-top: 8px; display: flex; align-items: center; gap: 5px; }
        
        /* Charts Grid */
        .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .chart-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1); }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .chart-title { color: #1f2937; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .chart-wrapper { height: 350px; position: relative; }
        
        /* Filter Section */
        .filter-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { color: #374151; margin-bottom: 8px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 6px; }
        .form-group select { padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; background: white; transition: border 0.3s; }
        .form-group select:focus { outline: none; border-color: #667eea; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateBGH.php'); ?>
        <div class="content-area">
            <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1><i class="fas fa-chart-line"></i> Dashboard Tổng Hợp Toàn Trường</h1>
                <div class="header-info">Năm học <?php echo $_GET['namHoc'] ?? '2024-2025'; ?> - Học kỳ <?php echo $_GET['hocKy'] ?? 'Cả năm'; ?></div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="cBGHReport.php?action=index" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
                <a href="../public/index.php?logout=1" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-card">
            <form method="GET" action="cBGHReport.php">
                <input type="hidden" name="action" value="tong-hop">
                <div class="filter-grid">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Học kỳ</label>
                        <select name="hocKy">
                            <option value="">Cả năm</option>
                            <option value="1" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '1') ? 'selected' : ''; ?>>Học kỳ 1</option>
                            <option value="2" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '2') ? 'selected' : ''; ?>>Học kỳ 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Năm học</label>
                        <select name="namHoc">
                            <option value="2024-2025" <?php echo (!isset($_GET['namHoc']) || $_GET['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option>
                            <option value="2023-2024">2023-2024</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Xem báo cáo</button>
                <a href="cBGHReport.php?action=tong-hop" class="btn btn-secondary"><i class="fas fa-redo"></i> Đặt lại</a>
            </form>
        </div>

        <!-- Stats Cards - 6 cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-school"></i></div>
                <div class="stat-details">
                    <h3>Tổng số lớp</h3>
                    <div class="number"><?php echo $tongLop; ?></div>
                    <div class="sub-info"><i class="fas fa-layer-group"></i> Khối 6, 7, 8, 9</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-details">
                    <h3>Tổng học sinh</h3>
                    <div class="number"><?php echo number_format($tongHocSinh); ?></div>
                    <div class="sub-info"><i class="fas fa-arrow-up"></i> Đang học tập</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-details">
                    <h3>Giáo viên</h3>
                    <div class="number"><?php echo $tongGiaoVien; ?></div>
                    <div class="sub-info"><i class="fas fa-users"></i> Toàn trường</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-book"></i></div>
                <div class="stat-details">
                    <h3>Môn học</h3>
                    <div class="number"><?php echo $tongMonHoc; ?></div>
                    <div class="sub-info"><i class="fas fa-clipboard-list"></i> Chương trình</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-star"></i></div>
                <div class="stat-details">
                    <h3>Điểm TB</h3>
                    <div class="number"><?php echo $diemTBChung; ?></div>
                    <div class="sub-info" style="color: <?php echo $diemTBChung >= 7 ? '#10b981' : '#f59e0b'; ?>"><i class="fas fa-chart-line"></i> Toàn trường</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon cyan"><i class="fas fa-trophy"></i></div>
                <div class="stat-details">
                    <h3>Học sinh giỏi</h3>
                    <div class="number"><?php echo $tyLeGioi; ?>%</div>
                    <div class="sub-info"><i class="fas fa-award"></i> Xuất sắc & Giỏi</div>
                </div>
            </div>
        </div>

        <?php if (!empty($duLieuBaoCao)): ?>
        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Chart 1: Phân bố học lực -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title"><i class="fas fa-chart-bar"></i> Phân Bố Học Lực Theo Lớp</h3>
                </div>
                <div class="chart-wrapper"><canvas id="gradeDistChart"></canvas></div>
            </div>
            
            <!-- Chart 2: Pie chart tổng quan -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title"><i class="fas fa-chart-pie"></i> Tỷ Lệ Học Lực Toàn Trường</h3>
                </div>
                <div class="chart-wrapper"><canvas id="gradePieChart"></canvas></div>
            </div>
            
            <!-- Chart 3: Điểm trung bình theo lớp -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title"><i class="fas fa-chart-line"></i> Điểm Trung Bình Các Lớp</h3>
                </div>
                <div class="chart-wrapper"><canvas id="avgScoreChart"></canvas></div>
            </div>
            
            <!-- Chart 4: Top 10 lớp xuất sắc -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title"><i class="fas fa-medal"></i> Top 10 Lớp Xuất Sắc Nhất</h3>
                </div>
                <div class="chart-wrapper"><canvas id="topClassChart"></canvas></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        <?php if (!empty($duLieuBaoCao)): ?>
        // Chart 1: Stacked Bar - Phân bố học lực theo lớp
        const ctx1 = document.getElementById('gradeDistChart');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($duLieuBaoCao as $row) echo "'" . $row['tenLop'] . "',"; ?>],
                datasets: [
                    { label: 'Xuất sắc', data: [<?php foreach ($duLieuBaoCao as $row) echo ($row['soHSXuatSac'] ?? 0) . ','; ?>], backgroundColor: '#7c3aed', stack: 'stack1' },
                    { label: 'Giỏi', data: [<?php foreach ($duLieuBaoCao as $row) echo ($row['soHSGioi'] ?? 0) . ','; ?>], backgroundColor: '#10b981', stack: 'stack1' },
                    { label: 'Khá', data: [<?php foreach ($duLieuBaoCao as $row) echo ($row['soHSKha'] ?? 0) . ','; ?>], backgroundColor: '#3b82f6', stack: 'stack1' },
                    { label: 'TB', data: [<?php foreach ($duLieuBaoCao as $row) echo ($row['soHSTrungBinh'] ?? 0) . ','; ?>], backgroundColor: '#f59e0b', stack: 'stack1' },
                    { label: 'Yếu', data: [<?php foreach ($duLieuBaoCao as $row) echo ($row['soHSYeu'] ?? 0) . ','; ?>], backgroundColor: '#ef4444', stack: 'stack1' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
        });

        // Chart 2: Pie - Tỷ lệ tổng quan
        const ctx2 = document.getElementById('gradePieChart');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Xuất sắc', 'Giỏi', 'Khá', 'Trung bình', 'Yếu'],
                datasets: [{
                    data: [<?php echo $hsXuatSac . ',' . $hsGioi . ',' . $hsKha . ',' . $hsTrungBinh . ',' . $hsYeu; ?>],
                    backgroundColor: ['#7c3aed', '#10b981', '#3b82f6', '#f59e0b', '#ef4444']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
        });

        // Chart 3: Line - Điểm TB các lớp
        const ctx3 = document.getElementById('avgScoreChart');
        new Chart(ctx3, {
            type: 'line',
            data: {
                labels: [<?php foreach ($duLieuBaoCao as $row) echo "'" . $row['tenLop'] . "',"; ?>],
                datasets: [{
                    label: 'Điểm TB',
                    data: [<?php foreach ($duLieuBaoCao as $row) echo ($row['diemTrungBinhLop'] ?? 0) . ','; ?>],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 10 } } }
        });

        // Chart 4: Top 10 lớp
        <?php
        $topClasses = $duLieuBaoCao;
        usort($topClasses, function($a, $b) {
            return ($b['diemTrungBinhLop'] ?? 0) <=> ($a['diemTrungBinhLop'] ?? 0);
        });
        $topClasses = array_slice($topClasses, 0, 10);
        ?>
        const ctx4 = document.getElementById('topClassChart');
        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($topClasses as $row) echo "'" . $row['tenLop'] . "',"; ?>],
                datasets: [{
                    label: 'Điểm TB',
                    data: [<?php foreach ($topClasses as $row) echo ($row['diemTrungBinhLop'] ?? 0) . ','; ?>],
                    backgroundColor: '#10b981'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true, max: 10 } } }
        });
        <?php endif; ?>
    </script>
            </div>
        </div>
    </div>
</body>
</html>
