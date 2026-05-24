<?php
session_start();
require '../koneksi.php';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $hashed_password = md5($password);
        
        // Cek login via username ATAU email
        $q_check_user = $conn->query("SELECT * FROM admin WHERE username='$username' OR email='$username'");
        
        if ($q_check_user->num_rows > 0) {
            $user = $q_check_user->fetch_assoc();
            if ($user['password'] === $hashed_password) {
                // Update last login
                $user_id = $user['id'];
                $conn->query("UPDATE admin SET terakhir_login=NOW() WHERE id=$user_id");

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_nama'] = $user['nama'];
                $_SESSION['admin_nip'] = $user['nip'];
                
                header("Location: Dashboard.php");
                exit;
            } else {
                $error_msg = "Password salah!";
            }
        } else {
            $error_msg = "Username atau Email tidak ditemukan!";
        }
    } else {
        $error_msg = "Silakan lengkapi username dan password.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPRESMA - Login</title>
    
    <!-- Modern font from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="Assets/Css/Style.css">
</head>
<body class="login-page">

    <main class="login-wrapper">
        <div class="login-card">
            
            <!-- Bagian Atas: Header Biru -->
            <div class="login-card-header">
                <div class="logo-container" style="width:120px; height:120px; display:flex; align-items:center; justify-content:center; margin: 0 auto 20px auto; flex-shrink:0; border-radius:50%; background:#ffffff; overflow:hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                    <img src="Assets/Images/logo_fasilkom.png" alt="Logo Fasilkom" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <h1 class="portal-title">SIPRESMA</h1>
                <p class="portal-subtitle">Sistem Informasi Prestasi Mahasiswa</p>
            </div>

            <!-- Bagian Bawah: Form Putih -->
            <div class="login-card-body">
                <h2 class="form-title">Login Admin</h2>
                
                <form action="Index.php" method="POST" class="login-form">
                    
                    <div class="form-group">
                        <label for="username">Username atau Email</label>
                        <input type="text" id="username" name="username" placeholder="Masukkan username atau email" required autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                            <i class="fa-regular fa-eye toggle-password" id="togglePasswordBtn"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Login</button>

                </form>
            </div>
            
        </div>
    </main>

    <!-- Custom JS -->
    <script src="Assets/Js/Script.js"></script>
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
