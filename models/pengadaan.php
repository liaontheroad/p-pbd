<?php
// --- DEBUGGING: Temporarily enable full error reporting as suggested. ---
// This should be disabled in a production environment.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING ---

require_once __DIR__ . '/../config/dbconnect.php';
require_once __DIR__ . '/../models/auth.php';

header('Content-Type: application/json');
checkAuth(true); // Protect the API endpoint

// --- Main Logic ---
$method = $_SERVER['REQUEST_METHOD'];

// Router to handle different actions
if ($method === 'GET') {
    $action = $_GET['action'] ?? null;
    $id = $_GET['id'] ?? null;

    if ($id) { // Highest priority: if an ID is provided, get that specific record.
        getPengadaanById($id);
    } elseif (isset($_GET['list_data'])) { // Check for master data request
        getMasterData();
    } else { // Default GET action is to list all records
        getAllPengadaan();
    }
} elseif ($method === 'POST') {
    // Handle POST, PUT, DELETE based on _method parameter
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;
    $_method = $data['_method'] ?? 'POST';

    if ($action === 'finalize') {
        handleFinalize($dbconn, $data);
    } elseif ($_method === 'DELETE') {
        deletePengadaan($dbconn, $data);
    } elseif ($_method === 'PUT') { // Implement the update logic
        updatePengadaan($dbconn, $data);
    } elseif ($_method === 'POST') {
        createPengadaan($dbconn, $data);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metode tidak didukung.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak didukung.']);
}

function getMasterData() {
    global $dbconn;
    try {
        $vendor_result = $dbconn->query("SELECT idvendor, nama_vendor FROM vendor WHERE status = '1'");
        $vendors = $vendor_result->fetch_all(MYSQLI_ASSOC);

        $barang_result = $dbconn->query("SELECT idbarang, nama, harga FROM barang WHERE status = 1");
        $barangs = $barang_result->fetch_all(MYSQLI_ASSOC);

        $user_result = $dbconn->query("SELECT iduser, username FROM user");
        $users = $user_result->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'vendors' => $vendors, 'barangs' => $barangs, 'users' => $users]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data master: ' . $e->getMessage()]);
    }
}

function getAllPengadaan() {
    global $dbconn;
    $sql = "SELECT 
                p.idpengadaan, p.timestamp as tanggal, v.nama_vendor, u.username, p.total_nilai,
                COALESCE((SELECT SUM(dp.jumlah) FROM detail_pengadaan dp WHERE dp.idpengadaan = p.idpengadaan), 0) AS total_dipesan,
                COALESCE((SELECT SUM(dpr.jumlah_terima) 
                          FROM detail_penerimaan dpr 
                          JOIN penerimaan pr ON dpr.idpenerimaan = pr.idpenerimaan 
                          WHERE pr.idpengadaan = p.idpengadaan), 0) AS total_diterima,
                CASE
                    WHEN p.status = 'c' THEN 'closed'
                    WHEN COALESCE((SELECT SUM(dpr.jumlah_terima) FROM detail_penerimaan dpr JOIN penerimaan pr ON dpr.idpenerimaan = pr.idpenerimaan WHERE pr.idpengadaan = p.idpengadaan), 0) >= 
                         COALESCE((SELECT SUM(dp.jumlah) FROM detail_pengadaan dp WHERE dp.idpengadaan = p.idpengadaan), 0) THEN 'Diterima Penuh'
                    WHEN COALESCE((SELECT SUM(dpr.jumlah_terima) FROM detail_penerimaan dpr JOIN penerimaan pr ON dpr.idpenerimaan = pr.idpenerimaan WHERE pr.idpengadaan = p.idpengadaan), 0) > 0 THEN 'Parsial'
                    ELSE 'Dipesan'
                END AS display_status
            FROM pengadaan p 
            LEFT JOIN vendor v ON p.vendor_idvendor = v.idvendor 
            LEFT JOIN user u ON p.user_iduser = u.iduser 
            ORDER BY p.timestamp DESC, p.idpengadaan DESC";
    $result = $dbconn->query($sql);
    $data = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
}

