<?php
session_start();
header("Location: ../../index.php");
if (isset($_SESSION['user_id'])) {
    // We could log logout here if we want, but session might be tricky if destroyed first.
    // For simplicity, just destroy.
    session_unset();
    session_destroy();
}
exit();
?>
