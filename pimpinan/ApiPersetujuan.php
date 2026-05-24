<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require '../koneksi.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $revisi_id = $_POST['id'] ?? 0;
    
    // Get revisi data
    $q = $conn->query("SELECT * FROM poin_revisi WHERE id = " . intval($revisi_id) . " AND status = 'Menunggu Persetujuan'");
    $revisi = $q->fetch_assoc();
    
    if (!$revisi) {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau sudah diproses.']);
        exit;
    }
    
    if ($action === 'approve') {
        $conn->begin_transaction();
        try {
            // Update the main poin_config table
            if ($revisi['tipe'] === 'insert') {
                $stmt = $conn->prepare("INSERT INTO poin_config (tingkat, juara, poin) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $revisi['tingkat'], $revisi['juara'], $revisi['poin_baru']);
                $stmt->execute();
                $new_id = $conn->insert_id;
                
                // Update revisi row with new config id
                $conn->query("UPDATE poin_revisi SET poin_config_id = $new_id WHERE id = $revisi_id");
            } elseif ($revisi['tipe'] === 'update') {
                $stmt = $conn->prepare("UPDATE poin_config SET tingkat = ?, juara = ?, poin = ? WHERE id = ?");
                $stmt->bind_param("ssii", $revisi['tingkat'], $revisi['juara'], $revisi['poin_baru'], $revisi['poin_config_id']);
                $stmt->execute();
            } elseif ($revisi['tipe'] === 'delete') {
                $stmt = $conn->prepare("DELETE FROM poin_config WHERE id = ?");
                $stmt->bind_param("i", $revisi['poin_config_id']);
                $stmt->execute();
            }
            
            // Mark as Disetujui
            $conn->query("UPDATE poin_revisi SET status = 'Disetujui', pimpinan_id = 1 WHERE id = $revisi_id");
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Perubahan kriteria poin disetujui.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($action === 'reject') {
        $alasan = $_POST['alasan'] ?? '';
        $stmt = $conn->prepare("UPDATE poin_revisi SET status = 'Ditolak', pimpinan_id = 1, alasan_penolakan = ? WHERE id = ?");
        $stmt->bind_param("si", $alasan, $revisi_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Perubahan kriteria poin ditolak.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
    }
}
?>
