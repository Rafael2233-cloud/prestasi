<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: Index.php"); exit; }
require '../koneksi.php';

// Get Stats
$q_pending = $conn->query("SELECT COUNT(*) as cnt FROM prestasi WHERE status='pending'");
$menunggu = $q_pending->fetch_assoc()['cnt'];

date_default_timezone_set('Asia/Jakarta');
$bulan = array(
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
);
$tanggal_hari_ini = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');

// Query Mahasiswa + Poin dari view_leaderboard
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'nim';
$sort_dir = isset($_GET['sort_dir']) ? strtoupper($_GET['sort_dir']) : 'ASC';

$allowed_sort = ['nim' => 'm.nim', 'nama' => 'm.nama', 'prodi' => 'm.prodi', 'angkatan' => 'm.angkatan'];
$order_by = isset($allowed_sort[$sort_by]) ? $allowed_sort[$sort_by] : 'm.nim';

$allowed_dir = ['ASC', 'DESC'];
if (!in_array($sort_dir, $allowed_dir)) {
    $sort_dir = 'ASC';
}

$q_mhs = $conn->query("
    SELECT m.*, 
        COALESCE(SUM(
            CASE WHEN p.status = 'approved' THEN 
                (SELECT pc.poin FROM poin_config pc WHERE (pc.tingkat = p.tingkat OR (p.tingkat = 'Kota/Kabupaten' AND pc.tingkat IN ('Kota','Kabupaten'))) AND pc.juara = p.juara ORDER BY pc.poin DESC LIMIT 1) 
            ELSE 0 END
        ), 0) AS total_poin 
    FROM mahasiswa m 
    LEFT JOIN prestasi p ON m.id = p.mahasiswa_id
    GROUP BY m.id, m.nim, m.nama, m.prodi, m.angkatan, m.email, m.telp, m.ipk
    ORDER BY $order_by $sort_dir
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa - Admin Portal</title>
    
    <!-- Modern font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="Assets/Css/Style.css?v=<?php echo time(); ?>">
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

        <!-- Dashboard Content -->
        <div class="content-wrapper-new">
            <div class="page-header-new">
                <div class="page-header-flex">
                    <div class="page-title-new">
                        <div>
                            <h1 class="page-title-text">Data Mahasiswa</h1>
                            <p>Kelola data mahasiswa yang terdaftar dalam sistem</p>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                        <div class="breadcrumb-top" style="margin-bottom: 0;">
                            <a href="Dashboard.php">Beranda</a> <span>/</span> Data Mahasiswa
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="verifikasi-panel">
                <div class="panel-header-flex">
                    <h2>Daftar Mahasiswa</h2>
                    <div class="filter-actions" style="display: flex; gap: 15px;">
                        <div style="position: relative;">
                            <input type="text" id="searchMahasiswa" class="custom-input" placeholder="Cari nama mahasiswa..." style="padding: 8px 15px 8px 35px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 13px; outline: none; width: 250px; color: #4b5563;">
                            <i class="fa-solid fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 13px;"></i>
                        </div>
                        <select id="filterAngkatan" class="custom-select">
                            <option value="">Semua Angkatan</option>
                            <option value="2022">2022</option>
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                        </select>
                    </div>
                </div>

                <style>
                    .sort-link {
                        color: inherit;
                        text-decoration: none;
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                        transition: color 0.2s;
                    }
                    .sort-link:hover {
                        color: #3b82f6;
                    }
                    .sort-icon-active {
                        color: #3b82f6;
                    }
                    .sort-icon-inactive {
                        color: #cbd5e1;
                    }
                </style>
                <div class="table-responsive">
                    <table class="verifikasi-table">
                        <thead>
                            <?php
                            function getSortIcon($col, $current_sort, $current_dir) {
                                if ($current_sort == $col) {
                                    return $current_dir == 'ASC' ? '<i class="fa-solid fa-sort-up sort-icon-active"></i>' : '<i class="fa-solid fa-sort-down sort-icon-active"></i>';
                                }
                                return '<i class="fa-solid fa-sort sort-icon-inactive"></i>';
                            }
                            function getNextDir($col, $current_sort, $current_dir) {
                                if ($current_sort == $col && $current_dir == 'ASC') return 'DESC';
                                return 'ASC';
                            }
                            ?>
                            <tr>
                                <th><a href="?sort_by=nim&sort_dir=<?= getNextDir('nim', $sort_by, $sort_dir) ?>" class="sort-link">NIM <?= getSortIcon('nim', $sort_by, $sort_dir) ?></a></th>
                                <th><a href="?sort_by=nama&sort_dir=<?= getNextDir('nama', $sort_by, $sort_dir) ?>" class="sort-link">NAMA LENGKAP <?= getSortIcon('nama', $sort_by, $sort_dir) ?></a></th>
                                <th><a href="?sort_by=prodi&sort_dir=<?= getNextDir('prodi', $sort_by, $sort_dir) ?>" class="sort-link">PROGRAM STUDI <?= getSortIcon('prodi', $sort_by, $sort_dir) ?></a></th>
                                <th><a href="?sort_by=angkatan&sort_dir=<?= getNextDir('angkatan', $sort_by, $sort_dir) ?>" class="sort-link">ANGKATAN <?= getSortIcon('angkatan', $sort_by, $sort_dir) ?></a></th>
                                <th>TOTAL POIN</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $q_mhs->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nim']) ?></td>
                                <td class="nama-mhs"><?= htmlspecialchars($row['nama']) ?></td>
                                <td class="prodi-mhs"><?= htmlspecialchars($row['prodi']) ?></td>
                                <td class="angkatan-mhs"><?= htmlspecialchars($row['angkatan']) ?></td>
                                <td><strong style="color: #3b82f6;"><?= $row['total_poin'] ?> poin</strong></td>
                                <td>
                                    <div class="action-buttons" style="flex-direction: row;">
                                        <a href="DetailMahasiswa.php?id=<?= $row['id'] ?>" class="btn-horizontal btn-detail"><i class="fa-solid fa-eye"></i> Lihat</a>
                                        <button class="btn-horizontal btn-reject btn-hapus-data"><i class="fa-solid fa-trash"></i> Hapus</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($q_mhs->num_rows == 0): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">Tidak ada data mahasiswa.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>


    </main>

    <!-- Custom JS -->
    <script src="Assets/Js/Script.js?v=<?php echo time(); ?>"></script>
</body>
</html>

