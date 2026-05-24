<?php
include 'LayoutHeader.php';

// 1. Ringkasan Prestasi
$stats_prestasi_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM prestasi WHERE mahasiswa_id = '$mahasiswa_id'";
$res_prestasi = mysqli_query($conn, $stats_prestasi_query);
$prestasi = mysqli_fetch_assoc($res_prestasi);
$tot_pres = $prestasi['total'] ?: 0;
$prestasi_terverifikasi = $prestasi['approved'] ?: 0;
$prestasi_menunggu = $prestasi['pending'] ?: 0;
$prestasi_ditolak = $prestasi['rejected'] ?: 0;


// 3. Grafik Perkembangan Prestasi Mahasiswa per Tahun
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
    WHERE status='approved' AND mahasiswa_id='$mahasiswa_id' AND tahun IN ($chart_in_years)
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

// 5. Aktivitas Terbaru (Prestasi)
$aktivitas_query = "SELECT * FROM prestasi WHERE mahasiswa_id = '$mahasiswa_id' ORDER BY updated_at DESC LIMIT 5";
$aktivitas_res = mysqli_query($conn, $aktivitas_query);

// 6. Top 5 Mahasiswa Leaderboard
$leaderboard_query = "SELECT m.id, m.nama, m.nim, m.prodi, COALESCE(SUM(CASE WHEN p.status = 'approved' THEN 
                (SELECT pc.poin FROM poin_config pc WHERE (pc.tingkat = p.tingkat OR (p.tingkat = 'Kota/Kabupaten' AND pc.tingkat IN ('Kota','Kabupaten'))) AND pc.juara = p.juara ORDER BY pc.poin DESC LIMIT 1) 
            ELSE 0 END), 0) AS total_poin
                    FROM mahasiswa m
                    LEFT JOIN prestasi p ON m.id = p.mahasiswa_id
                    GROUP BY m.id, m.nama, m.nim, m.prodi
                    ORDER BY total_poin DESC, m.nama ASC
                    LIMIT 5";
$leaderboard_result = mysqli_query($conn, $leaderboard_query);

date_default_timezone_set('Asia/Jakarta');
$bulan_full = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$hari_ini = date('w');
$nama_hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$tanggal_hari_ini = $nama_hari[$hari_ini] . ', ' . date('d') . ' ' . $bulan_full[(int)date('m')] . ' ' . date('Y');
$jam_hari_ini = date('H:i') . ' WIB';

function get_initials($name) {
    if (!$name) return '-';
    $words = explode(' ', trim($name));
    return strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
}

function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja'; 
}

$avatar_classes = ['av-orange', 'av-purple', 'av-blue', 'av-cyan', 'av-pink'];
?>

