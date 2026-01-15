<?php
require_once '../../config/db.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="bao_cao_tra_sach_phat.xls"');
header('Cache-Control: max-age=0');

// Fetch returned records with optional fines
$sql = "SELECT br.*, s.mssv, s.fullname, b.title, f.amount, f.reason
        FROM borrow_records br
        JOIN students s ON br.student_id = s.id
        JOIN books b ON br.book_id = b.id
        LEFT JOIN fines f ON br.id = f.borrow_record_id
        WHERE br.status = 'returned'
        ORDER BY br.return_date DESC";
$rows = $pdo->query($sql)->fetchAll();

echo '<table border="1">';
echo '<tr>
        <th>ID</th>
        <th>MSSV</th>
        <th>Sinh Viên</th>
        <th>Sách</th>
        <th>Ngày Mượn</th>
        <th>Hẹn Trả</th>
        <th>Ngày Trả</th>
        <th>Tiền Phạt</th>
        <th>Lý Do</th>
      </tr>';

foreach($rows as $r) {
    echo "<tr>
            <td>{$r['id']}</td>
            <td>{$r['mssv']}</td>
            <td>{$r['fullname']}</td>
            <td>{$r['title']}</td>
            <td>{$r['borrow_date']}</td>
            <td>{$r['due_date']}</td>
            <td>{$r['return_date']}</td>
            <td>". ($r['amount'] ? number_format($r['amount']) : '0') ."</td>
            <td>{$r['reason']}</td>
          </tr>";
}
echo '</table>';
exit();
?>
