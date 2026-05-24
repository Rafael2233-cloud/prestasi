<?php
require '../koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_aplikasi') {
        $nama_sistem = trim($_POST['nama_sistem'] ?? '');
        $tahun_akademik = trim($_POST['tahun_akademik'] ?? '');

        if (empty($nama_sistem)) {
            echo json_encode(['success' => false, 'message' => 'Nama Sistem tidak boleh kosong']);
            exit;
        }

        $nama_sistem_safe = $conn->real_escape_string($nama_sistem);
        $tahun_akademik_safe = $conn->real_escape_string($tahun_akademik);

        // Periksa apakah record sudah ada
        $check = $conn->query("SELECT id FROM pengaturan LIMIT 1");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE pengaturan SET nama_sistem='$nama_sistem_safe', tahun_akademik='$tahun_akademik_safe'");
        } else {
            $conn->query("INSERT INTO pengaturan (nama_sistem, tahun_akademik) VALUES ('$nama_sistem_safe', '$tahun_akademik_safe')");
        }

        echo json_encode(['success' => true, 'message' => 'Pengaturan aplikasi berhasil diperbarui']);
        exit;
    }

    if ($_POST['action'] === 'update_account') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Username tidak boleh kosong']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
            exit;
        }

        // Cek apakah username sudah dipakai akun lain (kecuali admin ini sendiri)
        $username_safe = $conn->real_escape_string($username);
        $email_safe = $conn->real_escape_string($email);

        $q_check = $conn->query("SELECT id FROM admin WHERE username='$username_safe' AND id != 1");
        if ($q_check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
            exit;
        }

        $conn->query("UPDATE admin SET username='$username_safe', email='$email_safe'");
        echo json_encode(['success' => true, 'message' => 'Username/Email berhasil diperbarui']);
        exit;
    }

    if ($_POST['action'] === 'update_profile') {
        $nama = trim($_POST['nama'] ?? '');
        $nip = trim($_POST['nip'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($nama)) {
            echo json_encode(['success' => false, 'message' => 'Nama tidak boleh kosong']);
            exit;
        }
        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Username tidak boleh kosong']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
            exit;
        }

        $nama_safe = $conn->real_escape_string($nama);
        $nip_safe = $conn->real_escape_string($nip);
        $username_safe = $conn->real_escape_string($username);
        $email_safe = $conn->real_escape_string($email);

        // Check if username is taken by other admins
        $q_check = $conn->query("SELECT id FROM admin WHERE username='$username_safe' AND id != 1");
        if ($q_check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
            exit;
        }

        $conn->query("UPDATE admin SET nama='$nama_safe', nip='$nip_safe', username='$username_safe', email='$email_safe'");
        
        session_start();
        $_SESSION['admin_nama'] = $nama;
        $_SESSION['admin_nip'] = $nip;

        echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui']);
        exit;
    }

    if ($_POST['action'] === 'update_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (strlen($new_pass) < 8) {
            echo json_encode(['success' => false, 'message' => 'Password baru minimal 8 karakter']);
            exit;
        }

        if ($new_pass !== $confirm_pass) {
            echo json_encode(['success' => false, 'message' => 'Konfirmasi password tidak cocok']);
            exit;
        }

        // Cek password lama
        $q = $conn->query("SELECT password FROM admin LIMIT 1");
        if ($q->num_rows > 0) {
            $row = $q->fetch_assoc();
            if (md5($old_pass) !== $row['password']) {
                echo json_encode(['success' => false, 'message' => 'Password lama tidak sesuai']);
                exit;
            }

            // Update password
            $new_hash = md5($new_pass);
            $conn->query("UPDATE admin SET password='$new_hash'");
            echo json_encode(['success' => true, 'message' => 'Password berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Akun admin tidak ditemukan']);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