<div class="content-wrapper-new" style="padding: 30px; overflow-x: hidden !important; width: 100%; box-sizing: border-box;">
    <!-- Header Section -->
    <div class="dash-header animate-slide-up" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
        <div class="dash-title">
            <h1 style="margin: 0 0 5px 0; font-size: 24px; font-weight: 800; color: #0f172a;">Dashboard Akademik</h1>
            <p style="margin: 0; font-size: 13px; color: #64748b;">Selamat datang, <?= htmlspecialchars($biodata['nama']) ?>. Ringkasan komprehensif pencapaian Anda.</p>
        </div>
        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
            <div class="breadcrumb-top" style="margin-bottom: 0;">
                <a href="Dashboard.php" style="color: #2563eb; text-decoration: none;">Beranda</a> <span>/</span> Dashboard
            </div>
            <div class="dash-date" style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 15px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                <i class="fa-regular fa-calendar-days" style="font-size: 20px; color: #3b82f6;"></i>
                <div class="dash-date-text" style="font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.3;"><?= $tanggal_hari_ini ?><br><span style="color:#64748b;font-weight:normal;font-size:11px;"><?= $jam_hari_ini ?></span></div>
            </div>
        </div>
    </div>

    <!-- Top Stats Grid -->
    <div class="top-stats-wrap animate-slide-up d-1" style="display: flex; gap: 20px; margin-bottom: 25px; flex-direction: column;">
        <div class="section-title" style="font-size: 13px; font-weight: 700; color: #2563eb; display: flex; align-items: center; gap: 8px; margin-bottom: 12px;"><i class="fa-solid fa-award"></i> Ringkasan Prestasi Anda</div>
        <div class="stats-grid-4" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
            <div class="stat-card-new sc-blue" style="background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; transition: all 0.2s ease; position: relative; overflow: hidden;">
                <div style="position: absolute; left: 0; top: 0; width: 3px; height: 100%; background-color: #3b82f6;"></div>
                <div class="sc-icon bg-light-blue" style="width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 16px; flex-shrink: 0; background: #eff6ff; color: #3b82f6;"><i class="fa-solid fa-trophy"></i></div>
                <div class="sc-info" style="display: flex; flex-direction: column; width: 100%; justify-content: center;">
                    <div class="sc-title" style="font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 4px; line-height: 1; text-transform: uppercase;">Total Prestasi</div>
                    <div class="sc-val count-up" data-value="<?= $tot_pres ?>" style="font-size: 26px; color: #0f172a; font-weight: 800; line-height: 1; margin-bottom: 4px;">0</div>
                    <div class="sc-sub" style="font-size: 11px; color: #94a3b8; font-weight: 500;">Semua Waktu</div>
                </div>
            </div>
            <div class="stat-card-new sc-green" style="background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; transition: all 0.2s ease; position: relative; overflow: hidden;">
                <div style="position: absolute; left: 0; top: 0; width: 3px; height: 100%; background-color: #10b981;"></div>
                <div class="sc-icon bg-light-green" style="width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 16px; flex-shrink: 0; background: #ecfdf5; color: #10b981;"><i class="fa-solid fa-circle-check"></i></div>
                <div class="sc-info" style="display: flex; flex-direction: column; width: 100%; justify-content: center;">
                    <div class="sc-title" style="font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 4px; line-height: 1; text-transform: uppercase;">Prestasi Diverifikasi</div>
                    <div class="sc-val count-up" data-value="<?= $prestasi_terverifikasi ?>" style="font-size: 26px; color: #0f172a; font-weight: 800; line-height: 1; margin-bottom: 4px;">0</div>
                    <div class="sc-sub" style="font-size: 11px; color: #94a3b8; font-weight: 500;"><?= $prestasi_terverifikasi ?> Prestasi</div>
                </div>
            </div>
            <div class="stat-card-new sc-orange" style="background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; transition: all 0.2s ease; position: relative; overflow: hidden;">
                <div style="position: absolute; left: 0; top: 0; width: 3px; height: 100%; background-color: #f59e0b;"></div>
                <div class="sc-icon bg-light-orange" style="width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 16px; flex-shrink: 0; background: #fffbeb; color: #f59e0b;"><i class="fa-regular fa-clock"></i></div>
                <div class="sc-info" style="display: flex; flex-direction: column; width: 100%; justify-content: center;">
                    <div class="sc-title" style="font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 4px; line-height: 1; text-transform: uppercase;">Menunggu Verifikasi</div>
                    <div class="sc-val count-up" data-value="<?= $prestasi_menunggu ?>" style="font-size: 26px; color: #0f172a; font-weight: 800; line-height: 1; margin-bottom: 4px;">0</div>
                    <div class="sc-sub" style="font-size: 11px; color: #94a3b8; font-weight: 500;"><?= $prestasi_menunggu ?> Prestasi</div>
                </div>
            </div>
            <div class="stat-card-new sc-red" style="background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; transition: all 0.2s ease; position: relative; overflow: hidden;">
                <div style="position: absolute; left: 0; top: 0; width: 3px; height: 100%; background-color: #ef4444;"></div>
                <div class="sc-icon bg-light-red" style="width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 16px; flex-shrink: 0; background: #fef2f2; color: #ef4444;"><i class="fa-solid fa-circle-xmark"></i></div>
                <div class="sc-info" style="display: flex; flex-direction: column; width: 100%; justify-content: center;">
                    <div class="sc-title" style="font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 4px; line-height: 1; text-transform: uppercase;">Prestasi Ditolak</div>
                    <div class="sc-val count-up" data-value="<?= $prestasi_ditolak ?>" style="font-size: 26px; color: #0f172a; font-weight: 800; line-height: 1; margin-bottom: 4px;">0</div>
                    <div class="sc-sub" style="font-size: 11px; color: #94a3b8; font-weight: 500;"><?= $prestasi_ditolak ?> Prestasi</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mid Grid -->
    <div class="mid-grid animate-slide-up d-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <!-- Grafik Perkembangan Prestasi -->
        <div class="panel-card" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); padding: 20px; display: flex; flex-direction: column;">
            <div class="pc-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div class="pc-title" style="font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-chart-line text-blue" style="color: #3b82f6;"></i> Perkembangan Prestasi Anda per Tahun</div>
                <select id="dashboardYearFilter" onchange="window.location.href='Dashboard.php?tahun=' + this.value" style="border:1px solid #e2e8f0; border-radius:6px; font-size:11px; padding:4px 8px; color:#475569; outline:none; cursor:pointer;">
                    <option value="" <?= $filter_tahun === '' ? 'selected' : '' ?>>Semua Tahun</option>
                    <option value="2026" <?= $filter_tahun === '2026' ? 'selected' : '' ?>>2026</option>
                    <option value="2025" <?= $filter_tahun === '2025' ? 'selected' : '' ?>>2025</option>
                    <option value="2024" <?= $filter_tahun === '2024' ? 'selected' : '' ?>>2024</option>
                </select>
            </div>
            <div class="chart-container" style="position: relative; height: 250px; width: 100%; display: flex; align-items: center; justify-content: center;">
                <canvas id="perkembanganChart"></canvas>
            </div>
        </div>

        <!-- Top 5 Mahasiswa -->
        <div class="panel-card" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); padding: 20px; display: flex; flex-direction: column;">
            <div class="pc-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div class="pc-title" style="font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-trophy text-blue" style="color: #3b82f6;"></i> Top 5 Mahasiswa (Leaderboard)</div>
                <a href="Leaderboard.php" class="pc-link" style="font-size: 11px; font-weight: 700; color: #2563eb; text-decoration: none; display: flex; align-items: center; gap: 5px; transition: background-color 0.2s; padding: 4px 8px; border-radius: 6px;">Lihat Semua <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="ts-list" style="display: flex; flex-direction: column; gap: 8px;">
                <?php if(mysqli_num_rows($leaderboard_result) == 0): ?>
                    <div style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">Belum ada data mahasiswa.</div>
                <?php else: ?>
                    <?php 
                    $i = 0;
                    while($lb = mysqli_fetch_assoc($leaderboard_result)): 
                        $av = $avatar_classes[$i % count($avatar_classes)];
                        $initials = get_initials($lb['nama']);
                        // Helper style
                        $av_bg = '';
                        if($av == 'av-orange') $av_bg = 'background: #f59e0b;';
                        if($av == 'av-purple') $av_bg = 'background: #8b5cf6;';
                        if($av == 'av-blue') $av_bg = 'background: #3b82f6;';
                        if($av == 'av-cyan') $av_bg = 'background: #06b6d4;';
                        if($av == 'av-pink') $av_bg = 'background: #ec4899;';
                    ?>
                    <div class="ts-item" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 8px; border: 1px solid transparent; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
                        <div class="ts-left" style="display: flex; align-items: center; gap: 12px;">
                            <div class="ts-avatar" style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: white; <?= $av_bg ?>"><?= $initials ?></div>
                            <div>
                                <div class="ts-name" style="font-size: 12px; font-weight: 700; color: #0f172a;"><?= htmlspecialchars($lb['nama']) ?></div>
                                <div style="font-size: 10px; color: #64748b;"><?= htmlspecialchars($lb['prodi']) ?></div>
                            </div>
                        </div>
                        <div class="ts-points" style="font-size: 13px; font-weight: 800; color: #0f172a;"><span class="count-up" data-value="<?= $lb['total_poin'] ?>">0</span> pts</div>
                    </div>
                    <?php $i++; endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom Grid -->
    <div class="bottom-grid animate-slide-up d-3" style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px;">
        <!-- Aktivitas Terbaru -->
        <div class="panel-card" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); padding: 20px; display: flex; flex-direction: column;">
            <div class="pc-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div class="pc-title" style="font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-clock-rotate-left text-blue" style="color: #3b82f6;"></i> Aktivitas Prestasi Anda</div>
                <a href="Riwayat.php" class="pc-link" style="font-size: 11px; font-weight: 700; color: #2563eb; text-decoration: none; display: flex; align-items: center; gap: 5px; transition: background-color 0.2s; padding: 4px 8px; border-radius: 6px;">Lihat Riwayat <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="activity-list" style="display: flex; flex-direction: column; gap: 8px;">
                <?php if(mysqli_num_rows($aktivitas_res) == 0): ?>
                    <div style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">Belum ada aktivitas.</div>
                <?php else: ?>
                    <?php while($act = mysqli_fetch_assoc($aktivitas_res)): 
                        $is_approved = $act['status'] === 'approved';
                        $is_pending = $act['status'] === 'pending';
                        
                        $icon_html = $is_approved ? '<i class="fa-solid fa-check"></i>' : ($is_pending ? '<i class="fa-regular fa-clock"></i>' : '<i class="fa-solid fa-xmark"></i>');
                        $action_text = $is_approved ? 'diverifikasi dan disetujui' : ($is_pending ? 'menunggu verifikasi' : 'diverifikasi namun ditolak');
                    ?>
                    <div class="act-item item-anim" style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 10px 12px; border-radius: 8px; border: 1px solid transparent; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
                        <div class="act-icon-small" style="width: 24px; height: 24px; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; color: white; <?= $is_approved ? 'background:#10b981;' : ($is_pending ? 'background:#f59e0b;' : 'background:#ef4444;') ?>"><?= $icon_html ?></div>
                        <div class="act-text" style="font-size: 11px; font-weight: 500; color: #334155; line-height: 1.5; flex: 1;">Prestasi "<b><?= htmlspecialchars($act['judul']) ?></b>" <?= $action_text ?> oleh Admin.</div>
                        <div class="act-time" style="font-size: 10px; font-weight: 600; color: #64748b; white-space: nowrap;"><?= time_elapsed_string($act['updated_at']) ?></div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

<?php include 'LayoutFooter.php'; ?>
