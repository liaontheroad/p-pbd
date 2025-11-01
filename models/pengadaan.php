<?php
require_once __DIR__ . '/../config/dbconnect.php';
require_once __DIR__ . '/../models/auth.php';

header('Content-Type: application/json');
checkAuth(true);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Ambil _method dari body untuk simulasi PUT/DELETE
if ($method === 'POST' && isset($input['_method'])) {
    $method = strtoupper($input['_method']);
}

switch ($method) {
    case 'GET':
        handleGet($dbconn);
        break;
    case 'POST':
        handlePost($dbconn, $input);
        break;
    case 'PUT':
        handlePut($dbconn, $input);
        break;
    case 'DELETE':
        handleDelete($dbconn, $input);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metode tidak didukung']);
        break;
}

$dbconn->close();

function handleGet($dbconn) {
    $action = $_GET['action'] ?? null;
    $id = $_GET['id'] ?? null;

    if ($id) {
        // Ambil satu data pengadaan beserta detailnya untuk form edit
        $stmt_header = $dbconn->prepare("SELECT idpengadaan, tanggal, idvendor, iduser FROM pengadaan WHERE idpengadaan = ?");
        $stmt_header->bind_param("i", $id);
        $stmt_header->execute();
        $header = $stmt_header->get_result()->fetch_assoc();

        if (!$header) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Data pengadaan tidak ditemukan.']);
            return;
        }

        $stmt_details = $dbconn->prepare(
            "SELECT dp.idbarang, b.nama as nama_barang, dp.jumlah, dp.harga_satuan, dp.subtotal 
             FROM detail_pengadaan dp
             JOIN barang b ON dp.idbarang = b.idbarang
             WHERE dp.idpengadaan = ?"
        );
        $stmt_details->bind_param("i", $id);
        $stmt_details->execute();
        $details = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);

        $header['details'] = $details;
        echo json_encode(['success' => true, 'data' => $header]);

    } elseif ($action === 'get_vendors') {
        $result = $dbconn->query("SELECT idvendor, nama_vendor FROM vendor WHERE status = 1 ORDER BY nama_vendor");
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'get_users') {
        $result = $dbconn->query("SELECT iduser, username FROM user ORDER BY username");
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'search_barang') {
        $term = $_GET['term'] ?? '';
        $stmt = $dbconn->prepare("SELECT idbarang, nama, harga FROM barang WHERE status = 1 AND nama LIKE ? LIMIT 10");
        $searchTerm = "%" . $term . "%";
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($action === 'get_all_barang') {
        $result = $dbconn->query("SELECT idbarang, nama, harga, stok FROM view_barang_aktif ORDER BY nama");
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
    } else {
        // Ambil semua data header pengadaan untuk tabel utama
        $sql = "SELECT p.idpengadaan, p.tanggal, v.nama_vendor, u.username, p.total_nilai 
                FROM pengadaan p
                JOIN vendor v ON p.idvendor = v.idvendor
                JOIN user u ON p.iduser = u.iduser
                ORDER BY p.tanggal DESC, p.idpengadaan DESC";
        $result = $dbconn->query($sql);
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
    }
}

function processPengadaan($dbconn, $input, $is_update = false) {
    $idpengadaan = $input['idpengadaan'] ?? null;
    $idvendor = $input['idvendor'] ?? null;
    $iduser = $input['iduser'] ?? null;
    $tanggal = $input['tanggal'] ?? null;
    $items = $input['items'] ?? [];

    if (empty($idvendor) || empty($iduser) || empty($tanggal) || empty($items)) {
        throw new Exception("Data tidak lengkap.");
    }

    $dbconn->begin_transaction();

    try {
        if ($is_update) {
            // Update header
            $stmt_header = $dbconn->prepare("UPDATE pengadaan SET tanggal=?, idvendor=?, iduser=? WHERE idpengadaan=?");
            $stmt_header->bind_param("siii", $tanggal, $idvendor, $iduser, $idpengadaan);
            $stmt_header->execute();
            // Hapus detail lama
            $stmt_delete = $dbconn->prepare("DELETE FROM detail_pengadaan WHERE idpengadaan=?");
            $stmt_delete->bind_param("i", $idpengadaan);
            $stmt_delete->execute();
        } else {
            // Buat header baru (total_nilai akan diupdate oleh SP)
            $stmt_header = $dbconn->prepare("INSERT INTO pengadaan (tanggal, idvendor, iduser, total_nilai) VALUES (?, ?, ?, 0)");
            $stmt_header->bind_param("sii", $tanggal, $idvendor, $iduser);
            $stmt_header->execute();
            $idpengadaan = $dbconn->insert_id;
        }

        // Masukkan detail baru
        $stmt_detail = $dbconn->prepare("INSERT INTO detail_pengadaan (idpengadaan, idbarang, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            // Kalkulasi subtotal di backend (sebagai validasi) menggunakan function SQL
            $subtotal = $item['jumlah'] * $item['harga'];
            $stmt_detail->bind_param("iiidd", $idpengadaan, $item['idbarang'], $item['jumlah'], $item['harga'], $subtotal);
            $stmt_detail->execute();
        }

        // Panggil Stored Procedure untuk finalisasi (menghitung dan update total_nilai di header)
        $stmt_sp = $dbconn->prepare("CALL sp_hitung_dan_finalisasi_pengadaan(?)");
        $stmt_sp->bind_param("i", $idpengadaan);
        $stmt_sp->execute();

        $dbconn->commit();
        return ['success' => true, 'message' => 'Pengadaan berhasil disimpan.'];
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }
}

function handlePost($dbconn, $input) {
    try {
        $result = processPengadaan($dbconn, $input, false);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
}

function handlePut($dbconn, $input) {
    try {
        $result = processPengadaan($dbconn, $input, true);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui: ' . $e->getMessage()]);
    }
}

function handleDelete($dbconn, $input) {
    $idpengadaan = $input['idpengadaan'] ?? null;
    if (empty($idpengadaan)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID Pengadaan tidak valid.']);
        return;
    }

    $dbconn->begin_transaction();
    try {
        // Hapus detail terlebih dahulu
        $stmt_detail = $dbconn->prepare("DELETE FROM detail_pengadaan WHERE idpengadaan = ?");
        $stmt_detail->bind_param("i", $idpengadaan);
        $stmt_detail->execute();

        // Hapus header
        $stmt_header = $dbconn->prepare("DELETE FROM pengadaan WHERE idpengadaan = ?");
        $stmt_header->bind_param("i", $idpengadaan);
        $stmt_header->execute();

        $dbconn->commit();
        echo json_encode(['success' => true, 'message' => 'Pengadaan berhasil dihapus.']);
    } catch (Exception $e) {
        $dbconn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
    }
}
?>