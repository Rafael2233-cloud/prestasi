<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: Index.php"); exit; }
require '../koneksi.php';

date_default_timezone_set('Asia/Jakarta');

// Get filter values from GET request
$filter_prodi = isset($_GET['prodi']) ? $_GET['prodi'] : '';
$filter_angkatan = isset($_GET['angkatan']) ? $_GET['angkatan'] : '';
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build dynamic query to support year filtering
$base_query = "
SELECT 
    m.id, 
    m.nim, 
    m.nama, 
    m.prodi, 
    m.angkatan,
    COALESCE(SUM(
        CASE WHEN p.status = 'approved' THEN 
            (SELECT pc.poin FROM poin_config pc WHERE (pc.tingkat = p.tingkat OR (p.tingkat = 'Kota/Kabupaten' AND pc.tingkat IN ('Kota','Kabupaten'))) AND pc.juara = p.juara ORDER BY pc.poin DESC LIMIT 1) 
        ELSE 0 END
    ), 0) AS total_poin,
    COUNT(CASE WHEN p.status = 'approved' THEN 1 END) AS total_prestasi,
    GROUP_CONCAT(DISTINCT CASE WHEN p.status = 'approved' THEN p.tahun END ORDER BY p.tahun DESC SEPARATOR ', ') AS tahun_prestasi
FROM mahasiswa m 
LEFT JOIN prestasi p ON m.id = p.mahasiswa_id
";

if ($filter_tahun !== '') {
    $base_query = str_replace("LEFT JOIN prestasi p ON m.id = p.mahasiswa_id", "LEFT JOIN prestasi p ON m.id = p.mahasiswa_id AND p.tahun = '" . $conn->real_escape_string($filter_tahun) . "'", $base_query);
}

$where_clauses = ["1=1"];
if ($filter_prodi !== '') {
    $where_clauses[] = "m.prodi = '" . $conn->real_escape_string($filter_prodi) . "'";
}
if ($filter_angkatan !== '') {
    $where_clauses[] = "m.angkatan = '" . $conn->real_escape_string($filter_angkatan) . "'";
}

$query = $base_query . " WHERE " . implode(" AND ", $where_clauses) . " GROUP BY m.id, m.nim, m.nama, m.prodi, m.angkatan ORDER BY total_poin DESC, m.nama ASC";

$q_leaderboard = $conn->query($query);
$leaderboard_data = [];
$rank = 1;
while($row = $q_leaderboard->fetch_assoc()) {
    $row['rank'] = $rank++;
    $leaderboard_data[] = $row;
}

// Fetch dynamic filter lists
$prodis = [];
$q_prodi = $conn->query("SELECT DISTINCT prodi FROM mahasiswa WHERE prodi != '' AND prodi IS NOT NULL ORDER BY prodi ASC");
while($row = $q_prodi->fetch_assoc()) { $prodis[] = $row['prodi']; }

$angkatans = [];
$q_angkatan = $conn->query("SELECT DISTINCT angkatan FROM mahasiswa WHERE angkatan != '' AND angkatan IS NOT NULL ORDER BY angkatan DESC");
while($row = $q_angkatan->fetch_assoc()) { $angkatans[] = $row['angkatan']; }

$tahuns = [];
$q_tahun = $conn->query("SELECT DISTINCT tahun FROM prestasi WHERE tahun != '' AND tahun IS NOT NULL ORDER BY tahun DESC");
while($row = $q_tahun->fetch_assoc()) { $tahuns[] = $row['tahun']; }

// Ensure we have at least 3 items for the top 3 cards
$top1 = isset($leaderboard_data[0]) ? $leaderboard_data[0] : null;
$top2 = isset($leaderboard_data[1]) ? $leaderboard_data[1] : null;
$top3 = isset($leaderboard_data[2]) ? $leaderboard_data[2] : null;

if ($search !== '') {
    $filtered_data = [];
    foreach ($leaderboard_data as $row) {
        if (stripos($row['nim'], $search) !== false || stripos($row['nama'], $search) !== false) {
            $filtered_data[] = $row;
        }
    }
    $leaderboard_data = $filtered_data;
}

