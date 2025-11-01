<?php
require_once 'config/dbconnect.php'; // Menghubungkan ke database

// Mengambil data untuk dropdown satuan
$satuan_result = $dbconn->query("SELECT idsatuan, nama_satuan FROM satuan WHERE status = 1 ORDER BY nama_satuan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Barang - PBD</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Adaptasi Styling Header */
        .top-nav {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            margin-bottom: 20px;
        }
        .top-nav nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-nav .left-logo {
            max-height: 50px;
        }
        .top-nav nav ul {
            list-style: none;
            display: flex;
            gap: 2rem;
            margin: 0;
            padding: 0;
        }
        .top-nav nav ul li a {
            text-decoration: none;
            color: #6588e8;
            font-weight: 600;
            transition: color 0.3s;
        }
        .top-nav nav ul li a:hover {
            color: #4a6fc4;
        }

        /* --- Styling Khusus Halaman Barang --- */
        .page-container {
            width: 90%;
            margin: 0 auto;
            padding: 20px 0;
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .form-section h2 {
            color: #4a6fc4;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-submit {
            background-color: #6588e8;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        
        /* Styling untuk View/Tampilan Data */
        .view-section h2 {
             color: #4a6fc4;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 12px 15px;
            text-align: left;
        }
        th {
            background-color: #f1f3f5;
            color: #343a40;
            font-weight: 700;
        }
        .btn-edit, .btn-delete {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 0.9em;
        }
        .btn-edit { background-color: #ffc107; color: #343a40; }
        .btn-delete { background-color: #dc3545; color: white; }

        footer {
            margin-top: 50px;
            padding: 15px;
            background-color: #f8f9fa;
            text-align: center;
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>

    <header class="top-nav">
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="#">Manajemen Barang (CUD)</a></li>
                <li><a href="#">Laporan</a></li>
                <li><a href="login.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="page-container">

        <div class="form-section">
            <h2>Form Input/Edit Data Barang</h2>
            <form action="backend_handler.php" method="POST">
                
                <input type="hidden" name="action" id="action-type" value="create">
                <div class="form-group">
                    <label for="idbarang">ID Barang (untuk Create/Edit)</label>
                    <input type="text" id="idbarang" name="idbarang" required>
                </div>
                
                <div class="form-group">
                    <label for="nama">Nama Barang</label>
                    <input type="text" id="nama" name="nama" required>
                </div>
                
                <div class="form-group">
                    <label for="idsatuan">Satuan (FK ke Tabel Satuan)</label>
                    <select id="idsatuan" name="idsatuan" required>
                        <option value="">-- Pilih Satuan --</option> 
                        <?php
                        if ($satuan_result->num_rows > 0) {
                            while($row = $satuan_result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['idsatuan']) . "'>" . htmlspecialchars($row['nama_satuan']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="harga">Harga Pokok</label>
                    <input type="number" id="harga" name="harga" required min="100">
                </div>

                <div class="form-group">
                    <label for="jenis">Jenis (Ex: M/P/K)</label>
                    <input type="text" id="jenis" name="jenis" maxlength="1">
                </div>

                <button type="submit" class="btn-submit">Simpan Data Barang</button>
            </form>
        </div>

        <div class="view-section">
            <h2>Daftar Barang (View)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Barang</th>
                        <th>Jenis</th>
                        <th>Satuan</th>
                        <th>Harga Pokok</th>
                        <th>Harga Jual Rekomendasi (Function)</th>
                        <th>Aksi (Edit/Delete)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query untuk mengambil data barang beserta nama satuannya
                    $sql = "SELECT b.idbarang, b.nama, b.jenis, s.nama_satuan, b.harga 
                            FROM barang b 
                            JOIN satuan s ON b.idsatuan = s.idsatuan 
                            WHERE b.status = 1 
                            ORDER BY b.idbarang ASC";
                    $result = $dbconn->query($sql);

                    if ($result->num_rows > 0) {
                        // Menampilkan data untuk setiap baris
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['idbarang']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['jenis']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_satuan']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['harga']) . "</td>";
                            echo "<td>(backend)</td>"; // Placeholder untuk harga jual rekomendasi
                            echo "<td>";
                            echo "<button class='btn-edit' onclick=\"loadEditForm('" . htmlspecialchars($row['idbarang']) . "', '" . htmlspecialchars(addslashes($row['nama'])) . "', '" . htmlspecialchars($row['idsatuan']) . "', '" . htmlspecialchars($row['harga']) . "', '" . htmlspecialchars($row['jenis']) . "')\">Edit</button>";
                            echo "<a href='backend_handler.php?action=delete&id=" . htmlspecialchars($row['idbarang']) . "' class='btn-delete' onclick=\"return confirm('Yakin hapus barang " . htmlspecialchars($row['idbarang']) . "?')\">Hapus</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center;'>Tidak ada data barang.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            
            <p style="margin-top: 20px;">*Catatan: Kolom 'Harga Jual Rekomendasi' dihitung menggunakan **Function** SQL (min 1 function) yang terintegrasi di backend.</p>
            <p>*Aksi Edit/Hapus akan memicu pembaruan atau penghapusan data, di mana proses pembaruan harga akan memicu **Trigger** (min 1 trigger) untuk mencatat histori perubahan.</p>
            <p>*Input data baru idealnya menggunakan **Stored Procedure** (min 1 stored procedure) di sisi backend untuk validasi dan penanganan bisnis logic.</p>
        </div>
    </div>

    <footer>
        <p>&copy; Copyright 2024 Universitas Airlangga. All Rights Reserved</p>
    </footer>

    <script>
        function loadEditForm(id, nama, idsatuan, harga, jenis) {
            document.getElementById('idbarang').value = id;
            document.getElementById('nama').value = nama;
            document.getElementById('idsatuan').value = idsatuan;
            document.getElementById('harga').value = harga;
            document.getElementById('jenis').value = jenis;
            document.getElementById('action-type').value = 'update';
            document.getElementById('idbarang').readOnly = true; // Tidak bisa edit PK saat update

            // Ganti judul form untuk menunjukkan mode edit
            document.querySelector('.form-section h2').textContent = 'Form Edit Data Barang (Update)';
            document.querySelector('.btn-submit').textContent = 'Perbarui Data Barang';
        }
    </script>

</body>
</html>