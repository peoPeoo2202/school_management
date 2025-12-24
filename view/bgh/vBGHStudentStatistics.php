<?php $hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê số liệu học sinh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../teacher/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .content-area { flex: 1; padding: 20px; }
        .header { background: white; padding: 20px 30px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #2d3748; font-size: 26px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
        .header h1 i { color: #667eea; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .main-content { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); margin-bottom: 20px; }
        .page-title { color: #1f2937; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 3px solid #667eea; font-size: 24px; font-weight: 700; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); display: flex; align-items: center; gap: 20px; transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15); }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 26px; color: white; }
        .stat-icon.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .stat-icon.pink { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-details h3 { color: #6b7280; font-size: 13px; font-weight: 500; margin-bottom: 5px; }
        .stat-details .number { color: #1f2937; font-size: 28px; font-weight: 700; }
        .stat-details .percentage { color: #10b981; font-size: 14px; font-weight: 600; margin-top: 4px; }
        .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .chart-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }
        .chart-wrapper { height: 350px; position: relative; }
        .filter-section { background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e5e7eb; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { color: #374151; margin-bottom: 8px; font-weight: 600; font-size: 13px; }
        .form-group select { padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; background: white; }
        table.dataTable { width: 100% !important; border-collapse: separate; border-spacing: 0; }
        table.dataTable thead { background: linear-gradient(135deg, #1f2937 0%, #374151 100%); }
        table.dataTable thead th { padding: 15px 12px; text-align: center; border: none; color: white; font-weight: 600; font-size: 13px; }
        table.dataTable tbody td { padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
        table.dataTable tbody tr:hover { background-color: #f3f4f6; }
        .progress-bar { width: 100%; height: 8px; background: #e5e7eb; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; transition: width 0.3s; }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { margin-top: 15px; color: #374151; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 5px 12px; margin: 0 3px; border-radius: 5px; border: 1px solid #e5e7eb; background: white; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #667eea; color: white !important; border-color: #667eea; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white !important; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateBGH.php'); ?>
        <div class="content-area">
    <?php
    $tongHS = 0; $hsNam = 0; $hsNu = 0; $tyLeNam = 0; $tyLeNu = 0;
    if (!empty($duLieuThongKe)) {
        foreach ($duLieuThongKe as $row) {
            $tongHS += $row['soHocSinhThucTe'] ?? 0;
            $hsNam += $row['soHSNam'] ?? 0;
            $hsNu += $row['soHSNu'] ?? 0;
        }
        $tyLeNam = $tongHS > 0 ? round(($hsNam / $tongHS) * 100, 1) : 0;
        $tyLeNu = $tongHS > 0 ? round(($hsNu / $tongHS) * 100, 1) : 0;
    }
    ?>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> Thống kê số liệu học sinh</h1>
            <div style="display: flex; gap: 10px;">
                <a href="cBGHReport.php?action=index" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
                <a href="../public/index.php?logout=1" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-details"><h3>Tổng học sinh</h3><div class="number"><?php echo number_format($tongHS); ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-male"></i></div>
                <div class="stat-details"><h3>Học sinh nam</h3><div class="number"><?php echo number_format($hsNam); ?></div><div class="percentage"><?php echo $tyLeNam; ?>%</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pink"><i class="fas fa-female"></i></div>
                <div class="stat-details"><h3>Học sinh nữ</h3><div class="number"><?php echo number_format($hsNu); ?></div><div class="percentage"><?php echo $tyLeNu; ?>%</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-details"><h3>TB ngày nghỉ/HS</h3><div class="number"><?php echo !empty($duLieuThongKe) ? number_format(array_sum(array_column($duLieuThongKe, 'trungBinhNgayNghi')) / count($duLieuThongKe), 1) : 0; ?></div></div>
            </div>
        </div>
        <?php if (!empty($duLieuThongKe)): ?>
        <div class="chart-grid">
            <div class="chart-container">
                <h3 style="margin-bottom: 20px; color: #1f2937;"><i class="fas fa-chart-pie"></i> Tỷ lệ giới tính</h3>
                <div class="chart-wrapper"><canvas id="genderChart"></canvas></div>
            </div>
            <div class="chart-container">
                <h3 style="margin-bottom: 20px; color: #1f2937;"><i class="fas fa-chart-bar"></i> Sĩ số theo lớp</h3>
                <div class="chart-wrapper"><canvas id="classChart"></canvas></div>
            </div>
        </div>
        <?php endif; ?>
        <div class="main-content">
            <h2 class="page-title"><i class="fas fa-table"></i> Chi tiết theo lớp</h2>
            <div class="filter-section">
                <form method="GET" action="cBGHReport.php">
                    <input type="hidden" name="action" value="thong-ke-hoc-sinh">
                    <div class="filter-grid">
                        <div class="form-group"><label><i class="fas fa-layer-group"></i> Khối</label>
                            <select name="khoiLop">
                                <option value="">Tất cả</option>
                                <option value="10" <?php echo (isset($_GET['khoiLop']) && $_GET['khoiLop'] == '10') ? 'selected' : ''; ?>>Khối 10</option>
                                <option value="11" <?php echo (isset($_GET['khoiLop']) && $_GET['khoiLop'] == '11') ? 'selected' : ''; ?>>Khối 11</option>
                                <option value="12" <?php echo (isset($_GET['khoiLop']) && $_GET['khoiLop'] == '12') ? 'selected' : ''; ?>>Khối 12</option>
                            </select>
                        </div>
                        <div class="form-group"><label><i class="fas fa-calendar"></i> Năm học</label>
                            <select name="namHoc"><option value="2024-2025" <?php echo (!isset($_GET['namHoc']) || $_GET['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option><option value="2023-2024">2023-2024</option></select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tìm kiếm</button>
                    <a href="cBGHReport.php?action=thong-ke-hoc-sinh" class="btn btn-secondary"><i class="fas fa-redo"></i> Đặt lại</a>
                </form>
            </div>
            <div style="overflow-x: auto;">
                <table id="studentStatsTable" class="display" style="width:100%">
                    <thead>
                        <tr><th>STT</th><th>Lớp</th><th>Khối</th><th>Sĩ số</th><th>Nam</th><th>Nữ</th><th>Tỷ lệ Nam</th><th>Tỷ lệ Nữ</th><th>TB nghỉ/HS</th><th>GVCN</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($duLieuThongKe)): ?>
                            <tr><td colspan="10" style="text-align: center; padding: 40px;">Không có dữ liệu</td></tr>
                        <?php else: $stt = 1; foreach ($duLieuThongKe as $row): 
                            $tyLeNamLop = ($row['soHocSinhThucTe'] ?? 0) > 0 ? round(($row['soHSNam'] / $row['soHocSinhThucTe']) * 100, 1) : 0;
                            $tyLeNuLop = ($row['soHocSinhThucTe'] ?? 0) > 0 ? round(($row['soHSNu'] / $row['soHocSinhThucTe']) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['tenLop']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['khoiLop']); ?></td>
                                <td><strong style="color: #667eea;"><?php echo $row['soHocSinhThucTe'] ?? 0; ?></strong></td>
                                <td><span style="color: #3b82f6; font-weight: 600;"><?php echo $row['soHSNam'] ?? 0; ?></span></td>
                                <td><span style="color: #ec4899; font-weight: 600;"><?php echo $row['soHSNu']; ?></span></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div class="progress-bar" style="flex: 1;"><div class="progress-fill" style="width: <?php echo $tyLeNamLop; ?>%; background: #3b82f6;"></div></div>
                                        <span style="font-size: 12px; font-weight: 600; color: #3b82f6;"><?php echo $tyLeNamLop; ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div class="progress-bar" style="flex: 1;"><div class="progress-fill" style="width: <?php echo $tyLeNuLop; ?>%; background: #ec4899;"></div></div>
                                        <span style="font-size: 12px; font-weight: 600; color: #ec4899;"><?php echo $tyLeNuLop; ?>%</span>
                                    </div>
                                </td>
                                <td><?php echo number_format($row['trungBinhNgayNghi'], 1); ?></td>
                                <td><?php echo htmlspecialchars($row['giaoVienChuNhiem'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            <?php if (!empty($duLieuThongKe)): ?>
            $('#studentStatsTable').DataTable({
                pageLength: 25, language: { "sProcessing": "Đang xử lý...", "sLengthMenu": "Hiển thị _MENU_ bản ghi", "sZeroRecords": "Không tìm thấy dữ liệu", "sInfo": "Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ bản ghi", "sSearch": "Tìm kiếm:", "oPaginate": { "sFirst": "Đầu", "sPrevious": "Trước", "sNext": "Tiếp", "sLast": "Cuối" } }
            });
            const ctx1 = document.getElementById('genderChart');
            new Chart(ctx1, {
                type: 'pie',
                data: {
                    labels: ['Nam', 'Nữ'],
                    datasets: [{
                        data: [<?php echo $hsNam . ',' . $hsNu; ?>],
                        backgroundColor: ['#3b82f6', '#ec4899']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, title: { display: false } } }
            });
            const ctx2 = document.getElementById('classChart');
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: [<?php foreach ($duLieuThongKe as $row) echo "'" . $row['tenLop'] . "',"; ?>],
                    datasets: [
                        { label: 'Nam', data: [<?php foreach ($duLieuThongKe as $row) echo $row['soHSNam'] . ','; ?>], backgroundColor: '#3b82f6' },
                        { label: 'Nữ', data: [<?php foreach ($duLieuThongKe as $row) echo $row['soHSNu'] . ','; ?>], backgroundColor: '#ec4899' }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true }, x: { stacked: false } } }
            });
            <?php endif; ?>
        });
    </script>
        </div>
    </div>
</body>
</html>