// Handle Excel Export (Using HTML Table for perfect Excel column formatting)
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"Leaderboard_Prestasi.xls\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr>
            <th style='background-color:#1e3a8a; color:white;'>Ranking</th>
            <th style='background-color:#1e3a8a; color:white;'>NIM</th>
            <th style='background-color:#1e3a8a; color:white;'>Nama Mahasiswa</th>
            <th style='background-color:#1e3a8a; color:white;'>Program Studi</th>
            <th style='background-color:#1e3a8a; color:white;'>Angkatan</th>
            <th style='background-color:#1e3a8a; color:white;'>Tahun Prestasi</th>
            <th style='background-color:#1e3a8a; color:white;'>Total Poin</th>
          </tr>";

    foreach ($leaderboard_data as $row) {
        echo "<tr>";
        echo "<td style='text-align:center;'>" . $row['rank'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nim']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
        echo "<td>" . htmlspecialchars($row['prodi']) . "</td>";
        echo "<td>" . htmlspecialchars($row['angkatan']) . "</td>";
        echo "<td>" . htmlspecialchars($row['tahun_prestasi'] ? $row['tahun_prestasi'] : '-') . "</td>";
        echo "<td>" . $row['total_poin'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

// Get pending count for sidebar badge
$q_pending = $conn->query("SELECT COUNT(*) as cnt FROM prestasi WHERE status='pending'");
$menunggu = $q_pending->fetch_assoc()['cnt'];

function getInitials($name) {
    if (!$name) return '-';
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $w) {
        $initials .= strtoupper(substr($w, 0, 1));
        if (strlen($initials) >= 2) break;
    }
    return $initials;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Admin Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Using the global CSS for consistency with Dashboard -->
    <link rel="stylesheet" href="Assets/Css/Style.css?v=<?= time() ?>">
    
    <style>
        /* Specific CSS for Leaderboard inner content that doesn't conflict with global */
        .content-wrapper-new {
            padding: 25px 30px;
        }
        
        /* Dropdown Menus for Topbar */
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 200px; z-index: 100; margin-top: 10px; padding: 5px 0; }
        .dropdown-menu.show { display: block; animation: fadeIn 0.2s ease-in-out; }
        .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 10px 15px; color: #475569; text-decoration: none; font-size: 13px; transition: background 0.2s; }
        .dropdown-item:hover { background: #f8fafc; color: var(--primary-new, #1e40af); }
        .dropdown-divider { height: 1px; background: #e2e8f0; margin: 5px 0; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .notification-btn { position: relative; color: #64748b; font-size: 20px; cursor: pointer; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.2s; }
        .notification-btn:hover { background: #f1f5f9; }
        .notification-btn .badge { position: absolute; top: 5px; right: 5px; background: #ef4444; color: white; font-size: 10px; font-weight: 700; padding: 2px 5px; border-radius: 10px; border: 2px solid white; line-height: 1; }
        
        /* Animations */
        @keyframes fadeInUpSmooth {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up { animation: fadeInUpSmooth 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .d-1 { animation-delay: 0.1s; } .d-2 { animation-delay: 0.2s; } .d-3 { animation-delay: 0.3s; }
        .item-anim { opacity: 0; animation: fadeInUpSmooth 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .d-item-1 { animation-delay: 0.1s; } .d-item-2 { animation-delay: 0.2s; } .d-item-3 { animation-delay: 0.3s; }
        .d-item-4 { animation-delay: 0.4s; } .d-item-5 { animation-delay: 0.5s; } .d-item-6 { animation-delay: 0.6s; }
        .d-item-7 { animation-delay: 0.7s; } .d-item-8 { animation-delay: 0.8s; } .d-item-9 { animation-delay: 0.9s; }
        .d-item-10 { animation-delay: 1.0s; }
        
        .user-profile-top { display: flex; align-items: center; gap: 10px; cursor: pointer; padding-left: 15px; border-left: 1px solid #e2e8f0; position: relative; }
        .user-profile-top .avatar { width: 36px; height: 36px; background: #2563eb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 16px; }
        .user-profile-top .info { display: flex; flex-direction: column; }
        .user-profile-top .info h4 { margin: 0; font-size: 14px; color: #1e293b; font-weight: 600; }
        .user-profile-top .info p { margin: 0; font-size: 12px; color: #64748b; }
        
        /* Leaderboard Specific Styles */
        .page-header { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; }
        .page-header-icon { width: 50px; height: 50px; background: white; border: 1px solid #e2e8f0; color: #2563eb; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .page-header-text h1 { margin: 0 0 5px 0; font-size: 22px; font-weight: 700; color: #0f172a; }
        .page-header-text p { margin: 0; font-size: 14px; color: #64748b; }
        
        /* Filter Card (Floating Labels) */
        .filter-card { background: white; border-radius: 12px; padding: 20px 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .filter-header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; font-size: 15px; font-weight: 600; color: #1e293b; }
        
        .filter-controls { display: flex; gap: 20px; align-items: stretch; flex-wrap: wrap; }
        .filter-group { flex: 1; min-width: 200px; position: relative; }
        .filter-label { position: absolute; top: -8px; left: 12px; background: white; padding: 0 6px; font-size: 11px; color: #64748b; font-weight: 500; z-index: 10; pointer-events: none; }
        .filter-select { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; color: #334155; font-size: 14px; outline: none; appearance: none; background: url('data:image/svg+xml;utf8,<svg fill="%2364748b" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 10px center; cursor: pointer; transition: border-color 0.2s; background-color: white; }
        .filter-select:focus { border-color: #2563eb; }
        
        .btn-reset { background: #2563eb; color: white; border: none; padding: 0 25px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; white-space: nowrap; height: 45px; }
        .btn-reset:hover { background: #1d4ed8; }
        .filter-input { width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #cbd5e1; border-radius: 8px; color: #334155; font-size: 14px; outline: none; transition: border-color 0.2s; background-color: white; box-sizing: border-box; height: 45px; }
        .filter-input:focus { border-color: #2563eb; }
        
        /* Top 3 Cards */
        .top3-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px; }
        
        .top-card { position: relative; border-radius: 12px; padding: 35px 20px 25px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; align-items: center; background: white; transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .top-card:hover { transform: translateY(-6px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.08), 0 10px 15px -6px rgba(0,0,0,0.04); border-color: #cbd5e1; }
        
        .top-card::before {
            content: ''; position: absolute; inset: 0;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none" stroke="%23e2e8f0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M30 80 C 10 70, 10 30, 30 20" /><path d="M70 80 C 90 70, 90 30, 70 20" /><path d="M30 80 L 70 80" /><circle cx="50" cy="50" r="15" fill="%23f8fafc" stroke="none"/></svg>');
            background-size: 120%; background-position: center 20%; background-repeat: no-repeat; opacity: 0.6; z-index: 0;
        }
        
        .top-card > * { z-index: 1; position: relative; }
        
        .medal-icon { position: absolute; top: 15px; left: 15px; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; color: white; z-index: 2; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 2px solid rgba(255,255,255,0.5); }
        .medal-icon::after, .medal-icon::before { content: ''; position: absolute; top: 30px; width: 12px; height: 25px; z-index: -1; }
        .medal-icon::before { left: 5px; background: inherit; transform: rotate(15deg); clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%); }
        .medal-icon::after { right: 5px; background: inherit; transform: rotate(-15deg); clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%); }
        
        .avatar-lg { width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700; margin: 10px auto 15px; color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        .top-card h3 { margin: 0 0 5px 0; font-size: 18px; font-weight: 700; color: #0f172a; }
        .top-card .student-details { margin: 0 0 20px 0; font-size: 11px; color: #64748b; font-weight: 500; letter-spacing: 0.3px; }
        
        .points-number { font-size: 38px; font-weight: 800; line-height: 1; margin-bottom: 5px; }
        .points-label { font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
        
        .card-gold { background: linear-gradient(135deg, #fffbeb, #ffffff); border-color: #fde68a; }
        .card-gold .medal-icon { background: #f59e0b; border-color: #fcd34d; }
        .card-gold .avatar-lg { background: #f59e0b; }
        .card-gold .points-number { color: #d97706; }
        
        .card-silver { background: linear-gradient(135deg, #f8fafc, #ffffff); border-color: #e2e8f0; }
        .card-silver .medal-icon { background: #94a3b8; border-color: #cbd5e1; }
        .card-silver .avatar-lg { background: #64748b; }
        .card-silver .points-number { color: #475569; }
        
        .card-bronze { background: linear-gradient(135deg, #fff7ed, #ffffff); border-color: #fed7aa; }
        .card-bronze .medal-icon { background: #d97706; border-color: #fdba74; }
        .card-bronze .avatar-lg { background: #d97706; }
        .card-bronze .points-number { color: #b45309; }
        
        /* Table Card */
        .table-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 30px; }
        .table-header { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .table-header h2 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
        .btn-export { background: white; border: 1px solid #cbd5e1; color: #334155; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; text-decoration: none; }
        .btn-export:hover { background: #f8fafc; border-color: #94a3b8; color: #0f172a; }
        
        .table-responsive { overflow-x: auto; }
        .custom-table { width: 100%; border-collapse: collapse; text-align: left; white-space: nowrap; }
        .custom-table th { padding: 15px 25px; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; }
        .custom-table td { padding: 15px 25px; font-size: 13px; border-bottom: 1px solid #f1f5f9; color: #475569; vertical-align: middle; transition: all 0.3s; }
        .custom-table tr:nth-child(even) td { background: #fafafa; }
        .table-row { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .table-row:hover { background: #f8fafc; transform: translateX(4px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03); }
        .table-row:hover td { background: transparent; }
        
        .custom-table .col-name { font-weight: 700; color: #0f172a; transition: color 0.3s; }
        .table-row:hover .col-name { color: #2563eb; }
        .custom-table .col-points { font-weight: 700; color: #2563eb; }
        
        .rank-circle { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; color: white; }
        .rank-1 { background: #f59e0b; }
        .rank-2 { background: #94a3b8; }
        .rank-3 { background: #d97706; }
        .rank-other { font-weight: 800; color: #0f172a; padding-left: 8px; }
        
        /* Pagination */
        .table-footer { padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; background: white; }
        .pagination-info { font-size: 13px; color: #64748b; }
        .pagination { display: flex; gap: 5px; }
        .page-btn { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border: 1px solid #cbd5e1; background: white; color: #334155; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; font-weight: 500; transition: all 0.2s; }
        .page-btn.active { background: #2563eb; color: white; border-color: #2563eb; }
        .page-btn:hover:not(.active):not(.dots) { background: #f1f5f9; }
        .page-btn.dots { border: none; cursor: default; }
        
        @media (max-width: 1024px) {
            .top3-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="dashboard-body new-dashboard">

    <!-- Sidebar standard from Dashboard.php -->
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
                <li class="menu-item-new"><a href="KriteriaPoin.php"><i class="fa-solid fa-gear"></i> <span class="menu-text">Kriteria Poin</span></a></li>
            </ul>

            <div class="sidebar-menu-title mt-custom">PERINGKAT & LAPORAN</div>
            <ul class="sidebar-menu-new">
                <li class="menu-item-new active"><a href="Leaderboard.php"><i class="fa-solid fa-trophy"></i> <span class="menu-text">Leaderboard</span></a></li>
            </ul>

        </div>

        <div class="sidebar-footer-new">
            
            <a href="Logout.php" class="logout-btn-new"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span class="menu-text">Logout</span></a>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <main class="main-content-new" id="mainContent">
        <?php include 'Topbar.php'; ?>

        <!-- Content Area -->
        <div class="content-wrapper-new">
            
            <div class="page-header-new">
                <div class="page-header-flex">
                    <div class="page-title-new">
                        <div class="page-header-text">
                            <h1 style="margin: 0 0 5px 0; font-size: 24px; font-weight: 800; color: #0f172a;">Leaderboard Prestasi</h1>
                            <p style="font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 400; color: #64748b; line-height: 1.5; margin: 0;">Peringkat mahasiswa berdasarkan total poin prestasi yang telah dicapai.</p>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                        <div class="breadcrumb-top" style="margin-bottom: 0;">
                            <a href="Dashboard.php">Beranda</a> <span>/</span> Leaderboard
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <form action="Leaderboard.php" method="GET" id="filterForm">
                <div class="filter-card">
                    <div class="filter-header">
                        <i class="fa-solid fa-filter"></i> Filter
                    </div>
                    <div class="filter-controls">
                        <div class="filter-group" style="flex: 2; min-width: 250px;">
                            <label class="filter-label">Cari NIM / Nama</label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <input type="text" class="filter-input" name="search" id="filterSearch" placeholder="Masukkan NIM atau nama..." value="<?= htmlspecialchars($search) ?>">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; color: #64748b; font-size: 14px;"></i>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Program Studi</label>
                            <select class="filter-select" name="prodi" id="filterProdi" onchange="document.getElementById('filterForm').submit()">
                                <option value="">Semua Program Studi</option>
                                <?php foreach($prodis as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $filter_prodi === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Angkatan</label>
                            <select class="filter-select" name="angkatan" id="filterAngkatan" onchange="document.getElementById('filterForm').submit()">
                                <option value="">Semua Angkatan</option>
                                <?php foreach($angkatans as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>" <?= $filter_angkatan === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Tahun</label>
                            <select class="filter-select" name="tahun" id="filterTahun" onchange="document.getElementById('filterForm').submit()">
                                <option value="">Semua Tahun</option>
                                <?php foreach($tahuns as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>" <?= $filter_tahun === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <a href="Leaderboard.php" class="btn-reset" style="text-decoration: none;">
                            <i class="fa-solid fa-rotate-right"></i> Reset Filter
                        </a>
                    </div>
                </div>
            </form>
            
            <!-- Top 3 Cards -->
            <?php if(count($leaderboard_data) >= 3): ?>
            <div class="top3-grid animate-slide-up d-1">
                <!-- 1st Place (Gold) -->
                <?php if($top1): ?>
                <div class="top-card card-gold">
                    <div class="medal-icon">1</div>
                    <div class="avatar-lg"><?= getInitials($top1['nama']) ?></div>
                    <h3><?= htmlspecialchars($top1['nama']) ?></h3>
                    <div class="student-details"><?= htmlspecialchars($top1['nim']) ?> | <?= htmlspecialchars($top1['prodi']) ?> | <?= htmlspecialchars($top1['angkatan']) ?></div>
                    <div class="points-number"><?= $top1['total_poin'] ?></div>
                    <div class="points-label">Total Poin</div>
                </div>
                <?php endif; ?>

                <!-- 2nd Place (Silver) -->
                <?php if($top2): ?>
                <div class="top-card card-silver">
                    <div class="medal-icon">2</div>
                    <div class="avatar-lg"><?= getInitials($top2['nama']) ?></div>
                    <h3><?= htmlspecialchars($top2['nama']) ?></h3>
                    <div class="student-details"><?= htmlspecialchars($top2['nim']) ?> | <?= htmlspecialchars($top2['prodi']) ?> | <?= htmlspecialchars($top2['angkatan']) ?></div>
                    <div class="points-number"><?= $top2['total_poin'] ?></div>
                    <div class="points-label">Total Poin</div>
                </div>
                <?php endif; ?>

                <!-- 3rd Place (Bronze) -->
                <?php if($top3): ?>
                <div class="top-card card-bronze">
                    <div class="medal-icon">3</div>
                    <div class="avatar-lg"><?= getInitials($top3['nama']) ?></div>
                    <h3><?= htmlspecialchars($top3['nama']) ?></h3>
                    <div class="student-details"><?= htmlspecialchars($top3['nim']) ?> | <?= htmlspecialchars($top3['prodi']) ?> | <?= htmlspecialchars($top3['angkatan']) ?></div>
                    <div class="points-number"><?= $top3['total_poin'] ?></div>
                    <div class="points-label">Total Poin</div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Table Card -->
            <div class="table-card animate-slide-up d-2">
                <div class="table-header">
                    <h2>Peringkat Lengkap</h2>
                    <!-- Link calls the PHP export parameter, preserving current filters -->
                    <a href="Leaderboard.php?export=excel&prodi=<?= urlencode($filter_prodi) ?>&angkatan=<?= urlencode($filter_angkatan) ?>&tahun=<?= urlencode($filter_tahun) ?>&search=<?= urlencode($search) ?>" class="btn-export">
                        <i class="fa-solid fa-download"></i> Export Excel
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="custom-table" id="leaderboardTable">
                        <thead>
                            <tr>
                                <th>PERINGKAT</th>
                                <th>NIM</th>
                                <th>NAMA MAHASISWA</th>
                                <th>PROGRAM STUDI</th>
                                <th>ANGKATAN</th>
                                <th>TAHUN PRESTASI</th>
                                <th>TOTAL PRESTASI</th>
                                <th>TOTAL POIN</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                             <?php 
                             $anim_idx = 1;
                             foreach($leaderboard_data as $row): 
                                 $rank_val = $row['rank'];
                                 $rank_html = '';
                                 if($rank_val == 1) $rank_html = '<div class="rank-circle rank-1">1</div>';
                                 elseif($rank_val == 2) $rank_html = '<div class="rank-circle rank-2">2</div>';
                                 elseif($rank_val == 3) $rank_html = '<div class="rank-circle rank-3">3</div>';
                                 else $rank_html = '<div class="rank-other">'.$rank_val.'</div>';
                                 
                                 $d_class = 'd-item-' . ($anim_idx <= 10 ? $anim_idx : 10);
                             ?>
                             <tr class="table-row item-anim <?= $d_class ?>">
                                 <td><?= $rank_html ?></td>
                                 <td><?= htmlspecialchars($row['nim']) ?></td>
                                 <td class="col-name"><?= htmlspecialchars($row['nama']) ?></td>
                                 <td><?= htmlspecialchars($row['prodi']) ?></td>
                                 <td><?= htmlspecialchars($row['angkatan']) ?></td>
                                 <td><?= htmlspecialchars($row['tahun_prestasi'] ? $row['tahun_prestasi'] : '-') ?></td>
                                 <td><?= $row['total_prestasi'] ?> prestasi</td>
                                 <td class="col-points"><?= $row['total_poin'] ?> poin</td>
                             </tr>
                             <?php 
                                 $anim_idx++;
                             endforeach; 
                            if(count($leaderboard_data) == 0):
                            ?>
                            <tr id="emptyRow">
                                <td colspan="7" style="text-align: center; padding: 30px; color: #64748b;">Data tidak ditemukan.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if(count($leaderboard_data) > 0): ?>
                <div class="table-footer">
                    <div class="pagination-info" id="paginationInfo">Menampilkan 1 - 10 dari <?= count($leaderboard_data) ?> data</div>
                    <div class="pagination" id="paginationControls">
                        <!-- Pagination controls will be injected here by JS -->
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
        </div>
    </main>

    <!-- Custom JS -->
    <script src="Assets/Js/Script.js?v=<?php echo time(); ?>"></script>
    <script>
        // Simple Client-side Pagination Logic
        document.addEventListener("DOMContentLoaded", function() {
            const rows = Array.from(document.querySelectorAll('.table-row'));
            const rowsPerPage = 10;
            const totalPages = Math.ceil(rows.length / rowsPerPage);
            let currentPage = 1;

            const renderTable = (page) => {
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                
                rows.forEach((row, index) => {
                    row.style.display = (index >= start && index < end) ? '' : 'none';
                });
                
                const currentEnd = Math.min(end, rows.length);
                const infoText = rows.length > 0 ? `Menampilkan ${start + 1} - ${currentEnd} dari ${rows.length} data` : `Menampilkan 0 data`;
                const infoEl = document.getElementById('paginationInfo');
                if(infoEl) infoEl.innerText = infoText;
                
                renderPagination(page);
            };

            const renderPagination = (page) => {
                const controls = document.getElementById('paginationControls');
                if(!controls || totalPages <= 1) {
                    if(controls) controls.innerHTML = '';
                    return;
                }
                
                let html = `<a href="#" class="page-btn prev" data-page="${page-1}"><i class="fa-solid fa-chevron-left"></i></a>`;
                
                for(let i = 1; i <= totalPages; i++) {
                    if(i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
                        html += `<a href="#" class="page-btn ${i === page ? 'active' : ''}" data-page="${i}">${i}</a>`;
                    } else if (i === page - 2 || i === page + 2) {
                        html += `<span class="page-btn dots">...</span>`;
                    }
                }
                
                html += `<a href="#" class="page-btn next" data-page="${page+1}"><i class="fa-solid fa-chevron-right"></i></a>`;
                
                controls.innerHTML = html;
                
                // Add click events
                controls.querySelectorAll('.page-btn:not(.dots)').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const newPage = parseInt(this.getAttribute('data-page'));
                        if(newPage >= 1 && newPage <= totalPages) {
                            currentPage = newPage;
                            renderTable(currentPage);
                        }
                    });
                });
            };

            if(rows.length > 0) {
                renderTable(currentPage);
            }
        });
    </script>
</body>
</html>

