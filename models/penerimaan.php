<?php
require_once __DIR__ . '/../config/dbconnect.php';
require_once __DIR__ . '/../models/auth.php';

header('Content-Type: application/json');
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

    if ($action === 'get_vendors') {
        // Ambil daftar vendor yang aktif
        $result = $dbconn->query("SELECT idvendor, nama_vendor FROM vendor WHERE status = 1 ORDER BY nama_vendor");
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

    $idvendor = $input['idvendor'] ?? null;
    $tanggal = $input['tanggal'] ?? null;
    $items = $input['items'] ?? [];
    $iduser = $_SESSION['user_id'];

    if (empty($idvendor) || empty($tanggal) || empty($items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap: Vendor, tanggal, dan minimal satu barang harus diisi.']);
        return;
    }

    // Memulai transaksi
    $dbconn->begin_transaction();

    try {
        // Panggil Stored Procedure untuk memproses penerimaan
        // SP_ProsesPenerimaan(IN p_idvendor INT, IN p_iduser INT, IN p_tanggal DATE, IN p_items_json JSON)
        $items_json = json_encode($items);
        $stmt = $dbconn->prepare("CALL SP_ProsesPenerimaan(?, ?, ?, ?)");
        $stmt->bind_param("iiss", $idvendor, $iduser, $tanggal, $items_json);
        $stmt->execute();

        // Commit transaksi jika berhasil
        $dbconn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi penerimaan berhasil disimpan.']);

    } catch (Exception $e) {
        // Rollback jika terjadi error
        $dbconn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat menyimpan: ' . $e->getMessage()]);
    }
}
?>