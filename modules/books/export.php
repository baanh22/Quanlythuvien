<?php
require_once '../../config/db.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="danh_sach_sach.xls"');
header('Cache-Control: max-age=0');

$sql = "SELECT b.*, c.name as category_name, p.name as publisher_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN publishers p ON b.publisher_id = p.id";
$books = $pdo->query($sql)->fetchAll();

// Output simplified HTML table for Excel
echo '<table border="1">';
echo '<tr>
        <th>ID</th>
        <th>ISBN</th>
        <th>Tên Sách</th>
        <th>Tác Giả</th>
        <th>Danh Mục</th>
        <th>NXB</th>
        <th>Tổng SL</th>
        <th>Tồn Kho</th>
        <th>Trạng Thái</th>
      </tr>';

foreach($books as $book) {
    echo '<tr>';
    echo '<td>'.$book['id'].'</td>';
    echo '<td>`'.$book['isbn'].'</td>'; // tick to force string
    echo '<td>'.$book['title'].'</td>';
    echo '<td>'.$book['author'].'</td>';
    echo '<td>'.$book['category_name'].'</td>';
    echo '<td>'.$book['publisher_name'].'</td>';
    echo '<td>'.$book['total_quantity'].'</td>';
    echo '<td>'.$book['available_quantity'].'</td>';
    echo '<td>'.$book['status'].'</td>';
    echo '</tr>';
}
echo '</table>';
exit();
?>
