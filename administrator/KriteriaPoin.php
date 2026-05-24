<?php
date_default_timezone_set('Asia/Jakarta');
$bulan = array(
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
);
$tanggal_hari_ini = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');

session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: Index.php"); exit; }
require '../koneksi.php';

// Get Stats
$q_pending = $conn->query("SELECT COUNT(*) as cnt FROM prestasi WHERE status='pending'");
$menunggu = $q_pending->fetch_assoc()['cnt'];

// Fetch statistics
$q_stats = $conn->query("SELECT 
    COUNT(DISTINCT tingkat) as total_tingkat,
    COUNT(id) as total_aturan,
    MAX(poin) as poin_max,
    MIN(poin) as poin_min
    FROM poin_config");
$stats = $q_stats->fetch_assoc();

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

// Ensure sorting of levels as per design
$levels_order = ['Internasional', 'Nasional', 'Provinsi', 'Kota', 'Kabupaten', 'Kampus'];
$sorted_poin_data = [];
foreach($levels_order as $lvl) {
    if(isset($grouped_poin[$lvl])) {
        $sorted_poin_data[$lvl] = $grouped_poin[$lvl];
    } else {
        $sorted_poin_data[$lvl] = []; // Empty array if no rules exist for this level
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

// Fetch pending revisions
$q_pending_revisi = $conn->query("SELECT * FROM poin_revisi WHERE status='Menunggu Persetujuan'");
$pending_revisi = [];
$pending_inserts = [];
$count_pending = 0;
while($row = $q_pending_revisi->fetch_assoc()) {
    $count_pending++;
    if($row['poin_config_id']) {
        $pending_revisi[$row['poin_config_id']] = $row;
    } else {
        $pending_inserts[$row['tingkat']][] = $row;
    }
}

// Fetch all revisions for history table
$q_all_revisi = $conn->query("SELECT * FROM poin_revisi ORDER BY tanggal_perubahan DESC");
$all_revisi = [];
while($row = $q_all_revisi->fetch_assoc()) {
    $all_revisi[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kriteria Poin - Admin Portal</title>
    
    <!-- Modern font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="Assets/Css/Style.css?v=<?= time(); ?>">
    <style>
        .page-header-new { margin-bottom: 25px; }
        .breadcrumb-top { font-size: 13px; color: #2563eb; font-weight: 500; text-align: right; margin-bottom: 5px; }
        .breadcrumb-top span { color: #6b7280; }
        .breadcrumb-top a { color: #2563eb; text-decoration: none; }
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; width: 100%; }
        .page-title-new h1 { font-size: 24px; font-weight: 800; color: #1e3a8a; margin: 0 0 5px 0; }
        .page-title-new p { font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 400; color: #64748b; line-height: 1.5; margin: 0; }
        
        .summary-container { display: flex; gap: 20px; margin-bottom: 25px; align-items: stretch; }
        .summary-box-blue { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 25px; flex: 1; display: flex; align-items: flex-start; gap: 20px; position: relative; overflow: hidden; }
        .summary-box-blue .icon-circle { width: 40px; height: 40px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); }
        .summary-box-blue .text-content { z-index: 2; }
        .summary-box-blue h3 { margin: 0 0 8px 0; color: #1e40af; font-size: 16px; font-weight: 700; }
        .summary-box-blue p { margin: 0; color: #3b82f6; font-size: 13px; line-height: 1.6; }
        .summary-box-blue .bg-illustration { position: absolute; right: 20px; bottom: 0; font-size: 100px; color: #bfdbfe; opacity: 0.5; z-index: 1; transform: translateY(20%); }
        
        .summary-box-white { background: white; border: 1px solid #e5e7eb; border-radius: 12px; flex: 2; display: flex; flex-direction: column; overflow: hidden; }
        .stats-row { display: flex; justify-content: space-around; padding: 20px 25px; flex: 1; }
        .stat-item { display: flex; align-items: flex-start; gap: 15px; flex: 1; }
        .stat-icon { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; flex-shrink: 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .stat-icon.bg-blue { background: #3b82f6; }
        .stat-icon.bg-green { background: #10b981; }
        .stat-icon.bg-yellow { background: #f59e0b; }
        .stat-icon.bg-purple { background: #8b5cf6; }
        .stat-text h2 { margin: 0 0 4px 0; font-size: 24px; font-weight: 700; color: #1f2937; line-height: 1; }
        .stat-text h4 { margin: 0 0 4px 0; font-size: 13px; font-weight: 600; color: #374151; }
        .stat-text p { margin: 0; font-size: 11px; color: #9ca3af; line-height: 1.4; }
        .summary-footer { padding: 12px 25px; background: #f9fafb; font-size: 12px; color: #6b7280; border-top: 1px solid #f3f4f6; display: flex; align-items: center; gap: 8px; }
        
        .levels-grid { display: flex; gap: 15px; margin-bottom: 25px; overflow-x: auto; padding-bottom: 10px; }
        .level-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; flex-direction: column; flex: 1; min-width: 200px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .level-header { padding: 25px 15px 15px; text-align: center; border-bottom: 1px solid #f3f4f6; }
        .level-icon { width: 45px; height: 45px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; margin: 0 auto 12px; }
        .level-header h3 { margin: 0 0 6px 0; color: #2563eb; font-size: 16px; font-weight: 700; }
        .level-header p { margin: 0; color: #6b7280; font-size: 11px; line-height: 1.4; }
        
        .level-body { flex: 1; padding: 15px; }
        .level-row-header { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 11px; font-weight: 600; color: #374151; }
        .level-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; position: relative; }
        .level-row .juara-name { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 500; color: #374151; }
        .level-row .juara-icon { color: #9ca3af; font-size: 14px; }
        .level-row .juara-icon.gold { color: #f59e0b; }
        .level-row .juara-icon.silver { color: #9ca3af; }
        .level-row .juara-icon.bronze { color: #d97706; }
        .level-row .juara-icon.blue { color: #3b82f6; }
        
        .level-row .poin-badge { background: #eff6ff; color: #2563eb; font-size: 13px; font-weight: 600; padding: 4px 12px; border-radius: 6px; transition: opacity 0.2s; }
        
        .level-actions { position: absolute; right: 0; top: 50%; transform: translateY(-50%); display: flex; gap: 5px; opacity: 0; transition: opacity 0.2s; background: white; padding-left: 5px; border-radius: 6px; }
        .level-row:hover .level-actions { opacity: 1; }
        .level-row:hover .poin-badge { opacity: 0; }
        
        .action-btn { width: 28px; height: 28px; border-radius: 6px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; color: white; font-size: 12px; }
        .action-btn.edit { background: #3b82f6; }
        .action-btn.delete { background: #ef4444; }
        
        .level-footer { padding: 15px; border-top: 1px solid #f3f4f6; text-align: center; }
        .level-footer a { color: #2563eb; font-size: 13px; font-weight: 600; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .level-footer a:hover { text-decoration: underline; }
        
        .notes-card { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 25px; display: flex; align-items: flex-start; gap: 20px; position: relative; overflow: hidden; margin-bottom: 30px; }
        .notes-icon { width: 45px; height: 45px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); }
        .notes-content { z-index: 2; }
        .notes-content h3 { margin: 0 0 12px 0; color: #1e40af; font-size: 16px; font-weight: 700; }
        .notes-content ul { margin: 0; padding-left: 20px; color: #1e3a8a; font-size: 13px; line-height: 1.8; }
        .notes-illustration { position: absolute; right: 40px; bottom: 0; font-size: 100px; color: #bfdbfe; opacity: 0.5; z-index: 1; transform: translateY(20%); }
        
        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; width: 100%; max-width: 450px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal-header { padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; font-size: 18px; font-weight: 600; color: #1f2937; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 15px 20px; border-top: 1px solid #e5e7eb; background: #f9fafb; display: flex; justify-content: flex-end; gap: 10px; }

        .pending-banner { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 15px 20px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; color: #b45309; }
        .pending-banner i { font-size: 20px; color: #f59e0b; }
        .pending-badge { background: #fef3c7; color: #d97706; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px; margin-left: 10px; border: 1px solid #fde68a; }
        
        .table-revisi { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-revisi th, .table-revisi td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #f3f4f6; font-size: 13.5px; color: #374151; }
        .table-revisi th { background: #f9fafb; font-weight: 600; color: #4b5563; }
        
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .badge-kuning { background: #fef3c7; color: #d97706; }
        .badge-hijau { background: #dcfce7; color: #16a34a; }
        .badge-merah { background: #fee2e2; color: #dc2626; }
        
        @media (max-width: 1200px) {
            .levels-grid { display: grid; grid-template-columns: repeat(3, 1fr); overflow: visible; }
        }
        @media (max-width: 1024px) {
            .summary-container { flex-direction: column; }
        }
        @media (max-width: 768px) {
            .levels-grid { grid-template-columns: repeat(2, 1fr); }
            .page-header-flex { flex-direction: column; align-items: flex-start; gap: 15px; }
            .breadcrumb-top { align-self: flex-start; }
            .stats-row { flex-wrap: wrap; gap: 20px; }
            .stat-item { min-width: 40%; }
        }
        @media (max-width: 480px) {
            .levels-grid { grid-template-columns: 1fr; }
            .stat-item { min-width: 100%; }
        }
    </style>
</head>
<body class="dashboard-body new-dashboard">

    <!-- Sidebar -->
    <aside class="sidebar-new" id="sidebar">
        <div class="sidebar-header-new">
            <div class="sidebar-header-left">
                <div class="logo-icon" style="width:56px; height:56px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border-radius:50%; background:#ffffff; overflow:hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.15);">
                    <img src="Assets/Images/logo_fasilkom.png" alt="Logo Fasilkom" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="logo-text">
                    <h2>SIPRESMA</h2>
                    <p>Sistem Informasi Prestasi Mahasiswa</p>
                </div>
            </div>
            <button class="toggle-btn-sidebar btn-toggle-sidebar" id="toggleSidebar" title="Toggle Sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <div class="sidebar-profile" style="border-top: 1px solid rgba(255, 255, 255, 0.08);">
            <div class="profile-icon" style="background-color: transparent; border: 1px solid rgba(255,255,255,0.4);">
                <i class="fa-regular fa-user"></i>
            </div>
            <div class="profile-details">
                <div class="profile-name"><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></div>
                <div class="profile-role"><?= htmlspecialchars($_SESSION['admin_nip'] ?? '-') ?></div>
            </div>
        </div>

        <div class="sidebar-menus-container">
            <div class="sidebar-menu-title">MENU UTAMA</div>
            <ul class="sidebar-menu-new">
                <li class="menu-item-new"><a href="Dashboard.php"><i class="fa-solid fa-house"></i> <span class="menu-text">Dashboard</span></a></li>
                <li class="menu-item-new"><a href="Biodata.php"><i class="fa-regular fa-user"></i> <span class="menu-text">Biodata</span></a></li>
            </ul>

            <div class="sidebar-menu-title mt-custom">MANAJEMEN PRESTASI</div>
            <ul class="sidebar-menu-new">
                <li class="menu-item-new">
                    <a href="VerifikasiPrestasi.php">
                        <i class="fa-solid fa-check-to-slot"></i> <span class="menu-text">Verifikasi Prestasi</span>
                        <?php if(isset($prestasi_menunggu) && $prestasi_menunggu > 0): ?><span class="badge"><?= $prestasi_menunggu ?></span><?php endif; ?>
                    </a>
                </li>
            </ul>
            


            <div class="sidebar-menu-title mt-custom">MANAJEMEN DATA</div>
            <ul class="sidebar-menu-new">
                <li class="menu-item-new"><a href="DataMahasiswa.php"><i class="fa-solid fa-users"></i> <span class="menu-text">Data Mahasiswa</span></a></li>
                <li class="menu-item-new active"><a href="KriteriaPoin.php"><i class="fa-solid fa-gear"></i> <span class="menu-text">Kriteria Poin</span></a></li>
            </ul>

            <div class="sidebar-menu-title mt-custom">PERINGKAT & LAPORAN</div>
            <ul class="sidebar-menu-new">
                <li class="menu-item-new"><a href="Leaderboard.php"><i class="fa-solid fa-trophy"></i> <span class="menu-text">Leaderboard</span></a></li>
            </ul>

        </div>

        <div class="sidebar-footer-new">
            
            <a href="Logout.php" class="logout-btn-new"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span class="menu-text">Logout</span></a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content-new" id="mainContent">
        <?php include 'Topbar.php'; ?>

        <!-- Dashboard Content -->
        <div class="content-wrapper-new">
            
            <div class="page-header-new">
                <div class="page-header-flex">
                    <div class="page-title-new">
                        <div class="page-title-text">
                            <h1>Kriteria Penilaian Poin Prestasi</h1>
                            <p>Pemetaan nilai poin berdasarkan tingkat kompetisi dan peringkat (juara) yang diperoleh mahasiswa.</p>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                        <div class="breadcrumb-top">
                            <a href="Dashboard.php">Beranda</a> <span>/</span> Kriteria Poin
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if($count_pending > 0): ?>
            <div class="pending-banner">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600;">Menunggu Persetujuan</h4>
                    <p style="margin: 0; font-size: 13px;">Terdapat <?= $count_pending ?> perubahan kriteria poin yang sedang menunggu persetujuan dari pimpinan. Perubahan belum aktif hingga disetujui.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="summary-container">
                <!-- Sistem Poin Otomatis Card -->
                <div class="summary-box-blue">
                    <div class="icon-circle"><i class="fa-solid fa-info"></i></div>
                    <div class="text-content">
                        <h3>Sistem Poin Otomatis</h3>
                        <p>Poin akan diberikan secara otomatis saat prestasi mahasiswa diverifikasi berdasarkan kriteria yang telah ditetapkan.</p>
                    </div>
                    <i class="fa-solid fa-clipboard-check bg-illustration"></i>
                </div>
                
                <!-- Metrics Card -->
                <div class="summary-box-white">
                    <div class="stats-row">
                        <div class="stat-item">
                            <div class="stat-icon bg-blue"><i class="fa-solid fa-building-columns"></i></div>
                            <div class="stat-text">
                                <h2><?= $stats['total_tingkat'] ?? 0 ?></h2>
                                <h4>Tingkat Kompetisi</h4>
                                <p>Jumlah tingkat kompetisi</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon bg-green"><i class="fa-solid fa-medal"></i></div>
                            <div class="stat-text">
                                <h2><?= $stats['total_aturan'] ?? 0 ?></h2>
                                <h4>Total Aturan</h4>
                                <p>Jumlah seluruh aturan poin</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon bg-yellow"><i class="fa-solid fa-star"></i></div>
                            <div class="stat-text">
                                <h2><?= $stats['poin_max'] ?? 0 ?></h2>
                                <h4>Poin Tertinggi (Konfigurasi)</h4>
                                <p>Nilai poin tertinggi dari konfigurasi</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon bg-purple"><i class="fa-solid fa-trophy"></i></div>
                            <div class="stat-text">
                                <h2><?= $stats['poin_min'] ?? 0 ?></h2>
                                <h4>Poin Terendah (Konfigurasi)</h4>
                                <p>Nilai poin terendah dari konfigurasi</p>
                            </div>
                        </div>
                    </div>
                    <div class="summary-footer">
                        <i class="fa-solid fa-circle-info" style="color: #3b82f6;"></i> Nilai berdasarkan konfigurasi poin yang digunakan dalam sistem.
                    </div>
                </div>
            </div>
            
            <div class="levels-grid">
                <?php 
                foreach($sorted_poin_data as $tingkat => $aturans): 
                    $icon = $level_icons[$tingkat] ?? 'fa-layer-group';
                    $count_aturan = count($aturans);
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
                        <div class="level-row" data-id="<?= $aturan['id'] ?>" data-tingkat="<?= htmlspecialchars($aturan['tingkat']) ?>" data-juara="<?= htmlspecialchars($aturan['juara']) ?>" data-poin="<?= $aturan['poin'] ?>">
                            <div class="juara-name">
                                <i class="fa-solid <?= $medal_class ?> juara-icon <?= $medal_color ?>"></i>
                                <?= htmlspecialchars($aturan['juara']) ?>
                                <?php if(isset($pending_revisi[$aturan['id']])): ?>
                                    <span class="pending-badge" title="Menunggu Persetujuan (<?= ucfirst($pending_revisi[$aturan['id']]['tipe']) ?>)">Menunggu</span>
                                <?php endif; ?>
                            </div>
                            <div class="poin-badge"><?= $aturan['poin'] ?></div>
                            <div class="level-actions">
                                <button class="action-btn edit btn-edit-poin" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                <button class="action-btn delete btn-hapus-poin" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Show Pending Inserts -->
                        <?php if(isset($pending_inserts[$tingkat])): foreach($pending_inserts[$tingkat] as $p_ins): ?>
                        <div class="level-row" style="background: #fefce8; border: 1px dashed #fde68a; padding: 10px; border-radius: 8px;">
                            <div class="juara-name">
                                <i class="fa-solid fa-star juara-icon blue"></i>
                                <?= htmlspecialchars($p_ins['juara']) ?>
                                <span class="pending-badge" title="Menunggu Persetujuan (Baru)">Menunggu (Baru)</span>
                            </div>
                            <div class="poin-badge" style="background: #fef3c7; color: #d97706;"><?= $p_ins['poin_baru'] ?></div>
                        </div>
                        <?php endforeach; endif; ?>
                        
                        <?php if($count_aturan == 0 && !isset($pending_inserts[$tingkat])): ?>
                        <div style="text-align: center; color: #9ca3af; font-size: 12px; padding: 20px 0;">Belum ada aturan</div>
                        <?php endif; ?>
                    </div>
                    <div class="level-footer">
                        <a href="#" class="btn-manage-level" data-tingkat="<?= htmlspecialchars($tingkat) ?>" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="notes-card">
                <div class="notes-icon"><i class="fa-solid fa-shield-check"></i></div>
                <div class="notes-content">
                    <h3>Catatan Penting</h3>
                    <ul>
                        <li>Poin diberikan otomatis berdasarkan tingkat kompetisi dan peringkat (juara).</li>
                        <li>Pastikan kriteria poin selalu diperbarui sesuai kebijakan institusi.</li>
                        <li>Perubahan kriteria akan berlaku untuk prestasi yang diverifikasi setelah perubahan dilakukan.</li>
                    </ul>
                </div>
                <i class="fa-solid fa-clipboard-list notes-illustration"></i>
            </div>
            
            <!-- Tabel Approval Kriteria Poin -->
            <div class="summary-box-white" style="margin-bottom: 30px; padding: 25px;">
                <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 16px; font-weight: 700;">Riwayat Pengajuan Kriteria Poin</h3>
                <div style="overflow-x: auto;">
                    <table class="table-revisi">
                        <thead>
                            <tr>
                                <th>Pencapaian</th>
                                <th>Poin</th>
                                <th>Status</th>
                                <th>Catatan Pimpinan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($all_revisi) > 0): ?>
                                <?php foreach($all_revisi as $rev): 
                                    $status_class = 'badge-kuning';
                                    if ($rev['status'] === 'Disetujui') $status_class = 'badge-hijau';
                                    if ($rev['status'] === 'Ditolak') $status_class = 'badge-merah';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($rev['tingkat']) ?></strong> - <?= htmlspecialchars($rev['juara']) ?>
                                    </td>
                                    <td>
                                        <?php if($rev['tipe'] === 'insert'): ?>
                                            <span style="color: #10b981; font-weight: 600;">+ <?= $rev['poin_baru'] ?> pts</span>
                                        <?php elseif($rev['tipe'] === 'delete'): ?>
                                            <span style="color: #ef4444; font-weight: 600; text-decoration: line-through;"><?= $rev['poin_lama'] ?> pts</span>
                                        <?php else: ?>
                                            <span style="color: #64748b;"><?= $rev['poin_lama'] ?> &rarr;</span> <span style="color: #2563eb; font-weight: 600;"><?= $rev['poin_baru'] ?> pts</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?= $status_class ?>"><?= $rev['status'] ?></span></td>
                                    <td>
                                        <?php if ($rev['status'] === 'Ditolak' && !empty($rev['alasan_penolakan'])): ?>
                                            <span style="color: #ef4444; font-size: 13px;"><?= htmlspecialchars($rev['alasan_penolakan']) ?></span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px; color: #64748b;">Belum ada riwayat pengajuan perubahan poin.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
        
        <!-- Modal Tambah/Edit Kriteria -->
        <div id="kriteriaModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalKriteriaTitle">Tambah Aturan</h3>
                    <button class="close-modal-kriteria" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #6b7280;"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body" style="padding-top: 15px;">
                    <input type="hidden" id="kriteriaId">
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #111827;">Tingkat Kompetisi:</label>
                        <input type="text" id="kriteriaTingkat" class="custom-input" style="width: 100%; padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none;">
                    </div>

                    <div class="form-group" id="groupJuaraInput" style="margin-bottom: 15px;">
                        <label id="labelJuaraInput" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #111827;">Pencapaian (Juara):</label>
                        <input type="text" id="kriteriaJuaraInput" class="custom-input" style="width: 100%; padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none;">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #111827;">Nilai Poin:</label>
                        <input type="number" id="kriteriaPoin" class="custom-input" style="width: 100%; padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none;" placeholder="Contoh: 100">
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button class="btn-cancel" id="cancelKriteriaBtn" style="background: white; border: 1px solid #d1d5db; color: #111827; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;">Batal</button>
                    <button class="btn-save" id="saveKriteriaBtn" style="background: #2563eb; border: none; color: white; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;">Simpan</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Custom JS -->
    <script src="Assets/Js/Script.js?v=<?= time(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const kriteriaModal = document.getElementById('kriteriaModal');
            const closeKriteriaBtnIcon = document.querySelector('.close-modal-kriteria');
            const cancelKriteriaBtn = document.getElementById('cancelKriteriaBtn');
            
            const closeKriteriaModal = () => {
                if (kriteriaModal) kriteriaModal.classList.remove('active');
            };
            
            if (closeKriteriaBtnIcon) closeKriteriaBtnIcon.addEventListener('click', closeKriteriaModal);
            if (cancelKriteriaBtn) cancelKriteriaBtn.addEventListener('click', closeKriteriaModal);

            const saveKriteriaBtn = document.getElementById('saveKriteriaBtn');
            if (saveKriteriaBtn) {
                const newSaveBtn = saveKriteriaBtn.cloneNode(true);
                saveKriteriaBtn.parentNode.replaceChild(newSaveBtn, saveKriteriaBtn);
                
                newSaveBtn.addEventListener('click', function() {
                    const id = document.getElementById('kriteriaId').value;
                    const tingkat = document.getElementById('kriteriaTingkat').value.trim();
                    const juara = document.getElementById('kriteriaJuaraInput').value.trim();
                    const poin = document.getElementById('kriteriaPoin').value;
                    
                    if (!tingkat || !juara || !poin) {
                        Swal.fire('Peringatan', 'Harap isi semua kolom!', 'warning');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('tingkat', tingkat);
                    formData.append('juara', juara);
                    formData.append('poin', poin);
                    
                    if (id) {
                        formData.append('action', 'edit');
                        formData.append('id', id);
                    } else {
                        formData.append('action', 'add');
                    }
                    
                    fetch('ApiKriteria.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            closeKriteriaModal();
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Aturan poin berhasil disimpan!',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            refreshGrid();
                        } else {
                            Swal.fire('Error', 'Gagal menyimpan: ' + data.message, 'error');
                        }
                    });
                });
            }
            
            function refreshGrid() {
                fetch(window.location.href)
                    .then(r => r.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        document.querySelector('.levels-grid').innerHTML = doc.querySelector('.levels-grid').innerHTML;
                        attachEventListeners();
                    });
            }
            
            function attachEventListeners() {
                document.querySelectorAll('.btn-hapus-poin').forEach(btn => {
                    const newBtn = btn.cloneNode(true);
                    btn.parentNode.replaceChild(newBtn, btn);
                    
                    newBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const row = this.closest('.level-row');
                        const id = row.getAttribute('data-id');
                        
                        Swal.fire({
                            title: 'Hapus Aturan?',
                            text: 'Aturan poin ini akan dihapus secara permanen.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Ya, Hapus',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const fd = new FormData();
                                fd.append('action', 'delete');
                                fd.append('id', id);
                                
                                fetch('ApiKriteria.php', { method: 'POST', body: fd })
                                .then(res => res.json())
                                .then(data => {
                                    if(data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Dihapus',
                                            text: 'Aturan poin berhasil dihapus!',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                        refreshGrid();
                                    } else {
                                        Swal.fire('Error', data.message || 'Gagal menghapus', 'error');
                                    }
                                });
                            }
                        });
                    });
                });

                document.querySelectorAll('.btn-edit-poin').forEach(btn => {
                    const newBtn = btn.cloneNode(true);
                    btn.parentNode.replaceChild(newBtn, btn);
                    
                    newBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const row = this.closest('.level-row');
                        const id = row.getAttribute('data-id');
                        const tingkat = row.getAttribute('data-tingkat');
                        const juara = row.getAttribute('data-juara');
                        const poin = row.getAttribute('data-poin');
                        
                        document.getElementById('modalKriteriaTitle').innerText = 'Edit Aturan Poin';
                        document.getElementById('kriteriaId').value = id;
                        
                        document.getElementById('kriteriaTingkat').value = tingkat;
                        document.getElementById('kriteriaTingkat').readOnly = true;
                        document.getElementById('kriteriaTingkat').style.backgroundColor = '#f3f4f6';
                        
                        document.getElementById('kriteriaJuaraInput').value = juara;
                        document.getElementById('kriteriaJuaraInput').readOnly = true;
                        document.getElementById('kriteriaJuaraInput').style.backgroundColor = '#f3f4f6';
                        
                        document.getElementById('kriteriaPoin').value = poin;
                        kriteriaModal.classList.add('active');
                    });
                });
                
                document.querySelectorAll('.btn-manage-level').forEach(btn => {
                    const newBtn = btn.cloneNode(true);
                    btn.parentNode.replaceChild(newBtn, btn);
                    
                    newBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const tingkat = this.getAttribute('data-tingkat');
                        
                        document.getElementById('modalKriteriaTitle').innerText = 'Tambah Aturan Poin';
                        document.getElementById('kriteriaId').value = '';
                        
                        document.getElementById('kriteriaTingkat').value = tingkat;
                        document.getElementById('kriteriaTingkat').readOnly = false;
                        document.getElementById('kriteriaTingkat').style.backgroundColor = 'white';
                        
                        document.getElementById('kriteriaJuaraInput').value = '';
                        document.getElementById('kriteriaJuaraInput').readOnly = false;
                        document.getElementById('kriteriaJuaraInput').style.backgroundColor = 'white';
                        
                        document.getElementById('kriteriaPoin').value = '';
                        kriteriaModal.classList.add('active');
                    });
                });
            }
            
            attachEventListeners();
        });
    </script>
</body>
</html>

