<?php
require_once 'config/db.php';

if (!isset($_GET['id'])) {
    header("Location: search.php");
    exit();
}

$id = $_GET['id'];

// Fetch Book Details
$sql = "SELECT b.*, c.name as category_name, p.name as publisher_name, p.address as publisher_address
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN publishers p ON b.publisher_id = p.id
        WHERE b.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$book = $stmt->fetch();

// Handle Borrow Registration
$msg = '';
$msg_type = '';

if (isset($_POST['register_borrow'])) {
    $mssv = trim($_POST['mssv']);
    $book_id = $_POST['book_id'];
    
    // Check Student
    $student = $pdo->prepare("SELECT * FROM students WHERE mssv = ?");
    $student->execute([$mssv]);
    $student_info = $student->fetch();

    if (!$student_info) {
        $msg = "Mã sinh viên không tốn tại!";
        $msg_type = "danger";
    } elseif ($student_info['status'] == 'locked') {
        $msg = "Tài khoản sinh viên đang bị khóa!";
        $msg_type = "danger";
    } elseif ($student_info['card_expiry_date'] < date('Y-m-d')) {
        $msg = "Thẻ thư viện đã hết hạn!";
        $msg_type = "danger";
    } else {
        // Check Overdue
        $check_overdue = $pdo->prepare("SELECT COUNT(*) FROM borrow_records WHERE student_id = ? AND status = 'borrowed' AND due_date < CURDATE()");
        $check_overdue->execute([$student_info['id']]);
        if ($check_overdue->fetchColumn() > 0) {
            $msg = "Bạn đang có sách quá hạn, không thể đăng ký thêm!";
            $msg_type = "danger";
        } else {
            // Check Book Quantity
            if ($book['available_quantity'] <= 0) {
                $msg = "Sách đã hết, không thể đăng ký!";
                $msg_type = "danger";
            } else {
                // Create Record
                $borrow_date = date('Y-m-d');
                $due_date = date('Y-m-d', strtotime('+7 days')); // Default 7 days hold
                
                $pdo->prepare("INSERT INTO borrow_records (student_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'pending')")
                    ->execute([$student_info['id'], $book_id, $borrow_date, $due_date]);
                
                // Decree Quantity (Reserve)
                $pdo->prepare("UPDATE books SET available_quantity = available_quantity - 1 WHERE id = ?")->execute([$book_id]);

                $msg = "Đăng ký mượn thành công! Vui lòng đến thư viện nhận sách trong 2 ngày.";
                $msg_type = "success";
                
                // Refresh book data
                $stmt->execute([$id]);
                $book = $stmt->fetch();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - Chi Tiết</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
        
        /* Hero Section */
        .hero-banner {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            padding: 40px 0 80px;
            color: white;
            margin-bottom: -60px; /* Overlap effect */
        }
        .hero-breadcrumb a { color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.9rem; }
        .hero-breadcrumb a:hover { color: white; text-decoration: underline; }
        .hero-breadcrumb span { color: rgba(255,255,255,0.6); font-size: 0.85rem; margin: 0 8px; }

        /* Main Card */
        .main-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 30px;
            margin-bottom: 30px;
            border: none;
        }

        /* Book Cover */
        .book-cover-wrapper {
            position: relative;
            margin-top: -20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .book-cover-lg {
            width: 220px;
            border-radius: 5px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
            transition: transform 0.3s ease;
        }
        .book-cover-lg:hover { transform: translateY(-5px); }

        /* Info Section */
        .book-title { font-weight: 800; color: #2c3e50; margin-bottom: 10px; font-size: 2rem; }
        .book-meta { color: #555; font-size: 1rem; margin-bottom: 20px; }
        .meta-item { display: inline-block; margin-right: 15px; margin-bottom: 5px; }
        .meta-item i { color: #4e73df; margin-right: 5px; }

        /* Summary Box */
        .summary-title { font-weight: 700; color: #4e73df; margin-bottom: 15px; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; display: inline-block; }
        .summary-text { line-height: 1.8; color: #4a5568; font-size: 1.05rem; text-align: justify; }

        /* Specification Table */
        .spec-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .spec-table th { width: 30%; color: #718096; font-weight: 600; padding: 10px 0; border-bottom: 1px solid #edf2f7; vertical-align: top; }
        .spec-table td { color: #2d3748; font-weight: 500; padding: 10px 0; border-bottom: 1px solid #edf2f7; }
        .spec-table tr:last-child th, .spec-table tr:last-child td { border-bottom: none; }

        /* Sidebar */
        .sidebar-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .sidebar-header { font-weight: 700; color: #2d3748; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #edf2f7; font-size: 0.95rem; text-transform: uppercase; }
        .sidebar-list li { margin-bottom: 12px; font-size: 0.95rem; }
        .sidebar-list a { color: #4a5568; text-decoration: none; transition: color 0.2s; display: flex; align-items: center; }
        .sidebar-list a:hover { color: #4e73df; }
        .sidebar-list i { margin-right: 10px; width: 20px; text-align: center; color: #cbd5e0; }
        .sidebar-list a:hover i { color: #4e73df; }

        /* Rating */
        .star-rating i { color: #cbd5e0; font-size: 1.1rem; }
        .star-rating i.active { color: #f6ad55; }
    </style>
</head>
<body>

    <!-- Hero Header -->
    <div class="hero-banner">
        <div class="container">
            <div class="hero-breadcrumb">
                <a href="search.php"><i class="fas fa-arrow-left"></i> Quay lại tìm kiếm</a>
                <span>/</span>
                <span class="text-white-50">Chi tiết tài liệu</span>
            </div>
        </div>
    </div>

    <div class="container mb-5" style="position: relative; z-index: 10;">
        <div class="row">
            <!-- Left Sidebar (Cover + Basic Info) -->
            <div class="col-lg-3 col-md-4">
                <div class="book-cover-wrapper">
                    <?php if (!empty($book['image'])): ?>
                        <img src="/Quanlythuvien/<?php echo $book['image']; ?>" class="book-cover-lg" alt="Cover">
                    <?php else: ?>
                        <div class="bg-white mx-auto d-flex align-items-center justify-content-center book-cover-lg text-muted" style="height: 320px; width: 220px; border: 1px solid #eee;">
                            <i class="fas fa-image fa-3x"></i>
                        </div>
                    <?php endif; ?>
                </div>


                <div class="sidebar-card">
                    <div class="sidebar-header"><i class="fas fa-info-circle me-2"></i> Trạng thái</div>
                     <?php if($book['available_quantity'] > 0): ?>
                        <div class="d-flex align-items-center text-success fw-bold mb-2">
                            <i class="fas fa-check-circle fa-lg me-2"></i> Có sẵn (<?php echo $book['available_quantity']; ?>)
                        </div>
                        <div class="small text-muted mb-3"><i class="fas fa-map-marker-alt me-1"></i> Vị trí: Kệ A1 - Tầng 2</div>
                        <button class="btn btn-primary w-100 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#borrowModal">
                            <i class="fas fa-shopping-basket me-2"></i> Đăng ký mượn
                        </button>
                    <?php else: ?>
                         <div class="d-flex align-items-center text-danger fw-bold mb-2">
                            <i class="fas fa-times-circle fa-lg me-2"></i> Đã hết
                        </div>
                        <div class="small text-muted mb-3">Vui lòng quay lại sau</div>
                        <button class="btn btn-secondary w-100 rounded-pill" disabled>Tạm thời hết sách</button>
                    <?php endif; ?>
                </div>

                 <div class="sidebar-card">
                    <div class="sidebar-header">Liên kết</div>
                    <ul class="list-unstyled sidebar-list w-100">
                        <li><a href="#"><i class="fas fa-qrcode"></i> Mã QR</a></li>
                        <li><a href="#"><i class="fas fa-share-alt"></i> Chia sẻ</a></li>
                        <li><a href="#"><i class="fas fa-print"></i> In phiếu</a></li>
                    </ul>
                </div>
            </div>

            <!-- Right Content -->
            <div class="col-lg-9 col-md-8">
                <?php if($msg): ?>
                    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show">
                        <?php echo $msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="main-card">
                    <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                    
                    <div class="book-meta">
                        <span class="meta-item"><i class="fas fa-user-edit"></i> <?php echo $book['author']; ?></span>
                        <span class="meta-item"><i class="fas fa-building"></i> <?php echo $book['publisher_name']; ?></span>
                        <span class="meta-item"><i class="fas fa-layer-group"></i> <?php echo $book['category_name']; ?></span>
                        
                         <div class="star-rating d-inline-block ms-3">
                            <i class="fas fa-star active"></i>
                            <i class="fas fa-star active"></i>
                            <i class="fas fa-star active"></i>
                            <i class="fas fa-star active"></i>
                            <i class="fas fa-star"></i>
                            <span class="text-muted small ms-1">(4.0)</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="summary-title"><i class="fas fa-align-left me-2"></i> Tóm tắt nội dung</h5>
                            <p class="summary-text">
                                Đây là tài liệu chuyên khảo thuộc lĩnh vực <strong><?php echo $book['category_name']; ?></strong>. 
                                Cuốn sách cung cấp những kiến thức nền tảng và nâng cao, phù hợp cho sinh viên và giảng viên tham khảo.
                                <br><br>
                                Hiện tại hệ thống đang cập nhật tóm tắt chi tiết cho ấn phẩm này. Bạn đọc có thể tham khảo mục lục hoặc đến trực tiếp thư viện để đọc thử.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top">
                        <h5 class="summary-title"><i class="fas fa-list me-2"></i> Thông tin chi tiết</h5>
                        <table class="spec-table">
                            <tr>
                                <th><i class="far fa-id-card me-2"></i> ISBN</th>
                                <td><?php echo $book['isbn']; ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-language me-2"></i> Ngôn ngữ</th>
                                <td>Tiếng Việt</td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-calendar-alt me-2"></i> Năm xuất bản</th>
                                <td>2023 (Dữ liệu mẫu)</td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-ruler-combined me-2"></i> Kích thước</th>
                                <td>16 x 24 cm</td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-file-alt me-2"></i> Số trang</th>
                                <td>300 trang</td>
                            </tr>
                             <tr>
                                <th><i class="fas fa-hashtag me-2"></i> Mã phân loại</th>
                                <td>005.133 (DDC)</td>
                            </tr>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <footer class="bg-white text-center py-4 border-top mt-auto">
        <div class="text-muted small">&copy; 2024 Thư Viện Đại Học ABC - All Rights Reserved.</div>
    </footer>

    <!-- Borrow Modal -->
    <div class="modal fade" id="borrowModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-book-reader me-2"></i> Đăng ký mượn sách</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nhập Mã Sinh Viên (MSSV):</label>
                            <input type="text" name="mssv" class="form-control" placeholder="Ví dụ: SV001" required>
                            <div class="form-text">Vui lòng nhập đúng MSSV để hệ thống xác nhận.</div>
                        </div>
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle"></i> Sách sẽ được giữ trong <strong>2 ngày</strong>. Vui lòng đến thư viện để nhận sách sớm.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" name="register_borrow" class="btn btn-primary">Xác nhận đăng ký</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
