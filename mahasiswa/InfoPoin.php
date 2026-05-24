<?php
include 'LayoutHeader.php';

// Fetch Total Poin Mahasiswa Realtime
$poin_mhs_query = "SELECT COALESCE(SUM(CASE WHEN p.status = 'approved' THEN 
                (SELECT pc.poin FROM poin_config pc WHERE (pc.tingkat = p.tingkat OR (p.tingkat = 'Kota/Kabupaten' AND pc.tingkat IN ('Kota','Kabupaten'))) AND pc.juara = p.juara ORDER BY pc.poin DESC LIMIT 1) 
            ELSE 0 END), 0) AS total_poin
                    FROM mahasiswa m
                    LEFT JOIN prestasi p ON m.id = p.mahasiswa_id
                    WHERE m.id = '$mahasiswa_id'
                    GROUP BY m.id";
$res_poin = mysqli_query($conn, $poin_mhs_query);
$poin_mhs = 0;
if ($res_poin) {
    $row_poin = mysqli_fetch_assoc($res_poin);
    if ($row_poin) {
        $poin_mhs = $row_poin['total_poin'];
    }
}

// Fetch and group configurations
$q_poin = $conn->query("SELECT * FROM poin_config ORDER BY poin DESC");
$poin_data = [
    'Internasional' => [],
    'Nasional' => [],
    'Provinsi' => [],
    'Kota' => [],
    'Kabupaten' => [],
    'Kampus' => []
];

$grouped_poin = [];
while($row = $q_poin->fetch_assoc()) {
    $grouped_poin[$row['tingkat']][] = $row;
}

$levels_order = ['Internasional', 'Nasional', 'Provinsi', 'Kota', 'Kabupaten', 'Kampus'];
$sorted_poin_data = [];
foreach($levels_order as $lvl) {
    if(isset($grouped_poin[$lvl])) {
        $sorted_poin_data[$lvl] = $grouped_poin[$lvl];
    } else {
        $sorted_poin_data[$lvl] = [];
    }
}
// Add any other levels that might exist in DB but not in our explicit order
foreach($grouped_poin as $lvl => $data) {
    if(!isset($sorted_poin_data[$lvl])) {
        $sorted_poin_data[$lvl] = $data;
    }
}

// Icons for levels
$level_icons = [
    'Internasional' => 'fa-globe',
    'Nasional' => 'fa-flag',
    'Provinsi' => 'fa-map-location-dot',
    'Kota' => 'fa-city',
    'Kabupaten' => 'fa-house',
    'Kampus' => 'fa-graduation-cap'
];
?>

