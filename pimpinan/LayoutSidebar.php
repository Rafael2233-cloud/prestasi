<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<aside class="sidebar-new" id="sidebar">
    <div class="sidebar-header-new">
        <div class="sidebar-header-left">
            <div class="logo-icon" style="width:56px; height:56px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border-radius:50%; background:#ffffff; overflow:hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.15);">
                <img src="../administrator/Assets/Images/logo_fasilkom.png" alt="Logo Fasilkom" style="width: 100%; height: 100%; object-fit: contain;">
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
            <div class="profile-name"><?= htmlspecialchars($_SESSION['pimpinan_nama'] ?? 'Pimpinan') ?></div>
            <div class="profile-role"><?= htmlspecialchars($_SESSION['pimpinan_nip'] ?? '-') ?></div>
        </div>
    </div>

    <div class="sidebar-menus-container">
        <div class="sidebar-menu-title">MENU UTAMA</div>
        <ul class="sidebar-menu-new">
            <li class="menu-item-new <?php echo ($current_page == 'Dashboard.php') ? 'active' : ''; ?>"><a href="Dashboard.php"><i class="fa-solid fa-house"></i> <span class="menu-text">Dashboard</span></a></li>
            <li class="menu-item-new <?php echo ($current_page == 'Biodata.php') ? 'active' : ''; ?>"><a href="Biodata.php"><i class="fa-regular fa-user"></i> <span class="menu-text">Biodata</span></a></li>
        </ul>

        <div class="sidebar-menu-title mt-custom" style="margin-top: 15px;">MANAJEMEN PRESTASI</div>
        <ul class="sidebar-menu-new">
            <li class="menu-item-new <?php echo ($current_page == 'RekapPrestasi.php') ? 'active' : ''; ?>"><a href="RekapPrestasi.php"><i class="fa-solid fa-file-lines"></i> <span class="menu-text">Rekap Prestasi</span></a></li>
            <li class="menu-item-new <?php echo ($current_page == 'Leaderboard.php') ? 'active' : ''; ?>"><a href="Leaderboard.php"><i class="fa-solid fa-trophy"></i> <span class="menu-text">Leaderboard</span></a></li>
            <li class="menu-item-new <?php echo ($current_page == 'PersetujuanPoin.php') ? 'active' : ''; ?>">
                <?php
                $total_poin_pending = 0;
                if (isset($conn)) {
                    $q_poin_pending = $conn->query("SELECT COUNT(*) as cnt FROM poin_revisi WHERE status='Menunggu Persetujuan'");
                    $total_poin_pending = $q_poin_pending ? $q_poin_pending->fetch_assoc()['cnt'] : 0;
                }
                ?>
                <a href="PersetujuanPoin.php"><i class="fa-solid fa-check-to-slot"></i> <span class="menu-text">Persetujuan Poin</span><?php if ($total_poin_pending > 0): ?><span class="badge"><?= $total_poin_pending ?></span><?php endif; ?></a>
            </li>
        </ul>

        <div class="sidebar-menu-title mt-custom" style="margin-top: 15px;">LAPORAN</div>
        <ul class="sidebar-menu-new">
            <li class="menu-item-new <?php echo ($current_page == 'LaporanPertahun.php') ? 'active' : ''; ?>"><a href="LaporanPertahun.php"><i class="fa-solid fa-calendar"></i> <span class="menu-text">Laporan per Tahun</span></a></li>
            <li class="menu-item-new <?php echo ($current_page == 'LaporanPerDosen.php') ? 'active' : ''; ?>"><a href="LaporanPerDosen.php"><i class="fa-solid fa-user-tie"></i> <span class="menu-text">Laporan per Dosen</span></a></li>
            <li class="menu-item-new <?php echo ($current_page == 'LaporanPerKategori.php') ? 'active' : ''; ?>"><a href="LaporanPerKategori.php"><i class="fa-solid fa-layer-group"></i> <span class="menu-text">Laporan per Kategori</span></a></li>
            <li class="menu-item-new <?php echo ($current_page == 'LaporanPerMahasiswa.php') ? 'active' : ''; ?>"><a href="LaporanPerMahasiswa.php"><i class="fa-solid fa-user-graduate"></i> <span class="menu-text">Laporan per Mahasiswa</span></a></li>
            <li class="menu-item-new <?php echo ($current_page == 'CekPoin.php') ? 'active' : ''; ?>"><a href="CekPoin.php"><i class="fa-solid fa-star"></i> <span class="menu-text">Cek Poin</span></a></li>
        </ul>
    </div>

    <div class="sidebar-footer-new">
        <a href="logout.php" class="logout-btn-new"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span class="menu-text">Logout</span></a>
    </div>
</aside>

<!-- <script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar'); // Tombol yang ada di sidebar
        const toggleBtnTopbar = document.getElementById('toggleSidebarTopbar'); // Tombol di topbar (kalau ada)

        // 1. Fungsi Toggle Sidebar
        const toggleSidebar = (e) => {
            if (e) e.stopPropagation(); // Mencegah event klik naik ke document
            if (sidebar) {
                sidebar.classList.toggle('collapsed');
            }
        };

        // 2. Bind langsung ke tombol, BUKAN ke document
        if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
        if (toggleBtnTopbar) toggleBtnTopbar.addEventListener('click', toggleSidebar);

        // 3. Logic terpisah untuk Dropdown (Biar ga campur aduk sama toggle sidebar)
        document.addEventListener('click', (e) => {
            const profileBtn = document.getElementById('profileBtn');
            const notifBtn = document.getElementById('notifBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            const notifDropdown = document.getElementById('notifDropdown');

            if (profileBtn && profileBtn.contains(e.target)) {
                profileDropdown.classList.toggle('show');
                if (notifDropdown) notifDropdown.classList.remove('show');
            } else if (notifBtn && notifBtn.contains(e.target)) {
                notifDropdown.classList.toggle('show');
                if (profileDropdown) profileDropdown.classList.remove('show');
            } else {
                // Klik di luar, tutup semua
                if (profileDropdown) profileDropdown.classList.remove('show');
                if (notifDropdown) notifDropdown.classList.remove('show');
            }
        });
    });
</script> -->