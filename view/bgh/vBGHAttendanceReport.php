<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

// Kiểm tra quyền Ban giám hiệu
if (!isset($_SESSION['loaiTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

// Lấy thông tin người dùng
$hoTen = $_SESSION['hoTen'] ?? 'Ban giám hiệu';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo chuyên cần toàn trường</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../teacher/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

    <style>
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #2d3748;
            font-size: 26px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 i {
            color: #667eea;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn-info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            font-size: 12px;
            padding: 6px 12px;
        }

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

        .stat-icon.blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .stat-icon.red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

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

        .form-group select {
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }

        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

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

        table.dataTable tbody tr:hover {
            background-color: #f3f4f6;
        }

        .attendance-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .attendance-tot {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .attendance-kha {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .attendance-trung-binh {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .attendance-yeu {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #6b7280;
            font-style: italic;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 20px;
        }

        .close {
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close:hover {
            transform: scale(1.2);
        }

        .modal-body {
            padding: 30px;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .detail-table th,
        .detail-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        .detail-table tr:hover {
            background: #f9fafb;
        }

        .type-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .type-cophep {
            background: #d1fae5;
            color: #065f46;
        }

        .type-khongphep {
            background: #fee2e2;
            color: #991b1b;
        }

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

        @media (max-width: 768px) {

            .stats-grid,
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 15px;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
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
            <?php
            $tongHocSinh = 0;
            $tongChuyenCanTot = 0;
            $tongChuyenCanKha = 0;
            $tongChuyenCanYeu = 0;
            $tongNghiCoPhep = 0;
            $tongNghiKhongPhep = 0;
            if (!empty($duLieuBaoCao)) {
                $tongHocSinh = count($duLieuBaoCao);
                foreach ($duLieuBaoCao as $row) {
                    $tongNghiCoPhep += $row['soNghiCoPhep'];
                    $tongNghiKhongPhep += $row['soNghiKhongPhep'];
                    if (isset($row['xepLoaiChuyenCan'])) {
                        switch ($row['xepLoaiChuyenCan']) {
                            case 'Tốt':
                                $tongChuyenCanTot++;
                                break;
                            case 'Khá':
                                $tongChuyenCanKha++;
                                break;
                            default:
                                $tongChuyenCanYeu++;
                                break;
                        }
                    }
                }
            }
            ?>
            <div class="header">
                <h1><i class="fas fa-user-check"></i> Báo cáo chuyên cần toàn trường</h1>
                <div class="btn-group">
                    <a href="cBGHReport.php?action=index" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
                    <a href="../public/index.php?logout=1" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="stat-details">
                        <h3>Tổng số học sinh</h3>
                        <div class="number"><?php echo number_format($tongHocSinh); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-details">
                        <h3>Chuyên cần tốt</h3>
                        <div class="number"><?php echo number_format($tongChuyenCanTot); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-details">
                        <h3>Nghỉ có phép</h3>
                        <div class="number"><?php echo number_format($tongNghiCoPhep); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-details">
                        <h3>Nghỉ không phép</h3>
                        <div class="number"><?php echo number_format($tongNghiKhongPhep); ?></div>
                    </div>
                </div>
            </div>
            <div class="main-content">
                <h2 class="page-title"><i class="fas fa-clipboard-list"></i> Chi tiết chuyên cần học sinh</h2>
                <div class="filter-section">
                    <div class="filter-header"><i class="fas fa-filter"></i> Bộ lọc tìm kiếm</div>
                    <form method="GET" action="cBGHReport.php">
                        <input type="hidden" name="action" value="chuyen-can">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label for="maLop"><i class="fas fa-school"></i> Lớp học</label>
                                <select name="maLop" id="maLop">
                                    <option value="">Tất cả các lớp</option>
                                    <?php foreach ($danhSachLop as $lop): ?>
                                        <option value="<?php echo $lop['maLop']; ?>" <?php echo (isset($_GET['maLop']) && $_GET['maLop'] == $lop['maLop']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($lop['tenLop']); ?> (<?php echo $lop['khoiLop']; ?>)
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
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tìm kiếm</button>
                            <a href="cBGHReport.php?action=chuyen-can" class="btn btn-secondary"><i class="fas fa-redo"></i> Đặt lại</a>
                        </div>
                    </form>
                </div>
                <?php if (!empty($duLieuBaoCao)): ?>
                    <div class="table-header">
                        <div class="table-title"><i class="fas fa-table"></i> Danh sách chuyên cần (<?php echo number_format($tongHocSinh); ?> học sinh)</div>
                        <div><a href="cBGHReport.php?action=xuat-excel&loai=chuyen-can&maLop=<?php echo $_GET['maLop'] ?? ''; ?>&hocKy=<?php echo $_GET['hocKy'] ?? ''; ?>&namHoc=<?php echo $_GET['namHoc'] ?? '2024-2025'; ?>" class="btn btn-success"><i class="fas fa-file-excel"></i> Xuất Excel</a></div>
                    </div>
                <?php endif; ?>
                <div class="table-container">
                    <table id="attendanceTable" class="display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã HS</th>
                                <th>Họ tên</th>
                                <th>Lớp</th>
                                <th>Khối</th>
                                <th>Tổng nghỉ</th>
                                <th>Có phép</th>
                                <th>Không phép</th>
                                <th>Xếp loại</th>
                                <th>GVCN</th>
                                <th>Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($duLieuBaoCao)): ?>
                                <tr>
                                    <td colspan="11" class="no-data"><i class="fas fa-inbox"></i><br>Không có dữ liệu để hiển thị.<br><small>Vui lòng chọn bộ lọc phù hợp.</small></td>
                                </tr>
                                <?php else: $stt = 1;
                                foreach ($duLieuBaoCao as $row):
                                    $badgeClass = 'attendance-badge';
                                    switch ($row['xepLoaiChuyenCan']) {
                                        case 'Tốt':
                                            $badgeClass .= ' attendance-tot';
                                            break;
                                        case 'Khá':
                                            $badgeClass .= ' attendance-kha';
                                            break;
                                        case 'Trung bình':
                                            $badgeClass .= ' attendance-trung-binh';
                                            break;
                                        default:
                                            $badgeClass .= ' attendance-yeu';
                                            break;
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $stt++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['maHS']); ?></strong></td>
                                        <td style="text-align: left;"><?php echo htmlspecialchars($row['tenHocSinh']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tenLop']); ?></td>
                                        <td><?php echo htmlspecialchars($row['khoiLop']); ?></td>
                                        <td><strong><?php echo $row['tongSoNghi']; ?></strong></td>
                                        <td><span style="color: #059669; font-weight: 600;"><?php echo $row['soNghiCoPhep']; ?></span></td>
                                        <td><span style="color: #dc2626; font-weight: 600;"><?php echo $row['soNghiKhongPhep']; ?></span></td>
                                        <td><span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['xepLoaiChuyenCan']); ?></span></td>
                                        <td style="text-align: left;"><?php echo htmlspecialchars($row['giaoVienChuNhiem'] ?? '-'); ?></td>
                                        <td><button class="btn btn-info view-detail-btn" data-mahs="<?php echo $row['maHS']; ?>" data-tenhs="<?php echo htmlspecialchars($row['tenHocSinh']); ?>" data-hocky="<?php echo $_GET['hocKy'] ?? ''; ?>" data-namhoc="<?php echo $_GET['namHoc'] ?? '2024-2025'; ?>"><i class="fas fa-eye"></i> Xem</button></td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div id="detailModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-info-circle"></i> Chi tiết ngày nghỉ - <span id="modalStudentName"></span></h2><span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="modalContent">
                        <div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #667eea;"></i>
                            <p style="margin-top: 15px; color: #6b7280;">Đang tải dữ liệu...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
        <script>
            $(document).ready(function() {
                <?php if (!empty($duLieuBaoCao)): ?>
                    $('#attendanceTable').DataTable({
                        responsive: true,
                        pageLength: 25,
                        lengthMenu: [
                            [10, 25, 50, 100, -1],
                            [10, 25, 50, 100, "Tất cả"]
                        ],
                        language: {
                            "sProcessing": "Đang xử lý...",
                            "sLengthMenu": "Hiển thị _MENU_ bản ghi",
                            "sZeroRecords": "Không tìm thấy dữ liệu",
                            "sInfo": "Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ bản ghi",
                            "sInfoEmpty": "Hiển thị 0 đến 0 trong tổng số 0 bản ghi",
                            "sInfoFiltered": "(lọc từ _MAX_ bản ghi)",
                            "sSearch": "Tìm kiếm:",
                            "oPaginate": {
                                "sFirst": "Đầu",
                                "sPrevious": "Trước",
                                "sNext": "Tiếp",
                                "sLast": "Cuối"
                            }
                        },
                        order: [
                            [5, 'desc']
                        ],
                        columnDefs: [{
                            orderable: false,
                            targets: [10]
                        }]
                    });
                <?php endif; ?>
                var modal = document.getElementById("detailModal");
                var span = document.getElementsByClassName("close")[0];
                $('.view-detail-btn').click(function() {
                    var maHS = $(this).data('mahs');
                    var tenHS = $(this).data('tenhs');
                    var hocKy = $(this).data('hocky');
                    var namHoc = $(this).data('namhoc');
                    $('#modalStudentName').text(tenHS);
                    modal.style.display = "block";
                    $.ajax({
                        url: 'cBGHReport.php',
                        method: 'GET',
                        data: {
                            action: 'chi-tiet-nghi-hoc',
                            maHS: maHS,
                            hocKy: hocKy,
                            namHoc: namHoc
                        },
                        success: function(response) {
                            $('#modalContent').html(response);
                        },
                        error: function() {
                            $('#modalContent').html('<p style="color: red; text-align: center;">Có lỗi xảy ra khi tải dữ liệu!</p>');
                        }
                    });
                });
                span.onclick = function() {
                    modal.style.display = "none";
                }
                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                }
            });
        </script>
    </div>
    </div>
</body>

</html>