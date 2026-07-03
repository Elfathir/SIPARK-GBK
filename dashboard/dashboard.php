<?php
// dashboard.php
require_once __DIR__ . '/../includes/dashboard/header.php';

// Tampilkan notifikasi akses ditolak jika ada
$_access_denied = false;
if (isset($_SESSION['access_denied']) && $_SESSION['access_denied']) {
    $_access_denied = true;
    unset($_SESSION['access_denied']);
}

$sql = "
    SELECT
        l.id_log,
        u.nama_lengkap,
        l.aktivitas,
        l.waktu
    FROM log_aktivitas l
    JOIN users u
        ON u.id_user = l.id_user
    ORDER BY l.waktu DESC
    LIMIT 10
";

$stmt = $conn->query($sql);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total kendaraan
$totalKendaraan = $conn->query("
    SELECT COUNT(*)
    FROM kendaraan
")->fetchColumn();

// Total Mobil
$totalMobil = $conn->query("
    SELECT COUNT(*)
    FROM kendaraan
    WHERE jenis_kendaraan = 'Mobil'
")->fetchColumn();

// Total Motor
$totalMotor = $conn->query("
    SELECT COUNT(*)
    FROM kendaraan
    WHERE jenis_kendaraan = 'Motor'
")->fetchColumn();

// Grafik kendaraan masuk
$sqlMasuk = "
    SELECT
        DATE(waktu_masuk) AS tanggal,
        TO_CHAR(DATE(waktu_masuk), 'DD Mon') AS label,
        COUNT(*) AS total
    FROM transaksi_parkir
    GROUP BY tanggal
    ORDER BY tanggal;
";

$stmt = $conn->query($sqlMasuk);

$chartLabel = [];
$chartMasuk = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $chartLabel[] = $row['label'];
    $chartMasuk[] = (int)$row['total'];
}

// Grafik kendaraan keluar
$sqlKeluar = "
    SELECT
        DATE(waktu_keluar) AS tanggal,
        TO_CHAR(DATE(waktu_keluar), 'DD Mon') AS label,
        COUNT(*) AS total
    FROM transaksi_parkir
    WHERE waktu_keluar IS NOT NULL
    GROUP BY tanggal
    ORDER BY tanggal;
";

$stmt = $conn->query($sqlKeluar);

$chartKeluar = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $chartKeluar[] = (int)$row['total'];
}

// Total area parkir
$totalArea = $conn->query("
    SELECT COUNT(*)
    FROM area_parkir
")->fetchColumn();

$sql = "
    SELECT
    COUNT(*) AS total_area,
    SUM(kapasitas_mobil) AS slot_mobil,
    SUM(kapasitas_motor) AS slot_motor
    FROM area_parkir
";

$dataArea = $conn
->query($sql)
->fetch(PDO::FETCH_ASSOC);

// Total petugas
$totalPetugas = $conn->query("
    SELECT COUNT(*)
    FROM petugas
")->fetchColumn();


// Total user
$totalUser = $conn->query("
    SELECT COUNT(*)
    FROM users
")->fetchColumn();

// Transaksi aktif
$totalTransaksi = $conn->query("
    SELECT COUNT(*)
    FROM transaksi_parkir
    WHERE status='Aktif'
")->fetchColumn();


// Pendapatan
$totalPendapatan = $conn->query("
    SELECT
    COALESCE(SUM(total_biaya),0)
    FROM transaksi_parkir
    WHERE status='Selesai'
")->fetchColumn();


// BAR CHART : Transaksi per Area
$sqlBar = "
    SELECT
        ap.nama_area,
        COUNT(tp.id_transaksi) AS total
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
?>

<div class="toast-container" id="toastContainer"></div>

<!-- Content Header -->
<div class="content-header">
    <div>
        <h1>Dashboard Utama</h1>
        <p>Sistem Informasi Parkir Kendaraan (SIPARK-GBK) - Kawasan Gelora Bung Karno</p>
    </div>
</div>

<?php if ($_access_denied): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toast akses ditolak
    const container = document.getElementById('toastContainer');
    if (container) {
        const toast = document.createElement('div');
        toast.className = 'toast danger show';
        toast.innerHTML = `
            <div class="toast-icon"><i class="fas fa-ban"></i></div>
            <div class="toast-content">
                <div class="toast-title">Akses Ditolak</div>
                <div class="toast-desc">Anda tidak memiliki izin untuk mengakses halaman tersebut.</div>
            </div>
            <div class="toast-close"><i class="fas fa-times"></i></div>
        `;
        container.appendChild(toast);
        toast.querySelector('.toast-close').onclick = () => toast.remove();
        setTimeout(() => { toast.remove(); }, 5000);
    }
});
</script>
<?php endif; ?>

<!-- Stats Cards Grid -->
<div class="stats-grid">
    <!-- Card 1: Kendaraan -->
    <div class="stats-card">
        <div class="stats-info">
            <h3>Total Kendaraan</h3>
            <div class="value" id="totalKendaraan">
                <?= $totalKendaraan ?>
            </div>
                <small id="detailKendaraan">
                    Mobil :
                    <strong><?= $totalMobil ?></strong>
                    |
                    Motor :
                    <strong><?= $totalMotor ?></strong>
                    </small>
        </div>
        <div class="stats-icon mobil">
            <i class="fas fa-car"></i>
        </div>
    </div>

    <!-- Card 2: Area Parkir -->
    <div class="stats-card">
        <div class="stats-info">
            <h3>Area Parkir</h3>
            <div class="value" id="totalArea">
            <?= $dataArea['total_area'] ?>
                <small>
                🚗 <?= number_format($dataArea['slot_mobil']) ?> Slot
                |
                🏍 <?= number_format($dataArea['slot_motor']) ?> Slot
                </small>
            </div>
        </div>
        <div class="stats-icon area">
            <i class="fas fa-map-marked-alt"></i>
        </div>
    </div>

    <!-- Card 3: Petugas -->
    <div class="stats-card">
        <div class="stats-info">
            <h3>Total Petugas</h3>
            <div class="value" id="totalPetugas">
                <?= $totalPetugas ?>
            </div>
        </div>
        <div class="stats-icon petugas">
            <i class="fas fa-user-tie"></i>
        </div>
    </div>

    <!-- Card 4: Users -->
    <div class="stats-card">
        <div class="stats-info">
            <h3>Pengguna Sistem</h3>
            <div class="value" id="totalUsers">
            <?= $totalUser ?>
            </div>
        </div>
        <div class="stats-icon users">
            <i class="fas fa-users-cog"></i>
        </div>
    </div>

    <!-- Card 5: Transaksi -->
    <div class="stats-card">
        <div class="stats-info">
            <h3>Transaksi Masuk</h3>
            <div class="value" id="totalTransaksi">
            <?= $totalTransaksi ?>
            </div>
        </div>
        <div class="stats-icon transaksi">
            <i class="fas fa-history"></i>
        </div>
    </div>

    <!-- Card 6: Pendapatan -->
    <div class="stats-card">
        <div class="stats-info">
            <h3>Pendapatan</h3>
            <div class="value" id="totalPendapatan" style="color: #10B981;">Rp <?= number_format($totalPendapatan,0,",",".") ?></div>
        </div>
        <div class="stats-icon pendapatan">
            <i class="fas fa-money-bill-wave"></i>
        </div>
    </div>
</div>

<!-- Charts & Activities Section -->
<div class="dashboard-grid-1 mt-20">
    <!-- Left Column: Area Chart -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">Grafik Kendaraan Masuk & Keluar</div>
            <div class="chart-filter">
                <select>
                    <option value="week">Minggu Ini</option>
                    <option value="month">Bulan Ini</option>
                    <option value="year">Tahun Ini</option>
                </select>
            </div>
        </div>
        <div class="chart-body">
            <canvas id="areaChart"></canvas>
        </div>
    </div>

    <!-- Right Column: Bar Chart & Activities -->
    <div style="display: flex; flex-direction: column; gap: 30px;">
        <!-- Bar Chart Card -->
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">Transaksi Hari Ini</div>
            </div>
            <div class="chart-body bar-scroll">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <!-- Latest Activities Card -->
        <div class="activities-card">
            <div class="activities-title">Aktivitas Terbaru</div>
            <div class="activities-list">
                <?php if (empty($activities)): ?>
                    <div class="text-center" style="color: var(--text-secondary); padding: 20px;">
                        Tidak ada aktivitas terbaru.
                    </div>
                <?php else: ?>
                <?php foreach ($activities as $act): ?>
                    <div class="activity-item">

                        <div class="activity-icon">
                            <i class="fas fa-user"></i>
                        </div>

                        <div class="activity-info">

                            <div class="activity-nopol">
                                <?= htmlspecialchars($act['nama_lengkap']); ?>
                            </div>

                            <div class="activity-loc">
                                <?= htmlspecialchars($act['aktivitas']); ?>
                            </div>

                        </div>

                        <div class="activity-time">
                            <i class="far fa-clock"></i>
                            <?= date('H:i', strtotime($act['waktu'])); ?>
                        </div>

                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const chartLabel = <?= json_encode($chartLabel) ?>;
    const chartMasuk = <?= json_encode($chartMasuk) ?>;
    const chartKeluar = <?= json_encode($chartKeluar) ?>;

    const barLabel = <?= json_encode($barLabel) ?>;
    const barData  = <?= json_encode($barData) ?>;
</script>

<script src="<?= BASE_URL ?>assets/js/chart.js"></script>
<script src="<?= BASE_URL ?>assets/js/dashboard-live.js"></script>

<?php
require_once __DIR__ . '/../includes/dashboard/footer.php';
?>