<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Add Category
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        // Helper redirect would be better but direct here
        echo "<script>window.location.href='index.php';</script>";
    }
}

// Delete Category
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    } catch (Exception $e) {
        $error = "Không thể xóa danh mục đang có sách.";
    }
    echo "<script>window.location.href='index.php';</script>";
}

// Fetch Categories with Book Count
$sql = "SELECT c.*, COUNT(b.id) as book_count 
        FROM categories c 
        LEFT JOIN books b ON c.id = b.category_id 
        GROUP BY c.id";
$categories = $pdo->query($sql)->fetchAll();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">Thêm Danh Mục</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Tên Danh Mục</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <button type="submit" name="add" class="btn btn-success w-100">Thêm Mới</button>
                </form>
            </div>
        </div>
        
        <div class="card shadow mt-4">
             <div class="card-header bg-info text-white">Quản Lý NXB</div>
             <div class="card-body text-center">
                 <a href="../publishers/index.php" class="btn btn-info text-white">Chuyển tới Quản lý NXB</a>
             </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Danh Sách Danh Mục</h6>
                <a href="#" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Xuất Excel</a>
            </div>
            <div class="card-body">
                <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Danh Mục</th>
                            <th>Số Lượng Sách</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td><?php echo htmlspecialchars($c['name']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo $c['book_count']; ?></span></td>
                            <td>
                                <a href="index.php?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn chắc chắn muốn xóa?')">
                                    <i class="fas fa-trash"></i>
                                </a>
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
