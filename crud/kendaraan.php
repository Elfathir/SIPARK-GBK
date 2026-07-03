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
                $whereClause = "WHERE plat_nomor ILIKE :search OR merk ILIKE :search OR pemilik ILIKE :search";
                $params[':search'] = '%' . $search . '%';
            }

            // Count total
            $countSql = "SELECT COUNT(*) FROM kendaraan $whereClause";
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $totalData = $countStmt->fetchColumn();

            // Fetch list
            $sql = "SELECT k.*, 
                            CASE WHEN EXISTS (
                                SELECT 1 FROM transaksi_parkir tp 
                                WHERE tp.id_kendaraan = k.id_kendaraan AND tp.status = 'Aktif'
                            ) THEN 'Parkir' ELSE 'Keluar' END AS status
                    FROM kendaraan k
                    $whereClause
                    ORDER BY k.id_kendaraan DESC
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $vehicles,
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

        if ($postAction === 'add') {
            $plat_nomor = strtoupper(trim($data['plat_nomor']));
            $jenis_kendaraan = trim($data['jenis_kendaraan']);
            $merk = trim($data['merk']);
            $warna = trim($data['warna']);
            $pemilik = trim($data['pemilik']);

            if (empty($plat_nomor) || empty($jenis_kendaraan) || empty($pemilik)) {
                echo json_encode(['success' => false, 'message' => 'Plat nomor, jenis kendaraan, dan pemilik wajib diisi.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // Check duplicate
                $check = $conn->prepare("SELECT COUNT(*) FROM kendaraan WHERE plat_nomor = :plat");
                $check->execute([':plat' => $plat_nomor]);
                if ($check->fetchColumn() > 0) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => "Kendaraan dengan plat nomor $plat_nomor sudah terdaftar."]);
                    exit;
                }

                $stmt = $conn->prepare("INSERT INTO kendaraan (plat_nomor, jenis_kendaraan, merk, warna, pemilik) 
                                        VALUES (:plat, :jenis, :merk, :warna, :pemilik)");
                $stmt->execute([
                    ':plat' => $plat_nomor,
                    ':jenis' => $jenis_kendaraan,
                    ':merk' => $merk,
                    ':warna' => $warna,
                    ':pemilik' => $pemilik
                ]);

                // Log activity
                $id_user = $_SESSION['id_user'];
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $id_user,
                    ':act' => "Menambahkan kendaraan baru $plat_nomor"
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Kendaraan berhasil ditambahkan.']);
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($postAction === 'edit') {
            $id_kendaraan = intval($data['id_kendaraan']);
            $plat_nomor = strtoupper(trim($data['plat_nomor']));
            $jenis_kendaraan = trim($data['jenis_kendaraan']);
            $merk = trim($data['merk']);
            $warna = trim($data['warna']);
            $pemilik = trim($data['pemilik']);

            if (empty($id_kendaraan) || empty($plat_nomor) || empty($jenis_kendaraan) || empty($pemilik)) {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap untuk diperbarui.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // Check duplicate plat_nomor
                $check = $conn->prepare("SELECT COUNT(*) FROM kendaraan WHERE plat_nomor = :plat AND id_kendaraan != :id");
                $check->execute([':plat' => $plat_nomor, ':id' => $id_kendaraan]);
                if ($check->fetchColumn() > 0) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => "Plat nomor $plat_nomor sudah digunakan oleh kendaraan lain."]);
                    exit;
                }

                $stmt = $conn->prepare("UPDATE kendaraan 
                                        SET plat_nomor = :plat, jenis_kendaraan = :jenis, merk = :merk, warna = :warna, pemilik = :pemilik 
                                        WHERE id_kendaraan = :id");
                $stmt->execute([
                    ':plat' => $plat_nomor,
                    ':jenis' => $jenis_kendaraan,
                    ':merk' => $merk,
                    ':warna' => $warna,
                    ':pemilik' => $pemilik,
                    ':id' => $id_kendaraan
                ]);

                // Log activity
                $id_user = $_SESSION['id_user'];
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $id_user,
                    ':act' => "Memperbarui detail kendaraan $plat_nomor"
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Detail kendaraan berhasil diperbarui.']);
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($postAction === 'delete') {
            $id_kendaraan = intval($data['id_kendaraan']);

            if (empty($id_kendaraan)) {
                echo json_encode(['success' => false, 'message' => 'ID Kendaraan tidak ditemukan.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // Check constraint
                $check = $conn->prepare("SELECT COUNT(*) FROM transaksi_parkir WHERE id_kendaraan = :id");
                $check->execute([':id' => $id_kendaraan]);
                if ($check->fetchColumn() > 0) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Gagal menghapus! Kendaraan ini memiliki riwayat transaksi parkir.']);
                    exit;
                }

                // Get plat_nomor
                $getPlat = $conn->prepare("SELECT plat_nomor FROM kendaraan WHERE id_kendaraan = :id");
                $getPlat->execute([':id' => $id_kendaraan]);
                $plat_nomor = $getPlat->fetchColumn();

                $stmt = $conn->prepare("DELETE FROM kendaraan WHERE id_kendaraan = :id");
                $stmt->execute([':id' => $id_kendaraan]);

                // Log activity
                $id_user = $_SESSION['id_user'];
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $id_user,
                    ':act' => "Menghapus kendaraan $plat_nomor"
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Kendaraan berhasil dihapus.']);
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
        <h1>Data Kendaraan</h1>
        <p>Kelola data kendaraan yang terdaftar di kawasan GBK</p>
    </div>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fas fa-plus"></i> Tambah Kendaraan
    </button>
</div>

<!-- Table Container -->
<div class="table-card mt-20">
    <div class="table-actions-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchVehicle" placeholder="Cari Plat Nomor, Merek, atau Pemilik...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>NO</th>
                    <th>PLAT NOMOR</th>
                    <th>JENIS</th>
                    <th>MEREK</th>
                    <th>WARNA</th>
                    <th>PEMILIK</th>
                    <th>STATUS</th>
                    <th style="width: 120px;">AKSI</th>
                </tr>
            </thead>
            <tbody id="tableKendaraan">
                <!-- Loaded dynamically by AJAX -->
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper" id="paginationWrapper">
        <!-- Loaded dynamically by AJAX -->
    </div>
</div>

<!-- MODAL: ADD / EDIT VEHICLE -->
<div class="modal-overlay" id="modalVehicle">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Kendaraan</h3>
            <button class="btn-close-modal" onclick="closeVehicleModal()">&times;</button>
        </div>
        <form id="formVehicle">
            <input type="hidden" name="id_kendaraan" id="id_kendaraan">
            <div class="modal-body">
                <div class="form-group">
                    <label for="plat_nomor">Plat Nomor</label>
                    <input type="text" name="plat_nomor" id="plat_nomor" class="form-control" placeholder="Contoh: B 1234 ABC" required>
                </div>
                <div class="form-group">
                    <label for="jenis_kendaraan">Jenis Kendaraan</label>
                    <select name="jenis_kendaraan" id="jenis_kendaraan" class="form-control" required>
                        <option value="Mobil" selected>Mobil</option>
                        <option value="Motor">Motor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="merk">Merek / Merk</label>
                    <input type="text" name="merk" id="merk" class="form-control" placeholder="Contoh: Toyota Avanza" required>
                </div>
                <div class="form-group">
                    <label for="warna">Warna</label>
                    <input type="text" name="warna" id="warna" class="form-control" placeholder="Contoh: Hitam" required>
                </div>
                <div class="form-group">
                    <label for="pemilik">Nama Pemilik</label>
                    <input type="text" name="pemilik" id="pemilik" class="form-control" placeholder="Contoh: Ahmad Fauzi" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeVehicleModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: DETAIL VEHICLE -->
<div class="modal-overlay" id="modalDetailVehicle">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Detail Kendaraan</h3>
            <button class="btn-close-modal" onclick="closeDetailModal()">&times;</button>
        </div>
        <div class="modal-body" style="display: flex; flex-direction: column; gap: 14px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Plat Nomor</span>
                <span id="detail-plat_nomor" style="font-weight: 600; color: var(--primary);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Jenis Kendaraan</span>
                <span id="detail-jenis_kendaraan" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Merek/Tipe</span>
                <span id="detail-merk" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Warna</span>
                <span id="detail-warna" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Nama Pemilik</span>
                <span id="detail-pemilik" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Status</span>
                <span id="detail-status" class="badge"></span>
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
    loadVehicles();

    // Live search
    document.getElementById("searchVehicle").addEventListener("input", () => {
        currentPage = 1;
        loadVehicles();
    });

    // Form submit
    document.getElementById("formVehicle").addEventListener("submit", function(e) {
        e.preventDefault();
        submitVehicle();
    });
});

function loadVehicles() {
    const search = document.getElementById("searchVehicle").value;
    fetch(`kendaraan.php?ajax=1&action=list&search=${encodeURIComponent(search)}&page=${currentPage}`)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const tbody = document.getElementById("tableKendaraan");
            tbody.innerHTML = "";
            
            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center" style="color: var(--text-secondary); padding: 30px;">Tidak ada data kendaraan.</td></tr>`;
                document.getElementById("paginationWrapper").innerHTML = "";
                return;
            }

            let startNo = (res.pagination.page - 1) * res.pagination.limit + 1;
            res.data.forEach(veh => {
                const badgeClass = veh.status === 'Parkir' ? 'info' : 'secondary';
                tbody.innerHTML += `
                    <tr>
                        <td>${startNo++}</td>
                        <td style="font-weight: 600; color: var(--primary);">${escapeHtml(veh.plat_nomor)}</td>
                        <td>${escapeHtml(veh.jenis_kendaraan)}</td>
                        <td>${escapeHtml(veh.merk)}</td>
                        <td>${escapeHtml(veh.warna)}</td>
                        <td>${escapeHtml(veh.pemilik)}</td>
                        <td><span class="badge badge-${badgeClass}">${escapeHtml(veh.status)}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon detail" title="Detail" onclick="viewDetail(${JSON.stringify(veh).replace(/"/g, '&quot;')})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-icon edit" title="Edit" onclick="editVehicle(${JSON.stringify(veh).replace(/"/g, '&quot;')})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon delete" title="Delete" onclick="deleteVehicle(${veh.id_kendaraan})">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            renderPagination(res.pagination);
        } else {
            showToast("danger", "Gagal", "Gagal mengambil data kendaraan.");
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
    loadVehicles();
}

function openAddModal() {
    document.getElementById("formVehicle").reset();
    document.getElementById("id_kendaraan").value = "";
    document.getElementById("modalTitle").textContent = "Tambah Kendaraan";
    document.getElementById("modalVehicle").classList.add("show");
}

function closeVehicleModal() {
    document.getElementById("modalVehicle").classList.remove("show");
}

function editVehicle(veh) {
    document.getElementById("id_kendaraan").value = veh.id_kendaraan;
    document.getElementById("plat_nomor").value = veh.plat_nomor;
    document.getElementById("jenis_kendaraan").value = veh.jenis_kendaraan;
    document.getElementById("merk").value = veh.merk;
    document.getElementById("warna").value = veh.warna;
    document.getElementById("pemilik").value = veh.pemilik;

    document.getElementById("modalTitle").textContent = "Edit Kendaraan";
    document.getElementById("modalVehicle").classList.add("show");
}

function submitVehicle() {
    const idKendaraan = document.getElementById("id_kendaraan").value;
    const action = idKendaraan ? 'edit' : 'add';

    const payload = {
        action: action,
        id_kendaraan: idKendaraan ? parseInt(idKendaraan) : null,
        plat_nomor: document.getElementById("plat_nomor").value,
        jenis_kendaraan: document.getElementById("jenis_kendaraan").value,
        merk: document.getElementById("merk").value,
        warna: document.getElementById("warna").value,
        pemilik: document.getElementById("pemilik").value
    };

    fetch('kendaraan.php?ajax=1', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            closeVehicleModal();
            loadVehicles();
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

function viewDetail(veh) {
    document.getElementById("detail-plat_nomor").textContent = veh.plat_nomor;
    document.getElementById("detail-jenis_kendaraan").textContent = veh.jenis_kendaraan;
    document.getElementById("detail-merk").textContent = veh.merk;
    document.getElementById("detail-warna").textContent = veh.warna;
    document.getElementById("detail-pemilik").textContent = veh.pemilik;
    
    const statusEl = document.getElementById("detail-status");
    statusEl.textContent = veh.status;
    statusEl.className = 'badge';
    if (veh.status === 'Parkir') {
        statusEl.classList.add('badge-info');
    } else {
        statusEl.classList.add('badge-secondary');
    }

    document.getElementById("modalDetailVehicle").classList.add("show");
}

function closeDetailModal() {
    document.getElementById("modalDetailVehicle").classList.remove("show");
}

function deleteVehicle(id) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus data kendaraan ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#9E3A3A',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('kendaraan.php?ajax=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'delete', id_kendaraan: id })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    loadVehicles();
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