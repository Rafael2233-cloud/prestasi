<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: Index.php"); exit; }
require '../koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("
    SELECT p.*, m.nim, m.nama, m.prodi, m.angkatan
    FROM prestasi p 
    JOIN mahasiswa m ON p.mahasiswa_id = m.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$prestasi = $stmt->get_result()->fetch_assoc();

if (!$prestasi) {
    header("Location: VerifikasiPrestasi.php");
    exit;
}

// Logic from VerifikasiPrestasi.php
$is_akademik = in_array($prestasi['jenis'], ['Programming / Software Development', 'Data Science / AI', 'Karya Ilmiah (LKTI, Esai, PKM)', 'Inovasi Teknologi', 'Bisnis & Startup', 'UI/UX & Desain Sistem', 'Sains (Matematika, Fisika, dll)']);
$kategori_utama = $prestasi['kategori'] ? $prestasi['kategori'] : ($is_akademik ? 'Akademik' : 'Non-Akademik');
$subkategori = $prestasi['jenis'];

// Fetch stats for sidebar
$q_pending = $conn->query("SELECT COUNT(*) as cnt FROM prestasi WHERE status='pending'");
$prestasi_menunggu = $q_pending->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Verifikasi Prestasi - Admin Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="Assets/Css/Style.css?v=<?= time(); ?>">
    <style>
        .detail-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin-bottom: 25px;
        }
        .detail-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .detail-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        .detail-body {
            padding: 25px;
        }
        .info-group {
            margin-bottom: 20px;
        }
        .info-label {
            font-size: 13px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 6px;
        }
        .info-value {
            font-size: 14px;
            color: #111827;
            background: #f9fafb;
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .file-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-top: 10px;
        }
        .file-icon {
            width: 40px;
            height: 40px;
            background: #eff6ff;
            color: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 20px;
        }
        .file-info {
            flex: 1;
        }
        .file-name {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 4px 0;
        }
        .file-size {
            font-size: 12px;
            color: #6b7280;
            margin: 0;
        }
        .file-actions {
            display: flex;
            gap: 10px;
        }
        .btn-file {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-preview {
            background: #eff6ff;
            color: #3b82f6;
            border: 1px solid #bfdbfe;
        }
        .btn-preview:hover {
            background: #dbeafe;
        }
        .btn-download {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .btn-download:hover {
            background: #dcfce7;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #dcfce7; color: #16a34a; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        
        .action-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 20px 25px;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
        }
        .btn-foot {
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-back {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-back:hover { background: #f9fafb; }
        .btn-approve {
            background: #10b981;
            color: white;
        }
        .btn-approve:hover { background: #059669; }
        .btn-reject {
            background: #ef4444;
            color: white;
        }
        .btn-reject:hover { background: #dc2626; }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Preview Modal Styles */
        .preview-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.85);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .preview-overlay.active {
            display: flex;
            opacity: 1;
        }
        .preview-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 1000px;
            height: 85vh;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }
        .preview-overlay.active .preview-content {
            transform: scale(1);
        }
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .preview-title {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .btn-close-preview {
            background: #f3f4f6;
            border: none;
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-close-preview:hover {
            background: #e5e7eb;
            color: #ef4444;
        }
        .preview-body {
            flex: 1;
            overflow: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            padding: 20px;
        }
        .preview-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .preview-pdf {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 8px;
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
                <li class="menu-item-new active">
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
                <li class="menu-item-new"><a href="Leaderboard.php"><i class="fa-solid fa-trophy"></i> <span class="menu-text">Leaderboard</span></a></li>
            </ul>

        </div>

        <div class="sidebar-footer-new">
            <a href="Logout.php" class="logout-btn-new"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span class="menu-text">Logout</span></a>
        </div>
    </aside>

    <main class="main-content-new" id="mainContent">
        <?php include 'Topbar.php'; ?>

        <div class="content-wrapper-new">
            <div class="page-header-new" style="margin-bottom: 25px;">
                <div class="page-header-flex">
                    <div class="page-title-new">
                        <div>
                            <h1 class="page-title-text">Detail Verifikasi Prestasi</h1>
                            <p>Tinjau rincian pengajuan prestasi mahasiswa secara lengkap.</p>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                        <div class="breadcrumb-top" style="margin-bottom: 0;">
                            <a href="Dashboard.php">Beranda</a> <span>/</span> <a href="VerifikasiPrestasi.php">Verifikasi Prestasi</a> <span>/</span> Detail
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-header">
                    <h3>Informasi Pengajuan</h3>
                    <?php 
                        $status = $prestasi['status'];
                        $status_class = $status == 'pending' ? 'status-pending' : ($status == 'approved' ? 'status-approved' : 'status-rejected');
                        $status_text = $status == 'pending' ? 'Menunggu Verifikasi' : ($status == 'approved' ? 'Disetujui' : 'Ditolak');
                    ?>
                    <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                </div>
                
                <div class="detail-body">
                    <div class="grid-2">
                        <!-- Left Column -->
                        <div>
                            <div class="info-group">
                                <div class="info-label">Mahasiswa Pengaju</div>
                                <div class="info-value">
                                    <strong><?= htmlspecialchars($prestasi['nama']) ?></strong><br>
                                    <span style="font-size: 13px; color: #6b7280;"><?= htmlspecialchars($prestasi['nim']) ?> - <?= htmlspecialchars($prestasi['prodi']) ?></span>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Judul Prestasi</div>
                                <div class="info-value"><?= htmlspecialchars($prestasi['judul']) ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Kategori</div>
                                <div class="info-value"><?= htmlspecialchars($kategori_utama) ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Subkategori <?php if($status == 'pending'): ?><span style="font-weight: normal; color: #6b7280; font-size: 11px;">(Dapat disesuaikan)</span><?php endif; ?></div>
                                <?php if($status == 'pending'): ?>
                                    <input type="text" id="adminSubkategori" value="<?= htmlspecialchars($subkategori) ?>" class="info-value" style="width: 100%; box-sizing: border-box; outline: none; border: 1px solid #e5e7eb; padding: 12px 15px;">
                                <?php else: ?>
                                    <div class="info-value"><?= htmlspecialchars($subkategori) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div>
                            <div class="info-group">
                                <div class="info-label">Tingkat Prestasi</div>
                                <div class="info-value"><?= htmlspecialchars($prestasi['tingkat']) ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Pencapaian (Juara)</div>
                                <div class="info-value"><?= htmlspecialchars($prestasi['juara']) ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Tahun</div>
                                <div class="info-value"><?= htmlspecialchars($prestasi['tahun']) ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Tanggal Ajukan</div>
                                <div class="info-value"><?= date('d M Y, H:i', strtotime($prestasi['created_at'])) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="info-group" style="margin-top: 10px;">
                        <div class="info-label">Deskripsi Singkat</div>
                        <div class="info-value" style="min-height: 80px; white-space: pre-wrap;"><?= htmlspecialchars($prestasi['deskripsi']) ?></div>
                    </div>
                    
                    <?php if($status == 'rejected' && !empty($prestasi['alasan_penolakan'])): ?>
                    <div class="info-group">
                        <div class="info-label" style="color: #ef4444;"><i class="fa-solid fa-circle-exclamation"></i> Catatan Admin (Alasan Ditolak)</div>
                        <div class="info-value" style="background: #fef2f2; border-color: #fca5a5; color: #991b1b;">
                            <?= nl2br(htmlspecialchars($prestasi['alasan_penolakan'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($status == 'approved'): ?>
                    <div class="info-group">
                        <div class="info-label" style="color: #10b981;"><i class="fa-solid fa-check-circle"></i> Catatan Admin</div>
                        <div class="info-value" style="background: #f0fdf4; border-color: #86efac; color: #166534;">
                            Data prestasi valid dan poin (<?= $prestasi['poin'] ?> Poin) telah ditambahkan ke sistem.
                        </div>
                    </div>
                    <?php endif; ?>

                    <h4 style="margin: 30px 0 15px 0; font-size: 15px; color: #1f2937; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px;">Dokumen Pendukung</h4>
                    
                    <!-- File Utama -->
                    <div class="info-group">
                        <div class="info-label">Bukti Prestasi (Sertifikat / Piagam)</div>
                        <?php if(!empty($prestasi['bukti_file'])): 
                            $ext = strtolower(pathinfo($prestasi['bukti_file'], PATHINFO_EXTENSION));
                            $icon_class = ($ext == 'pdf') ? 'fa-file-pdf' : 'fa-file-image';
                        ?>
                            <div class="file-item">
                                <div class="file-icon"><i class="fa-regular <?= $icon_class ?>"></i></div>
                                <div class="file-info">
                                    <h4 class="file-name"><?= htmlspecialchars(basename($prestasi['bukti_file'])) ?></h4>
                                    <p class="file-size">Dokumen Utama</p>
                                </div>
                                <div class="file-actions">
                                    <button type="button" onclick="openPreview('../mahasiswa/uploads/<?= htmlspecialchars(basename($prestasi['bukti_file'])) ?>', '<?= $ext ?>')" class="btn-file btn-preview"><i class="fa-regular fa-eye"></i> Lihat File</button>
                                    <a href="../mahasiswa/uploads/<?= htmlspecialchars(basename($prestasi['bukti_file'])) ?>" download class="btn-file btn-download"><i class="fa-solid fa-download"></i> Download</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="info-value" style="color: #6b7280; font-style: italic;">Tidak ada dokumen utama yang diunggah.</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- File Pendukung -->
                    <div class="info-group">
                        <div class="info-label">File Pendukung Tambahan (Opsional)</div>
                        <?php if(!empty($prestasi['file_pendukung']) || !empty($prestasi['bukti_pendukung'])): ?>
                            <?php 
                                $pendukung = !empty($prestasi['file_pendukung']) ? $prestasi['file_pendukung'] : $prestasi['bukti_pendukung'];
                                // If multiple files, take the first one or just display the first one for now
                                $pendukung_array = explode(',', $pendukung);
                                foreach($pendukung_array as $p):
                                    if(empty(trim($p))) continue;
                                    $p_ext = strtolower(pathinfo(trim($p), PATHINFO_EXTENSION));
                                    $p_icon = ($p_ext == 'pdf') ? 'fa-file-pdf' : 'fa-file-image';
                            ?>
                            <div class="file-item">
                                <div class="file-icon" style="background: #f3f4f6; color: #6b7280;"><i class="fa-regular <?= $p_icon ?>"></i></div>
                                <div class="file-info">
                                    <h4 class="file-name"><?= htmlspecialchars(basename(trim($p))) ?></h4>
                                    <p class="file-size">Dokumen Tambahan</p>
                                </div>
                                <div class="file-actions">
                                    <button type="button" onclick="openPreview('../mahasiswa/uploads/<?= htmlspecialchars(basename(trim($p))) ?>', '<?= $p_ext ?>')" class="btn-file btn-preview"><i class="fa-regular fa-eye"></i> Lihat File</button>
                                    <a href="../mahasiswa/uploads/<?= htmlspecialchars(basename(trim($p))) ?>" download class="btn-file btn-download"><i class="fa-solid fa-download"></i> Download</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="info-value" style="color: #6b7280; font-style: italic;">Tidak ada dokumen pendukung tambahan.</div>
                        <?php endif; ?>
                    </div>

                </div>
                
                <!-- Footer Actions -->
                <div class="action-footer">
                    <button class="btn-foot btn-back" onclick="window.location.href='VerifikasiPrestasi.php'">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </button>
                    <?php if($status == 'pending'): ?>
                        <button class="btn-foot btn-reject" onclick="processReject(<?= $prestasi['id'] ?>)">
                            <i class="fa-solid fa-xmark"></i> Tolak
                        </button>
                        <button class="btn-foot btn-approve" onclick="processApprove(<?= $prestasi['id'] ?>)">
                            <i class="fa-solid fa-check"></i> Setujui
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- Modal Preview File -->
    <div id="previewOverlay" class="preview-overlay">
        <div class="preview-content">
            <div class="preview-header">
                <h3 class="preview-title" id="previewTitle">File Preview</h3>
                <button class="btn-close-preview" onclick="closePreview()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="preview-body" id="previewBody">
                <!-- Content injected via JS -->
            </div>
        </div>
    </div>

    <script src="Assets/Js/Script.js?v=<?= time(); ?>"></script>
    <script>
        // Preview Functionality
        function openPreview(url, ext) {
            const overlay = document.getElementById('previewOverlay');
            const body = document.getElementById('previewBody');
            const title = document.getElementById('previewTitle');
            
            const filename = url.substring(url.lastIndexOf('/') + 1);
            title.textContent = filename;
            
            body.innerHTML = '';
            
            if (ext === 'pdf') {
                body.innerHTML = `<iframe src="${url}" class="preview-pdf"></iframe>`;
            } else {
                body.innerHTML = `<img src="${url}" class="preview-image" alt="Preview Gambar">`;
            }
            
            overlay.style.display = 'flex';
            void overlay.offsetWidth; // force reflow
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePreview() {
            const overlay = document.getElementById('previewOverlay');
            overlay.classList.remove('active');
            setTimeout(() => {
                overlay.style.display = 'none';
                document.getElementById('previewBody').innerHTML = '';
                document.body.style.overflow = '';
            }, 300);
        }

        document.getElementById('previewOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const overlay = document.getElementById('previewOverlay');
                if (overlay.classList.contains('active')) {
                    closePreview();
                }
            }
        });

        function processApprove(id) {
            Swal.fire({
                title: 'Setujui Prestasi?',
                text: 'Poin akan ditambahkan secara otomatis ke total poin mahasiswa dan memperbarui leaderboard.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Setujui',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitVerifikasi(id, 'approve', '');
                }
            });
        }

        function processReject(id) {
            Swal.fire({
                title: 'Tolak Prestasi',
                input: 'textarea',
                inputLabel: 'Catatan Admin / Alasan Penolakan',
                inputPlaceholder: 'Tuliskan alasan penolakan...',
                inputAttributes: {
                    'aria-label': 'Tuliskan alasan penolakan'
                },
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Tolak',
                cancelButtonText: 'Batal',
                preConfirm: (alasan) => {
                    if (!alasan) {
                        Swal.showValidationMessage('Alasan penolakan wajib diisi!');
                    }
                    return alasan;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitVerifikasi(id, 'reject', result.value);
                }
            });
        }

        function submitVerifikasi(id, action, alasan) {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('id', id);
            
            // Get subkategori baru if it exists
            const subkategoriInput = document.getElementById('adminSubkategori');
            if(subkategoriInput) {
                fd.append('subkategori_baru', subkategoriInput.value);
            }
            
            if(alasan) fd.append('alasan', alasan);
            
            fetch('ApiVerifikasi.php', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: data.message,
                        icon: 'success'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            });
        }
    </script>
</body>
</html>
