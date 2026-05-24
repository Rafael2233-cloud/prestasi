<?php
session_start();

// Validasi session
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require '../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_prodi = isset($_GET['prodi']) ? $_GET['prodi'] : '';
$filter_tingkat = isset($_GET['tingkat']) ? $_GET['tingkat'] : '';
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
$filter_angkatan = isset($_GET['angkatan']) ? $_GET['angkatan'] : '';

// Base query
$query = "
    SELECT p.id, m.nim, m.nama, m.prodi, m.angkatan, p.judul AS nama_prestasi, p.kategori, p.tingkat, p.juara, p.created_at AS tanggal, p.tahun, p.status,
    (SELECT pc.poin FROM poin_config pc WHERE (pc.tingkat = p.tingkat OR (p.tingkat = 'Kota/Kabupaten' AND pc.tingkat IN ('Kota','Kabupaten'))) AND pc.juara = p.juara ORDER BY pc.poin DESC LIMIT 1) AS poin
    FROM prestasi p
    JOIN mahasiswa m ON p.mahasiswa_id = m.id
    WHERE p.status = 'approved'
";

if ($search !== '') {
    $query .= " AND (m.nama LIKE '%" . $conn->real_escape_string($search) . "%' OR m.nim LIKE '%" . $conn->real_escape_string($search) . "%')";
}
if ($filter_prodi !== '') {
    $query .= " AND m.prodi = '" . $conn->real_escape_string($filter_prodi) . "'";
}
if ($filter_tingkat !== '') {
    $query .= " AND p.tingkat = '" . $conn->real_escape_string($filter_tingkat) . "'";
}
if ($filter_tahun !== '') {
    $query .= " AND p.tahun = '" . $conn->real_escape_string($filter_tahun) . "'";
}
if ($filter_angkatan !== '') {
    $query .= " AND m.angkatan = '" . $conn->real_escape_string($filter_angkatan) . "'";
}

$query .= " ORDER BY p.created_at DESC, m.nama ASC";

