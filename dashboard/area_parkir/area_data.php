<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page   = isset($_GET['page'])   ? max(1, intval($_GET['page'])) : 1;
    $limit  = isset($_GET['limit'])  ? max(1, intval($_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;

    $params      = [];
    $whereClause = '';
    if (!empty($search)) {
        $whereClause         = "WHERE nama_area ILIKE :search OR lokasi ILIKE :search";
        $params[':search']   = '%' . $search . '%';
    }

    // Hitung total data
    $countSql  = "SELECT COUNT(*) FROM area_parkir $whereClause";
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($params);
    $totalData = (int) $countStmt->fetchColumn();

    // Ambil data halaman ini
    $sql = "
        SELECT
            id_area,
            nama_area,
            lokasi,
            kapasitas_mobil,
            kapasitas_motor,
            status
        FROM area_parkir
        $whereClause
        ORDER BY id_area ASC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'data'    => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'pagination' => [
            'total' => $totalData,
            'page'  => $page,
            'limit' => $limit,
            'pages' => (int) ceil($totalData / $limit),
        ],
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => [], 'pagination' => []]);
}