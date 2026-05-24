<?php
require '../koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
        exit;
    }

    $subkategoriBaru = $_POST['subkategori_baru'] ?? '';
    if (!empty($subkategoriBaru)) {
        $stmtUpdateSub = $conn->prepare("UPDATE prestasi SET jenis = ? WHERE id = ?");
        $stmtUpdateSub->bind_param("si", $subkategoriBaru, $id);
        $stmtUpdateSub->execute();
    }

    if ($action === 'approve') {
        // Ambil data prestasi
        $stmt = $conn->prepare("SELECT * FROM prestasi WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $prestasi = $stmt->get_result()->fetch_assoc();

        if (!$prestasi) {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.']);
            exit;
        }

        // Ambil nilai dari tabel poin_config berdasarkan tingkat dan juara
        $stmt = $conn->prepare("
            SELECT poin FROM poin_config 
            WHERE (tingkat = ? OR (? = 'Kota/Kabupaten' AND tingkat IN ('Kota', 'Kabupaten'))) 
              AND juara = ? 
            ORDER BY poin DESC LIMIT 1
        ");
        $stmt->bind_param("sss", $prestasi['tingkat'], $prestasi['tingkat'], $prestasi['juara']);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        $totalPoin = 0;
        if ($res) {
            $totalPoin = $res['poin'];
        }

        // Update status di tabel prestasi
        $stmt = $conn->prepare("UPDATE prestasi SET status = 'approved', poin = ? WHERE id = ?");
        $stmt->bind_param("ii", $totalPoin, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Prestasi disetujui.', 'poin' => $totalPoin]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate database.']);
        }

    } elseif ($action === 'reject') {
        $alasan = $_POST['alasan'] ?? '';
        $stmt = $conn->prepare("UPDATE prestasi SET status = 'rejected', poin = 0, alasan_penolakan = ? WHERE id = ?");
        $stmt->bind_param("si", $alasan, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Prestasi ditolak.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate database.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
