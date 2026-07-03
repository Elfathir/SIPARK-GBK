<?php
session_start();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$totalKendaraan = $conn
->query("
    SELECT COUNT(*)
    FROM kendaraan
")
->fetchColumn();

$totalMobil = $conn
->query("
    SELECT COUNT(*)
    FROM kendaraan
    WHERE jenis_kendaraan='Mobil'
")
->fetchColumn();

$totalMotor = $conn
->query("
    SELECT COUNT(*)
    FROM kendaraan
    WHERE jenis_kendaraan='Motor'
")
->fetchColumn();

$totalArea = $conn
->query("
    SELECT COUNT(*)
    FROM area_parkir
")
->fetchColumn();

$totalPetugas = $conn
->query("
    SELECT COUNT(*)
    FROM petugas
")
->fetchColumn();

$totalUser = $conn
->query("
    SELECT COUNT(*)
    FROM users
")
->fetchColumn();

$totalTransaksi = $conn
->query("
    SELECT COUNT(*)
    FROM transaksi_parkir
    WHERE status='Aktif'
")
->fetchColumn();

$totalPendapatan = $conn
->query("
    SELECT
    COALESCE(SUM(total_biaya),0)
    FROM transaksi_parkir
    WHERE status='Selesai'
")
->fetchColumn();

// Kendaraan Masuk
$sqlMasuk = "
    SELECT
        TO_CHAR(DATE(waktu_masuk),'DD Mon') AS hari,
        COUNT(*) total
    FROM transaksi_parkir
    GROUP BY DATE(waktu_masuk)
    ORDER BY DATE(waktu_masuk);
";

$stmt = $conn->query($sqlMasuk);
$chartLabel = [];
$chartMasuk = [];

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $chartLabel[] = $row['hari'];
    $chartMasuk[] = (int)$row['total'];
}

// Kendaraan Keluar
$sqlKeluar = "
SELECT
    TO_CHAR(DATE(waktu_keluar),'DD Mon') AS hari,
    COUNT(*) total
FROM transaksi_parkir
WHERE waktu_keluar IS NOT NULL
GROUP BY DATE(waktu_keluar)
ORDER BY DATE(waktu_keluar);
";

$stmt = $conn->query($sqlKeluar);

$chartKeluar = [];

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

    $chartKeluar[] = (int)$row['total'];

}


// Bar Chart
$sqlBar = "
    SELECT
        ap.nama_area,
        COUNT(tp.id_transaksi) total
    FROM area_parkir ap
    LEFT JOIN transaksi_parkir tp
    ON ap.id_area = tp.id_area
    GROUP BY ap.id_area, ap.nama_area
    ORDER BY total DESC;
";

$stmt = $conn->query($sqlBar);
$barLabel = [];
$barData = [];

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $barLabel[] = $row['nama_area'];
    $barData[] = (int)$row['total'];
}

echo json_encode([
    "kendaraan"=>$totalKendaraan,
    "mobil"=>$totalMobil,
    "motor"=>$totalMotor,

    "area"=>$totalArea,
    "petugas"=>$totalPetugas,
    "users"=>$totalUser,

    "transaksi"=>$totalTransaksi,
    "pendapatan"=>$totalPendapatan,

    "chartLabel"=>$chartLabel,
    "chartMasuk"=>$chartMasuk,
    "chartKeluar"=>$chartKeluar,

    "barLabel"=>$barLabel,
    "barData"=>$barData
]);