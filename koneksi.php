<?php
// Konfigurasi Database (Otomatis deteksi Railway env, kalau gak ada pake lokal)
$host = getenv('MYSQLHOST') ?: "localhost";
$user = getenv('MYSQLUSER') ?: "root";
$pass = getenv('MYSQLPASSWORD') ?: "";
$db   = getenv('MYSQLDATABASE') ?: "db_prestasi_mahasiswa";
$port = getenv('MYSQLPORT') ?: "3306";

// Membuat koneksi menggunakan MySQLi dengan menyertakan port
$conn = new mysqli($host, $user, $pass, $db, $port);
