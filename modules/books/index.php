<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Prepare data for Filters
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$publishers = $pdo->query("SELECT * FROM publishers")->fetchAll();

// Search & Filter
$search = $_GET['search'] ?? '';
$cat_filter = $_GET['cat'] ?? '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT b.*, c.name as category_name, p.name as publisher_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN publishers p ON b.publisher_id = p.id
        WHERE (b.title LIKE ? OR b.isbn LIKE ?) ";
$params = ["%$search%", "%$search%"];

if (!empty($cat_filter)) {
    $sql .= " AND b.category_id = ?";
    $params[] = $cat_filter;
}

// Count total for pagination
$count_sql = str_replace("b.*, c.name as category_name, p.name as publisher_name", "COUNT(*)", $sql);
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($params);
$total_books = $stmt_count->fetchColumn();
$total_pages = ceil($total_books / $limit);

$sql .= " ORDER BY b.id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-book"></i> Quản Lý Kho Sách</h2>
    <div>
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nhập Sách Mới</a>
        <a href="export.php" class="btn btn-success"><i class="fas fa-file-excel"></i> Xuất Excel</a>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <form class="row g-3" method="GET">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm tên sách, ISBN..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="cat" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả danh mục --</option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $cat_filter == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo $c['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Tìm kiếm</button>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Hình Ảnh</th>
                        <th>ISBN</th>
                        <th>Tên Sách</th>
                        <th>Danh Mục</th>
                        <th>Tổng SL</th>
                        <th>Còn Lại</th>
                        <th>Trạng Thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($books as $book): ?>
                    <tr>
                        <td>
                            <?php if (!empty($book['image'])): ?>
                                <img src="/Quanlythuvien/<?php echo $book['image']; ?>" alt="Cover" style="height: 60px; object-fit: cover;">
                            <?php else: ?>
                                <span class="text-muted"><i class="fas fa-image fa-2x"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo htmlspecialchars($book['isbn']); ?></code></td>
                        <td><?php echo htmlspecialchars($book['title']); ?> <br> <small class="text-muted"><?php echo $book['author']; ?></small></td>
                        <td><?php echo $book['category_name']; ?></td>
                        <td><?php echo $book['total_quantity']; ?></td>
                        <td>
                            <strong class="<?php echo $book['available_quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $book['available_quantity']; ?>
                            </strong>
                        </td>
                        <td>
                            <?php if ($book['status'] == 'active'): ?>
                                <span class="badge bg-success">Hiển thị</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Đã ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                            <!-- Soft delete logic needed -->
                            <a href="delete.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa/ẩn sách này?')"><i class="fas fa-trash"></i></a>
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
