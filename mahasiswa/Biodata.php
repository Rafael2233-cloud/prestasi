<?php
require_once '../koneksi.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proses Ubah Password
if (isset($_POST['update_password'])) {
    $old_pass = mysqli_real_escape_string($conn, md5($_POST['old_password']));
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    $user_id = $_SESSION['user_id'];
    $check_query = mysqli_query($conn, "SELECT password FROM users WHERE id = '$user_id'");
    $user_data = mysqli_fetch_assoc($check_query);
    
    if ($user_data['password'] !== $old_pass) {
        $_SESSION['error_message'] = 'Password lama yang Anda masukkan salah.';
    } elseif (strlen($new_pass) < 6) {
        $_SESSION['error_message'] = 'Password baru harus memiliki minimal 6 karakter.';
    } elseif ($new_pass !== $confirm_pass) {
        $_SESSION['error_message'] = 'Konfirmasi password baru tidak cocok.';
    } else {
        $new_pass_hash = md5($new_pass);
        if (mysqli_query($conn, "UPDATE users SET password = '$new_pass_hash' WHERE id = '$user_id'")) {
            $_SESSION['success_message'] = 'Password berhasil diperbarui. Silakan gunakan password baru ini saat login kembali.';
        } else {
            $_SESSION['error_message'] = 'Terjadi kesalahan sistem saat memperbarui password.';
        }
    }
    header("Location: Biodata.php");
    exit();
}

include 'LayoutHeader.php';
?>
<div class="p-6 lg:p-8 w-full">
    <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Biodata & Akun</h2>
        <p class="text-slate-500 mt-1 text-sm">Kelola informasi data diri akademik dan keamanan akun Anda</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative">
        <div class="absolute top-0 right-0 w-64 h-64 bg-campus-500/10 rounded-full filter blur-3xl -mr-20 -mt-20"></div>
        <div class="bg-gradient-to-r from-campus-700 to-campus-500 h-32 relative z-10 overflow-hidden">
            <div class="absolute inset-0 bg-white/10 pattern-grid opacity-30"></div>
        </div>
        <div class="px-8 pb-8 relative z-20">
            <div class="flex items-end -mt-16 mb-8">
                <div class="w-32 h-32 bg-slate-50 rounded-2xl border-4 border-white shadow-lg flex items-center justify-center relative overflow-hidden group">
                    <svg class="w-16 h-16 text-slate-300" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    <div class="absolute inset-0 bg-black/50 hidden group-hover:flex items-center justify-center cursor-pointer transition-all">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
                <div class="ml-6 mb-2">
                    <h3 class="text-2xl font-extrabold text-slate-800 tracking-tight"><?= htmlspecialchars($biodata['nama'] ?? '') ?></h3>
                    <p class="text-campus-600 font-semibold"><?= htmlspecialchars($biodata['nim'] ?? '') ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 hover:border-campus-200 transition-colors">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-campus-100 text-campus-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">Program Studi</p>
                    </div>
                    <p class="text-lg font-bold text-slate-800 ml-11"><?= htmlspecialchars($biodata['prodi'] ?? '-') ?></p>
                </div>
                
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 hover:border-campus-200 transition-colors">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">Angkatan</p>
                    </div>
                    <p class="text-lg font-bold text-slate-800 ml-11"><?= htmlspecialchars($biodata['angkatan'] ?? '-') ?></p>
                </div>

                <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 hover:border-campus-200 transition-colors">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">Email</p>
                    </div>
                    <p class="text-lg font-bold text-slate-800 ml-11"><?= htmlspecialchars($biodata['email'] ?? '-') ?></p>
                </div>

                <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 hover:border-campus-200 transition-colors">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">Telepon</p>
                    </div>
                    <p class="text-lg font-bold text-slate-800 ml-11"><?= htmlspecialchars($biodata['telp'] ?? '-') ?></p>
                </div>

                <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 hover:border-campus-200 transition-colors md:col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-fuchsia-100 text-fuchsia-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">Pembimbing Akademik</p>
                    </div>
                    <p class="text-lg font-bold text-slate-800 ml-11"><?= htmlspecialchars($biodata['pembimbing_akademik'] ?? '-') ?></p>
                </div>
            </div>

        </div>
    </div>

    <!-- Ganti Password Section -->
    <div class="mt-8 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden max-w-3xl">
        <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-campus-50 text-campus-600 flex items-center justify-center shadow-sm border border-campus-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800">Ganti Password</h3>
        </div>

        <div class="p-6 md:p-8">
            <form method="POST" action="Biodata.php" class="space-y-6">
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2">Password Lama <span class="text-rose-500">*</span></label>
                    <input type="password" name="old_password" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm" placeholder="Masukkan password lama" required>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-slate-700 text-sm font-bold mb-2">Password Baru <span class="text-rose-500">*</span></label>
                        <input type="password" name="new_password" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm" placeholder="Minimal 6 karakter" minlength="6" required>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-sm font-bold mb-2">Konfirmasi Password Baru <span class="text-rose-500">*</span></label>
                        <input type="password" name="confirm_password" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm" placeholder="Ulangi password baru" minlength="6" required>
                    </div>
                </div>
                
                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" name="update_password" class="bg-campus-600 text-white font-bold py-3 px-8 rounded-xl hover:bg-campus-700 transition-colors focus:ring-4 focus:ring-campus-500/30 shadow-md flex items-center gap-2 text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Simpan Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'LayoutFooter.php'; ?>
