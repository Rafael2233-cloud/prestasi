<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require '../koneksi.php';

$action = $_POST['action'] ?? '';

if ($action == 'edit') {
    $nim = $_POST['nim'] ?? '';
    $nama = $_POST['nama'] ?? '';
    $prodi = $_POST['prodi'] ?? '';
    $angkatan = $_POST['angkatan'] ?? '';
    $email = $_POST['email'] ?? '';
    $telp = $_POST['telp'] ?? '';

    if (empty($nim) || empty($nama)) {
        echo json_encode(['success' => false, 'message' => 'NIM dan Nama tidak boleh kosong']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE mahasiswa SET nama=?, prodi=?, angkatan=?, email=?, telp=? WHERE nim=?");
    $stmt->bind_param("ssssss", $nama, $prodi, $angkatan, $email, $telp, $nim);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate data']);
    }
} elseif ($action == 'delete') {
    $nim = $_POST['nim'] ?? '';
    
    if (empty($nim)) {
        echo json_encode(['success' => false, 'message' => 'NIM tidak ditemukan']);
        exit;
    }

    // Since there are foreign key constraints, we might want to let the DB cascade or just delete.
    // Assuming cascading is enabled or we only delete mahasiswa.
    $stmt = $conn->prepare("DELETE FROM mahasiswa WHERE nim=?");
    $stmt->bind_param("s", $nim);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus data. Pastikan tidak ada data yang terikat.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
