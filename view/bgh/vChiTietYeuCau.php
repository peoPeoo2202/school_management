<?php
if (!isset($_SESSION)) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết yêu cầu - Hệ thống quản lý trường học</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .top-header {
            background: white;
            padding: 15px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-header h1 {
            color: #1e3c72;
            font-size: 24px;
            margin: 0;
        }

        .top-header .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .detail-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
        }

        .card-header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }

        .card-header .meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .card-header .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .card-body {
            padding: 30px;
        }

        .info-section {
            margin-bottom: 30px;
        }

        .info-section h3 {
            margin: 0 0 15px 0;
            color: #1f2937;
            font-size: 18px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
        }

        .info-value {
            color: #1f2937;
            font-size: 16px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-nghiphep {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-suadiem {
            background: #fce7f3;
            color: #9f1239;
        }

        .file-link {
            color: #667eea;
            text-decoration: none;
        }

        .file-link:hover {
            text-decoration: underline;
        }

        /* Action buttons */
        .action-section {
            margin-top: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-approve {
            background: #10b981;
            color: white;
        }

        .btn-approve:hover {
            background: #059669;
        }

        .btn-reject {
            background: #ef4444;
            color: white;
        }

        .btn-reject:hover {
            background: #dc2626;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
            color: #1f2937;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #374151;
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .close {
            color: #9ca3af;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #4b5563;
        }

        /* Lịch sử xử lý */
        .history-section {
            margin-top: 30px;
        }

        .history-item {
            padding: 15px;
            background: #f9fafb;
            border-left: 3px solid #667eea;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .history-item .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .history-item .history-time {
            color: #6b7280;
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
    </style>
</head>
<body>
    <div class="detail-container">
        <a href="?action=danhsach" class="back-link">← Quay lại danh sách</a>

        <div class="detail-card">
            <!-- Header -->
            <div class="card-header">
                <h1>Yêu cầu #<?php echo $yeuCau['maYeuCau']; ?></h1>
                <div class="meta">
                    <div class="meta-item">
                        <?php if ($yeuCau['loaiYeuCau'] == 'NghiPhep'): ?>
                            <span class="badge badge-nghiphep">📝 Nghỉ phép</span>
                        <?php else: ?>
                            <span class="badge badge-suadiem">✏️ Sửa điểm</span>
                        <?php endif; ?>
                    </div>
                    <div class="meta-item">
                        <?php if ($yeuCau['trangThai'] == 'Choxuly'): ?>
                            <span class="badge badge-pending">⏳ Chờ xử lý</span>
                        <?php elseif ($yeuCau['trangThai'] == 'Dachapnhan'): ?>
                            <span class="badge badge-approved">✅ Đã chấp nhận</span>
                        <?php else: ?>
                            <span class="badge badge-rejected">❌ Đã từ chối</span>
                        <?php endif; ?>
                    </div>
                    <div class="meta-item">
                        🕒 <?php echo date('d/m/Y H:i', strtotime($yeuCau['ngayGui'])); ?>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="card-body">
                <!-- Thông tin giáo viên -->
                <div class="info-section">
                    <h3>👤 Thông tin giáo viên</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Họ và tên</span>
                            <span class="info-value"><?php echo htmlspecialchars($yeuCau['tenGV']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($yeuCau['emailGV']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Số điện thoại</span>
                            <span class="info-value"><?php echo htmlspecialchars($yeuCau['sdtGV']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tổ bộ môn</span>
                            <span class="info-value"><?php echo htmlspecialchars($yeuCau['toBoMon']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Chi tiết yêu cầu -->
                <div class="info-section">
                    <h3>📋 Chi tiết yêu cầu</h3>
                    <div class="info-item" style="margin-bottom: 15px;">
                        <span class="info-label">Mô tả</span>
                        <span class="info-value"><?php echo nl2br(htmlspecialchars($yeuCau['moTa'])); ?></span>
                    </div>

                    <?php if ($yeuCau['loaiYeuCau'] == 'NghiPhep'): ?>
                        <!-- Chi tiết nghỉ phép -->
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Ngày bắt đầu nghỉ</span>
                                <span class="info-value"><?php echo date('d/m/Y', strtotime($yeuCau['ngayBatDauNghi'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Ngày kết thúc nghỉ</span>
                                <span class="info-value"><?php echo date('d/m/Y', strtotime($yeuCau['ngayKetThucNghi'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Số ngày nghỉ</span>
                                <span class="info-value">
                                    <?php 
                                        $start = new DateTime($yeuCau['ngayBatDauNghi']);
                                        $end = new DateTime($yeuCau['ngayKetThucNghi']);
                                        $interval = $start->diff($end);
                                        echo ($interval->days + 1) . ' ngày';
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-item" style="margin-top: 15px;">
                            <span class="info-label">Lý do</span>
                            <span class="info-value"><?php echo nl2br(htmlspecialchars($yeuCau['lyDoNghiPhep'])); ?></span>
                        </div>
                    <?php else: ?>
                        <!-- Chi tiết sửa điểm -->
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Học sinh</span>
                                <span class="info-value"><?php echo htmlspecialchars($yeuCau['tenHS']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Môn học</span>
                                <span class="info-value"><?php echo htmlspecialchars($yeuCau['tenMonHoc']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Học kỳ - Năm học</span>
                                <span class="info-value">HK<?php echo $yeuCau['hocKy']; ?> - <?php echo $yeuCau['namHoc']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Loại điểm</span>
                                <span class="info-value"><?php echo htmlspecialchars($yeuCau['loaiDiem']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Điểm hiện tại</span>
                                <span class="info-value" style="color: #ef4444; font-weight: bold;"><?php echo $yeuCau['diemHienTai']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Điểm đề nghị sửa</span>
                                <span class="info-value" style="color: #10b981; font-weight: bold;"><?php echo $yeuCau['diemDeNghiSua']; ?></span>
                            </div>
                        </div>
                        <div class="info-item" style="margin-top: 15px;">
                            <span class="info-label">Lý do sửa điểm</span>
                            <span class="info-value"><?php echo nl2br(htmlspecialchars($yeuCau['lyDoSuaDiem'])); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($yeuCau['minhChung']): ?>
                        <div class="info-item" style="margin-top: 15px;">
                            <span class="info-label">Minh chứng</span>
                            <span class="info-value">
                                <a href="../controller/download.php?maYeuCau=<?php echo $yeuCau['maYeuCau']; ?>" 
                                   class="file-link">
                                    📎 <?php echo htmlspecialchars($yeuCau['minhChung']); ?>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Thông tin xử lý -->
                <?php if ($yeuCau['trangThai'] != 'Choxuly'): ?>
                    <div class="info-section">
                        <h3>✅ Thông tin xử lý</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Người xử lý</span>
                                <span class="info-value"><?php echo htmlspecialchars($yeuCau['nguoiXuLy']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Ngày xử lý</span>
                                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($yeuCau['ngayXuLy'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Lịch sử xử lý -->
                <?php if (isset($lichSuXuLy) && count($lichSuXuLy) > 0): ?>
                    <div class="history-section">
                        <h3>📜 Lịch sử xử lý</h3>
                        <?php foreach ($lichSuXuLy as $ls): ?>
                            <div class="history-item">
                                <div class="history-header">
                                    <div>
                                        <strong><?php echo htmlspecialchars($ls['nguoiXuLy']); ?></strong>
                                        đã chuyển trạng thái từ 
                                        <strong><?php echo $ls['trangThaiCu']; ?></strong> 
                                        sang 
                                        <strong><?php echo $ls['trangThaiMoi']; ?></strong>
                                    </div>
                                    <div class="history-time">
                                        <?php echo date('d/m/Y H:i', strtotime($ls['ngayXuLy'])); ?>
                                    </div>
                                </div>
                                <?php if ($ls['lyDoTuChoi']): ?>
                                    <div style="color: #ef4444; margin-top: 10px;">
                                        <strong>Lý do từ chối:</strong> <?php echo nl2br(htmlspecialchars($ls['lyDoTuChoi'])); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($ls['ghiChu']): ?>
                                    <div style="color: #6b7280; margin-top: 5px;">
                                        <strong>Ghi chú:</strong> <?php echo nl2br(htmlspecialchars($ls['ghiChu'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Action buttons (chỉ hiện khi đang chờ xử lý) -->
                <?php if ($yeuCau['trangThai'] == 'Choxuly'): ?>
                    <div class="action-section">
                        <h3>⚡ Xử lý yêu cầu</h3>
                        <div class="action-buttons">
                            <button onclick="showApproveModal()" class="btn btn-approve">
                                ✅ Chấp nhận
                            </button>
                            <button onclick="showRejectModal()" class="btn btn-reject">
                                ❌ Từ chối
                            </button>
                            <a href="?action=danhsach" class="btn btn-secondary">
                                Quay lại
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Chấp nhận -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeApproveModal()">&times;</span>
                <h2>✅ Chấp nhận yêu cầu</h2>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn chấp nhận yêu cầu này không?</p>
                <form id="approveForm">
                    <div class="form-group">
                        <label>Ghi chú (tùy chọn)</label>
                        <textarea name="ghiChu" placeholder="Nhập ghi chú nếu cần..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick="submitApprove()" class="btn btn-approve">Xác nhận</button>
                <button onclick="closeApproveModal()" class="btn btn-secondary">Hủy</button>
            </div>
        </div>
    </div>

    <!-- Modal Từ chối -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeRejectModal()">&times;</span>
                <h2>❌ Từ chối yêu cầu</h2>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <div class="form-group">
                        <label>Lý do từ chối <span style="color: red;">*</span></label>
                        <textarea name="lyDoTuChoi" required placeholder="Vui lòng nhập lý do từ chối..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Ghi chú (tùy chọn)</label>
                        <textarea name="ghiChu" placeholder="Nhập ghi chú nếu cần..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick="submitReject()" class="btn btn-reject">Xác nhận</button>
                <button onclick="closeRejectModal()" class="btn btn-secondary">Hủy</button>
            </div>
        </div>
    </div>

    <script>
        const maYeuCau = <?php echo $yeuCau['maYeuCau']; ?>;

        function showApproveModal() {
            document.getElementById('approveModal').style.display = 'block';
        }

        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
        }

        function showRejectModal() {
            document.getElementById('rejectModal').style.display = 'block';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }

        function submitApprove() {
            const form = document.getElementById('approveForm');
            const formData = new FormData(form);
            formData.append('maYeuCau', maYeuCau);

            fetch('?action=chapnhan', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = '?action=danhsach';
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                alert('Có lỗi xảy ra: ' + error);
            });
        }

        function submitReject() {
            const form = document.getElementById('rejectForm');
            const formData = new FormData(form);
            
            if (!formData.get('lyDoTuChoi').trim()) {
                alert('Vui lòng nhập lý do từ chối');
                return;
            }

            formData.append('maYeuCau', maYeuCau);

            fetch('?action=tuchoi', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = '?action=danhsach';
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                alert('Có lỗi xảy ra: ' + error);
            });
        }

        // Đóng modal khi click bên ngoài
        window.onclick = function(event) {
            const approveModal = document.getElementById('approveModal');
            const rejectModal = document.getElementById('rejectModal');
            
            if (event.target == approveModal) {
                closeApproveModal();
            }
            if (event.target == rejectModal) {
                closeRejectModal();
            }
        }
    </script>
</body>
</html>

<?php require_once(__DIR__ . '/../layouts/footer.php'); ?>
