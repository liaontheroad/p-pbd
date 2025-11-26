<?php
require_once '../config/dbconnect.php';
require_once '../models/auth.php';
checkAuth();

// Pastikan hanya role 2 yang bisa akses halaman ini
if ($_SESSION['role_id'] != 2) {
    // Jika bukan, tendang ke halaman login atau dashboard admin
    header('Location: ../login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Inventory PBD</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .datamaster-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 32px;
        }
        .dm-card {
            background: #1a1f2e;
            padding: 28px;
            border-radius: 16px;
            border: 1px solid #2a3142;
            text-decoration: none;
            color: #e4e6eb;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .dm-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
            border-color: #667eea;
        }
        .dm-card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        .dm-card-icon { font-size: 2rem; flex-shrink: 0; }
        .dm-card-title { font-size: 1.2rem; font-weight: 600; color: #ffffff; }
        .dm-card-description { font-size: 0.9rem; color: #8b92a7; line-height: 1.5; }
        .page-title { font-size: 2rem; font-weight: 700; color: #ffffff; margin-bottom: 1rem; border-bottom: 1px solid #2a3142; padding-bottom: 1rem; }
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
                        <p>Dashboard User</p>
                    </div>
                </div>
                <div class="header-actions" style="display: flex; gap: 1rem;">
                 <a href="../models/auth.php?action=logout" class="btn btn-danger"><span>🚪</span> Keluar</a>
                </div>
            </div>
        </header>

        <div class="container">
            <h2 class="page-title">Menu Transaksi</h2>
            <div class="datamaster-grid">
                <a href="manage_pengadaan.php" class="dm-card">
                    <div class="dm-card-header"><span class="dm-card-icon">📝</span><h3 class="dm-card-title">Pengadaan (PO)</h3></div>
                    <p class="dm-card-description">Buat pesanan pembelian (Purchase Order) ke vendor.</p>
                </a>
                <a href="manage_penerimaan.php" class="dm-card">
                    <div class="dm-card-header"><span class="dm-card-icon">📥</span><h3 class="dm-card-title">Penerimaan Barang</h3></div>
                    <p class="dm-card-description">Catat barang yang masuk dari vendor untuk menambah stok.</p>
                </a>
                <a href="manage_penjualan.php" class="dm-card">
                    <div class="dm-card-header"><span class="dm-card-icon">📤</span><h3 class="dm-card-title">Penjualan Barang</h3></div>
                    <p class="dm-card-description">Catat transaksi penjualan barang ke pelanggan dan kurangi stok.</p>
                </a>
                 <a href="view_barang.php" class="dm-card">
                    <div class="dm-card-header"><span class="dm-card-icon">📦</span><h3 class="dm-card-title">Lihat Daftar Barang</h3></div>
                    <p class="dm-card-description">Lihat daftar barang dan stok yang tersedia.</p>
                </a>
            </div>
        </div>
    </div>
</body>
</html>