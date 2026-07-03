<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Prepare data for form
// Join with borrow_records to count overdue books
$students_active = $pdo->query("
    SELECT s.*, 
    (SELECT COUNT(*) FROM borrow_records br WHERE br.student_id = s.id AND br.status = 'borrowed' AND br.due_date < CURDATE()) as overdue_count
    FROM students s 
    WHERE s.status = 'active' AND s.card_expiry_date >= CURDATE()
")->fetchAll();
$books_available = $pdo->query("SELECT * FROM books WHERE available_quantity > 0 AND status = 'active'")->fetchAll();

if (isset($_POST['create_borrow'])) {
    $student_id = $_POST['student_id'];
    $book_id = $_POST['book_id'];
    $due_date = $_POST['due_date'];
    $borrow_date = date('Y-m-d');

    // 1. Check borrowing limit (Max 5)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_records WHERE student_id = ? AND status = 'borrowed'");
    $stmt->execute([$student_id]);
    $current_borrows = $stmt->fetchColumn();

    if ($current_borrows >= 5) {
        $error = "Sinh viên này đã mượn tối đa 5 cuốn sách!";
    } else {
        // 2. Check book availability (Double check)
        $stmt = $pdo->prepare("SELECT available_quantity, title FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();

        if ($book['available_quantity'] <= 0) {
            $error = "Sách này vừa hết hàng!";
        } else {
            try {
                $pdo->beginTransaction();

                // Insert into borrow_records
                $sql = "INSERT INTO borrow_records (student_id, book_id, borrow_date, due_date, status, created_by) 
                        VALUES (?, ?, ?, ?, 'borrowed', ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$student_id, $book_id, $borrow_date, $due_date, $_SESSION['user_id']]);

                // Decrease book quantity
                $pdo->prepare("UPDATE books SET available_quantity = available_quantity - 1 WHERE id = ?")->execute([$book_id]);

                // Log
                $pdo->prepare("INSERT INTO system_logs (account_id, action) VALUES (?, ?)")
                    ->execute([$_SESSION['user_id'], "Cho mượn sách: " . $book['title']]);

                $pdo->commit();
                echo "<script>alert('Tạo phiếu mượn thành công!'); window.location.href='index.php';</script>";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Lỗi giao dịch: " . $e->getMessage();
            }
        }
    }
}

// Handle Approval / Rejection
if (isset($_POST['approve_borrow'])) {
    $id = $_POST['borrow_id'];
    $pdo->prepare("UPDATE borrow_records SET status = 'borrowed', created_by = ? WHERE id = ?")
        ->execute([$_SESSION['user_id'], $id]);
    echo "<script>alert('Đã duyệt phiếu mượn!'); window.location.href='index.php';</script>";
}

if (isset($_POST['reject_borrow'])) {
    $id = $_POST['borrow_id'];
    $book_id = $_POST['book_id'];
    
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM borrow_records WHERE id = ?")->execute([$id]);
        $pdo->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE id = ?")->execute([$book_id]);
        $pdo->commit();
        echo "<script>alert('Đã từ chối phiếu mượn!'); window.location.href='index.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Lỗi: " . $e->getMessage() . "');</script>";
    }
}

// Fetch active borrows
$sql = "SELECT br.*, s.mssv, s.fullname, b.title, b.isbn 
        FROM borrow_records br
        JOIN students s ON br.student_id = s.id
        JOIN books b ON br.book_id = b.id
        WHERE br.status = 'borrowed'
        ORDER BY br.id DESC";

$borrows = $pdo->query($sql)->fetchAll();

// Fetch Pending Requests
$sql_pending = "SELECT br.*, s.mssv, s.fullname, b.title, b.isbn 
        FROM borrow_records br
        JOIN students s ON br.student_id = s.id
        JOIN books b ON br.book_id = b.id
        WHERE br.status = 'pending'
        ORDER BY br.id ASC";
$pending_borrows = $pdo->query($sql_pending)->fetchAll();
?>

<div class="row">
    <?php if(count($pending_borrows) > 0): ?>
    <div class="col-12 mb-4">
        <div class="card shadow border-left-warning">
            <div class="card-header bg-warning text-dark font-weight-bold">
                <i class="fas fa-clock"></i> Yêu Cầu Đang Chờ Duyệt (<?php echo count($pending_borrows); ?>)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Sinh Viên</th>
                                <th>Sách Đăng Ký</th>
                                <th>Ngày Đăng Ký</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_borrows as $p): ?>
                            <tr>
                                <td><?php echo $p['mssv'] . ' - ' . $p['fullname']; ?></td>
                                <td><?php echo $p['title']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($p['borrow_date'])); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="borrow_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" name="approve_borrow" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Duyệt
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn từ chối?');">
                                        <input type="hidden" name="borrow_id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="book_id" value="<?php echo $p['book_id']; ?>">
                                        <button type="submit" name="reject_borrow" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times"></i> Từ chối
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">Lập Phiếu Mượn</div>
            <div class="card-body">
                <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Chọn Sinh Viên</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Chọn Sinh Viên --</option>
                            <?php foreach($students_active as $s): ?>
                                <option value="<?php echo $s['id']; ?>" data-overdue="<?php echo $s['overdue_count']; ?>">
                                    <?php echo $s['mssv'] . ' - ' . $s['fullname']; ?> 
                                    <?php if($s['overdue_count'] > 0) echo "(⚠️ $s[overdue_count] quá hạn)"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Chỉ hiện sinh viên thẻ còn hạn.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Chọn Sách</label>
                        <select name="book_id" class="form-select" required>
                            <option value="">-- Chọn Sách --</option>
                            <?php foreach($books_available as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo $b['title'] . ' (Còn: ' . $b['available_quantity'] . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ngày Hẹn Trả</label>
                        <input type="date" name="due_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <button type="submit" name="create_borrow" class="btn btn-primary w-100">Xác Nhận Mượn</button>
                    <a href="export.php" class="btn btn-outline-success w-100 mt-2">Xuất Excel DS Đang Mượn</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Danh Sách Đang Mượn</h6>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>MSSV</th>
                            <th>Sinh Viên</th>
                            <th>Sách</th>
                            <th>Ngày Mượn</th>
                            <th>Hẹn Trả</th>
                            <th>Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($borrows as $b): ?>
                        <tr>
                            <td><?php echo $b['mssv']; ?></td>
                            <td><?php echo htmlspecialchars($b['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($b['title']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($b['borrow_date'])); ?></td>
                            <td>
                                <?php 
                                    echo date('d/m/Y', strtotime($b['due_date'])); 
                                    if(strtotime($b['due_date']) < time()) echo ' <span class="badge bg-danger">Quá hạn</span>';
                                ?>
                            </td>
                            <td><span class="badge bg-warning">Đang mượn</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
document.querySelector('select[name="student_id"]').addEventListener('change', function() {
    let selectedOption = this.options[this.selectedIndex];
    let overdueCount = selectedOption.getAttribute('data-overdue');
    
    if (overdueCount > 0) {
        alert('⚠️ CẢNH BÁO: Sinh viên này đang có ' + overdueCount + ' cuốn sách QUÁ HẠN!\nYêu cầu thu hồi sách trước khi cho mượn tiếp.');
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }
});
</script>
