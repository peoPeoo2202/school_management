<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

// Kiểm tra quyền truy cập - Chỉ Ban giám hiệu
if ($_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';

// Xác định có lọc tìm kiếm chưa (ít nhất 1 bộ lọc được chọn)
$daLoc = false;
if (
    (isset($_GET['maLop']) && $_GET['maLop'] !== '') ||
    (isset($_GET['maMonHoc']) && $_GET['maMonHoc'] !== '') ||
    (isset($_GET['hocKy']) && $_GET['hocKy'] !== '') ||
    (isset($_GET['namHoc']) && $_GET['namHoc'] !== '' && $_GET['namHoc'] !== '2024-2025')
) {
    $daLoc = true;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo kết quả học tập toàn trường</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../teacher/style.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateBGH.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <!-- Header Section -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-graduation-cap"></i> Báo cáo kết quả học tập</h2>
                    <p>Xem chi tiết kết quả học tập toàn trường theo các tiêu chí lọc</p>
                </div>
                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <!-- Content Card -->
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }

        .stat-icon.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .stat-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }

        .stat-details h3 {
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .stat-details .number {
            color: #1f2937;
            font-size: 28px;
            font-weight: 700;
        }

        /* Main Content */
        .main-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .page-title {
            color: #1f2937;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
            font-size: 24px;
            font-weight: 700;
        }

        /* Filter Section */
        .filter-section {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: #374151;
            font-weight: 600;
            font-size: 16px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: #374151;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group select,
        .form-group input {
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Table Styles */
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
        }

        .export-section {
            display: flex;
            gap: 10px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        table.dataTable {
            width: 100% !important;
            border-collapse: separate;
            border-spacing: 0;
        }

        table.dataTable thead {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
        }

        table.dataTable thead th {
            padding: 15px 12px;
            text-align: center;
            border: none;
            color: white;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.dataTable tbody td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            vertical-align: middle;
        }

        table.dataTable tbody tr {
            transition: all 0.2s;
        }

        table.dataTable tbody tr:hover {
            background-color: #f3f4f6;
        }

        /* Grade Badges */
        .grade-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .grade-xuat-sac {
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            color: white;
        }

        .grade-gioi {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .grade-kha {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .grade-trung-binh {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .grade-yeu {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .grade-kem {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: white;
        }

        /* Score Cell Styling */
        .score-cell {
            font-weight: 600;
            color: #1f2937;
        }

        .score-excellent { color: #9333ea; }
        .score-good { color: #059669; }
        .score-average { color: #d97706; }
        .score-poor { color: #dc2626; }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #6b7280;
            font-style: italic;
        }

        /* DataTables Custom Styling */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 15px;
            color: #374151;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 5px 12px;
            margin: 0 3px;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
            background: white;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #667eea;
            color: white !important;
            border-color: #667eea;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            border-color: #667eea;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php
    // Tính toán thống kê
    $tongHocSinh = 0;
    $tongXuatSac = 0;
    $tongGioi = 0;
    $tongKha = 0;
    $tongTrungBinh = 0;
    $tongYeuKem = 0;
    $diemTBToanTruong = 0;
    
    if (!empty($duLieuBaoCao)) {
            $dsMaHS = array();
            foreach ($duLieuBaoCao as $row) {
                if (!empty($row['maHS'])) {
                    $dsMaHS[$row['maHS']] = true;
                }
            }
            $tongHocSinh = count($dsMaHS); // Số học sinh duy nhất có điểm
        $tongDiem = 0;
        foreach ($duLieuBaoCao as $row) {
            if (isset($row['xepLoai'])) {
                switch ($row['xepLoai']) {
                    case 'Xuất sắc': $tongXuatSac++; break;
                    case 'Giỏi': $tongGioi++; break;
                    case 'Khá': $tongKha++; break;
                    case 'Trung bình': $tongTrungBinh++; break;
                    default: $tongYeuKem++; break;
                }
            }
            if (isset($row['diemTrungBinh'])) {
                $tongDiem += $row['diemTrungBinh'];
            }
        }
        $diemTBToanTruong = $tongHocSinh > 0 ? round($tongDiem / $tongHocSinh, 2) : 0;
    }
    ?>

            <!-- Content Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-chart-line"></i> Kết quả học tập toàn trường
                    </h2>
                </div>

        <!-- Statistics Cards -->
        <?php if ($daLoc && !empty($duLieuBaoCao)): ?>
                <div class="card-body">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3>Tổng số học sinh</h3>
                    <div class="number"><?php echo number_format($tongHocSinh); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-details">
                    <h3>Xuất sắc & Giỏi</h3>
                    <div class="number"><?php echo number_format($tongXuatSac + $tongGioi); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="stat-details">
                    <h3>Điểm TB</h3>
                    <div class="number"><?php echo number_format($diemTBToanTruong, 2); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-details">
                    <h3>Yếu & Kém</h3>
                    <div class="number"><?php echo number_format($tongYeuKem); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="main-content">
            <h2 class="page-title">
                <i class="fas fa-book-reader"></i> Chi tiết kết quả học tập
            </h2>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-header">
                    <i class="fas fa-filter"></i> Bộ lọc tìm kiếm
                </div>
                <form method="GET" action="cBGHReport.php" id="filterForm">
                    <input type="hidden" name="action" value="hoc-tap">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="maLop"><i class="fas fa-school"></i> Lớp học</label>
                            <select name="maLop" id="maLop">
                                <option value="">Tất cả các lớp</option>
                                <?php foreach ($danhSachLop as $lop): ?>
                                    <option value="<?php echo $lop['maLop']; ?>" 
                                        <?php echo (isset($_GET['maLop']) && $_GET['maLop'] == $lop['maLop']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lop['tenLop']); ?> 
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="maMonHoc"><i class="fas fa-book"></i> Môn học</label>
                            <select name="maMonHoc" id="maMonHoc">
                                <option value="">Tất cả môn học</option>
                                <?php foreach ($danhSachMonHoc as $mon): ?>
                                    <option value="<?php echo $mon['maMonHoc']; ?>" 
                                        <?php echo (isset($_GET['maMonHoc']) && $_GET['maMonHoc'] == $mon['maMonHoc']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mon['tenMonHoc']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="hocKy"><i class="fas fa-calendar-alt"></i> Học kỳ</label>
                            <select name="hocKy" id="hocKy">
                                <option value="">Tất cả học kỳ</option>
                                <option value="1" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '1') ? 'selected' : ''; ?>>Học kỳ 1</option>
                                <option value="2" <?php echo (isset($_GET['hocKy']) && $_GET['hocKy'] == '2') ? 'selected' : ''; ?>>Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="namHoc"><i class="fas fa-calendar"></i> Năm học</label>
                            <select name="namHoc" id="namHoc">
                                <option value="2024-2025" <?php echo (!isset($_GET['namHoc']) || $_GET['namHoc'] == '2024-2025') ? 'selected' : ''; ?>>2024-2025</option>
                                <option value="2023-2024" <?php echo (isset($_GET['namHoc']) && $_GET['namHoc'] == '2023-2024') ? 'selected' : ''; ?>>2023-2024</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                        <a href="cBGHReport.php?action=hoc-tap" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Đặt lại
                        </a>
                        <div id="filterError" style="color: red; margin-top: 10px; display: none; font-weight: 600;">
                            Vui lòng chọn đầy đủ Lớp, Môn học, Học kỳ và Năm học để tìm kiếm!
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table Header with Export -->
            <?php if ($daLoc): ?>
                <?php if (!empty($duLieuBaoCao)): ?>
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-table"></i> Danh sách kết quả (<?php echo number_format($tongHocSinh); ?> bản ghi)
                    </div>
                    <div class="export-section">
                        <a href="cBGHReport.php?action=xuat-excel&loai=hoc-tap&maLop=<?php echo $_GET['maLop'] ?? ''; ?>&maMonHoc=<?php echo $_GET['maMonHoc'] ?? ''; ?>&hocKy=<?php echo $_GET['hocKy'] ?? ''; ?>&namHoc=<?php echo $_GET['namHoc'] ?? '2024-2025'; ?>" 
                           class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Xuất Excel
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- DataTable -->
                <div class="table-container">
                    <table id="academicTable" class="display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã HS</th>
                                <th>Họ tên</th>
                                <th>Lớp</th>
                                <th>Môn học</th>
                                <th>HK</th>
                                <th>TX1</th>
                                <th>TX2</th>
                                <th>TX3</th>
                                <th>TX4</th>
                                <th>Giữa kỳ</th>
                                <th>Cuối kỳ</th>
                                <th>TB</th>
                                <th>Xếp loại</th>
                                <th>Giáo viên</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($duLieuBaoCao)): ?>
                                <tr>
                                    <td colspan="15" class="no-data">
                                        <i class="fas fa-inbox"></i><br>
                                        Không có dữ liệu để hiển thị.<br>
                                        <small>Vui lòng chọn bộ lọc phù hợp.</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $stt = 1; 
                                foreach ($duLieuBaoCao as $row): 
                                    // Xác định class màu cho điểm TB
                                    $scoreClass = 'score-cell';
                                    if ($row['diemTrungBinh'] >= 8.0) {
                                        $scoreClass .= ' score-excellent';
                                    } elseif ($row['diemTrungBinh'] >= 6.5) {
                                        $scoreClass .= ' score-good';
                                    } elseif ($row['diemTrungBinh'] >= 5.0) {
                                        $scoreClass .= ' score-average';
                                    } else {
                                        $scoreClass .= ' score-poor';
                                    }
                                    // Xác định class cho badge xếp loại
                                    $badgeClass = 'grade-badge';
                                    switch ($row['xepLoai']) {
                                        case 'Xuất sắc': $badgeClass .= ' grade-xuat-sac'; break;
                                        case 'Giỏi': $badgeClass .= ' grade-gioi'; break;
                                        case 'Khá': $badgeClass .= ' grade-kha'; break;
                                        case 'Trung bình': $badgeClass .= ' grade-trung-binh'; break;
                                        case 'Yếu': $badgeClass .= ' grade-yeu'; break;
                                        default: $badgeClass .= ' grade-kem'; break;
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['maHS']); ?></strong></td>
                                    <td style="text-align: left;"><?php echo htmlspecialchars($row['tenHocSinh']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tenLop']); ?></td>
                                    <td style="text-align: left;"><?php echo htmlspecialchars($row['tenMonHoc']); ?></td>
                                    <td><?php echo htmlspecialchars($row['hocKy']); ?></td>
                                    <td><?php echo $row['diemTX1'] !== null ? number_format($row['diemTX1'], 1) : '-'; ?></td>
                                    <td><?php echo $row['diemTX2'] !== null ? number_format($row['diemTX2'], 1) : '-'; ?></td>
                                    <td><?php echo $row['diemTX3'] !== null ? number_format($row['diemTX3'], 1) : '-'; ?></td>
                                    <td><?php echo $row['diemTX4'] !== null ? number_format($row['diemTX4'], 1) : '-'; ?></td>
                                    <td><?php echo $row['diemGiuaKy'] !== null ? number_format($row['diemGiuaKy'], 1) : '-'; ?></td>
                                    <td><?php echo $row['diemCuoiKy'] !== null ? number_format($row['diemCuoiKy'], 1) : '-'; ?></td>
                                    <td class="<?php echo $scoreClass; ?>">
                                        <?php echo $row['diemTrungBinh'] !== null ? number_format($row['diemTrungBinh'], 1) : '-'; ?>
                                    </td>
                                    <td>
                                        <span class="<?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($row['xepLoai'] ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: left;"><?php echo htmlspecialchars($row['tenGiaoVien'] ?? 'Chưa phân công'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data" style="padding: 60px 0;">
                    <i class="fas fa-search"></i><br>
                    Vui lòng chọn bộ lọc để hiển thị dữ liệu.<br>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script>
        $(document).ready(function() {
            // Bắt buộc chọn đầy đủ tất cả bộ lọc mới cho submit
            $('#filterForm').on('submit', function(e) {
                var maLop = $('#maLop').val();
                var maMonHoc = $('#maMonHoc').val();
                var hocKy = $('#hocKy').val();
                var namHoc = $('#namHoc').val();
                if (maLop === '' || maMonHoc === '' || hocKy === '' || namHoc === '') {
                    $('#filterError').show();
                    e.preventDefault();
                } else {
                    $('#filterError').hide();
                }
            });

            <?php if (!empty($duLieuBaoCao)): ?>
            $('#academicTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tất cả"]],
                language: {
                    "sProcessing":   "Đang xử lý...",
                    "sLengthMenu":   "Hiển thị _MENU_ bản ghi",
                    "sZeroRecords":  "Không tìm thấy dữ liệu",
                    "sInfo":         "Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ bản ghi",
                    "sInfoEmpty":    "Hiển thị 0 đến 0 trong tổng số 0 bản ghi",
                    "sInfoFiltered": "(lọc từ _MAX_ bản ghi)",
                    "sSearch":       "Tìm kiếm:",
                    "oPaginate": {
                        "sFirst":    "Đầu",
                        "sPrevious": "Trước",
                        "sNext":     "Tiếp",
                        "sLast":     "Cuối"
                    }
                },
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [6, 7, 8, 9, 10, 11] }
                ]
            });
            <?php endif; ?>
        });
    </script>

            </div>
        </div>
    </div>

    <script>
        // Menu toggle functionality for mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.bgh-navbar');
            
            if (window.innerWidth <= 768) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'navbar-toggle';
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                document.body.appendChild(toggleBtn);
                
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('active');
                });
                
                document.addEventListener('click', function(e) {
                    if (!sidebar.contains(e.target) && !e.target.classList.contains('navbar-toggle')) {
                        sidebar.classList.remove('active');
                    }
                });
            }
            
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
