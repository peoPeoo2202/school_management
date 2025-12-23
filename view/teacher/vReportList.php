<?php
// Lấy thông tin từ session (đã được kiểm tra ở controller)
$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo & Thống kê - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../view/teacher/style.css">
    <style>
        .file-link {
            color: #667eea;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .file-link:hover {
            color: #5a6fd8;
            text-decoration: underline;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
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

    <body>
        <div class="main-wrapper">
            <?php include(__DIR__ . '/../layouts/navigate/navigateTeacher.php'); ?>

            <div class="content-area">

                <!-- Header theo layout mẫu -->
                <div class="header-section">
                    <div class="header-left">
                        <h2><i class="fas fa-chart-bar"></i> Báo cáo & Thống kê</h2>
                        <p>Xem báo cáo, thống kê và nộp báo cáo cho Ban giám hiệu</p>
                    </div>

                    <div class="header-right">
                        <p class="welcome-text">Xin chào,</p>
                        <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="margin-bottom:16px;">
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
                    <div class="alert alert-error" style="margin-bottom:16px;">
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

                <!-- Card: Danh mục báo cáo/thống kê -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-layer-group"></i> Danh mục báo cáo & thống kê
                        </h2>
                    </div>

                    <div class="form-section">
                        <div class="reports-grid">
                            <div class="report-card" onclick="location.href='cReport.php?action=academic'">
                                <div class="report-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="report-title">Báo cáo kết quả học tập</div>
                                <div class="report-description">
                                    Xem kết quả học tập của học sinh theo môn học, lớp và học kỳ
                                </div>
                                <a href="cReport.php?action=academic" class="btn btn-primary">Xem báo cáo</a>
                            </div>

                            <div class="report-card" onclick="location.href='cReport.php?action=attendance'">
                                <div class="report-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="report-title">Báo cáo chuyên cần</div>
                                <div class="report-description">
                                    Thống kê tình hình chuyên cần và hạnh kiểm của học sinh
                                </div>
                                <a href="cReport.php?action=attendance" class="btn btn-primary">Xem báo cáo</a>
                            </div>

                            <div class="report-card" onclick="location.href='cReport.php?action=teaching'">
                                <div class="report-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="report-title">Báo cáo giảng dạy</div>
                                <div class="report-description">
                                    Tổng hợp lịch giảng dạy và tình hình bài tập của giáo viên
                                </div>
                                <a href="cReport.php?action=teaching" class="btn btn-primary">Xem báo cáo</a>
                            </div>

                            <div class="report-card" onclick="location.href='cReport.php?action=grade_stats'">
                                <div class="report-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="report-title">Thống kê điểm môn học</div>
                                <div class="report-description">
                                    Thống kê điểm số theo từng môn học
                                </div>
                                <a href="cReport.php?action=grade_stats" class="btn btn-primary">Xem thống kê</a>
                            </div>

                            <div class="report-card" onclick="location.href='cReport.php?action=submit'">
                                <div class="report-icon">
                                    <i class="fas fa-upload"></i>
                                </div>
                                <div class="report-title">Nộp báo cáo</div>
                                <div class="report-description">
                                    Tải lên và nộp báo cáo cho ban giám hiệu
                                </div>
                                <a href="cReport.php?action=submit" class="btn btn-primary">Nộp báo cáo</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Báo cáo đã lưu -->
                <?php if (!empty($danhSachBaoCaoDaLuu)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-archive"></i> Báo cáo đã gửi
                            </h2>
                        </div>

                        <div class="form-section">
                            <table class="common-table">
                                <thead>
                                    <tr>
                                        <th class="small-cell">STT</th>
                                        <th>Tên báo cáo</th>
                                        <th >Loại</th>
                                        <th>Mô tả</th>
                                        <th class="normal-cell">Ngày nộp</th>
                                        <th >Tải file</th>
                                    </tr>
                                </thead>

                                <tbody class="common-table-body">
                                    <?php foreach ($danhSachBaoCaoDaLuu as $index => $baoCao): ?>
                                        <?php
                                        // Text loại báo cáo
                                        $loaiText = '';
                                        switch ($baoCao['loaiBaoCao']) {
                                            case 'hoc-tap':
                                                $loaiText = 'Kết quả học tập';
                                                break;
                                            case 'chuyen-can':
                                                $loaiText = 'Chuyên cần';
                                                break;
                                            case 'giang-day':
                                                $loaiText = 'Giảng dạy';
                                                break;
                                            case 'thong-ke-diem':
                                                $loaiText = 'Thống kê điểm';
                                                break;
                                            case 'thong-ke-hoc-sinh':
                                                $loaiText = 'Thống kê học sinh';
                                                break;
                                            case 'tong-hop':
                                                $loaiText = 'Tổng hợp';
                                                break;
                                            case 'danh-gia':
                                                $loaiText = 'Đánh giá';
                                                break;
                                            default:
                                                $loaiText = $baoCao['loaiBaoCao'];
                                        }


                                        $moTa = $baoCao['moTa'] ?? '';
                                        ?>
                                        <tr class="table-normal-text">
                                            <!-- STT -->
                                            <td class="small-cell">
                                                <p><?php echo $index + 1; ?></p>
                                            </td>

                                            <!-- Tên báo cáo -->
                                            <td>
                                                <div>
                                                    <span class="
                                                    table-strong-text">
                                                        <?php echo htmlspecialchars($baoCao['tenBaoCao']); ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Loại -->
                                            <td >
                                                <span class="table-strong-text">
                                                    <?php echo htmlspecialchars($loaiText); ?>
                                                </span>
                                            </td>

                                            <!-- Mô tả -->
                                            <td>
                                                <?php echo $moTa ? htmlspecialchars($moTa) : '<span class="empty-info">Không có mô tả</span>'; ?>
                                            </td>

                                            

                                            <!-- Ngày nộp -->
                                            <td class="nowrap">
                                                <?php echo date('d/m/Y H:i', strtotime($baoCao['ngayNop'])); ?>
                                            </td>

                                            <!-- Thao tác -->
                                            <td class="action-cell">
                                                <div class="action-buttons">
                                                    <a href="download.php?id=<?php echo (int)$baoCao['maBaoCao']; ?>"
                                                        class="btn-view"
                                                        title="Tải xuống file">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <script>
            setTimeout(function() {
                document.querySelectorAll('.alert').forEach(function(alert) {
                    alert.style.display = 'none';
                });
            }, 5000);
        </script>
    </body>


</html>