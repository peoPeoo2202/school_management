<?php $hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê điểm môn học</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; }
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
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15); }
        .stat-card h3 { color: #6b7280; font-size: 13px; font-weight: 500; margin-bottom: 10px; }
        .stat-card .number { color: #1f2937; font-size: 32px; font-weight: 700; margin-bottom: 8px; }
        .stat-card .subject-name { color: #667eea; font-size: 16px; font-weight: 600; }
        .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 20px; margin-bottom: 30px; }
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
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-purple { background: #e9d5ff; color: #7c3aed; }
        .badge-green { background: #d1fae5; color: #059669; }
        .badge-blue { background: #dbeafe; color: #2563eb; }
        .badge-orange { background: #fed7aa; color: #d97706; }
        .badge-red { background: #fee2e2; color: #dc2626; }
        .badge-gray { background: #e5e7eb; color: #6b7280; }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { margin-top: 15px; color: #374151; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 5px 12px; margin: 0 3px; border-radius: 5px; border: 1px solid #e5e7eb; background: white; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #667eea; color: white !important; border-color: #667eea; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white !important; }
    </style>
</head>
<body>
    <?php
    $tongMonHoc = 0; $monCaoDiem = ['ten' => '-', 'diem' => 0]; $monThapDiem = ['ten' => '-', 'diem' => 10];
    $duLieuGopMon = [];
    if (!empty($duLieuThongKe)) {
        // Gộp các bản ghi trùng tên môn học, tính lại các thống kê
        foreach ($duLieuThongKe as $row) {
            $tenMon = $row['tenMonHoc'];
            if (!isset($duLieuGopMon[$tenMon])) {
                $duLieuGopMon[$tenMon] = [
                    'tenMonHoc' => $tenMon,
                    'tongSoHocSinh' => 0,
                    'tongDiem' => 0,
                    'soBanGhi' => 0,
                    'soHSXuatSac' => 0,
                    'soHSGioi' => 0,
                    'soHSKha' => 0,
                    'soHSTrungBinh' => 0,
                    'soHSYeu' => 0,
                    'soHSKem' => 0
                ];
            }
            $duLieuGopMon[$tenMon]['tongSoHocSinh'] += $row['tongSoHocSinh'] ?? 0;
            $duLieuGopMon[$tenMon]['tongDiem'] += ($row['diemTrungBinh'] ?? 0);
            $duLieuGopMon[$tenMon]['soBanGhi']++;
            $duLieuGopMon[$tenMon]['soHSXuatSac'] += $row['soHSXuatSac'] ?? 0;
            $duLieuGopMon[$tenMon]['soHSGioi'] += $row['soHSGioi'] ?? 0;
            $duLieuGopMon[$tenMon]['soHSKha'] += $row['soHSKha'] ?? 0;
            $duLieuGopMon[$tenMon]['soHSTrungBinh'] += $row['soHSTrungBinh'] ?? 0;
            $duLieuGopMon[$tenMon]['soHSYeu'] += $row['soHSYeu'] ?? 0;
            $duLieuGopMon[$tenMon]['soHSKem'] += $row['soHSKem'] ?? 0;
        }
        // Tính lại điểm trung bình cho từng môn
        foreach ($duLieuGopMon as $mon => &$data) {
            $data['diemTrungBinh'] = $data['soBanGhi'] > 0 ? $data['tongDiem'] / $data['soBanGhi'] : 0;
        }
        unset($data);
        $tongMonHoc = count($duLieuGopMon);
        // Tìm môn cao điểm/thấp điểm
        foreach ($duLieuGopMon as $data) {
            $dtb = $data['diemTrungBinh'];
            if ($dtb > $monCaoDiem['diem']) $monCaoDiem = ['ten' => $data['tenMonHoc'], 'diem' => $dtb];
            if ($dtb < $monThapDiem['diem'] && $dtb > 0) $monThapDiem = ['ten' => $data['tenMonHoc'], 'diem' => $dtb];
        }
    }
    ?>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> Thống kê điểm môn học</h1>
            <div style="display: flex; gap: 10px;">
                <a href="cBGHReport.php?action=index" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
                <a href="../public/index.php?logout=1" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-book"></i> Tổng số môn học</h3>
                <div class="number"><?php echo $tongMonHoc; ?></div>
                <div class="subject-name">Môn học trong hệ thống</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-arrow-up"></i> Môn có điểm trung bình điểm cao nhất</h3>
                <div class="number"><?php echo number_format($monCaoDiem['diem'], 2); ?></div>
                <div class="subject-name"><?php echo htmlspecialchars($monCaoDiem['ten']); ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-arrow-down"></i> Môn có điểm trung bình thấp nhất</h3>
                <div class="number"><?php echo number_format($monThapDiem['diem'], 2); ?></div>
                <div class="subject-name"><?php echo htmlspecialchars($monThapDiem['ten']); ?></div>
            </div>
        </div>
        <?php if (!empty($duLieuThongKe)): ?>
        <div class="chart-grid">
            <div class="chart-container">
                <h3 style="margin-bottom: 20px; color: #1f2937;"><i class="fas fa-chart-pie"></i> Phân bố học lực theo môn</h3>
                <div class="chart-wrapper"><canvas id="gradeDistChart"></canvas></div>
            </div>
            <div class="chart-container">
                <h3 style="margin-bottom: 20px; color: #1f2937;"><i class="fas fa-chart-bar"></i> Điểm trung bình các môn</h3>
                <div class="chart-wrapper"><canvas id="avgScoreChart"></canvas></div>
            </div>
        </div>
        <?php endif; ?>
        <div class="main-content">
            <h2 class="page-title"><i class="fas fa-table"></i> Chi tiết thống kê từng môn</h2>
            <div class="filter-section">
                <form method="GET" action="cBGHReport.php">
                    <input type="hidden" name="action" value="thong-ke-diem">
                    <div class="filter-grid">
                        <div class="form-group"><label><i class="fas fa-calendar-alt"></i> Học kỳ</label>
                            <select name="hocKy"><option value="">Tất cả</option><option value="1" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '1') ? 'selected' : ''; ?>>Học kỳ 1</option><option value="2" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '2') ? 'selected' : ''; ?>>Học kỳ 2</option></select>
                        </div>
                        <div class="form-group"><label><i class="fas fa-calendar"></i> Năm học</label>
                            <select name="namHoc"><option value="2024-2025" <?php echo (!isset($_GET['namHoc']) || $_GET['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option><option value="2023-2024">2023-2024</option></select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tìm kiếm</button>
                    <a href="cBGHReport.php?action=thong-ke-diem" class="btn btn-secondary"><i class="fas fa-redo"></i> Đặt lại</a>
                </form>
            </div>
            <div style="overflow-x: auto;">
                <table id="gradeStatsTable" class="display" style="width:100%">
                    <thead>
                        <tr><th>STT</th><th>Môn học</th><th>Điểm TB</th><th>Xuất sắc</th><th>Giỏi</th><th>Khá</th><th>Trung bình</th><th>Yếu</th><th>Kém</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($duLieuGopMon)): ?>
                            <tr><td colspan="9" style="text-align: center; padding: 40px;">Không có dữ liệu</td></tr>
                        <?php else: $stt = 1; foreach ($duLieuGopMon as $row): ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['tenMonHoc']); ?></strong></td>
                                <td><strong style="color: #667eea;"><?php echo $row['diemTrungBinh'] ? number_format($row['diemTrungBinh'], 2) : '-'; ?></strong></td>
                                <td><span class="badge badge-purple"><?php echo $row['soHSXuatSac']; ?></span></td>
                                <td><span class="badge badge-green"><?php echo $row['soHSGioi']; ?></span></td>
                                <td><span class="badge badge-blue"><?php echo $row['soHSKha']; ?></span></td>
                                <td><span class="badge badge-orange"><?php echo $row['soHSTrungBinh']; ?></span></td>
                                <td><span class="badge badge-red"><?php echo $row['soHSYeu']; ?></span></td>
                                <td><span class="badge badge-gray"><?php echo $row['soHSKem']; ?></span></td>
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
            <?php if (!empty($duLieuGopMon)): ?>
            $('#gradeStatsTable').DataTable({
                pageLength: 25, order: [[3, 'desc']], language: { "sProcessing": "Đang xử lý...", "sLengthMenu": "Hiển thị _MENU_ bản ghi", "sZeroRecords": "Không tìm thấy dữ liệu", "sInfo": "Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ bản ghi", "sSearch": "Tìm kiếm:", "oPaginate": { "sFirst": "Đầu", "sPrevious": "Trước", "sNext": "Tiếp", "sLast": "Cuối" } }
            });
            const ctx1 = document.getElementById('gradeDistChart');
            new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: ['Xuất sắc', 'Giỏi', 'Khá', 'Trung bình', 'Yếu', 'Kém'],
                    datasets: [{
                        data: [
                            <?php $totals = ['xs' => 0, 'g' => 0, 'k' => 0, 'tb' => 0, 'y' => 0, 'km' => 0]; foreach ($duLieuGopMon as $row) { $totals['xs'] += $row['soHSXuatSac']; $totals['g'] += $row['soHSGioi']; $totals['k'] += $row['soHSKha']; $totals['tb'] += $row['soHSTrungBinh']; $totals['y'] += $row['soHSYeu']; $totals['km'] += $row['soHSKem']; } echo implode(',', [$totals['xs'], $totals['g'], $totals['k'], $totals['tb'], $totals['y'], $totals['km']]); ?>
                        ],
                        backgroundColor: ['#7c3aed', '#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#6b7280']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
            });
            const ctx2 = document.getElementById('avgScoreChart');
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: [<?php foreach ($duLieuGopMon as $row) echo "'" . addslashes($row['tenMonHoc']) . "',"; ?>],
                    datasets: [{
                        label: 'Điểm trung bình',
                        data: [<?php foreach ($duLieuGopMon as $row) echo ($row['diemTrungBinh'] ?? 0) . ','; ?>],
                        backgroundColor: '#667eea'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 10 } } }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
