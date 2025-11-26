<?php
require_once '../config/dbconnect.php';
require_once '../models/auth.php';
checkAuth();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Barang - Sistem Inventory</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        #tableBarang tbody td a {
            color: #8be9fd;
            font-weight: 600;
            text-decoration: underline;
            text-decoration-style: dotted;
            text-underline-offset: 3px;
        }
        #tableBarang tbody td a:hover { color: #ffffff; }
    </style>
</head>
<body>
    <div class="dashboard-content">
        <!-- Header -->
        <header>
            <div class="header-content">
                <div class="header-left">
                    <div class="logo">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"></path><path d="M2 17l10 5 10-5"></path><path d="M2 12l10 5 10-5"></path></svg>
                    </div>
                    <div class="header-title">
                        <h1>Sistem Manajemen Inventory</h1>
                        <p>Daftar Barang</p>
                    </div>
                </div>
                <div class="header-actions" style="display: flex; gap: 1rem;">
                    <?php if ($_SESSION['role_id'] == 2): ?>
                        <a href="dashboard_user.php" class="btn btn-secondary"><span>⬅️</span> Kembali ke Dashboard</a>
                    <?php else: ?>
                        <a href="datamaster.php" class="btn btn-secondary"><span>⬅️</span> Kembali ke Data Master</a>
                    <?php endif; ?>
                    <a href="../models/auth.php?action=logout" class="btn btn-danger"><span>🚪</span> Keluar</a>
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
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <tr><td colspan="7" style="text-align: center;">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- Footer -->
        <footer>
            <p>Sistem Manajemen Inventory PBD © 2025</p>
        </footer>
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
        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadBarang();
        });

        async function loadStats() {
            try {
                const response = await fetch('../models/barang.php?action=get_stats');
                const result = await response.json();
                if (result.success) {
                    document.getElementById('totalBarang').textContent = result.data.total_barang;
                    document.getElementById('totalStok').textContent = result.data.total_stok || 0;
                    document.getElementById('totalNilai').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(result.data.total_nilai || 0);
                }
            } catch (error) { console.error('Error loading stats:', error); }
        }

        async function loadBarang() {
            const url = `../models/barang.php?filter=semua`;
            try {
                const response = await fetch(url);
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
                        </tr>
                    `).join('');
               } else {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Tidak ada data</td></tr>';
                }
            } catch (error) { console.error('Error loading barang:', error); }
        }

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
                        let jenisTransaksi = log.jenis_transaksi === 'M' ? `Penerimaan (ID: ${log.id_transaksi})` : `Penjualan (ID: ${log.id_transaksi})`;
                        return `
                            <tr>
                                <td>${tanggal}</td>
                                <td>${jenisTransaksi}</td>
                                <td style="text-align: right; color: #50fa7b;">${log.masuk > 0 ? `+${log.masuk}` : '0'}</td>
                                <td style="text-align: right; color: #f5576c;">${log.keluar > 0 ? `-${log.keluar}` : '0'}</td>
                                <td style="text-align: right; font-weight: bold;">${log.stok}</td>
                            </tr>`;
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

        document.getElementById('btnRefresh').addEventListener('click', () => {
            loadBarang();
            loadStats();
        });

        window.onclick = function(event) {
            const stockCardModal = document.getElementById('modalStockCard');
            if (event.target === stockCardModal) {
                closeStockCardModal();
            }
        }
    </script>
</body>
</html>