<?php
require_once '../../config/db.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="phieu_muon_dang_hoat_dong.xls"');
header('Cache-Control: max-age=0');

$sql = "SELECT br.*, s.mssv, s.fullname, b.title 
        FROM borrow_records br
        JOIN students s ON br.student_id = s.id
        JOIN books b ON br.book_id = b.id
        WHERE br.status = 'borrowed'";
$rows = $pdo->query($sql)->fetchAll();

echo '<table border="1">';
echo '<tr><th>ID</th><th>MSSV</th><th>Sinh Viên</th><th>Sách</th><th>Ngày Mượn</th><th>Hẹn Trả</th></tr>';
foreach($rows as $r) {
    echo "<tr>
            <td>{$r['id']}</td>
            <td>{$r['mssv']}</td>
            <td>{$r['fullname']}</td>
            <td>{$r['title']}</td>
            <td>{$r['borrow_date']}</td>
            <td>{$r['due_date']}</td>
          </tr>";
}
echo '</table>';
exit();
?>
