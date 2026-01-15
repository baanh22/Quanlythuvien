<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: modules/admin/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Quản Lý Thư Viện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/Quanlythuvien/assets/css/style.css?v=2.1" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h3 class="text-center mb-4 text-primary">Đăng Nhập Hệ Thống</h3>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form action="modules/auth/login.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Tên đăng nhập</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" name="username" required placeholder="admin">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Mật khẩu</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" name="password" required placeholder="admin123">
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" name="login" class="btn btn-primary btn-lg">Đăng nhập</button>
            </div>
        </form>
        <div class="text-center mt-3 small text-muted">
            Hệ thống Quản lý Thư viện v2.0 <br>
            <a href="search.php" class="text-decoration-none mt-2 d-inline-block"><i class="fas fa-search"></i> Tra cứu sách (Sinh viên)</a>
        </div>
    </div>
</body>
</html>
