<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(["success" => false, "message" => "Sesi tidak valid. Silakan login kembali."]);
    exit;
}

// Hak akses: hanya admin yang bisa hapus area
$_delete_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : 'petugas';
if ($_delete_role !== 'admin') {
    echo json_encode(["success" => false, "message" => "Akses ditolak. Hanya admin yang dapat menghapus area parkir."]);
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['id_area'])) {
    echo json_encode([
        "success" => false,
        "message" => "ID Area tidak ditemukan."
    ]);
    exit;
}

$id_area = intval($data['id_area']);

if (empty($id_area)) {
    echo json_encode(["success" => false, "message" => "ID Area tidak valid."]);
    exit;
}

try {
    $conn->beginTransaction();

    // Check relationship with transaksi_parkir
    $check = $conn->prepare("SELECT COUNT(*) FROM transaksi_parkir WHERE id_area = :id");
    $check->execute([":id" => $id_area]);
    $count = $check->fetchColumn();

    if ($count > 0) {
        $conn->rollBack();
        echo json_encode([
            "success" => false,
            "message" => "Gagal menghapus! Area ini memiliki riwayat transaksi parkir ($count transaksi)."
        ]);
        exit;
    }

    // Ambil nama untuk log
    $getName = $conn->prepare("SELECT nama_area FROM area_parkir WHERE id_area = :id");
    $getName->execute([':id' => $id_area]);
    $nama_area = $getName->fetchColumn();

    $stmt = $conn->prepare("DELETE FROM area_parkir WHERE id_area = :id");
    $stmt->execute([":id" => $id_area]);

    // Log aktivitas
    $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
    $logStmt->execute([
        ':uid' => $_SESSION['id_user'],
        ':act' => "Menghapus area parkir: $nama_area (ID: $id_area)"
    ]);

    $conn->commit();
    echo json_encode([
        "success" => true,
        "message" => "Area parkir '$nama_area' berhasil dihapus."
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        "success" => false,
        "message" => "Gagal menghapus data: " . $e->getMessage()
    ]);
}