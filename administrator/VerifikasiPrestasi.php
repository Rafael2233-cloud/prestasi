<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: Index.php");
    exit;
}
require '../koneksi.php';

// Get Stats
$q_pending = $conn->query("SELECT COUNT(*) as cnt FROM prestasi WHERE status='pending'");
$menunggu = $q_pending->fetch_assoc()['cnt'];

date_default_timezone_set('Asia/Jakarta');
$bulan = array(
    1 => 'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
);
$tanggal_hari_ini = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');

$q_prestasi = $conn->query("
    SELECT p.*, m.nim, m.nama, m.prodi, m.angkatan,
           (SELECT pc.poin FROM poin_config pc 
            WHERE (pc.tingkat = p.tingkat OR (p.tingkat = 'Kota/Kabupaten' AND pc.tingkat IN ('Kota', 'Kabupaten'))) 
              AND pc.juara = p.juara 
            ORDER BY pc.poin DESC LIMIT 1) as poin_otomatis
    FROM prestasi p 
    JOIN mahasiswa m ON p.mahasiswa_id = m.id 
    ORDER BY 
        CASE p.status
            WHEN 'approved' THEN 1
            WHEN 'pending' THEN 2
            WHEN 'rejected' THEN 3
            ELSE 4
        END ASC,
        CASE WHEN p.status = 'approved' THEN COALESCE((SELECT pc.poin FROM poin_config pc 
            WHERE (pc.tingkat = p.tingkat OR (p.tingkat = 'Kota/Kabupaten' AND pc.tingkat IN ('Kota', 'Kabupaten'))) 
              AND pc.juara = p.juara 
            ORDER BY pc.poin DESC LIMIT 1), 0) ELSE 0 END DESC,
        p.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Prestasi - Admin Portal</title>

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
                <li class="menu-item-new active">
                    <a href="VerifikasiPrestasi.php">
                        <i class="fa-solid fa-check-to-slot"></i> <span class="menu-text">Verifikasi Prestasi</span>
                        <?php if (isset($prestasi_menunggu) && $prestasi_menunggu > 0): ?><span class="badge"><?= $prestasi_menunggu ?></span><?php endif; ?>
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
                        <div>
                            <h1 class="page-title-text">Verifikasi Prestasi</h1>
                            <p>Tinjau dan verifikasi pengajuan prestasi mahasiswa</p>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                        <div class="breadcrumb-top" style="margin-bottom: 0;">
                            <a href="Dashboard.php">Beranda</a> <span>/</span> Verifikasi Prestasi
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($menunggu > 0): ?>
                <div class="notification-banner" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid var(--primary-color); padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                    <div style="background: var(--primary-color); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: var(--text-color); font-size: 1rem;">Notifikasi Baru</h4>
                        <p style="margin: 5px 0 0 0; color: var(--text-muted); font-size: 0.9rem;">Terdapat <strong><?= $menunggu ?> pengajuan</strong> prestasi yang menunggu untuk diverifikasi.</p>
                    </div>
                </div>
            <?php endif; ?>


            <div class="verifikasi-panel">
                <div class="panel-header-flex" style="flex-wrap: wrap; gap: 15px;">
                    <h2>Daftar Prestasi Menunggu Verifikasi</h2>
                    <div class="filter-actions" style="flex-wrap: wrap;">
                        <select class="custom-select" id="filterKategori">
                            <option value="">Semua Kategori</option>
                            <option value="Akademik">Akademik</option>
                            <option value="Non-Akademik">Non-Akademik</option>
                        </select>


                        <select class="custom-select" id="filterTingkat">
                            <option value="">Semua Tingkat</option>
                            <option value="Internasional">Internasional</option>
                            <option value="Nasional">Nasional</option>
                            <option value="Provinsi">Provinsi</option>
                            <option value="Kota">Kota</option>
                            <option value="Kabupaten">Kabupaten</option>
                            <option value="Kampus">Kampus</option>
                        </select>

                        <select class="custom-select" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="Menunggu">Menunggu</option>
                            <option value="Disetujui">Disetujui</option>
                            <option value="Ditolak">Ditolak</option>
                        </select>

                        <select class="custom-select" id="filterTahun">
                            <option value="">Semua Tahun</option>
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="verifikasi-table">
                        <thead>
                            <tr>
                                <th>TANGGAL AJUKAN</th>
                                <th>MAHASISWA</th>
                                <th>NAMA PRESTASI</th>
                                <th>KATEGORI</th>
                                <th>SUBKATEGORI</th>
                                <th>TINGKAT</th>
                                <th>PENCAPAIAN (JUARA)</th>
                                <th>STATUS</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $q_prestasi->fetch_assoc()):
                                // Map jenis to kategori utama and subkategori
                                $is_akademik = in_array($row['jenis'], ['Sains', 'Teknologi']);
                                $kategori_utama = $is_akademik ? 'Akademik' : 'Nonakademik';
                                $subkategori = $row['jenis'];

                                // Inject into row for json_encode
                                $row['kategori_utama'] = $kategori_utama;
                                $row['subkategori_utama'] = $subkategori;
                            ?>
                                <tr data-status="<?= htmlspecialchars($row['status']) ?>" data-kategori="<?= htmlspecialchars($kategori_utama) ?>" data-subkategori="<?= htmlspecialchars($subkategori) ?>" data-tahun="<?= htmlspecialchars($row['tahun']) ?>" data-id="<?= htmlspecialchars($row['id']) ?>">
                                    <td><?= date('d M Y') /* Assuming no date column in schema currently, so just show current or fake date */ ?></td>
                                    <td>
                                        <div class="mhs-name"><?= htmlspecialchars($row['nama']) ?></div>
                                        <div class="mhs-nim">NIM: <?= htmlspecialchars($row['nim']) ?></div>
                                    </td>
                                    <td>
                                        <div class="prestasi-name"><?= htmlspecialchars($row['judul']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($kategori_utama) ?></td>
                                    <td><?= htmlspecialchars($subkategori) ?></td>
                                    <td><?= htmlspecialchars($row['tingkat']) ?></td>
                                    <td><?= htmlspecialchars($row['juara']) ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <span class="status-badge status-menunggu">Menunggu</span>
                                        <?php elseif ($row['status'] == 'approved'): ?>
                                            <span class="status-badge status-disetujui">Disetujui</span>
                                        <?php elseif ($row['status'] == 'rejected'): ?>
                                            <span class="status-badge status-ditolak">Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-detail" onclick="window.location.href='DetailVerifikasi.php?id=<?= $row['id'] ?>'">
                                                <i class="fa-solid fa-eye"></i><span>Detail</span>
                                            </button>
                                            <?php if ($row['status'] == 'pending'): ?>
                                                <button class="btn-action btn-approve" onclick="approvePrestasi(<?= $row['id'] ?>)">
                                                    <i class="fa-solid fa-check"></i><span>Setuju</span>
                                                </button>
                                                <button class="btn-action btn-reject" onclick="rejectPrestasi(<?= $row['id'] ?>)">
                                                    <i class="fa-solid fa-xmark"></i><span>Tolak</span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($q_prestasi->num_rows == 0): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 20px;">Tidak ada pengajuan prestasi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div id="rejectModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Tolak Prestasi</h3>
                    <button class="close-modal" onclick="closeRejectModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <p>Masukkan alasan penolakan:</p>
                    <textarea id="rejectReason" class="custom-textarea" rows="4" placeholder="Contoh: Bukti sertifikat buram..."></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn-cancel" onclick="closeRejectModal()">Batal</button>
                    <button class="btn-submit-reject" onclick="submitReject()">Tolak Prestasi</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Custom JS -->
    <script src="Assets/Js/Script.js?v=<?php echo time(); ?>"></script>
</body>

</html>