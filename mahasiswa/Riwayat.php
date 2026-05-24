<?php
include 'LayoutHeader.php';

// Handle notifikasi read
if (isset($_GET['mark_read'])) {
    $mark_id = mysqli_real_escape_string($conn, $_GET['mark_read']);
    mysqli_query($conn, "UPDATE prestasi SET read_status = 'read' WHERE id = '$mark_id' AND mahasiswa_id = '$mahasiswa_id'");
    echo "<script>window.location.href='Riwayat.php';</script>";
    exit();
}

if (isset($_GET['mark_all_read'])) {
    mysqli_query($conn, "UPDATE prestasi SET read_status = 'read' WHERE mahasiswa_id = '$mahasiswa_id'");
    echo "<script>window.location.href='Riwayat.php';</script>";
    exit();
}

// Ambil riwayat prestasi
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : 'semua';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// Get available years for the dropdown
$years_query = mysqli_query($conn, "SELECT DISTINCT tahun FROM prestasi WHERE mahasiswa_id = '$mahasiswa_id' ORDER BY tahun DESC");
$available_years = [];
while($row = mysqli_fetch_assoc($years_query)) {
    $available_years[] = $row['tahun'];
}

$query_conditions = "WHERE mahasiswa_id = '$mahasiswa_id'";

if ($filter_tahun !== 'semua') {
    $safe_tahun = mysqli_real_escape_string($conn, $filter_tahun);
    $query_conditions .= " AND tahun = '$safe_tahun'";
}

$order_by = "ORDER BY tahun DESC, created_at DESC";
if ($sort_order === 'terlama') {
    $order_by = "ORDER BY tahun ASC, created_at ASC";
}

$prestasi_query = "SELECT * FROM prestasi $query_conditions $order_by";
$prestasi_result = mysqli_query($conn, $prestasi_query);
?>
<div class="p-6 lg:p-8 w-full">
    <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Riwayat Prestasi</h2>
        <p class="text-slate-500 mt-1 text-sm">Daftar semua prestasi akademik dan non-akademik yang telah Anda ajukan.</p>
    </div>

    <!-- Filter & Sort Form -->
    <div class="mb-6 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <form method="GET" action="Riwayat.php" class="flex flex-col sm:flex-row gap-5 items-end">
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Filter Tahun</label>
                <div class="relative">
                    <select name="tahun" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm appearance-none cursor-pointer font-medium text-slate-700" onchange="this.form.submit()">
                        <option value="semua" <?= $filter_tahun === 'semua' ? 'selected' : '' ?>>Semua Tahun</option>
                        <?php foreach($available_years as $y): ?>
                            <option value="<?= $y ?>" <?= $filter_tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Urutkan</label>
                <div class="relative">
                    <select name="sort" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm appearance-none cursor-pointer font-medium text-slate-700" onchange="this.form.submit()">
                        <option value="terbaru" <?= $sort_order === 'terbaru' ? 'selected' : '' ?>>Tahun Terbaru ke Terlama</option>
                        <option value="terlama" <?= $sort_order === 'terlama' ? 'selected' : '' ?>>Tahun Terlama ke Terbaru</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6">
            <div class="space-y-4">
                <?php if (mysqli_num_rows($prestasi_result) > 0): ?>
                    <?php while($p = mysqli_fetch_assoc($prestasi_result)): 
                        $statusClass = $p['status'] === 'approved' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 
                                      ($p['status'] === 'pending' ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-rose-100 text-rose-700 border-rose-200');
                        $statusIcon = $p['status'] === 'approved' ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>' : 
                                     ($p['status'] === 'pending' ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>');
                        $statusText = $p['status'] === 'approved' ? 'Disetujui' : 
                                     ($p['status'] === 'pending' ? 'Menunggu Verifikasi' : 'Ditolak');
                    ?>
                    <div class="group border border-slate-100 hover:border-campus-200 rounded-xl p-5 transition-all hover:shadow-md bg-white">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="flex-1 pr-4">
                                <h4 class="font-bold text-slate-800 text-lg group-hover:text-campus-700 transition-colors"><?= htmlspecialchars($p['judul']) ?></h4>
                                <div class="flex flex-wrap gap-2 mt-3 text-xs font-semibold text-slate-500">
                                    <span class="bg-slate-100 px-3 py-1.5 rounded-lg text-slate-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        <?= htmlspecialchars($p['jenis']) ?>
                                    </span>
                                    <span class="bg-slate-100 px-3 py-1.5 rounded-lg text-slate-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <?= htmlspecialchars($p['tingkat']) ?>
                                    </span>
                                    <span class="bg-slate-100 px-3 py-1.5 rounded-lg text-slate-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        <?= htmlspecialchars($p['juara']) ?>
                                    </span>
                                    <span class="text-slate-400 py-1.5 font-bold mx-1">•</span>
                                    <span class="text-slate-500 py-1.5 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Tahun <?= htmlspecialchars($p['tahun']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex flex-col items-start sm:items-end gap-2 shrink-0 border-t sm:border-t-0 border-slate-100 pt-3 sm:pt-0">
                                <span class="px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 border <?= $statusClass ?>">
                                    <?= $statusIcon ?>
                                    <?= $statusText ?>
                                </span>
                                <?php if ($p['status'] === 'approved'): ?>
                                    <span class="text-sm font-extrabold text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-100">
                                        +<?= $p['poin'] ?> Poin
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-400">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-base font-bold text-slate-700">Belum Ada Riwayat Pengajuan</p>
                        <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Anda belum pernah mengajukan prestasi. Silakan tambahkan prestasi Anda melalui menu Input Prestasi.</p>
                        <a href="Input.php" class="mt-6 px-6 py-2.5 bg-campus-600 text-white font-semibold rounded-xl hover:bg-campus-700 transition-colors text-sm shadow-md">Tambah Prestasi</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include 'LayoutFooter.php'; ?>
