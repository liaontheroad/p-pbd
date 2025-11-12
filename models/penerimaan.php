<?php
require_once __DIR__ . '/../config/dbconnect.php';
require_once __DIR__ . '/../models/auth.php';

header('Content-Type: application/json; charset=utf-8');
checkAuth(true); // Melindungi API

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

    if ($action === 'get_open_pos') {
        // Ambil daftar PO yang belum sepenuhnya diterima (logic ini bisa disempurnakan)
        $sql = "SELECT p.idpengadaan, p.timestamp, v.nama_vendor 
                FROM pengadaan p 
                JOIN vendor v ON p.vendor_idvendor = v.idvendor 
                WHERE p.status IS NULL OR p.status != 'closed'
                ORDER BY p.timestamp ASC";
        $result = $dbconn->query($sql);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);

    } elseif ($action === 'get_po_details' && isset($_GET['id'])) {
        // Ambil detail item dari sebuah PO
        $idpengadaan = $_GET['id'];
        $sql = "SELECT 
                    p.vendor_idvendor,
                    dp.idbarang,
                    b.nama,
                    dp.jumlah,
                    dp.harga_satuan
                FROM pengadaan p
                JOIN detail_pengadaan dp ON p.idpengadaan = dp.idpengadaan
                JOIN barang b ON dp.idbarang = b.idbarang
                WHERE p.idpengadaan = ?";
        $stmt = $dbconn->prepare($sql);
        $stmt->bind_param("i", $idpengadaan);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);

    } elseif ($action === 'get_vendors') {
        // Ambil daftar vendor yang aktif
        $result = $dbconn->query("SELECT idvendor, nama_vendor FROM vendor WHERE status = 1 ORDER BY nama_vendor");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } elseif ($action === 'search_barang') {
     } elseif ($action === 'get_penerimaan') {
        // Ambil data penerimaan untuk ditampilkan di tabel
        $sql = "SELECT idpenerimaan, idpengadaan, iduser, status, created_at FROM penerimaan ORDER BY created_at DESC";
        $result = $dbconn->query($sql);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);


    } elseif ($action === 'search_barang') {
        // Cari barang berdasarkan nama
        $term = $_GET['term'] ?? '';
        $stmt = $dbconn->prepare("SELECT idbarang, nama, harga FROM barang WHERE status = 1 AND nama LIKE ? LIMIT 10");
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

    $idpengadaan = $input['idpengadaan'] ?? null; // Ambil idpengadaan dari payload
    $iduser = $_SESSION['user_id'];

    // Validasi utama sekarang adalah idpengadaan, karena SP bergantung padanya.
    if (empty($idpengadaan)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap: Anda harus memilih Pengadaan (PO) terlebih dahulu.']);
        return;
    }

    // Memulai transaksi
    $dbconn->begin_transaction();
    try {
        // 1. Buat header penerimaan terlebih dahulu
        $stmt_penerimaan = $dbconn->prepare("INSERT INTO penerimaan (idpengadaan, iduser, status, created_at) VALUES (?, ?, 'P', NOW())");
        $stmt_penerimaan->bind_param("ii", $idpengadaan, $iduser);
        $stmt_penerimaan->execute();
        $idpenerimaan_baru = $dbconn->insert_id;

        if (!$idpenerimaan_baru) {
            throw new Exception("Gagal membuat header transaksi penerimaan.");
        }

        // 2. Insert detail penerimaan. Trigger 'trg_stok_masuk_penerimaan' akan otomatis berjalan untuk setiap insert.
        $items = $input['items'] ?? [];
        $stmt_detail = $dbconn->prepare(
            "INSERT INTO detail_penerimaan (idpenerimaan, barang_idbarang, jumlah_terima, harga_satuan_terima, sub_total_terima) VALUES (?, ?, ?, ?, ?)"
        );

        foreach ($items as $item) {
            if ($item['jumlah'] > 0) { // Hanya proses item yang diterima
                $subtotal = $item['jumlah'] * $item['harga'];
                $stmt_detail->bind_param("iiidd", $idpenerimaan_baru, $item['idbarang'], $item['jumlah'], $item['harga'], $subtotal);
                $stmt_detail->execute();
            }
        }

        // 3. Panggil SP untuk finalisasi status setelah semua detail dimasukkan
        $stmt_finalisasi = $dbconn->prepare("CALL finalisasi_status_penerimaan(?)");
        $stmt_finalisasi->bind_param("i", $idpenerimaan_baru);
        $stmt_finalisasi->execute();

        // Commit transaksi jika berhasil
        $dbconn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi penerimaan berhasil disimpan. Stok telah diperbarui.']);

    } catch (Exception $e) {
        // Rollback jika terjadi error
        $dbconn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat menyimpan: ' . $e->getMessage()]);
    }
}
?>