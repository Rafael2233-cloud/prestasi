<?php
session_start();
require_once '../koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$mahasiswa_id = $_SESSION['mahasiswa_id'];

// PROSES SUBMIT PRESTASI
// Auto-migrate schema if needed
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM prestasi LIKE 'kategori'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE prestasi CHANGE jenis jenis VARCHAR(100) NOT NULL");
    mysqli_query($conn, "ALTER TABLE prestasi ADD COLUMN kategori VARCHAR(50) AFTER judul");
}
$check_col_pendukung = mysqli_query($conn, "SHOW COLUMNS FROM prestasi LIKE 'file_pendukung'");
if (mysqli_num_rows($check_col_pendukung) == 0) {
    mysqli_query($conn, "ALTER TABLE prestasi ADD COLUMN file_pendukung TEXT AFTER bukti_file");
} else {
    mysqli_query($conn, "ALTER TABLE prestasi MODIFY file_pendukung TEXT");
}
// Ensure tingkat is VARCHAR for flexibility
mysqli_query($conn, "ALTER TABLE prestasi MODIFY tingkat VARCHAR(50) NOT NULL");

// Auto migrate poin_config for Kota and Kabupaten
$check_poin = mysqli_query($conn, "SELECT * FROM poin_config WHERE tingkat='Kota'");
if ($check_poin && mysqli_num_rows($check_poin) == 0) {
    mysqli_query($conn, "INSERT INTO poin_config (tingkat, juara, poin) SELECT 'Kota', juara, poin FROM poin_config WHERE tingkat='Kota/Kabupaten'");
    mysqli_query($conn, "INSERT INTO poin_config (tingkat, juara, poin) SELECT 'Kabupaten', juara, poin FROM poin_config WHERE tingkat='Kota/Kabupaten'");
}

