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
    <title>Manajemen Vendor - Sistem Inventory PBD</title>
    <link rel="stylesheet" href="../css/style.css">
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
                        <p>Manajemen Vendor</p>
                    </div>
                </div>
                <div class="header-actions" style="display: flex; gap: 1rem;">
                    <a href="datamaster.php" class="btn btn-secondary">
                        <span>⚙️</span> Data Master
                    </a>
                    <button id="btnTambah" class="btn btn-primary">
                        <span>+</span> Tambah Vendor
                    </button>
                    <a href="../models/auth.php?action=logout" class="btn btn-danger">
                        <span>🚪</span> Keluar
                    </a>
                </div>
            </div>
        </header>

        <div class="container">
            <!-- Vendor Table -->
            <div class="card">
                <div class="card-header">
                    <h2>Daftar Vendor</h2>
                    <button id="btnRefresh" class="btn btn-secondary btn-sm">🔄 Refresh</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableVendor">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Vendor</th>
                                    <th>Alamat</th>
                                    <th>Telepon</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <tr>
                                    <td colspan="6" style="text-align: center;">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer>
            <p>Sistem Manajemen Inventory PBD © 2025</p>
        </footer>
    </div>

    <!-- Modal Form -->
    <div id="modalForm" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Vendor</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <form id="formVendor">
                <input type="hidden" id="idvendor" name="idvendor">
                <input type="hidden" id="formMethod" name="_method">
                
                <div class="form-group">
                    <label for="nama_vendor">Nama Vendor *</label>
                    <input type="text" id="nama_vendor" name="nama_vendor" required>
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat</label>
                    <input type="text" id="alamat" name="alamat">
                </div>

                <div class="form-group">
                    <label for="telepon">Telepon</label>
                    <input type="text" id="telepon" name="telepon">
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </select>
                </div>
                
                <div class="form-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadVendor();
    });

    async function loadVendor() {
        try {
            const response = await fetch('../models/vendor.php');
            if (response.status === 401) {
                alert('Sesi Anda telah berakhir. Silakan login kembali.');
                window.location.href = '../login.html';
                return;
            }
            const result = await response.json();
            const tbody = document.getElementById('tableBody');
            
            if (result.success && result.data.length > 0) {
                tbody.innerHTML = result.data.map(item => `
                    <tr>
                        <td>${item.idvendor}</td>
                        <td>${item.nama_vendor}</td>
                        <td>${item.alamat || '-'}</td>
                        <td>${item.telepon || '-'}</td>
                        <td><span class="badge ${item.status == 1 ? 'badge-success' : 'badge-danger'}">${item.status_text}</span></td>
                        <td class="action-buttons">
                            <button class="btn btn-primary btn-sm" onclick="editVendor('${item.idvendor}')">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteVendor('${item.idvendor}', '${item.nama_vendor}')">Hapus</button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Tidak ada data vendor</td></tr>';
            }
        } catch (error) {
            console.error('Error loading vendor:', error);
            document.getElementById('tableBody').innerHTML = '<tr><td colspan="6" style="text-align: center;">Gagal memuat data</td></tr>';
        }
    }

    document.getElementById('btnTambah').addEventListener('click', () => {
        document.getElementById('modalTitle').textContent = 'Tambah Vendor';
        document.getElementById('formVendor').reset();
        document.getElementById('idvendor').value = '';
        document.getElementById('formMethod').value = '';
        document.getElementById('modalForm').classList.add('show');
    });

    async function editVendor(id) {
        try {
            const response = await fetch(`../models/vendor.php?id=${id}`);
            const result = await response.json();
            if (result.success) {
                const data = result.data;
                document.getElementById('modalTitle').textContent = 'Edit Vendor';
                document.getElementById('idvendor').value = data.idvendor;
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('nama_vendor').value = data.nama_vendor;
                document.getElementById('alamat').value = data.alamat;
                document.getElementById('telepon').value = data.telepon;
                document.getElementById('status').value = data.status_int; // Menggunakan status_int (1 atau 0)
                document.getElementById('modalForm').classList.add('show');
            }
        } catch (error) {
            alert('Error memuat data untuk edit: ' + error.message);
        }
    }

    async function deleteVendor(id, nama) {
        if (!confirm(`Yakin ingin menonaktifkan vendor "${nama}"?`)) return;
        
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        formData.append('idvendor', id);
        
        try {
            const response = await fetch('../models/vendor.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) loadVendor();
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    document.getElementById('formVendor').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const response = await fetch('../models/vendor.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
                closeModal();
                loadVendor();
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });

    function closeModal() {
        document.getElementById('modalForm').classList.remove('show');
    }

    document.getElementById('btnRefresh').addEventListener('click', loadVendor);

    window.onclick = function(event) {
        if (event.target === document.getElementById('modalForm')) {
            closeModal();
        }
    }
</script>
</body>
</html>