<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$publishers = $pdo->query("SELECT * FROM publishers")->fetchAll();

if (isset($_POST['add_book'])) {
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category_id = $_POST['category_id'];
    $publisher_id = $_POST['publisher_id'];
    $quantity = $_POST['quantity'];

    // Check ISBN duplication
    $check = $pdo->prepare("SELECT COUNT(*) FROM books WHERE isbn = ?");
    $check->execute([$isbn]);
    if ($check->fetchColumn() > 0) {
        $error = "Mã ISBN này đã tồn tại trong hệ thống!";
    } else {
        // Handle Image Upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_name = "book_" . time() . "_" . uniqid() . "." . $ext;
                $upload_dir = "../../assets/uploads/books/";
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                    $image_path = "assets/uploads/books/" . $new_name; // Store relative path for DB
                }
            } else {
                $error = "Chỉ chấp nhận file ảnh (jpg, jpeg, png, gif)!";
            }
        }

        if (!isset($error)) {
            try {
                $sql = "INSERT INTO books (isbn, title, author, category_id, publisher_id, total_quantity, available_quantity, image) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$isbn, $title, $author, $category_id, $publisher_id, $quantity, $quantity, $image_path]);
                
                // Log action
            $pdo->prepare("INSERT INTO system_logs (account_id, action) VALUES (?, ?)")
                ->execute([$_SESSION['user_id'], "Thêm sách mới: $title ($isbn)"]);

            echo "<script>alert('Thêm sách thành công!'); window.location.href='index.php';</script>";
        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">Nhập Sách Mới vào Kho</div>
            <div class="card-body">
                <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Mã ISBN <span class="text-danger">*</span></label>
                            <input type="text" name="isbn" class="form-control" required placeholder="Ví dụ: 978-3-16-148410-0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ảnh Bìa</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Số lượng nhập <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" required min="1" value="1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tên Sách <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tác Giả <span class="text-danger">*</span></label>
                        <input type="text" name="author" class="form-control" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Danh Mục</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Chọn Danh Mục --</option>
                                <?php foreach($categories as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nhà Xuất Bản</label>
                            <select name="publisher_id" class="form-select">
                                <option value="">-- Chọn NXB --</option>
                                <?php foreach($publishers as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Hủy bỏ</a>
                        <button type="submit" name="add_book" class="btn btn-primary px-4">Lưu Sách</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
