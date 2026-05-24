<?php
session_start();

// Validasi session, misal hanya untuk yang sudah login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require '../koneksi.php';

function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja'; 
}

// Aktivitas Terbaru 
$activities = [];
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
$tahun_condition = $filter_tahun !== '' ? " AND p.tahun = '" . $conn->real_escape_string($filter_tahun) . "'" : "";
$q_pres_act = $conn->query("SELECT p.status, p.updated_at as waktu, m.nama, p.judul as keterangan FROM prestasi p JOIN mahasiswa m ON p.mahasiswa_id = m.id WHERE 1=1 $tahun_condition ORDER BY p.updated_at DESC LIMIT 5");
if ($q_pres_act) {
    while($r = $q_pres_act->fetch_assoc()){
        if($r['status'] == 'approved') {
            $icon = '<div class="act-icon-small bg-green"><i class="fa-solid fa-check"></i></div>';
            $ket = 'Prestasi "'.htmlspecialchars($r['keterangan']).'" diverifikasi oleh Admin';
        } elseif($r['status'] == 'rejected') {
            $icon = '<div class="act-icon-small bg-red"><i class="fa-solid fa-xmark"></i></div>';
            $ket = 'Prestasi "'.htmlspecialchars($r['keterangan']).'" ditolak oleh Admin';
        } else {
            $icon = '<div class="act-icon-small bg-blue"><i class="fa-solid fa-user"></i></div>';
            $ket = 'Mahasiswa '.htmlspecialchars($r['nama']) . ' menambahkan prestasi baru';
        }
        $activities[] = ['waktu' => $r['waktu'], 'keterangan' => $ket, 'icon' => $icon];
    }
}
usort($activities, function($a, $b) { return strtotime($b['waktu']) - strtotime($a['waktu']); });
$activities = array_slice($activities, 0, 5);

