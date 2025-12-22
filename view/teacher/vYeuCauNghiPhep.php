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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #7f8c8d;
            font-size: 14px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #7f8c8d;
            font-size: 14px;
        }

        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #9b59b6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group label .required {
            color: #e74c3c;
            margin-left: 3px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #9b59b6;
            box-shadow: 0 0 0 3px rgba(155, 89, 182, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 15px;
            background: #ecf0f1;
            color: #2c3e50;
            border: 2px dashed #bdc3c7;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-label:hover {
            background: #e0e6e8;
            border-color: #95a5a6;
        }

        .file-input-label i {
            margin-right: 8px;
        }

        .file-name {
            margin-top: 10px;
            padding: 8px 12px;
            background: #e8f5e9;
            border-radius: 4px;
            color: #27ae60;
            font-size: 13px;
            display: none;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #e74c3c;
        }

        .alert-info {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #9b59b6;
            color: white;
        }

        .btn-primary:hover {
            background: #8e44ad;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(155, 89, 182, 0.3);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .help-text {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .info-box {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-box h4 {
            color: #f57c00;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .info-box ul {
            margin-left: 20px;
            color: #5d4037;
            font-size: 13px;
        }

        .info-box ul li {
            margin-bottom: 5px;
        }

        .date-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 13px;
            color: #495057;
        }

        .date-info i {
            color: #9b59b6;
        }
    </style>
</head>
<body>
    <?php include_once(__DIR__ . '/../layouts/teacher-layout-header.php'); ?>

    <div class="container">
        <div class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
            <span>/</span>
            <a href="index.php?action=danhsachyeucau">Danh sách yêu cầu</a>
            <span>/</span>
            <span>Yêu cầu nghỉ phép</span>
        </div>

        <div class="page-header">
            <h1><i class="fas fa-calendar-times"></i> Yêu cầu nghỉ phép</h1>
            <p>Gửi yêu cầu xin nghỉ phép đến Ban giám hiệu</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h4><i class="fas fa-info-circle"></i> Lưu ý khi gửi yêu cầu nghỉ phép:</h4>
            <ul>
                <li>Yêu cầu nên được gửi trước thời gian nghỉ ít nhất 3 ngày (trừ trường hợp khẩn cấp)</li>
                <li>Cung cấp đầy đủ thông tin và lý do rõ ràng</li>
                <li>Đính kèm minh chứng nếu có (giấy khám bệnh, giấy mời...)</li>
                <li>Ban giám hiệu sẽ xem xét và phản hồi trong vòng 24-48 giờ</li>
            </ul>
        </div>

        <div class="form-container">
            <form method="POST" action="dashboard.php?action=xulynghiphep" enctype="multipart/form-data" id="formNghiPhep">
                
                <!-- Mô tả -->
                <div class="form-section">
                    <h3 class="form-section-title">Mô tả chung</h3>
                    <p style="color: #555; margin-bottom: 15px; line-height: 1.6;">
                        Yêu cầu nghỉ phép là quy trình cho phép giáo viên đăng ký nghỉ phép với Ban giám hiệu.
                        Giáo viên cần cung cấp đầy đủ thông tin về thời gian nghỉ và lý do rõ ràng.
                        Yêu cầu sẽ được Ban giám hiệu xem xét và phản hồi trong thời gian sớm nhất.
                    </p>
                </div>

                <!-- Thông tin thời gian nghỉ -->
                <div class="form-section">
                    <h3 class="form-section-title">Thông tin thời gian nghỉ</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ngayBatDauNghi">Ngày bắt đầu nghỉ <span class="required">*</span></label>
                            <input type="date" name="ngayBatDauNghi" id="ngayBatDauNghi" class="form-control" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="ngayKetThucNghi">Ngày kết thúc nghỉ <span class="required">*</span></label>
                            <input type="date" name="ngayKetThucNghi" id="ngayKetThucNghi" class="form-control" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="date-info" id="dateInfo" style="display: none;">
                        <i class="fas fa-clock"></i>
                        <span id="dateInfoText"></span>
                    </div>
                </div>

                <!-- Lý do nghỉ -->
                <div class="form-section">
                    <h3 class="form-section-title">Lý do nghỉ phép</h3>
                    
                    <div class="form-group">
                        <label for="lyDo">Lý do nghỉ phép <span class="required">*</span></label>
                        <textarea name="lyDo" id="lyDo" class="form-control" 
                                  required placeholder="Vui lòng mô tả chi tiết lý do nghỉ phép (ví dụ: đi khám bệnh, việc gia đình, tham gia hội thảo...)"></textarea>
                        <div class="help-text">Cung cấp thông tin chi tiết và rõ ràng về lý do nghỉ phép</div>
                    </div>
                </div>

                <!-- File minh chứng -->
                <div class="form-section">
                    <h3 class="form-section-title">Minh chứng (nếu có)</h3>
                    
                    <div class="form-group">
                        <label for="minhChung">File minh chứng</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="minhChung" id="minhChung" accept=".jpg,.jpeg,.png,.pdf">
                            <label for="minhChung" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Chọn file minh chứng (ảnh hoặc PDF)</span>
                            </label>
                        </div>
                        <div class="file-name" id="fileName"></div>
                        <div class="help-text">
                            Tải lên giấy khám bệnh, giấy mời hội thảo, giấy báo tang... (định dạng: JPG, PNG, PDF - Tối đa 5MB)
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Gợi ý:</strong> Việc đính kèm minh chứng sẽ giúp yêu cầu của bạn được xử lý nhanh chóng và thuận lợi hơn.
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Gửi yêu cầu
                    </button>
                    <a href="dashboard.php?action=danhsachyeucau" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Hủy bỏ
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Xử lý hiển thị tên file
        document.getElementById('minhChung').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const fileNameDiv = document.getElementById('fileName');
            if (fileName) {
                fileNameDiv.textContent = '📎 ' + fileName;
                fileNameDiv.style.display = 'block';
            } else {
                fileNameDiv.style.display = 'none';
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

        // Event listeners cho ngày
        document.getElementById('ngayBatDauNghi').addEventListener('change', function() {
            document.getElementById('ngayKetThucNghi').min = this.value;
            calculateDays();
        });

        document.getElementById('ngayKetThucNghi').addEventListener('change', calculateDays);

        // Xác nhận trước khi gửi
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
            // Reset formChanged để không hiện cảnh báo khi chuyển trang
            formChanged = false;
        });

        // Cảnh báo khi rời trang
        let formChanged = false;
        document.querySelectorAll('input, textarea').forEach(element => {
            element.addEventListener('change', () => { formChanged = true; });
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>

    <?php include_once(__DIR__ . '/../layouts/teacher-layout-footer.php'); ?>
</body>
</html>
