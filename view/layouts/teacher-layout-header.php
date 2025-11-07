<?php
/**
 * Template Layout cho tất cả các trang teacher
 * Chứa sidebar navigation + content area
 * 
 * Sử dụng:
 * $pageTitle = "Tên trang";
 * $pageIcon = "fas fa-icon";
 * include('../layouts/teacher-layout.php');
 */

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../public/index.php");
    exit();
}

if ($_SESSION['loaiTaiKhoan'] !== 'giaovien') {
    header("Location: ../../public/index.php?error=access_denied");
    exit();
}

$pageTitle = $pageTitle ?? 'Trang';
$pageIcon = $pageIcon ?? 'fas fa-file';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Hệ thống Quản lý Giáo dục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .content-area {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
            transition: margin-left 0.3s;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header-left h1 {
            color: #667eea;
            font-size: 26px;
            margin-bottom: 5px;
        }

        .page-header-left p {
            color: #999;
            font-size: 13px;
        }

        .page-header h1 i {
            margin-right: 10px;
        }

        .page-header-right {
            text-align: right;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: #999;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
            transition: all 0.3s;
        }

        .breadcrumb a:hover {
            color: #764ba2;
        }

        .page-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9f9f9;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Filters */
        .filter-section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .filter-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #666;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }

        .empty-state p {
            font-size: 16px;
            margin: 10px 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-area {
                margin-left: 0;
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .filter-form {
                flex-direction: column;
            }

            .form-group {
                min-width: 100%;
            }

            .filter-buttons {
                flex-direction: column;
            }

            .filter-buttons .btn {
                width: 100%;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar Navigation -->
        <?php include('navigate/navigateTeacher.php'); ?>

        <!-- Main Content -->
        <div class="content-area">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-left">
                    <h1><i class="<?php echo $pageIcon; ?>"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
                    <p>Quản lý và theo dõi thông tin</p>
                </div>
                <div class="page-header-right">
                    <div class="breadcrumb">
                        <a href="../../view/teacher/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <span>/</span>
                        <span><?php echo htmlspecialchars($pageTitle); ?></span>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="page-content">
