<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = $_GET['id'];
$book = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$book->execute([$id]);
$book = $book->fetch();

if (!$book) {
    header('Location: index.php');
    exit();
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$publishers = $pdo->query("SELECT * FROM publishers")->fetchAll();

if (isset($_POST['update_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category_id = $_POST['category_id'];
    $publisher_id = $_POST['publisher_id'];
    $status = $_POST['status'];
    // Logic for quantity update is tricky if borrwed. 
    // Simplified: Just update total, available updates relative difference?
    // User requested "Update quantity when inputting stock".
    // Let's assume user edits Total Quantity manually. available = new_total - (old_total - old_available)
    $new_total = $_POST['quantity'];
    $borrowed = $book['total_quantity'] - $book['available_quantity'];
    $new_available = $new_total - $borrowed;

    if ($new_available < 0) {
       $error = "Không thể giảm số lượng xuống thấp hơn số sách đang cho mượn ($borrowed cuốn).";
    } else {
        // Image processing
        $image_path = $book['image']; // Default to existing
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_name = "book_" . time() . "_" . uniqid() . "." . $ext;
                $upload_dir = "../../assets/uploads/books/";
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                    $image_path = "assets/uploads/books/" . $new_name;
                }
            } else {
                $error = "File ảnh không hợp lệ.";
            }
        }

        if (!isset($error)) {
            try {
                $sql = "UPDATE books SET title=?, author=?, category_id=?, publisher_id=?, total_quantity=?, available_quantity=?, status=?, image=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$title, $author, $category_id, $publisher_id, $new_total, $new_available, $status, $image_path, $id]);
                
                // Log
            $pdo->prepare("INSERT INTO system_logs (account_id, action) VALUES (?, ?)")
                 ->execute([$_SESSION['user_id'], "Cập nhật sách: $title"]);

            echo "<script>alert('Cập nhật thành công!'); window.location.href='index.php';</script>";
        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
    }
}
?>

<div class="card shadow">
    <div class="card-header bg-warning text-dark">Chỉnh Sửa Sách</div>
    <div class="card-body">
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-6">
                     <label class="form-label">ISBN (Không đổi)</label>
                     <input type="text" class="form-control" value="<?php echo htmlspecialchars($book['isbn']); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ảnh Bìa</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <?php if (!empty($book['image'])): ?>
                        <div class="mt-2">
                            <img src="/Quanlythuvien/<?php echo $book['image']; ?>" alt="Current Cover" style="height: 80px;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                     <label class="form-label">Tổng Số Lượng</label>
                     <input type="number" name="quantity" class="form-control" required min="1" value="<?php echo $book['total_quantity']; ?>">
                     <small class="text-muted">Đang cho mượn: <?php echo $book['total_quantity'] - $book['available_quantity']; ?></small>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Tên Sách</label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($book['title']); ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Tác Giả</label>
                <input type="text" name="author" class="form-control" required value="<?php echo htmlspecialchars($book['author']); ?>">
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Danh Mục</label>
                    <select name="category_id" class="form-select">
                        <?php foreach($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $book['category_id'] == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo $c['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nhà Xuất Bản</label>
                    <select name="publisher_id" class="form-select">
                        <?php foreach($publishers as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $book['publisher_id'] == $p['id'] ? 'selected' : ''; ?>>
                                <?php echo $p['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                     <label class="form-label">Trạng Thái</label>
                     <select name="status" class="form-select">
                         <option value="active" <?php echo $book['status'] == 'active' ? 'selected' : ''; ?>>Hiển thị</option>
                         <option value="hidden" <?php echo $book['status'] == 'hidden' ? 'selected' : ''; ?>>Ẩn (Xóa mềm)</option>
                     </select>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="index.php" class="btn btn-secondary">Quay lại</a>
                <button type="submit" name="update_book" class="btn btn-primary px-4">Cập Nhật</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
