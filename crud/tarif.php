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

// AJAX ENDPOINTS HANDLER
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'list') {
        try {
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $params = [];
            $whereClause = "";
            if (!empty($search)) {
                $whereClause = "WHERE jenis_kendaraan ILIKE :search OR jenis_tarif ILIKE :search";
                $params[':search'] = '%' . $search . '%';
            }

            // Count total
            $countSql = "SELECT COUNT(*) FROM tarif_parkir $whereClause";
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $totalData = $countStmt->fetchColumn();

            // Fetch list
            $sql = "SELECT * FROM tarif_parkir 
                    $whereClause
                    ORDER BY id_tarif DESC
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $rates,
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Format input tidak valid.']);
            exit;
        }

        $postAction = isset($data['action']) ? $data['action'] : '';

        // Action 'add' dinonaktifkan — tarif fokus hanya Mobil & Motor
        if ($postAction === 'add') {
            echo json_encode(['success' => false, 'message' => 'Fitur tambah tarif tidak tersedia.']);
            exit;
        }

        if ($postAction === 'edit') {
            $id_tarif = intval($data['id_tarif']);
            $jenis_kendaraan = trim($data['jenis_kendaraan']);
            $tarif_awal = floatval($data['tarif_awal']);
            $durasi_awal = intval($data['durasi_awal']);
            $tarif_tambahan = floatval($data['tarif_tambahan']);
            $jenis_tarif = trim($data['jenis_tarif']);

            if (empty($id_tarif) || empty($jenis_kendaraan) || empty($jenis_tarif)) {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap untuk diperbarui.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // Check duplicate jenis_kendaraan
                $check = $conn->prepare("SELECT COUNT(*) FROM tarif_parkir WHERE LOWER(jenis_kendaraan) = LOWER(:jenis) AND id_tarif != :id");
                $check->execute([':jenis' => $jenis_kendaraan, ':id' => $id_tarif]);
                if ($check->fetchColumn() > 0) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => "Jenis kendaraan '$jenis_kendaraan' sudah digunakan di skema tarif lain."]);
                    exit;
                }

                $stmt = $conn->prepare("UPDATE tarif_parkir 
                                        SET jenis_kendaraan = :jenis, tarif_awal = :awal, durasi_awal = :durasi, 
                                            tarif_tambahan = :tambahan, jenis_tarif = :jenis_t 
                                        WHERE id_tarif = :id");
                $stmt->execute([
                    ':jenis' => $jenis_kendaraan,
                    ':awal' => $tarif_awal,
                    ':durasi' => $durasi_awal,
                    ':tambahan' => $tarif_tambahan,
                    ':jenis_t' => $jenis_tarif,
                    ':id' => $id_tarif
                ]);

                // Log activity
                $id_user = $_SESSION['id_user'];
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $id_user,
                    ':act' => "Memperbarui detail skema tarif $jenis_kendaraan"
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Tarif parkir berhasil diperbarui.']);
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($postAction === 'delete') {
            $id_tarif = intval($data['id_tarif']);

            if (empty($id_tarif)) {
                echo json_encode(['success' => false, 'message' => 'ID Tarif tidak ditemukan.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // Check constraint
                $check = $conn->prepare("SELECT COUNT(*) FROM transaksi_parkir WHERE id_tarif = :id");
                $check->execute([':id' => $id_tarif]);
                if ($check->fetchColumn() > 0) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Gagal menghapus! Tarif ini telah digunakan dalam data transaksi.']);
                    exit;
                }

                // Get jenis_kendaraan
                $getJenis = $conn->prepare("SELECT jenis_kendaraan FROM tarif_parkir WHERE id_tarif = :id");
                $getJenis->execute([':id' => $id_tarif]);
                $jenis_kendaraan = $getJenis->fetchColumn();

                $stmt = $conn->prepare("DELETE FROM tarif_parkir WHERE id_tarif = :id");
                $stmt->execute([':id' => $id_tarif]);

                // Log activity
                $id_user = $_SESSION['id_user'];
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $id_user,
                    ':act' => "Menghapus skema tarif $jenis_kendaraan"
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Tarif parkir berhasil dihapus.']);
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }
}

// Render regular layout
require_once __DIR__ . '/../includes/dashboard/header.php';
?>

<div class="toast-container" id="toastContainer"></div>

<!-- Content Header -->
<div class="content-header">
    <div>
        <h1>Data Tarif Parkir</h1>
        <p>Kelola skema tarif parkir kendaraan (Motor &amp; Mobil)</p>
    </div>
    <!-- Tombol Tambah Tarif dihapus: fokus hanya Motor & Mobil -->
</div>

<!-- Table Container -->
<div class="table-card mt-20">
    <div class="table-actions-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchRate" placeholder="Cari Jenis Kendaraan atau Jenis Tarif...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>NO</th>
                    <th>JENIS KENDARAAN</th>
                    <th>TARIF AWAL</th>
                    <th>DURASI AWAL (JAM)</th>
                    <th>TARIF TAMBAHAN / JAM</th>
                    <th>JENIS TARIF</th>
                    <th style="width: 120px;">AKSI</th>
                </tr>
            </thead>
            <tbody id="tableTarif">
                <!-- Loaded dynamically by AJAX -->
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper" id="paginationWrapper">
        <!-- Loaded dynamically by AJAX -->
    </div>
</div>

<!-- MODAL: ADD / EDIT RATE -->
<div class="modal-overlay" id="modalTarif">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Tarif</h3>
            <button class="btn-close-modal" onclick="closeTarifModal()">&times;</button>
        </div>
        <form id="formTarif">
            <input type="hidden" name="id_tarif" id="id_tarif">
            <div class="modal-body">
                <div class="form-group">
                    <label for="jenis_kendaraan">Jenis Kendaraan</label>
                    <select name="jenis_kendaraan" id="jenis_kendaraan" class="form-control" required>
                        <option value="Mobil">Mobil</option>
                        <option value="Motor">Motor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tarif_awal">Tarif Awal (Rp)</label>
                    <input type="number" name="tarif_awal" id="tarif_awal" class="form-control" min="0" placeholder="Contoh: 5000" required>
                </div>
                <div class="form-group">
                    <label for="durasi_awal">Durasi Awal (Jam)</label>
                    <input type="number" name="durasi_awal" id="durasi_awal" class="form-control" min="1" placeholder="Contoh: 1 atau 2" required>
                </div>
                <div class="form-group">
                    <label for="tarif_tambahan">Tarif Tambahan per Jam (Rp)</label>
                    <input type="number" name="tarif_tambahan" id="tarif_tambahan" class="form-control" min="0" placeholder="Contoh: 2000" required>
                </div>
                <div class="form-group">
                    <label for="jenis_tarif">Jenis Tarif</label>
                    <select name="jenis_tarif" id="jenis_tarif" class="form-control" required>
                        <option value="Bertingkat" selected>Bertingkat (Tarif Awal + Jam Tambahan)</option>
                        <option value="Flat">Flat (Tarif Awal Saja)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTarifModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: DETAIL RATE -->
<div class="modal-overlay" id="modalDetailTarif">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Detail Tarif Parkir</h3>
            <button class="btn-close-modal" onclick="closeDetailModal()">&times;</button>
        </div>
        <div class="modal-body" style="display: flex; flex-direction: column; gap: 14px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Jenis Kendaraan</span>
                <span id="detail-jenis_kendaraan" style="font-weight: 600; color: var(--primary);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Tarif Awal</span>
                <span id="detail-tarif_awal" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Durasi Awal</span>
                <span id="detail-durasi_awal" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Tarif Tambahan / Jam</span>
                <span id="detail-tarif_tambahan" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Jenis Skema Tarif</span>
                <span id="detail-jenis_tarif" class="badge badge-info"></span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;

document.addEventListener("DOMContentLoaded", () => {
    loadRates();

    // Live search
    document.getElementById("searchRate").addEventListener("input", () => {
        currentPage = 1;
        loadRates();
    });

    // Form submit
    document.getElementById("formTarif").addEventListener("submit", function(e) {
        e.preventDefault();
        submitTarif();
    });
});

function loadRates() {
    const search = document.getElementById("searchRate").value;
    fetch(`tarif.php?ajax=1&action=list&search=${encodeURIComponent(search)}&page=${currentPage}`)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const tbody = document.getElementById("tableTarif");
            tbody.innerHTML = "";
            
            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center" style="color: var(--text-secondary); padding: 30px;">Tidak ada data tarif parkir.</td></tr>`;
                document.getElementById("paginationWrapper").innerHTML = "";
                return;
            }

            let startNo = (res.pagination.page - 1) * res.pagination.limit + 1;
            res.data.forEach(rate => {
                tbody.innerHTML += `
                    <tr>
                        <td>${startNo++}</td>
                        <td style="font-weight: 600; color: var(--primary);">${escapeHtml(rate.jenis_kendaraan)}</td>
                        <td>Rp ${Number(rate.tarif_awal).toLocaleString('id-ID')}</td>
                        <td>${rate.durasi_awal} Jam</td>
                        <td>Rp ${Number(rate.tarif_tambahan).toLocaleString('id-ID')}</td>
                        <td><span class="badge ${rate.jenis_tarif === 'Bertingkat' ? 'badge-info' : 'badge-success'}">${escapeHtml(rate.jenis_tarif)}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon detail" title="Detail" onclick="viewDetail(${JSON.stringify(rate).replace(/"/g, '&quot;')})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-icon edit" title="Edit" onclick="editTarif(${JSON.stringify(rate).replace(/"/g, '&quot;')})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon delete" title="Delete" onclick="deleteTarif(${rate.id_tarif})">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            renderPagination(res.pagination);
        } else {
            showToast("danger", "Gagal", "Gagal mengambil data tarif parkir.");
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
    loadRates();
}

function closeTarifModal() {
    document.getElementById("modalTarif").classList.remove("show");
    document.body.style.overflow = '';
}

function editTarif(rate) {
    document.getElementById("id_tarif").value = rate.id_tarif;

    // Set select jenis_kendaraan — hanya Mobil/Motor
    const selJenis = document.getElementById("jenis_kendaraan");
    selJenis.value = rate.jenis_kendaraan;
    // Jika value tidak cocok (data lama), set Mobil sebagai default
    if (!selJenis.value) selJenis.value = 'Mobil';

    document.getElementById("tarif_awal").value = Math.round(rate.tarif_awal);
    document.getElementById("durasi_awal").value = rate.durasi_awal;
    document.getElementById("tarif_tambahan").value = Math.round(rate.tarif_tambahan);
    document.getElementById("jenis_tarif").value = rate.jenis_tarif;

    document.getElementById("modalTitle").textContent = "Edit Tarif Parkir";
    document.getElementById("modalTarif").classList.add("show");
    document.body.style.overflow = 'hidden';
}

function submitTarif() {
    const idTarif = document.getElementById("id_tarif").value;

    // Hanya izinkan edit — tambah tarif dinonaktifkan
    if (!idTarif) {
        showToast("warning", "Peringatan", "Fitur tambah tarif tidak tersedia.");
        return;
    }

    const payload = {
        action: 'edit',
        id_tarif: parseInt(idTarif),
        jenis_kendaraan: document.getElementById("jenis_kendaraan").value,
        tarif_awal: parseFloat(document.getElementById("tarif_awal").value) || 0,
        durasi_awal: parseInt(document.getElementById("durasi_awal").value) || 1,
        tarif_tambahan: parseFloat(document.getElementById("tarif_tambahan").value) || 0,
        jenis_tarif: document.getElementById("jenis_tarif").value
    };

    fetch('tarif.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            closeTarifModal();
            loadRates();
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

function viewDetail(rate) {
    document.getElementById("detail-jenis_kendaraan").textContent = rate.jenis_kendaraan;
    document.getElementById("detail-tarif_awal").textContent = 'Rp ' + Number(rate.tarif_awal).toLocaleString('id-ID');
    document.getElementById("detail-durasi_awal").textContent = rate.durasi_awal + ' Jam';
    document.getElementById("detail-tarif_tambahan").textContent = 'Rp ' + Number(rate.tarif_tambahan).toLocaleString('id-ID') + ' / Jam';
    
    const jenisTarifEl = document.getElementById("detail-jenis_tarif");
    jenisTarifEl.textContent = rate.jenis_tarif;
    jenisTarifEl.className = 'badge';
    if (rate.jenis_tarif === 'Bertingkat') {
        jenisTarifEl.classList.add('badge-info');
    } else {
        jenisTarifEl.classList.add('badge-success');
    }

    document.getElementById("modalDetailTarif").classList.add("show");
}

function closeDetailModal() {
    document.getElementById("modalDetailTarif").classList.remove("show");
}

function deleteTarif(id) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus skema tarif ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#9E3A3A',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('tarif.php?ajax=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'delete', id_tarif: id })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    loadRates();
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