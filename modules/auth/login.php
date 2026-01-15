<?php
session_start();
require_once '../../config/db.php';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Vui lòng nhập đầy đủ thông tin.";
        header("Location: ../../index.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO system_logs (account_id, action) VALUES (?, ?)");
            $logStmt->execute([$user['id'], "Đăng nhập hệ thống"]);

            // Update last login
            $pdo->prepare("UPDATE accounts SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            header("Location: ../admin/dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Sai tên đăng nhập hoặc mật khẩu.";
            header("Location: ../../index.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi hệ thống: " . $e->getMessage();
        header("Location: ../../index.php");
        exit();
    }
} else {
    header("Location: ../../index.php");
    exit();
}
?>
