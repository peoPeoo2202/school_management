<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Hệ thống quản lý học sinh'; ?></title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f5f5f5;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #4d5ef7 0%, #3b49df 100%);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
        }

        /* Logout button */
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-1px);
        }

        .logout-btn i {
            font-size: 16px;
        }

        /* Container with sidebar */
        .container {
            display: flex;
            flex: 1;
            min-height: 0;
        }

        /* Sidebar */
        .sidebar-container {
            width: 200px;
            background-color: white;
            border-right: 1px solid #e0e0e0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            flex-shrink: 0;
        }

        .sidebar-container ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-container a {
            display: block;
            padding: 15px 20px;
            color: #555;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }

        .sidebar-container a:hover,
        .sidebar-container a.active {
            background-color: #e7f3ff;
            color: #007bff;
            border-left-color: #007bff;
        }

        /* Main content */
        .main {
            flex: 1;
            padding: 25px;
            background-color: #f8f9fa;
            overflow-y: auto;
        }

        /* Footer - Always at bottom */
        footer {
            background: linear-gradient(135deg, #4d5ef7 0%, #3b49df 100%);
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: auto;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
        }

        footer p {
            margin: 0;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar-container {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }

            header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
