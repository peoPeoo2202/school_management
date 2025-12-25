<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['maTaiKhoan']) || $_SESSION['loaiTaiKhoan'] !== 'quantrivien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

// Lấy thông tin người dùng
$userName = $_SESSION['hoTen'] ?? 'Administrator';
$userInitial = strtoupper(substr($userName, 0, 1));

// Kết nối database để lấy dữ liệu
require_once('../../model/mConnect.php');
$db = new mConnect();
$conn = $db->mConnect();

// Lấy danh sách nhóm người dùng
$nhomList = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM nhomnguoidung ORDER BY tenNhom");
    while ($row = $result->fetch_assoc()) {
        $nhomList[] = $row;
    }
}

// Danh sách các chức năng hệ thống
$chucNangList = [
    'account' => [
        'label' => 'Quản lý tài khoản',
        'actions' => [
            'view' => 'Xem danh sách',
            'view_own' => 'Xem thông tin cá nhân',
            'create' => 'Thêm mới',
            'update' => 'Chỉnh sửa',
            'update_own' => 'Sửa thông tin cá nhân',
            'delete' => 'Xóa',
            'lock' => 'Khóa tài khoản',
            'unlock' => 'Mở khóa tài khoản',
            'reset_password' => 'Đặt lại mật khẩu'
        ]
    ],
    'student' => [
        'label' => 'Quản lý học sinh',
        'actions' => [
            'view' => 'Xem danh sách',
            'create' => 'Thêm mới',
            'update' => 'Chỉnh sửa',
            'delete' => 'Xóa'
        ]
    ],
    'teacher' => [
        'label' => 'Quản lý giáo viên',
        'actions' => [
            'view' => 'Xem danh sách',
            'create' => 'Thêm mới',
            'update' => 'Chỉnh sửa',
            'delete' => 'Xóa'
        ]
    ],
    'parent' => [
        'label' => 'Quản lý phụ huynh',
        'actions' => [
            'view' => 'Xem danh sách',
            'create' => 'Thêm mới',
            'update' => 'Chỉnh sửa',
            'delete' => 'Xóa'
        ]
    ],
    'class' => [
        'label' => 'Quản lý lớp học',
        'actions' => [
            'view' => 'Xem danh sách',
            'create' => 'Thêm mới',
            'update' => 'Chỉnh sửa',
            'delete' => 'Xóa'
        ]
    ],
    'grade' => [
        'label' => 'Quản lý điểm',
        'actions' => [
            'view' => 'Xem điểm',
            'manage' => 'Nhập/sửa điểm',
            'export' => 'Xuất điểm'
        ]
    ],
    'schedule' => [
        'label' => 'Quản lý lịch/TKB',
        'actions' => [
            'view' => 'Xem lịch',
            'create' => 'Tạo lịch',
            'update' => 'Chỉnh sửa lịch',
            'delete' => 'Xóa lịch'
        ]
    ],
    'report' => [
        'label' => 'Báo cáo/Thống kê',
        'actions' => [
            'view' => 'Xem báo cáo',
            'create' => 'Tạo báo cáo',
            'submit' => 'Gửi báo cáo',
            'export' => 'Xuất báo cáo'
        ]
    ],
    'group' => [
        'label' => 'Quản lý nhóm quyền',
        'actions' => [
            'view' => 'Xem danh sách',
            'create' => 'Thêm mới',
            'update' => 'Chỉnh sửa',
            'delete' => 'Xóa'
        ]
    ],
    'audit' => [
        'label' => 'Nhật ký hệ thống',
        'actions' => [
            'view' => 'Xem nhật ký'
        ]
    ],
    'system' => [
        'label' => 'Hệ thống',
        'actions' => [
            'admin' => 'Quyền quản trị',
            'config' => 'Cấu hình hệ thống',
            'backup' => 'Sao lưu dữ liệu'
        ]
    ]
];

