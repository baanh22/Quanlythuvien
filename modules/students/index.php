<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Add Student
if (isset($_POST['add_student'])) {
    $mssv = $_POST['mssv'];
    $fullname = $_POST['fullname'];
    $expiry = $_POST['expiry'];
    
    // Check duplication
    $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE mssv = ?");
    $check->execute([$mssv]);
    if ($check->fetchColumn() > 0) {
        $error = "Mã số sinh viên đã tồn tại.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO students (mssv, fullname, card_expiry_date) VALUES (?, ?, ?)");
        $stmt->execute([$mssv, $fullname, $expiry]);
        echo "<script>window.location.href='index.php';</script>";
    }
}

// Toggle Lock
if (isset($_GET['lock'])) {
    $id = $_GET['lock'];
    $pdo->prepare("UPDATE students SET status = IF(status='active', 'locked', 'active') WHERE id = ?")->execute([$id]);
    echo "<script>window.location.href='index.php';</script>";
}

// Renew Card
if (isset($_POST['renew_card'])) {
    $id = $_POST['student_id'];
    $new_expiry = $_POST['new_expiry'];
    $pdo->prepare("UPDATE students SET card_expiry_date = ? WHERE id = ?")->execute([$new_expiry, $id]);
    echo "<script>alert('Gia hạn thẻ thành công!'); window.location.href='index.php';</script>";
}

// Fetch Students
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM students WHERE fullname LIKE ? OR mssv LIKE ? ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%"]);
$students = $stmt->fetchAll();
?>

<h3><i class="fas fa-users"></i> Quản Lý Sinh Viên</h3>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header bg-success text-white">Thêm Sinh Viên Mới</div>
            <div class="card-body">
                <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">MSSV <span class="text-danger">*</span></label>
                        <input type="text" name="mssv" class="form-control" required pattern="[A-Za-z0-9]+" title="Chỉ gồm chữ và số">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Họ Tên</label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ngày Hết Hạn Thẻ</label>
                        <input type="date" name="expiry" class="form-control" required>
                    </div>
                    <button type="submit" name="add_student" class="btn btn-success w-100">Lưu Thông Tin</button>
                    <a href="export.php" class="btn btn-outline-success w-100 mt-2">Xuất Excel DS</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header">
                <form class="d-flex" method="GET">
                    <input type="text" name="search" class="form-control me-2" placeholder="Tìm theo tên hoặc MSSV..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-secondary">Tìm</button>
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>MSSV</th>
                            <th>Họ Tên</th>
                            <th>Hết Hạn</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $s): ?>
                        <tr>
                            <td><?php echo $s['mssv']; ?></td>
                            <td><?php echo htmlspecialchars($s['fullname']); ?></td>
                            <td>
                                <?php 
                                    echo date('d/m/Y', strtotime($s['card_expiry_date']));
                                    if(strtotime($s['card_expiry_date']) < time()) echo ' <span class="badge bg-danger">Hết hạn</span>';
                                ?>
                            </td>
                            <td>
                                <?php if($s['status'] == 'active'): ?>
                                    <span class="badge bg-success">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Bị khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="index.php?lock=<?php echo $s['id']; ?>" class="btn btn-sm btn-<?php echo $s['status'] == 'active' ? 'warning' : 'info'; ?>">
                                    <i class="fas fa-<?php echo $s['status'] == 'active' ? 'lock' : 'unlock'; ?>"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#renewModal" 
                                    onclick="openRenewModal(<?php echo $s['id']; ?>, '<?php echo $s['fullname']; ?>', '<?php echo $s['card_expiry_date']; ?>')">
                                    <i class="fas fa-calendar-plus"></i>
                                </button>
                                <!-- Edit could go here -->
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

<!-- Renew Modal -->
<div class="modal fade" id="renewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gia Hạn Thẻ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="student_id" id="renew_student_id">
                    <div class="mb-3">
                        <label class="form-label">Sinh viên:</label>
                        <input type="text" class="form-control" id="renew_fullname" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ngày hết hạn mới:</label>
                        <input type="date" name="new_expiry" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="renew_card" class="btn btn-primary">Xác nhận gia hạn</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRenewModal(id, name, currentExpiry) {
    document.getElementById('renew_student_id').value = id;
    document.getElementById('renew_fullname').value = name;
}
</script>
