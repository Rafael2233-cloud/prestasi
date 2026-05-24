document.addEventListener('DOMContentLoaded', () => {

    /* 
    ===============================================
        Login Page (Index.php) Interactions
    =============================================== 
    */
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function () {
            // Toggle the type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle the eye icon class
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    /* ===============================================
         PERBAIKAN HAMBURGER & RESIZE CLASH
     =============================================== 
     */
    const sidebar = document.getElementById('sidebar');
    const btnSidebarDesktop = document.getElementById('toggleSidebar'); // Tombol di dalem sidebar (Desktop)
    const btnSidebarMobile = document.getElementById('btnHamburgerMobile'); // Tombol di Topbar (Mobile)

    if (sidebar) {
        // 1. Aksi klik tombol Desktop
        if (btnSidebarDesktop) {
            btnSidebarDesktop.addEventListener('click', function (e) {
                e.preventDefault();
                sidebar.classList.toggle('collapsed');
            });
        }

        // 2. Aksi klik tombol Mobile
        if (btnSidebarMobile) {
            btnSidebarMobile.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                sidebar.classList.toggle('collapsed');
            });
        }

        // 3. Fix Bentrok Auto-Resize 
        // Simpan status layar saat ini (Mobile atau bukan?)
        let isSaatIniMobile = window.innerWidth <= 768;

        // Pas web pertama kali diload
        if (isSaatIniMobile) {
            sidebar.classList.add('collapsed');
        }

        const handleResize = () => {
            const cekLayarMobile = window.innerWidth <= 768;

            // Cuma jalankan aksi reset KALAU layarnya bener-bener nyebrang batas 768px
            if (cekLayarMobile !== isSaatIniMobile) {
                isSaatIniMobile = cekLayarMobile;

                if (isSaatIniMobile) {
                    sidebar.classList.add('collapsed'); // Berubah ke mode HP -> Sembunyiin
                } else {
                    sidebar.classList.remove('collapsed'); // Berubah ke mode Laptop -> Buka lagi
                }
            }
        };

        window.addEventListener('resize', handleResize);
    }
    if (sidebar) {

        // Responsive behavior: Automatically collapse sidebar on smaller screens
        const handleResize = () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
        };

        // Call once on load
        handleResize();

        // Listen for window resize
        window.addEventListener('resize', handleResize);
    }

    /* 
    ===============================================
        Action Buttons & Dummy Links Interactions
    =============================================== 
    */
    // Removed static logic since we use API now

    /* 
    ===============================================
        Filter Logic (VerifikasiPrestasi.php)
    =============================================== 
    */
    const filterKategori = document.getElementById('filterKategori');
    const filterTingkat = document.getElementById('filterTingkat');
    const filterStatus = document.getElementById('filterStatus');
    const filterTahun = document.getElementById('filterTahun');

    if (filterKategori || filterTingkat || filterStatus || filterTahun) {
        const filters = [filterKategori, filterTingkat, filterStatus, filterTahun];

        const filterTable = () => {
            const valKategori = filterKategori ? filterKategori.value.toLowerCase() : '';
            const valTingkat = filterTingkat ? filterTingkat.value.toLowerCase() : '';
            const valStatus = filterStatus ? filterStatus.value.toLowerCase() : '';
            const valTahun = filterTahun ? filterTahun.value.toLowerCase() : '';

            const rows = document.querySelectorAll('.verifikasi-table tbody tr');

            rows.forEach(row => {
                // Skip if already fading out
                if (row.classList.contains('fade-out')) return;

                const cols = row.querySelectorAll('td');
                if (cols.length < 8) return;

                const textTanggal = cols[0].innerText.toLowerCase().trim();
                const textKategori = cols[3].innerText.toLowerCase().trim();
                const textSubkategori = cols[4].innerText.toLowerCase().trim();
                const textTingkat = cols[5].innerText.toLowerCase().trim();
                const textStatus = cols[7].innerText.toLowerCase().trim();

                const matchKategori = valKategori === '' || textKategori === valKategori;
                const matchTingkat = valTingkat === '' || textTingkat === valTingkat || textTingkat.includes(valTingkat);
                const matchStatus = valStatus === '' || textStatus === valStatus;
                const matchTahun = valTahun === '' || textTanggal.includes(valTahun);

                if (matchKategori && matchTingkat && matchStatus && matchTahun) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        };

        filters.forEach(filter => {
            if (filter) {
                filter.addEventListener('change', filterTable);
            }
        });
    }

    /* 
    ===============================================
        Data Mahasiswa Actions & Filters
    =============================================== 
    */
    const searchMahasiswa = document.getElementById('searchMahasiswa');
    const filterAngkatan = document.getElementById('filterAngkatan');

    const filterDataMahasiswaTable = () => {
        const valSearch = searchMahasiswa ? searchMahasiswa.value.toLowerCase() : '';
        const valAngkatan = filterAngkatan ? filterAngkatan.value.toLowerCase() : '';

        // Ensure we only select rows from the DataMahasiswa table, not other pages
        const tableRows = document.querySelectorAll('.verifikasi-table tbody tr');

        tableRows.forEach(row => {
            if (row.classList.contains('fade-out')) return;

            const cols = row.querySelectorAll('td');
            if (cols.length < 6) return;

            // Kolom 1: Nama Lengkap
            const textNama = cols[1].innerText.toLowerCase().trim();
            // Kolom 3: Angkatan
            const textAngkatan = cols[3].innerText.toLowerCase().trim();

            const matchSearch = valSearch === '' || textNama.includes(valSearch);
            const matchAngkatan = valAngkatan === '' || textAngkatan === valAngkatan;

            if (matchSearch && matchAngkatan) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    };

    if (searchMahasiswa) searchMahasiswa.addEventListener('keyup', filterDataMahasiswaTable);
    if (filterAngkatan) filterAngkatan.addEventListener('change', filterDataMahasiswaTable);

    /* 
    ===============================================
        Leaderboard Filters
    =============================================== 
    */
    const filterProdiLb = document.getElementById('filterProdiLeaderboard');
    const filterAngkatanLb = document.getElementById('filterAngkatanLeaderboard');
    const filterTahunLb = document.getElementById('filterTahunLeaderboard');

    const filterLeaderboardTable = () => {
        const valProdi = filterProdiLb ? filterProdiLb.value.toLowerCase() : '';
        const valAngkatan = filterAngkatanLb ? filterAngkatanLb.value.toLowerCase() : '';
        const valTahun = filterTahunLb ? filterTahunLb.value.toLowerCase() : '';

        // Select the table in Leaderboard.php
        const tableRows = document.querySelectorAll('.verifikasi-table tbody tr');

        tableRows.forEach(row => {
            const cols = row.querySelectorAll('td');
            if (cols.length < 7) return;

            // Kolom 3: Program Studi
            const textProdi = cols[3].innerText.toLowerCase().trim();
            // Kolom 4: Angkatan
            const textAngkatan = cols[4].innerText.toLowerCase().trim();

            // Mock Tahun using data-tahun attribute if exists, else match all
            const rowTahun = row.getAttribute('data-tahun') || '';

            const matchProdi = valProdi === '' || textProdi === valProdi;
            const matchAngkatan = valAngkatan === '' || textAngkatan === valAngkatan;
            const matchTahun = valTahun === '' || rowTahun === valTahun;

            if (matchProdi && matchAngkatan && matchTahun) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    };

    if (filterProdiLb) filterProdiLb.addEventListener('change', filterLeaderboardTable);
    if (filterAngkatanLb) filterAngkatanLb.addEventListener('change', filterLeaderboardTable);
    if (filterTahunLb) filterTahunLb.addEventListener('change', filterLeaderboardTable);

    // Export to Excel functionality
    const btnExportExcel = document.getElementById('btnExportExcel');
    if (btnExportExcel) {
        btnExportExcel.addEventListener('click', function () {
            const table = document.querySelector('.verifikasi-table');
            if (!table) return;

            // Generate HTML for Excel
            const html = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head><meta charset="UTF-8"></head>
                <body>${table.outerHTML}</body>
                </html>
            `;

            // Create Blob and trigger download
            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Leaderboard_Prestasi.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    }

    const btnEditData = document.querySelectorAll('.btn-edit-data');
    const btnHapusData = document.querySelectorAll('.btn-hapus-data');
    const editModal = document.getElementById('editMahasiswaModal');
    const closeEditBtn = document.querySelector('.close-modal-edit');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const saveEditBtn = document.getElementById('saveEditBtn');

    let currentRowToEdit = null;

    btnEditData.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            currentRowToEdit = this.closest('tr');

            if (editModal && currentRowToEdit) {
                // Populate modal with row data
                const tds = currentRowToEdit.querySelectorAll('td');
                if (tds.length >= 5) {
                    document.getElementById('editNim').value = tds[0].innerText.trim();
                    document.getElementById('editNama').value = tds[1].innerText.trim();
                    document.getElementById('editProdi').value = tds[2].innerText.trim();
                    document.getElementById('editAngkatan').value = tds[3].innerText.trim();
                    document.getElementById('editEmail').value = tds[4].innerText.trim();

                    // Dummy data as requested
                    if (document.getElementById('editNoTelp')) document.getElementById('editNoTelp').value = '0812' + Math.floor(10000000 + Math.random() * 90000000);
                    if (document.getElementById('editDosen')) document.getElementById('editDosen').value = 'Dr. Ahmad Fauzi, S.Kom., M.T.';
                }

                editModal.classList.add('active');
            }
        });
    });

    const closeEditModal = () => {
        if (editModal) editModal.classList.remove('active');
        currentRowToEdit = null;
    };

    if (closeEditBtn) closeEditBtn.addEventListener('click', closeEditModal);
    if (cancelEditBtn) cancelEditBtn.addEventListener('click', closeEditModal);

    if (saveEditBtn) {
        saveEditBtn.addEventListener('click', function () {
            closeEditModal();
            // Show success message
            setTimeout(() => {
                alert('Perubahan data berhasil disimpan!');
            }, 300);
        });
    }

    btnHapusData.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (confirm('Apakah Anda yakin ingin menghapus data mahasiswa ini?')) {
                const tr = this.closest('tr');
                tr.classList.add('fade-out');
                setTimeout(() => {
                    tr.remove();
                }, 400);
            }
        });
    });

    /* 
    ===============================================
        Dashboard Stats Filter & Animation
    =============================================== 
    */
    const dashboardFilter = document.getElementById('dashboardPeriodFilter');
    const elVerifikasi = document.getElementById('countVerifikasi');
    const elDisetujui = document.getElementById('countDisetujui');
    const elDitolak = document.getElementById('countDitolak');
    const elMahasiswa = document.getElementById('countMahasiswa');

    const animateValue = (obj, start, end, duration) => {
        if (!obj) return;
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            // easeOutQuad animation
            const easeProgress = 1 - (1 - progress) * (1 - progress);
            obj.innerHTML = Math.floor(easeProgress * (end - start) + start);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                obj.innerHTML = end;
            }
        };
        window.requestAnimationFrame(step);
    };

    if (dashboardFilter && elVerifikasi && elDisetujui && elDitolak && elMahasiswa) {

        // Use actual data rendered by PHP for 'semua'
        const realVerifikasi = parseInt(elVerifikasi.innerText) || 0;
        const realDisetujui = parseInt(elDisetujui.innerText) || 0;
        const realDitolak = parseInt(elDitolak.innerText) || 0;
        const realMahasiswa = parseInt(elMahasiswa.innerText) || 0;

        const statsData = {
            'semua': { verifikasi: realVerifikasi, disetujui: realDisetujui, ditolak: realDitolak, mahasiswa: realMahasiswa },
            'bulan_ini': { verifikasi: Math.floor(realVerifikasi / 2), disetujui: Math.floor(realDisetujui / 2), ditolak: 0, mahasiswa: realMahasiswa },
            'tahun_ini': { verifikasi: Math.floor(realVerifikasi * 0.8), disetujui: Math.floor(realDisetujui * 0.8), ditolak: Math.floor(realDitolak * 0.8), mahasiswa: realMahasiswa }
        };

        dashboardFilter.addEventListener('change', function () {
            const val = this.value;
            const data = statsData[val] || statsData['semua'];

            // Current values to start animation from
            const startVer = parseInt(elVerifikasi.innerText) || 0;
            const startDis = parseInt(elDisetujui.innerText) || 0;
            const startDit = parseInt(elDitolak.innerText) || 0;
            const startMhs = parseInt(elMahasiswa.innerText) || 0;

            animateValue(elVerifikasi, startVer, data.verifikasi, 1000);
            animateValue(elDisetujui, startDis, data.disetujui, 1000);
            animateValue(elDitolak, startDit, data.ditolak, 1000);
            animateValue(elMahasiswa, startMhs, data.mahasiswa, 1000);
        });

        // Trigger initial animation on load
        elVerifikasi.innerText = "0";
        elDisetujui.innerText = "0";
        elDitolak.innerText = "0";
        elMahasiswa.innerText = "0";

        animateValue(elVerifikasi, 0, realVerifikasi, 1200);
        animateValue(elDisetujui, 0, realDisetujui, 1200);
        animateValue(elDitolak, 0, realDitolak, 1200);
        animateValue(elMahasiswa, 0, realMahasiswa, 1200);
    }

    /* 
    ===============================================
        Kriteria Poin Actions (Tambah, Edit, Hapus)
    =============================================== 
    */
    const kriteriaModal = document.getElementById('kriteriaModal');
    const closeKriteriaBtnIcon = document.querySelector('.close-modal-kriteria');
    const cancelKriteriaBtn = document.getElementById('cancelKriteriaBtn');
    const saveKriteriaBtn = document.getElementById('saveKriteriaBtn');

    // Function to close modal
    const closeKriteriaModal = () => {
        if (kriteriaModal) kriteriaModal.classList.remove('active');
    };

    if (closeKriteriaBtnIcon) closeKriteriaBtnIcon.addEventListener('click', closeKriteriaModal);
    if (cancelKriteriaBtn) cancelKriteriaBtn.addEventListener('click', closeKriteriaModal);

    // Tambah Aturan Poin Config
    const btnTambahPoinConfig = document.getElementById('btnTambahPoinConfig');
    if (btnTambahPoinConfig) {
        btnTambahPoinConfig.addEventListener('click', function (e) {
            e.preventDefault();
            if (kriteriaModal) {
                document.getElementById('modalKriteriaTitle').innerText = 'Tambah Aturan Poin';
                document.getElementById('kriteriaId').value = '';
                document.getElementById('kriteriaTingkat').value = 'Nasional';
                document.getElementById('kriteriaJuara').value = 'Juara 1';
                document.getElementById('kriteriaPoin').value = '';

                kriteriaModal.classList.add('active');
            }
        });
    }

    // Bind Edit Buttons dynamically
    const bindEditButtons = () => {
        document.querySelectorAll('.btn-edit-poin').forEach(btn => {
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            newBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (kriteriaModal) {
                    const tr = this.closest('tr');

                    document.getElementById('modalKriteriaTitle').innerText = 'Edit Aturan Poin';
                    document.getElementById('kriteriaId').value = tr.getAttribute('data-id');
                    document.getElementById('kriteriaTingkat').value = tr.getAttribute('data-tingkat');
                    document.getElementById('kriteriaJuara').value = tr.getAttribute('data-juara');
                    document.getElementById('kriteriaPoin').value = tr.getAttribute('data-poin');

                    kriteriaModal.classList.add('active');
                }
            });
        });
    };

    // Bind Hapus Buttons dynamically
    const bindHapusButtons = () => {
        document.querySelectorAll('.btn-hapus-poin').forEach(btn => {
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            newBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (confirm('Apakah Anda yakin ingin menghapus konfigurasi poin ini?')) {
                    const tr = this.closest('tr');
                    const id = tr.getAttribute('data-id');

                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    fetch('ApiKriteria.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                tr.style.opacity = '0';
                                tr.style.transition = 'opacity 0.3s';
                                setTimeout(() => {
                                    tr.remove();
                                }, 300);
                            } else {
                                alert("Gagal menghapus: " + data.message);
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert("Terjadi kesalahan.");
                        });
                }
            });
        });
    };

    bindEditButtons();
    bindHapusButtons();

    if (saveKriteriaBtn) {
        saveKriteriaBtn.addEventListener('click', function () {
            const id = document.getElementById('kriteriaId').value;
            const tingkat = document.getElementById('kriteriaTingkat').value;
            const juara = document.getElementById('kriteriaJuara').value;
            const poin = document.getElementById('kriteriaPoin').value;

            if (!tingkat || !juara || !poin) {
                alert("Harap isi semua kolom!");
                return;
            }

            const formData = new FormData();
            formData.append('tingkat', tingkat);
            formData.append('juara', juara);
            formData.append('poin', poin);

            if (id) {
                formData.append('action', 'edit');
                formData.append('id', id);

                fetch('ApiKriteria.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const tr = document.querySelector(`tr[data-id="${id}"]`);
                            if (tr) {
                                tr.setAttribute('data-tingkat', tingkat);
                                tr.setAttribute('data-juara', juara);
                                tr.setAttribute('data-poin', poin);

                                tr.cells[1].innerHTML = `<span class="badge badge-blue">${tingkat}</span>`;
                                tr.cells[2].innerHTML = `<i class="fa-solid fa-medal text-gold" style="margin-right: 5px;"></i> ${juara}`;
                                tr.cells[3].innerHTML = `<strong>${poin}</strong>`;
                            }
                            closeKriteriaModal();
                            setTimeout(() => alert('Konfigurasi poin berhasil diubah!'), 300);
                        } else {
                            alert("Gagal mengubah: " + data.message);
                        }
                    });
            } else {
                formData.append('action', 'add');

                fetch('ApiKriteria.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const newId = data.id;
                            const tbody = document.getElementById('tbody-poin-config');
                            const count = tbody.querySelectorAll('tr').length + 1;

                            const tr = document.createElement('tr');
                            tr.setAttribute('data-id', newId);
                            tr.setAttribute('data-tingkat', tingkat);
                            tr.setAttribute('data-juara', juara);
                            tr.setAttribute('data-poin', poin);

                            tr.innerHTML = `
                            <td>${count}</td>
                            <td><span class="badge badge-blue">${tingkat}</span></td>
                            <td><i class="fa-solid fa-medal text-gold" style="margin-right: 5px;"></i> ${juara}</td>
                            <td class="text-center"><strong>${poin}</strong></td>
                            <td class="text-center">
                                <button class="action-icon text-blue btn-edit-poin" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                <button class="action-icon text-red btn-hapus-poin" title="Hapus"><i class="fa-regular fa-trash-can"></i></button>
                            </td>
                        `;
                            tbody.appendChild(tr);
                            bindEditButtons();
                            bindHapusButtons();

                            closeKriteriaModal();
                            setTimeout(() => alert('Konfigurasi poin berhasil ditambahkan!'), 300);
                        } else {
                            alert("Gagal menambah: " + data.message);
                        }
                    });
            }
        });
    }

    const btnSimpanPerubahan = document.getElementById('btnSimpanPerubahan');
    if (btnSimpanPerubahan) {
        btnSimpanPerubahan.addEventListener('click', function () {
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-check"></i> Tersimpan';
            this.style.backgroundColor = '#10b981';
            this.style.borderColor = '#10b981';
            this.style.color = '#fff';

            setTimeout(() => {
                this.innerHTML = originalHtml;
                this.style.backgroundColor = '';
                this.style.borderColor = '';
                this.style.color = '';
            }, 2000);
        });
    }

    // Data Mahasiswa Actions
    const editMahasiswaModal = document.getElementById('editMahasiswaModal');
    const closeEditMhsBtn = document.querySelector('.close-modal-edit');
    const cancelEditMhsBtn = document.getElementById('cancelEditBtn');
    const saveEditMhsBtn = document.getElementById('saveEditBtn');

    const closeMhsModal = () => {
        if (editMahasiswaModal) editMahasiswaModal.classList.remove('active');
    };

    if (closeEditMhsBtn) closeEditMhsBtn.addEventListener('click', closeMhsModal);
    if (cancelEditMhsBtn) cancelEditMhsBtn.addEventListener('click', closeMhsModal);

    document.querySelectorAll('.btn-edit-data, .btn-lihat-data').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (editMahasiswaModal) {
                const tr = this.closest('tr');
                const tds = tr.querySelectorAll('td');
                if (tds.length >= 5) {
                    document.getElementById('editNim').value = tds[0].innerText.trim();
                    document.getElementById('editNama').value = tds[1].innerText.trim();
                    document.getElementById('editProdi').value = tds[2].innerText.trim();
                    document.getElementById('editAngkatan').value = tds[3].innerText.trim();
                    document.getElementById('editEmail').value = tds[4].innerText.trim();

                    // Dummy data as requested
                    if (document.getElementById('editNoTelp')) document.getElementById('editNoTelp').value = '0812' + Math.floor(10000000 + Math.random() * 90000000);
                    if (document.getElementById('editDosen')) document.getElementById('editDosen').value = 'Dr. Ahmad Fauzi, S.Kom., M.T.';

                    editMahasiswaModal.classList.add('active');
                }
            }
        });
    });

    document.querySelectorAll('.btn-hapus-data').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (confirm('Apakah Anda yakin ingin menghapus mahasiswa ini?')) {
                const tr = this.closest('tr');
                const nim = tr.querySelectorAll('td')[0].innerText.trim();

                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('nim', nim);

                fetch('ApiMahasiswa.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            tr.style.opacity = '0';
                            tr.style.transition = 'opacity 0.3s';
                            setTimeout(() => {
                                tr.remove();
                            }, 300);
                        } else {
                            alert('Gagal menghapus: ' + data.message);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Terjadi kesalahan.');
                    });
            }
        });
    });

    if (saveEditMhsBtn) {
        saveEditMhsBtn.addEventListener('click', function () {
            const nim = document.getElementById('editNim').value;
            const nama = document.getElementById('editNama').value;
            const prodi = document.getElementById('editProdi').value;
            const angkatan = document.getElementById('editAngkatan').value;
            const email = document.getElementById('editEmail').value;
            const telp = document.getElementById('editNoTelp') ? document.getElementById('editNoTelp').value : '';

            const formData = new FormData();
            formData.append('action', 'edit');
            formData.append('nim', nim);
            formData.append('nama', nama);
            formData.append('prodi', prodi);
            formData.append('angkatan', angkatan);
            formData.append('email', email);
            formData.append('telp', telp);

            fetch('ApiMahasiswa.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeMhsModal();
                        alert('Data mahasiswa berhasil diperbarui!');
                        location.reload();
                    } else {
                        alert('Gagal memperbarui: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan.');
                });
        });
    }

    // Removed simulator init

});

/* Global Functions for API Calls */
window.approvePrestasi = function (id) {
    if (!confirm("Anda yakin menyetujui prestasi ini?")) return;

    const formData = new FormData();
    formData.append('action', 'approve');
    formData.append('id', id);

    fetch('ApiVerifikasi.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message + " Total poin: " + data.poin);
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Terjadi kesalahan sistem.");
        });
};

let currentRejectId = null;

window.rejectPrestasi = function (id) {
    currentRejectId = id;
    const rejectModal = document.getElementById('rejectModal');
    const rejectReasonInput = document.getElementById('rejectReason');

    if (rejectModal && rejectReasonInput) {
        rejectReasonInput.value = ''; // Reset input
        rejectModal.classList.add('active');
    }
};

window.rejectPrestasi = function (id) {
    currentRejectId = id;
    const rejectModal = document.getElementById('rejectModal');
    const rejectReasonInput = document.getElementById('rejectReason');

    if (rejectModal && rejectReasonInput) {
        rejectReasonInput.value = ''; // Reset input
        rejectModal.classList.add('active'); // Munculin modal
    } else {
        console.error("HTML Modal Tolak belum ditambahin di VerifikasiPrestasi.php!");
    }
};

// Tutup Modal Tolak (Buat tombol Batal / X)
window.closeRejectModal = function () {
    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) rejectModal.classList.remove('active');
    currentRejectId = null;
};

// Kirim Data Penolakan
window.submitReject = function () {
    const alasan = document.getElementById('rejectReason').value;

    if (alasan.trim() === '') {
        alert('Alasan penolakan tidak boleh kosong!');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'reject');
    formData.append('id', currentRejectId);
    formData.append('alasan', alasan.trim());

    fetch('ApiVerifikasi.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); // Refresh biar status berubah
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Terjadi kesalahan sistem saat menolak.");
        });
};

document.addEventListener('DOMContentLoaded', () => {
    // Other DOM elements...

    // Reject Modal Handlers
    const rejectModal = document.getElementById('rejectModal');
    const cancelRejectBtn = document.getElementById('cancelReject');
    const submitRejectBtn = document.getElementById('submitReject');
    const closeRejectModalBtn = rejectModal ? rejectModal.querySelector('.close-modal') : null;
    const rejectReasonInput = document.getElementById('rejectReason');

    const closeRejectModal = () => {
        if (rejectModal) {
            rejectModal.classList.remove('active');
            currentRejectId = null;
        }
    };

    if (cancelRejectBtn) cancelRejectBtn.addEventListener('click', closeRejectModal);
    if (closeRejectModalBtn) closeRejectModalBtn.addEventListener('click', closeRejectModal);

    if (submitRejectBtn) {
        submitRejectBtn.addEventListener('click', () => {
            if (!currentRejectId) return;

            const reason = rejectReasonInput ? rejectReasonInput.value.trim() : '';
            if (reason === '') {
                alert("Alasan penolakan tidak boleh kosong.");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'reject');
            formData.append('id', currentRejectId);
            formData.append('alasan', reason);

            fetch('ApiVerifikasi.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Terjadi kesalahan sistem.");
                });

            closeRejectModal();
        });
    }
});

window.viewDetail = function (rowJson) {
    const detailModal = document.getElementById('detailModal');
    if (detailModal) {
        detailModal.classList.add('active');

        // Setup close button
        const closeDetailBtnIcon = document.querySelector('.close-modal-detail');
        const closeDetailBtn = document.getElementById('closeDetailBtn');
        const closeDetailModal = () => detailModal.classList.remove('active');
        if (closeDetailBtnIcon) closeDetailBtnIcon.onclick = closeDetailModal;
        if (closeDetailBtn) closeDetailBtn.onclick = closeDetailModal;

        // Setup Approve & Reject buttons in Detail Modal
        const approveFromDetailBtn = document.getElementById('approveFromDetail');
        const rejectFromDetailBtn = document.getElementById('rejectFromDetail');

        if (approveFromDetailBtn) {
            if (rowJson.status === 'pending') {
                approveFromDetailBtn.style.display = 'inline-block';
                approveFromDetailBtn.onclick = () => {
                    closeDetailModal();
                    approvePrestasi(rowJson.id);
                };
            } else {
                approveFromDetailBtn.style.display = 'none';
            }
        }
        if (rejectFromDetailBtn) {
            if (rowJson.status === 'pending') {
                rejectFromDetailBtn.style.display = 'inline-block';
                rejectFromDetailBtn.onclick = () => {
                    closeDetailModal();
                    rejectPrestasi(rowJson.id);
                };
            } else {
                rejectFromDetailBtn.style.display = 'none';
            }
        }

        // Populating the data inside the detail modal
        const mdlMahasiswa = document.getElementById('mdlMahasiswa');
        const mdlPrestasi = document.getElementById('mdlPrestasi');
        const mdlKategori = document.getElementById('mdlKategori');
        const mdlSubkategori = document.getElementById('mdlSubkategori');
        const mdlTingkat = document.getElementById('mdlTingkat');
        const mdlPeringkat = document.getElementById('mdlPeringkat');
        const mdlTahun = document.getElementById('mdlTahun');
        const mdlTanggal = document.getElementById('mdlTanggal');
        const mdlPenyelenggara = document.getElementById('mdlPenyelenggara');
        const mdlDeskripsi = document.getElementById('mdlDeskripsi');
        const mdlStatus = document.getElementById('mdlStatus');
        const mdlAlasanLabel = document.getElementById('mdlAlasanLabel');
        const mdlAlasan = document.getElementById('mdlAlasan');
        const mdlPoin = document.getElementById('mdlPoin');

        if (mdlMahasiswa) mdlMahasiswa.innerHTML = `<strong>${rowJson.nama}</strong><br><span class="text-muted">NIM: ${rowJson.nim}</span>`;
        if (mdlPrestasi) mdlPrestasi.innerText = rowJson.judul || '-';
        if (mdlKategori) mdlKategori.innerText = rowJson.kategori_utama || '-';
        if (mdlSubkategori) mdlSubkategori.innerText = rowJson.subkategori_utama || '-';
        if (mdlTingkat) mdlTingkat.innerText = rowJson.tingkat || '-';
        if (mdlPeringkat) mdlPeringkat.innerText = rowJson.juara || '-';
        if (mdlTahun) mdlTahun.innerText = rowJson.tahun || '-';
        if (mdlTanggal) mdlTanggal.innerText = '-'; // Not yet available
        if (mdlPenyelenggara) mdlPenyelenggara.innerText = '-'; // Not yet available
        if (mdlDeskripsi) mdlDeskripsi.innerText = rowJson.deskripsi || '-';

        if (mdlStatus) {
            if (rowJson.status === 'approved') {
                mdlStatus.innerHTML = '<span class="status-badge status-disetujui">Disetujui</span>';
            } else if (rowJson.status === 'rejected') {
                mdlStatus.innerHTML = '<span class="status-badge status-ditolak">Ditolak</span>';
            } else {
                mdlStatus.innerHTML = '<span class="status-badge status-menunggu">Menunggu</span>';
            }
        }

        if (mdlAlasanLabel && mdlAlasan) {
            if (rowJson.status === 'rejected') {
                mdlAlasanLabel.style.display = 'block';
                mdlAlasan.style.display = 'block';
                mdlAlasan.innerHTML = `<span style="color: #ef4444; font-weight: 500;">${rowJson.alasan_penolakan || '-'}</span>`;
            } else {
                mdlAlasanLabel.style.display = 'none';
                mdlAlasan.style.display = 'none';
            }
        }

        // Set mapped poin otomatis
        let pointDisplay = "0";
        if (rowJson.poin_otomatis !== null && rowJson.poin_otomatis !== undefined) {
            pointDisplay = rowJson.poin_otomatis; // Always show point from config regardless of status
        }

        if (mdlPoin) {
            mdlPoin.innerHTML = `
                <div class="poin-highlight">${pointDisplay} poin</div>
                <div class="poin-calculation">Sesuai mapping konfigurasi poin</div>
            `;
        }

        const mdlSertifikatArea = document.getElementById('mdlSertifikatArea');
        const mdlPendukungArea = document.getElementById('mdlPendukungArea');

        const renderFileCard = (filename) => {
            if (!filename) return '';
            const ext = filename.split('.').pop().toLowerCase();
            let iconClass = 'fa-file';
            let iconColor = '#64748b';

            if (['pdf'].includes(ext)) { iconClass = 'fa-file-pdf'; iconColor = '#ef4444'; }
            else if (['jpg', 'jpeg', 'png'].includes(ext)) { iconClass = 'fa-file-image'; iconColor = '#10b981'; }
            else if (['doc', 'docx'].includes(ext)) { iconClass = 'fa-file-word'; iconColor = '#3b82f6'; }

            return `
            <div class="file-attachment" style="min-width: 250px; flex: 1;">
                <div class="file-icon" style="color: ${iconColor}; background: ${iconColor}1a;">
                    <i class="fa-solid ${iconClass}"></i>
                    <span style="color: ${iconColor};">${ext.toUpperCase()}</span>
                </div>
                <div class="file-info">
                    <span class="file-name" title="${filename}">${filename.length > 25 ? filename.substring(0, 25) + '...' : filename}</span>
                    <span class="file-size">Dokumen</span>
                </div>
                <a href="Assets/Uploads/${filename}?v=${Date.now()}" target="_blank" class="file-link">Buka <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            </div>`;
        };

        if (mdlSertifikatArea) {
            const fileUtama = rowJson.bukti_file || 'sertifikat_hackathon_2026.pdf';
            mdlSertifikatArea.innerHTML = renderFileCard(fileUtama);
        }

        if (mdlPendukungArea) {
            if (rowJson.bukti_pendukung) {
                try {
                    let files = JSON.parse(rowJson.bukti_pendukung);
                    if (!Array.isArray(files)) files = [rowJson.bukti_pendukung];
                    if (files.length > 0) {
                        mdlPendukungArea.innerHTML = files.map(f => renderFileCard(f)).join('');
                    } else {
                        mdlPendukungArea.innerHTML = '<span class="text-muted" style="font-size: 13px; font-style: italic;">Tidak ada file pendukung tambahan.</span>';
                    }
                } catch (e) {
                    mdlPendukungArea.innerHTML = renderFileCard(rowJson.bukti_pendukung);
                }
            } else {
                mdlPendukungArea.innerHTML = '<span class="text-muted" style="font-size: 13px; font-style: italic;">Tidak ada file pendukung tambahan.</span>';
            }
        }
    } else {
        alert("Data Prestasi: \n" + JSON.stringify(rowJson, null, 2));
    }
};

// Global Dropdown Logic for Topbar
document.addEventListener("DOMContentLoaded", function () {
    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');

    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });

        document.addEventListener('click', function () {
            profileDropdown.classList.remove('show');
        });
    }
});

// Initialize Dashboard Charts if they exist
document.addEventListener("DOMContentLoaded", function () {
    // Check if chartData is defined from PHP
    if (typeof chartData !== 'undefined') {

        // 1. Line Chart
        const lineCanvas = document.getElementById('lineChart');
        if (lineCanvas) {
            new Chart(lineCanvas, {
                type: 'line',
                data: {
                    labels: chartData.months,
                    datasets: [{
                        label: 'Jumlah Prestasi',
                        data: chartData.counts,
                        borderColor: '#2563eb', // Blue
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#2563eb',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: '#94a3b8', padding: 10 },
                            grid: { color: '#f1f5f9', drawBorder: false }
                        },
                        x: {
                            ticks: { color: '#94a3b8', padding: 10 },
                            grid: { display: false, drawBorder: false }
                        }
                    }
                }
            });
        }

        // 2. Donut Chart
        const donutCanvas = document.getElementById('donutChart');
        if (donutCanvas) {
            new Chart(donutCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Akademik', 'Non-Akademik'],
                    datasets: [{
                        data: [chartData.akademik, chartData.nonAkademik],
                        backgroundColor: ['#2563eb', '#16a34a'],
                        borderWidth: 0,
                        cutout: '75%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const value = context.raw;
                                    const pct = context.dataIndex === 0 ? chartData.pctAkademik : chartData.pctNonAkademik;
                                    return ` ${context.label}: ${value} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Custom Legend
            const legendEl = document.getElementById('donutLegend');
            if (legendEl) {
                legendEl.innerHTML = `
                    <div class="legend-item">
                        <div class="legend-color-label">
                            <div class="legend-color" style="background-color: #2563eb;"></div>
                            <span>Akademik</span>
                        </div>
                        <span>${chartData.akademik} (${chartData.pctAkademik}%)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color-label">
                            <div class="legend-color" style="background-color: #16a34a;"></div>
                            <span>Non-Akademik</span>
                        </div>
                        <span>${chartData.nonAkademik} (${chartData.pctNonAkademik}%)</span>
                    </div>
                `;
            }
        }
    }
});
