<?php
require_once '../../config/db.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="danh_sach_sinh_vien.xls"');
header('Cache-Control: max-age=0');

$students = $pdo->query("SELECT * FROM students")->fetchAll();

echo '<table border="1">';
echo '<tr>
        <th>ID</th>
        <th>MSSV</th>
        <th>Họ Tên</th>
        <th>Email</th>
        <th>SĐT</th>
        <th>Ngày Hết Hạn</th>
        <th>Trạng Thái</th>
      </tr>';

foreach($students as $s) {
    echo '<tr>';
    echo '<td>'.$s['id'].'</td>';
    echo '<td>'.$s['mssv'].'</td>';
    echo '<td>'.$s['fullname'].'</td>';
    echo '<td>'.$s['email'].'</td>';
    echo '<td>'.$s['phone'].'</td>';
    echo '<td>'.$s['card_expiry_date'].'</td>';
    echo '<td>'.$s['status'].'</td>';
    echo '</tr>';
}
echo '</table>';
exit();
?>
