<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once(__DIR__ . '/../../config.php');

// Kiểm tra đăng nhập và quyền
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true || $_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: " . url('public/index.php'));
    exit();
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu nghỉ phép - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include('../layouts/navigate/navigateTeacher.php'); ?>

        <div class="content-area">

            <!-- Header theo layout mẫu -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-calendar-times"></i> Yêu cầu nghỉ phép</h2>
                    <p>Gửi yêu cầu xin nghỉ phép đến Ban giám hiệu</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <!-- Alert error -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error" style="margin-bottom:16px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Card: Lưu ý -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-info-circle"></i> Lưu ý khi gửi yêu cầu nghỉ phép</h2>
                </div>
                <ul style="margin:0; padding-left:24px; color:#555; line-height:1.8;">
                    <li>Yêu cầu nên được gửi trước thời gian nghỉ ít nhất 3 ngày (trừ trường hợp khẩn cấp)</li>
                    <li>Cung cấp đầy đủ thông tin và lý do rõ ràng</li>
                    <li>Đính kèm minh chứng nếu có (giấy khám bệnh, giấy mời...)</li>
                    <li>Ban giám hiệu sẽ xem xét và phản hồi trong vòng 24–48 giờ</li>
                </ul>
            </div>

            <!-- FORM (1 form bao hết 3 card) -->
            <form class="form-nghi-phep" method="POST"
                action="dashboard.php?action=xulynghiphep"
                enctype="multipart/form-data"
                id="formNghiPhep">

                <!-- Card 1: Mô tả -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Mô tả chung</h2>
                    </div>
                    <div class="form-section">
                        <p style="color:#555; margin:0; line-height:1.6;">
                            Yêu cầu nghỉ phép là quy trình cho phép giáo viên đăng ký nghỉ phép với Ban giám hiệu.
                            Giáo viên cần cung cấp đầy đủ thông tin về thời gian nghỉ và lý do rõ ràng.
                            Yêu cầu sẽ được Ban giám hiệu xem xét và phản hồi trong thời gian sớm nhất.
                        </p>
                    </div>
                </div>

                <!-- Card 2: Thông tin thời gian nghỉ -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Thông tin thời gian nghỉ</h2>
                    </div>
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="ngayBatDauNghi">Ngày bắt đầu nghỉ <span style="color:#e74c3c">*</span></label>
                                <input type="date"
                                    name="ngayBatDauNghi"
                                    id="ngayBatDauNghi"
                                    class="form-control"
                                    min="<?php echo date('Y-m-d'); ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="ngayKetThucNghi">Ngày kết thúc nghỉ <span style="color:#e74c3c">*</span></label>
                                <input type="date"
                                    name="ngayKetThucNghi"
                                    id="ngayKetThucNghi"
                                    class="form-control"
                                    min="<?php echo date('Y-m-d'); ?>"
                                    required>
                            </div>

                            <!-- date-info full row -->
                            <div class="form-group" style="grid-column:1 / -1;">
                                <div class="date-info" id="dateInfo">
                                    <i class="fas fa-clock"></i>
                                    <span id="dateInfoText"></span>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Lý do + minh chứng -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Lý do và minh chứng</h2>
                    </div>
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-group" style="grid-column:1 / -1;">
                                <label for="lyDo">Lý do nghỉ phép <span style="color:#e74c3c">*</span></label>
                                <textarea name="lyDo"
                                    id="lyDo"
                                    class="form-control"
                                    required
                                    placeholder="Vui lòng mô tả chi tiết lý do nghỉ phép (ví dụ: đi khám bệnh, việc gia đình, tham gia hội thảo...)"></textarea>
                                <div class="help-text">Cung cấp thông tin chi tiết và rõ ràng về lý do nghỉ phép</div>
                            </div>

                            <div class="form-group" style="grid-column:1 / -1;">
                                <label for="minhChung">File minh chứng</label>

                                <!-- Upload theo layout mẫu -->
                                <div class="file-upload-wrapper">
                                    <input type="file" name="minhChung" id="minhChung" accept=".jpg,.jpeg,.png,.pdf">
                                    <div style="font-size:42px;color:#5081BE;margin-bottom:8px;">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div style="color:#666;font-size:14px;">
                                        <strong>Chọn file hoặc kéo thả vào đây</strong>
                                    </div>
                                    <div style="margin-top:6px;font-size:12px;color:#999;">
                                        Định dạng: JPG, PNG, PDF - Tối đa 5MB
                                    </div>
                                </div>

                                <div id="fileSelected" class="file-selected" style="display:none;">
                                    <i class="fas fa-check-circle"></i>
                                    <span id="fileName"></span>
                                </div>
<br>
                                <div class="help-text">
                                    <i class="fas fa-lightbulb"></i>
                                    <strong>Gợi ý:</strong> Việc đính kèm minh chứng sẽ giúp yêu cầu của bạn được xử lý nhanh hơn.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions theo layout mẫu -->

                </div>

            </form>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Gửi yêu cầu
                </button>
                <a href="dashboard.php?action=danhsachyeucau" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
            </div>
        </div>
    </div>

    <script>
        // Hiển thị tên file theo kiểu layout mẫu
        document.getElementById('minhChung').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || '';
            const fileSelected = document.getElementById('fileSelected');
            const fileNameSpan = document.getElementById('fileName');

            if (fileName) {
                fileNameSpan.textContent = fileName;
                fileSelected.style.display = 'flex';
            } else {
                fileSelected.style.display = 'none';
            }
        });

        // Tính số ngày nghỉ
        function calculateDays() {
            const startDate = document.getElementById('ngayBatDauNghi').value;
            const endDate = document.getElementById('ngayKetThucNghi').value;
            const dateInfo = document.getElementById('dateInfo');
            const dateInfoText = document.getElementById('dateInfoText');

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

                if (end >= start) {
                    dateInfoText.textContent = `Tổng số ngày nghỉ: ${diffDays} ngày (từ ${formatDate(start)} đến ${formatDate(end)})`;
                    dateInfo.style.display = 'flex';
                    dateInfo.style.background = '#f0f7ff';
                    dateInfo.style.borderLeft = '4px solid #5081BE';
                } else {
                    dateInfoText.textContent = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu';
                    dateInfo.style.display = 'flex';
                    dateInfo.style.background = '#ffebee';
                    dateInfo.style.borderLeft = '4px solid #f44336';
                }
            } else {
                dateInfo.style.display = 'none';
            }
        }

        function formatDate(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }

        document.getElementById('ngayBatDauNghi').addEventListener('change', function() {
            document.getElementById('ngayKetThucNghi').min = this.value;
            calculateDays();
        });

        document.getElementById('ngayKetThucNghi').addEventListener('change', calculateDays);

        document.getElementById('formNghiPhep').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('ngayBatDauNghi').value);
            const endDate = new Date(document.getElementById('ngayKetThucNghi').value);

            if (endDate < startDate) {
                e.preventDefault();
                alert('Ngày kết thúc phải sau hoặc bằng ngày bắt đầu!');
                return false;
            }

            if (!confirm('Bạn có chắc chắn muốn gửi yêu cầu nghỉ phép này?')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>

</html>