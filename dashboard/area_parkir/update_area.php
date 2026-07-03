<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(["success" => false, "message" => "Sesi tidak valid. Silakan login kembali."]);
    exit;
}

// Hak akses: hanya admin yang bisa edit area
$_update_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : 'petugas';
if ($_update_role !== 'admin') {
    echo json_encode(["success" => false, "message" => "Akses ditolak. Hanya admin yang dapat mengubah area parkir."]);
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['id_area']) || empty(trim($data['nama_area'] ?? '')) || empty(trim($data['lokasi'] ?? ''))) {
    echo json_encode([
        "success" => false,
        "message" => "Data tidak lengkap untuk diperbarui."
    ]);
    exit;
}

$id_area         = intval($data['id_area']);
$nama_area       = trim($data['nama_area']);
$lokasi          = trim($data['lokasi']);
$kapasitas_mobil = isset($data['kapasitas_mobil']) ? intval($data['kapasitas_mobil']) : 0;
$kapasitas_motor = isset($data['kapasitas_motor']) ? intval($data['kapasitas_motor']) : 0;
$status          = isset($data['status']) && in_array($data['status'], ['Aktif', 'Nonaktif']) ? $data['status'] : 'Aktif';

try {
    $conn->beginTransaction();

    // Cek duplikat nama area (kecuali diri sendiri)
    $check = $conn->prepare("SELECT COUNT(*) FROM area_parkir WHERE LOWER(nama_area) = LOWER(:nama) AND id_area != :id");
    $check->execute([':nama' => $nama_area, ':id' => $id_area]);
    if ($check->fetchColumn() > 0) {
        $conn->rollBack();
        echo json_encode([
            "success" => false,
            "message" => "Area parkir dengan nama '$nama_area' sudah digunakan oleh area lain."
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE area_parkir
        SET nama_area = :nama,
            lokasi = :lokasi,
            kapasitas_mobil = :mobil,
            kapasitas_motor = :motor,
            status = :status
        WHERE id_area = :id
    ");

    $stmt->execute([
        ":nama"   => $nama_area,
        ":lokasi" => $lokasi,
        ":mobil"  => $kapasitas_mobil,
        ":motor"  => $kapasitas_motor,
        ":status" => $status,
        ":id"     => $id_area
    ]);

    // Log aktivitas
    $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
    $logStmt->execute([
        ':uid' => $_SESSION['id_user'],
        ':act' => "Memperbarui area parkir: $nama_area (ID: $id_area)"
    ]);

    $conn->commit();
    echo json_encode([
        "success" => true,
        "message" => "Area parkir '$nama_area' berhasil diperbarui."
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        "success" => false,
        "message" => "Gagal memperbarui data: " . $e->getMessage()
    ]);
}