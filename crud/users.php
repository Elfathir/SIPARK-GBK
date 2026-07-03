<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Hak akses: hanya admin
$_role_check = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'petugas';
if ($_role_check !== 'admin') {
    // Redirect petugas ke halaman akses ditolak atau dashboard
    $_SESSION['access_denied'] = true;
    header("Location: ../dashboard/dashboard.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

// ── Auto-migrate: tambah kolom password_plain jika belum ada ──
try {
    $conn->exec("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM information_schema.columns
                WHERE table_name = 'users' AND column_name = 'password_plain'
            ) THEN
                ALTER TABLE users ADD COLUMN password_plain VARCHAR(255) DEFAULT NULL;
            END IF;
        END
        $$;
    ");
} catch (Exception $_e) { /* abaikan jika kolom sudah ada */ }

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
                $whereClause = "WHERE nama_lengkap ILIKE :search OR username ILIKE :search OR role ILIKE :search";
                $params[':search'] = '%' . $search . '%';
            }

            // Count total
            $countSql = "SELECT COUNT(*) FROM users $whereClause";
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $totalData = $countStmt->fetchColumn();

            // Fetch list
            $sql = "SELECT id_user, nama_lengkap, username, role, created_at, password_plain
                    FROM users 
                    $whereClause
                    ORDER BY id_user DESC
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $users,
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

        // Action 'add' telah dihapus - tidak ada fitur tambah user
        if ($postAction === 'add') {
            echo json_encode(['success' => false, 'message' => 'Fitur tambah pengguna tidak tersedia.']);
            exit;
        }

        if ($postAction === 'edit') {
            $id_user_edit = intval($data['id_user']);
            $nama_lengkap = trim($data['nama_lengkap']);
            $username = trim($data['username']);
            $password = $data['password'];
            $role = trim($data['role']);

            if (empty($id_user_edit) || empty($nama_lengkap) || empty($username) || empty($role)) {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap untuk diperbarui.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // Prevent changing primary administrator username or role
                if ($id_user_edit === 1) {
                    $username = 'admin'; // Lock username
                    $role = 'admin';     // Lock role
                }

                // Check duplicate username
                $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :uname AND id_user != :id");
                $check->execute([':uname' => $username, ':id' => $id_user_edit]);
                if ($check->fetchColumn() > 0) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => "Username @$username sudah digunakan oleh pengguna lain."]);
                    exit;
                }

                // Update users
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET nama_lengkap = :nama, username = :username, password = :password, password_plain = :plain, role = :role WHERE id_user = :id");
                    $stmt->execute([
                        ':nama'     => $nama_lengkap,
                        ':username' => $username,
                        ':password' => $hash,
                        ':plain'    => $password,
                        ':role'     => $role,
                        ':id'       => $id_user_edit
                    ]);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET nama_lengkap = :nama, username = :username, role = :role WHERE id_user = :id");
                    $stmt->execute([
                        ':nama'     => $nama_lengkap,
                        ':username' => $username,
                        ':role'     => $role,
                        ':id'       => $id_user_edit
                    ]);
                }

                // Jika role menjadi admin, hapus data dari tabel petugas
                if ($role === 'admin') {
                    $stmtDelete = $conn->prepare("
                        DELETE FROM petugas
                        WHERE id_user = :id
                    ");
                    $stmtDelete->execute([
                        ':id' => $id_user_edit
                    ]);
                }

                // Log activity
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $_SESSION['id_user'],
                    ':act' => "Memperbarui data pengguna $nama_lengkap"
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Data pengguna berhasil diperbarui.']);
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($postAction === 'delete') {
            $id_user_delete = intval($data['id_user']);

            if (empty($id_user_delete)) {
                echo json_encode(['success' => false, 'message' => 'ID Pengguna tidak ditemukan.']);
                exit;
            }

            if ($id_user_delete === 1) {
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus! Administrator utama tidak boleh dihapus.']);
                exit;
            }

            if ($id_user_delete === $_SESSION['id_user']) {
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus! Anda tidak bisa menghapus akun Anda sendiri yang sedang aktif digunakan.']);
                exit;
            }

            try {
                $conn->beginTransaction();

                // Check constraints
                // 1. Check if linked to petugas who has transactions
                $checkTx = $conn->prepare(" SELECT COUNT(*) FROM transaksi_parkir tp 
                                            JOIN petugas p ON p.id_petugas = tp.id_petugas 
                                            WHERE p.id_user = :uid");
                $checkTx->execute([':uid' => $id_user_delete]);
                if ($checkTx->fetchColumn() > 0) {
                    $conn->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Gagal menghapus! Pengguna petugas ini memiliki riwayat transaksi parkir.']);
                    exit;
                }

                // Get name for logging
                $getName = $conn->prepare("SELECT nama_lengkap FROM users WHERE id_user = :id");
                $getName->execute([':id' => $id_user_delete]);
                $nama_lengkap = $getName->fetchColumn();

                // 2. Delete from petugas first if exists
                $stmtPetugas = $conn->prepare("DELETE FROM petugas WHERE id_user = :id");
                $stmtPetugas->execute([':id' => $id_user_delete]);

                // 3. Delete from log_aktivitas first if exists
                $stmtLog = $conn->prepare("DELETE FROM log_aktivitas WHERE id_user = :id");
                $stmtLog->execute([':id' => $id_user_delete]);

                // 4. Delete from users
                $stmt = $conn->prepare("DELETE FROM users WHERE id_user = :id");
                $stmt->execute([':id' => $id_user_delete]);

                // Log activity
                $logStmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES (:uid, :act, NOW())");
                $logStmt->execute([
                    ':uid' => $_SESSION['id_user'],
                    ':act' => "Menghapus pengguna $nama_lengkap"
                ]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Pengguna berhasil dihapus.']);
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
        <h1> Manajemen Pengguna </h1>
        <p> Kelola data akun pengguna sistem (Administrator &amp; Petugas) </p>
    </div>
    <!-- Tombol Tambah User dihapus -->
</div>

<!-- Table Container -->
<div class="table-card mt-20">
    <div class="table-actions-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchUser" placeholder="Cari Nama Pengguna, Username, atau Hak Akses...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA PENGGUNA</th>
                    <th>USERNAME</th>
                    <th>HAK AKSES</th>
                    <th>TANGGAL TERDAFTAR</th>
                    <th style="width: 120px;">AKSI</th>
                </tr>
            </thead>
            <tbody id="tableUsers">
                <!-- Loaded dynamically by AJAX -->
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper" id="paginationWrapper">
        <!-- Loaded dynamically by AJAX -->
    </div>
</div>

<!-- Modal Edit User (hanya modal edit, tambah dihapus) -->
<div class="modal-overlay" id="modalUser">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Pengguna</h3>
            <button class="btn-close-modal" onclick="closeUserModal()">&times;</button>
        </div>
        <form id="formUser">
            <input type="hidden" name="id_user" id="id_user">
            <div class="modal-body">
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" placeholder="Contoh: Administrator" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Contoh: admin" required>
                </div>

                <div class="form-group">
                    <label for="role">Hak Akses (Role)</label>
                    <select name="role" id="role" class="form-control" required>
                        <option value="admin" selected>Administrator</option>
                        <option value="petugas">Petugas</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: DETAIL USER -->
<div class="modal-overlay" id="modalDetailUser">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Detail Pengguna</h3>
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
            <!-- Baris Password dengan toggle mata -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Password</span>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span id="detail-password-mask" style="font-weight: 600; font-family: monospace; letter-spacing: 2px;">••••••••</span>
                    <span id="detail-password-text" style="font-weight: 600; display: none;"></span>
                    <button type="button" id="btn-toggle-password"
                        onclick="toggleDetailPassword()"
                        style="background: transparent; border: none; cursor: pointer; color: var(--text-secondary); font-size: 15px; padding: 2px 4px; transition: color .2s;"
                        title="Tampilkan/Sembunyikan Password">
                        <i class="fas fa-eye" id="icon-toggle-pass"></i>
                    </button>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Hak Akses</span>
                <span id="detail-role" class="badge"></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 8px;">
                <span style="color: var(--text-secondary); font-size: 13px;">Tanggal Registrasi</span>
                <span id="detail-created_at" style="font-weight: 600;"></span>
            </div>
        </div>

        <!-- Form Reset Password (hidden by default) -->
        <div id="resetPwForm" style="display:none; padding: 16px 24px; border-top: 1px solid var(--border-color); background: #FFF9F9; border-radius: 0 0 var(--border-radius) var(--border-radius);">
            <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px;">
                <i class="fas fa-key" style="color: var(--warning);"></i>
                &nbsp;Masukkan password baru untuk pengguna ini:
            </p>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" id="newPasswordInput" class="form-control"
                    placeholder="Password baru..."
                    style="flex:1; font-size:14px;"
                    autocomplete="new-password">
                <button type="button" class="btn btn-primary" onclick="submitResetPassword()" style="white-space:nowrap;">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <button type="button" class="btn btn-secondary" onclick="cancelResetPassword()">
                    Batal
                </button>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-warning" onclick="showResetPasswordForm()" style="color:#fff;">
                <i class="fas fa-key"></i> Reset Password
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const currentSessionUserId = <?= $_SESSION['id_user'] ?>;

document.addEventListener("DOMContentLoaded", () => {
    loadUsers();

    // Live search
    document.getElementById("searchUser").addEventListener("input", () => {
        currentPage = 1;
        loadUsers();
    });

    // Form submit (edit only)
    document.getElementById("formUser").addEventListener("submit", function(e) {
        e.preventDefault();
        submitUser();
    });
});

function loadUsers() {
    const search = document.getElementById("searchUser").value;
    fetch(`users.php?ajax=1&action=list&search=${encodeURIComponent(search)}&page=${currentPage}`)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const tbody = document.getElementById("tableUsers");
            tbody.innerHTML = "";
            
            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center" style="color: var(--text-secondary); padding: 30px;">Tidak ada data pengguna.</td></tr>`;
                document.getElementById("paginationWrapper").innerHTML = "";
                return;
            }

            let startNo = (res.pagination.page - 1) * res.pagination.limit + 1;
            res.data.forEach(u => {
                const roleClass = u.role === 'admin' ? 'success' : 'info';
                const createdDate = u.created_at ? new Date(u.created_at).toLocaleString('id-ID') : '-';
                
                // Hide delete button for primary admin (id_user=1) or current user themselves
                const deleteButtonHtml = (u.id_user === 1 || u.id_user === currentSessionUserId)
                    ? `<button class="btn-icon delete" style="opacity: 0.3; cursor: not-allowed;" title="Tidak dapat dihapus" disabled><i class="fas fa-trash-alt"></i></button>`
                    : `<button class="btn-icon delete" title="Delete" onclick="deleteUser(${u.id_user})"><i class="fas fa-trash-alt"></i></button>`;

                tbody.innerHTML += `
                    <tr>
                        <td>${startNo++}</td>
                        <td style="font-weight: 600; color: var(--primary);">${escapeHtml(u.nama_lengkap)}</td>
                        <td>@${escapeHtml(u.username)}</td>
                        <td><span class="badge badge-${roleClass}">${escapeHtml(u.role === 'admin' ? 'Administrator' : 'Petugas')}</span></td>
                        <td>${createdDate}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon detail" title="Detail" onclick="viewDetail(${JSON.stringify(u).replace(/"/g, '&quot;')})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-icon edit" title="Edit" onclick="editUser(${JSON.stringify(u).replace(/"/g, '&quot;')})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${deleteButtonHtml}
                            </div>
                        </td>
                    </tr>
                `;
            });

            renderPagination(res.pagination);
        } else {
            showToast("danger", "Gagal", "Gagal mengambil data pengguna.");
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
    loadUsers();
}

// openAddModal dihapus - fitur tambah user tidak tersedia

function closeUserModal() {
    document.getElementById("modalUser").classList.remove("show");
}

function editUser(u) {
    document.getElementById("id_user").value = u.id_user;
    document.getElementById("nama_lengkap").value = u.nama_lengkap;
    document.getElementById("username").value = u.username;
    document.getElementById("role").value = u.role;


    // Lock role and username if primary admin
    if (u.id_user === 1) {
        document.getElementById("role").disabled = true;
        document.getElementById("username").disabled = true;
    } else {
        document.getElementById("role").disabled = false;
        document.getElementById("username").disabled = false;
    }

    document.getElementById("modalTitle").textContent = "Edit Pengguna";
    document.getElementById("modalUser").classList.add("show");
}

function submitUser() {
    const idUser = document.getElementById("id_user").value;
    const action = idUser ? 'edit' : 'add';

    const payload = {
        action: 'edit',
        id_user: parseInt(idUser),
        nama_lengkap: document.getElementById("nama_lengkap").value,
        username: document.getElementById("username").value,
        password: '',
        role: document.getElementById("role").value
    };

    fetch('users.php?ajax=1', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            closeUserModal();
            loadUsers();
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

function viewDetail(u) {
    document.getElementById("detail-nama").textContent = u.nama_lengkap;
    document.getElementById("detail-username").textContent = '@' + u.username;

    // Password
    const passText = document.getElementById("detail-password-text");
    const passMask = document.getElementById("detail-password-mask");
    const passIcon = document.getElementById("icon-toggle-pass");
    const plainPw  = u.password_plain && u.password_plain.trim() !== '' ? u.password_plain : null;

    passText.textContent = plainPw || '(belum diatur via sistem)';
    passMask.style.display = 'inline';
    passText.style.display  = 'none';
    if (passIcon) {
        passIcon.className = 'fas fa-eye';
    }

    // Simpan id user aktif untuk fitur reset password
    _detailUserId = u.id_user;

    const roleEl = document.getElementById("detail-role");
    roleEl.textContent = u.role === 'admin' ? 'Administrator' : 'Petugas';
    roleEl.className = 'badge';
    if (u.role === 'admin') {
        roleEl.classList.add('badge-success');
    } else {
        roleEl.classList.add('badge-info');
    }
    document.getElementById("detail-created_at").textContent = u.created_at ? new Date(u.created_at).toLocaleString('id-ID') : '-';
    document.getElementById("modalDetailUser").classList.add("show");
}

function toggleDetailPassword() {
    const passText = document.getElementById("detail-password-text");
    const passMask = document.getElementById("detail-password-mask");
    const icon     = document.getElementById("icon-toggle-pass");
    const isHidden = passMask.style.display !== 'none';

    if (isHidden) {
        // Tampilkan
        passMask.style.display = 'none';
        passText.style.display  = 'inline';
        icon.className = 'fas fa-eye-slash';
    } else {
        // Sembunyikan
        passMask.style.display = 'inline';
        passText.style.display  = 'none';
        icon.className = 'fas fa-eye';
    }
}

function closeDetailModal() {
    document.getElementById("modalDetailUser").classList.remove("show");
    cancelResetPassword();
}

let _detailUserId = null;

function showResetPasswordForm() {
    document.getElementById("resetPwForm").style.display = 'block';
    document.getElementById("newPasswordInput").value = '';
    document.getElementById("newPasswordInput").focus();
}

function cancelResetPassword() {
    document.getElementById("resetPwForm").style.display = 'none';
    document.getElementById("newPasswordInput").value = '';
}

function submitResetPassword() {
    const newPw = document.getElementById("newPasswordInput").value.trim();
    if (!newPw) {
        showToast("warning", "Peringatan", "Password baru tidak boleh kosong.");
        return;
    }
    if (newPw.length < 4) {
        showToast("warning", "Peringatan", "Password minimal 4 karakter.");
        return;
    }
    if (!_detailUserId) {
        showToast("danger", "Error", "ID pengguna tidak ditemukan.");
        return;
    }

    // Ambil data user dari baris tabel agar bisa kirim lengkap
    const namaEl    = document.getElementById("detail-nama");
    const usernameEl = document.getElementById("detail-username");
    const roleEl    = document.getElementById("detail-role");

    const nama_lengkap = namaEl ? namaEl.textContent : '';
    const username     = usernameEl ? usernameEl.textContent.replace('@','') : '';
    const role         = roleEl && roleEl.textContent === 'Administrator' ? 'admin' : 'petugas';

    fetch('users.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action        : 'edit',
            id_user       : _detailUserId,
            nama_lengkap  : nama_lengkap,
            username      : username,
            role          : role,
            password      : newPw
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Update tampilan password di modal detail
            document.getElementById("detail-password-text").textContent = newPw;
            document.getElementById("detail-password-mask").style.display = 'inline';
            document.getElementById("detail-password-text").style.display = 'none';
            document.getElementById("icon-toggle-pass").className = 'fas fa-eye';
            cancelResetPassword();
            loadUsers();
            showToast("success", "Berhasil", "Password berhasil direset.");
        } else {
            showToast("danger", "Gagal", res.message || "Gagal mereset password.");
        }
    })
    .catch(() => showToast("danger", "Error", "Terjadi kesalahan server."));
}

function deleteUser(id) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus pengguna ini? Semua data log aktivitas dan relasi petugas juga akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#9E3A3A',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('users.php?ajax=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'delete', id_user: id })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    loadUsers();
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