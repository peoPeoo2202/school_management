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
                    <h2><i class="fas fa-edit"></i> Yêu cầu sửa điểm</h2>
                    <p>Gửi yêu cầu sửa điểm cho học sinh khi phát hiện sai sót</p>
                </div>

                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <!-- Alert giống layout mẫu (giữ logic session error) -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error" style="margin-bottom:16px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Card chứa form giống layout mẫu -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Thông tin môn học và học sinh</h2>
                </div>
                <form method="POST"
                    action="dashboard.php?action=xulysuadiem"
                    enctype="multipart/form-data"
                    id="formSuaDiem">

                    <!-- SECTION 1 -->
                    <div class="form-section">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="hocKy">Học kỳ <span style="color:#e74c3c">*</span></label>
                                <select name="hocKy" id="hocKy" class="form-control" required>
                                    <option value="">-- Chọn học kỳ --</option>
                                    <option value="1">Học kỳ 1</option>
                                    <option value="2">Học kỳ 2</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="namHoc">Năm học <span style="color:#e74c3c">*</span></label>
                                <select name="namHoc" id="namHoc" class="form-control" required>
                                    <option value="">-- Chọn năm học --</option>
                                    <option value="2023-2024">2023-2024</option>
                                    <option value="2024-2025" selected>2024-2025</option>
                                    <option value="2025-2026">2025-2026</option>
                                </select>
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="maMonHoc">Môn học <span style="color:#e74c3c">*</span></label>
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

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="maHS">Học sinh <span style="color:#e74c3c">*</span></label>
                                <select name="maHS" id="maHS" class="form-control" required disabled>
                                    <option value="">-- Chọn môn học và học kỳ trước --</option>
                                </select>
                                <input type="hidden" name="maBangDiem" id="maBangDiem">
                                <div class="help-text">Vui lòng chọn môn học, học kỳ và năm học trước</div>
                            </div>
                        </div>
                    </div>
            </div>
            <!-- SECTION 2 -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Thông tin điểm cần sửa</h2>
                </div>
                <div class="form-section">
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="loaiDiem">Loại đánh giá <span style="color:#e74c3c">*</span></label>
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

                        <div class="form-group">
                            <label for="diemHienTai">Điểm hiện tại <span style="color:#e74c3c">*</span></label>
                            <input type="number" name="diemHienTai" id="diemHienTai"
                                class="form-control" step="0.01" min="0" max="10" required readonly>
                            <div class="help-text">Điểm này sẽ tự động hiển thị khi chọn học sinh và loại điểm</div>
                        </div>

                        <div class="form-group">
                            <label for="diemDeNghiSua">Điểm đề nghị sửa <span style="color:#e74c3c">*</span></label>
                            <input type="number" name="diemDeNghiSua" id="diemDeNghiSua"
                                class="form-control" step="0.01" min="0" max="10" required>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <!-- SECTION 3 -->
                 <div class="card-header">
                    <h2 class="card-title"><i class="fa-solid fa-circle-info"></i>Lý do và minh chứng</h2>
                </div>
                <div class="form-section">

                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="lyDoSuaDiem">Lý do sửa điểm <span style="color:#e74c3c">*</span></label>
                            <textarea name="lyDoSuaDiem" id="lyDoSuaDiem" class="form-control"
                                required placeholder="Vui lòng mô tả chi tiết lý do cần sửa điểm..."></textarea>
                            <div class="help-text">Cung cấp thông tin chi tiết về lý do cần sửa điểm</div>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="minhChung">File minh chứng</label>

                            <!-- Upload theo layout mẫu gửi đề thi -->
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

                            <div id="fileSelected" class="file-selected">
                                <i class="fas fa-check-circle"></i>
                                <span id="fileName"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions theo layout mẫu -->
                
                </form>

                <div id="loading" class="loading" style="display:none;">
                    <div class="spinner"></div>
                    <p>Đang xử lý...</p>
                </div>
            </div>
        <div>
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
    </div>

    <script>
        // Hiển thị tên file giống layout mẫu gửi đề thi
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

        // ==== GIỮ NGUYÊN JS load danh sách học sinh / điểm như cũ ====
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
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `maMonHoc=${maMonHoc}&hocKy=${hocKy}&namHoc=${namHoc}`
                    })
                    .then(r => r.json())
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
                    .catch(() => {
                        selectHS.innerHTML = '<option value="">Lỗi khi tải dữ liệu</option>';
                    });
            }
        }

        function loadDiemHocSinh() {
            const selectHS = document.getElementById('maHS');
            const selectedOption = selectHS.options[selectHS.selectedIndex];
            const maBangDiem = selectedOption.dataset.maBangDiem;

            document.getElementById('maBangDiem').value = maBangDiem;

            if (maBangDiem) {
                fetch('../../controller/cTeacherRequest.php?ajax=layDiemHocSinh', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `maBangDiem=${maBangDiem}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            window.bangDiem = data.data;
                            updateDiemHienTai();
                        }
                    });
            }
        }

        function updateDiemHienTai() {
            const loaiDiem = document.getElementById('loaiDiem').value;
            const diemHienTaiInput = document.getElementById('diemHienTai');
            if (window.bangDiem && loaiDiem) {
                diemHienTaiInput.value = window.bangDiem[loaiDiem] || '0';
            }
        }

        document.getElementById('maMonHoc').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('tenMonHoc').value = selectedOption.textContent;
            loadDanhSachHocSinh();
        });
        document.getElementById('hocKy').addEventListener('change', loadDanhSachHocSinh);
        document.getElementById('namHoc').addEventListener('change', loadDanhSachHocSinh);
        document.getElementById('maHS').addEventListener('change', loadDiemHocSinh);
        document.getElementById('loaiDiem').addEventListener('change', updateDiemHienTai);

        document.getElementById('formSuaDiem').addEventListener('submit', function(e) {
            if (!confirm('Bạn có chắc chắn muốn gửi yêu cầu sửa điểm này?')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>

</html>