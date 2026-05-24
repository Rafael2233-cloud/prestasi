<?php
/**
 * ============================================
 * DATABASE CONNECTION - GLOBAL CONFIGURATION
 * ============================================
 */

// Konfigurasi Database (Otomatis deteksi Railway env, kalau gak ada pake lokal)
$host = getenv('MYSQLHOST') ?: "localhost";
$user = getenv('MYSQLUSER') ?: "root";
$pass = getenv('MYSQLPASSWORD') ?: "";
$db   = getenv('MYSQLDATABASE') ?: "db_prestasi_mahasiswa";
$port = getenv('MYSQLPORT') ?: "3306";

// Membuat koneksi menggunakan MySQLi dengan menyertakan port
$conn = new mysqli($host, $user, $pass, $db, $port);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set charset UTF-8 agar mendukung berbagai karakter
$conn->set_charset("utf8");

// Set default timezone untuk sinkronisasi waktu se-Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Define constants for legacy scripts if needed
if (!defined('DB_HOST')) define('DB_HOST', $host);
if (!defined('DB_USER')) define('DB_USER', $user);
if (!defined('DB_PASS')) define('DB_PASS', $pass);
if (!defined('DB_NAME')) define('DB_NAME', $db);
?>