$db->mDisconnect($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân quyền - Hệ thống Quản lý Trường học</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="css/schedule-style.css">
    
    <style>
        /* Permission page specific styles */
        .permission-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .accounts-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .panel-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        
        .panel-header h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 35px 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-box i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }
        
        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }
        
        .filter-tab {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: white;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-tab:hover {
            border-color: #3498db;
            color: #3498db;
        }
        
        .filter-tab.active {
            background: #3498db;
            border-color: #3498db;
            color: white;
        }
        
        .accounts-list {
            max-height: calc(100vh - 350px);
            overflow-y: auto;
        }
        
        .account-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f1f2f6;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .account-item:hover {
            background: #f8f9fa;
        }
        
        .account-item.selected {
            background: #ebf5fb;
            border-left: 3px solid #3498db;
        }
        
        .account-item.multi-selected {
            background: #e8f8f5;
            border-left: 3px solid #27ae60;
        }
        
        .account-checkbox {
            margin-right: 12px;
        }
        
        .account-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 12px;
            font-size: 14px;
        }
        
        .account-info {
            flex: 1;
        }
        
        .account-name {
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .account-meta {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 2px;
        }
        
        .account-role {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
            background: #ecf0f1;
            color: #7f8c8d;
        }
        
        .permissions-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .permissions-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .permissions-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .selected-info {
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .permissions-body {
            padding: 20px;
            max-height: calc(100vh - 350px);
            overflow-y: auto;
        }
        
        .empty-selection {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }
        
        .empty-selection i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .permission-group {
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .permission-group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
        }
        
        .permission-group-title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .permission-group-title i {
            margin-right: 10px;
            color: #3498db;
        }
        
        .permission-group-actions {
            display: flex;
            gap: 10px;
        }
        
        .select-all-btn {
            font-size: 12px;
            color: #3498db;
            cursor: pointer;
        }
        
        .select-all-btn:hover {
            text-decoration: underline;
        }
        
        .permission-group-body {
            padding: 15px;
        }
        
        .permission-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .permission-item:hover {
            background: #ecf0f1;
        }
        
        .permission-item input {
            margin-right: 10px;
        }
        
        .permission-item.checked {
            background: #e8f8f5;
            border: 1px solid #27ae60;
        }
        
        .permission-label {
            font-size: 13px;
            color: #2c3e50;
        }
        
        .permissions-footer {
            padding: 15px 20px;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        /* Multi-select toolbar */
        .multi-select-toolbar {
            display: none;
            padding: 15px 20px;
            background: #27ae60;
            color: white;
            align-items: center;
            justify-content: space-between;
        }
        
        .multi-select-toolbar.active {
            display: flex;
        }
        
        .multi-select-count {
            font-weight: 500;
        }
        
        /* Group Management Modal */
        .group-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .group-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .group-item:last-child {
            border-bottom: none;
        }
        
        .group-name {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .group-count {
            font-size: 12px;
            color: #95a5a6;
        }
        
        .group-actions {
            display: flex;
            gap: 8px;
        }
        
        /* Avatar colors by role */
        .avatar-admin { background: #9b59b6; }
        .avatar-bgh { background: #e74c3c; }
        .avatar-giaovien { background: #3498db; }
        .avatar-gvcn { background: #2ecc71; }
        .avatar-ttbm { background: #f39c12; }
        .avatar-hocsinh { background: #1abc9c; }
        .avatar-phuhuynh { background: #e67e22; }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .quick-action-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .quick-action-btn:hover {
            border-color: #3498db;
            color: #3498db;
        }
        
        .quick-action-btn i {
            margin-right: 6px;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .permission-container {
                grid-template-columns: 1fr;
            }
            
            .accounts-list {
                max-height: 300px;
            }
            
            .permissions-body {
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i> QLTH
                </a>
                <div class="sidebar-subtitle">Hệ thống Quản lý Trường học</div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">MENU CHÍNH</div>
                    
                    <div class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="students.php" class="nav-link">
                            <i class="fas fa-users-cog"></i>
                            <span>Quản lý thông tin</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="schedule.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Lên lịch</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="permissions.php" class="nav-link active">
                            <i class="fas fa-user-shield"></i>
                            <span>Phân quyền</span>
                        </a>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">CÔNG CỤ</div>
                    
                    <div class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-chart-bar"></i>
                            <span>Báo cáo thống kê</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Cấu hình hệ thống</span>
                        </a>
                    </div>
                </div>
            </nav>

            <div class="sidebar-footer">
                <a href="../../public/index.php?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="breadcrumb">
                    <div class="breadcrumb-item">
                        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    </div>
                    <div class="breadcrumb-item">
                        <span>Phân quyền</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="notification-wrapper">
                        <button class="notification-btn">
                            <i class="fas fa-bell"></i>
                            <span class="badge">3</span>
                        </button>
                    </div>
                    <div class="user-dropdown">
                        <div class="user-avatar"><?php echo $userInitial; ?></div>
                        <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>
            </header>

            <!-- Page Title -->
            <div class="page-title">
                <h1><i class="fas fa-user-shield"></i> Phân quyền người dùng</h1>
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="openGroupModal()">
                        <i class="fas fa-layer-group"></i> Quản lý nhóm
                    </button>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <button class="quick-action-btn" onclick="toggleMultiSelect()">
                    <i class="fas fa-check-square"></i> Chọn nhiều
                </button>
                <button class="quick-action-btn" onclick="exportPermissions()">
                    <i class="fas fa-file-export"></i> Xuất báo cáo
                </button>
                <button class="quick-action-btn" onclick="viewAuditLog()">
                    <i class="fas fa-history"></i> Lịch sử thay đổi
                </button>
            </div>

            <!-- Permission Container -->
            <div class="permission-container">
                <!-- Left Panel: Account List -->
                <div class="accounts-panel">
                    <div class="panel-header">
                        <h3><i class="fas fa-users"></i> Danh sách tài khoản</h3>
                        <div class="search-box">
                            <input type="text" id="search-account" placeholder="Tìm theo tên, mã số..." onkeyup="searchAccounts()">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="filter-tabs">
                            <button class="filter-tab active" data-filter="all" onclick="filterByGroup('all')">Tất cả</button>
                            <?php foreach ($nhomList as $nhom): ?>
                                <button class="filter-tab" data-filter="<?php echo $nhom['maNhom']; ?>" onclick="filterByGroup(<?php echo $nhom['maNhom']; ?>)">
                                    <?php echo htmlspecialchars($nhom['tenNhom']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Multi-select toolbar -->
                    <div class="multi-select-toolbar" id="multi-select-toolbar">
                        <span class="multi-select-count"><span id="selected-count">0</span> đã chọn</span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-secondary" onclick="clearSelection()">Bỏ chọn</button>
                            <button class="btn btn-sm btn-primary" onclick="applyBulkPermissions()">Áp dụng quyền</button>
                        </div>
                    </div>
                    
                    <div class="accounts-list" id="accounts-list">
                        <!-- Account items will be loaded here -->
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin"></i> Đang tải...
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Permission Settings -->
                <div class="permissions-panel">
                    <div class="permissions-header">
                        <h3><i class="fas fa-key"></i> Cấu hình quyền</h3>
                        <span class="selected-info" id="selected-info">Chọn tài khoản để phân quyền</span>
                    </div>
                    
                    <div class="permissions-body" id="permissions-body">
                        <div class="empty-selection" id="empty-selection">
                            <i class="fas fa-user-cog"></i>
                            <h3>Chọn tài khoản để phân quyền</h3>
                            <p>Nhấp vào tài khoản bên trái để xem và chỉnh sửa quyền truy cập</p>
                        </div>
                        
                        <div id="permissions-form" style="display: none;">
                            <!-- Account Info -->
                            <div class="account-detail-card" id="account-detail-card">
                                <div class="account-avatar-large" id="detail-avatar">A</div>
                                <div class="account-detail-info">
                                    <h4 id="detail-name">Tên người dùng</h4>
                                    <p id="detail-meta">Mã: - | Nhóm: -</p>
                                </div>
                            </div>
                            
                            <!-- Permission Groups -->
                            <?php foreach ($chucNangList as $key => $chucNang): ?>
                            <div class="permission-group" data-group="<?php echo $key; ?>">
                                <div class="permission-group-header" onclick="toggleGroup('<?php echo $key; ?>')">
                                    <span class="permission-group-title">
                                        <i class="fas fa-chevron-down"></i>
                                        <?php echo $chucNang['label']; ?>
                                    </span>
                                    <div class="permission-group-actions" onclick="event.stopPropagation()">
                                        <span class="select-all-btn" onclick="selectAllInGroup('<?php echo $key; ?>')">Chọn tất cả</span>
                                        <span class="select-all-btn" onclick="deselectAllInGroup('<?php echo $key; ?>')">Bỏ chọn</span>
                                    </div>
                                </div>
                                <div class="permission-group-body">
                                    <div class="permission-items">
                                        <?php foreach ($chucNang['actions'] as $action => $label): ?>
                                        <label class="permission-item">
                                            <input type="checkbox" 
                                                   name="permissions[]" 
                                                   value="<?php echo $key . '.' . $action; ?>"
                                                   onchange="onPermissionChange(this)">
                                            <span class="permission-label"><?php echo $label; ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="permissions-footer" id="permissions-footer" style="display: none;">
                        <div class="unsaved-warning" id="unsaved-warning" style="display: none;">
                            <i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i>
                            <span>Có thay đổi chưa lưu</span>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-secondary" onclick="resetPermissions()">
                                <i class="fas fa-undo"></i> Hủy thay đổi
                            </button>
                            <button class="btn btn-primary" onclick="savePermissions()">
                                <i class="fas fa-save"></i> Lưu quyền
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal: Quản lý nhóm -->
    <div id="group-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2><i class="fas fa-layer-group"></i> Quản lý nhóm người dùng</h2>
                <button class="modal-close" onclick="closeGroupModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <button class="btn btn-success btn-block" onclick="openAddGroupForm()">
                        <i class="fas fa-plus"></i> Thêm nhóm mới
                    </button>
                </div>
                
                <!-- Add Group Form -->
                <div id="add-group-form" style="display: none; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label>Tên nhóm <span class="required">*</span></label>
                        <input type="text" id="new-group-name" class="form-control" placeholder="Nhập tên nhóm">
                    </div>
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea id="new-group-desc" class="form-control" rows="2" placeholder="Mô tả nhóm (tùy chọn)"></textarea>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm" onclick="cancelAddGroup()">Hủy</button>
                        <button class="btn btn-primary btn-sm" onclick="saveNewGroup()">Lưu nhóm</button>
                    </div>
                </div>
                
                <div class="group-list" id="group-list">
                    <?php foreach ($nhomList as $nhom): ?>
                    <div class="group-item" data-id="<?php echo $nhom['maNhom']; ?>">
                        <div>
                            <div class="group-name"><?php echo htmlspecialchars($nhom['tenNhom']); ?></div>
                            <div class="group-count"><?php echo htmlspecialchars($nhom['moTa'] ?? 'Không có mô tả'); ?></div>
                        </div>
                        <div class="group-actions">
                            <button class="btn-icon btn-edit" onclick="editGroup(<?php echo $nhom['maNhom']; ?>)" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if (!in_array($nhom['maNhom'], [3001, 3002, 3003, 3004, 3005])): ?>
                            <button class="btn-icon btn-delete" onclick="deleteGroup(<?php echo $nhom['maNhom']; ?>)" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeGroupModal()">Đóng</button>
            </div>
        </div>
    </div>

    <!-- Modal: Xác nhận thoát -->
    <div id="confirm-exit-modal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>Xác nhận</h2>
                <button class="modal-close" onclick="closeConfirmExitModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i>
                </div>
                <p>Bạn có thay đổi chưa lưu. Bạn có muốn thoát không?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeConfirmExitModal()">Ở lại</button>
                <button class="btn btn-danger" onclick="confirmExit()">Thoát</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <script src="js/permissions.js"></script>
</body>
</html>
