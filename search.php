<?php
require_once 'config/db.php';

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Search Logic
$search = $_GET['search'] ?? '';
$cat_filter = $_GET['cat'] ?? '';

$sql = "SELECT b.*, c.name as category_name, p.name as publisher_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN publishers p ON b.publisher_id = p.id
        WHERE b.status = 'active' AND (b.title LIKE ? OR b.isbn LIKE ?)";
$params = ["%$search%", "%$search%"];

if (!empty($cat_filter)) {
    $sql .= " AND b.category_id = ?";
    $params[] = $cat_filter;
}

$sql .= " ORDER BY b.id DESC LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra Cứu Sách - Thư Viện</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .book-cover { height: 150px; object-fit: cover; width: 100px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .hero { background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); color: white; padding: 60px 0; margin-bottom: 30px; }
    </style>
</head>
<body>

    <div class="hero text-center">
        <div class="container">
            <h1 class="display-4"><i class="fas fa-book-reader"></i> Tra Cứu Thư Viện</h1>
            <p class="lead">Tìm kiếm sách, kiểm tra tình trạng nhanh chóng</p>
            
            <form class="row justify-content-center mt-4" method="GET">
                <div class="col-md-6">
                    <div class="input-group mb-3">
                        <select name="cat" class="form-select" style="max-width: 150px;">
                            <option value="">- Danh mục -</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $cat_filter == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo $c['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="search" class="form-control" placeholder="Nhập tên sách, ISBN..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-warning fw-bold" type="submit"><i class="fas fa-search"></i> Tìm kiếm</button>
                    </div>
                </div>
            </form>
            <a href="index.php" class="text-white text-decoration-underline">Đăng nhập Quản trị viên</a>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <?php if(count($books) > 0): ?>
                <?php foreach($books as $book): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <?php if (!empty($book['image'])): ?>
                                        <a href="book_detail.php?id=<?php echo $book['id']; ?>">
                                            <img src="/Quanlythuvien/<?php echo $book['image']; ?>" class="book-cover" alt="Cover">
                                        </a>
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center book-cover text-muted">
                                            <i class="fas fa-image fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title">
                                        <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="text-decoration-none text-primary fw-bold">
                                            <?php echo htmlspecialchars($book['title']); ?>
                                        </a>
                                    </h5>
                                    <h6 class="card-subtitle mb-2 text-muted"><?php echo $book['author']; ?></h6>
                                    <p class="card-text small mb-2">
                                        <strong>ISBN:</strong> <?php echo $book['isbn']; ?> | 
                                        <strong>NXB:</strong> <?php echo $book['publisher_name']; ?> |
                                        <strong>Danh mục:</strong> <?php echo $book['category_name']; ?>
                                    </p>
                                    
                                    <div class="mt-3">
                                        <?php if($book['available_quantity'] > 0): ?>
                                            <span class="badge bg-success p-2"><i class="fas fa-check-circle"></i> Có sẵn (<?php echo $book['available_quantity']; ?>)</span>
                                            <small class="text-muted ms-2">Vị trí: Kệ A1 (Demo)</small>
                                        <?php else: ?>
                                            <span class="badge bg-danger p-2"><i class="fas fa-times-circle"></i> Đã hết</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <h4>Không tìm thấy sách nào phù hợp</h4>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-light text-center py-3 border-top mt-auto">
        <small class="text-muted">&copy; 2024 Thư Viện Đại Học ABC</small>
    </footer>

</body>
</html>
