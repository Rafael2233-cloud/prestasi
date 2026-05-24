<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: Index.php"); exit; }
require '../koneksi.php';

// Get Stats for Topbar notification
$q_pending = $conn->query("SELECT COUNT(*) as cnt FROM prestasi WHERE status='pending'");
$menunggu = $q_pending->fetch_assoc()['cnt'];

$id_mhs = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$q_mhs = $conn->prepare("SELECT m.*, 
        COALESCE(SUM(
            CASE WHEN p.status = 'approved' THEN 
                (SELECT pc.poin FROM poin_config pc WHERE (pc.tingkat = p.tingkat OR (p.tingkat = 'Kota/Kabupaten' AND pc.tingkat IN ('Kota','Kabupaten'))) AND pc.juara = p.juara ORDER BY pc.poin DESC LIMIT 1) 
            ELSE 0 END
        ), 0) AS total_poin 
    FROM mahasiswa m 
    LEFT JOIN prestasi p ON m.id = p.mahasiswa_id
    WHERE m.id = ?
    GROUP BY m.id");
$q_mhs->bind_param("i", $id_mhs);
$q_mhs->execute();
$result_mhs = $q_mhs->get_result();

if ($result_mhs->num_rows == 0) {
    echo "<script>alert('Data mahasiswa tidak ditemukan!'); window.location.href='DataMahasiswa.php';</script>";
    exit;
}
$mhs = $result_mhs->fetch_assoc();

// Prestasi counts
$q_pres_count = $conn->query("SELECT COUNT(*) as total_prestasi FROM prestasi WHERE mahasiswa_id = $id_mhs AND status = 'approved'");
$total_prestasi = $q_pres_count->fetch_assoc()['total_prestasi'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Mahasiswa - Admin Portal</title>
    <!-- Modern font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="Assets/Css/Style.css?v=<?= time() ?>">
    <style>
        .profile-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid #f0f0f0;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
        }
        @media (min-width: 768px) {
            .profile-container {
                flex-direction: row;
            }
        }
        .profile-left {
            padding: 40px;
            text-align: center;
            border-right: 1px solid #f0f0f0;
            flex: 1;
            max-width: 350px;
        }
        .profile-right {
            padding: 40px;
            flex: 2;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background-color: #3b82f6;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
            font-weight: 700;
            margin: 0 auto 20px;
        }
        .profile-name {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .profile-role-badge {
            display: inline-block;
            background-color: #eff6ff;
            color: #3b82f6;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        .profile-stats-list {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
        }
        .profile-stats-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }
        .profile-stats-list li:last-child {
            border-bottom: none;
        }
        .profile-stats-list li .stat-label {
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .profile-stats-list li .stat-value {
            font-weight: 600;
            color: #334155;
        }
        .stat-value.status-active {
            color: #10b981;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 25px;
        }
        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        @media (max-width: 640px) {
            .info-list {
                grid-template-columns: 1fr;
            }
        }
        .info-list li {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px dashed #e2e8f0;
            align-items: flex-start;
        }
        .info-list li.full-width {
            grid-column: 1 / -1;
        }
        .info-list li .info-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #f1f5f9;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .info-list li .info-label {
            width: 120px;
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            padding-top: 8px;
            flex-shrink: 0;
        }
        .info-list li .info-value {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
            padding-top: 8px;
            word-break: break-word;
        }
        
        /* Header Styles */
        .page-header-new { margin-bottom: 25px; }
        .breadcrumb-top { font-size: 13px; color: #2563eb; font-weight: 500; text-align: right; margin-bottom: 5px; }
        .breadcrumb-top span { color: #6b7280; }
        .breadcrumb-top a { color: #2563eb; text-decoration: none; }
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; width: 100%; }
        .page-title-new { display: flex; align-items: center; gap: 15px; }
        
        @media (max-width: 768px) {
            .page-header-flex { flex-direction: column; align-items: flex-start; gap: 15px; }
            .breadcrumb-top { align-self: flex-start; }
        }

        .btn-back {
            background-color: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background-color: #f8fafc;
            color: #1e293b;
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
                        <?php if(isset($menunggu) && $menunggu > 0): ?><span class="badge"><?= $menunggu ?></span><?php endif; ?>
                    </a>
                </li>
            </ul>

            <div class="sidebar-menu-title mt-custom">MANAJEMEN DATA</div>
            <ul class="sidebar-menu-new">
                <li class="menu-item-new active"><a href="DataMahasiswa.php"><i class="fa-solid fa-users"></i> <span class="menu-text">Data Mahasiswa</span></a></li>
                <li class="menu-item-new"><a href="KriteriaPoin.php"><i class="fa-solid fa-gear"></i> <span class="menu-text">Kriteria Poin</span></a></li>
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

        <!-- Content -->
        <div class="content-wrapper-new">
            <div class="page-header-new">
                <div class="page-header-flex">
                    <div class="page-title-new">
                        <div class="page-title-text">
                            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0 0 5px 0;">Biodata Mahasiswa</h1>
                            <p style="font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 400; color: #64748b; line-height: 1.5; margin: 0;">Lihat informasi lengkap mahasiswa beserta rekap prestasinya.</p>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                        <div class="breadcrumb-top">
                            <a href="Dashboard.php">Beranda</a> <span>/</span> <a href="DataMahasiswa.php">Data Mahasiswa</a> <span>/</span> Detail
                        </div>
                        <a href="DataMahasiswa.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
                    </div>
                </div>
            </div>
            
            <div class="profile-container">
                <!-- Left Column -->
                <div class="profile-left">
                    <div class="profile-avatar"><?= substr(htmlspecialchars($mhs['nama']), 0, 1) ?></div>
                    <div class="profile-name"><?= htmlspecialchars($mhs['nama']) ?></div>
                    <div style="font-size: 13px; color: #64748b; margin-bottom: 5px;">NIM: <?= htmlspecialchars($mhs['nim']) ?></div>
                    <div class="profile-role-badge">Mahasiswa Aktif</div>
                    
                    <ul class="profile-stats-list">
                        <li>
                            <span class="stat-label"><i class="fa-solid fa-trophy"></i> Total Prestasi</span>
                            <span class="stat-value" style="color:#10b981;"><?= $total_prestasi ?> Disetujui</span>
                        </li>
                        <li>
                            <span class="stat-label"><i class="fa-solid fa-star"></i> Total Poin</span>
                            <span class="stat-value" style="color:#3b82f6;"><?= $mhs['total_poin'] ?> Poin</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Right Column -->
                <div class="profile-right">
                    <h3 class="section-title">Informasi Akademik & Kontak</h3>
                    
                    <ul class="info-list">
                        <li class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                            <div class="info-label">Program Studi</div>
                            <div class="info-value"><?= htmlspecialchars($mhs['prodi']) ?></div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon"><i class="fa-regular fa-calendar-days"></i></div>
                            <div class="info-label">Angkatan</div>
                            <div class="info-value"><?= htmlspecialchars($mhs['angkatan']) ?></div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon"><i class="fa-regular fa-envelope"></i></div>
                            <div class="info-label">Email Valid</div>
                            <div class="info-value"><?= htmlspecialchars($mhs['email']) ?></div>
                        </li>
                        <li class="info-item full-width">
                            <div class="info-icon"><i class="fa-solid fa-user-tie"></i></div>
                            <div class="info-label" style="width: 170px;">Pembimbing Akademik</div>
                            <div class="info-value"><?= htmlspecialchars($mhs['pembimbing_akademik']) ?></div>
                        </li>
                        <li class="info-item full-width">
                            <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
                            <div class="info-label" style="width: 170px;">No. Telepon / WA</div>
                            <div class="info-value"><?= htmlspecialchars($mhs['telp']) ?></div>
                        </li>
                    </ul>
                    
                </div>
            </div>
        </div>
    </main>

    <!-- Custom JS -->
    <script src="Assets/Js/Script.js?v=<?= time() ?>"></script>
</body>
</html>
