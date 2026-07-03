<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit;
}

$_petugas_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : 'petugas';

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
                $whereClause = "WHERE u.nama_lengkap ILIKE :search OR u.username ILIKE :search OR p.no_hp ILIKE :search";
                $params[':search'] = '%' . $search . '%';
            }

            // Count total
            $countSql = "SELECT COUNT(*) FROM petugas p JOIN users u ON u.id_user = p.id_user $whereClause";
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $totalData = $countStmt->fetchColumn();

            // Fetch list
            $sql = "SELECT p.id_petugas, p.id_user, u.nama_lengkap, u.username, p.no_hp, p.shift 
                    FROM petugas p 
                    JOIN users u ON u.id_user = p.id_user
                    $whereClause
                    ORDER BY p.id_petugas DESC
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $officers,
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
            // Hak akses: petugas tidak bisa tambah petugas
            if ($_petugas_role !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya admin yang dapat menambahkan petugas.']);
                exit;
            }

            $nama_lengkap = trim($data['nama_lengkap']);
            $username = trim($data['username']);
            $password = $data['password'];
            $no_hp = trim($data['no_hp']);
            // Fix bug shift Siang: trim dan normalize whitespace
            $shift = trim(preg_replace('/\s+/', ' ', $data['shift']));

            if (empty($nama_lengkap) || empty($username) || empty($password) || empty($shift)) {
                echo json_encode(['success' => false, 'message' => 'Nama lengkap, username, password, dan shift wajib diisi.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // Check duplicate username in users
                $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :uname");
                $check->execute([':uname' => $username]);
                if ($check->fetchColumn() > 0) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => "Username @$username sudah digunakan."]);
                    exit;
                }

                // 1. Insert into users
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmtUser = $conn->prepare("
                INSERT INTO users (
                    nama_lengkap,
                    username,
                    password,
                    password_plain,
                    role
                )
                VALUES (
                    :nama,
                    :username,
                    :password,
                    :plain,
                    'petugas'
                )
                ");

                $stmtUser->execute([
                    ':nama'     => $nama_lengkap,
                    ':username' => $username,
                    ':password' => $hash,
                    ':plain'    => $password
                ]);
                $id_user = $conn->lastInsertId();

                // 2. Insert into petugas
                $stmtPetugas = $conn->prepare(" INSERT INTO petugas (id_user, no_hp, shift) 
                                                VALUES (:uid, :hp, :shift)");
                $stmtPetugas->execute([
                    ':uid' => $id_user,
                    ':hp' => $no_hp,
                    ':shift' => $shift
                ]);

                // Log activity
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $_SESSION['id_user'],
                    ':act' => "Menambahkan petugas baru: $nama_lengkap (@$username)"
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Petugas baru berhasil ditambahkan.']);
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($postAction === 'edit') {
            $id_petugas = intval($data['id_petugas']);
            $id_user = intval($data['id_user']);
            $nama_lengkap = trim($data['nama_lengkap']);
            $username = trim($data['username']);
            $no_hp = trim($data['no_hp']);
            // Shift hanya boleh diubah oleh admin
            $shift = ($_petugas_role === 'admin')
                ? trim(preg_replace('/\s+/', ' ', $data['shift']))
                : null;

            if (empty($id_petugas) || empty($id_user) || empty($nama_lengkap) || empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap untuk diperbarui.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // Check duplicate username
                $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :uname AND id_user != :id");
                $check->execute([':uname' => $username, ':id' => $id_user]);
                if ($check->fetchColumn() > 0) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => "Username @$username sudah digunakan oleh pengguna lain."]);
                    exit;
                }

                // 1. Update users
                $stmtUser = $conn->prepare("
                    UPDATE users
                    SET
                        nama_lengkap = :nama,
                        username = :username
                    WHERE id_user = :id
                ");
                $stmtUser->execute([
                    ':nama' => $nama_lengkap,
                    ':username' => $username,
                    ':id' => $id_user
                ]);

                // 2. Update petugas
                if ($_petugas_role === 'admin') {
                    $stmtPetugas = $conn->prepare("
                        UPDATE petugas
                        SET
                            no_hp = :hp,
                            shift = :shift
                        WHERE id_petugas = :id
                    ");
                    $stmtPetugas->execute([
                        ':hp' => $no_hp,
                        ':shift' => $shift,
                        ':id' => $id_petugas
                    ]);
                } else {
                    $stmtPetugas = $conn->prepare("
                        UPDATE petugas
                        SET
                            no_hp = :hp
                        WHERE id_petugas = :id
                    ");
                    $stmtPetugas->execute([
                        ':hp' => $no_hp,
                        ':id' => $id_petugas
                    ]);
                }

                // Log activity
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $_SESSION['id_user'],
                    ':act' => "Memperbarui data petugas $nama_lengkap"
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Data petugas berhasil diperbarui.']);
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($postAction === 'delete') {
            $id_petugas = intval($data['id_petugas']);
            $id_user = intval($data['id_user']);

            if (empty($id_petugas) || empty($id_user)) {
                echo json_encode(['success' => false, 'message' => 'Data petugas tidak lengkap.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // Check constraint with transaksi_parkir
                $check = $conn->prepare("SELECT COUNT(*) FROM transaksi_parkir WHERE id_petugas = :id");
                $check->execute([':id' => $id_petugas]);
                if ($check->fetchColumn() > 0) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Gagal menghapus! Petugas ini memiliki riwayat pencatatan transaksi parkir.']);
                    exit;
                }

                // Get name for logging
                $getName = $conn->prepare("SELECT nama_lengkap FROM users WHERE id_user = :id");
                $getName->execute([':id' => $id_user]);
                $nama_lengkap = $getName->fetchColumn();

                // 1. Delete from petugas
                $stmtPetugas = $conn->prepare("DELETE FROM petugas WHERE id_petugas = :id");
                $stmtPetugas->execute([':id' => $id_petugas]);

                // 2. Delete from users
                $stmtUser = $conn->prepare("DELETE FROM users WHERE id_user = :id");
                $stmtUser->execute([':id' => $id_user]);

                // Log activity
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $_SESSION['id_user'],
                    ':act' => "Menghapus petugas $nama_lengkap"
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Petugas berhasil dihapus.']);
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
        <h1>Data Petugas</h1>
        <p>Kelola data petugas parkir dan shift kerja di kawasan GBK</p>
    </div>
    <?php if ($_petugas_role === 'admin'): ?>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fas fa-plus"></i> Tambah Petugas
    </button>
    <?php endif; ?>
</div>

<!-- Table Container -->
<div class="table-card mt-20">
    <div class="table-actions-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchPetugas" placeholder="Cari Nama Petugas, Username, HP...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA PETUGAS</th>
                    <th>USERNAME</th>
                    <th>NO HP</th>
                    <th>SHIFT</th>
                    <th style="width: 120px;">AKSI</th>
                </tr>
            </thead>
            <tbody id="tablePetugas">
                <!-- Loaded dynamically by AJAX -->
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper" id="paginationWrapper">
        <!-- Loaded dynamically by AJAX -->
    </div>
</div>

<!-- MODAL: TAMBAH PETUGAS (admin only) -->
<div class="modal-overlay" id="modalPetugas">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Petugas</h3>
            <button class="btn-close-modal" onclick="closePetugasModal()">&times;</button>
        </div>
        <form id="formPetugas">
            <input type="hidden" name="id_petugas" id="id_petugas">
            <input type="hidden" name="id_user" id="id_user">
            <div class="modal-body">
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" placeholder="Contoh: Hendra Wijaya" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Contoh: hendraw" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password untuk petugas baru" required>
                    <small style="color: var(--text-secondary);">Password wajib diisi untuk petugas baru.</small>
                </div>
                <div class="form-group">
                    <label for="no_hp">Nomor Handphone</label>
                    <input type="text" name="no_hp" id="no_hp" class="form-control" placeholder="Contoh: 08123456789">
                </div>
                <div class="form-group">
                    <label for="shift">Shift Kerja</label>
                    <select name="shift" id="shift" class="form-control" required>
                        <option value="Pagi" selected>Pagi (06:00 - 14:00)</option>
                        <option value="Siang">Siang (14:00 - 22:00)</option>
                        <option value="Malam">Malam (22:00 - 06:00)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePetugasModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDIT PETUGAS (tanpa field password, admin only) -->
<div class="modal-overlay" id="modalEditPetugas">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Edit Petugas</h3>
            <button class="btn-close-modal" onclick="closeEditPetugasModal()">&times;</button>
        </div>
        <form id="formEditPetugas">
            <input type="hidden" name="id_petugas" id="edit_id_petugas">
            <input type="hidden" name="id_user" id="edit_id_user">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_nama_lengkap">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-control" placeholder="Contoh: Hendra Wijaya" required>
                </div>
                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" name="username" id="edit_username" class="form-control" placeholder="Contoh: hendraw" required>
                </div>
                <div class="form-group">
                    <label for="edit_no_hp">Nomor Handphone</label>
                    <input type="text" name="no_hp" id="edit_no_hp" class="form-control" placeholder="Contoh: 08123456789">
                </div>
                <?php if ($_petugas_role === 'admin'): ?>
                <div class="form-group">
                    <label for="edit_shift">Shift Kerja</label>
                    <select name="shift" id="edit_shift" class="form-control" required>
                        <option value="Pagi">Pagi (06:00 - 14:00)</option>
                        <option value="Siang">Siang (14:00 - 22:00)</option>
                        <option value="Malam">Malam (22:00 - 06:00)</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditPetugasModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitEditPetugas()">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: DETAIL OFFICIER -->
<div class="modal-overlay" id="modalDetailPetugas">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Detail Petugas</h3>
            <button class="btn-close-modal" onclick="closeDetailModal()">&times;</button>
        </div>
        <div class="modal-body" style="display: flex; flex-direction: column; gap: 14px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Nama Lengkap</span>
                <span id="detail-nama" style="font-weight: 600; color: var(--primary);"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Username</span>
                <span id="detail-username" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">No Handphone</span>
                <span id="detail-no_hp" style="font-weight: 600;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Shift Kerja</span>
                <span id="detail-shift" class="badge badge-info"></span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
        </div>
    </div>
</div>

<script>
const _phpRole = "<?= $_petugas_role ?>";

let currentPage = 1;

document.addEventListener("DOMContentLoaded", () => {
    loadPetugas();

    // Live search
    document.getElementById("searchPetugas").addEventListener("input", () => {
        currentPage = 1;
        loadPetugas();
    });

    // Form submit
    document.getElementById("formPetugas").addEventListener("submit", function(e) {
        e.preventDefault();
        submitPetugas();
    });
});

function loadPetugas() {
    const search = document.getElementById("searchPetugas").value;
    fetch(`petugas.php?ajax=1&action=list&search=${encodeURIComponent(search)}&page=${currentPage}`)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const tbody = document.getElementById("tablePetugas");
            tbody.innerHTML = "";
            
            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center" style="color: var(--text-secondary); padding: 30px;">Tidak ada data petugas.</td></tr>`;
                document.getElementById("paginationWrapper").innerHTML = "";
                return;
            }

            let startNo = (res.pagination.page - 1) * res.pagination.limit + 1;
            // Cek role dari PHP yang di-embed ke JS
            const _isAdmin = _phpRole === 'admin';
            res.data.forEach(p => {
                const editBtn = _isAdmin
                    ? `<button class="btn-icon edit" title="Edit" onclick="editPetugas(${JSON.stringify(p).replace(/"/g, '&quot;')})"><i class="fas fa-edit"></i></button>`
                    : `<button class="btn-icon edit" style="opacity:0.3;cursor:not-allowed;" title="Hanya admin" disabled><i class="fas fa-edit"></i></button>`;
                const deleteBtn = _isAdmin
                    ? `<button class="btn-icon delete" title="Delete" onclick="deletePetugas(${p.id_petugas}, ${p.id_user})"><i class="fas fa-trash-alt"></i></button>`
                    : `<button class="btn-icon delete" style="opacity:0.3;cursor:not-allowed;" title="Hanya admin" disabled><i class="fas fa-trash-alt"></i></button>`;

                tbody.innerHTML += `
                    <tr>
                        <td>${startNo++}</td>
                        <td style="font-weight: 600; color: var(--primary);">${escapeHtml(p.nama_lengkap)}</td>
                        <td>@${escapeHtml(p.username)}</td>
                        <td>${p.no_hp ? escapeHtml(p.no_hp) : '-'}</td>
                        <td><span class="badge badge-info" style="text-transform: none;">Shift ${escapeHtml(p.shift)}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon detail" title="Detail" onclick="viewDetail(${JSON.stringify(p).replace(/"/g, '&quot;')})"><i class="fas fa-eye"></i></button>
                                ${editBtn}
                                ${deleteBtn}
                            </div>
                        </td>
                    </tr>
                `;
            });

            renderPagination(res.pagination);
        } else {
            showToast("danger", "Gagal", "Gagal mengambil data petugas.");
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
    loadPetugas();
}

function openAddModal() {
    document.getElementById("formPetugas").reset();
    document.getElementById("id_petugas").value = "";
    document.getElementById("id_user").value = "";
    document.getElementById("modalTitle").textContent = "Tambah Petugas";
    document.getElementById("password").required = true;
    document.getElementById("modalPetugas").classList.add("show");
}

function closePetugasModal() {
    document.getElementById("modalPetugas").classList.remove("show");
}

function editPetugas(p) {
    document.getElementById("edit_id_petugas").value = p.id_petugas;
    document.getElementById("edit_id_user").value = p.id_user;
    document.getElementById("edit_nama_lengkap").value = p.nama_lengkap;
    document.getElementById("edit_username").value = p.username;
    document.getElementById("edit_no_hp").value = p.no_hp || '';

    const shift = document.getElementById("edit_shift");
    if (shift) {
        shift.value = p.shift;
    }
    document.getElementById("modalEditPetugas").classList.add("show");
}

function closeEditPetugasModal() {
    document.getElementById("formEditPetugas").reset();
    document.getElementById("modalEditPetugas").classList.remove("show");
}

function submitPetugas() {
    // Digunakan untuk TAMBAH saja
    const payload = {
        action:       'add',
        id_petugas:   null,
        id_user:      null,
        nama_lengkap: document.getElementById("nama_lengkap").value,
        username:     document.getElementById("username").value,
        password:     document.getElementById("password").value,
        no_hp:        document.getElementById("no_hp").value,
        shift:        document.getElementById("shift").value
    };

    fetch('petugas.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            closePetugasModal();
            loadPetugas();
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

function submitEditPetugas() {
    // Digunakan untuk EDIT saja — tanpa password
    const payload = {
        action:       'edit',
        id_petugas:   parseInt(document.getElementById("edit_id_petugas").value),
        id_user:      parseInt(document.getElementById("edit_id_user").value),
        nama_lengkap: document.getElementById("edit_nama_lengkap").value,
        username:     document.getElementById("edit_username").value,
        password:     '',   // kosong = tidak ubah password di backend
        no_hp:        document.getElementById("edit_no_hp").value,
        shift: document.getElementById("edit_shift")
            ? document.getElementById("edit_shift").value
            : null
    };

    fetch('petugas.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            closeEditPetugasModal();
            loadPetugas();
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

function viewDetail(p) {
    document.getElementById("detail-nama").textContent = p.nama_lengkap;
    document.getElementById("detail-username").textContent = '@' + p.username;
    document.getElementById("detail-no_hp").textContent = p.no_hp ? p.no_hp : '-';
    
    const shiftEl = document.getElementById("detail-shift");
    shiftEl.textContent = 'Shift ' + p.shift;

    document.getElementById("modalDetailPetugas").classList.add("show");
}

function closeDetailModal() {
    document.getElementById("modalDetailPetugas").classList.remove("show");
}

function deletePetugas(idPetugas, idUser) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus petugas ini? Akun login user juga akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#9E3A3A',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('petugas.php?ajax=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'delete', id_petugas: idPetugas, id_user: idUser })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    loadPetugas();
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