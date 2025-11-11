<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo & Thống kê - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0
        }

        .main-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .content-area {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
            overflow-x: hidden;
            height: 100vh;
            box-sizing: border-box;
        }

        .header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 32px;
            font-size: 14px;
        }

        .header h1 {
            color: #5081BE;
        }

        .header h1 i {
            color: #5081BE;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-name {
            color: #666;
            font-weight: 500;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #545b62;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .report-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        .report-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #5081BE, #2d5a8c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
        }

        .report-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .report-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .report-button {
            background: #5081BE;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .report-button:hover {
            background: #2d5a8c;
        }

        .submit-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .submit-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .submit-button {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: background 0.3s;
        }

        .submit-button:hover {
            background: #218838;
        }

        .saved-reports {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .saved-reports h3 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .reports-table th,
        .reports-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .reports-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .reports-table tr:hover {
            background: #f8f9fa;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .content-area {
                margin-left: 0;
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .reports-grid {
                grid-template-columns: 1fr;
            }

            .reports-table {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <div class="header">
                <h1>
                    <i class="fas fa-chart-bar"></i>
                    Báo cáo & Thống kê
                </h1>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    switch ($_GET['success']) {
                        case 'upload_success':
                            echo '<i class="fas fa-check-circle"></i> Nộp báo cáo thành công!';
                            break;
                        default:
                            echo '<i class="fas fa-check-circle"></i> Thao tác thành công!';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php
                    switch ($_GET['error']) {
                        case 'upload_failed':
                            echo '<i class="fas fa-exclamation-circle"></i> Lỗi upload file!';
                            break;
                        case 'save_failed':
                            echo '<i class="fas fa-exclamation-circle"></i> Lỗi lưu báo cáo!';
                            break;
                        case 'no_file':
                            echo '<i class="fas fa-exclamation-circle"></i> Vui lòng chọn file báo cáo!';
                            break;
                        default:
                            echo '<i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra!';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="reports-grid">
                <div class="report-card" onclick="location.href='../../controller/cReport.php?action=academic'">
                    <div class="report-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="report-title">Báo cáo kết quả học tập</div>
                    <div class="report-description">
                        Xem kết quả học tập của học sinh theo môn học, lớp và học kỳ
                    </div>
                    <a href="../../controller/cReport.php?action=academic" class="report-button">Xem báo cáo</a>
                </div>

                <div class="report-card" onclick="location.href='../../controller/cReport.php?action=attendance'">
                    <div class="report-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="report-title">Báo cáo chuyên cần</div>
                    <div class="report-description">
                        Thống kê tình hình chuyên cần và hạnh kiểm của học sinh
                    </div>
                    <a href="../../controller/cReport.php?action=attendance" class="report-button">Xem báo cáo</a>
                </div>

                <div class="report-card" onclick="location.href='../../controller/cReport.php?action=teaching'">
                    <div class="report-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="report-title">Báo cáo giảng dạy</div>
                    <div class="report-description">
                        Tổng hợp lịch giảng dạy và tình hình bài tập của giáo viên
                    </div>
                    <a href="../../controller/cReport.php?action=teaching" class="report-button">Xem báo cáo</a>
                </div>

                <div class="report-card" onclick="location.href='../../controller/cReport.php?action=grade_stats'">
                    <div class="report-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="report-title">Thống kê điểm môn học</div>
                    <div class="report-description">
                        Phân tích thống kê điểm số theo từng môn học
                    </div>
                    <a href="../../controller/cReport.php?action=grade_stats" class="report-button">Xem thống kê</a>
                </div>

                <div class="report-card" onclick="location.href='../../controller/cReport.php?action=student_stats'">
                    <div class="report-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="report-title">Thống kê số liệu học sinh</div>
                    <div class="report-description">
                        Thống kê tổng quát về học sinh theo lớp và xếp loại
                    </div>
                    <a href="../../controller/cReport.php?action=student_stats" class="report-button">Xem thống kê</a>
                </div>

                <div class="report-card" onclick="location.href='../../controller/cReport.php?action=submit'">
                    <div class="report-icon">
                        <i class="fas fa-upload"></i>
                    </div>
                    <div class="report-title">Nộp báo cáo</div>
                    <div class="report-description">
                        Tải lên và nộp báo cáo cho ban giám hiệu
                    </div>
                    <a href="../../controller/cReport.php?action=submit" class="report-button">Nộp báo cáo</a>
                </div>
            </div>

            <?php if (!empty($danhSachBaoCaoDaLuu)): ?>
                <div class="saved-reports">
                    <h3>
                        <i class="fas fa-archive"></i>
                        Báo cáo đã lưu
                    </h3>
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Tên báo cáo</th>
                                <th>Loại</th>
                                <th>Học kỳ</th>
                                <th>Năm học</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($danhSachBaoCaoDaLuu as $baoCao): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($baoCao['tenBaoCao']); ?></td>
                                    <td>
                                        <?php
                                        switch ($baoCao['loaiBaoCao']) {
                                            case 'hoc-tap':
                                                echo 'Kết quả học tập';
                                                break;
                                            case 'chuyen-can':
                                                echo 'Chuyên cần';
                                                break;
                                            case 'giang-day':
                                                echo 'Giảng dạy';
                                                break;
                                            case 'thong-ke-diem':
                                                echo 'Thống kê điểm';
                                                break;
                                            case 'thong-ke-hoc-sinh':
                                                echo 'Thống kê học sinh';
                                                break;
                                            default:
                                                echo htmlspecialchars($baoCao['loaiBaoCao']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $baoCao['hocKy']; ?></td>
                                    <td><?php echo htmlspecialchars($baoCao['namHoc']); ?></td>
                                    <td>
                                        <?php
                                        $noiDung = json_decode($baoCao['noiDung'], true);
                                        if ($noiDung && isset($noiDung['upload_time'])) {
                                            echo date('d/m/Y H:i', strtotime($noiDung['upload_time']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>

</html>