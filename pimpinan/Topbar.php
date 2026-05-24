<?php
// Cek status notifikasi jika $conn tersedia
$total_notif = 0;
$notif_poin = 0;
if (isset($conn)) {
    $q_pending_poin = $conn->query("SELECT COUNT(*) as cnt FROM poin_revisi WHERE status='Menunggu Persetujuan'");
    $notif_poin = $q_pending_poin ? $q_pending_poin->fetch_assoc()['cnt'] : 0;

    $total_notif = $notif_poin;
}

$first_letter = 'P';
if (isset($_SESSION['pimpinan_nama']) && !empty($_SESSION['pimpinan_nama'])) {
    $first_letter = strtoupper(substr(trim($_SESSION['pimpinan_nama']), 0, 1));
}
?>
<!-- Topbar -->
<header class="topbar-new" style="display: flex; justify-content: space-between; align-items: center;">
    <button class="toggle-btn-new topbar-toggle-btn"
        onclick="document.getElementById('sidebar').classList.toggle('collapsed'); event.stopPropagation();">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="topbar-right" style="display: flex; align-items: center; gap: 20px; margin-left: auto;">
        <div class="notification-btn" id="notifBtn" style="position: relative; cursor: pointer; color: #64748b; font-size: 20px; text-decoration: none;">
            <i class="fa-regular fa-bell"></i>
            <?php if ($total_notif > 0): ?>
                <span class="badge" style="position: absolute; top: -5px; right: -8px; background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: bold;"><?= $total_notif ?></span>
            <?php endif; ?>

            <div class="dropdown-menu" id="notifDropdown" style="width: 280px; left: auto; right: -10px; top: 130%; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
                <div style="padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-weight: 600; font-size: 14px; color: #334155; display:flex; justify-content: space-between; align-items: center;">
                    Notifikasi
                    <?php if ($total_notif > 0): ?>
                        <span style="background: #ef4444; color: white; font-size: 11px; padding: 2px 6px; border-radius: 10px; font-weight: bold;"><?= $total_notif ?> Baru</span>
                    <?php endif; ?>
                </div>

                <?php if ($notif_poin > 0): ?>
                    <a href="PersetujuanPoin.php" class="dropdown-item" style="display: flex; align-items: start; gap: 12px; padding: 12px 15px; white-space: normal; line-height: 1.4;">
                        <div style="background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fa-solid fa-check-to-slot" style="font-size: 13px;"></i></div>
                        <div>
                            <div style="font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">Persetujuan Poin</div>
                            <div style="font-size: 12px; color: #64748b;">Terdapat <b><?= $notif_poin ?></b> kriteria poin baru menunggu persetujuan Anda</div>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if ($total_notif == 0): ?>
                    <div style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">
                        <i class="fa-regular fa-bell-slash" style="font-size: 24px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                        Tidak ada notifikasi baru
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vertical Divider -->
        <div style="width: 1px; height: 32px; background-color: #e2e8f0; margin: 0 5px;"></div>

        <div class="user-profile-top" onclick="document.getElementById('profileDropdown').classList.toggle('show'); document.getElementById('notifDropdown').classList.remove('show'); event.stopPropagation();" style="position: relative; cursor: pointer; display: flex; align-items: center; gap: 10px;">
            <div class="avatar" style="background-color: #3b82f6; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; pointer-events: none;">S</div>
            <i class="fa-solid fa-chevron-down" style="color: #94a3b8; font-size: 10px; margin-left: 2px; pointer-events: none;"></i>

            <div class="dropdown-menu" id="profileDropdown" style="z-index: 1000; width: 160px; right: 0; left: auto; margin-top: 10px;">
                <a href="Biodata.php" class="dropdown-item"><i class="fa-regular fa-user"></i> Data Diri</a>
                <a href="Logout.php" class="dropdown-item"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>
</header>

<script>
    // Sisa JS cuma buat nutup dropdown kalau user klik area kosong di layar
    document.addEventListener('click', () => {
        const notifDropdown = document.getElementById('notifDropdown');
        const profileDropdown = document.getElementById('profileDropdown');
        if (notifDropdown) notifDropdown.classList.remove('show');
        if (profileDropdown) profileDropdown.classList.remove('show');
    });

    const isDark = localStorage.getItem('theme_dark') === 'true';
    const noAnim = localStorage.getItem('theme_no_anim') === 'true';
    if (isDark) document.body.classList.add('dark-mode');
    if (noAnim) document.body.classList.add('no-animations');
</script>