<?php
session_start();
require '../koneksi.php';

// Validasi session
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $pimpinan_id = $_SESSION['pimpinan_id'] ?? null;
    $username_session = $_SESSION['username'] ?? '';
    
    if (!$pimpinan_id) {
        $q_get = $conn->query("SELECT id FROM pimpinan WHERE username='$username_session'");
        if ($q_get && $q_get->num_rows > 0) {
            $pimpinan_id = $q_get->fetch_assoc()['id'];
            $_SESSION['pimpinan_id'] = $pimpinan_id;
        }
    }

    if (!$pimpinan_id) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit;
    }

    if ($_POST['action'] === 'update_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (strlen($new_pass) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter']);
            exit;
        }

        if ($new_pass !== $confirm_pass) {
            echo json_encode(['success' => false, 'message' => 'Konfirmasi password tidak cocok']);
            exit;
        }

        $hashed_old = md5($old_pass);
        $q_check = $conn->query("SELECT password FROM pimpinan WHERE id=$pimpinan_id");
        $curr_pass = $q_check->fetch_assoc()['password'];
        
        if ($curr_pass !== $hashed_old) {
            echo json_encode(['success' => false, 'message' => 'Password lama tidak sesuai']);
            exit;
        }

        $hashed_new = md5($new_pass);
        if ($conn->query("UPDATE pimpinan SET password='$hashed_new' WHERE id=$pimpinan_id")) {
            echo json_encode(['success' => true, 'message' => 'Password berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan password: ' . $conn->error]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'update_profile') {
        $nip = $conn->real_escape_string(trim($_POST['nip'] ?? ''));
        $nama = $conn->real_escape_string(trim($_POST['nama'] ?? ''));
        $username = $conn->real_escape_string(trim($_POST['username'] ?? ''));
        $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));

        if (empty($nip) || empty($nama) || empty($username) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
            exit;
        }

        // Check unique constraints for email and username
        $q_check = $conn->query("SELECT id FROM pimpinan WHERE (username='$username' OR email='$email') AND id != $pimpinan_id");
        if ($q_check && $q_check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username atau Email sudah digunakan']);
            exit;
        }

        if ($conn->query("UPDATE pimpinan SET nip='$nip', nama='$nama', username='$username', email='$email' WHERE id=$pimpinan_id")) {
            $_SESSION['username'] = stripslashes($username);
            $_SESSION['pimpinan_nama'] = stripslashes($nama);
            $_SESSION['pimpinan_nip'] = stripslashes($nip);
            echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui profil: ' . $conn->error]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
