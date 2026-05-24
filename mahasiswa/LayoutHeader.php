<?php
// LayoutHeader.php
require_once '../koneksi.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$mahasiswa_id = $_SESSION['mahasiswa_id'];

// Ambil data dasar mahasiswa
$biodata_query = "SELECT * FROM mahasiswa WHERE id = '$mahasiswa_id'";
$biodata_result = mysqli_query($conn, $biodata_query);
$biodata = mysqli_fetch_assoc($biodata_result);

// Cek dan tambahkan kolom read_status jika belum ada
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM prestasi LIKE 'read_status'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE prestasi ADD COLUMN read_status ENUM('read', 'unread') DEFAULT 'read' AFTER status");
}

// Cek dan tambahkan kolom pembimbing_akademik jika belum ada
$check_pa = mysqli_query($conn, "SHOW COLUMNS FROM mahasiswa LIKE 'pembimbing_akademik'");
if(mysqli_num_rows($check_pa) == 0) {
    mysqli_query($conn, "ALTER TABLE mahasiswa ADD COLUMN pembimbing_akademik VARCHAR(100) NULL AFTER prodi");
}

// Ambil notifikasi unread
$notif_query = "SELECT * FROM prestasi WHERE mahasiswa_id = '$mahasiswa_id' AND status IN ('approved', 'rejected') AND read_status = 'unread' ORDER BY updated_at DESC";
$notif_result = mysqli_query($conn, $notif_query);
$unread_count = mysqli_num_rows($notif_result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Mahasiswa - Sistem Prestasi</title>
    <!-- Tailwind CSS for legacy pages -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        campus: {
                            50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Modern Admin CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../administrator/Assets/Css/Style.css?v=<?= time() ?>">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/Style.css">
    <style>
        .dropdown-menu-custom {
            display: none;
        }
        .dropdown-menu-custom.show {
            display: block;
        }
    </style>
</head>
<body class="dashboard-body new-dashboard">
    <?php include 'LayoutSidebar.php'; ?>
    
    <!-- Main Content Area -->
    <main class="main-content-new" id="mainContent">
        
        <!-- Top Navigation / Notification Bar -->
        <header class="topbar-new" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; background-color: #ffffff; border-bottom: 1px solid #e0e0e0; min-height: 60px;">
            <button class="toggle-btn-new topbar-toggle-btn" id="toggleSidebarTopbar" style="display:none;">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="topbar-right" style="display: flex; align-items: center; gap: 20px; margin-left: auto;">
                <!-- Notification Dropdown -->
                <div class="notification-btn" id="notifBtn" style="position: relative; cursor: pointer; color: #64748b; font-size: 20px;">
                    <i class="fa-regular fa-bell"></i>
                    <?php if($unread_count > 0): ?>
                        <span class="badge" style="position: absolute; top: -5px; right: -8px; background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: bold;"><?= $unread_count ?></span>
                    <?php endif; ?>
                    
                    <!-- Dropdown Panel -->
                    <div id="notifPanel" class="dropdown-menu-custom" style="position: absolute; right: -10px; top: 130%; width: 320px; background: white; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; z-index: 1000;">
                        <div style="padding: 12px 15px; border-bottom: 1px solid #e2e8f0; display:flex; justify-content: space-between; align-items: center;">
                            <h3 style="font-size: 14px; font-weight: 600; color: #334155; margin: 0;">Notifikasi</h3>
                            <?php if($unread_count > 0): ?>
                            <span style="background: #ef4444; color: white; font-size: 11px; padding: 2px 6px; border-radius: 10px; font-weight: bold;"><?= $unread_count ?> Baru</span>
                            <?php endif; ?>
                        </div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php if($unread_count > 0): ?>
                                <?php 
                                mysqli_data_seek($notif_result, 0);
                                while($notif = mysqli_fetch_assoc($notif_result)): 
                                    $is_approved = $notif['status'] === 'approved';
                                    $icon_bg = $is_approved ? 'background: #d1fae5; color: #10b981;' : 'background: #fee2e2; color: #ef4444;';
                                    $icon_svg = $is_approved ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-xmark"></i>';
                                ?>
                                <a href="Riwayat.php?mark_read=<?= $notif['id'] ?>" style="display: flex; align-items: start; gap: 12px; padding: 12px 15px; text-decoration: none; border-bottom: 1px solid #f8fafc; transition: background 0.2s;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; <?= $icon_bg ?>">
                                        <?= $icon_svg ?>
                                    </div>
                                    <div>
                                        <p style="font-size: 13px; font-weight: 500; color: #1e293b; margin: 0 0 4px 0; line-height: 1.4;">Prestasi <b>"<?= htmlspecialchars($notif['judul']) ?>"</b> telah <?= $is_approved ? 'disetujui' : 'ditolak' ?>.</p>
                                        <span style="font-size: 11px; color: #64748b; display: block;"><?= date('d M Y H:i', strtotime($notif['updated_at'])) ?></span>
                                    </div>
                                </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">
                                    <i class="fa-regular fa-bell-slash" style="font-size: 24px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                                    Tidak ada notifikasi baru
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if($unread_count > 0): ?>
                        <div style="padding: 10px; border-top: 1px solid #e2e8f0; text-align: center; background: #f8fafc;">
                            <a href="Riwayat.php?mark_all_read=1" style="font-size: 12px; font-weight: 600; color: #3b82f6; text-decoration: none;">Tandai semua sudah dibaca</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="width: 1px; height: 32px; background-color: #e2e8f0; margin: 0 5px;"></div>

                <div class="user-profile-top" style="display: flex; align-items: center; gap: 10px; cursor: pointer; position: relative;" id="profileBtn">
                    <?php
                        $words = explode(' ', trim($biodata['nama']));
                        $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                    ?>
                    <div class="avatar" style="background-color: #3b82f6; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px;"><?= $initials ?></div>
                    <i class="fa-solid fa-chevron-down" style="color: #94a3b8; font-size: 10px; margin-left: 2px;"></i>
                    <div class="dropdown-menu-custom" id="profileDropdown" style="position: absolute; right: 0; top: 130%; width: 160px; background: white; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; z-index: 1000;">
                        <a href="Biodata.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 15px; color: #475569; text-decoration: none; font-size: 13px; transition: background 0.2s;"><i class="fa-regular fa-user"></i> Biodata</a>
                        <a href="Logout.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 15px; color: #475569; text-decoration: none; font-size: 13px; transition: background 0.2s;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>
        
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const notifBtn = document.getElementById('notifBtn');
            const notifPanel = document.getElementById('notifPanel');
            const profileBtn = document.getElementById('profileBtn');
            const profileDropdown = document.getElementById('profileDropdown');

            if(notifBtn && notifPanel) {
                notifBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if(profileDropdown) profileDropdown.classList.remove('show');
                    notifPanel.classList.toggle('show');
                });
            }

            if(profileBtn && profileDropdown) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if(notifPanel) notifPanel.classList.remove('show');
                    profileDropdown.classList.toggle('show');
                });
            }

            document.addEventListener('click', () => {
                if(notifPanel) notifPanel.classList.remove('show');
                if(profileDropdown) profileDropdown.classList.remove('show');
            });
        });
        </script>

        <!-- Area Notifikasi Global -->
        <div style="padding: 15px 30px 0 30px;">
            <?php if (isset($_SESSION['success_message'])): ?>
            <div style="background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 14px;">
                <i class="fa-solid fa-check text-green-600"></i>
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
            <div style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 14px;">
                <i class="fa-solid fa-xmark text-red-600"></i>
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
            <?php endif; ?>
        </div>
