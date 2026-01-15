<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: /Quanlythuvien/index.php");
    exit();
}

// Helper to check active menu
function isActive($path) {
    return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Lý Thư Viện</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="/Quanlythuvien/assets/css/style.css?v=2.1" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar d-flex flex-column text-white">
            <div class="p-3 text-center border-bottom border-light">
                <h4 class="m-0"><i class="fas fa-book-reader"></i> Thư Viện</h4>
                <small>Hệ thống Quản lý</small>
            </div>
            
            <div class="p-3 text-center bg-white bg-opacity-10">
                <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
                <small class="text-warning"><?php echo ucfirst($_SESSION['role']); ?></small>
            </div>

            <div class="flex-grow-1 overflow-auto">
                <ul class="nav flex-column">
                    <!-- Module 6: Dashboard -->
                    <li class="nav-item">
                        <a href="/Quanlythuvien/modules/admin/dashboard.php" class="nav-link <?php echo isActive('dashboard.php'); ?>">
                            <i class="fas fa-tachometer-alt"></i> Tổng Quan
                        </a>
                    </li>
                    
                    <!-- Module 1: Sách -->
                    <li class="nav-item">
                        <a href="/Quanlythuvien/modules/books/index.php" class="nav-link <?php echo isActive('modules/books'); ?>">
                            <i class="fas fa-book"></i> Kho Sách
                        </a>
                    </li>

                    <!-- Module 2: Danh mục & NXB -->
                    <li class="nav-item">
                        <a href="/Quanlythuvien/modules/categories/index.php" class="nav-link <?php echo isActive('modules/categories'); ?>">
                            <i class="fas fa-list"></i> Danh mục & NXB
                        </a>
                    </li>

                    <!-- Module 3: Sinh viên -->
                    <li class="nav-item">
                        <a href="/Quanlythuvien/modules/students/index.php" class="nav-link <?php echo isActive('modules/students'); ?>">
                            <i class="fas fa-users"></i> Sinh Viên
                        </a>
                    </li>

                    <!-- Module 4: Mượn sách -->
                    <li class="nav-item">
                        <a href="/Quanlythuvien/modules/borrow/index.php" class="nav-link <?php echo isActive('modules/borrow'); ?>">
                            <i class="fas fa-shopping-basket"></i> Mượn Sách
                        </a>
                    </li>

                    <!-- Module 5: Trả sách -->
                    <li class="nav-item">
                        <a href="/Quanlythuvien/modules/returns/index.php" class="nav-link <?php echo isActive('modules/returns'); ?>">
                            <i class="fas fa-undo"></i> Trả Sách & Phạt
                        </a>
                    </li>

                    <?php if($_SESSION['role'] === 'admin'): ?>
                    <!-- Module 6: Tài khoản -->
                    <li class="nav-item">
                        <a href="/Quanlythuvien/modules/admin/accounts.php" class="nav-link <?php echo isActive('modules/admin/accounts.php'); ?>">
                            <i class="fas fa-user-shield"></i> Tài Khoản CB
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="p-3 border-top border-light">
                <a href="/Quanlythuvien/modules/auth/logout.php" class="btn btn-danger w-100">
                    <i class="fas fa-sign-out-alt"></i> Đăng Xuất
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="content-wrapper p-4">
            <!-- Breadcrumb could go here -->
