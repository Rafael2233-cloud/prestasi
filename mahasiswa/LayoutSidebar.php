<?php
// LayoutSidebar.php
$current_page = basename($_SERVER['PHP_SELF']);

// Helper function to determine active state
function is_active_new($page, $current) {
    return $page === $current ? 'active' : '';
}
?>
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
            <div class="profile-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Mahasiswa') ?></div>
            <div class="profile-role"><?= htmlspecialchars($_SESSION['nim'] ?? '') ?></div>
        </div>
    </div>

    <div class="sidebar-menus-container">
        <div class="sidebar-menu-title">MENU UTAMA</div>
        <ul class="sidebar-menu-new">
            <li class="menu-item-new <?= is_active_new('Dashboard.php', $current_page) ?>">
                <a href="Dashboard.php"><i class="fa-solid fa-house"></i> <span class="menu-text">Dashboard</span></a>
            </li>
            <li class="menu-item-new <?= is_active_new('Biodata.php', $current_page) ?>">
                <a href="Biodata.php"><i class="fa-regular fa-user"></i> <span class="menu-text">Biodata</span></a>
            </li>
        </ul>

        <div class="sidebar-menu-title mt-custom">MANAJEMEN PRESTASI</div>
        <ul class="sidebar-menu-new">
            <li class="menu-item-new <?= is_active_new('Input.php', $current_page) ?>">
                <a href="Input.php"><i class="fa-solid fa-plus-circle"></i> <span class="menu-text">Input Prestasi</span></a>
            </li>
            <li class="menu-item-new <?= is_active_new('Riwayat.php', $current_page) ?>">
                <a href="Riwayat.php"><i class="fa-solid fa-clock-rotate-left"></i> <span class="menu-text">Riwayat Prestasi</span></a>
            </li>
        </ul>

        <div class="sidebar-menu-title mt-custom">PERINGKAT & KLASEMEN</div>
        <ul class="sidebar-menu-new">
            <li class="menu-item-new <?= is_active_new('Leaderboard.php', $current_page) ?>">
                <a href="Leaderboard.php"><i class="fa-solid fa-trophy"></i> <span class="menu-text">Leaderboard</span></a>
            </li>
            <li class="menu-item-new <?= is_active_new('InfoPoin.php', $current_page) ?>">
                <a href="InfoPoin.php"><i class="fa-solid fa-circle-info"></i> <span class="menu-text">Info Poin Prestasi</span></a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer-new">
        <a href="Logout.php" onclick="return confirm('Anda yakin ingin keluar dari sistem?');" class="logout-btn-new">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> <span class="menu-text">Logout</span>
        </a>
    </div>
</aside>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.getElementById('sidebar');
        const toggleSidebar = document.getElementById('toggleSidebar');
        const toggleSidebarTopbar = document.getElementById('toggleSidebarTopbar'); // Ngambil hamburger menu dari topbar mobile

        // 1. Jalur Desktop: Tombol toggle yang ada di dalem sidebar
        if (toggleSidebar && sidebar) {
            toggleSidebar.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
            });
        }

        // 2. Jalur Mobile: Tombol hamburger yang ada di topbar luar (LayoutHeader.php)
        if (toggleSidebarTopbar && sidebar) {
            toggleSidebarTopbar.addEventListener('click', (e) => {
                e.stopPropagation(); // Biar event click-nya gak tabrakan/memicu document click
                sidebar.classList.toggle('collapsed');
            });
        }
    });
</script>
