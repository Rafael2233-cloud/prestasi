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

// Base query
$query = "
    SELECT p.tahun, 
           COUNT(DISTINCT m.id) as total_mahasiswa, 
           COUNT(p.id) as total_prestasi, 
           SUM(COALESCE((SELECT pc.poin FROM poin_config pc WHERE (pc.tingkat = p.tingkat OR (p.tingkat = 'Kota/Kabupaten' AND pc.tingkat IN ('Kota','Kabupaten'))) AND pc.juara = p.juara ORDER BY pc.poin DESC LIMIT 1), 0)) as total_poin
    FROM prestasi p
    JOIN mahasiswa m ON p.mahasiswa_id = m.id
    WHERE p.status = 'approved'
";

if ($search !== '') {
    $query .= " AND p.tahun = '" . $conn->real_escape_string($search) . "'";
}

$query .= " GROUP BY p.tahun ORDER BY p.tahun DESC";

$result = $conn->query($query);
$prestasi_data = [];
while($row = $result->fetch_assoc()) {
    $prestasi_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan per Tahun - SIPRESMA</title>
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
        .filter-input { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; color: #334155; font-size: 14px; outline: none; transition: border-color 0.2s; background-color: white; }
        .filter-input:focus { border-color: #2563eb; }
        
        .btn-reset { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 0 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; height: 45px; text-decoration: none; }
        .btn-reset:hover { background: #e2e8f0; color: #1e293b; }
        
        .btn-search { background: #2563eb; color: white; border: none; padding: 0 25px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; height: 45px; }
        .btn-search:hover { background: #1d4ed8; }

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
        
        .col-points { font-weight: 700; color: #2563eb; }
        .col-highlight { font-weight: 600; color: #0f172a; }
        
        /* Print Styles for PDF Export */
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .sidebar-new, .topbar-new, .filter-card, .export-actions, .breadcrumb-top { display: none !important; }
            .main-content-new { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .content-wrapper-new { padding: 10px !important; }
            .table-card { border: none; box-shadow: none; }
            .custom-table { width: 100%; border: 1px solid #ddd; }
            .custom-table th, .custom-table td { padding: 8px; border: 1px solid #ddd; font-size: 12pt; white-space: normal; }
            @page { size: portrait; margin: 1cm; }
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
                            <h1 style="margin: 0 0 5px 0; font-size: 24px; font-weight: 800; color: #0f172a;">Laporan per Tahun</h1>
                            <p style="font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 400; color: #64748b; line-height: 1.5; margin: 0;">Rekapitulasi data prestasi mahasiswa dikelompokkan per tahun akademik.</p>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                        <div class="breadcrumb-top" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
                            <a href="Dashboard.php" style="color: #2563eb; text-decoration: none;">Beranda</a> <span>/</span> Laporan per Tahun
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <form action="LaporanPertahun.php" method="GET" id="filterForm">
                <div class="filter-card">
                    <div class="filter-header">
                        <div class="filter-header-title"><i class="fa-solid fa-filter"></i> Filter Pencarian</div>
                        <div class="export-actions">
                            <button type="button" class="btn-export-pdf" onclick="window.print()">
                                <i class="fa-regular fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                    </div>
                    <div class="filter-controls">
                        <div class="filter-group">
                            <label class="filter-label">Cari Tahun</label>
                            <input type="text" class="filter-input" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan tahun (contoh: 2023)...">
                        </div>
                        <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
                        <a href="LaporanPertahun.php" class="btn-reset"><i class="fa-solid fa-rotate-right"></i> Reset</a>
                    </div>
                </div>
            </form>
            
            <!-- Table Card -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>TAHUN</th>
                                <th>TOTAL MAHASISWA BERPRESTASI</th>
                                <th>TOTAL PRESTASI</th>
                                <th>TOTAL POIN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach($prestasi_data as $row): 
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="col-highlight"><?= htmlspecialchars($row['tahun']) ?></td>
                                <td><?= htmlspecialchars($row['total_mahasiswa']) ?></td>
                                <td><?= htmlspecialchars($row['total_prestasi']) ?></td>
                                <td class="col-points"><?= ($row['total_poin'] ?? 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($prestasi_data) == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: #64748b;">Tidak ada data yang tersedia.</td>
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
