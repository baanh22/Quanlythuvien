<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Prepare Return (Show Confirm Form)
$confirm_return = null;
if (isset($_GET['confirm'])) {
    $borrow_id = $_GET['confirm'];
    $stmt = $pdo->prepare("SELECT br.*, s.fullname, b.title, b.isbn, DATEDIFF(CURDATE(), br.due_date) as days_overdue
                           FROM borrow_records br
                           JOIN students s ON br.student_id = s.id
                           JOIN books b ON br.book_id = b.id
                           WHERE br.id = ? AND br.status = 'borrowed'");
    $stmt->execute([$borrow_id]);
    $confirm_return = $stmt->fetch();
}

// Process Return
if (isset($_POST['process_return'])) {
    $id = $_POST['borrow_id'];
    $book_id = $_POST['book_id'];
    $fine_amount = $_POST['fine_amount'];
    $fine_reason = $_POST['fine_reason'];
    $return_date = date('Y-m-d');

    try {
        $pdo->beginTransaction();

        // Update Borrow Record
        $pdo->prepare("UPDATE borrow_records SET return_date = ?, status = 'returned' WHERE id = ?")
            ->execute([$return_date, $id]);
        
        // Increase Book Quantity
        $pdo->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE id = ?")
            ->execute([$book_id]);

        // Insert Fine if any
        if ($fine_amount > 0) {
            $pdo->prepare("INSERT INTO fines (borrow_record_id, amount, reason) VALUES (?, ?, ?)")
                ->execute([$id, $fine_amount, $fine_reason]);
        }

        // Log
        $pdo->prepare("INSERT INTO system_logs (account_id, action) VALUES (?, ?)")
            ->execute([$_SESSION['user_id'], "Nhận trả sách ID: $book_id. Phạt: $fine_amount"]);

        $pdo->commit();
        echo "<script>alert('Trả sách thành công!'); window.location.href='index.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Search for active borrows to return
$search = $_GET['search'] ?? '';
$borrows = [];
if ($search) {
    $sql = "SELECT br.*, s.mssv, s.fullname, b.title 
            FROM borrow_records br
            JOIN students s ON br.student_id = s.id
            JOIN books b ON br.book_id = b.id
            WHERE br.status = 'borrowed' AND (s.mssv LIKE ? OR s.fullname LIKE ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$search%", "%$search%"]);
    $borrows = $stmt->fetchAll();
}
?>

<h3><i class="fas fa-undo"></i> Trả Sách & Phạt</h3>

<div class="card shadow mb-4">
    <div class="card-body">
        <form class="d-flex w-50" method="GET">
            <input type="text" name="search" class="form-control me-2" placeholder="Nhập MSSV hoặc Tên SV để tìm..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary">Tìm Kiếm</button>
        </form>
    </div>
</div>

<?php if ($confirm_return): ?>
<div class="card shadow border-left-warning mb-4">
    <div class="card-header bg-warning text-dark">Xác Nhận Trả Sách</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="borrow_id" value="<?php echo $confirm_return['id']; ?>">
            <input type="hidden" name="book_id" value="<?php echo $confirm_return['book_id']; ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Sinh Viên:</strong> <?php echo $confirm_return['fullname']; ?></p>
                    <p><strong>Sách:</strong> <?php echo $confirm_return['title']; ?> (<?php echo $confirm_return['isbn']; ?>)</p>
                    <p><strong>Ngày Hẹn Trả:</strong> <?php echo $confirm_return['due_date']; ?></p>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Số ngày quá hạn</label>
                        <input type="text" class="form-control" value="<?php echo max(0, $confirm_return['days_overdue']); ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tiền Phạt (VND)</label>
                        <input type="number" name="fine_amount" class="form-control" value="<?php echo max(0, $confirm_return['days_overdue'] * 5000); ?>">
                        <small class="text-muted">Gợi ý: 5,000đ / ngày quá hạn</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Lý do phạt / Ghi chú</label>
                        <input type="text" name="fine_reason" class="form-control" value="<?php echo $confirm_return['days_overdue'] > 0 ? 'Quá hạn ' . $confirm_return['days_overdue'] . ' ngày' : ''; ?>">
                    </div>
                </div>
            </div>
            
            <button type="submit" name="process_return" class="btn btn-success btn-lg">Hoàn Tất Trả Sách</button>
            <a href="index.php" class="btn btn-secondary btn-lg">Hủy</a>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($borrows)): ?>
<div class="card shadow">
    <div class="card-header">Kết quả tìm kiếm</div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>MSSV</th>
                    <th>Sinh Viên</th>
                    <th>Sách</th>
                    <th>Ngày Mượn</th>
                    <th>Hẹn Trả</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($borrows as $b): ?>
                <tr>
                    <td><?php echo $b['mssv']; ?></td>
                    <td><?php echo htmlspecialchars($b['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($b['title']); ?></td>
                    <td><?php echo $b['borrow_date']; ?></td>
                    <td>
                        <?php 
                             echo $b['due_date']; 
                             if(strtotime($b['due_date']) < time()) echo ' <i class="fas fa-exclamation-circle text-danger"></i>';
                        ?>
                    </td>
                    <td>
                        <a href="index.php?confirm=<?php echo $b['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-check"></i> Trả Sách
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
