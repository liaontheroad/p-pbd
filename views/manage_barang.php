<?php
require_once '../config/dbconnect.php'; // Corrected path
require_once '../models/auth.php'; // Corrected path to auth.php model
checkAuth();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Inventory PBD</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Make the clickable stock number more visible */
        #tableBarang tbody td a {
            color: #8be9fd; /* Bright cyan for high visibility */
            font-weight: 600;
            text-decoration: underline;
            text-decoration-style: dotted;
            text-underline-offset: 3px;
        }
        #tableBarang tbody td a:hover {
            color: #ffffff; /* White on hover */
        }
    </style>
</head>
<body>
    <div class="dashboard-content">
        <!-- Header -->
        <header>
            <div class="header-content">
                <div class="header-left">
                    <div class="logo">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                        </svg>
                    </div>
                    <div class="header-title">
                        <h1>Sistem Manajemen Inventory</h1>
                        <p>Database PBD - Manajemen Barang</p>
                    </div>
                </div>
                <div class="header-actions" style="display: flex; gap: 1rem;">
                    <a href="datamaster.php" class="btn btn-secondary">
                        <span>⚙️</span> Data Master
                    </a>
                    <button id="btnTambah" class="btn btn-primary">
                        <span>+</span> Tambah Barang
                    </button>
                    <a href="../models/auth.php?action=logout" class="btn btn-danger">
                        <span>🚪</span> Keluar
                    </a>
                </div>
            </div>
        </header>

        <div class="container">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Barang</h3>
                    <div class="value" id="totalBarang">0</div>
                </div>
                <div class="stat-card">
                    <h3>Total Stok</h3>
                    <div class="value" id="totalStok">0</div>
                </div>
                <div class="stat-card">
                    <h3>Nilai Inventory</h3>
                    <div class="value" id="totalNilai">Rp 0</div>
                </div>
            </div>

            <!-- Barang Table -->
            <div class="card">
                <div class="card-header">
                    <h2>Daftar Barang</h2>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <button id="btnFilterAktif" class="btn btn-secondary btn-sm" data-filter="aktif">Tampilkan Semua</button>
                        <button id="btnRefresh" class="btn btn-secondary btn-sm">🔄 Refresh</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableBarang">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Barang</th>
                                    <th>Satuan</th>
                                    <th>Jenis</th>
                                    <th>Harga Pokok</th>
                                    <th>Stok</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <tr>
                                    <td colspan="8" style="text-align: center;">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- Footer -->
        <footer>
            <p>Sistem Manajemen Inventory PBD © 2025</p>
            <p>Dibuat dengan HTML, CSS, PHP & MySQL</p>
        </footer>
    </div>

    <!-- Modal Form -->
    <div id="modalForm" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Barang</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <form id="formBarang">
                <input type="hidden" id="idbarang" name="idbarang">
                <input type="hidden" id="formMethod" name="_method">
                
                <div id="kodeBarangDisplay" class="form-group" style="display: none;">
                    <label>Kode Barang</label>
                    <input type="text" id="kode_barang" name="kode_barang" readonly>
                </div>
                <div class="form-group">
                    <div class="form-group">
                        <label for="nama_barang">Nama Barang *</label>
                        <input type="text" id="nama_barang" name="nama_barang" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="idsatuan">Satuan *</label>
                        <select id="idsatuan" name="idsatuan" required>
                            <option value="">Pilih Satuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jenis_barang">Jenis Barang</label>
                        <select id="jenis_barang" name="jenis_barang">
                            <option value="">Pilih Jenis</option>
                            <option value="m">Makanan / Minuman (Konsumsi)</option>
                            <option value="p">Perawatan Diri / Personal Care</option>
                            <option value="k">Kebutuhan Dapur</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="harga_pokok">Harga Pokok *</label>
                        <input type="number" id="harga_pokok" name="harga_pokok" required step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="stok">Stok *</label>
                        <input type="number" id="stok" name="stok" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="aktif">Aktif</option>
                        <option value="tidak_aktif">Tidak Aktif</option>
                    </select>
                </div>
                
                <div class="form-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Stock Card -->
    <div id="modalStockCard" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 id="stockCardModalTitle">Kartu Stok Barang</h3>
                <button class="close" onclick="closeStockCardModal()">&times;</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tableStockCard">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis Transaksi</th>
                                <th>Masuk</th>
                                <th>Keluar</th>
                                <th>Stok Akhir</th>
                            </tr>
                        </thead>
                        <tbody id="stockCardTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load data on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadBarang();
            loadSatuan();
        });

        // Load statistics
        async function loadStats() {
            try {
                const response = await fetch('../models/barang.php?action=get_stats');
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('totalBarang').textContent = result.data.total_barang;
                    document.getElementById('totalStok').textContent = result.data.total_stok || 0;
                    document.getElementById('totalNilai').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(result.data.total_nilai || 0);
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Load barang list
        async function loadBarang() {
            const filter = document.getElementById('btnFilterAktif').dataset.filter;
            const url = `../models/barang.php?filter=${filter}`;

            try {
                const response = await fetch(url);

                // Menangani error otentikasi (sesi berakhir)
                if (response.status === 401) {
                    alert('Sesi Anda telah berakhir. Anda akan diarahkan ke halaman login.');
                    window.location.href = '../login.html';
                    return;
                }

               const result = await response.json();
                
                const tbody = document.getElementById('tableBody');
                
                if (result.success && result.data.length > 0) {
                    tbody.innerHTML = result.data.map(item => `
                        <tr>
                            <td>${item.kode_barang}</td>
                            <td>${item.nama_barang}</td>
                            <td>${item.nama_satuan || '-'}</td>
                            <td>${item.jenis_barang || '-'}</td>
                            <td>Rp ${new Intl.NumberFormat('id-ID').format(item.harga_pokok)}</td>
                            <td><a href="#" onclick="viewStockCard('${item.idbarang}', '${item.nama_barang}')" title="Lihat Kartu Stok">${item.stok}</a></td>
                            <td><span class="badge ${item.status === 'aktif' ? 'badge-success' : 'badge-danger'}">${item.status}</span></td>
                            <td class="action-buttons">
                                <button class="btn btn-primary btn-sm" onclick="editBarang('${item.idbarang}')">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteBarang('${item.idbarang}', '${item.nama_barang}')">Hapus</button>
                            </td>
                        </tr>
                    `).join('');
               } else {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Tidak ada data</td></tr>';
                }
            } catch (error) {
                console.error('Error loading barang:', error);
            }
        }

        // Toggle filter button
        document.getElementById('btnFilterAktif').addEventListener('click', function() {
            const currentFilter = this.dataset.filter;
            if (currentFilter === 'semua') {
                this.dataset.filter = 'aktif';
                this.textContent = 'Tampilkan Semua';
            } else {
                this.dataset.filter = 'semua';
                this.textContent = 'Tampilkan Aktif Saja';
            }
            loadBarang();
        });

        // Load satuan for dropdown
        async function loadSatuan() {
            try {
                const response = await fetch('../models/barang.php?action=get_satuan');
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById('idsatuan');
                    select.innerHTML = '<option value="">Pilih Satuan</option>' + 
                        result.data.map(item => `<option value="${item.idsatuan}">${item.nama_satuan}</option>`).join('');
                }
            } catch (error) {
                console.error('Error loading satuan:', error);
            }
        }

        // Show modal for add
        document.getElementById('btnTambah').addEventListener('click', () => {
            document.getElementById('modalTitle').textContent = 'Tambah Barang';
            document.getElementById('formBarang').reset();
            document.getElementById('idbarang').value = '';
            document.getElementById('formMethod').value = '';
            document.getElementById('kodeBarangDisplay').style.display = 'none'; // Sembunyikan kode barang saat tambah
            document.getElementById('modalForm').classList.add('show');
        });

        // Edit barang
        async function editBarang(id) {
            try {
                const response = await fetch(`../models/barang.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    document.getElementById('modalTitle').textContent = 'Edit Barang';
                    document.getElementById('idbarang').value = data.idbarang;
                    document.getElementById('formMethod').value = 'PUT';
                    document.getElementById('kode_barang').value = data.kode_barang;
                    document.getElementById('nama_barang').value = data.nama_barang;
                    document.getElementById('idsatuan').value = data.idsatuan;
                    document.getElementById('jenis_barang').value = data.jenis_barang;
                    document.getElementById('harga_pokok').value = data.harga_pokok;
                    document.getElementById('stok').value = data.stok || 0;
                    document.getElementById('status').value = data.status;
                    
                    // document.getElementById('kodeBarangDisplay').style.display = 'block'; // Sesuai permintaan, kode barang tidak ditampilkan saat edit
                    document.getElementById('modalForm').classList.add('show');
                }
            } catch (error) {
                alert('Error loading data: ' + error.message);
            }
        }

        // Delete barang
        async function deleteBarang(id, nama) {
            if (!confirm(`Hapus barang "${nama}"?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('_method', 'DELETE');
                formData.append('idbarang', id);
                
                const response = await fetch('../models/barang.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                alert(result.message);
                
                if (result.success) {
                    loadBarang();
                    loadStats();
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        // Submit form
        document.getElementById('formBarang').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
           try {
                const response = await fetch('../models/barang.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                alert(result.message);
                
                if (result.success) {
                    closeModal();
                    loadBarang();
                    loadStats();
                }
           } catch (error) {
                alert('Error: ' + error.message);
            }
        });

        // Close modal
        function closeModal() {
            document.getElementById('modalForm').classList.remove('show');
        }

        // --- Stock Card Modal Functions ---

        async function viewStockCard(idbarang, namaBarang) {
            document.getElementById('stockCardModalTitle').textContent = `Kartu Stok - ${namaBarang}`;
            const tableBody = document.getElementById('stockCardTableBody');
            tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Memuat histori stok...</td></tr>';
            document.getElementById('modalStockCard').classList.add('show');

            try {
                const response = await fetch(`../models/barang.php?action=get_stock_card&idbarang=${idbarang}`);
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    tableBody.innerHTML = result.data.map(log => {
                        const tanggal = new Date(log.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
                        let jenisTransaksi = '';
                        switch(log.jenis_transaksi) {
                            case 'M':
                                jenisTransaksi = `Penerimaan (ID: ${log.id_transaksi})`;
                                break;
                            case 'K':
                                jenisTransaksi = `Penjualan (ID: ${log.id_transaksi})`;
                                break;
                            default:
                                jenisTransaksi = 'Lainnya';
                        }
                        return `
                            <tr>
                                <td>${tanggal}</td>
                                <td>${jenisTransaksi}</td>
                                <td style="text-align: right; color: #50fa7b;">${log.masuk > 0 ? `+${log.masuk}` : '0'}</td>
                                <td style="text-align: right; color: #f5576c;">${log.keluar > 0 ? `-${log.keluar}` : '0'}</td>
                                <td style="text-align: right; font-weight: bold;">${log.stok}</td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Belum ada riwayat stok untuk barang ini.</td></tr>';
                }
            } catch (error) {
                console.error('Error loading stock card:', error);
                tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #f5576c;">Gagal memuat riwayat stok.</td></tr>';
            }
        }

        function closeStockCardModal() {
            document.getElementById('modalStockCard').classList.remove('show');
        }
        // Refresh button
        document.getElementById('btnRefresh').addEventListener('click', () => {
            loadBarang();
            loadStats();
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('modalForm');
            const stockCardModal = document.getElementById('modalStockCard');
            if (event.target === stockCardModal) {
                closeStockCardModal();
            } else if (event.target === modal) {
                closeModal();
            }
        }
    </script>

</body>
</html>