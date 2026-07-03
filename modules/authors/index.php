<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Handle Add / Edit / Delete
$edit_mode = false;
$edit_data = [];

if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM authors WHERE id = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch();
    if ($edit_data) $edit_mode = true;
}

if (isset($_POST['save_author'])) {
    $name = $_POST['name'];
    $bio = $_POST['biography'];
    $website = $_POST['website'];

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $pdo->prepare("UPDATE authors SET name = ?, biography = ?, website = ? WHERE id = ?")
            ->execute([$name, $bio, $website, $_POST['id']]);
        echo "<script>alert('Cập nhật thành công!'); window.location.href='index.php';</script>";
    } else {
        // Add
        try {
            $pdo->prepare("INSERT INTO authors (name, biography, website) VALUES (?, ?, ?)")
                ->execute([$name, $bio, $website]);
             echo "<script>alert('Thêm mới thành công!'); window.location.href='index.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Lỗi: Tên tác giả có thể đã tồn tại!');</script>";
        }
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
         $pdo->prepare("DELETE FROM authors WHERE id = ?")->execute([$id]);
         echo "<script>alert('Đã xóa tác giả!'); window.location.href='index.php';</script>";
    } catch (Exception $e) {
         echo "<script>alert('Không thể xóa: Tác giả này đang có sách trong hệ thống!'); window.location.href='index.php';</script>";
    }
}

$authors = $pdo->query("SELECT * FROM authors ORDER BY name ASC")->fetchAll();
?>

<div class="row">
    <!-- Form Section -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary text-white">
                <h6 class="m-0 font-weight-bold"><?php echo $edit_mode ? 'Cập Nhật Tác Giả' : 'Thêm Tác Giả Mới'; ?></h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if($edit_mode): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Tên Tác Giả <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?php echo $edit_mode ? htmlspecialchars($edit_data['name']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tiểu Sử / Giới Thiệu</label>
                        <textarea name="biography" class="form-control" rows="4"><?php echo $edit_mode ? htmlspecialchars($edit_data['biography']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Website / Blog</label>
                        <input type="text" name="website" class="form-control" value="<?php echo $edit_mode ? htmlspecialchars($edit_data['website']) : ''; ?>">
                    </div>

                    <button type="submit" name="save_author" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i> <?php echo $edit_mode ? 'Lưu Thay Đổi' : 'Thêm Mới'; ?>
                    </button>
                    
                    <?php if($edit_mode): ?>
                        <a href="index.php" class="btn btn-secondary w-100 mt-2">Hủy Bỏ</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- List Section -->
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Danh Sách Tác Giả</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="bg-light">
                            <tr>
                                <th>Tên Tác Giả</th>
                                <th>Thông Tin</th>
                                <th style="width: 100px;">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($authors as $a): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($a['name']); ?></td>
                                <td>
                                    <?php if($a['website']): ?>
                                        <div class="small mb-1"><i class="fas fa-globe text-info me-1"></i> <?php echo $a['website']; ?></div>
                                    <?php endif; ?>
                                    <div class="small text-muted text-truncate" style="max-width: 300px;">
                                        <?php echo htmlspecialchars($a['biography']); ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="index.php?edit=<?php echo $a['id']; ?>" class="btn btn-sm btn-info" title="Sửa"><i class="fas fa-edit"></i></a>
                                    <a href="index.php?delete=<?php echo $a['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn chắc chắn muốn xóa tác giả này?')" title="Xóa"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
