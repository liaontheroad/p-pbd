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
    <title>Penjualan Barang - Sistem Inventory</title>
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
                        <p>Transaksi Penjualan Barang</p>
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
                <form id="formPenjualan">
                    <!-- Form Header -->
                    <div class="form-header">
                        <div class="form-group">
                            <label for="tanggal">Tanggal Penjualan *</label>
                            <input type="date" id="tanggal" name="tanggal" required>
                        </div>
                        <div class="form-group">
                            <label for="search-barang">Cari & Tambah Barang</label>
                            <div class="search-container">
                                <input type="text" id="search-barang" placeholder="Ketik nama barang...">
                                <div id="search-results" style="display: none;"></div>
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
                                    <th width="20%">Harga Jual</th>
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
                            <button type="submit" class="btn btn-primary">Simpan Transaksi Penjualan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('tanggal').valueAsDate = new Date();
});

const formatRupiah = (number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);

const searchInput = document.getElementById('search-barang');
const searchResults = document.getElementById('search-results');
let searchTimeout;

searchInput.addEventListener('keyup', (e) => {
    clearTimeout(searchTimeout);
    const term = e.target.value;
    if (term.length < 2) {
        searchResults.style.display = 'none';
        return;
    }
    // Debounce search to avoid too many requests
    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`../models/penjualan.php?action=search_barang&term=${term}`);
            const result = await response.json();
            if (result.success && result.data.length > 0) {
                searchResults.innerHTML = result.data.map(item => `
                    <div class="search-item" onclick='addItem(${JSON.stringify(item)})'>
                        <strong>${item.nama}</strong><br>
                        <small>Stok: ${item.stok} | Harga: ${formatRupiah(item.harga_jual)}</small>
                    </div>
                `).join('');
                searchResults.style.display = 'block';
            } else {
                searchResults.innerHTML = `<div class="search-item" style="cursor:default;">Tidak ada hasil</div>`;
                searchResults.style.display = 'block';
            }
        } catch (error) {
            console.error('Error searching barang:', error);
        }
    }, 300);
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-container')) {
        searchResults.style.display = 'none';
    }
});

function addItem(item) {
    const itemListBody = document.getElementById('item-list-body');
    
    if (document.querySelector(`tr[data-idbarang="${item.idbarang}"]`)) {
        alert('Barang sudah ada di dalam daftar.');
        return;
    }

    const row = document.createElement('tr');
    row.setAttribute('data-idbarang', item.idbarang);
    row.innerHTML = `
        <td>${item.nama}</td>
        <td><input type="number" class="item-qty" value="1" min="1" max="${item.stok}" onchange="updateTotals()" onkeyup="updateTotals()"></td>
        <td class="item-price" data-price="${item.harga_jual}">${formatRupiah(item.harga_jual)}</td>
        <td class="item-subtotal">${formatRupiah(item.harga_jual)}</td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); updateTotals();">X</button></td>
    `;
    itemListBody.appendChild(row);
    
    searchInput.value = '';
    searchResults.style.display = 'none';
    updateTotals();
}

function updateTotals() {
    let grandTotal = 0;
    document.querySelectorAll('#item-list-body tr').forEach(row => {
        const qtyInput = row.querySelector('.item-qty');
        let qty = parseFloat(qtyInput.value) || 0;
        const maxQty = parseInt(qtyInput.max);

        // Validasi agar qty tidak melebihi stok
        if (qty > maxQty) {
            alert(`Stok tidak mencukupi. Stok maksimal untuk barang ini adalah ${maxQty}.`);
            qtyInput.value = maxQty;
            qty = maxQty;
        }

        const price = parseFloat(row.querySelector('.item-price').dataset.price) || 0;
        const subtotal = qty * price;
        grandTotal += subtotal;
        row.querySelector('.item-subtotal').textContent = formatRupiah(subtotal);
    });
    document.getElementById('grand-total').textContent = formatRupiah(grandTotal);
}

document.getElementById('formPenjualan').addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitButton = e.target.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.textContent = 'Menyimpan...';

    const tanggal = document.getElementById('tanggal').value;
    const items = [];

    document.querySelectorAll('#item-list-body tr').forEach(row => {
        items.push({
            idbarang: row.dataset.idbarang,
            jumlah: parseFloat(row.querySelector('.item-qty').value),
            harga_jual: parseFloat(row.querySelector('.item-price').dataset.price)
        });
    });

    if (!tanggal || items.length === 0) {
        alert('Mohon lengkapi tanggal dan tambahkan minimal satu barang.');
        submitButton.disabled = false;
        submitButton.textContent = 'Simpan Transaksi Penjualan';
        return;
    }

    const payload = { tanggal, items };

    try {
        const response = await fetch('../models/penjualan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        alert(result.message);

        if (result.success) {
            // Reset form
            document.getElementById('formPenjualan').reset();
            document.getElementById('tanggal').valueAsDate = new Date();
            document.getElementById('item-list-body').innerHTML = '';
            updateTotals();
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Simpan Transaksi Penjualan';
    }
});
</script>

</body>
</html>