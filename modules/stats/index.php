<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Get Filter Data
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// 1. Summary Stats
$stats = [
    'total_borrowed' => 0,
    'total_returned' => 0,
    'total_overdue' => 0
];

// Total Borrowed in Month
$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_records WHERE MONTH(borrow_date) = ? AND YEAR(borrow_date) = ?");
$stmt->execute([$month, $year]);
$stats['total_borrowed'] = $stmt->fetchColumn();

// Total Returned in Month
$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_records WHERE status = 'returned' AND MONTH(return_date) = ? AND YEAR(return_date) = ?");
$stmt->execute([$month, $year]);
$stats['total_returned'] = $stmt->fetchColumn();

// Overdue Records (Due in this month but returned late OR still not returned)
// Using pure SQL logic for "due in this month"
$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_records WHERE MONTH(due_date) = ? AND YEAR(due_date) = ? AND (status = 'borrowed' OR (status = 'returned' AND return_date > due_date))");
$stmt->execute([$month, $year]);
$stats['total_overdue'] = $stmt->fetchColumn();

// 2. Top 5 Borrowed Books
$top_books = $pdo->prepare("
    SELECT b.title, b.isbn, COUNT(br.id) as borrow_count 
    FROM borrow_records br
    JOIN books b ON br.book_id = b.id
    WHERE MONTH(br.borrow_date) = ? AND YEAR(br.borrow_date) = ?
    GROUP BY b.id
    ORDER BY borrow_count DESC
    LIMIT 5
");
$top_books->execute([$month, $year]);
$top_books_data = $top_books->fetchAll();

// 3. Top 5 Active Students
$top_students = $pdo->prepare("
    SELECT s.mssv, s.fullname, COUNT(br.id) as borrow_count
    FROM borrow_records br
    JOIN students s ON br.student_id = s.id
    WHERE MONTH(br.borrow_date) = ? AND YEAR(br.borrow_date) = ?
    GROUP BY s.id
    ORDER BY borrow_count DESC
    LIMIT 5
");
$top_students->execute([$month, $year]);
$top_students_data = $top_students->fetchAll();

?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Thống Kê Tháng <?php echo "$month/$year"; ?></h1>
    <form class="d-flex align-items-center bg-white p-2 rounded shadow-sm" method="GET">
        <label class="me-2 mb-0">Tháng:</label>
        <select name="month" class="form-select form-select-sm me-2" style="width: auto;">
            <?php for($i=1; $i<=12; $i++): ?>
                <option value="<?php echo $i; ?>" <?php if($i == $month) echo 'selected'; ?>>
                    <?php echo "Tháng $i"; ?>
                </option>
            <?php endfor; ?>
        </select>
        <label class="me-2 mb-0">Năm:</label>
        <select name="year" class="form-select form-select-sm me-2" style="width: auto;">
            <?php for($y=date('Y'); $y>=2020; $y--): ?>
                <option value="<?php echo $y; ?>" <?php if($y == $year) echo 'selected'; ?>>
                    <?php echo $y; ?>
                </option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Xem</button>
    </form>
</div>

<!-- Send Stats Cards -->
<div class="row">
    <!-- Borrowed -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Lượt Mượn Mới</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_borrowed']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book-reader fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Returned -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Lượt Trả Sách</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_returned']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Vi Phạm / Quá Hạn</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_overdue']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Top Books -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Top 5 Sách Mượn Nhiều Nhất</h6>
            </div>
            <div class="card-body">
                <?php if(count($top_books_data) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tên Sách</th>
                                    <th>Lượt Mượn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_books_data as $b): ?>
                                <tr>
                                    <td>
                                        <div class="font-weight-bold text-primary"><?php echo htmlspecialchars($b['title']); ?></div>
                                        <div class="small text-muted"><?php echo $b['isbn']; ?></div>
                                    </td>
                                    <td class="text-center font-weight-bold"><?php echo $b['borrow_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted py-3">Chưa có dữ liệu mượn trong tháng này.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Students -->
     <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success">Top 5 Sinh Viên Tích Cực</h6>
            </div>
            <div class="card-body">
                <?php if(count($top_students_data) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Sinh Viên</th>
                                    <th>Số Lần Mượn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_students_data as $s): ?>
                                <tr>
                                    <td>
                                        <div><?php echo htmlspecialchars($s['fullname']); ?></div>
                                        <div class="small text-muted"><?php echo $s['mssv']; ?></div>
                                    </td>
                                    <td class="text-center font-weight-bold"><?php echo $s['borrow_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted py-3">Chưa có dữ liệu trong tháng này.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