<style>
    /* Styling modern from Admin */
    .page-header-new { margin-bottom: 25px; }
    .breadcrumb-top { font-size: 13px; color: #2563eb; font-weight: 500; text-align: right; margin-bottom: 5px; }
    .breadcrumb-top span { color: #6b7280; }
    .breadcrumb-top a { color: #2563eb; text-decoration: none; }
    .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; width: 100%; }
    .page-title-new h1 { font-size: 24px; font-weight: 800; color: #1e3a8a; margin: 0 0 5px 0; }
    .page-title-new p { font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 400; color: #64748b; line-height: 1.5; margin: 0; }
    
    .summary-container { display: flex; gap: 20px; margin-bottom: 25px; align-items: stretch; }
    .summary-box-blue { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 25px; flex: 2; display: flex; align-items: flex-start; gap: 20px; position: relative; overflow: hidden; }
    .summary-box-blue .icon-circle { width: 40px; height: 40px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); }
    .summary-box-blue .text-content { z-index: 2; }
    .summary-box-blue h3 { margin: 0 0 8px 0; color: #1e40af; font-size: 16px; font-weight: 700; }
    .summary-box-blue p { margin: 0; color: #3b82f6; font-size: 13px; line-height: 1.6; }
    .summary-box-blue .bg-illustration { position: absolute; right: 20px; bottom: 0; font-size: 100px; color: #bfdbfe; opacity: 0.5; z-index: 1; transform: translateY(20%); }
    
    .summary-box-white { background: white; border: 1px solid #e5e7eb; border-radius: 12px; flex: 1; display: flex; flex-direction: column; overflow: hidden; position: relative; min-width: 250px;}
    .stats-row { display: flex; justify-content: space-around; padding: 20px 25px; flex: 1; align-items: center; position: z-index: 2;}
    .stat-item { display: flex; align-items: center; gap: 15px; flex: 1; z-index: 2; }
    .stat-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; flex-shrink: 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .stat-icon.bg-yellow { background: #f59e0b; }
    .stat-text h2 { margin: 0 0 4px 0; font-size: 32px; font-weight: 800; color: #1f2937; line-height: 1; }
    .stat-text h4 { margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #374151; }
    .summary-footer { padding: 12px 25px; background: #f9fafb; font-size: 12px; color: #6b7280; border-top: 1px solid #f3f4f6; display: flex; align-items: center; gap: 8px; z-index: 2; }
    
    .levels-grid { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
    .level-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; flex-direction: column; flex: 1 1 calc(33.333% - 15px); min-width: 280px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .level-header { padding: 25px 15px 15px; text-align: center; border-bottom: 1px solid #f3f4f6; }
    .level-icon { width: 45px; height: 45px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; margin: 0 auto 12px; }
    .level-header h3 { margin: 0 0 6px 0; color: #2563eb; font-size: 16px; font-weight: 700; }
    .level-header p { margin: 0; color: #6b7280; font-size: 11px; line-height: 1.4; }
    
    .level-body { flex: 1; padding: 15px; }
    .level-row-header { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 11px; font-weight: 600; color: #374151; }
    .level-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed #f3f4f6; }
    .level-row:last-child { border-bottom: none; }
    .level-row .juara-name { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 500; color: #374151; }
    .level-row .juara-icon { color: #9ca3af; font-size: 14px; }
    .level-row .juara-icon.gold { color: #f59e0b; }
    .level-row .juara-icon.silver { color: #9ca3af; }
    .level-row .juara-icon.bronze { color: #d97706; }
    .level-row .juara-icon.blue { color: #3b82f6; }
    
    .level-row .poin-badge { background: #eff6ff; color: #2563eb; font-size: 13px; font-weight: 600; padding: 4px 12px; border-radius: 6px; }
    
    .notes-card { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 25px; display: flex; align-items: flex-start; gap: 20px; position: relative; overflow: hidden; margin-bottom: 30px; }
    .notes-icon { width: 45px; height: 45px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); }
    .notes-content { z-index: 2; }
    .notes-content h3 { margin: 0 0 12px 0; color: #1e40af; font-size: 16px; font-weight: 700; }
    .notes-content ul { margin: 0; padding-left: 20px; color: #1e3a8a; font-size: 13px; line-height: 1.8; }
    .notes-illustration { position: absolute; right: 40px; bottom: 0; font-size: 100px; color: #bfdbfe; opacity: 0.5; z-index: 1; transform: translateY(20%); }

    @media (max-width: 1024px) {
        .summary-container { flex-direction: column; }
    }
    @media (max-width: 768px) {
        .page-header-flex { flex-direction: column; align-items: flex-start; gap: 15px; }
        .breadcrumb-top { align-self: flex-start; }
        .level-card { flex: 1 1 calc(50% - 15px); }
    }
    @media (max-width: 480px) {
        .level-card { flex: 1 1 100%; }
    }
</style>

<div class="content-wrapper-new" style="padding: 30px; overflow-x: hidden !important; width: 100%; box-sizing: border-box;">
    <div class="page-header-new">
        <div class="page-header-flex">
            <div class="page-title-new">
                <div class="page-title-text">
                    <h1>Info Poin Prestasi</h1>
                    <p>Informasi detail sistem penilaian poin prestasi yang transparan untuk mahasiswa.</p>
                </div>
            </div>
            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                <div class="breadcrumb-top">
                    <a href="Dashboard.php">Beranda</a> <span>/</span> Info Poin
                </div>
            </div>
        </div>
    </div>
    
    <div class="summary-container">
        <!-- Poin Realtime Mahasiswa Card -->
        <div class="summary-box-white">
            <i class="fa-solid fa-trophy" style="position: absolute; right: -10px; bottom: -20px; font-size: 120px; color: #fef3c7; z-index: 1;"></i>
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-icon bg-yellow"><i class="fa-solid fa-star"></i></div>
                    <div class="stat-text">
                        <h4>Total Poin Anda</h4>
                        <h2><?= number_format($poin_mhs, 0, ',', '.') ?></h2>
                    </div>
                </div>
            </div>
            <div class="summary-footer">
                <i class="fa-solid fa-circle-check" style="color: #10b981;"></i> Realtime dari database leaderboard.
            </div>
        </div>

        <!-- Sistem Poin Card -->
        <div class="summary-box-blue">
            <div class="icon-circle"><i class="fa-solid fa-info"></i></div>
            <div class="text-content">
                <h3>Sistem Penilaian Poin</h3>
                <p>Poin prestasi digunakan untuk perhitungan di <strong>Leaderboard Mahasiswa</strong>. Poin secara otomatis terakumulasi setelah prestasi Anda diverifikasi dan disetujui oleh Admin. Tabel di bawah ini merupakan referensi poin sesuai dengan kriteria yang ditetapkan institusi secara terpusat.</p>
            </div>
            <i class="fa-solid fa-clipboard-check bg-illustration"></i>
        </div>
    </div>

    <div class="section-title" style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-layer-group" style="color: #3b82f6;"></i> Kategori Prestasi & Poin
    </div>
    
    <div class="levels-grid">
        <?php 
        foreach($sorted_poin_data as $tingkat => $aturans): 
            if(count($aturans) == 0) continue; // Jangan tampilkan yang kosong
            $icon = $level_icons[$tingkat] ?? 'fa-layer-group';
        ?>
        <div class="level-card">
            <div class="level-header">
                <div class="level-icon"><i class="fa-solid <?= $icon ?>"></i></div>
                <h3><?= htmlspecialchars($tingkat) ?></h3>
                <p>Kompetisi Tingkat <?= htmlspecialchars($tingkat) ?></p>
            </div>
            <div class="level-body">
                <div class="level-row-header">
                    <span>Pencapaian (Juara)</span>
                    <span>Poin</span>
                </div>
                <?php foreach($aturans as $aturan): 
                    $juara_lower = strtolower($aturan['juara']);
                    
                    // Determine medal icon and color
                    $medal_class = 'fa-medal';
                    $medal_color = 'silver'; // default
                    
                    if (strpos($juara_lower, 'juara 1') !== false || strpos($juara_lower, 'pertama') !== false) {
                        $medal_color = 'gold';
                    } elseif (strpos($juara_lower, 'juara 2') !== false || strpos($juara_lower, 'kedua') !== false) {
                        $medal_color = 'silver';
                    } elseif (strpos($juara_lower, 'juara 3') !== false || strpos($juara_lower, 'ketiga') !== false) {
                        $medal_color = 'bronze';
                    } elseif (strpos($juara_lower, 'harapan') !== false || strpos($juara_lower, 'finalis') !== false) {
                        $medal_class = 'fa-award';
                        $medal_color = 'blue';
                    } else {
                        $medal_class = 'fa-star';
                        $medal_color = 'blue';
                    }
                ?>
                <div class="level-row">
                    <div class="juara-name">
                        <i class="fa-solid <?= $medal_class ?> juara-icon <?= $medal_color ?>"></i>
                        <?= htmlspecialchars($aturan['juara']) ?>
                    </div>
                    <div class="poin-badge"><?= $aturan['poin'] ?> Poin</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="notes-card" style="margin-bottom: 0;">
        <div class="notes-icon"><i class="fa-solid fa-shield-check"></i></div>
        <div class="notes-content">
            <h3>Catatan Penting</h3>
            <ul>
                <li>Jika Anda memiliki prestasi di tingkat dan capaian yang belum tercantum pada kriteria tabel di atas, maka poin akan ditentukan melalui kebijakan dan verifikasi manual oleh Admin.</li>
                <li>Pastikan Anda mengunggah bukti prestasi (sertifikat, dokumentasi) yang jelas dan valid untuk mempercepat proses verifikasi poin.</li>
            </ul>
        </div>
        <i class="fa-solid fa-bullhorn notes-illustration"></i>
    </div>
</div>

<?php include 'LayoutFooter.php'; ?>
