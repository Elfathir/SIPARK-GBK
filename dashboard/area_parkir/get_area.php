<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if (empty($id)) {
        echo json_encode(['error' => 'ID tidak valid']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT id_area, nama_area, lokasi, kapasitas_mobil, kapasitas_motor, status
        FROM area_parkir
        WHERE id_area = :id
    ");

    $stmt->execute([":id" => $id]);
    $area = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($area) {
        echo json_encode($area);
    } else {
        echo json_encode(['error' => 'Data tidak ditemukan']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}