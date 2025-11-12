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
    <title>Penerimaan Barang - Sistem Inventory</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .transaction-form .card-body { padding: 0; }
        .form-header, .form-footer { padding: 28px; }
        .form-header { border-bottom: 1px solid #2a3142; }
        .form-footer { border-top: 1px solid #2a3142; }
        #item-list-table th, #item-list-table td { padding: 16px 28px; }
        #item-list-table input { 
            background: #0f1419; border-color: #3a4254; 
            padding: 8px; text-align: right; 
            color: #e4e6eb; /* Menambahkan warna teks terang */
        }
        .search-container { position: relative; }
        #search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #2a3142;
            border: 1px solid #3a4254;
            border-radius: 0 0 10px 10px;
            z-index: 10;
            max-height: 250px;
            overflow-y: auto;
        }
        .search-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #3a4254;
        }
        .search-item:last-child { border-bottom: none; }
        .search-item:hover { background: #323948; }
        .search-item small { color: #8b92a7; }
        .total-section { text-align: right; font-size: 1.5rem; font-weight: 700; }
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
                        <p>Transaksi Penerimaan Barang</p>
                    </div>
                </div>
                <div class="header-actions" style="display: flex; gap: 1rem;">
                    <a href="datamaster.php" class="btn btn-secondary"><span>⚙️</span> Menu Utama</a>
                    <a href="../models/auth.php?action=logout" class="btn btn-danger"><span>🚪</span> Keluar</a>
                </div>
            </div>
        </header>

        <div class="container">
            <div class="card transaction-form">
                <form id="formPenerimaan">
                    <input type="hidden" id="idpengadaan" name="idpengadaan">
                    <!-- Form Header -->
                    <div class="form-header" style="background: rgba(102, 126, 234, 0.05);">
                        <div class="form-group">
                            <label for="select-po">Pilih Pengadaan (Purchase Order) untuk Diterima</label>
                            <select id="select-po"><option value="">Pilih PO...</option></select>
                        </div>
                    </div>
                    <div class="form-header">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="idvendor">Vendor *</label>
                                <select id="idvendor" name="idvendor" required>
                                    <option value="">Memuat vendor...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="tanggal">Tanggal Penerimaan *</label>
                                <input type="date" id="tanggal" name="tanggal" required>
                            </div>
                        </div>
                    </div>

                    <!-- Item List Table -->
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
                            <tbody id="item-list-body">
                                <!-- Items will be added here dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Form Footer -->
                    <div class="form-footer">
                        <div class="total-section">
                            <span>Total: </span>
                            <span id="grand-total">Rp 0</span>
                        </div>
                        <div class="form-footer" style="padding: 28px 0 0 0; border-top: none;">
                            <button type="submit" class="btn btn-primary">Simpan Transaksi Penerimaan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
         <!-- Area Tampilan Data -->
            <div class="card">
                <div class="card-header">
                    <h2>Daftar Penerimaan</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablePenerimaan">
                            <thead>
                                <tr>
                                    <th>ID Penerimaan</th>
                                    <th>ID Pengadaan</th>
                                    <th>ID User</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <tr><td colspan="5" style="text-align: center;">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadVendors();
    loadOpenPOs();
    loadPenerimaan(); // Panggil fungsi untuk memuat tabel penerimaan
    document.getElementById('tanggal').valueAsDate = new Date();

    document.getElementById('select-po').addEventListener('change', handlePOSelection);
});

const formatRupiah = (number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);

