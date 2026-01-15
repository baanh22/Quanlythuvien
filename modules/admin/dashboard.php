<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Fetch Statistics
$stats = [];
$stats['books'] = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$stats['students'] = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$stats['borrowed'] = $pdo->query("SELECT COUNT(*) FROM borrow_records WHERE status='borrowed'")->fetchColumn();
$stats['overdue'] = $pdo->query("SELECT COUNT(*) FROM borrow_records WHERE status='borrowed' AND due_date < CURDATE()")->fetchColumn();

// Recent logs
$logs = $pdo->query("SELECT l.*, a.fullname FROM system_logs l LEFT JOIN accounts a ON l.account_id = a.id ORDER BY l.created_at DESC LIMIT 5")->fetchAll();

// Monthly Stats for Chart
$monthly_stats = array_fill(1, 12, 0); // Init 1-12 with 0
$stmt = $pdo->query("SELECT MONTH(borrow_date) as m, COUNT(*) as c FROM borrow_records WHERE YEAR(borrow_date) = YEAR(CURDATE()) GROUP BY m");
while ($row = $stmt->fetch()) {
    $monthly_stats[$row['m']] = $row['c'];
}
$chart_data = json_encode(array_values($monthly_stats));
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card-counter primary">
            <i class="fas fa-book"></i>
            <div class="stats-info">
                <span class="count-numbers"><?php echo $stats['books']; ?></span>
                <span class="count-name">Đầu Sách</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-counter success">
            <i class="fas fa-users"></i>
            <div class="stats-info">
                <span class="count-numbers"><?php echo $stats['students']; ?></span>
                <span class="count-name">Sinh Viên</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-counter info">
            <i class="fas fa-exchange-alt"></i>
            <div class="stats-info">
                <span class="count-numbers"><?php echo $stats['borrowed']; ?></span>
                <span class="count-name">Đang Mượn</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-counter danger">
            <i class="fas fa-clock"></i>
            <div class="stats-info">
                <span class="count-numbers"><?php echo $stats['overdue']; ?></span>
                <span class="count-name">Đang Quá Hạn</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Biểu Đồ Mượn Trả (Demo)</h6>
            </div>
            <div class="card-body">
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Hoạt Động Gần Đây</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach($logs as $log): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted"><?php echo $log['created_at']; ?></small><br>
                            <strong><?php echo htmlspecialchars($log['fullname']); ?></strong>: 
                            <?php echo htmlspecialchars($log['action']); ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('myChart').getContext('2d');
const myChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
        datasets: [{
            label: 'Lượt mượn năm ' + new Date().getFullYear(),
            data: <?php echo $chart_data; ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        maintainAspectRatio: false,
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
