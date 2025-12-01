<?php
// The checkAuth() function must be called at the very beginning to initialize the session.
require_once '../models/auth.php';
checkAuth();
require_once '../config/dbconnect.php';
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
        #item-list-table input { 
            background: #0f1419; border-color: #3a4254; 
            padding: 8px; text-align: right; 
            color: #e4e6eb; 
        }
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
        .badge-warning {
            background: rgba(250, 173, 20, 0.15);
            color: #faad14;
        }
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
                    <?php if ($_SESSION['role_id'] == 2): ?>
                        <a href="dashboard_user.php" class="btn btn-secondary"><span>⬅️</span> Kembali ke Dashboard</a>
                    <?php else: ?>
                        <a href="datamaster.php" class="btn btn-secondary"><span>⚙️</span> Menu Utama</a>
                    <?php endif; ?>
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
                                <label>Dibuat Oleh</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                                <input type="hidden" id="iduser" name="iduser" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
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
                            <button type="button" id="btn-finalize" class="btn btn-success" style="display: none;" onclick="finalizePengadaan()">Finalisasi Pengadaan</button>
                            <button type="submit" class="btn btn-primary">Simpan Pengadaan</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Area Tampilan Data -->
            <div class="card">
                <div class="card-header">
                    <h2>Daftar Pengadaan</h2>
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
                                    <th>Sisa Penerimaan</th>
                                    <th>Status</th>
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

    <!-- Modal Detail Pengadaan -->
    <div id="modalDetail" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 id="detailModalTitle">Detail Pengadaan</h3>
                <button class="close" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 28px;">
                <div class="form-row" style="margin-bottom: 20px; background: #191f2c; padding: 16px; border-radius: 8px;">
                    <div class="form-group"><label>ID Pengadaan:</label><p id="detailIdPO" class="modal-info"></p></div>
                    <div class="form-group"><label>Vendor:</label><p id="detailVendor" class="modal-info"></p></div>
                    <div class="form-group"><label>Tanggal:</label><p id="detailTanggal" class="modal-info"></p></div>
                    <div class="form-group"><label>Dibuat Oleh:</label><p id="detailUser" class="modal-info"></p></div>
                </div>

                <div class="table-responsive">
                    <table id="detail-item-list-table">
                        <thead>
                            <tr>
                                <th>Nama Barang</th><th width="15%">Jumlah</th><th width="20%">Harga Beli</th><th width="20%">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detail-item-list-body"></tbody>
                    </table>
                </div>
                <div class="total-section" style="margin-top: 20px;">
                    <span>Total: </span><span id="detail-grand-total">Rp 0</span>
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
    loadMasterData();
    loadPengadaanList();
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

