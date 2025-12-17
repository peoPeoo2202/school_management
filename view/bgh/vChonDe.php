<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn đề thi - Ban Giám Hiệu</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container mt-4">
    <!-- Main Content -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-file-alt"></i>
                        Chọn đề thi
                    </h4>
                </div>
                
                <div class="card-body">
                    <!-- Thông báo -->
                    <?php if (!empty($data['message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Thành công!</strong> <?php echo htmlspecialchars($data['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($data['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Lỗi!</strong> <?php echo htmlspecialchars($data['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Thanh công cụ -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-end align-items-center mb-3">
                                <div>
                                    <button type="button" class="btn btn-secondary" onclick="goBack()">
                                        <i class="fas fa-arrow-left"></i> Quay lại
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <!-- Bộ lọc -->
                    <div class="row mb-4" id="boLoc">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="selectKhoi" class="form-label">Chọn khối: <span class="text-danger">*</span></label>
                                <select class="form-select" id="selectKhoi">
                                    <option value="">-- Chọn khối --</option>
                                    <?php if (!empty($data['danhSachKhoi'])): ?>
                                        <?php foreach ($data['danhSachKhoi'] as $khoi): ?>
                                            <option value="<?php echo $khoi['maKhoi']; ?>">
                                                Khối <?php echo htmlspecialchars($khoi['khoiLop']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="selectKyThi" class="form-label">Chọn kỳ thi: <span class="text-danger">*</span></label>
                                <select class="form-select" id="selectKyThi">
                                    <option value="">-- Chọn kỳ thi --</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="searchDeThi" class="form-label">Tìm kiếm đề thi:</label>
                                <input type="text" class="form-control" id="searchDeThi" placeholder="Nhập tên đề thi hoặc môn học...">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Nút tìm kiếm -->
                    <div class="row mb-4">
                        <div class="col-12 text-center">
                            <button type="button" class="btn btn-primary btn-lg" id="btnTimKiem">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                        </div>
                    </div>
                    
                    <!-- Thông tin kỳ thi -->
                    <div id="thongTinKyThi" class="card mb-4" style="display: none;">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Thông tin kỳ thi</h6>
                        </div>
                        <div class="card-body" id="noiDungThongTinKyThi">
                        </div>
                    </div>
                    
                    <!-- Danh sách đề thi -->
                    <div id="danhSachDeThi" style="display: none;">
                        
                        <!-- Card tóm tắt đề thi đã chọn -->
                        <div id="cardDeThiDaChon" class="card mb-4" style="display: none;">
                            <div class="card-header bg-success text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-clipboard-list"></i>
                                        Đề thi đã chọn cho kỳ thi
                                        <span class="badge bg-light text-success ms-2" id="badgeCountDeChon">0</span>
                                    </h6>
                                    <button type="button" class="btn btn-light btn-sm" id="btnToggleDanhSachChon">
                                        <i class="fas fa-eye"></i> Xem chi tiết
                                    </button>
                                </div>
                            </div>
                            <div class="card-body" id="bodyDeThiDaChon">
                                <div id="danhSachDeChon" class="row">
                                    <!-- Danh sách đề đã chọn sẽ được load vào đây -->
                                </div>
                                <div id="chuaCoDeChon" class="text-center text-muted py-3" style="display: none;">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p class="mb-0">Chưa có đề thi nào được chọn</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab để chuyển giữa đề đã duyệt và đề đã chọn -->
                        <ul class="nav nav-tabs mb-3" id="deThiTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="daDuyet-tab" data-bs-toggle="tab" data-bs-target="#daDuyet" type="button" role="tab">
                                    <i class="fas fa-check"></i> Đề đã duyệt
                                    <span class="badge bg-primary ms-2" id="countDaDuyet">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="daChon-tab" data-bs-toggle="tab" data-bs-target="#daChon" type="button" role="tab">
                                    <i class="fas fa-star"></i> Đề đã chọn
                                    <span class="badge bg-success ms-2" id="countDaChon">0</span>
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="deThiTabContent">
                            <!-- Tab đề đã duyệt -->
                            <div class="tab-pane fade show active" id="daDuyet" role="tabpanel">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-list"></i>
                                            Danh sách đề thi đã duyệt
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="tableDaDuyet">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>STT</th>
                                                        <th>Tên đề thi</th>
                                                        <th>Môn học</th>
                                                        <th>Khối</th>
                                                        <th>Loại kỳ thi</th>
                                                        <th>Người ra đề</th>
                                                        <th>File đề thi</th>
                                                        <th>Thao tác</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodyDaDuyet">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab đề đã chọn -->
                            <div class="tab-pane fade" id="daChon" role="tabpanel">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-star"></i>
                                            Danh sách đề thi đã chọn
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="tableDaChon">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>STT</th>
                                                        <th>Tên đề thi</th>
                                                        <th>Môn học</th>
                                                        <th>Khối</th>
                                                        <th>Loại kỳ thi</th>
                                                        <th>Người ra đề</th>
                                                        <th>Ngày chọn</th>
                                                        <th>File đề thi</th>
                                                        <th>Thao tác</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodyDaChon">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Thông báo thành công/lỗi sẽ được hiển thị bằng alert JavaScript -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    let currentKyThi = null;
    let allDeThi = [];
    let daDuyetDeThi = [];
    let daChonDeThi = [];
    
    // Function để tạo badge cho loại kỳ thi với màu sắc phân biệt
    window.getLoaiKyThiBadge = function(loaiKyThi) {
        if (!loaiKyThi) return '<span class="badge bg-secondary">N/A</span>';
        
        switch(loaiKyThi) {
            case 'Giữa kỳ':
                return '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Giữa kỳ</span>';
            case 'Cuối kỳ':
                return '<span class="badge bg-danger"><i class="fas fa-flag-checkered"></i> Cuối kỳ</span>';
            case 'Thi lại':
                return '<span class="badge bg-dark"><i class="fas fa-redo"></i> Thi lại</span>';
            default:
                return '<span class="badge bg-secondary">' + loaiKyThi + '</span>';
        }
    };
    
    // Load danh sách kỳ thi ban đầu
    loadDanhSachKyThi();
    
    // Xử lý khi chọn khối
    $('#selectKhoi').change(function() {
        loadDanhSachKyThi();
    });
    
    // Xử lý khi chọn kỳ thi
    $('#selectKyThi').change(function() {
        const maKyThi = $(this).val();
        if (!maKyThi) {
            $('#danhSachDeThi').hide();
            $('#thongTinKyThi').hide();
            $('#btnDeThiDaChon').hide();
        }
    });
    
    // Xử lý tìm kiếm đề thi
    $('#searchDeThi').on('input', function() {
        const searchText = $(this).val().toLowerCase();
        filterDeThi(searchText);
    });
    
    // Xử lý nút tìm kiếm
    $('#btnTimKiem').click(function() {
        const maKhoi = $('#selectKhoi').val();
        const maKyThi = $('#selectKyThi').val();
        
        // Kiểm tra bắt buộc chọn khối và kỳ thi
        if (!maKhoi) {
            alert('Vui lòng chọn khối!');
            $('#selectKhoi').focus();
            return;
        }
        
        if (!maKyThi) {
            alert('Vui lòng chọn kỳ thi!');
            $('#selectKyThi').focus();
            return;
        }
        
        // Load dữ liệu đề thi
        loadDanhSachDeThi(maKyThi);
    });
    
    // Load danh sách kỳ thi
    function loadDanhSachKyThi() {
        const maKhoi = $('#selectKhoi').val();
        
        $.get('?action=danh-sach-ky-thi', { maKhoi: maKhoi })
            .done(function(response) {
                if (response.success) {
                    let options = '<option value="">-- Chọn kỳ thi --</option>';
                    response.data.forEach(function(kyThi) {
                        options += `<option value="${kyThi.maKyThi}">
                                      ${kyThi.tenKyThi} (${kyThi.loaiKyThi} - HK${kyThi.hocKy} ${kyThi.namHoc})
                                    </option>`;
                    });
                    $('#selectKyThi').html(options);
                    $('#danhSachDeThi').hide();
                    $('#thongTinKyThi').hide();
                    $('#btnDeThiDaChon').hide();
                } else {
                    alert('Lỗi: ' + response.message);
                }
            })
            .fail(function() {
                alert('Có lỗi xảy ra khi tải danh sách kỳ thi!');
            });
    }
    
    // Load danh sách đề thi
    function loadDanhSachDeThi(maKyThi) {
        currentKyThi = maKyThi;
        
        // Load đề thi đã duyệt
        $.get('?action=danh-sach-de-thi', { maKyThi: maKyThi })
            .done(function(response) {
                if (response.success) {
                    // Hiển thị thông tin kỳ thi
                    displayThongTinKyThi(response.kyThi);
                    
                    // Phân loại đề thi
                    allDeThi = response.data;
                    daDuyetDeThi = allDeThi.filter(item => item.daChon == 0);
                    daChonDeThi = allDeThi.filter(item => item.daChon == 1);
                    
                    // Hiển thị danh sách đề thi
                    displayDanhSachDeThi();
                    
                    $('#danhSachDeThi').show();
                    $('#thongTinKyThi').show();
                    $('#btnDeThiDaChon').show();
                } else {
                    alert('Lỗi: ' + response.message);
                }
            })
            .fail(function() {
                alert('Có lỗi xảy ra khi tải danh sách đề thi!');
            });
    }
    
    // Hiển thị thông tin kỳ thi
    function displayThongTinKyThi(kyThi) {
        const html = `
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Tên kỳ thi:</strong> ${kyThi.tenKyThi}</p>
                    <p><strong>Loại kỳ thi:</strong> ${kyThi.loaiKyThi}</p>
                    <p><strong>Khối:</strong> ${kyThi.khoiLop}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Học kỳ:</strong> ${kyThi.hocKy}</p>
                    <p><strong>Năm học:</strong> ${kyThi.namHoc}</p>
                    <p><strong>Thời gian:</strong> ${kyThi.ngayBatDau} đến ${kyThi.ngayKetThuc}</p>
                </div>
            </div>
        `;
        $('#noiDungThongTinKyThi').html(html);
    }
    
    // Hiển thị danh sách đề thi đã duyệt
    function displayDaDuyetDeThi(danhSachDeThi = null) {
        const deThi = danhSachDeThi || daDuyetDeThi;
        let html = '';
        
        deThi.forEach(function(item, index) {
            const loaiKyThiBadge = getLoaiKyThiBadge(item.loaiKyThi);
            const fileInfo = item.tenFile ? 
                `<a href="?action=tai-file-de-thi&maDeThi=${item.maDeThi}" class="text-decoration-none" title="Tải xuống ${item.tenFile}">
                    <i class="fas fa-file-alt text-primary"></i> ${item.tenFile}
                 </a>` : 
                `<span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Chưa có file</span>`;
            
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${item.tenDeThi}</strong></td>
                    <td><span class="badge bg-secondary">${item.tenMonHoc}</span></td>
                    <td><span class="badge bg-info">Khối ${item.khoiLop || 'N/A'}</span></td>
                    <td>${loaiKyThiBadge}</td>
                    <td>${item.tenTTBM || 'N/A'}</td>
                    <td class="file-column">${fileInfo}</td>
                    <td>
                        ${item.tenFile ? 
                            `<button class="btn btn-sm btn-primary me-1" onclick="taiXuongFileDeThi(${item.maDeThi}, '${item.tenDeThi}')" title="Tải xuống">
                                <i class="fas fa-download"></i> Tải
                             </button>` : 
                            `<span class="text-muted small">Không có file</span>`
                        }
                        <button class="btn btn-sm btn-success" onclick="xacNhanChonDe(${item.maDeThi}, '${item.tenDeThi}')">
                            <i class="fas fa-check"></i> Chọn đề
                        </button>
                    </td>
                </tr>
            `;
        });
        
        $('#tbodyDaDuyet').html(html);
        $('#countDaDuyet').text(deThi.length);
    }
    
    // Hiển thị danh sách đề thi đã chọn  
    function displayDaChonDeThi(danhSachDeThi = null) {
        const deThi = danhSachDeThi || daChonDeThi;
        let html = '';
        
        deThi.forEach(function(item, index) {
            const ngayChon = item.ngayChon ? new Date(item.ngayChon).toLocaleString('vi-VN') : 'N/A';
            const loaiKyThiBadge = getLoaiKyThiBadge(item.loaiKyThi);
            const fileInfo = item.tenFile ? 
                `<a href="?action=tai-file-de-thi&maDeThi=${item.maDeThi}" class="text-decoration-none" title="Tải xuống ${item.tenFile}">
                    <i class="fas fa-file-alt text-primary"></i> ${item.tenFile}
                 </a>` : 
                `<span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Chưa có file</span>`;
            
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${item.tenDeThi}</strong></td>
                    <td><span class="badge bg-secondary">${item.tenMonHoc}</span></td>
                    <td><span class="badge bg-info">Khối ${item.khoiLop || 'N/A'}</span></td>
                    <td>${loaiKyThiBadge}</td>
                    <td>${item.tenTTBM || 'N/A'}</td>
                    <td>${ngayChon}</td>
                    <td class="file-column">${fileInfo}</td>
                    <td>
                        ${item.tenFile ? 
                            `<button class="btn btn-sm btn-primary me-1" onclick="taiXuongFileDeThi(${item.maDeThi}, '${item.tenDeThi}')" title="Tải xuống">
                                <i class="fas fa-download"></i> Tải
                             </button>` : 
                            `<span class="text-muted small">Không có file</span>`
                        }
                        <button class="btn btn-sm btn-danger" onclick="xacNhanBoChonDe(${item.maDeThi}, '${item.tenDeThi}')">
                            <i class="fas fa-times"></i> Bỏ chọn
                        </button>
                    </td>
                </tr>
            `;
        });
        
        $('#tbodyDaChon').html(html);
        $('#countDaChon').text(deThi.length);
    }
    
    // Lọc đề thi theo từ khóa
    function filterDeThi(searchText) {
        if (!searchText) {
            displayDaDuyetDeThi();
            displayDaChonDeThi();
            return;
        }
        
        const filteredDaDuyet = daDuyetDeThi.filter(item => 
            item.tenDeThi.toLowerCase().includes(searchText) || 
            item.tenMonHoc.toLowerCase().includes(searchText) ||
            (item.tenTTBM && item.tenTTBM.toLowerCase().includes(searchText)) ||
            (item.loaiKyThi && item.loaiKyThi.toLowerCase().includes(searchText)) ||
            (item.khoiLop && item.khoiLop.toString().includes(searchText))
        );
        
        const filteredDaChon = daChonDeThi.filter(item => 
            item.tenDeThi.toLowerCase().includes(searchText) || 
            item.tenMonHoc.toLowerCase().includes(searchText) ||
            (item.tenTTBM && item.tenTTBM.toLowerCase().includes(searchText)) ||
            (item.loaiKyThi && item.loaiKyThi.toLowerCase().includes(searchText)) ||
            (item.khoiLop && item.khoiLop.toString().includes(searchText))
        );
        
        displayDaDuyetDeThi(filteredDaDuyet);
        displayDaChonDeThi(filteredDaChon);
    }
    
    // Hiển thị tất cả đề thi
    function displayDanhSachDeThi() {
        displayDaDuyetDeThi();
        displayDaChonDeThi();
    }
    
    // Xác nhận chọn đề
    window.xacNhanChonDe = function(maDeThi, tenDeThi) {
        if (confirm(`Bạn có chắc chắn muốn chọn đề thi "${tenDeThi}" không?`)) {
            chonDeThi(maDeThi);
        }
    }
    
    // Xác nhận bỏ chọn đề
    window.xacNhanBoChonDe = function(maDeThi, tenDeThi) {
        if (confirm(`Bạn có chắc chắn muốn bỏ chọn đề thi "${tenDeThi}" không?`)) {
            boChonDeThi(maDeThi);
        }
    }
    
    // Chọn đề thi
    function chonDeThi(maDeThi) {
        $.post('?action=chon-de-thi', {
            maKyThi: currentKyThi,
            maDeThi: maDeThi
        })
        .done(function() {
            // Reload danh sách đề thi
            loadDanhSachDeThi(currentKyThi);
            alert('Chọn đề thi thành công!');
        })
        .fail(function() {
            alert('Có lỗi xảy ra khi chọn đề thi!');
        });
    }
    
    // Bỏ chọn đề thi
    function boChonDeThi(maDeThi) {
        $.post('?action=bo-chon-de-thi', {
            maKyThi: currentKyThi,
            maDeThi: maDeThi
        })
        .done(function() {
            // Reload danh sách đề thi
            loadDanhSachDeThi(currentKyThi);
            alert('Bỏ chọn đề thi thành công!');
        })
        .fail(function() {
            alert('Có lỗi xảy ra khi bỏ chọn đề thi!');
        });
    }
    
    // Quay lại
    window.goBack = function() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = '../index.php';
        }
    }
    
    // Xem đề thi đã chọn (chuyển tab)
    window.xemDeThiDaChon = function() {
        $('#daChon-tab').tab('show');
    }

    // Tải xuống file đề thi
    window.taiXuongFileDeThi = function(maDeThi, tenDeThi) {
        // Kiểm tra xem có file để tải không
        const deThi = allDeThi.find(item => item.maDeThi == maDeThi);
        
        if (!deThi || !deThi.tenFile) {
            alert('Đề thi này chưa có file đính kèm!');
            return;
        }
        
        // Tạo URL để tải file
        const downloadUrl = `?action=tai-file-de-thi&maDeThi=${maDeThi}`;
        
        // Tạo link tạm để download
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Thêm thông tin file vào bảng đề thi
    function addFileInfoToTable() {
        // Thêm cột File vào header của bảng
        const headers = document.querySelectorAll('#tableDaDuyet thead tr, #tableDaChon thead tr');
        headers.forEach(header => {
            if (!header.querySelector('.file-column')) {
                const fileHeader = document.createElement('th');
                fileHeader.className = 'file-column';
                fileHeader.textContent = 'File đề thi';
                // Thêm trước cột thao tác (cột cuối)
                const lastTh = header.querySelector('th:last-child');
                lastTh.parentNode.insertBefore(fileHeader, lastTh);
            }
        });
    }
});
</script>

</body>
</html>