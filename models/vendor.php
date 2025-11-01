<?php
require_once __DIR__ . '/../config/dbconnect.php';
require_once __DIR__ . '/../models/auth.php';

header('Content-Type: application/json');
checkAuth(true); // Melindungi API

$method = $_SERVER['REQUEST_METHOD'];

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
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metode tidak didukung']);
        break;
}

$dbconn->close();

function handleGet($dbconn) {
    $id = $_GET['id'] ?? null;

    if ($id) {
        // Ambil satu data vendor untuk form edit
        $stmt = $dbconn->prepare("SELECT idvendor, nama_vendor, alamat, telepon, status FROM vendor WHERE idvendor = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        // Tambahkan status_int untuk mencocokkan value di form (1 atau 0)
        if ($result) {
            $result['status_int'] = $result['status'];
        }
        echo json_encode(['success' => true, 'data' => $result]);
    } else {
        // Ambil semua data vendor untuk tabel utama, bisa dari view yang sudah dibuat
        $result = $dbconn->query("SELECT idvendor, nama_vendor, alamat, telepon, status FROM view_data_vendor ORDER BY idvendor ASC");
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Query Gagal: ' . $dbconn->error]);
            return;
        }

        $data = [];
        while ($row = $result->fetch_assoc()) {
            // Tambahkan status_text untuk tampilan di tabel (Aktif / Tidak Aktif)
            $row['status_text'] = $row['status'] == 1 ? 'Aktif' : 'Tidak Aktif';
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    }
}

function handlePost($dbconn) {
    $nama_vendor = $_POST['nama_vendor'];
    $alamat = $_POST['alamat'];
    $telepon = $_POST['telepon'];
    $status = (int)($_POST['status'] ?? 0);

    $stmt = $dbconn->prepare("INSERT INTO vendor (nama_vendor, alamat, telepon, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $nama_vendor, $alamat, $telepon, $status);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vendor berhasil ditambahkan.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan vendor: ' . $stmt->error]);
    }
}

function handlePut($dbconn) {
    $idvendor = $_POST['idvendor'];
    $nama_vendor = $_POST['nama_vendor'];
    $alamat = $_POST['alamat'];
    $telepon = $_POST['telepon'];
    $status = (int)($_POST['status'] ?? 0);

    $stmt = $dbconn->prepare("UPDATE vendor SET nama_vendor = ?, alamat = ?, telepon = ?, status = ? WHERE idvendor = ?");
    $stmt->bind_param("sssii", $nama_vendor, $alamat, $telepon, $status, $idvendor);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vendor berhasil diperbarui.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui vendor: ' . $stmt->error]);
    }
}

function handleDelete($dbconn) {
    $idvendor = $_POST['idvendor'];

    // Menggunakan soft delete (mengubah status menjadi tidak aktif)
    $stmt = $dbconn->prepare("UPDATE vendor SET status = 0 WHERE idvendor = ?");
    $stmt->bind_param("i", $idvendor);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Vendor berhasil dinonaktifkan.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Vendor tidak ditemukan.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menonaktifkan vendor: ' . $stmt->error]);
    }
}
?>