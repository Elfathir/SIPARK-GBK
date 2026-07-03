<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

// AJAX ENDPOINTS & EXPORTS HANDLER
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'list') {
        try {
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : 'Semua';
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $params = [];
            $whereParts = [];

            if (!empty($search)) {
                $whereParts[] = "(k.plat_nomor ILIKE :search OR ap.nama_area ILIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if ($status !== 'Semua') {
                $whereParts[] = "tp.status = :status";
                $params[':status'] = $status;
            }

            $whereClause = "";
            if (!empty($whereParts)) {
                $whereClause = "WHERE " . implode(" AND ", $whereParts);
            }

            // Count total
            $countSql = "   SELECT COUNT(*) 
                            FROM transaksi_parkir tp
                            JOIN kendaraan k ON k.id_kendaraan = tp.id_kendaraan
                            JOIN area_parkir ap ON ap.id_area = tp.id_area
                            $whereClause";
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $totalData = $countStmt->fetchColumn();

            // Fetch list
            $sql = "SELECT  tp.id_transaksi, tp.id_kendaraan, tp.id_area, tp.id_petugas, tp.id_tarif, 
                            tp.waktu_masuk, tp.waktu_keluar, tp.durasi_jam, tp.total_biaya, tp.status,
                            k.plat_nomor, k.jenis_kendaraan,
                            ap.nama_area,
                            u.nama_lengkap AS nama_petugas
                    FROM transaksi_parkir tp
                    JOIN kendaraan k ON k.id_kendaraan = tp.id_kendaraan
                    JOIN area_parkir ap ON ap.id_area = tp.id_area
                    JOIN petugas p ON p.id_petugas = tp.id_petugas
                    JOIN users u ON u.id_user = p.id_user
                    $whereClause
                    ORDER BY tp.waktu_masuk DESC
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $transactions,
                'pagination' => [
                    'total' => (int)$totalData,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($totalData / $limit)
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_available_vehicles') {
        try {
            // Get vehicles not currently parked
            $sql = "SELECT k.id_kendaraan, k.plat_nomor, k.jenis_kendaraan 
                    FROM kendaraan k 
                    WHERE NOT EXISTS (
                        SELECT 1 FROM transaksi_parkir tp 
                        WHERE tp.id_kendaraan = k.id_kendaraan AND tp.status = 'Aktif'
                    )
                    ORDER BY k.plat_nomor ASC";
            $vehicles = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $vehicles]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_active_areas') {
        try {
            $areas = $conn->query("SELECT id_area, nama_area, kapasitas_mobil, kapasitas_motor FROM area_parkir WHERE status = 'Aktif' ORDER BY nama_area ASC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $areas]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Format input tidak valid.']);
            exit;
        }

        $postAction = isset($data['action']) ? $data['action'] : '';

        if ($postAction === 'checkin') {
            $id_kendaraan = intval($data['id_kendaraan']);
            $id_area = intval($data['id_area']);

            if (empty($id_kendaraan) || empty($id_area)) {
                echo json_encode(['success' => false, 'message' => 'Kendaraan dan Area Parkir wajib dipilih.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // 1. Get logged in user's id_petugas
                $stmtP = $conn->prepare("SELECT id_petugas FROM petugas WHERE id_user = :uid LIMIT 1");
                $stmtP->execute([':uid' => $_SESSION['id_user']]);
                $id_petugas = $stmtP->fetchColumn();

                // Fallback to first officer if logged in as admin without petugas record
                if (!$id_petugas) {
                    $id_petugas = $conn->query("SELECT id_petugas FROM petugas LIMIT 1")->fetchColumn();
                }

                if (!$id_petugas) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Tidak ada data petugas di sistem. Silakan buat petugas dahulu.']);
                    exit;
                }

                // 2. Find vehicle type to map to id_tarif
                $stmtK = $conn->prepare("SELECT plat_nomor, jenis_kendaraan FROM kendaraan WHERE id_kendaraan = :id");
                $stmtK->execute([':id' => $id_kendaraan]);
                $veh = $stmtK->fetch(PDO::FETCH_ASSOC);

                if (!$veh) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Kendaraan tidak ditemukan.']);
                    exit;
                }

                $stmtT = $conn->prepare("SELECT id_tarif FROM tarif_parkir WHERE LOWER(jenis_kendaraan) = LOWER(:jenis) LIMIT 1");
                $stmtT->execute([':jenis' => $veh['jenis_kendaraan']]);
                $id_tarif = $stmtT->fetchColumn();

                // Fallback if rate not defined
                if (!$id_tarif) {
                    $id_tarif = $conn->query("SELECT id_tarif FROM tarif_parkir LIMIT 1")->fetchColumn();
                }

                if (!$id_tarif) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Skema tarif parkir belum diatur untuk kendaraan ini.']);
                    exit;
                }

                // 3. Insert transaction
                $stmt = $conn->prepare("INSERT INTO transaksi_parkir (id_kendaraan, id_area, id_petugas, id_tarif, waktu_masuk, status) 
                                        VALUES (:id_k, :id_a, :id_p, :id_t, NOW(), 'Aktif')");
                $stmt->execute([
                    ':id_k' => $id_kendaraan,
                    ':id_a' => $id_area,
                    ':id_p' => $id_petugas,
                    ':id_t' => $id_tarif
                ]);

                // 4. Log activity
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $_SESSION['id_user'],
                    ':act' => "Check-in kendaraan " . $veh['plat_nomor'] . " masuk parkir."
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Kendaraan berhasil di-check-in.']);
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($postAction === 'checkout') {
            $id_transaksi = intval($data['id_transaksi']);

            if (empty($id_transaksi)) {
                echo json_encode(['success' => false, 'message' => 'ID Transaksi tidak valid.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // 1. Fetch transaction and associated rate
                $sqlTx = "  SELECT tp.*, t.tarif_awal, t.durasi_awal, t.tarif_tambahan, t.jenis_tarif, k.plat_nomor 
                            FROM transaksi_parkir tp
                            JOIN kendaraan k ON k.id_kendaraan = tp.id_kendaraan
                            JOIN tarif_parkir t ON t.id_tarif = tp.id_tarif
                            WHERE tp.id_transaksi = :id AND tp.status = 'Aktif'";
                $stmtTx = $conn->prepare($sqlTx);
                $stmtTx->execute([':id' => $id_transaksi]);
                $tx = $stmtTx->fetch(PDO::FETCH_ASSOC);

                if (!$tx) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Transaksi aktif tidak ditemukan.']);
                    exit;
                }

                // 2. Calculate duration
                $waktu_masuk = strtotime($tx['waktu_masuk']);
                $waktu_keluar_raw = date('Y-m-d H:i:s');
                $waktu_keluar = strtotime($waktu_keluar_raw);

                $diff_seconds = $waktu_keluar - $waktu_masuk;
                $durasi_jam = ceil($diff_seconds / 3600);
                if ($durasi_jam < 1) $durasi_jam = 1;

                // 3. Calculate pricing
                $total_biaya = 0;
                $tarif_awal = floatval($tx['tarif_awal']);
                $durasi_awal = intval($tx['durasi_awal']);
                $tarif_tambahan = floatval($tx['tarif_tambahan']);

                if ($tx['jenis_tarif'] === 'Bertingkat') {
                    if ($durasi_jam <= $durasi_awal) {
                        $total_biaya = $tarif_awal;
                    } else {
                        $total_biaya = $tarif_awal + (($durasi_jam - $durasi_awal) * $tarif_tambahan);
                    }
                } else {
                    $total_biaya = $tarif_awal;
                }

                // 4. Update transaction
                $updateStmt = $conn->prepare("  UPDATE transaksi_parkir 
                                                SET waktu_keluar = :keluar, durasi_jam = :durasi, 
                                                    total_biaya = :biaya, status = 'Selesai' 
                                                WHERE id_transaksi = :id");
                $updateStmt->execute([
                    ':keluar' => $waktu_keluar_raw,
                    ':durasi' => $durasi_jam,
                    ':biaya' => $total_biaya,
                    ':id' => $id_transaksi
                ]);

                // 5. Log activity
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $_SESSION['id_user'],
                    ':act' => "Check-out kendaraan " . $tx['plat_nomor'] . " (Biaya: Rp " . number_format($total_biaya, 0, ',', '.') . ")"
                ]);

                $conn->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Check-out berhasil. Biaya Parkir: Rp ' . number_format($total_biaya, 0, ',', '.') . " (" . $durasi_jam . " jam)"
                ]);
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($postAction === 'delete') {
            $id_transaksi = intval($data['id_transaksi']);

            if (empty($id_transaksi)) {
                echo json_encode(['success' => false, 'message' => 'ID Transaksi tidak ditemukan.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // Get info for logging
                $getPlat = $conn->prepare("SELECT k.plat_nomor FROM transaksi_parkir tp JOIN kendaraan k ON k.id_kendaraan = tp.id_kendaraan WHERE tp.id_transaksi = :id");
                $getPlat->execute([':id' => $id_transaksi]);
                $plat_nomor = $getPlat->fetchColumn();

                $stmt = $conn->prepare("DELETE FROM transaksi_parkir WHERE id_transaksi = :id");
                $stmt->execute([':id' => $id_transaksi]);

                // Log activity
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $_SESSION['id_user'],
                    ':act' => "Menghapus riwayat transaksi kendaraan $plat_nomor"
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dihapus.']);
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }
}

// EXPORTS PROCESSING (EXCEL/PDF)
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'export_excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=SIPARK-GBK-Transaksi-' . date('Ymd-His') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Kode ID', 'Nomor Polisi', 'Area Parkir', 'Petugas', 'Waktu Masuk', 'Waktu Keluar', 'Durasi (Jam)', 'Total Biaya', 'Status']);
        
        $sql = "SELECT tp.id_transaksi, k.plat_nomor, ap.nama_area, u.nama_lengkap AS nama_petugas, 
                        tp.waktu_masuk, tp.waktu_keluar, tp.durasi_jam, tp.total_biaya, tp.status
                FROM transaksi_parkir tp
                JOIN kendaraan k ON k.id_kendaraan = tp.id_kendaraan
                JOIN area_parkir ap ON ap.id_area = tp.id_area
                JOIN petugas p ON p.id_petugas = tp.id_petugas
                JOIN users u ON u.id_user = p.id_user
                ORDER BY tp.waktu_masuk DESC";
        $txs = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($txs as $t) {
            fputcsv($output, [
                'TX-' . str_pad($t['id_transaksi'], 5, '0', STR_PAD_LEFT),
                $t['plat_nomor'],
                $t['nama_area'],
                $t['nama_petugas'],
                $t['waktu_masuk'],
                $t['waktu_keluar'] ? $t['waktu_keluar'] : '-',
                $t['durasi_jam'] ? $t['durasi_jam'] . ' jam' : '-',
                $t['total_biaya'] ? floatval($t['total_biaya']) : 0,
                $t['status']
            ]);
        }
        fclose($output);
        exit();
    }

    if ($action === 'export_pdf') {
        $sql = "SELECT  
                        tp.id_transaksi, k.plat_nomor, ap.nama_area, u.nama_lengkap AS nama_petugas, 
                        tp.waktu_masuk, tp.waktu_keluar, tp.durasi_jam, tp.total_biaya, tp.status
                FROM transaksi_parkir tp
                JOIN kendaraan k ON k.id_kendaraan = tp.id_kendaraan
                JOIN area_parkir ap ON ap.id_area = tp.id_area
                JOIN petugas p ON p.id_petugas = tp.id_petugas
                JOIN users u ON u.id_user = p.id_user
                ORDER BY tp.waktu_masuk DESC";
        $txs = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Laporan Transaksi Parkir - SIPARK-GBK</title>
            <style>
                body { font-family: 'Arial', sans-serif; padding: 30px; color: #1F2937; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #9E3A3A; padding-bottom: 15px; }
                .logo { font-size: 24px; font-weight: bold; color: #9E3A3A; }
                .subtitle { font-size: 12px; color: #6B7280; margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
                th, td { border: 1px solid #E5E7EB; padding: 10px; text-align: left; }
                th { background-color: #F9FAFB; font-weight: bold; }
                .badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
                .badge-success { background-color: #DEF7EC; color: #03543F; }
                .badge-info { background-color: #E1EFFE; color: #1E429F; }
            </style>
        </head>
        <body onload="window.print()">
            <div class="header">
                <div class="logo">SIPARK-GBK</div>
                <div style="font-size: 16px; font-weight: bold; margin-top: 5px;">LAPORAN TRANSAKSI PARKIR KENDARAAN</div>
                <div class="subtitle">Dicetak pada: <?php echo date('d-m-Y H:i:s'); ?> WIB</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>KODE TRANSAKSI</th>
                        <th>NOMOR POLISI</th>
                        <th>AREA PARKIR</th>
                        <th>PETUGAS</th>
                        <th>JAM MASUK</th>
                        <th>JAM KELUAR</th>
                        <th>DURASI</th>
                        <th>TOTAL BIAYA</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($txs as $t): ?>
                        <tr>
                            <td><strong>TX-<?php echo str_pad($t['id_transaksi'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                            <td><?php echo htmlspecialchars($t['plat_nomor']); ?></td>
                            <td><?php echo htmlspecialchars($t['nama_area']); ?></td>
                            <td><?php echo htmlspecialchars($t['nama_petugas']); ?></td>
                            <td><?php echo date('d M Y - H:i', strtotime($t['waktu_masuk'])); ?></td>
                            <td><?php echo $t['waktu_keluar'] ? date('d M Y - H:i', strtotime($t['waktu_keluar'])) : '-'; ?></td>
                            <td><?php echo $t['durasi_jam'] ? $t['durasi_jam'] . ' jam' : '-'; ?></td>
                            <td>Rp <?php echo $t['total_biaya'] ? number_format($t['total_biaya'], 0, ',', '.') : '0'; ?></td>
                            <td>
                                <span class="badge <?php echo $t['status'] === 'Selesai' ? 'badge-success' : 'badge-info'; ?>">
                                    <?php echo $t['status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        exit();
    }
}

// Render regular layout
require_once __DIR__ . '/../includes/dashboard/header.php';
?>

<div class="toast-container" id="toastContainer"></div>

<!-- Content Header -->
<div class="content-header">
    <div>
        <h1>Data Transaksi Parkir</h1>
        <p>Laporan pencatatan parkir kendaraan keluar dan masuk kawasan GBK</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button class="btn btn-primary" onclick="openCheckinModal()">
            <i class="fas fa-sign-in-alt"></i> Check-in Baru
        </button>
        <a href="transaksi.php?action=export_excel" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <a href="transaksi.php?action=export_pdf" target="_blank" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
    </div>
</div>

<!-- Table Container -->
<div class="table-card mt-20">
    <div class="table-actions-bar">
        <!-- Search bar -->
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchTx" placeholder="Cari Plat Nomor / Area Parkir...">
        </div>

        <!-- Filter status tabs/dropdown -->
        <div class="filter-group">
            <label for="status-filter" style="font-size: 13px; font-weight: 500;">Filter Status:</label>
            <select id="status-filter" class="filter-select" onchange="filterByStatus(this.value)">
                <option value="Semua">Semua Transaksi</option>
                <option value="Aktif">Sedang Parkir</option>
                <option value="Selesai">Selesai / Keluar</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>KODE</th>
                    <th>NO POLISI</th>
                    <th>AREA</th>
                    <th>JAM MASUK</th>
                    <th>JAM KELUAR</th>
                    <th>DURASI</th>
                    <th>TOTAL BIAYA</th>
                    <th>STATUS</th>
                    <th style="width: 140px;">AKSI</th>
                </tr>
            </thead>
            <tbody id="tableTransaksi">
                <!-- Loaded dynamically by AJAX -->
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper" id="paginationWrapper">
        <!-- Loaded dynamically by AJAX -->
    </div>
</div>

<!-- MODAL: CHECK-IN KENDARAAN (BARU) -->
<div class="modal-overlay" id="modalCheckin">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Check-in Kendaraan Baru</h3>
            <button class="btn-close-modal" onclick="closeCheckinModal()">&times;</button>
        </div>
        <form id="formCheckin">
            <input type="hidden" name="action" value="checkin">
            <div class="modal-body">
                <div class="form-group">
                    <label for="id_kendaraan">Pilih Kendaraan</label>
                    <select name="id_kendaraan" id="id_kendaraan" class="form-control" required>
                        <!-- Loaded dynamically by AJAX -->
                    </select>
                    <small style="color: var(--text-secondary);">Hanya menampilkan kendaraan yang tidak sedang parkir.</small>
                </div>
                <div class="form-group">
                    <label for="id_area">Pilih Area Parkir</label>
                    <select name="id_area" id="id_area" class="form-control" required>
                        <!-- Loaded dynamically by AJAX -->
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCheckinModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Proses Masuk</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: DETAIL TRANSAKSI -->
<div class="modal-overlay" id="modalDetailTx">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Detail Transaksi Parkir</h3>
            <button class="btn-close-modal" onclick="closeDetailModal()">&times;</button>
        </div>
        <div class="modal-body" style="display: flex; flex-direction: column; gap: 14px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Kode Transaksi</span>
                <span id="detail-kode" style="font-weight: 600; color: var(--primary);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Nomor Polisi</span>
                <span id="detail-plat" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Area Parkir</span>
                <span id="detail-area" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Waktu Masuk</span>
                <span id="detail-masuk" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Waktu Keluar</span>
                <span id="detail-keluar" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Durasi</span>
                <span id="detail-durasi" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Petugas Penginput</span>
                <span id="detail-petugas" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Total Biaya</span>
                <span id="detail-biaya" style="font-weight: 700; color: #10B981;"></span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentStatus = 'Semua';

document.addEventListener("DOMContentLoaded", () => {
    loadTransactions();

    // Live search
    document.getElementById("searchTx").addEventListener("input", () => {
        currentPage = 1;
        loadTransactions();
    });

    // Checkin Form Submit
    document.getElementById("formCheckin").addEventListener("submit", function(e) {
        e.preventDefault();
        submitCheckin();
    });
});

function loadTransactions() {
    const search = document.getElementById("searchTx").value;
    fetch(`transaksi.php?ajax=1&action=list&search=${encodeURIComponent(search)}&status=${currentStatus}&page=${currentPage}`)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const tbody = document.getElementById("tableTransaksi");
            tbody.innerHTML = "";
            
            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center" style="color: var(--text-secondary); padding: 30px;">Tidak ada data transaksi.</td></tr>`;
                document.getElementById("paginationWrapper").innerHTML = "";
                return;
            }

            res.data.forEach(t => {
                const badgeClass = t.status === 'Selesai' ? 'success' : 'info';
                const kodeTx = 'TX-' + t.id_transaksi.toString().padStart(5, '0');
                const tKeluar = t.waktu_keluar ? new Date(t.waktu_keluar).toLocaleString('id-ID') : '<span style="color: var(--text-light); font-style: italic;">Aktif</span>';
                const durasiText = t.durasi_jam ? t.durasi_jam + ' jam' : '-';
                const biayaText = t.status === 'Selesai' ? 'Rp ' + Number(t.total_biaya).toLocaleString('id-ID') : '-';

                let actionHtml = '';
                if (t.status === 'Aktif') {
                    actionHtml = `
                        <button class="btn btn-success" style="padding: 4px 8px; border-radius: 6px; font-size: 11px; gap: 4px; display: inline-flex;" title="Checkout Kendaraan Keluar" onclick="checkoutVehicle(${t.id_transaksi}, '${t.plat_nomor}')">
                            <i class="fas fa-sign-out-alt"></i> Checkout
                        </button>
                    `;
                } else {
                    actionHtml = `
                        <button class="btn btn-secondary" style="padding: 4px 8px; border-radius: 6px; font-size: 11px; gap: 4px; display: inline-flex; opacity: 0.5; cursor: not-allowed;" disabled>
                            <i class="fas fa-check"></i> Selesai
                        </button>
                    `;
                }

                tbody.innerHTML += `
                    <tr>
                        <td style="font-weight: 600; color: var(--primary);">${kodeTx}</td>
                        <td style="font-weight: 600;">${escapeHtml(t.plat_nomor)}</td>
                        <td>${escapeHtml(t.nama_area)}</td>
                        <td>${new Date(t.waktu_masuk).toLocaleString('id-ID')}</td>
                        <td>${tKeluar}</td>
                        <td>${durasiText}</td>
                        <td style="font-weight: 600;">${biayaText}</td>
                        <td><span class="badge badge-${badgeClass}">${escapeHtml(t.status)}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon detail" title="Detail" onclick="viewDetail(${JSON.stringify(t).replace(/"/g, '&quot;')})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${actionHtml}
                                <button class="btn-icon delete" title="Delete" onclick="deleteTx(${t.id_transaksi})">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            renderPagination(res.pagination);
        } else {
            showToast("danger", "Gagal", "Gagal mengambil data transaksi.");
        }
    })
    .catch(err => {
        console.error(err);
        showToast("danger", "Error", "Terjadi kesalahan server saat memuat data.");
    });
}

function renderPagination(p) {
    const wrapper = document.getElementById("paginationWrapper");
    wrapper.innerHTML = "";

    const start = (p.page - 1) * p.limit + 1;
    const end = Math.min(p.page * p.limit, p.total);
    
    let html = `<div class="pagination-info">Menampilkan ${start} sampai ${end} dari ${p.total} data</div>`;
    html += `<div class="pagination-list">`;
    
    // Prev Button
    if (p.page > 1) {
        html += `<span class="page-link" onclick="changePage(${p.page - 1})"><i class="fas fa-chevron-left"></i></span>`;
    } else {
        html += `<span class="page-link disabled"><i class="fas fa-chevron-left"></i></span>`;
    }

    // Page Numbers
    for (let i = 1; i <= p.pages; i++) {
        if (i === p.page) {
            html += `<span class="page-link active">${i}</span>`;
        } else {
            html += `<span class="page-link" onclick="changePage(${i})">${i}</span>`;
        }
    }

    // Next Button
    if (p.page < p.pages) {
        html += `<span class="page-link" onclick="changePage(${p.page + 1})"><i class="fas fa-chevron-right"></i></span>`;
    } else {
        html += `<span class="page-link disabled"><i class="fas fa-chevron-right"></i></span>`;
    }

    html += `</div>`;
    wrapper.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    loadTransactions();
}

function filterByStatus(status) {
    currentStatus = status;
    currentPage = 1;
    loadTransactions();
}

function openCheckinModal() {
    // Kunci scroll halaman agar tidak lompat saat dropdown dibuka
    document.body.style.overflow = 'hidden';
    document.body.style.position = 'relative';

    // 1. Fetch available vehicles
    fetch('transaksi.php?ajax=1&action=get_available_vehicles')
    .then(res => res.json())
    .then(res => {
        const selectV = document.getElementById("id_kendaraan");
        selectV.innerHTML = "";
        if (res.success && res.data.length > 0) {
            res.data.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id_kendaraan;
                opt.textContent = escapeHtml(v.plat_nomor) + ' (' + escapeHtml(v.jenis_kendaraan) + ')';
                selectV.appendChild(opt);
            });
        } else {
            selectV.innerHTML = `<option value="">-- Tidak ada kendaraan tersedia --</option>`;
        }
    });

    // 2. Fetch active areas
    fetch('transaksi.php?ajax=1&action=get_active_areas')
    .then(res => res.json())
    .then(res => {
        const selectA = document.getElementById("id_area");
        selectA.innerHTML = "";
        if (res.success && res.data.length > 0) {
            res.data.forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.id_area;
                opt.textContent = escapeHtml(a.nama_area) + ' (Mobil: ' + a.kapasitas_mobil + ', Motor: ' + a.kapasitas_motor + ')';
                selectA.appendChild(opt);
            });
        } else {
            selectA.innerHTML = `<option value="">-- Tidak ada area aktif --</option>`;
        }
    });

    document.getElementById("modalCheckin").classList.add("show");
}

function closeCheckinModal() {
    document.getElementById("modalCheckin").classList.remove("show");
    // Kembalikan scroll halaman ke normal
    document.body.style.overflow = '';
    document.body.style.position = '';
}

function submitCheckin() {
    const idKendaraan = document.getElementById("id_kendaraan").value;
    const idArea = document.getElementById("id_area").value;

    if (!idKendaraan || !idArea) {
        showToast("warning", "Peringatan", "Lengkapi pilihan Kendaraan & Area Parkir.");
        return;
    }

    fetch('transaksi.php?ajax=1', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'checkin',
            id_kendaraan: parseInt(idKendaraan),
            id_area: parseInt(idArea)
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            closeCheckinModal();
            loadTransactions();
            showToast("success", "Berhasil", res.message);
        } else {
            showToast("danger", "Gagal", res.message);
        }
    })
    .catch(err => {
        console.error(err);
        showToast("danger", "Error", "Terjadi kesalahan server.");
    });
}

function checkoutVehicle(id, plat) {
    Swal.fire({
        title: 'Checkout Kendaraan',
        text: `Apakah Anda yakin ingin memproses checkout untuk kendaraan ${plat}? Jam keluar dan biaya parkir akan langsung dihitung.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Proses Keluar',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('transaksi.php?ajax=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'checkout', id_transaksi: id })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    loadTransactions();
                    Swal.fire('Selesai!', res.message, 'success');
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
            });
        }
    });
}

function viewDetail(t) {
    document.getElementById("detail-kode").textContent = 'TX-' + t.id_transaksi.toString().padStart(5, '0');
    document.getElementById("detail-plat").textContent = t.plat_nomor;
    document.getElementById("detail-area").textContent = t.nama_area;
    document.getElementById("detail-masuk").textContent = new Date(t.waktu_masuk).toLocaleString('id-ID');
    document.getElementById("detail-keluar").textContent = t.waktu_keluar ? new Date(t.waktu_keluar).toLocaleString('id-ID') : 'Masih Parkir (Aktif)';
    document.getElementById("detail-durasi").textContent = t.durasi_jam ? t.durasi_jam + ' jam' : '-';
    document.getElementById("detail-petugas").textContent = t.nama_petugas;
    document.getElementById("detail-biaya").textContent = t.status === 'Selesai' ? 'Rp ' + Number(t.total_biaya).toLocaleString('id-ID') : '-';

    document.getElementById("modalDetailTx").classList.add("show");
}

function closeDetailModal() {
    document.getElementById("modalDetailTx").classList.remove("show");
}

function deleteTx(id) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus transaksi ini? Data transaksi yang dihapus tidak dapat dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#9E3A3A',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('transaksi.php?ajax=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'delete', id_transaksi: id })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    loadTransactions();
                    Swal.fire('Terhapus!', res.message, 'success');
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
            });
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function showToast(type, title, message) {
    const container = document.getElementById("toastContainer");
    if (!container) return;

    const toast = document.createElement("div");
    toast.className = `toast ${type}`;
    let icon = "fa-circle-info";
    switch (type) {
        case "success":
            icon = "fa-circle-check";
            break;
        case "danger":
            icon = "fa-circle-xmark";
            break;
        case "warning":
            icon = "fa-triangle-exclamation";
            break;
        case "info":
            icon = "fa-circle-info";
            break;
    }

    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${icon}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-desc">${message}</div>
        </div>
        <div class="toast-close">
            <i class="fas fa-times"></i>
        </div>
    `;

    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add("show");
    }, 100);

    toast.querySelector(".toast-close").onclick = () => {
        toast.remove();
    };

    setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => {
            toast.remove();
        }, 400);
    }, 4000);
}
</script>

<?php
require_once __DIR__ . '/../includes/dashboard/footer.php';
?>