// EXCEL EXPORT
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"Rekap_Prestasi_Pimpinan.xls\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr>
            <th style='background-color:#1e3a8a; color:white;'>No</th>
            <th style='background-color:#1e3a8a; color:white;'>NIM</th>
            <th style='background-color:#1e3a8a; color:white;'>Nama Mahasiswa</th>
            <th style='background-color:#1e3a8a; color:white;'>Program Studi</th>
            <th style='background-color:#1e3a8a; color:white;'>Angkatan</th>
            <th style='background-color:#1e3a8a; color:white;'>Nama Prestasi</th>
            <th style='background-color:#1e3a8a; color:white;'>Kategori</th>
            <th style='background-color:#1e3a8a; color:white;'>Tingkat</th>
            <th style='background-color:#1e3a8a; color:white;'>Juara</th>
            <th style='background-color:#1e3a8a; color:white;'>Tahun Prestasi</th>
            <th style='background-color:#1e3a8a; color:white;'>Poin</th>
          </tr>";

    $q_export = $conn->query($query);
    $no = 1;
    while($row = $q_export->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $no++ . "</td>";
        echo "<td>" . htmlspecialchars($row['nim']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
        echo "<td>" . htmlspecialchars($row['prodi']) . "</td>";
        echo "<td>" . htmlspecialchars($row['angkatan']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_prestasi']) . "</td>";
        echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
        echo "<td>" . htmlspecialchars($row['tingkat']) . "</td>";
        echo "<td>" . htmlspecialchars($row['juara']) . "</td>";
        echo "<td>" . htmlspecialchars($row['tahun']) . "</td>";
        echo "<td>" . ($row['poin'] ?? 0) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

$result = $conn->query($query);
$prestasi_data = [];
while($row = $result->fetch_assoc()) {
    $prestasi_data[] = $row;
}

$prodis = [];
$q_prodi = $conn->query("SELECT DISTINCT prodi FROM mahasiswa WHERE prodi != '' AND prodi IS NOT NULL ORDER BY prodi ASC");
while($row = $q_prodi->fetch_assoc()) { $prodis[] = $row['prodi']; }

$angkatans = [];
$q_angkatan = $conn->query("SELECT DISTINCT angkatan FROM mahasiswa WHERE angkatan != '' AND angkatan IS NOT NULL ORDER BY angkatan DESC");
while($row = $q_angkatan->fetch_assoc()) { $angkatans[] = $row['angkatan']; }

$tahuns = [];
$q_tahun = $conn->query("SELECT DISTINCT tahun FROM prestasi WHERE tahun != '' AND tahun IS NOT NULL ORDER BY tahun DESC");
while($row = $q_tahun->fetch_assoc()) { $tahuns[] = $row['tahun']; }

$tingkats = ["Internasional", "Nasional", "Provinsi", "Kota/Kabupaten", "Kecamatan", "Sekolah", "Universitas", "Fakultas", "Program Studi"];

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
    <title>Rekap Prestasi - SIPRESMA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../administrator/Assets/Css/Style.css?v=<?= time() ?>">
    <style>
        .content-wrapper-new { padding: 25px 30px; }
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px; }
        
        .filter-card { background: white; border-radius: 12px; padding: 20px 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .filter-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .filter-header-title { font-size: 15px; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        
        .filter-controls { display: flex; gap: 20px; align-items: stretch; flex-wrap: wrap; }
        .filter-group { flex: 1; min-width: 200px; position: relative; }
        .filter-label { position: absolute; top: -8px; left: 12px; background: white; padding: 0 6px; font-size: 11px; color: #64748b; font-weight: 500; z-index: 10; pointer-events: none; }
        .filter-select, .filter-input { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; color: #334155; font-size: 14px; outline: none; transition: border-color 0.2s; background-color: white; }
        .filter-select { appearance: none; background: url('data:image/svg+xml;utf8,<svg fill="%2364748b" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 10px center; cursor: pointer; }
        .filter-select:focus, .filter-input:focus { border-color: #2563eb; }
        
        .btn-reset { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 0 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; height: 45px; text-decoration: none; }
        .btn-reset:hover { background: #e2e8f0; color: #1e293b; }
        
        .btn-search { background: #2563eb; color: white; border: none; padding: 0 25px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; height: 45px; }
        .btn-search:hover { background: #1d4ed8; }

        .btn-export-excel { background: white; color: #10b981; border: 1px solid #10b981; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; text-decoration: none; }
        .btn-export-excel:hover { background: #ecfdf5; }
        
        .btn-export-pdf { background: white; color: #ef4444; border: 1px solid #ef4444; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; text-decoration: none; }
        .btn-export-pdf:hover { background: #fef2f2; }
        
        .export-actions { display: flex; gap: 10px; }

        .table-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 30px; }
        .table-responsive { overflow-x: auto; }
        .custom-table { width: 100%; border-collapse: collapse; text-align: left; white-space: nowrap; }
        .custom-table th { padding: 15px 25px; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; }
        .custom-table td { padding: 15px 25px; font-size: 13px; border-bottom: 1px solid #f1f5f9; color: #475569; vertical-align: middle; }
        .custom-table tr:nth-child(even) td { background: #fafafa; }
        .custom-table tr:hover td { background: #f8fafc; }
        
        .col-name { font-weight: 700; color: #0f172a; }
        .col-points { font-weight: 700; color: #2563eb; }
        
        .student-cell { display: flex; align-items: center; gap: 12px; }
        .avatar-sm { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: white; background: #3b82f6; }
        
        .badge-tingkat { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-internasional { background: #ede9fe; color: #6d28d9; }
        .badge-nasional { background: #dcfce7; color: #15803d; }
        .badge-provinsi { background: #fef9c3; color: #a16207; }
        .badge-default { background: #f1f5f9; color: #475569; }
        
        /* Print Styles for PDF Export */
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .sidebar-new, .topbar-new, .filter-card, .export-actions, .breadcrumb-top { display: none !important; }
            .main-content-new { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .content-wrapper-new { padding: 10px !important; }
            .table-card { border: none; box-shadow: none; }
            .custom-table { width: 100%; border: 1px solid #ddd; }
            .custom-table th, .custom-table td { padding: 8px; border: 1px solid #ddd; font-size: 12pt; white-space: normal; }
            @page { size: landscape; margin: 1cm; }
        }
    </style>
</head>
<body class="dashboard-body new-dashboard">
    <?php include 'LayoutSidebar.php'; ?>
    <main class="main-content-new" id="mainContent">
        <?php include 'Topbar.php'; ?>

        <div class="content-wrapper-new">
            
            <div class="page-header-new">
                <div class="page-header-flex">
                    <div class="page-title-new">
                        <div class="page-header-text">
                            <h1 style="margin: 0 0 5px 0; font-size: 24px; font-weight: 800; color: #0f172a;">Rekap Prestasi Mahasiswa</h1>
                            <p style="font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 400; color: #64748b; line-height: 1.5; margin: 0;">Data prestasi mahasiswa yang telah diverifikasi dan disetujui.</p>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                        <div class="breadcrumb-top" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
                            <a href="Dashboard.php" style="color: #2563eb; text-decoration: none;">Beranda</a> <span>/</span> Rekap Prestasi
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <form action="RekapPrestasi.php" method="GET" id="filterForm">
                <div class="filter-card">
                    <div class="filter-header">
                        <div class="filter-header-title"><i class="fa-solid fa-filter"></i> Filter Pencarian</div>
                        <div class="export-actions">
                            <!-- BUTTONS MATCHING THE UPLOADED IMAGE -->
                            <a href="RekapPrestasi.php?export=excel&search=<?= urlencode($search) ?>&prodi=<?= urlencode($filter_prodi) ?>&angkatan=<?= urlencode($filter_angkatan) ?>&tingkat=<?= urlencode($filter_tingkat) ?>&tahun=<?= urlencode($filter_tahun) ?>" class="btn-export-excel">
                                <i class="fa-regular fa-file-excel"></i> Export Excel
                            </a>
                            <button type="button" class="btn-export-pdf" onclick="window.print()">
                                <i class="fa-regular fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                    </div>
                    <div class="filter-controls">
                        <div class="filter-group">
                            <label class="filter-label">Cari Nama/NIM</label>
                            <input type="text" class="filter-input" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan kata kunci...">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Program Studi</label>
                            <select class="filter-select" name="prodi">
                                <option value="">Semua Program Studi</option>
                                <?php foreach($prodis as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $filter_prodi === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Angkatan</label>
                            <select class="filter-select" name="angkatan">
                                <option value="">Semua Angkatan</option>
                                <?php foreach($angkatans as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>" <?= $filter_angkatan === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Tingkat Prestasi</label>
                            <select class="filter-select" name="tingkat">
                                <option value="">Semua Tingkat</option>
                                <?php foreach($tingkats as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>" <?= $filter_tingkat === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Tahun Prestasi</label>
                            <select class="filter-select" name="tahun">
                                <option value="">Semua Tahun</option>
                                <?php foreach($tahuns as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>" <?= $filter_tahun === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
                        <a href="RekapPrestasi.php" class="btn-reset"><i class="fa-solid fa-rotate-right"></i> Reset</a>
                    </div>
                </div>
            </form>
            
            <!-- Table Card -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="custom-table" id="prestasiTable">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>MAHASISWA</th>
                                <th>PROGRAM STUDI</th>
                                <th>ANGKATAN</th>
                                <th>NAMA PRESTASI</th>
                                <th>TINGKAT</th>
                                <th>JUARA</th>
                                <th>TAHUN PRESTASI</th>
                                <th>POIN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach($prestasi_data as $row): 
                                $tingkat_class = 'badge-default';
                                if($row['tingkat'] == 'Internasional') $tingkat_class = 'badge-internasional';
                                elseif($row['tingkat'] == 'Nasional') $tingkat_class = 'badge-nasional';
                                elseif($row['tingkat'] == 'Provinsi') $tingkat_class = 'badge-provinsi';
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="student-cell">
                                        <div class="avatar-sm"><?= getInitials($row['nama']) ?></div>
                                        <div>
                                            <div class="col-name"><?= htmlspecialchars($row['nama']) ?></div>
                                            <div style="font-size:11px; color:#64748b;"><?= htmlspecialchars($row['nim']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['prodi']) ?></td>
                                <td><?= htmlspecialchars($row['angkatan']) ?></td>
                                <td>
                                    <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($row['nama_prestasi']) ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($row['kategori']) ?></div>
                                </td>
                                <td><span class="badge-tingkat <?= $tingkat_class ?>"><?= htmlspecialchars($row['tingkat']) ?></span></td>
                                <td><?= htmlspecialchars($row['juara']) ?></td>
                                <td><?= htmlspecialchars($row['tahun']) ?></td>
                                <td class="col-points"><?= ($row['poin'] ?? 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($prestasi_data) == 0): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 30px; color: #64748b;">Tidak ada data prestasi yang sesuai dengan filter.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </main>
    <script src="../administrator/Assets/Js/Script.js?v=<?= time() ?>"></script>
</body>
</html>