async function loadMasterData() {
    const result = await fetchData('?list_data=true');
    if (result.success) {
        // Populate Vendors
        const vendorSelect = document.getElementById('idvendor');
        vendorSelect.innerHTML = '<option value="">Pilih Vendor</option>' + 
            result.vendors.map(v => `<option value="${v.idvendor}">${v.nama_vendor}</option>`).join('');

        // Populate Barang
        const barangSelect = document.getElementById('select-barang');
        barangSelect.innerHTML = '<option value="">Pilih Barang</option>' + 
            result.barangs.map(item => {
                return `<option value='${JSON.stringify(item)}'>${item.nama} - ${formatRupiah(item.harga)}</option>`;
            }).join('');
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
                <td>${po.total_dipesan - po.total_diterima} item</td>
                <td>${getStatusBadge(po.display_status)}</td>
                <td class="action-buttons">
                    <button class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); viewPengadaanDetails(${po.idpengadaan})">Lihat Detail</button>
                </td>
            </tr>
        `).join('');
    } else {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Tidak ada data pengadaan.</td></tr>';
    }
}

function getStatusBadge(status) {
    switch (status) {
        case 'closed':
            return `<span class="badge badge-danger">Closed</span>`;
        case 'Dipesan':
            return `<span class="badge badge-success">Dipesan</span>`;
        case 'Parsial':
            return `<span class="badge badge-warning">Parsial</span>`;
        case 'Diterima Penuh':
            return `<span class="badge badge-info">Diterima Penuh</span>`; // Assuming an info badge style exists or can be added
        default:
            return `<span class="badge">${status}</span>`;
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

    // Kunci dropdown vendor setelah item pertama ditambahkan
    document.getElementById('idvendor').disabled = true;

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
    const itemRows = document.querySelectorAll('#item-list-body tr');
    const vendorSelect = document.getElementById('idvendor');

    itemRows.forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').dataset.price) || 0;
        const subtotal = qty * price;
        grandTotal += subtotal;
        row.querySelector('.item-subtotal').textContent = formatRupiah(subtotal);
    });

    document.getElementById('grand-total').textContent = formatRupiah(grandTotal);

    // Re-enable the vendor dropdown only if all items have been removed
    if (itemRows.length === 0) {
        vendorSelect.disabled = false; // Buka kembali kunci dropdown vendor
    }
}

function resetForm() {
    document.getElementById('formPengadaan').reset();
    document.getElementById('formMethod').value = '';
    document.getElementById('tanggal').valueAsDate = new Date();
    document.getElementById('item-list-body').innerHTML = '';
    document.getElementById('formTitle').textContent = 'Buat Pengadaan Baru';
    document.querySelector('#formPengadaan button[type="submit"]').textContent = 'Simpan Pengadaan';
    document.getElementById('btn-finalize').style.display = 'none';
    document.getElementById('idvendor').disabled = false; // Pastikan vendor bisa dipilih lagi
    document.getElementById('idpengadaan').value = '';
    document.getElementById('username').value = "<?php echo htmlspecialchars($_SESSION['username']); ?>";
    updateTotals(); // Recalculate total (should be 0)
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
        idvendor: document.getElementById('idvendor').value, // Get value directly from the element
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

async function finalizePengadaan() {
    const idpengadaan = document.getElementById('idpengadaan').value;
    if (!idpengadaan) {
        alert('ID Pengadaan tidak ditemukan.');
        return;
    }

    if (!confirm(`Anda yakin ingin memfinalisasi (menutup) Pengadaan PO-${idpengadaan}? Status akan diubah menjadi 'closed' dan tidak bisa diubah lagi.`)) return;

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idpengadaan: idpengadaan, action: 'finalize' })
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            resetForm();
            loadPengadaanList();
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function viewPengadaanDetails(id) {
    const result = await fetchData(`?id=${id}`);
    if (result.success) {
        const po = result.data;

        // Populate Modal Header
        document.getElementById('detailModalTitle').textContent = `Detail Pengadaan PO-${po.idpengadaan}`;
        document.getElementById('detailIdPO').textContent = `PO-${po.idpengadaan}`;
        document.getElementById('detailVendor').textContent = po.nama_vendor;
        document.getElementById('detailTanggal').textContent = new Date(po.tanggal).toLocaleDateString('id-ID');
        document.getElementById('detailUser').textContent = po.username;

        // Populate Item List
        const detailBody = document.getElementById('detail-item-list-body');
        detailBody.innerHTML = '';
        let grandTotal = 0;
        po.details.forEach(item => {
            const subtotal = item.jumlah * item.harga_satuan;
            grandTotal += subtotal;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.nama_barang}</td>
                <td style="text-align: right;">${item.jumlah}</td>
                <td style="text-align: right;">${formatRupiah(item.harga_satuan)}</td>
                <td style="text-align: right;">${formatRupiah(subtotal)}</td>
            `;
            detailBody.appendChild(row);
        });

        // Populate Grand Total
        document.getElementById('detail-grand-total').textContent = formatRupiah(grandTotal);

        // Show Modal
        document.getElementById('modalDetail').classList.add('show');
    } else {
        alert('Gagal memuat detail pengadaan.');
    }
}

function closeDetailModal() {
    document.getElementById('modalDetail').classList.remove('show');
}

// Close detail modal if clicked outside
window.addEventListener('click', (event) => {
    if (event.target == document.getElementById('modalDetail')) {
        closeDetailModal();
    }
});
</script>
</body>
</html>