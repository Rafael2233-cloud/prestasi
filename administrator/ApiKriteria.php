<?php
require '../koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
        session_start();
        $admin_id = $_SESSION['admin_id'] ?? 1; // Fallback to 1 if not set

        if ($action === 'add') {
            $tingkat = $_POST['tingkat'];
            $juara = $_POST['juara'];
            $poin = $_POST['poin'];
            
            $stmt = $conn->prepare("INSERT INTO poin_revisi (tingkat, juara, poin_baru, tipe, status, admin_id) VALUES (?, ?, ?, 'insert', 'Menunggu Persetujuan', ?)");
            $stmt->bind_param("ssii", $tingkat, $juara, $poin, $admin_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Penambahan diajukan, menunggu persetujuan pimpinan.']);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $id = $_POST['id'];
            $tingkat = $_POST['tingkat'];
            $juara = $_POST['juara'];
            $poin = $_POST['poin'];
            
            // Get poin_lama
            $q = $conn->query("SELECT poin FROM poin_config WHERE id = " . intval($id));
            $poin_lama = $q->fetch_assoc()['poin'] ?? 0;
            
            $stmt = $conn->prepare("INSERT INTO poin_revisi (poin_config_id, tingkat, juara, poin_lama, poin_baru, tipe, status, admin_id) VALUES (?, ?, ?, ?, ?, 'update', 'Menunggu Persetujuan', ?)");
            $stmt->bind_param("issiii", $id, $tingkat, $juara, $poin_lama, $poin, $admin_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Perubahan diajukan, menunggu persetujuan pimpinan.']);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }
            $stmt->close();
        } elseif ($action === 'delete') {
            $id = $_POST['id'];
            
            // Get data lama
            $q = $conn->query("SELECT tingkat, juara, poin FROM poin_config WHERE id = " . intval($id));
            $data = $q->fetch_assoc();
            if($data) {
                $tingkat = $data['tingkat'];
                $juara = $data['juara'];
                $poin_lama = $data['poin'];
                
                $stmt = $conn->prepare("INSERT INTO poin_revisi (poin_config_id, tingkat, juara, poin_lama, poin_baru, tipe, status, admin_id) VALUES (?, ?, ?, ?, 0, 'delete', 'Menunggu Persetujuan', ?)");
                $stmt->bind_param("issii", $id, $tingkat, $juara, $poin_lama, $admin_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Penghapusan diajukan, menunggu persetujuan pimpinan.']);
                } else {
                    echo json_encode(['success' => false, 'message' => $conn->error]);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
            }
        }
}
?>
