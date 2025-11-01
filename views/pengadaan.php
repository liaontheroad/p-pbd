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
    <title>Pengadaan Barang (PO) - Sistem Inventory</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .transaction-form .card-body { padding: 0; }
        .form-header, .form-footer { padding: 28px; }
        .form-header { border-bottom: 1px solid #2a3142; }
        .form-footer { border-top: 1px solid #2a3142; }
        #item-list-table th, #item-list-table td { padding: 16px 28px; }
        #item-list-table input { background: #0f1419; border-color: #3a4254; padding: 8px; text-align: right; }
        .search-container { position: relative; }
        #search-results {
            position: absolute; top: 100%; left: 0; right: 0; background: #2a3142;
            border: 1px solid #3a4254; border-radius: 0 0 10px 10px; z-index: 10;
            max-height: 250px; overflow-y: auto;
        }
        .search-item { padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #3a4254; }
        .search-item:last-child { border-bottom: none; }
        .search-item:hover { background: #323948; }
        .search-item small { color: #8b92a7; }
        .total-section { text-align: right; font-size: 1.5rem; font-weight: 700; }
        .form-section { margin-bottom: 30px; }
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
                        <p>Manajemen Pengadaan Barang (Purchase Order)</p>
                    </div>
                </div>
                <div class="header-actions" style="display: flex; gap: 1rem;">
                    <a href="datamaster.php" class="btn btn-secondary"><span>⚙️</span> Menu Utama</a>
                    <a href="../models/auth.php?action=logout" class="btn btn-danger"><span>🚪</span> Keluar</a>
                </div>
            </div>
        </header>

        <div class="container">
            <!-- Area Input/Edit -->
            <div class="card transaction-form form-section">
                <form id="formPengadaan">
                    <input type="hidden" id="idpengadaan" name="idpengadaan">
                    <input type="hidden" id="formMethod" name="_method">

                    <div class="form-header">
                        <h2 id="formTitle" style="margin-bottom: 20px;">Buat Pengadaan Baru</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="idvendor">Vendor *</label>
                                <select id="idvendor" name="idvendor" required>
                                    <option value="">Memuat vendor...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="iduser">Dibuat Oleh *</label>
                                <select id="iduser" name="iduser" required>
                                    <option value="">Memuat user...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="tanggal">Tanggal Pengadaan *</label>
                                <input type="date" id="tanggal" name="tanggal" required>
                            </div>
                        </div>
                        <div class="form-row" style="align-items: flex-end; gap: 16px;">
                            <div class="form-group" style="flex-grow: 3;">
                                <label for="select-barang">Pilih Barang untuk Ditambahkan</label>
                                <select id="select-barang">
                                    <option value="">Memuat barang...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="jumlah-barang">Jumlah</label>
                                <input type="number" id="jumlah-barang" value="1" min="1" style="text-align: center;">
                            </div>
                            <div class="form-group" style="align-self: flex-end;">
                                <button type="button" id="btn-tambah-barang" class="btn btn-secondary">Tambah ke Daftar</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="item-list-table">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th width="15%">Jumlah</th>
                                    <th width="20%">Harga Beli</th>
                                    <th width="20%">Subtotal</th>
                                    <th width="5%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="item-list-body"></tbody>
                        </table>
                    </div>

                    <div class="form-footer">
                        <div class="total-section">
                            <span>Total: </span>
                            <span id="grand-total">Rp 0</span>
                        </div>
                        <div class="form-footer" style="padding: 28px 0 0 0; border-top: none; display:flex; gap:1rem;">
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Pengadaan</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Area Tampilan Data -->
            <div class="card">
                <div class="card-header">
                    <h2>Daftar Pengadaan</h2>
                    <button id="btnRefresh" class="btn btn-secondary btn-sm">🔄 Refresh</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablePengadaan">
                            <thead>
                                <tr>
                                    <th>ID PO</th>
                                    <th>Tanggal</th>
                                    <th>Vendor</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Total Nilai</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <tr><td colspan="6" style="text-align: center;">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
const API_URL = '../models/pengadaan.php';

document.addEventListener('DOMContentLoaded', () => {
    loadInitialData();
    document.getElementById('tanggal').valueAsDate = new Date();
});

const formatRupiah = (number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number || 0);

async function loadInitialData() {
    await Promise.all([
        loadVendors(),
        loadUsers(),
        loadBarangDropdown(),
        loadPengadaanList()
    ]);
}

async function fetchData(params = '') {
    try {
        const response = await fetch(`${API_URL}${params}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        alert('Gagal memuat data: ' + error.message);
        return { success: false, data: [] };
    }
}

async function loadVendors() {
    const result = await fetchData('?action=get_vendors');
    if (result.success) {
        const select = document.getElementById('idvendor');
        select.innerHTML = '<option value="">Pilih Vendor</option>' + 
            result.data.map(v => `<option value="${v.idvendor}">${v.nama_vendor}</option>`).join('');
    }
}

async function loadUsers() {
    const result = await fetchData('?action=get_users');
    if (result.success) {
        const select = document.getElementById('iduser');
        select.innerHTML = '<option value="">Pilih User</option>' + 
            result.data.map(u => `<option value="${u.iduser}">${u.username}</option>`).join('');
    }
}

async function loadBarangDropdown() {
    const result = await fetchData('?action=get_all_barang');
    if (result.success) {
        const select = document.getElementById('select-barang');
        select.innerHTML = '<option value="">Pilih Barang</option>' + 
            result.data.map(item => `<option value='${JSON.stringify(item)}'>${item.nama} - ${formatRupiah(item.harga)}</option>`).join('');
    }
}


async function loadPengadaanList() {
    const result = await fetchData();
    const tbody = document.getElementById('tableBody');
    if (result.success && result.data.length > 0) {
        tbody.innerHTML = result.data.map(po => `
            <tr>
                <td>PO-${po.idpengadaan}</td>
                <td>${new Date(po.tanggal).toLocaleDateString('id-ID')}</td>
                <td>${po.nama_vendor}</td>
                <td>${po.username}</td>
                <td>${formatRupiah(po.total_nilai)}</td>
                <td class="action-buttons">
                    <button class="btn btn-primary btn-sm" onclick="editPengadaan('${po.idpengadaan}')">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deletePengadaan('${po.idpengadaan}')">Hapus</button>
                </td>
            </tr>
        `).join('');
    } else {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Tidak ada data pengadaan.</td></tr>';
    }
}

document.getElementById('btn-tambah-barang').addEventListener('click', () => {
    const select = document.getElementById('select-barang');
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption.value) {
        alert('Silakan pilih barang terlebih dahulu.');
        return;
    }
    const jumlah = document.getElementById('jumlah-barang').value;
    addItem(JSON.parse(selectedOption.value), jumlah);
});

function addItem(item, jumlah = 1) {
    const itemListBody = document.getElementById('item-list-body');
    if (document.querySelector(`tr[data-idbarang="${item.idbarang}"]`)) {
        alert('Barang sudah ada di dalam daftar.');
        return;
    }
    const row = document.createElement('tr');
    row.setAttribute('data-idbarang', item.idbarang);
    const subtotal = jumlah * item.harga;
    row.innerHTML = `
        <td>${item.nama}</td>
        <td><input type="number" class="item-qty" value="${jumlah}" min="1" oninput="updateTotals()"></td>
        <td class="item-price" data-price="${item.harga}">${formatRupiah(item.harga)}</td>
        <td class="item-subtotal">${formatRupiah(subtotal)}</td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); updateTotals();">X</button></td>
    `;
    itemListBody.appendChild(row);
    document.getElementById('select-barang').selectedIndex = 0; // Reset dropdown
    document.getElementById('jumlah-barang').value = 1; // Reset input jumlah
    updateTotals();
}

function updateTotals() {
    let grandTotal = 0;
    document.querySelectorAll('#item-list-body tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').dataset.price) || 0;
        const subtotal = qty * price;
        grandTotal += subtotal;
        row.querySelector('.item-subtotal').textContent = formatRupiah(subtotal);
    });
    document.getElementById('grand-total').textContent = formatRupiah(grandTotal);
}

function resetForm() {
    document.getElementById('formPengadaan').reset();
    document.getElementById('idpengadaan').value = '';
    document.getElementById('formMethod').value = '';
    document.getElementById('tanggal').valueAsDate = new Date();
    document.getElementById('item-list-body').innerHTML = '';
    document.getElementById('formTitle').textContent = 'Buat Pengadaan Baru';
    document.querySelector('#formPengadaan button[type="submit"]').textContent = 'Simpan Pengadaan';
    updateTotals();
}

document.getElementById('formPengadaan').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const method = formData.get('_method') || 'POST';

    const items = [];
    document.querySelectorAll('#item-list-body tr').forEach(row => {
        items.push({
            idbarang: row.dataset.idbarang,
            jumlah: parseFloat(row.querySelector('.item-qty').value),
            harga: parseFloat(row.querySelector('.item-price').dataset.price)
        });
    });

    if (items.length === 0) {
        alert('Mohon tambahkan minimal satu barang.');
        return;
    }

    const payload = {
        idpengadaan: formData.get('idpengadaan'),
        idvendor: formData.get('idvendor'),
        iduser: formData.get('iduser'),
        tanggal: formData.get('tanggal'),
        items: items
    };

    try {
        const response = await fetch(API_URL, {
            method: 'POST', // Selalu POST, metode asli dihandle di backend
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...payload, _method: method })
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            resetForm();
            loadPengadaanList();
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    }
});

async function editPengadaan(id) {
    const result = await fetchData(`?id=${id}`);
    if (result.success) {
        const po = result.data;
        resetForm();

        document.getElementById('formTitle').textContent = `Edit Pengadaan PO-${po.idpengadaan}`;
        document.getElementById('idpengadaan').value = po.idpengadaan;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('tanggal').value = po.tanggal;
        document.getElementById('idvendor').value = po.idvendor;
        document.getElementById('iduser').value = po.iduser;
        
        const itemListBody = document.getElementById('item-list-body');
        itemListBody.innerHTML = '';
        po.details.forEach(item => {
            const row = document.createElement('tr');
            row.setAttribute('data-idbarang', item.idbarang);
            row.innerHTML = `
                <td>${item.nama_barang}</td>
                <td><input type="number" class="item-qty" value="${item.jumlah}" min="1" oninput="updateTotals()"></td>
                <td class="item-price" data-price="${item.harga_satuan}">${formatRupiah(item.harga_satuan)}</td>
                <td class="item-subtotal">${formatRupiah(item.subtotal)}</td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); updateTotals();">X</button></td>
            `;
            itemListBody.appendChild(row);
        });

        updateTotals();
        document.querySelector('#formPengadaan button[type="submit"]').textContent = 'Perbarui Pengadaan';
        window.scrollTo(0, 0);
    }
}

async function deletePengadaan(id) {
    if (!confirm(`Yakin ingin menghapus Pengadaan PO-${id}? Aksi ini tidak dapat dibatalkan.`)) return;

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idpengadaan: id, _method: 'DELETE' })
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            loadPengadaanList();
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

document.getElementById('btnRefresh').addEventListener('click', loadPengadaanList);

</script>
</body>
</html>