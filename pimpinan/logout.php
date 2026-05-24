<?php
session_start();

// Hapus semua data session
session_unset();
session_destroy();

// Arahkan kembali ke halaman login (index.php)
header("Location: index.php");
exit;
?>
