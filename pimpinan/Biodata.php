<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
require '../koneksi.php';

// Data Pimpinan dari DB
$username_session = $_SESSION['username'] ?? '';
$q_pimpinan = $conn->query("SELECT * FROM pimpinan WHERE username='$username_session' LIMIT 1");
if ($q_pimpinan && $q_pimpinan->num_rows > 0) {
    $pimpinan_data = $q_pimpinan->fetch_assoc();
    $pimpinan_nip = $pimpinan_data['nip'];
    $pimpinan_nama = $pimpinan_data['nama'];
    $pimpinan_username = $pimpinan_data['username'];
    $pimpinan_email = $pimpinan_data['email'];
} else {
    $pimpinan_nip = '-';
    $pimpinan_nama = '-';
    $pimpinan_username = '-';
    $pimpinan_email = '-';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biodata - SIPRESMA</title>
    <!-- Modern font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../administrator/Assets/Css/Style.css?v=<?= time() ?>">
    <style>
        .profile-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid #f0f0f0;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
        }
        @media (min-width: 768px) {
            .profile-container {
                flex-direction: row;
            }
        }
        .profile-left {
            padding: 40px;
            text-align: center;
            border-right: 1px solid #f0f0f0;
            flex: 1;
            max-width: 350px;
        }
        .profile-right {
            padding: 40px;
            flex: 2;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background-color: #2563eb;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
            font-weight: 700;
            margin: 0 auto 20px;
        }
        .profile-name {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .profile-role-badge {
            display: inline-block;
            background-color: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        .profile-stats-list {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
        }
        .profile-stats-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }
        .profile-stats-list li:last-child {
            border-bottom: none;
        }
        .profile-stats-list li .stat-label {
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .profile-stats-list li .stat-value {
            font-weight: 600;
            color: #334155;
        }
        .stat-value.status-active {
            color: #10b981;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 25px;
        }
        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }
        .info-list li {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px dashed #e2e8f0;
            align-items: flex-start;
        }
        .info-list li .info-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #f1f5f9;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .info-list li .info-label {
            width: 150px;
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
            padding-top: 8px;
            flex-shrink: 0;
        }
        .info-list li .info-value {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
            padding-top: 8px;
        }
        
        .btn-edit {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            position: absolute;
            bottom: 40px;
            right: 40px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .btn-edit:hover {
            background-color: #1d4ed8;
        }
        
        /* Edit Mode Styles */
        .edit-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            display: none;
        }
        .edit-input:focus {
            outline: none;
            border-color: #2563eb;
        }
        .edit-mode .info-value-text {
            display: none;
        }
        .edit-mode .edit-input {
            display: block;
        }
        
        /* Header Styles */
        .page-header-new { margin-bottom: 25px; }
        .breadcrumb-top { font-size: 13px; color: #2563eb; font-weight: 500; text-align: right; margin-bottom: 5px; }
        .breadcrumb-top span { color: #6b7280; }
        .breadcrumb-top a { color: #2563eb; text-decoration: none; }
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; width: 100%; }
        .page-title-new { display: flex; align-items: center; gap: 15px; }
        
        @media (max-width: 768px) {
            .page-header-flex { flex-direction: column; align-items: flex-start; gap: 15px; }
            .breadcrumb-top { align-self: flex-start; }
        }
    </style>
</head>
<body class="dashboard-body new-dashboard">

    <!-- Sidebar -->
    <?php include 'LayoutSidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content-new" id="mainContent">
        <?php include 'Topbar.php'; ?>

        <!-- Content -->
        <div class="content-wrapper-new">
            <div class="page-header-new">
                <div class="page-header-flex">
                    <div class="page-title-new">
                        <div class="page-title-text">
                            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0 0 5px 0;">Biodata Profil</h1>
                            <p style="font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 400; color: #64748b; line-height: 1.5; margin: 0;">Kelola informasi profil admin dan data akun sistem.</p>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                        <div class="breadcrumb-top">
                            <a href="Dashboard.php">Beranda</a> <span>/</span> Biodata Profil
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="profile-container">
                <!-- Left Column -->
                <div class="profile-left">
                    <div class="profile-avatar"><?= substr(htmlspecialchars($pimpinan_nama), 0, 1) ?></div>
                    <div class="profile-name" id="displayNamaLeft"><?= htmlspecialchars($pimpinan_nama) ?></div>
                    <div style="font-size: 13px; color: #64748b; margin-bottom: 5px;" id="displayNipLeft">NIP: <?= htmlspecialchars($pimpinan_nip) ?></div>
                    <div class="profile-role-badge">Pimpinan Sistem Prestasi</div>
                    
                    <ul class="profile-stats-list">
                        <li>
                            <span class="stat-label"><i class="fa-regular fa-clock"></i> Terakhir Login</span>
                            <span class="stat-value"><?= date('d M Y, H:i') ?> WIB</span>
                        </li>
                        <li>
                            <span class="stat-label"><i class="fa-solid fa-shield-halved"></i> Status Akun</span>
                            <span class="stat-value status-active">Aktif</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Right Column -->
                <div class="profile-right" id="profileFormArea">
                    <h3 class="section-title">Informasi Akun</h3>
                    
                    <ul class="info-list">
                        <li class="info-item">
                            <div class="info-icon"><i class="fa-regular fa-id-card"></i></div>
                            <div class="info-label">NIP</div>
                            <div class="info-value">
                                <span class="info-value-text"><?= htmlspecialchars($pimpinan_nip) ?></span>
                                <input type="text" class="edit-input" value="<?= htmlspecialchars($pimpinan_nip) ?>" id="editNip">
                            </div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon"><i class="fa-regular fa-user"></i></div>
                            <div class="info-label">Nama Lengkap</div>
                            <div class="info-value">
                                <span class="info-value-text"><?= htmlspecialchars($pimpinan_nama) ?></span>
                                <input type="text" class="edit-input" value="<?= htmlspecialchars($pimpinan_nama) ?>" id="editNama">
                            </div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-user-tag"></i></div>
                            <div class="info-label">Username</div>
                            <div class="info-value">
                                <span class="info-value-text"><?= htmlspecialchars($pimpinan_username) ?></span>
                                <input type="text" class="edit-input" value="<?= htmlspecialchars($pimpinan_username) ?>" id="editUsername">
                            </div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon"><i class="fa-regular fa-envelope"></i></div>
                            <div class="info-label">Email Valid</div>
                            <div class="info-value">
                                <span class="info-value-text"><?= htmlspecialchars($pimpinan_email) ?></span>
                                <input type="email" class="edit-input" value="<?= htmlspecialchars($pimpinan_email) ?>" id="editEmail">
                            </div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                            <div class="info-label">Hak Akses</div>
                            <div class="info-value">
                                <ul style="margin: 0; padding-left: 20px; color: #1e293b; font-size: 13px; line-height: 1.6;">
                                    <li>Rekap Data Prestasi</li>
                                    <li>Persetujuan Poin</li>
                                    <li>Monitoring Leaderboard</li>
                                </ul>
                            </div>
                        </li>
                        <li class="info-item">
                            <div class="info-icon"><i class="fa-regular fa-id-badge"></i></div>
                            <div class="info-label">Role</div>
                            <div class="info-value">
                                <span class="info-value-text">Pimpinan Sistem Prestasi</span>
                            </div>
                        </li>
                    </ul>

                    <button class="btn-edit" id="btnEditProfile">
                        <i class="fa-solid fa-pen-to-square"></i> <span id="btnEditText">Edit Profil</span>
                    </button>
                </div>
            </div>
            
            <!-- Ganti Password Section -->
            <div class="profile-container" style="margin-top: 25px; display: block; padding: 40px;">
                
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px;">
                    <div style="width: 45px; height: 45px; background: #eff6ff; color: #2563eb; border-radius: 10px; border: 1px solid #dbeafe; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">Ganti Password</h3>
                </div>

                <div style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 8px;">Password Lama <span style="color: #ef4444;">*</span></label>
                        <div class="password-wrapper" style="position: relative;">
                            <input type="password" id="oldPassword" class="edit-input" placeholder="Masukkan password lama" style="display: block; padding: 12px 15px; padding-right: 40px; border: 1px solid #e2e8f0; border-radius: 8px; width: 100%; box-sizing: border-box; background: #f8fafc;">
                            <i class="fa-regular fa-eye toggle-password-settings" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; transition: color 0.2s;"></i>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 8px;">Password Baru <span style="color: #ef4444;">*</span></label>
                        <div class="password-wrapper" style="position: relative;">
                            <input type="password" id="newPassword" class="edit-input" placeholder="Minimal 6 karakter" style="display: block; padding: 12px 15px; padding-right: 40px; border: 1px solid #e2e8f0; border-radius: 8px; width: 100%; box-sizing: border-box; background: #f8fafc;">
                            <i class="fa-regular fa-eye toggle-password-settings" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; transition: color 0.2s;"></i>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 8px;">Konfirmasi Password Baru <span style="color: #ef4444;">*</span></label>
                        <div class="password-wrapper" style="position: relative;">
                            <input type="password" id="confirmPassword" class="edit-input" placeholder="Ulangi password baru" style="display: block; padding: 12px 15px; padding-right: 40px; border: 1px solid #e2e8f0; border-radius: 8px; width: 100%; box-sizing: border-box; background: #f8fafc;">
                            <i class="fa-regular fa-eye toggle-password-settings" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; transition: color 0.2s;"></i>
                        </div>
                    </div>
                </div>

                <div style="text-align: right; border-top: 1px solid #f1f5f9; padding-top: 25px;">
                    <button class="btn-edit" onclick="updatePassword()" style="position: static; background-color: #2563eb; padding: 12px 24px; font-size: 14px; display: inline-flex; border-radius: 8px;">
                        <i class="fa-solid fa-download"></i> <span>Simpan Password</span>
                    </button>
                </div>
            </div>
            
        </div>
    </main>

    <!-- Custom JS -->
    <script src="../administrator/Assets/Js/Script.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnEditProfile = document.getElementById('btnEditProfile');
            const btnEditText = document.getElementById('btnEditText');
            const formArea = document.getElementById('profileFormArea');
            const infoItems = document.querySelectorAll('.info-item');
            
            let isEditing = false;
            
            if(btnEditProfile) {
                btnEditProfile.addEventListener('click', function() {
                    if (!isEditing) {
                        // Masuk mode edit
                        isEditing = true;
                        
                        infoItems[0].classList.add('edit-mode');
                        infoItems[1].classList.add('edit-mode');
                        infoItems[2].classList.add('edit-mode');
                        infoItems[3].classList.add('edit-mode');
                        
                        this.style.backgroundColor = '#10b981'; // Green for save
                        this.innerHTML = '<i class="fa-solid fa-check"></i> <span id="btnEditText">Simpan Perubahan</span>';
                    } else {
                        // Simpan
                        const newNip = document.getElementById('editNip').value;
                        const newNama = document.getElementById('editNama').value;
                        const newUsername = document.getElementById('editUsername').value;
                        const newEmail = document.getElementById('editEmail').value;
                        
                        const formData = new FormData();
                        formData.append('action', 'update_profile');
                        formData.append('nip', newNip);
                        formData.append('nama', newNama);
                        formData.append('username', newUsername);
                        formData.append('email', newEmail);

                        fetch('ApiPengaturan.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // Update text values
                                infoItems[0].querySelector('.info-value-text').textContent = newNip;
                                infoItems[1].querySelector('.info-value-text').textContent = newNama;
                                infoItems[2].querySelector('.info-value-text').textContent = newUsername;
                                infoItems[3].querySelector('.info-value-text').textContent = newEmail;
                                
                                document.getElementById('displayNamaLeft').textContent = newNama;
                                document.getElementById('displayNipLeft').textContent = 'NIP: ' + newNip;

                                // Hapus mode edit
                                infoItems[0].classList.remove('edit-mode');
                                infoItems[1].classList.remove('edit-mode');
                                infoItems[2].classList.remove('edit-mode');
                                infoItems[3].classList.remove('edit-mode');
                                
                                // Ubah kembali tombol
                                btnEditProfile.style.backgroundColor = '#2563eb';
                                btnEditProfile.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> <span id="btnEditText">Edit Profil</span>';
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil',
                                    text: data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                isEditing = false;
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: data.message
                                });
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan sistem.' });
                        });
                    }
                });
            }

            // Toggle Password
            const toggleIcons = document.querySelectorAll('.toggle-password-settings');
            toggleIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    if (input.type === 'password') {
                        input.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                        this.style.color = '#3b82f6';
                    } else {
                        input.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                        this.style.color = '#94a3b8';
                    }
                });
            });
        });

        function updatePassword() {
            const oldPass = document.getElementById('oldPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;

            if (!oldPass || !newPass || !confirmPass) {
                Swal.fire({ icon: 'warning', title: 'Oops...', text: 'Harap isi semua field password.' });
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_password');
            formData.append('old_password', oldPass);
            formData.append('new_password', newPass);
            formData.append('confirm_password', confirmPass);

            fetch('ApiPengaturan.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        document.getElementById('oldPassword').value = '';
                        document.getElementById('newPassword').value = '';
                        document.getElementById('confirmPassword').value = '';
                        window.location.href = 'Logout.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message
                    });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan sistem.' });
            });
        }
    </script>
</body>
</html>

