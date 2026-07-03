<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(["success" => false, "message" => "Sesi tidak valid. Silakan login kembali."]);
    exit;
}

// Hak akses: hanya admin yang bisa tambah area
$_simpan_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : 'petugas';
if ($_simpan_role !== 'admin') {
    echo json_encode(["success" => false, "message" => "Akses ditolak. Hanya admin yang dapat menambahkan area parkir."]);
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty(trim($data['nama_area'] ?? '')) || empty(trim($data['lokasi'] ?? ''))) {
    echo json_encode([
        "success" => false,
        "message" => "Nama area dan lokasi wajib diisi."
    ]);
    exit;
}

$nama_area       = trim($data['nama_area']);
$lokasi          = trim($data['lokasi']);
$kapasitas_mobil = isset($data['kapasitas_mobil']) ? intval($data['kapasitas_mobil']) : 0;
$kapasitas_motor = isset($data['kapasitas_motor']) ? intval($data['kapasitas_motor']) : 0;
$status          = isset($data['status']) && in_array($data['status'], ['Aktif', 'Nonaktif']) ? $data['status'] : 'Aktif';

try {
    $conn->beginTransaction();

    // Cek duplikat nama area
    $check = $conn->prepare("SELECT COUNT(*) FROM area_parkir WHERE LOWER(nama_area) = LOWER(:nama)");
    $check->execute([':nama' => $nama_area]);
    if ($check->fetchColumn() > 0) {
        $conn->rollBack();
        echo json_encode([
            "success" => false,
            "message" => "Area parkir dengan nama '$nama_area' sudah terdaftar."
        ]);
        exit;
    }

    $sql = "INSERT INTO area_parkir (nama_area, lokasi, kapasitas_mobil, kapasitas_motor, status) 
            VALUES (:nama, :lokasi, :mobil, :motor, :status)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":nama"   => $nama_area,
        ":lokasi" => $lokasi,
        ":mobil"  => $kapasitas_mobil,
        ":motor"  => $kapasitas_motor,
        ":status" => $status
    ]);

    // Log aktivitas
    $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
    $logStmt->execute([
        ':uid' => $_SESSION['id_user'],
        ':act' => "Menambahkan area parkir baru: $nama_area"
    ]);

    $conn->commit();
    echo json_encode([
        "success" => true,
        "message" => "Area parkir '$nama_area' berhasil ditambahkan."
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        "success" => false,
        "message" => "Gagal menyimpan data: " . $e->getMessage()
    ]);
}