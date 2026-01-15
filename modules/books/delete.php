<?php
require_once '../../config/db.php';
session_start();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // Soft delete prefered or hard delete if no deps? 
    // Reqs say "Delete (Hide)", so soft delete.
    $stmt = $pdo->prepare("UPDATE books SET status = 'hidden' WHERE id = ?");
    $stmt->execute([$id]);
}
header('Location: index.php');
exit();
?>