if (isset($_POST['submit_prestasi'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis']);
    $tingkat = mysqli_real_escape_string($conn, $_POST['tingkat']);
    $juara = mysqli_real_escape_string($conn, $_POST['juara']);
    $tahun = mysqli_real_escape_string($conn, $_POST['tahun']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    // File upload logic
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $bukti_file_name = '';
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    
    if (isset($_FILES['bukti_file']) && $_FILES['bukti_file']['error'] === UPLOAD_ERR_OK) {
        $file_type = mime_content_type($_FILES['bukti_file']['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            // Get original extension to preserve it
            $ext = pathinfo($_FILES['bukti_file']['name'], PATHINFO_EXTENSION);
            // Reconstruct the name to avoid issues with special characters and ensure it keeps the extension
            $bukti_file_name = time() . '_bukti_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($_FILES['bukti_file']['name'], PATHINFO_FILENAME)) . '.' . $ext;
            
            move_uploaded_file($_FILES['bukti_file']['tmp_name'], $upload_dir . $bukti_file_name);
        } else {
            $_SESSION['error_message'] = 'Format file utama tidak didukung. Harap upload PDF, JPG, atau PNG.';
            header("Location: Input.php");
            exit();
        }
    }

    $file_pendukung_names = [];
    if (isset($_FILES['file_pendukung']) && !empty($_FILES['file_pendukung']['name'][0])) {
        $total_files = count($_FILES['file_pendukung']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['file_pendukung']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['file_pendukung']['tmp_name'][$i];
                $file_type = mime_content_type($tmp_name);
                
                if (in_array($file_type, $allowed_types)) {
                    $ext = pathinfo($_FILES['file_pendukung']['name'][$i], PATHINFO_EXTENSION);
                    $name = time() . '_' . $i . '_pendukung_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($_FILES['file_pendukung']['name'][$i], PATHINFO_FILENAME)) . '.' . $ext;
                    if (move_uploaded_file($tmp_name, $upload_dir . $name)) {
                        $file_pendukung_names[] = $name;
                    }
                }
            }
        }
    }
    $file_pendukung_name_str = implode(',', $file_pendukung_names);

    $insert_query = "INSERT INTO prestasi (mahasiswa_id, judul, kategori, jenis, tingkat, juara, tahun, deskripsi, bukti_file, file_pendukung, status, poin) 
                     VALUES ('$mahasiswa_id', '$judul', '$kategori', '$jenis', '$tingkat', '$juara', '$tahun', '$deskripsi', '$bukti_file_name', '$file_pendukung_name_str', 'pending', 0)";
    
    if (mysqli_query($conn, $insert_query)) {
        $_SESSION['success_message'] = 'Prestasi berhasil diajukan! Menunggu verifikasi admin.';
        header("Location: Input.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Gagal menyimpan prestasi.';
    }
}

include 'LayoutHeader.php';
?>

<div class="p-6 lg:p-8 w-full">
    <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight">Input Prestasi</h2>
        <p class="text-slate-500 mt-1 text-sm">Tambahkan pencapaian prestasi akademik dan non-akademik Anda.</p>
    </div>

    <div class="mb-6 bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-start gap-3">
        <div class="text-blue-500 mt-0.5 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <h4 class="text-sm font-bold text-blue-800">Transparansi Penilaian Poin</h4>
            <p class="text-xs text-blue-600 mt-1 leading-relaxed">Poin prestasi Anda akan dihitung secara transparan berdasarkan <b>Tingkat Prestasi</b> dan <b>Pencapaian Juara</b>. <a href="InfoPoin.php" class="font-bold underline hover:text-blue-800 transition-colors">Lihat detail tabel poin di halaman Info Poin Prestasi</a>.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <form method="POST" action="Input.php" class="space-y-6" enctype="multipart/form-data">
            <div>
                <label class="block text-slate-700 text-sm font-bold mb-2">Judul Prestasi <span class="text-rose-500">*</span></label>
                <input type="text" name="judul" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm" placeholder="Contoh: Juara 1 Lomba Web Design Tingkat Nasional" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2">Kategori <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select name="kategori" id="kategori" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm appearance-none cursor-pointer" required>
                            <option value="" disabled selected>Pilih Kategori</option>
                            <option value="Akademik">Akademik</option>
                            <option value="Non-Akademik">Non-Akademik</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2">Subkategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="jenis" id="subkategori" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm" placeholder="Masukkan subkategori prestasi" required>
                </div>

                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2">Tingkat Prestasi <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select name="tingkat" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm appearance-none cursor-pointer" required>
                            <option value="" disabled selected>Pilih Tingkat</option>
                            <option value="Internasional">Tingkat Internasional</option>
                            <option value="Nasional">Tingkat Nasional</option>
                            <option value="Provinsi">Tingkat Provinsi</option>
                            <option value="Kota">Tingkat Kota</option>
                            <option value="Kabupaten">Tingkat Kabupaten</option>
                            <option value="Kampus">Tingkat Kampus</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2">Pencapaian (Juara) <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select name="juara" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm appearance-none cursor-pointer" required>
                            <option value="" disabled selected>Pilih Pencapaian</option>
                            <option value="Juara 1">Juara 1</option>
                            <option value="Juara 2">Juara 2</option>
                            <option value="Juara 3">Juara 3</option>
                            <option value="Harapan 1">Harapan 1</option>
                            <option value="Harapan 2">Harapan 2</option>
                            <option value="Finalis">Finalis</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 text-sm font-bold mb-2">Tahun <span class="text-rose-500">*</span></label>
                    <input type="number" name="tahun" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm" placeholder="Misal: 2024" min="2000" max="2099" required>
                </div>
            </div>

            <div>
                <label class="block text-slate-700 text-sm font-bold mb-2">Deskripsi Singkat</label>
                <textarea name="deskripsi" rows="4" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm" placeholder="Jelaskan secara singkat kompetisi dan peran Anda di dalamnya..."></textarea>
            </div>

            <div>
                <label class="block text-slate-700 text-sm font-bold mb-2">Unggah Bukti (Sertifikat/Foto) <span class="text-rose-500">*</span></label>
                <input type="file" name="bukti_file" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-campus-100 file:text-campus-700 hover:file:bg-campus-200" accept="image/*,.pdf" required>
                <p class="text-xs text-slate-500 mt-2">File ini wajib diunggah. Maksimal ukuran file: 5MB. Format didukung: PDF, JPG, PNG.</p>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-slate-700 text-sm font-bold">Unggah File Pendukung <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <button type="button" onclick="addFilePendukung()" class="text-xs bg-campus-100 text-campus-700 hover:bg-campus-200 px-3 py-1.5 rounded-lg font-bold transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Tambah File
                    </button>
                </div>
                <div id="filePendukungContainer" class="space-y-3">
                    <div class="flex items-center gap-3 file-row">
                        <input type="file" name="file_pendukung[]" class="flex-1 w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-campus-500 focus:border-transparent outline-none transition-all text-sm cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-campus-100 file:text-campus-700 hover:file:bg-campus-200" accept="image/*,.pdf">
                        <button type="button" onclick="removeFilePendukung(this)" class="text-rose-500 bg-rose-50 hover:bg-rose-100 p-3 rounded-xl transition-colors hidden remove-btn">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-2">Bisa berupa foto kegiatan, surat tugas/delegasi, poster lomba, bukti pengumuman juara, atau dokumen tambahan lainnya. Maksimal ukuran per file: 5MB. Format didukung: PDF, JPG, PNG.</p>
            </div>

            <div class="flex gap-4 pt-4 border-t border-slate-100">
                <button type="submit" name="submit_prestasi" class="bg-campus-600 text-white font-bold py-3 px-6 rounded-xl hover:bg-campus-700 transition-colors focus:ring-4 focus:ring-campus-500/30 shadow-md flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Simpan Prestasi
                </button>
                <button type="reset" class="bg-white border border-slate-300 text-slate-700 font-bold py-3 px-6 rounded-xl hover:bg-slate-50 transition-colors focus:ring-4 focus:ring-slate-200">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function addFilePendukung() {
    const container = document.getElementById('filePendukungContainer');
    const firstRow = container.querySelector('.file-row');
    const newRow = firstRow.cloneNode(true);
    
    // Clear the file input value
    newRow.querySelector('input[type="file"]').value = '';
    
    // Show the remove button
    newRow.querySelector('.remove-btn').classList.remove('hidden');
    container.appendChild(newRow);
    
    updateRemoveButtons();
}

function removeFilePendukung(button) {
    const row = button.closest('.file-row');
    const container = document.getElementById('filePendukungContainer');
    
    if (container.querySelectorAll('.file-row').length > 1) {
        row.remove();
        updateRemoveButtons();
    } else {
        row.querySelector('input[type="file"]').value = '';
    }
}

function updateRemoveButtons() {
    const container = document.getElementById('filePendukungContainer');
    const rows = container.querySelectorAll('.file-row');
    const btns = container.querySelectorAll('.remove-btn');
    
    if (rows.length > 1) {
        btns.forEach(btn => btn.classList.remove('hidden'));
    } else {
        btns.forEach(btn => btn.classList.add('hidden'));
    }
}
</script>
<?php include 'LayoutFooter.php'; ?>
