<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

// Kiểm tra quyền giáo viên
if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

// Nếu chưa có $data, include controller để xử lý
if (!isset($data)) {
    define('INCLUDED_FROM_VIEW', true); // Đánh dấu được gọi từ view
    ob_start(); // Bắt đầu buffer để tránh controller render view
    require_once(__DIR__ . '/../../controller/cStudentClassification.php');
    ob_end_clean(); // Xóa buffer
    // Controller đã set biến $data, bây giờ view sẽ render
}

$hoTen = $_SESSION['hoTen'] ?? 'Giáo viên';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xếp loại học sinh - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modal styles */
        .criteria-section {
            margin-bottom: 24px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #5081BE;
        }

        .criteria-section h3 {
            color: #5081BE;
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .criteria-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .criteria-item {
            display: flex;
            flex-direction: column;
        }

        .criteria-item label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
            font-size: 13px;
        }

        .criteria-item input,
        .criteria-item select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .criteria-item input:focus,
        .criteria-item select:focus {
            outline: none;
            border-color: #5081BE;
            box-shadow: 0 0 0 2px rgba(80, 129, 190, 0.1);
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 13px;
            color: #1565c0;
        }

        .title-xuatsac {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #b8860b;
            border-color: #ffa500;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
        }
        
        .title-gioi {
            background: linear-gradient(135deg, #90EE90 0%, #98FB98 100%);
            color: #228B22;
            border-color: #32CD32;
            box-shadow: 0 2px 8px rgba(144, 238, 144, 0.3);
        }
        
        .title-none {
            background: #f5f5f5;
            color: #999;
            border-color: #ddd;
        }

        .select-wrapper {
            position: relative;
            display: inline-block;
        }

        .select-wrapper.changed::after {
            content: '✓';
            position: absolute;
            right: 35px;
            top: 50%;
            transform: translateY(-50%);
            color: #27ae60;
            font-weight: bold;
            font-size: 16px;
            pointer-events: none;
            animation: fadeIn 0.3s ease;
        }

        @media (max-width: 768px) {
            .criteria-grid {
                grid-template-columns: 1fr;
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
            <!-- Header -->
            <div class="header-section">
                <div class="header-left">
                    <h2><i class="fas fa-star"></i> Xếp loại học sinh</h2>
                    <p>Quản lý xếp loại học lực, hạnh kiểm và danh hiệu</p>
                </div>
                <div class="header-right">
                    <p class="welcome-text">Xin chào,</p>
                    <p class="user-name"><?php echo htmlspecialchars($hoTen); ?></p>
                </div>
            </div>

            <?php if (isset($data['error'])): ?>
                <div class="card">
                    <div class="alert alert-error">
                        <!-- <strong>⚠️ Lỗi:</strong>  -->
                        <?php echo htmlspecialchars($data['error']); ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Main Card -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-layer-group"></i> Lớp chủ nhiệm</h2>
                    </div>
                    <div class="class-info" style="background: linear-gradient(135deg, #5081BE15 0%, #4a6fa515 100%); padding: 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #5081BE;">
                        <h3 style="color: #5081BE; margin-bottom: 10px; font-size: 16px; font-weight: 600;">
                            <i class="fas fa-info-circle"></i> Thông tin lớp chủ nhiệm
                        </h3>
                        <p style="margin: 8px 0; color: #555; font-size: 14px;">
                            <strong>Lớp:</strong> <?php echo htmlspecialchars($data['classInfo']['tenLop']); ?> - Khối <?php echo htmlspecialchars($data['classInfo']['khoiLop']); ?>
                        </p>
                        <p style="margin: 8px 0; color: #555; font-size: 14px;">
                            <strong>Sĩ số:</strong> <?php echo $data['classInfo']['siSo']; ?> học sinh
                        </p>
                        <p style="margin: 8px 0; color: #555; font-size: 14px;">
                            <strong>Năm học:</strong> <?php echo htmlspecialchars($data['classInfo']['namHoc']); ?>
                        </p>
                        <input type="hidden" id="maLop" value="<?php echo $data['currentClassId']; ?>">
                        <input type="hidden" id="namHoc" value="<?php echo $data['namHoc']; ?>">
                    </div>

                    <!-- Chọn học kỳ đánh giá -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
                        <div style="margin-bottom: 0;">
                            <label for="hocKy" style="font-size: 16px; color: white; margin-bottom: 12px; display: block; font-weight: 600;">
                                <i class="fas fa-calendar-check" style="margin-right: 8px;"></i>
                                Học kỳ đánh giá xếp loại
                            </label>
                            <div style="display: flex; align-items: center; gap: 15px; background: white; padding: 12px 15px; border-radius: 8px; width: fit-content;">
                                <select name="hocKy" id="hocKy" onchange="loadData()" 
                                    style="border: 2px solid #667eea; border-radius: 6px; padding: 10px 40px 10px 15px; font-size: 15px; font-weight: 600; color: #2c3e50; cursor: pointer; background: white; outline: none; appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;12&quot; height=&quot;12&quot; viewBox=&quot;0 0 12 12&quot;><path fill=&quot;%23667eea&quot; d=&quot;M6 9L1 4h10z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 12px center;">
                                    <option value="1" <?php echo ($data['hocKy'] == 1) ? 'selected' : ''; ?>>📚 Học kỳ 1</option>
                                    <option value="2" <?php echo ($data['hocKy'] == 2) ? 'selected' : ''; ?>>📚 Học kỳ 2</option>
                                </select>
                                <span style="font-size: 13px; color: #667eea; font-style: italic;">
                                    <i class="fas fa-info-circle"></i> Áp dụng cho tất cả các tab
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Buttons -->
                    <div class="tab-buttons" style="display: flex; gap: 10px; border-top: 2px solid rgba(102, 126, 234, 0.1); padding-top: 20px; margin-top: 20px;">
                        <button class="tab-btn active" onclick="switchTab('academic')" style="padding: 12px 24px; background: white; color: #667eea; border: 2px solid #667eea; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
                            <i class="fas fa-graduation-cap"></i> Học lực
                        </button>
                        <button class="tab-btn" onclick="switchTab('conduct')" style="padding: 12px 24px; background: white; color: #667eea; border: 2px solid #ddd; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
                            <i class="fas fa-star"></i> Hạnh kiểm
                        </button>
                        <button class="tab-btn" onclick="switchTab('title')" style="padding: 12px 24px; background: white; color: #667eea; border: 2px solid #ddd; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
                            <i class="fas fa-award"></i> Danh hiệu
                        </button>
                    </div>

                    <!-- Tab: Xếp loại học lực -->
                    <div id="tab-academic" class="tab-content active" style="display: block; margin-top: 25px;">
                        <div id="academicContent"></div>
                    </div>

                    <!-- Tab: Xếp loại hạnh kiểm -->
                    <div id="tab-conduct" class="tab-content" style="display: none; margin-top: 25px;">
                        <div class="action-buttons" style="display: flex; gap: 10px; margin-bottom: 20px;">
                            <button class="btn btn-primary" onclick="openCriteriaModal()">
                                <i class="fas fa-cog"></i> Xếp loại tự động
                            </button>
                            <button class="btn btn-success" onclick="saveConductManual()">
                                <i class="fas fa-save"></i> Lưu xếp loại thủ công
                            </button>
                        </div>
                        <div id="conductContent"></div>
                    </div>

                    <!-- Tab: Xếp loại danh hiệu -->
                    <div id="tab-title" class="tab-content" style="display: none; margin-top: 25px;">
                        <div class="action-buttons" style="display: flex; gap: 10px; margin-bottom: 20px;">
                            <button class="btn btn-primary" onclick="classifyAllTitles()">
                                <i class="fas fa-magic"></i> Xếp loại danh hiệu tự động
                            </button>
                        </div>
                        <div id="titleContent"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal cấu hình tiêu chí -->
    <div id="criteriaModal" class="common-modal">
        <div class="modal-dialog" style="max-width: 900px;">
            <div class="common-modal-header">
                <h5 class="common-modal-title"><i class="fas fa-sliders-h"></i> Cấu hình tiêu chí xếp loại hạnh kiểm tự động</h5>
                <button class="btn-close-modal" onclick="closeCriteriaModal()"></button>
            </div>
            <div class="common-modal-body">
                <div class="info-box">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Hướng dẫn:</strong> Thiết lập các tiêu chí tối đa cho mỗi loại hạnh kiểm. 
                    Hệ thống sẽ tự động xếp loại học sinh dựa trên các tiêu chí này.
                </div>

                <!-- Tốt -->
                <div class="criteria-section" style="border-left-color: #27ae60;">
                    <h3><i class="fas fa-star" style="color: #27ae60;"></i> Hạnh kiểm Tốt</h3>
                    <div class="criteria-grid">
                        <div class="criteria-item">
                            <label>Nghỉ có phép (tối đa)</label>
                            <input type="number" id="tot-cophep" min="0" value="5">
                        </div>
                        <div class="criteria-item">
                            <label>Nghỉ không phép (tối đa)</label>
                            <input type="number" id="tot-khongphep" min="0" value="3">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nhẹ (tối đa)</label>
                            <input type="number" id="tot-nhe" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm TB (tối đa)</label>
                            <input type="number" id="tot-tb" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nặng (tối đa)</label>
                            <input type="number" id="tot-nang" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Học lực tối thiểu</label>
                            <select id="tot-hocluc">
                                <option value="Tốt">Tốt</option>
                                <option value="Khá" selected>Khá</option>
                                <option value="Đạt">Đạt</option>
                                <option value="">Không yêu cầu</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Khá -->
                <div class="criteria-section" style="border-left-color: #3498db;">
                    <h3><i class="fas fa-star" style="color: #3498db;"></i> Hạnh kiểm Khá</h3>
                    <div class="criteria-grid">
                        <div class="criteria-item">
                            <label>Nghỉ có phép (tối đa)</label>
                            <input type="number" id="kha-cophep" min="0" value="10">
                        </div>
                        <div class="criteria-item">
                            <label>Nghỉ không phép (tối đa)</label>
                            <input type="number" id="kha-khongphep" min="0" value="5">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nhẹ (tối đa)</label>
                            <input type="number" id="kha-nhe" min="0" value="1">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm TB (tối đa)</label>
                            <input type="number" id="kha-tb" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nặng (tối đa)</label>
                            <input type="number" id="kha-nang" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Học lực tối thiểu</label>
                            <select id="kha-hocluc">
                                <option value="Tốt">Tốt</option>
                                <option value="Khá">Khá</option>
                                <option value="Đạt" selected>Đạt</option>
                                <option value="">Không yêu cầu</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Đạt -->
                <div class="criteria-section" style="border-left-color: #f39c12;">
                    <h3><i class="fas fa-star" style="color: #f39c12;"></i> Hạnh kiểm Đạt</h3>
                    <div class="criteria-grid">
                        <div class="criteria-item">
                            <label>Nghỉ có phép (tối đa)</label>
                            <input type="number" id="dat-cophep" min="0" value="15">
                        </div>
                        <div class="criteria-item">
                            <label>Nghỉ không phép (tối đa)</label>
                            <input type="number" id="dat-khongphep" min="0" value="6">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nhẹ (tối đa)</label>
                            <input type="number" id="dat-nhe" min="0" value="3">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm TB (tối đa)</label>
                            <input type="number" id="dat-tb" min="0" value="3">
                        </div>
                        <div class="criteria-item">
                            <label>Vi phạm nặng (tối đa)</label>
                            <input type="number" id="dat-nang" min="0" value="0">
                        </div>
                        <div class="criteria-item">
                            <label>Học lực tối thiểu</label>
                            <select id="dat-hocluc">
                                <option value="Tốt">Tốt</option>
                                <option value="Khá">Khá</option>
                                <option value="Đạt" selected>Đạt</option>
                                <option value="">Không yêu cầu</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="common-modal-footer">
                <button class="btn btn-cancel" onclick="closeCriteriaModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button class="btn btn-success" onclick="saveCriteriaAndClassify()">
                    <i class="fas fa-check"></i> Lưu và áp dụng
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentTab = 'academic';
        let currentPage = 1;
        const itemsPerPage = 10;
        let totalStudents = 0;
        let allStudents = [];
        let allSubjects = []; // Lưu danh sách môn học
        let needsDataRefresh = false; // Cờ đánh dấu cần reload dữ liệu

        function switchTab(tab) {
            console.log(`[switchTab] Switching to ${tab}, needsDataRefresh: ${needsDataRefresh}`);
            
            currentTab = tab;
            currentPage = 1; // Reset về trang 1 khi chuyển tab
            
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = 'white';
                btn.style.borderColor = '#ddd';
                btn.style.color = '#667eea';
            });
            event.target.classList.add('active');
            event.target.style.background = 'white';
            event.target.style.borderColor = '#667eea';
            event.target.style.color = '#667eea';
            
            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
                content.classList.remove('active');
            });
            const activeTab = document.getElementById('tab-' + tab);
            if (activeTab) {
                activeTab.style.display = 'block';
                activeTab.classList.add('active');
            }
            
            // Ẩn/hiện dropdown học kỳ (Danh hiệu tính theo cả năm)
            const hocKyGroup = document.querySelector('.form-group:has(#hocKy)');
            if (hocKyGroup) {
                if (tab === 'title') {
                    hocKyGroup.style.display = 'none';
                } else {
                    hocKyGroup.style.display = 'flex';
                }
            }
            
            // LUÔN force reload cho tab Danh hiệu để lấy dữ liệu mới nhất
            const shouldForceReload = needsDataRefresh || (tab === 'title');
            console.log(`[switchTab] Force reload: ${shouldForceReload}`);
            
            // Load data - force reload nếu có thay đổi HOẶC đang chuyển sang tab Danh hiệu
            loadData(shouldForceReload);
            needsDataRefresh = false; // Reset cờ sau khi reload
        }

        function loadData(forceReload = false) {
            const maLop = document.getElementById('maLop').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            
            let contentDiv = 'academicContent';
            if (currentTab === 'conduct') contentDiv = 'conductContent';
            else if (currentTab === 'title') contentDiv = 'titleContent';
            
            document.getElementById(contentDiv).innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><p>Đang tải dữ liệu...</p></div>';
            
            // Thêm timestamp nếu force reload để bypass cache
            const timestamp = forceReload ? '&_t=' + new Date().getTime() : '';
            fetch(`?action=getData&type=${currentTab}&maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}${timestamp}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log(`[${currentTab}] Data received:`, data);
                    
                    if (data.error) {
                        document.getElementById(contentDiv).innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${data.error}</div>`;
                        console.error('Server error:', data.error);
                        return;
                    }
                    
                    // Kiểm tra dữ liệu trả về
                    if (!data.students || !Array.isArray(data.students)) {
                        document.getElementById(contentDiv).innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Dữ liệu không hợp lệ</div>`;
                        console.error('Invalid data structure:', data);
                        return;
                    }
                    
                    console.log(`[${currentTab}] Students count:`, data.students.length);
                    
                    // Lưu toàn bộ danh sách học sinh
                    allStudents = data.students;
                    totalStudents = allStudents.length;
                    
                    // Lưu danh sách môn học (nếu có)
                    if (data.subjects && Array.isArray(data.subjects)) {
                        allSubjects = data.subjects;
                    }
                    
                    // Hiển thị dữ liệu với phân trang
                    if (currentTab === 'academic') {
                        displayAcademic();
                    } else if (currentTab === 'conduct') {
                        displayConduct();
                    } else if (currentTab === 'title') {
                        displayTitles();
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    document.getElementById(contentDiv).innerHTML = `<div class="alert alert-error"><i class="fas fa-times-circle"></i> Lỗi khi tải dữ liệu: ${error.message}</div>`;
                });
        }

        function getPaginatedStudents() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            return allStudents.slice(startIndex, endIndex);
        }

        function createPagination() {
            const totalPages = Math.ceil(totalStudents / itemsPerPage);
            
            if (totalPages <= 1) {
                return ''; // Không hiển thị phân trang nếu chỉ có 1 trang
            }
            
            let html = '<div class="pagination">';
            
            // Nút Previous
            if (currentPage > 1) {
                html += `<a href="javascript:changePage(${currentPage - 1})"><i class="fas fa-chevron-left"></i> Trước</a>`;
            } else {
                html += `<span class="disabled"><i class="fas fa-chevron-left"></i> Trước</span>`;
            }
            
            // Hiển thị các nút trang
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                html += `<a href="javascript:changePage(1)">1</a>`;
                if (startPage > 2) {
                    html += `<span>...</span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    html += `<span class="current-page">${i}</span>`;
                } else {
                    html += `<a href="javascript:changePage(${i})">${i}</a>`;
                }
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `<span>...</span>`;
                }
                html += `<a href="javascript:changePage(${totalPages})">${totalPages}</a>`;
            }
            
            // Nút Next
            if (currentPage < totalPages) {
                html += `<a href="javascript:changePage(${currentPage + 1})">Sau <i class="fas fa-chevron-right"></i></a>`;
            } else {
                html += `<span class="disabled">Sau <i class="fas fa-chevron-right"></i></span>`;
            }
            
            html += '</div>';
            
            // Thêm pagination info
            const offset = (currentPage - 1) * itemsPerPage;
            html += `<div class="pagination-info">
                Hiển thị ${offset + 1} - ${Math.min(offset + itemsPerPage, totalStudents)} 
                trong tổng số ${totalStudents} học sinh
            </div>`;
            
            return html;
        }

        function changePage(page) {
            const totalPages = Math.ceil(totalStudents / itemsPerPage);
            if (page < 1 || page > totalPages) return;
            
            currentPage = page;
            
            // Hiển thị lại dữ liệu với trang mới
            if (currentTab === 'academic') {
                displayAcademic();
            } else if (currentTab === 'conduct') {
                displayConduct();
            } else if (currentTab === 'title') {
                displayTitles();
            }
        }

        function displayAcademic() {
            const students = getPaginatedStudents();
            
            if (allStudents.length === 0) {
                document.getElementById('academicContent').innerHTML = '<div class="alert alert-error">Chưa có dữ liệu điểm. Vui lòng nhập điểm trước khi xếp loại.</div>';
                return;
            }

            // Lấy danh sách tên môn từ allSubjects (được trả về từ server)
            let subjectNames = [];
            if (allSubjects && allSubjects.length > 0) {
                subjectNames = allSubjects.map(subject => subject.tenMonHoc || '');
            } else if (allStudents[0] && allStudents[0].grades && allStudents[0].grades.length > 0) {
                // Fallback: lấy từ học sinh đầu tiên nếu không có allSubjects
                subjectNames = allStudents[0].grades.map(grade => grade.tenMonHoc || '');
            }

            let html = `
                <div class="table-container">
                    <table class="common-table">
                        <thead>
                            <tr>
                                <th class="small-cell" rowspan="2">STT</th>
                                <th rowspan="2">Họ tên</th>
                                <th colspan="${subjectNames.length}" style="text-align: center;">Điểm trung bình các môn</th>
                                <th class="center" rowspan="2">ĐTB</th>
                                <th class="center" rowspan="2">Xếp loại</th>
                            </tr>
                            <tr>
            `;

            // Hiển thị tên môn học
            subjectNames.forEach(subjectName => {
                html += `<th style="text-align: center;">${subjectName}</th>`;
            });

            html += `
                            </tr>
                        </thead>
                        <tbody class="common-table-body">
            `;
            
            const startIndex = (currentPage - 1) * itemsPerPage;
            students.forEach((student, index) => {
                const globalIndex = startIndex + index + 1;
                html += `
                    <tr>
                        <td class="small-cell">
                            <p>${globalIndex}</p>
                        </td>
                        <td>
                            <div class="request-description">
                                <span class="request-des-title">${student.hoTen}</span>
                            </div>
                        </td>
                `;
                
                // Hiển thị điểm các môn theo thứ tự của allSubjects
                if (student.grades && student.grades.length > 0) {
                    student.grades.forEach(grade => {
                        if (grade.tbDiem === null || grade.tbDiem === undefined || grade.tbDiem === '') {
                            html += `<td style="color: #999; text-align: center;">-</td>`;
                        } else {
                            html += `<td style="text-align: center;">${parseFloat(grade.tbDiem).toFixed(1)}</td>`;
                        }
                    });
                } else {
                    // Nếu học sinh không có grades, hiển thị tất cả môn là "-"
                    for (let i = 0; i < subjectNames.length; i++) {
                        html += `<td style="color: #999; text-align: center;">-</td>`;
                    }
                }
                
                // Hiển thị điểm TB và xếp loại
                // Backend chỉ trả về diemTB_HS khi học sinh có đủ điểm tất cả các môn
                let avgDisplay = '<span style="color: #999;">-</span>';
                let rankingDisplay = '<span style="color: #999;">-</span>';
                
                if (student.diemTB_HS !== null && student.diemTB_HS !== undefined) {
                    avgDisplay = `<strong>${parseFloat(student.diemTB_HS).toFixed(2)}</strong>`;
                    
                    if (student.ranking && student.ranking.ranking && student.ranking.ranking !== 'Chưa xếp loại' && student.ranking.ranking !== null) {
                        rankingDisplay = formatRanking(student.ranking.ranking);
                    }
                }
                
                html += `
                        <td class="center">${avgDisplay}</td>
                        <td class="center">${rankingDisplay}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            // Thêm phân trang
            html += createPagination();
            
            document.getElementById('academicContent').innerHTML = html;
        }

        // Lưu trữ thay đổi hạnh kiểm
        let conductChanges = {};

        function displayConduct() {
            const students = getPaginatedStudents();
            
            if (allStudents.length === 0) {
                document.getElementById('conductContent').innerHTML = '<div class="alert alert-error">Chưa có dữ liệu học sinh.</div>';
                return;
            }

            // Reset changes
            conductChanges = {};

            let html = `
                <div class="table-container">
                    <table class="common-table">
                        <thead>
                            <tr>
                                <th class="small-cell">STT</th>
                                <th>Họ tên</th>
                                <th colspan="2" style="text-align: center;">Số buổi nghỉ</th>
                                <th colspan="3" style="text-align: center;">Số lần vi phạm</th>
                                <th class="center">Học lực</th>
                                <th class="center">Xếp loại HK</th>
                                <th class="center">Xếp loại thủ công</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <th style="text-align: center;">Có phép</th>
                                <th style="text-align: center;">Không phép</th>
                                <th style="text-align: center;">Nhẹ</th>
                                <th style="text-align: center;">TB</th>
                                <th style="text-align: center;">Nặng</th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="common-table-body">
            `;
            
            const startIndex = (currentPage - 1) * itemsPerPage;
            students.forEach((student, index) => {
                const globalIndex = startIndex + index + 1;
                
                // Lấy giá trị xếp loại: Ưu tiên DB, nếu không có thì lấy từ tính toán tự động
                // CHỈ HIỂN THỊ XẾP LOẠI TỰ ĐỘNG NẾU HỌC SINH ĐÃ CÓ HỌC LỰC
                let currentRanking = student.hanhKiem;
                
                if (!currentRanking && student.loaiHocLuc && student.conductRanking && student.conductRanking.ranking) {
                    currentRanking = student.conductRanking.ranking;
                }
                
                if (!currentRanking) {
                    currentRanking = null;
                }
                
                const normalizedRanking = normalizeRankingForSelect(currentRanking);
                
                html += `
                    <tr>
                        <td class="small-cell">
                            <p>${globalIndex}</p>
                        </td>
                        <td>
                            <div class="request-description">
                                <span class="request-des-title">${student.hoTen}</span>
                            </div>
                        </td>
                        <td class="center">${student.soBuoiNghiCoPhep}</td>
                        <td class="center">${student.soBuoiNghiKhongPhep}</td>
                        <td class="center">${student.soLanViPhamNhe}</td>
                        <td class="center">${student.soLanViPhamTB}</td>
                        <td class="center">${student.soLanViPhamNang}</td>
                        <td class="center">${student.loaiHocLuc ? formatRanking(student.loaiHocLuc) : '<span style="color: #999;">-</span>'}</td>
                        <td class="center">${formatRanking(currentRanking)}</td>
                        <td class="center">
                            <div class="select-wrapper" id="wrapper-${student.maHS}">
                                <select class="conduct-select" 
                                        data-mahs="${student.maHS}" 
                                        data-original="${currentRanking || ''}"
                                        onchange="markConductChanged(this)">
                                    <option value="">-- Chọn --</option>
                                    <option value="Tốt" ${normalizedRanking === 'Tốt' ? 'selected' : ''}>Tốt</option>
                                    <option value="Khá" ${normalizedRanking === 'Khá' ? 'selected' : ''}>Khá</option>
                                    <option value="Đạt" ${normalizedRanking === 'Đạt' ? 'selected' : ''}>Đạt</option>
                                    <option value="Chưa đạt" ${normalizedRanking === 'Chưa đạt' ? 'selected' : ''}>Chưa đạt</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            // Thêm phân trang
            html += createPagination();
            
            document.getElementById('conductContent').innerHTML = html;
        }

        function normalizeRankingForSelect(ranking) {
            if (!ranking) return '';
            
            const normalized = ranking.toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/đ/g, "d")
                .replace(/\s+/g, "");
            
            const mapping = {
                'tot': 'Tốt',
                'kha': 'Khá',
                'dat': 'Đạt',
                'chuadat': 'Chưa đạt'
            };
            
            return mapping[normalized] || ranking;
        }

        function normalizeRanking(ranking) {
            if (!ranking) return '';
            
            return ranking.toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/đ/g, "d")
                .replace(/\s+/g, "");
        }

        function markConductChanged(selectElement) {
            const maHS = selectElement.dataset.mahs;
            const loaiHK = selectElement.value;
            const originalValue = selectElement.dataset.original;
            
            // Chuẩn hóa để so sánh
            const normalizedOriginal = normalizeRanking(originalValue);
            const normalizedNew = normalizeRanking(loaiHK);
            
            // Chỉ đánh dấu thay đổi nếu giá trị khác với giá trị gốc
            if (normalizedOriginal !== normalizedNew) {
                // Đánh dấu đã thay đổi cho select
                selectElement.classList.add('changed');
                
                // Đánh dấu đã thay đổi cho wrapper
                const wrapper = document.getElementById('wrapper-' + maHS);
                if (wrapper) {
                    wrapper.classList.add('changed');
                }
                
                // Lưu vào object thay đổi
                conductChanges[maHS] = loaiHK;
            } else {
                // Nếu chọn lại giá trị gốc thì bỏ đánh dấu
                selectElement.classList.remove('changed');
                
                const wrapper = document.getElementById('wrapper-' + maHS);
                if (wrapper) {
                    wrapper.classList.remove('changed');
                }
                
                // Xóa khỏi object thay đổi
                delete conductChanges[maHS];
            }
        }

        function saveConductManual() {
            // Kiểm tra có thay đổi nào không
            if (Object.keys(conductChanges).length === 0) {
                const alertDiv = document.getElementById('alertMessage');
                alertDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-info-circle"></i> Chưa có thay đổi nào để lưu. Vui lòng chọn xếp loại hạnh kiểm cho học sinh.</div>';
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 3000);
                return;
            }

            if (!confirm(`Bạn có chắc chắn muốn lưu xếp loại hạnh kiểm cho ${Object.keys(conductChanges).length} học sinh?`)) {
                return;
            }
            
            const maLop = document.getElementById('maLop').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            
            // Chuyển đổi object thành array
            const conductData = Object.keys(conductChanges).map(maHS => ({
                maHS: maHS,
                loaiHK: conductChanges[maHS]
            }));
            
            // Hiển thị loading
            const alertDiv = document.getElementById('alertMessage');
            alertDiv.innerHTML = '<div class="alert" style="background: #e3f2fd; border: 1px solid #90caf9; color: #1565c0;"><i class="fas fa-spinner fa-spin"></i> Đang lưu dữ liệu...</div>';
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=saveConductManual&maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}&conductData=${encodeURIComponent(JSON.stringify(conductData))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alertDiv.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i> ${data.message}</div>`;
                    
                    // Reset changes
                    conductChanges = {};
                    
                    // Clear cache dữ liệu cũ để buộc reload
                    allStudents = [];
                    totalStudents = 0;
                    
                    // Đánh dấu cần refresh dữ liệu cho các tab khác
                    needsDataRefresh = true;
                    
                    // Reload data của tab hiện tại với force reload
                    setTimeout(() => {
                        loadData(true); // Force reload với timestamp
                    }, 1000);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
                }
                
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 5000);
            })
            .catch(error => {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Lỗi khi lưu dữ liệu. Vui lòng thử lại.</div>';
                
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 5000);
            });
        }

        function formatRanking(ranking) {
            if (!ranking || ranking === null) {
                return '<span style="color: #999;">-</span>';
            }
            
            // Chuẩn hóa tên xếp loại (bỏ dấu, viết thường)
            const normalizedRanking = ranking.toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/đ/g, "d")
                .replace(/\s+/g, "");
            
            const classes = {
                'tot': 'ranking-tot',
                'tốt': 'ranking-tot',
                'kha': 'ranking-kha',
                'khá': 'ranking-kha',
                'dat': 'ranking-dat',
                'đạt': 'ranking-dat',
                'trungbinh': 'ranking-dat',
                'tb': 'ranking-dat',
                'chuadat': 'ranking-chuadat',
                'yeu': 'ranking-chuadat'
            };
            
            const labels = {
                'tot': 'Tốt',
                'tốt': 'Tốt',
                'kha': 'Khá',
                'khá': 'Khá',
                'dat': 'Đạt',
                'đạt': 'Đạt',
                'trungbinh': 'TB',
                'tb': 'TB',
                'chuadat': 'Chưa đạt',
                'yeu': 'Yếu'
            };
            
            const cssClass = classes[normalizedRanking] || 'ranking-chuaxeploai';
            const label = labels[normalizedRanking] || ranking;
            
            return `<span class="ranking-badge ${cssClass}">${label}</span>`;
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN');
        }

        // Load data on page load
        window.onload = function() {
            loadData();
        };

        function openCriteriaModal() {
            // Load cấu hình hiện tại
            fetch('?action=getCriteria')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.criteria) {
                        loadCriteriaToForm(data.criteria);
                    }
                    document.getElementById('criteriaModal').classList.add('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('criteriaModal').classList.add('show');
                });
        }

        function closeCriteriaModal() {
            document.getElementById('criteriaModal').classList.remove('show');
        }

        function loadCriteriaToForm(criteria) {
            const rankings = ['tot', 'kha', 'dat'];
            const rankingMap = {
                'tot': 'Tốt',
                'kha': 'Khá',
                'dat': 'Đạt'
            };
            
            rankings.forEach(rank => {
                const rankingName = rankingMap[rank];
                if (criteria[rankingName]) {
                    const c = criteria[rankingName];
                    document.getElementById(`${rank}-cophep`).value = c.maxNghiCoPhep || 0;
                    document.getElementById(`${rank}-khongphep`).value = c.maxNghiKhongPhep || 0;
                    document.getElementById(`${rank}-nhe`).value = c.maxViPhamNhe || 0;
                    document.getElementById(`${rank}-tb`).value = c.maxViPhamTB || 0;
                    document.getElementById(`${rank}-nang`).value = c.maxViPhamNang || 0;
                    document.getElementById(`${rank}-hocluc`).value = c.minHocLuc || '';
                }
            });
        }

        function getCriteriaFromForm() {
            return {
                'Tốt': {
                    maxNghiCoPhep: parseInt(document.getElementById('tot-cophep').value) || 0,
                    maxNghiKhongPhep: parseInt(document.getElementById('tot-khongphep').value) || 0,
                    maxViPhamNhe: parseInt(document.getElementById('tot-nhe').value) || 0,
                    maxViPhamTB: parseInt(document.getElementById('tot-tb').value) || 0,
                    maxViPhamNang: parseInt(document.getElementById('tot-nang').value) || 0,
                    minHocLuc: document.getElementById('tot-hocluc').value
                },
                'Khá': {
                    maxNghiCoPhep: parseInt(document.getElementById('kha-cophep').value) || 0,
                    maxNghiKhongPhep: parseInt(document.getElementById('kha-khongphep').value) || 0,
                    maxViPhamNhe: parseInt(document.getElementById('kha-nhe').value) || 0,
                    maxViPhamTB: parseInt(document.getElementById('kha-tb').value) || 0,
                    maxViPhamNang: parseInt(document.getElementById('kha-nang').value) || 0,
                    minHocLuc: document.getElementById('kha-hocluc').value
                },
                'Đạt': {
                    maxNghiCoPhep: parseInt(document.getElementById('dat-cophep').value) || 0,
                    maxNghiKhongPhep: parseInt(document.getElementById('dat-khongphep').value) || 0,
                    maxViPhamNhe: parseInt(document.getElementById('dat-nhe').value) || 0,
                    maxViPhamTB: parseInt(document.getElementById('dat-tb').value) || 0,
                    maxViPhamNang: parseInt(document.getElementById('dat-nang').value) || 0,
                    minHocLuc: document.getElementById('dat-hocluc').value
                },
                'Chưa đạt': {
                    maxNghiCoPhep: 999,
                    maxNghiKhongPhep: 999,
                    maxViPhamNhe: 999,
                    maxViPhamTB: 999,
                    maxViPhamNang: 999,
                    minHocLuc: ''
                }
            };
        }

        function saveCriteriaAndClassify() {
            const criteria = getCriteriaFromForm();
            const maLop = document.getElementById('maLop').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            
            if (!confirm('Bạn có chắc chắn muốn lưu cấu hình và áp dụng xếp loại tự động cho tất cả học sinh?')) {
                return;
            }
            
            const alertDiv = document.getElementById('alertMessage');
            alertDiv.innerHTML = '<div class="alert" style="background: #e3f2fd; border: 1px solid #90caf9; color: #1565c0;"><i class="fas fa-spinner fa-spin"></i> Đang xử lý...</div>';
            
            // Đóng modal trước
            closeCriteriaModal();
            
            // Bước 1: Lưu cấu hình
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=saveCriteria&criteria=${encodeURIComponent(JSON.stringify(criteria))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Bước 2: Áp dụng xếp loại tự động
                    return fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=autoClassify&maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}`
                    });
                } else {
                    throw new Error(data.message || 'Lỗi khi lưu cấu hình');
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alertDiv.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i> ${data.message}</div>`;
                    
                    // Reload dữ liệu sau 1 giây để thấy kết quả
                    setTimeout(() => {
                        loadData();
                        alertDiv.innerHTML = '';
                    }, 1500);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
                    setTimeout(() => {
                        alertDiv.innerHTML = '';
                    }, 5000);
                }
            })
            .catch(error => {
                closeCriteriaModal();
                console.error('Error:', error);
                alertDiv.innerHTML = `<div class="alert alert-error"><i class="fas fa-times-circle"></i> ${error.message || 'Lỗi khi xử lý. Vui lòng thử lại.'}</div>`;
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 5000);
            });
        }

        function displayTitles() {
            console.log('[displayTitles] Called with allStudents length:', allStudents.length);
            
            const students = getPaginatedStudents();
            
            if (!allStudents || allStudents.length === 0) {
                console.warn('[displayTitles] No students data');
                document.getElementById('titleContent').innerHTML = `
                    <div class="alert alert-error">
                        <i class="fas fa-info-circle"></i> Chưa có dữ liệu học sinh trong lớp này.
                    </div>`;
                return;
            }

            console.log('[displayTitles] Displaying', students.length, 'students');

            let html = `
                <div class="table-container">
                    <table class="common-table">
                        <thead>
                            <tr>
                                <th class="small-cell">STT</th>
                                <th>Họ tên</th>
                                <th class="center">Học lực</th>
                                <th class="center">Hạnh kiểm</th>
                                <th class="center">Danh hiệu</th>
                            </tr>
                        </thead>
                        <tbody class="common-table-body">
            `;
            
            const startIndex = (currentPage - 1) * itemsPerPage;
            students.forEach((student, index) => {
                const globalIndex = startIndex + index + 1;
                
                // Xử lý dữ liệu null/undefined
                const hoTen = student.hoTen || 'N/A';
                const loaiHocLuc = student.loaiHocLuc || null;
                const hanhKiem = student.hanhKiem || null;
                const tenDanhHieu = student.tenDanhHieu || null;
                
                html += `
                    <tr>
                        <td class="small-cell">
                            <p>${globalIndex}</p>
                        </td>
                        <td>
                            <div class="request-description">
                                <span class="request-des-title">${hoTen}</span>
                            </div>
                        </td>
                        <td class="center">${loaiHocLuc ? formatRanking(loaiHocLuc) : '<span style="color: #999;">-</span>'}</td>
                        <td class="center">${hanhKiem ? formatRanking(hanhKiem) : '<span style="color: #999;">-</span>'}</td>
                        <td class="center">${tenDanhHieu ? formatTitle(tenDanhHieu) : '<span style="color: #999;">Chưa xếp</span>'}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            // Thêm phân trang
            html += createPagination();
            
            document.getElementById('titleContent').innerHTML = html;
            console.log('[displayTitles] Table rendered successfully');
        }

        function formatTitle(title) {
            if (!title) {
                return '<span style="color: #999;">-</span>';
            }
            
            if (title.includes('xuất sắc') || title.includes('Xuất sắc')) {
                return `<span class="title-badge title-xuatsac"><i class="fas fa-trophy"></i> ${title}</span>`;
            } else if (title.includes('giỏi') || title.includes('Giỏi')) {
                return `<span class="title-badge title-gioi"><i class="fas fa-medal"></i> ${title}</span>`;
            }
            
            return `<span class="title-badge">${title}</span>`;
        }

        // Xếp loại danh hiệu tự động
        function classifyAllTitles() {
            const maLop = document.getElementById('maLop').value;
            const hocKy = document.getElementById('hocKy').value;
            const namHoc = document.getElementById('namHoc').value;
            
            if (!confirm('Bạn có chắc chắn muốn xếp loại danh hiệu tự động cho tất cả học sinh?\n\nTiêu chí:\n• Học sinh xuất sắc: Học lực Tốt + Hạnh kiểm Tốt + ít nhất 6 môn ≥ 9.0\n• Học sinh giỏi: Học lực Tốt + Hạnh kiểm Tốt')) {
                return;
            }
            
            const alertDiv = document.getElementById('alertMessage');
            alertDiv.innerHTML = '<div class="alert" style="background: #e3f2fd; border: 1px solid #90caf9; color: #1565c0;"><i class="fas fa-spinner fa-spin"></i> Đang xếp loại danh hiệu...</div>';
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=classifyTitles&maLop=${maLop}&hocKy=${hocKy}&namHoc=${encodeURIComponent(namHoc)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alertDiv.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i> ${data.message}</div>`;
                    
                    // Đánh dấu cần refresh và reload data
                    needsDataRefresh = true;
                    setTimeout(() => {
                        loadData(true);
                        alertDiv.innerHTML = '';
                    }, 1500);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
                    setTimeout(() => {
                        alertDiv.innerHTML = '';
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Lỗi khi xếp loại danh hiệu. Vui lòng thử lại.</div>';
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 5000);
            });
        }

        // ...existing code for other functions...

        // Load data on page load
        window.onload = function() {
            loadData();
        };

        // ...existing code...
    </script>
</body>

</html>