async function loadPenerimaan() {
    try {
        const response = await fetch('../models/penerimaan.php?action=get_penerimaan');
        const result = await response.json();
        const tbody = document.getElementById('tableBody');

        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(p => {
                // Mapping untuk status agar lebih mudah dibaca
                const statusMap = {
                    'P': { text: 'Pending', class: 'badge-warning' },
                    'C': { text: 'Cicilan', class: 'badge-info' },
                    'F': { text: 'Final', class: 'badge-success' }
                };
                const statusInfo = statusMap[p.status] || { text: p.status, class: 'badge-secondary' };
                const tanggal = new Date(p.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

                return `
                    <tr>
                        <td>${p.idpenerimaan}</td>
                        <td>PO-${p.idpengadaan}</td>
                        <td>${p.iduser}</td>
                        <td><span class="badge ${statusInfo.class}">${statusInfo.text}</span></td>
                        <td>${tanggal}</td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Belum ada data penerimaan.</td></tr>';
        }
    } catch (error) {
        console.error('Error loading penerimaan:', error);
        document.getElementById('tableBody').innerHTML = '<tr><td colspan="5" style="text-align: center;">Gagal memuat data.</td></tr>';
    }
}

async function loadVendors() {
    try {
        const response = await fetch('../models/penerimaan.php?action=get_vendors');
        const result = await response.json();
        if (result.success) {
            const select = document.getElementById('idvendor');
            select.innerHTML = '<option value="">Pilih Vendor</option>' + 
                result.data.map(vendor => `<option value="${vendor.idvendor}">${vendor.nama_vendor}</option>`).join('');
        }
    } catch (error) {
        console.error('Error loading vendors:', error);
    }
}

async function loadOpenPOs() {
    try {
        const response = await fetch('../models/penerimaan.php?action=get_open_pos');
        const result = await response.json();
        if (result.success) {
            const select = document.getElementById('select-po');
            select.innerHTML = '<option value="">Pilih PO...</option>' + 
                result.data.map(po => {
                    const tanggal = new Date(po.timestamp).toLocaleDateString('id-ID');
                    return `<option value="${po.idpengadaan}">PO-${po.idpengadaan} - ${po.nama_vendor} (${tanggal})</option>`;
                }).join('');
        }
    } catch (error) {
        console.error('Error loading open POs:', error);
    }
}

async function handlePOSelection(e) {
    const idpengadaan = e.target.value;
    const itemListBody = document.getElementById('item-list-body');
    const vendorSelect = document.getElementById('idvendor');
    
    // Reset form jika tidak ada PO yang dipilih
    if (!idpengadaan) {
        vendorSelect.value = '';
        vendorSelect.disabled = false; // Aktifkan kembali dropdown vendor
        document.getElementById('idpengadaan').value = ''; // Reset hidden input
        itemListBody.innerHTML = '';
        updateTotals();
        return;
    }

    try {
        const response = await fetch(`../models/penerimaan.php?action=get_po_details&id=${idpengadaan}`);
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            // Set vendor dari PO yang dipilih
            vendorSelect.value = result.data[0].vendor_idvendor;
            vendorSelect.disabled = true; // Non-aktifkan dropdown vendor
            document.getElementById('idpengadaan').value = idpengadaan; // Simpan ID PO di hidden input
            
            // Kosongkan daftar item sebelum mengisi yang baru
            itemListBody.innerHTML = '';

            // Tambahkan setiap item dari PO ke tabel
            result.data.forEach(item => {
                // Menggunakan data dari PO untuk harga dan jumlah
                addItemFromPO(item);
            });
            updateTotals();
        } else {
            alert('Gagal memuat detail PO atau PO tidak memiliki item.');
        }
    } catch (error) {
        console.error('Error fetching PO details:', error);
        alert('Terjadi kesalahan saat mengambil detail PO.');
    }
}

function addItemFromPO(item) {
    const itemListBody = document.getElementById('item-list-body');
    if (document.querySelector(`tr[data-idbarang="${item.idbarang}"]`)) return; // Hindari duplikat

    const row = document.createElement('tr');
    row.setAttribute('data-idbarang', item.idbarang);
    const subtotal = item.jumlah * item.harga_satuan;
    row.innerHTML = `
        <td>${item.nama}</td>
        <td><input type="number" class="item-qty" value="${item.jumlah}" min="0" max="${item.jumlah}" onchange="updateTotals()" title="Jumlah di PO: ${item.jumlah}"></td>
        <td class="item-price" data-price="${item.harga_satuan}">${formatRupiah(item.harga_satuan)}</td>
        <td class="item-subtotal">${formatRupiah(subtotal)}</td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); updateTotals();" title="Hapus item dari daftar penerimaan">X</button></td>
    `;
    itemListBody.appendChild(row);
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

document.getElementById('formPenerimaan').addEventListener('submit', async (e) => {
    e.preventDefault();

    const idvendor = document.getElementById('idvendor').value;
    const tanggal = document.getElementById('tanggal').value;
    const idpengadaan = document.getElementById('idpengadaan').value; // Ambil ID PO dari hidden input
    const items = [];

    document.querySelectorAll('#item-list-body tr').forEach(row => {
        items.push({
            idbarang: row.dataset.idbarang,
            jumlah: parseFloat(row.querySelector('.item-qty').value),
            harga: parseFloat(row.querySelector('.item-price').dataset.price)
        });
    });

    if (!idvendor || !tanggal || items.length === 0) {
        alert('Mohon lengkapi vendor, tanggal, dan tambahkan minimal satu barang.');
        return;
    }

    const payload = { idvendor, tanggal, items, idpengadaan: idpengadaan };

    try {
        const response = await fetch('../models/penerimaan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        alert(result.message);

        if (result.success) {
            // Reset form
            document.getElementById('formPenerimaan').reset();
            document.getElementById('tanggal').valueAsDate = new Date();
            loadOpenPOs(); // Muat ulang daftar PO
            document.getElementById('item-list-body').innerHTML = '';
            updateTotals();
            loadPenerimaan(); // Muat ulang tabel penerimaan setelah berhasil menyimpan
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    }
});
</script>

</body>
</html>