$prestasi_total = $conn->query("SELECT COUNT(*) as c FROM prestasi")->fetch_assoc()['c'] ?? 0;
$prestasi_terverifikasi = $conn->query("SELECT COUNT(*) as c FROM prestasi WHERE status='approved'")->fetch_assoc()['c'] ?? 0;
$prestasi_menunggu = $conn->query("SELECT COUNT(*) as c FROM prestasi WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
$prestasi_ditolak = $conn->query("SELECT COUNT(*) as c FROM prestasi WHERE status='rejected'")->fetch_assoc()['c'] ?? 0;

date_default_timezone_set('Asia/Jakarta');
$bulan_full = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$hari_ini = date('w');
$nama_hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$tanggal_hari_ini = $nama_hari[$hari_ini] . ', ' . date('d') . ' ' . $bulan_full[(int)date('m')] . ' ' . date('Y');
$jam_hari_ini = date('H:i') . ' WIB';

// Leaderboard Top 5
$q_leaderboard = $conn->query("
    SELECT m.nama, m.id, m.prodi, m.angkatan,
        COALESCE(SUM(
            CASE WHEN p.status = 'approved' THEN 
                (SELECT pc.poin FROM poin_config pc WHERE (pc.tingkat = p.tingkat OR (p.tingkat = 'Kota/Kabupaten' AND pc.tingkat IN ('Kota','Kabupaten'))) AND pc.juara = p.juara ORDER BY pc.poin DESC LIMIT 1) 
            ELSE 0 END
        ), 0) AS total_poin,
        COUNT(CASE WHEN p.status = 'approved' THEN 1 END) AS total_prestasi
    FROM mahasiswa m 
    LEFT JOIN prestasi p ON m.id = p.mahasiswa_id
    GROUP BY m.id, m.nama, m.prodi, m.angkatan
    ORDER BY total_poin DESC, m.nama ASC LIMIT 5
");
$leaderboard_data = [];
if ($q_leaderboard) {
    while($r = $q_leaderboard->fetch_assoc()) {
        $leaderboard_data[] = $r;
    }
}

// Chart Data (Perkembangan Prestasi Mahasiswa per Tahun)
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
$tahuns_chart = [2024, 2025, 2026];
if ($filter_tahun !== '') {
    $tahuns_chart = [(int)$filter_tahun];
}

$chart_data = [];
foreach ($tahuns_chart as $th) {
    $chart_data[$th] = ['akademik' => 0, 'nonakademik' => 0];
}

$chart_in_years = implode(',', $tahuns_chart);
$q_chart = $conn->query("
    SELECT tahun, 
           SUM(CASE WHEN jenis IN ('Sains', 'Teknologi') THEN 1 ELSE 0 END) as jml_akademik,
           SUM(CASE WHEN jenis NOT IN ('Sains', 'Teknologi') THEN 1 ELSE 0 END) as jml_nonakademik
    FROM prestasi 
    WHERE status='approved' AND tahun IN ($chart_in_years)
    GROUP BY tahun
");

if ($q_chart) {
    while ($r = $q_chart->fetch_assoc()) {
        $th = (int)$r['tahun'];
        if (isset($chart_data[$th])) {
            $chart_data[$th]['akademik'] = (int)$r['jml_akademik'];
            $chart_data[$th]['nonakademik'] = (int)$r['jml_nonakademik'];
        }
    }
}

$chart_labels_json = json_encode(array_values(array_map('strval', $tahuns_chart)));
$chart_akademik_json = json_encode(array_values(array_column($chart_data, 'akademik')));
$chart_nonakademik_json = json_encode(array_values(array_column($chart_data, 'nonakademik')));

function getInitialsNew($name) {
    if (!$name) return '-';
    $words = explode(' ', trim($name));
    $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
    return $initials;
}
$avatar_classes = ['av-orange', 'av-purple', 'av-blue', 'av-cyan', 'av-pink'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Monitoring - SIPRESMA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../administrator/Assets/Css/Style.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        html, body {
            overflow-x: hidden !important;
            max-width: 100%;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        * {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        ::-webkit-scrollbar:horizontal { display: none !important; }
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .content-wrapper-new { padding: 30px; overflow-x: hidden !important; width: 100%; box-sizing: border-box; }

        /* Dashboard Header */
        .dash-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
        .dash-title h1 { margin: 0 0 5px 0; font-size: 24px; font-weight: 800; color: #0f172a; }
        .dash-title p { margin: 0; font-size: 13px; color: #64748b; }
        .dash-date { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 15px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
        .dash-date i { font-size: 20px; color: #3b82f6; }
        .dash-date-text { font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.3; }

        .section-title { font-size: 13px; font-weight: 700; color: #2563eb; display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }

        /* Stats Cards */
        .top-stats-wrap { display: flex; gap: 20px; margin-bottom: 25px; flex-direction: column;}
        .stats-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .stat-card-new { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; transition: all 0.2s ease; position: relative; overflow: hidden; }
        .stat-card-new:hover { transform: translateY(-2px); box-shadow: 0 4px 12px -4px rgba(0,0,0,0.05); border-color: #cbd5e1; }
        .stat-card-new::before { content: ''; position: absolute; left: 0; top: 0; width: 3px; height: 100%; }
        .stat-card-new.sc-blue::before { background-color: #3b82f6; }
        .stat-card-new.sc-green::before { background-color: #10b981; }
        .stat-card-new.sc-orange::before { background-color: #f59e0b; }
        .stat-card-new.sc-red::before { background-color: #ef4444; }
        .sc-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 16px; flex-shrink: 0; }
        .sc-info { display: flex; flex-direction: column; width: 100%; justify-content: center; }
        .sc-title { font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 4px; line-height: 1; text-transform: uppercase; }
        .sc-val { font-size: 26px; color: #0f172a; font-weight: 800; line-height: 1; margin-bottom: 4px; }
        .sc-sub { font-size: 11px; color: #94a3b8; font-weight: 500; }
        
        .bg-light-blue { background: #eff6ff; color: #3b82f6; }
        .bg-light-green { background: #ecfdf5; color: #10b981; }
        .bg-light-orange { background: #fffbeb; color: #f59e0b; }
        .bg-light-red { background: #fef2f2; color: #ef4444; }

        .mid-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .panel-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); padding: 20px; display: flex; flex-direction: column; }
        .pc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .pc-title { font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .pc-link { font-size: 11px; font-weight: 700; color: #2563eb; text-decoration: none; display: flex; align-items: center; gap: 5px; transition: background-color 0.2s; padding: 4px 8px; border-radius: 6px; }
        .pc-link:hover { background: #eff6ff; }

        .ts-list { display: flex; flex-direction: column; gap: 8px; }
        .ts-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 8px; border: 1px solid transparent; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .ts-item:hover { background: #f8fafc; border-color: #e2e8f0; transform: translateX(6px); }
        .ts-left { display: flex; align-items: center; gap: 12px; }
        .ts-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: white; }
        .av-orange { background: #f59e0b; } .av-purple { background: #8b5cf6; } .av-blue { background: #3b82f6; } .av-cyan { background: #06b6d4; } .av-pink { background: #ec4899; }
        .ts-name { font-size: 12px; font-weight: 700; color: #0f172a; }
        .ts-points { font-size: 13px; font-weight: 800; color: #0f172a; }

        .chart-container { position: relative; height: 250px; width: 100%; display: flex; align-items: center; justify-content: center; }

        /* Animations */
        @keyframes fadeInUpSmooth { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }
        .animate-slide-up { animation: fadeInUpSmooth 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .d-1 { animation-delay: 0.1s; } .d-2 { animation-delay: 0.2s; }
        
        @media (max-width: 1024px) {
            .mid-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .stats-grid-4 { grid-template-columns: repeat(2, 1fr); }
            .content-wrapper-new { padding: 15px; }
        }
        @media (max-width: 480px) {
            .stats-grid-4 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="dashboard-body new-dashboard">

    <!-- Main Content -->
    <?php include 'LayoutSidebar.php'; ?>
    <main class="main-content-new" id="mainContent">
        <?php include 'Topbar.php'; ?>

        <div class="content-wrapper-new">
            <!-- Header Section -->
            <div class="dash-header animate-slide-up">
                <div class="dash-title">
                    <h1>Dashboard Monitoring</h1>
                    <p>Selamat datang di SIPRESMA. Monitoring capaian prestasi mahasiswa.</p>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                    <div class="breadcrumb-top" style="margin-bottom: 0;">
                        <a href="Dashboard.php">Beranda</a> <span>/</span> Dashboard
                    </div>
                    <div class="dash-date">
                        <i class="fa-regular fa-calendar-days"></i>
                        <div class="dash-date-text"><?= $tanggal_hari_ini ?><br><span style="color:#64748b;font-weight:normal;font-size:11px;"><?= $jam_hari_ini ?></span></div>
                    </div>
                </div>
            </div>

            <!-- Top Stats Grid -->
            <div class="top-stats-wrap animate-slide-up d-1">
                <div class="section-title"><i class="fa-solid fa-award"></i> Ringkasan Prestasi</div>
                <div class="stats-grid-4">
                    <div class="stat-card-new sc-blue">
                        <div class="sc-icon bg-light-blue"><i class="fa-solid fa-trophy"></i></div>
                        <div class="sc-info">
                            <div class="sc-title">Total Prestasi</div>
                            <div class="sc-val count-up" data-value="<?= $prestasi_total ?>">0</div>
                            <div class="sc-sub">Semua Waktu</div>
                        </div>
                    </div>
                    <div class="stat-card-new sc-green">
                        <div class="sc-icon bg-light-green"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="sc-info">
                            <div class="sc-title">Prestasi Diverifikasi</div>
                            <div class="sc-val count-up" data-value="<?= $prestasi_terverifikasi ?>">0</div>
                            <div class="sc-sub"><?= $prestasi_terverifikasi ?> Prestasi</div>
                        </div>
                    </div>
                    <div class="stat-card-new sc-orange">
                        <div class="sc-icon bg-light-orange"><i class="fa-regular fa-clock"></i></div>
                        <div class="sc-info">
                            <div class="sc-title">Menunggu Verifikasi</div>
                            <div class="sc-val count-up" data-value="<?= $prestasi_menunggu ?>">0</div>
                            <div class="sc-sub"><?= $prestasi_menunggu ?> Prestasi</div>
                        </div>
                    </div>
                    <div class="stat-card-new sc-red">
                        <div class="sc-icon bg-light-red"><i class="fa-solid fa-circle-xmark"></i></div>
                        <div class="sc-info">
                            <div class="sc-title">Prestasi Ditolak</div>
                            <div class="sc-val count-up" data-value="<?= $prestasi_ditolak ?>">0</div>
                            <div class="sc-sub"><?= $prestasi_ditolak ?> Prestasi</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mid Grid -->
            <div class="mid-grid animate-slide-up d-2">
                <!-- Top 5 Mahasiswa -->
                <div class="panel-card">
                    <div class="pc-header">
                        <div class="pc-title"><i class="fa-solid fa-trophy text-blue" style="color: #3b82f6;"></i> Top 5 Mahasiswa Berprestasi</div>
                        <a href="Leaderboard.php" class="pc-link">Lihat Semua <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    <div class="ts-list">
                        <?php if(empty($leaderboard_data)): ?>
                            <div style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">Belum ada data mahasiswa.</div>
                        <?php else: ?>
                            <?php 
                            $i = 0;
                            foreach($leaderboard_data as $lb): 
                                $av = $avatar_classes[$i % count($avatar_classes)];
                                $initials = getInitialsNew($lb['nama']);
                            ?>
                            <div class="ts-item">
                                <div class="ts-left">
                                    <div class="ts-avatar <?= $av ?>"><?= $initials ?></div>
                                    <div>
                                        <div class="ts-name"><?= htmlspecialchars($lb['nama']) ?></div>
                                        <div style="font-size: 10px; color: #64748b;"><?= htmlspecialchars($lb['prodi']) ?></div>
                                    </div>
                                </div>
                                <div class="ts-points"><span class="count-up" data-value="<?= $lb['total_poin'] ?>">0</span> pts</div>
                            </div>
                            <?php $i++; endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Grafik Perkembangan Prestasi -->
                <div class="panel-card">
                    <div class="pc-header">
                        <div class="pc-title"><i class="fa-solid fa-chart-line text-blue" style="color: #3b82f6;"></i> Perkembangan Prestasi Mahasiswa per Tahun</div>
                        <select id="dashboardYearFilter" onchange="window.location.href='Dashboard.php?tahun=' + this.value" style="border:1px solid #e2e8f0; border-radius:6px; font-size:11px; padding:4px 8px; color:#475569; outline:none; cursor:pointer;">
                            <option value="" <?= $filter_tahun === '' ? 'selected' : '' ?>>Semua Tahun</option>
                            <option value="2026" <?= $filter_tahun === '2026' ? 'selected' : '' ?>>2026</option>
                            <option value="2025" <?= $filter_tahun === '2025' ? 'selected' : '' ?>>2025</option>
                            <option value="2024" <?= $filter_tahun === '2024' ? 'selected' : '' ?>>2024</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="perkembanganChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const countElements = document.querySelectorAll('.count-up');
            countElements.forEach(el => {
                const targetValue = parseInt(el.getAttribute('data-value'), 10) || 0;
                let startValue = 0;
                const duration = 1500; 
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    const easeProgress = 1 - Math.pow(1 - progress, 4);
                    const current = Math.floor(easeProgress * (targetValue - startValue) + startValue);
                    el.innerText = current > 999 ? current.toLocaleString('id-ID') : current;
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    } else {
                        el.innerText = targetValue > 999 ? targetValue.toLocaleString('id-ID') : targetValue;
                    }
                };
                window.requestAnimationFrame(step);
            });

            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');
            if (toggleSidebar && sidebar) {
                toggleSidebar.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                });
            }

            const ctx = document.getElementById('perkembanganChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= $chart_labels_json ?>,
                        datasets: [
                            {
                                label: 'Akademik',
                                data: <?= $chart_akademik_json ?>,
                                backgroundColor: '#3b82f6',
                                borderColor: '#3b82f6',
                                borderWidth: 0,
                                hoverBackgroundColor: '#2563eb', 
                                hoverBorderColor: '#2563eb',
                                hoverBorderWidth: 6,
                                borderRadius: 6,
                                barPercentage: 0.6,
                                categoryPercentage: 0.8
                            },
                            {
                                label: 'Non Akademik',
                                data: <?= $chart_nonakademik_json ?>,
                                backgroundColor: '#10b981',
                                borderColor: '#10b981',
                                borderWidth: 0,
                                hoverBackgroundColor: '#059669', 
                                hoverBorderColor: '#059669',
                                hoverBorderWidth: 6,
                                borderRadius: 6,
                                barPercentage: 0.6,
                                categoryPercentage: 0.8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    font: { family: 'Inter', size: 12, weight: '600' },
                                    color: '#475569',
                                    usePointStyle: true,
                                    boxWidth: 8
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                titleFont: { size: 13, family: 'Inter' },
                                bodyFont: { size: 13, family: 'Inter' },
                                padding: 10,
                                cornerRadius: 8,
                                mode: 'index',
                                intersect: false,
                                displayColors: true,
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0, font: { family: 'Inter', size: 11 }, color: '#64748b' },
                                grid: { color: '#f1f5f9', drawBorder: false }
                            },
                            x: {
                                ticks: { font: { family: 'Inter', size: 12, weight: '600' }, color: '#334155' },
                                grid: { display: false, drawBorder: false }
                            }
                        },
                        animations: {
                            y: {
                                duration: 2000,
                                easing: 'easeOutCubic',
                                from: (ctx) => {
                                    if (ctx.type === 'data') {
                                        return ctx.chart.scales.y.getPixelForValue(0);
                                    }
                                },
                                delay: (context) => {
                                    let delay = 0;
                                    if (context.type === 'data' && context.mode === 'default' && !window.chartAnimDone) {
                                        delay = context.dataIndex * 300 + context.datasetIndex * 150;
                                    }
                                    return delay;
                                }
                            }
                        },
                        animation: {
                            onComplete: () => {
                                window.chartAnimDone = true;
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>