function getPengadaanById($id) {
    global $dbconn;
    try {
        // Fetch complete PO header details in one query
        // This query is now simplified and corrected, inspired by the working 'penerimaan' model.
        $stmt_header = $dbconn->prepare("
                    SELECT p.idpengadaan, p.timestamp as tanggal, v.nama_vendor, u.username
                    FROM pengadaan p
                    LEFT JOIN vendor v ON p.vendor_idvendor = v.idvendor
                    LEFT JOIN user u ON p.user_iduser = u.iduser
                    WHERE p.idpengadaan = ?
        ");
        if (!$stmt_header) {
            throw new Exception("Gagal mempersiapkan statement header: " . $dbconn->error);
        }
        $stmt_header->bind_param("i", $id);
        $stmt_header->execute();
        $result_header = $stmt_header->get_result();
        $po = $result_header->fetch_assoc();

        // If no PO is found, return a 404 error.
        if (!$po) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Pengadaan dengan ID {$id} tidak ditemukan."]);
            return;
        }

        // Fetch PO details and received quantities
        $stmt_details = $dbconn->prepare("
            SELECT 
                dp.idbarang,
                b.nama as nama_barang,
                dp.jumlah,
                dp.harga_satuan,
                (dp.jumlah * dp.harga_satuan) as subtotal
            FROM detail_pengadaan dp
            JOIN barang b ON dp.idbarang = b.idbarang
            WHERE dp.idpengadaan = ?
        ");
        $stmt_details->bind_param("i", $id);
        if (!$stmt_details) {
            throw new Exception("Gagal mempersiapkan statement detail: " . $dbconn->error);
        }
        $stmt_details->execute();
        $po['details'] = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);

        // This part is not needed for the view-only detail modal,
        // but we'll keep it simple for now. The main fix is the query above.
        $po['is_finalizable'] = false; // Defaulting to false as edit logic is removed.

        echo json_encode(['success' => true, 'data' => $po]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil detail pengadaan: ' . $e->getMessage()]);
    }
}

function createPengadaan($dbconn, $data) {
    $idvendor = $data['idvendor'] ?? null;
    $iduser = $_SESSION['user_id']; // Ambil user ID dari session yang aktif
    $tanggal = $data['tanggal'] ?? null;
    $items = $data['items'] ?? [];

    if (empty($idvendor) || empty($tanggal) || empty($items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap. Vendor, Tanggal, dan Barang harus diisi.']);
        return;
    }

    $dbconn->begin_transaction();
    try {
        $sql_header = "INSERT INTO pengadaan (timestamp, user_iduser, vendor_idvendor, status, subtotal_nilai, ppn, total_nilai) VALUES (?, ?, ?, 'p', ?, ?, ?)";
        $stmt_header = $dbconn->prepare($sql_header);
        $subtotal_nilai = 0; $ppn = 0; $total_nilai = 0;
        $stmt_header->bind_param("siiddd", $tanggal, $iduser, $idvendor, $subtotal_nilai, $ppn, $total_nilai);
        $stmt_header->execute();
        $idpengadaan_baru = $dbconn->insert_id;

        $sql_detail = "INSERT INTO detail_pengadaan (idpengadaan, idbarang, harga_satuan, jumlah) VALUES (?, ?, ?, ?)";
        $stmt_detail = $dbconn->prepare($sql_detail);
        foreach ($items as $item) {
            $stmt_detail->bind_param("iiid", $idpengadaan_baru, $item['idbarang'], $item['harga'], $item['jumlah']);
            $stmt_detail->execute();
        }

        $stmt_sp = $dbconn->prepare("CALL sp_hitung_dan_finalisasi_pengadaan(?)"); 
        $stmt_sp->bind_param("i", $idpengadaan_baru);
        $stmt_sp->execute();
        
        $dbconn->commit();
        echo json_encode(['success' => true, 'message' => 'Pengadaan berhasil dibuat!', 'id' => $idpengadaan_baru]);
    } catch (Exception $e) {
        $dbconn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal membuat Pengadaan: ' . $e->getMessage()]);
    }
}

function deletePengadaan($dbconn, $data) {
    $idpengadaan = $data['idpengadaan'] ?? null;

    if (!$idpengadaan) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID Pengadaan tidak valid.']);
        return;
    }

    $dbconn->begin_transaction();
    try {
        $stmt_detail = $dbconn->prepare("DELETE FROM detail_pengadaan WHERE idpengadaan = ?");
        $stmt_detail->bind_param("i", $idpengadaan);
        $stmt_detail->execute();

        $stmt_header = $dbconn->prepare("DELETE FROM pengadaan WHERE idpengadaan = ?");
        $stmt_header->bind_param("i", $idpengadaan);
        $stmt_header->execute();

        $dbconn->commit();
        echo json_encode(['success' => true, 'message' => "Pengadaan PO-{$idpengadaan} berhasil dihapus."]);
    } catch (Exception $e) {
        $dbconn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus pengadaan: ' . $e->getMessage()]);
    }
}

function handleFinalize($dbconn, $data) {
    $idpengadaan = $data['idpengadaan'] ?? null;

    if (!$idpengadaan) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID Pengadaan tidak valid.']);
        return;
    }

    try {
        $stmt = $dbconn->prepare("UPDATE pengadaan SET status = 'c' WHERE idpengadaan = ?");
        $stmt->bind_param("i", $idpengadaan);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => "Pengadaan PO-{$idpengadaan} berhasil difinalisasi (ditutup)."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memfinalisasi pengadaan: ' . $e->getMessage()]);
    }
}