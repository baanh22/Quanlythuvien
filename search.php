<?php
require_once 'config/db.php';

// 1. Fetch Categories with Item Counts
$categories = $pdo->query("
    SELECT c.id, c.name, COUNT(b.id) as book_count 
    FROM categories c 
    LEFT JOIN books b ON c.id = b.category_id 
    GROUP BY c.id, c.name
")->fetchAll();

// 2. Search Logic
$search = $_GET['search'] ?? '';
$cat_filter = $_GET['cat'] ?? '';

$sql = "SELECT b.*, c.name as category_name, p.name as publisher_name, a.name as author_name
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN publishers p ON b.publisher_id = p.id
        LEFT JOIN authors a ON b.author_id = a.id
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
    <title>Thư Viện Sách - Tra Cứu</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Top Navigation */
        .navbar-custom { background: #007bff; padding: 10px 0; }
        .navbar-brand { color: white; font-weight: bold; font-size: 1.5rem; }
        .navbar-brand:hover { color:white; }
        .nav-link { color: rgba(255,255,255,0.9); }
        .nav-link:hover { color: white; }
        .search-box { border-radius: 20px 0 0 20px; border: none; }
        .btn-search { border-radius: 0 20px 20px 0; background: #28a745; color: white; font-weight: bold; border: none; }
        
        /* Banner Section */
        .banner-section { 
            background: #6c757d; 
            color: white; 
            text-align: center; 
            padding: 60px 0; 
            margin-bottom: 30px; 
            border-radius: 0 0 5px 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Sidebar */
        .categories-card { border: none; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .cat-header { background: #007bff; color: white; padding: 15px; font-weight: bold; border-radius: 5px 5px 0 0; }
        .cat-list { list-style: none; padding: 0; margin: 0; }
        .cat-item { border-bottom: 1px solid #f1f1f1; }
        .cat-item:last-child { border-bottom: none; }
        .cat-link { 
            display: flex; 
            justify-content: space-between; 
            padding: 12px 15px; 
            color: #555; 
            text-decoration: none; 
            transition: all 0.2s; 
            background: white;
        }
        .cat-link:hover { background: #f8f9fa; color: #007bff; padding-left: 20px; }
        .cat-link.active { color: #007bff; font-weight: 600; background: #f0f8ff; }
        .badge-count { background: #007bff; color: white; border-radius: 50%; padding: 2px 8px; font-size: 0.8rem; }

        /* Main Content */
        .section-title { font-size: 1.2rem; font-weight: 700; color: #333; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        
        /* Book Grid Cards */
        .book-card { 
            border: none; 
            border-radius: 8px; 
            overflow: hidden; 
            background: white; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
            transition: transform 0.2s; 
            height: 100%;
        }
        .book-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.15); }
        .book-img-wrapper { position: relative; padding-top: 130%; overflow: hidden; background: #eee; }
        .book-img { 
            position: absolute; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            object-fit: cover; 
            transition: transform 0.3s;
        }
        .book-card-body { padding: 15px; }
        .book-title { font-weight: 700; color: #333; margin-bottom: 5px; font-size: 1rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 3rem; }
        .book-author { color: #777; font-size: 0.9rem; margin-bottom: 8px; }
        .book-cat-badge { font-size: 0.75rem; background: #007bff; color: white; padding: 2px 8px; border-radius: 4px; display: inline-block; margin-bottom: 10px; }
        
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <header class="navbar-custom">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="search.php" class="navbar-brand"><i class="fas fa-book-open"></i> Thư Viện Sách</a>
            <div class="d-flex align-items-center">
                <a href="search.php" class="nav-link me-3 text-white">Trang chủ</a>
                <a href="index.php" class="nav-link text-white"><i class="fas fa-user-lock"></i> Quản lý</a>
            </div>
            
            <form class="d-flex" style="width: 300px;" method="GET">
                <input class="form-control search-box" type="search" name="search" placeholder="Tìm kiếm sách..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-search" type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </header>

    <div class="container">
        <!-- Banner Slider (Placeholder) -->
        <div class="banner-section text-center">
            <h2 class="fw-bold">Sách mới ra mắt</h2>
            <p>Cập nhật những đầu sách mới nhất</p>
            <div class="mt-3">
               <span style="display:inline-block; width: 30px; height: 4px; background: rgba(255,255,255,0.5); margin: 0 2px;"></span>
               <span style="display:inline-block; width: 30px; height: 4px; background: white; margin: 0 2px;"></span>
               <span style="display:inline-block; width: 30px; height: 4px; background: rgba(255,255,255,0.5); margin: 0 2px;"></span>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar: Categories -->
            <div class="col-lg-3 mb-4">
                <div class="categories-card rounded bg-white">
                    <div class="cat-header d-flex justify-content-between align-items-center">
                        <span>Danh mục sách</span>
                        <a href="search.php" class="text-white small" style="text-decoration: underline;"><i class="fas fa-list"></i> Tất cả</a>
                    </div>
                    <ul class="cat-list">
                        <?php foreach($categories as $c): ?>
                            <li class="cat-item">
                                <a href="search.php?cat=<?php echo $c['id']; ?>" class="cat-link <?php echo $cat_filter == $c['id'] ? 'active' : ''; ?>">
                                    <span><?php echo htmlspecialchars($c['name']); ?></span>
                                    <span class="badge-count"><?php echo $c['book_count']; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Main Content: Book Grid -->
            <div class="col-lg-9">
                <h4 class="section-title">Sách mới nhất</h4>
                
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
                    <?php if(count($books) > 0): ?>
                        <?php foreach($books as $book): ?>
                        <div class="col">
                            <div class="book-card h-100 position-relative">
                                <!-- Image -->
                                <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="book-img-wrapper d-block">
                                    <?php if (!empty($book['image'])): ?>
                                        <img src="/Quanlythuvien/<?php echo $book['image']; ?>" class="book-img" alt="<?php echo $book['title']; ?>">
                                    <?php else: ?>
                                        <div class="book-img d-flex align-items-center justify-content-center bg-light text-muted">
                                            <i class="fas fa-image fa-3x"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>

                                <div class="book-card-body">
                                    <h5 class="book-title" title="<?php echo htmlspecialchars($book['title']); ?>">
                                        <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($book['title']); ?>
                                        </a>
                                    </h5>
                                    
                                    <div class="book-author">
                                        <?php echo $book['author_name'] ?? $book['author']; ?>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                         <span class="book-cat-badge"><?php echo $book['category_name']; ?></span>
                                    </div>
                                    
                                    <div class="mt-3 d-grid">
                                        <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                                            Xem chi tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 py-5 text-center text-muted">
                            <i class="fas fa-search fa-3x mb-3 text-secondary"></i>
                            <h5>Không có sách nào trong danh mục này.</h5>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <footer class="bg-white text-center py-4 border-top mt-5">
        <div class="text-muted small">&copy; 2024 Thư Viện Sách - All Rights Reserved.</div>
    </footer>

</body>
</html>
