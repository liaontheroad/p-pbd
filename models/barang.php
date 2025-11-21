<?php
require_once __DIR__ . '/../config/dbconnect.php';
require_once __DIR__ . '/../models/auth.php';

// Set header untuk output JSON
header('Content-Type: application/json; charset=utf-8');

// Pastikan user sudah login untuk mengakses data ini
checkAuth(true); // Tandai sebagai panggilan API

$method = $_SERVER['REQUEST_METHOD'];

// Ambil _method dari POST untuk simulasi PUT/DELETE
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

switch ($method) {
    case 'GET':
        handleGet($dbconn);
        break;
    case 'POST':
        handlePost($dbconn);
        break;
    case 'PUT':
        handlePut($dbconn);
        break;
    case 'DELETE':
        handleDelete($dbconn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Metode tidak didukung']);
        break;
}

$dbconn->close();

function handleGet($dbconn) {
    $action = $_GET['action'] ?? null;
    $id = $_GET['id'] ?? null;

    if ($id) {
        // Ambil satu barang untuk form edit
        $stmt = $dbconn->prepare("SELECT idbarang, nama, idsatuan, jenis, harga, status FROM barang WHERE idbarang = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // Mapping nama kolom agar sesuai dengan frontend
        $data = [
            'idbarang' => $result['idbarang'] ?? null,
            'kode_barang' => $result['idbarang'],
            'nama_barang' => $result['nama'],
            'idsatuan' => $result['idsatuan'],
            'jenis_barang' => $result['jenis'],
            'harga_pokok' => $result['harga'],
            'stok' => 0, // Stok akan di-handle terpisah jika ada kartu stok
            'status' => ($result['status'] ?? 0) == 1 ? 'aktif' : 'tidak_aktif'
        ];
        echo json_encode(['success' => true, 'data' => $data]);

    } elseif ($action === 'get_stats') {
        // Ambil statistik untuk dashboard
        $sql = "SELECT 
                    COUNT(idbarang) as total_barang,
                    SUM(harga) as total_nilai 
                FROM barang WHERE status = 1";
        $result = $dbconn->query($sql)->fetch_assoc();
        $result['total_stok'] = 0; // Placeholder, karena stok belum ada di tabel barang
        echo json_encode(['success' => true, 'data' => $result]);

    } elseif ($action === 'get_satuan') {
        // Ambil daftar satuan untuk dropdown
        $result = $dbconn->query("SELECT idsatuan, nama_satuan FROM satuan WHERE status = 1 ORDER BY nama_satuan");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);

    } else {
        // Ambil semua barang untuk tabel utama
        $filter = $_GET['filter'] ?? 'aktif'; // Default filter adalah 'aktif'

        $sql = "SELECT vbd.idbarang, vbd.nama, vbd.nama_satuan, vbd.jenis, vbd.harga, vbd.status, 
                       COALESCE((SELECT stok FROM kartu_stok WHERE idbarang = vbd.idbarang ORDER BY created_at DESC, idkartu_stok DESC LIMIT 1), 0) AS stok 
                FROM view_barang_details vbd";

        $params = [];
        $types = '';

        // Tambahkan kondisi WHERE hanya jika filter bukan 'semua'
        if ($filter !== 'semua') {
            $sql .= " WHERE status = ?";
            $params[] = 1; // Asumsikan 'aktif' berarti status = 1
            $types .= 'i';
        }

        $sql .= " ORDER BY vbd.idbarang ASC";

        $stmt = $dbconn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];        
        while ($row = $result->fetch_assoc()) {
           // Terjemahkan kode jenis barang ke deskripsi lengkap
           $jenis_desc = $row['jenis']; // Default value
           switch (strtolower($row['jenis'])) {
               case 'm':
                   $jenis_desc = 'Makanan / Minuman (Konsumsi)';
                   break;
               case 'p':
                   $jenis_desc = 'Perawatan Diri / Personal Care';
                   break;
               case 'k':
                   $jenis_desc = 'Kebutuhan Dapur';
                   break;
           }

           $data[] = [
                'idbarang' => $row['idbarang'],
                'kode_barang' => $row['idbarang'],
                'nama_barang' => $row['nama'],
                'nama_satuan' => $row['nama_satuan'] ?? '-', // Gunakan null coalescing untuk fallback
                'jenis_barang' => $jenis_desc,
                'harga_pokok' => $row['harga'],
            'stok' => $row['stok'],  
                'status' => ($row['status'] ?? 0) == 1 ? 'aktif' : 'tidak_aktif'
           ];
        }
        echo json_encode(['success' => true, 'data' => $data]);
    }
}

function handlePost($dbconn) {
    // Ambil data dari form
    $nama = $_POST['nama_barang'];
    $idsatuan = $_POST['idsatuan'];
    $jenis = $_POST['jenis_barang'];
    $harga = $_POST['harga_pokok'];
    $status = ($_POST['status'] === 'aktif') ? 1 : 0;
    
    try {
        $stmt = $dbconn->prepare("INSERT INTO barang (nama, idsatuan, jenis, harga, status) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Gagal mempersiapkan statement: ' . $dbconn->error);
        }
        $stmt->bind_param("sissi", $nama, $idsatuan, $jenis, $harga, $status);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Barang berhasil ditambahkan.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan barang: ' . $e->getMessage()]);
    }
}

function handlePut($dbconn) {
    // Ambil data dari form
    $idbarang = $_POST['idbarang'];
    $nama = $_POST['nama_barang'];
    $idsatuan = $_POST['idsatuan'];
    $jenis = $_POST['jenis_barang'];
    $harga = $_POST['harga_pokok'];
    $status = ($_POST['status'] === 'aktif') ? 1 : 0;
    
    try {
        $stmt = $dbconn->prepare("UPDATE barang SET nama = ?, idsatuan = ?, jenis = ?, harga = ?, status = ? WHERE idbarang = ?");
        if (!$stmt) {
            throw new Exception('Gagal mempersiapkan statement: ' . $dbconn->error);
        }
        $stmt->bind_param("sissii", $nama, $idsatuan, $jenis, $harga, $status, $idbarang);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Barang berhasil diperbarui.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui barang: ' . $e->getMessage()]);
    }
}

function handleDelete($dbconn) {
    $idbarang = $_POST['idbarang'];

    // Seharusnya tidak menghapus permanen, tapi mengubah status (soft delete)
    // $stmt = $dbconn->prepare("UPDATE barang SET status = 0 WHERE idbarang = ?");
    
    // Untuk demo, kita lakukan hard delete
    // Hati-hati: jika ada foreign key constraint, ini bisa gagal.
    // Pertama, hapus dari tabel anak jika ada (contoh: kartu_stok)
    // $dbconn->query("DELETE FROM kartu_stok WHERE idbarang = $idbarang");
    
    // Menggunakan soft delete untuk keamanan data
    try {
        $stmt = $dbconn->prepare("UPDATE barang SET status = 0 WHERE idbarang = ?");
        if (!$stmt) {
            throw new Exception('Gagal mempersiapkan statement: ' . $dbconn->error);
        }
        $stmt->bind_param("i", $idbarang);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Barang berhasil dinonaktifkan (soft delete).']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menonaktifkan barang: ' . $e->getMessage()]);
    }
}

?>