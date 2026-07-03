<?php
require_once __DIR__ . '/../../includes/dashboard/header.php';

// Cek role untuk kontrol tampilan
$_area_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : 'petugas';
?>

<div class="toast-container" id="toastContainer"></div>
<div class="content-header">
    <div>
        <h1>Data Area Parkir</h1>
        <p>Kelola seluruh area parkir di kawasan Gelora Bung Karno.</p>
    </div>

    <?php if ($_area_role === 'admin'): ?>
    <button
        class="btn btn-primary"
        onclick="openAreaModal()"
    >
        <i class="fas fa-plus"></i>
        Tambah Area
    </button>
    <?php endif; ?>
</div>

<div class="table-card">
    <div class="table-toolbar">
        <div class="table-search">
            <input
                type="text"
                id="searchArea"
                placeholder="Cari nama area..."
            >
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th>No</th>
                <th>Nama Area</th>
                <th>Lokasi</th>
                <th>Kapasitas Mobil</th>
                <th>Kapasitas Motor</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            </thead>
            <tbody id="tableArea">
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper" id="paginationArea"></div>
</div>

<!-- Modal Tambah / Edit Area -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modalTitle">
                Tambah Area Parkir
            </h3>

            <button
                class="btn-close-modal"
                onclick="closeAreaModal()"
            >
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <form id="formTambahArea" onsubmit="return false;">
                <input
                    type="hidden"
                    id="id_area"
                    name="id_area"
                >

                <div class="form-group">
                    <label for="nama_area">Nama Area</label>
                    <input
                        type="text"
                        id="nama_area"
                        name="nama_area"
                        class="form-control"
                        placeholder="Contoh: Parkir Selatan"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="lokasi">Lokasi</label>

                    <input
                        type="text"
                        id="lokasi"
                        name="lokasi"
                        class="form-control"
                        placeholder="Contoh: Pintu Selatan GBK"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="kapasitas_mobil">Kapasitas Mobil</label>

                    <input
                        type="number"
                        id="kapasitas_mobil"
                        name="kapasitas_mobil"
                        class="form-control"
                        min="0"
                        value="0"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="kapasitas_motor">Kapasitas Motor</label>

                    <input
                        type="number"
                        id="kapasitas_motor"
                        name="kapasitas_motor"
                        class="form-control"
                        min="0"
                        value="0"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="status_area">Status</label>

                    <select
                        id="status_area"
                        name="status"
                        class="form-control"
                    >

                        <option value="Aktif">
                            Aktif
                        </option>

                        <option value="Nonaktif">
                            Nonaktif
                        </option>
                    </select>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button
                class="btn btn-secondary"
                onclick="closeAreaModal()"
            >
                Batal
            </button>

            <button
                type="button"
                class="btn btn-primary"
                onclick="submitArea()"
            >
                Simpan
            </button>
        </div>
    </div>
</div>

<script src="../../assets/js/area_parkir.js"></script>

<?php
require_once __DIR__ . '/../../includes/dashboard/footer.php';
?>