<?php
require_once __DIR__ . '/../config/dbconnect.php';
require_once __DIR__ . '/../models/auth.php';

header('Content-Type: application/json');
checkAuth(true); 

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($dbconn);
        break;
    case 'POST':
        handlePost($dbconn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metode tidak didukung']);
        break;
}

$dbconn->close();

function handleGet($dbconn) {
    $action = $_GET['action'] ?? null;

    if ($action === 'search_barang') {
        $term = $_GET['term'] ?? '';

        // Panggil Function hitung_harga_jual_dengan_margin untuk mendapatkan harga jual
        $stmt = $dbconn->prepare(
            "SELECT idbarang, nama, stok, hitung_harga_jual_dengan_margin(idbarang) as harga_jual 
             FROM barang 
             WHERE status = 1 AND stok > 0 AND nama LIKE ? 
             LIMIT 10"
        );
        $searchTerm = "%" . $term . "%";
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
    }
}

function handlePost($dbconn) {
    $input = json_decode(file_get_contents('php://input'), true);

    $tanggal = $input['tanggal'] ?? null;
    $items = $input['items'] ?? [];
    $iduser = $_SESSION['user_id'];

    if (empty($tanggal) || empty($items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap: Tanggal dan minimal satu barang harus diisi.']);
        return;
    }

    // Memulai transaksi database
    $dbconn->begin_transaction();

    try {
        // 1. Hitung total harga dari semua item di sisi PHP
        $total_harga = 0;
        foreach ($items as $item) {
            $total_harga += ($item['jumlah'] * $item['harga_jual']);
        }

        // 2. Buat header transaksi penjualan dengan total harga yang sudah dihitung
        $stmt_penjualan = $dbconn->prepare("INSERT INTO penjualan (tanggal, iduser, total) VALUES (?, ?, ?)");
        $stmt_penjualan->bind_param("sid", $tanggal, $iduser, $total_harga);
        $stmt_penjualan->execute();
        $idpenjualan = $dbconn->insert_id; // Ambil ID penjualan yang baru dibuat

        if (!$idpenjualan) {
            throw new Exception("Gagal membuat header transaksi penjualan.");
        }

        // 3. Loop setiap item dan masukkan ke tabel detail_penjualan
        // Ini lebih efisien daripada memanggil Stored Procedure berulang kali dalam loop.
        $stmt_detail = $dbconn->prepare("INSERT INTO detail_penjualan (penjualan_idpenjualan, idbarang, harga_satuan, jumlah, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $idbarang = $item['idbarang'];
            $jumlah = $item['jumlah'];
            $harga_jual = $item['harga_jual'];
            $subtotal = $jumlah * $harga_jual;

            $stmt_detail->bind_param("iiidd", $idpenjualan, $idbarang, $harga_jual, $jumlah, $subtotal);
            $stmt_detail->execute();
        }

        // Commit transaksi jika berhasil
        $dbconn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi penjualan berhasil disimpan.']);

    } catch (Exception $e) {
        // Rollback jika terjadi error
        $dbconn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage()]);
    }
}
?>