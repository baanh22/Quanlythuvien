<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $pdo->prepare("INSERT INTO publishers (name, address, contact) VALUES (?, ?, ?)")->execute([$name, $address, $contact]);
    echo "<script>window.location.href='index.php';</script>";
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
         $pdo->prepare("DELETE FROM publishers WHERE id = ?")->execute([$id]);
    } catch (Exception $e) {}
    echo "<script>window.location.href='index.php';</script>";
}

$publishers = $pdo->query("SELECT * FROM publishers")->fetchAll();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">Thêm Nhà Xuất Bản</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Tên NXB</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ</label>
                        <textarea name="address" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Liên hệ (SĐT/Email)</label>
                        <input type="text" name="contact" class="form-control">
                    </div>
                    <button type="submit" name="add" class="btn btn-success w-100">Thêm Mới</button>
                    <a href="../categories/index.php" class="btn btn-secondary w-100 mt-2">Quay lại Danh mục</a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Danh Sách Nhà Xuất Bản</h6>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tên NXB</th>
                            <th>Địa chỉ</th>
                            <th>Liên hệ</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($publishers as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td><?php echo htmlspecialchars($p['address']); ?></td>
                            <td><?php echo htmlspecialchars($p['contact']); ?></td>
                            <td>
                                <a href="index.php?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa NXB?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
