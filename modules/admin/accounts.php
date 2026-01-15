<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Strict Admin Check
if ($_SESSION['role'] !== 'admin') {
    die("Bạn không có quyền truy cập trang này.");
}

// Add Account
if (isset($_POST['add_account'])) {
    $username = $_POST['username'];
    $fullname = $_POST['fullname'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $pdo->prepare("INSERT INTO accounts (username, password, fullname, role) VALUES (?, ?, ?, ?)")
            ->execute([$username, $password, $fullname, $role]);
        echo "<script>alert('Tạo tài khoản thành công!'); window.location.href='accounts.php';</script>";
    } catch (PDOException $e) {
        $error = "Tên đăng nhập đã tồn tại hoặc lỗi khác.";
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($id != $_SESSION['user_id']) { // Prevent self-delete
        $pdo->prepare("DELETE FROM accounts WHERE id = ?")->execute([$id]);
    }
    echo "<script>window.location.href='accounts.php';</script>";
}

$accounts = $pdo->query("SELECT * FROM accounts")->fetchAll();
?>

<h3><i class="fas fa-user-shield"></i> Quản Lý Tài Khoản Cán Bộ</h3>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header bg-danger text-white">Thêm Tài Khoản</div>
            <div class="card-body">
                <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Tên Đăng Nhập</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật Khẩu</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Họ Tên</label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vai Trò</label>
                        <select name="role" class="form-select">
                            <option value="staff">Nhân viên (Thủ thư)</option>
                            <option value="admin">Quản trị viên (Admin)</option>
                        </select>
                    </div>
                    <button type="submit" name="add_account" class="btn btn-danger w-100">Tạo Tài Khoản</button>
                    <!-- Log export if needed -->
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Họ Tên</th>
                            <th>Role</th>
                            <th>Lần đăng nhập cuối</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($accounts as $acc): ?>
                        <tr>
                            <td><?php echo $acc['id']; ?></td>
                            <td><?php echo htmlspecialchars($acc['username']); ?></td>
                            <td><?php echo htmlspecialchars($acc['fullname']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $acc['role'] == 'admin' ? 'danger' : 'secondary'; ?>">
                                    <?php echo ucfirst($acc['role']); ?>
                                </span>
                            </td>
                            <td><?php echo $acc['last_login']; ?></td>
                            <td>
                                <?php if($acc['id'] != $_SESSION['user_id']): ?>
                                <a href="accounts.php?delete=<?php echo $acc['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa tài khoản này?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="logs.php" class="btn btn-sm btn-secondary">Xem Nhật Ký Hệ Thống</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
