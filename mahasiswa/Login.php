<?php
// Include file config untuk koneksi database
require_once '../koneksi.php';

// Start session
session_start();

// Jika sudah login, redirect ke dashboard.php
if (isset($_SESSION['user_id'])) {
    header("Location: Dashboard.php");
    exit();
}

// ============================================
// PROSES LOGIN
// ============================================
$error_msg = '';
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Query user dari database dengan kolom eksplisit untuk menghindari konflik nama kolom
    $query = "SELECT u.id AS u_id, u.username, u.password, u.role, 
                     m.id AS m_id, m.nama, m.nim
              FROM users u 
              LEFT JOIN mahasiswa m ON u.id = m.user_id 
              WHERE u.username = '$username' AND u.role = 'mahasiswa'";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Verifikasi password (MD5 untuk dummy)
        if (md5($password) === $user['password']) {
            // Set session secara aman menggunakan kolom ber-alias
            $_SESSION['user_id'] = $user['u_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['nim'] = $user['nim'];
            $_SESSION['mahasiswa_id'] = $user['m_id'];
            
            header("Location: Dashboard.php");
            exit();
        } else {
            $error_msg = 'Password salah!';
        }
    } else {
        $error_msg = 'Username tidak ditemukan!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPRESMA - Login Mahasiswa</title>
    
    <!-- Modern font from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../administrator/Assets/Css/Style.css">
</head>
<body class="login-page">

    <main class="login-wrapper">
        <div class="login-card">
            
            <!-- Bagian Atas: Header Biru -->
            <div class="login-card-header">
                <div class="logo-container" style="width:120px; height:120px; display:flex; align-items:center; justify-content:center; margin: 0 auto 20px auto; flex-shrink:0; border-radius:50%; background:#ffffff; overflow:hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                    <img src="../administrator/Assets/Images/logo_fasilkom.png" alt="Logo Fasilkom" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <h1 class="portal-title">SIPRESMA</h1>
                <p class="portal-subtitle">Sistem Informasi Prestasi Mahasiswa</p>
            </div>

            <!-- Bagian Bawah: Form Putih -->
            <div class="login-card-body">
                <h2 class="form-title">Login Mahasiswa</h2>
                
                <form action="Login.php" method="POST" class="login-form">
                    
                    <div class="form-group">
                        <label for="username">Username / NIM</label>
                        <input type="text" id="username" name="username" placeholder="Masukkan NIM Anda" required autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                            <i class="fa-regular fa-eye toggle-password" id="togglePasswordBtn"></i>
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn-primary">Login</button>

                </form>
            </div>
            
        </div>
    </main>

    <!-- Custom JS -->
    <script src="../administrator/Assets/Js/Script.js"></script>
    <?php if($error_msg): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Gagal Login',
            text: '<?= $error_msg ?>',
            confirmButtonColor: '#3b82f6'
        });
    </script>
    <?php endif; ?>
</body>
</html>
