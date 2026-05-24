<?php
/**
 * ============================================
 * DATABASE CONNECTION - GLOBAL CONFIGURATION
 * ============================================
 * File ini merupakan konfigurasi koneksi database terpusat
 * untuk seluruh role: Admin, Pimpinan, dan Mahasiswa.
 */

// Konfigurasi Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_prestasi_mahasiswa";

// Membuat koneksi menggunakan MySQLi
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set charset UTF-8 agar mendukung berbagai karakter
$conn->set_charset("utf8");

// Set default timezone untuk sinkronisasi waktu se-Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Define constants for legacy scripts if needed (optional, for backwards compatibility)
if (!defined('DB_HOST')) define('DB_HOST', $host);
if (!defined('DB_USER')) define('DB_USER', $user);
if (!defined('DB_PASS')) define('DB_PASS', $pass);
if (!defined('DB_NAME')) define('DB_NAME', $db);
?>
