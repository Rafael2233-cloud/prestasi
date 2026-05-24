<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
require '../koneksi.php';

// Fetch all revision history
$query = "SELECT pr.*, a.nama as admin_name 
          FROM poin_revisi pr 
          LEFT JOIN admin a ON pr.admin_id = a.id 
          ORDER BY pr.tanggal_perubahan DESC";
$result = $conn->query($query);
$revisions = [];
while($row = $result->fetch_assoc()) {
    $revisions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Kriteria Poin - SIPRESMA</title>
    
    <!-- Modern font from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../administrator/Assets/Css/Style.css">
    <style>
        .page-header-new {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-start; }
        .page-title-new h1 { margin: 0 0 5px 0; font-size: 24px; font-weight: 800; color: #0f172a; }
        .page-title-new p { font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 400; color: #64748b; line-height: 1.5; margin: 0; }
        
        .card-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 25px;
        }
        .panel-header { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; }
        .panel-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
        
        .revisi-table { width: 100%; border-collapse: collapse; text-align: left; }
        .revisi-table th { padding: 15px 25px; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;}
        .revisi-table td { padding: 15px 25px; font-size: 13px; border-bottom: 1px solid #f1f5f9; color: #475569; vertical-align: middle; transition: all 0.3s; }
        .revisi-table tr:hover td { background: #f8fafc; }
        
        .btn-action { padding: 6px 12px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s; }
        .btn-approve { background: #10b981; color: white; }
        .btn-approve:hover { background: #059669; }
        .btn-reject { background: #ef4444; color: white; }
        .btn-reject:hover { background: #dc2626; }
    </style>
</head>
<body class="dashboard-body new-dashboard">
    <?php include 'LayoutSidebar.php'; ?>
    <main class="main-content-new" id="mainContent">
        <?php include 'Topbar.php'; ?>

        <div class="content-wrapper-new">
            <div class="page-header-new">
                <div class="page-header-flex">
                    <div class="page-title-new">
                        <h1>Persetujuan Kriteria Poin</h1>
                        <p>Kelola persetujuan perubahan sistem poin prestasi oleh admin.</p>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 15px;">
                        <div class="breadcrumb-top" style="margin-bottom: 0;">
                            <a href="Dashboard.php">Beranda</a> <span>/</span> Persetujuan
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-panel animate-in">
                <div class="panel-header">
                    <h3>Permintaan Persetujuan Baru</h3>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="revisi-table">
                        <thead>
                            <tr>
                                <th>PENCAPAIAN</th>
                                <th>POIN LAMA</th>
                                <th>POIN BARU</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($revisions) > 0): ?>
                                <?php foreach($revisions as $rev): 
                                    if ($rev['status'] !== 'Menunggu Persetujuan') continue;
                                ?>
                                <tr>
                                    <td>
                                        <strong style="color: #0f172a; font-weight: 700;"><?= htmlspecialchars($rev['tingkat']) ?></strong><br>
                                        <span style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($rev['juara']) ?></span>
                                    </td>
                                    <td>
                                        <?php if($rev['tipe'] === 'insert'): ?>
                                            <span style="color: #64748b;">-</span>
                                        <?php else: ?>
                                            <span style="color: #64748b;"><?= $rev['poin_lama'] ?> pts</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($rev['tipe'] === 'delete'): ?>
                                            <span style="color: #ef4444; font-weight: 600; text-decoration: line-through;">Dihapus</span>
                                        <?php else: ?>
                                            <span style="color: #2563eb; font-weight: 700;"><?= $rev['poin_baru'] ?> pts</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <button class="btn-action btn-approve" onclick="processApproval(<?= $rev['id'] ?>, 'approve')">Setujui</button>
                                            <button class="btn-action btn-reject" onclick="processReject(<?= $rev['id'] ?>)">Tolak</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php 
                                // Check if there's any pending
                                $has_pending = false;
                                foreach($revisions as $r) {
                                    if($r['status'] === 'Menunggu Persetujuan') {
                                        $has_pending = true;
                                        break;
                                    }
                                }
                                if(!$has_pending):
                                ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px; color: #64748b;">Tidak ada pengajuan persetujuan saat ini.</td>
                                </tr>
                                <?php endif; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px; color: #64748b;">Belum ada riwayat perubahan poin.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Custom JS -->
    <script src="../administrator/Assets/Js/Script.js"></script>
    <script>
    function processReject(id) {
        Swal.fire({
            title: 'Tolak Perubahan',
            input: 'textarea',
            inputLabel: 'Alasan Penolakan',
            inputPlaceholder: 'Masukkan alasan...',
            inputAttributes: {
                'aria-label': 'Masukkan alasan'
            },
            showCancelButton: true,
            confirmButtonText: 'Tolak',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ef4444',
            preConfirm: (alasan) => {
                if (!alasan) {
                    Swal.showValidationMessage('Alasan penolakan wajib diisi!');
                }
                return alasan;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                submitApproval(id, 'reject', result.value);
            }
        });
    }

    function processApproval(id, action) {
        Swal.fire({
            title: 'Setujui Perubahan?',
            text: 'Kriteria poin akan segera diupdate ke sistem utama.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                submitApproval(id, 'approve', '');
            }
        });
    }

    function submitApproval(id, action, alasan) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('id', id);
        if(alasan) fd.append('alasan', alasan);
        
        fetch('ApiPersetujuan.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Berhasil', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
        });
    }
    </script>
</body>
</html>

