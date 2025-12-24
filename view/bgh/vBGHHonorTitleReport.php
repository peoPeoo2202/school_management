<?php
if (!isset($_SESSION['loaiTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'bangiamhieu') {
    header('Location: ../../public/index.php');
    exit();
}

$data = $_SESSION['dataKetQuaDanhGia'] ?? [];
$ketQua = $data['ketQua'] ?? [];
$danhSachKhoi = $data['danhSachKhoi'] ?? [];
$danhSachLop = $data['danhSachLop'] ?? [];
$thongKe = $data['thongKe'] ?? [];
$namHoc = $data['namHoc'] ?? '2024-2025';
$maKhoi = $data['maKhoi'] ?? null;
$maLop = $data['maLop'] ?? null;
$danhSachDanhHieu = $data['danhSachDanhHieu'] ?? [];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo kết quả đánh giá danh hiệu học sinh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../student/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .content-area {
            flex: 1;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #1e3c72;
            margin: 0;
            font-size: 24px;
        }
        
        .btn-back {
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        .main-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filter-section {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-submit {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-submit:hover {
            background: #0056b3;
        }
        
        .statistics-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-card .label {
            color: #666;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .report-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .report-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .report-table th {
            background: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        .report-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        
        .report-table tr:hover {
            background: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-hoc-luc {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-hanh-kiem {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .badge-danh-hieu {
            background: #fff3e0;
            color: #e65100;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include(__DIR__ . '/../layouts/navigate/navigateBGH.php'); ?>
        <div class="content-area">
            <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-medal"></i> Báo cáo kết quả đánh giá danh hiệu học sinh
            </h1>
            <a href="../controller/cBGHReport.php?action=index" class="btn-back">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>

        <div class="main-content">
            <!-- Bộ lọc -->
            <div class="filter-section">
                <form class="filter-form" method="POST" action="../../controller/cBGHReport.php?action=ket-qua-danh-gia">
                    <div class="form-group">
                        <label>Năm học:</label>
                        <input type="text" name="namHoc" value="<?= htmlspecialchars($namHoc) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Khối:</label>
                        <select name="maKhoi" id="maKhoi">
                            <option value="">-- Tất cả khối --</option>
                            <?php foreach ($danhSachKhoi as $khoi): ?>
                                <option value="<?= $khoi['maKhoi'] ?>" <?= $maKhoi == $khoi['maKhoi'] ? 'selected' : '' ?>>
                                    Khối <?= $khoi['tenKhoi'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Lớp:</label>
                        <select name="maLop" id="maLop">
                            <option value="">-- Tất cả lớp --</option>
                            <?php foreach ($danhSachLop as $lop): ?>
                                <option value="<?= $lop['maLop'] ?>" <?= $maLop == $lop['maLop'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lop['tenLop']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit">Xem báo cáo</button>
                </form>
            </div>

            <?php if (!empty($ketQua)): ?>
                <!-- Thống kê tổng quan -->
                <div class="statistics-cards">
                    <div class="stat-card">
                        <div class="number"><?= $thongKe['tongSo'] ?? 0 ?></div>
                        <div class="label">Tổng số học sinh</div>
                    </div>
                    <?php foreach ($danhSachDanhHieu as $dh): ?>
                        <div class="stat-card">
                            <div class="number"><?= $thongKe['danhHieu'][$dh['tenDanhHieu']] ?? 0 ?></div>
                            <div class="label"><?= htmlspecialchars($dh['tenDanhHieu']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Bảng kết quả -->
                <div class="report-table">
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã HS</th>
                                <th>Họ tên</th>
                                <th>Lớp</th>
                                <th>Điểm TB HK1</th>
                                <th>Điểm TB HK2</th>
                                <th>Điểm TB cả năm</th>
                                <th>Học lực</th>
                                <th>Hạnh kiểm HK1</th>
                                <th>Hạnh kiểm HK2</th>
                                <th>Vi phạm</th>
                                <th>Khen thưởng</th>
                                <th>Danh hiệu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $stt = 1; ?>
                            <?php foreach ($ketQua as $hs): ?>
                                <?php $danhHieuHienTai = $hs['tenDanhHieu'] ?? ($hs['danhHieuHienTai'] ?? 'Chưa xét'); ?>
                                <tr>
                                    <td><?= $stt++ ?></td>
                                    <td><?= htmlspecialchars($hs['maHS']) ?></td>
                                    <td><?= htmlspecialchars($hs['hoTen']) ?></td>
                                    <td><?= htmlspecialchars($hs['tenLop']) ?></td>
                                    <td><?= number_format($hs['diemTBHK1'] ?? 0, 2) ?></td>
                                    <td><?= number_format($hs['diemTBHK2'] ?? 0, 2) ?></td>
                                    <td><?= number_format($hs['diemTBCaNam'] ?? 0, 2) ?></td>
                                    <td>
                                        <span class="badge badge-hoc-luc">
                                            <?= htmlspecialchars($hs['loaiHocLuc'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-hanh-kiem">
                                            <?= htmlspecialchars($hs['hanhKiemHK1'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-hanh-kiem">
                                            <?= htmlspecialchars($hs['hanhKiemHK2'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td><?= intval($hs['soViPham'] ?? 0) ?></td>
                                    <td><?= intval($hs['soKhenThuong'] ?? 0) ?></td>
                                    <td>
                                        <span class="badge badge-danh-hieu">
                                            <?= htmlspecialchars($danhHieuHienTai) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <p>Không có dữ liệu. Vui lòng chọn điều kiện lọc và nhấn "Xem báo cáo".</p>
                </div>
            <?php endif; ?>
        </div>
            </div>
        </div>
    </div>
</body>
</html>
