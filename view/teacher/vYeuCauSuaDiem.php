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
    <title>Yêu cầu sửa điểm - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            max-width: 1000px;
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
            border-bottom: 2px solid #3498db;
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
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        textarea.form-control {
            min-height: 100px;
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

        .alert-success {
            background: #efe;
            color: #3c763d;
            border-left: 4px solid #27ae60;
        }

        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #2196f3;
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
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
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



        select.form-control {
            cursor: pointer;
        }

        select.form-control option {
            padding: 10px;
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
            <span>Yêu cầu sửa điểm</span>
        </div>

        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Yêu cầu sửa điểm</h1>
            <p>Gửi yêu cầu sửa điểm cho học sinh khi phát hiện sai sót</p>
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

        <div class="form-container">
            <form method="POST" action="dashboard.php?action=xulysuadiem" enctype="multipart/form-data" id="formSuaDiem">
                
                <!-- Thông tin môn học và học sinh -->
                <div class="form-section">
                    <h3 class="form-section-title">Thông tin môn học và học sinh</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="hocKy">Học kỳ <span class="required">*</span></label>
                            <select name="hocKy" id="hocKy" class="form-control" required>
                                <option value="">-- Chọn học kỳ --</option>
                                <option value="1">Học kỳ 1</option>
                                <option value="2">Học kỳ 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="namHoc">Năm học <span class="required">*</span></label>
                            <select name="namHoc" id="namHoc" class="form-control" required>
                                <option value="">-- Chọn năm học --</option>
                                <option value="2023-2024">2023-2024</option>
                                <option value="2024-2025" selected>2024-2025</option>
                                <option value="2025-2026">2025-2026</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="maMonHoc">Môn học <span class="required">*</span></label>
                        <select name="maMonHoc" id="maMonHoc" class="form-control" required>
                            <option value="">-- Chọn môn học --</option>
                            <?php foreach ($danhSachMonHoc as $monHoc): ?>
                                <option value="<?php echo $monHoc['maMonHoc']; ?>">
                                    <?php echo htmlspecialchars($monHoc['tenMonHoc']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="tenMonHoc" id="tenMonHoc">
                    </div>

                    <div class="form-group">
                        <label for="maHS">Học sinh <span class="required">*</span></label>
                        <select name="maHS" id="maHS" class="form-control" required disabled>
                            <option value="">-- Chọn môn học và học kỳ trước --</option>
                        </select>
                        <input type="hidden" name="maBangDiem" id="maBangDiem">
                        <div class="help-text">Vui lòng chọn môn học, học kỳ và năm học trước</div>
                    </div>
                </div>

                <!-- Thông tin điểm -->
                <div class="form-section">
                    <h3 class="form-section-title">Thông tin điểm cần sửa</h3>
                    
                    <div class="form-group">
                        <label for="loaiDiem">Loại đánh giá <span class="required">*</span></label>
                        <select name="loaiDiem" id="loaiDiem" class="form-control" required>
                            <option value="">-- Chọn loại điểm --</option>
                            <option value="diemTX1">Điểm thường xuyên 1</option>
                            <option value="diemTX2">Điểm thường xuyên 2</option>
                            <option value="diemTX3">Điểm thường xuyên 3</option>
                            <option value="diemTX4">Điểm thường xuyên 4</option>
                            <option value="diemGiuaKy">Điểm giữa kỳ</option>
                            <option value="diemCuoiKy">Điểm cuối kỳ</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="diemHienTai">Điểm hiện tại <span class="required">*</span></label>
                            <input type="number" name="diemHienTai" id="diemHienTai" class="form-control" 
                                   step="0.01" min="0" max="10" required readonly>
                            <div class="help-text">Điểm này sẽ tự động hiển thị khi chọn học sinh và loại điểm</div>
                        </div>

                        <div class="form-group">
                            <label for="diemDeNghiSua">Điểm đề nghị sửa <span class="required">*</span></label>
                            <input type="number" name="diemDeNghiSua" id="diemDeNghiSua" class="form-control" 
                                   step="0.01" min="0" max="10" required>
                        </div>
                    </div>
                </div>

                <!-- Lý do và minh chứng -->
                <div class="form-section">
                    <h3 class="form-section-title">Lý do và minh chứng</h3>
                    
                    <div class="form-group">
                        <label for="lyDoSuaDiem">Lý do sửa điểm <span class="required">*</span></label>
                        <textarea name="lyDoSuaDiem" id="lyDoSuaDiem" class="form-control" 
                                  required placeholder="Vui lòng mô tả chi tiết lý do cần sửa điểm..."></textarea>
                        <div class="help-text">Cung cấp thông tin chi tiết về lý do cần sửa điểm</div>
                    </div>

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
                            Tải lên ảnh chụp bài kiểm tra, biên bản... (định dạng: JPG, PNG, PDF - Tối đa 5MB)
                        </div>
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

        // Lấy danh sách học sinh khi chọn môn học và học kỳ
        function loadDanhSachHocSinh() {
            const maMonHoc = document.getElementById('maMonHoc').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            const selectHS = document.getElementById('maHS');

            if (maMonHoc && hocKy && namHoc) {
                selectHS.disabled = true;
                selectHS.innerHTML = '<option value="">Đang tải...</option>';

                fetch('../../controller/cTeacherRequest.php?ajax=layDanhSachHocSinh', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `maMonHoc=${maMonHoc}&hocKy=${hocKy}&namHoc=${namHoc}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        selectHS.innerHTML = '<option value="">-- Chọn học sinh --</option>';
                        data.data.forEach(hs => {
                            const option = document.createElement('option');
                            option.value = hs.maHS;
                            option.textContent = `${hs.hoTen} (${hs.tenLop})`;
                            option.dataset.maBangDiem = hs.maBangDiem || '';
                            selectHS.appendChild(option);
                        });
                        selectHS.disabled = false;
                    } else {
                        selectHS.innerHTML = '<option value="">Không có dữ liệu</option>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    selectHS.innerHTML = '<option value="">Lỗi khi tải dữ liệu</option>';
                });
            }
        }

        // Lấy điểm của học sinh
        function loadDiemHocSinh() {
            const selectHS = document.getElementById('maHS');
            const selectedOption = selectHS.options[selectHS.selectedIndex];
            const maBangDiem = selectedOption.dataset.maBangDiem;
            
            document.getElementById('maBangDiem').value = maBangDiem;
            
            if (maBangDiem) {
                fetch('../../controller/cTeacherRequest.php?ajax=layDiemHocSinh', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `maBangDiem=${maBangDiem}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.bangDiem = data.data;
                        updateDiemHienTai();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }

        // Cập nhật điểm hiện tại khi chọn loại điểm
        function updateDiemHienTai() {
            const loaiDiem = document.getElementById('loaiDiem').value;
            const diemHienTaiInput = document.getElementById('diemHienTai');
            
            if (window.bangDiem && loaiDiem) {
                const diem = window.bangDiem[loaiDiem];
                diemHienTaiInput.value = diem || '0';
            }
        }

        // Lưu tên môn học
        document.getElementById('maMonHoc').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('tenMonHoc').value = selectedOption.textContent;
            loadDanhSachHocSinh();
        });

        // Event listeners
        document.getElementById('hocKy').addEventListener('change', loadDanhSachHocSinh);
        document.getElementById('namHoc').addEventListener('change', loadDanhSachHocSinh);
        document.getElementById('maHS').addEventListener('change', loadDiemHocSinh);
        document.getElementById('loaiDiem').addEventListener('change', updateDiemHienTai);

        // Xác nhận trước khi gửi
        document.getElementById('formSuaDiem').addEventListener('submit', function(e) {
            if (!confirm('Bạn có chắc chắn muốn gửi yêu cầu sửa điểm này?')) {
                e.preventDefault();
                return false;
            }
            // Reset formChanged để không hiện cảnh báo khi chuyển trang
            formChanged = false;
        });

        // Cảnh báo khi rời trang
        let formChanged = false;
        document.querySelectorAll('input, select, textarea').forEach(element => {
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
