<?php
require_once __DIR__ . '/../config/dbconnect.php';

function handleLogin() {
    global $dbconn;

    // Set header to return JSON
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        echo json_encode(['success' => false, 'message' => 'Username dan password harus diisi.']);
        return;
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Fetch user by username
    // The user table in pbd-script.sql has iduser, username, password, idrole
    $stmt = $dbconn->prepare("SELECT iduser, username, password FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 2. Verify password
        // For demonstration, we'll check against a plain text password 'admin123'
        // and a hashed version for a more secure example.
        // In a real app, you would ONLY use password_verify.
        // The hash for 'admin123' is '$2y$10$9.GzC...'. You'd store this in the DB.
        $isPasswordCorrect = ($password === 'admin123' || password_verify($password, $user['password']));

        if ($isPasswordCorrect) {
            // Password is correct, start session
            $_SESSION['user_id'] = $user['iduser'];
            $_SESSION['username'] = $user['username'];
            
            echo json_encode(['success' => true, 'message' => 'Login berhasil! Mengalihkan...']);
        } else {
            // Password is not valid
            echo json_encode(['success' => false, 'message' => 'Password yang Anda masukkan salah.']);
        }
    } else {
        // No user found
        echo json_encode(['success' => false, 'message' => 'Username tidak ditemukan.']);
    }
}

function handleLogout() {
    session_destroy();
    header('Location: ../login.html');
    exit;
}

// Check if logged in (untuk protect dashboard.php)
function checkAuth($isApi = false) {
    if (!isset($_SESSION['user_id'])) {
        if ($isApi) {
            http_response_code(401); // Unauthorized
            // Pastikan header Content-Type adalah JSON sebelum output
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Sesi Anda telah berakhir. Silakan login kembali.']);
        } else {
            header('Location: login.html');
        }
        exit;
    }
}

// --- Main Logic ---
// Router untuk menangani action dari request
$action = $_REQUEST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    handleLogin();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'logout') {
    handleLogout();
    exit;
}
?>
